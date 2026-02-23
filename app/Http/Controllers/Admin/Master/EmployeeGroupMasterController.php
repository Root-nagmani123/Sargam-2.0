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
            return redirect()->back()->with('error', 'Employee Group not found.');
        }

        $employeeGroup->group_name = $request->group_name;
        $employeeGroup->save();

        $message = $id ? 'Employee Group updated successfully.' : 'Employee Group created successfully.';

        return redirect()->route('master.employee.group.index')->with('success', $message);
    }
    public function edit($id)
    {
<<<<<<< HEAD
        $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));
        // dd($employeeGroupMaster);
        return view('admin.master.employee_group.create', compact('employeeGroupMaster'));
=======
          //$decryptedId = decrypt($id);
          //dd($decryptedId);
        try {
            $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));

            // Return only form HTML for AJAX requests (modal)
            if (request()->ajax()) {
                return view('admin.master.employee_group._form', compact('employeeGroupMaster'))->render();
            }

            return view('admin.master.employee_group.create', compact('employeeGroupMaster'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to edit employee group: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to edit employee group: ' . $e->getMessage());
        }
>>>>>>> 2ab4dd78 (recent log issue fixed)
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
