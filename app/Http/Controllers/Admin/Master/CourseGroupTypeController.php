<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseGroupTypeMaster;
use App\DataTables\GroupMappingDataTable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;


class CourseGroupTypeController extends Controller
{
    function index(Request $request)
    {
        // Optional: Search support
        $query = CourseGroupTypeMaster::query();

        if ($request->search) {
            $query->where('type_name', 'LIKE', "%{$request->search}%");
        }

        // Pagination added here
        $courseGroupTypeMaster = $query->orderBy('pk', 'desc')->paginate(10);

        return view('admin.master.course_group_type_master.index');
    }

    public function grouptypeview(Request $request)
    {
        // UPDATE STATUS (Active / Inactive)
        if ($request->has('pk') && $request->has('active_inactive') && $request->active_inactive != 2) {
            CourseGroupTypeMaster::where('pk', $request->pk)
                ->update([
                    'active_inactive' => $request->active_inactive
                ]);

            // Group Type dropdowns on the Group Mapping page are cached against this
            // epoch; bump it so a status change is reflected there without waiting
            // for the cache TTL.
            GroupMappingDataTable::bumpListingCacheEpoch();
        }

        // DELETE ROW
        if ($request->has('pk') && $request->active_inactive == 2) {
            CourseGroupTypeMaster::where('pk', $request->pk)->delete();
            GroupMappingDataTable::bumpListingCacheEpoch();
        }

        // DataTable SELECT QUERY
        $query = CourseGroupTypeMaster::select(['pk', 'type_name', 'active_inactive'])
            ->orderByDesc('pk');

        return DataTables::of($query)
            ->addIndexColumn()

            // 🔍 GLOBAL SEARCH
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where('type_name', 'LIKE', "%{$search}%");
                }
            })

            // Type Name
            ->addColumn('type_name', function ($row) {
                return $row->type_name ?? 'N/A';
            })

            // Status Toggle
            // Status: soft badge, display only. data-order lets a client-side sort
            // order by state (docs/new-design-index-page.md 3b).
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })

            // Action: Edit - switch - Delete as equal-width icon-over-label stacks.
            // The switch carries NO .form-check.form-switch wrapper: that rule yanks
            // the input -2.375rem left of its caption (3b, trap 1). Delete is guarded
            // the way the server guards it - an active row cannot be deleted, so the
            // control is rendered disabled rather than red-and-always-failing.
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                // The caption names the ACTION, not the state.
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                $deleteHtml = $isActive
                    ? '<span class="prog-act prog-act--del is-disabled" aria-disabled="true"
                            title="Deactivate this group type before deleting">
                            <span class="prog-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                            <span class="prog-act__label">Delete</span>
                       </span>'
                    : '<button type="button" class="prog-act prog-act--del delete-btn" data-id="' . (int) $row->pk . '"
                            aria-disabled="false" title="Delete group type">
                            <span class="prog-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                            <span class="prog-act__label">Delete</span>
                       </button>';

                return '
                <div class="prog-act-group" role="group" aria-label="Row actions">
                    <button type="button" class="prog-act prog-act--edit edit-btn" title="Edit"
                        data-id="' . (int) $row->pk . '" data-type-name="' . e((string) $row->type_name) . '">
                        <span class="prog-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                        <span class="prog-act__label">Edit</span>
                    </button>
                    <label class="prog-act prog-act--toggle" title="' . $toggleLabel . ' group type">
                        <span class="prog-act__icon">
                            <input class="form-check-input plain-status-toggle" type="checkbox" role="switch"
                                data-table="course_group_type_master" data-column="active_inactive"
                                data-id="' . (int) $row->pk . '" ' . $checked . '>
                        </span>
                        <span class="prog-act__label">' . $toggleLabel . '</span>
                    </label>
                    ' . $deleteHtml . '
                </div>';
            })

            // Allow HTML
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function updateStatus(Request $request)
    {
        $table = 'course_group_type_master';
        try {
            DB::table($table)
                ->where('pk', $request->pk)
                ->update([
                    'type_name'       => $request->type_name,
                    'active_inactive' => $request->has('active_inactive') ? 1 : 0,
                ]);

            GroupMappingDataTable::bumpListingCacheEpoch();

            return redirect()->back()->with('success', 'Course Group Type updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Course Group Type not correct');
        }
    }

    function create()
    {
        return view('admin.master.course_group_type_master.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'type_name' => 'required|string|max:255',
            ]);

            // UPDATE
            if ($request->filled('id')) {

                $course = CourseGroupTypeMaster::find($request->id);

                if (!$course) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Course group type not found.'
                    ], 404);
                }

                $course->update([
                    'type_name' => $request->type_name
                ]);

                // Invalidate the cached Group Type dropdowns on the Group Mapping page.
                GroupMappingDataTable::bumpListingCacheEpoch();

                return response()->json([
                    'status' => true,
                    'message' => 'Course Group Type updated successfully.'
                ]);
            }

            // CREATE
            CourseGroupTypeMaster::create([
                'type_name' => $request->type_name
            ]);

            // Invalidate the cached Group Type dropdowns so the new type appears on
            // the Group Mapping page immediately (esp. on live, where Redis caching
            // is enabled) instead of waiting for the cache to expire.
            GroupMappingDataTable::bumpListingCacheEpoch();

            return response()->json([
                'status' => true,
                'message' => 'Course Group Type added successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    function edit($id)
    {
        try {
            $courseGroupTypeMaster = CourseGroupTypeMaster::find(decrypt($id));

            if (!$courseGroupTypeMaster) {
                return redirect()->route('master.course.group.type.index')
                    ->with('error', 'Course group type not found.');
            }

            return view('admin.master.course_group_type_master.create', compact('courseGroupTypeMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    function delete($id)
    {
        try {
            $courseGroupTypeMaster = CourseGroupTypeMaster::find(decrypt($id));

            if (!$courseGroupTypeMaster) {
                return redirect()->route('master.course.group.type.index')
                    ->with('error', 'Course group type not found.');
            }

            $courseGroupTypeMaster->delete();

            GroupMappingDataTable::bumpListingCacheEpoch();

            return redirect()->route('master.course.group.type.index')
                ->with('success', 'Course Group Type deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}