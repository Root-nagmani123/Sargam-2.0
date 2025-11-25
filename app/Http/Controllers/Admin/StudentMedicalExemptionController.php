<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentMedicalExemption;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use App\Models\ExemptionCategoryMaster;
use App\Models\ExemptionMedicalSpecialityMaster;
use Illuminate\Support\Facades\DB;


class StudentMedicalExemptionController extends Controller
{
    public function index(Request $request)
    {
<<<<<<< HEAD
<<<<<<< HEAD
        $records = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course'])
            ->whereHas('student', function ($q) {
                $q->whereNotNull('display_name')
                    ->where('display_name', '!=', '')
                    ->where('display_name', '!=', 'N/A');
            })
            ->get();
        return view('admin.student_medical_exemption.index', compact('records'));
=======
=======
>>>>>>> 4cf775d655eaca8109fed2fc8506aba2a45111fc
        $statusFilter = $request->input('status_filter', 'active');
        $courseFilter = $request->input('course_filter');
        $dateRangeFilter = $request->input('date_range_filter', 'all'); // all or current
        $currentDate = now()->format('Y-m-d');

        $query = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course']);

        // Filter by course status (active/archive) based on course end_date
        if ($statusFilter === 'active' || empty($statusFilter)) {
            $query->whereHas('course', function ($courseQuery) use ($currentDate) {
                $courseQuery->where(function ($q) use ($currentDate) {
                    $q->whereNull('end_date')
                      ->orWhereDate('end_date', '>=', $currentDate);
                });
            });
        } elseif ($statusFilter === 'archive') {
            $query->whereHas('course', function ($courseQuery) use ($currentDate) {
                $courseQuery->whereNotNull('end_date')
                    ->whereDate('end_date', '<', $currentDate);
            });
        }

        // Filter by specific course
        if (!empty($courseFilter)) {
            $query->where('course_master_pk', $courseFilter);
        }

        // Filter by date range - show only exemptions that are currently active (today's date falls within the range)
        if ($dateRangeFilter === 'current') {
            $query->whereDate('from_date', '<=', $currentDate)
                  ->whereDate('to_date', '>=', $currentDate);
        }

        $records = $query->get();

        // Get all courses for the dropdown
        $courses = CourseMaster::select('pk', 'course_name')
            ->orderBy('course_name')
            ->get()
            ->pluck('course_name', 'pk');
    
        return view('admin.student_medical_exemption.index', compact('records', 'courses'));
<<<<<<< HEAD
>>>>>>> 4cf775d6 (Mdo chnages)
=======
>>>>>>> 4cf775d655eaca8109fed2fc8506aba2a45111fc
    }

    public function create()
    {
        $courses = CourseMaster::where('active_inactive', '1')->get();

        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
        $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

        return view('admin.student_medical_exemption.create', compact('courses', 'categories', 'specialities'));
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

        // Handle file upload if exists
        if ($request->hasFile('Doc_upload')) {
            $file = $request->file('Doc_upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/exemptions', $filename, 'public');
            $validated['Doc_upload'] = $path;
        }

        StudentMedicalExemption::create($validated);

        return redirect()->route('student.medical.exemption.index')->with('success', 'Record created successfully.');
    }


    public function edit($id)
    {
        $record = StudentMedicalExemption::findOrFail(decrypt($id));

        $courses = CourseMaster::where('active_inactive', '1')->get();
        $students = StudentMaster::select('pk', 'generated_OT_code', 'display_name')
            ->where('status', '1')
            ->get();
        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
        $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

        return view('admin.student_medical_exemption.edit', compact('record', 'courses', 'students', 'categories', 'specialities'));
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|numeric',
            'student_master_pk' => 'required|numeric',
            'employee_master_pk' => 'nullable|numeric',
            'exemption_category_master_pk' => 'required|numeric',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'opd_category' => 'nullable|string|max:50',
            'exemption_medical_speciality_pk' => 'required|numeric',
            'Description' => 'nullable|string',
            'active_inactive' => 'required|boolean',
        ]);

        $record = StudentMedicalExemption::findOrFail(decrypt($id));

        if ($request->hasFile('Doc_upload')) {
            $file = $request->file('Doc_upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            $validated['Doc_upload'] = $file->storeAs('uploads/exemptions', $filename, 'public');
        }

        $record->update($validated);

        return redirect()->route('student.medical.exemption.index')->with('success', 'Record updated successfully.');
    }


    public function delete($id)
    {
        StudentMedicalExemption::destroy(decrypt($id));
        return redirect()->route('student.medical.exemption.index')->with('success', 'Deleted successfully.');
    }
    public function getStudentsByCourse(Request $request)
    {
        $courseId = $request->input('course_id');
        $students = DB::table('student_master_course__map')
            ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
            ->where('student_master_course__map.course_master_pk', $courseId)
            ->where('student_master.status', '1')
            ->select('student_master.pk', 'student_master.generated_OT_code', 'student_master.display_name')
            ->get();

        return response()->json(['students' => $students]);
    }
}
