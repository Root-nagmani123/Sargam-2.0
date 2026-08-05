<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\Models\DesignationMaster;
use App\DataTables\Master\DesignationMasterDataTable;
use Illuminate\Validation\Rule;

class DesignationMasterController extends Controller
{
    use HasBrandedExport;

    function index()
    {
        $designationMaster = new DesignationMasterDataTable;
        return $designationMaster->render('admin.master.designation.index');
        // return view('admin.master.designation.index');
    }
    function create()
    {
        return view('admin.master.designation.create');
    }
    function store(Request $request)
    {


        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'designation_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designation_master', 'designation_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $designation = $id ? DesignationMaster::find($id) : new DesignationMaster();

        if ($id && !$designation) {
            return redirect()->back()->with('error', 'Designation not found.');
        }

        $designation->designation_name = $request->designation_name;
        // Preserve existing behaviour (default active) while allowing the modal's Status field.
        $designation->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($designation->active_inactive ?? 1);
        $designation->save();

        $message = $id ? 'Designation updated successfully.' : 'Designation created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.designation.index')->with('success', $message);

    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf')
    {
        $rows = [];
        $i    = 1;
        foreach (DesignationMaster::orderBy('designation_name')->get() as $d) {
            $rows[] = [$i++, $d->designation_name, $d->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Designation Master', ['S. No.', 'Designation Name', 'Status'], $rows, 'designation-master');
    }
    function edit($id)
    {
        try {
            $designationMaster = DesignationMaster::find(decrypt($id));
            return view('admin.master.designation.create', compact('designationMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit designation: ' . $e->getMessage());
        }
    }

}
