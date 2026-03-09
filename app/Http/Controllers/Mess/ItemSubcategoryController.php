<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ItemCategory;

class ItemSubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ItemCategory::active()->orderBy('category_name')->get();

        $query = ItemSubcategory::with('category');
        $categoryIdFilter = $request->get('category_id');
        if ($categoryIdFilter !== null && $categoryIdFilter !== '') {
            $validIds = $categories->pluck('id')->toArray();
            if (in_array((int) $categoryIdFilter, $validIds, true)) {
                $query->where('category_id', (int) $categoryIdFilter);
            }
        }
        $itemsubcategories = $query->orderByDesc('id')->get();

        return view('mess.itemsubcategories.index', compact('itemsubcategories', 'categories', 'categoryIdFilter'));
    }

    public function create()
    {
        $categories = ItemCategory::active()->orderBy('category_name')->get();
        return view('mess.itemsubcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        // Item Code is mandatory and auto-generated
        $itemCode = $this->generateItemCode();
        if (Schema::hasColumn('mess_item_subcategories', 'item_code')) {
            $data['item_code'] = $itemCode;
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            $data['subcategory_code'] = $itemCode;
        }

        ItemSubcategory::create($data);

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
        // Item Code is mandatory and must not be changed on update
        unset($data['item_code']);
        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            unset($data['subcategory_code']);
        }

        $itemsubcategory->update($data);
        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item updated successfully');
    }

    public function destroy($id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $itemsubcategory->delete();
        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const ITEM_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Regex: letters, numbers, spaces, hyphen, slash, period only.
     */
    protected const UNIT_MEASUREMENT_PATTERN = '/^[\pL\pN\s\-\/\.]+$/u';

    /**
     * Build an array of validated attributes for create/update.
     */
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

        // Handle item_name - save to the correct column
        if (Schema::hasColumn('mess_item_subcategories', 'item_name')) {
            $data['item_name'] = $validated['item_name'];
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_name')) {
            $data['subcategory_name'] = $validated['item_name'];
        } elseif (Schema::hasColumn('mess_item_subcategories', 'name')) {
            $data['name'] = $validated['item_name'];
        }

        // Only add unit_measurement if the column exists
        if (Schema::hasColumn('mess_item_subcategories', 'unit_measurement')) {
            $data['unit_measurement'] = $validated['unit_measurement'];
        }

        // Only add alert_quantity if the column exists
        if (Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
            $data['alert_quantity'] = isset($validated['alert_quantity']) && $validated['alert_quantity'] !== '' && $validated['alert_quantity'] !== null
                ? $validated['alert_quantity'] : null;
        }

        // Only add status if the column exists
        if (Schema::hasColumn('mess_item_subcategories', 'status')) {
            $data['status'] = $status;
        }

        return $data;
    }

    /**
     * Generate a unique item code.
     */
    protected function generateItemCode(): string
    {
        $next = ((int) ItemSubcategory::max('id')) + 1;
        
        // Check which code column exists
        $hasItemCode = Schema::hasColumn('mess_item_subcategories', 'item_code');
        $hasSubcategoryCode = Schema::hasColumn('mess_item_subcategories', 'subcategory_code');
        
        $code = 'ITEM/' . $next . '/CODE';

        // Check for uniqueness based on which column exists
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
