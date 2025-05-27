<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentMedicalExemption;

class StudentMedicalExemptionController extends Controller
{
    public function index()
    {
        $records = StudentMedicalExemption::all();
        return view('admin.student_medical_exemption.index', compact('records'));
    }

    public function create()
    {
        return view('admin.student_medical_exemption.create_edit');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|numeric',
            'student_master_pk' => 'required|numeric',
            'employee_master_pk' => 'required|numeric',
            'exemption_category_master_pk' => 'required|numeric',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'opd_category' => 'nullable|string|max:50',
            'exemption_medical_speciality_pk' => 'required|numeric',
            'Description' => 'nullable|string',
            'active_inactive' => 'required|boolean',
        ]);

        // File upload
        if ($request->hasFile('Doc_upload')) {
            $file = $request->file('Doc_upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/exemptions', $filename, 'public');
            $validated['Doc_upload'] = $path;
        }

        if ($request->id) {
            $record = StudentMedicalExemption::findOrFail(decrypt($request->id));
            $record->update($validated);
            $msg = "Record updated successfully.";
        } else {
            StudentMedicalExemption::create($validated);
            $msg = "Record created successfully.";
        }

        return redirect()->route('student.medical.exemption.index')->with('success', $msg);
    }

    public function edit($id)
    {
        $record = StudentMedicalExemption::findOrFail(decrypt($id));
        return view('admin.student_medical_exemption.create_edit', compact('record'));
    }

    public function delete($id)
    {
        StudentMedicalExemption::destroy(decrypt($id));
        return redirect()->route('student.medical.exemption.index')->with('success', 'Deleted successfully.');
    }
}
