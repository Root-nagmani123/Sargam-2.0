<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Exports\StoreMasterExport;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use App\Models\Mess\Store;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class StoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_store_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'StoreController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_store_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $stores = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_STORE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_STORE_MASTER_LIST_CACHE_SECONDS',
            ],
            'StoreController@index',
            fn () => Store::orderByDesc('id')->get()
        );

        return view('mess.stores.index', compact('stores'));
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

        $export = new StoreMasterExport($search, $visibleColumns);
        $fileName = 'store-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.stores.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => ($search !== null && trim((string) $search) !== '') ? ('Applied Filters:   Search: ' . trim($search)) : '',
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
