<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\EstateElectricSlabDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateElectricSlab;
use App\Models\UnitType;
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
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        return view('admin.estate.define_electric_slab.form', compact('item', 'unitTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_unit_range' => 'required|integer|min:0',
            'end_unit_range' => 'required|integer|min:0|gte:start_unit_range',
            'rate_per_unit' => 'required|numeric|min:0',
            'estate_unit_type_master_pk' => 'nullable|integer|exists:estate_unit_type_master,pk',
        ]);
        $validated['estate_unit_type_master_pk'] = $request->filled('estate_unit_type_master_pk') ? (int) $request->input('estate_unit_type_master_pk') : null;
        EstateElectricSlab::create($validated);
        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', 'Electric slab added successfully.');
    }

    public function edit(string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        return view('admin.estate.define_electric_slab.form', compact('item', 'unitTypes'));
    }

    public function update(Request $request, string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);
        $validated = $request->validate([
            'start_unit_range' => 'required|integer|min:0',
            'end_unit_range' => 'required|integer|min:0|gte:start_unit_range',
            'rate_per_unit' => 'required|numeric|min:0',
            'estate_unit_type_master_pk' => 'nullable|integer|exists:estate_unit_type_master,pk',
        ]);
        $validated['estate_unit_type_master_pk'] = $request->filled('estate_unit_type_master_pk') ? (int) $request->input('estate_unit_type_master_pk') : null;
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
