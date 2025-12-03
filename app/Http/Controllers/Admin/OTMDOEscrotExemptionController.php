<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentMaster;
use App\Models\MDOEscotDutyMap;
use App\Models\CourseMaster;
use App\Models\MDODutyTypeMaster;
use App\Models\FacultyMaster;

class OTMDOEscrotExemptionController extends Controller
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
            // If not a student, show admin view
            return $this->adminView($request, $currentDate);
        }
        
        // Get user_id from user_credentials where category = 'S'
        $userId = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->where('user_category', 'S')
            ->value('user_id');
        
        if (!$userId) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Match user_id with student_master.pk
        $student = StudentMaster::where('pk', $userId)->first();
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Check student duty count in mdo_escot_duty_map
        // Compare selected_student_list = student_master.pk
        $dutyMaps = MDOEscotDutyMap::where('selected_student_list', $userId)
            ->with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->get();
        
        // Filter duty maps by course validation
        $validDutyMaps = [];
        
        foreach ($dutyMaps as $dutyMap) {
            $courseMasterPk = $dutyMap->course_master_pk;
            
            // Validate course in course_master
            // course_master.pk = course_master_pk
            // active = 1 (active_inactive = 1)
            // end_date >= today
            $course = CourseMaster::where('pk', $courseMasterPk)
                ->where('active_inactive', 1)
                ->where('end_date', '>=', $currentDate)
                ->first();
            
            if ($course) {
                // Fetch duty type name
                $dutyType = $dutyMap->mdoDutyTypeMaster;
                $dutyTypeName = $dutyType ? $dutyType->mdo_duty_type_name : 'N/A';
                
                // Fetch faculty name
                $faculty = $dutyMap->facultyMaster;
                $facultyName = $faculty ? $faculty->full_name : 'N/A';
                
                // Build final data
                $validDutyMaps[] = [
                    'date' => $dutyMap->mdo_date,
                    'user_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
                    'course' => $course->course_name,
                    'duty_type' => $dutyTypeName,
                    'faculty' => $facultyName,
                    'description' => $dutyMap->Remark ?? 'N/A',
                    'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                ];
            }
        }
        
        // Prepare data for view
        $studentData = [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email,
            'total_duty_count' => count($validDutyMaps),
            'duty_maps' => $validDutyMaps,
            'has_duties' => count($validDutyMaps) > 0,
        ];
        
        return view('admin.ot_mdo_escrot_exemption.view', compact('studentData'));
    }
    
    /**
     * Admin view for non-student users
     */
    private function adminView(Request $request, $currentDate)
    {
        // Get filter parameters
        $studentFilter = $request->get('student_filter');
        $courseFilter = $request->get('course_filter');
        
        // Get all students with category = 'S' from user_credentials
        $studentIds = DB::table('user_credentials')
            ->where('user_category', 'S')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();
        
        // Get student details
        $studentsQuery = StudentMaster::whereIn('pk', $studentIds)
            ->where('status', 1);
        
        if ($studentFilter) {
            $studentsQuery->where(function($q) use ($studentFilter) {
                $q->where('generated_OT_code', 'like', '%' . $studentFilter . '%')
                  ->orWhere('display_name', 'like', '%' . $studentFilter . '%');
            });
        }
        
        $students = $studentsQuery->orderBy('display_name')->get(['pk', 'generated_OT_code', 'display_name', 'email', 'first_name', 'last_name']);
        
        // Build the data structure with duty maps
        $studentData = [];
        
        foreach ($students as $student) {
            // Get duty maps for this student
            $dutyMapsQuery = MDOEscotDutyMap::where('selected_student_list', $student->pk)
                ->with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster']);
            
            // Filter by course if specified
            if ($courseFilter) {
                $dutyMapsQuery->where('course_master_pk', $courseFilter);
            }
            
            $dutyMaps = $dutyMapsQuery->get();
            
            // Filter by course validation
            $validDutyMaps = [];
            foreach ($dutyMaps as $dutyMap) {
                $course = CourseMaster::where('pk', $dutyMap->course_master_pk)
                    ->where('active_inactive', 1)
                    ->where('end_date', '>=', $currentDate)
                    ->first();
                
                if ($course) {
                    $dutyType = $dutyMap->mdoDutyTypeMaster;
                    $faculty = $dutyMap->facultyMaster;
                    
                    $validDutyMaps[] = [
                        'date' => $dutyMap->mdo_date,
                        'course' => $course->course_name,
                        'duty_type' => $dutyType ? $dutyType->mdo_duty_type_name : 'N/A',
                        'faculty' => $faculty ? $faculty->full_name : 'N/A',
                        'description' => $dutyMap->Remark ?? 'N/A',
                        'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                    ];
                }
            }
            
            if (count($validDutyMaps) > 0 || $studentFilter || $courseFilter) {
                $studentData[] = [
                    'student_id' => $student->pk,
                    'ot_code' => $student->generated_OT_code,
                    'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
                    'email' => $student->email,
                    'duty_count' => count($validDutyMaps),
                    'duty_maps' => $validDutyMaps,
                ];
            }
        }
        
        // Filter out students with 0 duties if no filters are applied
        if (!$studentFilter && !$courseFilter) {
            $studentData = array_filter($studentData, function($item) {
                return $item['duty_count'] > 0;
            });
        }
        
        // Get all active courses for filter dropdown
        $allCourses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        return view('admin.ot_mdo_escrot_exemption.view', compact(
            'studentData',
            'allCourses',
            'studentFilter',
            'courseFilter'
        ));
    }
}

