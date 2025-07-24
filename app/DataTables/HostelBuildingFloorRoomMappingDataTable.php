<?php

namespace App\DataTables;

use App\Models\HostelFloorRoomMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class HostelBuildingFloorRoomMappingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('hostel_building_floor_mapping_pk', function ($row) {
                $building = optional(optional($row->buildingFloor)->building)->hostel_building_name ?? '—';
                $floor = optional(optional($row->buildingFloor)->floor)->hostel_floor_name ?? '—';
                return '<label class="text-dark">' . $building . '-' . $floor . '</label>';
            })
            ->addColumn('hostel_room_master_pk', function($row) {
                return '<label class="text-dark">' . optional($row->room)->hostel_room_name ?? '—' . '</label>';
            })
            ->addColumn('actions', function ($row) {
                return '<a href="' . route('hostel.building.floor.room.map.edit', encrypt($row->pk)) . '" class="btn btn-sm btn-primary">Edit</a>
                ';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="hostel_floor_room_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->filterColumn('hostel_building_floor_mapping_pk', function ($query, $keyword) {
                $query->whereHas('buildingFloor', function ($q) use ($keyword) {
                    $q->whereHas('building', function ($q) use ($keyword) {
                        $q->where('hostel_building_name', 'like', "%{$keyword}%");
                    })->orWhereHas('floor', function ($q) use ($keyword) {
                        $q->where('hostel_floor_name', 'like', "%{$keyword}%");
                    });
                });
            })
            
            ->filterColumn('hostel_room_master_pk', function ($query, $keyword) {
                $query->whereHas('room', function ($q) use ($keyword) {
                    $q->where('hostel_room_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['hostel_building_floor_mapping_pk', 'hostel_room_master_pk', 'actions', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\HostelBuildingFloorMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(HostelFloorRoomMapping $model): QueryBuilder
    {
        return $model->with([
            'buildingFloor.building:pk,hostel_building_name',
            'buildingFloor.floor:pk,hostel_floor_name'
        ])->latest('pk');
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
            Column::make('hostel_building_floor_mapping_pk')->title('Hostel Building Floor Mapping')->addClass('text-center')->orderable(false),
            Column::make('hostel_room_master_pk')->title('Hostel Room Master')->addClass('text-center')->orderable(false),
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
        return 'HostelBuildingFloorRoomMapping_' . date('YmdHis');
    }
}
