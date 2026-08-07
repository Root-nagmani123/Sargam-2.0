<?php

namespace App\Http\Controllers\Mess;

use App\Exports\SubStoreMasterExport;
use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mess\SubStore;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class SubStoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_sub_store_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_sub_store_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'SubStoreController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'SubStoreController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_sub_store_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_SUB_STORE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_SUB_STORE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'SubStoreController@index',
                fn () => $this->buildSubStoreDatatableResponse($request)
            );
        }

        return view('mess.sub-stores.index');
    }

    private function buildSubStoreDatatableResponse(Request $request): JsonResponse
    {
        $query = SubStore::query();

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
                        $inner->where('sub_store_name', 'like', $like)
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
                $paged->orderBy('sub_store_name', $orderDir);
                break;
            case 1:
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

        $data = $rows->map(fn (SubStore $subStore) => $this->buildSubStoreDatatableRow($subStore, $canDelete))->all();

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
    private function buildSubStoreDatatableRow(SubStore $subStore, bool $canDelete): array
    {
        $nameCell = '<div class="fw-semibold">' . e($subStore->sub_store_name) . '</div>';
        $statusCell = '<span class="badge bg-' . e($subStore->status_badge_class) . '">'
            . e($subStore->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-substore bg-transparent border-0"'
            . ' data-id="' . (int) $subStore->id . '"'
            . ' data-sub-store-name="' . e($subStore->sub_store_name) . '"'
            . ' data-status="' . e($subStore->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.sub-stores.destroy', $subStore->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this sub store?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            $nameCell,
            $statusCell,
            $actions,
        ];
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
