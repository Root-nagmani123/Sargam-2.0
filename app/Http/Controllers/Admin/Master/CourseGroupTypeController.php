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

            return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input plain-status-toggle" type="checkbox" role="switch"
                        data-table="course_group_type_master"
                        data-column="active_inactive"
                        data-id="' . $row->pk . '"
                        ' . $checked . '>
                </div>';
        })

        // Action Dropdown
        ->addColumn('action', function ($row) {

            $disabled = $row->active_inactive == 1 ? 'disabled' : '';

            return '
                <div class="dropdown">
                    <a href="#" class="text-dark" data-bs-toggle="dropdown">
                        <i class="material-icons">more_horiz</i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item edit-btn" href="javascript:void(0)"
                               data-id="' . $row->pk . '"
                               data-type-name="' . $row->type_name . '">
                                <i class="material-icons me-2" style="font-size:18px;">edit</i>
                                Edit
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item delete-btn ' . $disabled . '" href="javascript:void(0)"
                               data-id="' . $row->pk . '">
                                <i class="material-icons me-2" style="font-size:18px;">delete</i>
                                Delete
                            </a>
                        </li>
                    </ul>
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

    function store(Request $request)
    {
        try {
            $request->validate([
                'type_name' => 'required|string|max:255',
            ]);

            if ($request->id != null) {
                $course = CourseGroupTypeMaster::find(decrypt($request->id));

                if (!$course) {
                    return redirect()->route('master.course.group.type.index')
                        ->with('error', 'Course group type not found.');
                }

                $course->update($request->only('type_name'));
            } else {
                CourseGroupTypeMaster::create($request->all());
            }

            return redirect()->route('master.course.group.type.index')
                ->with('success', 'Course Group Type saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
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
