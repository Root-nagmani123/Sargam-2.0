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
        return redirect()->route('master.department.master.index', ['open_dpm_modal' => 'add']);
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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Department not found.'], 404);
            }

            return redirect()->back()->with('error', 'Department not found.');
        }

        $department->department_name = $request->department_name;
        $department->save();

        $message = $id ? 'Department updated successfully.' : 'Department created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.department.master.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $departmentMaster = DepartmentMaster::findOrFail(decrypt($id));

            return redirect()->route('master.department.master.index', [
                'open_dpm_modal' => 'edit',
                'dpm_pk' => $id,
                'dpm_name' => $departmentMaster->department_name,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('master.department.master.index')
                ->with('error', 'Failed to edit department: ' . $e->getMessage());
        }
    }
    function delete($id)
    {
        // Logic to delete department by ID
        return redirect()->route('master.department.index')->with('success', 'Department deleted successfully.');
    }
}
