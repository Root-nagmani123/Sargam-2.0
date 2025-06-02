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
        return view('admin.master.department.create');
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

        return redirect()->route('master.department.master.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $departmentMaster = DepartmentMaster::find(decrypt($id));
            return view('admin.master.department.create', compact('departmentMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit department: ' . $e->getMessage());
        }
    }
    function delete($id)
    {
        // Logic to delete department by ID
        return redirect()->route('master.department.index')->with('success', 'Department deleted successfully.');
    }
}
