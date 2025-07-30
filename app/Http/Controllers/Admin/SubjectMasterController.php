<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SubjectMaster;
use App\Models\SubjectModuleMaster;
use Illuminate\Http\Request;


class SubjectMasterController extends Controller
{
    function __construct() {
        $this->middleware('permission:subject.index', ['only' => ['index']]);
        $this->middleware('permission:subject.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subject.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subject.delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        $subjects = SubjectMaster::all();
        return view('admin.subject.index', compact('subjects'));
    }

    // Show the form for creating a new subject
    public function create()
    { 
        $subjects = SubjectModuleMaster::where('active_inactive', 1)
                              ->get();
        
        return view('admin.subject.create',compact('subjects'));
    }

    // Store a newly created subject
    public function store(Request $request)
{
    // Validation rules
    $request->validate([
        'major_subject_name' => 'required|string|max:255',
        'short_name' => 'required|string|max:100',
         'subject_module' => 'required|exists:subject_module_master,pk',
    ]);

    // Data insertion
    $subject = new SubjectMaster();
    $subject->subject_name = $request->major_subject_name;
    $subject->sub_short_name = $request->short_name;
    $subject->subject_module_master_pk = $request->subject_module;
    $subject->active_inactive = $request->has('status') ? 1 : 0;

    $subject->save();

    // Redirect with success message
    return redirect()->route('subject.index')->with('success', 'Subject added successfully.');
}


    // Show the form for editing the subject
    public function edit($id)
    {
        $subject = SubjectMaster::find($id);

        // Check if record exists
        if (!$subject) {
            return redirect()->route('subject.index')->with('error', 'Subject not found.');
        }
    
        // Fetch the subject modules to populate the select box
        $subjects = SubjectModuleMaster::all();
        return view('admin.subject.edit', compact('subject', 'subjects'));
    }

    // Update the subject
    public function update(Request $request, $id)
{
    // Validate the form data
    $request->validate([
        'major_subject_name' => 'required|string|max:255',
        'short_name' => 'required|string|max:100',
      
        'subject_module' => 'required|exists:subject_module_master,pk',
    ]);

    // Find the subject record by ID
    $subject = SubjectMaster::find($id);

    // If subject not found, redirect with error
    if (!$subject) {
        return redirect()->route('subject.index')->with('error', 'Subject not found.');
    }

    // Update the subject record with the new data
    $subject->subject_name = $request->major_subject_name;
    $subject->sub_short_name = $request->short_name;
    $subject->subject_module_master_pk = $request->subject_module;
    $subject->active_inactive = $request->has('status') ? 1 : 0;

    // Save the updated record
    $subject->save();

    // Redirect to the index page with a success message
    return redirect()->route('subject.index')->with('success', 'Subject updated successfully.');
}

    // Delete a subject
    public function destroy($id)
    {
        $subject = SubjectMaster::findOrFail($id);
        $subject->delete();

        return redirect()->route('subject.index')->with('success', 'Subject deleted successfully.');
    }
}
