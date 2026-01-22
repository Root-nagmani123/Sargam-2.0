<?php

namespace App\Http\Controllers;

use App\Models\EstatePossession;
use App\Models\EstateUnitMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstatePossessionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstatePossession::with(['unit.campus', 'unit.area', 'unit.block', 'unit.unitType', 'employee'])
                ->orderBy('possession_date', 'desc')
                ->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_name', function($row){
                    return $row->employee ? $row->employee->name : 'N/A';
                })
                ->addColumn('unit_name', function($row){
                    return $row->unit ? $row->unit->unit_name : 'N/A';
                })
                ->addColumn('campus', function($row){
                    return $row->unit && $row->unit->campus ? $row->unit->campus->campus_name : 'N/A';
                })
                ->addColumn('area', function($row){
                    return $row->unit && $row->unit->area ? $row->unit->area->area_name : 'N/A';
                })
                ->addColumn('block', function($row){
                    return $row->unit && $row->unit->block ? $row->unit->block->block_name : 'N/A';
                })
                ->addColumn('status', function($row){
                    if ($row->vacation_date) {
                        return '<span class="badge bg-secondary">Vacated</span>';
                    }
                    return '<span class="badge bg-success">Active</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.possession.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <a href="'.route('estate.possession.meter-reading', $row->pk).'" class="btn btn-info btn-sm">Meter Reading</a>';
                    if (!$row->vacation_date) {
                        $btn .= ' <button class="btn btn-warning btn-sm vacate-btn" data-id="'.$row->pk.'">Vacate</button>';
                    }
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('estate.possession.index');
    }

    public function create()
    {
        $units = EstateUnitMaster::where('is_active', 1)
            ->with(['campus', 'area', 'block', 'unitType'])
            ->get();
        $employees = User::active()->get();
        
        return view('estate.possession.create', compact('units', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estate_unit_master_pk' => 'required|exists:estate_unit_master,pk',
            'employee_master_pk' => 'required|exists:users,id',
            'possession_date' => 'required|date',
            'meter_no_one' => 'nullable|string|max:50',
            'meter_no_two' => 'nullable|string|max:50',
            'initial_reading_one' => 'nullable|numeric|min:0',
            'initial_reading_two' => 'nullable|numeric|min:0',
            'licence_fee' => 'nullable|numeric|min:0',
            'water_charge' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstatePossession::create($validated);

        // Update unit status to Occupied
        EstateUnitMaster::where('pk', $validated['estate_unit_master_pk'])
            ->update(['is_active' => 0]); // Mark as occupied

        return redirect()->route('estate.possession.index')
            ->with('success', 'Possession record created successfully.');
    }

    public function edit(string $id)
    {
        $possession = EstatePossession::with(['unit', 'employee'])->findOrFail($id);
        $units = EstateUnitMaster::where('is_active', 1)
            ->orWhere('pk', $possession->estate_unit_master_pk)
            ->with(['campus', 'area', 'block', 'unitType'])
            ->get();
        $employees = User::active()->get();
        
        return view('estate.possession.edit', compact('possession', 'units', 'employees'));
    }

    public function update(Request $request, string $id)
    {
        $possession = EstatePossession::findOrFail($id);
        
        $validated = $request->validate([
            'estate_unit_master_pk' => 'required|exists:estate_unit_master,pk',
            'employee_master_pk' => 'required|exists:users,id',
            'possession_date' => 'required|date',
            'vacation_date' => 'nullable|date|after_or_equal:possession_date',
            'meter_no_one' => 'nullable|string|max:50',
            'meter_no_two' => 'nullable|string|max:50',
            'initial_reading_one' => 'nullable|numeric|min:0',
            'initial_reading_two' => 'nullable|numeric|min:0',
            'licence_fee' => 'nullable|numeric|min:0',
            'water_charge' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $possession->update($validated);

        // Update unit status based on vacation
        if ($validated['vacation_date'] ?? false) {
            EstateUnitMaster::where('pk', $validated['estate_unit_master_pk'])
                ->update(['is_active' => 1]); // Mark as available
        }

        return redirect()->route('estate.possession.index')
            ->with('success', 'Possession record updated successfully.');
    }

    public function vacate(Request $request, string $id)
    {
        $possession = EstatePossession::findOrFail($id);
        
        $validated = $request->validate([
            'vacation_date' => 'required|date|after_or_equal:possession_date',
        ]);

        $possession->update([
            'vacation_date' => $validated['vacation_date'],
            'modify_by' => Auth::id(),
            'modify_date' => now(),
        ]);

        // Mark unit as available
        EstateUnitMaster::where('pk', $possession->estate_unit_master_pk)
            ->update(['is_active' => 1]);

        return response()->json(['success' => true, 'message' => 'Unit vacated successfully.']);
    }

    public function destroy(string $id)
    {
        try {
            $possession = EstatePossession::findOrFail($id);
            
            // Mark unit as available if not already vacated
            if (!$possession->vacation_date) {
                EstateUnitMaster::where('pk', $possession->estate_unit_master_pk)
                    ->update(['is_active' => 1]);
            }
            
            $possession->delete();
            
            return response()->json(['success' => true, 'message' => 'Possession record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting possession: ' . $e->getMessage()], 500);
        }
    }

    public function meterReading(string $id)
    {
        $possession = EstatePossession::with(['unit', 'employee', 'meterReadings' => function($query) {
            $query->orderBy('reading_date', 'desc');
        }])->findOrFail($id);
        
        return view('estate.possession.meter-reading', compact('possession'));
    }
}
