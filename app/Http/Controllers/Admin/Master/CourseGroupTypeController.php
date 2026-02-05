<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseGroupTypeMaster;
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
        }

        // DELETE ROW
        if ($request->has('pk') && $request->active_inactive == 2) {
            CourseGroupTypeMaster::where('pk', $request->pk)->delete();
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
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                $badgeClass = $row->active_inactive == 1 ? 'bg-success' : 'bg-secondary';
                $badgeText = $row->active_inactive == 1 ? 'Active' : 'Inactive';

                return '
                <div class="d-flex align-items-center gap-2">
                    <span class="badge ' . $badgeClass . ' text-uppercase fw-semibold" style="font-size: 0.65rem; padding: 0.35em 0.6em;">' . $badgeText . '</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input plain-status-toggle" type="checkbox" role="switch"
                            data-table="course_group_type_master"
                            data-column="active_inactive"
                            data-id="' . $row->pk . '"
                            ' . $checked . '>
                    </div>
                </div>';
            })

            // Action Dropdown
            ->addColumn('action', function ($row) {
                $disabled = $row->active_inactive == 1 ? 'disabled-link' : '';
                $disabledAttr = $row->active_inactive == 1 ? 'disabled aria-disabled="true"' : '';

                return '
                <div class="btn-group btn-group-sm" role="group" aria-label="Row actions">
                    <a href="javascript:void(0)" data-id="' . $row->pk . '" data-type-name="' . e($row->type_name) . '"
                       class="btn btn-outline-primary edit-btn rounded-start-2 d-inline-flex align-items-center gap-1 px-3"
                       aria-label="Edit course group type">
                        <i class="material-icons material-symbols-rounded" style="font-size: 1.1rem;">edit</i>
                        <span class="d-none d-lg-inline">Edit</span>
                    </a>
                    <a href="javascript:void(0)" data-id="' . $row->pk . '"
                       class="btn btn-outline-danger delete-btn rounded-end-2 d-inline-flex align-items-center gap-1 px-3 ' . $disabled . '"
                       ' . $disabledAttr . '
                       aria-disabled="' . ($row->active_inactive == 1 ? 'true' : 'false') . '">
                        <i class="material-icons material-symbols-rounded" style="font-size: 1.1rem;">delete</i>
                        <span class="d-none d-lg-inline">Delete</span>
                    </a>
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

                return response()->json([
                    'status' => true,
                    'message' => 'Course Group Type updated successfully.'
                ]);
            }

            // CREATE
            CourseGroupTypeMaster::create([
                'type_name' => $request->type_name
            ]);

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

            return redirect()->route('master.course.group.type.index')
                ->with('success', 'Course Group Type deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
