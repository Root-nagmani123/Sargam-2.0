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
        if (request()->ajax()) {
            return view('admin.master.employee_type._form');
        }
        return view('admin.master.employee_type.create');
    }

    function store(Request $request)
    {
        //dd($request->all);
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

       // $employeeType = $id ? EmployeeTypeMaster::find($id) : new EmployeeTypeMaster();

      $employeeType = $id  ? EmployeeTypeMaster::findOrFail($id) : new EmployeeTypeMaster();

       if ($id && !$employeeType) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Employee Type not found.'], 422);
            }
            return redirect()->back()->with('error', 'Employee Type not found.');
        }

        //$employeeType->category_type_name = $request->employee_type_name;
        $employeeType->category_type_name = $request->employee_type_name;

        if (!$id) {
                $employeeType->created_date = now();
        }

        $employeeType->modified_date = now();


        $employeeType->save();

        $message = $id ? 'Employee Type updated successfully.' : 'Employee Type created successfully.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('master.employee.type.index')->with('success', $message);
    }

    function edit($id)
    {
        try {
            $employeeTypeMaster = EmployeeTypeMaster::find(decrypt($id));

            if (request()->ajax()) {
                return view('admin.master.employee_type._form', compact('employeeTypeMaster'));
            }

            return view('admin.master.employee_type.create', compact('employeeTypeMaster'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to edit employee type: ' . $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Failed to edit employee type: ' . $e->getMessage());
        }
    }

    function delete($id)
    {
        try {
            $employeeType = EmployeeTypeMaster::findOrFail(decrypt($id));
            $employeeType->delete();
            return redirect()->route('master.employee.type.index')->with('success', 'Employee Type deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete employee type: ' . $e->getMessage());
        }
    }
}
