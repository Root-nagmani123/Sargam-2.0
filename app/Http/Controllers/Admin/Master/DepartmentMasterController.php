<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\DataTables\Master\DepartmentMasterDataTable;
use App\Models\DepartmentMaster;
use Illuminate\Validation\Rule;


class DepartmentMasterController extends Controller
{
    use HasBrandedExport;

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
        // Preserve existing behaviour (default active) while allowing the modal's Status field.
        $department->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($department->active_inactive ?? 1);
        $department->save();

        $message = $id ? 'Department updated successfully.' : 'Department created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.department.master.index')->with('success', $message);

    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf')
    {
        $rows = [];
        $i    = 1;
        foreach (DepartmentMaster::orderBy('department_name')->get() as $d) {
            $rows[] = [$i++, $d->department_name, $d->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Department Master', ['S. No.', 'Department Name', 'Status'], $rows, 'department-master');
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
