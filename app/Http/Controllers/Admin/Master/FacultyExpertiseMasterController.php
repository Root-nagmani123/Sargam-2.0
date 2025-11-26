<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FacultyExpertiseMaster;
use Stringable;

class FacultyExpertiseMasterController extends Controller
{
    public function index() {
        $faculties = FacultyExpertiseMaster::latest('pk')->paginate(10);
        return view("admin.master.faculty_expertise_master.index", compact('faculties'));
    }

    public function create() {
        return view("admin.master.faculty_expertise_master.create");
    }

    public function store(Request $request) {
        $request->validate([
            'expertise_name' => 'required|string|max:255|unique:faculty_expertise_master,expertise_name',
        ]);

        if( $request->id ) {

            // Update existing record
            $id = decrypt($request->id);
            $expertise = FacultyExpertiseMaster::find($id);
        }
        else {
            // Create new record
            $expertise = new FacultyExpertiseMaster();
            $expertise->created_date = now();
        }
        $expertise->expertise_name = $request->expertise_name;
        $expertise->created_by = auth()->user()->id;
        $expertise->save();

        return redirect()->route('master.faculty.expertise.index')->with('success', 'Expertise saved successfully.');
    }

    public function edit(String $id) {
        if( !$id ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Invalid request.');
        }
        $expertise = FacultyExpertiseMaster::find(decrypt($id));
        if( !$expertise ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Expertise not found.');
        }

        return view("admin.master.faculty_expertise_master.create", compact('expertise'));
    }

    public function delete(String $id) {

        if( !$id ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Invalid request.');
        }
        $expertise = FacultyExpertiseMaster::find(decrypt($id));
        if( !$expertise ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Expertise not found.');
        }
        if( $expertise->delete() ) {
            return redirect()->route('master.faculty.expertise.index')->with('success', 'Expertise deleted successfully.');
        }
        else {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Failed to delete expertise.');
        }
    }
}
