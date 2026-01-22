<?php

namespace App\Http\Controllers;

use App\Models\EstateUnitTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateUnitTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateUnitTypeMaster::query();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.unit-type.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('estate.unit-type.index');
    }

    public function create()
    {
        return view('estate.unit-type.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_type' => 'required|string|max:255|unique:estate_unit_type_master,unit_type',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateUnitTypeMaster::create($validated);

        return redirect()->route('estate.unit-type.index')
            ->with('success', 'Unit Type created successfully.');
    }

    public function edit(string $id)
    {
        $unitType = EstateUnitTypeMaster::findOrFail($id);
        return view('estate.unit-type.edit', compact('unitType'));
    }

    public function update(Request $request, string $id)
    {
        $unitType = EstateUnitTypeMaster::findOrFail($id);
        
        $validated = $request->validate([
            'unit_type' => 'required|string|max:255|unique:estate_unit_type_master,unit_type,'.$id.',pk',
            'description' => 'nullable|string',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $unitType->update($validated);

        return redirect()->route('estate.unit-type.index')
            ->with('success', 'Unit Type updated successfully.');
    }

    public function destroy(string $id)
    {
        try {
            $unitType = EstateUnitTypeMaster::findOrFail($id);
            $unitType->delete();
            
            return response()->json(['success' => true, 'message' => 'Unit Type deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting unit type: ' . $e->getMessage()], 500);
        }
    }
}
