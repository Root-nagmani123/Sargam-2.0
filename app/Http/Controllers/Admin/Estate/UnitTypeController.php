<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    public function index()
    {
        $items = UnitType::orderBy('unit_type')->paginate(request('per_page', 10));
        return view('admin.estate.define_unit_type.index', compact('items'));
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_unit_type.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_type' => 'required|string|max:255',
        ]);
        UnitType::create($validated);
        return redirect()->route('admin.estate.define-unit-type.index')->with('success', 'Unit type added successfully.');
    }

    public function edit(string $id)
    {
        $item = UnitType::findOrFail($id);
        return view('admin.estate.define_unit_type.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = UnitType::findOrFail($id);
        $validated = $request->validate([
            'unit_type' => 'required|string|max:255',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-unit-type.index')->with('success', 'Unit type updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        UnitType::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit type deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-unit-type.index')->with('success', 'Unit type deleted successfully.');
    }
}
