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

            // Status Badge
            ->addColumn('status', function ($row) {
                if ($row->active_inactive == 1) {
                    return '<div class="text-center"><span class="cgt-badge-active">Active</span></div>';
                }
                return '<div class="text-center"><span class="cgt-badge-inactive">Inactive</span></div>';
            })

            // Action Icons
            ->addColumn('action', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';

                return '
    <div class="d-flex align-items-center justify-content-center gap-2" role="group" aria-label="Row actions">
        <a href="javascript:void(0)" data-id="' . $row->pk . '" data-type-name="' . e($row->type_name) . '"
           class="edit-btn cgt-action-btn cgt-action-edit border-0 p-0 bg-transparent" title="Edit" aria-label="Edit">
            <i class="material-icons material-symbols-rounded">edit</i>
        </a>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input plain-status-toggle" type="checkbox" role="switch"
                   data-id="' . $row->pk . '" title="Toggle status" aria-label="Toggle status" ' . $checked . '>
        </div>
        <a href="javascript:void(0)" data-id="' . $row->pk . '"
           class="delete-btn cgt-action-btn cgt-action-delete border-0 p-0 bg-transparent" title="Delete" aria-label="Delete">
            <i class="material-icons material-symbols-rounded">delete</i>
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