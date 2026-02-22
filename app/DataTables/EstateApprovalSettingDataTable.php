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
            ->filterColumn('requested_by', function ($query, $keyword) {
                $this->applyNameSearch($query, $keyword, false);
            })
            ->filterColumn('approved_by', function ($query, $keyword) {
                $this->applyNameSearch($query, $keyword, true);
            })
            ->filter(function ($query) {
                $search = request()->get('search');
                $searchValue = is_array($search) && isset($search['value'])
                    ? trim((string) $search['value'])
                    : trim((string) request()->input('search.value', ''));
                if ($searchValue === '') {
                    return;
                }
                $this->applyNameSearch($query, $searchValue, false);
            }, true)
            ->orderColumn('DT_RowIndex', 'estate_home_req_approval_mgmt.pk $1')
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.estate.add-approved-request-house', ['approver' => $row->employees_pk]);
                return '<div class="d-inline-flex align-items-center gap-1" role="group">' .
                    '<a href="' . e($editUrl) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>' .
                    '</div>';
            })
            ->rawColumns(['action'])
            ->setRowId('pk');
    }

    public function query(EstateHomeReqApprovalMgmt $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['requestedBy', 'approvedBy'])
            ->select('estate_home_req_approval_mgmt.*');
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
                'dom' => '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8 estate-approval-search-col"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(true)->searchable(false)->width('60px'),
            Column::make('requested_by')->title('Requested By')->orderable(false)->searchable(true),
            Column::make('approved_by')->title('Approved By')->orderable(false)->searchable(true),
            Column::computed('action')->title('Action')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateApprovalSetting_' . date('YmdHis');
    }

    /**
     * Apply search on requested_by (employee_master_pk) and approved_by (employees_pk) via employee_master names.
     * Both columns point to employee_master.pk; we search first_name, last_name and full name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $keyword
     * @param  bool  $useOrWhere  true for second column so condition is OR'd (requested_by OR approved_by)
     */
    protected function applyNameSearch($query, string $keyword, bool $useOrWhere): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }
        $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
        $closure = function ($q) use ($searchLike) {
            $q->whereHas('requestedBy', function ($sub) use ($searchLike) {
                $sub->where('first_name', 'like', $searchLike)
                    ->orWhere('last_name', 'like', $searchLike)
                    ->orWhereRaw('CONCAT(COALESCE(first_name,""), " ", COALESCE(last_name,"")) LIKE ?', [$searchLike]);
            })->orWhereHas('approvedBy', function ($sub) use ($searchLike) {
                $sub->where('first_name', 'like', $searchLike)
                    ->orWhere('last_name', 'like', $searchLike)
                    ->orWhereRaw('CONCAT(COALESCE(first_name,""), " ", COALESCE(last_name,"")) LIKE ?', [$searchLike]);
            });
        };
        if ($useOrWhere) {
            $query->orWhere($closure);
        } else {
            $query->where($closure);
        }
    }
}
