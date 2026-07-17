<?php

namespace App\DataTables;

use App\Models\IssueCategoryMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueCategoryDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('description', function ($row) {
                return $row->description
                    ? \Illuminate\Support\Str::limit($row->description, 50)
                    : 'No description';
            })
            ->addColumn('sub_categories_count', function ($row) {
                return $row->subCategories->count();
            })
            ->addColumn('status', function ($row) {
                $checked = $row->status == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-flex justify-content-center">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="issue_category_master" data-column="status" data-id="' . $row->pk . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('admin.issue-categories.destroy', $row->pk);
                $token = csrf_token();
                $name = e(addslashes($row->issue_category));
                $description = e(addslashes($row->description));

                return '<div class="btn-action-group justify-content-center">
                    <a href="javascript:void(0)" class="text-primary" onclick="editCategory(' . $row->pk . ', \'' . $name . '\', \'' . $description . '\', ' . $row->status . ')" title="Edit Category">
                        <i class="material-icons material-symbols-rounded">edit</i>
                    </a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this category?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 text-primary border-0" title="Delete Category">
                            <i class="material-icons material-symbols-rounded">delete</i>
                        </button>
                    </form>
                </div>';
            })
            ->rawColumns(['status', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssueCategoryMaster $model): QueryBuilder
    {
        return $model->newQuery()->with('subCategories')->orderBy('issue_category');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('categoriesTable')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'order' => [[1, 'asc']],
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search categories:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ categories',
                    'infoEmpty' => 'No categories',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No matching categories found',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::make('issue_category')->title('Category Name'),
            Column::computed('description')->title('Description')->orderable(false)->searchable(false),
            Column::computed('sub_categories_count')->title('Sub-Categories')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ComplaintCategory_' . date('YmdHis');
    }
}
