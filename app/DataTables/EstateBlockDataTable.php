<?php

namespace App\DataTables;

use App\Models\EstateBlock;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateBlockDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('block_name', fn ($row) => $row->block_name ?? '-')
            ->filterColumn('block_name', function ($query, $keyword) {
                $query->where('block_name', 'like', "%{$keyword}%");
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.define-block-building.edit', $row->pk);
                $deleteUrl = route('admin.estate.define-block-building.destroy', $row->pk);
                $token = csrf_token();

                return '<div class="d-flex gap-1 flex-wrap">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline delete-form">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0)" class="text-primary" title="Delete" onclick="return confirm(\'Are you sure you want to delete this block/building?\');"><i class="material-icons material-symbols-rounded">delete</i></a>
                    </form>
                </div>';
            })
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(EstateBlock $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('block_name');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateBlockTable')
            ->addTableClass('table w-100')
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
                    'search' => 'Search:',
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
                    'emptyTable' => 'No block/building found. <a href="' . route('admin.estate.define-block-building.create') . '">Add one</a>.',
                ],
                'dom' => '<"row mb-3"<"col-12 col-md-6"l><"col-12 col-md-6"f>>rt<"row mt-3"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::make('block_name')->title('Building / Block')->orderable(true)->searchable(true),
            Column::computed('actions')->title('Actions')->addClass('text-nowrap')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateBlock_' . date('YmdHis');
    }
}
