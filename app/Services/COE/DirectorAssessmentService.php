<?php

namespace App\Services\COE;

use App\Models\COE\EvaluationMark;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * COE → Examination Management → Evaluation & Marks: the Director Assessment,
 * entered by the Training Section (TI) and sent to COE.
 *
 *   01 Faculty marks received   faculty enter and approve the drive's other
 *                               components on Result Entry (ResultMarksService)
 *   02 TI · Director assessment the Training Section reviews each OT (faculty marks,
 *                               remarks, notices & memos, photo) and enters the
 *                               Director's overall marks and remarks
 *   03 COE                      Send to COE locks them; from there they count like
 *                               any approved mark (Result Compilation, Marks
 *                               Moderation, Merit & Ranking part B, marksheets)
 *
 * Storage is the faculty marks' own: one coe_evaluation_marks row per OT on the
 * Director component (ResultMarksService::isDirectorComponent()). Save draft =
 * STATUS_DRAFT, Send to COE = STATUS_APPROVED; the remarks are its `remarks`.
 *
 * One review row is one OT × one Director component (drive + subject).
 */
class DirectorAssessmentService
{
    /** Who evaluates the Director component, as Marks Moderation and the screens name it. */
    public const EVALUATOR = 'Training Section';

    /** coe_evaluation_marks.remarks is varchar(500). */
    public const REMARKS_MAX = 500;

    public function __construct(private ResultMarksService $results)
    {
    }

    /**
     * Director columns open for review: on an Active, CC-approved Director Assessment
     * drive, whose exam is over. Keyed by column key.
     *
     * @return array<string, array{col:array, drive:ExaminationDrive, map:ExaminationDriveComponentMap}>
     */
    private function columns(): array
    {
        $out = [];
        foreach ($this->results->entryDrives() as $drive) {
            foreach ($drive->componentMaps->sortBy('id') as $map) {
                if (ResultMarksService::isDirectorComponent($drive, $map) && $this->results->examOver($drive, $map)) {
                    $col = $this->results->column($drive, $map);
                    $out[$col['key']] = ['col' => $col, 'drive' => $drive, 'map' => $map];
                }
            }
        }

        return $out;
    }

    /**
     * The review table: courses and examinations (drives) to filter on, and one row
     * per OT × Director column of the chosen course (and drive), in drive, subject and
     * OT-code order, split into Pending and Completed (sent to COE).
     *
     * @return array{courses:array<int,string>, coursePk:?int, drives:array<int,string>, drivePk:?int, rows:list<array>, pendingCount:int, completedCount:int, faculty:array<string,string>, max:?float}
     */
    public function sheet(?int $coursePk, ?int $drivePk): array
    {
        $all = $this->columns();
        $courses = collect($all)->mapWithKeys(fn ($c) => [$c['col']['course_pk'] => $c['col']['course']])
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $coursePk = isset($courses[$coursePk]) ? $coursePk : array_key_first($courses);

        $courseColumns = array_filter($all, fn ($c) => $c['col']['course_pk'] === $coursePk);
        $drives = collect($courseColumns)->mapWithKeys(fn ($c) => [$c['col']['drive_id'] => $c['col']['drive']])->all();
        $drivePk = isset($drives[$drivePk]) ? $drivePk : null;
        $columns = $drivePk ? array_filter($courseColumns, fn ($c) => $c['col']['drive_id'] === $drivePk) : $courseColumns;

        $rows = $this->rows($columns);
        $done = count(array_filter($rows, fn ($r) => $r['sent']));

        // One header per faculty component label across the table ("Journaling /50").
        $faculty = [];
        foreach ($rows as $r) {
            foreach ($r['faculty'] as $f) {
                $faculty[$f['label']] ??= $f['label'].' /'.ResultMarksService::number($f['max']);
            }
        }
        $maxes = array_unique(array_column($rows, 'max'));

        return [
            'courses' => $courses,
            'coursePk' => $coursePk,
            'drives' => $drives,
            'drivePk' => $drivePk,
            'rows' => $rows,
            'pendingCount' => count($rows) - $done,
            'completedCount' => $done,
            'faculty' => $faculty,
            'max' => count($maxes) === 1 ? (float) reset($maxes) : null,
        ];
    }

    /**
     * One OT's review (the OT profile): its row, the notices and memos on record, and
     * the rows before and after it in the table it was opened from (same course,
     * drive and tab).
     *
     * @return array{row:array, notices:\Illuminate\Support\Collection, memos:\Illuminate\Support\Collection, prev:?array, next:?array, position:int, total:int}|null
     */
    public function review(string $key, int $student, ?int $coursePk, ?int $drivePk, bool $completed): ?array
    {
        $all = $this->columns();
        if (! isset($all[$key])) {
            return null;
        }
        $coursePk ??= $all[$key]['col']['course_pk'];
        $sheet = $this->sheet($coursePk, $drivePk);
        $isThis = fn ($r) => $r['key'] === $key && $r['student'] === $student;
        // The tab's rows, plus this one wherever it is now: one just sent to COE has left
        // Pending, but Previous / Next still go on from its place in the table.
        $list = array_values(array_filter($sheet['rows'], fn ($r) => $r['sent'] === $completed || $isThis($r)));

        $at = null;
        foreach ($list as $i => $r) {
            if ($isThis($r)) {
                $at = $i;
                break;
            }
        }
        if ($at === null) {
            // Not on this course / examination filter: show it alone.
            $row = collect($this->rows([$key => $all[$key]]))->firstWhere('student', $student);
            if ($row === null) {
                return null;
            }
            $list = [$row];
            $at = 0;
        }

        return [
            'row' => $list[$at],
            'notices' => $this->notices($student),
            'memos' => $this->memos($student),
            'prev' => $list[$at - 1] ?? null,
            'next' => $list[$at + 1] ?? null,
            'position' => $at + 1,
            'total' => count($list),
        ];
    }

    /**
     * @param  array<string, array{col:array, drive:ExaminationDrive, map:ExaminationDriveComponentMap}>  $columns
     * @return list<array<string, mixed>>
     */
    private function rows(array $columns): array
    {
        if ($columns === []) {
            return [];
        }
        $driveIds = array_values(array_unique(array_map(fn ($c) => $c['col']['drive_id'], $columns)));
        $marks = $this->results->marks($driveIds);

        $participants = [];
        foreach ($columns as $c) {
            $participants += $this->results->participants($c['drive']);
        }
        $people = $this->people(array_keys($participants));

        $rows = [];
        foreach ($columns as $key => $c) {
            $col = $c['col'];
            $drive = $c['drive'];
            $subjectFaculty = $drive->facultyMaps->where('subject_master_pk', $col['subject_pk'])
                ->map(fn ($f) => trim((string) ($f->faculty->full_name ?? '')))->filter()->unique()->values()->all();
            // The subject's other components on the drive: faculty-evaluated.
            $components = $drive->componentMaps->sortBy('id')
                ->filter(fn ($m) => (int) $m->subject_master_pk === $col['subject_pk'] && ! ResultMarksService::isDirectorComponent($drive, $m))
                ->map(fn ($m) => ['map' => $m, 'col' => $this->results->column($drive, $m)])->values();
            $labels = array_count_values($components->map(fn ($f) => $f['col']['component'])->all());

            $sno = 0;
            foreach ($this->results->participants($drive) as $pk => $p) {
                $sno++;
                $faculty = [];
                foreach ($components as $f) {
                    $m = $marks[$f['col']['key']][$pk] ?? null;
                    $received = (bool) $m?->isApproved();
                    $system = in_array($f['map']->source, ['Sargam', 'Sargam 2.0'], true);
                    $faculty[] = [
                        'label' => $labels[$f['col']['component']] > 1 ? $f['col']['component'].' #'.$f['map']->id : $f['col']['component'],
                        'max' => $f['col']['max'],
                        'source' => (string) $f['map']->source,
                        // Who evaluates this OT on it, as on Result Entry (Tutor Name).
                        'evaluator' => $system ? (string) $f['map']->source
                            : implode(', ', $this->results->evaluatorNames($f['map'], $p) ?: $subjectFaculty),
                        // Faculty marks reach the TI once approved; a draft is still the faculty's.
                        'received' => $received,
                        'absent' => $received && $m->is_absent,
                        'marks' => $received && ! $m->is_absent ? (float) $m->marks : null,
                        'remarks' => $received ? trim((string) ($m->remarks ?? '')) : '',
                        'over' => $this->results->examOver($drive, $f['map']),
                    ];
                }

                $mark = $marks[$key][$pk] ?? null;
                $received = array_filter($faculty, fn ($f) => $f['received']);
                $person = $people[$pk] ?? ['photo' => null, 'notices' => 0, 'memos' => 0];
                $rows[] = [
                    'key' => $key,
                    'student' => $pk,
                    'sno' => $sno,
                    'code' => $p['code'],
                    'name' => html_entity_decode($p['name'], ENT_QUOTES | ENT_HTML5),
                    'photo' => $person['photo'],
                    'drive_id' => $col['drive_id'],
                    'drive' => $col['drive'],
                    'course_pk' => $col['course_pk'],
                    'course' => $col['course_short'],
                    'subject' => $col['subject'],
                    'component' => $col['component'],
                    'max' => $col['max'],
                    'faculty' => $faculty,
                    'sources' => array_values(array_unique(array_column($faculty, 'source'))),
                    'evaluators' => array_values(array_unique(array_filter(array_column($faculty, 'evaluator')))),
                    'remarks' => array_values(array_filter(array_column($faculty, 'remarks'))),
                    // Absent once every faculty mark received says so; Present once any is a mark.
                    'attendance' => match (true) {
                        $received === [] => null,
                        count(array_filter($received, fn ($f) => $f['absent'])) === count($received) => 'Absent',
                        default => 'Present',
                    },
                    'eligible' => count($received) === count($faculty),
                    'notices' => $person['notices'],
                    'memos' => $person['memos'],
                    'mark' => $mark,
                    'marks' => $mark?->marks,
                    'note' => trim((string) ($mark?->remarks ?? '')),
                    'sent' => (bool) $mark?->isApproved(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Photo URL and how many notices / memos each OT has on record, for many OTs at once.
     *
     * @param  int[]  $students
     * @return array<int, array{photo:?string, notices:int, memos:int}>
     */
    private function people(array $students): array
    {
        if ($students === []) {
            return [];
        }
        $out = array_fill_keys($students, ['photo' => null, 'notices' => 0, 'memos' => 0]);

        DB::table('student_master')->whereIn('pk', $students)->whereNotNull('photo_path')->where('photo_path', '<>', '')
            ->pluck('photo_path', 'pk')
            ->each(function ($path, $pk) use (&$out) {
                $out[(int) $pk]['photo'] = self::photoUrl((string) $path);
            });
        foreach ($this->noticeCounts($students) as $pk => $n) {
            $out[$pk]['notices'] = $n;
        }
        foreach ($this->memoCounts($students) as $pk => $n) {
            $out[$pk]['memos'] = $n;
        }

        return $out;
    }

    /** Where an OT's registration photo is served from (as build_student_photo_url()). */
    public static function photoUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path === '' ? null : asset('storage/form-uploads/photo/'.ltrim($path, '/'));
    }

    /* ───────────── Notices and memos ───────────── */

    /**
     * Notices issued to the OT (the notice-memo portal's student_notice_status).
     * Same source as OTNoticeMemoService::getNotices(), without the conversations.
     */
    private function noticeQuery()
    {
        return DB::table('student_notice_status as n')->where('n.notice_memo', 1);
    }

    /** @return array<int, int> student pk => notices */
    private function noticeCounts(array $students): array
    {
        return $this->noticeQuery()->whereIn('n.student_pk', $students)
            ->groupBy('n.student_pk')->selectRaw('n.student_pk as pk, COUNT(*) as n')
            ->pluck('n', 'pk')->map(fn ($n) => (int) $n)->all();
    }

    /**
     * Memos: those a notice escalated to (student_memo_status) and those of the
     * Discipline Memo module (discipline_memo_status, active disciplines — as
     * OTNoticeMemoService::getDisciplineMemos()).
     *
     * @return array<int, int> student pk => memos
     */
    private function memoCounts(array $students): array
    {
        $out = [];
        $add = function ($rows) use (&$out) {
            foreach ($rows as $pk => $n) {
                $out[(int) $pk] = ($out[(int) $pk] ?? 0) + (int) $n;
            }
        };
        $add(DB::table('student_memo_status')->whereIn('student_pk', $students)
            ->groupBy('student_pk')->selectRaw('student_pk as pk, COUNT(*) as n')->pluck('n', 'pk'));
        $add(DB::table('discipline_memo_status as d')
            ->join('discipline_master as dm', 'dm.pk', '=', 'd.discipline_master_pk')->where('dm.active_inactive', 1)
            ->whereIn('d.student_master_pk', $students)
            ->groupBy('d.student_master_pk')->selectRaw('d.student_master_pk as pk, COUNT(*) as n')->pluck('n', 'pk'));

        return $out;
    }

    /** @return \Illuminate\Support\Collection<int, object{date:?string, course:?string, topic:?string, status:?int}> */
    public function notices(int $student)
    {
        return $this->noticeQuery()->where('n.student_pk', $student)
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'n.course_master_pk')
            ->leftJoin('timetable as t', 't.pk', '=', 'n.subject_topic')
            ->orderByDesc('n.date_')->orderByDesc('n.pk')
            ->get(['n.pk', 'n.date_ as date', 'cm.course_name as course', 't.subject_topic as topic', 'n.status']);
    }

    /** @return \Illuminate\Support\Collection<int, object{kind:string, date:?string, course:?string, topic:?string, status:?int, conclusion:?string, remark:?string}> */
    public function memos(int $student)
    {
        $notice = DB::table('student_memo_status as m')->where('m.student_pk', $student)
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'm.course_master_pk')
            ->leftJoin('student_notice_status as n', 'n.pk', '=', 'm.student_notice_status_pk')
            ->leftJoin('timetable as t', 't.pk', '=', 'n.subject_topic')
            ->leftJoin('memo_conclusion_master as mc', 'mc.pk', '=', 'm.memo_conclusion_master_pk')
            ->get(['m.pk', 'm.date as date', 'cm.course_name as course', 't.subject_topic as topic', 'm.status',
                'mc.discussion_name as conclusion', 'm.conclusion_remark as remark'])
            ->map(fn ($m) => (object) ((array) $m + ['kind' => 'Memo']));

        $discipline = DB::table('discipline_memo_status as d')->where('d.student_master_pk', $student)
            ->join('discipline_master as dm', 'dm.pk', '=', 'd.discipline_master_pk')->where('dm.active_inactive', 1)
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'd.course_master_pk')
            ->leftJoin('memo_conclusion_master as mc', 'mc.pk', '=', 'd.conclusion_type_pk')
            ->get(['d.pk', 'd.date as date', 'cm.course_name as course', 'dm.discipline_name as topic', 'd.status',
                'mc.discussion_name as conclusion', 'd.conclusion_remark as remark'])
            ->map(fn ($m) => (object) ((array) $m + ['kind' => 'Discipline memo']));

        return $notice->concat($discipline)->sortByDesc('date')->values();
    }

    /* ───────────── Save draft / Send to COE ───────────── */

    /**
     * Save the Director's marks and remarks typed for some OTs; send the cells in
     * $send ("key|student") to COE after. All or nothing: any error writes nothing.
     *
     * $marks / $remarks are column key => student pk => text; a cell left out of one
     * keeps what it has. Marks are 0..max with up to 2 decimals ('' clears). A cell
     * already sent to COE is locked. Send to COE needs marks, remarks, and every
     * faculty mark of the OT on that subject received (approved).
     *
     * @param  string[]  $send
     * @return array{saved:int, sent:int, errors:array<string, string>} errors keyed "key|student", or "_"
     */
    public function save(User $user, array $marks, array $remarks, array $send = []): array
    {
        // The Director's remarks need coe_evaluation_marks.remarks (DBA DDL, no migration).
        if (! ResultMarksService::remarksEnabled()) {
            return $this->refuse("Director's remarks can't be saved yet: the database is missing coe_evaluation_marks.remarks. Ask the DBA to add it.");
        }

        $all = $this->columns();
        $cells = [];
        foreach ([$marks, $remarks] as $input) {
            foreach ($input as $key => $students) {
                if (! isset($all[$key]) || ! is_array($students)) {
                    return $this->refuse('This Director assessment is no longer open. Reload the page.');
                }
                foreach (array_keys($students) as $student) {
                    $cells[$key.'|'.(int) $student] = [$key, (int) $student];
                }
            }
        }
        foreach (array_unique($send) as $cell) {
            [$key, $student] = array_pad(explode('|', (string) $cell, 2), 2, null);
            if (! isset($all[$key]) || ! ctype_digit((string) $student)) {
                return $this->refuse('This Director assessment is no longer open. Reload the page.');
            }
            $cells[$key.'|'.(int) $student] = [$key, (int) $student];
        }
        if ($cells === []) {
            return ['saved' => 0, 'sent' => 0, 'errors' => []];
        }

        // Only OTs of the course, and their rows as the table shows them (eligibility).
        $keys = array_values(array_unique(array_column($cells, 0)));
        $rows = collect($this->rows(array_intersect_key($all, array_flip($keys))))
            ->keyBy(fn ($r) => $r['key'].'|'.$r['student']);
        $errors = [];
        $changes = [];
        foreach ($cells as $cell => [$key, $student]) {
            if (! $rows->has($cell)) {
                return $this->refuse('Some of these OTs are not on this course. Reload the page.');
            }
            $max = $all[$key]['col']['max'];
            $change = [];
            if (array_key_exists($student, $marks[$key] ?? [])) {
                $raw = trim((string) $marks[$key][$student]);
                if ($raw !== '' && (! preg_match('/^\d+(\.\d{1,2})?$/', $raw) || (float) $raw > $max)) {
                    $errors[$cell] = 'Enter marks from 0 to '.ResultMarksService::number($max).' (up to 2 decimals).';

                    continue;
                }
                $change['marks'] = $raw === '' ? null : (float) $raw;
            }
            if (array_key_exists($student, $remarks[$key] ?? [])) {
                $note = trim((string) $remarks[$key][$student]);
                if (mb_strlen($note) > self::REMARKS_MAX) {
                    $errors[$cell] = 'Remarks can be at most '.self::REMARKS_MAX.' characters.';

                    continue;
                }
                $change['remarks'] = $note === '' ? null : $note;
            }
            $changes[$cell] = $change;
        }
        if ($errors) {
            return ['saved' => 0, 'sent' => 0, 'errors' => $errors];
        }

        // "key|student" as $cells spells it (the student as an int: "07" is 7).
        $sendCells = [];
        foreach ($send as $cell) {
            [$key, $student] = explode('|', (string) $cell, 2);
            $sendCells[$key.'|'.(int) $student] = true;
        }
        foreach (array_keys($sendCells) as $cell) {
            if (! $rows[$cell]['eligible']) {
                $errors[$cell] = "{$rows[$cell]['code']}: the faculty marks of {$rows[$cell]['subject']} are not all received yet.";
            }
        }
        if ($errors) {
            return ['saved' => 0, 'sent' => 0, 'errors' => $errors, 'message' => reset($errors)];
        }

        $userId = $user->user_id;
        try {
            return DB::transaction(function () use ($cells, $changes, $sendCells, $all, $rows, $userId) {
                $saved = 0;
                $sent = 0;
                $errors = [];
                $now = now();
                foreach ($cells as $cell => [$key, $student]) {
                    $col = $all[$key]['col'];
                    $mark = EvaluationMark::where('examination_drive_id', $col['drive_id'])
                        ->where('subject_master_pk', $col['subject_pk'])
                        ->where('component_master_pk', $col['component_pk'])
                        ->where('student_master_pk', $student)
                        ->lockForUpdate()->first();
                    if ($mark?->isApproved()) {
                        if (($changes[$cell] ?? []) !== [] || isset($sendCells[$cell])) {
                            $errors[$cell] = "{$rows[$cell]['code']} is already sent to COE and cannot be changed.";
                        }

                        continue;
                    }

                    $change = $changes[$cell] ?? [];
                    $value = array_key_exists('marks', $change) ? $change['marks'] : $mark?->marks;
                    $note = array_key_exists('remarks', $change) ? $change['remarks'] : $mark?->remarks;
                    if ($change !== [] && ($mark === null || $mark->marks !== $value || $mark->remarks !== $note)) {
                        if ($value === null && $note === null) {
                            // Nothing left on the cell: no row.
                            $mark?->delete();
                            $mark = null;
                        } else {
                            $mark ??= new EvaluationMark([
                                'examination_drive_id' => $col['drive_id'],
                                'subject_master_pk' => $col['subject_pk'],
                                'component_master_pk' => $col['component_pk'],
                                'student_master_pk' => $student,
                                'created_by' => $userId,
                                'created_date' => $now,
                            ]);
                            $mark->fill([
                                'marks' => $value,
                                'is_absent' => false,
                                'remarks' => $note,
                                // Not a faculty's mark: the Training Section's (created_by / modified_by).
                                'evaluated_by_faculty_pk' => null,
                                'modified_by' => $userId,
                                'modified_date' => $now,
                            ])->save();
                        }
                        $saved++;
                    }

                    if (isset($sendCells[$cell])) {
                        $code = $rows[$cell]['code'];
                        if ($mark?->marks === null) {
                            $errors[$cell] = "{$code}: enter the Director's overall marks before sending to COE.";
                        } elseif (trim((string) $mark->remarks) === '') {
                            $errors[$cell] = "{$code}: enter the Director's overall remarks before sending to COE.";
                        } else {
                            $mark->forceFill([
                                'status' => EvaluationMark::STATUS_APPROVED,
                                'approved_by' => $userId,
                                'approved_at' => $now,
                                'modified_by' => $userId,
                                'modified_date' => $now,
                            ])->save();
                            $sent++;
                        }
                    }
                }
                if ($errors) {
                    // Rolls the saves back too: all or nothing.
                    throw new ResultMarksException(json_encode($errors));
                }

                return ['saved' => $saved, 'sent' => $sent, 'errors' => []];
            });
        } catch (ResultMarksException $e) {
            $errors = (array) json_decode($e->getMessage(), true);

            return ['saved' => 0, 'sent' => 0, 'errors' => $errors, 'message' => reset($errors)];
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->refuse('Someone else saved this assessment just now. Reload the page and try again.');
            }
            throw $e;
        }
    }

    private function refuse(string $message): array
    {
        return ['saved' => 0, 'sent' => 0, 'errors' => ['_' => $message]];
    }
}
