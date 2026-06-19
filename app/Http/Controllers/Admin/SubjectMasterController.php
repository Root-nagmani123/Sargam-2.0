<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SubjectMaster;
use Illuminate\Http\Request;


class SubjectMasterController extends Controller
{
    public function index()
    {
        $search = request('search');

        $perPage = (int) request('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $subjects = SubjectMaster::when($search, function ($q) use ($search) {
            $q->where('subject_name', 'like', "%$search%")
              ->orWhere('sub_short_name', 'like', "%$search%");
        })
        ->orderBy('created_date', 'desc')
        ->paginate($perPage)
        ->appends(['search' => $search, 'per_page' => $perPage]);

        $smSubjectEditData = [];
        foreach ($subjects as $subject) {
            $smSubjectEditData[$subject->pk] = [
                'major_subject_name' => $subject->subject_name,
                'short_name' => $subject->sub_short_name,
                'status' => $subject->active_inactive,
            ];
        }
        if (request()->filled('open_edit_subject')) {
            $extra = SubjectMaster::find(request('open_edit_subject'));
            if ($extra) {
                $smSubjectEditData[$extra->pk] = [
                    'major_subject_name' => $extra->subject_name,
                    'short_name' => $extra->sub_short_name,
                    'status' => $extra->active_inactive,
                ];
            }
        }

        return view('admin.subject.index', compact('subjects', 'smSubjectEditData'));
    }

    // Show the form for creating a new subject
    public function create()
    {
        return redirect()->route('subject.index', ['open_add_subject' => 1]);
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

        if (!$subject) {
            return redirect()->route('subject.index')->with('error', 'Subject not found.');
        }

        return redirect()->route('subject.index', ['open_edit_subject' => $id]);
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