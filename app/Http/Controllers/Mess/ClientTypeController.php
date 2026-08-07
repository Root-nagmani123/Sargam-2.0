<?php

namespace App\Http\Controllers\Mess;

use App\Exports\ClientTypeMasterExport;
use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\JsonResponse;
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
    private const DT_LIST_EPOCH_KEY = 'mess_client_type_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ClientTypeController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'ClientTypeController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_client_type_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_CLIENT_TYPE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_CLIENT_TYPE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'ClientTypeController@index',
                fn () => $this->buildClientTypeDatatableResponse($request)
            );
        }

        return view('mess.client-types.index');
    }

    private function buildClientTypeDatatableResponse(Request $request): JsonResponse
    {
        $query = ClientType::query();

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
                        $inner->where('client_type', 'like', $like)
                            ->orWhere('client_name', 'like', $like)
                            ->orWhere('status', 'like', $like);
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'asc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy('client_type', $orderDir);
                break;
            case 1:
                $paged->orderBy('client_name', $orderDir);
                break;
            case 2:
                $paged->orderBy('status', $orderDir);
                break;
            default:
                $paged->orderByDesc('id');
        }
        $paged->orderByDesc('id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));
        $clientTypeOptions = ClientType::clientTypes();

        $data = $rows->map(fn (ClientType $clientType) => $this->buildClientTypeDatatableRow($clientType, $canDelete, $clientTypeOptions))->all();

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
    private function buildClientTypeDatatableRow(ClientType $clientType, bool $canDelete, array $clientTypeOptions): array
    {
        $typeLabel = $clientTypeOptions[$clientType->client_type] ?? $clientType->client_type;
        $typeCell = '<div class="fw-semibold">' . e($typeLabel) . '</div>';
        $nameCell = '<div class="fw-semibold">' . e($clientType->client_name) . '</div>';
        $statusCell = '<span class="badge bg-' . e($clientType->status_badge_class) . '">'
            . e($clientType->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-clienttype bg-transparent border-0"'
            . ' data-id="' . (int) $clientType->id . '"'
            . ' data-client-type="' . e($clientType->client_type) . '"'
            . ' data-client-name="' . e($clientType->client_name) . '"'
            . ' data-status="' . e($clientType->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.client-types.destroy', $clientType->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this client type?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            $typeCell,
            $nameCell,
            $statusCell,
            $actions,
        ];
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
