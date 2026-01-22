<?php

namespace App\Http\Controllers;

use App\Models\EstateUnitMaster;
use App\Models\EstateCampusMaster;
use App\Models\EstateAreaMaster;
use App\Models\EstateBlockMaster;
use App\Models\EstateUnitTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateUnitController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateUnitMaster::with(['campus', 'area', 'block', 'unitType'])->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('campus', function($row){
                    return $row->campus->campus_name ?? 'N/A';
                })
                ->addColumn('area', function($row){
                    return $row->area->area_name ?? 'N/A';
                })
                ->addColumn('block', function($row){
                    return $row->block->block_name ?? 'N/A';
                })
                ->addColumn('unit_type', function($row){
                    return $row->unitType->unit_type ?? 'N/A';
                })
                ->addColumn('status', function($row){
                    return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.unit.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('estate.unit.index');
    }

    public function create()
    {
        $campuses = EstateCampusMaster::all();
        $unitTypes = EstateUnitTypeMaster::all();
        
        return view('estate.unit.create', compact('campuses', 'unitTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|exists:estate_campus_master,pk',
            'estate_area_master_pk' => 'required|exists:estate_area_master,pk',
            'estate_block_master_pk' => 'required|exists:estate_block_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'unit_name' => 'required|string|max:255',
            'house_address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:1',
            'estate_value' => 'nullable|numeric|min:0',
            'rent' => 'nullable|numeric|min:0',
        ]);

        $validated['is_rent_applicable'] = $request->has('is_rent_applicable') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateUnitMaster::create($validated);

        return redirect()->route('estate.unit.index')
            ->with('success', 'Unit created successfully.');
    }

    public function edit(string $id)
    {
        $unit = EstateUnitMaster::findOrFail($id);
        $campuses = EstateCampusMaster::all();
        $areas = EstateAreaMaster::where('campus_fk', $unit->estate_campus_master_pk)->get();
        $blocks = EstateBlockMaster::where('area_fk', $unit->estate_area_master_pk)->get();
        $unitTypes = EstateUnitTypeMaster::all();
        
        return view('estate.unit.edit', compact('unit', 'campuses', 'areas', 'blocks', 'unitTypes'));
    }

    public function update(Request $request, string $id)
    {
        $unit = EstateUnitMaster::findOrFail($id);
        
        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|exists:estate_campus_master,pk',
            'estate_area_master_pk' => 'required|exists:estate_area_master,pk',
            'estate_block_master_pk' => 'required|exists:estate_block_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'unit_name' => 'required|string|max:255',
            'house_address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:1',
            'estate_value' => 'nullable|numeric|min:0',
            'rent' => 'nullable|numeric|min:0',
        ]);

        $validated['is_rent_applicable'] = $request->has('is_rent_applicable') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;
        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $unit->update($validated);

        return redirect()->route('estate.unit.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(string $id)
    {
        try {
            $unit = EstateUnitMaster::findOrFail($id);
            $unit->delete();
            
            return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting unit: ' . $e->getMessage()], 500);
        }
    }
}
