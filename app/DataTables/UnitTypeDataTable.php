<?php

namespace App\DataTables;

use App\Models\UnitType;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UnitTypeDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('unit_type', fn ($row) => $row->unit_type ?? '-')
            ->filterColumn('unit_type', function ($query, $keyword) {
                $query->where('unit_type', 'like', "%{$keyword}%");
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.define-unit-type.edit', $row->pk);
                return '<div class="d-flex gap-1 flex-wrap">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                </div>';
            })
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(UnitType $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('unit_type');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('unitTypeTable')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'asc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search within table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                    'emptyTable' => 'No unit type found. <a href="' . route('admin.estate.define-unit-type.create') . '">Add one</a>.',
                ],
                'dom' => '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'scrollY' => '70vh',
                'scrollCollapse' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::make('unit_type')->title('UNIT TYPE')->orderable(true)->searchable(true),
            Column::computed('actions')->title('EDIT')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
        ];
    }

    protected function filename(): string
    {
        return 'UnitType_' . date('YmdHis');
    }
}
