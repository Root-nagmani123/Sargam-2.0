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
                ->orderBy('p.pk')
                ->get(['p.student_master_pk', 'p.subject_master_pk', 'p.unique_code', $name])
                ->each(function ($r) use (&$rows) {
                    $pk = (int) $r->student_master_pk;
                    $rows[$pk] = ($rows[$pk] ?? ['student' => $pk, 'roll' => null, 'name' => (string) $r->name])
                        + ['unique' => null, 'uniques' => []];
                    // One code per OT per subject (coe_participant_codes.subject_master_pk); 'unique'
                    // stays the OT's first one, for screens that show one code per OT.
                    $rows[$pk]['unique'] ??= trim($r->unique_code);
                    if ($r->subject_master_pk !== null) {
                        $rows[$pk]['uniques'][(int) $r->subject_master_pk] = trim($r->unique_code);
                    }
                });
        }

        foreach ($rows as &$row) {
            $row['uniques'] ??= [];
            // The Unique Participant Code stands in for the OT during evaluation.
            $row['code'] = $row['unique'] ?? $row['roll'];
        }
        unset($row);

        uasort($rows, fn ($a, $b) => self::compareOtCodes($a['code'], $b['code']));

        return $rows;
    }

    /**
     * The OT's Unique Participant Code for one subject (codes are per OT per subject), else
     * their roll number: what stands in for them while that subject is evaluated.
     */
    public static function codeFor(array $participant, int $subjectPk): string
    {
        return (string) ($participant['uniques'][$subjectPk] ?? $participant['roll'] ?? $participant['code']);
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
        // The OT's code on this component's subject (codes are per subject), not another subject's.
        $unique = $participant['uniques'][(int) $map->subject_master_pk] ?? null;
        foreach ($map->subCoordinatorNames() as $name) {
            $codes = $this->assignedCodes($map, $name);
            if ($codes === [] || in_array($participant['roll'], $codes, true) || ($unique !== null && in_array($unique, $codes, true))) {
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
     * Re-exam drives only when $withReexams (Result Entry and its "entry ready" bell; their
     * OTs narrowed by entryParticipants). Compilation, Merit, Moderation and the "Marks
     * Pending" reminder keep them out (ReexamService::excludeFrom).
     */
    public function entryDrives(?int $coursePk = null, bool $withReexams = false): Collection
    {
        $query = ExaminationDrive::with(['course', 'examinationType', 'facultyMaps.faculty', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', ExaminationDrive::STATUS_ACTIVE)
            ->where('cc_status', ExaminationDrive::CC_APPROVED)
            ->when($coursePk, fn ($q) => $q->where('course_master_pk', $coursePk));

        return ($withReexams ? $query : ReexamService::excludeFrom($query))
            ->orderBy('id')
            ->get();
    }

    /** @var array<int, array<int, list<int>>|null> drive id => its re-exam candidates per subject (null: not a re-exam) */
    private array $reexamCandidates = [];

    /**
     * The OTs marks are entered for on one subject of $drive: every participant on a
     * normal drive (unchanged); on a re-exam drive only that subject's re-exam OTs
     * (ReexamService::seatingBySubject, coe_reexam_candidates).
     *
     * @return array<int, array<string, mixed>>
     */
    private function entryParticipants(ExaminationDrive $drive, int $subjectPk): array
    {
        $participants = $this->participants($drive);
        $id = (int) $drive->id;
        if (! array_key_exists($id, $this->reexamCandidates)) {
            $this->reexamCandidates[$id] = ReexamService::seatingBySubject($id);
        }
        if ($this->reexamCandidates[$id] === null) {
            return $participants;
        }

        return array_intersect_key($participants, array_flip($this->reexamCandidates[$id][$subjectPk] ?? []));
    }

    /**
     * Marks exist only after the exam: a component counts from its exam end date
     * (else its exam date, else the drive's end date), that day included.
     */
    public function examOver(ExaminationDrive $drive, ExaminationDriveComponentMap $map): bool
    {
        // The slot that counts, to the minute — the CC's confirmed one, else the Tentative Exam
        // Range's times (ExaminationDriveComponentMap::slotEnd); else the end date (then the
        // drive's), which counts from that day.
        if ($map->slotEnd() !== null) {
            return $map->slotEnd()->lte(now());
        }
        $end = $map->exam_end_date ?? $map->exam_date ?? $drive->end_date;

        return $end === null || $end->lte(today());
    }

    /**
     * The Director's overall assessment: a component named "Director…" (Component
     * Master) on a Director Assessment drive. The Training Section enters it on
     * Evaluation & Marks (DirectorAssessmentService), never faculty on Result Entry.
     */
    public static function isDirectorComponent(ExaminationDrive $drive, ExaminationDriveComponentMap $map): bool
    {
        return self::isDirectorAssessment($drive)
            && (bool) preg_match('/director/i', (string) ($map->component->component_name ?? ''));
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

        // Re-exam drives too: only their re-exam OTs, per subject (entryParticipants).
        foreach ($this->entryDrives($coursePk, true) as $drive) {
            foreach ($drive->componentMaps->sortBy('id') as $map) {
                // The Director's overall assessment is the Training Section's, not faculty's.
                if (! $this->examOver($drive, $map) || self::isDirectorComponent($drive, $map)) {
                    continue;
                }
                $participants = $this->entryParticipants($drive, (int) $map->subject_master_pk);
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
     * The faculty Result Entry sheet: the user's courses, and on the chosen one (none = every
     * course; optionally one subject, and one component of it) their columns, participants
     * and saved marks. $componentKey is a column key; one not on the sheet is ignored.
     *
     * @return array{courses:array<int,string>, coursePk:?int, subjects:array, subjectPk:?int, components:array<string,string>, componentKey:?string, columns:list<array>, rows:array<int,array>, marks:array}
     */
    public function entrySheet(User $user, ?int $coursePk, ?int $subjectPk, ?string $componentKey = null): array
    {
        $all = $this->entryColumns($user);
        $courses = collect($all)->mapWithKeys(fn ($c) => [$c['course_pk'] => $c['course']])
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        // No course (or not one of the user's): every course, nothing pre-filtered.
        $coursePk = isset($courses[$coursePk]) ? $coursePk : null;

        $courseColumns = $coursePk === null ? $all : array_values(array_filter($all, fn ($c) => $c['course_pk'] === $coursePk));
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
                    'code' => self::codeFor($rows[$pk], $col['subject_pk']),
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
    public function column(ExaminationDrive $drive, ExaminationDriveComponentMap $map): array
    {
        return [
            'key' => $drive->id.':'.$map->subject_master_pk.':'.$map->component_master_pk,
            'drive_id' => (int) $drive->id,
            // Names as text: some were stored HTML-encoded, some twice ("&amp;#039;").
            'drive' => CourseParticipants::plainName($drive->drive_name) ?: 'Examination Drive #'.$drive->id,
            'course_pk' => (int) $drive->course_master_pk,
            'course' => CourseParticipants::plainName($drive->course->course_name ?? ''),
            'course_short' => CourseParticipants::plainName($drive->course->couse_short_name ?? '') ?: CourseParticipants::plainName($drive->course->course_name ?? ''),
            'subject_pk' => (int) $map->subject_master_pk,
            'subject' => CourseParticipants::plainName($map->subject->subject_name ?? ''),
            'subject_code' => CourseParticipants::plainName($map->subject->sub_short_name ?? ''),
            'component_pk' => (int) $map->component_master_pk,
            'component' => CourseParticipants::plainName($map->component->component_name ?? ''),
            'max' => (float) $map->max_marks,
            'passing' => $map->passing_marks === null ? null : (float) $map->passing_marks,
            // Why nobody may be entering it yet (Result Compilation's Action column).
            'open' => $drive->status === ExaminationDrive::STATUS_ACTIVE && $drive->cc_status === ExaminationDrive::CC_APPROVED,
            // The Training Section enters the Director's overall assessment: never short of a faculty.
            'has_faculty' => self::isDirectorComponent($drive, $map)
                || $this->subjectFacultyPks($drive, (int) $map->subject_master_pk) !== [] || $map->subCoordinatorNames() !== [],
            'director' => self::isDirectorComponent($drive, $map),
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
    public function courseSheets(string $scope, ?int $coursePk = null, ?int $driveId = null): array
    {
        $drives = $this->scopeDrives($scope, $coursePk, $driveId);
        $marks = $this->marks($drives->pluck('id')->all());

        return $drives->groupBy('course_master_pk')
            ->map(fn (Collection $courseDrives) => $this->courseSheet($courseDrives, $marks))
            ->filter()
            ->sortBy('course', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /**
     * active = Active drives, archived = Closed drives (Draft drives are not released yet).
     * Re-exam drives are left out (ReexamService): Compilation's main rows, Merit, Moderation
     * and the marksheets count the main drives only. Compilation adds its "(Re-exam)" rows
     * separately (reexamCompilationRows).
     */
    private function scopeDrives(string $scope, ?int $coursePk = null, ?int $driveId = null): Collection
    {
        return ReexamService::excludeFrom(ExaminationDrive::with(['course', 'examinationType', 'facultyMaps.faculty', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', $scope === 'archived' ? ExaminationDrive::STATUS_CLOSED : ExaminationDrive::STATUS_ACTIVE)
            ->when($coursePk, fn ($q) => $q->where('course_master_pk', $coursePk))
            ->when($driveId, fn ($q) => $q->where('id', $driveId))
            ->whereHas('componentMaps'))
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
     * A mark Merit & Ranking (and the marksheets) may count: approved, and moderated
     * by Marks Moderation. An absent mark is never moderated, so approval is enough.
     * Until moderationEnabled() (DBA DDL) nothing can be moderated, so approval is enough.
     */
    public static function isFinal(?EvaluationMark $m): bool
    {
        if (! $m?->isApproved()) {
            return false;
        }

        return $m->is_absent || ! self::moderationEnabled() || $m->moderated_marks !== null;
    }

    /**
     * Services whose OTs pass a component at BHUTAN_PASSING_PERCENT; everyone else at
     * PASSING_PERCENT. Matched on service_master.service_short_name, upper-cased, so the
     * rule holds in every environment whatever pk the rows have there.
     */
    public const BHUTAN_SERVICES = ['RBCS', 'RBFOS', 'RBPS'];

    public const BHUTAN_PASSING_PERCENT = 25.0;

    public const PASSING_PERCENT = 50.0;

    /** Passing percentage for an OT of this service (short name); fixed, not editable. */
    public static function passingPercent(?string $serviceShortName): float
    {
        return in_array(strtoupper(trim((string) $serviceShortName)), self::BHUTAN_SERVICES, true)
            ? self::BHUTAN_PASSING_PERCENT
            : self::PASSING_PERCENT;
    }

    /** Passing marks of a component out of $max for an OT of this service. */
    public static function passingMarks(float $max, ?string $serviceShortName): float
    {
        return round($max * self::passingPercent($serviceShortName) / 100, 2);
    }

    /**
     * "20 (Bhutan 10)" — a component's passing marks as the setup screens show them, since
     * the value depends on each OT's service and is not stored per component.
     */
    public static function passingMarksLabel(?float $max): string
    {
        if ($max === null) {
            return '-';
        }
        $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        return $fmt(self::passingMarks($max, null)).' (Bhutan '.$fmt(self::passingMarks($max, self::BHUTAN_SERVICES[0])).')';
    }

    /**
     * Merit & Ranking in $scope (as courseSheets()) of one course, or of every course
     * ($coursePk null = All; each ranked within its own course).
     *
     * Every component whose exam is over counts, split three ways:
     *   B          components on a Director Assessment drive (isDirectorAssessment())
     *   ICT/Hindi  qualifying papers (qualifyingPaper()), shown but never added up
     *   A          the subjects the listed drives hold, MERIT_SUBJECTS order first, then any
     *              other; Total (A) is their sum. A subject (or ICT / Hindi) no listed
     *              drive holds is not shown. $driveId: one examination drive only.
     * Grand Total = A + B. Marks are resultMarks(). Header max marks: what the drive
     * maps, else Subject Max Marks, else the Figma's (MERIT_SUBJECTS, DIRECTOR_MAX,
     * QUALIFYING_MAX); Total (A) / Grand Total are out of the shown columns.
     *
     * An OT is ranked once every one of those marks is approved and moderated
     * (isFinal()); the rest are only counted ($pending). Equal Grand Totals share a rank (1, 2, 2, 4), in OT-code order.
     * Pass: every Part A and qualifying component at or above its passing marks —
     * passingPercent() of that component's max marks, by the OT's own service (Bhutan
     * services 25%, all others 50%). Absent counts 0, so it fails. Part B (Director
     * Assessment) is not a pass/fail paper. Every ranked OT gets a result.
     *
     * Under All, a header max is null where the courses listed differ on it; each row
     * carries its course's own in row['max'] (subjects, a, b, max, qualifying).
     *
     * @return array{courses:array<int,string>, coursePk:?int, subjects:array, maxA:?float, maxB:?float, max:?float, qualifying:array<string,?float>, rows:list<array>, pending:int, noPassMarks:list<string>}
     */
    public function merit(string $scope, ?int $coursePk, ?int $driveId = null, bool $firstCourseByDefault = false): array
    {
        $byCourse = $this->scopeDrives($scope)->groupBy('course_master_pk')
            ->filter(fn (Collection $drives) => $drives->contains(fn ($d) => $d->componentMaps->contains(fn ($m) => $this->examOver($d, $m))));
        $courses = $byCourse->map(fn (Collection $drives) => CourseParticipants::plainName($drives->first()->course->course_name ?? ''))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        // Opened with no course: the first course, so the columns are that course's subjects
        // (All lists every course's subjects side by side).
        if ($coursePk === null && $driveId === null && $firstCourseByDefault) {
            $coursePk = array_key_first($courses);
        }
        $coursePk = isset($courses[$coursePk]) ? $coursePk : null;
        // Drive filter: that one examination drive only (its course, its subjects).
        $driveCourse = $driveId ? $byCourse->search(fn (Collection $drives) => $drives->contains('id', $driveId)) : false;
        $driveCourse = $driveCourse === false || ($coursePk && (int) $driveCourse !== $coursePk) ? null : (int) $driveCourse;

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
        $defaultQualifying = $qualifyingMax;

        $sheets = [];
        foreach ($driveCourse ? [$driveCourse] : ($coursePk ? [$coursePk] : array_keys($courses)) as $pk) {
            $drives = $driveCourse ? $byCourse[$pk]->where('id', $driveId)->values() : $byCourse[$pk];
            $sheets[$pk] = $this->meritSheet($drives, $courses[$pk]);
        }
        $held = [];
        $heldPapers = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet['subjects'] as $key => $s) {
                $held[$key] = true;
                $subjects[$key] ??= ['name' => $s['name'], 'max' => $s['max'], 'columns' => []];
                if ($s['columns'] && $subjects[$key]['columns'] === []) {
                    $subjects[$key]['columns'] = $s['columns'];
                }
            }
            foreach ($sheet['qualifying'] as $paper => $m) {
                if ($m !== null) {
                    $heldPapers[$paper] = true;
                }
            }
        }
        // Only the subjects (and qualifying papers) the listed drives hold: the Figma's fixed
        // columns stay in their order but are no longer shown when no drive holds them.
        $subjects = array_intersect_key($subjects, $held);

        // Each course's own max marks: what its drives map, else the header defaults.
        // The same subject can be out of 70 on one course and 140 on another (two
        // drives), so under All every row carries its course's maxes.
        $rows = [];
        $pending = 0;
        $noPassMarks = [];
        $perCourse = [];
        $hasDirector = false;
        foreach ($sheets as $pk => $sheet) {
            // Total (A) is out of the subjects this course's drives hold, not the fixed list.
            $max = [];
            foreach ($sheet['subjects'] as $key => $s) {
                $max[$key] = $s['max'];
            }
            $a = (float) array_sum($max);
            // No Director Assessment drive on the course: no Part B (Grand Total = Total (A)).
            $b = $sheet['maxB'] ?? 0.0;
            $hasDirector = $hasDirector || $sheet['maxB'] !== null;
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
        $maxA = $pick('a', null);
        $maxB = $hasDirector ? $pick('b', null) : 0.0;
        $qualifyingMax = [];
        foreach (array_intersect_key($defaultQualifying, $heldPapers) as $paper => $m) {
            $qualifyingMax[$paper] = $same(array_map(fn ($c) => $c['qualifying'][$paper], $perCourse));
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
            // Director Assessment column only when a listed drive is a Director Assessment one.
            'hasDirector' => $hasDirector,
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
        $driveNames = [];
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
                $driveNames[] = CourseParticipants::plainName($drive->drive_name) ?: 'Examination Drive #'.$drive->id;
            }
        }
        $marks = $this->marks($drives->pluck('id')->all());
        // Merit & Ranking's Examination Drive column: the drives the marks come from.
        $driveLabel = implode(', ', array_unique($driveNames));

        $students = DB::table('student_master as s')
            ->leftJoin('service_master as sv', 'sv.pk', '=', 's.service_master_pk')
            ->whereIn('s.pk', array_keys($participants))
            ->selectRaw("s.pk, COALESCE(NULLIF(TRIM(sv.service_short_name), ''), TRIM(sv.service_name)) as service, TRIM(sv.service_short_name) as service_code")
            ->get()->keyBy('pk');

        $sum = fn (array $keys, array $approved) => round(array_sum(array_map(fn ($key) => self::resultMarks($approved[$key]), $keys)), 2);
        // Columns that must each be cleared: Part A subjects and the qualifying papers.
        $passColumns = array_merge(
            ...array_values(array_column($subjects, 'columns')),
            ...array_values($qualifying)
        );
        $rows = [];
        // Kept for the views/marksheet contract; the fixed rule decides every result now.
        $noPassMarks = [];
        foreach ($participants as $pk => $p) {
            $approved = [];
            foreach (array_merge(array_keys($partB), ...array_map('array_keys', array_values(array_column($subjects, 'columns'))), ...array_map('array_keys', array_values($qualifying))) as $key) {
                $m = $marks[$key][$pk] ?? null;
                if (! self::isFinal($m)) {
                    continue 2;
                }
                $approved[$key] = $m;
            }

            $totals = array_map(fn ($s) => $sum(array_keys($s['columns']), $approved), $subjects);
            $totalA = round(array_sum($totals), 2);
            $director = $partB ? $sum(array_keys($partB), $approved) : null;
            $grand = round($totalA + ($director ?? 0), 2);

            $papers = [];
            foreach ($qualifying as $paper => $cols) {
                $papers[$paper] = $cols ? $sum(array_keys($cols), $approved) : null;
            }

            // Pass only if every Part A and qualifying component is cleared at the OT's
            // service percentage of that component's max marks (absent counts 0).
            $student = $students[$pk] ?? null;
            $passed = true;
            foreach ($passColumns as $key => $col) {
                if (self::resultMarks($approved[$key]) < self::passingMarks((float) $col['max'], $student?->service_code)) {
                    $passed = false;
                    break;
                }
            }
            $rows[] = $p + [
                'course' => $course,
                'drive' => $driveLabel,
                'service' => (string) ($student?->service ?? ''),
                'subjects' => $totals,
                'total_a' => $totalA,
                'director' => $director,
                'grand' => $grand,
                'qualifying' => $papers,
                // Per column key, for the marksheet (MarksheetService).
                'marks' => array_map(fn (EvaluationMark $m) => self::resultMarks($m), $approved),
                'result' => $passed ? 'Pass' : 'Fail',
                'passing_percent' => self::passingPercent($student?->service_code),
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

    /* ───────────── Merit & Ranking: Drive / Re-exam filters ───────────── */

    /**
     * The Drive and Re-exam filter options of Merit & Ranking in $scope: every main drive
     * in scope (Active, or Closed for archived; re-exam drives are under Re-exam), and
     * the re-exams of those drives. Picking a drive narrows the Re-exam list only.
     *
     * @return array{drives: array<int, array{name:string, course:int}>, reexams: array<int, array{label:string, main:int, course:int, no:int}>}
     */
    public function meritReexamFilters(string $scope): array
    {
        $out = ['drives' => [], 'reexams' => []];
        $mains = $this->scopeDrives($scope)->keyBy('id');
        foreach ($mains->sortBy(fn ($d) => mb_strtolower(ReexamService::driveName($d))) as $main) {
            $out['drives'][(int) $main->id] = ['name' => ReexamService::driveName($main), 'course' => (int) $main->course_master_pk];
        }
        if (! ReexamService::enabled()) {
            return $out;
        }
        $links = \App\Models\COE\Reexam::with('reexamDrive')->whereIn('main_drive_id', $mains->keys())
            ->orderBy('main_drive_id')->orderBy('reexam_no')->get();
        foreach ($links as $link) {
            if (! $link->reexamDrive || $link->reexamDrive->status === ExaminationDrive::STATUS_DRAFT) {
                continue;
            }
            $main = $mains[$link->main_drive_id];
            $out['reexams'][(int) $link->reexam_drive_id] = [
                'label' => 'Re-exam '.$link->reexam_no.' · '.ReexamService::driveName($main),
                'main' => (int) $main->id,
                'course' => (int) $main->course_master_pk,
                'no' => (int) $link->reexam_no,
            ];
        }

        return $out;
    }

    /**
     * Merit & Ranking with one re-exam picked: the merit() page of its course, narrowed to
     * that re-exam's OTs. In each subject an OT re-sat, the re-exam mark (approved, raw: no
     * moderation) takes the place of the main drive's mark in Total (A), Grand Total and
     * Pass / Fail; both are shown ($row['reexam']). The **rank stays the main merit rank**
     * (a re-exam never changes rank). A re-exam mark not approved yet: that subject and the
     * totals are null and the result is 'Pending'. Null when the re-exam is not on this tab.
     *
     * @return array<string, mixed>|null merit() + reexam: {id, no, name, main}
     */
    public function reexamMerit(string $scope, int $reexamDriveId): ?array
    {
        $link = ReexamService::enabled()
            ? \App\Models\COE\Reexam::with(['candidates', 'mainDrive.componentMaps.subject', 'mainDrive.examinationType', 'reexamDrive.componentMaps'])
                ->where('reexam_drive_id', $reexamDriveId)->first()
            : null;
        $main = $link?->mainDrive;
        if (! $main || ! $link->reexamDrive) {
            return null;
        }
        $merit = $this->merit($scope, (int) $main->course_master_pk);
        if ($merit['coursePk'] !== (int) $main->course_master_pk) {
            return null;
        }

        // Every merit column of the course: which bucket it adds to, its max, whether it must be passed.
        $columns = [];
        foreach ($this->scopeDrives($scope, (int) $main->course_master_pk) as $drive) {
            foreach ($drive->componentMaps as $map) {
                if (! $this->examOver($drive, $map)) {
                    continue;
                }
                $col = $this->column($drive, $map);
                $bucket = self::isDirectorAssessment($drive) ? 'B'
                    : (($paper = self::qualifyingPaper($col)) !== null ? 'q:'.$paper : self::meritKey($col['subject'], $col['subject_code'], $col['subject_pk']));
                $columns[$col['key']] = ['bucket' => $bucket, 'max' => $col['max']];
            }
        }

        $sits = [];
        foreach ($link->candidates as $c) {
            $sits[(int) $c->student_master_pk][] = (int) $c->subject_master_pk;
        }
        $reMaps = $link->reexamDrive->componentMaps->groupBy('subject_master_pk');
        $reMarks = $this->marks([(int) $link->reexam_drive_id]);
        $round = fn (array $values) => round(array_sum($values), 2);

        $rows = [];
        foreach ($merit['rows'] as $row) {
            $pk = (int) $row['student'];
            if (! isset($sits[$pk])) {
                continue;
            }
            $marks = $row['marks'];
            $swapped = [];
            $pending = [];
            foreach ($sits[$pk] as $subjectPk) {
                foreach ($reMaps->get($subjectPk, collect()) as $map) {
                    $mainKey = $main->id.':'.$subjectPk.':'.$map->component_master_pk;
                    $m = $reMarks[$link->reexam_drive_id.':'.$subjectPk.':'.$map->component_master_pk][$pk] ?? null;
                    $bucket = $columns[$mainKey]['bucket'] ?? null;
                    if ($bucket === null) {
                        continue;
                    }
                    $swapped[$bucket] = true;
                    if (! ReexamService::markIsFinal($m, true)) {
                        $pending[$bucket] = true;

                        continue;
                    }
                    $marks[$mainKey] = ReexamService::markValue($m, true);
                }
            }

            $byBucket = [];
            foreach ($columns as $key => $col) {
                if (array_key_exists($key, $marks)) {
                    $byBucket[$col['bucket']][] = $marks[$key];
                }
            }
            $new = $row;
            foreach ($row['subjects'] as $key => $old) {
                if (isset($swapped[$key])) {
                    $new['subjects'][$key] = isset($pending[$key]) ? null : $round($byBucket[$key] ?? []);
                }
            }
            foreach ($row['qualifying'] as $paper => $old) {
                if (isset($swapped['q:'.$paper])) {
                    $new['qualifying'][$paper] = isset($pending['q:'.$paper]) ? null : $round($byBucket['q:'.$paper] ?? []);
                }
            }
            if ($pending) {
                $new['total_a'] = $new['grand'] = null;
                $new['result'] = ReexamService::RESULT_PENDING;
            } else {
                $new['total_a'] = $round(array_filter($new['subjects'], fn ($v) => $v !== null));
                $new['grand'] = round($new['total_a'] + ($row['director'] ?? 0), 2);
                // Pass: every Part A and qualifying column at the OT's service percentage (as merit()).
                $passed = true;
                foreach ($columns as $key => $col) {
                    if ($col['bucket'] !== 'B' && array_key_exists($key, $marks)
                        && $marks[$key] < round($col['max'] * $row['passing_percent'] / 100, 2)) {
                        $passed = false;
                        break;
                    }
                }
                $new['result'] = $passed ? ReexamService::RESULT_PASS : ReexamService::RESULT_FAIL;
            }
            $new['marks'] = $marks;
            // What the main merit showed, for the cells the re-exam changed.
            $new['reexam'] = [
                'subjects' => array_intersect_key($row['subjects'], $swapped),
                'qualifying' => array_filter($row['qualifying'], fn ($v, $paper) => isset($swapped['q:'.$paper]), ARRAY_FILTER_USE_BOTH),
                'total_a' => $row['total_a'],
                'grand' => $row['grand'],
                'result' => $row['result'],
            ];
            $rows[] = $new;
        }

        return ['rows' => $rows, 'pending' => 0, 'reexam' => [
            'id' => (int) $link->reexam_drive_id,
            'no' => (int) $link->reexam_no,
            'name' => ReexamService::driveName($link->reexamDrive),
            'main' => ReexamService::driveName($main),
        ]] + $merit;
    }

    private function courseSheet(Collection $drives, array $marks): ?array
    {
        $participants = [];
        $columns = [];
        foreach ($drives as $drive) {
            // Every component of the drive, held or not: a paper not held yet is pending for everyone.
            foreach ($drive->componentMaps->sortBy('id') as $map) {
                $columns[] = $this->column($drive, $map) + ['held' => $this->examOver($drive, $map)];
            }
            if ($drive->componentMaps->isNotEmpty()) {
                $participants += $this->participants($drive);
            }
        }
        // Every subject of the drive, whether or not any of its marks are in yet.
        $subjects = $this->groupBySubject($columns);
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
            'course' => CourseParticipants::plainName($first->course->course_name ?? ''),
            'subjects' => $subjects,
            'rows' => $rows,
        ];
    }

    /**
     * Result Compilation grid: one row per examination drive × subject. Each drive is its own
     * exam: its maximum and its pending OTs are that drive's components only, never summed
     * with another drive of the course.
     *
     * @return list<array<string, mixed>>
     */
    public function compilation(string $scope): array
    {
        $drives = $this->scopeDrives($scope)
            ->sortBy(fn ($d) => [mb_strtolower(CourseParticipants::plainName($d->course->course_name ?? '')), (int) $d->id]);
        $marks = $this->marks($drives->pluck('id')->all());

        $out = [];
        foreach ($drives as $drive) {
            $sheet = $this->courseSheet(collect([$drive]), $marks);
            if ($sheet === null) {
                continue;
            }
            foreach ($sheet['subjects'] as $spk => $s) {
                $done = array_filter($sheet['rows'], fn ($r) => ! in_array($spk, $r['pending'], true));
                $out[] = [
                    'course_pk' => $sheet['course_pk'],
                    'course' => $sheet['course'],
                    'drive_id' => (int) $drive->id,
                    'drive' => CourseParticipants::plainName($drive->drive_name) ?: 'Examination Drive #'.$drive->id,
                    'subject_pk' => $spk,
                    'subject' => $s['name'],
                    'code' => $s['code'],
                    'max' => $s['max'],
                    'approved' => count($done),
                    'pending' => count($sheet['rows']) - count($done),
                    'total' => count($sheet['rows']),
                    'blocker' => match (true) {
                        ! collect($s['columns'])->contains('open', true) => 'Waiting for Course Coordinator approval',
                        ! collect($s['columns'])->contains('held', true) => 'Exam not held yet',
                        ! collect($s['columns'])->contains(fn ($c) => $c['open'] && $c['has_faculty']) => 'No faculty mapped to this subject',
                        default => null,
                    },
                    // Mean evaluated score as a share of the subject's maximum.
                    'average' => $done && $s['max'] > 0
                        ? round(array_sum(array_map(fn ($r) => $r['subjects'][$spk], $done)) / count($done) / $s['max'] * 100, 1)
                        : null,
                    'reexam' => null,
                ];
            }
        }

        // Re-exam rows ("(Re-exam)"), each right after its subject's main row (after an earlier
        // re-exam of it), else at the end of its course, else at the end.
        foreach ($this->reexamCompilationRows($scope) as $rx) {
            $same = $course = null;
            foreach ($out as $i => $row) {
                if ($row['course_pk'] === $rx['course_pk']) {
                    $course = $i;
                    if ($row['subject_pk'] === $rx['subject_pk']) {
                        $same = $i;
                    }
                }
            }
            $at = $same ?? $course;
            array_splice($out, $at === null ? count($out) : $at + 1, 0, [$rx]);
        }

        return $out;
    }

    /* ───────────── Result Compilation: re-exam rows ───────────── */

    /**
     * Re-exam drives in $scope (Active, or Closed for archived), optionally one. Kept out of
     * scopeDrives(), so the main rows, Merit and Moderation never count them.
     */
    private function reexamScopeDrives(string $scope, ?int $driveId = null): Collection
    {
        if (! ReexamService::enabled()) {
            return collect();
        }

        return ExaminationDrive::with(['course', 'examinationType', 'facultyMaps.faculty', 'componentMaps.component', 'componentMaps.subject', 'componentMaps.faculty'])
            ->where('status', $scope === 'archived' ? ExaminationDrive::STATUS_CLOSED : ExaminationDrive::STATUS_ACTIVE)
            ->whereIn('id', DB::table('coe_reexams')->select('reexam_drive_id'))
            ->when($driveId, fn ($q) => $q->whereKey($driveId))
            ->whereHas('componentMaps')
            ->orderBy('id')
            ->get();
    }

    /**
     * One re-exam drive as a compilation sheet (same shape as courseSheet()): every subject
     * of the re-exam (held or not, so the row shows from creation) and, per subject, only
     * its re-exam OTs (coe_reexam_candidates), on the re-exam's own approved marks (raw:
     * re-exams have no moderation). 'sits' = the subjects an OT sits; other cells are null.
     *
     * @param  array<string, array<int, EvaluationMark>>|null  $marks
     * @return array{course_pk:int, course:string, drive_id:int, drive:string, subjects:array, rows:array}
     */
    private function reexamSheet(ExaminationDrive $drive, ?array $marks = null): array
    {
        $marks ??= $this->marks([(int) $drive->id]);
        $columns = $drive->componentMaps->sortBy('id')
            ->map(fn ($map) => $this->column($drive, $map) + ['held' => $this->examOver($drive, $map)])->values()->all();
        $subjects = $this->groupBySubject($columns);
        $bySubject = ReexamService::seatingBySubject((int) $drive->id) ?? [];
        $participants = $this->participants($drive);

        $rows = [];
        foreach ($subjects as $spk => $s) {
            foreach ($bySubject[$spk] ?? [] as $pk) {
                if (! isset($participants[$pk])) {
                    continue;
                }
                $rows[$pk] ??= $participants[$pk] + ['marks' => [], 'subjects' => [], 'pending' => [], 'sits' => []];
                $rows[$pk]['sits'][] = $spk;
                $total = 0.0;
                $done = true;
                foreach ($s['columns'] as $key => $col) {
                    $m = ($marks[$key][$pk] ?? null)?->isApproved() ? $marks[$key][$pk] : null;
                    $rows[$pk]['marks'][$key] = $m === null ? null : ($m->is_absent ? 'AB' : $m->marks);
                    $done = $done && $m !== null;
                    $total += $m && ! $m->is_absent ? (float) $m->marks : 0;
                }
                $rows[$pk]['subjects'][$spk] = round($total, 2);
                if (! $done) {
                    $rows[$pk]['pending'][] = $spk;
                }
            }
        }
        // Subjects an OT does not sit: blank cells.
        foreach ($rows as &$row) {
            foreach ($subjects as $spk => $s) {
                $row['subjects'] += [$spk => null];
                foreach (array_keys($s['columns']) as $key) {
                    $row['marks'] += [$key => null];
                }
            }
        }
        unset($row);
        uasort($rows, fn ($a, $b) => self::compareOtCodes($a['code'], $b['code']));

        return [
            'course_pk' => (int) $drive->course_master_pk,
            'course' => trim((string) ($drive->course->course_name ?? '')),
            'drive_id' => (int) $drive->id,
            'drive' => ReexamService::driveName($drive),
            'subjects' => $subjects,
            'rows' => $rows,
        ];
    }

    /** The re-exam sheet for the Evaluated / Pending lists of a "(Re-exam)" row, or null. */
    public function reexamCompilationSheet(string $scope, int $driveId): ?array
    {
        $drive = $this->reexamScopeDrives($scope, $driveId)->first();

        return $drive ? $this->reexamSheet($drive) : null;
    }

    /**
     * Result Compilation rows of the re-exams in $scope: one per re-exam + subject, counting
     * only that subject's re-exam OTs. Shown from creation; until marks can be entered the
     * Action column says why (CC approval, exam not held yet, no faculty).
     *
     * @return list<array<string, mixed>>
     */
    private function reexamCompilationRows(string $scope): array
    {
        $drives = $this->reexamScopeDrives($scope);
        if ($drives->isEmpty()) {
            return [];
        }
        $marks = $this->marks($drives->pluck('id')->all());
        $out = [];
        foreach ($drives as $drive) {
            $sheet = $this->reexamSheet($drive, $marks);
            foreach ($sheet['subjects'] as $spk => $s) {
                $sitting = array_filter($sheet['rows'], fn ($r) => in_array($spk, $r['sits'], true));
                $done = array_filter($sitting, fn ($r) => ! in_array($spk, $r['pending'], true));
                $out[] = [
                    'course_pk' => $sheet['course_pk'],
                    'course' => CourseParticipants::plainName($sheet['course']),
                    // Opened with ?reexam=, not ?drive= (only its re-exam OTs).
                    'drive_id' => null,
                    'drive' => ReexamService::driveName($drive),
                    'subject_pk' => $spk,
                    'subject' => $s['name'],
                    'code' => $s['code'],
                    'max' => $s['max'],
                    'approved' => count($done),
                    'pending' => count($sitting) - count($done),
                    'total' => count($sitting),
                    'blocker' => match (true) {
                        ! collect($s['columns'])->contains('open', true) => 'Waiting for Course Coordinator approval',
                        ! collect($s['columns'])->every(fn ($c) => $c['held']) => 'Re-exam not held yet',
                        ! collect($s['columns'])->contains('has_faculty', true) => 'No faculty mapped to this subject',
                        default => null,
                    },
                    'average' => $done && $s['max'] > 0
                        ? round(array_sum(array_map(fn ($r) => $r['subjects'][$spk], $done)) / count($done) / $s['max'] * 100, 1)
                        : null,
                    'reexam' => $sheet['drive_id'],
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
                    $names = $col['director']
                        ? [DirectorAssessmentService::EVALUATOR]
                        : ($this->evaluatorNames($map, $p) ?: ($ownersTakeAll ? $subjectFaculty : []));
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
                        // The OT's code on this subject (one Unique Participant Code per subject).
                        'code' => self::codeFor($p, $col['subject_pk']),
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

        // The same component on several drives is told apart by the Examination Drive column.
        usort($rows, fn ($a, $b) => [$a['course'], $a['drive'], $a['subject'], $a['component']] <=> [$b['course'], $b['drive'], $b['subject'], $b['component']]
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

    /* ───────────── Entry ready ───────────── */

    /** notifications.module_name of the "marks entry is open" bell; reference_pk is the component map id. */
    public const READY_MODULE = 'FacultyResultEntryReady';

    /**
     * Bell notification to each faculty the moment a component opens for them on
     * Evaluation & Marks Entry: drive Active and approved by the CC, and the
     * component's exam over (see entryDrives(), examOver()). Recipients are the
     * subject's faculty plus the component evaluators of its unapproved cells, as
     * in notifyPending(). Sent once per faculty and component — an earlier bell of
     * this module on the same map stops a repeat — so it is safe to run daily and
     * again on CC approval. A component with every mark approved is skipped.
     * A failure to send is logged, never thrown.
     *
     * @return array{components:int, notified:int, unreachable:int}
     */
    public function notifyEntryReady(?int $driveId = null, bool $dryRun = false): array
    {
        $drives = $this->entryDrives(null, true)->when($driveId, fn ($c) => $c->where('id', $driveId));
        $marks = $this->marks($drives->pluck('id')->all());

        // map id => [drive, map, faculty pks]
        $ready = [];
        $allNames = [];
        foreach ($drives as $drive) {
            foreach ($drive->componentMaps as $map) {
                if (! $this->examOver($drive, $map) || self::isDirectorComponent($drive, $map)) {
                    continue;
                }
                $key = $drive->id.':'.$map->subject_master_pk.':'.$map->component_master_pk;
                $names = [];
                $pending = false;
                foreach ($this->entryParticipants($drive, (int) $map->subject_master_pk) as $pk => $p) {
                    if (($marks[$key][$pk] ?? null)?->isApproved()) {
                        continue;
                    }
                    $pending = true;
                    array_push($names, ...$this->evaluatorNames($map, $p));
                }
                if (! $pending) {
                    continue;
                }
                $names = array_values(array_unique($names));
                array_push($allNames, ...$names);
                $ready[(int) $map->id] = [
                    'drive' => $drive,
                    'map' => $map,
                    'faculty' => $this->subjectFacultyPks($drive, (int) $map->subject_master_pk),
                    'names' => $names,
                ];
            }
        }

        if ($ready === []) {
            return ['components' => 0, 'notified' => 0, 'unreachable' => 0];
        }

        $pkByName = [];
        if ($allNames) {
            FacultyMaster::whereIn(DB::raw('TRIM(full_name)'), array_values(array_unique($allNames)))
                ->get(['pk', 'full_name'])
                ->each(function ($f) use (&$pkByName) {
                    $pkByName[trim((string) $f->full_name)][] = (int) $f->pk;
                });
        }
        $allPks = [];
        foreach ($ready as $id => $r) {
            $pks = $r['faculty'];
            foreach ($r['names'] as $name) {
                array_push($pks, ...($pkByName[$name] ?? []));
            }
            $ready[$id]['faculty'] = array_values(array_unique(array_map('intval', $pks)));
            array_push($allPks, ...$ready[$id]['faculty']);
        }

        // faculty pk => login user_id (faculty_master.employee_master_pk = user_credentials.user_id)
        $userOf = FacultyMaster::whereIn('pk', array_values(array_unique($allPks)))
            ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
            ->pluck('employee_master_pk', 'pk')
            ->map(fn ($id) => (int) $id)
            ->all();

        $already = DB::table('notifications')
            ->where('type', 'coe_result_entry')
            ->where('module_name', self::READY_MODULE)
            ->whereIn('reference_pk', array_keys($ready))
            ->get(['reference_pk', 'receiver_user_id'])
            ->mapWithKeys(fn ($n) => [$n->reference_pk.'|'.$n->receiver_user_id => true])
            ->all();

        $service = app(NotificationService::class);
        $notified = 0;
        $unreachable = 0;
        foreach ($ready as $mapId => $r) {
            $userIds = [];
            foreach ($r['faculty'] as $pk) {
                if (! isset($userOf[$pk])) {
                    $unreachable++;
                } elseif (! isset($already[$mapId.'|'.$userOf[$pk]])) {
                    $userIds[] = $userOf[$pk];
                }
            }
            $userIds = array_values(array_unique($userIds));
            if ($userIds === [] || $dryRun) {
                $notified += $dryRun ? count($userIds) : 0;
                continue;
            }

            $col = $this->column($r['drive'], $r['map']);
            try {
                $notified += $service->createMultiple(
                    $userIds, 'coe_result_entry', self::READY_MODULE, $mapId, 'Evaluation & Marks Entry Ready',
                    "Evaluation & Marks Entry is open for {$col['subject']} — {$col['component']} ({$col['drive']}, {$col['course']}). "
                        .'Enter the marks and approve them.'
                );
            } catch (\Throwable $e) {
                Log::error('Marks entry ready notification failed', ['component_map_id' => $mapId, 'error' => $e->getMessage()]);
            }
        }

        return ['components' => count($ready), 'notified' => $notified, 'unreachable' => $unreachable];
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
    public function notifyPending(int $coursePk, ?int $subjectPk = null, ?int $studentPk = null, ?int $reexamDriveId = null, ?int $driveId = null): array
    {
        // A "(Re-exam)" row: only that re-exam drive and its re-exam OTs (entryParticipants);
        // the main rows keep re-exam drives out, as before. A main row is one drive ($driveId).
        $drives = $reexamDriveId
            ? $this->entryDrives($coursePk, true)->filter(fn ($d) => (int) $d->id === $reexamDriveId && ReexamService::isReexamDrive($reexamDriveId))->values()
            : $this->entryDrives($coursePk)->when($driveId, fn ($c) => $c->where('id', $driveId)->values());
        $marks = $this->marks($drives->pluck('id')->all());

        $pending = 0;
        $facultyPks = [];
        $names = [];
        $subjects = [];

        foreach ($drives as $drive) {
            foreach ($drive->componentMaps as $map) {
                // Faculty owe every component but the Director's overall assessment (Training Section's).
                if (($subjectPk && (int) $map->subject_master_pk !== $subjectPk) || ! $this->examOver($drive, $map)
                    || self::isDirectorComponent($drive, $map)) {
                    continue;
                }
                $key = $drive->id.':'.$map->subject_master_pk.':'.$map->component_master_pk;
                foreach ($this->entryParticipants($drive, (int) $map->subject_master_pk) as $pk => $p) {
                    if (($studentPk && $pk !== $studentPk) || ($marks[$key][$pk] ?? null)?->isApproved()) {
                        continue;
                    }
                    $pending++;
                    $subjects[(int) $map->subject_master_pk] = trim((string) ($map->subject->subject_name ?? '')).($reexamDriveId ? ' (Re-exam)' : '');
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
