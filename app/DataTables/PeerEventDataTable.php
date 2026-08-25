<?php

namespace App\DataTables;

use App\Models\PeerEvent;
use App\Support\PeerCourseStatusScope;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side feed for Peer Evaluation -> Manage Events.
 *
 * Course Name is a joined column, so the select carries the course name as a
 * real column (`course_name`) rather than an eager-loaded relation: that keeps
 * it sortable and searchable in SQL instead of paging the whole table into PHP.
 *
 * `peer_events.course_id` is a `course_master.pk` (see
 * 2026_08_24_000002_point_peer_evaluation_at_course_master) - the module used to
 * carry its own `peer_courses` list, which is gone.
 */
class PeerEventDataTable extends DataTable
{
    /** d/m/Y matches the design; DB dates are Y-m-d. */
    private const DISPLAY_DATE = 'd/m/Y';

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->addColumn('event_name', fn ($row) => e($row->event_name ?? '-'))
            ->addColumn('created_date', fn ($row) => optional($row->created_at)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('start_date_fmt', fn ($row) => optional($row->start_date)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('end_date_fmt', fn ($row) => optional($row->end_date)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('action', function ($row) {
                // Everything the Edit modal needs travels on the button, so opening
                // it costs no extra request.
                $edit = '<button type="button" class="pe-act pe-act--edit pe-edit-btn"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-course-id="' . (int) $row->course_id . '"'
                    . ' data-event-name="' . e((string) $row->event_name) . '"'
                    . ' data-start-date="' . (optional($row->start_date)->format('Y-m-d') ?: '') . '"'
                    . ' data-end-date="' . (optional($row->end_date)->format('Y-m-d') ?: '') . '"'
                    . ' data-description="' . e((string) $row->description) . '">'
                    . '<span class="pe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="pe-act__label">Edit</span>'
                    . '</button>';

                // Mirror PeerEventController::destroy()'s own refusal: an event that
                // still owns groups cannot be deleted, so render a disabled control
                // rather than a red button that always fails.
                $groupCount = (int) ($row->groups_count ?? 0);

                if ($groupCount > 0) {
                    $delete = '<span class="pe-act pe-act--del is-disabled"'
                        . ' title="This event has ' . $groupCount . ' group(s). Remove them first."'
                        . ' aria-disabled="true">'
                        . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="pe-act__label">Delete</span>'
                        . '</span>';
                } else {
                    $delete = '<button type="button" class="pe-act pe-act--del pe-delete-btn"'
                        . ' data-id="' . (int) $row->id . '"'
                        . ' data-event-name="' . e((string) $row->event_name) . '">'
                        . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="pe-act__label">Delete</span>'
                        . '</button>';
                }

                return '<div class="pe-act-group" role="group" aria-label="Row actions">' . $edit . $delete . '</div>';
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_master.course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('event_name', function ($query, $keyword) {
                $query->where('peer_events.event_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('created_date', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(peer_events.created_at, '%d/%m/%Y') like ?", ["%{$keyword}%"]);
            })
            ->filterColumn('start_date_fmt', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(peer_events.start_date, '%d/%m/%Y') like ?", ["%{$keyword}%"]);
            })
            ->filterColumn('end_date_fmt', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(peer_events.end_date, '%d/%m/%Y') like ?", ["%{$keyword}%"]);
            })
            // orderColumn is needed wherever the DataTables column name is not a
            // real SQL column, or Yajra would ORDER BY a name MySQL doesn't know.
            ->orderColumn('course_name', 'course_master.course_name $1')
            ->orderColumn('event_name', 'peer_events.event_name $1')
            ->orderColumn('created_date', 'peer_events.created_at $1')
            ->orderColumn('start_date_fmt', 'peer_events.start_date $1')
            ->orderColumn('end_date_fmt', 'peer_events.end_date $1')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (! empty($searchValue)) {
                    $query->where(function ($sub) use ($searchValue) {
                        $sub->where('peer_events.event_name', 'like', "%{$searchValue}%")
                            ->orWhere('course_master.course_name', 'like', "%{$searchValue}%")
                            ->orWhereRaw("DATE_FORMAT(peer_events.created_at, '%d/%m/%Y') like ?", ["%{$searchValue}%"])
                            ->orWhereRaw("DATE_FORMAT(peer_events.start_date, '%d/%m/%Y') like ?", ["%{$searchValue}%"])
                            ->orWhereRaw("DATE_FORMAT(peer_events.end_date, '%d/%m/%Y') like ?", ["%{$searchValue}%"]);
                    });
                }
            }, true)
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(PeerEvent $model): QueryBuilder
    {
        return self::baseQuery($model, request('course_filter'), request('status_filter'));
    }

    /**
     * The Active / Archived pills, scoped on the COURSE the event belongs to.
     *
     * Thin delegates to PeerCourseStatusScope so Manage Events and Manage
     * Reflection Fields can never disagree about what "Archived" means. See that
     * class for why the course-side and related-side forms are not interchangeable.
     */
    public static function normaliseStatus($status): string
    {
        return PeerCourseStatusScope::normalise($status);
    }

    public static function applyCourseStatusScope($query, $status)
    {
        return PeerCourseStatusScope::forCourses($query, $status);
    }

    public static function applyStatusScope($query, $status)
    {
        // No includeUnscoped: an event without a course is not meaningful here -
        // course_id is required when adding one.
        return PeerCourseStatusScope::forRelated($query, $status, 'peer_events.course_id');
    }

    /**
     * The one query the grid and every export share, so a download can never
     * show a different set of rows than the screen.
     */
    public static function baseQuery(PeerEvent $model, $courseFilter = null, $status = null): QueryBuilder
    {
        // select() BEFORE withCount(): withCount appends its sub-select to the
        // column list, so an explicit select() afterwards silently throws
        // groups_count away and the delete guard would never fire.
        $query = $model->newQuery()
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_events.course_id')
            ->select([
                'peer_events.id',
                'peer_events.event_name',
                'peer_events.course_id',
                'peer_events.start_date',
                'peer_events.end_date',
                'peer_events.description',
                'peer_events.created_at',
                'course_master.course_name as course_name',
            ])
            ->withCount('groups');

        self::applyStatusScope($query, $status);

        if (filled($courseFilter)) {
            $query->where('peer_events.course_id', $courseFilter);
        }

        // Newest first by default, but only while the user hasn't clicked a
        // header - otherwise this base order would dominate the requested one.
        if (empty(request('order'))) {
            $query->orderBy('peer_events.id', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        // No ->buttons(): the page's own Download/Print pair is server-rendered
        // (see PeerEventController::export). Button::make('reset'|'reload') in
        // particular throws "unknown button type", after which jQuery skips every
        // later init.dt handler - which is what strips the search box and pager
        // off a grid that otherwise looks fine.
        return $this->builder()
            ->setTableId('peerEventsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native server-side ordering: a header click
                // re-queries and sorts the whole table, not just this page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order' => [],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search',
                    'paginate' => ['previous' => "\u{2039}", 'next' => "\u{203A}"],
                    'lengthMenu' => 'Showing _MENU_',
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
                    'infoFiltered' => 'of _MAX_ items',
                    'emptyTable' => 'No events found',
                    'zeroRecords' => 'No events match your search',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('course_name')->title('Course Name')->orderable(true)->searchable(true),
            Column::make('event_name')->title('Event Name')->orderable(true)->searchable(true),
            Column::make('created_date')->title('Event Created Date')->orderable(true)->searchable(true)->addClass('text-center'),
            Column::make('start_date_fmt')->title('Start Date')->orderable(true)->searchable(true)->addClass('text-center'),
            Column::make('end_date_fmt')->title('End Date')->orderable(true)->searchable(true)->addClass('text-center'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PeerEvents_' . date('YmdHis');
    }
}
