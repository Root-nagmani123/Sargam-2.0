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
                    $query->where(function ($q) use ($searchValue) {
                        $q->whereHas('estateHomeRequestDetails', function ($sub) use ($searchValue) {
                            $sub->where('estate_home_request_details.emp_name', 'like', "%{$searchValue}%")
                                ->orWhere('estate_home_request_details.employee_id', 'like', "%{$searchValue}%")
                                ->orWhere('estate_home_request_details.emp_designation', 'like', "%{$searchValue}%")
                                ->orWhere('estate_home_request_details.pay_scale', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('estate_change_home_req_details.estate_change_req_ID', 'like', "%{$searchValue}%")
                        ->orWhere('estate_change_home_req_details.change_house_no', 'like', "%{$searchValue}%")
                        ->orWhere('estate_change_home_req_details.remarks', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->filterColumn('emp_name', function ($query, $keyword) {
                $query->whereHas('estateHomeRequestDetails', function ($q) use ($keyword) {
                    $q->where('estate_home_request_details.emp_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('employee_id', function ($query, $keyword) {
                $query->whereHas('estateHomeRequestDetails', function ($q) use ($keyword) {
                    $q->where('estate_home_request_details.employee_id', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('emp_designation', function ($query, $keyword) {
                $query->whereHas('estateHomeRequestDetails', function ($q) use ($keyword) {
                    $q->where('estate_home_request_details.emp_designation', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('pay_scale', function ($query, $keyword) {
                $query->whereHas('estateHomeRequestDetails', function ($q) use ($keyword) {
                    $q->where('estate_home_request_details.pay_scale', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('availability_as_per_request', function ($query, $keyword) {
                $query->where('estate_change_home_req_details.change_house_no', 'like', "%{$keyword}%");
            })
            ->orderColumn('estate_change_req_ID', fn ($query, $order) => $query->orderBy('estate_change_home_req_details.estate_change_req_ID', $order))
            ->orderColumn('change_req_date', fn ($query, $order) => $query->orderBy('estate_change_home_req_details.change_req_date', $order))
            ->orderColumn('availability_as_per_request', fn ($query, $order) => $query->orderBy('estate_change_home_req_details.change_house_no', $order))
            ->setRowId('pk');
    }

    public function query(EstateChangeHomeReqDetails $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('estateHomeRequestDetails')
            ->where('estate_change_home_req_details.estate_change_hac_status', 1);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateChangeRequestTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => true,
                'scrollX' => false,
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
                'dom' => '<"row flex-wrap align-items-center justify-content-between gap-2 mb-3"<"col-auto"l><"col-auto"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('estate_change_req_ID')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('change_req_date')->title('REQUEST DATE')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('NAME')->orderable(false)->searchable(true),
            Column::make('employee_id')->title('EMP.ID')->orderable(false)->searchable(true),
            Column::make('emp_designation')->title('DESIGNATION')->orderable(false)->searchable(true),
            Column::make('pay_scale')->title('CURRENT PAY SCALE')->orderable(false)->searchable(true),
            Column::make('doj_pay_scale')->title('DATE OF JOINING IN CURRENT PAY SCALE')->orderable(false)->searchable(false),
            Column::make('doj_service')->title('DATE OF JOINING IN SERVICE')->orderable(false)->searchable(false),
            Column::make('doj_academic')->title('DATE OF JOINING IN ACADEMY')->orderable(false)->searchable(false),
            Column::computed('retirement_deputation')->title('RETIREMENT DATE / DEPUTATION END DATE')->orderable(false)->searchable(false),
            Column::make('eligibility_type_pk')->title('ELIGIBILITY TYPE')->orderable(false)->searchable(false),
            Column::computed('request_type')->title('REQUEST TYPE')->orderable(false)->searchable(false),
            Column::make('availability_as_per_request')->title('AVAILABILITY AS PER REQUEST')->orderable(true)->searchable(true),
            Column::make('remarks')->title('REMARKS')->orderable(false)->searchable(true),
            Column::computed('approve_disapprove')->title('APPROVE/DISAPPROVE')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EstateChangeRequest_' . date('YmdHis');
    }
}
