<?php

namespace App\Http\Controllers\Admin\COE;

use App\Exports\COE\RollNumberImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\COE\RollNumberImport;
use App\Models\COE\RollNumber;
use App\Models\COE\RollNumberAllocation;
use App\Models\StudentMaster;
use App\Services\COE\RollNumberAllocator;
use App\Services\COE\RollNumberWriter;
use App\Services\NotificationReceiverService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * COE → Examination Management → Roll Numbers.
 *
 * The grid is a client-side CoeGrid table: index() renders every row of the chosen scope
 * and the toolbar filters run in the browser. Every write is an AJAX endpoint answering
 * JSON — `message` always, plus whatever the page needs to repaint the affected rows.
 *
 * Roll numbers are per course + batch: one coe_roll_number_allocations row per pair, one
 * coe_roll_numbers row per participant (student_master_pk is UNIQUE, so the left join in
 * filteredQuery() cannot fan a participant out into two rows).
 */
class RollNumberController extends Controller
{
    /** Scope tabs. `all` is the landing tab. */
    public const SCOPES = ['all', 'generated', 'frozen'];

    /** Row states the grid renders. A participant with no roll number is `not_generated`. */
    private const STATE_NOT_GENERATED = 'not_generated';
    private const STATE_GENERATED = 'generated';
    private const STATE_FROZEN = 'frozen';

    /** Badge text per state — Pending is the design's word for generated-not-frozen. */
    private const STATE_LABELS = [
        self::STATE_NOT_GENERATED => 'Not Generated',
        self::STATE_GENERATED => 'Pending',
        self::STATE_FROZEN => 'Frozen',
    ];

    /**
     * Ceiling on one generation run. The rules are about neighbours, so the whole cohort
     * is held in memory to be ordered; this stops a mis-selected pair from turning into
     * an unbounded allocation.
     */
    private const MAX_PARTICIPANTS_PER_RUN = 2000;

    /** Prefix characters accepted everywhere a prefix is typed. */
    private const PREFIX_REGEX = 'regex:/^[A-Za-z0-9\-\/]+$/';

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'all';

        $writer = app(RollNumberWriter::class);

        $rows = $this->filteredQuery($scope)->get()->map(function (StudentMaster $student) use ($writer) {
            $roll = $this->rollStateFor($student);
            $parsed = $roll['roll_number'] !== null ? $writer->parse($roll['roll_number']) : null;

            return [
                'student_pk' => (int) $student->pk,
                'roll_pk' => $roll['pk'],
                'name' => $this->studentName($student),
                'service' => (string) ($student->service_short_name ?: ($student->service_name ?: '—')),
                'service_pk' => (string) ($student->service_master_pk ?? ''),
                'cadre' => (string) ($student->cadre_name ?: '—'),
                'cadre_pk' => (string) ($student->cadre_master_pk ?? ''),
                'gender' => (string) ($student->gender ?: '—'),
                'ot_code' => (string) ($student->generated_OT_code ?: '—'),
                'roll_number' => $roll['roll_number'],
                'state' => $roll['state'],
                'status' => self::STATE_LABELS[$roll['state']],
                'course_pk' => (int) $student->course_master_pk,
                'course' => (string) ($student->course_name ?: ''),
                'batch' => (string) ($student->batch ?? ''),
                // Pre-fills the Edit dialog: the digits are kept as typed so 001 stays 001.
                'prefix' => $parsed['prefix'] ?? '',
                'number' => $parsed !== null && $parsed['serial'] !== null
                    ? str_pad((string) $parsed['serial'], $parsed['padding'], '0', STR_PAD_LEFT)
                    : '',
            ];
        });

        // Filter options come from the rows on screen: an option that can only ever
        // return nothing is worse than no option.
        $optionsFrom = fn (string $key, string $label) => $rows
            ->filter(fn ($r) => $r[$key] !== '' && $r[$key] !== '0')
            ->mapWithKeys(fn ($r) => [$r[$key] => $r[$label]])
            ->unique()
            ->sort();

        $courseBatches = $this->courseBatchMap();

        return view('admin.coe.roll_number.index', [
            'scope' => $scope,
            'rows' => $rows,
            'filterCourses' => $optionsFrom('course_pk', 'course'),
            'filterServices' => $optionsFrom('service_pk', 'service'),
            'filterCadres' => $optionsFrom('cadre_pk', 'cadre'),
            'filterBatches' => $rows->pluck('batch')->filter()->unique()->sortDesc()->values(),
            // Every course that has participants with a batch — the Generate and Add
            // Single Record pickers, with the batches each course actually has.
            'courses' => $courseBatches['courses'],
            'courseBatches' => $courseBatches['batches'],
            'strategies' => RollNumberAllocation::strategies(),
            // The strategies that honour the rule checkboxes; the form hides the rules for
            // the rest. Derived from the allocator so the screen cannot disagree with it.
            'ruleStrategies' => array_values(array_filter(
                array_keys(RollNumberAllocation::strategies()),
                fn (string $key) => app(RollNumberAllocator::class)->appliesRules($key)
            )),
            'otCodeStrategy' => RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_OT_CODE,
        ]);
    }

    /**
     * Generate roll numbers for one course+batch.
     *
     * Ordering comes from RollNumberAllocator; this method owns the persistence and the
     * workflow guards around it.
     */
    public function generate(Request $request, RollNumberAllocator $allocator): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course' => ['required', 'integer', Rule::exists('course_master', 'pk')],
            'batch' => ['required', 'integer', Rule::exists('student_master', 'batch')],
            'prefix' => ['required', 'string', 'max:20', self::PREFIX_REGEX],
            // A string: the leading zeros are the padding width. Not asked for under
            // Sequential by OT Code, where the serial comes from the code itself.
            'start_number' => [
                'nullable',
                'required_unless:strategy,' . RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_OT_CODE,
                'string',
                'regex:/^\d{1,9}$/',
            ],
            'strategy' => ['required', Rule::in(array_keys(RollNumberAllocation::strategies()))],
            'rules' => ['sometimes', 'array'],
            'rules.*' => [Rule::in([
                RollNumberAllocator::RULE_AVOID_SAME_SERVICE,
                RollNumberAllocator::RULE_ALTERNATE_GENDER,
            ])],
        ], [
            'prefix.regex' => 'The prefix may contain letters, digits, - and / only.',
            'start_number.regex' => 'The starting number must be digits only.',
            'start_number.required_unless' => 'Please give a starting number.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $validated = $validator->validated();
        $derivesFromOtCode = $allocator->derivesSerialFromOtCode($validated['strategy']);

        $startNumber = $derivesFromOtCode ? null : (int) $validated['start_number'];
        if (! $derivesFromOtCode && $startNumber < 1) {
            return $this->fail('The starting number must be at least 1.');
        }

        $courseId = (int) $validated['course'];
        $batch = (int) $validated['batch'];
        $selectedRules = (array) ($validated['rules'] ?? []);

        $existing = $this->existingAllocation($courseId, $batch);
        if ($existing !== null && ($blocker = $this->regenerationBlocker($existing)) !== null) {
            return $this->fail($blocker);
        }

        $participants = $this->participantsFor($courseId, $batch);
        if ($participants->isEmpty()) {
            return $this->fail('No participants are mapped to that course and batch, so there is nothing to number.');
        }

        if ($participants->count() > self::MAX_PARTICIPANTS_PER_RUN) {
            return $this->fail(sprintf(
                'That course and batch has %s participants, above the %s a single run allocates. Narrow the batch or raise the limit deliberately.',
                number_format($participants->count()),
                number_format(self::MAX_PARTICIPANTS_PER_RUN)
            ));
        }

        // Derived before the transaction: a code with no number and two codes with the
        // same number are properties of the cohort, and coe_roll_numbers.roll_number is
        // UNIQUE, so a clash would otherwise surface as an anonymous failed insert.
        $serials = [];
        $padding = strlen((string) ($validated['start_number'] ?? ''));

        if ($derivesFromOtCode) {
            $derived = $this->otCodeSerials($allocator, $participants);
            if ($derived['error'] !== null) {
                return $this->fail($derived['error']);
            }

            $serials = $derived['serials'];
            $startNumber = min($serials);
            $padding = max(3, strlen((string) max($serials)));
        }

        $allocation = $allocator->allocate(
            $participants,
            [
                RollNumberAllocator::RULE_AVOID_SAME_SERVICE => in_array(RollNumberAllocator::RULE_AVOID_SAME_SERVICE, $selectedRules, true),
                // The design has no theatre rule and the allocator cannot apply one (no
                // theatre mapping exists), so it is never requested from this screen.
                RollNumberAllocator::RULE_AVOID_CONSECUTIVE_THEATRE => false,
                RollNumberAllocator::RULE_ALTERNATE_GENDER => in_array(RollNumberAllocator::RULE_ALTERNATE_GENDER, $selectedRules, true),
            ],
            $validated['strategy']
        );

        try {
            $written = DB::transaction(function () use ($validated, $courseId, $batch, $startNumber, $padding, $serials, $selectedRules, $allocation, $existing) {
                $record = $this->persistAllocation($existing, $validated, $courseId, $batch, $startNumber, $padding, $selectedRules, count($allocation['order']));

                // Replace, never merge: regeneration reorders everyone, so a leftover row
                // would hold a roll number that no longer belongs to that position.
                RollNumber::where('roll_number_allocation_pk', $record->pk)->delete();

                return $this->writeRollNumbers($record, $allocation['order'], $startNumber, $serials);
            });
        } catch (\Throwable $e) {
            Log::error('COE roll number generation failed', [
                'course_master_pk' => $courseId,
                'batch' => $batch,
                'exception' => $e->getMessage(),
            ]);

            return $this->fail('Roll numbers could not be generated. ' . $this->generationFailureHint($e), 500);
        }

        return response()->json([
            'message' => $this->generationSummary($written, $allocation['report']),
            'written' => $written,
        ]);
    }

    /**
     * Download the .xlsx an administrator fills in before importing — pre-filled with one
     * row per participant (OT codes are not unique, so the import matches on the id).
     * Scoped to a course and/or batch when given.
     */
    public function importTemplate(Request $request)
    {
        $filters = [
            'course' => trim((string) $request->query('course', '')),
            'batch' => trim((string) $request->query('batch', '')),
        ];

        $participants = $this->filteredQuery(null, $filters)
            ->get()
            ->map(fn (StudentMaster $student) => (object) [
                'pk' => (int) $student->pk,
                'generated_OT_code' => $student->generated_OT_code,
                'display_name' => $this->studentName($student),
                'course_name' => $student->course_name,
                'batch' => $student->batch,
                'roll_number' => $student->roll_number,
            ]);

        return Excel::download(
            new RollNumberImportTemplateExport($participants),
            'RollNumberImportTemplate_' . now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Import roll numbers from a filled-in template. Every row is validated and every
     * failure collected, so one bad row does not discard the rest.
     */
    public function import(Request $request, RollNumberWriter $writer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // `mimes` guesses from content and a genuine .csv guesses as `txt`, so txt is
            // allowed there and the closure pins the client extension down.
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv,txt',
                'max:5120',
                function (string $attribute, $value, \Closure $fail) {
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                        $fail('Upload an .xlsx, .xls or .csv file.');
                    }
                },
            ],
        ], [
            'file.required' => 'Choose a file to import.',
            'file.mimes' => 'Upload an .xlsx, .xls or .csv file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first('file'));
        }

        $import = new RollNumberImport($writer);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('COE roll number import failed', ['exception' => $e->getMessage()]);

            return $this->fail('The file could not be read. Check that it matches the downloaded template.');
        }

        $changed = $import->importedCount + $import->updatedCount;

        return response()->json([
            'message' => $this->importSummary($import),
            'imported' => $import->importedCount,
            'updated' => $import->updatedCount,
            'skipped' => $import->skippedCount,
            'failed' => count($import->failures),
        ], $changed === 0 && $import->failures !== [] ? 422 : 200);
    }

    /** Add or correct one participant's roll number by hand. */
    public function storeSingle(Request $request, RollNumberWriter $writer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course' => ['required', 'integer', Rule::exists('course_master', 'pk')],
            'batch' => ['required', 'integer', Rule::exists('student_master', 'batch')],
            'student_master_pk' => ['required', 'integer', Rule::exists('student_master', 'pk')],
            'prefix' => ['required', 'string', 'max:20', self::PREFIX_REGEX],
            'number' => ['required', 'string', 'regex:/^\d{1,9}$/'],
        ], [
            'student_master_pk.required' => 'Select an OT.',
            'prefix.regex' => 'The prefix may contain letters, digits, - and / only.',
            'number.regex' => 'The number must be digits only.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $validated = $validator->validated();

        // The participant must actually sit the chosen course+batch, or a hand-edited
        // request could file somebody under a course they are not on.
        $participant = DB::table('student_master')
            ->select('pk', 'display_name', 'course_master_pk', 'batch')
            ->where('pk', (int) $validated['student_master_pk'])
            ->where('course_master_pk', (int) $validated['course'])
            ->where('batch', (int) $validated['batch'])
            ->first();

        if ($participant === null) {
            return $this->fail('That participant is not on the chosen course and batch.');
        }

        $existing = RollNumber::where('student_master_pk', $participant->pk)->first();
        if ($existing !== null && $existing->isFrozen()) {
            return $this->fail(sprintf(
                '%s is frozen at %s and cannot be changed.',
                $participant->display_name,
                $existing->roll_number
            ));
        }

        $rollNumber = $this->composeRollNumber(
            (int) $validated['course'],
            (int) $validated['batch'],
            trim($validated['prefix']),
            $validated['number']
        );

        if ($this->rollNumberTaken($rollNumber, (int) $participant->pk)) {
            return $this->fail("Roll number {$rollNumber} already belongs to another participant.");
        }

        try {
            DB::transaction(fn () => $writer->assign($participant, $rollNumber, $existing));
        } catch (\Throwable $e) {
            Log::error('COE single roll number add failed', [
                'student_master_pk' => $participant->pk,
                'exception' => $e->getMessage(),
            ]);

            return $this->fail('The roll number could not be saved.', 500);
        }

        return response()->json([
            'message' => sprintf(
                '%s %s %s.',
                $rollNumber,
                $existing === null ? 'assigned to' : 'updated for',
                $participant->display_name
            ),
            'roll_number' => $rollNumber,
        ]);
    }

    /**
     * Participants of one course+batch for the Add Single Record picker. Everyone is
     * listed — a participant who already has a number shows it, so correcting one is the
     * same two clicks as adding one — and frozen rows are flagged, since the server
     * refuses them.
     */
    public function participants(Request $request): JsonResponse
    {
        $courseId = (int) $request->query('course', 0);
        $batch = (int) $request->query('batch', 0);

        if ($courseId <= 0 || $batch <= 0) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('student_master as sm')
            ->leftJoin('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->where('sm.course_master_pk', $courseId)
            ->where('sm.batch', $batch)
            ->orderByRaw('rn.serial_number is null, rn.serial_number')
            ->orderBy('sm.display_name')
            ->get(['sm.pk', 'sm.display_name', 'sm.generated_OT_code', 'rn.roll_number', 'rn.status as roll_status']);

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'pk' => (int) $row->pk,
                'label' => trim(($row->generated_OT_code ? $row->generated_OT_code . ' – ' : '') . $row->display_name),
                'roll_number' => $row->roll_number,
                'frozen' => (int) ($row->roll_status ?? 0) === RollNumber::STATUS_FROZEN,
            ])->all(),
        ]);
    }

    /**
     * Correct one roll number by hand.
     *
     * serial_number is deliberately left alone: it records the participant's position in
     * the allocation order, which faculty ranges are cut from, and a correction to the
     * printed value must not silently move someone into another evaluator's range.
     */
    public function update(Request $request, int $rollNumber): JsonResponse
    {
        $record = RollNumber::find($rollNumber);
        if ($record === null) {
            return $this->fail('That roll number no longer exists.', 404);
        }

        if ($record->isFrozen()) {
            return $this->fail('This roll number is frozen and can no longer be edited.');
        }

        $validator = Validator::make($request->all(), [
            'prefix' => ['required', 'string', 'max:20', self::PREFIX_REGEX],
            'number' => ['required', 'string', 'regex:/^\d{1,9}$/'],
        ], [
            'prefix.regex' => 'The prefix may contain letters, digits, - and / only.',
            'number.regex' => 'The number must be digits only.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $separator = (string) (RollNumberAllocation::whereKey($record->roll_number_allocation_pk)->value('number_separator') ?? '-');
        $value = trim($validator->validated()['prefix']) . $separator . $validator->validated()['number'];

        if (mb_strlen($value) > 50) {
            return $this->fail('The roll number may not be longer than 50 characters.');
        }

        // Mirrors coe_rn_roll_number_unique; the key is the real guard, this only turns
        // the collision into a readable message.
        if ($this->rollNumberTaken($value, (int) $record->student_master_pk)) {
            return $this->fail("Roll number {$value} already belongs to another participant.");
        }

        $record->fill([
            'roll_number' => $value,
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ])->save();

        return response()->json([
            'message' => "Roll number updated to {$value}.",
            'roll_number' => $value,
        ]);
    }

    /**
     * Freeze one or more roll numbers. Idempotent: an already-frozen row keeps its
     * original frozen_at and is not counted. Freezing does NOT notify — that is notify().
     */
    public function freeze(Request $request): JsonResponse
    {
        $ids = $this->requestedIds($request);
        if ($ids === null) {
            return $this->fail('Select at least one roll number to freeze.');
        }

        $toFreeze = RollNumber::whereIn('pk', $ids)
            ->where('status', RollNumber::STATUS_GENERATED)
            ->pluck('pk')
            ->map(fn ($id) => (int) $id)
            ->all();

        try {
            $frozen = DB::transaction(function () use ($ids, $toFreeze) {
                $count = $toFreeze === [] ? 0 : RollNumber::whereIn('pk', $toFreeze)
                    ->where('status', RollNumber::STATUS_GENERATED)
                    ->update([
                        'status' => RollNumber::STATUS_FROZEN,
                        'frozen_at' => now(),
                        'frozen_by' => Auth::id(),
                        'modified_by' => Auth::id(),
                        'modified_date' => now(),
                    ]);

                $this->syncAllocationFreezeState($ids);

                return $count;
            });
        } catch (\Throwable $e) {
            Log::error('COE roll number freeze failed', ['ids' => $ids, 'exception' => $e->getMessage()]);

            return $this->fail('The roll numbers could not be frozen.', 500);
        }

        return response()->json([
            'message' => $frozen === 0
                ? 'Nothing to freeze — every roll number selected was already frozen.'
                : trans_choice(':count roll number frozen.|:count roll numbers frozen.', $frozen, ['count' => $frozen]),
            'frozen' => $frozen,
            'frozen_ids' => $toFreeze,
        ]);
    }

    /**
     * Send each OT an in-app notification with their roll number.
     *
     * Frozen roll numbers only: a number that can still change must not be announced.
     */
    public function notify(
        Request $request,
        NotificationReceiverService $receivers,
        NotificationService $notifications
    ): JsonResponse {
        $ids = $this->requestedIds($request);
        if ($ids === null) {
            return $this->fail('Select at least one roll number to notify.');
        }

        $records = DB::table('coe_roll_numbers as rn')
            ->join('coe_roll_number_allocations as a', 'a.pk', '=', 'rn.roll_number_allocation_pk')
            ->leftJoin('course_master as c', 'c.pk', '=', 'a.course_master_pk')
            ->whereIn('rn.pk', $ids)
            ->get(['rn.pk', 'rn.student_master_pk', 'rn.roll_number', 'rn.status', 'c.course_name', 'a.batch']);

        $frozen = $records->filter(fn ($r) => (int) $r->status === RollNumber::STATUS_FROZEN);
        $notFrozen = $records->count() - $frozen->count();

        if ($frozen->isEmpty()) {
            return $this->fail('Only frozen roll numbers can be notified — freeze them first, so OTs are never sent a number that may still change.');
        }

        $notified = 0;
        $noAccount = 0;
        $failed = 0;

        foreach ($frozen as $record) {
            $receiverUserId = $receivers->getStudentUserId((int) $record->student_master_pk);
            if (! $receiverUserId) {
                $noAccount++;
                continue;
            }

            try {
                $notifications->create(
                    (int) $receiverUserId,
                    'coe_roll_number',
                    'COE',
                    (int) $record->pk,
                    'Roll number allotted',
                    sprintf(
                        'Your roll number%s is %s.',
                        $record->course_name ? ' for ' . $record->course_name . ' (' . $record->batch . ')' : '',
                        $record->roll_number
                    )
                );
                $notified++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('COE roll number notification failed', [
                    'roll_number_pk' => $record->pk,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $parts = [trans_choice(':count OT notified.|:count OTs notified.', $notified, ['count' => $notified])];
        if ($noAccount > 0) {
            $parts[] = trans_choice(':count OT has no user account and was not notified.|:count OTs have no user account and were not notified.', $noAccount, ['count' => $noAccount]);
        }
        if ($notFrozen > 0) {
            $parts[] = trans_choice(':count roll number is not frozen and was skipped.|:count roll numbers are not frozen and were skipped.', $notFrozen, ['count' => $notFrozen]);
        }
        if ($failed > 0) {
            $parts[] = $failed . ' could not be sent.';
        }

        return response()->json([
            'message' => implode(' ', $parts),
            'notified' => $notified,
            'no_account' => $noAccount,
            'skipped_not_frozen' => $notFrozen,
            'failed' => $failed,
        ], $notified === 0 ? 422 : 200);
    }

    /** Delete one roll number. Frozen rows are refused. */
    public function destroy(int $rollNumber, RollNumberWriter $writer): JsonResponse
    {
        $record = RollNumber::find($rollNumber);
        if ($record === null) {
            return $this->fail('That roll number no longer exists.', 404);
        }

        if ($record->isFrozen()) {
            return $this->fail('This roll number is frozen and can no longer be deleted.');
        }

        $allocationId = (int) $record->roll_number_allocation_pk;
        $record->delete();
        $this->refreshCounts([$allocationId], $writer);

        return response()->json([
            'message' => 'Roll number deleted.',
            'deleted_ids' => [$rollNumber],
        ]);
    }

    /** Delete several roll numbers. Frozen ones are left in place and reported. */
    public function bulkDestroy(Request $request, RollNumberWriter $writer): JsonResponse
    {
        $ids = $this->requestedIds($request);
        if ($ids === null) {
            return $this->fail('Select at least one roll number to delete.');
        }

        $rows = RollNumber::whereIn('pk', $ids)->get(['pk', 'status', 'roll_number_allocation_pk']);
        $deletable = $rows->filter(fn (RollNumber $r) => ! $r->isFrozen());
        $frozenCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return $this->fail($frozenCount > 0
                ? 'Frozen roll numbers cannot be deleted, and every one selected is frozen.'
                : 'None of the selected roll numbers exist any more.');
        }

        $deleteIds = $deletable->pluck('pk')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($deleteIds, $deletable, $writer) {
            RollNumber::whereIn('pk', $deleteIds)
                ->where('status', RollNumber::STATUS_GENERATED)
                ->delete();
            $this->refreshCounts($deletable->pluck('roll_number_allocation_pk')->unique()->all(), $writer);
        });

        $message = trans_choice(':count roll number deleted.|:count roll numbers deleted.', count($deleteIds), ['count' => count($deleteIds)]);
        if ($frozenCount > 0) {
            $message .= ' ' . trans_choice(':count frozen roll number was kept.|:count frozen roll numbers were kept.', $frozenCount, ['count' => $frozenCount]);
        }

        return response()->json([
            'message' => $message,
            'deleted_ids' => $deleteIds,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
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

    private function rollNumberTaken(string $rollNumber, int $exceptStudentPk): bool
    {
        return RollNumber::where('roll_number', $rollNumber)
            ->where('student_master_pk', '!=', $exceptStudentPk)
            ->exists();
    }

    /**
     * The separator is the one the course+batch already uses, so a number added by hand
     * looks like the ones Generate produced.
     */
    private function composeRollNumber(int $courseId, int $batch, string $prefix, string $number): string
    {
        $separator = RollNumberAllocation::where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->value('number_separator') ?? '-';

        return $prefix . $separator . $number;
    }

    /** @param array<int, int> $allocationIds */
    private function refreshCounts(array $allocationIds, RollNumberWriter $writer): void
    {
        RollNumberAllocation::whereIn('pk', $allocationIds)->get()
            ->each(fn (RollNumberAllocation $a) => $writer->refreshParticipantCount($a));
    }

    private function importSummary(RollNumberImport $import): string
    {
        $parts = [];

        if ($import->importedCount > 0) {
            $parts[] = $import->importedCount . ' added';
        }
        if ($import->updatedCount > 0) {
            $parts[] = $import->updatedCount . ' updated';
        }
        if ($import->skippedCount > 0) {
            $parts[] = $import->skippedCount . ' skipped (no roll number in the file)';
        }

        $message = $parts === [] ? 'Nothing was imported.' : ucfirst(implode(', ', $parts)) . '.';

        if ($import->failures === []) {
            return $message;
        }

        // Capped so a file where every row is wrong stays readable; the count is exact.
        $shown = array_slice($import->failures, 0, 5);
        $extra = count($import->failures) - count($shown);

        return $message . ' ' . count($import->failures) . ' row(s) failed — '
            . implode(' ', $shown)
            . ($extra > 0 ? " (+{$extra} more)" : '');
    }

    private function existingAllocation(int $courseId, int $batch): ?RollNumberAllocation
    {
        return RollNumberAllocation::where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->first();
    }

    /**
     * Why an existing allocation may not be regenerated, or null when it may.
     * Regeneration reorders everyone, so one frozen roll number inside the run blocks it.
     */
    private function regenerationBlocker(RollNumberAllocation $allocation): ?string
    {
        if ($allocation->isFrozen()) {
            return 'Roll numbers for that course and batch are frozen and cannot be regenerated.';
        }

        $frozenRows = RollNumber::where('roll_number_allocation_pk', $allocation->pk)
            ->where('status', RollNumber::STATUS_FROZEN)
            ->count();

        if ($frozenRows > 0) {
            return sprintf(
                '%d roll number(s) in that course and batch are already frozen, so the run cannot be regenerated.',
                $frozenRows
            );
        }

        return null;
    }

    /**
     * The participants a run numbers — the same predicate the grid uses, so what
     * Generate numbers is exactly what the screen lists for that pair.
     *
     * @return Collection<int, object>
     */
    private function participantsFor(int $courseId, int $batch): Collection
    {
        return DB::table('student_master as sm')
            ->leftJoin('service_master as svc', 'svc.pk', '=', 'sm.service_master_pk')
            ->select('sm.pk', 'sm.service_master_pk', 'sm.gender', 'sm.display_name', 'sm.generated_OT_code', 'svc.service_name')
            ->where('sm.course_master_pk', $courseId)
            ->where('sm.batch', $batch)
            ->orderBy('sm.pk')
            ->get();
    }

    /**
     * Each participant's serial from the digits of their OT code. A code with no number,
     * or two codes with the same number, is a refusal rather than a guess.
     *
     * @param  Collection<int, object>  $participants
     * @return array{serials: array<int, int>, error: string|null}
     */
    private function otCodeSerials(RollNumberAllocator $allocator, Collection $participants): array
    {
        $serials = [];
        $missing = [];
        $codesBySerial = [];

        foreach ($participants as $participant) {
            $code = trim((string) ($participant->generated_OT_code ?? ''));
            $serial = $allocator->serialFromOtCode($code);

            if ($serial === null) {
                $missing[] = trim((string) ($participant->display_name ?? '')) ?: ('Participant #' . (int) $participant->pk);
                continue;
            }

            $serials[(int) $participant->pk] = $serial;
            $codesBySerial[$serial][] = $code;
        }

        if ($missing !== []) {
            return [
                'serials' => [],
                'error' => 'Sequential by OT Code needs every participant to have a number in their OT code, and '
                    . $this->namedList($missing) . ' do not. Fix the OT codes, or choose another strategy.',
            ];
        }

        $clashes = [];
        foreach ($codesBySerial as $serial => $codes) {
            if (count($codes) > 1) {
                $clashes[] = implode(' and ', array_unique($codes)) . ' (both number ' . $serial . ')';
            }
        }

        if ($clashes !== []) {
            return [
                'serials' => [],
                'error' => 'Sequential by OT Code gives each participant the number in their OT code, and these codes '
                    . 'would collide on the same roll number: ' . $this->namedList($clashes)
                    . '. Fix the OT codes, or choose another strategy.',
            ];
        }

        return ['serials' => $serials, 'error' => null];
    }

    /** @param array<int, string> $items */
    private function namedList(array $items, int $limit = 5): string
    {
        $shown = array_slice($items, 0, $limit);
        $more = count($items) - count($shown);

        return implode(', ', $shown) . ($more > 0 ? sprintf(' and %d more', $more) : '');
    }

    /**
     * Create or update the allocation row the numbers hang off.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, string>  $selectedRules
     */
    private function persistAllocation(
        ?RollNumberAllocation $existing,
        array $validated,
        int $courseId,
        int $batch,
        int $startNumber,
        int $padding,
        array $selectedRules,
        int $participantCount
    ): RollNumberAllocation {
        $attributes = [
            'prefix' => trim($validated['prefix']),
            'start_number' => $startNumber,
            // The digits the admin typed ARE the format: "0001" pads to four.
            'number_padding' => $padding,
            'strategy' => $validated['strategy'],
            'rule_avoid_same_service' => in_array(RollNumberAllocator::RULE_AVOID_SAME_SERVICE, $selectedRules, true),
            'rule_avoid_consecutive_theatre' => false,
            'rule_alternate_gender' => in_array(RollNumberAllocator::RULE_ALTERNATE_GENDER, $selectedRules, true),
            'participant_count' => $participantCount,
            'generated_at' => now(),
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ];

        if ($existing !== null) {
            $existing->fill($attributes)->save();

            return $existing;
        }

        $created = RollNumberAllocation::create($attributes + [
            'course_master_pk' => $courseId,
            'batch' => $batch,
            'created_by' => Auth::id(),
            'created_date' => now(),
        ]);

        // number_separator comes from a column default and formatRollNumber() reads it.
        return $created->refresh();
    }

    /**
     * Write the ordered participants out as roll numbers in chunked inserts.
     *
     * @param  array<int, object>  $ordered
     * @param  array<int, int>  $serials  participant pk => serial for OT-code runs; empty = positional
     */
    private function writeRollNumbers(RollNumberAllocation $allocation, array $ordered, int $startNumber, array $serials = []): int
    {
        $now = now();
        $userId = Auth::id();
        $rows = [];

        foreach ($ordered as $index => $participant) {
            $serial = $serials[(int) $participant->pk] ?? ($startNumber + $index);
            $rows[] = [
                'roll_number_allocation_pk' => $allocation->pk,
                'student_master_pk' => (int) $participant->pk,
                'serial_number' => $serial,
                'roll_number' => $allocation->formatRollNumber($serial),
                'status' => RollNumber::STATUS_GENERATED,
                'created_by' => $userId,
                'created_date' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            RollNumber::insert($chunk);
        }

        return count($rows);
    }

    /**
     * Mark an allocation frozen once every roll number under it is — derived from the
     * rows, since the screen freezes individual participants.
     *
     * @param  array<int, int>  $rollNumberIds
     */
    private function syncAllocationFreezeState(array $rollNumberIds): void
    {
        $allocationIds = RollNumber::whereIn('pk', $rollNumberIds)
            ->distinct()
            ->pluck('roll_number_allocation_pk')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($allocationIds === []) {
            return;
        }

        $stillOpen = RollNumber::whereIn('roll_number_allocation_pk', $allocationIds)
            ->where('status', RollNumber::STATUS_GENERATED)
            ->distinct()
            ->pluck('roll_number_allocation_pk')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fullyFrozen = array_values(array_diff($allocationIds, $stillOpen));
        if ($fullyFrozen === []) {
            return;
        }

        RollNumberAllocation::whereIn('pk', $fullyFrozen)
            ->where('status', RollNumberAllocation::STATUS_GENERATED)
            ->update([
                'status' => RollNumberAllocation::STATUS_FROZEN,
                'frozen_at' => now(),
                'frozen_by' => Auth::id(),
                'modified_by' => Auth::id(),
                'modified_date' => now(),
            ]);
    }

    private function generationFailureHint(\Throwable $e): string
    {
        if (str_contains($e->getMessage(), 'coe_rn_roll_number_unique')) {
            return 'That prefix and starting number are already in use by another course — roll numbers must be unique across the Academy. Choose a different prefix.';
        }

        if (str_contains($e->getMessage(), 'coe_rna_course_batch_unique')) {
            return 'Another generation run for that course and batch finished first. Reload the page to see it.';
        }

        return 'Please try again, and report it if it keeps happening.';
    }

    /** @param array<string, array<string, mixed>> $report */
    private function generationSummary(int $written, array $report): string
    {
        $message = trans_choice(
            ':count roll number generated.|:count roll numbers generated.',
            $written,
            ['count' => $written]
        ) . ' Strategy: ' . RollNumberAllocation::strategyLabel($report['strategy'] ?? null) . '.';

        $notes = [];

        if (! ($report['applies_rules'] ?? true)) {
            $notes[] = 'The allocation rules were not applied — this strategy orders participants directly.';

            if (($report['strategy'] ?? null) === RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_OT_CODE) {
                $notes[] = 'Each serial came from the participant\'s OT code, not from a starting number.';
            }

            return $message . ' ' . implode(' ', $notes);
        }

        $service = $report[RollNumberAllocator::RULE_AVOID_SAME_SERVICE] ?? null;
        if ($service && $service['applied'] && (int) $service['violations'] > 0) {
            $notes[] = sprintf(
                '%d position(s) still have the same service side by side — that course has more participants in one service than the list can separate.',
                $service['violations']
            );
        }

        $gender = $report[RollNumberAllocator::RULE_ALTERNATE_GENDER] ?? null;
        if ($gender && $gender['applied'] && (int) $gender['violations'] > 0) {
            $notes[] = sprintf('%d position(s) could not alternate gender.', $gender['violations']);
        }

        return $notes === [] ? $message : $message . ' ' . implode(' ', $notes);
    }

    // ── Query ───────────────────────────────────────────────────────────────

    /**
     * The one query behind the grid and the import template.
     *
     * Scope `all` lists every participant of each course+batch that HAS an allocation
     * (i.e. has been generated or had a number added), with the roll number left-joined
     * — so a participant missing from the run shows as Not Generated, while the 2,900
     * participants of courses nobody has numbered yet stay off the screen. `generated` /
     * `frozen` list only rows holding a number in that state. `null` applies no scope
     * (the template, which is filtered by course/batch instead).
     *
     * @param  array{course?: string, batch?: string}  $filters
     */
    private function filteredQuery(?string $scope, array $filters = []): Builder
    {
        $filters = array_merge(['course' => '', 'batch' => ''], $filters);

        return StudentMaster::query()
            ->select([
                'student_master.pk',
                'student_master.display_name',
                'student_master.first_name',
                'student_master.middle_name',
                'student_master.last_name',
                'student_master.gender',
                'student_master.generated_OT_code',
                'student_master.batch',
                'student_master.course_master_pk',
                'student_master.service_master_pk',
                'student_master.cadre_master_pk',
                'service_master.service_name',
                'service_master.service_short_name',
                'cadre_master.cadre_name',
                'course_master.course_name',
                // Aliased: student_master has its own `status`, coe_roll_numbers a `pk`.
                'coe_roll_numbers.pk as roll_number_pk',
                'coe_roll_numbers.roll_number',
                'coe_roll_numbers.serial_number',
                'coe_roll_numbers.status as roll_status',
            ])
            ->leftJoin('service_master', 'service_master.pk', '=', 'student_master.service_master_pk')
            ->leftJoin('cadre_master', 'cadre_master.pk', '=', 'student_master.cadre_master_pk')
            ->leftJoin('course_master', 'course_master.pk', '=', 'student_master.course_master_pk')
            ->leftJoin('coe_roll_numbers', 'coe_roll_numbers.student_master_pk', '=', 'student_master.pk')
            ->when($filters['course'] !== '', fn (Builder $q) => $q->where('student_master.course_master_pk', $filters['course']))
            ->when($filters['batch'] !== '', fn (Builder $q) => $q->where('student_master.batch', $filters['batch']))
            ->when($scope === 'all', function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('coe_roll_numbers.pk')
                        ->orWhereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('coe_roll_number_allocations as rna')
                                ->whereColumn('rna.course_master_pk', 'student_master.course_master_pk')
                                ->whereColumn('rna.batch', 'student_master.batch');
                        });
                });
            })
            ->when($scope === 'generated', fn (Builder $q) => $q->where('coe_roll_numbers.status', RollNumber::STATUS_GENERATED))
            ->when($scope === 'frozen', fn (Builder $q) => $q->where('coe_roll_numbers.status', RollNumber::STATUS_FROZEN))
            ->orderBy('course_master.course_name')
            ->orderBy('student_master.batch')
            ->orderByRaw('coe_roll_numbers.serial_number is null, coe_roll_numbers.serial_number')
            ->orderBy('student_master.display_name')
            ->orderBy('student_master.pk');
    }

    /**
     * Roll number + lifecycle state for one row, from the left-joined columns.
     *
     * @return array{pk: int|null, roll_number: string|null, state: string}
     */
    private function rollStateFor(object $row): array
    {
        $rollNumber = $row->roll_number ?? null;

        if ($rollNumber === null || $rollNumber === '') {
            return ['pk' => null, 'roll_number' => null, 'state' => self::STATE_NOT_GENERATED];
        }

        return [
            'pk' => (int) $row->roll_number_pk,
            'roll_number' => (string) $rollNumber,
            'state' => (int) $row->roll_status === RollNumber::STATUS_FROZEN
                ? self::STATE_FROZEN
                : self::STATE_GENERATED,
        ];
    }

    private function studentName(object $student): string
    {
        $display = trim(preg_replace('/\s+/', ' ', (string) $student->display_name));
        if ($display !== '') {
            return $display;
        }

        $parts = array_filter([$student->first_name, $student->middle_name, $student->last_name]);

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts))) ?: '—';
    }

    /**
     * Courses that have participants with a batch, and the batches each one has — the
     * Generate and Add Single Record pickers. A batch with nobody in it can never produce
     * a roll number, so only real pairs are offered.
     *
     * @return array{courses: Collection<int, object>, batches: array<int, array<int, int>>}
     */
    private function courseBatchMap(): array
    {
        $pairs = DB::table('student_master as sm')
            ->join('course_master as c', 'c.pk', '=', 'sm.course_master_pk')
            ->whereNotNull('sm.batch')
            ->where('sm.batch', '>', 0)
            ->whereNotNull('c.course_name')
            ->where('c.course_name', '!=', '')
            ->distinct()
            ->get(['c.pk', 'c.course_name', 'sm.batch']);

        $batches = [];
        foreach ($pairs as $pair) {
            $batches[(int) $pair->pk][] = (int) $pair->batch;
        }
        foreach ($batches as &$list) {
            $list = array_values(array_unique($list));
            rsort($list);
        }
        unset($list);

        $courses = $pairs->unique('pk')
            ->map(fn ($p) => (object) ['pk' => (int) $p->pk, 'course_name' => (string) $p->course_name])
            ->sortBy('course_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return ['courses' => $courses, 'batches' => $batches];
    }
}
