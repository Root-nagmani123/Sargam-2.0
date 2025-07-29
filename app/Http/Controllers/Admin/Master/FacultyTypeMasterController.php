<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FacultyTypeMaster;
use PhpParser\Node\Stmt\Catch_;

class FacultyTypeMasterController extends Controller
{
    function __construct() {
        $this->middleware('permission:master.faculty-type-master.index', ['only' => ['index']]);
        $this->middleware('permission:master.faculty-type-master.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:master.faculty-type-master.edit', ['only' => ['edit', 'store']]);
        $this->middleware('permission:master.faculty-type-master.delete', ['only' => ['delete']]);
    }

    function index()
    {
        $facultyTypes = FacultyTypeMaster::all();
        return view('admin.master.faculty_type.index', compact('facultyTypes'));
    }

    function create()
    {
        return view('admin.master.faculty_type.create');
    }

    function store(Request $request)
    {
        $request->validate([
            'faculty_type_name' => 'required|string|max:255',
            'shot_faculty_type_name' => 'required|string|max:255',
        ]);
        try {
            if ($request->pk) {
                $facultyType = FacultyTypeMaster::findOrFail(decrypt($request->pk));
            } else {
                $facultyType = FacultyTypeMaster::create($request->all());
            }

            $facultyType->faculty_type_name = $request->faculty_type_name;
            $facultyType->shot_faculty_type_name = $request->shot_faculty_type_name;
            $facultyType->save();
            return redirect()->route('master.faculty.type.master.index')->with('success', 'Faculty Type created successfully');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    function edit($id)
    {
        $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));
        return view('admin.master.faculty_type.create', compact('facultyType'));
    }

    function delete($id)
    {
        try {
            $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));
            $facultyType->delete();
            return redirect()->route('master.faculty.type.master.index')->with('success', 'Faculty Type deleted successfully');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
