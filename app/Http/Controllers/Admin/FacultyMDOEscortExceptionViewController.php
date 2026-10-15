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

    /** menus.permission_name of this screen (menus row "Faculty MDO Escort Exception"). */
    private const ADMIN_PERMISSION = 'faculty_mdo_escort_exception_view';

    public function index(Request $request)
    {
        $currentDate = now()->format('Y-m-d');

        // Faculty accounts hold the "Faculty" role (it also holds this screen's
        // menu permission), so it must route here before the admin branch.
        if (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Faculty')) {
            // Faculty Login View - Show only their courses
            return $this->facultyLoginView($request, $this->loginFaculty(), $currentDate);
        }

        // Admin View lists every trainee's escort exceptions, so it needs the
        // screen's menu permission (Super Admin always passes); the route itself
        // is auth-only because the faculty branch above must stay reachable.
        abort_unless(hasMenuPermission(self::ADMIN_PERMISSION), 403, 'You do not have permission to open this screen.');

        return $this->adminView($request, $currentDate);
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
     * Escort duties a faculty login may see, decided per course: every duty of
     * a course they are CC or ACC of, and on any other course only the duties
     * they are assigned to (any position of faculty_master_pks, or a legacy
     * faculty_master_pk). The table and the Course filter both start here.
     */
    private function facultyDutiesQuery(int $facultyMasterPk, \Closure $courseScope)
    {
        $coordinatedCourseIds = CourseCordinatorMaster::courseIdsForFaculty($facultyMasterPk);

        return MDOEscotDutyMap::where('mdo_duty_type_master_pk', self::ESCORT_DUTY_TYPE)
            ->whereHas('courseMaster', $courseScope)
            ->where(function ($q) use ($coordinatedCourseIds, $facultyMasterPk) {
                $q->whereIn('course_master_pk', $coordinatedCourseIds)
                  ->orWhere(fn ($own) => $own->associatedWithFaculty($facultyMasterPk));
            });
    }

    /**
     * The logged-in user's faculty_master row, or null. A faculty login
     * (user_category F) stores the faculty pk itself in user_id; any other
     * login stores an employee pk, linked through faculty_master.employee_master_pk.
     */
    private function loginFaculty(): ?FacultyMaster
    {
        return FacultyMaster::where(login_faculty_key_column(), Auth::user()->user_id)->first();
    }

    /**
     * Faculty Login View - escort exceptions per facultyDutiesQuery(): the whole
     * course for its CC/ACC, otherwise only the faculty's own duties.
     */
    private function facultyLoginView(Request $request, ?FacultyMaster $faculty, $currentDate)
    {
        $courseFilter = $this->filterId($request, 'course_filter');
        $courseStatus = $this->courseStatus($request);
        $courseScope = $this->courseScope($courseStatus, $currentDate);

        if (!$faculty) {
            return redirect()->back()->with('error', 'Faculty record not found.');
        }

        $availableCourses = $this->getAvailableCourses($faculty->pk, $courseScope);

        // Visible duties in the selected tab (Active / Archived).
        $dutyMapsQuery = $this->facultyDutiesQuery($faculty->pk, $courseScope)
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
     * Get available courses for filter dropdown — courses in the selected tab
     * with at least one escort duty this faculty may see (facultyDutiesQuery()).
     */
    private function getAvailableCourses(int $facultyMasterPk, \Closure $courseScope): array
    {
        return CourseMaster::where($courseScope)
            ->whereIn('pk', $this->facultyDutiesQuery($facultyMasterPk, $courseScope)->select('course_master_pk'))
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
