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

        // Build course-centric data to align with view and paginate server-side
        $courseData = [];
        $totalExceptions = 0;

        foreach ($validCourses as $course) {
            $courseId = $course->pk;

            // Coordinators
            $coordinators = CourseCordinatorMaster::where('courses_master_pk', $courseId)->first();
            $ccName = $coordinators->Coordinator_name ?? 'Not Assigned';

            // Enrolled students
            $studentIds = StudentMasterCourseMap::where('course_master_pk', $courseId)
                ->where('active_inactive', 1)
                ->pluck('student_master_pk');

            $students = StudentMaster::whereIn('pk', $studentIds)
                ->where('status', 1)
                ->get(['pk', 'generated_OT_code', 'display_name']);

            // Students on medical exception (current)
            $exceptionStudentIds = StudentMedicalExemption::where('course_master_pk', $courseId)
                ->whereIn('student_master_pk', $studentIds)
                ->where('from_date', '<=', $currentDate)
                ->where(function($q) use ($currentDate) {
                    $q->where('to_date', '>=', $currentDate)
                      ->orWhereNull('to_date');
                })
                ->where('active_inactive', 1)
                ->distinct('student_master_pk')
                ->pluck('student_master_pk');

            $exceptionStudents = $students->whereIn('pk', $exceptionStudentIds->all())
                ->map(function($s) {
                    return [
                        'generated_OT_code' => $s->generated_OT_code,
                        'display_name' => $s->display_name,
                    ];
                })->values()->all();

            $exemptionCount = count($exceptionStudents);
            $totalExceptions += $exemptionCount;

            $courseData[] = [
                'course_name' => $course->course_name,
                'cc_name' => $ccName,
                'total_students' => $students->count(),
                'total_exemption_count' => $exemptionCount,
                'students' => $exceptionStudents,
            ];
        }

        // Server-side pagination for course summary
        $perPage = 10;
        $coursePage = (int) $request->get('course_page', 1);
        $courseTotal = count($courseData);
        $courseItems = array_slice($courseData, ($coursePage - 1) * $perPage, $perPage);
        $coursePaginator = new LengthAwarePaginator(
            $courseItems,
            $courseTotal,
            $perPage,
            $coursePage,
            ['path' => $request->url(), 'pageName' => 'course_page']
        );

        // Server-side pagination for flattened students under exception
        $studentRows = [];
        foreach ($courseData as $course) {
            if (!empty($course['students'])) {
                foreach ($course['students'] as $student) {
                    $studentRows[] = [
                        'course_name' => $course['course_name'],
                        'generated_OT_code' => $student['generated_OT_code'] ?? '',
                        'display_name' => $student['display_name'] ?? '',
                        'status' => 'Medical Exception',
                    ];
                }
            } else {
                $studentRows[] = [
                    'course_name' => $course['course_name'],
                    'generated_OT_code' => '',
                    'display_name' => 'No students under medical exception.',
                    'status' => '',
                ];
            }
        }

        $studentsPage = (int) $request->get('students_page', 1);
        $studentsTotal = count($studentRows);
        $studentsItems = array_slice($studentRows, ($studentsPage - 1) * $perPage, $perPage);
        $studentsPaginator = new LengthAwarePaginator(
            $studentsItems,
            $studentsTotal,
            $perPage,
            $studentsPage,
            ['path' => $request->url(), 'pageName' => 'students_page']
        );

        return view('admin.medical_exception.faculty_view', [
            'isFacultyView' => true,
            'coursePaginator' => $coursePaginator,
            'studentsPaginator' => $studentsPaginator,
            'hasData' => $courseTotal > 0
        ]);
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
            'facultyData' => $facultyData,
            'allFaculties' => $allFaculties,
            'allCourses' => $allCourses,
            'facultyFilter' => $facultyFilter,
            'courseFilter' => $courseFilter,
            'adminCoursePaginator' => $adminCoursePaginator,
            'adminStudentsPaginator' => $adminStudentsPaginator,
        ]);
    }
}

