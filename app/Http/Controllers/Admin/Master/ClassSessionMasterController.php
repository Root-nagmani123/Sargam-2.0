<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSessionMaster;
use App\Http\Requests\ClassSessionMasterRequest;

class ClassSessionMasterController extends Controller
{
    function index() {

        $classSessionMaster = ClassSessionMaster::paginate(10);
        return view('admin.master.class_session_master.index', compact('classSessionMaster'));
    }

    function create(Request $request) {
        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.master.class_session_master._form');
        }

        return redirect()->route('master.class.session.index', ['open_csm_modal' => 'add']);
    }

    function store(ClassSessionMasterRequest $request) {
        
        try {
            
            if($request->id) {
                $classSessionMaster = ClassSessionMaster::find(decrypt($request->id));
                $message = 'Class session updated successfully.';
            } else {
                $classSessionMaster = new ClassSessionMaster();
                $message = 'Class session created successfully.';
            }
            
            $classSessionMaster->shift_name = $request->shift_name;
            $classSessionMaster->start_time = $request->start_time;
            $classSessionMaster->end_time = $request->end_time;
            
            $classSessionMaster->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('master.class.session.index')->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error',$e->getMessage())->withInput();
        }
    }

    function edit(String $id, Request $request) {
        try {
            $classSessionMaster = ClassSessionMaster::find(decrypt($id));
            if (!$classSessionMaster) {
                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['message' => 'Class session not found.'], 404);
                }

                return redirect()->route('master.class.session.index')->with('error', 'Class session not found.');
            }

            if ($request->ajax() || $request->expectsJson()) {
                return view('admin.master.class_session_master._form', compact('classSessionMaster'));
            }

            return redirect()->route('master.class.session.index', [
                'open_csm_modal' => 'edit',
                'csm_id' => $id,
            ]);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

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
