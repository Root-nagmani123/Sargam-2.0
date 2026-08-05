<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\DataTables\Master\CasteCategoryMasterDataTable;
use App\Models\CasteCategoryMaster;
use Illuminate\Validation\Rule;

class CasteCategoryMasterController extends Controller
{
    use HasBrandedExport;

    public function index()
    {
        return (new CasteCategoryMasterDataTable())->render('admin.master.caste_category.index');
    }
    public function create()
    {
        return view('admin.master.caste_category.create');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'Seat_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('caste_category_master', 'Seat_name')->ignore($id, 'pk'),
            ],
            'Seat_name_hindi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('caste_category_master', 'Seat_name_hindi')->ignore($id, 'pk'),
            ]
        ];

        $request->validate($rules);

        $casteCategory = $id ? CasteCategoryMaster::find($id) : new CasteCategoryMaster();

        if ($id && !$casteCategory) {
            return redirect()->back()->with('error', 'Caste Category not found.');
        }

        $casteCategory->Seat_name = $request->Seat_name;
        $casteCategory->Seat_name_hindi = $request->Seat_name_hindi;
        // Preserve existing behaviour (default active) while allowing the modal's Status field.
        $casteCategory->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($casteCategory->active_inactive ?? 1);
        $casteCategory->save();

        $message = $id ? 'Caste Category updated successfully.' : 'Caste Category created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.caste.category.index')->with('success', $message);
    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf')
    {
        $rows = [];
        $i    = 1;
        foreach (CasteCategoryMaster::orderBy('Seat_name')->get() as $c) {
            $rows[] = [$i++, $c->Seat_name, $c->Seat_name_hindi, $c->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport(
            $format,
            'Caste Category Master',
            ['S. No.', 'Category/Caste name', 'Category/Caste name (Hindi)', 'Status'],
            $rows,
            'caste-category-master'
        );
    }
    public function edit($id)
    {
        $casteCategory = CasteCategoryMaster::findOrFail(decrypt($id));
        return view('admin.master.caste_category.create', compact('casteCategory'));
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
