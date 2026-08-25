<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PeerEventDataTable;
use App\Exports\PeerEventExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\PeerEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Peer Evaluation -> Manage Events.
 *
 * The dedicated CRUD screen for peer_events. The legacy combined admin panel
 * (PeerEvaluationController@index) still owns groups / columns / reflection
 * fields; this controller owns the standalone grid, its modals and its exports.
 *
 * Courses come from course_master - the real institutional course list. The
 * module used to keep its own `peer_courses` table, retired by
 * 2026_08_24_000002_point_peer_evaluation_at_course_master.
 */
class PeerEventController extends Controller
{
    /** d/m/Y everywhere the user sees a date; the DB stores Y-m-d. */
    private const DISPLAY_DATE = 'd/m/Y';

    public function index(PeerEventDataTable $dataTable)
    {
        $status = PeerEventDataTable::normaliseStatus(request('status_filter', 'active'));

        return $dataTable->render('admin.forms.peer_evaluation.events.index', [
            // Two different course lists on one page, on purpose:
            //   $courses      - scoped to the status pill, for the grid's Filter
            //   $modalCourses - every active course, for Add/Edit
            // A brand-new event has no dates yet, so restricting the modal to
            // courses that already own an event in this scope would make it
            // impossible to add the first event to a course.
            'courses' => $this->courseOptionsForStatus($status),
            'modalCourses' => $this->allCourseOptions(),
            'courseFilter' => (string) request('course_filter', ''),
            'statusFilter' => $status,
        ]);
    }

    /**
     * Courses in the given status scope, for the grid's Filter dropdown.
     *
     * The dropdown must follow the Active / Archived pill, so it lists the same
     * courses the grid is showing events for. Scope comes from CourseMaster's own
     * scopeActiveRunning() / scopeArchived() via PeerEventDataTable, so the pill,
     * the grid and this list can never disagree.
     */
    private function courseOptionsForStatus(string $status)
    {
        $query = CourseMaster::query();

        PeerEventDataTable::applyCourseStatusScope($query, $status);
        $this->applyRoleScope($query);

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    /**
     * Every course the user may see, for the Add / Edit modals' Course Name select.
     *
     * Deliberately NOT status-scoped. A brand-new event has no course yet, and an
     * existing one may sit on an archived course - restricting the modal to the
     * pill currently on screen would make those uneditable. Select2 handles the
     * length (145 courses today).
     */
    private function allCourseOptions()
    {
        $query = CourseMaster::query();
        $this->applyRoleScope($query);

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    /**
     * Restrict to the courses this user's roles cover.
     *
     * get_Role_by_course() returns [] for Admin / Super Admin / PA (meaning "no
     * restriction") and [-1] for a non-admin with no roles (meaning "nothing"), so
     * an empty array must NOT be fed to whereIn.
     */
    private function applyRoleScope($query): void
    {
        $allowed = get_Role_by_course();

        if (! empty($allowed)) {
            $query->whereIn('course_master.pk', $allowed);
        }
    }

    /**
     * Course options for one status pill (AJAX).
     *
     * Called when the user switches between Active and Archived so the Filter
     * dropdown is rebuilt for the scope now on screen.
     */
    public function coursesByStatus(Request $request)
    {
        $status = PeerEventDataTable::normaliseStatus($request->input('status', 'active'));

        // A LIST of {id, name}, not an id => name map. JavaScript reorders
        // numeric-looking object keys into ascending order, so a map would come
        // back sorted by course id and the dropdown would be alphabetical on
        // first load but id-ordered after every tab switch.
        $courses = $this->courseOptionsForStatus($status)
            ->map(fn ($name, $pk) => ['id' => (string) $pk, 'name' => $name])
            ->values();

        return response()->json([
            'success' => true,
            'status' => $status,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            PeerEvent::create($data + ['is_active' => true]);
        } catch (\Throwable $e) {
            Log::error('Peer event create failed', ['error' => $e->getMessage()]);

            return $this->fail($request, 'Could not add the event. Please try again.');
        }

        return $this->ok($request, 'Event added successfully.');
    }

    public function update(Request $request, $id)
    {
        $event = PeerEvent::findOrFail($id);
        $data = $this->validated($request, (int) $event->id);

        try {
            $event->update($data);
        } catch (\Throwable $e) {
            Log::error('Peer event update failed', ['id' => $event->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not update the event. Please try again.');
        }

        return $this->ok($request, 'Event updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $event = PeerEvent::withCount('groups')->findOrFail($id);

        // The grid renders Delete disabled in this state; re-check here because
        // the grid's copy can be stale and the route is reachable on its own.
        if ($event->groups_count > 0) {
            return $this->fail(
                $request,
                'This event has ' . $event->groups_count . ' group(s) attached. Remove them before deleting the event.',
                409
            );
        }

        try {
            $event->delete();
        } catch (\Throwable $e) {
            Log::error('Peer event delete failed', ['id' => $event->id, 'error' => $e->getMessage()]);

            return $this->fail($request, 'Could not delete the event. Please try again.');
        }

        return $this->ok($request, 'Event deleted successfully.');
    }

    /**
     * Shared rules for create and edit.
     *
     * Event names are unique WITHIN a course (matching the composite index added
     * by 2026_08_24_000000_add_schedule_fields_to_peer_events_table), so the
     * uniqueness rule has to be scoped to the submitted course, not global.
     *
     * course_id is validated against course_master.pk - peer_courses is gone.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $unique = Rule::unique('peer_events', 'event_name')
            ->where(fn ($query) => $query->where('course_id', $request->input('course_id')));

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('course_master', 'pk')],
            'event_name' => ['required', 'string', 'max:255', $unique],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ], [
            'event_name.unique' => 'This course already has an event with that name.',
            'end_date.after_or_equal' => 'End Date cannot be before Start Date.',
        ], [
            // Without these the messages read "The course id field is required."
            // instead of naming the labels the modal actually shows.
            'course_id' => 'Course Name',
            'event_name' => 'Event Name',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'description' => 'Description',
        ]);

        $validated['description'] = filled($validated['description'] ?? null)
            ? $validated['description']
            : null;

        return $validated;
    }

    private function ok(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.peer.events.index')->with('success', $message);
    }

    private function fail(Request $request, string $message, int $status = 500)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->route('admin.peer.events.index')->with('error', $message);
    }

    // ==================== EXPORTS ====================

    /**
     * Canonical column list. CSV, Excel, PDF and the print sheet all render from
     * this one array, keyed by column - so hiding a column in the grid's Columns
     * modal drops it from every format and the four can never drift apart.
     *
     * Action is absent by design: it is chrome, not data.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        $date = fn ($value) => $value ? $value->format(self::DISPLAY_DATE) : '-';

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
            'created_date' => [
                'heading' => 'Event Created Date',
                'class' => 'col-date',
                'value' => fn ($row) => $date($row->created_at),
            ],
            'start_date' => [
                'heading' => 'Start Date',
                'class' => 'col-date',
                'value' => fn ($row) => $date($row->start_date),
            ],
            'end_date' => [
                'heading' => 'End Date',
                'class' => 'col-date',
                'value' => fn ($row) => $date($row->end_date),
            ],
        ];
    }

    /**
     * Intersect ?cols= against the canonical list rather than trusting it, so a
     * hand-edited query string can't reorder the report or inject a column.
     */
    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        // Every column hidden would produce an empty file - fall back to all.
        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $courseFilter = $request->query('course_filter');
        $search = trim((string) $request->query('q', ''));
        $status = PeerEventDataTable::normaliseStatus($request->query('status_filter', 'active'));

        // Same query the grid uses, so the download is what is on screen -
        // including the Active / Archived pill, or Download on the Archived tab
        // would quietly hand back the active rows.
        $query = PeerEventDataTable::baseQuery(new PeerEvent(), $courseFilter, $status);

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('peer_events.event_name', 'like', "%{$search}%")
                    ->orWhere('course_master.course_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->get();

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        $filterBits = ['Status: ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($courseFilter)) {
            $courseName = CourseMaster::whereKey($courseFilter)->value('course_name');
            if ($courseName) {
                $filterBits[] = 'Course: ' . $courseName;
            }
        }
        if ($search !== '') {
            $filterBits[] = 'Search: ' . $search;
        }
        $filterText = implode('  |  ', $filterBits);

        if ($format === 'print') {
            return view('admin.forms.peer_evaluation.events.export_print', [
                'columns' => $columns,
                'rows' => $rows,
                'filterText' => $filterText,
                'exportDate' => $exportDate,
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new PeerEventExport($rows, $columns, $exportDate, $filterText),
                'ManageEvents_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.forms.peer_evaluation.events.export_pdf', [
                'columns' => $columns,
                'rows' => $rows,
                'filterText' => $filterText,
                'exportDate' => $exportDate,
            ])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download('ManageEvents_' . $stamp . '.pdf');
        }

        $csvBand = \App\Support\ExportCsvHeader::rows(
            'Manage Events',
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
                fputcsv($handle, array_values(array_map(
                    fn ($col) => $col['value']($row, $index),
                    $columns
                )));
            }

            fclose($handle);
        }, 'ManageEvents_' . $stamp . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
