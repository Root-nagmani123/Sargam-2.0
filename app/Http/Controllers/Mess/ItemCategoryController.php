<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Mess\ItemCategory;

class ItemCategoryController extends Controller
{
    public function index()
    {
        $itemcategories = ItemCategory::orderByDesc('id')->get();
        return view('mess.itemcategories.index', compact('itemcategories'));
    }

    public function create()
    {
        return view('mess.itemcategories.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        ItemCategory::create($data);

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
        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category updated successfully');
    }

    public function destroy($id)
    {
        $itemcategory = ItemCategory::findOrFail($id);
        $itemcategory->delete();
        return redirect()->route('admin.mess.itemcategories.index')->with('success', 'Item Category deleted successfully');
    }

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
                $itemcategory
                    ? Rule::unique('mess_item_categories', 'category_name')->ignore($itemcategory->id)
                    : Rule::unique('mess_item_categories', 'category_name'),
            ],
            'category_type' => ['nullable', 'string', 'in:raw_material,finished_good,consumable,equipment'],
            'description'   => ['nullable', 'string'],
            'status'        => ['nullable', 'in:active,inactive'],
        ];

        $validated = $request->validate($rules);

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
}
