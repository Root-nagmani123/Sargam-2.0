<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseGroupTypeMaster;

class CourseGroupTypeController extends Controller
{
function index()
{
    $courseGroupTypeMaster = CourseGroupTypeMaster::orderBy('pk', 'DESC')->get();
    return view('admin.master.course_group_type_master.index', compact('courseGroupTypeMaster'));
}


    function create()
    {
        return view('admin.master.course_group_type_master.create');
    }
    function store(Request $request)
    {
        try{
            $request->validate([
                'type_name' => 'required|string|max:255',
            ]);
            
            if( $request->id != null){
                $course = CourseGroupTypeMaster::find(decrypt( $request->id ));
                if (!$course) {
                    return redirect()->route('master.course.group.type.index')->with('error', 'Course group type not found.');
                }
                $course->update($request->only('type_name'));
            }else{
                $course = CourseGroupTypeMaster::create($request->all());
            }
            return redirect()->route('master.course.group.type.index')->with('success', 'Course Group Type created successfully.');
        }
        catch(\Exception $e){
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
        
    }
    function edit($id)
    {
        try {
            $courseGroupTypeMaster = CourseGroupTypeMaster::find(decrypt($id));
            if (!$courseGroupTypeMaster) {
                return redirect()->route('master.course.group.type.index')->with('error', 'Course group type not found.');
            }
            return view('admin.master.course_group_type_master.create', compact('courseGroupTypeMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
    }
    
    function delete($id)
    {
        try {
            $courseGroupTypeMaster = CourseGroupTypeMaster::find(decrypt($id));
            if (!$courseGroupTypeMaster) {
                return redirect()->route('master.course.group.type.index')->with('error', 'Course group type not found.');
            }
            $courseGroupTypeMaster->delete();
            return redirect()->route('master.course.group.type.index')->with('success', 'Course Group Type deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
        
    }
}
