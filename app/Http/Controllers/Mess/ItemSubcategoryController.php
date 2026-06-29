<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\ItemCategory;
use App\Models\Mess\ItemSubcategory;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
                'mess_item_subcategory_dt:v1:',
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
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 1);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'asc');
        $nameCol = ItemSubcategory::displayNameColumnForQuery();

        switch ($orderCol) {
            case 0:
                $paged->leftJoin('mess_item_categories as isc_sort_cat', $table . '.category_id', '=', 'isc_sort_cat.id')
                    ->orderBy('isc_sort_cat.category_name', $orderDir)
                    ->select($table . '.*');
                break;
            case 1:
                $paged->orderBy($nameCol, $orderDir);
                break;
            case 2:
                if (Schema::hasColumn($table, 'item_code')) {
                    $paged->orderBy('item_code', $orderDir);
                } elseif (Schema::hasColumn($table, 'subcategory_code')) {
                    $paged->orderBy('subcategory_code', $orderDir);
                }
                break;
            case 3:
                $paged->orderBy('unit_measurement', $orderDir);
                break;
            case 4:
                if (Schema::hasColumn($table, 'alert_quantity')) {
                    $paged->orderBy('alert_quantity', $orderDir);
                }
                break;
            case 5:
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

        $data = $rows->map(fn (ItemSubcategory $item) => $this->buildItemSubcategoryDatatableRow($item, $canDelete))->all();

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
    private function buildItemSubcategoryDatatableRow(ItemSubcategory $itemsubcategory, bool $canDelete): array
    {
        $categoryCell = e($itemsubcategory->category ? $itemsubcategory->category->category_name : '-');
        $itemName = e($itemsubcategory->item_name ?? '');
        $itemNameCell = '<div class="fw-semibold">' . $itemName . '</div>';
        $itemCode = e($itemsubcategory->item_code ?? '-');
        $unit = e($itemsubcategory->unit_measurement ?? '-');
        $alertQty = isset($itemsubcategory->alert_quantity) && $itemsubcategory->alert_quantity !== null && $itemsubcategory->alert_quantity !== ''
            ? e(number_format((float) $itemsubcategory->alert_quantity, 2))
            : '-';
        $statusCell = '<span class="badge bg-' . e($itemsubcategory->status_badge_class) . '">'
            . e($itemsubcategory->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-itemsubcategory bg-transparent border-0"'
            . ' data-id="' . (int) $itemsubcategory->id . '"'
            . ' data-category-id="' . e((string) ($itemsubcategory->category_id ?? '')) . '"'
            . ' data-item-name="' . e($itemsubcategory->item_name ?? '') . '"'
            . ' data-item-code="' . e($itemsubcategory->item_code ?? '') . '"'
            . ' data-unit-measurement="' . e($itemsubcategory->unit_measurement ?? '') . '"'
            . ' data-alert-quantity="' . e((string) ($itemsubcategory->alert_quantity ?? '')) . '"'
            . ' data-description="' . e($itemsubcategory->description ?? '') . '"'
            . ' data-status="' . e($itemsubcategory->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this item?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary btn-delete-itemsubcategory bg-transparent border-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
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
        $itemsubcategory = ItemSubcategory::findOrFail(decrypt($id));
        $categories = ItemCategory::active()->orderBy('category_name')->get();

        return view('mess.itemsubcategories.edit', compact('itemsubcategory', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail(decrypt($id));
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
        $itemsubcategory = ItemSubcategory::findOrFail(decrypt($id));
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
