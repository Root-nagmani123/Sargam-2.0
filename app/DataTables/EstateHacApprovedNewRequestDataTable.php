<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateHacApprovedNewRequestDataTable extends DataTable
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
            ->editColumn('current_alot', fn ($row) => e($row->current_alot ?? '—'))
            ->editColumn('remarks', fn ($row) => \Illuminate\Support\Str::limit(e($row->remarks ?? ''), 60))
            ->addColumn('allot', function ($row) {
                $url = route('admin.estate.new-request.allot-details', ['id' => $row->pk]);
                return '<button type="button" class="btn btn-sm btn-success btn-allot-new-request" data-id="' . (int) $row->pk . '" data-req-id="' . e($row->req_id ?? '') . '" data-details-url="' . e($url) . '" title="Allot house (add to Possession Details)">
                    <i class="bi bi-house-add me-1"></i> Allot
                </button>';
            })
            ->rawColumns(['allot'])
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
        $hasPossessionPks = DB::table('estate_possession_details')
            ->whereNotNull('estate_home_request_details')
            ->pluck('estate_home_request_details')
            ->unique()
            ->values()
            ->all();

        return $model->newQuery()
            ->select([
                'estate_home_request_details.pk',
                'estate_home_request_details.req_id',
                'estate_home_request_details.req_date',
                'estate_home_request_details.emp_name',
                'estate_home_request_details.employee_id',
                'estate_home_request_details.emp_designation',
                'estate_home_request_details.pay_scale',
                'estate_home_request_details.current_alot',
                'estate_home_request_details.remarks',
            ])
            ->where('estate_home_request_details.hac_status', 1)
            ->where('estate_home_request_details.f_status', 1)
            ->where('estate_home_request_details.change_status', 0)
            ->when(!empty($hasPossessionPks), function ($q) use ($hasPossessionPks) {
                $q->whereNotIn('estate_home_request_details.pk', $hasPossessionPks);
            })
            ->orderBy('estate_home_request_details.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('hacApprovedNewRequestTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax(route('admin.estate.new-request-datatable'))
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
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
                ],
                'dom' => '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('req_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('REQUEST DATE')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('NAME')->orderable(true)->searchable(true),
            Column::make('employee_id')->title('EMP.ID')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('DESIGNATION')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('PAY SCALE')->orderable(true)->searchable(true),
            Column::make('current_alot')->title('CURRENT ALLOTMENT')->orderable(true)->searchable(true),
            Column::make('remarks')->title('REMARKS')->orderable(false)->searchable(true),
            Column::computed('allot')->title('ALLOT')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'HacApprovedNewRequest_' . date('YmdHis');
    }
}
