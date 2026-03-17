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
            ->editColumn('emp_designation', fn ($row) => e($row->emp_designation ?? '—'))
            ->editColumn('pay_scale', fn ($row) => e($row->pay_scale ?? '—'))
            ->editColumn('request_type', fn ($row) => $row->request_type === 'change'
                ? '<span class="badge bg-danger">Change Request</span>'
                : '<span class="badge bg-primary">New Request</span>')
            ->addColumn('action', function ($row) {
                $detailsPk = (int) ($row->estate_home_request_details_pk ?? $row->source_pk ?? 0);
                $detailsUrl = $detailsPk ? route('admin.estate.request-details', ['id' => $detailsPk]) : '#';
                $detailsLink = $detailsPk
                    ? '<a href="' . e($detailsUrl) . '" class="btn btn-outline-primary btn-sm hac-action-btn" title="View details" aria-label="View details"><i class="material-icons">visibility</i></a>'
                    : '';
                if ($row->request_type === 'change') {
                    $status = (int) ($row->change_ap_dis_status ?? 0);
                    if ($status === 1) {
                        return '<div class="hac-action d-inline-flex align-items-center gap-1 justify-content-center flex-nowrap">'
                            . $detailsLink
                            . '<span class="btn btn-success btn-sm hac-action-btn" title="Approved" aria-label="Approved"><i class="material-icons">check</i></span>'
                            . '</div>';
                    }
                    if ($status === 2) {
                        return '<div class="hac-action d-inline-flex align-items-center gap-1 justify-content-center flex-nowrap">'
                            . $detailsLink
                            . '<span class="badge bg-danger">Disapproved</span>'
                            . '</div>';
                    }
                    $reqId = e($row->request_id ?? 'N/A');
                    return '<div class="hac-action d-inline-flex align-items-center gap-1 justify-content-center flex-nowrap">'
                        . $detailsLink
                        . '<button type="button" class="btn btn-success btn-sm hac-action-btn btn-approve-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '" title="Approve" aria-label="Approve"><i class="material-icons">check</i></button>'
                        . '<button type="button" class="btn btn-outline-danger btn-sm hac-action-btn btn-disapprove-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '" title="Disapprove" aria-label="Disapprove"><i class="material-icons">close</i></button>'
                        . '</div>';
                }
                $url = route('admin.estate.new-request.allot-details', ['id' => $row->source_pk]);
                return '<div class="hac-action d-inline-flex align-items-center gap-1 justify-content-center flex-nowrap">'
                    . $detailsLink
                    . '<button type="button" class="btn btn-sm btn-success hac-action-btn btn-allot-new-request" data-id="' . (int) $row->source_pk . '" data-req-id="' . e($row->request_id ?? '') . '" data-details-url="' . e($url) . '" title="Allot house" aria-label="Allot house"><i class="material-icons">home</i></button>'
                    . '</div>';
            })
            ->rawColumns(['request_type', 'action'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue !== '') {
                    $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                    $query->where(function ($q) use ($searchLike) {
                        $q->where('request_id', 'like', $searchLike)
                            ->orWhere('emp_name', 'like', $searchLike)
                            ->orWhere('emp_designation', 'like', $searchLike)
                            ->orWhere('pay_scale', 'like', $searchLike);
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
                'eh.pk as estate_home_request_details_pk',
                'ec.estate_change_req_ID as request_id',
                'ec.change_req_date as request_date',
                'eh.emp_name',
                'eh.emp_designation',
                'eh.pay_scale',
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
                'eh.pk as estate_home_request_details_pk',
                'eh.req_id as request_id',
                'eh.req_date as request_date',
                'eh.emp_name',
                'eh.emp_designation',
                'eh.pay_scale',
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
                'dom' => '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5 ms-auto text-md-end"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
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
            Column::make('emp_designation')->title('DESIGNATION')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('PAY SCALE')->orderable(true)->searchable(true),
            Column::computed('action')->title('ACTION')->addClass('text-center')->orderable(false)->searchable(false)->width('180px'),
        ];
    }

    protected function filename(): string
    {
        return 'HacApproved_' . date('YmdHis');
    }
}