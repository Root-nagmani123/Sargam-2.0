<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Exports\ItemCategoryMasterExport;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Mess\ItemCategory;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class ItemCategoryController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_item_category_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ItemCategoryController');
        ItemSubcategoryController::bumpListCacheEpoch();
    }

    public function index(Request $request)
    {
        $categoryTypeFilter = $request->get('category_type');
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_item_category_master_list:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'category_type' => $categoryTypeFilter,
        ]));

        $itemcategories = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_ITEM_CATEGORY_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_ITEM_CATEGORY_MASTER_LIST_CACHE_SECONDS',
            ],
            'ItemCategoryController@index',
            fn () => $this->loadItemCategoriesForIndex($categoryTypeFilter)
        );

        return view('mess.itemcategories.index', compact('itemcategories', 'categoryTypeFilter'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ItemCategory>
     */
    private function loadItemCategoriesForIndex(mixed $categoryTypeFilter)
    {
        $query = ItemCategory::query();

        if ($categoryTypeFilter !== null && $categoryTypeFilter !== '') {
            $validTypes = array_keys(ItemCategory::categoryTypes());
            if (in_array($categoryTypeFilter, $validTypes, true) && Schema::hasColumn('mess_item_categories', 'category_type')) {
                $query->where('category_type', $categoryTypeFilter);
            }
        }

        return $query->orderByDesc('id')->get();
    }

    public function create()
    {
        return view('mess.itemcategories.create');
    }

    /**
     * Branded Category Item Master report — Print (inline PDF) and Download (styled
     * .xlsx). Both carry the official LBSNAA header and respect the Category-type
     * filter + search. See {@see \App\Exports\ItemCategoryMasterExport}.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $categoryType = $request->get('category_type');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));

        $export = new ItemCategoryMasterExport($search, $categoryType, $visibleColumns);
        $fileName = 'category-item-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.itemcategories.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => $this->buildExportFilterLine($request),
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Category Item Master',
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
     * Turn the `columns=0,1,2,…` request param into a clean list of exportable
     * data-column indexes. Only 0..4 are valid — Action (5) is never exported.
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

    /** "Applied Filters: …" line for the PDF header, or '' when unfiltered. */
    private function buildExportFilterLine(Request $request): string
    {
        $parts = [];
        $type = $request->get('category_type');
        if ($type !== null && trim((string) $type) !== '') {
            $types = ItemCategory::categoryTypes();
            $parts[] = 'Type: ' . ($types[$type] ?? ucfirst(str_replace('_', ' ', (string) $type)));
        }
        $search = $request->get('search');
        if ($search !== null && trim((string) $search) !== '') {
            $parts[] = 'Search: ' . trim($search);
        }

        return $parts === [] ? '' : 'Applied Filters:   ' . implode('   |   ', $parts);
    }

    /**
     * Branded LBSNAA header assets for the PDF export — the same emblem / Hindi
     * title / 75-years logo used by the official report layout.
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

        ItemCategory::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category added successfully');
    }

    public function edit($id)
    {
        $itemcategory = ItemCategory::findOrFail($id);
        return view('mess.itemcategories.edit', compact('itemcategory'));
    }

    public function update(Request $request, $id)
    {
        $itemcategory = ItemCategory::findOrFail($id);
        $data = $this->validatedData($request, $itemcategory);

        $itemcategory->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category updated successfully');
    }

    public function destroy($id)
    {
        abort_if(! $this->canDeleteItemCategory(), 403, 'You are not authorized to delete item categories.');

        $itemcategory = ItemCategory::findOrFail($id);
        $itemcategory->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const CATEGORY_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?ItemCategory $itemcategory = null): array
    {
        $rules = [
            'category_name' => [
                'required',
                'string',
                'max:255',
                'regex:' . self::CATEGORY_NAME_PATTERN,
                $itemcategory
                    ? Rule::unique('mess_item_categories', 'category_name')->ignore($itemcategory->id)
                    : Rule::unique('mess_item_categories', 'category_name'),
            ],
            'category_type' => ['nullable', 'string', 'in:raw_material,finished_good,consumable,equipment'],
            'description'   => ['nullable', 'string'],
            'status'        => ['nullable', 'in:active,inactive'],
        ];

        $validated = $request->validate($rules, [
            'category_name.regex' => 'Category name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? ItemCategory::STATUS_ACTIVE;
        $categoryType = $validated['category_type'] ?? ItemCategory::TYPE_RAW_MATERIAL;

        $data = [
            'category_name' => $validated['category_name'],
            'description'   => $validated['description'] ?? null,
        ];

        // Only add category_type if the column exists
        if (Schema::hasColumn('mess_item_categories', 'category_type')) {
            $data['category_type'] = $categoryType;
        }

        // Only add status if the column exists
        if (Schema::hasColumn('mess_item_categories', 'status')) {
            $data['status'] = $status;
        }

        return $data;
    }

    protected function canDeleteItemCategory(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return hasRole('Main Admin')
            || (hasRole('Mess Admin') && strcasecmp((string) $user->name, 'Rohit Aggarwal') === 0);
    }
}
