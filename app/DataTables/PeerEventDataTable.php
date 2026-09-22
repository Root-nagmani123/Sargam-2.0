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

    /**
     * Per-row counts the Delete control reads.
     *
     * `submissions_count` is the guard: scores, remarks and reflection answers live
     * in three separate tables and ANY of them means an OT has already worked under
     * this event, so they are summed rather than checked one at a time. The other
     * three are what the confirm dialog itemises before the cascade runs.
     *
     * One definition, used by the grid and re-derived by
     * PeerEventController::destroy() - the button and the route must agree on what
     * "has submissions" means, or the grid would offer a delete the route refuses.
     */
    public static function countSubqueries(): string
    {
        return '(SELECT COUNT(*) FROM peer_scores ps'
            . ' JOIN peer_groups g1 ON g1.id = ps.group_id WHERE g1.event_id = peer_events.id)'
            . ' + (SELECT COUNT(*) FROM peer_evaluation_remarks pr'
            . ' JOIN peer_groups g2 ON g2.id = pr.group_id WHERE g2.event_id = peer_events.id)'
            . ' + (SELECT COUNT(*) FROM reflection_responses rr'
            . ' JOIN peer_groups g3 ON g3.id = rr.group_id WHERE g3.event_id = peer_events.id)'
            . ' AS submissions_count,'
            . ' (SELECT COUNT(*) FROM peer_group_members pm'
            . ' JOIN peer_groups g4 ON g4.id = pm.group_id WHERE g4.event_id = peer_events.id)'
            . ' AS members_count,'
            . ' (SELECT COUNT(*) FROM peer_columns pc WHERE pc.event_id = peer_events.id)'
            . ' AS columns_count,'
            . ' (SELECT COUNT(*) FROM peer_reflection_fields pf WHERE pf.event_id = peer_events.id)'
            . ' AS fields_count';
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->addColumn('event_name', fn ($row) => e($row->event_name ?? '-'))
            ->addColumn('created_date', fn ($row) => optional($row->created_at)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('start_date_fmt', fn ($row) => optional($row->start_date)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('end_date_fmt', fn ($row) => optional($row->end_date)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('status', function ($row) {
                // Display only; the switch that changes it lives in the Action
                // column, same as Manage Reflection Fields. This is the EVENT's own
                // on/off flag - distinct from the Active / Archived pills above the
                // grid, which describe the COURSE the event belongs to.
                $active = (bool) $row->is_active;

                return '<span class="status-pill badge rounded-1 ' . ($active ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($active ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                // Read once: both the Delete guard below and the switch depend on it.
                $active = (bool) $row->is_active;

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

                // Mirror PeerEventController::destroy()'s own refusals rather than
                // rendering a red button that always fails. Two of them, in the same
                // order the controller applies:
                //   1. a LIVE event is not deletable - deactivate it first, which is
                //      what the switch beside this button is for;
                //   2. an event an OT has already evaluated under is not deletable
                //      either, because the delete takes its groups and their members
                //      with it and those submissions would go too.
                // Its groups do NOT block it: nothing outside the event owns them, so
                // they are removed along with it. The counts ride on the button so the
                // confirm dialog can say exactly what goes.
                $groupCount = (int) ($row->groups_count ?? 0);
                $submissions = (int) ($row->submissions_count ?? 0);

                if ($active) {
                    $delete = '<span class="pe-act pe-act--del is-disabled"'
                        . ' title="Deactivate this event first, then it can be deleted."'
                        . ' aria-disabled="true">'
                        . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="pe-act__label">Delete</span>'
                        . '</span>';
                } elseif ($submissions > 0) {
                    $delete = '<span class="pe-act pe-act--del is-disabled"'
                        . ' title="' . $submissions . ' evaluation entr' . ($submissions === 1 ? 'y has' : 'ies have')
                        . ' already been submitted under this event, so it can no longer be deleted."'
                        . ' aria-disabled="true">'
                        . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="pe-act__label">Delete</span>'
                        . '</span>';
                } else {
                    $delete = '<button type="button" class="pe-act pe-act--del pe-delete-btn"'
                        . ' data-id="' . (int) $row->id . '"'
                        . ' data-event-name="' . e((string) $row->event_name) . '"'
                        . ' data-groups="' . $groupCount . '"'
                        . ' data-members="' . (int) ($row->members_count ?? 0) . '"'
                        . ' data-columns="' . (int) ($row->columns_count ?? 0) . '"'
                        . ' data-fields="' . (int) ($row->fields_count ?? 0) . '">'
                        . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="pe-act__label">Delete</span>'
                        . '</button>';
                }

                // Driven by the global .status-toggle handler (admin_assets/js/custom.js
                // via routes.toggleStatus, loaded in admin/layouts/footer.blade.php):
                // SweetAlert confirm -> POST admin/toggle-status, no page JS beyond the
                // redraw hook in the blade. peer_events keys on `id`, not `pk`, hence
                // data-id_column. No .form-check/.form-switch wrapper - that pulls the
                // input -2.375rem left (custom.css:107-112) and knocks it off centre
                // above its caption. The caption names the ACTION, not the state; the
                // state is already shown one column over.
                $toggle = '<label class="pe-act pe-act--toggle">'
                    . '<span class="pe-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="peer_events" data-column="is_active"'
                    . ' data-id_column="id" data-id="' . (int) $row->id . '"'
                    . ($active ? ' checked' : '') . '>'
                    . '</span>'
                    . '<span class="pe-act__label">' . ($active ? 'Deactivate' : 'Activate') . '</span>'
                    . '</label>';

                return '<div class="pe-act-group pe-act-group--wide" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete . '</div>';
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
            ->orderColumn('status', 'peer_events.is_active $1')
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
            ->rawColumns(['status', 'action'])
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
                'peer_events.is_active',
                'peer_events.created_at',
                'course_master.course_name as course_name',
            ])
            ->withCount('groups');

        // What a delete would take with it, and what blocks it. Correlated
        // subqueries rather than joins: the grid pages ten rows at a time, and
        // joining peer_group_members would multiply each event row out.
        $query->selectRaw(self::countSubqueries());

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
                // Responsive OFF since the Status column and the switch landed: with
                // eight columns and a three-action row group the table is wider than
                // the card, and Responsive "solves" that by collapsing the LAST column
                // into a child row - hiding Edit / the switch / Delete behind an
                // expander. The panel's .table-responsive wrapper scrolls instead.
                'responsive' => false,
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
            Column::computed('status')->title('Status')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PeerEvents_' . date('YmdHis');
    }
}
