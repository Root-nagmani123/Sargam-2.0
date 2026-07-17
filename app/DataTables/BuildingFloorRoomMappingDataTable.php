<?php

namespace App\DataTables;

use App\Models\BuildingFloorRoomMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BuildingFloorRoomMappingDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('building_master_pk', fn($row) => optional($row->building)->building_name ?? '—')
            ->addColumn('floor_master_pk', fn($row) => optional($row->floor)->floor_name ?? '—')
            ->addColumn('room_name', fn($row) => $row->room_name ?? '-')
            ->addColumn('room_type', fn($row) => $row->room_type ?? '-')
            ->addColumn('capacity', fn($row) => $row->capacity ?? '-')
            ->addColumn('comment', function ($row) {
                return '<input type="text" class="comment-input" data-id="' . $row->pk . '"
                            value="' . htmlspecialchars($row->comment ?? '') . '" placeholder="Add comment">';
            })
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('hostel.building.floor.room.map.destroy', encrypt($row->pk));
                $isActive  = (int) $row->active_inactive === 1;
                $checked   = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                $rn = $row->room_name ?? '';
                $roomMiddle = '';
                if ($rn !== '') {
                    $roomSuffix = substr($rn, 6);
                    $roomMiddle = explode('-', $roomSuffix)[0] ?? '';
                }

                $editBtn = '<button type="button" class="programme-action-btn hr-edit-btn" aria-label="Edit room"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-building="' . e($row->building_master_pk) . '"'
                        . ' data-floor="' . e($row->floor_master_pk) . '"'
                        . ' data-roomtype="' . e($row->room_type) . '"'
                        . ' data-roomname="' . e($roomMiddle) . '"'
                        . ' data-capacity="' . e($row->capacity) . '"'
                        . ' data-comment="' . e($row->comment) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline-flex m-0" onsubmit="return confirm(\'Are you sure you want to delete this room mapping?\')">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" aria-label="Delete room">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                        . '</button>'
                        . '</form>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="building_floor_room_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                    ' . $deleteHtml . '
                </div>';
            })
            ->setRowId('pk')
            ->orderColumn('building_master_pk', function ($query, $order) {
                $query->orderBy(
                    \App\Models\BuildingMaster::select('building_name')
                        ->whereColumn('building_master.pk', 'building_floor_room_mapping.building_master_pk'),
                    $order
                );
            })
            ->orderColumn('floor_master_pk', function ($query, $order) {
                $query->orderBy(
                    \App\Models\FloorMaster::select('floor_name')
                        ->whereColumn('floor_master.pk', 'building_floor_room_mapping.floor_master_pk'),
                    $order
                );
            })
            ->orderColumn('room_name', function ($query, $order) {
                $query->orderBy('room_name', $order);
            })
            ->orderColumn('room_type', function ($query, $order) {
                $query->orderBy('room_type', $order);
            })
            ->orderColumn('capacity', function ($query, $order) {
                $query->orderBy('capacity', $order);
            })
            ->filterColumn('room_name', function ($query, $keyword) {
                $query->where('room_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('comment', function ($query, $keyword) {
                $query->whereRaw("IFNULL(comment,'') like ?", ["%{$keyword}%"]);
            })
            ->filterColumn('capacity', function ($query, $keyword) {
                $query->where('capacity', 'like', "%{$keyword}%");
            })
            ->rawColumns(['comment', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\BuildingFloorRoomMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BuildingFloorRoomMapping $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['building', 'floor']);

        // Default newest-first, but ONLY when the user hasn't clicked a column
        // to sort — otherwise this base order would dominate (pk is unique, so
        // a requested secondary sort would never take visible effect). When an
        // order is requested, Yajra applies it on the unordered query.
        if (empty(request('order'))) {
            $query->latest('pk');
        }

        if (request()->filled('building_id')) {
            $query->where('building_master_pk', request('building_id'));
        }
        if (request()->filled('room_type')) {
            $query->where('room_type', request('room_type'));
        }
        if (request()->filled('status')) {
            $query->where('active_inactive', request('status'));
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('hostelbuildingfloorroommapping-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->selectStyleSingle()
                    ->responsive(true)
                    ->parameters([
                        'responsive'   => true,
                        'scrollX'      => false,
                        'autoWidth'    => false,
                        'ordering'     => true,
                        // Keep DataTables' native server-side ordering (see
                        // datatable-global-ui.js): clicking a header re-queries and
                        // sorts the FULL dataset, not just the visible page.
                        'sargamServerOrder' => true,
                        'searching'    => true,
                        'lengthChange' => true,
                        'pageLength'   => 10,
                        'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'order'        => [],
                        'language'     => [
                            'search'           => '',
                            'searchPlaceholder' => 'Search',
                            'paginate'         => [
                                'previous' => '‹',
                                'next'     => '›',
                            ],
                            'lengthMenu'   => 'Showing _MENU_',
                            'info'         => 'of _TOTAL_ items',
                            'infoEmpty'    => 'of 0 items',
                            'infoFiltered' => 'of _MAX_ items',
                        ],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload'),
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('building_master_pk')->title('Building Name')->orderable(true)->searchable(false),
            Column::make('floor_master_pk')->title('Floor Name')->orderable(true)->searchable(false),
            Column::make('room_name')->title('Room Name')->orderable(true),
            Column::make('room_type')->title('Room Type')->orderable(true)->searchable(false),
            Column::make('capacity')->title('Capacity')->orderable(true)->addClass('text-center'),
            Column::computed('comment')->title('Comment')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'BuildingFloorRoomMapping_' . date('YmdHis');
    }
}
