<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MDOEscotDutyMap;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use App\Models\MDODutyTypeMaster;
use App\Models\FacultyMaster;

class FacultyMDOEscortExceptionViewController extends Controller
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
        if ($userCategory === 'S') {
            // Students should not access faculty view
            return redirect()->back()->with('error', 'Access denied. This page is for faculty members only.');
        }
        
        // Admin View - Show all faculties with filters (only for admin users)
        return $this->adminView($request, $currentDate);
    }
    
    /**
     * Faculty Login View - Shows MDO/Escort exceptions for courses where faculty is assigned
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
        
        // Get course filter from request
        $courseFilter = $request->get('course_filter');
        
        // Step 2: Match Faculty With mdo_escot_duty_map
        // Use the faculty login user_id to match with duty map:
        // user_credential.user_id = mdo_escot_duty_map.faculty_master_pk
        // Also check: mdo_escot_duty_map.mdo_duty_type_master_pk = 2 (2 = MDO/Escort Exception duty type)
        $dutyMaps = MDOEscotDutyMap::where('faculty_master_pk', $userId)
            ->where('mdo_duty_type_master_pk', 2)
            ->with(['courseMaster', 'mdoDutyTypeMaster', 'studentMaster', 'facultyMaster'])
            ->get();
        
        if ($dutyMaps->isEmpty()) {
            // Get all available courses for filter dropdown
            $availableCourses = CourseMaster::where('active_inactive', 1)
                ->where('end_date', '>=', $currentDate)
                ->orderBy('course_name')
                ->pluck('course_name', 'pk')
                ->toArray();
            
            // Show "Data Not Found" message
            return view('admin.faculty_mdo_escort_exception.view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false,
                'courseMaster' => $availableCourses,
                'courseFilter' => $courseFilter
            ]);
        }
        
        // Step 3: Get Course Information from mdo_escot_duty_map
        // Step 4: Validate Course in course_master
        // In course_master: pk = course_master_pk, active = 1, end_date >= today
        $validDutyMaps = [];
        $courseIds = [];
        
        foreach ($dutyMaps as $dutyMap) {
            $courseMasterPk = $dutyMap->course_master_pk;
            
            // Validate course
            $course = CourseMaster::where('pk', $courseMasterPk)
                ->where('active_inactive', 1)
                ->where('end_date', '>=', $currentDate)
                ->first();
            
            if ($course) {
                // Apply course filter if provided
                if ($courseFilter && $courseMasterPk != $courseFilter) {
                    continue;
                }
                
                $courseIds[] = $courseMasterPk;
                $validDutyMaps[] = $dutyMap;
            }
        }
        
        // Get all available courses for filter dropdown (from valid courses)
        $availableCourses = CourseMaster::whereIn('pk', array_unique($courseIds))
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
        
        if (empty($validDutyMaps)) {
            // Show "Data Not Found" message
            return view('admin.faculty_mdo_escort_exception.view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false,
                'courseMaster' => $availableCourses,
                'courseFilter' => $courseFilter
            ]);
        }
        
        // Step 5: Fetch All Students' MDO/Escort Exceptions for That Course
        // From mdo_escot_duty_map: mdo_escot_duty_map.selected_student_list = student_master.pk
        $uniqueCourseIds = array_unique($courseIds);
        
        // Get unique student IDs from valid duty maps
        $studentIds = collect($validDutyMaps)
            ->pluck('selected_student_list')
            ->unique()
            ->filter();
        
        // Get all available courses for filter dropdown (from valid courses)
        $availableCourses = CourseMaster::whereIn('pk', $uniqueCourseIds)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
        
        if ($studentIds->isEmpty()) {
            // Show "Data Not Found" message
            return view('admin.faculty_mdo_escort_exception.view', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalExceptions' => 0,
                'hasData' => false,
                'courseMaster' => $availableCourses,
                'courseFilter' => $courseFilter
            ]);
        }
        
        // Fetch student details
        $students = StudentMaster::whereIn('pk', $studentIds)
            ->get(['pk', 'display_name', 'generated_OT_code', 'email', 'first_name', 'last_name']);
        
        $studentData = [];
        $totalExceptions = 0;
        
        foreach ($students as $student) {
            // Get all duty maps for this student from valid duty maps
            $studentDutyMaps = collect($validDutyMaps)
                ->where('selected_student_list', $student->pk);
            
            $exemptionDetails = [];
            foreach ($studentDutyMaps as $dutyMap) {
                // Get course name
                $course = CourseMaster::where('pk', $dutyMap->course_master_pk)->first();
                
                // Get duty type
                $dutyType = $dutyMap->mdoDutyTypeMaster;
                $dutyTypeName = $dutyType ? $dutyType->mdo_duty_type_name : 'N/A';
                
                // Get faculty name
                $faculty = $dutyMap->facultyMaster;
                $facultyName = $faculty ? $faculty->full_name : 'N/A';
                
                $exemptionDetails[] = [
                    'date' => $dutyMap->mdo_date,
                    'course_master_pk' => $dutyMap->course_master_pk,
                    'course_name' => $course ? $course->course_name : 'N/A',
                    'duty_type' => $dutyTypeName,
                    'faculty' => $facultyName,
                    'description' => $dutyMap->Remark ?? 'N/A',
                    'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
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
                'total_exception_count' => $exemptionCount,
                'exemptions' => $exemptionDetails,
            ];
        }
        
        // Sort by student name
        usort($studentData, function($a, $b) {
            return strcmp($a['student_name'], $b['student_name']);
        });
        
        // Get all available courses for filter dropdown (from valid courses)
        $availableCourses = CourseMaster::whereIn('pk', $uniqueCourseIds)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
        
        return view('admin.faculty_mdo_escort_exception.view', [
            'isFacultyView' => true,
            'studentData' => $studentData,
            'totalExceptions' => $totalExceptions,
            'hasData' => count($studentData) > 0,
            'courseMaster' => $availableCourses,
            'courseFilter' => $courseFilter
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
        
        // Get all faculties who have MDO/Escort Exception duties assigned
        $facultiesQuery = FacultyMaster::query()
            ->whereHas('mdoEscotDutyMaps', function($q) {
                $q->where('mdo_duty_type_master_pk', 2);
            })
            ->with(['mdoEscotDutyMaps' => function($q) {
                $q->where('mdo_duty_type_master_pk', 2)
                  ->with(['courseMaster', 'mdoDutyTypeMaster', 'studentMaster']);
            }]);
        
        if ($facultyFilter) {
            $facultiesQuery->where('pk', $facultyFilter);
        }
        
        $faculties = $facultiesQuery->get();
        
        // Build the data structure
        $facultyData = [];
        
        foreach ($faculties as $faculty) {
            $dutyMaps = $faculty->mdoEscotDutyMaps;
            
            // Group by course
            $coursesData = [];
            $courseGroups = $dutyMaps->groupBy('course_master_pk');
            
            foreach ($courseGroups as $courseId => $courseDutyMaps) {
                // Validate course
                $course = CourseMaster::where('pk', $courseId)
                    ->where('active_inactive', 1)
                    ->where('end_date', '>=', $currentDate)
                    ->first();
                
                if (!$course) {
                    continue;
                }
                
                // Filter by course if specified
                if ($courseFilter && $course->pk != $courseFilter) {
                    continue;
                }
                
                // Get students from duty maps
                $studentIds = $courseDutyMaps->pluck('selected_student_list')->unique()->filter();
                $students = StudentMaster::whereIn('pk', $studentIds)
                    ->where('status', 1)
                    ->get(['pk', 'generated_OT_code', 'display_name']);
                
                // Build student duty details
                $studentDutyDetails = [];
                foreach ($courseDutyMaps as $dutyMap) {
                    $student = $students->firstWhere('pk', $dutyMap->selected_student_list);
                    if ($student) {
                        $dutyType = $dutyMap->mdoDutyTypeMaster;
                        $studentDutyDetails[] = [
                            'student_pk' => $student->pk,
                            'student_name' => $student->display_name ?? 'N/A',
                            'ot_code' => $student->generated_OT_code,
                            'date' => $dutyMap->mdo_date,
                            'duty_type' => $dutyType ? $dutyType->mdo_duty_type_name : 'N/A',
                            'description' => $dutyMap->Remark ?? 'N/A',
                            'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                        ];
                    }
                }
                
                $coursesData[] = [
                    'course_id' => $course->pk,
                    'course_name' => $course->course_name,
                    'duty_count' => count($studentDutyDetails),
                    'student_duties' => $studentDutyDetails,
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
        $allFaculties = FacultyMaster::whereHas('mdoEscotDutyMaps', function($q) {
            $q->where('mdo_duty_type_master_pk', 2);
        })->orderBy('full_name')->get(['pk', 'full_name']);
        
        // Get all active courses for filter dropdown
        $allCourses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        return view('admin.faculty_mdo_escort_exception.view', compact(
            'facultyData',
            'allFaculties',
            'allCourses',
            'facultyFilter',
            'courseFilter'
        ));
    }
}

