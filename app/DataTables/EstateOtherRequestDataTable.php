<?php

namespace App\DataTables;

use App\Models\EstateOtherRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateOtherRequestDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('request_no_oth', fn($row) => $row->request_no_oth ?? 'N/A')
            ->editColumn('emp_name', fn($row) => $row->emp_name ?? 'N/A')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('emp_name', 'like', "%{$searchValue}%")
                            ->orWhere('f_name', 'like', "%{$searchValue}%")
                            ->orWhere('section', 'like', "%{$searchValue}%")
                            ->orWhere('request_no_oth', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->orderColumn('DT_RowIndex', 'estate_other_req.pk $1')
            ->addColumn('actions', function ($row) {
                $viewUrl = route('admin.estate.possession-view', ['requester_id' => $row->pk]);
                $deleteUrl = route('admin.estate.other-estate-request.destroy', ['id' => $row->pk]);
                $doj = $row->doj_acad ? $row->doj_acad->format('Y-m-d') : '';

                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="javascript:void(0);" class="text-primary btn-edit-other-request" title="Edit"
                        data-id="' . (int) $row->pk . '"
                        data-employee_name="' . e($row->emp_name ?? '') . '"
                        data-father_name="' . e($row->f_name ?? '') . '"
                        data-section="' . e($row->section ?? '') . '"
                        data-doj_academy="' . e($doj) . '">
                        <i class="material-symbols-rounded" style="font-size:18px;">edit</i>
                    </a>
                    <a href="javascript:void(0);" class="text-primary btn-delete-other-request" data-url="' . e($deleteUrl) . '" data-id="' . $row->pk . '" title="Delete">
                        <i class="material-symbols-rounded" style="font-size:18px;">delete</i>
                    </a>
                </div>';
            })
            ->rawColumns(['actions']);
    }

    public function query(EstateOtherRequest $model): QueryBuilder
    {
        return $model->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateRequestTable')
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
                'order' => [[0, 'asc']],
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
                ],
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(true)->searchable(false)->width('80px'),
            Column::make('request_no_oth')->title('Request ID')->orderable(false)->searchable(true),
            Column::make('emp_name')->title('Employee Name')->orderable(false)->searchable(true),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EstateOtherRequest_' . date('YmdHis');
    }
}
