<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Mess\ItemCategory;

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

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        ItemCategory::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category added successfully');
    }

    public function edit($id)
    {
        $itemcategory = ItemCategory::findOrFail(decrypt($id));
        return view('mess.itemcategories.edit', compact('itemcategory'));
    }

    public function update(Request $request, $id)
    {
        $itemcategory = ItemCategory::findOrFail(decrypt($id));
        $data = $this->validatedData($request, $itemcategory);

        $itemcategory->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category updated successfully');
    }

    public function destroy($id)
    {
        abort_if(! $this->canDeleteItemCategory(), 403, 'You are not authorized to delete item categories.');

        $itemcategory = ItemCategory::findOrFail(decrypt($id));
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
