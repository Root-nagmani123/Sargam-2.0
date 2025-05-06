<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSessionMaster;
use App\Http\Requests\ClassSessionMasterRequest;

class ClassSessionMasterController extends Controller
{
    function index() {

        $classSessionMaster = ClassSessionMaster::all();
        return view('admin.class_session_master.index', compact('classSessionMaster'));
    }
    function create() {
        return view('admin.class_session_master.create');
    }
    function store(ClassSessionMasterRequest $request) {
        
        try {
            
            $classSessionMaster = new ClassSessionMaster();
            $classSessionMaster->shift_name = $request->shift_name;
            $classSessionMaster->start_time = $request->start_time;
            $classSessionMaster->end_time = $request->end_time;
            
            $classSessionMaster->save();

            return redirect()->route('master.class.session.index')->with('success', 'Class session saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
        

        
    }
    function edit(String $id) {
        try {
            $classSessionMaster = ClassSessionMaster::find(decrypt($id));
            if (!$classSessionMaster) {
                return redirect()->route('master.class.session.index')->with('error', 'Class session not found.');
            }
            return view('admin.class_session_master.create', compact('classSessionMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
    }
    
    function delete(String $id) {

        try {
            $classSessionMaster = ClassSessionMaster::findOrFail(decrypt($id));
            $classSessionMaster->delete();
            return redirect()->route('master.class.session.index')->with('success', 'Class session deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
    }
}
