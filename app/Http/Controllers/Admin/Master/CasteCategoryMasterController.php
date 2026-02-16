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
        $casteCategory = null;
        
        // Return only form HTML for AJAX requests
        if (request()->ajax()) {
            return view('admin.master.caste_category._form', compact('casteCategory'))->render();
        }
        
        return view('admin.master.caste_category.create', compact('casteCategory'));
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
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caste Category not found.'
                ], 404);
            }
            return redirect()->back()->with('error', 'Caste Category not found.');
        }

        $casteCategory->Seat_name = $request->Seat_name;
        $casteCategory->Seat_name_hindi = $request->Seat_name_hindi;
        $casteCategory->save();

        $message = $id ? 'Caste Category updated successfully.' : 'Caste Category created successfully.';

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('master.caste.category.index')->with('success', $message);
    }
    public function edit($id)
    {
        $casteCategory = CasteCategoryMaster::findOrFail(decrypt($id));
        
        // Return only form HTML for AJAX requests
        if (request()->ajax()) {
            return view('admin.master.caste_category._form', compact('casteCategory'))->render();
        }
        
        return view('admin.master.caste_category.create', compact('casteCategory'));
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

    public function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
            $casteCategory = CasteCategoryMaster::findOrFail($pk);
            
            // Check if caste category is active
            if ($casteCategory->active_inactive == 1) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete an active caste category. Please deactivate it first.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Cannot delete an active caste category. Please deactivate it first.');
            }
            
            $casteCategory->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'message' => 'Caste category deleted successfully.'
                ]);
            }
            
            return redirect()->route('master.caste.category.index')->with('success', 'Caste category deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete caste category: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete caste category: ' . $e->getMessage());
        }
    }
}
