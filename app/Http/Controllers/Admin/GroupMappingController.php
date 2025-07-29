<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\GroupMapping\GroupMappingMultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{CourseMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};
use App\Exports\GroupMappingExport;
use App\DataTables\GroupMappingDataTable;

class GroupMappingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:group.mapping.index', ['only' => ['index']]);
        $this->middleware('permission:group.mapping.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:group.mapping.edit', ['only' => ['edit', 'store']]);
        $this->middleware('permission:group.mapping.delete', ['only' => ['delete']]);
        $this->middleware('permission:group.mapping.import_excel', ['only' => ['importGroupMapping']]);
        $this->middleware('permission:group.mapping.export_excel', ['only' => ['exportStudentList']]);
    }
    public function index(GroupMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.group_mapping.index');
    }


    /**
     * Show the form for creating a new group mapping.
     *
     * @return \Illuminate\View\View
     */
    function create()
    {
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();
        return view('admin.group_mapping.create', compact('courses', 'courseGroupTypeMaster'));
    }

    /**
     * Show the form for editing an existing group mapping.
     *
     * @param string $id Encrypted group mapping ID
     * @return \Illuminate\View\View
     */
    function edit(string $id)
    {
        $groupMapping = GroupTypeMasterCourseMasterMap::find(decrypt($id));
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();
        return view('admin.group_mapping.create', compact('groupMapping', 'courses', 'courseGroupTypeMaster'));
    }

    /**
     * Store or update a group mapping in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function store(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|string|max:255',
                'type_id' => 'required|string|max:255',
                'group_name' => 'required|string|max:255'
            ]);

            if ($request->pk) {
                $groupMapping = GroupTypeMasterCourseMasterMap::find($request->pk);
                $message = 'Group Mapping updated successfully.';
            } else {
                $groupMapping = new GroupTypeMasterCourseMasterMap();
                $message = 'Group Mapping created successfully.';
            }
            $groupMapping->course_name = $request->course_id;
            $groupMapping->type_name = $request->type_id;
            $groupMapping->group_name = $request->group_name;
            $groupMapping->save();

            return redirect()->route('group.mapping.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Import group mappings from an Excel file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    function importGroupMapping(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10248',
            ]);

            $import = new GroupMappingMultipleSheetImport();

            Excel::import($import, $request->file('file'));

            $failures = $import->sheet1Import->failures;

            if (count($failures) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors found in Excel file.',
                    'failures' => $failures,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Group Mapping imported successfully.',
            ], 200);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Fetch and return a paginated list of students for a specific group mapping.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function studentList(Request $request)
    {
        try {
            $groupMappingID = decrypt($request->groupMappingID);
            $groupMapping = GroupTypeMasterCourseMasterMap::findOrFail($groupMappingID);
            // Apply pagination
            $students = StudentCourseGroupMap::with('studentsMaster:display_name,email,contact_no,pk')
                ->where('group_type_master_course_master_map_pk', $groupMapping->pk)
                ->paginate(10, ['*'], 'page', $request->page);
            // Render the HTML partial
            $groupMappingPk = $groupMapping->pk;
            $html = view('admin.group_mapping.student_list_ajax', compact('students', 'groupMappingPk'))->render();

            return response()->json([
                'status' => 'success',
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Export the student list for group mappings to an Excel file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportStudentList($id = null)
    {
        try {

            if (!$id) {
                return redirect()->back()->with('error', 'Group Mapping ID is required.');
            }

            $fileName = 'group-mapping-export-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            decrypt($id);
            if ($id) {
                return Excel::download(new GroupMappingExport($id), $fileName);
            } else {
                return Excel::download(new GroupMappingExport, $fileName);
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }


    function delete(string $id)
    {
        try {
            
            $groupMapping = GroupTypeMasterCourseMasterMap::findOrFail(decrypt($id));
            $groupMapping->studentCourseGroupMap()->delete();
            $groupMapping->delete();
            
            return redirect()->route('group.mapping.index')->with('success', 'Group Mapping deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
