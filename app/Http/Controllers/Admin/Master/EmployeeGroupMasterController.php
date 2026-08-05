<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeGroupMasterDataTable;
use App\Models\EmployeeGroupMaster;
use Illuminate\Validation\Rule;

class EmployeeGroupMasterController extends Controller
{
    use HasBrandedExport;

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

        // The live column is `emp_group_name`; the form field is posted as `group_name`.
        $rules = [
            'group_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_group_master', 'emp_group_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $employeeGroup = $id ? EmployeeGroupMaster::find($id) : new EmployeeGroupMaster();

        if ($id && !$employeeGroup) {
            return redirect()->back()->with('error', 'Employee Group not found.');
        }

        $employeeGroup->emp_group_name = $request->group_name;
        // Preserve existing behaviour (default active) while allowing the modal's Status field.
        $employeeGroup->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($employeeGroup->active_inactive ?? 1);
        $employeeGroup->save();

        $message = $id ? 'Employee Group updated successfully.' : 'Employee Group created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.employee.group.index')->with('success', $message);
    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf')
    {
        $rows = [];
        $i    = 1;
        foreach (EmployeeGroupMaster::orderBy('emp_group_name')->get() as $g) {
            $rows[] = [$i++, $g->emp_group_name, $g->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Employee Group Master', ['S. No.', 'Employee Group Name', 'Status'], $rows, 'employee-group-master');
    }
    public function edit($id)
    {
        $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));
        // dd($employeeGroupMaster);
        return view('admin.master.employee_group.create', compact('employeeGroupMaster'));
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
