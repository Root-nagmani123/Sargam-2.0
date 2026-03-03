<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\UnitSubType;
use Illuminate\Http\Request;

class UnitSubTypeController extends Controller
{
    public function index()
    {
        $items = UnitSubType::orderBy('unit_sub_type')->paginate(request('per_page', 10));
        return view('admin.estate.define_unit_sub_type.index', compact('items'));
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_unit_sub_type.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_sub_type' => 'required|string|max:255',
        ]);
        UnitSubType::create($validated);
        return redirect()->route('admin.estate.define-unit-sub-type.index')->with('success', 'Unit sub type added successfully.');
    }

    public function edit(string $id)
    {
        $item = UnitSubType::findOrFail($id);
        return view('admin.estate.define_unit_sub_type.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = UnitSubType::findOrFail($id);
        $validated = $request->validate([
            'unit_sub_type' => 'required|string|max:255',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-unit-sub-type.index')->with('success', 'Unit sub type updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        UnitSubType::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit sub type deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-unit-sub-type.index')->with('success', 'Unit sub type deleted successfully.');
    }
}
