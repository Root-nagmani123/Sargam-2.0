<?php

namespace App\Http\Controllers;

use App\Models\EstateCampusMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateCampusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateCampusMaster::query();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.campus.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('estate.campus.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('estate.campus.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255|unique:estate_campus_master,campus_name',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateCampusMaster::create($validated);

        return redirect()->route('estate.campus.index')
            ->with('success', 'Campus created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $campus = EstateCampusMaster::with(['areas', 'units'])->findOrFail($id);
        return view('estate.campus.show', compact('campus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $campus = EstateCampusMaster::findOrFail($id);
        return view('estate.campus.edit', compact('campus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $campus = EstateCampusMaster::findOrFail($id);
        
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255|unique:estate_campus_master,campus_name,'.$id.',pk',
            'description' => 'nullable|string',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $campus->update($validated);

        return redirect()->route('estate.campus.index')
            ->with('success', 'Campus updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $campus = EstateCampusMaster::findOrFail($id);
            $campus->delete();
            
            return response()->json(['success' => true, 'message' => 'Campus deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting campus: ' . $e->getMessage()], 500);
        }
    }
}
