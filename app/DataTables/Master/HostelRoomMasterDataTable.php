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
            ->addColumn('action', function ($row) {
                if(auth()->user()->can('master.hostel-room-master.edit')) {
                    $editUrl = route('master.hostel.room.edit', ['id' => encrypt($row->pk)]);
                    return '<a href="' . $editUrl . '" class="btn btn-primary btn-sm">Edit</a>';
                }
            })
            ->addColumn('status', function ($row) {
                if(auth()->user()->can('master.hostel-room-master.active_inactive')) {
                    $checked = $row->active_inactive == 1 ? 'checked' : '';
                        return '<div class="form-check form-switch d-inline-block ms-2">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="hostel_room_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>';
                }
            })
            ->setRowId('pk')
            ->setRowClass('text-center')
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
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->parameters([
                        'order' => [],
                    ])
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('hostel_room_name')->title('Hostel Room Name')->orderable(false)->addClass('text-center'),
            Column::make('capacity')->title('Capacity')->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center')
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
