<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FacultyTypeMaster;
use App\DataTables\Master\FacultyTypeMasterDataTable;
use PhpParser\Node\Stmt\Catch_;

class FacultyTypeMasterController extends Controller
{
    function index(FacultyTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.faculty_type.index');
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
                $facultyType = new FacultyTypeMaster();
            }

            $facultyType->faculty_type_name = $request->faculty_type_name;
            $facultyType->shot_faculty_type_name = $request->shot_faculty_type_name;
            $facultyType->save();
            
            $message = $request->pk ? 'Faculty Type updated successfully.' : 'Faculty Type created successfully.';
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->route('master.faculty.type.master.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    function edit($id)
    {
        $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));
        return view('admin.master.faculty_type.create', compact('facultyType'));
    }

    function delete(Request $request, $id)
    {
        try {
            $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));
            $facultyType->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Faculty Type deleted successfully.'
                ]);
            }
            
            return redirect()->route('master.faculty.type.master.index')->with('success', 'Faculty Type deleted successfully');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete faculty type. ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
