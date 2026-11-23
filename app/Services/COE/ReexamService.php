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
 * exam is over, **every participant's marksheet on the course is published** (UDC → AAO →
 * COE signed; marksheetRefusal) and every participant's mark in every subject is final
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
 * Re-exam drives stay out of Result Entry, Compilation, Merit, Moderation, Marksheet and
 * Venue Allocation (excludeFrom()) until those steps learn about re-exams.
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
        $published = self::publishedMarksheets([(int) $main->course_master_pk]);
        if ($refusal = self::marksheetRefusal($main, $participants, $published)) {
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
        // Only once the course's marksheets are published (one query for the page).
        $published = self::publishedMarksheets($mains->pluck('course_master_pk')->map(fn ($pk) => (int) $pk)->unique()->all());
        $mains = $mains->filter(fn (ExaminationDrive $d) => self::marksheetRefusal($d, $this->results->participants($d), $published) === null);
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
     * Agreed flow: a re-exam opens only after the marksheets are published (UDC → AAO →
     * COE signed, coe_marksheets.status = Published). Every participant of the drive needs
     * a published marksheet on the drive's course; else why not, or null.
     *
     * @param  array<int, array<int, true>>  $published  course pk => student pk => true
     */
    private static function marksheetRefusal(ExaminationDrive $main, array $participants, array $published): ?string
    {
        if (! MarksheetService::enabled()) {
            return 'Marksheets are not set up yet, so a re-exam cannot open.';
        }
        if ($participants === []) {
            return 'The drive has no participants.';
        }
        $have = $published[(int) $main->course_master_pk] ?? [];
        $missing = count(array_diff_key($participants, $have));

        return $missing === 0 ? null
            : 'The marksheets are not published yet ('.(count($participants) - $missing).' of '.count($participants)
                .' OTs). A re-exam opens once UDC, AAO and COE have signed the marksheets.';
    }

    /** @return array<int, array<int, true>> course pk => student pk => true, for published marksheets */
    private static function publishedMarksheets(array $coursePks): array
    {
        if ($coursePks === [] || ! MarksheetService::enabled()) {
            return [];
        }
        $out = [];
        DB::table('coe_marksheets')->whereIn('course_master_pk', $coursePks)
            ->where('status', \App\Models\COE\Marksheet::STATUS_PUBLISHED)
            ->get(['course_master_pk', 'student_master_pk'])
            ->each(function ($r) use (&$out) {
                $out[(int) $r->course_master_pk][(int) $r->student_master_pk] = true;
            });

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
