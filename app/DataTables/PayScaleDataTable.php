<?php

namespace App\DataTables;

use App\Models\PayScale;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PayScaleDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('salary_grade', fn ($row) => $row->salary_grade ?? '-')
            ->filterColumn('salary_grade', function ($query, $keyword) {
                $query->where('salary_grade', 'like', "%{$keyword}%");
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.define-pay-scale.edit', $row->pk);
                $deleteUrl = route('admin.estate.define-pay-scale.destroy', $row->pk);
                $token = csrf_token();

                return '<div class="d-flex gap-1 flex-wrap">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this pay scale?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0)" class="text-primary" title="Delete"><i class="material-icons material-symbols-rounded">delete</i></a>
                    </form>
                </div>';
            })
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(PayScale $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('salary_grade');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('payScaleTable')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
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
                ],
                'dom' => '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::make('salary_grade')->title('Salary Grade / Pay Scale')->orderable(true)->searchable(true),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'PayScale_' . date('YmdHis');
    }
}
