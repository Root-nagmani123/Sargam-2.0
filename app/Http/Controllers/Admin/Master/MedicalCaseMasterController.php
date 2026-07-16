<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicalCaseMaster;
use Yajra\DataTables\Facades\DataTables;

class MedicalCaseMasterController extends Controller
{
    public function index()
    {
        return view('admin.master.medical_case_master.index');
    }

    public function datatable(Request $request)
    {
        /* ===============================
           UPDATE STATUS (Active / Inactive)
        ================================ */
        if ($request->filled('pk') && $request->filled('active_inactive') && $request->active_inactive != 2) {
            MedicalCaseMaster::whereKey($request->pk)->update([
                'active_inactive' => $request->active_inactive,
                'modified_date'   => now(),
            ]);
        }

        /* ===============================
           DELETE RECORD
        ================================ */
        if ($request->filled('pk') && $request->active_inactive == 2) {
            MedicalCaseMaster::whereKey($request->pk)->delete();
        }

        /* ===============================
           DATATABLE QUERY
        ================================ */
        $query = MedicalCaseMaster::orderByDesc('pk');

        return DataTables::of($query)
            ->addIndexColumn()

            /* GLOBAL SEARCH */
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('case_name', 'LIKE', "%{$search}%");
                    });
                }
            })

            /* COLUMNS */
            ->addColumn('case_name', function ($row) {
                return $row->case_name ?? 'N/A';
            })

            ->addColumn('created_date', function ($row) {
                return $row->created_date
                    ? \Carbon\Carbon::parse($row->created_date)->format('d-m-Y')
                    : 'N/A';
            })

            /* STATUS TOGGLE */
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

            /* ACTION BUTTONS */
            ->addColumn('action', function ($row) {
                $disabled = $row->active_inactive == 1 ? 'disabled aria-disabled="true"' : '';

                return '
                    <div class="d-inline-flex align-items-center gap-2"
                        role="group"
                        aria-label="Row actions">

                        <!-- Edit Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        data-case_name="' . e($row->case_name) . '"
                        data-active_inactive="' . $row->active_inactive . '"
                        class="btn btn-sm edit-btn btn-outline-primary d-inline-flex align-items-center gap-1"
                        aria-label="Edit medical case">

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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'case_name' => 'required|string|max:100',
                'status'    => 'required|in:0,1',
            ]);

            if ($request->filled('id')) {

                $case = MedicalCaseMaster::findOrFail($request->id);

                $case->update([
                    'case_name'       => $validated['case_name'],
                    'active_inactive' => $validated['status'],
                    'modified_date'   => now(),
                ]);

                return response()->json([
                    'status'  => true,
                    'type'    => 'update',
                    'message' => 'Medical Case updated successfully.',
                ], 200);

            } else {

                MedicalCaseMaster::create([
                    'case_name'       => $validated['case_name'],
                    'active_inactive' => $validated['status'],
                    'created_date'    => now(),
                    'modified_date'   => now(),
                ]);

                return response()->json([
                    'status'  => true,
                    'type'    => 'create',
                    'message' => 'Medical Case created successfully.',
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

    public function edit($id)
    {
        $case = MedicalCaseMaster::findOrFail(decrypt($id));
        return view('admin.master.medical_case_master.index', compact('case'));
    }

    public function delete($id)
    {
        MedicalCaseMaster::destroy(decrypt($id));
        return redirect()->route('master.medical.case.master.index')->with('success', 'Medical Case deleted successfully.');
    }
}
