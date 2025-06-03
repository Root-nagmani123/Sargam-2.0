<?php

namespace App\DataTables\Master;

use App\Models\CasteCategoryMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CasteCategoryMasterDataTable extends DataTable
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
            ->addColumn('Seat_name', fn($row) => $row->Seat_name ?? '-')
            ->addColumn('Seat_name_hindi', fn($row) => $row->Seat_name_hindi ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.caste.category.edit', ['id' => encrypt($row->pk)]);
                return '<a href="' . $editUrl . '" class="btn btn-primary btn-sm">Edit</a>';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="caste_category_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('group_name', function ($query, $keyword) {
                $query->where('group_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['Seat_name', 'Seat_name_hindi', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CasteCategoryMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CasteCategoryMaster $model): QueryBuilder
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
            ->setTableId('castecategorymaster-table')
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('Seat_name')->title('Seat Name')->orderable(false)->addClass('text-center'),
            Column::make('Seat_name_hindi')->title('Seat Name (Hindi)')->orderable(false)->addClass('text-center'),
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
        return 'CasteCategoryMaster_' . date('YmdHis');
    }
}
