<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $currentDate = now()->format('Y-m-d');
        
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

