<?php

namespace App\Http\Controllers;

use App\Models\EstateElectricSlab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateElectricSlabController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateElectricSlab::query();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function($row){
                    return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.electric-slab.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('estate.electric-slab.index');
    }

    public function create()
    {
        return view('estate.electric-slab.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slab_name' => 'required|string|max:255',
            'from_unit' => 'required|integer|min:0',
            'to_unit' => 'required|integer|gt:from_unit',
            'rate_per_unit' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateElectricSlab::create($validated);

        return redirect()->route('estate.electric-slab.index')
            ->with('success', 'Electric Slab created successfully.');
    }

    public function edit(string $id)
    {
        $slab = EstateElectricSlab::findOrFail($id);
        return view('estate.electric-slab.edit', compact('slab'));
    }

    public function update(Request $request, string $id)
    {
        $slab = EstateElectricSlab::findOrFail($id);
        
        $validated = $request->validate([
            'slab_name' => 'required|string|max:255',
            'from_unit' => 'required|integer|min:0',
            'to_unit' => 'required|integer|gt:from_unit',
            'rate_per_unit' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $slab->update($validated);

        return redirect()->route('estate.electric-slab.index')
            ->with('success', 'Electric Slab updated successfully.');
    }

    public function destroy(string $id)
    {
        try {
            $slab = EstateElectricSlab::findOrFail($id);
            $slab->delete();
            
            return response()->json(['success' => true, 'message' => 'Electric Slab deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting slab: ' . $e->getMessage()], 500);
        }
    }
}
