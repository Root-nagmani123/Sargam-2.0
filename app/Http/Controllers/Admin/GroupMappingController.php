<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\GroupMapping\GroupMappingMultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{CourseMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};

class GroupMappingController extends Controller
{
    function index()
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::withCount('studentCourseGroupMap')->get();
        return view('admin.group_mapping.index', compact('groupTypeMaster'));
    }


    function create()
    {
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();
        return view('admin.group_mapping.create', compact('courses', 'courseGroupTypeMaster'));
    }

    function edit(string $id)
    {
        $groupMapping = GroupTypeMasterCourseMasterMap::find(decrypt($id));
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();
        return view('admin.group_mapping.create', compact('groupMapping', 'courses', 'courseGroupTypeMaster'));
    }

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

    function studentList(Request $request)
    {
        try {
            $request->validate([
                'groupMappingID' => 'required|string|max:255',
            ]);

            $groupMappingID = decrypt($request->groupMappingID);
            $groupMapping = GroupTypeMasterCourseMasterMap::findOrFail($groupMappingID);

            $students = StudentCourseGroupMap::with('studentsMaster')
                ->where('group_type_master_course_master_map_pk', $groupMapping->pk)
                ->paginate(10); // Set items per page

            
            $html = view('admin.group_mapping.student_list_ajax', compact('students'))->render();

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
}
