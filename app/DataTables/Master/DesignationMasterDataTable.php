<?php

namespace App\DataTables\Master;

use App\Models\DesignationMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class DesignationMasterDataTable extends DataTable
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
            ->addColumn('designation_name', fn($row) => $row->designation_name ?? '-')
                ->addColumn('action', function ($row) {

                    if(auth()->user()->can('master.designation.edit')) {
                        $editUrl = route('master.designation.edit', ['id' => encrypt($row->pk)]);
                        return '<a href="' . $editUrl . '" class="btn btn-primary btn-sm">Edit</a>';
                    }
                    return '';
                })
            ->addColumn('status', function ($row) {
                if(auth()->user()->can('master.designation.active_inactive')) {
                    $checked = $row->active_inactive == 1 ? 'checked' : '';
                        return '<div class="form-check form-switch d-inline-block ms-2">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="designation_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>';
                }
                return '';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('designation_name', function ($query, $keyword) {
                $query->where('designation_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['designation_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\DesignationMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(DesignationMaster $model): QueryBuilder
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
            ->setTableId('departmentmaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax() // This will use the current route for ajax
            // ->orderBy(1)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('designation_name')->title('Designation Name')->orderable(false)->addClass('text-center'),
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
        return 'DesignationMaster_' . date('YmdHis');
    }
}
