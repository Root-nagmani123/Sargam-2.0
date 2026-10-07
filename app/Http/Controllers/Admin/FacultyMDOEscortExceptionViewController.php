<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MDOEscotDutyMap;
use App\Models\CourseMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\StudentMaster;
use App\Models\FacultyMaster;

class FacultyMDOEscortExceptionViewController extends Controller
{
    public function index(Request $request)
    {
        $currentDate = now()->format('Y-m-d');

        if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
            $facultyPk = Auth::user()->user_id;

            // Faculty Login View - Show only their courses
            return $this->facultyLoginView($request, $facultyPk, $currentDate);
        }else{
            // Admin View - Show all faculties with filters (only for admin users)
            return $this->adminView($request, $currentDate);
        }
    }
    
    /**
     * Faculty Login View - Shows MDO/Escort exceptions for courses where faculty is assigned
     */
    private function facultyLoginView(Request $request, $facultyPk, $currentDate)
    {
        $courseFilter = $request->get('course_filter');

        // Get faculty record
        $faculty = FacultyMaster::where('employee_master_pk', $facultyPk)->first();
        
        if (!$faculty) {
            return redirect()->back()->with('error', 'Faculty record not found.');
        }

        // Get course IDs where faculty is coordinator or assistant coordinator (single query with proper grouping)
        $courseIds = CourseCordinatorMaster::where(function($query) use ($faculty) {
                $query->where('Coordinator_name', $faculty->pk)
                      ->orWhere('assistant_coordinator_name', $faculty->pk);
            })
            ->pluck('courses_master_pk')
            ->unique()
            ->values()
            ->toArray();

        if (empty($courseIds)) {
            return $this->getEmptyFacultyView($courseFilter);
        }

        // Build query with course validation at database level using whereHas
        $dutyMapsQuery = MDOEscotDutyMap::whereIn('course_master_pk', $courseIds)
            ->where('mdo_duty_type_master_pk', 2)
            ->whereHas('courseMaster', function($query) use ($currentDate) {
                $query->where('active_inactive', 1)
                      ->where('end_date', '>=', $currentDate);
            })
            ->with([
                'courseMaster' => function($query) use ($currentDate) {
                    $query->where('active_inactive', 1)
                          ->where('end_date', '>=', $currentDate)
                          ->select('pk', 'course_name');
                },
                'mdoDutyTypeMaster:pk,mdo_duty_type_name',
            ]);

        // Apply course filter if provided
        if ($courseFilter) {
            $dutyMapsQuery->where('course_master_pk', $courseFilter);
        }

        $dutyMaps = $dutyMapsQuery->get();

        if ($dutyMaps->isEmpty()) {
            $availableCourses = $this->getAvailableCourses($courseIds, $currentDate);
            return $this->getEmptyFacultyView($courseFilter, $availableCourses);
        }

        // Get unique student IDs (single collection operation)
        $studentIds = $dutyMaps->pluck('selected_student_list')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($studentIds)) {
            $availableCourses = $this->getAvailableCourses($courseIds, $currentDate);
            return $this->getEmptyFacultyView($courseFilter, $availableCourses);
        }

        // Fetch students in single query
        $students = StudentMaster::whereIn('pk', $studentIds)
            ->get(['pk', 'display_name', 'generated_OT_code', 'email', 'first_name', 'last_name'])
            ->keyBy('pk');

        // A duty can carry several faculty (faculty_master_pks; faculty_master_pk is
        // only the first). Resolve every name in one query, in the order selected.
        $facultyNames = FacultyMaster::whereIn('pk', $dutyMaps->flatMap->facultyPks()->unique()->values())
            ->pluck('full_name', 'pk');

        // Build student data structure using collections
        $dutyMapsByStudent = $dutyMaps->groupBy('selected_student_list');

        $studentData = $students->map(function($student) use ($dutyMapsByStudent, $facultyNames) {
            $studentDutyMaps = $dutyMapsByStudent->get($student->pk, collect());

            $exemptionDetails = $studentDutyMaps->map(function($dutyMap) use ($facultyNames) {
                $faculty = collect($dutyMap->facultyPks())
                    ->map(fn ($pk) => $facultyNames[$pk] ?? null)
                    ->filter()
                    ->implode(', ');

                return [
                    'date' => $dutyMap->mdo_date,
                    'course_master_pk' => $dutyMap->course_master_pk,
                    'course_name' => $dutyMap->courseMaster->course_name ?? 'N/A',
                    'duty_type' => $dutyMap->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A',
                    'faculty' => $faculty !== '' ? $faculty : 'N/A',
                    'description' => $dutyMap->Remark ?? 'N/A',
                    'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                ];
            })->toArray();

            return [
                'student_pk' => $student->pk,
                'student_name' => $this->getStudentName($student),
                'ot_code' => $student->generated_OT_code,
                'email' => $student->email,
                'total_exception_count' => count($exemptionDetails),
                'exemptions' => $exemptionDetails,
            ];
        })->values()
          ->sortBy('student_name')
          ->values()
          ->toArray();

        $totalExceptions = collect($studentData)->sum('total_exception_count');
        $availableCourses = $this->getAvailableCourses($courseIds, $currentDate);

        return view('admin.faculty_mdo_escort_exception.view', [
            'isFacultyView' => true,
            'studentData' => $studentData,
            'totalExceptions' => $totalExceptions,
            'hasData' => !empty($studentData),
            'courseMaster' => $availableCourses,
            'courseFilter' => $courseFilter
        ]);
    }

    /**
     * Get available courses for filter dropdown
     */
    private function getAvailableCourses(array $courseIds, string $currentDate): array
    {
        return CourseMaster::whereIn('pk', $courseIds)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
    }

    /**
     * Get empty faculty view response
     */
    private function getEmptyFacultyView(?string $courseFilter, array $availableCourses = []): \Illuminate\View\View
    {
        return view('admin.faculty_mdo_escort_exception.view', [
            'isFacultyView' => true,
            'studentData' => [],
            'totalExceptions' => 0,
            'hasData' => false,
            'courseMaster' => $availableCourses,
            'courseFilter' => $courseFilter
        ]);
    }

    /**
     * Get student name with fallback
     */
    private function getStudentName(StudentMaster $student): string
    {
        if ($student->display_name) {
            return $student->display_name;
        }

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        return $name ?: 'N/A';
    }
    
    /**
     * Admin view for non-faculty users (original functionality)
     */
    private function adminView(Request $request, $currentDate)
    {
        $facultyFilter = $request->get('faculty_filter');
        $courseFilter = $request->get('course_filter');

        // Active tab = running / upcoming courses (end date today or later, or none);
        // Archived tab = courses that have ended. Same split as Course Master.
        $courseStatus = $request->get('course_status') === 'archive' ? 'archive' : 'active';
        $courseScope = function ($cq) use ($courseStatus, $currentDate) {
            $cq->where('active_inactive', 1);
            if ($courseStatus === 'archive') {
                $cq->whereNotNull('end_date')->where('end_date', '<', $currentDate);
            } else {
                $cq->where(function ($qq) use ($currentDate) {
                    $qq->whereNull('end_date')->orWhere('end_date', '>=', $currentDate);
                });
            }
        };

        // Get faculties with MDO/Escort Exception duties, filtering courses at DB level
        $facultiesQuery = FacultyMaster::query()
            ->whereHas('mdoEscotDutyMaps', function($q) use ($courseScope, $courseFilter) {
                $q->where('mdo_duty_type_master_pk', 2)
                  ->whereHas('courseMaster', $courseScope);

                if ($courseFilter) {
                    $q->where('course_master_pk', $courseFilter);
                }
            })
            ->with(['mdoEscotDutyMaps' => function($q) use ($courseScope, $courseFilter) {
                $q->where('mdo_duty_type_master_pk', 2)
                  ->whereHas('courseMaster', $courseScope)
                  ->when($courseFilter, function($query) use ($courseFilter) {
                      $query->where('course_master_pk', $courseFilter);
                  })
                  ->with([
                      'courseMaster' => function($cq) use ($courseScope) {
                          $courseScope($cq);
                          $cq->select('pk', 'course_name');
                      },
                      'mdoDutyTypeMaster:pk,mdo_duty_type_name'
                  ]);
            }]);
        
        if ($facultyFilter) {
            $facultiesQuery->where('pk', $facultyFilter);
        }
        
        $faculties = $facultiesQuery->get();
        
        // Build data structure using collections
        $facultyData = $faculties->map(function($faculty) use ($courseFilter) {
            $dutyMaps = $faculty->mdoEscotDutyMaps;
            
            if ($dutyMaps->isEmpty()) {
                return null;
            }
            
            // Group by course and build course data
            $coursesData = $dutyMaps->groupBy('course_master_pk')
                ->map(function($courseDutyMaps, $courseId) use ($courseFilter) {
                    $course = $courseDutyMaps->first()->courseMaster;
                    
                    if (!$course || ($courseFilter && $course->pk != $courseFilter)) {
                        return null;
                    }
                    
                    // Get unique student IDs for this course
                    $studentIds = $courseDutyMaps->pluck('selected_student_list')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                    
                    if (empty($studentIds)) {
                        return null;
                    }
                    
                    // Fetch students in single query
                    $students = StudentMaster::whereIn('pk', $studentIds)
                        ->where('status', 1)
                        ->get(['pk', 'generated_OT_code', 'display_name'])
                        ->keyBy('pk');
                    
                    // Build student duty details
                    $studentDutyDetails = $courseDutyMaps->map(function($dutyMap) use ($students) {
                        $student = $students->get($dutyMap->selected_student_list);
                        
                        if (!$student) {
                            return null;
                        }
                        
                        return [
                            'student_pk' => $student->pk,
                            'student_name' => $student->display_name ?? 'N/A',
                            'ot_code' => $student->generated_OT_code,
                            'date' => $dutyMap->mdo_date,
                            'duty_type' => $dutyMap->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A',
                            'description' => $dutyMap->Remark ?? 'N/A',
                            'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                        ];
                    })->filter()->values()->toArray();
                    
                    return [
                        'course_id' => $course->pk,
                        'course_name' => $course->course_name,
                        'duty_count' => count($studentDutyDetails),
                        'student_duties' => $studentDutyDetails,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
            
            if (empty($coursesData)) {
                return null;
            }
            
            return [
                'faculty_id' => $faculty->pk,
                'faculty_name' => $faculty->full_name ?? 'N/A',
                'courses' => $coursesData,
            ];
        })
        ->filter()
        ->values()
        ->toArray();
        
        // Get filter options (single queries)
        $allFaculties = FacultyMaster::whereHas('mdoEscotDutyMaps', function($q) {
            $q->where('mdo_duty_type_master_pk', 2);
        })
        ->orderBy('full_name')
        ->pluck('full_name', 'pk')
        ->toArray();
        
        // Courses in the selected tab that actually have escort duties.
        $allCourses = CourseMaster::where($courseScope)
            ->whereIn('pk', MDOEscotDutyMap::where('mdo_duty_type_master_pk', 2)->select('course_master_pk'))
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();

        return view('admin.faculty_mdo_escort_exception.view', compact(
            'facultyData',
            'allFaculties',
            'allCourses',
            'facultyFilter',
            'courseFilter',
            'courseStatus'
        ));
    }
}

