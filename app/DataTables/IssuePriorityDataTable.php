<?php

namespace App\DataTables;

use App\Models\IssuePriorityMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssuePriorityDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('description', function ($row) {
                return $row->description ?? '-';
            })
            ->addColumn('status', function ($row) {
                return $row->status == 1
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('admin.issue-priorities.destroy', $row->pk);
                $token = csrf_token();
                $name = e(addslashes($row->priority));
                $description = e(addslashes($row->description));

                return '<button type="button" class="btn btn-sm btn-warning" onclick="editPriority(' . $row->pk . ', \'' . $name . '\', \'' . $description . '\', ' . $row->status . ')">
                        <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                    </button>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this priority?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon> Delete
                        </button>
                    </form>';
            })
            ->rawColumns(['status', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssuePriorityMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('priority');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prioritiesTable')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'order' => [],
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search priorities:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ priorities',
                    'infoEmpty' => 'No priorities',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No matching priorities found',
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
            Column::computed('DT_RowIndex')->title('ID')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::make('priority')->title('Priority Name'),
            Column::computed('description')->title('Description')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'IssuePriorities_' . date('YmdHis');
    }
}
