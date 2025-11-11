<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SubjectMaster;
use Illuminate\Http\Request;


class SubjectMasterController extends Controller
{
    public function index()
    {
        $subjects = SubjectMaster::all();
        return view('admin.subject.index', compact('subjects'));
    }

    // Show the form for creating a new subject
    public function create()
    {
        return view('admin.subject.create');
    }

    // Store a newly created subject
    public function store(Request $request)
{
    // Validation rules
    $request->validate([
        'major_subject_name' => 'required|string|max:255',
        'short_name' => 'required|string|max:100',
    ]);

    // Data insertion
    $subject = new SubjectMaster();
    $subject->subject_name = $request->major_subject_name;
    $subject->sub_short_name = $request->short_name;
    $subject->active_inactive = (int) $request->input('status', 1);

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
    
        return view('admin.subject.edit', compact('subject'));
    }

    // Update the subject
    public function update(Request $request, $id)
{
    // Validate the form data
    $request->validate([
        'major_subject_name' => 'required|string|max:255',
        'short_name' => 'required|string|max:100',
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
    $subject->active_inactive = (int) $request->input('status', 1);

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
