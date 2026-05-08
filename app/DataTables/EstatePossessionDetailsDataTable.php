<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstatePossessionDetailsDataTable extends DataTable
{
    /**
     * Server-side JSON (ESTATE_UPDATE_METER_READING_CACHE_*). Keys: estate_epd:v1:…
     * Cached rows may embed CSRF in delete forms; token is refreshed on cache hit.
     */
    public function ajax(): JsonResponse
    {
        $draw = (int) $this->request()->input('draw', 0);
        $fingerprint = $this->possessionDetailsDataTableCacheFingerprint();
        $cacheKey = 'estate_epd:v1:' . md5(json_encode($fingerprint));

        $payload = $this->rememberEstateListingCache($cacheKey, function () {
            $resp = parent::ajax();
            $data = $resp->getData(true);
            if (! is_array($data)) {
                return ['__passthrough' => true, 'body' => $resp->getContent()];
            }
            unset($data['draw']);

            return $data;
        });

        if (is_array($payload) && ! isset($payload['__passthrough'])) {
            $payload = $this->refreshCsrfTokensInDataTableRows($payload);
        }

        if (isset($payload['__passthrough']) && $payload['__passthrough']) {
            $decoded = json_decode((string) ($payload['body'] ?? ''), true);
            if (! is_array($decoded)) {
                return parent::ajax();
            }
            $decoded = $this->refreshCsrfTokensInDataTableRows($decoded);

            return new JsonResponse(array_merge($decoded, ['draw' => $draw]));
        }

        $payload['draw'] = $draw;

        return new JsonResponse($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function refreshCsrfTokensInDataTableRows(array $payload): array
    {
        $token = csrf_token();
        if ($token === '' || ! isset($payload['data']) || ! is_array($payload['data'])) {
            return $payload;
        }
        $replacement = 'name="_token" value="' . e($token) . '"';
        foreach ($payload['data'] as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $key => $val) {
                if (! is_string($val) || ! str_contains($val, 'name="_token"')) {
                    continue;
                }
                $payload['data'][$i][$key] = preg_replace(
                    '/name="_token" value="[^"]*"/',
                    $replacement,
                    $val
                ) ?? $val;
            }
        }

        return $payload;
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
            Log::warning('Possession details DataTable: cache store failed, using DB only.', [
                'store' => $storeName,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function possessionDetailsDataTableCacheFingerprint(): array
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
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        $authorityPersonalScope = $r->input('scope') === 'self' && $isEstateAuthority;
        $hideAuthorityMutations = $authorityPersonalScope;

        $empScope = ['t' => 'all'];
        if (! $isEstateAuthority || $authorityPersonalScope) {
            if ($user) {
                $ids = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                $ids = array_values(array_unique(array_map('intval', $ids)));
                sort($ids, SORT_NUMERIC);
                $empScope = ['t' => 'emp', 'ids' => $ids];
            } else {
                $empScope = ['t' => 'emp', 'ids' => []];
            }
        }

        return [
            'start' => (int) $r->input('start', 0),
            'len' => $r->input('length', 10),
            'q' => trim((string) data_get($r->all(), 'search.value', '')),
            'order' => $r->input('order', []),
            'cols' => $colSearch,
            'scope' => (string) $r->input('scope', ''),
            'emp' => $empScope,
            'isa' => $isEstateAuthority ? 1 : 0,
            'ham' => $hideAuthorityMutations ? 1 : 0,
            'ut' => Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 1 : 0,
            'r2' => Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2') ? 1 : 0,
            'uid' => Auth::id(),
        ];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $hasReading2Col = Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');

        $isEstateAuthority = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
        // Home ?scope=self: read-only list; edit/delete/bulk from Setup → Estate only.
        $hideAuthorityMutations = request('scope') === 'self' && $isEstateAuthority;

        $dataTable = (new EloquentDataTable($query))
            ->addIndexColumn();

        if ($isEstateAuthority && ! $hideAuthorityMutations) {
            $dataTable->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input row-select-possession-details" data-id="' . (int) $row->pk . '" aria-label="Select row">';
            });
        }

        $dataTable = $dataTable
            ->editColumn('request_id', fn ($row) => e($row->request_id ?? '—'))
            ->editColumn('emp_name', fn ($row) => e($row->emp_name ?? '—'))
            ->editColumn('employee_id', fn ($row) => e($row->employee_id ?? '—'))
            ->editColumn('emp_designation', fn ($row) => e($row->emp_designation ?? '—'))
            ->editColumn('estate_name', fn ($row) => e($row->estate_name ?? '—'))
            ->editColumn('building_name', fn ($row) => e($row->building_name ?? '—'))
            ->editColumn('unit_type', fn ($row) => e($row->unit_type ?? '—'))
            ->editColumn('unit_sub_type', fn ($row) => e($row->unit_sub_type ?? '—'))
            ->editColumn('house_no', fn ($row) => e($row->house_no ?? '—'))
            ->editColumn('allotment_date', function ($row) {
                $d = $row->allotment_date ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('possession_date', function ($row) {
                $d = $row->possession_date ?? null;
                if (! $d) return '—';
                try {
                    $dt = \Carbon\Carbon::parse($d);
                    if ($dt->format('Y-m-d') <= '1900-01-01') return '—';
                    return $dt->format('d-m-Y');
                } catch (\Throwable $e) {
                    return '—';
                }
            })
            ->editColumn('electric_meter_reading', function ($row) use ($hasReading2Col) {
                $primary = $row->electric_meter_reading;
                $secondary = $hasReading2Col ? ($row->electric_meter_reading_2 ?? null) : null;

                $seg = static function ($v) {
                    return ($v !== null && trim((string) $v) !== '') ? (string) $v : '—';
                };

                $secStr = $secondary !== null ? trim((string) $secondary) : '';
                $hasSecondaryEntered = $hasReading2Col
                    && $secStr !== ''
                    && ! (is_numeric($secStr) && (int) $secStr === 0);

                if ($hasSecondaryEntered) {
                    return $seg($primary) . '/' . $seg($secondary);
                }

                return ($primary !== null && $primary !== '') ? (string) $primary : '---';
            });

        if ($isEstateAuthority && ! $hideAuthorityMutations) {
            $dataTable->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.possession-details.create', [
                    'requester_id' => $row->estate_home_request_details_pk,
                ]);
                $deleteUrl = route('admin.estate.possession-details.delete', ['id' => $row->pk]);

                return '<div class="d-inline-flex align-items-center gap-2" role="group">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit">
                        <i class="material-symbols-rounded">edit</i>
                    </a>
                    <form method="POST" action="' . e($deleteUrl) . '" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this possession details record?\')">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 text-danger" title="Delete" aria-label="Delete">
                            <i class="material-symbols-rounded">delete</i>
                        </button>
                    </form>
                </div>';
            });
        }

        return $dataTable
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue === '') {
                    return;
                }
                $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                $query->where(function ($q) use ($searchLike) {
                    $q->where('ehrd.req_id', 'like', $searchLike)
                        ->orWhere('ehrd.emp_name', 'like', $searchLike)
                        ->orWhere('ehrd.employee_id', 'like', $searchLike)
                        ->orWhere('ehrd.emp_designation', 'like', $searchLike)
                        ->orWhere('ec.campus_name', 'like', $searchLike)
                        ->orWhere('eb.block_name', 'like', $searchLike)
                        ->orWhere('eut.unit_type', 'like', $searchLike)
                        ->orWhere('eust.unit_sub_type', 'like', $searchLike)
                        ->orWhere('ehm.house_no', 'like', $searchLike);
                });
            }, true)
            ->rawColumns(array_values(array_filter([
                ($isEstateAuthority && ! $hideAuthorityMutations) ? 'checkbox' : null,
                ($isEstateAuthority && ! $hideAuthorityMutations) ? 'actions' : null,
            ])))
            ->setRowId('pk');
    }

    public function query(EstateHomeRequestDetails $model): QueryBuilder
    {
        // Last month readings = epd.electric_meter_reading (I) and optional epd.electric_meter_reading_2 (II).
        // Do NOT use estate_month_reading_details (curr_month_elec_red) here — that shows "latest updated"
        // reading and was causing wrong/old value to appear (bug raised multiple times).
        $query = $model->newQuery()
            ->from('estate_home_request_details as ehrd')
            ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_campus_master as ec', 'ehm.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk'), function ($q) {
                $q->leftJoin('estate_unit_type_master as eut', 'eust.estate_unit_type_master_pk', '=', 'eut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as eut', 'ehm.estate_unit_master_pk', '=', 'eut.pk');
            })
            ->select(array_merge([
                'epd.pk as pk',
                'ehrd.pk as estate_home_request_details_pk',
                'ehrd.req_id as request_id',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'ec.campus_name as estate_name',
                'eb.block_name as building_name',
                'eut.unit_type',
                'eust.unit_sub_type',
                'ehm.house_no',
                'epd.allotment_date',
                'epd.possession_date',
                'epd.electric_meter_reading',
            ], Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')
                ? ['epd.electric_meter_reading_2']
                : []));

        // Show only *completed* possessions in listing.
        // Pending possessions (created at allotment time) store a sentinel date: 1900-01-01.
        $query->where('epd.possession_date', '>', '1900-01-01');
        $query->where('epd.return_home_status', 0);

        // RBAC: Admin / Estate / Super Admin see full list unless Home ?scope=self (own rows only).
        // Other roles (Staff / HAC / Training / IST etc.) always see only their own possessions.
        $user = \Illuminate\Support\Facades\Auth::user();
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        $authorityPersonalScope = request('scope') === 'self' && $isEstateAuthority;
        if (! $isEstateAuthority || $authorityPersonalScope) {
            if ($user) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (! empty($employeeIds)) {
                    $query->whereIn('ehrd.employee_pk', $employeeIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderBy('epd.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estatePossessionDetailsTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100')
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
                'order' => [[1, 'desc']], // epd.pk desc = newest possession first
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
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
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        $isEstateAuthority = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
        $hideAuthorityMutations = request('scope') === 'self' && $isEstateAuthority;

        $columns = [];
        if ($isEstateAuthority && ! $hideAuthorityMutations) {
            $columns[] = Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="selectAllPossessionDetails" aria-label="Select all">')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('40px');
        }
        $columns = array_merge($columns, [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            // Hidden column for default sort: newest possession (highest pk) first
            Column::make('pk')->name('epd.pk')->title('ID')->orderable(true)->searchable(false)->addClass('d-none')->visible(false),
            // searchable(false) so Yajra does not add WHERE using model table + data name (estate_home_request_details.request_id);
            // global search is handled by the custom filter() above with correct aliases (ehrd.req_id etc.)
            Column::make('request_id')->name('ehrd.req_id')->title('REQUEST ID')->orderable(true)->searchable(false),
            Column::make('emp_name')->name('ehrd.emp_name')->title('NAME')->orderable(true)->searchable(false),
            Column::make('employee_id')->name('ehrd.employee_id')->title('EMPLOYEE ID')->orderable(true)->searchable(false),
            Column::make('emp_designation')->name('ehrd.emp_designation')->title('DESIGNATION')->orderable(true)->searchable(false),
            Column::make('estate_name')->name('ec.campus_name')->title('ESTATE NAME')->orderable(true)->searchable(false),
            Column::make('building_name')->name('eb.block_name')->title('BUILDING NAME')->orderable(true)->searchable(false),
            Column::make('unit_type')->name('eut.unit_type')->title('UNIT TYPE')->orderable(true)->searchable(false),
            Column::make('unit_sub_type')->name('eust.unit_sub_type')->title('UNIT SUB TYPE')->orderable(true)->searchable(false),
            Column::make('house_no')->name('ehm.house_no')->title('HOUSE NO.')->orderable(true)->searchable(false),
            Column::make('allotment_date')->name('epd.allotment_date')->title('ALLOTMENT DATE')->orderable(true)->searchable(false),
            Column::make('possession_date')->name('epd.possession_date')->title('POSSESSION DATE')->orderable(true)->searchable(false),
            Column::make('electric_meter_reading')->name('epd.electric_meter_reading')->title('LAST MONTH ELECTRIC METER READING')->orderable(false)->searchable(false)->addClass('text-end')->width('140px'),
        ]);

        if ($isEstateAuthority && ! $hideAuthorityMutations) {
            $columns[] = Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false)->width('120px');
        }

        return $columns;
    }

    protected function filename(): string
    {
        return 'EstatePossessionDetails_' . date('YmdHis');
    }
}