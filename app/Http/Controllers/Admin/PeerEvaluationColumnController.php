<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PeerEvaluationColumnDataTable;
use App\Exports\PeerEvaluationColumnExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\PeerColumn;
use App\Models\PeerEvent;
use App\Models\PeerGroup;
use App\Support\PeerCourseStatusScope;
use App\Support\PeerGroupSource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Peer Evaluation -> Manage Evaluation Columns.
 *
 * Three levels, each fetched on demand:
 *   index()   - one row per EVENT (course, group count, event name)
 *   groups()  - that event's groups          (child row of level 1)
 *   columns() - that group's columns         (child row of level 2), split into
 *               Rate Peers / Distribute Marks tabs
 *
 * A column is scoped to a group; course_id and event_id are denormalised onto it
 * from the group so the grid and the Rating Type filter can query without a join
 * back through peer_groups.
 *
 * "Distribute Marks" is a CATEGORISATION only at this stage - the evaluation form
 * still scores every column the same way. The one behaviour change is that a
 * column's own max_marks now caps its input, replacing the group-wide cap.
 */
class PeerEvaluationColumnController extends Controller
{
    private const DISPLAY_DATE = 'd/m/Y';

    public function index(PeerEvaluationColumnDataTable $dataTable)
    {
        $status = PeerCourseStatusScope::normalise(request('status_filter', 'active'));

        return $dataTable->render('admin.forms.peer_evaluation.columns.index', [
            'courses' => $this->courseOptions($status),
            'modalCourses' => $this->allCourseOptions(),
            'types' => PeerColumn::TYPES,
            'courseFilter' => (string) request('course_filter', ''),
            'typeFilter' => (string) request('type_filter', ''),
            'statusFilter' => $status,
        ]);
    }

    // ==================== NESTED LEVELS ====================

    /**
     * Level 2: the groups of one event.
     *
     * Returns rendered HTML rather than JSON - the markup is a table with its own
     * chrome, and building it in JavaScript would duplicate the blade.
     */
    public function groups(Request $request, $event)
    {
        $peerEvent = PeerEvent::findOrFail($event);

        $groups = PeerGroup::query()
            ->where('event_id', $peerEvent->id)
            ->withCount('columns')
            ->orderBy('group_name')
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('admin.forms.peer_evaluation.columns._groups', [
                'event' => $peerEvent,
                'groups' => $groups,
            ])->render(),
        ]);
    }

    /**
     * Level 3: one group's columns, both rating types.
     */
    public function columns(Request $request, $group)
    {
        $peerGroup = PeerGroup::with('event')->findOrFail($group);

        $columns = PeerColumn::query()
            ->where('group_id', $peerGroup->id)
            ->orderBy('id')
            ->get()
            ->groupBy('evaluation_type');

        return response()->json([
            'success' => true,
            'html' => view('admin.forms.peer_evaluation.columns._columns', [
                'group' => $peerGroup,
                'byType' => $columns,
                'types' => PeerColumn::TYPES,
            ])->render(),
        ]);
    }

    // ==================== FILTER OPTIONS ====================

    private function courseOptions(string $status)
    {
        $query = CourseMaster::query();

        PeerCourseStatusScope::forCourses($query, $status);
        $this->applyRoleScope($query);

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    /**
     * Every course the user may touch, each tagged active or archive.
     *
     * The modal is deliberately NOT filtered by the Active / Archived pill - a
     * column can legitimately be added to a course that has finished - so without
     * the tag travelling alongside the name there is nothing in the dropdown to
     * tell the two apart.
     *
     * The CASE is CourseMaster::scopeActiveRunning() written per row: doing it in
     * SQL keeps the two definitions identical and tags every course in one pass,
     * where running both scopes would be two queries and could drift.
     */
    private function allCourseOptions()
    {
        $query = CourseMaster::query();
        $this->applyRoleScope($query);

        return $query
            ->selectRaw(
                "course_master.pk, course_master.course_name,
                 CASE WHEN course_master.active_inactive = 1
                           AND course_master.end_date >= ?
                      THEN ? ELSE ? END AS peer_status",
                [now()->toDateString(), PeerCourseStatusScope::ACTIVE, PeerCourseStatusScope::ARCHIVE]
            )
            ->orderBy('course_name')
            ->get()
            ->map(fn ($course) => [
                'id' => (string) $course->pk,
                'name' => $course->course_name,
                'status' => $course->peer_status,
            ]);
    }

    /**
     * get_Role_by_course() returns [] for Admin/Super Admin/PA ("no restriction")
     * and [-1] for a non-admin with no roles ("nothing"), so an empty array must
     * NOT be fed to whereIn.
     */
    private function applyRoleScope($query): void
    {
        $allowed = get_Role_by_course();

        if (! empty($allowed)) {
            $query->whereIn('course_master.pk', $allowed);
        }
    }

    /**
     * Dependent-dropdown feed. Ordered LISTS of {id, name}, never id => name maps:
     * JavaScript reorders numeric-looking object keys, so a map comes back sorted
     * by id and the dropdown stops being alphabetical after a rebuild.
     */
    public function options(Request $request)
    {
        $courseId = $request->query('course_id');
        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $status = $status === null ? null : PeerCourseStatusScope::normalise($status);

        $events = PeerEvent::query();
        if (filled($courseId)) {
            $events->where('course_id', $courseId);
        }
        if ($status !== null) {
            PeerCourseStatusScope::forRelated($events, $status, 'peer_events.course_id');
        }

        // Groups come from Course Group Mapping, keyed by that table's pk. store()
        // turns the chosen one into the peer_groups row group_id stores.
        $groupCourseId = filled($eventId)
            ? (PeerEvent::whereKey($eventId)->value('course_id') ?: $courseId)
            : $courseId;

        $shape = fn ($collection) => $collection
            ->map(fn ($name, $id) => ['id' => (string) $id, 'name' => $name])
            ->values();

        // Which evaluation type each group of this event has already committed to,
        // so the modal can grey out the other one instead of letting the admin fill
        // the form and be refused on submit. assertSingleEvaluationType() is still
        // the authority - this is only the hint.
        $usedTypes = [];

        if (filled($eventId)) {
            $usedTypes = DB::table('peer_columns')
                ->join('peer_groups', 'peer_groups.id', '=', 'peer_columns.group_id')
                ->where('peer_groups.event_id', $eventId)
                ->whereNotNull('peer_groups.group_map_pk')
                ->pluck('peer_columns.evaluation_type', 'peer_groups.group_map_pk')
                ->toArray();
        }

        $payload = [
            'success' => true,
            'events' => $shape($events->orderBy('event_name')->pluck('event_name', 'id')),
            'groups' => PeerGroupSource::options($groupCourseId)
                ->map(fn ($group) => [
                    'id' => (string) $group->pk,
                    'name' => $group->label,
                    'used_type' => $usedTypes[$group->pk] ?? null,
                ])
                ->values(),
        ];

        if ($status !== null) {
            $payload['courses'] = $shape($this->courseOptions($status));
        }

        return response()->json($payload);
    }

    // ==================== CRUD ====================

    /**
     * Add one or more columns to one or more groups in a single submit.
     *
     * Two dimensions here, both arrays:
     *   columns[]    the modal's repeatable Column Name / Max Marks / Remarks card
     *   group_ids[]  the groups that whole set of columns goes on
     * Every column is created on every group, so the result is |columns| x |groups|
     * rows. Doing that here rather than making the user re-fill the form once per
     * group is the whole point of the multi-select.
     *
     * event_id is REQUIRED. It used to be nullable, and a column saved without one
     * still wrote a peer_columns row - but the grid is built event -> group ->
     * column, so that row rendered nowhere and read as a failed save.
     *
     * course_id and event_id on the row are taken from the GROUP rather than
     * trusted from the form - the three selects are only a picker.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Course Group Mapping pks; each linked below into a peer_groups row.
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', Rule::exists(PeerGroupSource::TABLE, 'pk')],
            'event_id' => ['required', 'integer', Rule::exists('peer_events', 'id')],
            'evaluation_type' => ['required', Rule::in(array_keys(PeerColumn::TYPES))],
            // Group-level, and only meaningful for Distribute Marks: it is the one
            // pool an OT shares out across the group, not a per-column cap.
            'buffer_marks' => [
                'required_if:evaluation_type,' . PeerColumn::TYPE_DISTRIBUTE_MARKS,
                'nullable', 'numeric', 'min:0.01', 'max:99999.99',
            ],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.column_name' => ['required', 'string', 'max:255'],
            'columns.*.max_marks' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            // One answer for the whole submit, not one per column. The OT form puts
            // a single remark against the evaluated person rather than one per
            // criterion, so asking per column offered a choice that could not be
            // honoured.
            'has_remarks' => ['required', 'boolean'],
        ], [
            'group_ids.required' => 'Please pick at least one group.',
            'group_ids.*.exists' => 'One of the selected groups no longer exists.',
            'event_id.required' => 'Please pick an event.',
            'buffer_marks.required_if' => 'Please set the Buffer Marks for OTs.',
        ], [
            'group_ids' => 'Group Name',
            'group_ids.*' => 'Group Name',
            'event_id' => 'Event Name',
            'evaluation_type' => 'Evaluation Type',
            'buffer_marks' => 'Buffer Marks for OTs',
            'columns.*.column_name' => 'Column Name',
            'columns.*.max_marks' => 'Max Marks',
            'has_remarks' => 'Remarks',
        ]);

        // The same group twice would write every column twice and then trip the
        // uniqueness check against rows this very request had just inserted.
        $groupIds = array_values(array_unique($validated['group_ids']));

        try {
            $created = DB::transaction(function () use ($validated, $groupIds) {
                // Link EVERY group and check EVERY group's names before writing a
                // single column. Interleaving the two would leave the groups
                // processed first holding new columns while a clash on a later
                // group aborted the request - a half-applied submit.
                $groups = [];

                foreach ($groupIds as $groupId) {
                    $group = PeerGroupSource::link($validated['event_id'], $groupId);

                    if (! $group) {
                        throw ValidationException::withMessages([
                            'group_ids' => 'One of those groups could not be linked. Please pick another.',
                        ]);
                    }

                    $groups[] = $group;
                }

                foreach ($groups as $group) {
                    $this->assertNamesAreUnique($group, $validated['columns']);
                    $this->assertSingleEvaluationType($group, $validated['evaluation_type']);
                }

                // The pool belongs to the group, so it is stored once per group
                // rather than copied onto every column. Only Distribute Marks
                // submits it; Rate Peers leaves whatever the group already had.
                if ($validated['evaluation_type'] === PeerColumn::TYPE_DISTRIBUTE_MARKS
                    && filled($validated['buffer_marks'] ?? null)) {
                    foreach ($groups as $group) {
                        $group->buffer_marks = $validated['buffer_marks'];
                        $group->save();
                    }
                }

                $count = 0;

                foreach ($groups as $group) {
                    foreach ($validated['columns'] as $column) {
                        PeerColumn::create([
                            'column_name' => $column['column_name'],
                            'max_marks' => $column['max_marks'],
                            'has_remarks' => (bool) $validated['has_remarks'],
                            'evaluation_type' => $validated['evaluation_type'],
                            'group_id' => $group->id,
                            // Denormalised from the group so the grid and the
                            // Rating Type filter don't have to join back through
                            // peer_groups.
                            'course_id' => $group->course_id,
                            'event_id' => $group->event_id,
                            'is_visible' => true,
                        ]);
                        $count++;
                    }
                }

                return ['columns' => $count, 'groups' => count($groups)];
            });
        } catch (ValidationException $e) {
            // The transaction has already rolled back. Let the 422 through rather
            // than reporting it as a server error below.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Peer evaluation column create failed', ['error' => $e->getMessage()]);

            return $this->fail($request, 'Could not add the columns. Please try again.');
        }

        return $this->ok($request, $this->addedMessage($created['columns'], $created['groups']));
    }

    /** "2 columns added across 3 groups.", and the singular variants of it. */
    private function addedMessage(int $columns, int $groups): string
    {
        if ($groups === 1) {
            return $columns === 1
                ? 'Column added successfully.'
                : "{$columns} columns added successfully.";
        }

        $noun = $columns === 1 ? 'column' : 'columns';

        return "{$columns} {$noun} added across {$groups} groups.";
    }

    public function update(Request $request, $id)
    {
        $column = PeerColumn::findOrFail($id);

        $validated = $request->validate([
            'column_name' => ['required', 'string', 'max:255'],
            'max_marks' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'has_remarks' => ['required', 'boolean'],
            'evaluation_type' => ['required', Rule::in(array_keys(PeerColumn::TYPES))],
        ], [], [
            'column_name' => 'Column Name',
            'max_marks' => 'Max Marks',
            'has_remarks' => 'Remarks',
            'evaluation_type' => 'Evaluation Type',
        ]);

        if ($column->group_id) {
            $group = PeerGroup::find($column->group_id);

            $this->assertNamesAreUnique(
                $group,
                [['column_name' => $validated['column_name']]],
                $column->id
            );

            // Switching one column's type is the other way a group ends up with
            // both, so the same rule applies here - ignoring this column, which is
            // the one being moved.
            if ($group && $validated['evaluation_type'] !== $column->evaluation_type) {
                $clash = PeerColumn::where('group_id', $group->id)
                    ->whereKeyNot($column->id)
                    ->where('evaluation_type', '<>', $validated['evaluation_type'])
                    ->value('evaluation_type');

                if ($clash) {
                    throw ValidationException::withMessages([
                        'evaluation_type' => sprintf(
                            '%s already uses %s. A group can only use one evaluation type.',
                            $group->group_name,
                            PeerColumn::TYPES[$clash] ?? $clash
                        ),
                    ]);
                }
            }
        }

        try {
            $column->update($validated);
        } catch (\Throwable $e) {
            Log::error('Peer evaluation column update failed', ['id' => $column->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not update the column. Please try again.');
        }

        return $this->ok($request, 'Column updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $column = PeerColumn::findOrFail($id);

        // A column that has been scored carries evaluation data; deleting it would
        // orphan those peer_scores rows and silently change everyone's averages.
        $scored = \DB::table('peer_scores')->where('column_id', $column->id)->count();

        if ($scored > 0) {
            return $this->fail(
                $request,
                "This column already has {$scored} score(s) recorded. Deactivate it instead of deleting it.",
                409
            );
        }

        try {
            $column->delete();
        } catch (\Throwable $e) {
            Log::error('Peer evaluation column delete failed', ['id' => $column->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not delete the column. Please try again.');
        }

        return $this->ok($request, 'Column deleted successfully.');
    }

    /**
     * A group runs ONE kind of evaluation, never both.
     *
     * The two are scored under incompatible rules: Rate Peers caps each criterion
     * separately, while Distribute Marks shares one group-level pool
     * (peer_groups.buffer_marks) across its criteria. A group holding both gives
     * the OT one form governed by two contradictory rules, and the pool total can
     * only be checked against the columns that actually belong to it.
     *
     * Existing mixed groups are left alone - this only stops NEW columns from
     * creating or extending a mix.
     */
    private function assertSingleEvaluationType(PeerGroup $group, string $type): void
    {
        $existing = PeerColumn::where('group_id', $group->id)
            ->where('evaluation_type', '<>', $type)
            ->value('evaluation_type');

        if (! $existing) {
            return;
        }

        throw ValidationException::withMessages([
            'evaluation_type' => sprintf(
                '%s already uses %s. A group can only use one evaluation type.',
                $group->group_name,
                PeerColumn::TYPES[$existing] ?? $existing
            ),
        ]);
    }

    /**
     * Column names must be unique within a group, so two identically named columns
     * can't appear side by side on the evaluation form. Checked across the whole
     * batch as well as against what is already stored.
     *
     * @param  array<int, array<string, mixed>>  $columns
     */
    private function assertNamesAreUnique(?PeerGroup $group, array $columns, ?int $ignoreId = null): void
    {
        if (! $group) {
            return;
        }

        $names = array_map(fn ($c) => mb_strtolower(trim((string) $c['column_name'])), $columns);

        $duplicatesInBatch = array_diff_assoc($names, array_unique($names));
        if ($duplicatesInBatch !== []) {
            throw ValidationException::withMessages([
                'columns' => 'The same column name appears twice in this form.',
            ]);
        }

        $existing = PeerColumn::where('group_id', $group->id)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('column_name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->all();

        $clash = array_intersect($names, $existing);
        if ($clash !== []) {
            // array_intersect keeps the keys of its first argument, and $names was
            // built with array_map over $columns - so the key indexes straight back
            // into the submitted row and the message can quote what the user
            // actually typed rather than the lower-cased comparison key.
            $index = array_key_first($clash);
            $typed = trim((string) $columns[$index]['column_name']);

            // Named, not "this group": one submit can now target several groups,
            // so "this group" would leave the user guessing which one clashed.
            throw ValidationException::withMessages([
                'columns' => 'The group "' . $group->group_name . '" already has a column called "'
                    . $typed . '".',
            ]);
        }
    }

    /**
     * The group-level pool an OT distributes under "Distribute Marks".
     */
    public function updateBufferMarks(Request $request, $group)
    {
        $peerGroup = PeerGroup::findOrFail($group);

        $validated = $request->validate([
            'buffer_marks' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ], [], ['buffer_marks' => 'Buffer Marks']);

        $peerGroup->update($validated);

        return $this->ok($request, 'Buffer marks updated successfully.');
    }

    private function ok(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.peer.columns.index')->with('success', $message);
    }

    private function fail(Request $request, string $message, int $status = 500)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->route('admin.peer.columns.index')->with('error', $message);
    }

    // ==================== EXPORTS ====================

    /**
     * The export flattens all three levels: one row per COLUMN, carrying its
     * course, event and group. A three-level nesting has no sensible flat form
     * otherwise, and a spreadsheet of just events would say nothing useful.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => ['heading' => 'S. No.', 'class' => 'col-sno', 'value' => fn ($r, int $i) => $i + 1],
            'course_name' => ['heading' => 'Course Name', 'class' => 'col-course', 'value' => fn ($r) => $r->course_name ?: '-'],
            'event_name' => ['heading' => 'Event', 'class' => 'col-event', 'value' => fn ($r) => $r->event_name ?: '-'],
            'group_name' => ['heading' => 'Group Name', 'class' => 'col-group', 'value' => fn ($r) => $r->group_name ?: '-'],
            'column_name' => ['heading' => 'Column Name', 'class' => 'col-name', 'value' => fn ($r) => $r->column_name ?: '-'],
            'evaluation_type' => [
                'heading' => 'Rating Type',
                'class' => 'col-type',
                'value' => fn ($r) => PeerColumn::TYPES[$r->evaluation_type] ?? PeerColumn::TYPES[PeerColumn::TYPE_RATE_PEERS],
            ],
            'max_marks' => ['heading' => 'Max Marks', 'class' => 'col-num', 'value' => fn ($r) => rtrim(rtrim(number_format((float) $r->max_marks, 2), '0'), '.')],
            'buffer_marks' => [
                'heading' => 'Buffer Marks for OTs',
                'class' => 'col-num',
                'value' => fn ($r) => $r->evaluation_type === PeerColumn::TYPE_DISTRIBUTE_MARKS
                    ? rtrim(rtrim(number_format((float) ($r->buffer_marks ?? 0), 2), '0'), '.')
                    : '-',
            ],
            'has_remarks' => ['heading' => 'Remarks', 'class' => 'col-status', 'value' => fn ($r) => $r->has_remarks ? 'Yes' : 'No'],
            'created_date' => ['heading' => 'Column Created Date', 'class' => 'col-date', 'value' => fn ($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format(self::DISPLAY_DATE) : '-'],
            'status' => ['heading' => 'Status', 'class' => 'col-status', 'value' => fn ($r) => $r->is_visible ? 'Active' : 'Inactive'],
        ];
    }

    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $status = PeerCourseStatusScope::normalise($request->query('status_filter', 'active'));
        $search = trim((string) $request->query('q', ''));
        $courseFilter = $request->query('course_filter');
        $typeFilter = $request->query('type_filter');

        // Flattened to one row per column - see exportColumnDefs().
        $query = PeerColumn::query()
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_columns.course_id')
            ->leftJoin('peer_events', 'peer_events.id', '=', 'peer_columns.event_id')
            ->leftJoin('peer_groups', 'peer_groups.id', '=', 'peer_columns.group_id')
            ->select([
                'peer_columns.*',
                'course_master.course_name as course_name',
                'peer_events.event_name as event_name',
                'peer_groups.group_name as group_name',
                'peer_groups.buffer_marks as buffer_marks',
            ]);

        PeerCourseStatusScope::forRelated($query, $status, 'peer_columns.course_id', true);

        if (filled($courseFilter)) {
            $query->where('peer_columns.course_id', $courseFilter);
        }
        if (in_array($typeFilter, array_keys(PeerColumn::TYPES), true)) {
            $query->where('peer_columns.evaluation_type', $typeFilter);
        }
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('peer_columns.column_name', 'like', "%{$search}%")
                    ->orWhere('course_master.course_name', 'like', "%{$search}%")
                    ->orWhere('peer_events.event_name', 'like', "%{$search}%")
                    ->orWhere('peer_groups.group_name', 'like', "%{$search}%");
            });
        }

        $allowed = get_Role_by_course();
        if (! empty($allowed)) {
            $query->whereIn('peer_columns.course_id', $allowed);
        }

        $rows = $query->orderBy('course_master.course_name')
            ->orderBy('peer_events.event_name')
            ->orderBy('peer_groups.group_name')
            ->orderBy('peer_columns.id')
            ->get();

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        $filterBits = ['Status: ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($courseFilter)) {
            $name = CourseMaster::whereKey($courseFilter)->value('course_name');
            if ($name) { $filterBits[] = 'Course: ' . $name; }
        }
        if (in_array($typeFilter, array_keys(PeerColumn::TYPES), true)) {
            $filterBits[] = 'Rating Type: ' . PeerColumn::TYPES[$typeFilter];
        }
        if ($search !== '') {
            $filterBits[] = 'Search: ' . $search;
        }
        $filterText = implode('  |  ', $filterBits);

        if ($format === 'print') {
            return view('admin.forms.peer_evaluation.columns.export_print', compact('columns', 'rows', 'filterText', 'exportDate'));
        }

        if ($format === 'excel') {
            return Excel::download(
                new PeerEvaluationColumnExport($rows, $columns, $exportDate, $filterText),
                'EvaluationColumns_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.forms.peer_evaluation.columns.export_pdf', compact('columns', 'rows', 'filterText', 'exportDate'))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download('EvaluationColumns_' . $stamp . '.pdf');
        }

        $csvBand = \App\Support\ExportCsvHeader::rows(
            'Manage Evaluation Columns',
            $filterText !== '' ? $filterText : null,
            $exportDate,
            $rows->count()
        );

        return response()->streamDownload(function () use ($columns, $header, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(fn ($col) => $col['value']($row, $index), $columns)));
            }

            fclose($handle);
        }, 'EvaluationColumns_' . $stamp . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
