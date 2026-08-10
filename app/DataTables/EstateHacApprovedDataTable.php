<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersEstateRowActions;
use App\Models\EstateHacApprovedRow;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Single table for HAC Approved: Change requests + New requests.
 */
class EstateHacApprovedDataTable extends DataTable
{
    use RendersEstateRowActions;

    public const LISTING_CACHE_EPOCH_KEY = 'estate_hacap_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EstateHacApprovedDataTable');
    }

    /**
     * Server-side JSON (.env: ESTATE_UPDATE_METER_READING_CACHE_*).
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'estate_hacap:v2:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'ESTATE_UPDATE_METER_READING_CACHE_ENABLED',
                'seconds' => 'ESTATE_UPDATE_METER_READING_CACHE_SECONDS',
            ],
            'EstateHacApprovedDataTable',
            fn () => parent::ajax(),
            $this->hacApprovedListingCacheExtra()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function hacApprovedListingCacheExtra(): array
    {
        $r = $this->request();
        $user = Auth::user();
        $canSeeHacApproved = $user && (hasRole('HAC Person') || isEstateHacAuthority());

        $authorityPersonalScope = $r->input('scope') === 'self'
            && isEstateHacAuthority();

        $empScope = ['t' => 'all'];
        if ($authorityPersonalScope && $user) {
            $ids = array_values(array_filter(
                getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null) ?? []
            ));
            $ids = array_values(array_unique(array_map('intval', $ids)));
            sort($ids, SORT_NUMERIC);
            $empScope = ['t' => 'emp', 'ids' => $ids];
        } elseif ($authorityPersonalScope) {
            $empScope = ['t' => 'emp', 'ids' => []];
        }

        return [
            'type_filter' => trim((string) $r->input('type_filter', '')),
            'scope' => (string) $r->input('scope', ''),
            'emp' => $empScope,
            'can' => $canSeeHacApproved ? 1 : 0,
            'uid' => Auth::id(),
        ];
    }

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
                ? self::statusBadge('Change Request', 'change-request')
                : self::statusBadge('New Request', 'new-request'))
            ->addColumn('name_id', fn ($row) => self::nameWithId($row->emp_name ?? '', $row->employee_id ?? ''))
            ->editColumn('current_or_availability', function ($row) {
                // Change request: show current allotment/availability only after approval
                if (($row->request_type ?? '') === 'change') {
                    $approved = (int) ($row->change_ap_dis_status ?? 0) === 1;
                    return $approved ? e($row->current_or_availability ?? '—') : '—';
                }
                return e($row->current_or_availability ?? '—');
            })
            ->editColumn('remarks', fn ($row) => \Illuminate\Support\Str::limit(e($row->remarks ?? ''), 60))
            ->addColumn('action', function ($row) {
                // Every row shows the same four actions; the ones that don't apply
                // are greyed rather than removed so the column keeps one shape.
                $detailsPk = (int) ($row->estate_home_request_details_pk ?? $row->source_pk ?? 0);
                $isChange = ($row->request_type ?? '') === 'change';
                $sourcePk = (int) $row->source_pk;
                $reqId = e($row->request_id ?? 'N/A');
                $decision = $row->change_ap_dis_status !== null ? (int) $row->change_ap_dis_status : null;

                $view = $detailsPk
                    ? self::actionLink('visibility', 'View', 'view', [
                        'href' => route('admin.estate.request-details', ['id' => $detailsPk]),
                        'title' => 'View request & change details',
                    ])
                    : self::actionLink('visibility', 'View', 'view', ['disabled' => true, 'title' => 'No linked request']);

                // Allot House belongs to NEW requests only.
                $allot = $isChange
                    ? self::actionLink('add_home', 'Allot House', 'possession', [
                        'disabled' => true,
                        'title' => 'Allotment applies to new requests; approve the change request instead',
                    ])
                    : self::actionLink('add_home', 'Allot House', 'possession', [
                        'class' => 'btn-allot-new-request',
                        'title' => 'Allot house (add to Possession Details)',
                        'attrs' => 'data-id="' . $sourcePk . '"'
                            . ' data-req-id="' . $reqId . '"'
                            . ' data-details-url="' . e(route('admin.estate.new-request.allot-details', ['id' => $sourcePk])) . '"',
                    ]);

                // Approve / Reject belong to CHANGE requests that are still undecided.
                $pendingChange = $isChange && ($decision === null || $decision === 0);

                if ($pendingChange) {
                    $approve = self::actionLink('check_circle', 'Approve', 'approve', [
                        'class' => 'btn-approve-change-request',
                        'title' => 'Approve change request',
                        'attrs' => 'data-id="' . $sourcePk . '" data-request-id="' . $reqId . '"',
                    ]);
                    $reject = self::actionLink('cancel', 'Reject', 'reject', [
                        'class' => 'btn-disapprove-change-request',
                        'title' => 'Reject change request',
                        'attrs' => 'data-id="' . $sourcePk . '" data-request-id="' . $reqId . '"',
                    ]);
                } else {
                    $decidedTitle = match (true) {
                        $decision === 1 => 'Already approved',
                        $decision === 2 => 'Already rejected',
                        default => 'Approval applies to change requests',
                    };
                    $approve = self::actionLink('check_circle', 'Approve', 'approve', ['disabled' => true, 'title' => $decidedTitle]);
                    $reject = self::actionLink('cancel', 'Reject', 'reject', ['disabled' => true, 'title' => $decidedTitle]);
                }

                return '<div class="rfe-actions" role="group" aria-label="Row actions">'
                    . $view . $allot . $approve . $reject
                    . '</div>';
            })
            ->rawColumns(['request_type', 'name_id', 'action'])
            ->filter(function ($query) {
                static::applyFilters(
                    $query,
                    (string) request()->input('search.value', ''),
                    (string) request()->input('type_filter', '')
                );
            }, true)
            ->orderColumn('request_date', fn ($query, $order) => $query->reorder()
                ->orderBy('request_date', $order)
                ->orderBy('pk', $order))
            ->orderColumn('request_type', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_type, "")) ' . $order))
            ->orderColumn('request_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_id, "")) ' . $order))
            ->orderColumn('name_id', fn ($query, $order) => $query->reorder()
                ->orderByRaw('LOWER(COALESCE(emp_name, "")) ' . $order)
                ->orderByRaw('LOWER(COALESCE(employee_id, "")) ' . $order))
            ->orderColumn('emp_designation', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(emp_designation, "")) ' . $order))
            ->orderColumn('pay_scale', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(pay_scale, "")) ' . $order))
            ->setRowId('pk');
    }

    public function query(EstateHacApprovedRow $model): EloquentBuilder
    {
        return static::listingQuery($model);
    }

    /**
     * The listing query - the change/new union plus the HAC visibility rules.
     *
     * The Download / Print exports call this too, so what a user downloads is
     * always exactly the rows they are allowed to see on screen.
     */
    public static function listingQuery(?EstateHacApprovedRow $model = null): EloquentBuilder
    {
        $model = $model ?: new EstateHacApprovedRow();

        $canSeeHacApproved = hasRole('HAC Person') || isEstateHacAuthority();

        $authorityPersonalScope = request('scope') === 'self'
            && isEstateHacAuthority();
        $selfEmployeePks = [];
        if ($authorityPersonalScope && Auth::check()) {
            $selfEmployeePks = array_values(array_filter(
                getEmployeeIdsForUser(Auth::user()->user_id ?? Auth::user()->pk ?? null) ?? []
            ));
        }

        $part1 = DB::table('estate_change_home_req_details as ec')
            ->join('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->leftJoin('employee_master as e_emp', function ($join) {
                $join->on('eh.employee_pk', '=', 'e_emp.pk');
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->orOn('eh.employee_pk', '=', 'e_emp.pk_old');
                }
            })
            ->leftJoin('designation_master as d_emp', 'e_emp.designation_master_pk', '=', 'd_emp.pk')
            ->where('ec.estate_change_hac_status', 1)
            ->select(
                DB::raw("'change' as request_type"),
                'ec.pk as source_pk',
                'ec.pk as pk',
                'eh.pk as estate_home_request_details_pk',
                'ec.estate_change_req_ID as request_id',
                'ec.change_req_date as request_date',
                'eh.emp_name',
                'eh.employee_id',
                DB::raw("COALESCE(NULLIF(TRIM(d_emp.designation_name), ''), NULLIF(TRIM(eh.emp_designation), '')) as emp_designation"),
                'eh.pay_scale',
                'eh.doj_pay_scale',
                'eh.doj_service',
                'eh.doj_academic',
                DB::raw("CASE eh.eligibility_type_pk WHEN 61 THEN 'Type-I' WHEN 62 THEN 'Type-II' WHEN 63 THEN 'Type-III' ELSE 'Type-IV' END as eligibility_label"),
                'ec.change_house_no as current_or_availability',
                'ec.remarks',
                'ec.change_ap_dis_status'
            )
            ->when($authorityPersonalScope, function ($q) use ($selfEmployeePks) {
                if (! empty($selfEmployeePks)) {
                    $q->whereIn('eh.employee_pk', $selfEmployeePks);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        $hasPossessionPks = DB::table('estate_possession_details')
            ->whereNotNull('estate_home_request_details')
            ->pluck('estate_home_request_details')
            ->unique()
            ->values()
            ->all();

        $part2 = DB::table('estate_home_request_details as eh')
            ->leftJoin('employee_master as e_emp', function ($join) {
                $join->on('eh.employee_pk', '=', 'e_emp.pk');
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->orOn('eh.employee_pk', '=', 'e_emp.pk_old');
                }
            })
            ->leftJoin('designation_master as d_emp', 'e_emp.designation_master_pk', '=', 'd_emp.pk')
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
                'eh.employee_id',
                DB::raw("COALESCE(NULLIF(TRIM(d_emp.designation_name), ''), NULLIF(TRIM(eh.emp_designation), '')) as emp_designation"),
                'eh.pay_scale',
                'eh.doj_pay_scale',
                'eh.doj_service',
                'eh.doj_academic',
                DB::raw("CASE eh.eligibility_type_pk WHEN 61 THEN 'Type-I' WHEN 62 THEN 'Type-II' WHEN 63 THEN 'Type-III' ELSE 'Type-IV' END as eligibility_label"),
                'eh.current_alot as current_or_availability',
                'eh.remarks',
                DB::raw('NULL as change_ap_dis_status')
            )
            ->when($authorityPersonalScope, function ($q) use ($selfEmployeePks) {
                if (! empty($selfEmployeePks)) {
                    $q->whereIn('eh.employee_pk', $selfEmployeePks);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        $unionQuery = $part1->unionAll($part2);

        $q = $model->newQuery()
            ->fromSub($unionQuery, 'hac_approved')
            ->orderByDesc('request_date')
            ->orderByDesc('pk');

        // Self-service staff/training roles must not access HAC approved queues.
        if (! Auth::check() || ! $canSeeHacApproved) {
            $q->whereRaw('1 = 0');
        }

        return $q;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateHacApprovedTable')
            // programme-dt chrome (docs/new-design-index-page.md) - no `dom` and no
            // `language` here on purpose: datatable-global-ui.js owns both, and a
            // page-level override would win and break the "Showing N of M items" footer.
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'type_filter' => '$("#hacApprovedTypeFilter").val()',
                'scope' => 'new URLSearchParams(window.location.search).get("scope") || ""',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native (server-side) ordering so a header click
                // re-sorts the WHOLE queue instead of just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->width('64px'),
            Column::make('request_date')->title('Request Date')->orderable(true)->searchable(false)->visible(false),
            Column::make('request_type')->title('Request Type')->orderable(true)->searchable(false)->width('150px'),
            Column::make('request_id')->title('Request ID')->orderable(true)->searchable(true),
            Column::computed('name_id')->title('Name & ID')->addClass('hac-col-name')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('Designation')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('Pay Scale')->orderable(true)->searchable(true),
            // Wide enough for the four actions to sit on one row - see .hac-col-action.
            Column::computed('action')->title('Action')->addClass('hac-col-action')->orderable(false)->searchable(false)->width('215px'),
        ];
    }

    /**
     * The listing's type filter + free-text search, shared by the DataTable and
     * the exports so a download of a filtered list matches what the table showed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyFilters($query, string $searchValue, string $typeFilter): void
    {
        $typeFilter = trim($typeFilter);
        if (in_array($typeFilter, ['change', 'new'], true)) {
            $query->where('request_type', $typeFilter);
        }

        $searchValue = trim($searchValue);
        if ($searchValue === '') {
            return;
        }

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

    /** Plain-text request type, shared with the exports. */
    public static function requestTypeLabel($row): string
    {
        return ($row->request_type ?? '') === 'change' ? 'Change Request' : 'New Request';
    }

    /** Plain-text decision on a change request ('-' for new requests). */
    public static function decisionLabel($row): string
    {
        if (($row->request_type ?? '') !== 'change') {
            return '-';
        }

        return match ((int) ($row->change_ap_dis_status ?? 0)) {
            1 => 'Approved',
            2 => 'Rejected',
            default => 'Pending',
        };
    }

    protected function filename(): string
    {
        return 'HacApproved_' . date('YmdHis');
    }
}
