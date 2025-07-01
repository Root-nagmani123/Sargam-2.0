<?php

namespace App\DataTables;

use App\Models\HostelBuildingFloorMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class HostelBuildingFloorMappingDataTable extends DataTable
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
            ->addColumn('hostel_building_name', 
            function($row) {
                return '<label class="text-dark">' . $row->building->hostel_building_name . '</label>';
            })
            ->addColumn('hostel_floor_name', fn($row) => '<label class="text-dark">' . $row->floor->hostel_floor_name . '</label>')
            ->addColumn('actions', function($row) {
                return '<a href="' . route('hostel.building.map.edit', encrypt($row->pk)) . '" class="btn btn-sm btn-primary">Edit</a>
                ';

            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="hostel_building_floor_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->rawColumns(['hostel_building_name', 'hostel_floor_name', 'actions', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\HostelBuildingFloorMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(HostelBuildingFloorMapping $model): QueryBuilder
    {
        return $model->with(['building', 'floor'])->latest('pk')->newQuery();
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
                    ->orderBy(1)
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
            Column::make('hostel_building_name')->title('Hostel Building Name')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('hostel_floor_name')->title('Hostel Floor Name')->addClass('text-center')->orderable(false)->searchable(false),
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
        return 'HostelBuildingFloorMapping_' . date('YmdHis');
    }
}
