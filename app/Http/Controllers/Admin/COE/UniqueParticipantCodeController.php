<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
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
 * One coe_participant_codes row per participant. The grid shows the codes that exist:
 * Course Name (course_master), OT Name (student_master.display_name), DT Name (the OT's
 * login, user_credentials.user_name), Unique Code and Status (Pending / Frozen).
 *
 * Codes are generated per course + batch, in name order, as prefix + a running number
 * whose width is the width of "Count Start from" as typed (UC- + 0324 → UC-0324, UC-0325…).
 * A frozen code cannot be edited, deleted or regenerated.
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
            'courses' => $rows->pluck('course_name')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'cohorts' => $this->cohorts($scope),
        ]);
    }

    /** Generate (or regenerate) the codes of one course + batch. */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course_batch' => ['required', 'regex:/^\d+:\d+$/'],
            'prefix' => ['required', 'string', 'max:20', self::PREFIX_REGEX],
            'count_start' => ['required', 'string', 'regex:/^\d{1,9}$/'],
        ], [
            'course_batch.required' => 'Select a course.',
            'prefix.regex' => 'The prefix may contain letters, digits, - and / only.',
            'count_start.regex' => 'The start number must be digits only.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $validated = $validator->validated();
        [$courseId, $batch] = array_map('intval', explode(':', $validated['course_batch']));
        $prefix = trim($validated['prefix']);
        $start = (int) $validated['count_start'];
        $padding = strlen($validated['count_start']);

        if ($start < 1) {
            return $this->fail('The start number must be at least 1.');
        }

        // On the course by student_master or by student_master_course__map — see CourseParticipants.
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

        // One code per OT: regenerating here must not take an OT's code off another course.
        $elsewhere = CourseParticipants::codesElsewhere($participants->all(), $courseId, $batch);
        if ($elsewhere !== []) {
            return $this->fail(CourseParticipants::elsewhereMessage($elsewhere, 'Unique Participant Code'));
        }

        $frozen = ParticipantCode::whereIn('student_master_pk', $participants)
            ->where('status', ParticipantCode::STATUS_FROZEN)
            ->count();
        if ($frozen > 0) {
            return $this->fail("{$frozen} code(s) in that course and batch are already frozen, so the codes cannot be regenerated.");
        }

        $codes = [];
        foreach ($participants->values() as $i => $studentPk) {
            $codes[$studentPk] = $prefix . str_pad((string) ($start + $i), $padding, '0', STR_PAD_LEFT);
        }

        if (($longest = max(array_map('mb_strlen', $codes))) > 50) {
            return $this->fail("The codes would be {$longest} characters long; the limit is 50.");
        }

        // coe_pc_code_unique is the real guard; this names the clash instead of a failed insert.
        $taken = ParticipantCode::whereIn('unique_code', array_values($codes))
            ->whereNotIn('student_master_pk', $participants)
            ->value('unique_code');
        if ($taken !== null) {
            return $this->fail("Code {$taken} already belongs to a participant of another course. Choose a different prefix or start number.");
        }

        try {
            DB::transaction(function () use ($codes, $courseId, $batch) {
                // Replace, never merge: regeneration renumbers everyone.
                ParticipantCode::whereIn('student_master_pk', array_keys($codes))->delete();

                $now = now();
                $rows = [];
                foreach ($codes as $studentPk => $code) {
                    $rows[] = [
                        'student_master_pk' => $studentPk,
                        'course_master_pk' => $courseId,
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
        } catch (\Throwable $e) {
            Log::error('COE participant code generation failed', [
                'course_master_pk' => $courseId,
                'batch' => $batch,
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

        return response()->json([
            'message' => count($codes) . " code(s) generated, {$first} to {$last}.",
            'written' => count($codes),
            'scope' => $scope,
            'redirect' => route('admin.coe.unique-participant-code.index', ['scope' => $scope]),
        ]);
    }

    /** Correct one code by hand. Course, OT and DT name come from the masters and are not editable here. */
    public function update(Request $request, int $code): JsonResponse
    {
        $record = ParticipantCode::find($code);
        if ($record === null) {
            return $this->fail('That code no longer exists.', 404);
        }
        if ($record->isFrozen()) {
            return $this->fail('This code is frozen and can no longer be edited.');
        }

        $validator = Validator::make($request->all(), [
            'unique_code' => ['required', 'string', 'max:50', self::PREFIX_REGEX],
        ], [
            'unique_code.required' => 'Enter the unique code.',
            'unique_code.regex' => 'The code may contain letters, digits, - and / only.',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $value = trim($validator->validated()['unique_code']);
        if (ParticipantCode::where('unique_code', $value)->where('pk', '!=', $record->pk)->exists()) {
            return $this->fail("Code {$value} already belongs to another participant.");
        }

        $record->fill([
            'unique_code' => $value,
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ])->save();

        return response()->json(['message' => "Code updated to {$value}.", 'unique_code' => $value]);
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
    private function rows(string $scope): Collection
    {
        return DB::table('coe_participant_codes as pc')
            ->join('student_master as sm', 'sm.pk', '=', 'pc.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'pc.course_master_pk')
            // An OT may have no login (or, in old data, two); take one user_name per OT.
            ->leftJoinSub(
                DB::table('user_credentials')->where('user_category', 'S')
                    ->groupBy('user_id')->select('user_id', DB::raw('min(user_name) as user_name')),
                'uc', 'uc.user_id', '=', 'sm.pk'
            )
            ->where(fn ($q) => $this->whereCourseInScope($q, $scope, 'cm'))
            ->orderBy('cm.course_name')
            ->orderBy('pc.batch')
            ->orderBy('pc.unique_code')
            ->get(['pc.pk', 'pc.unique_code', 'pc.status', 'pc.batch', 'cm.course_name',
                'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name', 'uc.user_name'])
            ->map(fn ($r) => [
                'id' => (int) $r->pk,
                'course_name' => (string) $r->course_name,
                'batch' => (int) $r->batch,
                'ot_name' => $this->studentName($r),
                'dt_name' => (string) ($r->user_name ?: '—'),
                'unique_code' => (string) $r->unique_code,
                'status' => ParticipantCode::statusLabel((int) $r->status),
            ]);
    }

    /**
     * The Generate picker: every course + batch with participants in the tab's scope, with its head count.
     *
     * @return Collection<int, array{key: string, label: string, count: int}>
     */
    private function cohorts(string $scope): Collection
    {
        $inScope = DB::table('course_master as cm')
            ->where(fn ($q) => $this->whereCourseInScope($q, $scope, 'cm'))
            ->pluck('cm.pk')
            ->map(fn ($pk) => (int) $pk)
            ->flip();

        return CourseParticipants::cohorts()
            ->filter(fn ($r) => $inScope->has($r->course_master_pk))
            ->values()
            ->map(fn ($r) => [
                'key' => $r->course_master_pk . ':' . $r->batch,
                'label' => $r->course_name . ' — ' . $r->batch,
                'count' => $r->n,
            ]);
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

    private function studentName(object $s): string
    {
        $name = trim((string) ($s->display_name ?? ''));
        if ($name === '') {
            $name = trim(implode(' ', array_filter([$s->first_name ?? null, $s->middle_name ?? null, $s->last_name ?? null])));
        }

        return $name !== '' ? $name : '—';
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
