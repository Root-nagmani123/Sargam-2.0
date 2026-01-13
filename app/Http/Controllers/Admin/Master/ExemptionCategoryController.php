<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExemptionCategoryMaster;
use App\Models\ExemptionMedicalSpecialityMaster;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;



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
                       data-id="' . $row->pk . '"
                       ' . $checked . '>
            </div>';
            })

            /* ===============================
           ACTION BUTTONS
        ================================ */
    ->addColumn('action', function ($row) {
        $disabled = $row->active_inactive == 1 ? 'disabled aria-disabled="true"' : '';

                return '
                    <div class="d-inline-flex align-items-center gap-2"
                        role="group"
                        aria-label="Row actions">

                        <!-- Edit Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        data-exemp_category_name="' . $row->exemp_category_name . '"
                        data-exemp_cat_short_name="' . $row->exemp_cat_short_name . '"
                        data-active_inactive="' . $row->active_inactive . '"
                        class="btn btn-sm edit-btn btn-outline-primary d-inline-flex align-items-center gap-1"
                        aria-label="Edit course group type">

                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">edit</i>

                            <span class="d-none d-md-inline">Edit</span>
                        </a>

                        <!-- Delete Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        class="btn btn-sm btn-outline-danger delete-btn d-inline-flex align-items-center gap-1 ' . $disabled . '"
                        aria-disabled="' . ($row->active_inactive == 1 ? 'true' : 'false') . '">

                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">delete</i>

                            <span class="d-none d-md-inline">Delete</span>
                        </a>

                    </div>
                ';
            })


            ->rawColumns(['status', 'action'])
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
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';

                return '
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input plain-status-toggle"
                       type="checkbox"
                       data-id="' . $row->pk . '"
                       ' . $checked . '>
            </div>';
            })

            /* ===============================
           ACTION BUTTONS
        ================================ */
            

             ->addColumn('action', function ($row) {
              $disabled = $row->active_inactive == 1 ? 'disabled aria-disabled="true"' : '';

                return '
                    <div class="d-inline-flex align-items-center gap-2"
                        role="group"
                        aria-label="Row actions">

                        <!-- Edit Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        data-speciality_name="' . $row->speciality_name . '"
                        data-created_date="' . $row->created_date . '"
                        data-active_inactive="' . $row->active_inactive . '"
                        class="btn btn-sm edit-btn btn-outline-primary d-inline-flex align-items-center gap-1"
                        aria-label="Edit course group type">

                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">edit</i>

                            <span class="d-none d-md-inline">Edit</span>
                        </a>

                        <!-- Delete Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        class="btn btn-sm btn-outline-danger delete-btn d-inline-flex align-items-center gap-1 ' . $disabled . '"
                        aria-disabled="' . ($row->active_inactive == 1 ? 'true' : 'false') . '">

                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">delete</i>

                            <span class="d-none d-md-inline">Delete</span>
                        </a>

                    </div>
                ';
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
