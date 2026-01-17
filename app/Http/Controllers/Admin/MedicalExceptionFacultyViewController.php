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
use Illuminate\Pagination\LengthAwarePaginator;

class MedicalExceptionFacultyViewController extends Controller
{


    public function index(Request $request)
    {
        $currentDate = now()->format('Y-m-d');
        
        if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
            $employeeMasterPk = Auth::user()->user_id;
        
            $facultyPk = DB::table('faculty_master')
                ->where('employee_master_pk', $employeeMasterPk)
                ->value('pk');
        
            // Faculty Login View - Show only their courses
            return $this->facultyLoginView($request, $facultyPk, $currentDate);
        }else{
            // Admin View - Show all faculties with filters (only for admin users)
            return $this->adminView($request, $currentDate);
        }
        
    }

    /**
     * Faculty Login View - Shows medical exceptions for courses where faculty is coordinator
     */
    private function facultyLoginView(Request $request, $facultyPk, $currentDate)
    {
  // Get filter parameters
  $courseFilter = $request->get('course');
  $dateFromFilter = $request->get('date_from');

  // Get course IDs first to optimize the main query
  $courseIds = DB::table('course_coordinator_master')
      ->where('Coordinator_name', $facultyPk)
      ->orWhereRaw('FIND_IN_SET(?, Assistant_Coordinator_name)', [$facultyPk])
      ->pluck('courses_master_pk')
      ->unique()
      ->filter()
      ->values()
      ->toArray();

  if (empty($courseIds)) {
      $data = collect();
      $courses = collect();
  } else {
      // Build query with optimized whereIn
      $query = DB::table('student_medical_exemption as sme')
          ->join('course_master as cm', 'cm.pk', '=', 'sme.course_master_pk')
          ->join('student_master as sm', 'sm.pk', '=', 'sme.student_master_pk')
          ->join('employee_master as em', 'em.pk', '=', 'sme.employee_master_pk')
          ->leftJoin('exemption_medical_speciality_master as ems', 'ems.pk', '=', 'sme.exemption_medical_speciality_pk')
          ->whereIn('sme.course_master_pk', $courseIds)
          ->where('cm.active_inactive', 1)
          ->where('sme.active_inactive', 1);

      // Apply course filter
      if ($courseFilter) {
          $query->where('sme.course_master_pk', $courseFilter);
      }

      // Apply date filter
      if ($dateFromFilter) {
          $query->whereDate('sme.from_date', '>=', $dateFromFilter);
      }

      // Select fields
      $data = $query->select([
              'cm.course_name',
              DB::raw("CONCAT_WS(' ', em.first_name, em.middle_name, em.last_name) as faculty_name"),
              'sme.description as topics',
              'sm.display_name as student_name',
              'sme.from_date',
              'sme.to_date',
              'sm.generated_OT_code as ot_code',
              'sme.doc_upload as medical_document',
              'ems.speciality_name as application_type',
              DB::raw('COUNT(sme.pk) OVER (PARTITION BY sme.course_master_pk) as exemption_count'),
              'sme.created_date as submitted_on'
          ])
          ->orderBy('cm.course_name')
          ->orderBy('sme.from_date', 'desc')
          ->get();

      // Get courses for filter dropdown (reuse courseIds)
      $courses = DB::table('course_master')
          ->whereIn('pk', $courseIds)
          ->where('active_inactive', 1)
          ->select('pk', 'course_name')
          ->orderBy('course_name')
          ->get();
  }

  return view('admin.medical_exception.faculty_view', compact('data', 'courses', 'courseFilter', 'dateFromFilter'));
}        


    
    /**
     * Admin view for non-faculty users (original functionality)
     */
    private function adminView(Request $request, $currentDate)
    {
        // Get filter parameters (accept both course and course_filter for compatibility)
        $facultyFilter = $request->get('faculty_filter');
        $courseFilter = $request->get('course_filter') ?: $request->get('course');
        
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
        
        // Collect all unique course IDs first
        $allCourseIds = collect();
        foreach ($faculties as $faculty) {
            foreach ($faculty->timetableCourses as $timetable) {
                if ($timetable->course_master_pk) {
                    $allCourseIds->push($timetable->course_master_pk);
                } elseif ($timetable->course_group_type_master) {
                    $allCourseIds->push($timetable->course_group_type_master);
                }
            }
        }
        $allCourseIds = $allCourseIds->unique()->filter()->values();
        
        // Initialize empty collections
        $courses = collect();
        $coordinators = collect();
        $studentMappings = collect();
        $students = collect();
        $exemptions = collect();
        $courseIds = [];
        
        // Batch load all courses, coordinators, students, and exemptions only if we have course IDs
        if ($allCourseIds->isNotEmpty()) {
            $coursesQuery = CourseMaster::whereIn('pk', $allCourseIds)
                ->where('active_inactive', 1)
                ->where('end_date', '>', $currentDate);
            
            if ($courseFilter) {
                $coursesQuery->where('pk', $courseFilter);
            }
            
            $courses = $coursesQuery->get(['pk', 'course_name'])->keyBy('pk');
            $courseIds = $courses->pluck('pk')->toArray();
            
            if (!empty($courseIds)) {
                // Batch load coordinators
                $coordinators = CourseCordinatorMaster::whereIn('courses_master_pk', $courseIds)
                    ->get(['courses_master_pk', 'Coordinator_name', 'Assistant_Coordinator_name'])
                    ->keyBy('courses_master_pk');
                
                // Batch load student mappings
                $studentMappings = StudentMasterCourseMap::whereIn('course_master_pk', $courseIds)
                    ->where('active_inactive', 1)
                    ->get(['course_master_pk', 'student_master_pk'])
                    ->groupBy('course_master_pk');
                
                $allStudentIds = $studentMappings->flatten()->pluck('student_master_pk')->unique()->values()->toArray();
                
                if (!empty($allStudentIds)) {
                    // Batch load students
                    $students = StudentMaster::whereIn('pk', $allStudentIds)
                        ->where('status', 1)
                        ->get(['pk', 'generated_OT_code', 'display_name'])
                        ->keyBy('pk');
                    
                    // Batch load exemptions
                    $exemptions = StudentMedicalExemption::whereIn('course_master_pk', $courseIds)
                        ->whereIn('student_master_pk', $allStudentIds)
                        ->where('from_date', '<=', $currentDate)
                        ->where(function($q) use ($currentDate) {
                            $q->where('to_date', '>=', $currentDate)
                              ->orWhereNull('to_date');
                        })
                        ->where('active_inactive', 1)
                        ->get(['course_master_pk', 'student_master_pk'])
                        ->groupBy('course_master_pk')
                        ->map(function($group) {
                            return $group->pluck('student_master_pk')->unique()->count();
                        });
                }
            }
        }
        
        // Build the data structure
        $facultyData = [];
        
        foreach ($faculties as $faculty) {
            // Get unique courses for this faculty from timetable
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
                if (!$courseId || !isset($courses[$courseId])) {
                    continue;
                }
                
                $course = $courses[$courseId];
                
                // Filter by course if specified
                if ($courseFilter && $course->pk != $courseFilter) {
                    continue;
                }
                
                // Get ACC/CC for this course
                $coordinator = $coordinators->get($courseId);
                $cc = $coordinator ? ($coordinator->Coordinator_name ?? 'Not Assigned') : 'Not Assigned';
                $acc = $coordinator ? ($coordinator->Assistant_Coordinator_name ?? 'Not Assigned') : 'Not Assigned';
                
                // Get students enrolled in this course
                $courseStudentIds = $studentMappings->get($courseId, collect())->pluck('student_master_pk')->toArray();
                $courseStudents = collect();
                foreach ($courseStudentIds as $studentId) {
                    if ($students->has($studentId)) {
                        $courseStudents->push($students[$studentId]);
                    }
                }
                
                // Get exemption count
                $exemptionCount = $exemptions->get($courseId, 0);
                
                $coursesData[] = [
                    'course_id' => $course->pk,
                    'course_name' => $course->course_name,
                    'cc' => $cc,
                    'acc' => $acc,
                    'students' => $courseStudents,
                    'total_students' => $courseStudents->count(),
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
        
        // Query medical exceptions for admin view (similar to faculty view)
        $adminDataQuery = DB::table('student_medical_exemption as sme')
            ->join('course_master as cm', 'cm.pk', '=', 'sme.course_master_pk')
            ->join('student_master as sm', 'sm.pk', '=', 'sme.student_master_pk')
            ->join('employee_master as em', 'em.pk', '=', 'sme.employee_master_pk')
            ->leftJoin('exemption_medical_speciality_master as ems', 'ems.pk', '=', 'sme.exemption_medical_speciality_pk')
            ->where('cm.active_inactive', 1)
            ->where('sme.active_inactive', 1);
        
        // Apply course filter if specified
        if ($courseFilter) {
            $adminDataQuery->where('sme.course_master_pk', $courseFilter);
        } else {
            // If no course filter, only show courses that are in the faculty data
            if (!empty($courseIds)) {
                $adminDataQuery->whereIn('sme.course_master_pk', $courseIds);
            } else {
                // If no courses found, return empty
                $adminDataQuery->whereRaw('1 = 0');
            }
        }
        
        $adminData = $adminDataQuery->select([
                'cm.course_name',
                DB::raw("CONCAT_WS(' ', em.first_name, em.middle_name, em.last_name) as faculty_name"),
                'sme.description as topics',
                'sm.display_name as student_name',
                'sme.from_date',
                'sme.to_date',
                'sm.generated_OT_code as ot_code',
                'sme.doc_upload as medical_document',
                'ems.speciality_name as application_type',
                DB::raw('COUNT(sme.pk) OVER (PARTITION BY sme.course_master_pk) as exemption_count'),
                'sme.created_date as submitted_on'
            ])
            ->orderBy('cm.course_name')
            ->orderBy('sme.from_date', 'desc')
            ->get();
        
        // Build paginated rows for admin tables
        $perPage = 10;
        $adminCoursePage = (int) $request->get('admin_course_page', 1);
        $adminStudentPage = (int) $request->get('admin_students_page', 1);

        // Flatten course rows
        $adminCourseRows = [];
        foreach ($facultyData as $faculty) {
            foreach ($faculty['courses'] as $course) {
                $adminCourseRows[] = [
                    'faculty_name' => $faculty['faculty_name'],
                    'course_name' => $course['course_name'],
                    'cc' => $course['cc'],
                    'acc' => $course['acc'],
                    'total_students' => $course['total_students'],
                    'exemption_count' => $course['exemption_count'],
                ];
            }
        }

        $adminCourseTotal = count($adminCourseRows);
        $adminCourseItems = array_slice($adminCourseRows, ($adminCoursePage - 1) * $perPage, $perPage);
        $adminCoursePaginator = new LengthAwarePaginator(
            $adminCourseItems,
            $adminCourseTotal,
            $perPage,
            $adminCoursePage,
            ['path' => $request->url(), 'pageName' => 'admin_course_page']
        );

        // Flatten student rows (all students per course)
        $adminStudentRows = [];
        foreach ($facultyData as $faculty) {
            foreach ($faculty['courses'] as $course) {
                if ($course['students']->count() > 0) {
                    foreach ($course['students'] as $student) {
                        $adminStudentRows[] = [
                            'faculty_name' => $faculty['faculty_name'],
                            'course_name' => $course['course_name'],
                            'generated_OT_code' => $student->generated_OT_code,
                            'display_name' => $student->display_name,
                        ];
                    }
                } else {
                    $adminStudentRows[] = [
                        'faculty_name' => $faculty['faculty_name'],
                        'course_name' => $course['course_name'],
                        'generated_OT_code' => '',
                        'display_name' => 'No students enrolled in this course.',
                    ];
                }
            }
        }

        $adminStudentsTotal = count($adminStudentRows);
        $adminStudentsItems = array_slice($adminStudentRows, ($adminStudentPage - 1) * $perPage, $perPage);
        $adminStudentsPaginator = new LengthAwarePaginator(
            $adminStudentsItems,
            $adminStudentsTotal,
            $perPage,
            $adminStudentPage,
            ['path' => $request->url(), 'pageName' => 'admin_students_page']
        );

        return view('admin.medical_exception.faculty_view', [
            'data' => $adminData, // Medical exceptions data for admin view
            'courses' => $allCourses, // Map allCourses to courses for dropdown
            'courseFilter' => $courseFilter,
            'dateFromFilter' => null,
            'facultyData' => $facultyData,
            'allFaculties' => $allFaculties,
            'allCourses' => $allCourses,
            'facultyFilter' => $facultyFilter,
            'adminCoursePaginator' => $adminCoursePaginator,
            'adminStudentsPaginator' => $adminStudentsPaginator,
        ]);
    }
}

