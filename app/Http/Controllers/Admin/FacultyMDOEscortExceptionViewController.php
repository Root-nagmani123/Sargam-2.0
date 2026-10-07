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
    /** mdo_duty_type_master.pk of "Escort" — the only duty type this view lists. */
    private const ESCORT_DUTY_TYPE = 2;

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
     * Active tab = running / upcoming courses (end date today or later, or none);
     * Archived tab = courses that have ended. Same split as Course Master.
     */
    private function courseStatus(Request $request): string
    {
        return $request->query('course_status') === 'archive' ? 'archive' : 'active';
    }

    private function courseScope(string $courseStatus, string $currentDate): \Closure
    {
        return function ($cq) use ($courseStatus, $currentDate) {
            $cq->where('active_inactive', 1);
            if ($courseStatus === 'archive') {
                $cq->whereNotNull('end_date')->where('end_date', '<', $currentDate);
            } else {
                $cq->where(function ($qq) use ($currentDate) {
                    $qq->whereNull('end_date')->orWhere('end_date', '>=', $currentDate);
                });
            }
        };
    }

    /**
     * A filter id from the query string, or null when absent or not a positive
     * integer (an array such as course_filter[]=1 is ignored, not a 500).
     */
    private function filterId(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Faculty names for every faculty on the given duties, in one query, ordered by name.
     */
    private function facultyNamesFor($dutyMaps)
    {
        return FacultyMaster::whereIn('pk', $dutyMaps->flatMap->facultyPks()->unique()->values())
            ->orderBy('full_name')
            ->pluck('full_name', 'pk');
    }

    /**
     * Names of all faculty on one duty, in the order they were selected. A pk
     * with no faculty_master row is skipped (there is no name to show).
     */
    private function dutyFacultyNames(MDOEscotDutyMap $dutyMap, $facultyNames): array
    {
        return collect($dutyMap->facultyPks())
            ->map(fn ($pk) => $facultyNames[$pk] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Faculty Login View - Shows MDO/Escort exceptions for courses where faculty is assigned
     */
    private function facultyLoginView(Request $request, $facultyPk, $currentDate)
    {
        $courseFilter = $this->filterId($request, 'course_filter');
        $courseStatus = $this->courseStatus($request);
        $courseScope = $this->courseScope($courseStatus, $currentDate);

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
            return $this->getEmptyFacultyView($courseFilter, $courseStatus);
        }

        $availableCourses = $this->getAvailableCourses($courseIds, $courseScope);

        // Only the coordinator's own courses, in the selected tab (Active / Archived).
        $dutyMapsQuery = MDOEscotDutyMap::whereIn('course_master_pk', $courseIds)
            ->where('mdo_duty_type_master_pk', self::ESCORT_DUTY_TYPE)
            ->whereHas('courseMaster', $courseScope)
            ->with([
                'courseMaster:pk,course_name',
                'mdoDutyTypeMaster:pk,mdo_duty_type_name',
            ])
            ->orderBy('pk');

        // Apply course filter if provided
        if ($courseFilter) {
            $dutyMapsQuery->where('course_master_pk', $courseFilter);
        }

        $dutyMaps = $dutyMapsQuery->get();

        if ($dutyMaps->isEmpty()) {
            return $this->getEmptyFacultyView($courseFilter, $courseStatus, $availableCourses);
        }

        // Get unique student IDs (single collection operation)
        $studentIds = $dutyMaps->pluck('selected_student_list')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($studentIds)) {
            return $this->getEmptyFacultyView($courseFilter, $courseStatus, $availableCourses);
        }

        // Fetch students in single query
        $students = StudentMaster::whereIn('pk', $studentIds)
            ->get(['pk', 'display_name', 'generated_OT_code', 'email', 'first_name', 'last_name'])
            ->keyBy('pk');

        // A duty can carry several faculty (faculty_master_pks; faculty_master_pk is
        // only the first). Resolve every name in one query.
        $facultyNames = $this->facultyNamesFor($dutyMaps);

        // Build student data structure using collections
        $dutyMapsByStudent = $dutyMaps->groupBy('selected_student_list');

        $studentData = $students->map(function($student) use ($dutyMapsByStudent, $facultyNames) {
            $studentDutyMaps = $dutyMapsByStudent->get($student->pk, collect());

            $exemptionDetails = $studentDutyMaps->map(function($dutyMap) use ($facultyNames) {
                return [
                    'date' => $dutyMap->mdo_date,
                    'course_master_pk' => $dutyMap->course_master_pk,
                    'course_name' => $dutyMap->courseMaster->course_name ?? 'N/A',
                    'duty_type' => $dutyMap->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A',
                    'faculty' => $this->dutyFacultyNames($dutyMap, $facultyNames),
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

        return view('admin.faculty_mdo_escort_exception.view', [
            'isFacultyView' => true,
            'studentData' => $studentData,
            'totalExceptions' => $totalExceptions,
            'hasData' => !empty($studentData),
            'courseMaster' => $availableCourses,
            'courseFilter' => $courseFilter,
            'courseStatus' => $courseStatus,
        ]);
    }

    /**
     * Get available courses for filter dropdown — the coordinator's courses in the selected tab.
     */
    private function getAvailableCourses(array $courseIds, \Closure $courseScope): array
    {
        return CourseMaster::whereIn('pk', $courseIds)
            ->where($courseScope)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
    }

    /**
     * Get empty faculty view response
     */
    private function getEmptyFacultyView(?int $courseFilter, string $courseStatus, array $availableCourses = []): \Illuminate\View\View
    {
        return view('admin.faculty_mdo_escort_exception.view', [
            'isFacultyView' => true,
            'studentData' => [],
            'totalExceptions' => 0,
            'hasData' => false,
            'courseMaster' => $availableCourses,
            'courseFilter' => $courseFilter,
            'courseStatus' => $courseStatus,
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
     * Admin view for non-faculty users: faculty → course → exceptions.
     *
     * Built from the duties, not from FacultyMaster::mdoEscotDutyMaps(): that
     * relation joins on faculty_master_pk, which holds only the first of a duty's
     * faculty, so the second and later faculty never saw the duty and the
     * Faculty filter could not find it. A duty with several faculty is listed
     * under each of them.
     */
    private function adminView(Request $request, $currentDate)
    {
        $facultyFilter = $this->filterId($request, 'faculty_filter');
        $courseFilter = $this->filterId($request, 'course_filter');
        $courseStatus = $this->courseStatus($request);
        $courseScope = $this->courseScope($courseStatus, $currentDate);

        $dutyMaps = MDOEscotDutyMap::where('mdo_duty_type_master_pk', self::ESCORT_DUTY_TYPE)
            ->whereHas('courseMaster', $courseScope)
            ->when($courseFilter, fn ($q) => $q->where('course_master_pk', $courseFilter))
            ->when($facultyFilter, fn ($q) => $q->associatedWithFaculty($facultyFilter))
            ->with([
                'courseMaster:pk,course_name',
                'mdoDutyTypeMaster:pk,mdo_duty_type_name',
            ])
            ->orderBy('pk')
            ->get();

        $facultyNames = $this->facultyNamesFor($dutyMaps);

        // Fetch students in single query (inactive students are not listed).
        $students = StudentMaster::whereIn('pk', $dutyMaps->pluck('selected_student_list')->filter()->unique()->values())
            ->where('status', 1)
            ->get(['pk', 'generated_OT_code', 'display_name'])
            ->keyBy('pk');

        // faculty pk => course pk => course + its exception rows.
        $byFaculty = [];
        foreach ($dutyMaps as $dutyMap) {
            $student = $students->get($dutyMap->selected_student_list);
            if (!$student || !$dutyMap->courseMaster) {
                continue;
            }

            $row = [
                'duty_pk' => $dutyMap->pk,
                'student_pk' => $student->pk,
                'student_name' => $student->display_name ?? 'N/A',
                'ot_code' => $student->generated_OT_code,
                'faculty' => $this->dutyFacultyNames($dutyMap, $facultyNames),
                'date' => $dutyMap->mdo_date,
                'duty_type' => $dutyMap->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A',
                'description' => $dutyMap->Remark ?? 'N/A',
                'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
            ];

            foreach ($dutyMap->facultyPks() as $pk) {
                // With a Faculty filter only that faculty's card is shown; its
                // co-faculty still appear in the row's Faculty column.
                if (!isset($facultyNames[$pk]) || ($facultyFilter && $pk !== $facultyFilter)) {
                    continue;
                }

                $byFaculty[$pk][$dutyMap->course_master_pk] ??= [
                    'course_id' => $dutyMap->course_master_pk,
                    'course_name' => $dutyMap->courseMaster->course_name,
                    'student_duties' => [],
                ];
                $byFaculty[$pk][$dutyMap->course_master_pk]['student_duties'][] = $row;
            }
        }

        // Faculty cards in name order ($facultyNames is ordered by full_name).
        $facultyData = [];
        foreach ($facultyNames as $pk => $name) {
            if (empty($byFaculty[$pk])) {
                continue;
            }

            $facultyData[] = [
                'faculty_id' => $pk,
                'faculty_name' => $name ?? 'N/A',
                'courses' => array_values(array_map(function ($course) {
                    $course['duty_count'] = count($course['student_duties']);
                    return $course;
                }, $byFaculty[$pk])),
            ];
        }

        // Faculty filter options: every faculty on an escort duty, in any position.
        $allFaculties = FacultyMaster::whereIn('pk',
                MDOEscotDutyMap::where('mdo_duty_type_master_pk', self::ESCORT_DUTY_TYPE)
                    ->get(['faculty_master_pk', 'faculty_master_pks'])
                    ->flatMap->facultyPks()
                    ->unique()
                    ->values()
            )
            ->orderBy('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        // Courses in the selected tab that actually have escort duties.
        $allCourses = CourseMaster::where($courseScope)
            ->whereIn('pk', MDOEscotDutyMap::where('mdo_duty_type_master_pk', self::ESCORT_DUTY_TYPE)->select('course_master_pk'))
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
