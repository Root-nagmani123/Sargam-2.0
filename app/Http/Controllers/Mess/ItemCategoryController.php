<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\ItemCategory;

class ItemCategoryController extends Controller
{
    public function index()
    {
        $itemcategories = ItemCategory::all();
        return view('mess.itemcategories.index', compact('itemcategories'));
    }

    public function create()
    {
        return view('mess.itemcategories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);
        ItemCategory::create($request->all());
        return redirect()->route('mess.itemcategories.index')->with('success', 'Category added successfully');
    }
}
