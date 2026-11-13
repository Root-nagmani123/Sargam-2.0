<?php

namespace App\Services\COE;

use App\Models\COE\EvaluationMark;
use App\Models\COE\ParticipantCode;
use App\Models\COE\RollNumberAllocation;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\FacultyMaster;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Marks behind COE → Result Management: faculty enter and approve them (Faculty
 * Result Entry), COE compiles them per course × subject (Result Compilation).
 *
 * Where everything comes from:
 *   drive / subject / component / max marks  examination_drives + examination_drive_component_maps
 *   who evaluates                            the subject's faculty (examination_drive_faculty_maps)
 *                                            get every participant; a component's setup faculty and
 *                                            sub-coordinators get the roll numbers / Unique Participant
 *                                            Codes assigned to them on Complete Setup (faculty_codes,
 *                                            faculty_unique_codes), or everyone when none were
 *   participants                             coe_roll_numbers + coe_participant_codes of the course
 *   marks                                    coe_evaluation_marks
 *
 * A column is one component of one subject on one drive, keyed "drive:subject:component".
 */
class ResultMarksService
{
    /** Longest faculty remark a mark may carry. */
    public const REMARKS_MAX = 500;

    /** @var array<string, array<int, array>> */
    private array $participantCache = [];

    /* ───────────── Participants ───────────── */

    /**
     * The drive course's participants, keyed by student_master.pk, in OT-code order.
     * Same batch rule as the drive's Select Roll Number / Unique Codes lists: the
     * batch matching the drive's academic session, else the course's latest one.
     *
     * @return array<int, array{student:int, code:string, roll:?string, unique:?string, name:string}>
     */
    public function participants(ExaminationDrive $drive): array
    {
        $key = $drive->course_master_pk.':'.$drive->academic_session;

        return $this->participantCache[$key] ??= $this->loadParticipants((int) $drive->course_master_pk, (int) $drive->academic_session);
    }

    private function loadParticipants(int $coursePk, int $session): array
    {
        $name = DB::raw("COALESCE(NULLIF(TRIM(s.display_name), ''), TRIM(CONCAT_WS(' ', s.first_name, s.last_name))) as name");
        $rows = [];

        $allocations = RollNumberAllocation::where('course_master_pk', $coursePk);
        $allocation = (clone $allocations)->where('batch', $session)->first()
            ?? $allocations->orderByDesc('batch')->first();
        if ($allocation) {
            DB::table('coe_roll_numbers as r')
                ->join('student_master as s', 's.pk', '=', 'r.student_master_pk')
                ->where('r.roll_number_allocation_pk', $allocation->pk)
                ->get(['r.student_master_pk', 'r.roll_number', $name])
                ->each(function ($r) use (&$rows) {
                    $rows[(int) $r->student_master_pk] = [
                        'student' => (int) $r->student_master_pk,
                        'roll' => trim($r->roll_number),
                        'unique' => null,
                        'name' => (string) $r->name,
                    ];
                });
        }

        $codes = ParticipantCode::where('course_master_pk', $coursePk);
        $batch = (clone $codes)->where('batch', $session)->exists() ? $session : (clone $codes)->max('batch');
        if ($batch !== null) {
            DB::table('coe_participant_codes as p')
                ->join('student_master as s', 's.pk', '=', 'p.student_master_pk')
                ->where('p.course_master_pk', $coursePk)
                ->where('p.batch', $batch)
                ->get(['p.student_master_pk', 'p.unique_code', $name])
                ->each(function ($r) use (&$rows) {
                    $pk = (int) $r->student_master_pk;
                    $rows[$pk] = ($rows[$pk] ?? ['student' => $pk, 'roll' => null, 'name' => (string) $r->name])
                        + ['unique' => null];
                    $rows[$pk]['unique'] = trim($r->unique_code);
                });
        }

        foreach ($rows as &$row) {
            // The Unique Participant Code stands in for the OT during evaluation.
            $row['code'] = $row['unique'] ?? $row['roll'];
        }
        unset($row);

        uasort($rows, fn ($a, $b) => self::compareOtCodes($a['code'], $b['code']));

        return $rows;
    }

    /** Same ordering as the drive's code lists: A2 < A10, FC101-002 < FC101-010. */
    public static function compareOtCodes(string $a, string $b): int
    {
        $split = fn (string $c) => preg_match('/^(\D*)(\d+)(.*)$/', $c, $m) ? [strtoupper($m[1]), (int) $m[2], $m[3]] : [strtoupper($c), 0, ''];
        [$pa, $pb] = [$split($a), $split($b)];

        return [$pa[0], $pa[1]] <=> [$pb[0], $pb[1]] ?: strnatcmp($pa[2], $pb[2]);
    }

    /* ───────────── Who evaluates what ───────────── */

    /**
     * The user's faculty_master rows as [pk => trimmed full name]. The login ->
     * faculty link is faculty_master.employee_master_pk = user_credentials.user_id.
     *
     * @return array<int, string>
     */
    public function facultyOf(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return FacultyMaster::where('employee_master_pk', $user->user_id)
            ->pluck('full_name', 'pk')
            ->map(fn ($name) => trim((string) $name))
            ->all();
    }

    /**
     * Super Admin, and anyone who is neither a faculty row nor in the Faculty role
     * (COE staff), may enter every column. Same rule as the drive's subject tabs.
     */
    public function seesAll(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $this->facultyOf($user) === [] && ! $user->hasRole('Faculty');
    }

    /**
     * Component evaluators of one participant: the setup faculty and sub-coordinators
     * who have this participant's roll number / unique code, or no codes at all.
     *
     * @return string[]
     */
    public function evaluatorNames(ExaminationDriveComponentMap $map, array $participant): array
    {
        $names = [];
        foreach ($map->subCoordinatorNames() as $name) {
            $codes = $this->assignedCodes($map, $name);
            if ($codes === [] || in_array($participant['roll'], $codes, true) || in_array($participant['unique'], $codes, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** @return string[] */
    private function assignedCodes(ExaminationDriveComponentMap $map, string $name): array
    {
        $codes = [];
        foreach ([$map->faculty_codes ?? [], $map->faculty_unique_codes ?? []] as $assigned) {
            // A list picked from the course's codes; a single free-text code on older rows.
            $codes = array_merge($codes, (array) ($assigned[$name] ?? []));
        }

        return array_values(array_filter(array_map(fn ($c) => trim((string) $c), $codes), 'strlen'));
    }

    /** Faculty pks mapped to the map's subject on its drive (examination_drive_faculty_maps). */
    private function subjectFacultyPks(ExaminationDrive $drive, int $subjectPk): array
    {
        return $drive->facultyMaps->where('subject_master_pk', $subjectPk)->pluck('faculty_master_pk')->map(fn ($pk) => (int) $pk)->all();
    }

    /**
     * Drives open for marks entry: released (Active, not Draft or Closed) and
     * approved by the Course Coordinator — the same gate as question-paper upload.
     */
    private function entryDrives(?int $coursePk = null): Collection
    {
        return ExaminationDrive::with(['course', 'facultyMaps', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', ExaminationDrive::STATUS_ACTIVE)
            ->where('cc_status', ExaminationDrive::CC_APPROVED)
            ->when($coursePk, fn ($q) => $q->where('course_master_pk', $coursePk))
            ->orderBy('id')
            ->get();
    }

    /**
     * Marks exist only after the exam: a component counts from its exam end date
     * (else its exam date, else the drive's end date), that day included.
     */
    private function examOver(ExaminationDrive $drive, ExaminationDriveComponentMap $map): bool
    {
        $end = $map->exam_end_date ?? $map->exam_date ?? $drive->end_date;

        return $end === null || $end->lte(today());
    }

    /**
     * The columns $user may fill, each with the student pks they may fill it for
     * and, per student, who evaluates them (the sheet's Tutor Name).
     *
     * @return list<array<string, mixed>>
     */
    public function entryColumns(User $user, ?int $coursePk = null): array
    {
        $all = $this->seesAll($user);
        $mine = $this->facultyOf($user);
        $columns = [];

        foreach ($this->entryDrives($coursePk) as $drive) {
            $participants = $this->participants($drive);
            foreach ($drive->componentMaps->sortBy('id') as $map) {
                if (! $this->examOver($drive, $map)) {
                    continue;
                }
                $owners = $this->subjectFacultyPks($drive, (int) $map->subject_master_pk);
                // The subject's faculty see every OT of the component, unless they are given
                // OT codes on it themselves: then, like any evaluator, only those OTs.
                $hasOwnCodes = array_filter(
                    array_intersect($map->subCoordinatorNames(), $mine),
                    fn ($name) => $this->assignedCodes($map, $name) !== []
                ) !== [];
                $isOwner = $all || (array_intersect($owners, array_keys($mine)) !== [] && ! $hasOwnCodes);

                $students = [];
                $tutors = [];
                foreach ($participants as $pk => $p) {
                    $evaluators = $this->evaluatorNames($map, $p);
                    if ($isOwner || array_intersect($evaluators, $mine) !== []) {
                        $students[] = $pk;
                        $tutors[$pk] = $evaluators;
                    }
                }
                if (! $isOwner && $students === []) {
                    continue;
                }

                $columns[] = $this->column($drive, $map) + ['students' => $students, 'tutors' => $tutors];
            }
        }

        return $columns;
    }

    /**
     * The faculty Result Entry sheet: the user's courses, and on the chosen one
     * (optionally one subject, and one component of it) their columns, participants
     * and saved marks. $componentKey is a column key; one not on the sheet is ignored.
     *
     * @return array{courses:array<int,string>, coursePk:?int, subjects:array, subjectPk:?int, components:array<string,string>, componentKey:?string, columns:list<array>, rows:array<int,array>, marks:array}
     */
    public function entrySheet(User $user, ?int $coursePk, ?int $subjectPk, ?string $componentKey = null): array
    {
        $all = $this->entryColumns($user);
        $courses = collect($all)->mapWithKeys(fn ($c) => [$c['course_pk'] => $c['course']])
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $coursePk = isset($courses[$coursePk]) ? $coursePk : array_key_first($courses);

        $courseColumns = array_values(array_filter($all, fn ($c) => $c['course_pk'] === $coursePk));
        $subjects = $this->groupBySubject($courseColumns);
        $subjectPk = isset($subjects[$subjectPk]) ? $subjectPk : null;
        $columns = $subjectPk ? array_values($subjects[$subjectPk]['columns']) : $courseColumns;

        // Component filter: the columns left by Course / Subject, named as the table names them.
        $components = [];
        foreach ($columns as $col) {
            $label = $subjects[$col['subject_pk']]['columns'][$col['key']]['label'] ?? $col['component'];
            $components[$col['key']] = $subjectPk ? $label : $col['subject'].' — '.$label;
        }
        $componentKey = isset($components[$componentKey]) ? $componentKey : null;
        if ($componentKey !== null) {
            $columns = array_values(array_filter($columns, fn ($c) => $c['key'] === $componentKey));
        }

        $participants = [];
        foreach (ExaminationDrive::whereIn('id', array_unique(array_column($columns, 'drive_id')))->get() as $drive) {
            $participants += $this->participants($drive);
        }

        $rows = [];
        foreach ($columns as $col) {
            foreach ($col['students'] as $pk) {
                $rows[$pk] ??= $participants[$pk] + ['tutors' => []];
                $rows[$pk]['tutors'] = array_values(array_unique(array_merge($rows[$pk]['tutors'], $col['tutors'][$pk] ?? [])));
            }
        }
        uasort($rows, fn ($a, $b) => self::compareOtCodes($a['code'], $b['code']));

        $marks = $this->marks(array_values(array_unique(array_column($columns, 'drive_id'))));
        $entries = $this->entries($columns, $subjects, $rows, $marks);

        return [
            'courses' => $courses,
            'coursePk' => $coursePk,
            'subjects' => $subjectPk ? [$subjectPk => $subjects[$subjectPk]] : $subjects,
            'allSubjects' => $subjects,
            'subjectPk' => $subjectPk,
            'components' => $components,
            'componentKey' => $componentKey,
            'columns' => $columns,
            'rows' => $rows,
            'marks' => $marks,
            'entries' => $entries,
            'pendingCount' => count(array_filter($entries, fn ($e) => ! $e['mark']?->isApproved())),
            'completedCount' => count(array_filter($entries, fn ($e) => (bool) $e['mark']?->isApproved())),
        ];
    }

    /**
     * The sheet as Evaluation & Marks Entry lists it: one entry per participant ×
     * column, in OT-code order and, within one OT, in column order.
     *
     * @return list<array{key:string, student:int, code:string, course:string, subject:string, component:string, max:float, mark:?EvaluationMark}>
     */
    private function entries(array $columns, array $subjects, array $rows, array $marks): array
    {
        $assigned = array_map(fn ($col) => array_flip($col['students']), $columns);
        $entries = [];
        foreach (array_keys($rows) as $pk) {
            foreach ($columns as $i => $col) {
                if (! isset($assigned[$i][$pk])) {
                    continue;
                }
                $entries[] = [
                    'key' => $col['key'],
                    'student' => $pk,
                    'code' => $rows[$pk]['code'],
                    'course' => $col['course_short'],
                    'subject' => $col['subject'],
                    'component' => $subjects[$col['subject_pk']]['columns'][$col['key']]['label'] ?? $col['component'],
                    'max' => $col['max'],
                    'mark' => $marks[$col['key']][$pk] ?? null,
                ];
            }
        }

        return $entries;
    }

    /**
     * Faculty remarks live in coe_evaluation_marks.remarks, added by DBA DDL. Until
     * the column exists the sheet hides Remarks and saves ignore them.
     */
    public static function remarksEnabled(): bool
    {
        static $enabled;

        return $enabled ??= Schema::hasColumn('coe_evaluation_marks', 'remarks');
    }

    /** @return array<string, mixed> */
    private function column(ExaminationDrive $drive, ExaminationDriveComponentMap $map): array
    {
        return [
            'key' => $drive->id.':'.$map->subject_master_pk.':'.$map->component_master_pk,
            'drive_id' => (int) $drive->id,
            'drive' => filled($drive->drive_name) ? trim($drive->drive_name) : 'Examination Drive #'.$drive->id,
            'course_pk' => (int) $drive->course_master_pk,
            'course' => trim((string) ($drive->course->course_name ?? '')),
            'course_short' => trim((string) ($drive->course->couse_short_name ?? '')) ?: trim((string) ($drive->course->course_name ?? '')),
            'subject_pk' => (int) $map->subject_master_pk,
            'subject' => trim((string) ($map->subject->subject_name ?? '')),
            'subject_code' => trim((string) ($map->subject->sub_short_name ?? '')),
            'component_pk' => (int) $map->component_master_pk,
            'component' => trim((string) ($map->component->component_name ?? '')),
            'max' => (float) $map->max_marks,
            'passing' => $map->passing_marks === null ? null : (float) $map->passing_marks,
            // Why nobody may be entering it yet (Result Compilation's Action column).
            'open' => $drive->status === ExaminationDrive::STATUS_ACTIVE && $drive->cc_status === ExaminationDrive::CC_APPROVED,
            'has_faculty' => $this->subjectFacultyPks($drive, (int) $map->subject_master_pk) !== [] || $map->subCoordinatorNames() !== [],
        ];
    }

    /**
     * Columns grouped by subject for a two-row header, each labelled with its
     * component, plus the drive when a subject has the same component twice.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return array<int, array{pk:int, name:string, code:string, max:float, columns:array<string, array>}>
     */
    public function groupBySubject(array $columns): array
    {
        $subjects = [];
        foreach ($columns as $col) {
            $subjects[$col['subject_pk']] ??= [
                'pk' => $col['subject_pk'], 'name' => $col['subject'], 'code' => $col['subject_code'], 'max' => 0.0, 'columns' => [],
            ];
            $subjects[$col['subject_pk']]['columns'][$col['key']] = $col;
            $subjects[$col['subject_pk']]['max'] += $col['max'];
        }

        foreach ($subjects as &$s) {
            $counts = array_count_values(array_column($s['columns'], 'component'));
            foreach ($s['columns'] as &$col) {
                $col['label'] = $counts[$col['component']] > 1 ? $col['component'].' · '.$col['drive'] : $col['component'];
            }
            unset($col);
        }
        unset($s);

        return $subjects;
    }

    /* ───────────── Marks ───────────── */

    /** @return array<string, array<int, EvaluationMark>> column key => student pk => mark */
    public function marks(array $driveIds): array
    {
        $out = [];
        if ($driveIds === []) {
            return $out;
        }
        EvaluationMark::whereIn('examination_drive_id', $driveIds)->get()
            ->each(function (EvaluationMark $m) use (&$out) {
                $out[$m->columnKey()][(int) $m->student_master_pk] = $m;
            });

        return $out;
    }

    /**
     * Save marks typed on the faculty sheet; with $approve, then approve every
     * cell of $columns (all must be filled). All or nothing: any error writes nothing.
     *
     * $input is column key => student pk => '' (clear) | number | 'AB' (absent).
     * $remarks is the same shape with the cell's remarks; a cell left out of it keeps
     * the remarks it has. Approved cells are locked and their input is ignored.
     *
     * @param  list<array<string, mixed>>  $columns  entryColumns() the page showed
     * @return array{saved:int, approved:int, errors:array<string, string>} errors keyed "key|student", or "_"
     */
    public function save(User $user, array $columns, array $input, bool $approve, array $remarks = []): array
    {
        $byKey = array_column($columns, null, 'key');
        $withRemarks = self::remarksEnabled();
        $errors = [];
        $changes = [];
        $notes = [];

        foreach ($input as $key => $cells) {
            if (! isset($byKey[$key]) || ! is_array($cells)) {
                return ['saved' => 0, 'approved' => 0, 'errors' => ['_' => 'You are not assigned to some of these components. Reload the page.']];
            }
            $col = $byKey[$key];
            foreach ($cells as $student => $raw) {
                if (! in_array((int) $student, $col['students'], true)) {
                    return ['saved' => 0, 'approved' => 0, 'errors' => ['_' => 'You are not assigned to some of these participants. Reload the page.']];
                }
                $value = $this->parseMark($raw, $col['max']);
                if (is_string($value)) {
                    $errors[$key.'|'.$student] = $value;

                    continue;
                }
                $changes[$key][(int) $student] = $value;

                if ($withRemarks && is_array($remarks[$key] ?? null) && array_key_exists($student, $remarks[$key])) {
                    $note = trim((string) $remarks[$key][$student]);
                    if (mb_strlen($note) > self::REMARKS_MAX) {
                        $errors[$key.'|'.$student] = 'Remarks can be at most '.self::REMARKS_MAX.' characters.';

                        continue;
                    }
                    $notes[$key][(int) $student] = $note === '' ? null : $note;
                }
            }
        }
        if ($errors) {
            return ['saved' => 0, 'approved' => 0, 'errors' => $errors];
        }

        $facultyPk = array_key_first($this->facultyOf($user));
        $userId = $user->user_id;

        try {
            return DB::transaction(function () use ($columns, $changes, $notes, $withRemarks, $approve, $facultyPk, $userId) {
                $saved = 0;
                $approved = 0;
                $empty = 0;
                $now = now();

                foreach ($columns as $col) {
                    $existing = $this->columnQuery($col)->whereIn('student_master_pk', $col['students'])
                        ->lockForUpdate()->get()->keyBy(fn ($m) => (int) $m->student_master_pk);

                    foreach ($changes[$col['key']] ?? [] as $student => $value) {
                        $mark = $existing[$student] ?? null;
                        if ($mark?->isApproved()) {
                            continue;
                        }
                        $note = array_key_exists($student, $notes[$col['key']] ?? [])
                            ? $notes[$col['key']][$student]
                            : ($withRemarks ? $mark?->remarks : null);
                        // A cell with no marks is kept only while it still has remarks.
                        if ($value === null && $note === null) {
                            if ($mark) {
                                $mark->delete();
                                $existing->forget($student);
                                $saved++;
                            }

                            continue;
                        }
                        [$marks, $absent] = $value ?? [null, false];
                        if ($mark && $mark->is_absent === $absent && $mark->marks === $marks && (! $withRemarks || $mark->remarks === $note)) {
                            continue;
                        }
                        $mark ??= new EvaluationMark([
                            'examination_drive_id' => $col['drive_id'],
                            'subject_master_pk' => $col['subject_pk'],
                            'component_master_pk' => $col['component_pk'],
                            'student_master_pk' => $student,
                            'created_by' => $userId,
                            'created_date' => $now,
                        ]);
                        $mark->fill([
                            'marks' => $marks,
                            'is_absent' => $absent,
                            'evaluated_by_faculty_pk' => $facultyPk,
                            'modified_by' => $userId,
                            'modified_date' => $now,
                        ] + ($withRemarks ? ['remarks' => $note] : []))->save();
                        $existing[$student] = $mark;
                        $saved++;
                    }

                    if ($approve) {
                        $empty += count(array_filter($col['students'], fn ($s) => ! ($existing[$s] ?? null)?->isFilled()));
                    }
                }

                if ($approve) {
                    if ($empty > 0) {
                        // Throwing rolls the saves back too: approval is all or nothing.
                        throw new ResultMarksException($empty === 1
                            ? '1 mark is still empty. Enter it (or AB for absent) before approving.'
                            : "{$empty} marks are still empty. Enter them (or AB for absent) before approving.");
                    }
                    foreach ($columns as $col) {
                        $approved += $this->columnQuery($col)
                            ->whereIn('student_master_pk', $col['students'])
                            ->where('status', EvaluationMark::STATUS_DRAFT)
                            ->update([
                                'status' => EvaluationMark::STATUS_APPROVED,
                                'approved_by' => $userId,
                                'approved_at' => $now,
                                'modified_by' => $userId,
                                'modified_date' => $now,
                            ]);
                    }
                }

                return ['saved' => $saved, 'approved' => $approved, 'errors' => []];
            });
        } catch (ResultMarksException $e) {
            return ['saved' => 0, 'approved' => 0, 'errors' => ['_' => $e->getMessage()]];
        } catch (QueryException $e) {
            // Two people saving the same new cell at once: the unique key turns one away.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return ['saved' => 0, 'approved' => 0, 'errors' => ['_' => 'Someone else saved these marks just now. Reload the page and try again.']];
            }
            throw $e;
        }
    }

    private function columnQuery(array $col)
    {
        return EvaluationMark::where('examination_drive_id', $col['drive_id'])
            ->where('subject_master_pk', $col['subject_pk'])
            ->where('component_master_pk', $col['component_pk']);
    }

    /**
     * '' -> null (clear), 'AB' -> [null, true], a number 0..max with up to two
     * decimals -> [marks, false]; anything else -> the error message.
     *
     * @return array{0:?float, 1:bool}|string|null
     */
    private function parseMark(mixed $raw, float $max): array|string|null
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (strcasecmp($raw, 'AB') === 0) {
            return [null, true];
        }
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $raw) || (float) $raw > $max) {
            return 'Enter marks from 0 to '.self::number($max).' (up to 2 decimals), or AB for absent.';
        }

        return [(float) $raw, false];
    }

    public static function number(?float $v): string
    {
        return $v === null ? '-' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    /* ───────────── Result Compilation ───────────── */

    /**
     * One sheet per course with drives in $scope: active = Active drives,
     * archived = Closed drives (Draft drives are not released yet). A participant's
     * subject is evaluated once every component of it, on every drive, is approved.
     * Only approved marks are carried: draft marks are blank and left out of totals.
     *
     * @return array<int, array{course_pk:int, course:string, subjects:array, rows:array}> keyed by course pk
     */
    public function courseSheets(string $scope, ?int $coursePk = null): array
    {
        $drives = $this->scopeDrives($scope, $coursePk);
        $marks = $this->marks($drives->pluck('id')->all());

        return $drives->groupBy('course_master_pk')
            ->map(fn (Collection $courseDrives) => $this->courseSheet($courseDrives, $marks))
            ->filter()
            ->sortBy('course', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /** active = Active drives, archived = Closed drives (Draft drives are not released yet). */
    private function scopeDrives(string $scope, ?int $coursePk = null): Collection
    {
        return ExaminationDrive::with(['course', 'examinationType', 'facultyMaps.faculty', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', $scope === 'archived' ? ExaminationDrive::STATUS_CLOSED : ExaminationDrive::STATUS_ACTIVE)
            ->when($coursePk, fn ($q) => $q->where('course_master_pk', $coursePk))
            ->whereHas('componentMaps')
            ->orderBy('id')
            ->get();
    }

    /* ───────────── Merit & Ranking ───────────── */

    /** Qualifying papers: shown beside Grand Total, never added to it. Matched on subject name / short name. */
    public const QUALIFYING = [
        'ICT' => ['ict', 'information & communication technology', 'information and communication technology'],
        'Hindi' => ['hindi'],
    ];

    /** A drive of this examination type (Examination Type Master) is the Director Assessment (B). */
    public static function isDirectorAssessment(ExaminationDrive $drive): bool
    {
        return (bool) preg_match('/director/i', (string) ($drive->examinationType->exam_type_name ?? ''));
    }

    /** 'ICT' / 'Hindi' when the column's subject is a qualifying paper, else null. */
    public static function qualifyingPaper(array $col): ?string
    {
        $names = [mb_strtolower(trim($col['subject'])), mb_strtolower(trim($col['subject_code']))];
        foreach (self::QUALIFYING as $paper => $matches) {
            if (array_intersect($names, $matches) !== []) {
                return $paper;
            }
        }

        return null;
    }

    /**
     * The Merit & Ranking subject columns (as the Figma), in order: label => [subject
     * names / short names it matches, max marks shown until a drive holds it]. A subject
     * matching none of them gets a column of its own after these.
     */
    public const MERIT_SUBJECTS = [
        'Pub. Admin.' => [['public administration', 'pub. admin.', 'pa'], 90],
        'Basic Economics' => [['basic economics', 'economics', 'eco'], 90],
        'Law' => [['law'], 90],
        'Political Concept & Constitution of India' => [['political concept & constitution of india', 'political concepts and constitution of india', 'psc'], 60],
        'Indian History & Culture' => [['indian history & culture', 'indian history and culture', 'history'], 60],
        'Management & Behavioural Science' => [['management & behavioural science', 'management and behavioural science', 'management'], 60],
    ];

    /** Max marks shown for Director Assessment / a qualifying paper until a drive holds it. */
    public const DIRECTOR_MAX = 172.5;

    public const QUALIFYING_MAX = 50.0;

    /** Active Subject Max Marks rows with their subject. */
    private function subjectMaxMarks(): Collection
    {
        return DB::table('subject_max_marks_master as mm')
            ->join('subject_master as s', 's.pk', '=', 'mm.subject_master_pk')
            ->where('mm.active_inactive', 1)
            ->get(['s.pk', 's.subject_name', 's.sub_short_name', 'mm.max_marks']);
    }

    /** The MERIT_SUBJECTS column a subject belongs to, else its own ('s:<pk>'). */
    private static function meritKey(string $name, string $code, int $pk): string
    {
        $names = [mb_strtolower(trim($name)), mb_strtolower(trim($code))];
        foreach (self::MERIT_SUBJECTS as $label => [$matches]) {
            if (array_intersect($names, $matches) !== []) {
                return 'f:'.$label;
            }
        }

        return 's:'.$pk;
    }

    /**
     * The marks a result counts: the SOP moderated marks where Marks Moderation saved
     * them, else the approved marks. Absent counts 0.
     */
    public static function resultMarks(EvaluationMark $m): float
    {
        if ($m->is_absent) {
            return 0.0;
        }

        return self::moderationEnabled() && $m->moderated_marks !== null ? (float) $m->moderated_marks : (float) $m->marks;
    }

    /**
     * Merit & Ranking in $scope (as courseSheets()) of one course, or of every course
     * ($coursePk null = All; each ranked within its own course).
     *
     * Every component whose exam is over counts, split three ways:
     *   B          components on a Director Assessment drive (isDirectorAssessment())
     *   ICT/Hindi  qualifying papers (qualifyingPaper()), shown but never added up
     *   A          the MERIT_SUBJECTS columns (always shown, '-' until held), then any
     *              other subject held; Total (A) is their sum
     * Grand Total = A + B. Marks are resultMarks(). Header max marks: what the drive
     * maps, else Subject Max Marks, else the Figma's (MERIT_SUBJECTS, DIRECTOR_MAX,
     * QUALIFYING_MAX); Total (A) / Grand Total are out of the shown columns.
     *
     * An OT is ranked once every one of those marks is approved; the rest are only
     * counted ($pending). Equal Grand Totals share a rank (1, 2, 2, 4), in OT-code order.
     * Pass: every qualifying paper at or above its passing marks (absent fails), and
     * Grand Total at or above the OT's service passing marks (Service Passing Marks).
     * With no passing marks set for the service, the result is null (not decided).
     *
     * Under All, a header max is null where the courses listed differ on it; each row
     * carries its course's own in row['max'] (subjects, a, b, max, qualifying).
     *
     * @return array{courses:array<int,string>, coursePk:?int, subjects:array, maxA:?float, maxB:?float, max:?float, qualifying:array<string,?float>, rows:list<array>, pending:int, noPassMarks:list<string>}
     */
    public function merit(string $scope, ?int $coursePk): array
    {
        $byCourse = $this->scopeDrives($scope)->groupBy('course_master_pk')
            ->filter(fn (Collection $drives) => $drives->contains(fn ($d) => $d->componentMaps->contains(fn ($m) => $this->examOver($d, $m))));
        $courses = $byCourse->map(fn (Collection $drives) => trim((string) ($drives->first()->course->course_name ?? '')))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $coursePk = isset($courses[$coursePk]) ? $coursePk : null;

        // Header defaults: Subject Max Marks, else the Figma's.
        $master = $this->subjectMaxMarks();
        $subjects = [];
        foreach (self::MERIT_SUBJECTS as $label => [, $max]) {
            $subjects['f:'.$label] = ['name' => $label, 'max' => (float) $max, 'columns' => []];
        }
        $qualifyingMax = array_fill_keys(array_keys(self::QUALIFYING), self::QUALIFYING_MAX);
        foreach ($master as $s) {
            if ($paper = self::qualifyingPaper(['subject' => $s->subject_name, 'subject_code' => (string) $s->sub_short_name])) {
                $qualifyingMax[$paper] = (float) $s->max_marks;
            } elseif (isset($subjects[$key = self::meritKey($s->subject_name, (string) $s->sub_short_name, (int) $s->pk)])) {
                $subjects[$key]['max'] = (float) $s->max_marks;
            }
        }
        $defaultMax = array_map(fn ($s) => $s['max'], $subjects);
        $defaultQualifying = $qualifyingMax;

        $sheets = [];
        foreach ($coursePk ? [$coursePk] : array_keys($courses) as $pk) {
            $sheets[$pk] = $this->meritSheet($byCourse[$pk], $courses[$pk]);
        }
        foreach ($sheets as $sheet) {
            foreach ($sheet['subjects'] as $key => $s) {
                $subjects[$key] ??= ['name' => $s['name'], 'max' => $s['max'], 'columns' => []];
                if ($s['columns'] && $subjects[$key]['columns'] === []) {
                    $subjects[$key]['columns'] = $s['columns'];
                }
            }
        }

        // Each course's own max marks: what its drives map, else the header defaults.
        // The same subject can be out of 70 on one course and 140 on another (two
        // drives), so under All every row carries its course's maxes.
        $rows = [];
        $pending = 0;
        $noPassMarks = [];
        $perCourse = [];
        foreach ($sheets as $pk => $sheet) {
            $max = $defaultMax;
            foreach ($sheet['subjects'] as $key => $s) {
                $max[$key] = $s['max'];
            }
            $a = (float) array_sum($max);
            $b = $sheet['maxB'] ?? self::DIRECTOR_MAX;
            $q = array_map(fn ($m) => $m, $defaultQualifying);
            foreach ($sheet['qualifying'] as $paper => $m) {
                $q[$paper] = $m ?? $q[$paper];
            }
            $perCourse[$pk] = ['subjects' => $max, 'a' => $a, 'b' => $b, 'max' => $a + $b, 'qualifying' => $q];

            foreach ($sheet['rows'] as $row) {
                $rows[] = $row + ['max' => $perCourse[$pk]];
            }
            $pending += $sheet['pending'];
            $noPassMarks = array_merge($noPassMarks, $sheet['noPassMarks']);
        }

        // The header carries a max only where every course listed agrees on it.
        $same = function (array $values) {
            $values = array_values(array_unique(array_map(fn ($v) => (string) $v, $values)));

            return count($values) === 1 ? (float) $values[0] : null;
        };
        foreach ($subjects as $key => &$s) {
            $values = array_column(array_filter($perCourse, fn ($c) => isset($c['subjects'][$key])), 'subjects');
            $values = array_map(fn ($m) => $m[$key], $values);
            $s['max'] = $values === [] ? $s['max'] : $same($values);
        }
        unset($s);
        $pick = fn (string $field, $fallback) => $perCourse === [] ? $fallback : $same(array_column($perCourse, $field));
        $maxA = $pick('a', (float) array_sum($defaultMax));
        $maxB = $pick('b', self::DIRECTOR_MAX);
        $qualifyingMax = [];
        foreach ($defaultQualifying as $paper => $m) {
            $qualifyingMax[$paper] = $perCourse === [] ? $m : $same(array_map(fn ($c) => $c['qualifying'][$paper], $perCourse));
        }

        // Every row has every column: '-' where its course does not hold it.
        foreach ($rows as &$row) {
            $row['subjects'] = array_replace(array_map(fn ($s) => null, $subjects), $row['subjects']);
        }
        unset($row);
        if (! $coursePk) {
            usort($rows, fn ($a, $b) => strnatcasecmp($a['course'], $b['course']) ?: $a['rank'] <=> $b['rank'] ?: self::compareOtCodes($a['code'], $b['code']));
        }

        return [
            'courses' => $courses,
            'coursePk' => $coursePk,
            'subjects' => $subjects,
            'maxA' => $maxA,
            'maxB' => $maxB,
            'max' => $maxA !== null && $maxB !== null ? $maxA + $maxB : $pick('max', null),
            'qualifying' => $qualifyingMax,
            'rows' => $rows,
            'pending' => $pending,
            'noPassMarks' => array_values(array_unique($noPassMarks)),
        ];
    }

    /**
     * One course of merit(): its held subject columns (keyed as meritKey()), Director
     * Assessment and qualifying max marks (null when not held), and its ranked rows.
     */
    private function meritSheet(Collection $drives, string $course): array
    {
        $subjects = [];
        $partB = [];
        $qualifying = array_fill_keys(array_keys(self::QUALIFYING), []);
        $participants = [];
        foreach ($drives as $drive) {
            $held = $drive->componentMaps->sortBy('id')->filter(fn ($map) => $this->examOver($drive, $map));
            foreach ($held as $map) {
                $col = $this->column($drive, $map);
                if (self::isDirectorAssessment($drive)) {
                    $partB[$col['key']] = $col;
                } elseif (($paper = self::qualifyingPaper($col)) !== null) {
                    $qualifying[$paper][$col['key']] = $col;
                } else {
                    $key = self::meritKey($col['subject'], $col['subject_code'], $col['subject_pk']);
                    $subjects[$key] ??= ['name' => str_starts_with($key, 'f:') ? substr($key, 2) : $col['subject'], 'max' => 0.0, 'columns' => []];
                    $subjects[$key]['columns'][$col['key']] = $col;
                    $subjects[$key]['max'] += $col['max'];
                }
            }
            if ($held->isNotEmpty()) {
                $participants += $this->participants($drive);
            }
        }
        $marks = $this->marks($drives->pluck('id')->all());

        $students = DB::table('student_master as s')
            ->leftJoin('service_master as sv', 'sv.pk', '=', 's.service_master_pk')
            ->leftJoin('service_passing_marks_master as sp', fn ($j) => $j->on('sp.service_master_pk', '=', 's.service_master_pk')->where('sp.active_inactive', 1))
            ->whereIn('s.pk', array_keys($participants))
            ->selectRaw("s.pk, COALESCE(NULLIF(TRIM(sv.service_short_name), ''), TRIM(sv.service_name)) as service, sp.passing_marks")
            ->get()->keyBy('pk');

        $sum = fn (array $keys, array $approved) => round(array_sum(array_map(fn ($key) => self::resultMarks($approved[$key]), $keys)), 2);
        $rows = [];
        $noPassMarks = [];
        foreach ($participants as $pk => $p) {
            $approved = [];
            foreach (array_merge(array_keys($partB), ...array_map('array_keys', array_values(array_column($subjects, 'columns'))), ...array_map('array_keys', array_values($qualifying))) as $key) {
                $m = $marks[$key][$pk] ?? null;
                if (! $m?->isApproved()) {
                    continue 2;
                }
                $approved[$key] = $m;
            }

            $totals = array_map(fn ($s) => $sum(array_keys($s['columns']), $approved), $subjects);
            $totalA = round(array_sum($totals), 2);
            $director = $partB ? $sum(array_keys($partB), $approved) : null;
            $grand = round($totalA + ($director ?? 0), 2);

            $papers = [];
            $qualified = true;
            foreach ($qualifying as $paper => $cols) {
                $papers[$paper] = $cols ? $sum(array_keys($cols), $approved) : null;
                foreach ($cols as $key => $col) {
                    $m = $approved[$key];
                    $qualified = $qualified && ! $m->is_absent && ($col['passing'] === null || self::resultMarks($m) >= $col['passing']);
                }
            }

            $student = $students[$pk] ?? null;
            $passMarks = $student?->passing_marks === null ? null : (float) $student->passing_marks;
            if ($passMarks === null && $qualified) {
                $noPassMarks[] = (string) ($student?->service ?: 'No service');
            }
            $rows[] = $p + [
                'course' => $course,
                'service' => (string) ($student?->service ?? ''),
                'subjects' => $totals,
                'total_a' => $totalA,
                'director' => $director,
                'grand' => $grand,
                'qualifying' => $papers,
                // Per column key, for the marksheet (MarksheetService).
                'marks' => array_map(fn (EvaluationMark $m) => self::resultMarks($m), $approved),
                'result' => match (true) {
                    ! $qualified => 'Fail',
                    $passMarks === null => null,
                    default => $grand >= $passMarks ? 'Pass' : 'Fail',
                },
            ];
        }

        usort($rows, fn ($a, $b) => $b['grand'] <=> $a['grand'] ?: self::compareOtCodes($a['code'], $b['code']));
        foreach ($rows as $i => &$row) {
            $row['rank'] = $i > 0 && $rows[$i - 1]['grand'] === $row['grand'] ? $rows[$i - 1]['rank'] : $i + 1;
        }
        unset($row);

        return [
            'subjects' => $subjects,
            'partB' => $partB,
            'maxB' => $partB ? (float) array_sum(array_column($partB, 'max')) : null,
            'qualifying' => array_map(fn ($cols) => $cols ? (float) array_sum(array_column($cols, 'max')) : null, $qualifying),
            'rows' => $rows,
            'pending' => count($participants) - count($rows),
            'noPassMarks' => $noPassMarks,
        ];
    }

    /**
     * Merit & Ranking of every course in $scope, as the marksheets need it
     * (MarksheetService): each course's merit sheet — rows carry their mark per
     * column key — with the drives whose exams are over.
     *
     * @return array<int, array<string, mixed>> keyed by course pk
     */
    public function meritSheets(string $scope): array
    {
        $out = [];
        foreach ($this->scopeDrives($scope)->groupBy('course_master_pk') as $pk => $drives) {
            $held = $drives->filter(fn ($d) => $d->componentMaps->contains(fn ($m) => $this->examOver($d, $m)))->values();
            if ($held->isEmpty()) {
                continue;
            }
            $course = $held->first()->course;
            $name = trim((string) ($course->course_name ?? ''));
            $out[(int) $pk] = [
                'course_pk' => (int) $pk,
                'course' => $name,
                'course_short' => trim((string) ($course->couse_short_name ?? '')) ?: $name,
                'drives' => $held,
            ] + $this->meritSheet($drives, $name);
        }

        return $out;
    }

    private function courseSheet(Collection $drives, array $marks): ?array
    {
        $participants = [];
        $columns = [];
        foreach ($drives as $drive) {
            // Only drives with an exam already held bring their columns and participants.
            $held = $drive->componentMaps->sortBy('id')->filter(fn ($map) => $this->examOver($drive, $map));
            foreach ($held as $map) {
                $columns[] = $this->column($drive, $map);
            }
            if ($held->isNotEmpty()) {
                $participants += $this->participants($drive);
            }
        }
        // A subject reaches Result Compilation only once faculty have approved some of its marks.
        $subjects = array_filter($this->groupBySubject($columns), fn ($s) => collect(array_keys($s['columns']))
            ->contains(fn ($key) => collect($marks[$key] ?? [])->contains(fn (EvaluationMark $m) => $m->isApproved())));
        if ($subjects === []) {
            return null;
        }
        uasort($participants, fn ($a, $b) => self::compareOtCodes($a['code'], $b['code']));

        $rows = [];
        foreach ($participants as $pk => $p) {
            $row = $p + ['marks' => [], 'subjects' => [], 'pending' => []];
            foreach ($subjects as $spk => $s) {
                $total = 0.0;
                $done = true;
                foreach ($s['columns'] as $key => $col) {
                    // Only faculty-approved marks are results; a draft shows as not entered.
                    $m = ($marks[$key][$pk] ?? null)?->isApproved() ? $marks[$key][$pk] : null;
                    $row['marks'][$key] = $m === null ? null : ($m->is_absent ? 'AB' : $m->marks);
                    $done = $done && $m !== null;
                    $total += $m && ! $m->is_absent ? (float) $m->marks : 0;
                }
                $row['subjects'][$spk] = round($total, 2);
                if (! $done) {
                    $row['pending'][] = $spk;
                }
            }
            $rows[$pk] = $row;
        }

        $first = $drives->first();

        return [
            'course_pk' => (int) $first->course_master_pk,
            'course' => trim((string) ($first->course->course_name ?? '')),
            'subjects' => $subjects,
            'rows' => $rows,
        ];
    }

    /**
     * Result Compilation grid: one row per course × subject.
     *
     * @return list<array<string, mixed>>
     */
    public function compilation(string $scope): array
    {
        $out = [];
        foreach ($this->courseSheets($scope) as $sheet) {
            foreach ($sheet['subjects'] as $spk => $s) {
                $done = array_filter($sheet['rows'], fn ($r) => ! in_array($spk, $r['pending'], true));
                $out[] = [
                    'course_pk' => $sheet['course_pk'],
                    'course' => $sheet['course'],
                    'subject_pk' => $spk,
                    'subject' => $s['name'],
                    'code' => $s['code'],
                    'max' => $s['max'],
                    'approved' => count($done),
                    'pending' => count($sheet['rows']) - count($done),
                    'total' => count($sheet['rows']),
                    'blocker' => match (true) {
                        ! collect($s['columns'])->contains('open', true) => 'Waiting for Course Coordinator approval',
                        ! collect($s['columns'])->contains(fn ($c) => $c['open'] && $c['has_faculty']) => 'No faculty mapped to this subject',
                        default => null,
                    },
                    // Mean evaluated score as a share of the subject's maximum.
                    'average' => $done && $s['max'] > 0
                        ? round(array_sum(array_map(fn ($r) => $r['subjects'][$spk], $done)) / count($done) / $s['max'] * 100, 1)
                        : null,
                ];
            }
        }

        return $out;
    }

    /* ───────────── Result Records and Marks Moderation ───────────── */

    /**
     * Moderated marks live in coe_evaluation_marks.moderated_marks / moderated_by /
     * moderated_at, added by DBA DDL. Until they exist the screen shows the moderated
     * marks the SOP formula gives, but cannot save them.
     */
    public static function moderationEnabled(): bool
    {
        static $enabled;

        return $enabled ??= Schema::hasColumn('coe_evaluation_marks', 'moderated_marks');
    }

    /**
     * One row per participant × component in $scope (as courseSheets()), for every
     * component whose exam is over and that has a mark entered. The evaluator of an
     * OT is who the drive maps to them (the Result Entry sheet's Tutor Name): the
     * component's setup faculty / sub-coordinators holding their code, else the
     * subject's faculty. evaluated_by_faculty_pk is not used — it records who typed
     * the mark, which is often COE staff on someone's behalf.
     *
     * A component is moderated as a whole (MarksModeration): its moderated marks are
     * worked out only once every participant's mark is approved, since one pending
     * mark would move the means every other OT is moderated against. An OT no faculty
     * is assigned to (and not marked) does not hold the rest back: it is left out.
     *
     * status: unassigned | pending (not approved yet) | absent | not-moderated | moderated
     *
     * @return list<array<string, mixed>>
     */
    public function moderationSheet(string $scope): array
    {
        $drives = $this->scopeDrives($scope);
        $marks = $this->marks($drives->pluck('id')->all());
        $saved = self::moderationEnabled();
        $rows = [];

        foreach ($drives as $drive) {
            foreach ($drive->componentMaps->sortBy('id') as $map) {
                $col = $this->column($drive, $map);
                if (! $this->examOver($drive, $map) || empty($marks[$col['key']])) {
                    continue;
                }
                $subjectFaculty = $drive->facultyMaps->where('subject_master_pk', $col['subject_pk'])
                    ->map(fn ($f) => trim((string) ($f->faculty->full_name ?? '')))->filter()->unique()->values()->all();
                // As on Result Entry (entryColumns): the subject's faculty take every OT only
                // while they hold no OT codes on the component themselves.
                $ownersTakeAll = array_filter($subjectFaculty, fn ($name) => $this->assignedCodes($map, $name) === []) !== [];

                $colRows = [];
                $scores = [];
                $complete = true;
                foreach ($this->participants($drive) as $pk => $p) {
                    $m = $marks[$col['key']][$pk] ?? null;
                    $approved = (bool) $m?->isApproved();
                    $names = $this->evaluatorNames($map, $p) ?: ($ownersTakeAll ? $subjectFaculty : []);
                    // An OT no faculty can enter marks for is left out of the component's
                    // moderation instead of holding it back; once marked it counts like any other.
                    $unassigned = $names === [] && ! $approved;
                    $complete = $complete && ($approved || $unassigned);
                    $evaluator = implode(', ', $names) ?: 'Unassigned';
                    $moderated = $saved && $m?->moderated_marks !== null ? (float) $m->moderated_marks : null;
                    if ($approved && ! $m->is_absent) {
                        $scores[$pk] = ['marks' => (float) $m->marks, 'evaluator' => $evaluator];
                    }

                    $colRows[$pk] = [
                        'key' => $col['key'].'|'.$pk,
                        'mark_pk' => $m?->pk,
                        'code' => $p['code'],
                        'name' => $p['name'],
                        'drive' => $col['drive'],
                        'course' => $col['course'],
                        'subject' => $col['subject'],
                        'component' => $col['component'],
                        'max' => $col['max'],
                        'marks' => $m === null ? null : ($m->is_absent ? 'AB' : $m->marks),
                        'evaluator' => $evaluator,
                        'moderated' => $moderated,
                        'status' => match (true) {
                            $unassigned => 'unassigned',
                            ! $approved => 'pending',
                            $m->is_absent => 'absent',
                            $moderated !== null => 'moderated',
                            default => 'not-moderated',
                        },
                    ];
                }

                $calc = $complete && $scores ? MarksModeration::compute($scores, $col['max']) : null;
                foreach ($colRows as $pk => $row) {
                    $e = isset($scores[$pk]) ? $calc['evaluators'][$scores[$pk]['evaluator']] ?? null : null;
                    $rows[] = $row + [
                        // What the formula gives (shown on tick, saved by Moderate Marks).
                        'preview' => $calc['moderated'][$pk] ?? null,
                        'working' => $e === null ? null : [
                            'um' => $scores[$pk]['marks'],
                            'xj' => round($e['mean'], 2), 'sdj' => round($e['sd'], 2), 'nj' => $e['n'],
                            'mu' => round($calc['group']['mean'], 2), 'sdg' => round($calc['group']['sd'], 2), 'ng' => $calc['group']['n'],
                        ],
                        'waiting' => ! $complete,
                    ];
                }
            }
        }

        // The same component on several drives of a course/subject is told apart by
        // its drive name, as on the faculty Result Entry sheet.
        $drivesOf = [];
        foreach ($rows as $row) {
            $drivesOf[$row['course']."\0".$row['subject']."\0".$row['component']][strtok($row['key'], ':')] = true;
        }
        foreach ($rows as &$row) {
            if (count($drivesOf[$row['course']."\0".$row['subject']."\0".$row['component']]) > 1) {
                $row['component'] .= ' · '.$row['drive'];
            }
        }
        unset($row);

        usort($rows, fn ($a, $b) => [$a['course'], $a['subject'], $a['component']] <=> [$b['course'], $b['subject'], $b['component']]
            ?: self::compareOtCodes($a['code'], $b['code']));

        return $rows;
    }

    /**
     * Moderate the given rows ("drive:subject:component|student") on Active drives:
     * work out their SOP moderated marks and save them. All or nothing; a row already
     * moderated is skipped. Until moderationEnabled() the marks are only worked out
     * and returned (saved = false).
     *
     * @param  string[]  $keys
     * @return array{moderated:int, values:array<string, float>, saved:bool, error:?string}
     */
    public function moderate(User $user, array $keys): array
    {
        $rows = collect($this->moderationSheet('active'))->keyBy('key');
        $todo = [];
        $values = [];
        foreach (array_unique($keys) as $key) {
            $row = $rows[$key] ?? null;
            if ($row === null) {
                return ['moderated' => 0, 'values' => [], 'saved' => false, 'error' => 'Some of these marks are no longer listed. Reload the page.'];
            }
            if ($row['status'] === 'moderated') {
                continue;
            }
            if ($row['status'] !== 'not-moderated' || $row['preview'] === null) {
                return ['moderated' => 0, 'values' => [], 'saved' => false, 'error' => "{$row['code']} ({$row['component']}) cannot be moderated yet: every mark of the component must be approved first."];
            }
            $todo[$row['mark_pk']] = $row['preview'];
            $values[$key] = $row['preview'];
        }

        if (! self::moderationEnabled()) {
            return ['moderated' => count($values), 'values' => $values, 'saved' => false, 'error' => null];
        }

        $now = now();
        $moderated = DB::transaction(function () use ($todo, $user, $now) {
            $n = 0;
            foreach ($todo as $pk => $value) {
                $n += EvaluationMark::whereKey($pk)
                    ->where('status', EvaluationMark::STATUS_APPROVED)
                    ->whereNull('moderated_marks')
                    ->update([
                        'moderated_marks' => $value,
                        'moderated_by' => $user->user_id,
                        'moderated_at' => $now,
                    ]);
            }

            return $n;
        });

        return ['moderated' => $moderated, 'values' => $values, 'saved' => true, 'error' => null];
    }

    /* ───────────── Notify for Pending ───────────── */

    /**
     * Bell notification to the faculty who still owe marks on a course — optionally
     * one subject, optionally one participant: the subject's faculty plus the
     * component evaluators of each unapproved cell. Only drives open for entry
     * count; a failure to send is logged, never thrown.
     *
     * @return array{pending:int, notified:int, unreachable:int}
     */
    public function notifyPending(int $coursePk, ?int $subjectPk = null, ?int $studentPk = null): array
    {
        $drives = $this->entryDrives($coursePk);
        $marks = $this->marks($drives->pluck('id')->all());

        $pending = 0;
        $facultyPks = [];
        $names = [];
        $subjects = [];

        foreach ($drives as $drive) {
            foreach ($drive->componentMaps as $map) {
                if (($subjectPk && (int) $map->subject_master_pk !== $subjectPk) || ! $this->examOver($drive, $map)) {
                    continue;
                }
                $key = $drive->id.':'.$map->subject_master_pk.':'.$map->component_master_pk;
                foreach ($this->participants($drive) as $pk => $p) {
                    if (($studentPk && $pk !== $studentPk) || ($marks[$key][$pk] ?? null)?->isApproved()) {
                        continue;
                    }
                    $pending++;
                    $subjects[(int) $map->subject_master_pk] = trim((string) ($map->subject->subject_name ?? ''));
                    array_push($facultyPks, ...$this->subjectFacultyPks($drive, (int) $map->subject_master_pk));
                    array_push($names, ...$this->evaluatorNames($map, $p));
                }
            }
        }

        if ($pending === 0) {
            return ['pending' => 0, 'notified' => 0, 'unreachable' => 0];
        }

        $names = array_values(array_unique($names));
        if ($names) {
            $facultyPks = array_merge($facultyPks, FacultyMaster::whereIn(DB::raw('TRIM(full_name)'), $names)->pluck('pk')->all());
        }
        $facultyPks = array_values(array_unique(array_map('intval', $facultyPks)));

        $userIds = FacultyMaster::whereIn('pk', $facultyPks)
            ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
            ->pluck('employee_master_pk')
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();

        $notified = 0;
        if ($userIds) {
            $course = trim((string) ($drives->first()->course->course_name ?? ''));
            try {
                $notified = app(NotificationService::class)->createMultiple(
                    $userIds, 'coe_result_entry', 'FacultyResultEntry', $coursePk, 'Marks Pending',
                    'Marks are pending for '.implode(', ', array_filter($subjects))." on {$course}. Enter and approve them in Result Entry."
                );
            } catch (\Throwable $e) {
                Log::error('Result entry reminder failed', ['course_master_pk' => $coursePk, 'error' => $e->getMessage()]);
            }
        }

        return ['pending' => $pending, 'notified' => $notified, 'unreachable' => count($facultyPks) - count($userIds)];
    }
}
