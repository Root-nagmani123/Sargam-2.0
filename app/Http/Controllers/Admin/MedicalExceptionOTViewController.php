<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentMaster;
use App\Models\StudentMedicalExemption;

class MedicalExceptionOTViewController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        $currentDate = now()->format('Y-m-d');
        
        // Check if user_category = 'S' (Student)
        $userCategory = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->value('user_category');
        
        if ($userCategory !== 'S') {
            // If not a student, only show admin view if user is NOT faculty (F)
            // Faculty should use faculty view, not OT view
            if ($userCategory === 'F') {
                return redirect()->back()->with('error', 'Access denied. Faculty members should use the Faculty View page.');
            }
            // Show admin view for other user categories (admin, etc.)
            return $this->adminView($request);
        }
        
        // Get user_id from user_credentials (which points to student_master.pk)
        $studentMasterPk = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->value('user_id');
        
        if (!$studentMasterPk) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Match with student_master table
        $student = StudentMaster::where('pk', $studentMasterPk)->first();
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Count how many times the student exists in student_medical_exemption
        $exemptionCount = StudentMedicalExemption::where('student_master_pk', $studentMasterPk)
            ->count();
        
        // Exemptions on a running course, newest first.
        //
        // The course filter is a join rather than a per-row CourseMaster::find, and
        // the doctor / category / speciality names come through eager-loaded
        // relations — this used to be one query per exemption plus three lookups
        // the view never had.
        $exemptions = StudentMedicalExemption::query()
            ->with(['employee', 'category', 'speciality'])
            ->join('course_master as cm', 'cm.pk', '=', 'student_medical_exemption.course_master_pk')
            ->where('student_medical_exemption.student_master_pk', $studentMasterPk)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', '>=', $currentDate)
            ->orderByDesc('student_medical_exemption.from_date')
            ->get(['student_medical_exemption.*', 'cm.course_name']);

        // from_date / to_date are datetime columns, so the date and the time asked
        // for are two views of one value, not two fields.
        $validExemptions = $exemptions->map(fn ($exemption) => [
            'course_name' => $exemption->course_name,
            'doctor_name' => $this->doctorName($exemption->employee),
            'from_date' => $exemption->from_date,
            'to_date' => $exemption->to_date,
            'exemption_category' => $exemption->category->exemp_category_name ?? null,
            'medical_speciality' => $exemption->speciality->speciality_name ?? null,
            'opd_category' => $exemption->opd_category,
            'description' => $exemption->Description,
            'doc_upload' => $exemption->Doc_upload,
        ])->all();

        // Prepare data for view
        $studentData = [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email,
            'total_exemption_count' => $exemptionCount, // Total count of all exceptions
            'exemptions' => $validExemptions, // Only valid exemptions (with active courses)
            'has_exemptions' => count($validExemptions) > 0, // Flag to check if student has any valid exemptions
        ];
        
        return view('admin.medical_exception.ot_view', compact('studentData'));
    }
    
    /**
     * Treating doctor's display name — employee_master keeps it as first/last,
     * unlike faculty_master which has a full_name.
     */
    private function doctorName($employee): ?string
    {
        if (! $employee) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $employee->first_name ?? '',
            $employee->last_name ?? '',
        ]))) ?: null;
    }

    /**
     * Admin view for non-student users (original functionality)
     */
    private function adminView(Request $request)
    {
        // Get filter parameters
        $studentFilter = $request->get('student_filter');
        $courseFilter = $request->get('course_filter');
        
        // Get all active students
        $studentsQuery = StudentMaster::where('status', 1);
        
        if ($studentFilter) {
            $studentsQuery->where(function($q) use ($studentFilter) {
                $q->where('generated_OT_code', 'like', '%' . $studentFilter . '%')
                  ->orWhere('display_name', 'like', '%' . $studentFilter . '%');
            });
        }
        
        $students = $studentsQuery->orderBy('display_name')->get(['pk', 'generated_OT_code', 'display_name']);
        
        // Build the data structure with exemption counts
        $studentData = [];
        
        foreach ($students as $student) {
            // Count total medical exceptions for this student
            $exemptionQuery = StudentMedicalExemption::where('student_master_pk', $student->pk)
                ->where('active_inactive', 1);
            
            // Filter by course if specified
            if ($courseFilter) {
                $exemptionQuery->where('course_master_pk', $courseFilter);
            }
            
            $exemptionCount = $exemptionQuery->count();
            
            // Get exemption details
            $exemptions = $exemptionQuery->with(['course', 'category', 'speciality'])
                ->orderBy('from_date', 'desc')
                ->get();
            
            $studentData[] = [
                'student_id' => $student->pk,
                'ot_code' => $student->generated_OT_code,
                'student_name' => $student->display_name,
                'exemption_count' => $exemptionCount,
                'exemptions' => $exemptions,
            ];
        }
        
        // Filter out students with 0 exemptions if no filters are applied
        if (!$studentFilter && !$courseFilter) {
            $studentData = array_filter($studentData, function($item) {
                return $item['exemption_count'] > 0;
            });
        }
        
        // Get all active courses for filter dropdown (active_inactive = 1 and end_date > current date)
        $currentDate = now()->format('Y-m-d');
        $allCourses = DB::table('course_master')
            ->where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        return view('admin.medical_exception.ot_view', compact(
            'studentData',
            'allCourses',
            'studentFilter',
            'courseFilter'
        ));
    }
}

