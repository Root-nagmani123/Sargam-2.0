<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateRequestPutInHacDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('req_id', fn ($row) => $row->req_id ?? '—')
            ->editColumn('req_date', function ($row) {
                $d = $row->req_date;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('emp_name', fn ($row) => e($row->emp_name ?? '—'))
            ->editColumn('employee_id', fn ($row) => e($row->employee_id ?? '—'))
            ->editColumn('emp_designation', fn ($row) => e($row->emp_designation ?? '—'))
            ->editColumn('pay_scale', fn ($row) => e($row->pay_scale ?? '—'))
            ->editColumn('doj_pay_scale', function ($row) {
                $d = $row->doj_pay_scale;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_service', function ($row) {
                $d = $row->doj_service;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->doj_academic;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('current_alot', fn ($row) => e($row->current_alot ?? '—'))
            ->editColumn('remarks', fn ($row) => \Illuminate\Support\Str::limit(e($row->remarks ?? ''), 80))
            ->addColumn('put_in_hac', function ($row) {
                return '<div class="form-check form-check-inline d-flex justify-content-center">
                    <input type="checkbox" class="form-check-input put-in-hac-checkbox" data-pk="' . (int) $row->pk . '" data-req-id="' . e($row->req_id ?? '') . '">
                    <label class="form-check-label visually-hidden">Put in HAC</label>
                </div>';
            })
            ->rawColumns(['put_in_hac'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue !== '') {
                    $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                    $query->where(function ($q) use ($searchLike) {
                        $q->where('estate_home_request_details.req_id', 'like', $searchLike)
                            ->orWhere('estate_home_request_details.emp_name', 'like', $searchLike)
                            ->orWhere('estate_home_request_details.employee_id', 'like', $searchLike)
                            ->orWhere('estate_home_request_details.current_alot', 'like', $searchLike)
                            ->orWhere('estate_home_request_details.remarks', 'like', $searchLike);
                    });
                }
            }, true)
            ->setRowId('pk');
    }

    public function query(EstateHomeRequestDetails $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'estate_home_request_details.pk',
                'estate_home_request_details.req_id',
                'estate_home_request_details.req_date',
                'estate_home_request_details.emp_name',
                'estate_home_request_details.employee_id',
                'estate_home_request_details.emp_designation',
                'estate_home_request_details.pay_scale',
                'estate_home_request_details.doj_pay_scale',
                'estate_home_request_details.doj_academic',
                'estate_home_request_details.doj_service',
                'estate_home_request_details.current_alot',
                'estate_home_request_details.remarks',
                'estate_home_request_details.hac_status',
            ])
            ->where('estate_home_request_details.hac_status', 0)
            ->where('estate_home_request_details.change_status', 0)
            ->orderBy('estate_home_request_details.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('putInHacTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'desc']],
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
                'dom' => '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('req_id')->title('Request ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('Request Date')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('NAME')->orderable(true)->searchable(true),
            Column::make('employee_id')->title('Employee ID')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('Designation')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('Current Pay Scale')->orderable(true)->searchable(true),
            Column::make('doj_pay_scale')->title('Date of Joining in Current Pay Scale')->orderable(false)->searchable(false),
            Column::make('doj_service')->title('Date of Joining in Service')->orderable(false)->searchable(false),
            Column::make('doj_academic')->title('Date of Joining in Academy')->orderable(false)->searchable(false),
            Column::make('current_alot')->title('Current Allotment')->orderable(true)->searchable(true),
            Column::make('remarks')->title('Remarks')->orderable(false)->searchable(true),
            Column::computed('put_in_hac')->title('Put in HAC')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'PutInHac_' . date('YmdHis');
    }
}
