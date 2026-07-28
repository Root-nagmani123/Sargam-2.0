<?php

namespace App\Http\Controllers\Mess;

use App\Exports\SubStoreMasterExport;
use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use App\Models\Mess\SubStore;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class SubStoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_sub_store_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'SubStoreController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_sub_store_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $subStores = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_SUB_STORE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_SUB_STORE_MASTER_LIST_CACHE_SECONDS',
            ],
            'SubStoreController@index',
            fn () => SubStore::orderByDesc('id')->get()
        );

        return view('mess.sub-stores.index', compact('subStores'));
    }

    public function create()
    {
        return view('mess.sub-stores.create');
    }

    /**
     * Branded Sub Store Master report — Print (inline PDF) and Download (styled .xlsx).
     * Both carry the official LBSNAA header. See {@see \App\Exports\SubStoreMasterExport}.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));

        $export = new SubStoreMasterExport($search, $visibleColumns);
        $fileName = 'sub-store-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.sub-stores.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => ($search !== null && trim((string) $search) !== '') ? ('Applied Filters:   Search: ' . trim($search)) : '',
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Sub Store Master',
            ], $this->buildExportHeaderData()))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                ]);

            return $request->boolean('inline')
                ? $pdf->stream($fileName . '.pdf')
                : $pdf->download($fileName . '.pdf');
        }

        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    /**
     * `columns=0,1,2` → clean list of exportable data-column indexes (0..2).
     * Action (3) is never exported.
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
            static fn ($v) => $v >= 0 && $v <= 2
        )));

        return $cols !== [] ? $cols : null;
    }

    /**
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

        SubStore::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store added successfully');
    }

    public function edit($id)
    {
        $subStore = SubStore::findOrFail($id);
        return view('mess.sub-stores.edit', compact('subStore'));
    }

    public function update(Request $request, $id)
    {
        $subStore = SubStore::findOrFail($id);
        $data = $this->validatedData($request, $subStore);

        $subStore->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store updated successfully');
    }

    public function destroy($id)
    {
        $subStore = SubStore::findOrFail($id);
        $subStore->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?SubStore $subStore = null): array
    {
        $validated = $request->validate([
            'sub_store_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pN\s\-]+$/u'],
            'status'         => ['nullable', 'in:active,inactive'],
        ], [
            'sub_store_name.regex' => 'Sub Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? SubStore::STATUS_ACTIVE;

        return [
            'sub_store_name' => $validated['sub_store_name'],
            'status'         => $status,
        ];
    }
}
