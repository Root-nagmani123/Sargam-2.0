<?php

namespace App\DataTables;

use App\Models\EstateHomeReqApprovalMgmt;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateApprovalSettingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('requested_by', function ($row) {
                $emp = $row->requestedBy;
                $name = $emp ? (trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: '—') : '—';
                return e($name);
            })
            ->editColumn('approved_by', function ($row) {
                $emp = $row->approvedBy;
                $name = $emp ? (trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: '—') : '—';
                return e($name);
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.estate.add-approved-request-house', ['approver' => $row->employees_pk]);
                return '<div class="d-inline-flex align-items-center gap-1" role="group">' .
                    '<a href="' . e($editUrl) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>' .
                    '</div>';
            })
            ->rawColumns(['action'])
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->whereHas('requestedBy', function ($sub) use ($searchValue) {
                            $sub->where('first_name', 'like', "%{$searchValue}%")
                                ->orWhere('last_name', 'like', "%{$searchValue}%");
                        })->orWhereHas('approvedBy', function ($sub) use ($searchValue) {
                            $sub->where('first_name', 'like', "%{$searchValue}%")
                                ->orWhere('last_name', 'like', "%{$searchValue}%");
                        });
                    });
                }
            }, true)
            ->setRowId('pk');
    }

    public function query(EstateHomeReqApprovalMgmt $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['requestedBy', 'approvedBy'])
            ->select('estate_home_req_approval_mgmt.*')
            ->orderBy('estate_home_req_approval_mgmt.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateApprovalSettingTable')
            ->addTableClass('table table-striped table-hover align-middle mb-0')
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
                'dom' => '<"row flex-wrap align-items-center gap-2"<"col-12 col-md-6"l><"col-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::make('requested_by')->title('Requested By')->orderable(false)->searchable(true),
            Column::make('approved_by')->title('Approved By')->orderable(false)->searchable(true),
            Column::computed('action')->title('Action')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateApprovalSetting_' . date('YmdHis');
    }
}
