<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\CasteCategoryMasterDataTable;
use App\Models\CasteCategoryMaster;
use Illuminate\Validation\Rule;

class CasteCategoryMasterController extends Controller
{
    public function index()
    {
        return (new CasteCategoryMasterDataTable())->render('admin.master.caste_category.index');
    }
    public function create()
    {
        return view('admin.master.caste_category.create');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        // Seat_name / Seat_name_hindi are both varchar(30) — keep in sync with the columns.
        $rules = [
            'Seat_name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('caste_category_master', 'Seat_name')->ignore($id, 'pk'),
            ],
            'Seat_name_hindi' => [
                'required',
                'string',
                'max:30',
                Rule::unique('caste_category_master', 'Seat_name_hindi')->ignore($id, 'pk'),
            ]
        ];

        $request->validate($rules, [], [
            'Seat_name' => 'caste name in English',
            'Seat_name_hindi' => 'caste name in Hindi',
        ]);

        $casteCategory = $id ? CasteCategoryMaster::find($id) : new CasteCategoryMaster();

        if ($id && !$casteCategory) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Caste Category not found.'], 404);
            }

            return redirect()->back()->with('error', 'Caste Category not found.');
        }

        $casteCategory->Seat_name = $request->Seat_name;
        $casteCategory->Seat_name_hindi = $request->Seat_name_hindi;
        $casteCategory->active_inactive = $casteCategory->active_inactive ?? 1;
        $casteCategory->save();

        $message = $id ? 'Caste Category updated successfully.' : 'Caste Category created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.caste.category.index')->with('success', $message);
    }
    public function edit($id)
    {
        $casteCategory = CasteCategoryMaster::findOrFail(decrypt($id));
        return view('admin.master.caste_category.create', compact('casteCategory'));
    }
}
