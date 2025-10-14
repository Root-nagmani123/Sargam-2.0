<?php

namespace App\DataTables\Master;

use App\Models\BuildingMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BuildingMasterDataTable extends DataTable
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
            ->addColumn('building_name', fn($row) => $row->building_name ?? '-')
            ->addColumn('no_of_floors', fn($row) => $row->no_of_floors ?? '-')
            ->addColumn('no_of_rooms', fn($row) => $row->no_of_rooms ?? '-')
            ->addColumn('building_type', fn($row) => $row->building_type ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.hostel.building.edit', ['id' => encrypt($row->pk)]);
                
                $deleteUrl = route('master.hostel.building.destroy', ['id' => encrypt($row->pk)]);
                return '
                    <a href="' . $editUrl . '" class="btn btn-primary btn-sm">Edit</a>
                    <form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure?\')">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm" ' . ($row->active_inactive == 0 ? '' : 'disabled') . '>Delete</button>
                    </form>
                ';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="building_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('building_name', function ($query, $keyword) {
                $query->where('building_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['building_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\BuildingMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BuildingMaster $model): QueryBuilder
    {
        return $model->newQuery()->latest('pk');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('hostelbuildingmaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'order' => [],
                    ])
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
            Column::make('building_name')->title('Building Name')->orderable(false)->addClass('text-center'),
            Column::make('no_of_floors')->title('No. of Floors')->orderable(false)->addClass('text-center'),
            Column::make('no_of_rooms')->title('No. of Rooms')->orderable(false)->addClass('text-center'),
            Column::make('building_type')->title('Building Type')->orderable(false)->addClass('text-center'),
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
        return 'BuildingMaster_' . date('YmdHis');
    }
}
