<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\EstateBlock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EstateBlockController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.estate.define_block_building.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = EstateBlock::query()->select(['pk', 'block_name']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<a href="'.route('admin.estate.define-block-building.edit', $row->pk).'" class="text-primary" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded">edit</i></a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_block_building.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_name' => 'required|string|max:255',
        ]);
        EstateBlock::create($validated);
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building added successfully.');
    }

    public function edit(string $id)
    {
        $item = EstateBlock::findOrFail($id);
        return view('admin.estate.define_block_building.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = EstateBlock::findOrFail($id);
        $validated = $request->validate([
            'block_name' => 'required|string|max:255',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EstateBlock::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Estate block/building deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-block-building.index')->with('success', 'Estate block/building deleted successfully.');
    }
}
