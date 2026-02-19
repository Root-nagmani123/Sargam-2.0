<?php

namespace App\DataTables;

use App\Models\EstateChangeHomeReqDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateChangeRequestDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('estate_change_req_ID', fn ($row) => $row->estate_change_req_ID ?? 'N/A')
            ->editColumn('change_req_date', function ($row) {
                $d = $row->change_req_date;
                if (!$d) return '—';
                if (strlen($d) > 10) {
                    $dt = \Carbon\Carbon::parse($d);
                    return $dt->format('d-m-Y');
                }
                return $d;
            })
            ->editColumn('emp_name', fn ($row) => $row->estateHomeRequestDetails->emp_name ?? '—')
            ->editColumn('employee_id', fn ($row) => $row->estateHomeRequestDetails->employee_id ?? '—')
            ->editColumn('emp_designation', fn ($row) => $row->estateHomeRequestDetails->emp_designation ?? '—')
            ->editColumn('pay_scale', fn ($row) => $row->estateHomeRequestDetails->pay_scale ?? '—')
            ->editColumn('doj_pay_scale', function ($row) {
                $d = $row->estateHomeRequestDetails->doj_pay_scale ?? null;
                return $d ? $d->format('d-m-Y') : '—';
            })
            ->editColumn('doj_service', function ($row) {
                $d = $row->estateHomeRequestDetails->doj_service ?? null;
                return $d ? $d->format('d-m-Y') : '—';
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->estateHomeRequestDetails->doj_academic ?? null;
                return $d ? $d->format('d-m-Y') : '—';
            })
            ->editColumn('retirement_deputation', function ($row) {
                return '—';
            })
            ->editColumn('eligibility_type_pk', function ($row) {
                $pk = $row->estateHomeRequestDetails->eligibility_type_pk ?? null;
                if ($pk === null) return '—';
                return 'Type -' . ($pk == 62 ? 'II' : ($pk == 63 ? 'III' : ($pk == 61 ? 'I' : 'IV')));
            })
            ->editColumn('request_type', fn () => 'Change Request')
            ->editColumn('availability_as_per_request', fn ($row) => $row->change_house_no ?? '—')
            ->editColumn('remarks', function ($row) {
                $remarks = $row->remarks ?? '';
                return $remarks ? e($remarks) : '—';
            })
            ->addColumn('approve_disapprove', function ($row) {
                $status = (int) ($row->change_ap_dis_status ?? 0);
                if ($status === 1) {
                    return '<span class="badge bg-success">Approved</span>';
                }
                if ($status === 2) {
                    return '<span class="badge bg-danger">Disapproved</span>';
                }
                $approveUrl = route('admin.estate.change-request.approve', ['id' => $row->pk]);
                $reqId = e($row->estate_change_req_ID ?? 'N/A');
                return '<div class="d-flex flex-wrap gap-1 justify-content-center">
                    <form method="POST" action="' . $approveUrl . '" class="d-inline" data-confirm="Approve this change request?">
                        ' . csrf_field() . '
                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-disapprove-change-request" data-id="' . (int) $row->pk . '" data-request-id="' . $reqId . '">Disapprove</button>
                </div>';
            })
            ->rawColumns(['approve_disapprove'])
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->whereHas('estateHomeRequestDetails', function ($q) use ($searchValue) {
                        $q->where('estate_home_request_details.emp_name', 'like', "%{$searchValue}%")
                            ->orWhere('estate_home_request_details.employee_id', 'like', "%{$searchValue}%")
                            ->orWhere('estate_home_request_details.emp_designation', 'like', "%{$searchValue}%")
                            ->orWhere('estate_home_request_details.pay_scale', 'like', "%{$searchValue}%");
                    })->orWhere('estate_change_home_req_details.estate_change_req_ID', 'like', "%{$searchValue}%")
                      ->orWhere('estate_change_home_req_details.change_house_no', 'like', "%{$searchValue}%")
                      ->orWhere('estate_change_home_req_details.remarks', 'like', "%{$searchValue}%");
                }
            }, true)
            ->setRowId('pk');
    }

    public function query(EstateChangeHomeReqDetails $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('estateHomeRequestDetails')
            ->where('estate_change_home_req_details.estate_change_hac_status', 1)
            ->orderBy('estate_change_home_req_details.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateChangeRequestTable')
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
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('estate_change_req_ID')->title('Request ID')->orderable(true)->searchable(true),
            Column::make('change_req_date')->title('Request Date')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('Name')->orderable(false)->searchable(true),
            Column::make('employee_id')->title('Emp.ID')->orderable(false)->searchable(true),
            Column::make('emp_designation')->title('Designation')->orderable(false)->searchable(true),
            Column::make('pay_scale')->title('Current Pay Scale')->orderable(false)->searchable(true),
            Column::make('doj_pay_scale')->title('Date of Joining in Current Pay Scale')->orderable(false)->searchable(false),
            Column::make('doj_service')->title('Date of Joining in Service')->orderable(false)->searchable(false),
            Column::make('doj_academic')->title('Date of Joining in Academy')->orderable(false)->searchable(false),
            Column::computed('retirement_deputation')->title('Retirement Date / Deputation End Date')->orderable(false)->searchable(false),
            Column::make('eligibility_type_pk')->title('Eligibility Type')->orderable(false)->searchable(false),
            Column::computed('request_type')->title('Request Type')->orderable(false)->searchable(false),
            Column::make('availability_as_per_request')->title('Availability as per Request')->orderable(true)->searchable(true),
            Column::make('remarks')->title('Remarks')->orderable(false)->searchable(true),
            Column::computed('approve_disapprove')->title('Approve/Disapprove')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EstateChangeRequest_' . date('YmdHis');
    }
}
