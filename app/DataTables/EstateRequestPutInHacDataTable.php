<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateRequestPutInHacDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'estate_pih_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EstateRequestPutInHacDataTable');
    }

    /**
     * Server-side JSON (.env: ESTATE_UPDATE_METER_READING_CACHE_*).
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'estate_pih:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'ESTATE_UPDATE_METER_READING_CACHE_ENABLED',
                'seconds' => 'ESTATE_UPDATE_METER_READING_CACHE_SECONDS',
            ],
            'EstateRequestPutInHacDataTable',
            fn () => parent::ajax(),
            $this->putInHacListingCacheExtra()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function putInHacListingCacheExtra(): array
    {
        $r = $this->request();
        $user = Auth::user();
        $canPutInHac = $user && (hasRole('HAC Person') || isEstateHacAuthority());

        $empScope = ['t' => 'all'];
        if ($canPutInHac
            && $r->input('scope') === 'self'
            && isEstateHacAuthority()) {
            $ids = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            $ids = array_values(array_unique(array_map('intval', $ids)));
            sort($ids, SORT_NUMERIC);
            $empScope = ['t' => 'emp', 'ids' => $ids];
        }

        return [
            'scope' => (string) $r->input('scope', ''),
            'emp' => $empScope,
            'can' => $canPutInHac ? 1 : 0,
            'uid' => Auth::id(),
        ];
    }

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
            ->editColumn('doj_pay_scale', function ($row) {
                $d = $row->doj_pay_scale;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_service', function ($row) {
                $d = $row->doj_service;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('doj_academic', function ($row) {
                $d = $row->doj_academic;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('current_alot', fn ($row) => e($row->current_alot ?? '—'))
            ->editColumn('remarks', function ($row) {
                $raw = (string) ($row->remarks ?? '');
                $display = \Illuminate\Support\Str::limit($raw, 80);
                $displayEscaped = e($display);
                if ($raw === '') {
                    return '—';
                }
                if (mb_strlen($raw) > 80) {
                    return '<span title="' . e($raw) . '" class="text-truncate d-inline-block" style="max-width:200px;">' . $displayEscaped . '</span>';
                }
                return $displayEscaped;
            })
            ->addColumn('put_in_hac', function ($row) {
                $reqId = e($row->req_id ?? '');

                return '<div class="pih-check">
                    <input type="checkbox" class="form-check-input put-in-hac-checkbox" id="pih-' . (int) $row->pk . '"
                        data-pk="' . (int) $row->pk . '" data-req-id="' . $reqId . '"
                        aria-label="Select request ' . $reqId . ' for HAC">
                </div>';
            })
            ->rawColumns(['remarks', 'put_in_hac'])
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
        $canPutInHac = hasRole('HAC Person') || isEstateHacAuthority();
        // Self-service staff/training roles must not access HAC queues via this listing.
        // Return an empty dataset when user is not authorized.
        if (! Auth::check() || ! $canPutInHac) {
            return $model->newQuery()
                ->whereRaw('1 = 0');
        }

        $q = $model->newQuery()
            ->select([
                'estate_home_request_details.pk',
                'estate_home_request_details.req_id',
                'estate_home_request_details.req_date',
                'estate_home_request_details.emp_name',
                'estate_home_request_details.employee_id',
                'estate_home_request_details.emp_designation',
                'estate_home_request_details.pay_scale',
                'estate_home_request_details.doj_pay_scale',
                'estate_home_request_details.doj_academic',
                'estate_home_request_details.doj_service',
                'estate_home_request_details.current_alot',
                'estate_home_request_details.remarks',
                'estate_home_request_details.hac_status',
            ])
            ->where(function ($query) {
                $query->where('estate_home_request_details.hac_status', 0)
                    ->orWhereNull('estate_home_request_details.hac_status');
            })
            ->where(function ($query) {
                $query->where('estate_home_request_details.change_status', 0)
                    ->orWhereNull('estate_home_request_details.change_status');
            })
            ->where('estate_home_request_details.status', '!=', 2)
            ->orderBy('estate_home_request_details.pk', 'desc');

        // Home ?scope=self: only this user's requests (same as Request For Estate self view).
        if (request('scope') === 'self'
            && isEstateHacAuthority()) {
            $user = Auth::user();
            if ($user) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (! empty($employeeIds)) {
                    $q->whereIn('estate_home_request_details.employee_pk', $employeeIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            } else {
                $q->whereRaw('1 = 0');
            }
        }

        return $q;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('putInHacTable')
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
                // re-sorts the WHOLE queue instead of just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[2, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('put_in_hac')->title('Select')->addClass('pih-col-select')->orderable(false)->searchable(false)->width('72px'),
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->width('64px'),
            Column::make('req_id')->title('Request ID')->orderable(true)->searchable(true),
            Column::make('req_date')->title('Request Date')->orderable(true)->searchable(false),
            Column::make('emp_name')->title('Name')->addClass('pih-col-name')->orderable(true)->searchable(true),
            Column::make('employee_id')->title('Employee ID')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('Designation')->orderable(true)->searchable(true),
            Column::make('pay_scale')->title('Current Pay Scale')->orderable(true)->searchable(true),
            Column::make('doj_pay_scale')->title('DOJ (Current Pay Scale)')->orderable(false)->searchable(false),
            Column::make('doj_service')->title('DOJ (Service)')->orderable(false)->searchable(false),
            Column::make('doj_academic')->title('DOJ (Academy)')->orderable(false)->searchable(false),
            Column::make('current_alot')->title('Current Allotment')->orderable(true)->searchable(true),
            Column::make('remarks')->title('Remarks')->orderable(false)->searchable(true),
        ];
    }

    protected function filename(): string
    {
        return 'PutInHac_' . date('YmdHis');
    }
}