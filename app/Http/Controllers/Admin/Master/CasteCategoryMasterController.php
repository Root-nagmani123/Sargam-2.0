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
        return redirect()->route('master.caste.category.index', ['open_ccm_modal' => 'add']);
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'Seat_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('caste_category_master', 'Seat_name')->ignore($id, 'pk'),
            ],
            'Seat_name_hindi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('caste_category_master', 'Seat_name_hindi')->ignore($id, 'pk'),
            ]
        ];

        $request->validate($rules);

        $casteCategory = $id ? CasteCategoryMaster::find($id) : new CasteCategoryMaster();

        if ($id && !$casteCategory) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Caste Category not found.'], 404);
            }

            return redirect()->back()->with('error', 'Caste Category not found.');
        }

        $casteCategory->Seat_name = $request->Seat_name;
        $casteCategory->Seat_name_hindi = $request->Seat_name_hindi;
        $casteCategory->save();

        $message = $id ? 'Caste Category updated successfully.' : 'Caste Category created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.caste.category.index')->with('success', $message);
    }
    public function edit($id)
    {
        try {
            $casteCategory = CasteCategoryMaster::findOrFail(decrypt($id));

            return redirect()->route('master.caste.category.index', [
                'open_ccm_modal' => 'edit',
                'ccm_pk' => $id,
                'ccm_seat_name' => $casteCategory->Seat_name,
                'ccm_seat_name_hindi' => $casteCategory->Seat_name_hindi,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('master.caste.category.index')
                ->with('error', 'Failed to edit caste category: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'group_name' => 'required|string|max:255',
        ]);

        $employeeGroup = \App\Models\EmployeeGroupMaster::findOrFail($id);
        $employeeGroup->update($data);
        return redirect()->route('admin.master.employee_group_master.index')->with('success', 'Employee Group updated successfully.');
    }
}
