<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\UnitSubType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitSubTypeController extends Controller
{
    public function index()
    {
        $items = UnitSubType::orderBy('pk', 'desc')->get();
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

        // estate_unit_sub_type_master.pk is not AUTO_INCREMENT in some DBs (e.g. staging dump),
        // so we assign next pk manually to avoid "Field 'pk' doesn't have a default value".
        DB::transaction(function () use ($validated) {
            $nextPk = (int) (DB::table('estate_unit_sub_type_master')->max('pk') ?? 0) + 1;
            UnitSubType::create([
                'pk' => $nextPk,
                'unit_sub_type' => $validated['unit_sub_type'],
            ]);
        });

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
