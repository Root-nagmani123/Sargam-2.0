<?php
namespace App\Http\Controllers\Mess;

use App\Exports\ItemSubcategoryMasterExport;
use App\Http\Controllers\Controller;
use App\Models\Mess\ItemCategory;
use App\Models\Mess\ItemSubcategory;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class ItemSubcategoryController extends Controller
{
    private const DT_LIST_EPOCH_KEY = 'mess_item_subcategory_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'ItemSubcategoryController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_item_subcategory_dt:v2:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_ITEM_SUBCATEGORY_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_ITEM_SUBCATEGORY_DATATABLE_CACHE_SECONDS',
                ],
                'ItemSubcategoryController@index',
                fn () => $this->buildItemSubcategoryDatatableResponse($request),
                $this->itemSubcategoryDatatableFilterFingerprint($request)
            );
        }

        $categories = ItemCategory::active()->orderBy('category_name')->get();
        $categoryIdFilter = $request->get('category_id');

        return view('mess.itemsubcategories.index', compact('categories', 'categoryIdFilter'));
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSubcategoryDatatableFilterFingerprint(Request $request): array
    {
        return [
            'category_id' => $request->get('category_id'),
            'can_delete' => function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin')),
        ];
    }

    private function itemSubcategoryFilteredQuery(Request $request): Builder
    {
        $query = ItemSubcategory::query()->with('category');

        $categoryIdFilter = $request->get('category_id');
        if ($categoryIdFilter !== null && $categoryIdFilter !== '') {
            $validIds = ItemCategory::active()->pluck('id')->all();
            if (in_array((int) $categoryIdFilter, array_map('intval', $validIds), true)) {
                $query->where('category_id', (int) $categoryIdFilter);
            }
        }

        return $query;
    }

    private function buildItemSubcategoryDatatableResponse(Request $request): JsonResponse
    {
        $query = $this->itemSubcategoryFilteredQuery($request);

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== []) {
            $nameCol = ItemSubcategory::displayNameColumnForQuery();
            $query->where(function ($q) use ($searchTokens, $nameCol) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like, $nameCol) {
                        $inner->where($nameCol, 'like', $like)
                            ->orWhere('unit_measurement', 'like', $like)
                            ->orWhere('status', 'like', $like)
                            ->orWhereHas('category', function ($cat) use ($like) {
                                $cat->where('category_name', 'like', $like);
                            });
                        if (Schema::hasColumn('mess_item_subcategories', 'item_code')) {
                            $inner->orWhere('item_code', 'like', $like);
                        }
                        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
                            $inner->orWhere('subcategory_code', 'like', $like);
                        }
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $table = (new ItemSubcategory())->getTable();
        // Column 0 is now the (unsortable) S.No; data columns shift by +1.
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 2);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'asc');
        $nameCol = ItemSubcategory::displayNameColumnForQuery();

        switch ($orderCol) {
            case 1:
                $paged->leftJoin('mess_item_categories as isc_sort_cat', $table . '.category_id', '=', 'isc_sort_cat.id')
                    ->orderBy('isc_sort_cat.category_name', $orderDir)
                    ->select($table . '.*');
                break;
            case 2:
                $paged->orderBy($nameCol, $orderDir);
                break;
            case 3:
                if (Schema::hasColumn($table, 'item_code')) {
                    $paged->orderBy('item_code', $orderDir);
                } elseif (Schema::hasColumn($table, 'subcategory_code')) {
                    $paged->orderBy('subcategory_code', $orderDir);
                }
                break;
            case 4:
                $paged->orderBy('unit_measurement', $orderDir);
                break;
            case 5:
                if (Schema::hasColumn($table, 'alert_quantity')) {
                    $paged->orderBy('alert_quantity', $orderDir);
                }
                break;
            case 6:
                if (Schema::hasColumn($table, 'status')) {
                    $paged->orderBy('status', $orderDir);
                }
                break;
            default:
                $paged->orderByDesc($table . '.id');
        }
        $paged->orderByDesc($table . '.id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin'));

        // S.No is the running position within the full filtered set (survives paging).
        $data = $rows->values()
            ->map(fn (ItemSubcategory $item, int $i) => $this->buildItemSubcategoryDatatableRow($item, $canDelete, $start + $i + 1))
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
    private function buildItemSubcategoryDatatableRow(ItemSubcategory $itemsubcategory, bool $canDelete, int $serial): array
    {
        $categoryCell = e($itemsubcategory->category ? $itemsubcategory->category->category_name : '-');
        $itemName = e($itemsubcategory->item_name ?? '');
        $itemNameCell = '<div class="itemsub-name-primary">' . $itemName . '</div>';
        $itemCode = e($itemsubcategory->item_code ?? '-');
        $unit = e($itemsubcategory->unit_measurement ?? '-');
        $alertQty = isset($itemsubcategory->alert_quantity) && $itemsubcategory->alert_quantity !== null && $itemsubcategory->alert_quantity !== ''
            ? e(number_format((float) $itemsubcategory->alert_quantity, 2))
            : '-';
        $isActive = ($itemsubcategory->status ?? 'active') === ItemSubcategory::STATUS_ACTIVE;
        $statusCell = '<span class="badge programme-status-badge programme-status-badge--' . ($isActive ? 'active' : 'inactive') . '">'
            . e(ucfirst((string) $itemsubcategory->status_label)) . '</span>';

        $editBtn = '<button type="button" class="itemsub-action-btn btn-edit-itemsubcategory text-primary"'
            . ' data-id="' . (int) $itemsubcategory->id . '"'
            . ' data-category-id="' . e((string) ($itemsubcategory->category_id ?? '')) . '"'
            . ' data-item-name="' . e($itemsubcategory->item_name ?? '') . '"'
            . ' data-item-code="' . e($itemsubcategory->item_code ?? '') . '"'
            . ' data-unit-measurement="' . e($itemsubcategory->unit_measurement ?? '') . '"'
            . ' data-alert-quantity="' . e((string) ($itemsubcategory->alert_quantity ?? '')) . '"'
            . ' data-description="' . e($itemsubcategory->description ?? '') . '"'
            . ' data-status="' . e($itemsubcategory->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-symbols-rounded">edit</i><span>Edit</span></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="mess-delete-form"'
                . ' data-confirm-title="Delete Sub-Category Item?"'
                . ' data-confirm-message="Are you sure you want to delete this item?">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="itemsub-action-btn text-danger" title="Delete">'
                . '<i class="material-symbols-rounded">delete</i><span>Delete</span></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex align-items-start justify-content-start itemsub-actions">' . $editBtn . $deleteForm . '</div>';

        return [
            (string) $serial,
            $categoryCell,
            $itemNameCell,
            $itemCode,
            $unit,
            $alertQty,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        $categories = ItemCategory::active()->orderBy('category_name')->get();

        return view('mess.itemsubcategories.create', compact('categories'));
    }

    /**
     * Branded Sub-Category Item Master report — Print (inline PDF) and Download
     * (styled .xlsx). Both carry the official LBSNAA header and respect the Category
     * filter + search. See {@see \App\Exports\ItemSubcategoryMasterExport}.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));

        $export = new ItemSubcategoryMasterExport($search, $categoryId, $visibleColumns);
        $fileName = 'sub-category-item-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.itemsubcategories.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => $this->buildExportFilterLine($request),
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Sub-Category Item Master',
            ], $this->buildExportHeaderData()))
                ->setPaper('a4', 'landscape')
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
     * Turn the `columns=0,1,2,…` request param into a clean list of exportable
     * data-column indexes. Only 0..6 are valid — Action (7) is never exported.
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
            static fn ($v) => $v >= 0 && $v <= 6
        )));

        return $cols !== [] ? $cols : null;
    }

    /** "Applied Filters: …" line for the PDF header, or '' when unfiltered. */
    private function buildExportFilterLine(Request $request): string
    {
        $parts = [];
        $categoryId = $request->get('category_id');
        if ($categoryId !== null && trim((string) $categoryId) !== '') {
            $cat = ItemCategory::find((int) $categoryId);
            if ($cat) {
                $parts[] = 'Category: ' . $cat->category_name;
            }
        }
        $search = $request->get('search');
        if ($search !== null && trim((string) $search) !== '') {
            $parts[] = 'Search: ' . trim($search);
        }

        return $parts === [] ? '' : 'Applied Filters:   ' . implode('   |   ', $parts);
    }

    /**
     * Branded LBSNAA header assets for the PDF export — emblem / Hindi title /
     * 75-years logo used by the official report layout.
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

        $itemCode = $this->generateItemCode();
        if (Schema::hasColumn('mess_item_subcategories', 'item_code')) {
            $data['item_code'] = $itemCode;
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            $data['subcategory_code'] = $itemCode;
        }

        ItemSubcategory::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item added successfully');
    }

    public function edit($id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $categories = ItemCategory::active()->orderBy('category_name')->get();

        return view('mess.itemsubcategories.edit', compact('itemsubcategory', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $data = $this->validatedData($request, $itemsubcategory);
        unset($data['item_code']);
        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            unset($data['subcategory_code']);
        }

        $itemsubcategory->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item updated successfully');
    }

    public function destroy($id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $itemsubcategory->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item deleted successfully');
    }

    protected const ITEM_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    protected const UNIT_MEASUREMENT_PATTERN = '/^[\pL\pN\s\-\/\.]+$/u';

    protected function validatedData(Request $request, ?ItemSubcategory $itemsubcategory = null): array
    {
        $validated = $request->validate([
            'category_id'      => ['required', 'exists:mess_item_categories,id'],
            'item_name'        => ['required', 'string', 'max:255', 'regex:' . self::ITEM_NAME_PATTERN],
            'unit_measurement' => ['required', 'string', 'max:50', 'regex:' . self::UNIT_MEASUREMENT_PATTERN],
            'alert_quantity'   => ['nullable', 'numeric', 'min:0'],
            'description'      => ['nullable', 'string'],
            'status'           => ['nullable', 'in:active,inactive'],
        ], [
            'item_name.regex'        => 'Item name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
            'unit_measurement.regex' => 'Unit measurement may only contain letters, numbers, spaces, hyphens, slashes and periods. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? ItemSubcategory::STATUS_ACTIVE;

        $data = [
            'category_id' => $validated['category_id'],
            'description' => $validated['description'] ?? null,
        ];

        if (Schema::hasColumn('mess_item_subcategories', 'item_name')) {
            $data['item_name'] = $validated['item_name'];
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_name')) {
            $data['subcategory_name'] = $validated['item_name'];
        } elseif (Schema::hasColumn('mess_item_subcategories', 'name')) {
            $data['name'] = $validated['item_name'];
        }

        if (Schema::hasColumn('mess_item_subcategories', 'unit_measurement')) {
            $data['unit_measurement'] = $validated['unit_measurement'];
        }

        if (Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
            $data['alert_quantity'] = isset($validated['alert_quantity']) && $validated['alert_quantity'] !== '' && $validated['alert_quantity'] !== null
                ? $validated['alert_quantity'] : null;
        }

        if (Schema::hasColumn('mess_item_subcategories', 'status')) {
            $data['status'] = $status;
        }

        return $data;
    }

    protected function generateItemCode(): string
    {
        $next = ((int) ItemSubcategory::max('id')) + 1;

        $hasItemCode = Schema::hasColumn('mess_item_subcategories', 'item_code');
        $hasSubcategoryCode = Schema::hasColumn('mess_item_subcategories', 'subcategory_code');

        $code = 'ITEM/' . $next . '/CODE';

        if ($hasItemCode) {
            while (ItemSubcategory::where('item_code', $code)->exists()) {
                $next++;
                $code = 'ITEM/' . $next . '/CODE';
            }
        } elseif ($hasSubcategoryCode) {
            while (ItemSubcategory::where('subcategory_code', $code)->exists()) {
                $next++;
                $code = 'ITEM/' . $next . '/CODE';
            }
        }

        return $code;
    }
}
