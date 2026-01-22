<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ItemCategory;

class ItemSubcategoryController extends Controller
{
    public function index()
    {
        $itemsubcategories = ItemSubcategory::with('category')->get();
        return view('mess.itemsubcategories.index', compact('itemsubcategories'));
    }

    public function create()
    {
        $categories = ItemCategory::all();
        return view('mess.itemsubcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category_id' => 'required|exists:mess_item_categories,id',
            'description' => 'nullable',
        ]);
        ItemSubcategory::create($request->all());
        return redirect()->route('mess.itemsubcategories.index')->with('success', 'Subcategory added successfully');
    }
}
