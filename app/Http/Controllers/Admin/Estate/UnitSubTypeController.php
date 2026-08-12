<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\UnitSubType;
use Illuminate\Http\Request;
use App\Support\DataTableSearchHelper;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UnitSubTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.estate.define_unit_sub_type.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = UnitSubType::query()->select(['pk', 'unit_sub_type']);
        if (! DataTableSearchHelper::clientOrdered()) {
            $query->orderBy('pk', 'desc');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<a href="'.route('admin.estate.define-unit-sub-type.edit', $row->pk).'" class="text-primary" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded">edit</i></a>';
            })
            ->rawColumns(['action'])
            ->make(true);
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
