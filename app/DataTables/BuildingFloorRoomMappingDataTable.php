<?php

namespace App\DataTables;

use App\Models\BuildingFloorRoomMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
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
            ->addColumn('building_master_pk', function ($row) {

                return '<label class="text-dark">' . optional($row->building)->building_name . '</label>';
            })
            ->addColumn('floor_master_pk', function($row) {
                return '<label class="text-dark">' . optional($row->floor)->floor_name ?? '—' . '</label>';
            })
            ->addColumn('room_name', function($row) {
                return '<label class="text-dark">' . $row->room_name . '</label>';
            })
            ->addColumn('capacity', function($row) {
                return '<label class="text-dark">' . $row->capacity . '</label>';
            })
            ->addColumn('actions', function ($row) {
                return '<a href="' . route('hostel.building.floor.room.map.edit', encrypt($row->pk)) . '" class="btn btn-sm btn-primary">Edit</a>
                ';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="building_floor_room_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })

            ->rawColumns(['building_master_pk', 'floor_master_pk', 'room_name', 'capacity', 'actions', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\BuildingFloorRoomMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BuildingFloorRoomMapping $model): QueryBuilder
    {
        return $model->newQuery()->latest('pk');
        // return $model->with([
        //     'buildingFloor.building:pk,hostel_building_name',
        //     'buildingFloor.floor:pk,hostel_floor_name'
        // ])->latest('pk');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('hostelbuildingfloormapping-table')
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
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('building_master_pk')->title('Building Name')->addClass('text-center')->orderable(false),
            Column::make('floor_master_pk')->title('Floor')->addClass('text-center')->orderable(false),
            Column::make('room_name')->title('Room Name')->addClass('text-center')->orderable(false),
            Column::make('capacity')->title('Capacity')->addClass('text-center')->orderable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
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
