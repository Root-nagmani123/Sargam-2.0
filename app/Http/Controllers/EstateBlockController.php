<?php

namespace App\Http\Controllers;

use App\Models\EstateBlockMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateBlockController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateBlockMaster::query();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.block.edit', $row->pk).'" class="edit btn btn-primary btn-sm">Edit</a>';
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('estate.block.index');
    }

    public function create()
    {
        return view('estate.block.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_name' => 'required|string|max:255|unique:estate_block_master,block_name',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateBlockMaster::create($validated);

        return redirect()->route('estate.block.index')
            ->with('success', 'Block created successfully.');
    }

    public function edit(string $id)
    {
        $block = EstateBlockMaster::findOrFail($id);
        return view('estate.block.edit', compact('block'));
    }

    public function update(Request $request, string $id)
    {
        $block = EstateBlockMaster::findOrFail($id);
        
        $validated = $request->validate([
            'block_name' => 'required|string|max:255|unique:estate_block_master,block_name,'.$id.',pk',
            'description' => 'nullable|string',
        ]);

        $validated['modify_by'] = Auth::id();
        $validated['modify_date'] = now();

        $block->update($validated);

        return redirect()->route('estate.block.index')
            ->with('success', 'Block updated successfully.');
    }

    public function destroy(string $id)
    {
        try {
            $block = EstateBlockMaster::findOrFail($id);
            $block->delete();
            
            return response()->json(['success' => true, 'message' => 'Block deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting block: ' . $e->getMessage()], 500);
        }
    }
}
