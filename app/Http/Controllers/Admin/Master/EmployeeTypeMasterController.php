<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeMasterController extends Controller
{
    use HasBrandedExport;

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
        // Preserve existing behaviour (default active) while allowing the modal's Status field.
        $employeeType->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($employeeType->active_inactive ?? 1);
        $employeeType->save();

        $message = $id ? 'Employee Type updated successfully.' : 'Employee Type created successfully.';

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.employee.type.index')->with('success', $message);

    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf')
    {
        $rows = [];
        $i    = 1;
        foreach (EmployeeTypeMaster::orderBy('category_type_name')->get() as $e) {
            $rows[] = [$i++, $e->category_type_name, $e->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Employee Type Master', ['S. No.', 'Category Type Name', 'Status'], $rows, 'employee-type-master');
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
