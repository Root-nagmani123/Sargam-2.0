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

            // Status: soft badge, display only (docs/new-design-index-page.md
            // §3b). data-order lets a client-side sort order by state. The
            // switch that changes it lives in the action group; it used to be
            // rendered here and moved across by JS on every draw.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill mcm-status-badge badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })

            // Action: Edit · the status switch · Delete, three equal stacks of an
            // icon over a caption (§3b).
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                // The caption names the ACTION, not the state: the state is
                // already shown by the badge one column over.
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                $edit = '<a href="javascript:void(0)" class="exm-act exm-act--edit edit-btn"'
                    . ' data-id="' . (int) $row->pk . '"'
                    . ' data-case_name="' . e($row->case_name) . '"'
                    . ' data-active_inactive="' . (int) $row->active_inactive . '"'
                    . ' title="Edit" aria-label="Edit medical case">'
                    . '<span class="exm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="exm-act__label">Edit</span></a>';

                // No .form-check/.form-switch wrapper (§3b trap 1): custom.css
                // pulls a .form-check-input inside one left by -2.375rem, which
                // is right for switch-beside-label and wrong for this layout.
                $toggle = '<label class="exm-act exm-act--toggle" title="' . $toggleLabel . '">'
                    . '<span class="exm-act__icon">'
                    . '<input class="form-check-input plain-status-toggle" type="checkbox" role="switch"'
                    . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                    . ' aria-label="' . $toggleLabel . ' medical case">'
                    . '</span>'
                    . '<span class="exm-act__label">' . $toggleLabel . '</span></label>';

                // Mirror the rule the page enforces: an active case cannot be
                // deleted, so the control is muted and inert rather than
                // red-and-always-failing.
                $delete = $isActive
                    ? '<span class="exm-act exm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this medical case before deleting">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></span>'
                    : '<a href="javascript:void(0)" class="exm-act exm-act--del delete-btn"'
                        . ' data-id="' . (int) $row->pk . '" aria-disabled="false"'
                        . ' title="Delete" aria-label="Delete medical case">'
                        . '<span class="exm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="exm-act__label">Delete</span></a>';

                return '<div class="exm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete
                    . '</div>';
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
