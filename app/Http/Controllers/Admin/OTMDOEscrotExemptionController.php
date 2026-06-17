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

        // Resolve the logged-in student (student_master) record.
        $student = $this->resolveStudent($user);
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        // Get filter parameters
        $dutyTypeFilter = $request->get('duty_type_filter');
        $fromDateFilter = $request->get('from_date_filter');
        $toDateFilter = $request->get('to_date_filter');

        // Build the full, course-validated duty list once (used for both stats and the
        // filtered table, so we only hit the DB once for the heavy part).
        $allDuties = $this->buildDutyMaps($student->pk, $currentDate);

        // Stat-card aggregates (independent of the toolbar filters).
        $stats = [
            'today' => collect($allDuties)->where('date', $currentDate)->count(),
            'pending' => collect($allDuties)->where('is_completed', false)->count(),
            'completed' => collect($allDuties)->where('is_completed', true)->count(),
        ];

        // Apply the toolbar filters (in PHP) to produce the visible table rows.
        $validDutyMaps = $this->applyFilters($allDuties, $dutyTypeFilter, $fromDateFilter, $toDateFilter);

        // Get all duty types for filter dropdown
        $allDutyTypes = $this->dutyTypeOptions();

        // Prepare data for view
        $studentData = [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email,
            'total_duty_count' => count($validDutyMaps),
            'duty_maps' => $validDutyMaps,
            'has_duties' => count($validDutyMaps) > 0,
            'stats' => $stats,
        ];

        return view('admin.ot_mdo_escrot_exemption.view', compact('studentData', 'allDutyTypes', 'dutyTypeFilter', 'fromDateFilter', 'toDateFilter'));
    }

    /**
     * OT acknowledges one of their own duties: Pending -> Completed.
     */
    public function acknowledge(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $request->validate([
            'duty_pk' => 'required',
        ]);

        $user = Auth::user();
        $student = $this->resolveStudent($user);
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        // Ownership check: the duty must belong to the logged-in student.
        $duty = MDOEscotDutyMap::where('pk', $request->duty_pk)
            ->where('selected_student_list', $student->pk)
            ->first();

        if (!$duty) {
            return redirect()->back()->with('error', 'Duty not found or you are not authorised to acknowledge it.');
        }

        if ($duty->duty_status === MDOEscotDutyMap::STATUS_COMPLETED) {
            return redirect()->back()->with('success', 'This duty was already acknowledged.');
        }

        $duty->duty_status = MDOEscotDutyMap::STATUS_COMPLETED;
        $duty->acknowledged_by = $student->pk;
        $duty->acknowledged_at = now();
        $duty->save();

        return redirect()->back()->with('success', 'Duty acknowledged successfully.');
    }

    /**
     * Sub-page: Today's duties for the logged-in OT.
     */
    public function today(Request $request)
    {
        return $this->renderSection($request, 'today', "Today's Duty");
    }

    /**
     * Sub-page: Pending (un-acknowledged) duties for the logged-in OT.
     */
    public function pending(Request $request)
    {
        return $this->renderSection($request, 'pending', 'Pending Duty');
    }

    /**
     * Sub-page: Completed (acknowledged) duties for the logged-in OT.
     */
    public function completed(Request $request)
    {
        return $this->renderSection($request, 'completed', 'Completed Duty');
    }

    /**
     * Shared renderer for the Today / Pending / Completed sub-pages.
     */
    private function renderSection(Request $request, string $section, string $pageTitle)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        $currentDate = now()->format('Y-m-d');

        $student = $this->resolveStudent($user);
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $allDuties = $this->buildDutyMaps($student->pk, $currentDate);

        $duties = collect($allDuties)->filter(function ($d) use ($section, $currentDate) {
            return match ($section) {
                'today' => $d['date'] === $currentDate,
                'pending' => $d['is_completed'] === false,
                'completed' => $d['is_completed'] === true,
                default => true,
            };
        })->values()->all();

        // Acknowledge is offered on pending rows only (Today's page can also contain pending rows).
        $showAcknowledge = in_array($section, ['today', 'pending'], true);

        $studentData = [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email,
            'duty_maps' => $duties,
        ];

        return view('admin.ot_mdo_escrot_exemption.list', [
            'studentData' => $studentData,
            'pageTitle' => $pageTitle,
            'section' => $section,
            'showAcknowledge' => $showAcknowledge,
        ]);
    }

    /**
     * Admin view for non-student users
     */
    private function adminView(Request $request, $currentDate)
    {
        // Get filter parameters
        $dutyTypeFilter = $request->get('duty_type_filter');
        $fromDateFilter = $request->get('from_date_filter');
        $toDateFilter = $request->get('to_date_filter');

        // Get all students with category = 'S' from user_credentials
        $studentIds = DB::table('user_credentials')
            ->where('user_category', 'S')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        // Get student details
        $students = StudentMaster::whereIn('pk', $studentIds)
            ->where('status', 1)
            ->orderBy('display_name')
            ->get(['pk', 'generated_OT_code', 'display_name', 'email', 'first_name', 'last_name']);

        // Build the data structure with duty maps
        $studentData = [];

        foreach ($students as $student) {
            $allDuties = $this->buildDutyMaps($student->pk, $currentDate);
            $validDutyMaps = $this->applyFilters($allDuties, $dutyTypeFilter, $fromDateFilter, $toDateFilter);

            // Only add students who have matching duties
            if (count($validDutyMaps) > 0) {
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

        // Get all duty types for filter dropdown
        $allDutyTypes = $this->dutyTypeOptions();

        return view('admin.ot_mdo_escrot_exemption.view', compact(
            'studentData',
            'allDutyTypes',
            'dutyTypeFilter',
            'fromDateFilter',
            'toDateFilter'
        ));
    }

    /**
     * Resolve the StudentMaster record for the logged-in (category 'S') user.
     */
    private function resolveStudent($user): ?StudentMaster
    {
        $userId = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->where('user_category', 'S')
            ->value('user_id');

        if (!$userId) {
            return null;
        }

        return StudentMaster::where('pk', $userId)->first();
    }

    /**
     * Build the full, course-validated list of duty rows for a student.
     * One DB query for the duties + cached course lookups. Each row carries the
     * acknowledgement status so callers can compute stats / filter by section.
     */
    private function buildDutyMaps($studentPk, string $currentDate): array
    {
        $dutyMaps = MDOEscotDutyMap::where('selected_student_list', $studentPk)
            ->with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->orderBy('created_date', 'desc')
            ->get();

        $courseCache = [];
        $rows = [];

        foreach ($dutyMaps as $dutyMap) {
            $courseMasterPk = $dutyMap->course_master_pk;

            // Validate course: active and not ended (cached per course pk).
            if (!array_key_exists($courseMasterPk, $courseCache)) {
                $courseCache[$courseMasterPk] = CourseMaster::where('pk', $courseMasterPk)
                    ->where('active_inactive', 1)
                    ->where('end_date', '>=', $currentDate)
                    ->first();
            }
            $course = $courseCache[$courseMasterPk];
            if (!$course) {
                continue;
            }

            $dutyType = $dutyMap->mdoDutyTypeMaster;
            $faculty = $dutyMap->facultyMaster;
            $isCompleted = ($dutyMap->duty_status ?? MDOEscotDutyMap::STATUS_PENDING) === MDOEscotDutyMap::STATUS_COMPLETED;

            $rows[] = [
                'id' => $dutyMap->pk,
                'date' => $dutyMap->mdo_date ? \Carbon\Carbon::parse($dutyMap->mdo_date)->format('Y-m-d') : null,
                'course' => $course->course_name,
                'duty_type' => $dutyType ? $dutyType->mdo_duty_type_name : 'N/A',
                'duty_type_pk' => $dutyMap->mdo_duty_type_master_pk,
                'faculty' => $faculty ? $faculty->full_name : 'N/A',
                'faculty_master_pk' => $dutyMap->faculty_master_pk,
                'description' => $dutyMap->Remark ?? 'N/A',
                'time' => ($dutyMap->Time_from ?? 'N/A') . ' - ' . ($dutyMap->Time_to ?? 'N/A'),
                'created_date' => $dutyMap->created_date ?? null,
                'status' => $isCompleted ? MDOEscotDutyMap::STATUS_COMPLETED : MDOEscotDutyMap::STATUS_PENDING,
                'is_completed' => $isCompleted,
                'acknowledged_at' => $dutyMap->acknowledged_at,
            ];
        }

        // Sort by created_date desc
        usort($rows, function ($a, $b) {
            $dateA = $a['created_date'] ?? '1970-01-01 00:00:00';
            $dateB = $b['created_date'] ?? '1970-01-01 00:00:00';
            return strtotime($dateB) - strtotime($dateA);
        });

        return $rows;
    }

    /**
     * Apply the duty-type + date-range toolbar filters to an already-built list.
     */
    private function applyFilters(array $rows, $dutyTypeFilter, $fromDateFilter, $toDateFilter): array
    {
        return collect($rows)
            ->filter(function ($r) use ($dutyTypeFilter, $fromDateFilter, $toDateFilter) {
                if ($dutyTypeFilter && (string) $r['duty_type_pk'] !== (string) $dutyTypeFilter) {
                    return false;
                }
                if ($fromDateFilter && (!$r['date'] || $r['date'] < $fromDateFilter)) {
                    return false;
                }
                if ($toDateFilter && (!$r['date'] || $r['date'] > $toDateFilter)) {
                    return false;
                }
                return true;
            })
            ->values()
            ->all();
    }

    /**
     * Duty types for the filter dropdown.
     */
    private function dutyTypeOptions(): array
    {
        return MDODutyTypeMaster::where('active_inactive', 1)
            ->orderBy('mdo_duty_type_name')
            ->pluck('mdo_duty_type_name', 'pk')
            ->toArray();
    }
}
