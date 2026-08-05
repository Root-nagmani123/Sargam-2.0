<?php

namespace App\DataTables\Master;

use App\Models\HostelRoomMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class HostelRoomMasterDataTable extends DataTable
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
            ->addColumn('hostel_room_name', fn($row) => $row->hostel_room_name ?? '-')
            ->addColumn('capacity', fn($row) => $row->capacity ?? '-')
            ->addColumn('status', function ($row) {
                // Soft status badge — the canonical country/index pattern (new-design-index-page.md §3b).
                return (int) $row->active_inactive === 1
                    ? '<span class="status-pill badge bg-success-subtle">Active</span>'
                    : '<span class="status-pill badge bg-danger-subtle">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                // Icon-over-label actions (§3b). Hostel Room has no delete route, so the group is
                // Edit + status toggle only. `hr-edit-btn` is kept for the modal JS click handler.
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<button type="button" class="hr-act hr-act--edit hr-edit-btn" aria-label="Edit hostel room"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->hostel_room_name) . '"'
                        . ' data-capacity="' . e($row->capacity) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span>'
                        . '</button>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="hostel_room_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('hostel_room_name', function ($query, $keyword) {
                $query->where('hostel_room_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['hostel_room_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\HostelRoomMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(HostelRoomMaster $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('hostelroommaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->selectStyleSingle()
                    ->responsive(true)
                    ->parameters([
                        'responsive'   => true,
                        'scrollX'      => false,
                        'autoWidth'    => false,
                        'ordering'     => false,
                        'searching'    => true,
                        'lengthChange' => true,
                        'pageLength'   => 10,
                        'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'order'        => [],
                        'language'     => [
                            'search'            => '',
                            'searchPlaceholder' => 'Search',
                            'paginate'          => [
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
            Column::make('hostel_room_name')->title('Hostel Room Name')->orderable(false),
            Column::make('capacity')->title('Capacity')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'HostelRoomMaster_' . date('YmdHis');
    }
}
