<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Exports\StoreMasterExport;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mess\Store;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class StoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_store_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_store_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'StoreController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'StoreController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_store_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_STORE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_STORE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'StoreController@index',
                fn () => $this->buildStoreDatatableResponse($request),
                // The cache fingerprint covers DataTables' own params only, so the
                // status pill has to be added by hand — without it Active and
                // Inactive would share a cache entry and serve each other's rows.
                ['status' => $this->resolveStatusFilter($request)]
            );
        }

        return view('mess.stores.index');
    }

    /**
     * Status values the pill row may ask for; anything else means "all".
     */
    private function resolveStatusFilter(Request $request): ?string
    {
        $status = strtolower(trim((string) $request->query('status', '')));

        return in_array($status, [Store::STATUS_ACTIVE, Store::STATUS_INACTIVE], true) ? $status : null;
    }

    private function storeFilteredQuery(Request $request): Builder
    {
        $query = Store::query();

        // Driven by the status pills above the grid (see mess/stores/index.blade.php).
        $status = $this->resolveStatusFilter($request);
        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query;
    }

    private function buildStoreDatatableResponse(Request $request): JsonResponse
    {
        $query = $this->storeFilteredQuery($request);

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== []) {
            $query->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like) {
                        $inner->where('store_name', 'like', $like)
                            ->orWhere('store_code', 'like', $like)
                            ->orWhere('store_type', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhere('status', 'like', $like);
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy('id', $orderDir);
                break;
            case 1:
                $paged->orderBy('store_name', $orderDir);
                break;
            case 2:
                $paged->orderBy('store_type', $orderDir);
                break;
            case 3:
                $paged->orderBy('location', $orderDir);
                break;
            case 4:
                $paged->orderBy('status', $orderDir);
                break;
            default:
                $paged->orderByDesc('id');
        }

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));
        $storeTypes = Store::storeTypes();

        // Column 0 is a running serial, not the primary key — the same "S. No."
        // the branded export prints. It follows the page, so it always reads 1..n.
        $data = $rows->values()
            ->map(fn (Store $store, int $i) => $this->buildStoreDatatableRow($store, $canDelete, $storeTypes, $start + $i + 1))
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @return string[]
     */
    private function buildStoreDatatableRow(Store $store, bool $canDelete, array $storeTypes, int $serial): array
    {
        // Markup here is the design-system vocabulary the listing's page CSS
        // styles: .store-name-* for the two-line name, .programme-status-badge
        // for status, and .store-action-btn for the icon-over-label row actions
        // (docs/design.md — "Applying the design to a mess-master index page").
        $nameCell = '<div class="store-name-primary">' . e($store->store_name) . '</div>'
            . '<div class="store-name-code">Code: ' . e($store->store_code) . '</div>';
        $typeLabel = $storeTypes[$store->store_type ?? 'mess'] ?? ($store->store_type ?? '-');
        $typeCell = '<span class="text-capitalize">' . e($typeLabel) . '</span>';
        $locationCell = e($store->location ?: '-');

        // `badge` stays: .programme-status-badge only carries the colours and
        // padding, and leans on Bootstrap's .badge for the pill shape.
        $isActive = ($store->status ?? 'active') === 'active';
        // status_label is the raw column value ('active'); the pill reads as a word.
        $statusCell = '<span class="badge programme-status-badge programme-status-badge--'
            . ($isActive ? 'active' : 'inactive') . '">' . e(ucfirst((string) $store->status_label)) . '</span>';

        $editBtn = '<button type="button" class="store-action-btn text-primary btn-edit-store"'
            . ' data-id="' . (int) $store->id . '"'
            . ' data-store-name="' . e($store->store_name) . '"'
            . ' data-store-type="' . e(trim((string) ($store->store_type ?? '')) ?: 'mess') . '"'
            . ' data-location="' . e($store->location ?? '') . '"'
            . ' data-status="' . e($store->status ?? 'active') . '"'
            . ' aria-label="Edit ' . e($store->store_name) . '">'
            . '<i class="material-symbols-rounded" aria-hidden="true">edit</i><span>Edit</span></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.stores.destroy', $store->id);
            // mess-delete-form + no native confirm(): mess.partials.delete-confirm
            // intercepts the submit and shows the branded dialog instead.
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline mess-delete-form"'
                . ' data-confirm-title="Delete Store?"'
                . ' data-confirm-message="' . e('“' . $store->store_name . '” will be removed. This action cannot be undone.') . '">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="store-action-btn text-danger"'
                . ' aria-label="Delete ' . e($store->store_name) . '">'
                . '<i class="material-symbols-rounded" aria-hidden="true">delete</i><span>Delete</span></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex align-items-center store-actions">' . $editBtn . $deleteForm . '</div>';

        return [
            (string) $serial,
            $nameCell,
            $typeCell,
            $locationCell,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        return view('mess.stores.create');
    }

    /**
     * Branded Store Master report — Print (inline PDF) and Download (styled .xlsx).
     * Both carry the official LBSNAA header (emblem, academy line, 75-years logo,
     * blue title band + blue table header) so the two exports stay visually in step.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));
        // The report follows the grid: same search, same status pill, same columns.
        $status = $this->resolveStatusFilter($request);

        $export = new StoreMasterExport($search, $visibleColumns, $status);
        $fileName = 'store-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.stores.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => $export->filterLine(),
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Store Master',
            ], $this->buildExportHeaderData()))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                ]);

            // Print opens the PDF inline (?inline=1) so the browser shows the print
            // preview; a plain request downloads the file.
            return $request->boolean('inline')
                ? $pdf->stream($fileName . '.pdf')
                : $pdf->download($fileName . '.pdf');
        }

        // Styled workbook (logos, blue header band, bordered zebra rows) so the
        // download visually matches the Print / PDF layout — a plain CSV can't.
        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    /**
     * Turn the `columns=0,1,2,…` request param (built from the live DataTable's
     * visible columns) into a clean list of exportable data-column indexes.
     * Only 0..4 are valid — Action (5) is never exported. Returns null when nothing
     * usable is supplied, so the export shows every column.
     *
     * @return array<int,int>|null
     */
    private function parseVisibleColumns($raw): ?array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $cols = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $raw)),
            static fn ($v) => $v >= 0 && $v <= 4
        )));

        return $cols !== [] ? $cols : null;
    }

    /**
     * Branded LBSNAA header assets for the PDF export — the same emblem / Hindi
     * title / 75-years logo used by the official report layout. Store Master has no
     * course context, so the course line is left empty.
     *
     * @return array{logoLeft:?string,logoRight:?string,titleHindi:?string,courseName:string,courseDuration:string}
     */
    private function buildExportHeaderData(): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };

        return [
            'logoLeft' => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight' => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
            'courseName' => '',
            'courseDuration' => '',
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Store::create(array_merge($data, [
            'store_code' => $this->generateStoreCode(),
        ]));

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store added successfully');
    }

    public function edit($id)
    {
        $store = Store::findOrFail($id);
        return view('mess.stores.edit', compact('store'));
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        $data  = $this->validatedData($request, $store);

        $store->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store updated successfully');
    }

    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const STORE_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Regex: letters, numbers, spaces, hyphen, comma, period (no special characters). Empty allowed for nullable.
     */
    protected const LOCATION_PATTERN = '/^[\pL\pN\s\-\.\,]*$/u';

    protected function validatedData(Request $request, ?Store $store = null): array
    {
        $validated = $request->validate([
            'store_name' => [
                'required',
                'string',
                'max:255',
                'regex:' . self::STORE_NAME_PATTERN,
            ],
            'store_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Store::storeTypes()))],
            'location'   => [
                'nullable',
                'string',
                'max:255',
                'regex:' . self::LOCATION_PATTERN,
            ],
            'status'     => ['nullable', 'in:active,inactive'],
        ], [
            'store_name.regex' => 'Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
            'location.regex'   => 'Location may only contain letters, numbers, spaces, hyphens, commas and periods. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? Store::STATUS_ACTIVE;
        $storeType = $validated['store_type'] ?? Store::TYPE_MESS;

        return [
            'store_name' => $validated['store_name'],
            'store_type' => $storeType,
            'location'   => $validated['location'] ?? null,
            'status'     => $status,
        ];
    }

    /**
     * Generate a unique store code.
     */
    protected function generateStoreCode(): string
    {
        $next = ((int) Store::max('id')) + 1;
        $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        while (Store::where('store_code', $code)->exists()) {
            $next++;
            $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
