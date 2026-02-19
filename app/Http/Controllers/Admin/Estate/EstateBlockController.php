<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\EstateBlockDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateBlock;
use Illuminate\Http\Request;

class EstateBlockController extends Controller
{
    public function index(EstateBlockDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.define_block_building.index');
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_block_building.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_name' => 'required|string|max:255',
        ]);
        EstateBlock::create($validated);
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building added successfully.');
    }

    public function edit(string $id)
    {
        $item = EstateBlock::findOrFail($id);
        return view('admin.estate.define_block_building.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = EstateBlock::findOrFail($id);
        $validated = $request->validate([
            'block_name' => 'required|string|max:255',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EstateBlock::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Estate block/building deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building deleted successfully.');
    }
}
