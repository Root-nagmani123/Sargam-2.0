<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateRequestForEstateDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'estate_rfet_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EstateRequestForEstateDataTable');
    }

    /**
     * Server-side JSON for the listing (.env: ESTATE_UPDATE_METER_READING_CACHE_*).
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'estate_rfet:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'ESTATE_UPDATE_METER_READING_CACHE_ENABLED',
                'seconds' => 'ESTATE_UPDATE_METER_READING_CACHE_SECONDS',
            ],
            'EstateRequestForEstateDataTable',
            fn () => parent::ajax(),
            $this->requestForEstateListingCacheExtra()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestForEstateListingCacheExtra(): array
    {
        $r = $this->request();
        $user = Auth::user();
        $applySelfEmployeeFilter = false;
        if ($user) {
            // Only Estate Admin (+ HAC listing behaviour) sees all rows; Admin / Super Admin behave like Staff (own rows).
            if (! (isEstateAuthority() || hasRole('HAC Person'))) {
                $applySelfEmployeeFilter = true;
            } elseif ($r->input('scope') === 'self' && isEstateAuthority()) {
                $applySelfEmployeeFilter = true;
            }
        }

        $empScope = ['t' => 'all'];
        if ($applySelfEmployeeFilter && $user) {
            $ids = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            $ids = array_values(array_unique(array_map('intval', $ids)));
            sort($ids, SORT_NUMERIC);
            $empScope = ['t' => 'emp', 'ids' => $ids];
        } elseif ($applySelfEmployeeFilter) {
            $empScope = ['t' => 'emp', 'ids' => []];
        }

        return [
            'status_filter' => $r->input('status_filter'),
            'scope' => (string) $r->input('scope', ''),
            'emp' => $empScope,
            'uid' => Auth::id(),
        ];
    }

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

                if ($name === '' && $id === '') {
                    return '—';
                }

                // Name in ink, employee id as a muted suffix — "Darren Kelly - NNP00037".
                $html = $name !== '' ? '<span class="rfe-name">' . e($name) . '</span>' : '';
                if ($id !== '') {
                    $html .= ($html !== '' ? ' ' : '') . '<span class="rfe-emp-id">' . ($html !== '' ? '- ' : '') . e($id) . '</span>';
                }

                return $html;
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->doj_academic;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('status', function ($row) {
                // Label resolution (incl. the legacy "allotted but still status=0" rows)
                // lives in statusLabel() so the exports render the same word.
                $label = self::statusLabel($row);
                $tone = [
                    'Pending' => 'pending',
                    'Allotted' => 'allotted',
                    'Rejected' => 'rejected',
                    'Returned' => 'returned',
                ][$label] ?? 'neutral';

                return self::statusBadge($label, $tone);
            })
            ->editColumn('current_alot', fn($row) => $row->current_alot ?? '—')
            ->editColumn('change_req_status', function ($row) {
                // Plain text (not a badge) — the Status column already carries the
                // page's only colour-coded pill, so this stays quiet next to it.
                $label = self::changeRequestLabel($row->change_req_status);
                if ($label === null) {
                    return '<span class="rfe-muted">-</span>';
                }

                $tone = ['Pending' => 'pending', 'Approved' => 'approved', 'Disapproved' => 'disapproved'][$label];

                return '<span class="rfe-change-status rfe-change-status--' . $tone . '">' . e($label) . '</span>';
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
                // Estate office (not ?scope=self): can raise change request for others. Admin / Super Admin follow self-service actions only.
                $isEstateAuthority = isEstateAuthority() && request('scope') !== 'self';

                // Existing authority-only change request link (no change here).
                $canRaiseChangeRequest = $isEstateAuthority && $currentAlot !== '' && ! $hasChangeStatus;
                $raiseChangeUrl = $canRaiseChangeRequest
                    ? route('admin.estate.raise-change-request', ['id' => $row->pk])
                    : '';
                // href stays the standalone page so ctrl-click / no-JS still works;
                // the listing's JS intercepts the click and opens the modal instead.
                $raiseChangeLink = $raiseChangeUrl !== ''
                    ? self::actionLink('swap_horiz', 'Change Request', 'change', [
                        'href' => $raiseChangeUrl,
                        'title' => 'Raise Change Request',
                        'class' => 'btn-raise-change-request',
                        'attrs' => 'data-id="' . (int) $row->pk . '"',
                    ])
                    : '';
                // Lock row (no Edit/Delete) when request is effectively Allotted or Returned.
                // Some legacy/self-service records may remain status=0 even after allotment,
                // so also lock when there is active possession OR current allotment is present.
                $statusInt = (int) ($row->status ?? 0);
                $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;
                $isReturnedEffective = (! $hasActive && $hasReturned) || $statusInt === 3;
                $hasCurrentAllotment = trim((string) ($row->current_alot ?? '')) !== '';
                $isAllottedEffective = $statusInt === 1 || $hasActive || $hasCurrentAllotment;
                $isLocked = $isAllottedEffective || $isReturnedEffective;

                // Locked rows keep the buttons in place but greyed out, so the action
                // column never changes width from row to row.
                $editLink = $isLocked
                    ? self::actionLink('edit', 'Edit', 'edit', ['disabled' => true, 'title' => 'Editing is locked once the request is allotted or returned'])
                    : self::actionLink('edit', 'Edit', 'edit', ['class' => 'btn-edit-request-estate', 'title' => 'Edit', 'attrs' => $dataAttrs]);

                $deleteLink = $isLocked
                    ? self::actionLink('delete', 'Delete', 'delete', ['disabled' => true, 'title' => 'Deleting is locked once the request is allotted or returned'])
                    : self::actionLink('delete', 'Delete', 'delete', ['class' => 'btn-delete-request-estate', 'title' => 'Delete', 'attrs' => 'data-url="' . e($deleteUrl) . '"']);

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
                $canShowPossessionButtonForRole = ! isEstateAuthority() || request('scope') === 'self';
                if ($canAllot && $canShowPossessionButtonForRole) {
                    // Always open generic Add Possession page; no preselected requester in URL.
                    $url = route('admin.estate.possession-details.create');
                    $addPossessionButton = self::actionLink('add_home', 'Possession', 'possession', [
                        'href' => $url,
                        'title' => 'Add Possession',
                    ]);
                } elseif (! $isEstateAuthority && $hasActive && ! $hasReturned) {
                    // For self-service users, show a non-clickable "Possession done" indicator once possession exists.
                    $addPossessionButton = self::actionLink('check_circle', 'Possession', 'possession', [
                        'static' => true,
                        'title' => 'Possession already created',
                    ]);
                }

                // Self-service user options (Return House + Raise Change Request) after possession exists.
                if (! $isEstateAuthority) {
                    $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
                    $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;

                    // Return House: only when there is an active possession and not yet returned.
                    if ($hasActive && ! $hasReturned) {
                        // For user role, go directly to Return House page with request_id.
                        $returnUrl = route('admin.estate.return-house', ['request_id' => $row->pk]);
                        $returnHouseButton = self::actionLink('logout', 'Return', 'return', [
                            'href' => $returnUrl,
                            'title' => 'Return House',
                        ]);
                    }

                    // User Raise Change Request:
                    // show when request is effectively allotted (active possession OR current allotment),
                    // no existing change request, and request is not already returned.
                    $canSelfRaiseChangeRequest = ($hasActive || $hasCurrentAllotment) && ! $hasChangeStatus && ! $isReturnedEffective;
                    if ($canSelfRaiseChangeRequest) {
                        $selfCrUrl = route('admin.estate.raise-change-request', ['id' => $row->pk]);
                        $selfChangeRequestButton = self::actionLink('swap_horiz', 'Change Request', 'change', [
                            'href' => $selfCrUrl,
                            'title' => 'Raise Change Request',
                            'class' => 'btn-raise-change-request',
                            'attrs' => 'data-id="' . (int) $row->pk . '"',
                        ]);
                    }
                }

                $changeRequestButton = $raiseChangeLink !== '' ? $raiseChangeLink : $selfChangeRequestButton;
                if ($changeRequestButton === '') {
                    $changeRequestButton = self::actionLink('swap_horiz', 'Change Request', 'change', [
                        'disabled' => true,
                        'title' => 'A change request can only be raised on an allotted request that has none pending',
                    ]);
                }

                $viewLink = self::actionLink('visibility', 'View', 'view', [
                    'href' => $detailsUrl,
                    'title' => 'Request & Change Details',
                ]);

                return '<div class="rfe-actions" role="group" aria-label="Row actions">'
                    . $viewLink
                    . $editLink
                    . $changeRequestButton
                    . $deleteLink
                    . $addPossessionButton
                    . $returnHouseButton
                    . '</div>';
            })
            ->rawColumns(['name_id', 'status', 'change_req_status', 'action'])
            ->filter(function ($query) {
                static::applySearch($query, (string) request()->input('search.value', ''));
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
        return static::listingQuery($model);
    }

    /**
     * The listing query — row scoping, the rejected-row exclusion and the status
     * filter, all in one place.
     *
     * The Download / Print exports call this too, so what a user downloads is
     * always exactly the rows they are allowed to see on screen.
     */
    public static function listingQuery(?EstateHomeRequestDetails $model = null): QueryBuilder
    {
        $model = $model ?: new EstateHomeRequestDetails();

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

        // Self-service: non-estate/HAC users see only their rows. Estate sees all; with ?scope=self Estate sees only their own.
        $user = Auth::user();
        $applySelfEmployeeFilter = false;
        if ($user) {
            if (! (isEstateAuthority() || hasRole('HAC Person'))) {
                $applySelfEmployeeFilter = true;
            } elseif (request('scope') === 'self' && isEstateAuthority()) {
                $applySelfEmployeeFilter = true;
            }
        }

        if ($applySelfEmployeeFilter) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (!empty($employeeIds)) {
                $query->whereIn('estate_home_request_details.employee_pk', $employeeIds);
            } else {
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

        return $query->orderByDesc('estate_home_request_details.req_date')
            ->orderByDesc('estate_home_request_details.pk');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('requestForEstateTable')
            // programme-dt chrome (docs/new-design-index-page.md) — no `dom` and no
            // `language` here on purpose: datatable-global-ui.js owns both, and a
            // page-level override would win and break the "Showing N of M items" footer.
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'scope' => 'new URLSearchParams(window.location.search).get("scope") || ""',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native (server-side) ordering so a header click
                // re-sorts the WHOLE dataset instead of just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                // Default sort by Request Date (column index 2) descending.
                'order' => [[2, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->width('64px'),
            Column::make('req_id')->title('Request ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('Request Date')->orderable(true)->searchable(false),
            Column::computed('name_id')->title('Name & ID')->addClass('rfe-col-name')->orderable(true)->searchable(true),
            Column::make('status')->title('Status')->orderable(false)->searchable(false),
            Column::make('change_req_status')->title('Change Request Status')->orderable(false)->searchable(false),
            // Wide enough for the four standard actions (View / Edit / Change
            // Request / Delete) to sit on one row — see .rfe-col-action.
            Column::computed('action')->title('Action')->addClass('rfe-col-action')->orderable(false)->searchable(false)->width('190px'),
        ];
    }

    protected function filename(): string
    {
        return 'RequestForEstate_' . date('YmdHis');
    }

    /**
     * The listing's free-text search, shared by the DataTable and the exports so a
     * download of a searched list matches what the table showed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function applySearch($query, string $searchValue): void
    {
        $searchValue = trim($searchValue);
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
    }

    /**
     * Plain-text status label for one row — the same precedence the on-screen
     * badge uses (rejected → returned → legacy-allotted → stored status), so the
     * exports and the table can't disagree.
     */
    public static function statusLabel($row): string
    {
        $s = (int) ($row->status ?? 0);
        if ($s === 2) {
            return 'Rejected';
        }

        $hasActive = (int) ($row->has_active_possession ?? 0) === 1;
        $hasReturned = (int) ($row->has_any_returned ?? 0) === 1;
        if (! $hasActive && $hasReturned) {
            return 'Returned';
        }

        if ($s === 0 && trim((string) ($row->current_alot ?? '')) !== '') {
            $s = 1;
        }

        return [0 => 'Pending', 1 => 'Allotted', 2 => 'Rejected', 3 => 'Returned'][$s] ?? 'Unknown';
    }

    /**
     * Soft status pill (matching .programme-status-badge sizing, estate tones).
     */
    private static function statusBadge(string $label, string $tone): string
    {
        return '<span class="badge rounded-1 programme-status-badge rfe-status rfe-status--' . $tone . '">'
            . e($label) . '</span>';
    }

    /**
     * Shared label for the latest change-request status, so the listing, the print
     * view and the Excel export can never disagree. null = no change request.
     */
    public static function changeRequestLabel($rawStatus): ?string
    {
        if ($rawStatus === null || $rawStatus === '') {
            return null;
        }

        return match ((int) $rawStatus) {
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Disapproved',
            default => null,
        };
    }

    /**
     * One row-action: a stacked material icon + caption.
     *
     * Unavailable actions render as a greyed, non-interactive span rather than
     * disappearing, so the Action column keeps the same shape on every row.
     *
     * @param array{href?:string,class?:string,title?:string,attrs?:string,disabled?:bool,static?:bool} $options
     */
    private static function actionLink(string $icon, string $label, string $tone, array $options = []): string
    {
        $title = e($options['title'] ?? $label);
        $isDisabled = ! empty($options['disabled']);
        $isStatic = $isDisabled || ! empty($options['static']);

        $classes = 'rfe-action rfe-action--' . $tone . ($isDisabled ? ' rfe-action--disabled' : '');
        if (! empty($options['class'])) {
            $classes .= ' ' . $options['class'];
        }

        $inner = '<i class="material-icons material-symbols-rounded" aria-hidden="true">' . $icon . '</i>'
            . '<span class="rfe-action-label">' . e($label) . '</span>';

        if ($isStatic) {
            return '<span class="' . $classes . '" title="' . $title . '" aria-disabled="true">' . $inner . '</span>';
        }

        $href = $options['href'] ?? 'javascript:void(0);';
        $extra = ! empty($options['attrs']) ? ' ' . $options['attrs'] : '';

        return '<a href="' . e($href) . '" class="' . $classes . '" title="' . $title . '"'
            . ' aria-label="' . $title . '"' . $extra . '>' . $inner . '</a>';
    }
}
