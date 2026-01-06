<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExemptionCategoryMaster; 
use App\Models\ExemptionMedicalSpecialityMaster; 
use Yajra\DataTables\Facades\DataTables;


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
        ->addColumn('status', function ($row) {
            $checked = $row->active_inactive == 1 ? 'checked' : '';

            return '
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input plain-status-toggle"
                       type="checkbox"
                       data-id="'.$row->pk.'"
                       '.$checked.'>
            </div>';
        })

        /* ===============================
           ACTION BUTTONS
        ================================ */
        ->addColumn('action', function ($row) {
            $disabled = $row->active_inactive == 1 ? 'disabled' : '';

            return '
            <div class="dropdown">
                <a href="#" class="text-dark" data-bs-toggle="dropdown">
                    <i class="material-icons">more_horiz</i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item edit-btn"
                           href="javascript:void(0)"
                           data-id="'.$row->pk.'"
                           data-exemp_category_name="'.$row->exemp_category_name.'"
                           data-exemp_cat_short_name="'.$row->exemp_cat_short_name.'"
                           data-active_inactive="'.$row->active_inactive.'">
                            <i class="material-icons me-2" style="font-size:18px;">edit</i>
                            Edit
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item delete-btn '.$disabled.'"
                           href="javascript:void(0)"
                           data-id="'.$row->pk.'">
                            <i class="material-icons me-2" style="font-size:18px;">delete</i>
                            Delete
                        </a>
                    </li>
                </ul>
            </div>';
        })

        ->rawColumns(['status', 'action'])
        ->make(true);
}


 public function updatedata(Request $request)
    {
        $table = 'exemption_category_master';
        try {
            DB::table($table)->where('pk', $request->pk)->update(['exemp_category_name' => $request->exemp_category_name,
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
    $request->validate([
        'exemp_category_name' => 'required|string|max:100',
        'exemp_cat_short_name' => 'required|string|max:50',
    ]);

    try {
        if ($request->id) {
            // Update Mode
            $category = ExemptionCategoryMaster::findOrFail(decrypt($request->id));
            $category->exemp_category_name = $request->exemp_category_name;
            $category->exemp_cat_short_name = $request->exemp_cat_short_name;
            $category->active_inactive = $request->active_inactive ?? 1;
            $category->save();

            $message = 'Category updated successfully.';
        } else {
            // Create Mode
            ExemptionCategoryMaster::create([
                'exemp_category_name' => $request->exemp_category_name,
                'exemp_cat_short_name' => $request->exemp_cat_short_name,
                'active_inactive' => $request->active_inactive ?? 1,
            ]);

            $message = 'Category created successfully.';
        }

        return redirect()->route('master.exemption.category.master.index')->with('success', $message);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage())->withInput();
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
            return $row->created_date ?? 'N/A';
        })

        /* ===============================
           STATUS TOGGLE
        ================================ */
        ->addColumn('status', function ($row) {
            $checked = $row->active_inactive == 1 ? 'checked' : '';

            return '
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input plain-status-toggle"
                       type="checkbox"
                       data-id="'.$row->pk.'"
                       '.$checked.'>
            </div>';
        })

        /* ===============================
           ACTION BUTTONS
        ================================ */
        ->addColumn('action', function ($row) {
            $disabled = $row->active_inactive == 1 ? 'disabled' : '';

            return '
            <div class="dropdown">
                <a href="#" class="text-dark" data-bs-toggle="dropdown">
                    <i class="material-icons">more_horiz</i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item edit-btn"
                           href="javascript:void(0)"
                           data-id="'.$row->pk.'"
                           data-speciality_name="'.$row->speciality_name.'"
                           data-created_date="'.$row->created_date.'"
                           data-active_inactive="'.$row->active_inactive.'">
                            <i class="material-icons me-2" style="font-size:18px;">edit</i>
                            Edit
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item delete-btn '.$disabled.'"
                           href="javascript:void(0)"
                           data-id="'.$row->pk.'">
                            <i class="material-icons me-2" style="font-size:18px;">delete</i>
                            Delete
                        </a>
                    </li>
                </ul>
            </div>';
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
    $request->validate([
        'medical_speciality_name' => 'required|string|max:100',
    ]);

    try {
        if ($request->id) {

            $speciality = ExemptionMedicalSpecialityMaster::findOrFail(decrypt($request->id));
            $speciality->speciality_name = $request->medical_speciality_name;
            $speciality->active_inactive = $request->active_inactive ?? 1;
            $speciality->save();

            $message = 'Medical Speciality updated successfully.';
        } else {
            ExemptionMedicalSpecialityMaster::create([
                'speciality_name' => $request->medical_speciality_name,
                'active_inactive' => $request->active_inactive ?? 1,
            ]);

            $message = 'Medical Speciality created successfully.';
        }

        return redirect()->route('master.exemption.medical.speciality.index')->with('success', $message);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage())->withInput();
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
