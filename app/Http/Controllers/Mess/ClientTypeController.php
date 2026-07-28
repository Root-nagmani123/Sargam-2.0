<?php

namespace App\Http\Controllers\Mess;

use App\Exports\ClientTypeMasterExport;
use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Mess\ClientType;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientTypeController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_client_type_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ClientTypeController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_client_type_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $clientTypes = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_CLIENT_TYPE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_CLIENT_TYPE_MASTER_LIST_CACHE_SECONDS',
            ],
            'ClientTypeController@index',
            fn () => ClientType::orderByDesc('id')->get()
        );

        return view('mess.client-types.index', compact('clientTypes'));
    }

    public function create()
    {
        return view('mess.client-types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        ClientType::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type added successfully');
    }

    public function edit($id)
    {
        $clientType = ClientType::findOrFail($id);
        return view('mess.client-types.edit', compact('clientType'));
    }

    public function update(Request $request, $id)
    {
        $clientType = ClientType::findOrFail($id);
        $data = $this->validatedData($request, $clientType);

        $clientType->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type updated successfully');
    }

    public function destroy($id)
    {
        $clientType = ClientType::findOrFail($id);
        $clientType->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const CLIENT_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?ClientType $clientType = null): array
    {
        $clientNameRules = [
            'required',
            'string',
            'max:255',
            'regex:' . self::CLIENT_NAME_PATTERN,
            Rule::unique('mess_client_types', 'client_name'),
        ];
        if ($clientType !== null) {
            $clientNameRules[3] = Rule::unique('mess_client_types', 'client_name')->ignore($clientType->id);
        }

        $validated = $request->validate([
            'client_type' => ['required', 'string', 'in:employee,ot,course,section,other'],
            'client_name' => $clientNameRules,
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:active,inactive'],
        ], [
            'client_name.regex' => 'Client name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? ClientType::STATUS_ACTIVE;

        $data = [
            'client_type' => $validated['client_type'],
            'client_name' => $validated['client_name'],
            'status'      => $status,
        ];

        // Only persist description if the column exists (added by a later migration).
        if (Schema::hasColumn('mess_client_types', 'description')) {
            $data['description'] = $validated['description'] ?? null;
        }

        return $data;
    }

    /**
     * Branded Client Master report — Print (inline PDF) and Download (styled .xlsx).
     * Both carry the official LBSNAA header. See {@see \App\Exports\ClientTypeMasterExport}.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));

        $export = new ClientTypeMasterExport($search, $visibleColumns);
        $fileName = 'client-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.client-types.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => ($search !== null && trim((string) $search) !== '') ? ('Applied Filters:   Search: ' . trim($search)) : '',
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Client Master',
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
     * `columns=0,1,2,…` → clean list of exportable data-column indexes (0..3).
     * Action (4) is never exported.
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
            static fn ($v) => $v >= 0 && $v <= 3
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
}
