<?php

namespace App\DataTables;

use App\Models\EstateHacApprovedRow;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Single table for HAC Approved: Change requests + New requests.
 */
class EstateHacApprovedDataTable extends DataTable
{
    public function dataTable(EloquentBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('request_id', fn ($row) => e($row->request_id ?? '—'))
            ->editColumn('request_date', function ($row) {
                $d = $row->request_date;
                if (!$d) return '—';
                return \Carbon\Carbon::parse($d)->format('d-m-Y');
            })
            ->editColumn('emp_name', fn ($row) => e($row->emp_name ?? '—'))
            ->editColumn('employee_id', fn ($row) => e($row->employee_id ?? '—'))
            ->editColumn('emp_designation', fn ($row) => e($row->emp_designation ?? '—'))
            ->editColumn('pay_scale', fn ($row) => e($row->pay_scale ?? '—'))
            ->editColumn('doj_pay_scale', function ($row) {
                $d = $row->doj_pay_scale ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_service', function ($row) {
                $d = $row->doj_service ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->doj_academic ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('eligibility_label', fn ($row) => e($row->eligibility_label ?? '—'))
            ->editColumn('request_type', fn ($row) => $row->request_type === 'change'
                ? '<span class="badge bg-info">Change Request</span>'
                : '<span class="badge bg-secondary">New Request</span>')
            ->editColumn('current_or_availability', fn ($row) => e($row->current_or_availability ?? '—'))
            ->editColumn('remarks', fn ($row) => \Illuminate\Support\Str::limit(e($row->remarks ?? ''), 60))
            ->addColumn('action', function ($row) {
                if ($row->request_type === 'change') {
                    $status = (int) ($row->change_ap_dis_status ?? 0);
                    if ($status === 1) {
                        return '<span class="badge bg-success">Approved</span>';
                    }
                    if ($status === 2) {
                        return '<span class="badge bg-danger">Disapproved</span>';
                    }
                    $reqId = e($row->request_id ?? 'N/A');
                    return '<div class="d-flex flex-wrap gap-1 justify-content-center">
                        <button type="button" class="btn btn-sm btn-success btn-approve-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '">Approve</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-disapprove-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '">Disapprove</button>
                    </div>';
                }
                $url = route('admin.estate.new-request.allot-details', ['id' => $row->source_pk]);
                return '<button type="button" class="btn btn-sm btn-success btn-allot-new-request" data-id="' . (int) $row->source_pk . '" data-req-id="' . e($row->request_id ?? '') . '" data-details-url="' . e($url) . '" title="Allot house (add to Possession Details)">
                    <i class="bi bi-house-add me-1"></i> Allot
                </button>';
            })
            ->rawColumns(['request_type', 'action'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue !== '') {
                    $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                    $query->where(function ($q) use ($searchLike) {
                        $q->where('request_id', 'like', $searchLike)
                            ->orWhere('emp_name', 'like', $searchLike)
                            ->orWhere('employee_id', 'like', $searchLike)
                            ->orWhere('emp_designation', 'like', $searchLike)
                            ->orWhere('pay_scale', 'like', $searchLike)
                            ->orWhere('current_or_availability', 'like', $searchLike)
                            ->orWhere('remarks', 'like', $searchLike);
                    });
                }
            }, true)
            ->setRowId('pk');
    }

    public function query(EstateHacApprovedRow $model): EloquentBuilder
    {
        $part1 = DB::table('estate_change_home_req_details as ec')
            ->join('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->where('ec.estate_change_hac_status', 1)
            ->select(
                DB::raw("'change' as request_type"),
                'ec.pk as source_pk',
                'ec.pk as pk',
                'ec.estate_change_req_ID as request_id',
                'ec.change_req_date as request_date',
                'eh.emp_name',
                'eh.employee_id',
                'eh.emp_designation',
                'eh.pay_scale',
                'eh.doj_pay_scale',
                'eh.doj_service',
                'eh.doj_academic',
                DB::raw("CASE eh.eligibility_type_pk WHEN 61 THEN 'Type-I' WHEN 62 THEN 'Type-II' WHEN 63 THEN 'Type-III' ELSE 'Type-IV' END as eligibility_label"),
                'ec.change_house_no as current_or_availability',
                'ec.remarks',
                'ec.change_ap_dis_status'
            );

        $hasPossessionPks = DB::table('estate_possession_details')
            ->whereNotNull('estate_home_request_details')
            ->pluck('estate_home_request_details')
            ->unique()
            ->values()
            ->all();

        $part2 = DB::table('estate_home_request_details as eh')
            ->where('eh.hac_status', 1)
            ->where('eh.change_status', 0)
            ->when(!empty($hasPossessionPks), function ($q) use ($hasPossessionPks) {
                $q->whereNotIn('eh.pk', $hasPossessionPks);
            })
            ->select(
                DB::raw("'new' as request_type"),
                'eh.pk as source_pk',
                'eh.pk as pk',
                'eh.req_id as request_id',
                'eh.req_date as request_date',
                'eh.emp_name',
                'eh.employee_id',
                'eh.emp_designation',
                'eh.pay_scale',
                'eh.doj_pay_scale',
                'eh.doj_service',
                'eh.doj_academic',
                DB::raw("CASE eh.eligibility_type_pk WHEN 61 THEN 'Type-I' WHEN 62 THEN 'Type-II' WHEN 63 THEN 'Type-III' ELSE 'Type-IV' END as eligibility_label"),
                'eh.current_alot as current_or_availability',
                'eh.remarks',
                DB::raw('NULL as change_ap_dis_status')
            );

        $unionQuery = $part1->unionAll($part2);

        return $model->newQuery()
            ->fromSub($unionQuery, 'hac_approved')
            ->orderBy('request_date', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateHacApprovedTable')
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
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
                'order' => [[2, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search within table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'dom' => '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
                'initComplete' => "function() { var tbl = document.getElementById('estateHacApprovedTable'); if (tbl && tbl.parentNode && !tbl.parentNode.classList.contains('table-responsive')) { var wrap = document.createElement('div'); wrap.className = 'table-responsive'; wrap.style.overflowX = 'auto'; wrap.style.webkitOverflowScrolling = 'touch'; tbl.parentNode.insertBefore(wrap, tbl); wrap.appendChild(tbl); } }",
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('request_type')->title('TYPE')->orderable(true)->searchable(false)->width('120px'),
            Column::make('request_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('request_date')->title('REQUEST DATE')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('NAME')->orderable(true)->searchable(true),
            Column::make('employee_id')->title('EMP.ID')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('DESIGNATION')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('PAY SCALE')->orderable(true)->searchable(true),
            Column::make('doj_pay_scale')->title('DOJ PAY SCALE')->orderable(false)->searchable(false),
            Column::make('doj_service')->title('DOJ SERVICE')->orderable(false)->searchable(false),
            Column::make('doj_academic')->title('DOJ ACADEMY')->orderable(false)->searchable(false),
            Column::make('eligibility_label')->title('ELIGIBILITY')->orderable(false)->searchable(false),
            Column::make('current_or_availability')->title('CURRENT ALLOTMENT / AVAILABILITY')->orderable(true)->searchable(true),
            Column::make('remarks')->title('REMARKS')->orderable(false)->searchable(true),
            Column::computed('action')->title('ACTION')->addClass('text-center')->orderable(false)->searchable(false)->width('180px'),
        ];
    }

    protected function filename(): string
    {
        return 'HacApproved_' . date('YmdHis');
    }
}
