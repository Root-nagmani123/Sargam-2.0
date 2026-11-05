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
                $isOwner = $all || array_intersect($owners, array_keys($mine)) !== [];

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
     * (optionally one subject) their columns, participants and saved marks.
     *
     * @return array{courses:array<int,string>, coursePk:?int, subjects:array, subjectPk:?int, columns:list<array>, rows:array<int,array>, marks:array}
     */
    public function entrySheet(User $user, ?int $coursePk, ?int $subjectPk): array
    {
        $all = $this->entryColumns($user);
        $courses = collect($all)->mapWithKeys(fn ($c) => [$c['course_pk'] => $c['course']])
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $coursePk = isset($courses[$coursePk]) ? $coursePk : array_key_first($courses);

        $courseColumns = array_values(array_filter($all, fn ($c) => $c['course_pk'] === $coursePk));
        $subjects = $this->groupBySubject($courseColumns);
        $subjectPk = isset($subjects[$subjectPk]) ? $subjectPk : null;
        $columns = $subjectPk ? array_values($subjects[$subjectPk]['columns']) : $courseColumns;

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

        return [
            'courses' => $courses,
            'coursePk' => $coursePk,
            'subjects' => $subjectPk ? [$subjectPk => $subjects[$subjectPk]] : $subjects,
            'allSubjects' => $subjects,
            'subjectPk' => $subjectPk,
            'columns' => $columns,
            'rows' => $rows,
            'marks' => $this->marks(array_values(array_unique(array_column($columns, 'drive_id')))),
        ];
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
            'subject_pk' => (int) $map->subject_master_pk,
            'subject' => trim((string) ($map->subject->subject_name ?? '')),
            'subject_code' => trim((string) ($map->subject->sub_short_name ?? '')),
            'component_pk' => (int) $map->component_master_pk,
            'component' => trim((string) ($map->component->component_name ?? '')),
            'max' => (float) $map->max_marks,
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
     * Approved cells are locked and their input is ignored.
     *
     * @param  list<array<string, mixed>>  $columns  entryColumns() the page showed
     * @return array{saved:int, approved:int, errors:array<string, string>} errors keyed "key|student", or "_"
     */
    public function save(User $user, array $columns, array $input, bool $approve): array
    {
        $byKey = array_column($columns, null, 'key');
        $errors = [];
        $changes = [];

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
            }
        }
        if ($errors) {
            return ['saved' => 0, 'approved' => 0, 'errors' => $errors];
        }

        $facultyPk = array_key_first($this->facultyOf($user));
        $userId = $user->user_id;

        try {
            return DB::transaction(function () use ($columns, $changes, $approve, $facultyPk, $userId) {
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
                        if ($value === null) {
                            if ($mark) {
                                $mark->delete();
                                $existing->forget($student);
                                $saved++;
                            }

                            continue;
                        }
                        [$marks, $absent] = $value;
                        if ($mark && $mark->is_absent === $absent && $mark->marks === $marks) {
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
                        ])->save();
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
     *
     * @return array<int, array{course_pk:int, course:string, subjects:array, rows:array}> keyed by course pk
     */
    public function courseSheets(string $scope, ?int $coursePk = null): array
    {
        $drives = ExaminationDrive::with(['course', 'facultyMaps', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', $scope === 'archived' ? ExaminationDrive::STATUS_CLOSED : ExaminationDrive::STATUS_ACTIVE)
            ->when($coursePk, fn ($q) => $q->where('course_master_pk', $coursePk))
            ->whereHas('componentMaps')
            ->orderBy('id')
            ->get();

        $marks = $this->marks($drives->pluck('id')->all());

        return $drives->groupBy('course_master_pk')
            ->map(fn (Collection $courseDrives) => $this->courseSheet($courseDrives, $marks))
            ->filter()
            ->sortBy('course', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
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
                    $m = $marks[$key][$pk] ?? null;
                    $row['marks'][$key] = $m === null ? null : ($m->is_absent ? 'AB' : $m->marks);
                    $done = $done && $m?->isApproved();
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
