<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeGroupMasterDataTable;
use App\Models\EmployeeGroupMaster;
use Illuminate\Validation\Rule;

class EmployeeGroupMasterController extends Controller
{
    public function index()
    {
        return (new EmployeeGroupMasterDataTable())->render('admin.master.employee_group.index');
    }

    public function create()
    {
        return view('admin.master.employee_group.create');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $request->validate([
            'emp_group_name' => [
                'required',
                'string',
                // emp_group_name is varchar(30) — keep in sync with the column.
                'max:30',
                Rule::unique('employee_group_master', 'emp_group_name')->ignore($id, 'pk'),
            ],
        ], [], ['emp_group_name' => 'employee group name']);

        $employeeGroup = $id ? EmployeeGroupMaster::find($id) : new EmployeeGroupMaster();

        if ($id && !$employeeGroup) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Employee Group not found.'], 404);
            }

            return redirect()->back()->with('error', 'Employee Group not found.');
        }

        $employeeGroup->emp_group_name = $request->emp_group_name;
        $employeeGroup->active_inactive = $employeeGroup->active_inactive ?? 1;
        $employeeGroup->save();

        $message = $id ? 'Employee Group updated successfully.' : 'Employee Group created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.employee.group.index')->with('success', $message);
    }

    public function edit($id)
    {
        $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));
        return view('admin.master.employee_group.create', compact('employeeGroupMaster'));
    }
}
