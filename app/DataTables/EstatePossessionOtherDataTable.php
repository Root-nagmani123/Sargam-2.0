<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersEstateRowActions;
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
    use RendersEstateRowActions;

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

        $canDelete = isEstateAuthority();

        return [
            'start' => (int) $r->input('start', 0),
            'len' => $r->input('length', 10),
            'q' => trim((string) data_get($r->all(), 'search.value', '')),
            'order' => $r->input('order', []),
            'cols' => $colSearch,
            'estate_filter' => trim((string) $r->input('estate_filter', '')),
            'allotment_date_filter' => trim((string) $r->input('allotment_date_filter', '')),
            'can_del' => $canDelete ? 1 : 0,
            'uid' => Auth::id(),
        ];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canMutate = isEstateAuthority();

        $dataTable = (new EloquentDataTable($query))
            ->addIndexColumn();

        if ($canMutate) {
            $dataTable->addColumn('checkbox', function ($row) {
                return '<div class="pd-check"><input type="checkbox" class="form-check-input row-select-possession"'
                    . ' data-id="' . (int) $row->pk . '" aria-label="Select possession record ' . e($row->request_no_oth ?? '') . '"></div>';
            });
        }

        $dataTable = $dataTable
            ->editColumn('request_id', fn($row) => self::valueOrDash($row->estateOtherRequest->request_no_oth ?? $row->request_no_oth ?? null))
            ->editColumn('name', fn($row) => self::nameWithId($row->estateOtherRequest->emp_name ?? $row->emp_name ?? '', null))
            ->editColumn('section_name', fn($row) => self::valueOrDash(static::sectionLabel($row)))
            ->editColumn('estate_name', fn($row) => self::valueOrDash($row->campus_name ?? null))
            ->editColumn('unit_type', fn($row) => self::valueOrDash($row->unit_type_name ?? null))
            ->editColumn('building_name', fn($row) => self::valueOrDash($row->block_name ?? null))
            ->editColumn('unit_sub_type', fn($row) => self::valueOrDash($row->unit_sub_type_name ?? null))
            ->editColumn('house_no', fn($row) => self::valueOrDash($row->house_no ?? $row->house_no_display ?? null))
            ->editColumn('allotment_date', fn($row) => $row->allotment_date ? $row->allotment_date->format('d-m-Y') : '—')
            ->editColumn('possession_date_oth', fn($row) => $row->possession_date_oth ? $row->possession_date_oth->format('d-m-Y') : '—')
            // Show "primary/secondary" only after secondary reading is actually saved (not empty / not 0).
            ->editColumn('meter_reading_oth', fn($row) => static::meterReadingLabel($row, '—'))
            ->filter(function ($query) {
                static::applyFilters(
                    $query,
                    (string) request()->input('search.value', ''),
                    (string) request()->input('estate_filter', ''),
                    (string) request()->input('allotment_date_filter', '')
                );
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
            ->addColumn('actions', function ($row) use ($canMutate) {
                $editUrl = route('admin.estate.possession-view', ['id' => $row->pk]);

                // href stays the standalone page so ctrl-click / no-JS still works;
                // the listing's JS intercepts the click and opens the modal instead.
                $edit = self::actionLink('edit', 'Edit', 'edit', [
                    'href' => $editUrl,
                    'title' => 'Edit possession',
                    'class' => 'btn-edit-possession-other',
                    'attrs' => 'data-id="' . (int) $row->pk . '"',
                ]);

                // Delete goes through the page's confirm dialog (and the same bulk
                // endpoint), so no inline form / native confirm() here.
                $delete = $canMutate
                    ? self::actionLink('delete', 'Delete', 'delete', [
                        'class' => 'btn-delete-possession-other',
                        'title' => 'Delete possession',
                        'attrs' => 'data-id="' . (int) $row->pk . '"',
                    ])
                    : '';

                return '<div class="rfe-actions" role="group" aria-label="Row actions">' . $edit . $delete . '</div>';
            })
            ->rawColumns(array_values(array_filter([
                'name',
                $canMutate ? 'checkbox' : null,
                'actions',
            ])))
            ->setRowId('pk');

        return $dataTable;
    }

    /** Section, falling back to designation — the two carry the same thing on legacy rows. */
    public static function sectionLabel($row): ?string
    {
        $section = $row->getAttribute('eor_section') ?? $row->estateOtherRequest?->section;
        if ($section !== null && trim((string) $section) !== '') {
            return (string) $section;
        }

        $designation = $row->getAttribute('eor_designation') ?? $row->estateOtherRequest?->designation;

        return ($designation !== null && trim((string) $designation) !== '') ? (string) $designation : null;
    }

    /** Meter reading as shown on screen (I, or "I/II" once a second is entered). */
    public static function meterReadingLabel($row, string $dash = '-'): string
    {
        $primary = $row->meter_reading_oth ?? null;
        $secondary = $row->meter_reading_oth1 ?? null;

        $seg = static fn ($v) => ($v !== null && trim((string) $v) !== '') ? (string) $v : $dash;

        $secStr = $secondary !== null ? trim((string) $secondary) : '';
        $hasSecondary = $secStr !== '' && ! (is_numeric($secStr) && (int) $secStr === 0);

        if ($hasSecondary) {
            return $seg($primary) . '/' . $seg($secondary);
        }

        return ($primary !== null && trim((string) $primary) !== '') ? (string) $primary : $dash;
    }

    /** Cell text, or the shared muted dash when the column is empty. */
    private static function valueOrDash($value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || $value === '-' ? '—' : $value;
    }

    public function query(EstatePossessionOther $model): QueryBuilder
    {
        return static::listingQuery($model);
    }

    /**
     * The listing query — joins and the "still in possession" rule.
     *
     * The Download / Print exports call this too, so what a user downloads is
     * always exactly the rows the table showed.
     */
    public static function listingQuery(?EstatePossessionOther $model = null): QueryBuilder
    {
        $model = $model ?: new EstatePossessionOther();

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

    /**
     * The listing's search + Estate Name / Allotment date filters, shared by the
     * DataTable and the exports so a download matches what the table showed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyFilters($query, string $searchValue, string $estateFilter = '', string $allotmentDate = ''): void
    {
        $estateFilter = trim($estateFilter);
        if ($estateFilter !== '') {
            $query->where('estate_possession_other.estate_campus_master_pk', (int) $estateFilter);
        }

        $allotmentDate = trim($allotmentDate);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $allotmentDate) === 1) {
            $query->whereDate('estate_possession_other.allotment_date', $allotmentDate);
        }

        $searchValue = trim($searchValue);
        if ($searchValue === '') {
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
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estatePossessionTable')
            // programme-dt chrome (docs/new-design-index-page.md) — no `dom` and no
            // `language` here on purpose: datatable-global-ui.js owns both, and a
            // page-level override would win and break the "Showing N of M items" footer.
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'estate_filter' => '$("#epoEstateFilter").val() || ""',
                'allotment_date_filter' => '$("#epoAllotmentDateFilter").val() || ""',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native (server-side) ordering so a header click
                // re-sorts the WHOLE list instead of just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                // Newest possession first — S. No. maps to estate_possession_other.pk.
                'order' => [[isEstateAuthority() ? 1 : 0, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        $canMutate = isEstateAuthority();

        $columns = [];
        if ($canMutate) {
            $columns[] = Column::computed('checkbox')
                ->title('<div class="pd-check"><input type="checkbox" class="form-check-input" id="selectAllPossessionOthers" aria-label="Select all rows on this page"></div>')
                ->addClass('pd-col-select')
                ->orderable(false)
                ->searchable(false)
                ->width('48px');
        }

        return array_merge($columns, [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('epo-col-sno')->orderable(true)->searchable(false)->width('72px'),
            Column::make('request_id')->title('Request ID')->addClass('epo-col-req')->orderable(true)->searchable(true),
            Column::make('name')->title('Employee Name')->addClass('pd-col-name')->orderable(true)->searchable(true),
            Column::make('section_name')->title('Section Name')->orderable(true)->searchable(true),
            Column::make('estate_name')->title('Estate Name')->orderable(true)->searchable(true),
            Column::make('building_name')->title('Building Name')->orderable(true)->searchable(true),
            Column::make('unit_type')->title('Unit Type')->orderable(true)->searchable(true),
            Column::make('unit_sub_type')->title('Unit Sub Type')->orderable(true)->searchable(true),
            Column::make('house_no')->title('House Number')->orderable(true)->searchable(true),
            Column::make('allotment_date')->title('Allotment Date')->orderable(true)->searchable(false),
            Column::make('possession_date_oth')->title('Possession Date')->orderable(true)->searchable(false),
            Column::make('meter_reading_oth')->title('Last Electric Bill Reading')->orderable(false)->searchable(false)->width('140px'),
            Column::computed('actions')->title('Action')->addClass('pd-col-action')->orderable(false)->searchable(false)->width('120px'),
        ]);
    }

    protected function filename(): string
    {
        return 'EstatePossessionOther_' . date('YmdHis');
    }
}