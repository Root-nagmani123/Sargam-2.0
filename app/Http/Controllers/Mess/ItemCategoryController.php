<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Mess\ItemCategory;

class ItemCategoryController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_item_category_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_item_category_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ItemCategoryController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'ItemCategoryController');
        ItemSubcategoryController::bumpListCacheEpoch();
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_item_category_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_ITEM_CATEGORY_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_ITEM_CATEGORY_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'ItemCategoryController@index',
                fn () => $this->buildItemCategoryDatatableResponse($request),
                $this->itemCategoryDatatableFilterFingerprint($request)
            );
        }

        $categoryTypeFilter = $request->get('category_type');

        return view('mess.itemcategories.index', compact('categoryTypeFilter'));
    }

    /**
     * @return array<string, mixed>
     */
    private function itemCategoryDatatableFilterFingerprint(Request $request): array
    {
        return [
            'category_type' => $request->get('category_type'),
            'can_delete' => $this->canDeleteItemCategory(),
        ];
    }

    private function itemCategoryFilteredQuery(Request $request): Builder
    {
        $query = ItemCategory::query();

        $categoryTypeFilter = $request->get('category_type');
        if ($categoryTypeFilter !== null && $categoryTypeFilter !== '') {
            $validTypes = array_keys(ItemCategory::categoryTypes());
            if (in_array($categoryTypeFilter, $validTypes, true) && Schema::hasColumn('mess_item_categories', 'category_type')) {
                $query->where('category_type', $categoryTypeFilter);
            }
        }

        return $query;
    }

    private function buildItemCategoryDatatableResponse(Request $request): JsonResponse
    {
        $query = $this->itemCategoryFilteredQuery($request);

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
                        $inner->where('category_name', 'like', $like)
                            ->orWhere('category_type', 'like', $like)
                            ->orWhere('description', 'like', $like)
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
                $paged->orderBy('category_name', $orderDir);
                break;
            case 1:
                if (Schema::hasColumn('mess_item_categories', 'category_type')) {
                    $paged->orderBy('category_type', $orderDir);
                }
                break;
            case 2:
                $paged->orderBy('description', $orderDir);
                break;
            case 3:
                if (Schema::hasColumn('mess_item_categories', 'status')) {
                    $paged->orderBy('status', $orderDir);
                }
                break;
            default:
                $paged->orderByDesc('id');
        }
        $paged->orderByDesc('id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = $this->canDeleteItemCategory();
        $categoryTypes = ItemCategory::categoryTypes();

        $data = $rows->map(fn (ItemCategory $itemcategory) => $this->buildItemCategoryDatatableRow($itemcategory, $canDelete, $categoryTypes))->all();

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
    private function buildItemCategoryDatatableRow(ItemCategory $itemcategory, bool $canDelete, array $categoryTypes): array
    {
        $nameCell = '<div class="fw-semibold">' . e($itemcategory->category_name) . '</div>';
        $typeLabel = $categoryTypes[$itemcategory->category_type ?? 'raw_material'] ?? ucfirst(str_replace('_', ' ', $itemcategory->category_type ?? ''));
        $typeCell = e($typeLabel);
        $descriptionCell = e($itemcategory->description ?? '-');
        $statusCell = '<span class="badge bg-' . e($itemcategory->status_badge_class) . '">'
            . e($itemcategory->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-itemcategory bg-transparent border-0"'
            . ' data-id="' . (int) $itemcategory->id . '"'
            . ' data-category-name="' . e($itemcategory->category_name) . '"'
            . ' data-category-type="' . e($itemcategory->category_type ?? 'raw_material') . '"'
            . ' data-description="' . e($itemcategory->description ?? '') . '"'
            . ' data-status="' . e($itemcategory->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.itemcategories.destroy', $itemcategory->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this category item?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary btn-delete-itemcategory bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            $nameCell,
            $typeCell,
            $descriptionCell,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        return view('mess.itemcategories.create');
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
