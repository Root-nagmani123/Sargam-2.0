<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\COE\ParticipantCode;
use App\Services\COE\CourseParticipants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * COE → Master Data → Unique Participant Code.
 *
 * One coe_participant_codes row per OT per SUBJECT of a course + batch: the code stands in
 * for the OT when that subject is evaluated. Generate works per course + batch, like Roll
 * Numbers: the same active courses as Examination Drive creation, then one of the batches the
 * course has OTs in, and, independently of the course, every active subject (subject_master).
 *
 * Two screens over the same rows:
 *   index — one row per OT: OT Name, User Name, per course the number of subject codes
 *           held, Status, View.
 *   ot    — one OT's codes (opened by a count, preset to that course, or by View):
 *           Course, Subject, Unique Code, Status, with Freeze / Delete.
 *
 * Codes are generated per course + batch + subject, in name order, as prefix + a running
 * number whose width is the width of "Count Start from" as typed (UC- + 0324 → UC-0324).
 * Generating again never changes a code already given: only OTs without a code for that
 * subject get one, numbered on from the subject's highest code of that prefix. Codes are
 * never edited by hand: delete a pending code and generate again. A frozen code cannot be
 * deleted.
 *
 * Scopes: `active` — courses that are active and not past their end date; `archived` — the rest.
 */
class UniqueParticipantCodeController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    /** Prefix characters accepted everywhere a prefix is typed — same as Roll Numbers. */
    private const PREFIX_REGEX = 'regex:/^[A-Za-z0-9\-\/]+$/';

    /** Same ceiling as one roll-number run. */
    private const MAX_PARTICIPANTS_PER_RUN = 2000;

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope')
            : 'active';

        $rows = $this->rows($scope);

        return view('admin.coe.unique_participant_code.index', [
            'scope' => $scope,
            'rows' => $rows,
            // Generate: the active courses (as Examination Drive) and every active subject.
            'cohorts' => $this->cohorts(),
            'activeSubjects' => $this->activeSubjects(),
            'otCourses' => $this->otCourses($rows),
            'otRows' => $this->otRows($rows),
        ]);
    }

    /**
     * One OT's codes, opened from the OT list: by a course count (?course= presets the
     * Course filter) or by View (every course). Same Active / Archived scope as the list.
     */
    public function ot(Request $request, int $student)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope')
            : 'active';

        $ot = DB::table('student_master as sm')
            ->leftJoin('user_credentials as uc', fn ($j) => $j->on('uc.user_id', '=', 'sm.pk')->where('uc.user_category', 'S'))
            ->where('sm.pk', $student)
            ->first(['sm.pk', 'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name', 'uc.user_name']);
        abort_if($ot === null, 404);

        $rows = $this->rows($scope, $student);
        $course = (int) $request->query('course', 0);

        return view('admin.coe.unique_participant_code.ot', [
            'scope' => $scope,
            'rows' => $rows,
            'otName' => $this->fullName($ot),
            'userName' => $this->clean($ot->user_name) ?: '—',
            'courses' => $rows->pluck('course_name')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'batches' => $rows->pluck('batch')->unique()->sortDesc()->values(),
            'presetCourse' => (string) ($rows->firstWhere('course_pk', $course)['course_name'] ?? ''),
            'cohorts' => $this->cohorts(),
            'activeSubjects' => $this->activeSubjects(),
        ]);
    }

    /** Give codes for one subject to the OTs of one course + batch that have none for it yet. */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course' => ['required', 'integer'],
            'batch' => ['required', 'integer', 'min:1'],
            'subject' => ['required', 'integer'],
            'prefix' => ['required', 'string', 'max:20', self::PREFIX_REGEX],
            'count_start' => ['required', 'string', 'regex:/^\d{1,9}$/'],
        ], [
            'course.required' => 'Select a course.',
            'batch.required' => 'Select a batch.',
            'subject.required' => 'Select a subject.',
            'prefix.regex' => 'The prefix may contain letters, digits, - and / only.',
            'count_start.regex' => 'The start number must be digits only.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $validated = $validator->validated();
        $courseId = (int) $validated['course'];
        $batch = (int) $validated['batch'];
        $subjectId = (int) $validated['subject'];
        $prefix = trim($validated['prefix']);
        $start = (int) $validated['count_start'];
        $padding = strlen($validated['count_start']);

        if ($start < 1) {
            return $this->fail('The start number must be at least 1.');
        }

        // Same "active course" as Examination Drive creation (CourseMaster::activeRunning).
        if (! CourseMaster::activeRunning()->where('pk', $courseId)->exists()) {
            return $this->fail('Codes can be generated only for an active course.');
        }
        if (! $this->activeSubjects()->contains('id', $subjectId)) {
            return $this->fail('Select an active subject.');
        }

        // The OTs of the course + batch, as Roll Numbers takes them: by student_master or by
        // student_master_course__map — see CourseParticipants.
        $participants = DB::table('student_master')
            ->whereIn('pk', CourseParticipants::of($courseId, $batch))
            ->orderBy('display_name')
            ->orderBy('first_name')
            ->orderBy('pk')
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk);

        if ($participants->isEmpty()) {
            return $this->fail('No participants are mapped to that course and batch.');
        }
        if ($participants->count() > self::MAX_PARTICIPANTS_PER_RUN) {
            return $this->fail('That course and batch has more participants than one run may generate.');
        }

        $subjectCodes = ParticipantCode::where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->where('subject_master_pk', $subjectId);

        // An OT keeps the code they have for this subject (pending or frozen): generating
        // again only gives codes to the OTs still without one, e.g. added to the course later.
        $existing = (clone $subjectCodes)->pluck('unique_code', 'student_master_pk')
            ->mapWithKeys(fn ($code, $pk) => [(int) $pk => trim((string) $code)]);
        $missing = $participants->reject(fn ($pk) => $existing->has($pk))->values();
        if ($missing->isEmpty()) {
            return $this->fail("All {$participants->count()} OT(s) of that course and batch already have a code for this subject. Nothing was changed.");
        }

        // New codes carry on after the subject's own codes of the same prefix (UC-0324…0333 → UC-0334).
        $next = $start;
        foreach ($existing as $code) {
            if (str_starts_with($code, $prefix) && ctype_digit($rest = substr($code, strlen($prefix)))) {
                $next = max($next, (int) $rest + 1);
            }
        }

        $codes = [];
        foreach ($missing as $i => $studentPk) {
            $codes[$studentPk] = $prefix . str_pad((string) ($next + $i), $padding, '0', STR_PAD_LEFT);
        }

        if (($longest = max(array_map('mb_strlen', $codes))) > 50) {
            return $this->fail("The codes would be {$longest} characters long; the limit is 50.");
        }

        try {
            DB::transaction(function () use ($codes, $courseId, $batch, $subjectId) {
                // Re-checked under lock: a parallel run for the same subject loses cleanly.
                $taken = ParticipantCode::where('course_master_pk', $courseId)
                    ->where('batch', $batch)
                    ->where('subject_master_pk', $subjectId)
                    ->lockForUpdate()
                    ->get(['student_master_pk', 'unique_code']);
                $clash = $taken->first(fn ($r) => in_array(trim($r->unique_code), $codes, true)
                    || array_key_exists((int) $r->student_master_pk, $codes));
                if ($clash !== null) {
                    throw new \DomainException("Code {$clash->unique_code} is already used in this subject. Choose a different prefix or start number, or reload: someone else may have generated these codes.");
                }

                // Add, never replace: codes already given stay as they are.
                $now = now();
                $rows = [];
                foreach ($codes as $studentPk => $code) {
                    $rows[] = [
                        'student_master_pk' => $studentPk,
                        'course_master_pk' => $courseId,
                        'subject_master_pk' => $subjectId,
                        'batch' => $batch,
                        'unique_code' => $code,
                        'status' => ParticipantCode::STATUS_PENDING,
                        'created_by' => Auth::id(),
                        'created_date' => $now,
                    ];
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    ParticipantCode::insert($chunk);
                }
            });
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('COE participant code generation failed', [
                'course_master_pk' => $courseId,
                'batch' => $batch,
                'subject_master_pk' => $subjectId,
                'exception' => $e->getMessage(),
            ]);

            return $this->fail('The codes could not be generated. Nothing was changed.', 500);
        }

        $first = reset($codes);
        $last = end($codes);

        // The tab the codes appear under, so the page can reload onto it.
        $scope = DB::table('course_master as cm')->where('cm.pk', $courseId)
            ->where(fn ($q) => $this->whereCourseInScope($q, 'active', 'cm'))
            ->exists() ? 'active' : 'archived';

        $kept = $existing->count();

        return response()->json([
            'message' => count($codes) . " code(s) generated, {$first} to {$last}."
                . ($kept ? " {$kept} OT(s) already had a code for this subject and kept it." : ''),
            'written' => count($codes),
            'scope' => $scope,
            'redirect' => route('admin.coe.unique-participant-code.index', ['scope' => $scope]),
        ]);
    }

    /** Freeze one or more codes. Idempotent: an already-frozen code keeps its frozen_at. */
    public function freeze(Request $request): JsonResponse
    {
        $ids = $this->requestedIds($request);
        if ($ids === null) {
            return $this->fail('Select at least one code to freeze.');
        }

        $toFreeze = ParticipantCode::whereIn('pk', $ids)
            ->where('status', ParticipantCode::STATUS_PENDING)
            ->pluck('pk')
            ->map(fn ($id) => (int) $id)
            ->all();

        $frozen = $toFreeze === [] ? 0 : ParticipantCode::whereIn('pk', $toFreeze)
            ->where('status', ParticipantCode::STATUS_PENDING)
            ->update([
                'status' => ParticipantCode::STATUS_FROZEN,
                'frozen_at' => now(),
                'frozen_by' => Auth::id(),
                'modified_by' => Auth::id(),
                'modified_date' => now(),
            ]);

        return response()->json([
            'message' => $frozen === 0
                ? 'Nothing to freeze — every code selected was already frozen.'
                : trans_choice(':count code frozen.|:count codes frozen.', $frozen, ['count' => $frozen]),
            'frozen' => $frozen,
            'frozen_ids' => $toFreeze,
        ]);
    }

    /** Delete one or more codes. Frozen ones are left in place and reported. */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $this->requestedIds($request);
        if ($ids === null) {
            return $this->fail('Select at least one code to delete.');
        }

        $deletable = ParticipantCode::whereIn('pk', $ids)
            ->where('status', ParticipantCode::STATUS_PENDING)
            ->pluck('pk')
            ->map(fn ($id) => (int) $id)
            ->all();
        $kept = count($ids) - count($deletable);

        if ($deletable === []) {
            return $this->fail($kept === 1
                ? 'This code is frozen and can no longer be deleted.'
                : 'The selected codes are frozen and can no longer be deleted.');
        }

        ParticipantCode::whereIn('pk', $deletable)->delete();

        $message = trans_choice(':count code deleted.|:count codes deleted.', count($deletable), ['count' => count($deletable)]);
        if ($kept > 0) {
            $message .= " {$kept} frozen code(s) were kept.";
        }

        return response()->json(['message' => $message, 'deleted_ids' => $deletable]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rows(string $scope, ?int $student = null): Collection
    {
        return DB::table('coe_participant_codes as pc')
            ->join('student_master as sm', 'sm.pk', '=', 'pc.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'pc.course_master_pk')
            ->join('subject_master as sub', 'sub.pk', '=', 'pc.subject_master_pk')
            ->leftJoin('user_credentials as uc', fn ($j) => $j->on('uc.user_id', '=', 'sm.pk')->where('uc.user_category', 'S'))
            ->where(fn ($q) => $this->whereCourseInScope($q, $scope, 'cm'))
            ->when($student !== null, fn ($q) => $q->where('pc.student_master_pk', $student))
            ->orderBy('cm.course_name')
            ->orderByDesc('pc.batch')
            ->orderBy('sub.subject_name')
            ->orderBy('pc.unique_code')
            ->get(['pc.pk', 'pc.unique_code', 'pc.status', 'pc.batch', 'pc.course_master_pk', 'pc.student_master_pk',
                'cm.course_name', 'sub.subject_name', 'uc.user_name',
                'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name'])
            ->map(fn ($r) => [
                'id' => (int) $r->pk,
                'student_pk' => (int) $r->student_master_pk,
                'course_pk' => (int) $r->course_master_pk,
                'course_name' => $this->clean($r->course_name),
                'batch' => (int) $r->batch,
                'subject_name' => $this->clean($r->subject_name),
                'ot_name' => $this->fullName($r),
                'display_name' => $this->clean($r->display_name) ?: '—',
                'user_name' => $this->clean($r->user_name) ?: '—',
                'unique_code' => (string) $r->unique_code,
                'status' => ParticipantCode::statusLabel((int) $r->status),
            ]);
    }

    /**
     * By OT: the courses that have codes in this scope, as the table's course columns.
     *
     * @return Collection<int, array{key: string, label: string}>
     */
    private function otCourses(Collection $rows): Collection
    {
        return $rows->map(fn ($r) => ['key' => (string) $r['course_pk'], 'label' => $r['course_name']])
            ->unique('key')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * By OT: one row per OT with their codes per course + batch.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function otRows(Collection $rows): Collection
    {
        return $rows->groupBy('student_pk')
            ->map(function (Collection $codes) {
                $first = $codes->first();

                return [
                    'student_pk' => $first['student_pk'],
                    'ot_name' => $first['ot_name'],
                    'user_name' => $first['user_name'],
                    // The OT's codes in this tab: what Delete / Freeze Selected act on.
                    'ids' => $codes->pluck('id')->all(),
                    // Frozen only once every code the OT holds is frozen.
                    'status' => $codes->every(fn ($c) => $c['status'] === 'Frozen') ? 'Frozen' : 'Pending',
                    'courses' => $codes->groupBy(fn ($c) => (string) $c['course_pk'])
                        ->map(fn (Collection $c) => $c->map(fn ($x) => [
                            'course' => $x['course_name'],
                            'subject' => $x['subject_name'],
                            'code' => $x['unique_code'],
                            'status' => $x['status'],
                        ])->values()->all())
                        ->all(),
                ];
            })
            ->sortBy('ot_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * The Generate course picker: exactly the courses Examination Drive creation offers
     * (CourseMaster::activeRunning — active and not past its end date), one entry per
     * course, each with the batches it has OTs in (newest first) and their head counts —
     * the same Course → Batch pickers as Roll Numbers. A course with no OTs yet offers its
     * course year with 0 OTs.
     *
     * @return Collection<int, array{key: string, label: string, batches: list<array{batch: int, count: int}>}>
     */
    private function cohorts(): Collection
    {
        $withOts = CourseParticipants::cohorts()->groupBy('course_master_pk');

        return CourseMaster::activeRunning()
            ->whereNotNull('course_name')->where('course_name', '!=', '')
            ->get(['pk', 'course_name', 'course_year'])
            ->map(function ($c) use ($withOts) {
                $batches = ($withOts[(int) $c->pk] ?? collect())
                    ->sortByDesc('batch')
                    ->map(fn ($r) => ['batch' => (int) $r->batch, 'count' => (int) $r->n])
                    ->values()->all();
                if ($batches === []) {
                    $batches = [['batch' => (int) trim((string) $c->course_year) ?: (int) now()->year, 'count' => 0]];
                }

                return ['key' => (string) $c->pk, 'label' => $this->clean($c->course_name), 'batches' => $batches];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Every active subject (subject_master.active_inactive = 1, as Examination Drive's
     * subject list), for Generate — not tied to the course.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    private function activeSubjects(): Collection
    {
        return DB::table('subject_master')
            ->where('active_inactive', 1)
            ->get(['pk', 'subject_name'])
            ->map(fn ($s) => ['id' => (int) $s->pk, 'name' => $this->clean($s->subject_name)])
            ->filter(fn ($s) => $s['name'] !== '')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** Active: active and not past its end date. Archived: everything else. */
    private function whereCourseInScope($query, string $scope, string $alias): void
    {
        $today = now()->toDateString();

        if ($scope === 'active') {
            $query->where("{$alias}.active_inactive", 1)
                ->where(fn ($w) => $w->whereNull("{$alias}.end_date")->orWhere("{$alias}.end_date", '>=', $today));
        } else {
            $query->where("{$alias}.active_inactive", '!=', 1)->orWhereNull("{$alias}.active_inactive")
                ->orWhere("{$alias}.end_date", '<', $today);
        }
    }

    /** OT Name: first, middle and last name (student_master), else the display name. */
    private function fullName(object $s): string
    {
        $name = trim(implode(' ', array_filter(array_map(fn ($p) => $this->clean($p), [$s->first_name ?? null, $s->middle_name ?? null, $s->last_name ?? null]))));

        return $name !== '' ? $name : ($this->clean($s->display_name ?? null) ?: '—');
    }

    /** Trimmed, single-spaced, with HTML entities in old imports (&amp;) decoded. */
    private function clean(?string $value): string
    {
        return CourseParticipants::plainName($value);
    }

    /**
     * The ids[] the bulk endpoints act on, de-duplicated, or null when none were sent.
     *
     * @return array<int, int>|null
     */
    private function requestedIds(Request $request): ?array
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $validator->validated()['ids'])));
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
