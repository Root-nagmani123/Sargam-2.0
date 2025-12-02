<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StudentMaster;
use App\Models\StudentMedicalExemption;

class MedicalExceptionOTViewController extends Controller
{
    public function index(Request $request)
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

