<?php

namespace App\Services\COE;

use App\Models\COE\Reexam;
use App\Models\COE\ReexamCandidate;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\FacultyMaster;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * COE Re-exam (see the create_coe_reexams_tables migration).
 *
 * A main drive can get a re-exam once its results are in: the CC approved it, every
 * exam is over, **Merit & Ranking shows every participant with Pass / Fail** (resultRefusal;
 * no marksheet signing needed) and every participant's mark in every subject is final
 * (ResultMarksService::isFinal, the rule Merit & Ranking counts). The OTs who then
 * failed or were absent in a subject are the candidates, one row per OT + subject:
 *
 *   Absent  AB in any component of the subject.
 *   Failed  any component below its passing marks for the OT's service
 *           (ResultMarksService::passingMarks, the Merit & Ranking rule).
 *   Score % the subject's marks / its max marks x 100 (absent counts 0).
 *
 * "Latest result": an OT who sat the subject on an earlier re-exam is judged on that
 * re-exam's marks (the re-exam mark replaces the old one), so Re-exam 2 lists who still
 * failed after Re-exam 1, and waits until Re-exam 1's marks are final. Re-exams have
 * **no moderation**: their marks count once approved, raw (markIsFinal / markValue).
 *
 * The re-exam drive is a normal examination drive copied from the main drive: same
 * course, term, type, subjects, faculty and components (max marks, mode, type,
 * sub-coordinators, assigned codes), exam dates left empty for the faculty to set.
 *
 * Re-exam drives are in Result Entry (only their OTs, ResultMarksService::entryParticipants)
 * and Venue Allocation by drive (only their OTs); they stay out of Compilation, Merit,
 * Moderation and the main Marksheet (excludeFrom()). Their result is result() and their
 * Pass / Fail marksheet ReexamMarksheetService.
 */
class ReexamService
{
    public function __construct(private ResultMarksService $results)
    {
    }

    /** Whether the re-exam tables exist (migration run). Everything here is a no-op before. */
    public static function enabled(): bool
    {
        static $enabled;

        return $enabled ??= Schema::hasTable('coe_reexams') && Schema::hasTable('coe_reexam_candidates');
    }

    /**
     * Keep re-exam drives out of a drive query: $column is the examination_drives id
     * column of $query ('id' on ExaminationDrive, 'ed.id' on a joined query).
     */
    public static function excludeFrom(EloquentBuilder|QueryBuilder $query, string $column = 'id'): EloquentBuilder|QueryBuilder
    {
        return self::enabled()
            ? $query->whereNotIn($column, DB::table('coe_reexams')->select('reexam_drive_id'))
            : $query;
    }

    /** @return array<int, Reexam> re-exam drive id => its link row, for the drives given */
    public static function linksFor(array $driveIds): array
    {
        if (! self::enabled() || $driveIds === []) {
            return [];
        }

        return Reexam::whereIn('reexam_drive_id', $driveIds)->get()->keyBy('reexam_drive_id')->all();
    }

    public static function isReexamDrive(int $driveId): bool
    {
        return self::enabled() && Reexam::where('reexam_drive_id', $driveId)->exists();
    }

    /* ───────────── Seating (Venue Allocation) ───────────── */

    /**
     * A re-exam drive's candidates per subject, or null when the drive is not a re-exam
     * (Venue Allocation then works as before, on the whole cohort).
     *
     * @return array<int, list<int>>|null subject pk => student pks
     */
    public static function seatingBySubject(int $driveId): ?array
    {
        if (! self::enabled()) {
            return null;
        }
        $reexam = Reexam::where('reexam_drive_id', $driveId)->first();
        if (! $reexam) {
            return null;
        }
        $out = [];
        foreach (ReexamCandidate::where('reexam_id', $reexam->id)->get(['subject_master_pk', 'student_master_pk']) as $c) {
            $out[$c->subject_master_pk][] = $c->student_master_pk;
        }

        return $out;
    }

    /**
     * The OTs who sit one component on a re-exam (its subject's candidates), or null when
     * the component is not on a re-exam drive.
     *
     * @return list<int>|null
     */
    public static function seatingStudents(?int $componentMapId): ?array
    {
        if (! $componentMapId || ! self::enabled()) {
            return null;
        }
        $map = DB::table('examination_drive_component_maps')->where('id', $componentMapId)->first(['examination_drive_id', 'subject_master_pk']);
        if (! $map) {
            return null;
        }
        $bySubject = self::seatingBySubject((int) $map->examination_drive_id);

        return $bySubject === null ? null : ($bySubject[(int) $map->subject_master_pk] ?? []);
    }

    /** Whether the main drive has re-exams (it may then not be deleted). */
    public static function hasReexams(int $mainDriveId): bool
    {
        return self::enabled() && Reexam::where('main_drive_id', $mainDriveId)->exists();
    }

    /* ───────────── Who sits the re-exam ───────────── */

    /**
     * Why $drive cannot get a re-exam on the face of it (no marks read), or null.
     * Needs componentMaps and examinationType loaded for a cheap check. $isReexam when
     * the caller already knows (the drive list), else it is looked up.
     */
    public function refusal(ExaminationDrive $drive, ?bool $isReexam = null): ?string
    {
        if (! self::enabled()) {
            return 'Re-exam is not set up yet (run the migrations).';
        }
        if ($isReexam ?? self::isReexamDrive((int) $drive->id)) {
            return 'This is a re-exam drive. Create the next re-exam from its main drive.';
        }
        if ($drive->status === ExaminationDrive::STATUS_DRAFT) {
            return 'The drive is still a Draft.';
        }
        if ($drive->cc_status !== ExaminationDrive::CC_APPROVED) {
            return 'The drive is not approved by the Course Coordinator yet.';
        }
        if (ResultMarksService::isDirectorAssessment($drive)) {
            return 'Director Assessment has no pass or fail, so it has no re-exam.';
        }
        if ($drive->componentMaps->isEmpty()) {
            return 'The drive has no components.';
        }
        foreach ($drive->componentMaps as $map) {
            if (! $this->results->examOver($drive, $map)) {
                return 'Not every exam of this drive is over yet.';
            }
        }

        return null;
    }

    /**
     * The Create Re-exam list of a main drive.
     *
     * @return array{ready:bool, message:?string, next_no:int, rows:list<array<string, mixed>>}
     */
    public function sheet(ExaminationDrive $main): array
    {
        $main->loadMissing(['examinationType', 'componentMaps.subject']);
        if ($refusal = $this->refusal($main)) {
            return ['ready' => false, 'message' => $refusal, 'next_no' => 0, 'rows' => []];
        }

        $participants = $this->results->participants($main);
        if ($refusal = self::resultRefusal($main, $participants, $this->meritResults(collect([$main])))) {
            return ['ready' => false, 'message' => $refusal, 'next_no' => 0, 'rows' => []];
        }

        $reexams = Reexam::with('candidates')->where('main_drive_id', $main->id)->orderBy('reexam_no')->get();
        $marks = $this->results->marks(array_merge([(int) $main->id], $reexams->pluck('reexam_drive_id')->all()));

        return $this->evaluate($main, $reexams, $marks, $participants, $this->students(array_keys($participants)));
    }

    /**
     * For the drive list: which of $drives show the Re-exam button (results final and at
     * least one OT failed or absent). A few queries for the whole page, not per row.
     *
     * @param  Collection<int, ExaminationDrive>  $drives  with componentMaps.subject and examinationType
     * @return array<int, bool> drive id => show
     */
    public function readyMap(Collection $drives): array
    {
        $out = [];
        if (! self::enabled()) {
            return $out;
        }
        $links = self::linksFor($drives->pluck('id')->all());
        $mains = $drives->filter(fn (ExaminationDrive $d) => ! isset($links[$d->id]) && $this->refusal($d, false) === null);
        if ($mains->isEmpty()) {
            return $out;
        }
        // Only once Merit & Ranking shows the drive's OTs with Pass / Fail (merit is drive-wise).
        $results = $this->meritResults($mains);
        $mains = $mains->filter(fn (ExaminationDrive $d) => self::resultRefusal($d, $this->results->participants($d), $results) === null);
        if ($mains->isEmpty()) {
            return $out;
        }

        $reexams = Reexam::with('candidates')->whereIn('main_drive_id', $mains->pluck('id'))->orderBy('reexam_no')->get()->groupBy('main_drive_id');
        $marks = $this->results->marks(array_merge(
            $mains->pluck('id')->map(fn ($id) => (int) $id)->all(),
            $reexams->flatten()->pluck('reexam_drive_id')->all()
        ));
        $participants = [];
        foreach ($mains as $main) {
            $participants[$main->id] = $this->results->participants($main);
        }
        $students = $this->students(array_keys(array_replace([], ...array_values($participants))));

        foreach ($mains as $main) {
            $sheet = $this->evaluate($main, $reexams[$main->id] ?? collect(), $marks, $participants[$main->id], $students);
            $out[(int) $main->id] = $sheet['ready'] && $sheet['rows'] !== [];
        }

        return $out;
    }

    /**
     * Agreed flow (2026-11-23): a re-exam opens once **Merit & Ranking shows Pass / Fail**
     * for every participant of the drive (an OT is ranked there only when all their marks
     * on the drive are approved and moderated; merit is drive-wise). No marksheet signing
     * needed. Else why not, or null.
     *
     * @param  array<int, array<int, true>>  $results  drive id => student pk => true (has Pass / Fail)
     */
    private static function resultRefusal(ExaminationDrive $main, array $participants, array $results): ?string
    {
        if ($participants === []) {
            return 'The drive has no participants.';
        }
        $have = $results[(int) $main->id] ?? [];
        $missing = count(array_diff_key($participants, $have));

        return $missing === 0 ? null
            : 'Merit & Ranking does not show Pass / Fail yet for '.$missing.' of '.count($participants)
                .' OTs (their marks on this drive are not all approved and moderated). The re-exam opens once it does.';
    }

    /**
     * Who has a Pass / Fail on Merit & Ranking, per drive of $drives (the tab the drive is
     * on: Active, or Archived for a Closed drive). Merit is drive-wise: one sheet per drive.
     *
     * @param  Collection<int, ExaminationDrive>  $drives
     * @return array<int, array<int, true>> drive id => student pk => true
     */
    private function meritResults(Collection $drives): array
    {
        $out = [];
        foreach ($drives as $drive) {
            $scope = $drive->status === ExaminationDrive::STATUS_CLOSED ? 'archived' : 'active';
            $merit = $this->results->merit($scope, (int) $drive->course_master_pk, (int) $drive->id);
            if ((int) ($merit['coursePk'] ?? 0) !== (int) $drive->course_master_pk) {
                continue;   // the drive is not on that Merit tab (nothing held yet)
            }
            foreach ($merit['rows'] as $row) {
                if ((int) ($row['drive_id'] ?? 0) === (int) $drive->id && in_array($row['result'] ?? null, ['Pass', 'Fail'], true)) {
                    $out[(int) $drive->id][(int) $row['student']] = true;
                }
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, Reexam>  $reexams  of $main, by reexam_no, with candidates
     * @param  array<string, array<int, \App\Models\COE\EvaluationMark>>  $marks
     * @param  array<int, array<string, mixed>>  $participants
     * @param  array<int, object>  $students  pk => {ot_code, service_code}
     */
    private function evaluate(ExaminationDrive $main, Collection $reexams, array $marks, array $participants, array $students): array
    {
        $nextNo = (int) $reexams->max('reexam_no') + 1;
        // Who sat which subject on which re-exam: [subject][student] => [re-exam drive id, re-exam no] (latest wins).
        $sat = [];
        foreach ($reexams as $reexam) {
            foreach ($reexam->candidates as $c) {
                $sat[$c->subject_master_pk][$c->student_master_pk] = [(int) $reexam->reexam_drive_id, (int) $reexam->reexam_no];
            }
        }

        $rows = [];
        $pending = [];
        foreach ($main->componentMaps->sortBy('id')->groupBy('subject_master_pk') as $subjectPk => $maps) {
            $subjectName = trim((string) ($maps->first()->subject->subject_name ?? '')) ?: 'Subject #'.$subjectPk;
            foreach ($participants as $pk => $p) {
                [$driveId, $no] = $sat[$subjectPk][$pk] ?? [(int) $main->id, 0];
                $student = $students[$pk] ?? null;
                $got = 0.0;
                $max = 0.0;
                $absent = false;
                $failed = false;
                foreach ($maps as $map) {
                    $m = $marks[$driveId.':'.$subjectPk.':'.$map->component_master_pk][$pk] ?? null;
                    if (! self::markIsFinal($m, $no > 0)) {
                        $pending[$no] = ($pending[$no] ?? 0) + 1;

                        continue 2;
                    }
                    $value = self::markValue($m, $no > 0);
                    $got += $value;
                    $max += (float) $map->max_marks;
                    if ($m->is_absent) {
                        $absent = true;
                    } elseif ($value < ResultMarksService::passingMarks((float) $map->max_marks, $student->service_code ?? null)) {
                        $failed = true;
                    }
                }
                if (! $absent && ! $failed) {
                    continue;
                }
                $rows[] = [
                    'key' => $subjectPk.':'.$pk,
                    'student_pk' => (int) $pk,
                    'name' => $p['name'],
                    'ot_code' => (string) ($student->ot_code ?? ''),
                    'roll' => (string) ($p['roll'] ?? ''),
                    'subject_pk' => (int) $subjectPk,
                    'subject' => $subjectName,
                    'reason' => $absent ? ReexamCandidate::REASON_ABSENT : ReexamCandidate::REASON_FAILED,
                    'reason_label' => ReexamCandidate::REASONS[$absent ? ReexamCandidate::REASON_ABSENT : ReexamCandidate::REASON_FAILED],
                    'score' => $max > 0 ? round($got / $max * 100, 2) : 0.0,
                ];
            }
        }

        if ($pending !== []) {
            ksort($pending);
            $parts = [];
            foreach ($pending as $no => $count) {
                $parts[] = $count.' '.($no === 0 ? 'of this drive' : 'of Re-exam '.$no);
            }

            return ['ready' => false, 'next_no' => $nextNo, 'rows' => [],
                'message' => 'Results are not final yet: '.implode(', ', $parts).' OT-subject marks are not approved (and moderated) yet.'];
        }

        usort($rows, fn ($a, $b) => strnatcasecmp($a['subject'], $b['subject'])
            ?: ResultMarksService::compareOtCodes($a['roll'] ?: $a['ot_code'], $b['roll'] ?: $b['ot_code']));

        return [
            'ready' => true,
            'message' => $rows === [] ? 'Every OT has passed every subject of this drive. No re-exam is needed.' : null,
            'next_no' => $nextNo,
            'rows' => $rows,
        ];
    }

    /**
     * A mark the result may count. Main drive: approved and moderated (Merit rule,
     * ResultMarksService::isFinal). Re-exam: **no moderation** for re-exams, so approved is enough.
     */
    public static function markIsFinal(?\App\Models\COE\EvaluationMark $m, bool $reexam): bool
    {
        return $reexam ? (bool) $m?->isApproved() : ResultMarksService::isFinal($m);
    }

    /** The marks counted: moderated where the main drive has them; a re-exam's raw marks. Absent = 0. */
    public static function markValue(\App\Models\COE\EvaluationMark $m, bool $reexam): float
    {
        if ($m->is_absent) {
            return 0.0;
        }

        return $reexam ? (float) $m->marks : ResultMarksService::resultMarks($m);
    }

    /* ───────────── Re-exam result (step 6) ───────────── */

    /** Result of one OT + subject, or of one OT, on a re-exam. */
    public const RESULT_PASS = 'Pass';

    public const RESULT_FAIL = 'Fail';

    public const RESULT_PENDING = 'Pending';

    /**
     * The result of a re-exam drive, on **its own marks only** (they replace the main
     * drive's for these subjects; no moderation, so a mark counts once approved). Per
     * candidate + subject, the main rule (evaluate()): Fail if AB in any component or any
     * component below passingMarks() for the OT's service, else Pass; Pending while a mark
     * of the subject is not approved. Per OT: Pending while any subject is, else Fail if any
     * subject failed, else Pass. Rank on Merit & Ranking is not touched.
     *
     * @return array{ready:bool, message:?string, drive:string, main:string, reexam_no:int, rows:list<array<string, mixed>>, ots:array<int, array<string, mixed>>, counts:array<string, int>}
     */
    public function result(ExaminationDrive $drive): array
    {
        $empty = ['ready' => false, 'drive' => self::driveName($drive), 'main' => '', 'reexam_no' => 0, 'rows' => [], 'ots' => [],
            'counts' => [self::RESULT_PASS => 0, self::RESULT_FAIL => 0, self::RESULT_PENDING => 0]];
        if (! self::enabled()) {
            return $empty + ['message' => 'Re-exam is not set up yet (run the migrations).'];
        }
        $link = Reexam::with(['candidates', 'mainDrive'])->where('reexam_drive_id', $drive->id)->first();
        if (! $link) {
            return $empty + ['message' => 'This is not a re-exam drive.'];
        }
        $drive->loadMissing(['componentMaps.subject']);
        $maps = $drive->componentMaps->sortBy('id')->groupBy('subject_master_pk');
        $marks = $this->results->marks([(int) $drive->id]);
        $participants = $this->results->participants($drive);
        $students = $this->students($link->candidates->pluck('student_master_pk')->unique()->values()->all());

        $rows = [];
        foreach ($link->candidates as $c) {
            $pk = (int) $c->student_master_pk;
            $subjectPk = (int) $c->subject_master_pk;
            $subjectMaps = $maps->get($subjectPk, collect());
            $student = $students[$pk] ?? null;
            $p = $participants[$pk] ?? null;

            $result = $subjectMaps->isEmpty() ? self::RESULT_PENDING : self::RESULT_PASS;
            $absent = false;
            $got = 0.0;
            $max = 0.0;
            foreach ($subjectMaps as $map) {
                $m = $marks[$drive->id.':'.$subjectPk.':'.$map->component_master_pk][$pk] ?? null;
                if (! self::markIsFinal($m, true)) {
                    $result = self::RESULT_PENDING;
                    break;
                }
                $value = self::markValue($m, true);
                $got += $value;
                $max += (float) $map->max_marks;
                if ($m->is_absent) {
                    $absent = true;
                    $result = self::RESULT_FAIL;
                } elseif ($value < ResultMarksService::passingMarks((float) $map->max_marks, $student->service_code ?? null)) {
                    $result = self::RESULT_FAIL;
                }
            }

            $rows[] = [
                'key' => $subjectPk.':'.$pk,
                'student_pk' => $pk,
                'name' => (string) ($p['name'] ?? ''),
                'ot_code' => (string) ($student->ot_code ?? ''),
                'roll' => (string) ($p['roll'] ?? ''),
                'service' => (string) ($student->service_code ?? ''),
                'subject_pk' => $subjectPk,
                'subject' => trim((string) ($subjectMaps->first()?->subject->subject_name ?? '')) ?: 'Subject #'.$subjectPk,
                'previous' => ReexamCandidate::REASONS[$c->reason] ?? '',
                'previous_score' => (float) $c->score_percent,
                'result' => $result,
                'absent' => $result === self::RESULT_FAIL && $absent,
                'score' => $result === self::RESULT_PENDING || $max <= 0 ? null : round($got / $max * 100, 2),
            ];
        }
        usort($rows, fn ($a, $b) => ResultMarksService::compareOtCodes($a['roll'] ?: $a['ot_code'], $b['roll'] ?: $b['ot_code'])
            ?: strnatcasecmp($a['subject'], $b['subject']));

        $ots = [];
        foreach ($rows as $r) {
            $ot = $ots[$r['student_pk']] ?? [
                'student_pk' => $r['student_pk'], 'name' => $r['name'], 'roll' => $r['roll'], 'ot_code' => $r['ot_code'],
                'service' => $r['service'], 'subjects' => [], 'result' => self::RESULT_PASS,
            ];
            $ot['subjects'][] = ['pk' => $r['subject_pk'], 'name' => $r['subject'], 'result' => $r['result']];
            if ($r['result'] === self::RESULT_PENDING) {
                $ot['result'] = self::RESULT_PENDING;
            } elseif ($r['result'] === self::RESULT_FAIL && $ot['result'] !== self::RESULT_PENDING) {
                $ot['result'] = self::RESULT_FAIL;
            }
            $ots[$r['student_pk']] = $ot;
        }
        $counts = array_count_values(array_column($ots, 'result')) + $empty['counts'];

        return [
            'ready' => $counts[self::RESULT_PENDING] === 0 && $ots !== [],
            'message' => $ots === [] ? 'This re-exam has no OTs.'
                : ($counts[self::RESULT_PENDING] ? $counts[self::RESULT_PENDING].' of '.count($ots).' OTs have re-exam marks not approved yet.' : null),
            'drive' => self::driveName($drive),
            'main' => $link->mainDrive ? self::driveName($link->mainDrive) : '',
            'reexam_no' => (int) $link->reexam_no,
            'rows' => $rows,
            'ots' => $ots,
            'counts' => $counts,
        ];
    }

    public static function driveName(ExaminationDrive $drive): string
    {
        return filled($drive->drive_name) ? trim($drive->drive_name) : 'Examination Drive #'.$drive->id;
    }

    /* ───────────── Edit Re-exam (name + dates) ───────────── */

    /**
     * Why the re-exam drive cannot be edited, or null. Same lock as a main drive: not
     * once it is Closed, pending at the CC or approved by the CC.
     */
    public static function editRefusal(ExaminationDrive $drive): ?string
    {
        if ($drive->status === ExaminationDrive::STATUS_CLOSED) {
            return 'A closed drive cannot be edited.';
        }
        if ($drive->cc_status === ExaminationDrive::CC_APPROVED) {
            return 'This re-exam has been approved by the Course Coordinator and cannot be edited.';
        }
        if ($drive->cc_status === ExaminationDrive::CC_PENDING) {
            return 'This re-exam is pending at the Course Coordinator and cannot be edited.';
        }

        return null;
    }

    /**
     * Edit Re-exam: only the name and the re-exam dates. Course, term, type, subjects,
     * faculty and OTs stay as created (copied from the main drive). An exam range the
     * faculty already set must stay inside the new dates. Locked like a main drive
     * (editRefusal), checked again under a row lock.
     *
     * @throws ResultMarksException
     */
    public function update(ExaminationDrive $drive, string $name, string $start, string $end, ?User $user = null): ExaminationDrive
    {
        if (! self::isReexamDrive((int) $drive->id)) {
            throw new ResultMarksException('This is not a re-exam drive.');
        }

        $drive = DB::transaction(function () use ($drive, $name, $start, $end) {
            $drive = ExaminationDrive::whereKey($drive->id)->lockForUpdate()->firstOrFail();
            if ($refusal = self::editRefusal($drive)) {
                throw new ResultMarksException($refusal);
            }
            foreach (ExaminationDriveComponentMap::with(['subject', 'component'])->where('examination_drive_id', $drive->id)
                ->whereNotNull('exam_date')->orderBy('id')->get() as $map) {
                $from = $map->exam_date->toDateString();
                $to = ($map->exam_end_date ?? $map->exam_date)->toDateString();
                if ($from < $start || $to > $end) {
                    $what = trim(($map->subject->subject_name ?? '').' · '.($map->component->component_name ?? ''), ' ·');
                    throw new ResultMarksException("{$what} is set for {$map->exam_date->format('d-m-Y')}"
                        .($to !== $from ? ' to '.($map->exam_end_date ?? $map->exam_date)->format('d-m-Y') : '')
                        .', outside the new re-exam dates. Ask the faculty to change it first, or keep the dates around it.');
                }
            }
            $drive->fill(['drive_name' => trim($name), 'start_date' => $start, 'end_date' => $end]);
            if (array_key_exists('modified_date', $drive->getAttributes())) {
                $drive->modified_date = now();
            }
            $drive->save();

            return $drive;
        });

        Log::info('COE re-exam edited', ['reexam_drive_id' => $drive->id, 'start' => $start, 'end' => $end, 'by' => $user->user_id ?? null]);

        return $drive;
    }

    /* ───────────── View (Faculty Coordinates & Components) ───────────── */

    /**
     * What the View page adds for re-exams:
     *   re-exam drive  its main drive, the main drive's components of the re-exam subjects
     *                  (exam dates, marks) and the candidates with reason, main score %,
     *                  re-exam score % and result (result()).
     *   main drive     its re-exams (no., name, dates, status, OTs).
     * Null for a drive with neither.
     *
     * @return array<string, mixed>|null
     */
    public function overview(ExaminationDrive $drive): ?array
    {
        if (! self::enabled()) {
            return null;
        }
        $link = Reexam::with('mainDrive.componentMaps.subject', 'mainDrive.componentMaps.component', 'mainDrive.course', 'mainDrive.examinationType')
            ->where('reexam_drive_id', $drive->id)->first();
        if ($link) {
            $main = $link->mainDrive;
            $subjects = ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)->distinct()->pluck('subject_master_pk')
                ->map(fn ($pk) => (int) $pk)->all();

            return [
                'type' => 'reexam',
                'no' => (int) $link->reexam_no,
                'main' => $main ? [
                    'id' => (int) $main->id,
                    'name' => self::driveName($main),
                    'exam_type' => $main->examinationType->exam_type_name ?? '-',
                    'course' => $main->course->course_name ?? '-',
                    'start_date' => optional($main->start_date)->format('d-m-Y') ?? '-',
                    'end_date' => optional($main->end_date)->format('d-m-Y') ?? '-',
                    'status' => $main->progress()[0],
                    'components' => $main->componentMaps->whereIn('subject_master_pk', $subjects)->sortBy('id')
                        ->map(fn (ExaminationDriveComponentMap $map) => [
                            'subject' => trim((string) ($map->subject->subject_name ?? '')) ?: '-',
                            'component' => $map->component->component_name ?? '-',
                            'max_marks' => $map->max_marks,
                            'passing_marks' => ResultMarksService::passingMarksLabel($map->max_marks === null ? null : (float) $map->max_marks),
                            'exam_range' => self::examRange($map),
                            'exam_mode' => $map->exam_mode ?: '-',
                        ])->values()->all(),
                ] : null,
                'result' => $this->result($drive),
            ];
        }

        $reexams = Reexam::with('reexamDrive')->withCount('candidates')->where('main_drive_id', $drive->id)->orderBy('reexam_no')->get();
        if ($reexams->isEmpty()) {
            return null;
        }

        return [
            'type' => 'main',
            'reexams' => $reexams->filter(fn (Reexam $r) => $r->reexamDrive)->map(fn (Reexam $r) => [
                'id' => (int) $r->reexam_drive_id,
                'no' => (int) $r->reexam_no,
                'name' => self::driveName($r->reexamDrive),
                'start_date' => optional($r->reexamDrive->start_date)->format('d-m-Y') ?? '-',
                'end_date' => optional($r->reexamDrive->end_date)->format('d-m-Y') ?? '-',
                'status' => $r->reexamDrive->progress()[0],
                'ots' => (int) DB::table('coe_reexam_candidates')->where('reexam_id', $r->id)->distinct()->count('student_master_pk'),
                'subjects' => (int) $r->candidates_count,
            ])->values()->all(),
        ];
    }

    /** "10-11-2026 10:00 to 10-11-2026 13:00", the dates when no times, '-' when not set. */
    private static function examRange(ExaminationDriveComponentMap $map): string
    {
        if (! $map->exam_date && ! $map->exam_start_at) {
            return '-';
        }
        $from = $map->exam_start_at ? $map->exam_start_at->format('d-m-Y H:i') : $map->exam_date->format('d-m-Y');
        $to = $map->exam_end_at ? $map->exam_end_at->format('d-m-Y H:i') : optional($map->exam_end_date ?? $map->exam_date)->format('d-m-Y');

        return $to && $to !== $from ? $from.' to '.$to : $from;
    }

    /* ───────────── Re-exam drive setup (faculty) and CC approval ───────────── */

    /**
     * The faculty setup rows to save for one subject of a re-exam drive: everything as
     * stored (copied from the main drive), only the Tentative Exam Range taken from the
     * request (exam_start_at / exam_end_at with times, or exam_date / exam_end_date from
     * the admin page). Components cannot be added or removed.
     *
     * @param  list<array<string, mixed>>  $requested  the validated request rows
     * @return list<array<string, mixed>>  rows in the shape ExaminationDriveController::saveComponents reads
     *
     * @throws ResultMarksException when a component has no range
     */
    public function setupRows(ExaminationDrive $drive, int $subjectPk, array $requested): array
    {
        $byComponent = collect($requested)->keyBy(fn ($r) => trim((string) ($r['component'] ?? '')));
        $rows = [];
        foreach (ExaminationDriveComponentMap::with(['component', 'faculty'])->where('examination_drive_id', $drive->id)
            ->where('subject_master_pk', $subjectPk)->orderBy('id')->get() as $map) {
            $name = trim((string) ($map->component->component_name ?? ''));
            $in = $byComponent->get($name, []);
            if (blank($in['exam_start_at'] ?? null) && blank($in['exam_date'] ?? null)) {
                throw new ResultMarksException("Select the Tentative Exam Range for {$name}.");
            }
            $row = [
                'component' => $name,
                'max_marks' => $map->max_marks,
                'source' => $map->source,
                'faculty' => trim((string) ($map->faculty->full_name ?? '')),
                'weightage' => $map->weightage,
                'exam_mode' => $map->exam_mode,
                'exam_type' => $map->exam_type,
                'sub_faculty' => json_encode(array_values($map->sub_faculty_names ?? [])),
                'codes' => json_encode((object) ($map->faculty_codes ?? [])),
                'unique_codes' => json_encode((object) ($map->faculty_unique_codes ?? [])),
            ];
            foreach (['exam_start_at', 'exam_end_at', 'exam_date', 'exam_end_date'] as $key) {
                if (filled($in[$key] ?? null)) {
                    $row[$key] = $in[$key];
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Subjects of a re-exam drive that are set up: every component has its Tentative
     * Exam Range (the copied rows exist from the start, so "has rows" is not enough).
     *
     * @return list<int> subject pks
     */
    public static function datedSubjects(int $driveId): array
    {
        return ExaminationDriveComponentMap::where('examination_drive_id', $driveId)->get(['subject_master_pk', 'exam_date'])
            ->groupBy('subject_master_pk')
            ->filter(fn ($maps) => $maps->every(fn ($m) => $m->exam_date !== null))
            ->keys()->map(fn ($pk) => (int) $pk)->values()->all();
    }

    /** @return array<int, object> student pk => {ot_code, service_code} */
    private function students(array $pks): array
    {
        if ($pks === []) {
            return [];
        }

        return DB::table('student_master as s')
            ->leftJoin('service_master as sv', 'sv.pk', '=', 's.service_master_pk')
            ->whereIn('s.pk', $pks)
            ->get(['s.pk', 's.generated_OT_code as ot_code', DB::raw('TRIM(sv.service_short_name) as service_code')])
            ->keyBy('pk')
            ->all();
    }

    /* ───────────── Create ───────────── */

    /**
     * Create the next re-exam of $main for the picked rows ("subject:student" keys from
     * sheet()). The list is worked out again under a lock, so a stale or forged key is
     * refused rather than saved.
     *
     * @param  list<string>  $keys
     * @return array{drive: ExaminationDrive, reexam: Reexam, notice: string}
     */
    public function create(ExaminationDrive $main, User $user, array $keys, string $start, string $end, ?string $name): array
    {
        $keys = array_values(array_unique(array_map('strval', $keys)));
        if ($keys === []) {
            throw new ResultMarksException('Tick at least one OT for the re-exam.');
        }

        [$drive, $reexam, $subjects] = DB::transaction(function () use ($main, $user, $keys, $start, $end, $name) {
            // One re-exam at a time per main drive.
            $main = ExaminationDrive::whereKey($main->id)->lockForUpdate()->firstOrFail();
            $sheet = $this->sheet($main);
            if (! $sheet['ready']) {
                throw new ResultMarksException($sheet['message'] ?? 'This drive cannot have a re-exam yet.');
            }
            $rows = collect($sheet['rows'])->keyBy('key');
            $unknown = array_diff($keys, $rows->keys()->all());
            if ($unknown !== []) {
                throw new ResultMarksException('The list has changed since it was opened. Close it and open Re-exam again.');
            }
            $picked = $rows->only($keys);
            $subjects = $picked->pluck('subject_pk')->unique()->values()->all();
            $no = $sheet['next_no'];
            $now = now();

            $drive = $main->replicate()->unsetRelations();
            $drive->fill([
                'drive_name' => filled($name) ? trim($name) : self::defaultName($main, $no),
                'status' => ExaminationDrive::STATUS_ACTIVE,
                'start_date' => $start,
                'end_date' => $end,
                'created_by' => auth()->id(),
                'created_date' => $now,
                'modified_date' => $now,
            ]);
            // A fresh drive for the faculty and the CC: nothing of the main drive's approval.
            foreach (['cc_status', 'cc_remarks', 'cc_action_by', 'cc_action_at', 'proposed_start_date', 'proposed_end_date'] as $column) {
                if (array_key_exists($column, $main->getAttributes())) {
                    $drive->{$column} = null;
                }
            }
            $drive->save();

            ExaminationDriveFacultyMap::where('examination_drive_id', $main->id)->whereIn('subject_master_pk', $subjects)->orderBy('id')->get()
                ->each(function (ExaminationDriveFacultyMap $map) use ($drive, $now) {
                    $copy = $map->replicate();
                    $copy->examination_drive_id = $drive->id;
                    if (array_key_exists('created_date', $map->getAttributes())) {
                        $copy->created_date = $now;
                    }
                    $copy->save();
                });

            ExaminationDriveComponentMap::where('examination_drive_id', $main->id)->whereIn('subject_master_pk', $subjects)->orderBy('id')->get()
                ->each(function (ExaminationDriveComponentMap $map) use ($drive, $now) {
                    $copy = $map->replicate();
                    $copy->examination_drive_id = $drive->id;
                    // Exam dates are new: the faculty sets them, the CC confirms them.
                    foreach (['exam_date', 'exam_end_date', 'exam_start_at', 'exam_end_at', 'proposed_start_at', 'proposed_end_at'] as $column) {
                        if (array_key_exists($column, $map->getAttributes())) {
                            $copy->{$column} = null;
                        }
                    }
                    foreach (['created_date', 'modified_date'] as $column) {
                        if (array_key_exists($column, $map->getAttributes())) {
                            $copy->{$column} = $now;
                        }
                    }
                    $copy->save();
                });

            $reexam = Reexam::create([
                'main_drive_id' => $main->id,
                'reexam_drive_id' => $drive->id,
                'reexam_no' => $no,
                'created_by' => $user->user_id ?? null,
                'created_at' => $now,
            ]);
            ReexamCandidate::insert($picked->map(fn ($r) => [
                'reexam_id' => $reexam->id,
                'subject_master_pk' => $r['subject_pk'],
                'student_master_pk' => $r['student_pk'],
                'reason' => $r['reason'],
                'score_percent' => $r['score'],
                'created_at' => $now,
            ])->values()->all());

            return [$drive, $reexam, $subjects];
        });

        Log::info('COE re-exam created', [
            'main_drive_id' => $main->id, 'reexam_drive_id' => $drive->id, 'reexam_no' => $reexam->reexam_no,
            'candidates' => count($keys), 'by' => $user->user_id ?? null,
        ]);

        return ['drive' => $drive, 'reexam' => $reexam, 'notice' => $this->notifyFaculty($drive, $subjects)];
    }

    /** "Law Drive — Re-exam 1" */
    public static function defaultName(ExaminationDrive $main, int $no): string
    {
        $base = filled($main->drive_name) ? trim($main->drive_name) : 'Examination Drive #'.$main->id;

        return mb_substr($base, 0, 130).' — Re-exam '.$no;
    }

    /**
     * Bell to each faculty of the re-exam's subjects: set the exam dates. Opens their
     * setup page (config/notifications.php exam_drive.ExaminationDrive). Never fails the create.
     */
    private function notifyFaculty(ExaminationDrive $drive, array $subjectPks): string
    {
        try {
            $bySubject = ExaminationDriveFacultyMap::with('subject')->where('examination_drive_id', $drive->id)->get()
                ->groupBy('faculty_master_pk')
                ->map(fn ($maps) => $maps->map(fn ($m) => trim((string) ($m->subject->subject_name ?? '')))->filter()->unique()->values()->all());
            $userIds = FacultyMaster::whereIn('pk', $bySubject->keys())
                ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
                ->pluck('employee_master_pk', 'pk');
            $service = app(NotificationService::class);
            foreach ($userIds as $facultyPk => $userId) {
                $service->create((int) $userId, 'exam_drive', 'ExaminationDrive', (int) $drive->id, 'Re-exam Created',
                    "{$drive->drive_name} has been created for ".implode(', ', $bySubject[$facultyPk] ?? [])
                        .'. Set the Tentative Exam Range of each component.');
            }
            $missing = $bySubject->count() - $userIds->count();

            return trim(($userIds->count() ? $userIds->count().' faculty notified.' : '')
                .($missing ? " {$missing} faculty could not be notified (no login linked)." : ''));
        } catch (\Throwable $e) {
            Log::error('COE re-exam notification failed', ['drive_id' => $drive->id, 'error' => $e->getMessage()]);

            return '';
        }
    }
}
