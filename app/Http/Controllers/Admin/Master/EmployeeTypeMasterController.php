<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeMasterController extends Controller
{
    function index()
    {
        $employeeTypeMaster = new EmployeeTypeMasterDataTable;
        return $employeeTypeMaster->render('admin.master.employee_type.index');
        // return view('admin.master.employee_type.index');
    }
    function create()
    {
        return view('admin.master.employee_type.create');
    }
    function store(Request $request)
    {

        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'employee_type_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_type_master', 'category_type_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $employeeType = $id ? EmployeeTypeMaster::find($id) : new EmployeeTypeMaster();

        if ($id && !$employeeType) {
            return redirect()->back()->with('error', 'Employee Type not found.');
        }

        $employeeType->category_type_name = $request->employee_type_name;
        $employeeType->save();

        $message = $id ? 'Employee Type updated successfully.' : 'Employee Type created successfully.';

        return redirect()->route('master.employee.type.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $employeeTypeMaster = EmployeeTypeMaster::find(decrypt($id));
            
            return view('admin.master.employee_type.create', compact('employeeTypeMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit employee type: ' . $e->getMessage());
        }
    }
    // function delete($id)
    // {
    //     // Logic to delete department by ID
    //     return redirect()->route('master.department.index')->with('success', 'Department deleted successfully.');
    // }
}
