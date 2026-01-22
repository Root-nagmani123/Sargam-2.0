<?php

namespace App\Http\Controllers;

use App\Models\EstateAreaMaster;
use App\Models\EstateCampusMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateAreaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateAreaMaster::with('campus')->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('campus_name', function($row){
                    return $row->campus->campus_name ?? 'N/A';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.area.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('estate.area.index');
    }

    public function create()
    {
        $campuses = EstateCampusMaster::all();
        return view('estate.area.create', compact('campuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|exists:estate_campus_master,pk',
            'area_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateAreaMaster::create($validated);

        return redirect()->route('estate.area.index')
            ->with('success', 'Area created successfully.');
    }

    public function edit(string $id)
    {
        $area = EstateAreaMaster::findOrFail($id);
        $campuses = EstateCampusMaster::all();
        return view('estate.area.edit', compact('area', 'campuses'));
    }

    public function update(Request $request, string $id)
    {
        $area = EstateAreaMaster::findOrFail($id);
        
        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|exists:estate_campus_master,pk',
            'area_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $area->update($validated);

        return redirect()->route('estate.area.index')
            ->with('success', 'Area updated successfully.');
    }

    public function destroy(string $id)
    {
        try {
            $area = EstateAreaMaster::findOrFail($id);
            $area->delete();
            
            return response()->json(['success' => true, 'message' => 'Area deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting area: ' . $e->getMessage()], 500);
        }
    }

    public function getAreasByCampus($campusId)
    {
        $areas = EstateAreaMaster::where('estate_campus_master_pk', $campusId)->get();
        return response()->json($areas);
    }
}
