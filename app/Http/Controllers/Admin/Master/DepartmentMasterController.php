<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\DepartmentMasterDataTable;
use App\Models\DepartmentMaster;
use Illuminate\Validation\Rule;


class DepartmentMasterController extends Controller
{
    function index()
    {
        $departmentMaster = new DepartmentMasterDataTable;
        return $departmentMaster->render('admin.master.department.index');
        // return view('admin.master.department.index');
    }
    function create()
    {
        $departmentMaster = null;

        // Return only form HTML for AJAX requests (modal)
        if (request()->ajax()) {
            return view('admin.master.department._form', compact('departmentMaster'))->render();
        }

        return view('admin.master.department.create', compact('departmentMaster'));
    }
    function store(Request $request)
    {


        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'department_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('department_master', 'department_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $department = $id ? DepartmentMaster::find($id) : new DepartmentMaster();

        if ($id && !$department) {
            return redirect()->back()->with('error', 'Department not found.');
        }

        $department->department_name = $request->department_name;
        $department->save();

        $message = $id ? 'Department updated successfully.' : 'Department created successfully.';

        // Return JSON response for AJAX requests (modal)
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('master.department.master.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $departmentMaster = DepartmentMaster::findOrFail(decrypt($id));

            // Return only form HTML for AJAX requests (modal)
            if (request()->ajax()) {
                return view('admin.master.department._form', compact('departmentMaster'))->render();
            }

            return view('admin.master.department.create', compact('departmentMaster'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to edit department: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to edit department: ' . $e->getMessage());
        }
    }
    function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
            $department = DepartmentMaster::findOrFail($pk);
            
            // Check if department is active
            if ($department->active_inactive == 1) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete an active department. Please deactivate it first.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Cannot delete an active department. Please deactivate it first.');
            }
            
            $department->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'message' => 'Department deleted successfully.'
                ]);
            }
            
            return redirect()->route('master.department.master.index')->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete department: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete department: ' . $e->getMessage());
        }
    }
}
