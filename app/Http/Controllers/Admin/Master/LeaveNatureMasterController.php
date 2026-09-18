<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\LeaveNatureMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Nature Leave Master — the natures each leave form offers.
 *
 * Rows are bucketed by leave_type: what is filed under "Leave" is what the
 * Training Section's Apply Leave on Behalf of OT page lists, and the other two
 * buckets feed the officer trainee's own PT Exemption and Stationed Leave forms.
 */
class LeaveNatureMasterController extends Controller
{
    public function index()
    {
        return view('admin.master.leave_nature_master.index', [
            'leaveTypes' => LeaveNatureMaster::TYPE_LABELS,
        ]);
    }

    public function datatable(Request $request)
    {
        /* Status toggle from the grid */
        if ($request->filled('pk') && $request->filled('active_inactive') && $request->active_inactive != 2) {
            LeaveNatureMaster::whereKey($request->pk)->update([
                'active_inactive' => (int) $request->active_inactive,
                'modified_date' => now(),
            ]);
        }

        /* Delete from the grid */
        if ($request->filled('pk') && $request->active_inactive == 2) {
            LeaveNatureMaster::whereKey($request->pk)->delete();
        }

        $query = LeaveNatureMaster::query()
            ->when($request->filled('leave_type_filter'), fn ($q) => $q->where('leave_type', $request->input('leave_type_filter')))
            ->orderBy('leave_type')
            ->orderBy('display_order')
            ->orderByDesc('pk');

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (! empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nature_name', 'LIKE', "%{$search}%")
                            ->orWhere('leave_type', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->addColumn('leave_type_label', fn ($row) => e($row->leave_type_label))
            ->addColumn('nature_name', fn ($row) => e($row->nature_name ?? 'N/A'))
            ->addColumn('display_order', fn ($row) => (int) $row->display_order)
            ->addColumn('status', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                return '<div class="form-check form-switch d-inline-block programme-action-switch">'
                    . '<input class="form-check-input plain-status-toggle" type="checkbox" data-id="' . $row->pk . '" ' . $checked . '>'
                    . '</div>';
            })
            ->addColumn('action', function ($row) {
                $disabled = (int) $row->active_inactive === 1 ? 'disabled aria-disabled="true"' : '';

                return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Row actions">'
                    . '<a href="javascript:void(0)" class="btn btn-sm edit-btn btn-outline-primary d-inline-flex align-items-center gap-1"'
                    . ' data-id="' . $row->pk . '"'
                    . ' data-leave_type="' . e($row->leave_type) . '"'
                    . ' data-nature_name="' . e($row->nature_name) . '"'
                    . ' data-display_order="' . (int) $row->display_order . '"'
                    . ' data-active_inactive="' . (int) $row->active_inactive . '"'
                    . ' aria-label="Edit nature"><i class="bi bi-pencil"></i><span class="d-none d-md-inline">Edit</span></a>'
                    . '<a href="javascript:void(0)" class="btn btn-sm btn-outline-danger delete-btn d-inline-flex align-items-center gap-1 ' . $disabled . '"'
                    . ' data-id="' . $row->pk . '" aria-disabled="' . ((int) $row->active_inactive === 1 ? 'true' : 'false') . '">'
                    . '<i class="bi bi-trash"></i><span class="d-none d-md-inline">Delete</span></a>'
                    . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            $id = $request->input('id');

            $validated = $request->validate([
                'leave_type' => ['required', Rule::in(array_keys(LeaveNatureMaster::TYPE_LABELS))],
                // Unique per bucket, not globally: "Medical" legitimately exists
                // under both PT Exemption and Stationed Leave today.
                'nature_name' => [
                    'required', 'string', 'max:150',
                    Rule::unique('leave_nature_master', 'nature_name')
                        ->where(fn ($q) => $q->where('leave_type', $request->input('leave_type')))
                        ->ignore($id, 'pk'),
                ],
                'display_order' => 'nullable|integer|min:0|max:9999',
                'status' => 'required|in:0,1',
            ], [
                'nature_name.unique' => 'This nature already exists for the selected leave type.',
            ]);

            $data = [
                'leave_type' => $validated['leave_type'],
                'nature_name' => $validated['nature_name'],
                'display_order' => (int) ($validated['display_order'] ?? 0),
                'active_inactive' => (int) $validated['status'],
                'modified_date' => now(),
            ];

            if ($id) {
                LeaveNatureMaster::findOrFail($id)->update($data);

                return response()->json([
                    'status' => true,
                    'type' => 'update',
                    'message' => 'Nature of leave updated successfully.',
                ]);
            }

            LeaveNatureMaster::create($data + ['created_date' => now()]);

            return response()->json([
                'status' => true,
                'type' => 'create',
                'message' => 'Nature of leave created successfully.',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        LeaveNatureMaster::destroy(decrypt($id));

        return redirect()->route('master.leave.nature.master.index')
            ->with('success', 'Nature of leave deleted successfully.');
    }
}
