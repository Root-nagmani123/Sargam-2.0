<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PeerReflectionFieldDataTable;
use App\Exports\PeerReflectionFieldExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\PeerColumn;
use App\Models\PeerEvent;
use App\Models\PeerGroup;
use App\Models\PeerGroupMember;
use App\Models\PeerReflectionField;
use App\Support\PeerCourseStatusScope;
use App\Support\PeerEvaluationForm;
use App\Support\PeerGroupSource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Peer Evaluation -> Manage Reflection Fields.
 *
 * Reflection fields are the free-text questions ("Learning from the Paramilitary
 * Forces", "Overall Comradery and Team Building") that sit under the scored
 * evaluation grid on a peer evaluation form.
 *
 * Scope is a three-level funnel, every level optional:
 *   course -> event -> group
 * A field with none of them set is GLOBAL and appears on every form. That is why
 * the joins are LEFT and every scope column is `nullable` in validation.
 *
 * Courses come from course_master (see
 * 2026_08_24_000002_point_peer_evaluation_at_course_master).
 */
class PeerReflectionFieldController extends Controller
{
    private const DISPLAY_DATE = 'd/m/Y';

    public function index(PeerReflectionFieldDataTable $dataTable)
    {
        $status = PeerCourseStatusScope::normalise(request('status_filter', 'active'));

        return $dataTable->render('admin.forms.peer_evaluation.reflection_fields.index', [
            // Two course lists on one page, on purpose:
            //   $courses      - scoped to the pill, for the grid's Filter
            //   $modalCourses - every course, for Add/Edit
            // A field can legitimately be attached to a finished course, so
            // restricting the modal to the pill on screen would make those
            // uneditable.
            'courses' => $this->courseOptionsForStatus($status),
            'modalCourses' => $this->allCourseOptions(),
            'events' => $this->eventOptions(request('course_filter'), $status),
            'courseFilter' => (string) request('course_filter', ''),
            'eventFilter' => (string) request('event_filter', ''),
            'statusFilter' => $status,
        ]);
    }

    /**
     * Courses in the given pill's scope, for the grid's Filter dropdown.
     *
     * The dropdown must follow the pill, or it offers courses the grid is not
     * showing fields for - a filter that can only ever return an empty grid.
     */
    private function courseOptionsForStatus(string $status)
    {
        $query = CourseMaster::query();

        PeerCourseStatusScope::forCourses($query, $status);
        $this->applyRoleScope($query);

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    /**
     * Every course the user may see, for the Add / Edit modals, each tagged
     * active or archive.
     *
     * The modals are deliberately NOT narrowed by the Active / Archived pill on
     * the grid - a reflection field can be added to a course that has finished -
     * so the tag travels alongside the name and decides which heading the course
     * lands under in the picker.
     *
     * The CASE comes from PeerCourseStatusScope so this cannot disagree with the
     * pill the grid files that same course under.
     */
    private function allCourseOptions()
    {
        $query = CourseMaster::query();
        $this->applyRoleScope($query);

        [$statusCase, $bindings] = PeerCourseStatusScope::statusCase();

        return $query
            ->selectRaw("course_master.pk, course_master.course_name, {$statusCase}", $bindings)
            ->orderBy('course_name')
            ->get()
            ->map(fn ($course) => [
                'id' => (string) $course->pk,
                'name' => $course->course_name,
                'status' => $course->peer_status,
            ]);
    }

    /**
     * Events for one course, or every event when no course is given.
     *
     * $status narrows to events whose COURSE is in that pill, so the Event filter
     * can't offer an event from a course the grid is currently hiding. Passing
     * null (the modals) leaves it unscoped.
     *
     * Ordered by name so the dropdown reads alphabetically both on first render
     * and after an AJAX rebuild.
     */
    private function eventOptions($courseId = null, ?string $status = null)
    {
        $query = PeerEvent::query();

        if (filled($courseId)) {
            $query->where('course_id', $courseId);
        }

        if ($status !== null) {
            PeerCourseStatusScope::forRelated($query, $status, 'peer_events.course_id');
        }

        return $query->orderBy('event_name')->pluck('event_name', 'id');
    }

    /**
     * Groups come from Course Group Mapping, keyed by that table's pk. The stored
     * `group_id` is still a peer_groups.id - validated() turns the picked mapping
     * row into one via PeerGroupSource::link().
     */
    private function groupOptions($courseId = null, $eventId = null)
    {
        if (filled($eventId)) {
            $courseId = PeerEvent::whereKey($eventId)->value('course_id') ?: $courseId;
        }

        return PeerGroupSource::options($courseId)->pluck('label', 'pk');
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
     * Dependent-dropdown feed for the filter row and both modals.
     *
     * Returns ordered LISTS of {id, name}, not id => name maps: JavaScript
     * reorders numeric-looking object keys, so a map would come back sorted by id
     * and the dropdown would be alphabetical on first load but id-ordered after
     * every rebuild.
     */
    public function options(Request $request)
    {
        $courseId = $request->query('course_id');
        $eventId = $request->query('event_id');

        // `status` is sent by the FILTER row (which follows the pill) and omitted
        // by the modals (which must reach every course/event).
        $status = $request->query('status');
        $status = $status === null ? null : PeerCourseStatusScope::normalise($status);

        $shape = fn ($collection) => $collection
            ->map(fn ($name, $id) => ['id' => (string) $id, 'name' => $name])
            ->values();

        $payload = [
            'success' => true,
            'events' => $shape($this->eventOptions($courseId, $status)),
            'groups' => $shape($this->groupOptions($courseId, $eventId)),
        ];

        // The filter row also rebuilds its Course list when the pill changes.
        if ($status !== null) {
            $payload['courses'] = $shape($this->courseOptionsForStatus($status));
        }

        return response()->json($payload);
    }

    // ==================== CRUD ====================

    /**
     * Add takes a LIST of labels, Edit takes one.
     *
     * A form normally needs several reflection questions and they all share one
     * Course / Event / Group, so the scope is validated once here and every label
     * is checked against it. Scope handling is the same pipeline validated() uses:
     * resolve the group first, blank selects become NULL, then assert the three
     * levels agree.
     *
     * @return array{scope: array<string, int|null>, labels: array<int, string>}
     */
    private function validatedMany(Request $request): array
    {
        // Before the uniqueness rule, which scopes on group_id and so has to
        // compare against the id that gets stored - not the mapping pk posted.
        $this->resolveGroupSelection($request);

        $unique = Rule::unique('peer_reflection_fields', 'field_label')
            ->where(function ($query) use ($request) {
                foreach (['course_id', 'event_id', 'group_id'] as $column) {
                    $value = $request->input($column);
                    filled($value) ? $query->where($column, $value) : $query->whereNull($column);
                }

                return $query;
            });

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            // distinct catches the same label typed into two rows of ONE submit.
            // The unique rule cannot: neither row is in the table yet, so both
            // would pass and the second insert would be the duplicate.
            'fields.*.field_label' => ['required', 'string', 'max:255', 'distinct:ignore_case', $unique],
            'course_id' => ['nullable', 'integer', Rule::exists('course_master', 'pk')],
            'event_id' => ['nullable', 'integer', Rule::exists('peer_events', 'id')],
            // Already a peer_groups id by this point - resolveGroupSelection()
            // swapped the posted mapping pk for it.
            'group_id' => ['nullable', 'integer', Rule::exists('peer_groups', 'id')],
        ], [
            'fields.required' => 'Please add at least one reflection field.',
            'fields.*.field_label.unique' => 'A reflection field with that label already exists for this course / event / group.',
            'fields.*.field_label.distinct' => 'This field name is listed more than once.',
        ], [
            'fields.*.field_label' => 'Field Name',
            'course_id' => 'Course Name',
            'event_id' => 'Event Name',
            'group_id' => 'Group Name',
        ]);

        // Blank selects arrive as '' - store NULL so "global" is a real NULL and
        // the unique rule above lines up with what is in the table.
        $scope = [];

        foreach (['course_id', 'event_id', 'group_id'] as $column) {
            $scope[$column] = filled($validated[$column] ?? null) ? (int) $validated[$column] : null;
        }

        $this->assertScopeIsConsistent($scope);

        return [
            'scope' => $scope,
            'labels' => array_map(static fn ($row) => trim($row['field_label']), $validated['fields']),
        ];
    }

    public function store(Request $request)
    {
        ['scope' => $scope, 'labels' => $labels] = $this->validatedMany($request);

        try {
            // All or nothing. A partial insert would leave the admin working out
            // which of the labels still on screen had already been saved.
            DB::transaction(function () use ($scope, $labels) {
                foreach ($labels as $label) {
                    PeerReflectionField::create($scope + [
                        'field_label' => $label,
                        'is_active' => true,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Peer reflection field create failed', ['error' => $e->getMessage()]);

            return $this->fail($request, 'Could not add the reflection fields. Please try again.');
        }

        return $this->ok($request, count($labels) === 1
            ? 'Reflection field added successfully.'
            : count($labels).' reflection fields added successfully.');
    }

    public function update(Request $request, $id)
    {
        $field = PeerReflectionField::findOrFail($id);
        $data = $this->validated($request, (int) $field->id);

        try {
            $field->update($data);
        } catch (\Throwable $e) {
            Log::error('Peer reflection field update failed', ['id' => $field->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not update the reflection field. Please try again.');
        }

        return $this->ok($request, 'Reflection field updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $field = PeerReflectionField::findOrFail($id);

        try {
            $field->delete();
        } catch (\Throwable $e) {
            Log::error('Peer reflection field delete failed', ['id' => $field->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not delete the reflection field. Please try again.');
        }

        return $this->ok($request, 'Reflection field deleted successfully.');
    }

    /**
     * Shared rules for create and edit.
     *
     * The label is unique within its scope, so the same question can be asked on
     * two different events without the second one being rejected. Scoped on the
     * exact (course, event, group) triple, NULLs included - `whereNull` is needed
     * because SQL `= NULL` never matches.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        // Resolve the picked Course Group Mapping row into its peer_groups row
        // BEFORE anything else: the uniqueness rule below scopes on group_id, and
        // it has to compare against the id that actually gets stored - not the
        // mapping pk the form posted.
        $this->resolveGroupSelection($request);

        $unique = Rule::unique('peer_reflection_fields', 'field_label')
            ->where(function ($query) use ($request) {
                foreach (['course_id', 'event_id', 'group_id'] as $column) {
                    $value = $request->input($column);
                    filled($value) ? $query->where($column, $value) : $query->whereNull($column);
                }

                return $query;
            });

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        $validated = $request->validate([
            'field_label' => ['required', 'string', 'max:255', $unique],
            'course_id' => ['nullable', 'integer', Rule::exists('course_master', 'pk')],
            'event_id' => ['nullable', 'integer', Rule::exists('peer_events', 'id')],
            // Already a peer_groups id by this point - resolveGroupSelection()
            // swapped the posted mapping pk for it.
            'group_id' => ['nullable', 'integer', Rule::exists('peer_groups', 'id')],
        ], [
            'field_label.unique' => 'A reflection field with that label already exists for this course / event / group.',
        ], [
            'field_label' => 'Field Name',
            'course_id' => 'Course Name',
            'event_id' => 'Event Name',
            'group_id' => 'Group Name',
        ]);

        // Blank selects arrive as '' - store NULL so "global" is a real NULL and
        // the unique rule above lines up with what is in the table.
        foreach (['course_id', 'event_id', 'group_id'] as $column) {
            $validated[$column] = filled($validated[$column] ?? null) ? (int) $validated[$column] : null;
        }

        $this->assertScopeIsConsistent($validated);

        return $validated;
    }

    /**
     * Swap the posted Course Group Mapping pk for the peer_groups row it maps to,
     * creating that row (and syncing its members) on first use.
     *
     * Mutates the request so every rule downstream sees the resolved id. A group
     * knows its own event, so an otherwise-blank Event is adopted from it -
     * without that the field would be group-scoped but event-unscoped and would
     * show up against every event of the course.
     */
    private function resolveGroupSelection(Request $request): void
    {
        $mapPk = $request->input('group_id');

        if (blank($mapPk)) {
            return;
        }

        $group = PeerGroupSource::link($request->input('event_id'), $mapPk);

        if (! $group) {
            throw ValidationException::withMessages([
                'group_id' => 'That group could not be linked. Please pick another.',
            ]);
        }

        $request->merge([
            'group_id' => $group->id,
            'event_id' => filled($request->input('event_id')) ? $request->input('event_id') : $group->event_id,
        ]);
    }

    /**
     * Reject a scope whose levels contradict each other.
     *
     * The modal's dropdowns are dependent so the UI can't normally produce this,
     * but the route is reachable on its own and a stale form could post an event
     * from a different course.
     */
    private function assertScopeIsConsistent(array $data): void
    {
        // ValidationException, not abort(422): that returns an exception-shaped
        // body with no `errors` key, so the modal could only show the message in a
        // generic alert instead of under the field that is actually wrong.
        if ($data['event_id'] && $data['course_id']) {
            $event = PeerEvent::find($data['event_id']);
            if ($event && (int) $event->course_id !== (int) $data['course_id']) {
                throw ValidationException::withMessages([
                    'event_id' => 'That event does not belong to the selected course.',
                ]);
            }
        }

        // No group/event check here any more: link() creates the peer_groups row
        // FOR the submitted event, so the two can no longer disagree.
    }

    private function ok(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.peer.reflection-fields.index')->with('success', $message);
    }

    private function fail(Request $request, string $message, int $status = 500)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->route('admin.peer.reflection-fields.index')->with('error', $message);
    }

    // ==================== FORM PREVIEW ====================

    /**
     * Read-only preview of the evaluation form a given scope produces.
     *
     * Everything renders disabled: this is a preview of what an Officer Trainee
     * would see, not a form an admin can submit.
     *
     * The whole point is that it agrees with the real thing, so the criteria, the
     * reflection fields and the members all come from App\Support\PeerEvaluationForm
     * - the same class the OT-facing form renders from. Running its own queries is
     * what let this screen show columns belonging to OTHER groups of the event: the
     * column query filtered on course and event but never on group.
     *
     * The single "full form" view replaced a Reflection Only / Full Form pair. The
     * reflection-only half showed a fragment of a form nobody ever sees, and the
     * scored grid above it is the part that needed checking.
     */
    public function preview(Request $request)
    {
        $groupId = $request->query('group_id');

        // The real peer_groups row wins over the query string. A group's own
        // course_id / event_id are what the OT form scopes by, so trusting the URL
        // could preview a combination that will never be rendered for anyone.
        $group = filled($groupId) ? PeerGroup::find($groupId) : null;

        // Reached from the Reflection Fields grid, a field may have no group at
        // all. PeerEvaluationForm reads only these three, and handles a null id by
        // matching group-less rows.
        $scope = $group ?: (object) [
            'id' => null,
            'course_id' => $request->query('course_id'),
            'event_id' => $request->query('event_id'),
        ];

        $fields = PeerEvaluationForm::reflectionFieldsFor($scope);
        $columns = PeerEvaluationForm::columnsFor($scope);

        // No self-exclusion here: an admin previewing the form is not one of the
        // OTs, so every member of the group is listed.
        $members = $group ? PeerEvaluationForm::peersFor($group, null) : collect();

        // Same gate the OT form uses - the Remarks column exists only when a
        // criterion on this form actually asks for one.
        $allowsRemarks = $columns->contains(fn ($column) => (bool) $column->has_remarks);

        $courseId = $scope->course_id;
        $eventId = $scope->event_id;

        return view('admin.forms.peer_evaluation.reflection_fields.preview', [
            'fields' => $fields,
            'columns' => $columns,
            'members' => $members,
            'allowsRemarks' => $allowsRemarks,
            'courseId' => $courseId,
            'eventId' => $eventId,
            'groupId' => $group->id ?? null,
            'courseName' => filled($courseId) ? CourseMaster::whereKey($courseId)->value('course_name') : null,
            'eventName' => filled($eventId) ? PeerEvent::whereKey($eventId)->value('event_name') : null,
            'groupName' => $group->group_name ?? null,
        ]);
    }

    // ==================== EXPORTS ====================

    /**
     * Canonical column list. CSV, Excel, PDF and the print sheet all render from
     * this one array, keyed by column - so hiding a column in the grid's Columns
     * modal drops it from every format and the four can never drift apart.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'course_name' => [
                'heading' => 'Course Name',
                'class' => 'col-course',
                'value' => fn ($row) => $row->course_name ?: '-',
            ],
            'event_name' => [
                'heading' => 'Event Name',
                'class' => 'col-event',
                'value' => fn ($row) => $row->event_name ?: '-',
            ],
            'field_label' => [
                'heading' => 'Field Label',
                'class' => 'col-label',
                'value' => fn ($row) => $row->field_label ?: '-',
            ],
            'created_date' => [
                'heading' => 'Reflection Field Created Date',
                'class' => 'col-date',
                'value' => fn ($row) => optional($row->created_at)->format(self::DISPLAY_DATE) ?: '-',
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => $row->is_active ? 'Active' : 'Inactive',
            ],
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

        $courseFilter = $request->query('course_filter');
        $eventFilter = $request->query('event_filter');
        $search = trim((string) $request->query('q', ''));
        $status = PeerCourseStatusScope::normalise($request->query('status_filter', 'active'));

        // Same query the grid uses, pill included - otherwise Download on the
        // Archived tab would quietly hand back the active rows.
        $query = PeerReflectionFieldDataTable::baseQuery(
            new PeerReflectionField(),
            $courseFilter,
            $eventFilter,
            $status
        );

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('peer_reflection_fields.field_label', 'like', "%{$search}%")
                    ->orWhere('course_master.course_name', 'like', "%{$search}%")
                    ->orWhere('peer_events.event_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->get();

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        $filterBits = ['Status: ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($courseFilter)) {
            $name = CourseMaster::whereKey($courseFilter)->value('course_name');
            if ($name) { $filterBits[] = 'Course: ' . $name; }
        }
        if (filled($eventFilter)) {
            $name = PeerEvent::whereKey($eventFilter)->value('event_name');
            if ($name) { $filterBits[] = 'Event: ' . $name; }
        }
        if ($search !== '') {
            $filterBits[] = 'Search: ' . $search;
        }
        $filterText = implode('  |  ', $filterBits);

        if ($format === 'print') {
            return view('admin.forms.peer_evaluation.reflection_fields.export_print', compact('columns', 'rows', 'filterText', 'exportDate'));
        }

        if ($format === 'excel') {
            return Excel::download(
                new PeerReflectionFieldExport($rows, $columns, $exportDate, $filterText),
                'ManageReflectionFields_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.forms.peer_evaluation.reflection_fields.export_pdf', compact('columns', 'rows', 'filterText', 'exportDate'))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download('ManageReflectionFields_' . $stamp . '.pdf');
        }

        $csvBand = \App\Support\ExportCsvHeader::rows(
            'Manage Reflection Fields',
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
        }, 'ManageReflectionFields_' . $stamp . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
