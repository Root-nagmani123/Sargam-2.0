<?php

namespace App\DataTables;

use App\Models\IssueSubCategoryMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueSubCategoryDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('category', function ($row) {
                return $row->category->issue_category ?? '-';
            })
            ->orderColumn('category', 'issue_category_master_pk $1')
            ->filterColumn('category', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereHas('category', function ($q) use ($like) {
                    $q->where('issue_category', 'like', $like);
                });
            })
            ->addColumn('status', function ($row) {
                $checked = $row->status == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-flex justify-content-center">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="issue_sub_category_master" data-column="status" data-id="' . $row->pk . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('admin.issue-sub-categories.destroy', $row->pk);
                $token = csrf_token();
                $categoryId = $row->issue_category_master_pk ?? 'null';
                $name = e(addslashes($row->issue_sub_category));

                return '<div class="d-flex justify-content-center gap-2">
                    <a href="javascript:void(0)" class="text-primary" onclick="editSubCategory(' . $row->pk . ', ' . $categoryId . ', \'' . $name . '\', ' . $row->status . ')" title="Edit Sub-Category">
                        <i class="material-icons material-symbols-rounded" style="font-size: 18px;">edit</i>
                    </a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this sub-category?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 text-primary border-0" title="Delete Sub-Category">
                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;">delete</i>
                        </button>
                    </form>
                </div>';
            })
            ->rawColumns(['status', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssueSubCategoryMaster $model): QueryBuilder
    {
        $query = $model->newQuery()->with('category');

        $categoryId = request('category_id');
        if (filled($categoryId)) {
            $query->where('issue_category_master_pk', $categoryId);
        }

        return $query->orderByDesc('pk');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('subCategoriesTable')
            ->addTableClass('table align-middle text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax('', '', array_filter(['category_id' => request('category_id')], fn ($v) => filled($v)))
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'order' => [],
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search sub-categories:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ sub-categories',
                    'infoEmpty' => 'No sub-categories',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No matching sub-categories found',
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
            Column::computed('category')->title('Category'),
            Column::make('issue_sub_category')->title('Sub-Category Name'),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ComplaintSubCategory_' . date('YmdHis');
    }
}
