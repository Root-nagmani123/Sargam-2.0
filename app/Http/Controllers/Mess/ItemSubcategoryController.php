<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ItemCategory;

class ItemSubcategoryController extends Controller
{
    public function index()
    {
        $itemsubcategories = ItemSubcategory::with('category')->orderByDesc('id')->get();
        $categories = ItemCategory::orderBy('category_name')->get();
        return view('mess.itemsubcategories.index', compact('itemsubcategories', 'categories'));
    }

    public function create()
    {
        $categories = ItemCategory::orderBy('category_name')->get();
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

        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item Subcategory added successfully');
    }

    public function edit($id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $categories = ItemCategory::orderBy('category_name')->get();
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
        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item Subcategory updated successfully');
    }

    public function destroy($id)
    {
        $itemsubcategory = ItemSubcategory::findOrFail($id);
        $itemsubcategory->delete();
        return redirect()->route('admin.mess.itemsubcategories.index')->with('success', 'Item Subcategory deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?ItemSubcategory $itemsubcategory = null): array
    {
        $validated = $request->validate([
            'category_id'      => ['required', 'exists:mess_item_categories,id'],
            'item_name'        => ['required', 'string', 'max:255'],
            'unit_measurement' => ['nullable', 'string', 'max:50'],
            'standard_cost'   => ['nullable', 'numeric', 'min:0'],
            'description'     => ['nullable', 'string'],
            'status'          => ['nullable', 'in:active,inactive'],
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
            $data['unit_measurement'] = $validated['unit_measurement'] ?? null;
        }

        // Only add standard_cost if the column exists
        if (Schema::hasColumn('mess_item_subcategories', 'standard_cost')) {
            $data['standard_cost'] = $validated['standard_cost'] ?? null;
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
        
        $code = 'ITM' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        // Check for uniqueness based on which column exists
        if ($hasItemCode) {
            while (ItemSubcategory::where('item_code', $code)->exists()) {
                $next++;
                $code = 'ITM' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        } elseif ($hasSubcategoryCode) {
            while (ItemSubcategory::where('subcategory_code', $code)->exists()) {
                $next++;
                $code = 'ITM' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        }

        return $code;
    }
}
