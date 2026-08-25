<?php

namespace App\DataTables;

use App\Models\PeerColumn;
use App\Models\PeerEvent;
use App\Support\PeerCourseStatusScope;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Peer Evaluation -> Manage Evaluation Columns (top level).
 *
 * One row per EVENT. An event belongs to exactly one course, which is why the
 * design shows "Event 01" repeated down the list against different courses -
 * those are distinct events that happen to share a name.
 *
 * Each row expands (DataTables child row) into that event's groups, and each
 * group expands again into its columns. Both nested levels are fetched on demand
 * from PeerEvaluationColumnController - rendering three levels up front would
 * mean loading every column of every group on page load.
 */
class PeerEvaluationColumnDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', function ($row) {
                $name = $row->course_name ?: '-';

                // A link, not a button: the design underlines it, and it points at
                // the same expansion the Action chevron opens.
                return '<a href="javascript:void(0)" class="pec-link pec-expand" data-event-id="' . (int) $row->id . '">'
                    . e($name) . '</a>';
            })
            ->addColumn('groups', function ($row) {
                $count = (int) ($row->groups_count ?? 0);

                return '<a href="javascript:void(0)" class="pec-link pec-expand" data-event-id="' . (int) $row->id . '">'
                    . $count . '</a>';
            })
            ->addColumn('event', function ($row) {
                return '<a href="javascript:void(0)" class="pec-link pec-expand" data-event-id="' . (int) $row->id . '">'
                    . e($row->event_name ?: '-') . '</a>';
            })
            ->addColumn('action', function ($row) {
                // The caption flips to Close when the row is open - see the page
                // script, which owns the open/closed state.
                return '<button type="button" class="pe-act pec-toggle" data-event-id="' . (int) $row->id . '"'
                    . ' aria-expanded="false">'
                    . '<span class="pe-act__icon"><i class="bi bi-chevron-down" aria-hidden="true"></i></span>'
                    . '<span class="pe-act__label">View</span>'
                    . '</button>';
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_master.course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('event', function ($query, $keyword) {
                $query->where('peer_events.event_name', 'like', "%{$keyword}%");
            })
            ->orderColumn('course_name', 'course_master.course_name $1')
            ->orderColumn('event', 'peer_events.event_name $1')
            ->orderColumn('groups', 'groups_count $1')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (! empty($searchValue)) {
                    $query->where(function ($sub) use ($searchValue) {
                        $sub->where('course_master.course_name', 'like', "%{$searchValue}%")
                            ->orWhere('peer_events.event_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['course_name', 'groups', 'event', 'action'])
            ->setRowId('id');
    }

    public function query(PeerEvent $model): QueryBuilder
    {
        return self::baseQuery($model, [
            'course' => request('course_filter'),
            'type' => request('type_filter'),
            'status' => request('status_filter'),
        ]);
    }

    /**
     * The one query the grid and every export share.
     *
     * @param  array{course?:mixed,type?:mixed,status?:mixed}  $filters
     */
    public static function baseQuery(PeerEvent $model, array $filters = []): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_events.course_id')
            ->select([
                'peer_events.id',
                'peer_events.event_name',
                'peer_events.course_id',
                'peer_events.created_at',
                'course_master.course_name as course_name',
            ])
            ->selectSub(
                DB::table('peer_groups')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('peer_groups.event_id', 'peer_events.id'),
                'groups_count'
            );

        PeerCourseStatusScope::forRelated($query, $filters['status'] ?? null, 'peer_events.course_id');

        if (filled($filters['course'] ?? null)) {
            $query->where('peer_events.course_id', $filters['course']);
        }

        // Rating Type filters the EVENT by whether it owns any column of that type,
        // since the type lives on the column, not the event.
        $type = $filters['type'] ?? null;
        if (in_array($type, array_keys(PeerColumn::TYPES), true)) {
            $query->whereExists(function ($sub) use ($type) {
                $sub->selectRaw('1')
                    ->from('peer_columns')
                    ->whereColumn('peer_columns.event_id', 'peer_events.id')
                    ->where('peer_columns.evaluation_type', $type);
            });
        }

        $allowed = get_Role_by_course();
        if (! empty($allowed)) {
            $query->whereIn('peer_events.course_id', $allowed);
        }

        if (empty(request('order'))) {
            $query->orderBy('peer_events.id', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('peerEvaluationColumnsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                // Responsive off: it collapses the LAST column into a child row,
                // and this table uses child rows itself for the nested levels -
                // the two would fight over the same slot.
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'ordering' => true,
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
            Column::make('groups')->title('Groups')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::make('event')->title('Event')->orderable(true)->searchable(true),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PeerEvaluationColumns_' . date('YmdHis');
    }
}
