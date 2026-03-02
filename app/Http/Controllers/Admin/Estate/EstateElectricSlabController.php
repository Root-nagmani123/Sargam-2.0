<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\EstateElectricSlabDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateElectricSlab;
use Illuminate\Http\Request;

class EstateElectricSlabController extends Controller
{
    public function index(EstateElectricSlabDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.define_electric_slab.index');
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_electric_slab.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_unit_range' => 'required|integer|min:0',
            'end_unit_range' => 'required|integer|min:0|gte:start_unit_range',
            'rate_per_unit' => 'required|numeric|min:0',
            'house' => 'nullable|in:0,1',
        ]);
        $validated['house'] = (int) $request->input('house', 0);
        EstateElectricSlab::create($validated);
        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', 'Electric slab added successfully.');
    }

    public function edit(string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);
        return view('admin.estate.define_electric_slab.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);
        $validated = $request->validate([
            'start_unit_range' => 'required|integer|min:0',
            'end_unit_range' => 'required|integer|min:0|gte:start_unit_range',
            'rate_per_unit' => 'required|numeric|min:0',
            'house' => 'nullable|in:0,1',
        ]);
        $validated['house'] = (int) $request->input('house', 0);
        $item->update($validated);
        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', 'Electric slab updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EstateElectricSlab::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Electric slab deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', 'Electric slab deleted successfully.');
    }
}
