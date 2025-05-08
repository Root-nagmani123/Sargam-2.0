<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\GroupMappingImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{CourseMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap};

class GroupMappingController extends Controller
{
    function index()
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::all();
        return view('admin.group_mapping.index', compact('groupTypeMaster'));
    }


    function create()
    {
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name','pk')->toArray();
        return view('admin.group_mapping.create', compact('courses', 'courseGroupTypeMaster'));
    }

    function edit(String $id)
    {
        $groupMapping = GroupTypeMasterCourseMasterMap::find(decrypt($id));
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name','pk')->toArray();
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
            
            if( $request->pk ) {
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
            
            $import = new GroupMappingImport;
            Excel::import($import, $request->file('file'));

            if (count($import->failures) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors found in Excel file.',
                    'failures' => $import->failures,
                ], 422);
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
