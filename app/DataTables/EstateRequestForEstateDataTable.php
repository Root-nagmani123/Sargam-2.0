<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateRequestForEstateDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('req_id', fn($row) => $row->req_id ?? '—')
            ->editColumn('req_date', function ($row) {
                $d = $row->req_date;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('name_id', function ($row) {
                $name = trim((string) ($row->emp_name ?? ''));
                $id = trim((string) ($row->employee_id ?? ''));

                // Defensive fix: some legacy/self-service rows may have empty emp_name even though employee_pk is set.
                // In that case, try to resolve the name from employee_master on the fly so listing shows proper name.
                if ($name === '' && (int) ($row->employee_pk ?? 0) > 0) {
                    static $empNameCache = [];
                    $empPk = (int) $row->employee_pk;
                    if (! array_key_exists($empPk, $empNameCache)) {
                        $empPkCol = Schema::hasColumn('employee_master', 'pk_old') ? 'pk_old' : 'pk';
                        $empQuery = DB::table('employee_master');
                        $empQuery->where('pk', $empPk);
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $empQuery->orWhere('pk_old', $empPk);
                        }
                        $empRow = $empQuery
                            ->select('first_name', 'middle_name', 'last_name')
                            ->first();
                        $resolved = '';
                        if ($empRow) {
                            $resolved = trim(
                                (string) ($empRow->first_name ?? '') . ' ' .
                                (string) ($empRow->middle_name ?? '') . ' ' .
                                (string) ($empRow->last_name ?? '')
                            );
                        }
                        $empNameCache[$empPk] = $resolved;
                    }
                    if ($empNameCache[$empPk] !== '') {
                        $name = $empNameCache[$empPk];
                    }
                }

                return $name ? ($id ? $name . ' / ' . $id : $name) : ($id ?: '—');
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->doj_academic;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('status', function ($row) {
                // Prefer actual possession flags from estate_possession_details to decide "Returned".
                $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;
                if (! $hasActive && $hasReturned) {
                    return '<span class="badge bg-info">Returned</span>';
                }

                $s = (int) ($row->status ?? 0);
                // If system has a current allotment recorded but status is still Pending (0),
                // treat it as Allotted in UI. This fixes legacy/migrated rows like Nishant Joshi,
                // where possession exists (and house no. is set) but status was never updated.
                $hasCurrentAllotment = trim((string) ($row->current_alot ?? '')) !== '';
                if ($s === 0 && $hasCurrentAllotment) {
                    $s = 1;
                }
                // 0 = Pending, 1 = Allotted, 2 = Rejected, 3 = Returned (explicit flag, if used)
                $labels = [0 => 'Pending', 1 => 'Allotted', 2 => 'Rejected', 3 => 'Returned'];
                $classes = [0 => 'warning', 1 => 'success', 2 => 'danger', 3 => 'info'];
                $label = $labels[$s] ?? 'Unknown';
                $class = $classes[$s] ?? 'secondary';
                return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
            })
            ->editColumn('current_alot', fn($row) => $row->current_alot ?? '—')
            ->editColumn('eligibility_type_pk', function ($row) {
                $pk = (int) ($row->eligibility_type_pk ?? 0);
                $map = [61 => 'I', 62 => 'II', 63 => 'III', 64 => 'IV', 65 => 'V', 66 => 'VI', 69 => 'IX', 70 => 'X', 71 => 'XI', 73 => 'XIII'];
                return $map[$pk] ?? '—';
            })
            ->addColumn('change', function ($row) {
                $deleteUrl = route('admin.estate.request-for-estate.destroy', ['id' => $row->pk]);
                $detailsUrl = route('admin.estate.request-details', ['id' => $row->pk]);
                $reqDate = $row->req_date ? \Carbon\Carbon::parse($row->req_date)->format('Y-m-d') : '';
                $dojPayScale = $row->doj_pay_scale ? \Carbon\Carbon::parse($row->doj_pay_scale)->format('Y-m-d') : '';
                $dojAcademic = $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('Y-m-d') : '';
                $dojService = $row->doj_service ? \Carbon\Carbon::parse($row->doj_service)->format('Y-m-d') : '';
                $eligPk = (int) ($row->eligibility_type_pk ?? 0);
                $eligMap = [61 => 'I', 62 => 'II', 63 => 'III', 64 => 'IV', 65 => 'V', 66 => 'VI', 69 => 'IX', 70 => 'X', 71 => 'XI', 73 => 'XIII'];
                $attrs = [
                    'data-id' => (int) $row->pk,
                    'data-employee_pk' => (int) ($row->employee_pk ?? 0),
                    'data-req_id' => e($row->req_id ?? ''),
                    'data-req_date' => $reqDate,
                    'data-emp_name' => e($row->emp_name ?? ''),
                    'data-employee_id' => e($row->employee_id ?? ''),
                    'data-emp_designation' => e($row->emp_designation ?? ''),
                    'data-pay_scale' => e($row->pay_scale ?? ''),
                    'data-doj_pay_scale' => $dojPayScale,
                    'data-doj_academic' => $dojAcademic,
                    'data-doj_service' => $dojService,
                    'data-eligibility_type_pk' => $eligPk,
                    'data-eligibility_type_label' => e($eligMap[$eligPk] ?? 'Type ' . $eligPk),
                    'data-status' => (int) ($row->status ?? 0),
                    'data-current_alot' => e($row->current_alot ?? ''),
                    'data-remarks' => e($row->remarks ?? ''),
                ];
                $dataAttrs = implode(' ', array_map(fn ($k, $v) => $k . '="' . $v . '"', array_keys($attrs), $attrs));
                $currentAlot = trim((string) ($row->current_alot ?? ''));
                $hasChangeStatus = (int) ($row->change_status ?? 0) === 1;
                $canRaiseChangeRequest = (hasRole('Estate') || hasRole('Admin')) && $currentAlot !== '' && ! $hasChangeStatus;
                $raiseChangeUrl = $canRaiseChangeRequest
                    ? route('admin.estate.raise-change-request', ['id' => $row->pk])
                    : '';
                $raiseChangeLink = $raiseChangeUrl !== ''
                    ? '<a href="' . e($raiseChangeUrl) . '" class="text-info" title="Raise Change Request"><i class="material-icons material-symbols-rounded">swap_horiz</i></a>'
                    : '';
                $isAllotted = (int) ($row->status ?? 0) === 1;
                $editLink = $isAllotted ? '' : '<a href="javascript:void(0);" class="text-primary btn-edit-request-estate" title="Edit" ' . $dataAttrs . '><i class="material-icons material-symbols-rounded">edit</i></a>';
                $deleteLink = $isAllotted ? '' : '<a href="javascript:void(0);" class="text-primary btn-delete-request-estate" title="Delete" data-url="' . e($deleteUrl) . '"><i class="material-icons material-symbols-rounded">delete</i></a>';
                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="' . e($detailsUrl) . '" class="text-primary" title="Request &amp; Change Details"><i class="material-icons material-symbols-rounded">visibility</i></a>
                    ' . $raiseChangeLink . '
                    ' . $editLink . '
                    ' . $deleteLink . '
                </div>';
            })
            ->rawColumns(['status', 'change'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue === '') {
                    return;
                }
                $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                $query->where(function ($q) use ($searchValue, $searchLike) {
                    $q->where('estate_home_request_details.req_id', 'like', $searchLike)
                        ->orWhere('estate_home_request_details.emp_name', 'like', $searchLike)
                        ->orWhere('estate_home_request_details.employee_id', 'like', $searchLike)
                        ->orWhere('estate_home_request_details.current_alot', 'like', $searchLike)
                        ->orWhereRaw('CONCAT(TRIM(COALESCE(estate_home_request_details.emp_name,"")), " / ", TRIM(COALESCE(estate_home_request_details.employee_id,""))) LIKE ?', [$searchLike]);
                    $statusMap = ['pending' => 0, 'allotted' => 1, 'rejected' => 2];
                    $searchLower = strtolower($searchValue);
                    if (isset($statusMap[$searchLower])) {
                        $q->orWhere('estate_home_request_details.status', $statusMap[$searchLower]);
                    } elseif (is_numeric($searchValue) && in_array((int) $searchValue, [0, 1, 2], true)) {
                        $q->orWhere('estate_home_request_details.status', (int) $searchValue);
                    }
                });
            }, true)
            ->filterColumn('req_id', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->where('estate_home_request_details.req_id', 'like', $like);
            })
            ->filterColumn('current_alot', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->where('estate_home_request_details.current_alot', 'like', $like);
            })
            ->filterColumn('name_id', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('estate_home_request_details.emp_name', 'like', $like)
                        ->orWhere('estate_home_request_details.employee_id', 'like', $like)
                        ->orWhereRaw('CONCAT(TRIM(COALESCE(estate_home_request_details.emp_name,"")), " / ", TRIM(COALESCE(estate_home_request_details.employee_id,""))) LIKE ?', [$like]);
                });
            })
            ->orderColumn('pk', fn ($query, $order) => $query->orderBy('estate_home_request_details.pk', $order))
            ->orderColumn('req_id', fn ($query, $order) => $query->orderBy('estate_home_request_details.req_id', $order))
            ->orderColumn('req_date', fn ($query, $order) => $query->orderBy('estate_home_request_details.req_date', $order))
            ->setRowId('pk');
    }

    public function query(EstateHomeRequestDetails $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select([
                'estate_home_request_details.pk',
                'estate_home_request_details.employee_pk',
                'estate_home_request_details.req_id',
                'estate_home_request_details.req_date',
                'estate_home_request_details.emp_name',
                'estate_home_request_details.employee_id',
                'estate_home_request_details.emp_designation',
                'estate_home_request_details.pay_scale',
                'estate_home_request_details.doj_pay_scale',
                'estate_home_request_details.doj_academic',
                'estate_home_request_details.doj_service',
                'estate_home_request_details.status',
                'estate_home_request_details.current_alot',
                'estate_home_request_details.eligibility_type_pk',
                'estate_home_request_details.remarks',
                'estate_home_request_details.change_status',
                // Derived flags from estate_possession_details:
                // has_active_possession: at least one possession row with house and not returned.
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM estate_possession_details epd
                    WHERE epd.estate_home_request_details = estate_home_request_details.pk
                      AND epd.estate_house_master_pk IS NOT NULL
                      AND (epd.return_home_status IS NULL OR epd.return_home_status = 0)
                ) THEN 1 ELSE 0 END AS has_active_possession"),
                // has_any_returned: at least one possession row with house and return_home_status = 1.
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM estate_possession_details epd2
                    WHERE epd2.estate_home_request_details = estate_home_request_details.pk
                      AND epd2.estate_house_master_pk IS NOT NULL
                      AND epd2.return_home_status = 1
                ) THEN 1 ELSE 0 END AS has_any_returned"),
            ]);

        // Self-service: non-estate/admin/HAC-approval users should only see their own requests.
        // Estate / Admin / Training-* / IST / HAC Person see full list (they work on others' requests).
        $user = Auth::user();
        if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST') || hasRole('HAC Person'))) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (!empty($employeeIds)) {
                $query->whereIn('estate_home_request_details.employee_pk', $employeeIds);
            } else {
                // No mapped employee → show nothing
                $query->whereRaw('1 = 0');
            }
        }

        // Status filter: All (empty), Pending (0), Allotted (1), Rejected (2), Returned (3)
        $statusFilter = request('status_filter');
        if ($statusFilter !== null && $statusFilter !== '') {
            $statusVal = (int) $statusFilter;
            if (in_array($statusVal, [0, 1, 2], true)) {
                $query->where('estate_home_request_details.status', $statusVal);
            } elseif ($statusVal === 3) {
                // Returned: no active possession, but at least one returned possession row.
                $query->havingRaw('has_any_returned = 1 AND has_active_possession = 0');
            }
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('requestForEstateTable')
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
                'order' => [[8, 'desc']],
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
                'dom' => '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8 request-for-estate-search-col"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
                'columnDefs' => [['targets' => [8], 'visible' => false]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('req_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('REQUEST DATE')->orderable(true)->searchable(false),
            Column::computed('name_id')->title('NAME / ID')->orderable(false)->searchable(true),
            Column::make('doj_academic')->title('DATE OF JOINING IN ACADEMY')->orderable(false)->searchable(false),
            Column::make('status')->title('STATUS OF REQUEST')->orderable(false)->searchable(false),
            Column::make('current_alot')->title('ALLOTED HOUSE')->orderable(false)->searchable(true),
            Column::make('eligibility_type_pk')->title('ELIGIBILITY TYPE')->orderable(false)->searchable(false),
            Column::make('pk')->title('')->orderable(true)->searchable(false)->exportable(false)->printable(false),
            Column::computed('change')->title('CHANGE')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'RequestForEstate_' . date('YmdHis');
    }
}
