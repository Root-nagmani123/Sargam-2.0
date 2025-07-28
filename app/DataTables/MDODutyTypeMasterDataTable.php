<?php

namespace App\DataTables;

use App\Models\MDODutyTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MDODutyTypeMasterDataTable extends DataTable
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
            ->setRowId('pk')
            ->editColumn('mdo_duty_type_name', fn($row) => '<label class="text-dark">' . $row->mdo_duty_type_name . '</label>')
            ->filterColumn('mdo_duty_type_name', function ($query, $keyword) {
                $query->where('mdo_duty_type_name', 'like', "%{$keyword}%");
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                   data-table="mdo_duty_type_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
            })
            ->addColumn('actions', function ($row) {
                return view('admin.master.mdo_duty_type.actions', compact('row'))->render();
            })
            ->rawColumns(['mdo_duty_type_name', 'status', 'actions']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\MDODutyTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(MDODutyTypeMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('mdodutytypemaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
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
            Column::make('pk')->title('ID')->addClass('text-center')->orderable(false),
            Column::make('mdo_duty_type_name')->title('Duty Type Name')->addClass('text-center')->orderable(false),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'MDODutyTypeMaster_' . date('YmdHis');
    }
}
