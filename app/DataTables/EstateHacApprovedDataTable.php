<?php

namespace App\DataTables;

use App\Models\EstateHacApprovedRow;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Single table for HAC Approved: Change requests + New requests.
 */
class EstateHacApprovedDataTable extends DataTable
{
    /**
     * Server-side JSON (ESTATE_UPDATE_METER_READING_CACHE_*). Keys: estate_hacap:v1:…
     */
    public function ajax(): JsonResponse
    {
        $draw = (int) $this->request()->input('draw', 0);
        $fingerprint = $this->hacApprovedDataTableCacheFingerprint();
        $cacheKey = 'estate_hacap:v1:' . md5(json_encode($fingerprint));

        $payload = $this->rememberEstateListingCache($cacheKey, function () {
            $resp = parent::ajax();
            $data = $resp->getData(true);
            if (! is_array($data)) {
                return ['__passthrough' => true, 'body' => $resp->getContent()];
            }
            unset($data['draw']);

            return $data;
        });

        if (isset($payload['__passthrough']) && $payload['__passthrough']) {
            $decoded = json_decode((string) ($payload['body'] ?? ''), true);

            return is_array($decoded)
                ? new JsonResponse(array_merge($decoded, ['draw' => $draw]))
                : parent::ajax();
        }

        $payload['draw'] = $draw;

        return new JsonResponse($payload);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function rememberEstateListingCache(string $cacheKey, callable $callback)
    {
        $enabled = ! in_array(strtolower((string) env('ESTATE_UPDATE_METER_READING_CACHE_ENABLED', 'true')), ['0', 'false', 'no', 'off'], true);
        $ttl = max(30, (int) env('ESTATE_UPDATE_METER_READING_CACHE_SECONDS', 300));
        $storeName = (string) env('ESTATE_UPDATE_METER_READING_CACHE_STORE', env('ESTATE_BILL_REPORT_GRID_CACHE_STORE', 'redis'));
        $repository = array_key_exists($storeName, config('cache.stores', []))
            ? \Illuminate\Support\Facades\Cache::store($storeName)
            : \Illuminate\Support\Facades\Cache::store(config('cache.default'));
        if (! $enabled) {
            return $callback();
        }
        try {
            return $repository->remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('HAC approved DataTable: cache store failed, using DB only.', [
                'store' => $storeName,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function hacApprovedDataTableCacheFingerprint(): array
    {
        $r = $this->request();
        $columns = $r->input('columns', []);
        $colSearch = [];
        if (is_array($columns)) {
            foreach ($columns as $c) {
                if (! is_array($c)) {
                    continue;
                }
                $colSearch[] = [
                    'data' => $c['data'] ?? '',
                    'sv' => trim((string) data_get($c, 'search.value', '')),
                ];
            }
        }

        $user = Auth::user();
        $canSeeHacApproved = $user && (hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));

        $authorityPersonalScope = $r->input('scope') === 'self'
            && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));

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
            'start' => (int) $r->input('start', 0),
            'len' => $r->input('length', 10),
            'q' => trim((string) data_get($r->all(), 'search.value', '')),
            'order' => $r->input('order', []),
            'cols' => $colSearch,
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
                ? '<span class="badge bg-danger">Change Request</span>'
                : '<span class="badge bg-primary">New Request</span>')
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
                $detailsPk = (int) ($row->estate_home_request_details_pk ?? $row->source_pk ?? 0);
                $detailsUrl = $detailsPk ? route('admin.estate.request-details', ['id' => $detailsPk]) : '#';
                $detailsLink = $detailsPk
                    ? '<a href="' . e($detailsUrl) . '" class="text-primary" title="View request &amp; change details">
                           <i class="material-icons material-symbols-rounded">visibility</i>
                       </a>'
                    : '';
                if ($row->request_type === 'change') {
                    $status = (int) ($row->change_ap_dis_status ?? 0);
                    if ($status === 1) {
                        return '<div class="d-inline-flex align-items-center gap-1 justify-content-center">'
                            . $detailsLink
                            . '<span class="text-success" title="Approved">
                                   <i class="material-icons material-symbols-rounded">check_circle</i>
                               </span>'
                            . '</div>';
                    }
                    if ($status === 2) {
                        return '<div class="d-inline-flex align-items-center gap-1 justify-content-center">'
                            . $detailsLink
                            . '<span class="text-danger" title="Disapproved">
                                   <i class="material-icons material-symbols-rounded">cancel</i>
                               </span>'
                            . '</div>';
                    }
                    $reqId = e($row->request_id ?? 'N/A');
                    return '<div class="d-inline-flex align-items-center gap-1 justify-content-center">'
                        . $detailsLink
                        . '<a href="javascript:void(0);" class="text-success btn-approve-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '" title="Approve change request">
                               <i class="material-icons material-symbols-rounded">check_circle</i>
                           </a>'
                        . '<a href="javascript:void(0);" class="text-danger btn-disapprove-change-request" data-id="' . (int) $row->source_pk . '" data-request-id="' . $reqId . '" title="Disapprove change request">
                               <i class="material-icons material-symbols-rounded">cancel</i>
                           </a>'
                        . '</div>';
                }
                $url = route('admin.estate.new-request.allot-details', ['id' => $row->source_pk]);
                return '<div class="d-inline-flex align-items-center gap-1 justify-content-center">'
                    . $detailsLink
                    . '<a href="javascript:void(0);" class="text-success btn-allot-new-request" data-id="' . (int) $row->source_pk . '" data-req-id="' . e($row->request_id ?? '') . '" data-details-url="' . e($url) . '" title="Allot house (add to Possession Details)">
                           <i class="material-icons material-symbols-rounded">add_home</i>
                       </a>'
                    . '</div>';
            })
            ->rawColumns(['request_type', 'action'])
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                $typeFilter = trim((string) request()->input('type_filter', ''));

                if (in_array($typeFilter, ['change', 'new'], true)) {
                    $query->where('request_type', $typeFilter);
                }

                if ($searchValue !== '') {
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
            }, true)
            ->orderColumn('request_date', fn ($query, $order) => $query->reorder()
                ->orderBy('request_date', $order)
                ->orderBy('pk', $order))
            ->orderColumn('request_type', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_type, "")) ' . $order))
            ->orderColumn('request_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_id, "")) ' . $order))
            ->orderColumn('emp_name', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(emp_name, "")) ' . $order))
            ->orderColumn('emp_designation', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(emp_designation, "")) ' . $order))
            ->orderColumn('pay_scale', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(pay_scale, "")) ' . $order))
            ->setRowId('pk');
    }

    public function query(EstateHacApprovedRow $model): EloquentBuilder
    {
        $canSeeHacApproved = hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');

        $authorityPersonalScope = request('scope') === 'self'
            && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        $selfEmployeePks = [];
        if ($authorityPersonalScope && Auth::check()) {
            $selfEmployeePks = array_values(array_filter(
                getEmployeeIdsForUser(Auth::user()->user_id ?? Auth::user()->pk ?? null) ?? []
            ));
        }

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
                'eh.employee_id',
                'eh.emp_designation',
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
                'eh.emp_designation',
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
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'type_filter' => '$("#hacApprovedTypeFilter").val()',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'desc']],
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
            Column::make('request_date')->title('REQUEST DATE')->orderable(true)->searchable(false)->visible(false),
            Column::make('request_type')->title('TYPE')->orderable(true)->searchable(false)->width('120px'),
            Column::make('request_id')->title('REQUEST ID')->orderable(true)->searchable(true),
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
