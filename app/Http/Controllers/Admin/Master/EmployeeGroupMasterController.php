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
        return redirect()->route('master.employee.group.index', ['open_egm_modal' => 'add']);
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'group_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_group_master', 'group_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $employeeGroup = $id ? EmployeeGroupMaster::find($id) : new EmployeeGroupMaster();

        if ($id && !$employeeGroup) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Employee Group not found.'], 404);
            }

            return redirect()->back()->with('error', 'Employee Group not found.');
        }

        $employeeGroup->group_name = $request->group_name;
        $employeeGroup->save();

        $message = $id ? 'Employee Group updated successfully.' : 'Employee Group created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.employee.group.index')->with('success', $message);
    }
    public function edit($id)
    {
        try {
            $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));

            return redirect()->route('master.employee.group.index', [
                'open_egm_modal' => 'edit',
                'egm_pk' => $id,
                'egm_name' => $employeeGroupMaster->emp_group_name ?? $employeeGroupMaster->group_name ?? '',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('master.employee.group.index')
                ->with('error', 'Failed to edit employee group: ' . $e->getMessage());
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
