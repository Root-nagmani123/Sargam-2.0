<?php

namespace App\DataTables;

use App\Models\EstatePossessionOther;
use App\Support\RedisBackedCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstatePossessionOtherDataTable extends DataTable
{
    /**
     * Server-side JSON (ESTATE_UPDATE_METER_READING_CACHE_*). Keys: estate_epo:v1:…
     */
    public function ajax(): JsonResponse
    {
        $draw = (int) $this->request()->input('draw', 0);
        $fingerprint = $this->possessionOtherDataTableCacheFingerprint();
        $cacheKey = 'estate_epo:v1:' . md5(json_encode($fingerprint));

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
        $storeName = RedisBackedCache::estateUpdateMeterReadingStoreName();
        $repository = RedisBackedCache::repositoryForStore($storeName);
        if (! $enabled) {
            return $callback();
        }
        try {
            return $repository->remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('Possession for others DataTable: cache store failed, using DB only.', [
                'store' => $storeName,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function possessionOtherDataTableCacheFingerprint(): array
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

        $canDelete = hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin');

        return [
            'start' => (int) $r->input('start', 0),
            'len' => $r->input('length', 10),
            'q' => trim((string) data_get($r->all(), 'search.value', '')),
            'order' => $r->input('order', []),
            'cols' => $colSearch,
            'can_del' => $canDelete ? 1 : 0,
            'uid' => Auth::id(),
        ];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input row-select-possession" data-id="' . (int) $row->pk . '" aria-label="Select row">';
            })
            ->editColumn('request_id', fn($row) => $row->estateOtherRequest->request_no_oth ?? 'N/A')
            ->editColumn('name', fn($row) => $row->estateOtherRequest->emp_name ?? 'N/A')
            ->editColumn('section_name', function ($row) {
                $section = $row->getAttribute('eor_section') ?? $row->estateOtherRequest?->section;
                $designation = $row->getAttribute('eor_designation') ?? $row->estateOtherRequest?->designation;
                $value = ($section !== null && $section !== '') ? $section : $designation;
                return $value !== null && $value !== '' ? $value : '—';
            })
            ->editColumn('estate_name', fn($row) => $row->campus_name ?? 'N/A')
            ->editColumn('unit_type', fn($row) => $row->unit_type_name ?? 'N/A')
            ->editColumn('building_name', fn($row) => $row->block_name ?? 'N/A')
            ->editColumn('unit_sub_type', fn($row) => $row->unit_sub_type_name ?? 'N/A')
            ->editColumn('house_no', fn($row) => $row->house_no ?? $row->house_no_display ?? 'N/A')
            ->editColumn('allotment_date', fn($row) => $row->allotment_date ? $row->allotment_date->format('d-m-Y') : '—')
            ->editColumn('possession_date_oth', fn($row) => $row->possession_date_oth ? $row->possession_date_oth->format('d-m-Y') : '—')
            // Show "primary/secondary" only after secondary reading is actually saved (not empty / not 0).
            ->editColumn('meter_reading_oth', function ($row) {
                $primary = $row->meter_reading_oth;
                $secondary = $row->meter_reading_oth1;

                $seg = static function ($v) {
                    return ($v !== null && trim((string) $v) !== '') ? (string) $v : '—';
                };

                $secStr = $secondary !== null ? trim((string) $secondary) : '';
                $hasSecondaryEntered = $secStr !== ''
                    && ! (is_numeric($secStr) && (int) $secStr === 0);

                if ($hasSecondaryEntered) {
                    return $seg($primary) . '/' . $seg($secondary);
                }

                return ($primary !== null && $primary !== '') ? (string) $primary : '---';
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (empty($searchValue)) {
                    return;
                }
                $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                $query->where(function ($q) use ($searchLike) {
                    $q->where('eor.request_no_oth', 'like', $searchLike)
                        ->orWhere('eor.emp_name', 'like', $searchLike)
                        ->orWhere('eor.section', 'like', $searchLike)
                        ->orWhere('eor.designation', 'like', $searchLike)
                        ->orWhere('ec.campus_name', 'like', $searchLike)
                        ->orWhere('eb.block_name', 'like', $searchLike)
                        ->orWhere('eut.unit_type', 'like', $searchLike)
                        ->orWhere('eust.unit_sub_type', 'like', $searchLike)
                        ->orWhere('estate_possession_other.house_no', 'like', $searchLike)
                        ->orWhere('ehm.house_no', 'like', $searchLike);
                });
            })
            ->orderColumn('DT_RowIndex', fn ($query, $order) => $query->orderBy('estate_possession_other.pk', $order))
            ->orderColumn('request_id', fn ($query, $order) => $query->orderBy('eor.request_no_oth', $order))
            ->orderColumn('name', fn ($query, $order) => $query->orderBy('eor.emp_name', $order))
            ->orderColumn('section_name', fn ($query, $order) => $query->orderBy('eor.section', $order))
            ->orderColumn('estate_name', fn ($query, $order) => $query->orderBy('ec.campus_name', $order))
            ->orderColumn('unit_type', fn ($query, $order) => $query->orderBy('eut.unit_type', $order))
            ->orderColumn('building_name', fn ($query, $order) => $query->orderBy('eb.block_name', $order))
            ->orderColumn('unit_sub_type', fn ($query, $order) => $query->orderBy('eust.unit_sub_type', $order))
            ->orderColumn('house_no', fn ($query, $order) => $query->orderBy('estate_possession_other.house_no', $order))
            ->orderColumn('allotment_date', fn ($query, $order) => $query->orderBy('estate_possession_other.allotment_date', $order))
            ->orderColumn('possession_date_oth', fn ($query, $order) => $query->orderBy('estate_possession_other.possession_date_oth', $order))
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.possession-view', ['id' => $row->pk]);
                $canDelete = hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin');
                $deleteUrl = route('admin.estate.possession-delete', ['id' => $row->pk]);

                $html = '<div class="d-inline-flex align-items-center gap-2" role="group">';
                $html .= '<a href="' . $editUrl . '" class="text-primary" title="Edit">
                    <i class="material-symbols-rounded">edit</i>
                </a>';

                if ($canDelete) {
                    $html .= '<form method="POST" action="' . $deleteUrl . '" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this possession?\')">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 text-danger" title="Delete" aria-label="Delete">
                            <i class="material-symbols-rounded">delete</i>
                        </button>
                    </form>';
                }

                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['checkbox', 'actions'])
            ->setRowId('pk');
    }

    public function query(EstatePossessionOther $model): QueryBuilder
    {
        $latestOtherReadings = DB::table('estate_month_reading_details_other as emro')
            ->join(DB::raw('(SELECT estate_possession_other_pk, MAX(pk) as max_pk FROM estate_month_reading_details_other GROUP BY estate_possession_other_pk) as x'), 'emro.pk', '=', 'x.max_pk')
            ->select('emro.estate_possession_other_pk', 'emro.curr_month_elec_red');

        return $model->newQuery()
            ->with(['estateOtherRequest:pk,emp_name,request_no_oth,section,designation'])
            ->select([
                'estate_possession_other.*',
                'ec.campus_name',
                'eb.block_name',
                'eor.request_no_oth',
                'eor.emp_name',
                'eor.section',
                'eor.designation',
                'eor.designation as eor_designation',
                'eor.section as eor_section',
                'eut.unit_type as unit_type_name',
                'eust.unit_sub_type as unit_sub_type_name',
                'ehm.house_no as house_no_display',
                'emro_latest.curr_month_elec_red as latest_curr_month_elec_red',
            ])
            ->leftJoin('estate_other_req as eor', 'estate_possession_other.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_campus_master as ec', 'estate_possession_other.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'estate_possession_other.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'estate_possession_other.estate_unit_type_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'estate_possession_other.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_house_master as ehm', 'estate_possession_other.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoinSub($latestOtherReadings, 'emro_latest', function ($join) {
                $join->on('emro_latest.estate_possession_other_pk', '=', 'estate_possession_other.pk');
            })
            ->where('estate_possession_other.return_home_status', 0)
            ->orderByDesc('estate_possession_other.pk');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estatePossessionTable')
            ->addTableClass('table table-bordered table-hover text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                // Default sort: newest possession first (by S.NO. which maps to pk desc)
                'order' => [[1, 'desc']],
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
                'scrollX' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="selectAllPossessionOthers" aria-label="Select all">')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('40px'),
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(true)->searchable(false)->width('50px'),
            Column::make('request_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('name')->title('NAME')->orderable(true)->searchable(true),
            Column::make('section_name')->title('SECTION NAME')->orderable(true)->searchable(true),
            Column::make('estate_name')->title('ESTATE NAME')->orderable(true)->searchable(true),
            Column::make('unit_type')->title('UNIT TYPE')->orderable(true)->searchable(true),
            Column::make('building_name')->title('BUILDING NAME')->orderable(true)->searchable(true),
            Column::make('unit_sub_type')->title('UNIT SUB TYPE')->orderable(true)->searchable(true),
            Column::make('house_no')->title('HOUSE NO.')->orderable(true)->searchable(true),
            Column::make('allotment_date')->title('ALLOTMENT DATE')->orderable(true)->searchable(false),
            Column::make('possession_date_oth')->title('POSSESSION DATE')->orderable(true)->searchable(false),
            Column::make('meter_reading_oth')->title('LAST MONTH ELECTRIC METER READING')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstatePossessionOther_' . date('YmdHis');
    }
}