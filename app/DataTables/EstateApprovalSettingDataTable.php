<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersEstateRowActions;
use App\Models\EstateHomeReqApprovalMgmt;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateApprovalSettingDataTable extends DataTable
{
    use RendersEstateRowActions;

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('requested_by', fn ($row) => e($this->employeeName($row->requestedBy)))
            ->editColumn('approved_by', fn ($row) => e($this->employeeName($row->approvedBy)))
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
                $deleteUrl = route('admin.estate.estate-approval-setting.destroy', ['id' => $row->pk]);
                // Icon-over-label actions shared with every Estate grid; Delete opens
                // the confirm dialog on estate_approval_setting.blade.php.
                return '<div class="rfe-actions" role="group">'
                    . self::actionLink('edit', 'Edit', 'edit', ['href' => $editUrl])
                    . self::actionLink('delete', 'Delete', 'delete', [
                        'class' => 'js-em-delete',
                        'attrs' => 'data-url="' . e($deleteUrl) . '"',
                    ])
                    . '</div>';
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
            // programme-dt chrome — no `dom` / `language`: datatable-global-ui.js owns
            // both and fills the search / footer slots on the page.
            ->addTableClass('table align-middle mb-0 w-100 programme-dt-table em-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[0, 'asc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('em-col-sno')->orderable(true)->searchable(false),
            Column::make('requested_by')->title('Requested By')->orderable(false)->searchable(true),
            Column::make('approved_by')->title('Approved By')->orderable(false)->searchable(true),
            Column::computed('action')->title('Action')->addClass('em-col-action no-sort')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EstateApprovalSetting_' . date('YmdHis');
    }

    private function employeeName($emp): string
    {
        return $emp ? (trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: '—') : '—';
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