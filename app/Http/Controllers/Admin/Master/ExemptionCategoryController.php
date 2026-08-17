<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExemptionCategoryMaster;
use App\Models\ExemptionMedicalSpecialityMaster;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class ExemptionCategoryController extends Controller
{

    public function index()
    {
        $categories = ExemptionCategoryMaster::paginate(10);
        return view('admin.master.exemption_categories_master.index');
    }

    public function getcategory(Request $request)
    {
        /* ===============================
       UPDATE STATUS (Active / Inactive)
    ================================ */
        if ($request->filled('pk') && $request->filled('active_inactive') && $request->active_inactive != 2) {
            ExemptionCategoryMaster::whereKey($request->pk)->update([
                'active_inactive' => $request->active_inactive
            ]);
        }

        /* ===============================
       DELETE RECORD
    ================================ */
        if ($request->filled('pk') && $request->active_inactive == 2) {
            ExemptionCategoryMaster::whereKey($request->pk)->delete();
        }

        /* ===============================
       DATATABLE QUERY
    ================================ */
        $query = ExemptionCategoryMaster::orderByDesc('pk');

        return DataTables::of($query)
            ->addIndexColumn()

            /* ===============================
           GLOBAL SEARCH
        ================================ */
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $search = $request->search['value'];

                    $query->where(function ($q) use ($search) {
                        $q->where('exemp_category_name', 'LIKE', "%{$search}%")
                            ->orWhere('exemp_cat_short_name', 'LIKE', "%{$search}%");
                    });
                }
            })

            /* ===============================
           COLUMNS
        ================================ */
            ->addColumn('exemp_category_name', function ($row) {
                return $row->exemp_category_name ?? 'N/A';
            })

            ->addColumn('ShortName', function ($row) {
                return $row->exemp_cat_short_name ?? 'N/A';
            })

            /* ===============================
           STATUS TOGGLE
        ================================ */
            // Status: soft badge, display only (docs/new-design-index-page.md
            // §3b). data-order lets a client-side sort order by state.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                // The caption names the ACTION, not the state: the state is
                // already shown by the badge one column over (§3b).
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                $edit = '<a href="javascript:void(0)" class="exm-act exm-act--edit edit-btn"'
                    . ' data-id="' . (int) $row->pk . '"'
                    . ' data-exemp_category_name="' . e($row->exemp_category_name) . '"'
                    . ' data-exemp_cat_short_name="' . e($row->exemp_cat_short_name) . '"'
                    . ' data-active_inactive="' . (int) $row->active_inactive . '"'
                    . ' title="Edit" aria-label="Edit category">'
                    . '<span class="exm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="exm-act__label">Edit</span></a>';

                // No .form-check/.form-switch wrapper (§3b trap 1): custom.css
                // pulls a .form-check-input inside one left by -2.375rem, which
                // is right for switch-beside-label and wrong for this layout.
                $toggle = '<label class="exm-act exm-act--toggle" title="' . $toggleLabel . '">'
                    . '<span class="exm-act__icon">'
                    . '<input class="form-check-input plain-status-toggle" type="checkbox" role="switch"'
                    . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                    . ' aria-label="' . $toggleLabel . ' category">'
                    . '</span>'
                    . '<span class="exm-act__label">' . $toggleLabel . '</span></label>';

                // Mirror the rule the page enforces: an active row cannot be
                // deleted, so the control is muted and inert rather than
                // red-and-always-failing.
                $delete = $isActive
                    ? '<span class="exm-act exm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this category before deleting">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></span>'
                    : '<a href="javascript:void(0)" class="exm-act exm-act--del delete-btn"'
                        . ' data-id="' . (int) $row->pk . '" aria-disabled="false"'
                        . ' title="Delete" aria-label="Delete category">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></a>';

                return '<div class="exm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete
                    . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->orderColumn('DT_RowIndex', 'pk $1') //Added this line for ordering
            ->make(true);
    }


    public function updatedata(Request $request)
    {
        $table = 'exemption_category_master';
        try {
            DB::table($table)->where('pk', $request->pk)->update([
                'exemp_category_name' => $request->exemp_category_name,
                'exemp_cat_short_name' => $request->exemp_cat_short_name,
                'active_inactive' => $request->active_inactive
            ]);
            return redirect()->back()->with('success', 'Exemption categories updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Exemption categories not correct');
        }
    }

    public function create()
    {
        return view('admin.master.exemption_categories_master.create_edit');
    }

public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'exemp_category_name'  => 'required|string|max:100',
            'exemp_cat_short_name' => 'required|string|max:50',
            'status'               => 'required|in:0,1',
        ]);

        if ($request->pk) {
            $category = ExemptionCategoryMaster::findOrFail($request->pk);
            $category->update([
                'exemp_category_name'  => $validated['exemp_category_name'],
                'exemp_cat_short_name' => $validated['exemp_cat_short_name'],
                'active_inactive'      => $validated['status'],
                'modified_date'        => now(),
            ]);
            $message = 'Category updated successfully.';
        } else {
            ExemptionCategoryMaster::create([
                'exemp_category_name'  => $validated['exemp_category_name'],
                'exemp_cat_short_name' => $validated['exemp_cat_short_name'],
                'active_inactive'      => $validated['status'],
                'created_date'         => now(),
                'modified_date'        => now(),
            ]);
            $message = 'Category created successfully.';
        }

        return response()->json([
            'status'  => true,
            'message' => $message
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status' => false,
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



    public function edit($id)
    {
        // print_r(decrypt($id));
        $exemptionCategory = ExemptionCategoryMaster::findOrFail(decrypt($id));
        // print_r($category);die;
        return view('admin.master.exemption_categories_master.create_edit', compact('exemptionCategory'));
    }

    public function delete($id)
    {
        ExemptionCategoryMaster::destroy(decrypt($id));
        return redirect()->route('master.exemption.category.master.index')->with('success', 'Category deleted successfully.');
    }





    public function medicalSpecialityIndex()
    {
        // $specialities = ExemptionMedicalSpecialityMaster::paginate(10);
        return view('admin.master.exemption_medical_speciality.index');
    }

    public function exemption_med_spec_mst(Request $request)
    {
        /* ===============================
       UPDATE STATUS (Active / Inactive)
    ================================ */
        if ($request->filled('pk') && $request->filled('active_inactive') && $request->active_inactive != 2) {
            ExemptionMedicalSpecialityMaster::whereKey($request->pk)->update([
                'active_inactive' => $request->active_inactive
            ]);
        }

        /* ===============================
       DELETE RECORD
    ================================ */
        if ($request->filled('pk') && $request->active_inactive == 2) {
            ExemptionMedicalSpecialityMaster::whereKey($request->pk)->delete();
        }

        /* ===============================
       DATATABLE QUERY
    ================================ */
        $query = ExemptionMedicalSpecialityMaster::orderByDesc('pk');

        return DataTables::of($query)
            ->addIndexColumn()

            /* ===============================
           GLOBAL SEARCH
        ================================ */
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $search = $request->search['value'];

                    $query->where(function ($q) use ($search) {
                        $q->where('speciality_name', 'LIKE', "%{$search}%");
                    });
                }
            })

            /* ===============================
           COLUMNS
        ================================ */
            ->addColumn('speciality_name', function ($row) {
                return $row->speciality_name ?? 'N/A';
            })

            ->addColumn('created_date', function ($row) {
             return $row->created_date
                ? \Carbon\Carbon::parse($row->created_date)->format('d-m-Y')
                : 'N/A';
            })

            /* ===============================
           STATUS TOGGLE
        ================================ */
            // Status: soft badge, display only (docs/new-design-index-page.md
            // §3b). data-order lets a client-side sort order by state.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                // The caption names the ACTION, not the state: the state is
                // already shown by the badge one column over (§3b).
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                $edit = '<a href="javascript:void(0)" class="exm-act exm-act--edit edit-btn"'
                    . ' data-id="' . (int) $row->pk . '"'
                    . ' data-speciality_name="' . e($row->speciality_name) . '"'
                    . ' data-created_date="' . e($row->created_date) . '"'
                    . ' data-active_inactive="' . (int) $row->active_inactive . '"'
                    . ' title="Edit" aria-label="Edit speciality">'
                    . '<span class="exm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="exm-act__label">Edit</span></a>';

                // No .form-check/.form-switch wrapper (§3b trap 1): custom.css
                // pulls a .form-check-input inside one left by -2.375rem, which
                // is right for switch-beside-label and wrong for this layout.
                $toggle = '<label class="exm-act exm-act--toggle" title="' . $toggleLabel . '">'
                    . '<span class="exm-act__icon">'
                    . '<input class="form-check-input plain-status-toggle" type="checkbox" role="switch"'
                    . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                    . ' aria-label="' . $toggleLabel . ' speciality">'
                    . '</span>'
                    . '<span class="exm-act__label">' . $toggleLabel . '</span></label>';

                // Mirror the rule the page enforces: an active row cannot be
                // deleted, so the control is muted and inert rather than
                // red-and-always-failing.
                $delete = $isActive
                    ? '<span class="exm-act exm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this speciality before deleting">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></span>'
                    : '<a href="javascript:void(0)" class="exm-act exm-act--del delete-btn"'
                        . ' data-id="' . (int) $row->pk . '" aria-disabled="false"'
                        . ' title="Delete" aria-label="Delete speciality">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></a>';

                return '<div class="exm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete
                    . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function medicalSpecialityCreate()
    {
        return view('admin.master.exemption_medical_speciality.create_edit');
    }

public function medicalSpecialityStore(Request $request)
{
    try {
        $validated = $request->validate([
            'speciality_name' => 'required|string|max:100',
            'status' => 'required|in:0,1',
        ]);

        if ($request->filled('id')) {

            $speciality = ExemptionMedicalSpecialityMaster::findOrFail($request->id);

            $speciality->update([
                'speciality_name' => $validated['speciality_name'],
                'active_inactive' => $validated['status'],
            ]);

            return response()->json([
                'status'  => true,
                'type'    => 'update',
                'message' => 'Medical Speciality updated successfully.',
            ], 200);

        } else {

            ExemptionMedicalSpecialityMaster::create([
                'speciality_name' => $validated['speciality_name'],
                'active_inactive' => $validated['status'],
            ]);

            return response()->json([
                'status'  => true,
                'type'    => 'create',
                'message' => 'Medical Speciality created successfully.',
            ], 201);
        }

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status' => false,
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function medicalSpecialityEdit($id)
    {
        $speciality = ExemptionMedicalSpecialityMaster::findOrFail(decrypt($id));
        return view('admin.master.exemption_medical_speciality.create_edit', compact('speciality'));
    }

    public function medicalSpecialityDelete($id)
    {
        ExemptionMedicalSpecialityMaster::destroy(decrypt($id));
        return redirect()->route('master.exemption.medical.speciality.index')->with('success', 'Medical Speciality deleted successfully.');
    }
}
