<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\FacultyMaster;
use App\Models\CourseMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\StudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\StudentMedicalExemption;
use App\Models\CalendarEvent;

class MedicalExceptionFacultyViewController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        $currentDate = now()->format('Y-m-d');
        
        // Check if user_category = 'F' (Faculty)
        $userCategory = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->value('user_category');
        
        if ($userCategory === 'F') {
            // Faculty Login View - Show only their courses
            return $this->facultyLoginView($request, $user, $currentDate);
        }
        
        // Only show admin view if user is NOT a student (S) or faculty (F)
        // This ensures students and faculty don't see other users' data
        if ($userCategory === 'S') {
            // Students should not access faculty view
            return redirect()->back()->with('error', 'Access denied. This page is for faculty members only.');
        }
        
        // Admin View - Show all faculties with filters (only for admin users)
        return $this->adminView($request, $currentDate);
    }
    
    /**
     * Faculty Login View - Shows medical exceptions for courses where faculty is coordinator
     */
    private function facultyLoginView(Request $request, $user, $currentDate)
    {
        // Step 1: Get user_id from user_credentials where category = 'F'
        $userId = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->where('user_category', 'F')
            ->value('user_id');
        
        if (!$userId) {
            return redirect()->back()->with('error', 'Faculty record not found.');
        }
        
        // Step 2: Match user_id with course_coordinator_master.coordinator_name
        // Get all courses where this user is coordinator
        // Try matching as both string and numeric (in case coordinator_name stores user_id)
        $coordinatorCourses = CourseCordinatorMaster::where(function($q) use ($userId) {
                $q->where('Coordinator_name', $userId)
                  ->orWhere('Coordinator_name', (string)$userId);
            })
            ->pluck('courses_master_pk')
            ->unique()
            ->filter();
        
        if ($coordinatorCourses->isEmpty()) {
            // Show "Data Not Found" message
            return view('admin.medical_exception.faculty_view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false
            ]);
        }
        
        // Step 3: Validate courses in course_master
        $validCourses = CourseMaster::whereIn('pk', $coordinatorCourses)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->get();
        
        if ($validCourses->isEmpty()) {
            // Show "Data Not Found" message
            return view('admin.medical_exception.faculty_view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false
            ]);
        }
        
        $validCourseIds = $validCourses->pluck('pk');
        
        // Step 4: Fetch all students' medical exceptions for those courses
        $exemptions = StudentMedicalExemption::whereIn('course_master_pk', $validCourseIds)
            ->where('active_inactive', 1)
            ->get();
        
        if ($exemptions->isEmpty()) {
            // Show "Data Not Found" message
            return view('admin.medical_exception.faculty_view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false
            ]);
        }
        
        // Step 5: Get unique student_pk who took medical exception
        $studentIds = $exemptions->pluck('student_master_pk')->unique();
        
        // Step 6: Fetch student details and build data structure
        $students = StudentMaster::whereIn('pk', $studentIds)
            ->get(['pk', 'display_name', 'generated_OT_code', 'email']);
        
        $studentData = [];
        $totalExceptions = 0;
        
        foreach ($students as $student) {
            // Get all exemptions for this student in the valid courses
            $studentExemptions = $exemptions->where('student_master_pk', $student->pk);
            
            $exemptionDetails = [];
            foreach ($studentExemptions as $exemption) {
                // Get course name
                $course = $validCourses->firstWhere('pk', $exemption->course_master_pk);
                
                $exemptionDetails[] = [
                    'from_date' => $exemption->from_date,
                    'to_date' => $exemption->to_date,
                    'opd_category' => $exemption->opd_category,
                    'description' => $exemption->Description,
                    'doc_upload' => $exemption->Doc_upload,
                    'course_master_pk' => $exemption->course_master_pk,
                    'course_name' => $course ? $course->course_name : 'N/A',
                ];
            }
            
            $exemptionCount = count($exemptionDetails);
            $totalExceptions += $exemptionCount;
            
            // Get student name - prefer display_name, fallback to first_name + last_name
            $studentName = $student->display_name;
            if (!$studentName && (isset($student->first_name) || isset($student->last_name))) {
                $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
            }
            $studentName = $studentName ?: 'N/A';
            
            $studentData[] = [
                'student_pk' => $student->pk,
                'student_name' => $studentName,
                'ot_code' => $student->generated_OT_code,
                'email' => $student->email,
                'total_exemption_count' => $exemptionCount,
                'exemptions' => $exemptionDetails,
            ];
        }
        
        // Sort by student name
        usort($studentData, function($a, $b) {
            return strcmp($a['student_name'], $b['student_name']);
        });
        
        return view('admin.medical_exception.faculty_view', [
            'isFacultyView' => true,
            'studentData' => $studentData,
            'totalExceptions' => $totalExceptions,
            'hasData' => count($studentData) > 0
        ]);
    }
    
    /**
     * Admin view for non-faculty users (original functionality)
     */
    private function adminView(Request $request, $currentDate)
    {
        // Get filter parameters
        $facultyFilter = $request->get('faculty_filter');
        $courseFilter = $request->get('course_filter');
        
        // Get all faculties who have courses assigned (from timetable)
        $facultiesQuery = FacultyMaster::query()
            ->whereHas('timetableCourses', function($q) {
                $q->where('active_inactive', 1);
            })
            ->with(['timetableCourses' => function($q) {
                $q->where('active_inactive', 1)
                  ->with('courseGroupTypeMaster');
            }]);
        
        if ($facultyFilter) {
            $facultiesQuery->where('pk', $facultyFilter);
        }
        
        $faculties = $facultiesQuery->get();
        
        // Build the data structure
        $facultyData = [];
        
        foreach ($faculties as $faculty) {
            // Get unique courses for this faculty from timetable
            // Prioritize course_master_pk, fallback to course_group_type_master
            $courseIds = collect();
            foreach ($faculty->timetableCourses as $timetable) {
                if ($timetable->course_master_pk) {
                    $courseIds->push($timetable->course_master_pk);
                } elseif ($timetable->course_group_type_master) {
                    $courseIds->push($timetable->course_group_type_master);
                }
            }
            $courseIds = $courseIds->unique()->filter();
            
            if ($courseIds->isEmpty()) {
                continue;
            }
            
            $coursesData = [];
            
            foreach ($courseIds as $courseId) {
                if (!$courseId) {
                    continue;
                }
                
                $course = CourseMaster::where('pk', $courseId)
                    ->where('active_inactive', 1)
                    ->where('end_date', '>', $currentDate)
                    ->first();
                
                if (!$course) {
                    continue;
                }
                
                // Filter by course if specified
                if ($courseFilter && $course->pk != $courseFilter) {
                    continue;
                }
                
                // Get ACC/CC for this course
                $coordinators = CourseCordinatorMaster::where('courses_master_pk', $courseId)->first();
                $cc = $coordinators->Coordinator_name ?? 'Not Assigned';
                $acc = $coordinators->Assistant_Coordinator_name ?? 'Not Assigned';
                
                // Get students enrolled in this course
                $studentIds = StudentMasterCourseMap::where('course_master_pk', $courseId)
                    ->where('active_inactive', 1)
                    ->pluck('student_master_pk');
                
                $students = StudentMaster::whereIn('pk', $studentIds)
                    ->where('status', 1)
                    ->get(['pk', 'generated_OT_code', 'display_name']);
                
                // Count students currently on medical exception
                $exemptionCount = StudentMedicalExemption::where('course_master_pk', $courseId)
                    ->whereIn('student_master_pk', $studentIds)
                    ->where('from_date', '<=', $currentDate)
                    ->where(function($q) use ($currentDate) {
                        $q->where('to_date', '>=', $currentDate)
                          ->orWhereNull('to_date');
                    })
                    ->where('active_inactive', 1)
                    ->distinct('student_master_pk')
                    ->count('student_master_pk');
                
                $coursesData[] = [
                    'course_id' => $course->pk,
                    'course_name' => $course->course_name,
                    'cc' => $cc,
                    'acc' => $acc,
                    'students' => $students,
                    'total_students' => $students->count(),
                    'exemption_count' => $exemptionCount,
                ];
            }
            
            if (!empty($coursesData)) {
                $facultyData[] = [
                    'faculty_id' => $faculty->pk,
                    'faculty_name' => $faculty->full_name ?? 'N/A',
                    'courses' => $coursesData,
                ];
            }
        }
        
        // Get all faculties for filter dropdown
        $allFaculties = FacultyMaster::whereHas('timetableCourses', function($q) {
            $q->where('active_inactive', 1);
        })->orderBy('full_name')->get(['pk', 'full_name']);
        
        // Get all active courses for filter dropdown (active_inactive = 1 and end_date > current date)
        $allCourses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        return view('admin.medical_exception.faculty_view', compact(
            'facultyData',
            'allFaculties',
            'allCourses',
            'facultyFilter',
            'courseFilter'
        ));
    }
}

