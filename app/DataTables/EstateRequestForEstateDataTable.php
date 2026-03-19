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
                // Prefer explicit Rejected flag from main status when status = 2.
                $s = (int) ($row->status ?? 0);
                if ($s === 2) {
                    return '<span class="badge bg-danger">Rejected</span>';
                }

                // Prefer actual possession flags from estate_possession_details to decide "Returned".
                $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;
                if (! $hasActive && $hasReturned) {
                    return '<span class="badge bg-info">Returned</span>';
                }

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
            ->editColumn('change_req_status', function ($row) {
                $s = $row->change_req_status !== null && $row->change_req_status !== '' ? (int) $row->change_req_status : null;
                if ($s === null) {
                    return '—';
                }
                if ($s === 0) {
                    return '<span class="badge bg-warning" title="Change request pending">Pending</span>';
                }
                if ($s === 1) {
                    return '<span class="badge bg-success" title="Change request approved"><i class="material-icons material-symbols-rounded" style="font-size:1rem;vertical-align:middle">check_circle</i> Approved</span>';
                }
                if ($s === 2) {
                    return '<span class="badge bg-danger" title="Change request disapproved"><i class="material-icons material-symbols-rounded" style="font-size:1rem;vertical-align:middle">cancel</i> Disapproved</span>';
                }
                return '—';
            })
            ->editColumn('eligibility_type_pk', function ($row) {
                $pk = (int) ($row->eligibility_type_pk ?? 0);
                $map = [61 => 'I', 62 => 'II', 63 => 'III', 64 => 'IV', 65 => 'V', 66 => 'VI', 69 => 'IX', 70 => 'X', 71 => 'XI', 73 => 'XIII'];
                return $map[$pk] ?? '—';
            })
            ->addColumn('action', function ($row) {
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
                $isEstateAuthority = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST');

                // Existing authority-only change request link (no change here).
                $canRaiseChangeRequest = $isEstateAuthority && $currentAlot !== '' && ! $hasChangeStatus;
                $raiseChangeUrl = $canRaiseChangeRequest
                    ? route('admin.estate.raise-change-request', ['id' => $row->pk])
                    : '';
                $raiseChangeLink = $raiseChangeUrl !== ''
                    ? '<a href="' . e($raiseChangeUrl) . '" class="text-info" title="Raise Change Request"><i class="material-icons material-symbols-rounded">swap_horiz</i></a>'
                    : '';
                // Lock row (no Edit/Delete) when request is effectively Allotted or Returned.
                $statusInt = (int) ($row->status ?? 0);
                $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;
                $isReturnedEffective = (! $hasActive && $hasReturned) || $statusInt === 3;
                $isLocked = $statusInt === 1 || $isReturnedEffective;

                $editLink = $isLocked ? '' : '<a href="javascript:void(0);" class="text-primary btn-edit-request-estate" title="Edit" ' . $dataAttrs . '><i class="material-icons material-symbols-rounded">edit</i></a>';
                $deleteLink = $isLocked ? '' : '<a href="javascript:void(0);" class="text-primary btn-delete-request-estate" title="Delete" data-url="' . e($deleteUrl) . '"><i class="material-icons material-symbols-rounded">delete</i></a>';

                // Common flags for possession / return / change actions
                $addPossessionButton = '';
                $returnHouseButton = '';
                $selfChangeRequestButton = '';

                // Add Possession button:
                // - Only when HAC-approved
                // - No pending change request
                // - No active possession yet (user should not be able to create multiple possessions for same request)
                // - Hidden for Admin / Super Admin / Estate roles as per requirement
                //   (user/self-service flows remain unchanged).
                // Returned requests never show Add button again.
                $canAllot = (int) ($row->hac_status ?? 0) === 1
                    && (int) ($row->change_status ?? 0) === 0
                    && ! $hasActive
                    && ! $isReturnedEffective;
                $canShowPossessionButtonForRole = ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
                if ($canAllot && $canShowPossessionButtonForRole) {
                    // Always open generic Add Possession page; no preselected requester in URL.
                    $url = route('admin.estate.possession-details.create');
                    $addPossessionButton = '<a href="' . e($url) . '" class="text-success" title="Add Possession">
                        <i class="material-icons material-symbols-rounded">add_home</i>
                    </a>';
                } elseif (! $isEstateAuthority && $hasActive && ! $hasReturned) {
                    // For self-service users, show a non-clickable "Possession done" indicator once possession exists.
                    $addPossessionButton = '<span class="text-success" title="Possession already created">
                        <i class="material-icons material-symbols-rounded">check_circle</i>
                    </span>';
                }

                // Self-service user options (Return House + Raise Change Request) after possession exists.
                if (! $isEstateAuthority) {
                    $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                    $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;

                    // Return House: only when there is an active possession and not yet returned.
                    if ($hasActive && ! $hasReturned) {
                        // For user role, go directly to Return House page with request_id.
                        $returnUrl = route('admin.estate.return-house', ['request_id' => $row->pk]);
                        $returnHouseButton = '<a href="' . e($returnUrl) . '" class="text-warning" title="Return House">
                            <i class="material-icons material-symbols-rounded">logout</i>
                        </a>';
                    }

                    // User Raise Change Request: active possession, no existing change request.
                    if ($hasActive && ! $hasChangeStatus) {
                        $selfCrUrl = route('admin.estate.raise-change-request', ['id' => $row->pk]);
                        $selfChangeRequestButton = '<a href="' . e($selfCrUrl) . '" class="text-info" title="Raise Change Request">
                            <i class="material-icons material-symbols-rounded">swap_horiz</i>
                        </a>';
                    }
                }

                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="' . e($detailsUrl) . '" class="text-primary" title="Request &amp; Change Details"><i class="material-icons material-symbols-rounded">visibility</i></a>
                    ' . $raiseChangeLink . '
                    ' . $editLink . '
                    ' . $deleteLink . '
                    ' . $addPossessionButton . '
                    ' . $returnHouseButton . '
                    ' . $selfChangeRequestButton . '
                </div>';
            })
            ->rawColumns(['status', 'change_req_status', 'action'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue === '') {
                    return;
                }
                $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                $query->where(function ($q) use ($searchValue, $searchLike) {
                    $utf8Expr = static fn (string $column): string =>
                        "CONVERT(COALESCE($column, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci";

                    $q->whereRaw($utf8Expr('estate_home_request_details.req_id') . ' LIKE ?', [$searchLike])
                        ->orWhereRaw($utf8Expr('estate_home_request_details.emp_name') . ' LIKE ?', [$searchLike])
                        ->orWhereRaw($utf8Expr('estate_home_request_details.employee_id') . ' LIKE ?', [$searchLike])
                        ->orWhereRaw($utf8Expr('estate_home_request_details.current_alot') . ' LIKE ?', [$searchLike])
                        ->orWhereRaw(
                            'CONCAT(TRIM(' . $utf8Expr('estate_home_request_details.emp_name') . '), " / ", TRIM(' . $utf8Expr('estate_home_request_details.employee_id') . ')) LIKE ?',
                            [$searchLike]
                        );
                    $statusMap = ['pending' => 0, 'allotted' => 1];
                    $searchLower = strtolower($searchValue);
                    if (isset($statusMap[$searchLower])) {
                        $q->orWhere('estate_home_request_details.status', $statusMap[$searchLower]);
                    } elseif (is_numeric($searchValue) && in_array((int) $searchValue, [0, 1], true)) {
                        $q->orWhere('estate_home_request_details.status', (int) $searchValue);
                    }
                });
            }, false)
            ->filterColumn('req_id', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereRaw("CONVERT(COALESCE(estate_home_request_details.req_id, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like]);
            })
            ->filterColumn('current_alot', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereRaw("CONVERT(COALESCE(estate_home_request_details.current_alot, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like]);
            })
            ->filterColumn('name_id', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->where(function ($q) use ($like) {
                    $q->whereRaw("CONVERT(COALESCE(estate_home_request_details.emp_name, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like])
                        ->orWhereRaw("CONVERT(COALESCE(estate_home_request_details.employee_id, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like])
                        ->orWhereRaw(
                            "CONCAT(TRIM(CONVERT(COALESCE(estate_home_request_details.emp_name, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci), \" / \", TRIM(CONVERT(COALESCE(estate_home_request_details.employee_id, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci)) LIKE ?",
                            [$like]
                        );
                });
            })
            ->orderColumn('pk', fn ($query, $order) => $query->reorder()->orderBy('estate_home_request_details.pk', $order))
            ->orderColumn('req_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_home_request_details.req_id, "")) ' . $order))
            ->orderColumn('req_date', fn ($query, $order) => $query->reorder()
                ->orderBy('estate_home_request_details.req_date', $order)
                ->orderBy('estate_home_request_details.pk', $order))
            ->orderColumn('name_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_home_request_details.emp_name, "")) ' . $order)->orderByRaw('LOWER(COALESCE(estate_home_request_details.employee_id, "")) ' . $order))
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
                'estate_home_request_details.hac_status',
                // Derived flags from estate_possession_details:
                // has_active_possession: at least one *completed* possession row with house and not returned.
                // Pending possessions (created at allotment time) use sentinel dates (1900-01-01) and should NOT
                // be treated as "possession already created" for self-service users.
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM estate_possession_details epd
                    WHERE epd.estate_home_request_details = estate_home_request_details.pk
                      AND epd.estate_house_master_pk IS NOT NULL
                      AND epd.possession_date > '1900-01-01'
                      AND (epd.return_home_status IS NULL OR epd.return_home_status = 0)
                ) THEN 1 ELSE 0 END AS has_active_possession"),
                // has_any_returned: at least one possession row with house and return_home_status = 1.
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM estate_possession_details epd2
                    WHERE epd2.estate_home_request_details = estate_home_request_details.pk
                      AND epd2.estate_house_master_pk IS NOT NULL
                      AND epd2.possession_date > '1900-01-01'
                      AND epd2.return_home_status = 1
                ) THEN 1 ELSE 0 END AS has_any_returned"),
                // Latest change request status (0=Pending, 1=Approved, 2=Disapproved) for user visibility.
                DB::raw("(SELECT ec.change_ap_dis_status FROM estate_change_home_req_details ec WHERE ec.estate_home_req_details_pk = estate_home_request_details.pk ORDER BY ec.pk DESC LIMIT 1) AS change_req_status"),
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

        // Exclude rejected requests from listing (status = 2).
        $query->where('estate_home_request_details.status', '!=', 2);

        // Status filter: All (empty), Pending (0), Allotted (1), Returned (3)
        $statusFilter = request('status_filter');
        if ($statusFilter !== null && $statusFilter !== '') {
            $statusVal = (int) $statusFilter;
            if ($statusVal === 0) {
                // Pending: stored as status = 0 and not currently allotted or returned.
                $query->where('estate_home_request_details.status', 0)
                    ->havingRaw('has_active_possession = 0 AND has_any_returned = 0');
            } elseif ($statusVal === 1) {
                // Allotted: there is an active possession for this request.
                $query->havingRaw('has_active_possession = 1');
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
                // Default sort by Request Date (column index 2) descending.
                'order' => [[2, 'desc']],
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
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('req_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('REQUEST DATE')->orderable(true)->searchable(false),
            Column::computed('name_id')->title('NAME / ID')->orderable(true)->searchable(true),
            Column::make('status')->title('STATUS OF REQUEST')->orderable(false)->searchable(false),
            Column::make('change_req_status')->title('CHANGE REQ. STATUS')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('ACTION')->addClass('text-center')->orderable(false)->searchable(false)->width('100px'),
        ];
    }

    protected function filename(): string
    {
        return 'RequestForEstate_' . date('YmdHis');
    }
}
