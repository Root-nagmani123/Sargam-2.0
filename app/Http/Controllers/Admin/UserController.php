<?php

namespace App\Http\Controllers\Admin;

use Adldap\Laravel\Facades\Adldap;
use App\DataTables\CourseMasterDataTable;
use App\DataTables\FacultyDataTable;
use App\DataTables\GroupMappingDataTable;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\DataTables\MemberDataTable;
use App\DataTables\RoleDataTable;
use App\Exports\StudentListReportExport;
use App\Exports\BrandedGridExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Admin\IssueManagement\IssueCategoryController;
use App\Http\Controllers\Admin\IssueManagement\IssueEscalationMatrixController;
use App\Http\Controllers\Admin\IssueManagement\IssuePriorityController;
use App\Http\Controllers\Admin\IssueManagement\IssueSubCategoryController;
use App\Http\Controllers\Admin\Master\FacultyExpertiseMasterController;
use App\Http\Controllers\Admin\Master\FacultyTypeMasterController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\CalendarEvent;
use App\Models\CourseCordinatorMaster;
use App\Models\CourseMaster;
use App\Models\CourseStudentAttendance;
use App\Models\DashboardCard;
use App\Models\EmployeeMaster;
use App\Models\EmployeeRoleMapping;
use App\Models\FacultyMaster;
use App\Models\Holiday;
use App\Services\NotificationService;
use App\Exports\LbsnaaTableExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\StudentMedicalExemption;
use App\Models\LeaveApplication;
use App\Models\MDOEscotDutyMap;
use App\Models\StudentCourseGroupMap;
use App\Models\ClassSessionMaster;
use App\Models\VenueMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\StudentMaster;
use App\Services\Attendance\OtExemptionResolver;
use App\Services\Discipline\OtMarksDeductedService;
use App\Services\Messaging\EmailService;
use App\Services\Messaging\SmsService;
use App\Services\FacultyFeedbackReportService;
use App\Services\Timetable\FacultySessionScope;
use App\Services\FC\RegistrationService;
use App\Models\MemoDiscipline;
use App\Models\CourseGroupTimetableMapping;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityDupPermIdApply;
use App\Models\Notification;
use App\Models\SecurityFamilyIdApply;
use App\Models\User;
use App\Models\UserRoleMaster;
use App\Models\VehiclePassFWApply;
use App\Models\VehiclePassTWApply;
use App\Services\OTNoticeMemoService;
use App\Support\DataTableRedisCache;
use App\Support\LogSafe;
use App\Support\PdfPageNumbers;
use App\Models\OtParticipantComment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /** Notices per page on the dashboard feed. */
    private const NOTICE_FEED_PER_PAGE = 10;

    /**
     * The courses that are running right now — flagged active in the master AND
     * not past their end date. One definition for the faculty dashboard's three
     * course-scoped features (My Counsellees, House Wise Details and the House
     * wise Performance panel), so a batch leaves all of them on the same day.
     *
     * A course with no end date has not ended.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function currentCourseIds(): \Illuminate\Support\Collection
    {
        return CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->pluck('pk');
    }

    private const ADMIN_USERS_INDEX_LIST_EPOCH_KEY = 'admin_users_index_list_epoch';

    /**
     * Rows the PDF export will lay out before it truncates.
     *
     * DomPDF builds the whole frame tree in memory: measured on this listing it
     * peaks at 178 MB for 500 rows and 364 MB for 1,000, and fatals outright at
     * 1,500 under the 512 MB limit. user_credentials has ~15k rows, so an
     * uncapped PDF is a guaranteed 500. CSV and XLSX have no such ceiling and
     * stay complete; the sheet says so when it truncates.
     */
    private const ADMIN_USERS_PDF_ROW_CAP = 750;

    /**
     * Row ceiling for the printable HTML sheet.
     *
     * Higher than the PDF's because the browser does the layout rather than
     * DomPDF, but not absent: uncapped, one request built a 9.6 MB document out
     * of all ~15k rows, server-side, for any user who asked and as often as they
     * asked. That is a cost the application pays, not the browser.
     */
    private const ADMIN_USERS_PRINT_ROW_CAP = 5000;

    /**
     * Human-readable labels for the user_category code stored on user_credentials.
     * Extend this map as new user types are introduced.
     */
    public const USER_TYPE_LABELS = [
        'S' => 'Student',
        'E' => 'Employee',
        'F' => 'Faculty',
        'A' => 'Admin',
    ];

    /**
     * Resolve a user_category code to a display label, falling back gracefully.
     */
    public static function userTypeLabel($code): string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return 'Unknown';
        }

        return self::USER_TYPE_LABELS[$code] ?? 'Other';
    }

    /**
     * Display a listing of users.
     *
     * @return View
     */
    public function dashboard(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // Fetch holidays for the selected month/year
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $holidays = Holiday::active()
            ->whereBetween('holiday_date', [$startDate, $endDate])
            ->get();

        // Format events array with holidays
        $events = [];
        foreach ($holidays as $holiday) {
            $dateKey = $holiday->holiday_date->format('Y-m-d');
            if (! isset($events[$dateKey])) {
                $events[$dateKey] = [];
            }
            $events[$dateKey][] = [
                'title' => $holiday->holiday_name,
                'type' => 'holiday',
                'holiday_type' => $holiday->holiday_type,
                'description' => $holiday->description,
            ];
        }

        // Add logged-in user's birthday to calendar
        $currentUser = Auth::user();
        $userEmployee = $currentUser ? EmployeeMaster::where('pk', $currentUser->user_id)->where('status', 1)->first() : null;
        if ($userEmployee && $userEmployee->dob) {
            $dob = Carbon::parse($userEmployee->dob);
            $birthdayThisYear = $dob->copy()->year($year);
            if ((int) $birthdayThisYear->month === (int) $month) {
                $bdKey = $birthdayThisYear->format('Y-m-d');
                if (! isset($events[$bdKey])) {
                    $events[$bdKey] = [];
                }
                $events[$bdKey][] = [
                    'title' => 'Your Birthday! 🎂',
                    'type' => 'birthday',
                    'description' => 'Happy Birthday!',
                ];
            }
        }

        $emp_dob_data = EmployeeMaster::where('status', 1)->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
            ->where('employee_master.pk', '!=', Auth::user()->user_id ?? 0)
            ->leftjoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->select(
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.email',
                'employee_master.mobile',
                'employee_master.office_extension_no',
                'employee_master.profile_picture',
                'employee_master.last_name',
                'designation_master.designation_name',
                'employee_master.dob'
            )
            ->get();

        // Check if today is logged-in user's birthday
        $isMyBirthday = false;
        if ($userEmployee && $userEmployee->dob) {
            $myDob = Carbon::parse($userEmployee->dob);
            $isMyBirthday = $myDob->format('m-d') === now()->format('m-d');
        }

        // Count wishes received today (birthday notifications for logged-in user)
        $myBirthdayWishCount = 0;
        if ($isMyBirthday) {
            $myBirthdayWishCount = Notification::where('receiver_user_id', Auth::user()->user_id)
                ->where('type', 'birthday')
                ->whereDate('created_at', today())
                ->count();
        }

        // Wish count per birthday person (how many wishes they received today)
        $birthdayWishCounts = [];
        if ($emp_dob_data->isNotEmpty()) {
            $birthdayPks = $emp_dob_data->pluck('pk')->toArray();
            $birthdayWishCounts = Notification::whereIn('receiver_user_id', $birthdayPks)
                ->where('type', 'birthday')
                ->whereDate('created_at', today())
                ->selectRaw('receiver_user_id, COUNT(*) as wish_count')
                ->groupBy('receiver_user_id')
                ->pluck('wish_count', 'receiver_user_id')
                ->toArray();
        }

        // Upcoming birthdays (next 7 days, excluding today)
        $upcomingBirthdays = collect();
        for ($i = 1; $i <= 7; $i++) {
            $futureDate = now()->addDays($i);
            $upcoming = EmployeeMaster::where('status', 1)
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$futureDate->format('m-d')])
                ->leftjoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
                ->select(
                    'employee_master.pk',
                    'employee_master.first_name',
                    'employee_master.last_name',
                    'employee_master.profile_picture',
                    'employee_master.dob',
                    'designation_master.designation_name'
                )
                ->get()
                ->each(function ($emp) use ($futureDate) {
                    $emp->birthday_date = $futureDate->format('d M');
                    $emp->days_away = $futureDate->diffInDays(now());
                });
            $upcomingBirthdays = $upcomingBirthdays->merge($upcoming);
        }

        $totalActiveCourses = CourseMaster::where('active_inactive', 1)->where('start_year', '<=', now()->toDateString())->where('end_date', '>=', now()->toDateString())->count();
        $upcomingCourses = CourseMaster::where('active_inactive', 1)->where('start_year', '>', now()->toDateString())->count();
        $upcomingEventsCount = Holiday::active()->where('holiday_date', '>', now())->count();

        $total_guest_faculty = FacultyMaster::where('active_inactive', 1)->where('faculty_type', 2)->count();
        $total_internal_faculty = FacultyMaster::where('active_inactive', 1)->where('faculty_type', 1)->count();
        //   print_r($emp_data);exit;
        $exemptionCount = 0;
        $MDO_count = 0;
        $myGroupsCount = 0;
        $todayTimetable = collect([]);
        $totalSessions = 0;
        $totalStudents = 0;
        $facultyTotalSessions = 0;
        $facultyTotalFeedback = 0;
        $facultyCounsellees = 0;
        $facultyHouses = 0;
        $isCCorACC = false;
        $userId = Auth::user()->user_id;
        if (hasRole('Student-OT')) {
            $exemptionQuery = StudentMedicalExemption::where('student_master_pk', $userId)
                ->where('active_inactive', 1);
            $exemptionCount = $exemptionQuery->count();

            $MDO_count = MDOEscotDutyMap::where('selected_student_list', $userId)
                ->with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
                ->count();

            // "My Groups" card: how many Course Group Mapping groups this OT is in.
            // Same trainee check as the page it opens (F-055).
            $myGroupsCount = $this->isMyGroupsTrainee()
                ? $this->myGroupsQuery($userId)->distinct()->count('gmap.pk')
                : 0;

            // Fetch today's timetable for the logged-in student
            $todayTimetable = $this->getTodayTimetableForStudent($userId);
        }

         // "Total Marks Deducted in Discipline" — every concluded deduction against
         // this OT, from BOTH registers: Discipline Memos and Memo/Notices. Counted
         // through the service the page behind the card lists from, so the tile and
         // the rows agree.
         //
         // Gated on isOfficerTraineeUser(), not hasRole('Student-OT') like the block
         // above: Student-OT is a session pseudo-role set at login, so an OT who
         // arrives holding only the Spatie "Officer Trainee" role would have been
         // shown a card reading zero over real deductions.
         //
         // And only for user_category 'S' (PR #334 F-047): user_id is a student_master
         // pk only for a trainee login. A staff login that also holds the role has an
         // employee / faculty pk there, which can equal another trainee's pk — the
         // pages behind both cards refuse it for that reason.
         $disciplineMarksDeducted = 0;
         $pendingFeedbackCount = 0;
         if (isOfficerTraineeUser() && (Auth::user()->user_category ?? null) === 'S') {
             $disciplineMarksDeducted = app(OtMarksDeductedService::class)->totalFor((int) $userId);
             $pendingFeedbackCount = $this->getOtPendingFeedbackCount($userId);
         }

         // Calculate total sessions for faculty portal users (Faculty / Internal / Guest)
         if (is_faculty_portal_user()) {
             $facultyPk = get_auth_faculty_master_pk();

            if ($facultyPk) {
                $totalSessions = CalendarEvent::where('active_inactive', 1)
                    ->where(function ($query) use ($facultyPk) {
                        $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$facultyPk.'"'])
                            ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$facultyPk]);
                    })
                    ->count();

                 // "Total Sessions" card — the sessions this faculty TEACHES, across
                 // active and ended courses alike. Counted through the same scope the
                 // Timetable Session Report filters by, and the card links to that
                 // report's All Courses tab with the Role filter on Teaching, so the
                 // page it opens holds exactly these rows.
                 $facultyTotalSessions = FacultySessionScope::countFor(
                     $facultyPk,
                     'all',
                     FacultySessionScope::ROLE_TEACHING
                 );

                 // "Total Running Courses Feedback" card — this faculty's submitted
                 // feedback on running (active, not yet ended) courses, counted the way
                 // the Faculty Feedback with Comments page the card opens counts it, so
                 // the figure is verifiable on the page it leads to.
                 $facultyTotalFeedback = app(FacultyFeedbackReportService::class)
                     ->getTotalFeedbackCount($facultyPk);

                 // Both cards count students off the Course Group Mapping page, for
                 // the groups mapped to THIS faculty, and open the OT / Participants
                 // list on exactly that scope so the number and the rows agree.
                 //
                 //   My Counsellees     -> their Counsellor Groups (the cadres)
                 //   House Wise Details -> their House Groups, i.e. how many
                 //                         students their house holds
                 //
                 // Both on current courses only: a course switched off in the master,
                 // or one whose end date has passed, stops counting.
                 // A COUNT(DISTINCT) over the same mappings facultyGroupRows() reads,
                 // not the full hydrated rows: this runs on every faculty dashboard
                 // load (PR #334 F-010; measured 22 -> 6 queries, ~50 -> ~8 ms for a
                 // faculty with 48 + 46 students, identical counts).
                 $facultyCounsellees = $this->facultyGroupStudentCount($facultyPk, '%counsel%', true);

                 $facultyHouses = $this->facultyGroupStudentCount($facultyPk, '%house%', true);

                 // Check if faculty is CC or ACC
                 $coordinatorCourses = $this->getCoordinatorCourseIds($facultyPk);

                // Flag CC/ACC so the "Total Students" / "Student Details" cards
                // become visible for them (card visibility is unchanged).
                if ($coordinatorCourses->isNotEmpty()) {
                    $isCCorACC = true;
                }

                // "Total Students" is scoped to the viewer's COURSE ACCESS —
                // get_Role_by_course() maps the user's role(s) to
                // course_master.user_role_master_pk, the same access basis the
                // "My Course Participant" card uses. Empty result = no restriction
                // (Admin / Super Admin / PA see all); [-1] = no access → zero.
                // Counted as DISTINCT active enrolments so a student in several
                // accessible courses is not double-counted.
                $roleCourseIds = get_Role_by_course();
                $totalStudents = $this->dashboardTotalStudentsCount($roleCourseIds);
            } else {
                $totalSessions = 0;
            }

            // Fetch today's timetable for the logged-in faculty
            $todayTimetable = $this->getTodayTimetableForFaculty($userId);
        }

        // Super Admin / PA / Admin also see the "Total Students" / "Student Details"
        // cards, but they are NOT faculty-portal users, so the block above skips them
        // and $totalStudents stayed 0. Compute it here on the same basis as the
        // faculty branch (see dashboardTotalStudentsCount()).
        if (! hasRole('Student-OT') && ! is_faculty_portal_user()) {
            $roleCourseIds = get_Role_by_course();
            $totalStudents = $this->dashboardTotalStudentsCount($roleCourseIds);
        }

        if ($request->boolean('calendar_only')) {
            $calendarHtml = view('components.calendar', [
                'year' => $year,
                'month' => $month,
                'selected' => now()->toDateString(),
                'events' => $events,
                'theme' => 'gov-red',
            ])->render();

            return response()->json([
                'html' => $calendarHtml,
            ]);
        }

        $todayFamilyApprovals = $this->getTodayPendingFamilyApprovalsCount(true);
        $fullFamilyApprovals = $this->getTodayPendingFamilyApprovalsCount(false);
        $todayVehicleApprovals = $this->getTodayPendingVehicleApprovalsCount(true);
        $fullVehicleApprovals = $this->getTodayPendingVehicleApprovalsCount(false);
        $todayIdCardRequests = $this->getTodayPendingIdCardRequestsCount();
        $todayPendingSplit = $this->getTodayPendingIdCardRequestsSplit(true);
        $todayPendingPermanentIdCardRequests = (int) ($todayPendingSplit['perm'] ?? 0);
        $todayPendingContractualIdCardRequests = (int) ($todayPendingSplit['cont'] ?? 0);
        $fullPendingSplit = $this->getTodayPendingIdCardRequestsSplit(false);
        $fullPendingPermanentIdCardRequests = (int) ($fullPendingSplit['perm'] ?? 0);
        $fullPendingContractualIdCardRequests = (int) ($fullPendingSplit['cont'] ?? 0);
        $todayApproval1Split = $this->getTodayPendingSecurityApproval1Split();
        $todayApproval1IdCardRequests = (int) ($todayApproval1Split['idcard'] ?? 0);
        $todayApproval1DuplicateIdCardRequests = (int) ($todayApproval1Split['duplicate'] ?? 0);
        $todayDuplicatePermIdCardRequests = $this->getTodayDuplicatePermanentIdCardRequestsCount(true);
        $todayDuplicateContractualIdCardRequests = $this->getTodayDuplicateContractualIdCardRequestsCount(true);
        $fullDuplicatePermIdCardRequests = $this->getTodayDuplicatePermanentIdCardRequestsCount(false);
        $fullDuplicateContractualIdCardRequests = $this->getTodayDuplicateContractualIdCardRequestsCount(false);
        $idCardApprovalRoute = route('admin.security.employee_idcard_approval.all');

        // Role flags used for card visibility
        $isSecurityRole = hasRole('Security Card') || hasRole('Admin Security');
        $isSuperAdmin   = hasRole('Super Admin');
        $isStudentOT    = hasRole('Student-OT');
        // Student-OT is a pseudo-role set at login; an OT who arrives holding only
        // the Spatie "Officer Trainee" role does not have it. The OT cards below
        // key off this instead, or their links would resolve to the staff pages —
        // the same trap the discipline card documents.
        $isOtUser       = $isStudentOT || isOfficerTraineeUser();
        $isFacultyRole  = hasRole('Internal Faculty') || hasRole('Guest Faculty');
        // The two faculty cards below key off portal membership, not those two role
        // names — the only faculty role actually present is "Faculty", which
        // $isFacultyRole does not match.
        $isFacultyPortalUser = is_faculty_portal_user();

        // Role-scoped course IDs for "My Course Participant" ([] = all, [-1] = none, [pks] = restricted)
        $myCourseIds = get_Role_by_course();

        // Hardcoded card definitions: count, link, visibility
        $cardDefinitions = [
            'pending_permanent_id'    => ['count' => $todayPendingPermanentIdCardRequests ?? 0,    'link' => $idCardApprovalRoute,                                          'visible' => $isSecurityRole || $isSuperAdmin],
            'pending_contractual_id'  => ['count' => $todayPendingContractualIdCardRequests ?? 0,  'link' => $idCardApprovalRoute,                                          'visible' => $isSecurityRole || $isSuperAdmin],
            'duplicate_permanent_id'  => ['count' => $todayDuplicatePermIdCardRequests ?? 0,       'link' => $idCardApprovalRoute,                                          'visible' => $isSecurityRole || $isSuperAdmin],
            'duplicate_contractual_id'=> ['count' => $todayDuplicateContractualIdCardRequests ?? 0,'link' => $idCardApprovalRoute,                                          'visible' => $isSecurityRole || $isSuperAdmin],
            'requested_family_id'     => ['count' => $todayFamilyApprovals ?? 0,                   'link' => route('admin.security.family_idcard_approval.index'),          'visible' => $isSecurityRole || $isSuperAdmin],
            'requested_vehicle_pass'  => ['count' => $todayVehicleApprovals ?? 0,                  'link' => route('admin.security.vehicle_pass_approval.index'),           'visible' => $isSecurityRole || $isSuperAdmin],
            'total_active_courses'    => ['count' => $totalActiveCourses,                          'link' => route('admin.dashboard.active_course'),                        'visible' => !$isSecurityRole],
            'upcoming_courses'        => ['count' => $upcomingCourses,                             'link' => route('admin.dashboard.incoming_course'),                      'visible' => !$isSecurityRole],
            'upcoming_events'         => ['count' => $upcomingEventsCount,                         'link' => route('admin.dashboard.upcoming_events'),                      'visible' => !$isSecurityRole],
            'medical_exception'       => ['count' => $exemptionCount ?? 0,                         'link' => route('medical.exception.ot.view'),                            'visible' => !$isSecurityRole && $isStudentOT],
            'total_guest_faculty'     => ['count' => $total_guest_faculty,                         'link' => route('admin.dashboard.guest_faculty'),                        'visible' => !$isSecurityRole && !$isStudentOT],
            'pending_id_approval1'    => ['count' => $todayApproval1IdCardRequests ?? 0,           'link' => route('admin.security.employee_idcard_approval.approval1'),    'visible' => !$isSecurityRole && ($todayApproval1IdCardRequests ?? 0) > 0],
            'pending_dup_id_approval1'=> ['count' => $todayApproval1DuplicateIdCardRequests ?? 0,  'link' => route('admin.security.employee_idcard_approval.approval1'),    'visible' => !$isSecurityRole && ($todayApproval1DuplicateIdCardRequests ?? 0) > 0],
            'ot_mdo_escort'           => ['count' => $MDO_count ?? 0,                              'link' => route('ot.mdo.escrot.exemption.view'),                         'visible' => !$isSecurityRole && $isStudentOT],
            'my_groups'               => ['count' => $myGroupsCount ?? 0,                          'link' => route('admin.dashboard.my-groups'),                            'visible' => !$isSecurityRole && $isStudentOT],
            'total_inhouse_faculty'   => ['count' => $total_internal_faculty,                      'link' => route('admin.dashboard.inhouse_faculty'),                      'visible' => !$isSecurityRole && !$isStudentOT],
            'session_details'         => ['count' => $totalSessions,                               'link' => route('admin.dashboard.sessions'),                             'visible' => !$isSecurityRole && ($isFacultyRole || $isSuperAdmin)],
            // Faculty-only cards. Both open a report that scopes itself to the
            // logged-in faculty server-side, so the count and the page agree.
            'total_sessions'          => ['count' => $facultyTotalSessions,                        'link' => route('timetable-report.index', ['course_mode' => 'all', 'faculty_role' => FacultySessionScope::ROLE_TEACHING]), 'visible' => !$isSecurityRole && $isFacultyPortalUser],
            'total_feedback'          => ['count' => $facultyTotalFeedback,                        'link' => route('faculty.session_feedback.comments', ['course_type' => 'current', 'program_id' => 'all']), 'visible' => !$isSecurityRole && $isFacultyPortalUser],
            // No count on the two timetable cards: they open a calendar, not a list
            // whose rows could be counted. Academic = the whole Academy's timetable
            // (?scope=academy), My Timetable = the same page scoped to the viewer,
            // which is what it already does for a faculty login.
            'academic_timetable'      => [                                                         'link' => route('calendar.index', ['scope' => 'academy']),               'visible' => !$isSecurityRole && ($isFacultyPortalUser || $isOtUser)],
            // An OT's own timetable is their dedicated calendar — the sessions of
            // the groups they are enrolled in. A faculty's is the same page scoped
            // to their classes, which is what it already does for them.
            'my_timetable'            => ['link' => $isOtUser ? route('calendar.ot.index') : route('calendar.index'),                            'visible' => !$isSecurityRole && ($isFacultyPortalUser || $isOtUser)],
            // Both open the OT / Participants list — the second ordered by House so it
            // opens house-wise, with the page's House filter to narrow to one.
            'my_counsellees'          => ['count' => $facultyCounsellees,                          'link' => route('admin.dashboard.ot-participants', ['view' => 'counsellees']), 'visible' => !$isSecurityRole && $isFacultyPortalUser],
            'house_wise_details'      => ['count' => $facultyHouses,                               'link' => route('admin.dashboard.ot-participants', ['view' => 'house']), 'visible' => !$isSecurityRole && $isFacultyPortalUser],
            // No count: Who's Who opens on a course picker, so there is no single
            // number the tile could honestly show — same as the timetable cards.
            'whos_who'                => [                                                         'link' => route('admin.faculty.whos-who'),                               'visible' => !$isSecurityRole && $isFacultyPortalUser],
            'total_students'          => ['count' => $totalStudents,                               'link' => route('admin.dashboard.students'),                             'visible' => !$isSecurityRole && (isset($isCCorACC) && $isCCorACC)],
            'student_details'         => ['count' => $totalStudents,                               'link' => route('admin.dashboard.students'),                             'visible' => !$isSecurityRole && (isset($isCCorACC) && $isCCorACC)],
            'my_course_participant'   => ['count' => StudentMasterCourseMap::query()->when(!empty($myCourseIds), fn($q) => $q->whereIn('course_master_pk', $myCourseIds))->count(), 'link' => route('my.course.participant'),                                'visible' => true],
            'discipline_marks_deducted' => ['count' => $disciplineMarksDeducted,                   'link' => route('memo.discipline.ot_marks'),                              'visible' => !$isSecurityRole && $isOtUser],
            'pending_feedback'        => ['count' => $pendingFeedbackCount,                        'link' => route('feedback.get.studentFeedbackUrl'),                      'visible' => !$isSecurityRole && $isOtUser],
        ];

        // Count map for custom cards added via UI.
        // Add an entry here when a custom card needs a real count.
        $cardCounts = [
            // 'my_card_key' => SomeModel::where('status', 'pending')->count(),
        ];

        // Fetch which cards are enabled for this user's role
        $userRoles = Auth::user()->roles ?? collect();
        if ($userRoles->isNotEmpty()) {
            $roleIds = $userRoles->pluck('id')->toArray();
            $enabledCards = DashboardCard::whereHas('roles', function ($q) use ($roleIds) {
                $q->whereIn('roles.id', $roleIds);
            })
                ->orderBy('sort_order')
                ->get();
        } else {
            $enabledCards = collect();
        }

        $baseCards = $enabledCards;

        $enabledWidgetKeys = $baseCards->filter(fn ($c) => str_starts_with($c->key, 'widget_'))->pluck('key')->toArray();

        // House wise Performance panel. Built only when the panel is actually on
        // this dashboard — it is four queries, and no other card needs them.
        $houseOnDashboard = in_array('widget_house_performance', $enabledWidgetKeys, true);
        // ?house_course= narrows the panel; the select posts back to the dashboard
        // rather than fetching, so the figure and the page agree without a second
        // code path computing it.
        $houseCourseFilter = $request->filled('house_course') ? (int) $request->input('house_course') : null;
        $housePerformance = $houseOnDashboard
            ? $this->houseWisePerformance($houseCourseFilter)
            : collect();
        $houseCourses = $houseOnDashboard ? $this->houseCourseOptions() : collect();

        $issueReportModules = \App\Http\Controllers\Admin\IssueReportController::moduleOptions();

        // Cards whose 'visible' flag is enforced: the OT and faculty-portal cards,
        // whose role mapping alone would show them to a login the page behind them
        // refuses (an OT card to a non-OT, opening a 403). The older cards' flags
        // were never applied and their role mappings are what admins have tuned
        // against, so they are left as they are.
        $gatedCardKeys = [
            'my_groups', 'discipline_marks_deducted', 'pending_feedback',
            'total_sessions', 'total_feedback', 'my_counsellees', 'house_wise_details', 'whos_who',
            'academic_timetable', 'my_timetable',
        ];

        $cardsToRender = $baseCards->filter(fn ($c) => ! str_starts_with($c->key, 'widget_'))->filter(function ($card) use ($cardDefinitions, $gatedCardKeys) {
            return ! in_array($card->key, $gatedCardKeys, true)
                || ($cardDefinitions[$card->key]['visible'] ?? true);
        })->map(function ($card) use ($cardDefinitions, $cardCounts) {
            $def = $cardDefinitions[$card->key] ?? null;

            return [
                'key' => $card->key,
                'label' => $card->label,
                'icon' => $card->icon,
                'color_class' => $card->color_class,
                'link'        => $def['link'] ?? null,
                'count'       => $def['count'] ?? ($cardCounts[$card->key] ?? 0),
                // A definition that omits 'count' is a card that opens something
                // rather than counting it (the timetables) — it renders as a tile
                // with no number, not as a zero.
                'show_count'  => $def === null || array_key_exists('count', $def),
            ];
        })->values();

        return view('admin.dashboard', compact(
            'year',
            'month',
            'events',
            'emp_dob_data',
            'isMyBirthday',
            'myBirthdayWishCount',
            'birthdayWishCounts',
            'upcomingBirthdays',
            'totalActiveCourses',
            'upcomingCourses',
            'upcomingEventsCount',
            'total_guest_faculty',
            'total_internal_faculty',
            'exemptionCount',
            'MDO_count',
            'disciplineMarksDeducted',
            'todayTimetable',
            'totalSessions',
            'totalStudents',
            'facultyTotalSessions',
            'facultyTotalFeedback',
            'isCCorACC',
            'todayFamilyApprovals',
            'fullFamilyApprovals',
            'todayVehicleApprovals',
            'fullVehicleApprovals',
            'todayIdCardRequests',
            'todayPendingPermanentIdCardRequests',
            'todayPendingContractualIdCardRequests',
            'fullPendingPermanentIdCardRequests',
            'fullPendingContractualIdCardRequests',
            'todayApproval1IdCardRequests',
            'todayApproval1DuplicateIdCardRequests',
            'todayDuplicatePermIdCardRequests',
            'todayDuplicateContractualIdCardRequests',
            'fullDuplicatePermIdCardRequests',
            'fullDuplicateContractualIdCardRequests',
            'idCardApprovalRoute',
            'cardsToRender',
            'enabledWidgetKeys',
            'housePerformance',
            'houseCourses',
            'houseCourseFilter',
            'issueReportModules'
        ));
    }

    /**
     * The students of one KIND of group mapped to a faculty on the Course Group
     * Mapping page — group type LIKE $typeNameLike, faculty = this faculty.
     *
     * That page is the definition behind two dashboard cards:
     *
     *   My Counsellees     -> Counsellor Group, whose group names are the cadres
     *   House Wise Details -> House Group, whose group names are the houses
     *
     * so the same rows give both the card's count and the dropdown on the list it
     * opens. Matched by type name rather than the hard-coded pks (8 and 20), so a
     * renamed type still counts.
     *
     * Deliberately NOT resolveDashboardStudentListPayload(): that pulls in
     * coordinator courses, every other group type the faculty owns and the
     * sessions they taught — which is why My Counsellees read 00 while the mapping
     * page listed 48 students.
     *
     * Rows carry the shape the OT / Participants list expects, plus the group name
     * under $labelProperty, so the column and filter for that view can read the
     * group rather than the student's own cadre master (70 of these students have
     * no cadre on record) or their hostel room.
     *
     * @param  bool  $currentCoursesOnly  Only groups on a running course
     *                                    ({@see currentCourseIds()}).
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function facultyGroupRows(
        int $facultyPk,
        string $typeNameLike,
        string $labelProperty,
        bool $currentCoursesOnly = false
    ): \Illuminate\Support\Collection
    {
        $mappings = $this->facultyGroupMappings($facultyPk, $typeNameLike, $currentCoursesOnly);

        if ($mappings->isEmpty()) {
            return collect();
        }

        $groupMemberships = StudentCourseGroupMap::with([
            'student.cadre',
            'groupTypeMasterCourseMasterMap.courseGroup',
            'groupTypeMasterCourseMasterMap.courseGroupType',
            'groupTypeMasterCourseMasterMap.Faculty',
        ])
            ->whereIn('group_type_master_course_master_map_pk', $mappings->pluck('pk'))
            ->where('active_inactive', 1)
            ->get();

        $groupByMapping = $mappings->keyBy('pk');
        $courses = CourseMaster::whereIn('pk', $mappings->pluck('course_pk')->filter()->unique())->get()->keyBy('pk');

        // House Name, the same lookup the payload does — the column stays on the
        // grid even though the counsellee view offers no House filter.
        $userIds = $groupMemberships->map(fn ($m) => $m->student->user_id ?? null)->filter()->unique()->values()->all();
        $houseByUser = ! empty($userIds)
            ? DB::table('ot_hostel_room_details')
                ->where('active_inactive', 1)
                ->whereIn('user_name', $userIds)
                ->pluck('hostel_room_name', 'user_name')
            : collect();

        $rows = collect();
        $seen = [];

        foreach ($groupMemberships as $membership) {
            $student = $membership->student;
            $mapping = $groupByMapping[$membership->group_type_master_course_master_map_pk] ?? null;

            if (! $student || ! $mapping) {
                continue;
            }

            // One row per student per course, as the rest of the list expects.
            $key = $student->pk . '_' . ($mapping->course_pk ?? 0);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $row = new \stdClass();
            $row->student_master_pk = $membership->student_master_pk;
            $row->course_master_pk = $mapping->course_pk;
            $row->studentMaster = $student;
            $row->course = $courses[$mapping->course_pk] ?? null;
            $row->groupMapping = $membership;
            $row->{$labelProperty} = trim((string) $mapping->group_name);
            // On the counsellee view the cadre IS the counsellor group, which is what
            // the Cadre dropdown offers — the row filter reads cadre_name first.
            if ($labelProperty === 'counsellor_group_name') {
                $row->cadre_name = $row->counsellor_group_name;
            }
            $uid = $student->user_id ?? null;
            $row->house_name = ($uid && isset($houseByUser[$uid])) ? $houseByUser[$uid] : null;
            $row->source = 'faculty_group';

            $rows->push($row);
        }

        return $rows;
    }

    /**
     * The faculty's group mappings of one group type — shared by
     * facultyGroupRows() and facultyGroupStudentCount(), so a dashboard count and
     * the list it opens can never select different groups.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>  pk, group_name, course_pk
     */
    private function facultyGroupMappings(int $facultyPk, string $typeNameLike, bool $currentCoursesOnly): \Illuminate\Support\Collection
    {
        $groupTypeIds = DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->whereRaw('LOWER(type_name) LIKE ?', [$typeNameLike])
            ->pluck('pk');

        if ($groupTypeIds->isEmpty()) {
            return collect();
        }

        return DB::table('group_type_master_course_master_map as g')
            ->whereIn('g.type_name', $groupTypeIds)
            ->where('g.facility_id', $facultyPk)
            ->where('g.active_inactive', 1)
            // Running courses only, when the caller asks: neither a switched-off
            // course nor a finished batch keeps counting. An orphaned mapping —
            // one whose course_name matches no course_master row — drops out with
            // them, there being no course to call current.
            ->when($currentCoursesOnly, fn ($q) => $q->whereIn('g.course_name', $this->currentCourseIds()))
            ->get(['g.pk', 'g.group_name', 'g.course_name as course_pk']);
    }

    /**
     * Distinct students in the faculty's groups of one type — the number the
     * My Counsellees / House Wise Details cards show — as one COUNT(DISTINCT)
     * instead of hydrating every membership through facultyGroupRows() just to
     * count it (PR #334 F-010). Same mappings, same active membership filter,
     * and the same "student row must exist" rule the rows apply.
     */
    private function facultyGroupStudentCount(int $facultyPk, string $typeNameLike, bool $currentCoursesOnly = false): int
    {
        $mappingPks = $this->facultyGroupMappings($facultyPk, $typeNameLike, $currentCoursesOnly)->pluck('pk');

        if ($mappingPks->isEmpty()) {
            return 0;
        }

        return (int) DB::table('student_course_group_map as scgm')
            ->join('student_master as sm', 'sm.pk', '=', 'scgm.student_master_pk')
            ->whereIn('scgm.group_type_master_course_master_map_pk', $mappingPks)
            ->where('scgm.active_inactive', 1)
            // facultyGroupRows()->pluck()->filter() dropped a 0 pk; keep parity.
            ->where('scgm.student_master_pk', '<>', 0)
            ->distinct()
            ->count('scgm.student_master_pk');
    }

    /**
     * "House wise Performance" panel: every house, worst behaviour last.
     *
     * A house is a group on the Course Group Mapping page whose group TYPE is the
     * House one, on a course that is still running — the page's own Active list,
     * so a house added there appears here without any further wiring, and one
     * whose batch has finished leaves. Resolved by type name rather than a
     * hard-coded pk so a renamed or duplicated House type still counts.
     *
     * The figure against each house is its students' Discipline Memos plus their
     * Memo/Notices — memos AND notices together — counting only the CLOSED ones,
     * and only those raised on a course that is still running. A case still being
     * argued is not a result yet, and a finished or switched-off batch is not this
     * term's record.
     *
     * Closed is each module's own end state:
     *
     *   discipline_memo_status.status = 3   (1 Recorded, 2 Memo Sent, 3 Closed)
     *   student_memo_status.status    = 2   (what End Chat sets, alongside the
     *   student_notice_status.status  = 2    "Memo Closed" notice to the OT)
     *
     * A notice can be closed as a notice OR converted into a memo, and a converted
     * one keeps status 2 while its memo lives on in student_memo_status pointing
     * back at it. Counting both would charge that single case twice — every memo
     * on this data came from a notice — so a notice counts only when no memo
     * references it, the same test the Notice/Memo listing makes.
     *
     * Memos sum memo_count, falling back to one per record for the older rows that
     * leave it NULL — the same count the OT / Participants list prints
     * ({@see otParticipantsRowMeta()}); discipline memos and notices are one per
     * record.
     *
     * Students are deduplicated first — the mapping table holds repeat rows, and a
     * student in two mappings of the same house must not pay twice.
     *
     * Ascending, lowest first, because the panel reads as a league table: the
     * house at the top is the one with least against it.
     *
     * @return \Illuminate\Support\Collection<int, array{house: string, total: int, students: int}>
     */
    private function houseWisePerformance(?int $courseFilter = null): \Illuminate\Support\Collection
    {
        ['houses' => $studentsByHouse, 'course_ids' => $currentCourseIds] = $this->houseMemberships($courseFilter);

        if ($studentsByHouse === []) {
            return collect();
        }

        $studentPks = collect($studentsByHouse)->flatMap(fn ($set) => array_keys($set))->unique()->values()->all();

        // Marks, not record counts (UAT 15-09-2026). A house's figure is the sum of
        // every closed deduction against its OTs — final_mark_deduction on discipline
        // memos plus mark_of_deduction on memos/notices. Counting records made two
        // houses look equal when one had lost 2 marks and the other 20.
        //
        // OtMarksDeductedService owns those rules, and the OT's own card and page
        // already read it, so a house total and the OTs' own totals cannot disagree.
        // Scoped to running courses, the same way the houses above are.
        //
        // Per (student, course): a house belongs to one course, so it takes only the
        // marks its OTs lost on that course (PR #334 F-052).
        $marksByStudent = app(OtMarksDeductedService::class)
            ->totalsForStudentsByCourse($studentPks, $currentCourseIds instanceof \Illuminate\Support\Collection
                ? $currentCourseIds->all()
                : (array) $currentCourseIds);

        return collect($studentsByHouse)
            ->map(function (array $students, string $house) use ($marksByStudent) {
                $total = 0.0;
                foreach ($students as $pk => $courses) {
                    foreach (array_keys($courses) as $coursePk) {
                        $total += (float) ($marksByStudent[(int) $pk][(int) $coursePk] ?? 0);
                    }
                }

                return [
                    'house' => $house,
                    'total' => round($total, 2),
                    'students' => count($students),
                ];
            })
            ->sortBy([['total', 'asc'], ['house', 'asc']])
            ->values();
    }

    /**
     * Which officer trainees sit in which house, on the courses running now.
     *
     * Shared by the dashboard panel and the House wise Performance page, so the
     * tile's figure and the page's rows are drawn from exactly the same set — a
     * house missing from one and present in the other would be indefensible.
     *
     * Each member carries the course(s) of the mapping that put them in the house,
     * so a total can take only that course's marks (PR #334 F-052).
     *
     * @return array{houses: array<string, array<int, array<int, true>>>, course_ids: mixed}  house => [student_pk => [course_pk => true]]
     */
    private function houseMemberships(?int $courseFilter = null): array
    {
        $currentCourseIds = collect($this->currentCourseIds())->map(fn ($id) => (int) $id);

        // A course filter narrows the running courses rather than replacing them,
        // so a finished or switched-off course cannot be reached by passing its id.
        if ($courseFilter !== null) {
            $currentCourseIds = $currentCourseIds->filter(fn ($id) => $id === $courseFilter)->values();

            if ($currentCourseIds->isEmpty()) {
                return ['houses' => [], 'course_ids' => collect([-1])];
            }
        }

        $houseTypeIds = DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->whereRaw('LOWER(type_name) LIKE ?', ['%house%'])
            ->pluck('pk');

        if ($houseTypeIds->isEmpty()) {
            return ['houses' => [], 'course_ids' => $currentCourseIds];
        }

        // Every house on a RUNNING course. A house whose batch has finished, or
        // whose course was switched off in the master, drops out with it —
        // otherwise the list grows a row per past programme and stops being
        // this term's table.
        $mappings = DB::table('group_type_master_course_master_map')
            ->whereIn('type_name', $houseTypeIds)
            ->whereIn('course_name', $currentCourseIds)
            ->where('active_inactive', 1)
            ->whereNotNull('group_name')
            ->where('group_name', '<>', '')
            ->get(['pk', 'group_name', 'course_name']);

        if ($mappings->isEmpty()) {
            return ['houses' => [], 'course_ids' => $currentCourseIds];
        }

        $houseByMapping = $mappings->pluck('group_name', 'pk');
        $courseByMapping = $mappings->pluck('course_name', 'pk');

        // house name => [student_master_pk => [course_master_pk => true]]
        $studentsByHouse = [];
        foreach ($houseByMapping as $houseName) {
            $studentsByHouse[trim((string) $houseName)] ??= [];
        }

        $memberships = DB::table('student_course_group_map')
            ->whereIn('group_type_master_course_master_map_pk', $mappings->pluck('pk'))
            ->where('active_inactive', 1)
            ->get(['group_type_master_course_master_map_pk as map_pk', 'student_master_pk']);

        foreach ($memberships as $row) {
            $house = trim((string) ($houseByMapping[$row->map_pk] ?? ''));
            if ($house === '' || empty($row->student_master_pk)) {
                continue;
            }
            $studentsByHouse[$house][(int) $row->student_master_pk][(int) ($courseByMapping[$row->map_pk] ?? 0)] = true;
        }

        return ['houses' => $studentsByHouse, 'course_ids' => $currentCourseIds];
    }

    /**
     * House wise Performance in full: every house, its officer trainees, and each
     * closed deduction against them, with the house total last.
     *
     * The page the dashboard panel links to.
     */
    public function houseWisePerformanceDetail(Request $request)
    {
        // The route carries only `auth`, and the rows are trainees' discipline
        // deductions. Admit exactly whoever the dashboard shows the panel to,
        // before any query and for the page and both downloads alike.
        abort_unless($this->canSeeHousePerformance(), 403);

        $courseFilter = $request->filled('course') ? (int) $request->input('course') : null;
        $houses = $this->houseWisePerformanceRows($courseFilter);

        $format = is_string($request->get('format')) ? strtolower($request->get('format')) : '';
        if ($format === 'excel' || $format === 'pdf') {
            return $this->exportHouseWisePerformance($houses, $format, $courseFilter);
        }

        return view('admin.dashboard.house_wise_performance', [
            'houses' => $houses,
            'generatedOn' => now(),
            'courses' => $this->houseCourseOptions(),
            'courseFilter' => $courseFilter,
        ]);
    }

    /**
     * Whether the signed-in user may see House wise Performance: Super Admin, or
     * a role the House wise Performance dashboard widget is assigned to — the
     * same role → dashboard_cards mapping dashboard() uses to show the panel.
     */
    private function canSeeHousePerformance(): bool
    {
        // A trainee login is refused before any role is consulted, as in
        // canUseOtParticipants(): an OT account carrying a staff role must not read
        // every trainee's discipline deductions (PR #334 F-034).
        if (isTraineeLogin()) {
            return false;
        }

        if (hasRole('Super Admin')) {
            return true;
        }

        $roleIds = (Auth::user()->roles ?? collect())->pluck('id')->all();

        return $roleIds !== []
            && DashboardCard::where('key', 'widget_house_performance')
                ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
                ->exists();
    }

    /**
     * Running courses that actually have a house mapped — the options both the
     * dashboard card's filter and the page's filter offer. A course with no
     * house would filter the panel down to nothing, so it is not listed.
     */
    private function houseCourseOptions()
    {
        $houseTypeIds = DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->whereRaw('LOWER(type_name) LIKE ?', ['%house%'])
            ->pluck('pk');

        if ($houseTypeIds->isEmpty()) {
            return collect();
        }

        $courseIds = DB::table('group_type_master_course_master_map')
            ->whereIn('type_name', $houseTypeIds)
            ->whereIn('course_name', $this->currentCourseIds())
            ->where('active_inactive', 1)
            ->whereNotNull('group_name')
            ->where('group_name', '<>', '')
            ->distinct()
            ->pluck('course_name');

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return CourseMaster::whereIn('pk', $courseIds)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');
    }

    /**
     * House wise Performance rows: every house, the officer trainees who have
     * actually lost marks, and each closed deduction behind that.
     *
     * Only OTs carrying a penalty are listed (UAT): a house roster of 80 where
     * 3 have deductions was 77 rows of "no deduction on record", which buried
     * the 3 rows the page exists to show. Deductions are closed-only already —
     * OtMarksDeductedService never counts an open case, because a mark is only
     * written at conclusion.
     */
    private function houseWisePerformanceRows(?int $courseFilter = null): \Illuminate\Support\Collection
    {
        ['houses' => $studentsByHouse, 'course_ids' => $currentCourseIds] = $this->houseMemberships($courseFilter);

        $studentPks = collect($studentsByHouse)->flatMap(fn ($set) => array_keys($set))->unique()->values()->all();

        $courseIdList = $currentCourseIds instanceof \Illuminate\Support\Collection
            ? $currentCourseIds->all()
            : (array) $currentCourseIds;

        $service = app(OtMarksDeductedService::class);
        $rowsByStudent = $service->rowsForStudents($studentPks, $courseIdList);

        $students = empty($studentPks)
            ? collect()
            : StudentMaster::whereIn('pk', $studentPks)
                ->get(['pk', 'display_name', 'first_name', 'last_name', 'generated_OT_code'])
                ->keyBy('pk');

        // One entry per house: its penalised OTs (each with their deduction rows)
        // and the house total, which is the sum of those rows.
        return collect($studentsByHouse)
            ->map(function (array $memberSet, string $house) use ($rowsByStudent, $students) {
                $members = collect($memberSet)
                    ->map(function (array $courses, int $pk) use ($rowsByStudent, $students) {
                        $student = $students->get($pk);
                        // Only deductions on this house's course (PR #334 F-052).
                        $rows = collect($rowsByStudent->get($pk, collect()))
                            ->filter(fn (array $row) => isset($courses[$row['course_pk']]))
                            ->values();

                        return [
                            'name' => $this->studentDisplayName($student),
                            'ot_code' => $student->generated_OT_code ?? '-',
                            'rows' => $rows,
                            'total' => round((float) $rows->sum('marks'), 2),
                        ];
                    })
                    // Only those carrying a final mark against them.
                    ->filter(fn (array $member) => $member['total'] > 0)
                    // Heaviest penalty first, then alphabetical — the reason
                    // someone opens this page is to see who is carrying marks.
                    ->sortBy([['total', 'desc'], ['name', 'asc']])
                    ->values();

                return [
                    'house' => $house,
                    'members' => $members,
                    'student_count' => $members->count(),
                    'total' => round((float) $members->sum('total'), 2),
                ];
            })
            // A house with nobody penalised has nothing to report.
            ->filter(fn (array $house) => $house['members']->isNotEmpty())
            ->sortBy('house')
            ->values();
    }

    /**
     * Excel or PDF of House wise Performance — one flat table, each house's rows
     * followed by its Final Marks line, so the file reads like the page.
     */
    private function exportHouseWisePerformance(\Illuminate\Support\Collection $houses, string $format, ?int $courseFilter = null)
    {
        $filterLine = $courseFilter
            ? 'Course: ' . (CourseMaster::where('pk', $courseFilter)->value('course_name') ?: $courseFilter)
            : '';

        // Same columns and same row order as the page, so a download reads like
        // the screen it came from: a band per house, one row per deduction, each
        // trainee's own subtotal, then Final Marks.
        $headings = ['S. No.', 'Student Name', 'OT Code', 'Discipline Category', 'Marks'];
        $centreColumns = [0, 2, 4];
        $today = now()->format('d M Y');

        $data = collect();
        $sectionRows = [];
        $totalRows = [];

        foreach ($houses as $house) {
            $sectionRows[] = $data->count();
            $data->push([
                $house['house'] . '  —  ' . $today
                    . '   (' . $house['student_count'] . ' OT' . ($house['student_count'] == 1 ? '' : 's')
                    . ', Total Marks Deducted: ' . ($house['total'] + 0) . ')',
                '', '', '', '',
            ]);

            foreach ($house['members'] as $index => $member) {
                foreach ($member['rows'] as $i => $row) {
                    $data->push([
                        $i === 0 ? $index + 1 : '',
                        $i === 0 ? $member['name'] : '',
                        $i === 0 ? $member['ot_code'] : '',
                        trim($row['category'] . (empty($row['severity']) ? '' : ' (' . $row['severity'] . ')')),
                        $row['marks'] + 0,
                    ]);
                }

                // Every trainee's own total, as on the page.
                $totalRows[] = $data->count();
                $data->push(['', '', '', $member['name'] . ' — Total Marks', $member['total'] + 0]);
            }

            $totalRows[] = $data->count();
            $data->push(['', '', '', 'Final Marks — ' . $house['house'], $house['total'] + 0]);
        }

        $baseName = 'House_Wise_Performance_' . now()->format('Ymd_His');
        $title = 'House wise Performance';

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            return Pdf::loadView('admin.exports.table_pdf', [
                'headings' => $headings,
                'rows' => $data,
                'reportTitle' => $title,
                'filterLine' => $filterLine,
                'centreColumns' => $centreColumns,
                'sectionRows' => $sectionRows,
                'totalRows' => $totalRows,
            ])->setPaper('a4', 'portrait')->download($baseName . '.pdf');
        }

        return Excel::download(
            new LbsnaaTableExport($data, $headings, $title, $filterLine, $centreColumns, null, $sectionRows, $totalRows),
            $baseName . '.xlsx'
        );
    }

    /** Display name for a student_master row, falling back to first + last. */
    private function studentDisplayName($student): string
    {
        if (! $student) {
            return 'Officer Trainee';
        }

        $display = trim((string) ($student->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        return trim(implode(' ', array_filter([
            $student->first_name ?? '',
            $student->last_name ?? '',
        ]))) ?: 'Officer Trainee';
    }

    /**
     * Dashboard feed "See all" page (notifications, notices, birthdays, wishes).
     */
    public function dashboardFeed(Request $request)
    {
        $allowedTabs = ['notifications', 'notices', 'birthdays', 'wishes'];
        $activeTab = $request->query('tab', 'notifications');
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'notifications';
        }

        $data = $this->buildDashboardFeedData($request);
        $data['activeTab'] = $activeTab;

        return view('admin.dashboard.feed', $data);
    }

    /**
     * Notices tab: role-scoped, filtered and paginated in SQL.
     *
     * Returns [paginator, filterOptions, appliedFilters].
     */
    protected function buildNoticeFeed(?Request $request): array
    {
        // Scope picks which side of expiry_date the feed reads. Archive is the
        // whole back catalogue this user was entitled to see — the role predicates
        // in notice_feed_query_by_role() are the same either way, so "archive"
        // never widens what someone can read, it only reaches further back.
        $scope = $request?->query('notice_scope') === 'archive' ? 'archive' : 'live';

        // Year applies to display_date. It only earns its place once the archive
        // exists: on the live feed the set spans a year or two at most.
        $rawYear = $request?->query('notice_year');
        $year = trim(is_scalar($rawYear) ? (string) $rawYear : ''); // ?notice_year[]= is not a year (PR #334 F-025)
        if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
            $year = '';
        }

        $filters = [
            'scope'    => $scope,
            'year'     => $year,
            // Scalar only: ?notice_type[]= reached the string cast and returned 500
            // (PR #334 F-037).
            'type'     => is_scalar($v = $request?->query('notice_type')) ? trim((string) $v) : '',
            'dept'     => is_scalar($v = $request?->query('notice_dept')) ? trim((string) $v) : '',
            'audience' => is_scalar($v = $request?->query('notice_audience')) ? trim((string) $v) : '',
            'q'        => is_scalar($v = $request?->query('q')) ? trim((string) $v) : '',
        ];

        $base = notice_feed_query_by_role($scope);

        if (! $base) {
            $empty = new LengthAwarePaginator([], 0, self::NOTICE_FEED_PER_PAGE, 1, [
                'path' => Paginator::resolveCurrentPath(),
            ]);

            return [
                $empty,
                ['types' => collect(), 'depts' => collect(), 'audiences' => collect(), 'years' => collect(), 'archiveCount' => 0],
                $filters,
            ];
        }

        // Dropdown options come from the UNFILTERED role-scoped set, so choosing a
        // Type never empties the Department list (and a filtered-away option can
        // still be un-chosen).
        //
        // One DISTINCT per column, never one DISTINCT across several together: a
        // combined DISTINCT that includes a near-unique column (display_date) has a
        // row count that tracks the notice count, so it degenerates into reading the
        // whole live set. Per column the result is bounded by that column's real
        // cardinality — a handful of types, audiences and departments.
        //
        // select(), not selectRaw(): selectRaw APPENDS to the base query's column
        // list, which would mix the 13 plain columns with the projection here.
        $distinctOf = fn (string $column) => (clone $base)
            ->reorder()
            ->select($column)
            ->distinct()
            ->pluck($column)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Years come from the same unfiltered, scope-applied set, so the dropdown
        // never offers a year that returns nothing. YEAR() is projected rather than
        // derived in PHP so the DISTINCT still collapses in SQL.
        $years = (clone $base)
            ->reorder()
            ->select(DB::raw('YEAR(notices_notification.display_date) as notice_year'))
            ->distinct()
            ->pluck('notice_year')
            ->filter()
            ->sortDesc()
            ->values();

        // Drives the Archive button's count. Cheap — one COUNT over the same
        // role-scoped predicates, and it tells the user whether the archive is
        // worth opening before they switch to it.
        $archiveCount = notice_feed_query_by_role('archive')->reorder()->count();

        $filterOptions = [
            'types'        => $distinctOf('notices_notification.notice_type'),
            'depts'        => $distinctOf('notice_author_dept.department_name'),
            'audiences'    => $distinctOf('notices_notification.target_audience'),
            'years'        => $years,
            'archiveCount' => $archiveCount,
        ];

        if ($filters['year'] !== '') {
            $base->whereYear('notices_notification.display_date', $filters['year']);
        }

        if ($filters['type'] !== '') {
            $base->where('notices_notification.notice_type', $filters['type']);
        }

        if ($filters['audience'] !== '') {
            $base->where('notices_notification.target_audience', $filters['audience']);
        }

        if ($filters['dept'] !== '') {
            $base->where('notice_author_dept.department_name', $filters['dept']);
        }

        if ($filters['q'] !== '') {
            // Escape LIKE metacharacters: a typed "%" should match a literal percent
            // sign, not every notice.
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $base->where(function ($w) use ($like) {
                $w->where('notices_notification.notice_title', 'like', $like)
                    ->orWhere('notices_notification.notice_type', 'like', $like)
                    ->orWhere('notice_author_dept.department_name', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', notice_author.first_name, notice_author.last_name) like ?", [$like]);
            });
        }

        $notices = $base->paginate(self::NOTICE_FEED_PER_PAGE)->withQueryString();

        return [$notices, $filterOptions, $filters];
    }

    /**
     * Count for the dashboard's "Total Students" / "Student Details" cards.
     *
     * Must match what the card OPENS — /dashboard/students, whose payload
     * (resolveDashboardStudentListPayload()) lists students of courses that are
     * active AND not yet ended. Counting enrolment rows alone over-reports for
     * Super Admin / PA / Admin, who have no course restriction and so pick up
     * every inactive and finished course too.
     *
     * @param  array<int, int|string>  $roleCourseIds  get_Role_by_course(): [] = no
     *                                 restriction (Super Admin / Admin / PA),
     *                                 [-1] = no access → count 0.
     */
    private function dashboardTotalStudentsCount(array $roleCourseIds): int
    {
        return (int) StudentMasterCourseMap::query()
            ->where('student_master_course__map.active_inactive', 1)
            ->whereIn('student_master_course__map.course_master_pk', function ($q) use ($roleCourseIds) {
                $q->select('pk')
                    ->from('course_master')
                    ->where('active_inactive', 1)
                    ->where('end_date', '>=', now()->toDateString());
                if (! empty($roleCourseIds)) {
                    $q->whereIn('pk', $roleCourseIds);
                }
            })
            ->distinct()
            ->count('student_master_course__map.student_master_pk');
    }

    /**
     * Shared data for dashboard feed page.
     *
     * $request is optional so the dashboard widget can call this without one;
     * the notices tab reads its filters from it when present.
     */
    protected function buildDashboardFeedData(?Request $request = null): array
    {
        $user = Auth::user();
        $isAdminSummary = hasRole('Admin');
        $daysOld = $isAdminSummary ? 10 : null;
        $currentUserPk = ($user && $user->user_id) ? $user->user_id : 0;

        $emp_dob_data = EmployeeMaster::where('status', 1)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
            ->where('employee_master.pk', '!=', $currentUserPk)
            ->leftJoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->select(
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.email',
                'employee_master.mobile',
                'employee_master.office_extension_no',
                'employee_master.profile_picture',
                'employee_master.last_name',
                'designation_master.designation_name',
                'employee_master.dob'
            )
            ->get();

        $birthdayWishCounts = [];
        if ($emp_dob_data->isNotEmpty()) {
            $birthdayPks = $emp_dob_data->pluck('pk')->toArray();
            $birthdayWishCounts = Notification::whereIn('receiver_user_id', $birthdayPks)
                ->where('type', 'birthday')
                ->whereDate('created_at', today())
                ->selectRaw('receiver_user_id, COUNT(*) as wish_count')
                ->groupBy('receiver_user_id')
                ->pluck('wish_count', 'receiver_user_id')
                ->toArray();
        }

        $upcomingBirthdays = collect();
        for ($i = 1; $i <= 7; $i++) {
            $futureDate = now()->addDays($i);
            $upcoming = EmployeeMaster::where('status', 1)
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$futureDate->format('m-d')])
                ->leftJoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
                ->select(
                    'employee_master.pk',
                    'employee_master.first_name',
                    'employee_master.last_name',
                    'employee_master.email',
                    'employee_master.mobile',
                    'employee_master.office_extension_no',
                    'employee_master.profile_picture',
                    'employee_master.dob',
                    'designation_master.designation_name'
                )
                ->get()
                ->each(function ($emp) use ($futureDate) {
                    $emp->birthday_date = $futureDate->format('d M');
                    $emp->days_away = $futureDate->diffInDays(now());
                });
            $upcomingBirthdays = $upcomingBirthdays->merge($upcoming);
        }

        // Notices are filtered and paginated in SQL (see buildNoticeFeed) rather
        // than fetched whole and filtered in the browser.
        [$notices, $noticeFilterOptions, $noticeFilters] = $this->buildNoticeFeed($request);

        $feedExpandedNotifications = collect();
        $feedExpandedWishes = collect();
        $notificationBadgeCount = 0;

        if ($user && $user->user_id) {
            $feedNotificationsQuery = Notification::with('sender')
                ->where('receiver_user_id', $user->user_id);
            if ($daysOld !== null) {
                $feedNotificationsQuery->where('created_at', '>=', now()->subDays($daysOld));
            }
            $feedAll = $feedNotificationsQuery->orderByDesc('created_at')->limit(100)->get();
            $feedExpandedWishes = $feedAll->filter(function ($item) {
                return strtolower((string) ($item->type ?? '')) === 'birthday';
            })->values();
            $feedExpandedNotifications = $feedAll->filter(function ($item) {
                return strtolower((string) ($item->type ?? '')) !== 'birthday';
            })->values();

            $notificationBadgeCount = $isAdminSummary
                ? notification()->getUnreadCount($user->user_id, $daysOld)
                : $feedAll->where('is_read', 0)->count();
        }

        return compact(
            'user',
            'isAdminSummary',
            'daysOld',
            'emp_dob_data',
            'upcomingBirthdays',
            'birthdayWishCounts',
            'notices',
            'noticeFilterOptions',
            'noticeFilters',
            'feedExpandedNotifications',
            'feedExpandedWishes',
            'notificationBadgeCount'
        );
    }

    /**
     * Split "today pending employee id requests" into:
     * - perm: pending Permanent ID cards
     * - cont: pending Contractual ID cards
     *
     * Logic matches existing getTodayPendingIdCardRequestsCount(), but returns both parts separately.
     *
     * @param  bool  $todayOnly  When true, only applications created today; when false, all actionable pending at this approval level (any request date).
     */
    private function getTodayPendingIdCardRequestsSplit(bool $todayOnly = true): array
    {
        $user = Auth::user();
        if (! $user) {
            return ['perm' => 0, 'cont' => 0];
        }

        if (! (hasRole('Security Card') || hasRole('Admin Security'))) {
            return ['perm' => 0, 'cont' => 0];
        }

        $start = Carbon::today()->startOfDay()->toDateTimeString();
        $end = Carbon::today()->endOfDay()->toDateTimeString();

        $isApproval2 = hasRole('Security Card') && ! hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && ! hasRole('Security Card');

        // If user has both roles, fall back to Approval II "actionable" definition.
        if (! $isApproval2 && ! $isApproval3) {
            $isApproval2 = true;
        }

        if ($isApproval2) {
            // Match Approval II list: actionable “new request” contractual rows exclude L2-done
            // (security_con_oth_id_apply_approval status=1, recommend_status=1). Rows with status=0 for
            // Level 3 would incorrectly match legacy “pending ids with status 0”.
            $contHasA2Recommended = DB::table('security_con_oth_id_apply_approval')
                ->where('status', 1)
                ->where('recommend_status', 1)
                ->pluck('security_parm_id_apply_pk');

            $permQuery = DB::table('security_parm_id_apply as spa')
                ->where('spa.id_status', SecurityParmIdApply::ID_STATUS_PENDING)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('security_parm_id_apply_approval as a')
                        ->whereColumn('a.security_parm_id_apply_pk', 'spa.emp_id_apply')
                        ->where('a.status', 2);
                });
            if (Schema::hasColumn('security_parm_id_apply', 'id_card_generate_date')) {
                $permQuery->whereNull('spa.id_card_generate_date');
            }
            if ($todayOnly) {
                $permQuery->whereBetween('spa.created_date', [$start, $end]);
            }
            $permCount = (int) $permQuery->count();

            $contQuery = DB::table('security_con_oth_id_apply as sco')
                ->where('sco.id_status', 1)
                ->where('sco.depart_approval_status', 2);
            if (Schema::hasColumn('security_con_oth_id_apply', 'id_card_generate_date')) {
                $contQuery->whereNull('sco.id_card_generate_date');
            }
            if ($contHasA2Recommended->isNotEmpty()) {
                $contQuery->whereNotIn('sco.emp_id_apply', $contHasA2Recommended);
            }
            if ($todayOnly) {
                $contQuery->whereBetween('sco.created_date', [$start, $end]);
            }
            $contCount = (int) $contQuery->count();

            return ['perm' => $permCount, 'cont' => $contCount];
        }

        // Approval III final pending
        $permFinalQuery = DB::table('security_parm_id_apply as spa')
            ->where('spa.id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_parm_id_apply_approval as a')
                    ->whereColumn('a.security_parm_id_apply_pk', 'spa.emp_id_apply')
                    ->where('a.status', 2);
            });
        if ($todayOnly) {
            $permFinalQuery->whereBetween('spa.created_date', [$start, $end]);
        }
        $permFinalPending = (int) $permFinalQuery->count();

        $contRecommendedIds = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->where('recommend_status', 1)
            ->pluck('security_parm_id_apply_pk');
        $contFinalDoneIds = DB::table('security_con_oth_id_apply_approval')
            ->whereIn('status', [2, 3])
            ->pluck('security_parm_id_apply_pk');

        $contFinalPending = 0;
        if ($contRecommendedIds->isNotEmpty()) {
            $contQuery = DB::table('security_con_oth_id_apply as sco')
                ->where('sco.id_status', 1)
                ->where('sco.depart_approval_status', 2)
                ->whereIn('sco.emp_id_apply', $contRecommendedIds);

            if ($contFinalDoneIds->isNotEmpty()) {
                $contQuery->whereNotIn('sco.emp_id_apply', $contFinalDoneIds);
            }
            if ($todayOnly) {
                $contQuery->whereBetween('sco.created_date', [$start, $end]);
            }

            $contFinalPending = (int) $contQuery->count();
        }

        return ['perm' => $permFinalPending, 'cont' => $contFinalPending];
    }

    /**
     * @param  bool  $todayOnly  When false, counts all actionable pending family ID requests (any date).
     */
    private function getTodayPendingFamilyApprovalsCount(bool $todayOnly = true): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        $isLevel1 = hasRole('Security Card') && ! hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && ! hasRole('Security Card');
        if (! $isLevel1 && ! $isLevel2) {
            return 0;
        }

        $q = SecurityFamilyIdApply::with('approvals');
        if ($todayOnly) {
            $q->whereDate('created_date', Carbon::today());
        }
        $rows = $q->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $groupKey = function ($r) {
            $date = $r->created_date ? Carbon::parse($r->created_date)->format('Y-m-d H:i:s') : '';

            return $r->emp_id_apply.'|'.($r->created_by ?? '').'|'.$date;
        };

        $groups = $rows->groupBy($groupKey);

        $count = 0;
        foreach ($groups as $rowsInGroup) {
            $first = $rowsInGroup->sortBy('fml_id_apply')->first();
            $statusInt = (int) ($first->id_status ?? 1);
            $hasLevel1 = $first->approvals && $first->approvals->where('status', 1)->isNotEmpty();
            $hasLevel2 = $first->approvals && $first->approvals->where('status', 2)->isNotEmpty();

            $canApprove = false;
            if ($statusInt === 1) {
                if ($isLevel1 && ! $hasLevel1) {
                    $canApprove = true;
                } elseif ($isLevel2 && $hasLevel1 && ! $hasLevel2) {
                    $canApprove = true;
                }
            }

            if ($canApprove) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  bool  $todayOnly  When false, counts all actionable pending vehicle pass requests (any date).
     */
    private function getTodayPendingVehicleApprovalsCount(bool $todayOnly = true): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        $isLevel1 = hasRole('Security Card') && ! hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && ! hasRole('Security Card');
        if (! $isLevel1 && ! $isLevel2) {
            return 0;
        }

        $today = Carbon::today();

        $twQ = VehiclePassTWApply::with('approvals');
        $fwQ = VehiclePassFWApply::with('approvals');
        if ($todayOnly) {
            $twQ->whereDate('created_date', $today);
            $fwQ->whereDate('created_date', $today);
        }
        $twRows = $twQ->get();
        $fwRows = $fwQ->get();

        $rows = $twRows->map(function ($r) {
            $r->kind = 'tw';

            return $r;
        })->concat(
            $fwRows->map(function ($r) {
                $r->kind = 'fw';

                return $r;
            })
        );

        if ($rows->isEmpty()) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $r) {
            $statusInt = (int) ($r->vech_card_status ?? 1);
            $approvals = $r->approvals ?? collect();
            $hasLevel1 = $approvals->where(function ($a) {
                return (int) ($a->veh_recommend_status ?? 0) === 1 || (int) ($a->status ?? 0) === 1;
            })->isNotEmpty();
            $hasLevel2 = $approvals->where(function ($a) {
                return (int) ($a->status ?? 0) === 2;
            })->isNotEmpty();

            $canApprove = false;
            if ($statusInt === 1) {
                if ($isLevel1 && ! $hasLevel1) {
                    $canApprove = true;
                } elseif ($isLevel2 && $hasLevel1 && ! $hasLevel2) {
                    $canApprove = true;
                }
            }

            if ($canApprove) {
                $count++;
            }
        }

        return $count;
    }

    private function getTodayPendingIdCardRequestsCount(): int
    {
        $split = $this->getTodayPendingIdCardRequestsSplit();

        return (int) ($split['perm'] ?? 0) + (int) ($split['cont'] ?? 0);
    }

    /**
     * Today's pending Security Approval-I for the logged-in department authority,
     * split into contractual new ID card vs contractual duplicate ID card (matches Approval I screen).
     *
     * @return array{idcard: int, duplicate: int}
     */
    private function getTodayPendingSecurityApproval1Split(): array
    {
        $user = Auth::user();
        if (! $user) {
            return ['idcard' => 0, 'duplicate' => 0];
        }

        $employeePk = $user->user_id ?? $user->pk ?? null;
        if (! $employeePk) {
            return ['idcard' => 0, 'duplicate' => 0];
        }

        $start = Carbon::today()->startOfDay()->toDateTimeString();
        $end = Carbon::today()->endOfDay()->toDateTimeString();

        // Contractual regular ID card: pending at Approval-I for this authority
        $contQuery = DB::table('security_con_oth_id_apply')
            ->whereBetween('created_date', [$start, $end])
            ->where('id_status', 1)
            ->where('depart_approval_status', 1)
            ->where('department_approval_emp_pk', $employeePk);

        $contA1Done = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_parm_id_apply_pk');
        if ($contA1Done->isNotEmpty()) {
            $contQuery->whereNotIn('emp_id_apply', $contA1Done);
        }

        $idcardCount = (int) $contQuery->count();

        // Contractual duplicate ID card: pending at Approval-I for this authority
        $dupA1Done = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_con_id_apply_pk');

        $dupQuery = DB::table('security_dup_other_id_apply')
            ->whereBetween('created_date', [$start, $end])
            ->where('id_status', 1)
            ->where('card_type', 'Contractual')
            ->where('depart_approval_status', 1)
            ->where('department_approval_emp_pk', $employeePk);

        if ($dupA1Done->isNotEmpty()) {
            $dupQuery->whereNotIn('emp_id_apply', $dupA1Done);
        }

        $duplicateCount = (int) $dupQuery->count();

        return ['idcard' => $idcardCount, 'duplicate' => $duplicateCount];
    }

    /**
     * @param  bool  $todayOnly  When false, counts all actionable pending duplicate permanent requests (any date).
     */
    private function getTodayDuplicatePermanentIdCardRequestsCount(bool $todayOnly = true): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        if (! (hasRole('Security Card') || hasRole('Admin Security'))) {
            return 0;
        }

        $start = Carbon::today()->startOfDay()->toDateTimeString();
        $end = Carbon::today()->endOfDay()->toDateTimeString();
        $isApproval2 = hasRole('Security Card') && ! hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && ! hasRole('Security Card');

        // Permanent Duplicate (security_dup_perm_id_apply)
        $base = DB::table('security_dup_perm_id_apply as dup')
            ->where('dup.id_status', 1);
        if (Schema::hasColumn('security_dup_perm_id_apply', 'id_card_generate_date')) {
            $base->whereNull('dup.id_card_generate_date');
        }
        if ($todayOnly) {
            $base->whereBetween('dup.created_date', [$start, $end]);
        }

        if ($isApproval2) {
            // Actionable at Approval II = not yet recommended
            $base->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_perm_id_apply_approval as a')
                    ->whereColumn('a.security_parm_id_apply_pk', 'dup.emp_id_apply')
                    ->where('a.status', 1)
                    ->where('a.recommend_status', 1);
            });
        } elseif ($isApproval3) {
            // Pending final at Approval III = recommended, not finally approved/rejected
            $base->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_perm_id_apply_approval as a')
                    ->whereColumn('a.security_parm_id_apply_pk', 'dup.emp_id_apply')
                    ->where('a.status', 1)
                    ->where('a.recommend_status', 1);
            })->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_perm_id_apply_approval as a2')
                    ->whereColumn('a2.security_parm_id_apply_pk', 'dup.emp_id_apply')
                    ->whereIn('a2.status', [2, 3]);
            });
        }

        return (int) $base->count();
    }

    /**
     * @param  bool  $todayOnly  When false, counts all actionable pending duplicate contractual requests (any date).
     */
    private function getTodayDuplicateContractualIdCardRequestsCount(bool $todayOnly = true): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        if (! (hasRole('Security Card') || hasRole('Admin Security'))) {
            return 0;
        }

        $start = Carbon::today()->startOfDay()->toDateTimeString();
        $end = Carbon::today()->endOfDay()->toDateTimeString();
        $isApproval2 = hasRole('Security Card') && ! hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && ! hasRole('Security Card');

        // Contractual Duplicate (security_dup_other_id_apply with depart_approval_status = 2)
        $base = DB::table('security_dup_other_id_apply as duo')
            ->where('duo.id_status', 1)
            ->where('depart_approval_status', 2);
        if (Schema::hasColumn('security_dup_other_id_apply', 'id_card_generate_date')) {
            $base->whereNull('duo.id_card_generate_date');
        }
        if ($todayOnly) {
            $base->whereBetween('duo.created_date', [$start, $end]);
        }

        if ($isApproval2) {
            // Actionable at Approval II = not yet recommended
            $base->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_other_id_apply_approval as a')
                    ->whereColumn('a.security_con_id_apply_pk', 'duo.emp_id_apply')
                    ->where('a.status', 1)
                    ->where('a.recommend_status', 1);
            });
        } elseif ($isApproval3) {
            // Pending final at Approval III = recommended, not finally approved/rejected
            $base->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_other_id_apply_approval as a')
                    ->whereColumn('a.security_con_id_apply_pk', 'duo.emp_id_apply')
                    ->where('a.status', 1)
                    ->where('a.recommend_status', 1);
            })->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('security_dup_other_id_apply_approval as a2')
                    ->whereColumn('a2.security_con_id_apply_pk', 'duo.emp_id_apply')
                    ->whereIn('a2.status', [2, 3]);
            });
        }

        return (int) $base->count();
    }

    private function getOtPendingFeedbackCount(int $studentPk): int
    {
        try {
            // Match EXACT logic from studentFeedback_url() in CalendarController
            // Single query with leftJoin - NEW backend format only
            
            $pendingQuery = DB::table('timetable as t')
                ->select(['t.pk as timetable_pk', 'f.pk as faculty_pk'])
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->whereRaw("
                    (
                        JSON_VALID(t.faculty_master)
                        AND JSON_CONTAINS(
                            t.faculty_master,
                            JSON_QUOTE(CAST(f.pk AS CHAR))
                        )
                    )
                    OR
                    (
                        NOT JSON_VALID(t.faculty_master)
                        AND CAST(t.faculty_master AS CHAR) = CAST(f.pk AS CHAR)
                    )
                ");
                })
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->join('student_master_course__map as smcm', function ($join) use ($studentPk) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.student_master_pk', '=', $studentPk)
                        ->where('smcm.active_inactive', '=', 1);
                })
                ->where('t.feedback_checkbox', 1)
                ->join('course_student_attendance as csa', function ($join) use ($studentPk) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->where('csa.Student_master_pk', '=', $studentPk)
                        ->where('csa.status', '1');
                })
                ->whereNotExists(function ($sub) use ($studentPk) {
                    $sub->select(DB::raw(1))
                        ->from('topic_feedback as tf')
                        ->whereColumn('tf.timetable_pk', 't.pk')
                        ->where('tf.student_master_pk', $studentPk)
                        ->where('tf.faculty_pk', DB::raw('f.pk'))
                        ->where('tf.is_submitted', 1);
                })
                ->whereRaw("
                    JSON_VALID(t.faculty_details) = 1
                    AND JSON_CONTAINS(
                        t.faculty_details,
                        JSON_OBJECT('faculty_pk', f.pk, 'role', 'Teaching')
                    ) = 1
                ")
                ->whereRaw("
                    TIMESTAMP(
                        t.END_DATE,
                        CASE
                            WHEN t.class_session LIKE '% - %' THEN
                                STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' - ', -1)), '%h:%i %p')
                            WHEN t.class_session LIKE '% to %' THEN
                                STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' to ', -1)), '%H:%i')
                            ELSE NULL
                        END
                    ) <= NOW()
                ");

            if (hasRole('Student-OT')) {
                $pendingQuery
                    ->join('course_group_timetable_mapping as cgtm', 'cgtm.timetable_pk', '=', 't.pk')
                    ->join('student_course_group_map as scgm', 'scgm.group_type_master_course_master_map_pk', '=', 'cgtm.group_pk')
                    ->where('scgm.student_master_pk', $studentPk);
            }

            $pending = $pendingQuery
                ->orderBy('t.START_DATE', 'asc')
                ->get()
                ->unique(fn($item) => $item->timetable_pk . '_' . $item->faculty_pk)
                ->count();

            return (int) $pending;
        } catch (\Throwable $e) {
            \Log::error('Error counting OT pending feedback: ' . $e->getMessage(), [
                'student_pk' => $studentPk,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Display student list for CC/ACC faculty
     *
     * @return View
     */
    public function studentList(Request $request)
    {
        // Default the Time Period filter to TODAY on a fresh load (no date params at
        // all) so the list opens scoped to the current date. A present-but-empty
        // param means the user deliberately cleared the filter, so it's left alone;
        // the Present/Absent cards and manual picker pass explicit dates as usual.
        if (! $request->has('from_date') && ! $request->has('to_date')) {
            $today = now()->toDateString();
            $request->merge(['from_date' => $today, 'to_date' => $today]);
        }

        // Active / Archived course status. "archive" shows students of courses that
        // have already ended so their old attendance stays viewable; "active" (default)
        // shows currently-running courses. The payload scopes students accordingly;
        // the course-option queries below switch their end_date comparison to match.
        // Date-only comparison (see resolveDashboardStudentListPayload()): end_date is
        // a DATE, so a course ending today must stay "active" all day.
        $archive = $request->input('status') === 'archive';
        $courseDateOp = $archive ? '<' : '>=';

        $payload = $this->resolveDashboardStudentListPayload($request);
        $students = $payload['students'];
        $availableCourses = $payload['availableCourses'];
        $facultyPk = $payload['facultyPk'];

        // Mirrors resolveDashboardStudentListPayload(): Super Admin and the training
        // authorities oversee every course, everyone else (faculty / CC / ACC) is
        // scoped to their own courses. The filter dropdowns below follow the same
        // rule so they never offer a course the viewer has no students in.
        $seesAllCourses = hasRole('Super Admin')
            || hasRole('Training Induction Admin')
            || hasRole('Training MCTP Admin')
            || hasRole('Training IST');

        if ($request->ajax() && $request->has('draw')) {
            return $this->dashboardStudentListDataTableResponse($request, $students);
        }

        // Get counsellor type names and courses from group_type_master_course_master_map
        // From group_type_master_course_master_map, get faculty_id, type_name and course_name
        // Then match type_name (pk) with course_group_type_master to get the type_name
        // And match course_name (pk) with course_master to get the course name
        // Only include counsellor types for active courses (active_inactive = 1 and end_date >= now())
        // Filter by logged-in faculty if available
        $counsellorTypesQuery = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_group_type_master as cgroup', 'gmap.type_name', '=', 'cgroup.pk')
            ->join('course_master as cm', 'gmap.course_name', '=', 'cm.pk')
            ->join('faculty_master as fm', 'gmap.facility_id', '=', 'fm.pk')
            ->where('gmap.active_inactive', 1)
            ->where('cgroup.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', $courseDateOp, now()->toDateString())
            ->where('fm.active_inactive', 1);

        // Filter by logged-in faculty if available. A non-admin viewer with no
        // faculty pk has no rows of their own, so they get no options either —
        // without this they'd see every faculty's counsellor types.
        if ($facultyPk) {
            $counsellorTypesQuery->where('gmap.facility_id', $facultyPk);
        } elseif (! $seesAllCourses) {
            $counsellorTypesQuery->whereRaw('1 = 0');
        }

        $counsellorTypes = $counsellorTypesQuery
            ->select(
                'cgroup.pk as type_pk',
                'cgroup.type_name as counsellor_type_name'
            )
            ->distinct()
            ->orderBy('cgroup.type_name')
            ->get();

        // CC/ACC counsellor faculty options for the dependent dropdown shown when the
        // "CC/ACC" role filter is selected. A student's counsellor is the faculty
        // (gmap.facility_id) of the course group they belong to. Derive the list from
        // the students already in scope so it stays consistent with the rows shown —
        // a Super Admin sees every counsellor, a coordinator sees all counsellors
        // across their courses (not just themselves).
        $counsellorFaculties = $students
            ->map(function ($m) {
                $gmap = $m->groupMapping->groupTypeMasterCourseMasterMap ?? null;
                if (! $gmap || empty($gmap->facility_id)) {
                    return null;
                }

                return (object) [
                    'faculty_pk' => $gmap->facility_id,
                    'faculty_name' => $gmap->Faculty->full_name ?? ('Faculty #'.$gmap->facility_id),
                ];
            })
            ->filter()
            ->unique('faculty_pk')
            ->sortBy('faculty_name')
            ->values();

        // Get courses from group_type_master_course_master_map and merge with available courses
        // Only include active courses (active_inactive = 1 and end_date >= now())
        // Filter by logged-in faculty if available
        $groupMapCoursesQuery = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_master as cm', 'gmap.course_name', '=', 'cm.pk')
            ->join('faculty_master as fm', 'gmap.facility_id', '=', 'fm.pk')
            ->where('gmap.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', $courseDateOp, now()->toDateString())
            ->where('fm.active_inactive', 1);

        // Filter by logged-in faculty if available (see the counsellor-type note above).
        if ($facultyPk) {
            $groupMapCoursesQuery->where('gmap.facility_id', $facultyPk);
        } elseif (! $seesAllCourses) {
            $groupMapCoursesQuery->whereRaw('1 = 0');
        }

        $groupMapCourses = $groupMapCoursesQuery
            ->select(
                'cm.pk',
                'cm.course_name'
            )
            ->distinct()
            ->get()
            ->map(function ($course) {
                return [
                    'pk' => $course->pk,
                    'course_name' => $course->course_name,
                ];
            });

        // Merge courses from students and group_type_master_course_master_map
        $availableCourses = $availableCourses->merge($groupMapCourses)
            ->unique('pk')
            ->sortBy('course_name')
            ->values();

        // Course filter dropdown — ROLE SCOPED. Only Super Admin / training
        // authorities may pick any running course; a faculty / CC / ACC must see
        // ONLY their own courses, otherwise the dropdown leaks the names of courses
        // they have no students in (same rule as the OT participants page).
        // A faculty can belong to several courses at once, so the list still has to
        // be selectable rather than implied by the row set: use $availableCourses,
        // which resolveDashboardStudentListPayload() already scoped to this faculty,
        // unioned with the faculty-scoped $groupMapCourses merged in above. Entries
        // arrive as a mix of objects (student sources) and arrays ($groupMapCourses),
        // so normalise to pks and re-read the rows from course_master — that keeps
        // the short name / duration the option needs, and looking them up by pk
        // (no end_date filter) also keeps a course whose end_date has just passed
        // visible while its students are still listed.
        $allowedCoursePks = collect($availableCourses)
            ->map(fn ($c) => is_array($c) ? ($c['pk'] ?? null) : ($c->pk ?? null))
            ->filter()
            ->unique()
            ->values();

        $courseOptions = ($seesAllCourses
                ? CourseMaster::where('active_inactive', '1')
                    ->where('end_date', $courseDateOp, now()->toDateString())
                    ->orderBy('course_name')
                    ->get(['pk', 'course_name', 'couse_short_name', 'start_year', 'end_date'])
                    // toBase(): Eloquent\Collection::merge() keys items by getKey(),
                    // which would collapse the merge below. Drop to a base collection.
                    ->toBase()
                : collect())
            ->merge(
                $allowedCoursePks->isNotEmpty()
                    ? CourseMaster::whereIn('pk', $allowedCoursePks)
                        ->get(['pk', 'course_name', 'couse_short_name', 'start_year', 'end_date'])
                        ->toBase()
                    : collect()
            )
            ->filter(fn ($c) => ! empty($c->pk))
            ->unique('pk')
            ->sortBy('course_name')
            ->values();

        // Get group names from group_type_master_course_master_map with their type_name (counsellor type)
        // Only include groups for active courses (active_inactive = 1 and end_date >= now())
        // Filter by logged-in faculty if available
        $groupNamesQuery = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_master as cm', 'gmap.course_name', '=', 'cm.pk')
            ->join('faculty_master as fm', 'gmap.facility_id', '=', 'fm.pk')
            ->where('gmap.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', $courseDateOp, now()->toDateString())
            ->where('fm.active_inactive', 1)
            ->whereNotNull('gmap.group_name')
            ->where('gmap.group_name', '!=', '');

        // Filter by logged-in faculty if available (see the counsellor-type note above).
        if ($facultyPk) {
            $groupNamesQuery->where('gmap.facility_id', $facultyPk);
        } elseif (! $seesAllCourses) {
            $groupNamesQuery->whereRaw('1 = 0');
        }

        $groupNames = $groupNamesQuery
            ->select(
                'gmap.pk as group_pk',
                'gmap.group_name',
                'gmap.type_name as counsellor_type_pk'
            )
            ->distinct()
            ->orderBy('gmap.group_name')
            ->get();

        // Distinct Cadre / House Name options (from the unfiltered set) for the "+3 Filters" panel.
        $cadreOptions = $students
            ->map(fn ($m) => $m->studentMaster->cadre->cadre_name ?? null)
            ->filter()->unique()->sort()->values();
        $houseOptions = $students
            ->map(fn ($m) => $m->house_name ?? null)
            ->filter()->unique()->sort()->values();

        // Session / Topic options cascade off the selected Time Period:
        // date range → Session (scoped to range) → Topic (scoped to range + Session).
        // See dashboardStudentListFilterOptions(). No date range → both empty.
        $scopedStudentPks = $students->pluck('student_master_pk')->filter()->unique()->values()->all();
        [$sessionOptions, $topicOptions] = $this->dashboardStudentListFilterOptions(
            $scopedStudentPks,
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null,
            (string) $request->input('session', '')
        );

        // OT / Participant options (each distinct student), mapped to the selected
        // Course so the OT list only shows participants of the chosen course.
        $participantOptions = $this->dashboardStudentListParticipantOptions($students, $request);

        // Kept for the "OT/ Participants Details" card, which counts the roster the
        // linked page shows — filters applied, but WITHOUT the session-date drop.
        $rosterStudents = $students;

        // Tab sets for the initial render. Built by the SAME method the DataTable
        // response uses (dashboardStudentListTabSets), so the first paint already
        // matches what the first AJAX draw reports — previously this path collapsed
        // the buckets itself and skipped the PT / Stationed-leave absentees, so the
        // Absent count jumped as soon as the table refreshed.
        [$students, $presentStudents, $absentStudents] = $this->dashboardStudentListTabSets($request, $students);

        $tabCounts = [
            'all' => $students->count(),
            'present' => $presentStudents->count(),
            'absent' => $absentStudents->count(),
        ];

        // Summary cards, derived from those same tab sets — see
        // dashboardStudentListCardCounts().
        [$cardCounts, $snapshotDate] = $this->dashboardStudentListCardCounts(
            $request,
            $rosterStudents,
            $presentStudents,
            $absentStudents
        );

        $attendance = $request->input('attendance', 'all');
        $students = $attendance === 'present'
            ? $presentStudents
            : ($attendance === 'absent' ? $absentStudents : $students->values());

        // Title bar: the selected course's name, else a generic heading.
        $listTitle = 'Student List';
        if ($request->filled('course_id')) {
            $selected = $availableCourses->firstWhere('pk', $request->input('course_id'));
            if ($selected) {
                $listTitle = (is_array($selected) ? ($selected['course_name'] ?? '') : ($selected->course_name ?? '')) ?: $listTitle;
            }
        }

        $dutyTypes = DB::table('mdo_duty_type_master')
            ->where('active_inactive', 1)
            ->orderBy('mdo_duty_type_name')
            ->get(['pk', 'mdo_duty_type_name']);

        $filters = [
            'course_id' => (string) $request->input('course_id', ''),
            'role_filter' => (string) $request->input('role_filter', ''),
            'faculty_filter' => (string) $request->input('faculty_filter', ''),
            'group_pk' => (string) $request->input('group_pk', ''),
            'duty_type' => (string) $request->input('duty_type', ''),
            'from_date' => (string) $request->input('from_date', ''),
            'to_date' => (string) $request->input('to_date', ''),
            'attendance' => (string) $request->input('attendance', 'all'),
            'cadre' => (string) $request->input('cadre', ''),
            'house' => (string) $request->input('house', ''),
            'counsellor_faculty' => (string) $request->input('counsellor_faculty', ''),
            'session' => (string) $request->input('session', ''),
            'topic' => (string) $request->input('topic', ''),
            'participant' => (string) $request->input('participant', ''),
            'status' => $archive ? 'archive' : 'active',
        ];

        return view('admin.dashboard.student_list', compact('students', 'presentStudents', 'absentStudents', 'availableCourses', 'courseOptions', 'counsellorTypes', 'counsellorFaculties', 'groupNames', 'dutyTypes', 'filters', 'cadreOptions', 'houseOptions', 'sessionOptions', 'topicOptions', 'participantOptions', 'tabCounts', 'cardCounts', 'snapshotDate', 'listTitle'));
    }

    /**
     * OT / Participants List — the drill-down opened from the "OT/ Participants
     * Attendance" dashboard card. One row per participant (not per session),
     * split into Present / Absent tabs, with per-student Medical Exemption,
     * PT Exception and Stationed Leave counts.
     */
    /**
     * The participant rows for one request: shared filters, collapsed to one row
     * per student, then narrowed by the Cadre Counsellor selection.
     *
     * Extracted so the filter dropdowns can be rebuilt against the SAME rows the
     * table shows — see otParticipantsFilterOptions(), which calls this once per
     * dropdown with that dropdown's own filter dropped.
     *
     * The Time Period filter only scopes the count columns here (see $rowMeta in
     * the caller), so $applySessionDateFilter = false keeps every student visible
     * regardless of dates. $applySearch = false because the search box is applied
     * in otParticipantsDataTableResponse() instead, over THIS page's columns
     * (House Group, Duty Type, the count columns) — letting the shared filter
     * search first would drop every row on a House Group query.
     */
    /**
     * ?view=counsellees / ?view=house — opened from the My Counsellees and House
     * Wise Details cards. The list is then the students of that faculty's groups
     * on the Course Group Mapping page and nothing else, so each card's number
     * and its rows agree. Each view drops the filter the other one is about, and
     * both drop the Active/Archived tabs: the scope spans both.
     *
     * @return array{0: ?int, 1: bool, 2: bool}  [facultyPk, isCounselleeView, isHouseView]
     */
    private function otParticipantsScopedView(Request $request): array
    {
        $requestedView = $request->input('view');
        $scopeFacultyPk = in_array($requestedView, ['counsellees', 'house'], true)
            ? get_auth_faculty_master_pk()
            : null;

        return [
            $scopeFacultyPk,
            $scopeFacultyPk !== null && $requestedView === 'counsellees',
            $scopeFacultyPk !== null && $requestedView === 'house',
        ];
    }

    /**
     * The students of the faculty's own groups for a scoped view — the same scope
     * the dashboard cards count on, so the list holds exactly their number.
     */
    private function otParticipantsScopedStudents(int $facultyPk, bool $isCounselleeView)
    {
        return $isCounselleeView
            ? $this->facultyGroupRows($facultyPk, '%counsel%', 'counsellor_group_name', true)
            : $this->facultyGroupRows($facultyPk, '%house%', 'house_group_name', true);
    }

    /**
     * The Cadre a participant row shows — one label for the grid, its sort, the
     * search and the export, so all four agree with the Cadre filter (which reads
     * cadre_name). On the counsellee view the cadre is the counsellor group name.
     */
    private function otParticipantCadreLabel($p): string
    {
        return (string) (($p->counsellor_group_name ?? null)
            ?: (($p->cadre_name ?? null) ?: ($p->studentMaster->cadre->cadre_name ?? '')));
    }

    /**
     * The House a participant row shows: the House view's own group name, else the
     * Course Group Mapping house group (what the House Group filter and the export
     * use). Only the counsellee view, which has no house group, falls back to the
     * hostel room — see otParticipantsHouseRoomFallback().
     */
    private function otParticipantHouseLabel($p, bool $roomFallback): string
    {
        $label = ($p->house_group_name ?? null) ?: ($p->house_group ?? null);
        if (! $label && $roomFallback) {
            $label = $p->house_name ?? null;
        }

        return (string) ($label ?? '');
    }

    private function otParticipantsHouseRoomFallback(Request $request): bool
    {
        return $this->otParticipantsScopedView($request)[1];
    }

    private function otParticipantsRowsFor(Request $request, $students)
    {
        [$scopeFacultyPk, $isCounselleeView, $isHouseView] = $this->otParticipantsScopedView($request);

        // The card views list the faculty's own groups. Every other view lists the
        // $students the caller built — the viewer's coordinated roster — and must
        // not rebuild a wider payload here.
        if ($isCounselleeView || $isHouseView) {
            $students = $this->otParticipantsScopedStudents($scopeFacultyPk, $isCounselleeView);
        }

        $sessionRows = $this->applyDashboardStudentListFilters($students, $request, false, false);

        $byStudent = [];
        foreach ($sessionRows as $m) {
            $spk = $m->student_master_pk;
            if (! $spk) {
                continue;
            }
            if (! isset($byStudent[$spk])) {
                $byStudent[$spk] = (object) [
                    'student_master_pk' => $spk,
                    'studentMaster' => $m->studentMaster,
                    // The courses this student is in VIEW for. The row collapses
                    // many (student, course) rows into one, but the group mappings
                    // behind Cadre Counsellor / House Group Faculty belong to a
                    // course — keeping the set lets those stay course-scoped.
                    'course_pks' => [],
                    'house_name' => $m->house_name ?? null,
                    // Kept through the collapse so the Cadre and House columns,
                    // their sorts and the search read the group on those views.
                    'counsellor_group_name' => $m->counsellor_group_name ?? null,
                    'house_group_name' => $m->house_group_name ?? null,
                    'cadre_name' => $m->cadre_name ?? null,
                    'counsellor_name' => $m->counsellor_name ?? null,
                    'house_group' => $m->house_group ?? null,
                    'house_groups' => $m->house_groups ?? [],
                    'house_faculty_name' => $m->house_faculty_name ?? null,
                ];
            }
            if (! empty($m->course_master_pk)) {
                $byStudent[$spk]->course_pks[(string) $m->course_master_pk] = true;
            }
            if (empty($byStudent[$spk]->house_name) && ! empty($m->house_name)) {
                $byStudent[$spk]->house_name = $m->house_name;
            }
            if (empty($byStudent[$spk]->cadre_name) && ! empty($m->cadre_name)) {
                $byStudent[$spk]->cadre_name = $m->cadre_name;
            }
            if (empty($byStudent[$spk]->counsellor_name) && ! empty($m->counsellor_name)) {
                $byStudent[$spk]->counsellor_name = $m->counsellor_name;
            }
            if (empty($byStudent[$spk]->house_group) && ! empty($m->house_group)) {
                $byStudent[$spk]->house_group = $m->house_group;
                $byStudent[$spk]->house_groups = $m->house_groups ?? [];
            }
            if (empty($byStudent[$spk]->house_faculty_name) && ! empty($m->house_faculty_name)) {
                $byStudent[$spk]->house_faculty_name = $m->house_faculty_name;
            }
        }
        foreach ($byStudent as $row) {
            $row->course_pks = array_keys($row->course_pks);
        }
        $participants = collect(array_values($byStudent));

        // Cadre Counsellor filter (the dependent dropdown beside Cadre). Resolved
        // from the group-map tables rather than the row objects — this page's
        // payload is loaded without group mappings.
        // ?counsellor_faculty[]= is not a faculty (PR #334 F-037: was a 500).
        $counsellorFaculty = is_scalar($v = $request->input('counsellor_faculty', '')) ? (string) $v : '';
        if ($counsellorFaculty !== '') {
            $counselled = $this->studentPksForCounsellorFaculty(
                $counsellorFaculty,
                $participants->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all(),
                $this->participantCoursePks($participants)
            );
            $participants = $participants
                ->filter(fn ($p) => isset($counselled[(int) $p->student_master_pk]))
                ->values();
        }

        // House Group Faculty — the dependent dropdown beside House Group, resolved
        // the same way off the house-group mapping.
        $houseFaculty = (string) $request->input('house_faculty', '');
        if ($houseFaculty !== '') {
            $housed = $this->studentPksForHouseFaculty(
                $houseFaculty,
                $participants->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all(),
                $this->participantCoursePks($participants)
            );
            $participants = $participants
                ->filter(fn ($p) => isset($housed[(int) $p->student_master_pk]))
                ->values();
        }

        return $participants;
    }

    /**
     * The Cadre / Cadre Counsellor / House Group dropdown options for the OT
     * participants page, each scoped to the rows that WOULD be in view with that
     * dropdown's own filter cleared.
     *
     * Without the scoping the dropdowns listed every value in the payload, so
     * picking one could return nothing: with Cadre = AGMUT and a counsellor
     * selected, House Group still offered "Nanda Devi" and "Namcha Barwa" even
     * though no AGMUT participant of that counsellor is in either house.
     *
     * Each list drops only its OWN filter, so the three stay mutually consistent:
     * House Group lists the houses reachable under the current Cadre/Counsellor,
     * Cadre lists the cadres reachable under the current House Group, and so on.
     *
     * @return array{cadre: \Illuminate\Support\Collection, houseGroup: \Illuminate\Support\Collection, counsellorsByCadre: array}
     */
    private function otParticipantsFilterOptions(Request $request, $students): array
    {
        // A copy of this request with some filters cleared. Only the query bag
        // matters — applyDashboardStudentListFilters() reads inputs, nothing else.
        $without = function (array $keys) use ($request) {
            $params = $request->query();
            foreach ($keys as $k) {
                unset($params[$k]);
            }

            return Request::create($request->url(), 'GET', $params);
        };

        // Cadre and the counsellor map share a scope: both drop the cadre and
        // counsellor filters (the counsellor list is keyed by cadre and re-keyed
        // client-side) and keep everything else.
        $forCadre = $this->otParticipantsRowsFor($without(['cadre', 'counsellor_faculty']), $students);
        // House Group and its faculty share a scope for the same reason.
        $forHouseGroup = $this->otParticipantsRowsFor($without(['house_group', 'house_faculty']), $students);

        // Which of the two pairs this viewer gets at all — see the helper. An
        // out-of-scope pair is returned empty, which is what hides it in the blade.
        $typeScope = $this->otParticipantsGroupTypeScope(
            $this->participantCourseScope($this->participantCoursePks($forCadre->concat($forHouseGroup)))
        );

        return [
            'cadre' => $typeScope['cadre']
                ? $forCadre->map(fn ($m) => $m->cadre_name ?? null)->filter()->unique()->sort()->values()
                : collect([]),
            // Every house group in view, not just each student's first — otherwise a
            // group whose members are all also in another group never appears.
            'houseGroup' => $typeScope['house']
                ? $forHouseGroup->flatMap(fn ($m) => $m->house_groups ?? [])->filter()->unique()->sort()->values()
                : collect([]),
            'counsellorsByCadre' => $typeScope['cadre']
                ? $this->otParticipantsCounsellorOptions(
                    $forCadre->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all(),
                    $this->participantCoursePks($forCadre)
                )
                : [],
            'facultyByHouseGroup' => $typeScope['house']
                ? $this->otParticipantsHouseFacultyOptions(
                    $forHouseGroup->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all(),
                    $this->participantCoursePks($forHouseGroup)
                )
                : [],
        ];
    }

    /**
     * Which filter pairs this viewer gets: Cadre / Cadre Counsellor, and
     * House Group / House Group Faculty.
     *
     * The filters follow the COURSE GROUP MAPPING, exactly as Training Setup
     * shows it. A group faculty reaches this page through the groups they own, so
     * a Counsellor Group faculty gets the Cadre pair and a House Group faculty
     * gets the House Group pair. Offering a House Group filter to a cadre
     * counsellor slices their list by somebody else's mapping — Ganesh Shankar
     * Mishra, whose only mapping on a course is the "Maharastra" counsellor
     * group, was being offered a House Group filter because his 43 OTs happen to
     * sit in another faculty's house.
     *
     * A viewer who is NOT scoped by their own groups — Super Admin / training
     * admin, or the coordinator of a course in view — oversees the whole roster,
     * so they get every pair the course's mapping supports (both, on a course
     * mapped with counsellor AND house groups).
     *
     * @param  array<int, string>  $inViewCoursePks  the courses the rows come from
     * @return array{cadre: bool, house: bool}
     */
    private function otParticipantsGroupTypeScope(array $inViewCoursePks): array
    {
        $both = ['cadre' => true, 'house' => true];

        $facultyPk = get_auth_faculty_master_pk();
        if (! $facultyPk
            || hasRole('Super Admin') || hasRole('Admin') || hasRole('PA')
            || hasRole('Training Induction Admin')
            || hasRole('Training MCTP Admin')
            || hasRole('Training IST')) {
            return $both;
        }

        // Coordinator of a course in view: the whole course's mapping is theirs.
        $coordinated = $this->getCoordinatorCourseIds((int) $facultyPk)
            ->map(fn ($v) => (string) $v)->all();
        foreach ($inViewCoursePks as $pk) {
            if (in_array((string) $pk, $coordinated, true)) {
                return $both;
            }
        }

        // Otherwise the rows are here because of THIS faculty's own group
        // mappings, so only those group types get a filter.
        $ownTypes = DB::table('group_type_master_course_master_map')
            ->where('facility_id', $facultyPk)
            ->where('active_inactive', 1)
            ->when(! empty($inViewCoursePks), fn ($q) => $q->whereIn('course_name', $inViewCoursePks))
            ->pluck('type_name')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->all();

        if (empty($ownTypes)) {
            // Nothing to narrow by (no rows, or a scope this rule does not cover) —
            // leave the filters alone rather than stripping them.
            return $both;
        }

        $owns = fn (array $typePks) => ! empty(array_intersect(
            $ownTypes,
            array_map(fn ($v) => (string) $v, $typePks)
        ));

        return [
            'cadre' => $owns($this->counsellorGroupTypePks()),
            'house' => $owns($this->houseGroupTypePks()),
        ];
    }

    public function otParticipantsList(Request $request)
    {
        if (! $this->canUseOtParticipants()) {
            abort(403, 'You are not authorized to view the OT / Participants list.');
        }

        // from_date / to_date come straight off the query string, and on the export
        // paths they reach Carbon::parse(), which throws on a malformed value. Validate
        // on all three paths so the table and its exports agree on what a date is. The
        // rule accepts everything the UI sends (YYYY-MM-DD), an empty string and an
        // absent key, so an unfiltered view or export is unaffected.
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        // Skip the payload's per-student total_* / notice-memo N+1 loop — this page
        // computes its counts separately via otParticipantsRowMeta (batched).
        //
        // $coordinatedOnly: participants are this viewer's own roster — the courses
        // they coordinate (course_coordinator_master) plus the students of the course
        // groups they own (House / Counsellor group faculty) — and nothing else: no
        // merely-taught courses, no memo-only students. The "OT/ Participants
        // Details" card on the student list counts the same set, so the two agree.
        $payload = $this->resolveDashboardStudentListPayload($request, false, true);
        $availableCourses = $payload['availableCourses'];

        $participants = $this->otParticipantsRowsFor($request, $payload['students']);
        $counsellorFaculty = is_scalar($v = $request->input('counsellor_faculty', '')) ? (string) $v : '';

        // Show every student of the selected course — no Present/Absent split.
        $rows = $participants;
        $totalParticipants = $participants->count();

        // Time Period scopes the count columns only (all students stay visible).
        $rowMeta = $this->otParticipantsRowMeta(
            $participants->pluck('student_master_pk')->all(),
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null
        );

        // Cadre / Cadre Counsellor / House Group options, each scoped to the rows
        // reachable with that dropdown's own filter cleared — so an option can
        // never be offered that would return nothing.
        $filterOptions = $this->otParticipantsFilterOptions($request, $payload['students']);

        if ($request->ajax() && $request->has('draw')) {
            return $this->otParticipantsDataTableResponse($request, $rows, $rowMeta, $totalParticipants, $filterOptions);
        }

        // Filter option lists (mirrors the student list page). On the card views
        // they come off the faculty's own group rows, so the Cadre / House dropdowns
        // offer only what is mapped to this faculty.
        [$scopeFacultyPk, $isCounselleeView, $isHouseView] = $this->otParticipantsScopedView($request);
        $students = ($isCounselleeView || $isHouseView)
            ? $this->otParticipantsScopedStudents($scopeFacultyPk, $isCounselleeView)
            : $payload['students'];
        // In the counsellee view the cadres ARE the counsellor group names off the
        // Course Group Mapping page — which is where the faculty verifies this list
        // — not the students' own cadre master, where 70 of them have nothing. The
        // house view has no Cadre filter at all.
        $cadreOptions = $isHouseView
            ? collect()
            : $students
                ->map(fn ($m) => $isCounselleeView
                    ? ($m->counsellor_group_name ?? null)
                    : ($m->studentMaster->cadre->cadre_name ?? null))
                ->filter()->unique()->sort()->values();

        // Likewise the houses in the house view are the faculty's House Groups, not
        // the hostel room each student happens to hold; and the counsellee view has
        // no House filter.
        $houseOptions = $isCounselleeView
            ? collect()
            : $students
                ->map(fn ($m) => $isHouseView
                    ? ($m->house_group_name ?? null)
                    : ($m->house_name ?? null))
                ->filter()->unique()->sort()->values();

        $houseGroupOptions = $filterOptions['houseGroup'];
        $counsellorsByCadre = $filterOptions['counsellorsByCadre'];
        $facultyByHouseGroup = $filterOptions['facultyByHouseGroup'];
        // Session options are independent of the Time Period — see resolveScopedSessionOptions().
        $sessionOptions = $this->resolveScopedSessionOptions(
            $students->pluck('student_master_pk')->filter()->unique()->values()->all()
        );
        $participantOptions = $students
            ->map(function ($m) {
                $s = $m->studentMaster;
                if (! $s) {
                    return null;
                }
                $name = $s->display_name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? ''));
                $code = $s->generated_OT_code ?? '';

                return (object) [
                    'pk' => (string) $s->pk,
                    'label' => trim(($code !== '' ? $code.' — ' : '').$name),
                ];
            })
            ->filter()->unique('pk')->sortBy('label')->values();

        $status = $request->input('status') === 'archive' ? 'archive' : 'active';

        // ?house[]=x is not a filter value: casting an array is "Array to string
        // conversion", a 500 (PR #334 F-051, the F-025 family).
        $in = fn (string $key) => is_scalar($v = $request->input($key, '')) ? (string) $v : '';

        $filters = [
            'from_date' => $in('from_date'),
            'to_date' => $in('to_date'),
            'session' => $in('session'),
            'participant' => $in('participant'),
            'course_id' => $in('course_id'),
            'cadre' => $in('cadre'),
            // House Name: applyDashboardStudentListFilters() has always honoured it,
            // but the page had no control to set it — the dashboard's House Wise
            // Details card opens here, so the filter is now on the toolbar.
            'house' => $in('house'),
            'counsellor_faculty' => $counsellorFaculty,
            'house_group' => $in('house_group'),
            'house_faculty' => $in('house_faculty'),
            'status' => $status,
            // The House Wise Details view opens the list ordered by house.
            'sort' => ($isHouseView || $request->input('sort') === 'house') ? 'house' : '',
            // Carried on every request the grid makes, or the scope would be lost
            // on the first filter change.
            'view' => $isCounselleeView ? 'counsellees' : ($isHouseView ? 'house' : ''),
        ];

        // Course filter scope: Super Admin / Admin / PA can pick ANY course for the
        // current tab (active = not yet ended, archive = ended); a CC/ACC only sees
        // THEIR OWN courses (already scoped to the tab by the payload's $availableCourses).
        $courseDateOp = $status === 'archive' ? '<' : '>=';
        if (hasRole('Super Admin') || hasRole('Admin') || hasRole('PA')) {
            $courseOptions = CourseMaster::where('active_inactive', '1')
                ->where('end_date', $courseDateOp, now()->toDateString())
                ->orderBy('course_name')
                ->get(['pk', 'course_name', 'couse_short_name', 'start_year', 'end_date']);
        } else {
            $courseOptions = collect($availableCourses)
                ->map(fn ($c) => (object) [
                    'pk' => is_array($c) ? ($c['pk'] ?? null) : ($c->pk ?? null),
                    'course_name' => is_array($c) ? ($c['course_name'] ?? '') : ($c->course_name ?? ''),
                    'couse_short_name' => is_array($c) ? ($c['couse_short_name'] ?? null) : ($c->couse_short_name ?? null),
                    'start_year' => is_array($c) ? ($c['start_year'] ?? null) : ($c->start_year ?? null),
                    'end_date' => is_array($c) ? ($c['end_date'] ?? null) : ($c->end_date ?? null),
                ])
                ->filter(fn ($c) => ! empty($c->pk))
                ->unique('pk')
                ->sortBy('course_name')
                ->values();
        }

        return view('admin.dashboard.ot_participants_list', compact(
            'availableCourses', 'courseOptions', 'filters', 'cadreOptions', 'houseOptions',
            'sessionOptions', 'participantOptions', 'isCounselleeView', 'isHouseView',
            'houseGroupOptions', 'sessionOptions', 'participantOptions',
            'counsellorsByCadre', 'facultyByHouseGroup'
        ));
    }

    /**
     * The course-group types that represent a CADRE COUNSELLOR mapping.
     *
     * A student sits in many course groups at once — Counsellor Group, House
     * Group, Language Group, one per seminar — and every one of them has a
     * faculty on gmap.facility_id. Only the counsellor type is that student's
     * cadre counsellor; the rest are house wardens, tutors, seminar leads.
     * Matched by NAME, not a hard-coded pk, so a rename to e.g. "Cadre
     * Counsellor Group" keeps working.
     *
     * @return array<int, int|string>  course_group_type_master pks (empty = don't restrict)
     */
    private function counsellorGroupTypePks(): array
    {
        return DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->where('type_name', 'like', '%counsellor%')
            ->pluck('pk')
            ->all();
    }

    /**
     * The course-group types that represent a HOUSE GROUP mapping.
     *
     * Backs the House Group column and its filter. This is the course's House
     * Group from Training Setup → Course Group Mapping ("Nanda Devi", "Stok
     * Kangri", …) — NOT the hostel room in ot_hostel_room_details, whose values
     * are room codes like "GANG-116". The student list still shows that room as
     * its own House Name column.
     *
     * @return array<int, int|string>  course_group_type_master pks (empty = don't restrict)
     */
    private function houseGroupTypePks(): array
    {
        return DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->where('type_name', 'like', '%house%')
            ->pluck('pk')
            ->all();
    }

    /**
     * The Course Group Mapping row that represents a student for the given group
     * types — its group_name AND its faculty, together — keyed per (student,
     * course) and per student.
     *
     * The shared reader behind the Cadre / Cadre Counsellor and House Group /
     * House Group Faculty pairs. Reading name and faculty from ONE row is the
     * whole point: resolving them separately let the two disagree, so a student
     * mapped to both "Nanda Devi" and a later "A" group rendered as
     * House Group "A" with Nanda Devi's warden beside it.
     *
     * A student SHOULD sit in one group per type per course, but the data does not
     * guarantee it (Phase-II-2024 has four real houses plus extra "A"/"B" rows, and
     * most of its students are in two). When there are several, the winner is
     * picked deterministically rather than left to row order:
     *   1. a group that HAS a faculty beats one that does not — a house with no
     *      warden is an incomplete mapping
     *   2. then the LOWEST gmap.pk — the original mapping beats one added later
     *
     * @param  array<int, int>  $studentPks
     * @param  array<int, int|string>  $typePks  empty = every type (no restriction)
     * @return array{byStudentCourse: array<string, array{name: string, faculty: ?string}>, byStudent: array<int, array{name: string, faculty: ?string}>}
     */
    private function resolveParticipantGroupRows(array $studentPks, array $typePks): array
    {
        $out = ['byStudentCourse' => [], 'byStudent' => []];
        if (empty($studentPks)) {
            return $out;
        }

        $rows = DB::table('student_course_group_map as scg')
            ->join('group_type_master_course_master_map as gmap', 'scg.group_type_master_course_master_map_pk', '=', 'gmap.pk')
            ->leftJoin('faculty_master as fm', function ($join) {
                $join->on('gmap.facility_id', '=', 'fm.pk')->where('fm.active_inactive', '=', 1);
            })
            ->whereIn('scg.student_master_pk', $studentPks)
            ->when(! empty($typePks), fn ($q) => $q->whereIn('gmap.type_name', $typePks))
            ->whereNotNull('gmap.group_name')
            ->where('gmap.group_name', '<>', '')
            ->where('scg.active_inactive', 1)
            ->where('gmap.active_inactive', 1)
            // Faculty-bearing groups first, then the earliest mapping — see above.
            ->orderByRaw('CASE WHEN fm.pk IS NULL THEN 1 ELSE 0 END')
            ->orderBy('gmap.pk')
            ->get([
                'scg.student_master_pk as spk',
                'gmap.pk as gmap_pk',
                'gmap.group_name',
                'gmap.course_name as course_pk',
                'fm.pk as faculty_pk',
                'fm.full_name as faculty_name',
            ]);

        foreach ($rows as $r) {
            $name = trim((string) $r->group_name);
            if ($name === '') {
                continue;
            }

            $faculty = null;
            if (! empty($r->faculty_pk)) {
                // Blank full_name rows still need something readable in the cell.
                $faculty = trim((string) $r->faculty_name) ?: ('Faculty #' . $r->faculty_pk);
            }

            $entry = ['name' => $name, 'faculty' => $faculty];

            // EVERY group is kept, best-first. A student really can belong to more
            // than one group of a type, and the filter dropdown must be able to
            // offer all of them — dropping the extras hid "A" / "B" / "House A"
            // from the House Group filter entirely. Callers that need a single
            // value take the first (see participantGroupValueFor()).
            $out['byStudentCourse'][$r->spk . '_' . $r->course_pk][] = $entry;
            $out['byStudent'][$r->spk][] = $entry;
        }

        return $out;
    }

    /**
     * One field ('name' or 'faculty') of the group row that represents a payload
     * row: this row's own course first, then any other course the student is in.
     *
     * @param  array{byStudentCourse: array<string, array{name: string, faculty: ?string}>, byStudent: array<int, array{name: string, faculty: ?string}>}  $map
     */
    private function participantGroupValueFor($studentMap, array $map, string $field = 'name'): ?string
    {
        $values = $this->participantGroupListFor($studentMap, $map, $field);

        return $values[0] ?? null;
    }

    /**
     * EVERY value of one field for the group rows that represent a payload row,
     * best-first and de-duplicated.
     *
     * Backs the House Group column and its filter: a student in both "Nanda Devi"
     * and "A" must be findable under either, and the column says so rather than
     * silently naming one of them.
     *
     * @param  array{byStudentCourse: array<string, array<int, array{name: string, faculty: ?string}>>, byStudent: array<int, array<int, array{name: string, faculty: ?string}>>}  $map
     * @return array<int, string>
     */
    private function participantGroupListFor($studentMap, array $map, string $field = 'name'): array
    {
        $values = [];
        foreach ($this->participantGroupEntriesFor($studentMap, $map) as $entry) {
            $v = $entry[$field] ?? null;
            if ($v !== null && $v !== '' && ! in_array($v, $values, true)) {
                $values[] = $v;
            }
        }

        return $values;
    }

    /**
     * The group rows that represent a payload row — best-first, one per distinct
     * group name — as ['name' => ..., 'faculty' => ...] pairs.
     *
     * Callers render the two columns FROM THE SAME LIST, position by position, so
     * "House Group" and "House Group Faculty" line up: a student in Kangchendjunga
     * and B reads "Kangchendjunga, B" / "Rajesh Meena, —", never Kangchendjunga's
     * warden presented as B's.
     *
     * @param  array{byStudentCourse: array<string, array<int, array{name: string, faculty: ?string}>>, byStudent: array<int, array<int, array{name: string, faculty: ?string}>>}  $map
     * @return array<int, array{name: string, faculty: ?string}>
     */
    private function participantGroupEntriesFor($studentMap, array $map): array
    {
        $spk = $studentMap->student_master_pk ?? null;
        if (! $spk) {
            return [];
        }

        // A group belongs to a course. When this row HAS a course, only that
        // course's groups count — falling back to another course's group showed a
        // Cadre / Cadre Counsellor / House Group from a list the viewer is not
        // even looking at. byStudent is only for rows with no course context.
        $coursePk = $studentMap->course_master_pk ?? null;
        $entries = $coursePk !== null
            ? ($map['byStudentCourse'][$spk . '_' . $coursePk] ?? [])
            : ($map['byStudent'][$spk] ?? []);

        $seen = [];
        $out = [];
        foreach ($entries as $entry) {
            $name = $entry['name'] ?? '';
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $out[] = $entry;
        }

        return $out;
    }

    /**
     * A closure that snaps a raw counsellor group_name to the cadre spelling the
     * rows use: fn (string $rawGroupName, int $studentPk): string.
     *
     * Shared by the Cadre column (resolveParticipantCadres()) and the Cadre
     * Counsellor dropdown, so a counsellor is always filed under a cadre value
     * the Cadre filter can actually be set to.
     *
     * @param  array<int, int>  $studentPks
     */
    private function cadreNameNormaliser(array $studentPks): callable
    {
        $studentCadre = DB::table('student_master as sm')
            ->join('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
            ->whereIn('sm.pk', $studentPks)
            ->pluck('cad.cadre_name', 'sm.pk');

        $canonicalKey = fn ($v) => preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $v)));
        $canonical = [];
        foreach (DB::table('cadre_master')->pluck('cadre_name') as $name) {
            $canonical[$canonicalKey($name)] = $name;
        }

        return function (string $raw, $spk) use ($canonical, $canonicalKey, $studentCadre): string {
            $cadre = $canonical[$canonicalKey($raw)] ?? null;
            if ($cadre !== null) {
                return $cadre;
            }

            // Free-text group_name with no cadre_master counterpart: prefer the
            // student's own normalised cadre, else show the mapping verbatim.
            $own = trim((string) ($studentCadre[$spk] ?? ''));

            return $own !== '' ? $own : $raw;
        };
    }

    /**
     * A participant's CADRE, resolved from the Course Group Mapping first.
     *
     * The counsellor group a student sits in IS their cadre for that course —
     * group_name carries the cadre ("AGMUT", "Bihar") and facility_id the
     * counsellor, both on the same row. That mapping is course-specific and is
     * what the Training Setup screens actually manage, so it wins over the static
     * student_master.cadre_master_pk.
     *
     * Resolution order, per student:
     *   1. counsellor group_name for THIS row's course
     *   2. counsellor group_name from any of the student's other courses
     *   3. student_master.cadre_master_pk → cadre_master.cadre_name
     *
     * group_name is free text, so a matched value is snapped back to its
     * cadre_master spelling (case- and punctuation-insensitive): "Assam-Meghalaya"
     * becomes "Assam Meghalaya" rather than opening a second dropdown entry. A
     * group_name with no cadre_master counterpart ("Chhattisgarh", "Meghalaya")
     * falls through to the student's own cadre, which is the normalised one. Only
     * a group whose students have no cadre_master row at all is shown verbatim —
     * that surfaces a bad mapping instead of silently blanking those students.
     *
     * @param  array<int, int>  $studentPks
     * @return array{byStudentCourse: array<string, string>, byStudent: array<int, string>}
     */
    private function resolveParticipantCadres(array $studentPks): array
    {
        $out = ['byStudentCourse' => [], 'byStudent' => []];
        if (empty($studentPks)) {
            return $out;
        }

        // student_master cadre — the fallback, and the normalised spelling source.
        $studentCadre = DB::table('student_master as sm')
            ->join('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
            ->whereIn('sm.pk', $studentPks)
            ->pluck('cad.cadre_name', 'sm.pk');

        $groups = $this->resolveParticipantGroupRows($studentPks, $this->counsellorGroupTypePks());

        // Normalise each raw counsellor group_name to its cadre_master spelling.
        $normalise = $this->cadreNameNormaliser($studentPks);

        // A cadre is a single value, so only the best-ranked counsellor group counts.
        foreach ($groups['byStudentCourse'] as $key => $entries) {
            $spk = (int) strtok($key, '_');
            $cadre = $normalise($entries[0]['name'] ?? '', $spk);
            if ($cadre !== '') {
                $out['byStudentCourse'][$key] = $cadre;
            }
        }

        foreach ($groups['byStudent'] as $spk => $entries) {
            $cadre = $normalise($entries[0]['name'] ?? '', $spk);
            if ($cadre !== '') {
                $out['byStudent'][$spk] ??= $cadre;
            }
        }

        // Students with no counsellor group keep their student_master cadre.
        foreach ($studentCadre as $spk => $name) {
            $out['byStudent'][$spk] ??= $name;
        }

        return $out;
    }

    /**
     * The cadre to show for one payload row: the counsellor group this student is
     * in FOR THIS ROW'S COURSE, else their student_master cadre.
     *
     * Deliberately does NOT fall back to a counsellor group from one of the
     * student's other courses. A counsellor group belongs to a course, so reading
     * one course's group while listing another course's roster showed a cadre that
     * has nothing to do with the list being viewed.
     *
     * @param  array{byStudentCourse: array<string, string>, byStudent: array<int, string>}  $cadres
     */
    private function participantCadreFor($studentMap, array $cadres): ?string
    {
        $spk = $studentMap->student_master_pk ?? null;
        if (! $spk) {
            return null;
        }

        $coursePk = $studentMap->course_master_pk ?? null;
        $cadre = $coursePk !== null
            ? ($cadres['byStudentCourse'][$spk . '_' . $coursePk] ?? null)
            : null;

        return $cadre ?? ($studentMap->studentMaster->cadre->cadre_name ?? null);
    }

    /**
     * Cadre → Cadre Counsellor options for the OT participants page.
     *
     * A participant's counsellor is the faculty mapped to the COUNSELLOR course
     * group they belong to (student_course_group_map →
     * group_type_master_course_master_map.facility_id → faculty_master).
     * Grouping that by the group's own name gives the dependent dropdown that
     * opens beside Cadre: pick "Bihar" and you get the faculty who actually
     * counsel Bihar participants, not every faculty on record.
     *
     * The group-type restriction matters: without it the dropdown also listed
     * every student's House Group warden (and seminar/tutor faculty) as if they
     * were cadre counsellors.
     *
     * Scoped to the students already in view, so the options can never offer a
     * counsellor whose participants this viewer cannot see.
     *
     * @param  array<int, int>  $studentPks
     * @param  array<int, array<int, string>>  $coursePksByStudent  spk => in-view course pks
     * @return array<string, array<int, array{pk: string, name: string}>>  cadre => counsellors
     */
    private function otParticipantsCounsellorOptions(array $studentPks, array $coursePksByStudent = []): array
    {
        return $this->otParticipantsGroupFacultyOptions(
            $studentPks,
            $this->counsellorGroupTypePks(),
            $coursePksByStudent,
            // Cadre keys are the cadre_master spelling of the group name — the same
            // normalisation the Cadre column and filter use.
            $this->cadreNameNormaliser($studentPks)
        );
    }

    /**
     * House Group → House Group Faculty options, the dependent dropdown beside
     * House Group. Same shape and rules as the Cadre Counsellor list, keyed by the
     * house group's own name (no cadre normalisation).
     *
     * @param  array<int, int>  $studentPks
     * @param  array<int, array<int, string>>  $coursePksByStudent  spk => in-view course pks
     * @return array<string, array<int, array{pk: string, name: string}>>  house group => faculty
     */
    private function otParticipantsHouseFacultyOptions(array $studentPks, array $coursePksByStudent = []): array
    {
        return $this->otParticipantsGroupFacultyOptions(
            $studentPks,
            $this->houseGroupTypePks(),
            $coursePksByStudent
        );
    }

    /**
     * The shared builder behind both faculty dropdowns: the faculty mapped to a
     * course group of the given types, grouped by THAT MAPPING'S OWN group name.
     *
     * Two rules keep the list honest, and both were learned from real data:
     *
     *  - Group by the mapping's own group_name, never by whichever cadre / house
     *    the student happens to display under. Keying off the student filed every
     *    counsellor group they sit in under one cadre — so Cadre "Maharastra",
     *    whose only mapping is Ganesh Shankar Mishra, also offered the counsellors
     *    of the Andhra Pradesh and Uttar Pradesh groups those same students are in.
     *
     *  - Only count mappings from a course the student is IN VIEW for. A group
     *    belongs to a course; reading another course's mapping listed a faculty
     *    who counsels nobody on the list being looked at. Matches
     *    participantGroupEntriesFor(), which renders the columns the same way.
     *
     * @param  array<int, int>  $studentPks
     * @param  array<int, int|string>  $typePks
     * @param  array<int, array<int, string>>  $coursePksByStudent  spk => in-view course pks
     *                                                             (empty = don't course-scope)
     * @param  callable|null  $keyNormaliser  fn (string $groupName, int $spk): string
     * @return array<string, array<int, array{pk: string, name: string}>>
     */
    private function otParticipantsGroupFacultyOptions(array $studentPks, array $typePks, array $coursePksByStudent = [], ?callable $keyNormaliser = null): array
    {
        if (empty($studentPks)) {
            return [];
        }

        $courseScope = $this->participantCourseScope($coursePksByStudent);

        $rows = DB::table('student_course_group_map as scg')
            ->join('group_type_master_course_master_map as gmap', 'scg.group_type_master_course_master_map_pk', '=', 'gmap.pk')
            ->join('faculty_master as fm', 'gmap.facility_id', '=', 'fm.pk')
            ->whereIn('scg.student_master_pk', $studentPks)
            ->when(! empty($typePks), fn ($q) => $q->whereIn('gmap.type_name', $typePks))
            ->when(! empty($courseScope), fn ($q) => $q->whereIn('gmap.course_name', $courseScope))
            ->whereNotNull('gmap.group_name')
            ->where('gmap.group_name', '<>', '')
            ->where('scg.active_inactive', 1)
            ->where('gmap.active_inactive', 1)
            ->where('fm.active_inactive', 1)
            ->distinct()
            ->orderBy('fm.full_name')
            ->get([
                'scg.student_master_pk as spk',
                'gmap.course_name as course_pk',
                'gmap.group_name',
                'fm.pk as faculty_pk',
                'fm.full_name as faculty_name',
            ]);

        $byGroup = [];
        foreach ($rows as $r) {
            $spk = (int) $r->spk;
            // whereIn above narrows to the courses in view as a set; this drops the
            // rows where the course belongs to a DIFFERENT student's view.
            if (! $this->participantCourseInView($spk, $r->course_pk, $coursePksByStudent)) {
                continue;
            }

            $group = trim((string) $r->group_name);
            if ($group === '' || empty($r->faculty_pk)) {
                continue;
            }
            if ($keyNormaliser !== null) {
                $group = trim((string) $keyNormaliser($group, $spk));
                if ($group === '') {
                    continue;
                }
            }

            $name = trim((string) ($r->faculty_name ?? ''));
            $byGroup[$group][(string) $r->faculty_pk] = [
                'pk' => (string) $r->faculty_pk,
                // Blank full_name rows still need a label to be selectable.
                'name' => $name !== '' ? $name : ('Faculty #' . $r->faculty_pk),
            ];
        }

        ksort($byGroup, SORT_NATURAL | SORT_FLAG_CASE);

        // Drop the faculty-pk keys — the front-end just iterates the list.
        return array_map('array_values', $byGroup);
    }

    /**
     * spk => the course pks that student is in view for, from the collapsed
     * participant rows. Backs the course scoping of both faculty dropdowns and
     * their filters.
     *
     * @return array<int, array<int, string>>
     */
    private function participantCoursePks($participants): array
    {
        $out = [];
        foreach ($participants as $p) {
            $spk = (int) ($p->student_master_pk ?? 0);
            if (! $spk) {
                continue;
            }
            foreach ((array) ($p->course_pks ?? []) as $cpk) {
                if ((string) $cpk !== '') {
                    $out[$spk][(string) $cpk] = true;
                }
            }
        }

        return array_map(fn ($c) => array_keys($c), $out);
    }

    /**
     * Every course pk any in-view student belongs to — the SQL-side narrowing for
     * the per-student check below. Empty = the caller had no course context, so
     * nothing is scoped away.
     *
     * @param  array<int, array<int, string>>  $coursePksByStudent
     * @return array<int, string>
     */
    private function participantCourseScope(array $coursePksByStudent): array
    {
        if (empty($coursePksByStudent)) {
            return [];
        }

        $all = [];
        foreach ($coursePksByStudent as $pks) {
            foreach ($pks as $pk) {
                $all[(string) $pk] = true;
            }
        }

        return array_keys($all);
    }

    /**
     * Is this group mapping's course one the student is in view for?
     *
     * @param  array<int, array<int, string>>  $coursePksByStudent
     */
    private function participantCourseInView(int $spk, $coursePk, array $coursePksByStudent): bool
    {
        if (empty($coursePksByStudent)) {
            return true;
        }

        // Course pks are compared as strings on both sides: array keys collapse a
        // numeric pk back to an int, so a strict in_array() against the raw column
        // value never matched and scoped every option away.
        foreach ($coursePksByStudent[$spk] ?? [] as $pk) {
            if ((string) $pk === (string) $coursePk) {
                return true;
            }
        }

        return false;
    }

    /**
     * Participants counselled by one faculty, as a spk => true lookup.
     *
     * Backs the Cadre Counsellor filter on the OT participants page. That page
     * loads its payload without group mappings ($withTotals = false), so the row
     * objects carry no counsellor to filter on — resolve it from the map tables
     * instead.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, true>
     */
    private function studentPksForCounsellorFaculty($facultyPk, array $studentPks, array $coursePksByStudent = []): array
    {
        // Same group-type AND course restriction as otParticipantsCounsellorOptions(),
        // so the filter selects on exactly the counsellor mapping the dropdown was
        // built from — not on the student's House Group / seminar faculty, and not
        // on a counsellor group from a course that is not on screen.
        return $this->studentPksForGroupFaculty($facultyPk, $studentPks, $this->counsellorGroupTypePks(), $coursePksByStudent);
    }

    /**
     * Participants whose HOUSE GROUP faculty is the given faculty.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, true>
     */
    private function studentPksForHouseFaculty($facultyPk, array $studentPks, array $coursePksByStudent = []): array
    {
        return $this->studentPksForGroupFaculty($facultyPk, $studentPks, $this->houseGroupTypePks(), $coursePksByStudent);
    }

    /**
     * Participants mapped to one faculty through a course group of the given
     * types, as a spk => true lookup.
     *
     * @param  array<int, int>  $studentPks
     * @param  array<int, int|string>  $typePks
     * @return array<int, true>
     */
    private function studentPksForGroupFaculty($facultyPk, array $studentPks, array $typePks, array $coursePksByStudent = []): array
    {
        if (empty($studentPks) || (string) $facultyPk === '') {
            return [];
        }

        $courseScope = $this->participantCourseScope($coursePksByStudent);

        $rows = DB::table('student_course_group_map as scg')
            ->join('group_type_master_course_master_map as gmap', 'scg.group_type_master_course_master_map_pk', '=', 'gmap.pk')
            ->whereIn('scg.student_master_pk', $studentPks)
            ->where('gmap.facility_id', $facultyPk)
            ->when(! empty($typePks), fn ($q) => $q->whereIn('gmap.type_name', $typePks))
            ->when(! empty($courseScope), fn ($q) => $q->whereIn('gmap.course_name', $courseScope))
            ->where('scg.active_inactive', 1)
            ->where('gmap.active_inactive', 1)
            ->distinct()
            ->get(['scg.student_master_pk as spk', 'gmap.course_name as course_pk']);

        $out = [];
        foreach ($rows as $r) {
            $spk = (int) $r->spk;
            if ($this->participantCourseInView($spk, $r->course_pk, $coursePksByStudent)) {
                $out[$spk] = true;
            }
        }

        return $out;
    }

    /**
     * Per-student meta for the OT participants list, batched for the whole set:
     *   medical         → student_medical_exemption rows (active)
     *   pt              → leave_application rows, leave_type = PT_EXEMPTION
     *   stationed       → leave_application rows, leave_type = STATIONED_LEAVE
     *   duty_count/type → mdo_escot_duty_map rows (type from mdo_duty_type_master)
     *   notice_memo     → student_memo_status memo_count (OT-portal memos)
     *   discipline_memo → discipline_memo_status rows
     *
     * When a Time Period ($fromDate/$toDate, Y-m-d) is supplied, every count is
     * scoped to rows whose own date column falls inside that range:
     *   medical/pt/stationed → from_date, duty → mdo_date, memos → date.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, array{medical:int,pt:int,stationed:int,duty_count:int,duty_type:string,notice_memo:int,discipline_memo:int}>
     */
    private function otParticipantsRowMeta(array $studentPks, ?string $fromDate = null, ?string $toDate = null): array
    {
        $out = [];
        if (empty($studentPks)) {
            return $out;
        }
        foreach ($studentPks as $pk) {
            $out[$pk] = [
                'medical' => 0, 'pt' => 0, 'stationed' => 0,
                'duty_count' => 0, 'duty_type' => '-',
                'notice_memo' => 0, 'discipline_memo' => 0,
            ];
        }

        $medical = DB::table('student_medical_exemption')
            ->whereIn('student_master_pk', $studentPks)
            ->where('active_inactive', 1)
            ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
            ->selectRaw('student_master_pk, COUNT(*) c')
            ->groupBy('student_master_pk')
            ->pluck('c', 'student_master_pk');
        foreach ($medical as $pk => $c) {
            if (isset($out[$pk])) {
                $out[$pk]['medical'] = (int) $c;
            }
        }

        // PT / Stationed leaves are reported as the NUMBER OF DAYS. When a Time
        // Period is selected, only the days that fall inside that window count —
        // e.g. a 13–18 leave filtered to 13 counts as 1 day. So we pull any leave
        // that OVERLAPS the window, then clip each range to it before counting.
        $leaveRows = DB::table('leave_application')
            ->whereIn('student_master_pk', $studentPks)
            ->where('active_inactive', 1)
            ->whereIn('leave_type', ['PT_EXEMPTION', 'STATIONED_LEAVE'])
            // Overlap: leave ends on/after the window start AND starts on/before its end.
            ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
            ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
            ->orderBy('from_date')
            ->get(['student_master_pk', 'leave_type', 'from_date', 'to_date']);

        $rangesByKey = []; // "studentPk|leaveType" => [[fromYmd, toYmd], ...]
        foreach ($leaveRows as $r) {
            if (! isset($out[$r->student_master_pk]) || empty($r->from_date)) {
                continue;
            }
            $from = substr((string) $r->from_date, 0, 10);
            $to = ! empty($r->to_date) ? substr((string) $r->to_date, 0, 10) : $from;
            if ($to < $from) {
                $to = $from;
            }
            // Clip the leave to the selected Time Period so only in-window days count.
            if ($fromDate && $from < $fromDate) {
                $from = $fromDate;
            }
            if ($toDate && $to > $toDate) {
                $to = $toDate;
            }
            if ($to < $from) {
                continue; // no overlap with the filter window
            }
            $rangesByKey[$r->student_master_pk.'|'.$r->leave_type][] = [$from, $to];
        }

        foreach ($rangesByKey as $key => $ranges) {
            usort($ranges, fn ($a, $b) => strcmp($a[0], $b[0]));
            // totalDays = distinct calendar days covered. Both PT Exemption and
            // Stationed Leave show the number of days (contiguous/overlapping day
            // rows of one request are merged first, so days are never double-counted).
            $totalDays = 0;
            $currentStart = null;
            $currentEnd = null;
            foreach ($ranges as [$from, $to]) {
                // Merge into the running span while ranges stay contiguous/overlapping;
                // a real gap (>1 day) closes the current span and opens a new one.
                if ($currentEnd === null || $from > Carbon::parse($currentEnd)->addDay()->toDateString()) {
                    if ($currentStart !== null) {
                        $totalDays += Carbon::parse($currentStart)->diffInDays(Carbon::parse($currentEnd)) + 1;
                    }
                    $currentStart = $from;
                    $currentEnd = $to;
                } elseif ($to > $currentEnd) {
                    $currentEnd = $to;
                }
            }
            if ($currentStart !== null) {
                $totalDays += Carbon::parse($currentStart)->diffInDays(Carbon::parse($currentEnd)) + 1;
            }
            [$pk, $leaveType] = explode('|', $key, 2);
            if ($leaveType === 'PT_EXEMPTION') {
                $out[$pk]['pt'] = $totalDays;
            } elseif ($leaveType === 'STATIONED_LEAVE') {
                $out[$pk]['stationed'] = $totalDays;
            }
        }

        // Duty count + type. selected_student_list carries the assigned OT pk;
        // count all duties for a student and list every DISTINCT duty type they
        // hold (a student with multiple duties can span multiple types).
        $duties = DB::table('mdo_escot_duty_map as d')
            ->leftJoin('mdo_duty_type_master as m', 'd.mdo_duty_type_master_pk', '=', 'm.pk')
            ->whereIn('d.selected_student_list', $studentPks)
            ->when($fromDate, fn ($q) => $q->whereDate('d.mdo_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('d.mdo_date', '<=', $toDate))
            ->get(['d.selected_student_list as spk', 'm.mdo_duty_type_name as type']);
        $dutyTypes = []; // pk => [distinct type names, in first-seen order]
        foreach ($duties as $r) {
            $pk = (int) $r->spk;
            if (! isset($out[$pk])) {
                continue;
            }
            $out[$pk]['duty_count']++;
            $type = trim((string) ($r->type ?? ''));
            if ($type !== '' && ! in_array($type, $dutyTypes[$pk] ?? [], true)) {
                $dutyTypes[$pk][] = $type;
            }
        }
        foreach ($dutyTypes as $pk => $types) {
            if (isset($out[$pk]) && ! empty($types)) {
                $out[$pk]['duty_type'] = implode(', ', $types);
            }
        }

        // Notice / Memo (OT-portal): sum of memo_count per student.
        $memo = DB::table('student_memo_status')
            ->whereIn('student_pk', $studentPks)
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->selectRaw('student_pk, COALESCE(SUM(memo_count), COUNT(*)) c')
            ->groupBy('student_pk')
            ->pluck('c', 'student_pk');
        foreach ($memo as $pk => $c) {
            if (isset($out[$pk])) {
                $out[$pk]['notice_memo'] = (int) $c;
            }
        }

        // Discipline memos.
        $disc = DB::table('discipline_memo_status')
            ->whereIn('student_master_pk', $studentPks)
            ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
            ->selectRaw('student_master_pk, COUNT(*) c')
            ->groupBy('student_master_pk')
            ->pluck('c', 'student_master_pk');
        foreach ($disc as $pk => $c) {
            if (isset($out[$pk])) {
                $out[$pk]['discipline_memo'] = (int) $c;
            }
        }

        return $out;
    }

    /**
     * May the current user work with the OT / Participants List?
     *
     * The same rule the list itself uses, so the export, the comment form and the
     * comment history never disagree with what the table already shows.
     */
    private function canUseOtParticipants(): bool
    {
        // Denied outright for a trainee, checked BEFORE the allow-list below.
        // is_faculty_portal_user() alone is not enough: some OT logins also carry a
        // Spatie "Faculty" role, which would otherwise let a trainee post feedback
        // about other participants.
        if (isOfficerTraineeUser()) {
            return false;
        }

        $facultyPk = get_auth_faculty_master_pk();

        return hasRole('Super Admin')
            || hasRole('Training Induction Admin')
            || hasRole('Training MCTP Admin')
            || hasRole('Training IST')
            || is_faculty_portal_user()
            || ($facultyPk && ! hasRole('Student-OT'));
    }

    /** Memoised otParticipantRosterPks() result; null is a meaningful value here. */
    private ?array $otParticipantRoster = null;

    private bool $otParticipantRosterResolved = false;

    /**
     * The participant roster this viewer may act on — the SAME set
     * otParticipantsList() shows: the students of the courses they coordinate
     * (CC / ACC) plus the students of the course groups they own (House /
     * Counsellor group faculty).
     *
     * canUseOtParticipants() answers "may this user use the feature at all"; this
     * answers "about WHICH participants", which is the part the list enforces and
     * the endpoints behind it must enforce too.
     *
     * Resolved here rather than from resolveDashboardStudentListPayload() on
     * purpose. That walks the entire payload — group maps, cadres, attendance
     * sessions — to answer a yes/no question, and it is scoped both to the
     * Active / Archive tab and to whatever filters the request carries. Reading
     * either into an authorization decision would refuse a legitimate comment on
     * a participant whose course has finished, or one hidden by a Course filter
     * that happened to be posted. This reads neither: course end dates and
     * request filters are presentation, not permission.
     *
     * @return array<int, true>|null  student_master_pk => true, or null for a
     *                                viewer who oversees every course
     */
    private function otParticipantRosterPks(): ?array
    {
        if ($this->otParticipantRosterResolved) {
            return $this->otParticipantRoster;
        }

        $this->otParticipantRosterResolved = true;

        // Training authorities oversee every course and have no faculty pk of
        // their own — same treatment resolveDashboardStudentListPayload() gives
        // them, so their list and their endpoints agree.
        if (hasRole('Super Admin')
            || hasRole('Training Induction Admin')
            || hasRole('Training MCTP Admin')
            || hasRole('Training IST')) {
            return $this->otParticipantRoster = null;
        }

        $facultyPk = get_auth_faculty_master_pk();
        if (! $facultyPk || hasRole('Student-OT')) {
            // No faculty record ⇒ no roster of their own, whatever portal role
            // they carry. An empty set refuses every participant.
            return $this->otParticipantRoster = [];
        }

        $pks = [];

        // Source 1 — enrolments of the courses they coordinate.
        $coordinatorCourses = $this->getCoordinatorCourseIds((int) $facultyPk);
        if ($coordinatorCourses->isNotEmpty()) {
            $courseIds = CourseMaster::whereIn('pk', $coordinatorCourses)
                ->where('active_inactive', 1)
                ->pluck('pk');

            if ($courseIds->isNotEmpty()) {
                foreach (StudentMasterCourseMap::whereIn('course_master_pk', $courseIds)
                    ->where('active_inactive', 1)
                    ->pluck('student_master_pk') as $spk) {
                    $pks[(int) $spk] = true;
                }
            }
        }

        // Source 2 — students of the course groups this faculty owns. Kept in
        // step with the payload's source 2: leaving these out is what showed a
        // House Group warden an empty list.
        $groupMapPks = DB::table('group_type_master_course_master_map')
            ->where('facility_id', $facultyPk)
            ->where('active_inactive', 1)
            ->pluck('pk');

        if ($groupMapPks->isNotEmpty()) {
            foreach (StudentCourseGroupMap::whereIn('group_type_master_course_master_map_pk', $groupMapPks)
                ->where('active_inactive', 1)
                ->pluck('student_master_pk') as $spk) {
                $pks[(int) $spk] = true;
            }
        }

        return $this->otParticipantRoster = $pks;
    }

    /**
     * Whether this viewer may read or write feedback about one participant.
     *
     * Every comment endpoint asserts this. Without it the page is scoped to the
     * viewer's own roster while the endpoints behind it accept any participant in
     * the institute — and the store endpoint also pushes the text to them as a
     * notification.
     */
    private function canActOnOtParticipant(int $studentPk): bool
    {
        $roster = $this->otParticipantRosterPks();

        return $roster === null || isset($roster[$studentPk]);
    }

    /**
     * Every cell of a spreadsheet export through sanitize_export_cell().
     *
     * A cell beginning = + - @ (or a tab / CR) is evaluated as a formula by Excel,
     * LibreOffice and Sheets, so free text a user typed has to be neutralised
     * before it reaches the file. Spreadsheet paths only — the print and PDF views
     * render HTML and would show the guard's leading apostrophe.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<int, string>>
     */
    private function sanitizeExportRows(array $rows): array
    {
        $cell = function ($value): string {
            $value = (string) $value;

            // A lone placeholder character cannot begin a formula, and these exports
            // write '-' for "none" in eight count columns of every row — sending it
            // through the guard would print a literal apostrophe in all of them.
            // Anything longer still goes through.
            if ($value === '-' || $value === '+' || $value === '@') {
                return $value;
            }

            return sanitize_export_cell($value);
        };

        return array_map(fn (array $row) => array_map($cell, $row), $rows);
    }

    /**
     * Comment / feedback counts for a page of participants, in one query.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, int>  spk => count
     */
    private function otParticipantCommentCounts(array $studentPks): array
    {
        if (empty($studentPks)) {
            return [];
        }

        return OtParticipantComment::active()
            ->whereIn('student_master_pk', $studentPks)
            ->selectRaw('student_master_pk, COUNT(*) as total')
            ->groupBy('student_master_pk')
            ->pluck('total', 'student_master_pk')
            ->mapWithKeys(fn ($n, $spk) => [(int) $spk => (int) $n])
            ->all();
    }

    /**
     * Save a comment / feedback against an OT, and notify them when asked.
     */
    public function otParticipantCommentStore(Request $request)
    {
        if (! $this->canUseOtParticipants()) {
            abort(403, 'You are not authorized to comment on participants.');
        }

        $data = $request->validate([
            'student_master_pk' => ['required', 'integer'],
            'course_master_pk' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:5000'],
            'notify_ot' => ['required', 'in:0,1'],
        ]);

        $student = StudentMaster::find($data['student_master_pk']);
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Participant not found.'], 404);
        }

        // student_master_pk arrives as a plain integer, so the roster check is the
        // whole authorization boundary here — canUseOtParticipants() above is
        // satisfied by any faculty-portal user and says nothing about WHICH
        // participant. Without this, a faculty member could store attributed
        // feedback about anyone in the institute and notify them.
        if (! $this->canActOnOtParticipant((int) $data['student_master_pk'])) {
            return response()->json([
                'success' => false,
                'message' => 'This participant is not on your roster.',
            ], 403);
        }

        $author = Auth::user();
        $authorName = trim((string) (($author->first_name ?? '') . ' ' . ($author->last_name ?? '')));

        $message = trim($data['message']);

        $comment = new OtParticipantComment([
            'student_master_pk' => (int) $data['student_master_pk'],
            'course_master_pk' => $data['course_master_pk'] ?? null,
            'message' => $message,
            'notify_ot' => (int) $data['notify_ot'],
            'comment_date' => now()->toDateString(),
        ]);

        // Attribution is assigned, never mass-assigned — these columns are outside
        // the model's $fillable so that no present or future request payload can
        // forge an author or deactivate a comment. See OtParticipantComment.
        $comment->comment_by_user_id = $author->user_id ?? null;
        $comment->comment_by_name = $authorName !== '' ? $authorName : ($author->user_name ?? null);
        $comment->active_inactive = 1;
        $comment->created_by = $author->user_id ?? null;
        $comment->save();

        // Notify OT = Yes. Resolved through NotificationReceiverService because
        // student_master.user_id is a login string, not the numeric receiver id
        // notifications are keyed by (same path MemoDisciplineController uses).
        if ((int) $data['notify_ot'] === 1) {
            try {
                $receiverUserId = app(\App\Services\NotificationReceiverService::class)
                    ->getStudentUserId((int) $data['student_master_pk']);

                if ($receiverUserId) {
                    // The feedback itself goes INTO the notification — an OT who
                    // only sees "you have received a comment" has to hunt for it.
                    // Newlines are flattened because the bell list renders the
                    // message as one truncated line.
                    $preview = trim(preg_replace('/\s+/u', ' ', $message));

                    app(\App\Services\NotificationService::class)->create(
                        (int) $receiverUserId,
                        'ot_comment',
                        'OT Comment/Feedback',
                        (int) $comment->pk,
                        'New Comment/Feedback',
                        // The title already says what this is, so the body leads
                        // with the author and the feedback — the bell list cuts the
                        // message at 120 chars and boilerplate would eat that budget.
                        ($authorName !== '' ? "From {$authorName}: " : '')
                            . ($preview !== '' ? '"' . $preview . '"' : 'You have received a new comment/feedback.')
                    );
                }
            } catch (\Throwable $e) {
                // The comment is saved either way — a failed notification must not
                // lose the feedback that was just written.
                \Log::error('OT comment notification failed: ' . $e->getMessage());
            }
        }

        $spk = (int) $data['student_master_pk'];

        return response()->json([
            'success' => true,
            'message' => 'Comment/Feedback added successfully.',
            // Returned so the row's count can update without a full table reload.
            'count' => $this->otParticipantCommentCounts([$spk])[$spk] ?? 0,
        ]);
    }

    /**
     * One participant's comment / feedback history — the page behind the count in
     * the COMMENTS/FEEDBACKS column.
     */
    public function otParticipantComments(Request $request, $id)
    {
        if (! $this->canUseOtParticipants()) {
            abort(403, 'You are not authorized to view participant feedback.');
        }

        try {
            $studentPk = (int) decrypt($id);
        } catch (\Throwable $e) {
            return redirect()->route('admin.dashboard.ot-participants')
                ->with('error', 'Invalid participant ID.');
        }

        $student = StudentMaster::find($studentPk);
        if (! $student) {
            return redirect()->route('admin.dashboard.ot-participants')
                ->with('error', 'Participant not found.');
        }

        // The encrypted id raises the bar on guessing a participant; it is not an
        // authorization check. This is — same roster the list is built from.
        if (! $this->canActOnOtParticipant($studentPk)) {
            abort(403, 'This participant is not on your roster.');
        }

        $rows = $this->otParticipantCommentRows($request, $studentPk);

        if ($request->ajax() && $request->has('draw')) {
            return $this->otParticipantCommentsDataTableResponse($request, $rows);
        }

        $studentName = $student->display_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        return view('admin.dashboard.ot_participant_comments', [
            'student' => $student,
            'studentName' => $studentName !== '' ? $studentName : 'Participant',
            'studentId' => $id,
            'filters' => [
                'from_date' => (string) $request->input('from_date', ''),
                'to_date' => (string) $request->input('to_date', ''),
            ],
        ]);
    }

    /**
     * The comment rows for one participant, newest first, honouring the Date Range
     * filter and the search box. Shared by the table, the exports and the print view.
     */
    private function otParticipantCommentRows(Request $request, int $studentPk)
    {
        $from = $request->input('from_date') ?: null;
        $to = $request->input('to_date') ?: null;

        $rows = OtParticipantComment::active()
            ->where('student_master_pk', $studentPk)
            ->when($from, fn ($q) => $q->whereDate('comment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('comment_date', '<=', $to))
            ->orderByDesc('comment_date')
            ->orderByDesc('pk')
            ->get();

        $searchInput = $request->input('search');
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        // ?search[value][]= is not a search term (PR #334 F-037: was a 500).
        $search = is_scalar($searchValue) ? strtolower(trim((string) $searchValue)) : '';
        if ($search !== '') {
            // The comment columns as the table shows them (PR #334 F-019: the merge
            // 7082e5204 had left the participants-list search body here).
            $rows = $rows->filter(function ($r) use ($search) {
                $haystack = strtolower(implode(' ', [
                    (string) $r->comment_by_name,
                    (string) $r->message,
                    ((int) $r->notify_ot === 1 ? 'yes' : 'no'),
                    optional($r->comment_date)->format('d M Y') ?? '',
                ]));

                return str_contains($haystack, $search);
            })->values();
        }

        return $rows->values();
    }

    /**
     * Server-side JSON for the comment / feedback history table.
     */
    private function otParticipantCommentsDataTableResponse(Request $request, $rows)
    {
        $total = $rows->count();

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $paged = $length < 0 ? $rows->slice($start)->values() : $rows->slice($start, $length)->values();

        $data = [];
        foreach ($paged as $idx => $r) {
            $data[] = [
                's_no' => $start + $idx + 1,
                'comment_by' => e($r->comment_by_name ?: 'N/A'),
                'message' => e($r->message),
                'notify_ot' => (int) $r->notify_ot === 1 ? 'Yes' : 'No',
                'date' => optional($r->comment_date)->format('d M Y') ?? '-',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }

    /**
     * Export one participant's comment / feedback history.
     */
    public function otParticipantCommentsExport(Request $request, $id, string $format)
    {
        if (! in_array($format, ['csv', 'pdf', 'print'], true)) {
            abort(404);
        }

        if (! $this->canUseOtParticipants()) {
            abort(403, 'You are not authorized to export participant feedback.');
        }

        // Same date rule as the list page — the filter summary below calls
        // Carbon::parse() on these, which 500s on a malformed value.
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        try {
            $studentPk = (int) decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $student = StudentMaster::find($studentPk);
        if (! $student) {
            abort(404);
        }

        // Same roster boundary as the history page this file is downloaded from.
        if (! $this->canActOnOtParticipant($studentPk)) {
            abort(403, 'This participant is not on your roster.');
        }

        $studentName = $student->display_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        $rows = $this->otParticipantCommentRows($request, $studentPk);

        $headings = ['S.No', 'Comment/Feedback By', 'Message', 'Notify OT', 'Date'];
        $widths = [5, 18, 55, 9, 13];
        $body = [];
        foreach ($rows as $idx => $r) {
            $body[] = [
                $idx + 1,
                (string) ($r->comment_by_name ?: 'N/A'),
                (string) $r->message,
                (int) $r->notify_ot === 1 ? 'Yes' : 'No',
                optional($r->comment_date)->format('d M Y') ?? '-',
            ];
        }

        $reportTitle = trim($studentName . "'s Comment/ Feedback");
        $filterSummary = '';
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $filterSummary = 'Date Range: ' . Carbon::parse($request->input('from_date'))->format('d-m-Y')
                . ' to ' . Carbon::parse($request->input('to_date'))->format('d-m-Y');
        }

        $header = $this->buildStudentListExportHeaderData($request);
        $timestamp = now()->format('Ymd_His');
        $fileBase = 'ot_comments_' . $timestamp;

        if ($format === 'print') {
            return view('admin.dashboard.export.student_list_print', array_merge([
                'headings' => $headings,
                'rows' => $body,
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
            ], $header));
        }

        if ($format === 'pdf') {
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('admin.dashboard.export.student_list_pdf', array_merge([
                'headings' => $headings,
                'rows' => $body,
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
                'columnWidths' => $widths,
            ], $header))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // Both deliberately OFF. isPhpEnabled makes the renderer a PHP
                    // execution context for any raw block that reaches the template, and
                    // isRemoteEnabled lets the document fetch arbitrary URLs from the
                    // server (SSRF). This view needs neither — it has no <script
                    // type="text/php"> block and no remote asset. Copied from
                    // studentListExport(), which still has them on; do not copy them back.
                    'isRemoteEnabled' => false,
                    'isPhpEnabled' => false,
                    'dpi' => 96,
                ]);

            return $pdf->download("{$fileBase}.pdf");
        }

        // CWE-1236: the message is free text the commenter typed, so every cell of
        // the workbook goes through sanitize_export_cell() — a message starting "="
        // would otherwise open in Excel as a live formula. This branch only:
        // print/pdf render HTML, where the guard's leading apostrophe would just be
        // visible noise.
        return Excel::download(
            new StudentListReportExport(
                $headings,
                $this->sanitizeExportRows($body),
                sanitize_export_cell($reportTitle),
                sanitize_export_cell((string) ($header['courseName'] ?? '')),
                sanitize_export_cell((string) ($header['courseDuration'] ?? '')),
                sanitize_export_cell($filterSummary),
                now()->format('d-m-Y H:i'),
                count($body),
            ),
            "{$fileBase}.xlsx",
            ExcelWriter::XLSX
        );
    }

    /**
     * Export the OT / Participants List as Excel, PDF or a printable page.
     *
     * Uses the SAME pipeline as the on-screen table — same coordinated-roster
     * payload, same filters, same search — so the file always matches what the
     * viewer is looking at, including the Active / Archived tab.
     */
    public function otParticipantsExport(Request $request, string $format)
    {
        if (! in_array($format, ['csv', 'pdf', 'print'], true)) {
            abort(404);
        }

        // Anyone who can VIEW the list may export it — the same gate the list
        // itself uses, so the download never 403s for someone the table works
        // for, and never succeeds for someone the table would refuse.
        if (! $this->canUseOtParticipants()) {
            abort(403, 'You are not authorized to export the OT / Participants list.');
        }

        // Same date rule as the list page — otParticipantsFilterSummary() below calls
        // Carbon::parse() on these, which 500s on a malformed value.
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $payload = $this->resolveDashboardStudentListPayload($request, false, true);
        $rows = $this->otParticipantsRowsFor($request, $payload['students']);

        $rowMeta = $this->otParticipantsRowMeta(
            $rows->pluck('student_master_pk')->all(),
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null
        );

        // The search box narrows the export exactly as it narrows the table.
        $rows = $this->otParticipantsApplySearch($request, $rows, $rowMeta);

        $exportData = $this->otParticipantsExportData($rows, $rowMeta, $this->otParticipantsHouseRoomFallback($request));

        $timestamp = now()->format('Ymd_His');
        $fileBase = "ot_participants_{$timestamp}";
        $reportTitle = 'OT / Participants List';
        $filterSummary = $this->otParticipantsFilterSummary($request);
        $header = $this->buildStudentListExportHeaderData($request);

        if ($format === 'print') {
            return view('admin.dashboard.export.student_list_print', array_merge([
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
            ], $header));
        }

        if ($format === 'pdf') {
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('admin.dashboard.export.student_list_pdf', array_merge([
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
                // 18 columns do not fit an auto-layout table: DomPDF lets it grow
                // past the page and clips the right-hand columns. These weights
                // switch the table to a fixed layout so every column lands on the
                // page. They mirror the on-screen Print widths.
                'columnWidths' => $exportData['widths'],
            ], $header))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // Both deliberately OFF. isPhpEnabled makes the renderer a PHP
                    // execution context for any raw block that reaches the template, and
                    // isRemoteEnabled lets the document fetch arbitrary URLs from the
                    // server (SSRF). This view needs neither — it has no <script
                    // type="text/php"> block and no remote asset. Copied from
                    // studentListExport(), which still has them on; do not copy them back.
                    'isRemoteEnabled' => false,
                    'isPhpEnabled' => false,
                    'dpi' => 96,
                ]);

            return $pdf->download("{$fileBase}.pdf");
        }

        // Delivered as a branded .xlsx rather than a flat CSV — see the note on
        // studentListExport().
        // Same formula-injection guard as the comment export. The filter summary
        // matters here too: otParticipantsFilterSummary() echoes raw
        // $request->input() back into that line whenever a name lookup misses.
        return Excel::download(
            new StudentListReportExport(
                $exportData['headings'],
                $this->sanitizeExportRows($exportData['rows']),
                sanitize_export_cell($reportTitle),
                sanitize_export_cell((string) ($header['courseName'] ?? '')),
                sanitize_export_cell((string) ($header['courseDuration'] ?? '')),
                sanitize_export_cell($filterSummary),
                now()->format('d-m-Y H:i'),
                count($exportData['rows']),
            ),
            "{$fileBase}.xlsx",
            ExcelWriter::XLSX
        );
    }

    /**
     * Headings + plain-text rows for the OT participants exports, in the same
     * order as the on-screen columns. Counts are written as the zero-padded
     * strings the table shows, with a dash for none.
     *
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>, widths: array<int, int>}
     */
    private function otParticipantsExportData($rows, array $rowMeta, bool $roomFallback = false): array
    {
        // Heading, then the relative width it gets in the PDF. Text columns need
        // the room; the count columns hold two characters and stay narrow.
        $columns = [
            ['S. No.', 3],
            ['OT Code', 5],
            ['Name', 13],
            ['Email', 17],
            ['Mobile No', 8],
            ['User Name', 10],
            ['Cadre', 8],
            ['Cadre Counsellor', 10],
            ['House Group Faculty', 11],
            ['House Group', 9],
            ['MDO Duty', 5],
            ['Duty Type', 7],
            ['Medical Exemption', 6],
            ['PT Exemption', 5],
            ['Stationed Leave', 5],
            ['Notice/Memo', 5],
            ['Discipline Memo', 5],
            ['Comments/ Feedbacks', 5],
        ];
        $headings = array_column($columns, 0);
        $widths = array_column($columns, 1);

        // Counts for the whole exported set, in one query.
        $commentCounts = $this->otParticipantCommentCounts(
            collect($rows)->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all()
        );

        $count = fn ($n) => (int) $n > 0 ? str_pad((string) (int) $n, 2, '0', STR_PAD_LEFT) : '-';

        $out = [];
        foreach (collect($rows)->values() as $index => $p) {
            $s = $p->studentMaster;
            if (! $s) {
                continue;
            }

            $meta = $rowMeta[$p->student_master_pk] ?? [];
            $name = $s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));

            $out[] = [
                $index + 1,
                (string) ($s->generated_OT_code ?: 'N/A'),
                (string) ($name ?: 'N/A'),
                (string) ($s->email ?: 'N/A'),
                (string) ($s->contact_no ?: 'N/A'),
                (string) ($s->user_id ?: 'N/A'),
                (string) ($this->otParticipantCadreLabel($p) ?: 'N/A'),
                (string) ($p->counsellor_name ?: 'N/A'),
                (string) ($p->house_faculty_name ?: 'N/A'),
                (string) ($this->otParticipantHouseLabel($p, $roomFallback) ?: 'N/A'),
                $count($meta['duty_count'] ?? 0),
                (string) ($meta['duty_type'] ?: '-'),
                $count($meta['medical'] ?? 0),
                $count($meta['pt'] ?? 0),
                $count($meta['stationed'] ?? 0),
                $count($meta['notice_memo'] ?? 0),
                $count($meta['discipline_memo'] ?? 0),
                $count($commentCounts[(int) $p->student_master_pk] ?? 0),
            ];
        }

        return ['headings' => $headings, 'rows' => $out, 'widths' => $widths];
    }

    /**
     * One-line description of the filters in force, printed under the report
     * title so a saved file says what it was filtered by.
     */
    private function otParticipantsFilterSummary(Request $request): string
    {
        $parts = [];
        // Every value is concatenated; the list itself already treats an array-valued
        // parameter as unset (applyDashboardStudentListFilters), so the summary must
        // too, not throw "Array to string conversion" (PR #334 F-041 family).
        $in = fn (string $key) => is_scalar($request->input($key)) ? trim((string) $request->input($key)) : '';

        if ($in('course_id') !== '') {
            $course = CourseMaster::find((int) $in('course_id'));
            $parts[] = 'Course: ' . ($course->course_name ?? $in('course_id'));
        }
        if ($in('cadre') !== '') {
            $parts[] = 'Cadre: ' . $in('cadre');
        }
        if ($in('counsellor_faculty') !== '') {
            $name = DB::table('faculty_master')->where('pk', (int) $in('counsellor_faculty'))->value('full_name');
            $parts[] = 'Cadre Counsellor: ' . trim((string) ($name ?: $in('counsellor_faculty')));
        }
        if ($in('house_group') !== '') {
            $parts[] = 'House Group: ' . $in('house_group');
        }
        if ($in('house_faculty') !== '') {
            $name = DB::table('faculty_master')->where('pk', (int) $in('house_faculty'))->value('full_name');
            $parts[] = 'House Group Faculty: ' . trim((string) ($name ?: $in('house_faculty')));
        }
        // The page's "House" / "Hostel Room" select (#houseFilter) — same label as
        // the page, which depends on the view (PR #334 F-043).
        if ($in('house') !== '') {
            $parts[] = ($this->otParticipantsScopedView($request)[2] ? 'House: ' : 'Hostel Room: ') . $in('house');
        }
        if ($in('session') !== '') {
            $parts[] = 'Session: ' . $in('session');
        }
        if ($in('from_date') !== '' && $in('to_date') !== '') {
            $parts[] = 'Time Period: ' . Carbon::parse($in('from_date'))->format('d-m-Y')
                . ' to ' . Carbon::parse($in('to_date'))->format('d-m-Y');
        }

        $searchInput = $request->input('search');
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        $search = is_scalar($searchValue) ? trim((string) $searchValue) : '';
        if ($search !== '') {
            $parts[] = 'Search: ' . $search;
        }

        $parts[] = 'Status: ' . ($request->input('status') === 'archive' ? 'Archived' : 'Active');

        return implode('  |  ', $parts);
    }

    /**
     * The DataTables search box, applied over the columns THIS page renders.
     *
     * Shared by the table and the exports so a downloaded file carries exactly the
     * rows the viewer had on screen.
     */
    private function otParticipantsApplySearch(Request $request, $rows, array $rowMeta)
    {
        $searchInput = $request->input('search');
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        $search = is_scalar($searchValue) ? strtolower(trim((string) $searchValue)) : '';
        if ($search === '') {
            return collect($rows)->values();
        }

        $roomFallback = $this->otParticipantsHouseRoomFallback($request);

        return collect($rows)->filter(function ($p) use ($search, $rowMeta, $roomFallback) {
            $s = $p->studentMaster;
            if (! $s) {
                return false;
            }
            $meta = $rowMeta[$p->student_master_pk] ?? [];
            // Zero-padded count strings so "02" and "2" both match, alongside
            // the raw numbers.
            $pad = fn ($n) => str_pad((string) (int) $n, 2, '0', STR_PAD_LEFT);
            $countFields = ['duty_count', 'medical', 'pt', 'stationed', 'notice_memo', 'discipline_memo'];
            $counts = [];
            foreach ($countFields as $f) {
                $n = (int) ($meta[$f] ?? 0);
                $counts[] = (string) $n;
                $counts[] = $pad($n);
            }
            // A single haystack of every column's displayed value.
            $haystack = strtolower(implode(' ', array_filter([
                $s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                $s->generated_OT_code ?? '',
                $s->email ?? '',
                $s->contact_no ?? '',
                // student_master.user_id IS the login name (it matches
                // user_credentials.user_name) — no extra lookup needed.
                $s->user_id ?? '',
                $this->otParticipantCadreLabel($p),
                $p->counsellor_name ?? '',
                $p->house_faculty_name ?? '',
                $this->otParticipantHouseLabel($p, $roomFallback),
                $p->topic ?? '',
                $meta['duty_type'] ?? '',
                implode(' ', $counts),
            ], fn ($v) => trim((string) $v) !== '')));

            return str_contains($haystack, $search);
        })->values();
    }

    /**
     * Server-side JSON for the OT / Participants List DataTable.
     */
    private function otParticipantsDataTableResponse(Request $request, $rows, array $rowMeta, int $totalParticipants, array $filterOptions = [])
    {
        $rows = $this->otParticipantsApplySearch($request, $rows, $rowMeta);

        $recordsTotal = $totalParticipants;
        $recordsFiltered = $rows->count();

        // Sorting (S.No / OT Code / Name / Email / Cadre / House).
        // Column indexes must track the <thead> order in ot_participants_list.blade.php.
        $columnMap = [
            1 => 'ot_code', 2 => 'name', 3 => 'email', 4 => 'mobile', 5 => 'user_name',
            6 => 'cadre', 7 => 'counsellor', 8 => 'house_faculty', 9 => 'house',
        ];
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortKey = $columnMap[$orderCol] ?? null;
        $roomFallback = $this->otParticipantsHouseRoomFallback($request);
        if ($sortKey !== null) {
            $rows = $rows->sortBy(function ($p) use ($sortKey, $roomFallback) {
                $s = $p->studentMaster;

                return match ($sortKey) {
                    'ot_code' => (string) ($s->generated_OT_code ?? ''),
                    'name' => (string) ($s->display_name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? ''))),
                    'email' => (string) ($s->email ?? ''),
                    'mobile' => (string) ($s->contact_no ?? ''),
                    'user_name' => (string) ($s->user_id ?? ''),
                    'cadre' => $this->otParticipantCadreLabel($p),
                    'counsellor' => (string) ($p->counsellor_name ?? ''),
                    'house_faculty' => (string) ($p->house_faculty_name ?? ''),
                    'house' => $this->otParticipantHouseLabel($p, $roomFallback),
                    default => '',
                };
            }, SORT_NATURAL | SORT_FLAG_CASE, $orderDir === 'desc')->values();
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $paged = $length < 0 ? $rows->slice($start)->values() : $rows->slice($start, $length)->values();

        // Carry the Time Period filter into the detail-page count links so the
        // opened section shows the same date-scoped data.
        $linkDateQs = '';
        $fdParam = (string) $request->input('from_date', '');
        $tdParam = (string) $request->input('to_date', '');
        if ($fdParam !== '') {
            $linkDateQs .= '&from_date='.urlencode($fdParam);
        }
        if ($tdParam !== '') {
            $linkDateQs .= '&to_date='.urlencode($tdParam);
        }

        // Comment counts for THIS page only — one query for the ten-odd rows on
        // screen rather than the whole filtered set.
        $commentCounts = $this->otParticipantCommentCounts(
            $paged->pluck('student_master_pk')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all()
        );

        $data = [];
        foreach ($paged as $idx => $p) {
            $s = $p->studentMaster;
            if (! $s) {
                continue;
            }
            $meta = $rowMeta[$p->student_master_pk] ?? [
                'medical' => 0, 'pt' => 0, 'stationed' => 0,
                'duty_count' => 0, 'duty_type' => '-', 'notice_memo' => 0, 'discipline_memo' => 0,
            ];
            $detailUrl = route('admin.dashboard.students.detail', encrypt($s->pk));
            $name = $s->display_name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? ''));

            $data[] = [
                's_no' => $start + $idx + 1,
                'ot_code' => e($s->generated_OT_code ?? 'N/A'),
                'name' => '<a href="'.e($detailUrl).'" class="sl-count">'.e($name).'</a>',
                'email' => e($s->email ?? 'N/A'),
                'mobile' => e($s->contact_no ?: 'N/A'),
                'user_name' => e($s->user_id ?: 'N/A'),
                // The faculty on the same Course Group Mapping row as the cadre
                // (counsellor group) — i.e. this participant's cadre counsellor.
                'counsellor' => e($p->counsellor_name ?: 'N/A'),
                // The faculty on the same Course Group Mapping row as the house group.
                'house_faculty' => e($p->house_faculty_name ?: 'N/A'),
                // Same labels as the Cadre filter, the search and the export —
                // see otParticipantCadreLabel() / otParticipantHouseLabel().
                'cadre' => e($this->otParticipantCadreLabel($p) ?: 'N/A'),
                'house' => e($this->otParticipantHouseLabel($p, $roomFallback) ?: 'N/A'),
                'duty_count' => $this->otCountCell($meta['duty_count'], $detailUrl.'?section=dutiesSection'.$linkDateQs),
                'duty_type' => e($meta['duty_type'] ?: '-'),
                'medical' => $this->otCountCell($meta['medical'], $detailUrl.'?section=medicalExceptionsSection'.$linkDateQs),
                'pt' => $this->otCountCell($meta['pt'], $detailUrl.'?section=ptExemptionsSection'.$linkDateQs),
                'stationed' => $this->otCountCell($meta['stationed'], $detailUrl.'?section=stationedLeavesSection'.$linkDateQs),
                'notice_memo' => $this->otCountCell($meta['notice_memo'], $detailUrl.'?section=noticesSection'.$linkDateQs),
                'discipline_memo' => $this->otCountCell($meta['discipline_memo'], $detailUrl.'?section=memosSection'.$linkDateQs),
                // Count links to this participant's own comment history page.
                'comments' => $this->otCountCell(
                    (int) ($commentCounts[(int) $p->student_master_pk] ?? 0),
                    route('admin.dashboard.ot-participants.comments', encrypt($s->pk))
                ),
                // The Action column's "Add Comment/ Feedback" trigger. The name is
                // carried on the button so the modal can pre-fill OT Name without
                // another lookup.
                'action' => '<button type="button" class="ot-add-comment-btn"'
                    .' data-student="'.e($s->pk).'"'
                    .' data-name="'.e($name).'">'
                    .'<i class="bi bi-chat-left-text" aria-hidden="true"></i>'
                    .'<span>Add Comment/ Feedback</span></button>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            // Re-scoped dropdown options for this filter combination — the
            // front-end rebuilds Cadre / Cadre Counsellor / House Group from these
            // on every draw, so no dropdown keeps offering a dead option.
            'filterOptions' => [
                'cadre' => $filterOptions['cadre'] ?? [],
                'houseGroup' => $filterOptions['houseGroup'] ?? [],
                'counsellorsByCadre' => (object) ($filterOptions['counsellorsByCadre'] ?? []),
                'facultyByHouseGroup' => (object) ($filterOptions['facultyByHouseGroup'] ?? []),
            ],
        ]);
    }

    /**
     * A zero-padded count cell for the OT participants list. Blank counts render
     * as a muted dash; non-zero counts render as a blue badge-style number that,
     * when $url is given, links to the relevant section of the student detail page.
     */
    private function otCountCell(int $n, ?string $url = null): string
    {
        if ($n <= 0) {
            return '<span class="text-muted">-</span>';
        }

        $label = str_pad((string) $n, 2, '0', STR_PAD_LEFT);

        if ($url) {
            return '<a href="'.e($url).'" class="sl-count">'.$label.'</a>';
        }

        return '<span class="sl-count">'.$label.'</span>';
    }

    /**
     * Export the dashboard student list as CSV or PDF, honouring active filters.
     */
    public function studentListExport(Request $request, string $format)
    {
        if (! in_array($format, ['csv', 'pdf', 'print'], true)) {
            abort(404);
        }

        // Anyone who can VIEW the list may export it. Mirror the data-visibility
        // guard in resolveDashboardStudentListPayload(): Super Admin, a standard
        // faculty-portal user, OR a coordinator/ACC reached via their faculty pk
        // (login role need not be a standard faculty-portal role). Otherwise the
        // export 403s for users who see the on-screen table fine.
        $exportFacultyPk = get_auth_faculty_master_pk();
        if (! hasRole('Super Admin')
            && ! hasRole('Training Induction Admin')
            && ! hasRole('Training MCTP Admin')
            && ! hasRole('Training IST')
            && ! is_faculty_portal_user()
            && ! ($exportFacultyPk && ! hasRole('Student-OT'))) {
            abort(403, 'You are not authorized to export the student list.');
        }

        // Match the on-screen table exactly: same filters, same attendance tab, and
        // the same date-scoped Present/Absent split (incl. PT/Stationed-leave absentees).
        $payload = $this->resolveDashboardStudentListPayload($request);
        [$filteredAll, $presentAll, $absentAll] = $this->dashboardStudentListTabSets($request, $payload['students']);
        $attendance = (string) $request->input('attendance', 'all');
        $students = $attendance === 'present'
            ? $presentAll
            : ($attendance === 'absent' ? $absentAll : $filteredAll);
        $exportData = $this->dashboardStudentListExportData($students, $attendance);

        $timestamp = now()->format('Ymd_His');
        $fileBase = "student_list_{$timestamp}";

        $reportTitle = 'Student List';
        $filterSummary = $this->dashboardStudentListFilterSummary($request);
        $header = $this->buildStudentListExportHeaderData($request);

        // Browser-printable report: same clean layout as the PDF, rendered as
        // HTML in a new tab that auto-opens the print dialog.
        if ($format === 'print') {
            return view('admin.dashboard.export.student_list_print', array_merge([
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
            ], $header));
        }

        if ($format === 'pdf') {
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('admin.dashboard.export.student_list_pdf', array_merge([
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $filterSummary,
                'reportTitle' => $reportTitle,
            ], $header))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    // Never true: isPhpEnabled makes the renderer a PHP
                    // execution context for the whole view, so any raw
                    // block that later appears in an export blade would
                    // execute. Page numbers are stamped on the canvas
                    // after render instead - see PdfPageNumbers.
                    'isPhpEnabled' => false,
                    'dpi' => 96,
                ]);

            return $pdf->download("{$fileBase}.pdf");
        }

        // "CSV" download is delivered as a branded .xlsx: a plain CSV cannot carry
        // the logos, blue title band or centred/merged academy titles, so — like
        // the other report exports in this app — it is a styled workbook that
        // visually mirrors the Print/PDF header (institution → course → report
        // title → column band), not a flat comma file.
        return Excel::download(
            new StudentListReportExport(
                $exportData['headings'],
                $exportData['rows'],
                $reportTitle,
                (string) ($header['courseName'] ?? ''),
                (string) ($header['courseDuration'] ?? ''),
                $filterSummary,
                now()->format('d-m-Y H:i'),
                count($exportData['rows']),
            ),
            "{$fileBase}.xlsx",
            ExcelWriter::XLSX
        );
    }

    /**
     * Branded LBSNAA header assets for the student-list exports — the same
     * emblem / Hindi title / 75-years logo and course line used by the official
     * Student Medical Exemption report layout.
     *
     * @return array{logoLeft:?string,logoRight:?string,titleHindi:?string,courseName:string,courseDuration:string}
     */
    private function buildStudentListExportHeaderData(Request $request): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:'.$mime.';base64,'.base64_encode($raw);
        };

        $courseName = '';
        $courseDuration = '';
        if ($request->filled('course_id')) {
            $course = CourseMaster::find($request->input('course_id'));
            if ($course) {
                $courseName = (string) ($course->course_name ?? '');
                $start = ! empty($course->start_date ?? $course->start_year ?? null)
                    ? Carbon::parse($course->start_date ?? $course->start_year)->format('j F Y') : '';
                $end = ! empty($course->end_date)
                    ? Carbon::parse($course->end_date)->format('j F Y') : '';
                $courseDuration = ($start && $end) ? $start.' to '.$end : '';
            }
        }

        return [
            'logoLeft' => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight' => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
            'courseName' => $courseName,
            'courseDuration' => $courseDuration,
        ];
    }

    /**
     * @return array{students: Collection, availableCourses: Collection, facultyPk: int|null}
     */
    private function resolveDashboardStudentListPayload(?Request $request = null, bool $withTotals = true, bool $coordinatedOnly = false): array
    {
        $students = collect([]);
        $availableCourses = collect([]);
        $facultyPk = null;

        $facultyPk = get_auth_faculty_master_pk();
        $isSuperAdmin = hasRole('Super Admin');

        // Training authorities (Induction / MCTP / IST admins) are administrative
        // roles that oversee every course, not a single faculty's classes — the
        // student-detail page already grants them Super-Admin-level access. Treat them
        // the same here so their student list isn't empty (they have no faculty pk).
        $seesAllCourses = $isSuperAdmin
            || hasRole('Training Induction Admin')
            || hasRole('Training MCTP Admin')
            || hasRole('Training IST');

        // Active vs Archive scope. Only the OT-participants page sends status=archive;
        // everything else (student list, export) omits it and stays on "active".
        // Archive = course has ended (end_date < today); Active = still running.
        // Compared against now()->toDateString(), NOT now(): course_master.end_date
        // is a DATE, so a full datetime would read '2026-08-27' as 00:00:00 and
        // archive a course part-way through its own last day — dropping it (and its
        // students) from the Course filter and the list while it is still running.
        $archive = ($request?->input('status') === 'archive');
        $dateOp = $archive ? '<' : '>=';

        // Super Admin sees students of every active course; faculty / CC / ACC are
        // scoped to their courses below. A coordinator/ACC is reached via their
        // faculty pk even if their login role isn't a standard faculty-portal role.
        // (Exclude Student-OT: their user_id can collide with a faculty pk.)
        if ($seesAllCourses || is_faculty_portal_user() || ($facultyPk && ! hasRole('Student-OT'))) {

            if ($seesAllCourses || $facultyPk) {
                $source1Students = collect([]);
                // "spk_coursePk" => true for every (student, course) pair that is
                // this viewer's PARTICIPANT roster: coordinated-course enrolments
                // plus the students of the course groups they own.
                $participantRosterKeys = [];

                // Course set feeding the primary (enrollment) student source.
                if ($seesAllCourses) {
                    $activeCoordinatorCourses = CourseMaster::where('active_inactive', 1)
                        ->where('end_date', $dateOp, now()->toDateString())
                        ->pluck('pk');
                } else {
                    $coordinatorCourses = $this->getCoordinatorCourseIds($facultyPk);
                    $activeCoordinatorCourses = $coordinatorCourses->isNotEmpty()
                        ? CourseMaster::whereIn('pk', $coordinatorCourses)
                            ->where('active_inactive', 1)
                            ->where('end_date', $dateOp, now()->toDateString())
                            ->pluck('pk')
                        : collect([]);
                }

                if ($activeCoordinatorCourses->isNotEmpty()) {
                    $source1StudentMaps = StudentMasterCourseMap::with([
                        'studentMaster.cadre',
                        'course',
                    ])
                        ->whereIn('course_master_pk', $activeCoordinatorCourses)
                        ->where('active_inactive', 1)
                        ->get();

                    foreach ($source1StudentMaps as $studentMap) {
                        $stdObj = new \stdClass;
                        $stdObj->student_master_pk = $studentMap->student_master_pk;
                        $stdObj->course_master_pk = $studentMap->course_master_pk;
                        $stdObj->studentMaster = $studentMap->studentMaster;
                        $stdObj->course = $studentMap->course;
                        $stdObj->source = 'cc_acc';
                        $source1Students->push($stdObj);

                        // The exact (student, course) pairs that ARE the coordinated
                        // roster. The card counts these — see $participantRosterKeys.
                        $participantRosterKeys[$studentMap->student_master_pk . '_' . $studentMap->course_master_pk] = true;
                    }
                }

                $source2Students = collect([]);
                // Group-mapping source is faculty-specific; Super Admin already has
                // every student via source1, so skip it when there's no faculty pk.
                //
                // $coordinatedOnly callers (the OT participants page and the card that
                // opens it) DO want these: the students of a House / Counsellor group
                // this faculty owns are that faculty's own OTs, exactly as much as a
                // coordinated course's roster is. Leaving them out is what showed a
                // House Group warden 0 participants while Course Group Mapping
                // credited them with 43. Sources 3 (merely taught) and 4 (memo-only)
                // stay out — those students are not on any roster of theirs.
                $groupMappings = $facultyPk
                    ? DB::table('group_type_master_course_master_map')
                        ->where('facility_id', $facultyPk)
                        ->where('active_inactive', 1)
                        ->get()
                    : collect([]);

                if ($groupMappings->isNotEmpty()) {
                    $groupMapCourseIds = $groupMappings->pluck('course_name')->unique();
                    $activeCourseIds = CourseMaster::whereIn('pk', $groupMapCourseIds)
                        ->where('active_inactive', 1)
                        ->where('end_date', $dateOp, now()->toDateString())
                        ->pluck('pk');

                    if ($activeCourseIds->isNotEmpty()) {
                        $activeGroupMappingPks = $groupMappings
                            ->whereIn('course_name', $activeCourseIds)
                            ->pluck('pk')
                            ->unique();

                        if ($activeGroupMappingPks->isNotEmpty()) {
                            $source2GroupMaps = StudentCourseGroupMap::with([
                                'student.cadre',
                                'groupTypeMasterCourseMasterMap.courseGroup',
                                'groupTypeMasterCourseMasterMap.courseGroupType',
                                'groupTypeMasterCourseMasterMap.Faculty',
                            ])
                                ->whereIn('group_type_master_course_master_map_pk', $activeGroupMappingPks)
                                ->where('active_inactive', 1)
                                ->get();

                            foreach ($source2GroupMaps as $groupMap) {
                                $studentPk = $groupMap->student_master_pk;
                                $coursePk = $groupMap->groupTypeMasterCourseMasterMap->course_name ?? null;

                                if ($coursePk && $groupMap->student) {
                                    $course = $groupMap->groupTypeMasterCourseMasterMap->courseGroup ?? null;

                                    if ($course) {
                                        $studentMap = new \stdClass;
                                        $studentMap->student_master_pk = $studentPk;
                                        $studentMap->course_master_pk = $coursePk;
                                        $studentMap->studentMaster = $groupMap->student;
                                        $studentMap->course = $course;
                                        $studentMap->groupMapping = $groupMap;
                                        $studentMap->source = 'group_mapping';
                                        $source2Students->push($studentMap);

                                        // On the roster the "OT/ Participants Details"
                                        // card counts — the page it opens lists this
                                        // student now, so the two must agree.
                                        $participantRosterKeys[$studentPk . '_' . $coursePk] = true;
                                    }
                                }
                            }
                        }
                    }
                }

                // Source 3 — students of sessions the faculty TAUGHT. A faculty is
                // assigned to a timetable session via faculty_master / internal_faculty
                // (JSON PK arrays). If they neither coordinate the course (source1) nor
                // own the class group (source2), those students would otherwise be
                // invisible — so their own sessions' attendance never shows. Pull them
                // in here (the session-level scope in expandStudentRowsBySession then
                // keeps only this faculty's own sessions for such non-coordinated courses).
                $source3Students = collect([]);
                if ($facultyPk && ! $seesAllCourses && ! $coordinatedOnly) {
                    $taughtRows = DB::table('course_student_attendance as a')
                        ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
                        ->where(function ($q) use ($facultyPk) {
                            $q->whereRaw('JSON_CONTAINS(t.faculty_master, ?)', [json_encode((string) $facultyPk)])
                                ->orWhereRaw('FIND_IN_SET(?, t.faculty_master)', [$facultyPk])
                                ->orWhereRaw('JSON_CONTAINS(t.internal_faculty, ?)', [json_encode((string) $facultyPk)])
                                ->orWhereRaw('FIND_IN_SET(?, t.internal_faculty)', [$facultyPk]);
                        })
                        ->distinct()
                        ->get(['a.Student_master_pk as student_pk', 'a.course_master_pk as course_pk']);

                    if ($taughtRows->isNotEmpty()) {
                        $activeTaughtCourseIds = CourseMaster::whereIn('pk', $taughtRows->pluck('course_pk')->unique()->filter())
                            ->where('active_inactive', 1)
                            ->where('end_date', $dateOp, now()->toDateString())
                            ->pluck('pk');
                        $activeTaughtSet = $activeTaughtCourseIds->map(fn ($p) => (string) $p)->all();

                        if (! empty($activeTaughtSet)) {
                            $pairs = $taughtRows->filter(fn ($r) => in_array((string) $r->course_pk, $activeTaughtSet, true));
                            $taughtStudents = StudentMaster::with('cadre')
                                ->whereIn('pk', $pairs->pluck('student_pk')->unique()->filter())
                                ->get()->keyBy(fn ($m) => (string) $m->pk);
                            $taughtCourses = CourseMaster::whereIn('pk', $activeTaughtCourseIds)
                                ->get()->keyBy(fn ($c) => (string) $c->pk);

                            foreach ($pairs as $r) {
                                $student = $taughtStudents->get((string) $r->student_pk);
                                $course = $taughtCourses->get((string) $r->course_pk);
                                if ($student && $course) {
                                    $o = new \stdClass;
                                    $o->student_master_pk = $r->student_pk;
                                    $o->course_master_pk = $r->course_pk;
                                    $o->studentMaster = $student;
                                    $o->course = $course;
                                    $o->source = 'session_taught';
                                    $source3Students->push($o);
                                }
                            }
                        }
                    }
                }

                $seenStudentCourseKeys = [];
                $uniqueStudents = collect([]);

                foreach ($source2Students->concat($source1Students)->concat($source3Students) as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    $coursePk = $studentMap->course_master_pk ?? 0;
                    $studentCourseKey = $studentPk.'_'.$coursePk;

                    if (! in_array($studentCourseKey, $seenStudentCourseKeys, true)) {
                        $seenStudentCourseKeys[] = $studentCourseKey;
                        $uniqueStudents->push($studentMap);
                    }
                }

                // The per-student total_* counts + notice/memo lookups below are an
                // N+1 that only the student-list page needs; callers that compute
                // their own counts (e.g. the OT participants page via
                // otParticipantsRowMeta) pass $withTotals = false to skip it.
                $noticeMemoService = $withTotals ? app(OTNoticeMemoService::class) : null;

                // Time Period + Duty Type filters scope the count columns.
                $fromDate = $request?->input('from_date') ?: null;
                $toDate = $request?->input('to_date') ?: null;
                $dutyType = $request?->input('duty_type') ?: null;

                // Include students who have discipline memos but were dropped from the
                // active-course list (their course has expired or their enrolment is
                // inactive), so their memo history still surfaces here. Scoped to what
                // the current viewer is allowed to see.
                //
                // Student-list only. These students are not on any coordinated course
                // roster, so counting them as "participants" overstated the OT/
                // Participants Details card — it read 50 for a Super Admin whose only
                // active course has no enrolments at all.
                if (! $coordinatedOnly) {
                    $this->appendStudentsWithMemos($uniqueStudents, $seenStudentCourseKeys, $seesAllCourses, $facultyPk);
                }

                // Batch-load House Name (hostel room, keyed by student_master.user_id == hostel user_name)
                // and the latest attendance status per student (for Present/Absent).
                $studentPks = $uniqueStudents->pluck('student_master_pk')->filter()->unique()->values()->all();
                $userIds = $uniqueStudents
                    ->map(fn ($m) => $m->studentMaster->user_id ?? null)
                    ->filter()->unique()->values()->all();

                $houseByUser = ! empty($userIds)
                    ? DB::table('ot_hostel_room_details')
                        ->where('active_inactive', 1)
                        ->whereIn('user_name', $userIds)
                        ->pluck('hostel_room_name', 'user_name')
                    : collect();

                // Which rows are on the PARTICIPANT ROSTER — the exact (student,
                // course) pairs sources 1 and 2 were built from. The card counts
                // these, and the OT participants page lists exactly the same set.
                //
                // Matched on the pair, not on ->source and not on the course alone:
                //   - ->source is unreliable because the dedupe below keeps whichever
                //     source reached a pair first, so a student who is both on a
                //     coordinated course and in one of this faculty's groups is
                //     stored as 'group_mapping';
                //   - the course alone is unreliable because a 'session_taught' row
                //     carries the course whose attendance was marked, which can be a
                //     coordinated course even when the student is not enrolled in it.
                //     That is what made the card read 88 against a list of 85.

                // Cadre and House Group resolved from the Course Group Mapping, for
                // the OT participants page only — the student list still reads
                // student_master through the cadre relation.
                $emptyGroupMap = ['byStudentCourse' => [], 'byStudent' => []];
                $houseGroupTypePks = $withTotals ? [] : $this->houseGroupTypePks();
                $counsellorTypePks = $withTotals ? [] : $this->counsellorGroupTypePks();
                $participantCadres = $withTotals ? $emptyGroupMap : $this->resolveParticipantCadres($studentPks);
                // One map per group type — the name and the faculty of a pair always
                // come from the SAME mapping row, so the two columns cannot disagree.
                $participantCounsellorRows = $withTotals ? $emptyGroupMap : $this->resolveParticipantGroupRows($studentPks, $counsellorTypePks);
                $participantHouseRows = $withTotals ? $emptyGroupMap : $this->resolveParticipantGroupRows($studentPks, $houseGroupTypePks);

                // Every marked attendance session per student (one row per session is
                // produced after this loop). Scoped to the Time Period filter, so a
                // student with no session in range gets no session rows.
                //
                // Only the student list needs this. The OT participants page
                // ($withTotals = false) collapses straight back to one row per
                // student, so materialising every session there is pure waste — and
                // on the Archive tab (2.7k students / 70k+ sessions) it blew past
                // PHP's memory_limit and 500'd the page. See the compact
                // session_times attachment after this loop for the one thing that
                // page does need out of the session data.
                $attendanceSessions = $withTotals
                    ? $this->resolveStudentAttendanceSessions($studentPks, $fromDate, $toDate)
                    : [];

                foreach ($uniqueStudents as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    $coursePk = $studentMap->course_master_pk ?? null;

                    // House Name is cheap (batched above) and always needed.
                    $uid = $studentMap->studentMaster->user_id ?? null;
                    $studentMap->house_name = ($uid && isset($houseByUser[$uid])) ? $houseByUser[$uid] : null;

                    // Backs the "OT/ Participants Details" card — see the note where
                    // $participantRosterKeys is built.
                    $studentMap->is_participant = $coursePk !== null
                        && isset($participantRosterKeys[$studentPk . '_' . $coursePk]);

                    if (! $withTotals) {
                        // Cadre from the Course Group Mapping (see
                        // resolveParticipantCadres()). Only the OT participants page
                        // sources it this way; the student list keeps reading
                        // student_master via the cadre relation.
                        $studentMap->cadre_name = $this->participantCadreFor($studentMap, $participantCadres);
                        $studentMap->counsellor_name = $this->participantGroupValueFor($studentMap, $participantCounsellorRows, 'faculty');
                        // A student can sit in several house groups at once, so the
                        // column names all of them and the filter matches any. The
                        // faculty column is built from the SAME list in the SAME
                        // order — a group with no faculty holds its place with a
                        // dash, so the two columns always line up group-for-faculty.
                        $houseEntries = $this->participantGroupEntriesFor($studentMap, $participantHouseRows);
                        $studentMap->house_groups = array_column($houseEntries, 'name');
                        $studentMap->house_group = implode(', ', $studentMap->house_groups) ?: null;
                        $studentMap->house_faculty_name = $houseEntries
                            ? implode(', ', array_map(fn ($e) => $e['faculty'] ?: '—', $houseEntries))
                            : null;
                        continue; // caller computes its own counts / doesn't need group mapping
                    }

                    if (! isset($studentMap->groupMapping) && $coursePk) {
                        $groupMap = StudentCourseGroupMap::with([
                            'groupTypeMasterCourseMasterMap.courseGroupType',
                            'groupTypeMasterCourseMasterMap.Faculty',
                            'groupTypeMasterCourseMasterMap.courseGroup',
                        ])
                            ->where('student_master_pk', $studentPk)
                            ->where('active_inactive', 1)
                            ->whereHas('groupTypeMasterCourseMasterMap', function ($query) use ($coursePk) {
                                $query->where('course_name', $coursePk);
                            })
                            ->first();

                        $studentMap->groupMapping = $groupMap;
                    }

                    $studentMap->total_duty_count = MDOEscotDutyMap::where('selected_student_list', $studentPk)
                        ->when($dutyType, fn ($q) => $q->where('mdo_duty_type_master_pk', $dutyType))
                        ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
                        ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
                        ->count();
                    $studentMap->total_medical_exception_count = StudentMedicalExemption::where('student_master_pk', $studentPk)
                        ->where('active_inactive', 1)
                        ->count();
                    $studentMap->total_pt_exemption_count = LeaveApplication::where('student_master_pk', $studentPk)
                        ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
                        ->where('active_inactive', 1)
                        ->where('status', LeaveApplication::STATUS_APPROVED)
                        ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
                        ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
                        ->count();
                    $studentMap->total_stationed_leave_count = LeaveApplication::where('student_master_pk', $studentPk)
                        ->where('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE)
                        ->where('active_inactive', 1)
                        ->whereIn('status', [
                            LeaveApplication::STATUS_APPROVED,
                            LeaveApplication::STATUS_PENDING,
                        ])
                        ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
                        ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
                        ->count();

                    $notices = $noticeMemoService->getNotices($studentPk);
                    $memos = $noticeMemoService->getDisciplineMemos($studentPk);
                    $studentMap->total_notice_count = $notices->count();
                    $studentMap->total_memo_count = $memos->count();
                }

                // Faculty session scope: a plain session-teacher sees only the
                // sessions THEY conducted; a CC/ACC sees all sessions of the courses
                // they coordinate. Super Admin / Training authority (even with a
                // faculty pk) is unscoped.
                $sessionFacultyScope = $seesAllCourses ? null : $facultyPk;
                $coordinatorCourseIds = $sessionFacultyScope
                    ? $this->getCoordinatorCourseIds($sessionFacultyScope)->map(fn ($id) => (string) $id)->values()->all()
                    : [];

                if ($withTotals) {
                    // One row per marked attendance session (student totals repeat per row).
                    $uniqueStudents = $this->expandStudentRowsBySession($uniqueStudents, $attendanceSessions, $sessionFacultyScope, $coordinatorCourseIds);
                } else {
                    // The OT participants page keeps one row per student. The only
                    // thing it wants from the session data is the Session filter, so
                    // attach just the distinct class_session values per student —
                    // and only when that filter is actually set, so the common case
                    // reads no attendance rows at all.
                    $this->attachStudentSessionTimes(
                        $uniqueStudents,
                        (string) ($request?->input('session') ?? ''),
                        $sessionFacultyScope,
                        $coordinatorCourseIds
                    );
                }

                $students = $uniqueStudents->filter(function ($studentMap) {
                    return ! empty($studentMap->studentMaster);
                })->values();

                $availableCourses = $students->pluck('course')
                    ->filter(function ($course) use ($archive) {
                        return $course
                            && isset($course->active_inactive)
                            && $course->active_inactive == 1
                            && isset($course->end_date)
                            && ($archive
                                ? Carbon::parse($course->end_date)->startOfDay()->lt(now()->startOfDay())
                                : Carbon::parse($course->end_date)->startOfDay()->gte(now()->startOfDay()));
                    })
                    ->unique('pk')
                    ->map(function ($course) {
                        return [
                            'pk' => $course->pk,
                            'course_name' => $course->course_name,
                        ];
                    })
                    ->values()
                    ->sortBy('course_name');
            }
        } elseif (hasRole('Super Admin')) {
            $activeCourseIds = CourseMaster::where('active_inactive', 1)
                ->where('end_date', $dateOp, now()->toDateString())
                ->pluck('pk');

            $superAdminStudentMaps = StudentMasterCourseMap::with([
                'studentMaster.cadre',
                'course',
            ])
                ->whereIn('course_master_pk', $activeCourseIds)
                ->where('active_inactive', 1)
                ->get();

            $seenStudentCourseKeys = [];
            $uniqueStudents = collect([]);

            foreach ($superAdminStudentMaps as $studentMap) {
                $stdObj = new \stdClass;
                $stdObj->student_master_pk = $studentMap->student_master_pk;
                $stdObj->course_master_pk = $studentMap->course_master_pk;
                $stdObj->studentMaster = $studentMap->studentMaster;
                $stdObj->course = $studentMap->course;
                $stdObj->source = 'super_admin';

                $key = $stdObj->student_master_pk.'_'.($stdObj->course_master_pk ?? 0);
                if (! in_array($key, $seenStudentCourseKeys, true)) {
                    $seenStudentCourseKeys[] = $key;
                    $uniqueStudents->push($stdObj);
                }
            }

            $uniqueStudents = $this->augmentStudentListEntries($uniqueStudents, $request);

            $students = $uniqueStudents->filter(fn ($m) => ! empty($m->studentMaster))->values();

            $availableCourses = $students->pluck('course')
                ->filter(function ($course) use ($archive) {
                    return $course
                        && isset($course->active_inactive)
                        && $course->active_inactive == 1
                        && isset($course->end_date)
                        && ($archive
                            ? Carbon::parse($course->end_date)->startOfDay()->lt(now()->startOfDay())
                            : Carbon::parse($course->end_date)->startOfDay()->gte(now()->startOfDay()));
                })
                ->unique('pk')
                ->map(fn ($c) => ['pk' => $c->pk, 'course_name' => $c->course_name])
                ->values()
                ->sortBy('course_name');
        }

        return compact('students', 'availableCourses', 'facultyPk');
    }

    /**
     * Append students who have discipline memos but are missing from the active-course
     * list (their course has expired, or their enrolment map is inactive). Each is given
     * course context from their most recent enrolment so the row renders normally. The
     * caller's existing per-student loop then fills in memo/attendance/other counts.
     *
     * Super Admin sees every memo student; a faculty only sees memo students within
     * their remit (canFacultyViewStudent).
     *
     * @param  array<int, string>  $seenStudentCourseKeys
     * @param  int|null  $facultyPk
     */
    private function appendStudentsWithMemos(Collection $uniqueStudents, array &$seenStudentCourseKeys, bool $isSuperAdmin, $facultyPk): void
    {
        // Students carrying discipline memos (discipline_memo_status), scoped to
        // active disciplines — same source as /memo/discipline and the memo counts.
        $memoStudentPks = DB::table('discipline_memo_status as dms')
            ->join('discipline_master as dm', 'dms.discipline_master_pk', '=', 'dm.pk')
            ->where('dm.active_inactive', 1)
            ->distinct()
            ->pluck('dms.student_master_pk')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->all();

        if (empty($memoStudentPks)) {
            return;
        }

        // Students already on the list (any course row) — skip them.
        $existingPks = $uniqueStudents
            ->pluck('student_master_pk')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->flip();

        foreach ($memoStudentPks as $studentPk) {
            if (isset($existingPks[$studentPk])) {
                continue;
            }

            // Faculty may only see memo students within their remit.
            if (! $isSuperAdmin && $facultyPk && ! $this->canFacultyViewStudent($facultyPk, $studentPk)) {
                continue;
            }

            // Most recent enrolment (any status) provides the course context to display.
            $courseMap = StudentMasterCourseMap::with(['studentMaster.cadre', 'course'])
                ->where('student_master_pk', $studentPk)
                ->orderByDesc('pk')
                ->first();

            $student = $courseMap?->studentMaster ?? StudentMaster::with('cadre')->find($studentPk);
            if (! $student) {
                continue;
            }

            $stdObj = new \stdClass;
            $stdObj->student_master_pk = $studentPk;
            $stdObj->course_master_pk = $courseMap->course_master_pk ?? null;
            $stdObj->studentMaster = $student;
            $stdObj->course = $courseMap?->course;
            $stdObj->source = 'memo';

            $key = $studentPk.'_'.($stdObj->course_master_pk ?? 0);
            if (in_array($key, $seenStudentCourseKeys, true)) {
                continue;
            }

            $seenStudentCourseKeys[] = $key;
            $uniqueStudents->push($stdObj);
        }
    }

    private function augmentStudentListEntries(Collection $uniqueStudents, ?Request $request = null): Collection
    {
        $noticeMemoService = app(OTNoticeMemoService::class);

        $fromDate = $request?->input('from_date') ?: null;
        $toDate = $request?->input('to_date') ?: null;
        $dutyType = $request?->input('duty_type') ?: null;

        $studentPks = $uniqueStudents->pluck('student_master_pk')->filter()->unique()->values()->all();
        $userIds = $uniqueStudents
            ->map(fn ($m) => $m->studentMaster->user_id ?? null)
            ->filter()->unique()->values()->all();

        $houseByUser = ! empty($userIds)
            ? DB::table('ot_hostel_room_details')
                ->where('active_inactive', 1)
                ->whereIn('user_name', $userIds)
                ->pluck('hostel_room_name', 'user_name')
            : collect();

        // Every marked attendance session per student (one row per session is
        // produced below). Scoped to the Time Period filter.
        $attendanceSessions = $this->resolveStudentAttendanceSessions($studentPks, $fromDate, $toDate);

        foreach ($uniqueStudents as $studentMap) {
            $studentPk = $studentMap->student_master_pk;
            $coursePk = $studentMap->course_master_pk ?? null;

            if (! isset($studentMap->groupMapping) && $coursePk) {
                $groupMap = StudentCourseGroupMap::with([
                    'groupTypeMasterCourseMasterMap.courseGroupType',
                    'groupTypeMasterCourseMasterMap.Faculty',
                    'groupTypeMasterCourseMasterMap.courseGroup',
                ])
                    ->where('student_master_pk', $studentPk)
                    ->where('active_inactive', 1)
                    ->whereHas('groupTypeMasterCourseMasterMap', function ($query) use ($coursePk) {
                        $query->where('course_name', $coursePk);
                    })
                    ->first();

                $studentMap->groupMapping = $groupMap;
            }

            $studentMap->total_duty_count = MDOEscotDutyMap::where('selected_student_list', $studentPk)
                ->when($dutyType, fn ($q) => $q->where('mdo_duty_type_master_pk', $dutyType))
                ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
                ->count();
            $studentMap->total_medical_exception_count = StudentMedicalExemption::where('student_master_pk', $studentPk)
                ->where('active_inactive', 1)
                ->count();
            $studentMap->total_pt_exemption_count = LeaveApplication::where('student_master_pk', $studentPk)
                ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
                ->where('active_inactive', 1)
                ->where('status', LeaveApplication::STATUS_APPROVED)
                ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
                ->count();
            $studentMap->total_stationed_leave_count = LeaveApplication::where('student_master_pk', $studentPk)
                ->where('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE)
                ->where('active_inactive', 1)
                ->whereIn('status', [
                    LeaveApplication::STATUS_APPROVED,
                    LeaveApplication::STATUS_PENDING,
                ])
                ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
                ->count();

            $notices = $noticeMemoService->getNotices($studentPk);
            $memos = $noticeMemoService->getDisciplineMemos($studentPk);
            $studentMap->total_notice_count = $notices->count();
            $studentMap->total_memo_count = $memos->count();

            $uid = $studentMap->studentMaster->user_id ?? null;
            $studentMap->house_name = ($uid && isset($houseByUser[$uid])) ? $houseByUser[$uid] : null;
        }

        // One row per marked attendance session (student totals repeat per row).
        return $this->expandStudentRowsBySession($uniqueStudents, $attendanceSessions);
    }

    /**
     * Cascading Session / Topic dropdown options for the student list filter panel.
     *
     * The filters cascade off the selected Time Period: first a date range is
     * picked, then the Session dropdown is scoped to that range, then the Topic
     * dropdown is scoped to the range AND the chosen Session. With no date range
     * selected there is nothing to cascade from, so both come back empty. Row-level
     * scope (faculty / course / date) is still enforced when a filter is applied.
     *
     * @param  array<int, int>  $studentPks
     * @return array{0: Collection, 1: Collection} [sessionOptions, topicOptions]
     */
    private function dashboardStudentListFilterOptions(array $studentPks, ?string $fromDate, ?string $toDate, string $sessionValue = ''): array
    {
        // No date range → nothing to map. Keep both dropdowns empty.
        if (! $fromDate || ! $toDate) {
            return [collect(), collect()];
        }

        $sessionOptions = $this->resolveScopedSessionOptions($studentPks, $fromDate, $toDate);
        $topicOptions = $this->resolveScopedTopicOptions(
            $studentPks,
            $fromDate,
            $toDate,
            $sessionValue !== '' ? $sessionValue : null
        );

        return [$sessionOptions, $topicOptions];
    }

    /**
     * OT / Participant dropdown options, mapped to the selected Course so the OT
     * list only shows participants belonging to the chosen course. With no course
     * selected every in-scope participant is offered. Each option is a {pk, label}
     * pair — pk is the student, label is "OT code — Name".
     *
     * @param  Collection  $students
     * @return Collection
     */
    private function dashboardStudentListParticipantOptions($students, Request $request)
    {
        $courseId = (string) $request->input('course_id', '');

        return $students
            ->filter(function ($m) use ($courseId) {
                if (! $m->studentMaster) {
                    return false;
                }
                if ($courseId !== '' && (string) ($m->course->pk ?? '') !== $courseId) {
                    return false;
                }

                return true;
            })
            ->map(function ($m) {
                $s = $m->studentMaster;
                $name = $s->display_name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? ''));
                $code = $s->generated_OT_code ?? '';

                return (object) [
                    'pk' => (string) $s->pk,
                    'label' => trim(($code !== '' ? $code.' — ' : '').$name),
                ];
            })
            ->unique('pk')->sortBy('label')->values();
    }

    /**
     * Distinct class-session time slots for the given students within the selected
     * Time Period (Session filter dropdown). Scoped to [$fromDate, $toDate].
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedSessionOptions(array $studentPks, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return $this->resolveScopedTimetableOptions($studentPks, 'class_session', $fromDate, $toDate);
    }

    /**
     * Distinct session topics for the given students within the selected Time
     * Period, optionally narrowed to a single Session (class_session). Backs the
     * Topic filter dropdown, which cascades off date range → session.
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedTopicOptions(array $studentPks, ?string $fromDate = null, ?string $toDate = null, ?string $sessionValue = null): Collection
    {
        return $this->resolveScopedTimetableOptions($studentPks, 'subject_topic', $fromDate, $toDate, $sessionValue);
    }

    /**
     * Distinct non-empty values of a timetable column for the sessions the given
     * students have attendance for, scoped to a date range (and optionally a single
     * class_session). Backs the cascading Session / Topic filter dropdowns.
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedTimetableOptions(array $studentPks, string $column, ?string $fromDate = null, ?string $toDate = null, ?string $sessionValue = null): Collection
    {
        if (empty($studentPks)) {
            return collect();
        }

        return DB::table('course_student_attendance as a')
            ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
            ->whereIn('a.Student_master_pk', $studentPks)
            ->whereNotNull("t.$column")
            ->where("t.$column", '<>', '')
            ->when($fromDate, fn ($q) => $q->whereDate('t.START_DATE', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('t.START_DATE', '<=', $toDate))
            ->when(
                $sessionValue !== null && $sessionValue !== '',
                fn ($q) => $q->where('t.class_session', $sessionValue)
            )
            ->distinct()
            ->orderBy("t.$column")
            ->pluck("t.$column")
            ->values();
    }

    /**
     * Resolve every marked attendance session per student, newest first.
     *
     * Attendance is recorded per timetable session (course_student_attendance,
     * keyed by timetable_pk), so a student can have many sessions. Each entry
     * carries that session's date/time/topic, its raw status code and a derived
     * present flag (Absent only when status == 3). The dashboard student list
     * renders ONE ROW PER SESSION, so every session the student was marked in is
     * shown — not just the latest.
     *
     * When a date range is supplied, only sessions whose timetable START_DATE
     * falls within [$fromDate, $toDate] are returned (Time Period filter).
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, array<int, array<string, mixed>>> spk => list of sessions
     */
    private function resolveStudentAttendanceSessions(array $studentPks, ?string $fromDate = null, ?string $toDate = null): array
    {
        if (empty($studentPks)) {
            return [];
        }

        // Every attendance row for these students, joined to its timetable session
        // (date / time / topic). Newest session first. Scoped to the selected
        // Time Period (event date) when one is active.
        return DB::table('course_student_attendance as a')
            ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
            ->whereIn('a.Student_master_pk', $studentPks)
            ->when($fromDate, fn ($q) => $q->whereDate('t.START_DATE', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('t.START_DATE', '<=', $toDate))
            ->orderByDesc('t.START_DATE')
            ->orderByDesc('a.pk')
            ->get([
                'a.Student_master_pk as spk',
                'a.pk as attendance_pk',
                'a.status',
                'a.other_exemption_comments',
                'a.timetable_pk',
                'a.course_master_pk',
                't.START_DATE as session_date',
                't.class_session as session_time',
                't.subject_topic as session_topic',
                't.faculty_master as session_faculty_master',
                't.internal_faculty as session_internal_faculty',
            ])
            ->groupBy('spk')
            ->map(function ($sessions) {
                return $sessions->map(fn ($r) => [
                    'attendance_pk' => (int) $r->attendance_pk,
                    'timetable_pk' => $r->timetable_pk,
                    'course_master_pk' => $r->course_master_pk,
                    'status' => $r->status,
                    'present' => (int) $r->status !== 3,
                    'other_exemption_comments' => $r->other_exemption_comments ?? null,
                    'session_date' => $r->session_date ?? null,
                    'session_time' => $r->session_time ?? null,
                    'session_topic' => $r->session_topic ?? null,
                    'session_faculty_master' => $r->session_faculty_master ?? null,
                    'session_internal_faculty' => $r->session_internal_faculty ?? null,
                ])->values()->all();
            })
            ->all();
    }

    /**
     * Expand one-row-per-student into one-row-per-session.
     *
     * Every student/course entry is cloned once per marked attendance session
     * (scoped to that entry's own course), carrying the session's date/time/topic
     * and status; the per-student totals (duty / medical / PT / stationed / notice
     * / memo) simply repeat on each of that student's session rows. A student with
     * no marked session keeps a single roster row (present by default) and is
     * flagged has_session_in_range = false so the Time Period filter drops it.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $attendanceSessions
     */
    private function expandStudentRowsBySession(Collection $uniqueStudents, array $attendanceSessions, $facultyScopePk = null, array $coordinatorCourseIds = []): Collection
    {
        $expanded = collect();

        // Tracks which attendance sessions have already been rendered for each
        // student, so the same session never repeats across two of that
        // student's roster rows once the course fallback below kicks in.
        $emittedByStudent = [];

        foreach ($uniqueStudents as $studentMap) {
            $studentPk = $studentMap->student_master_pk;
            $coursePk = $studentMap->course_master_pk ?? null;

            $sessions = $attendanceSessions[$studentPk] ?? [];

            // Prefer this enrolment row's own course so a student enrolled in
            // several courses shows each course's sessions on its own row. But
            // the enrolment course and the course attendance was actually marked
            // under do NOT always match in the data — so when this row's course
            // has no sessions of its own, fall back to the student's remaining
            // sessions instead of dropping them. Dropping them wrongly showed the
            // student as an unmarked "Present" default with empty
            // Session / Topic / Faculty (the N/A rows).
            if ($coursePk !== null && (int) $coursePk !== 0) {
                $courseSessions = array_values(array_filter($sessions, function ($s) use ($coursePk) {
                    return (string) ($s['course_master_pk'] ?? '') === (string) $coursePk;
                }));
                if (! empty($courseSessions)) {
                    $sessions = $courseSessions;
                }
            }

            // Never render a session already shown on an earlier row for this
            // student (guards the fallback above against duplicating sessions
            // across a multi-course student's rows).
            $already = $emittedByStudent[$studentPk] ?? [];
            if (! empty($already)) {
                $sessions = array_values(array_filter($sessions, function ($s) use ($already) {
                    return ! in_array((int) ($s['attendance_pk'] ?? 0), $already, true);
                }));
            }

            // Faculty session scope: for a course the faculty only TEACHES (not
            // CC/ACC), keep just the sessions they themselves conducted. For
            // courses they coordinate (CC/ACC) — and for Super Admin
            // ($facultyScopePk null) — all sessions stay.
            if ($facultyScopePk !== null) {
                $isCoordinatedCourse = $coursePk !== null
                    && in_array((string) $coursePk, $coordinatorCourseIds, true);
                if (! $isCoordinatedCourse) {
                    $sessions = array_values(array_filter($sessions, function ($s) use ($facultyScopePk) {
                        return $this->sessionHasFaculty($s, $facultyScopePk);
                    }));
                }
            }

            if (empty($sessions)) {
                $studentMap->attendance_present = true;
                $studentMap->attendance_status = null;
                $studentMap->other_exemption_comments = null;
                $studentMap->session_date = null;
                $studentMap->session_time = null;
                $studentMap->session_topic = null;
                $studentMap->session_faculty_master = null;
                $studentMap->session_internal_faculty = null;
                $studentMap->session_timetable_pk = null;
                $studentMap->session_course_pk = null;
                $studentMap->has_session_in_range = false;
                $expanded->push($studentMap);

                continue;
            }

            foreach ($sessions as $session) {
                $row = clone $studentMap;
                $row->attendance_present = $session['present'];
                $row->attendance_status = $session['status'];
                $row->other_exemption_comments = $session['other_exemption_comments'] ?? null;
                $row->session_date = $session['session_date'];
                $row->session_time = $session['session_time'];
                $row->session_topic = $session['session_topic'];
                $row->session_faculty_master = $session['session_faculty_master'] ?? null;
                $row->session_internal_faculty = $session['session_internal_faculty'] ?? null;
                // The session and the course its attendance was marked under: what
                // dashboardSessionCoverage() asks OtExemptionResolver about.
                $row->session_timetable_pk = $session['timetable_pk'] ?? null;
                $row->session_course_pk = $session['course_master_pk'] ?? null;
                $row->has_session_in_range = true;
                $expanded->push($row);
                $emittedByStudent[$studentPk][] = (int) ($session['attendance_pk'] ?? 0);
            }
        }

        return $expanded;
    }

    /**
     * Attach the distinct class_session values each student has attendance for,
     * as $studentMap->session_times (an array).
     *
     * The lightweight counterpart to expandStudentRowsBySession() for callers that
     * keep one row per student (the OT participants page). That page collapses its
     * rows by student anyway, so it needs the SET of a student's session slots, not
     * a row per session — and reading the set costs a fraction of the memory.
     *
     * Only runs when the Session filter is actually set: with no filter there is
     * nothing to match against, so no attendance rows are read at all. The rows are
     * left without the property in that case, and applyDashboardStudentListFilters()
     * falls back to its per-row session_time check.
     *
     * Faculty scope mirrors expandStudentRowsBySession(): for a course the faculty
     * merely TEACHES (does not coordinate), only the sessions they themselves
     * conducted count towards the student's set.
     *
     * @param  \Illuminate\Support\Collection  $uniqueStudents
     * @param  array<int, string>  $coordinatorCourseIds
     */
    private function attachStudentSessionTimes(\Illuminate\Support\Collection $uniqueStudents, string $sessionValue, $facultyScopePk = null, array $coordinatorCourseIds = []): void
    {
        if ($sessionValue === '') {
            return;
        }

        $studentPks = $uniqueStudents->pluck('student_master_pk')->filter()->unique()->values()->all();
        if (empty($studentPks)) {
            return;
        }

        // Narrowed to the selected slot, so this stays a small result set even for
        // the Archive tab's full student body.
        $rows = DB::table('course_student_attendance as a')
            ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
            ->whereIn('a.Student_master_pk', $studentPks)
            ->where('t.class_session', $sessionValue)
            ->distinct()
            ->get([
                'a.Student_master_pk as spk',
                'a.course_master_pk',
                't.class_session as session_time',
                't.faculty_master as session_faculty_master',
                't.internal_faculty as session_internal_faculty',
            ]);

        $timesByStudent = [];
        foreach ($rows as $r) {
            if ($facultyScopePk !== null) {
                $isCoordinatedCourse = in_array((string) $r->course_master_pk, $coordinatorCourseIds, true);
                if (! $isCoordinatedCourse && ! $this->sessionHasFaculty([
                    'session_faculty_master' => $r->session_faculty_master ?? null,
                    'session_internal_faculty' => $r->session_internal_faculty ?? null,
                ], $facultyScopePk)) {
                    continue;
                }
            }

            $timesByStudent[$r->spk][(string) $r->session_time] = true;
        }

        foreach ($uniqueStudents as $studentMap) {
            $studentMap->session_times = array_keys($timesByStudent[$studentMap->student_master_pk] ?? []);
        }
    }

    /**
     * Does a resolved session belong to the given faculty? Checks the timetable's
     * faculty_master / internal_faculty JSON PK arrays carried on the session.
     */
    private function sessionHasFaculty(array $session, $facultyPk): bool
    {
        $pk = (int) $facultyPk;
        if ($pk === 0) {
            return false;
        }

        foreach (['session_faculty_master', 'session_internal_faculty'] as $key) {
            $raw = $session[$key] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }
            $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $id) {
                    if (is_numeric($id) && (int) $id === $pk) {
                        return true;
                    }
                }
            } elseif (is_numeric($raw) && (int) $raw === $pk) {
                return true;
            }
        }

        return false;
    }

    private function applyDashboardStudentListFilters($students, Request $request, bool $applySessionDateFilter = true, bool $applySearch = true)
    {
        // Every filter below is compared as a string, so an array-valued query
        // parameter (?cadre[]=x) reads as "not set" instead of throwing
        // "Array to string conversion" (PR #334 F-025 family).
        $scalar = fn (string $key, $default = null) => is_scalar($v = $request->input($key, $default)) ? $v : $default;

        $courseId = $scalar('course_id');
        $roleFilter = $scalar('role_filter');
        $counsellorFaculty = $scalar('counsellor_faculty');
        $groupPk = $scalar('group_pk');
        $cadre = $scalar('cadre');
        $house = $scalar('house');
        $houseGroup = (string) $scalar('house_group', '');
        $session = (string) $scalar('session', '');
        $topic = (string) $scalar('topic', '');
        $participant = (string) $scalar('participant', '');
        // The DataTables search box. Callers that run their own search over the
        // columns THEY render (e.g. the OT participants page, whose House Group /
        // Duty Type / count columns don't exist here) pass $applySearch = false so
        // this narrower haystack doesn't drop their rows first.
        $searchInput = $applySearch ? $request->input('search', '') : '';
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        // ?search[value][]= — not a search term.
        $search = is_scalar($searchValue) ? strtolower(trim((string) $searchValue)) : '';

        // Time Period (event date) filter: when a range is selected, only students who
        // have a timetable session/event within that range are kept. If no event exists
        // on the chosen day(s), the list ends up empty ("Data not found.").
        // Callers that only want the Time Period to scope count columns (not drop
        // students) pass $applySessionDateFilter = false.
        $hasSessionDateFilter = $applySessionDateFilter
            && $request->filled('from_date') && $request->filled('to_date');

        return $students->filter(function ($studentMap) use ($courseId, $roleFilter, $counsellorFaculty, $groupPk, $cadre, $house, $houseGroup, $session, $topic, $participant, $search, $hasSessionDateFilter) {
            $student = $studentMap->studentMaster;
            $course = $studentMap->course;
            $counsellorTypePk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->type_name ?? '');
            $rowFacilityId = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->facility_id ?? '');
            $rowGroupPk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->pk ?? '');
            $rowCourseId = (string) ($course->pk ?? '');

            if ($hasSessionDateFilter && ! ($studentMap->has_session_in_range ?? false)) {
                return false;
            }

            // Session (class-session time slot) filter. Rows expanded per session
            // carry a single session_time; rows kept one-per-student (the OT
            // participants page) carry the SET of their slots as session_times
            // instead — see attachStudentSessionTimes().
            if ($session !== '') {
                $sessionTimes = $studentMap->session_times ?? null;

                if (is_array($sessionTimes)) {
                    if (! in_array($session, $sessionTimes, true)) {
                        return false;
                    }
                } elseif ((string) ($studentMap->session_time ?? '') !== $session) {
                    return false;
                }
            }

            // Topic (session subject/topic) filter.
            if ($topic !== '' && (string) ($studentMap->session_topic ?? '') !== $topic) {
                return false;
            }

            // OT / Participant filter (a specific student).
            if ($participant !== '' && (string) ($student->pk ?? '') !== $participant) {
                return false;
            }

            if ($courseId && $rowCourseId !== (string) $courseId) {
                return false;
            }

            // Rows carrying a resolved cadre_name (the OT participants page, sourced
            // from the Course Group Mapping) filter on that; everything else keeps
            // reading student_master through the cadre relation.
            if ($cadre) {
                $rowCadre = $studentMap->cadre_name ?? ($student->cadre->cadre_name ?? '');
                if ((string) $rowCadre !== (string) $cadre) {
                    return false;
                }
            }

            // House Group (Course Group Mapping), distinct from the House Name
            // hostel-room filter below. Matches ANY of the student's house groups —
            // a student in both "Nanda Devi" and "A" is found under either.
            if ($houseGroup !== '') {
                $groups = $studentMap->house_groups ?? null;
                if (is_array($groups)) {
                    if (! in_array($houseGroup, $groups, true)) {
                        return false;
                    }
                } elseif ((string) ($studentMap->house_group ?? '') !== $houseGroup) {
                    return false;
                }
            }

            // House. On the house view the row's house is its House Group off the
            // Course Group Mapping page (see facultyGroupRows()), which is what the
            // dropdown offers there; everywhere else it is the hostel room.
            if ($house) {
                $rowHouse = $studentMap->house_group_name ?? ($studentMap->house_name ?? '');

                if ((string) $rowHouse !== (string) $house) {
                    return false;
                }
            }

            if ($roleFilter === 'cc_acc') {
                if ($counsellorTypePk === '') {
                    return false;
                }
                // Optional narrowing to a specific CC/ACC faculty (counsellor).
                if ($counsellorFaculty !== null && $counsellorFaculty !== ''
                    && $rowFacilityId !== (string) $counsellorFaculty) {
                    return false;
                }
            } elseif ($roleFilter !== null && $roleFilter !== '') {
                if ($counsellorTypePk !== (string) $roleFilter) {
                    return false;
                }
            }

            if ($groupPk && $rowGroupPk !== (string) $groupPk) {
                return false;
            }

            if ($search !== '') {
                $name = strtolower(trim((string) (($student->display_name ?? '') ?: trim(($student->first_name ?? '').' '.($student->last_name ?? '')))));
                $otCode = strtolower((string) ($student->generated_OT_code ?? ''));
                $username = strtolower((string) ($student->user_id ?? ''));
                $email = strtolower((string) ($student->email ?? ''));
                $groupName = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->group_name ?? null;
                $cadre = strtolower((string) ($groupName ?: ($student->cadre->cadre_name ?? '')));
                $topic = strtolower((string) ($studentMap->session_topic ?? ''));
                $faculty = strtolower((string) $this->dashboardResolveSessionFaculty(
                    $studentMap->session_faculty_master ?? null,
                    $studentMap->session_internal_faculty ?? null
                ));

                if (
                    ! str_contains($name, $search)
                    && ! str_contains($otCode, $search)
                    && ! str_contains($username, $search)
                    && ! str_contains($email, $search)
                    && ! str_contains($cadre, $search)
                    && ! str_contains($topic, $search)
                    && ! str_contains($faculty, $search)
                ) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Split the per-session rows into the Present / Absent buckets for a
     * date-scoped attendance view (the Present/Absent Today cards, or any Time
     * Period filter). Only sessions GENUINELY MARKED (status != 0) within range are
     * kept; no-session default rows are dropped (not treated as "Present").
     *   - Present = every NON-absent marked session in range
     *   - Absent  = every absent (status 3) marked session in range
     * Rows stay ONE PER SESSION, matching the All tab: a student marked in two
     * sessions shows two rows. A student can appear in BOTH tabs (present in some
     * sessions, absent in others), and each row carries its own session date so the
     * Absent Reason resolves against that day.
     *
     * @return array{0: Collection, 1: Collection} [present, absent]
     */
    private function collapseDateScopedAttendance($rows): array
    {
        $present = collect();
        $absent = collect();

        // Which rows a duty/exemption covers. Resolved for the whole set up front,
        // with a fixed number of queries, so a session the OT was on duty for lands
        // in Present rather than being counted as an absence against them, the same
        // way the badge and AttendanceController::save treat it.
        $rowList = collect($rows)->values();
        $coverage = $this->dashboardSessionCoverage($rowList);
        $dutyCovered = [];
        foreach ($rowList as $i => $row) {
            if ($this->dashboardRowIsDutyPresent((int) ($row->attendance_status ?? 0), $coverage[$i] ?? false)) {
                $dutyCovered[spl_object_id($row)] = true;
            }
        }
        $isAbsentRow = fn ($m) => (int) $m->attendance_status === 3 && ! isset($dutyCovered[spl_object_id($m)]);

        foreach ($rows->groupBy('student_master_pk') as $spk => $group) {
            if (empty($spk)) {
                continue;
            }
            // Genuinely marked sessions in range for this student (status 0 = not
            // marked, and no-session default rows, are excluded).
            $marked = $group->filter(function ($m) {
                return ($m->has_session_in_range ?? false) === true
                    && $m->attendance_status !== null
                    && (int) $m->attendance_status !== 0;
            });
            if ($marked->isEmpty()) {
                continue;
            }

            // Present bucket: EVERY non-absent marked session — including an absence
            // a duty/exemption covers — so a student marked present in several
            // sessions of the range shows one row per session exactly like the All tab.
            foreach ($marked->reject($isAbsentRow) as $presentRow) {
                $presentRow->attendance_present = true;
                $present->push($presentRow);
            }

            // Absent bucket: EVERY uncovered absent (status 3) marked session. Each row
            // keeps its own session date so the Absent Reason resolves against that day.
            foreach ($marked->filter($isAbsentRow) as $absentRow) {
                $absentRow->attendance_present = false;
                $absent->push($absentRow);
            }
        }

        return [$present->values(), $absent->values()];
    }

    /**
     * Resolve the three attendance tab sets (All / Present / Absent) for the current
     * filters, applying the date-scoped one-row-per-student collapse and appending
     * PT/Stationed-leave absentees — the single source of truth shared by the live
     * DataTable and the export/report so both stay in sync with the on-screen view.
     *
     * @return array{0: Collection, 1: Collection, 2: Collection} [all, present, absent]
     */
    private function dashboardStudentListTabSets(Request $request, $students): array
    {
        // Apply the shared filters ONCE to the full set, then split for the tabs.
        $filteredAll = $this->applyDashboardStudentListFilters($students, $request)->values();

        // A date range (Present/Absent Today cards, or Time Period filter) switches
        // the Present/Absent tabs to one-row-per-student, marked-only buckets that
        // match the dashboard cards. Without dates the tabs stay empty (a date is
        // required for Present/Absent details).
        $dateScoped = $request->filled('from_date') || $request->filled('to_date');
        if (! $dateScoped) {
            return [$filteredAll, collect(), collect()];
        }

        [$presentAll, $absentAll] = $this->collapseDateScopedAttendance($filteredAll);

        // Students on PT Exemption / Stationed Leave during the window are absent
        // WITH a reason even when no attendance session was marked for them — add
        // them to the Absent list so the leave surfaces (one row per student).
        // Source them from the roster WITHOUT the session-date drop (but with all
        // other filters), since a leave student has no session in range.
        $rosterAll = $this->applyDashboardStudentListFilters($students, $request, false);
        $byStudent = [];
        foreach ($rosterAll as $m) {
            $spk = (int) ($m->student_master_pk ?? 0);
            if ($spk && ! isset($byStudent[$spk])) {
                $byStudent[$spk] = $m;
            }
        }
        $alreadyAbsent = [];
        foreach ($absentAll as $m) {
            $alreadyAbsent[(int) ($m->student_master_pk ?? 0)] = true;
        }
        $leaveAbsentees = $this->leaveBasedAbsentees(
            array_keys($byStudent),
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null
        );
        foreach ($leaveAbsentees as $spk => $coverDate) {
            if (isset($alreadyAbsent[$spk]) || ! isset($byStudent[$spk])) {
                continue;
            }
            $row = clone $byStudent[$spk];
            $row->attendance_present = false;
            $row->attendance_status = 3; // display as Absent
            $row->session_date = $coverDate;
            $row->session_time = null;
            $row->session_topic = null;
            $row->session_faculty_master = null;
            $row->session_internal_faculty = null;
            $row->session_timetable_pk = null;
            $row->session_course_pk = null;
            $row->has_session_in_range = true;
            $absentAll->push($row);
        }

        return [$filteredAll, $presentAll->values(), $absentAll->values()];
    }

    /**
     * Summary-card counts for the student list, derived from the SAME tab sets the
     * Present / Absent tabs render — so the cards can never disagree with the tabs
     * beneath them.
     *
     *   total         → distinct students on the roster the "OT/ Participants
     *                   Details" card links to: the page's filters applied, but NOT
     *                   the session-date drop, since that page lists every
     *                   participant regardless of the Time Period.
     *   present_today → DISTINCT STUDENTS in the Present tab set
     *   absent_today  → DISTINCT STUDENTS in the Absent tab set
     *
     * The tab counters count ROWS (one per marked session), the cards count PEOPLE;
     * both read the same collections, so a student marked in three sessions adds
     * three to the tab and one to the card.
     *
     * The window is whatever the Time Period filter holds, falling back to today
     * when no range is set. The cards previously hard-coded today regardless of the
     * filter, so selecting any other range left them reading a different day than
     * the table.
     *
     * @return array{0: array<string, int>, 1: array{0: string, 1: string}} [counts, [fromDate, toDate]]
     */
    private function dashboardStudentListCardCounts(
        Request $request,
        $students,
        $presentStudents,
        $absentStudents
    ): array {
        $today = now()->toDateString();
        $from = (string) ($request->input('from_date') ?: $today);
        $to = (string) ($request->input('to_date') ?: $from);

        $distinct = fn ($rows) => collect($rows)
            ->pluck('student_master_pk')
            ->filter()
            ->unique()
            ->count();

        // "OT/ Participants Details" counts the PARTICIPANT roster only — rows whose
        // course comes from course_coordinator_master or from a course group this
        // faculty owns, which is exactly what the OT participants page this card
        // opens lists. Merely-taught and memo-only rows are on the student list below
        // but are nobody's participants, so they are not counted.
        $coordinated = collect($students)->filter(fn ($m) => ($m->is_participant ?? false) === true);

        $counts = [
            'total' => $distinct($this->applyDashboardStudentListFilters($coordinated, $request, false)),
            'present_today' => $distinct($presentStudents),
            'absent_today' => $distinct($absentStudents),
        ];

        return [$counts, [$from, $to]];
    }

    /**
     * Server-side JSON for dashboard student list DataTables.
     */
    private function dashboardStudentListDataTableResponse(Request $request, $students)
    {
        $attendance = (string) $request->input('attendance', 'all');

        // Cascading Session / Topic dropdown options for the CURRENT date range and
        // selected session, so the front-end can rebuild the dropdowns on every
        // filter change (the dropdowns are otherwise only rendered at page load).
        $scopedStudentPks = $students->pluck('student_master_pk')->filter()->unique()->values()->all();
        [$sessionOptions, $topicOptions] = $this->dashboardStudentListFilterOptions(
            $scopedStudentPks,
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null,
            (string) $request->input('session', '')
        );

        // OT / Participant options mapped to the selected Course, so the dropdown
        // refreshes to that course's participants whenever the Course filter changes.
        $participantOptions = $this->dashboardStudentListParticipantOptions($students, $request);

        [$filteredAll, $presentAll, $absentAll] = $this->dashboardStudentListTabSets($request, $students);

        [$cardCounts, $cardWindow] = $this->dashboardStudentListCardCounts($request, $students, $presentAll, $absentAll);

        $counts = [
            'all' => $filteredAll->count(),
            'present' => $presentAll->count(),
            'absent' => $absentAll->count(),
        ];

        $rows = $attendance === 'present'
            ? $presentAll
            : ($attendance === 'absent' ? $absentAll : $filteredAll);

        // recordsTotal = attendance-tab size before the DataTables search box;
        // recordsFiltered = after all filters (search included above).
        $recordsTotal = $counts[$attendance] ?? $counts['all'];
        $recordsFiltered = $rows->count();

        // Column layout (DataTable column index → sort key).
        $columnMap = [
            0 => 'serial_no',
            // The OT code no longer has its own column — it renders under the name.
            1 => 'name',
            2 => 'course',
            3 => 'username',
            4 => 'cadre',
            5 => 'date',
            6 => 'session',
            7 => 'topic',
            8 => 'faculty',
            9 => 'status',
            10 => 'mdo',
            11 => 'escort',
            12 => 'other_exempt',
        ];

        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortKey = $columnMap[$orderCol] ?? 'serial_no';

        if ($sortKey !== 'serial_no') {
            $rows = $rows->sortBy(function ($studentMap) use ($sortKey) {
                return $this->dashboardStudentListSortValue($studentMap, $sortKey);
            }, SORT_NATURAL | SORT_FLAG_CASE, $orderDir === 'desc')->values();
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $pagedStudents = $length < 0
            ? $rows->slice($start)->values()
            : $rows->slice($start, $length)->values();

        // Absent Reason (shown only on the Absent tab): attendance stores no reason
        // field, so derive it from a leave / medical exemption that overlaps the
        // absent session's date. Batched for the current page.
        $absentReasons = $this->dashboardAbsentReasons($pagedStudents);

        // MDO / Escort / Medical / Other flags, mapped from the duty + medical tables
        // by student + session date. The attendance status code (4/5/6/7) alone does
        // not reflect a duty/exemption created separately (mdo_escot_duty_map), so we
        // cross-reference the source tables here. Batched for the current page.
        $dutyExemptionFlags = $this->dashboardDutyExemptionFlags($pagedStudents);

        // Whether a duty/exemption covers each row's session: the status rule, which
        // the date-only flags above are not.
        $sessionCoverage = $this->dashboardSessionCoverage($pagedStudents);

        // Carry the Time Period filter into the detail-page section links so the
        // opened section (MDO/Escort duty, Medical exemption) shows the same
        // date-scoped data as the list row.
        $linkDateQs = '';
        $fdParam = (string) $request->input('from_date', '');
        $tdParam = (string) $request->input('to_date', '');
        if ($fdParam !== '') {
            $linkDateQs .= '&from_date='.urlencode($fdParam);
        }
        if ($tdParam !== '') {
            $linkDateQs .= '&to_date='.urlencode($tdParam);
        }

        $data = [];
        foreach ($pagedStudents as $idx => $studentMap) {
            $student = $studentMap->studentMaster;
            if (! $student) {
                continue;
            }

            $detailUrl = route('admin.dashboard.students.detail', encrypt($student->pk));
            $displayName = $student->display_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
            $statusCode = (int) ($studentMap->attendance_status ?? 0);
            $isAbsent = ($studentMap->attendance_present ?? true) === false;

            // Show the column when EITHER the attendance status code marks it, OR a
            // matching duty / medical exemption is mapped for this student + date.
            $flags = $dutyExemptionFlags[$idx] ?? ['mdo' => false, 'escort' => false, 'medical' => false, 'other' => false];
            $showMdo = $statusCode === 4 || $flags['mdo'];
            $showEscort = $statusCode === 5 || $flags['escort'];
            $showMedical = $statusCode === 6 || $flags['medical'];
            $showOther = $statusCode === 7 || $flags['other'];
            $dutyPresent = $this->dashboardRowIsDutyPresent($statusCode, $sessionCoverage[$idx] ?? false);

            $data[] = [
                's_no' => $start + $idx + 1,
                // OT code has no column of its own; it sits under the name so the
                // listing stays narrower without losing the identifier.
                'name' => (function () use ($detailUrl, $displayName, $student) {
                    $html = '<a href="'.e($detailUrl).'" class="sl-count">'.e($displayName).'</a>';
                    $otCode = trim((string) ($student->generated_OT_code ?? ''));
                    if ($otCode !== '') {
                        $html .= '<div class="sl-ot-code">'.e($otCode).'</div>';
                    }

                    return $html;
                })(),
                // A faculty can be mapped to several courses, so each row names the
                // course it belongs to — otherwise a multi-course list is ambiguous.
                'course' => e($studentMap->course->course_name ?? 'N/A'),
                'username' => e($student->user_id ?? 'N/A'),
                'cadre' => e($student->cadre->cadre_name ?? 'N/A'),
                'date' => e($studentMap->session_date ? \Illuminate\Support\Carbon::parse($studentMap->session_date)->format('d M Y') : 'N/A'),
                'session' => e($studentMap->session_time ?: 'N/A'),
                'topic' => e($studentMap->session_topic ?: 'N/A'),
                'faculty' => e($this->dashboardResolveSessionFaculty(
                    $studentMap->session_faculty_master ?? null,
                    $studentMap->session_internal_faculty ?? null
                )),
                // Attendance status; for an absent student the reason (Stationed
                // Leave / PT Exemption / Medical Exemption, when one covers the day)
                // is shown right below the "Absent" badge so it's visible in the list.
                'status' => (function () use ($studentMap, $statusCode, $isAbsent, $absentReasons, $idx, $dutyPresent) {
                    $present = ($studentMap->attendance_present ?? true);
                    // A duty/exemption row is Present whatever the saved code says,
                    // and outranks the Late badge below: the MDO column on this very
                    // row already says the OT was on duty.
                    if ($dutyPresent) {
                        return '<span class="sl-status-badge sl-status-present">Present</span>';
                    }
                    // "Late" (status 2) is an attended-but-late state — the Present
                    // bucket already keeps it (status !== 3), so it appears in both
                    // the All and Present views. Show a distinct amber "Late" badge
                    // instead of the generic green "Present" so it's called out.
                    if ($statusCode === 2) {
                        $html = '<span class="sl-status-badge sl-status-late">Late</span>';
                    } else {
                        $html = '<span class="sl-status-badge '.($present ? 'sl-status-present' : 'sl-status-absent')
                            .'">'.($present ? 'Present' : 'Absent').'</span>';
                    }
                    $reason = $isAbsent ? ($absentReasons[$idx] ?? '-') : '-';
                    if ($isAbsent && $reason !== '-') {
                        $html .= '<div class="text-muted small mt-1">'.e($reason).'</div>';
                    }

                    return $html;
                })(),
                // MDO / Escort duties and Medical exemptions are clickable and open
                // the relevant section of the student's detail page (date-scoped).
                // "Other" (status 7) has no dedicated section, so it links to the
                // full detail page.
                'mdo' => $showMdo
                    ? '<a href="'.e($detailUrl.'?section=dutiesSection'.$linkDateQs).'" class="sl-count">MDO</a>'
                    : '-',
                'escort' => $showEscort
                    ? '<a href="'.e($detailUrl.'?section=dutiesSection'.$linkDateQs).'" class="sl-count">Escort</a>'
                    : '-',
                'other_exempt' => (function () use ($showMedical, $showOther, $studentMap, $detailUrl, $linkDateQs) {
                    if ($showMedical) {
                        return '<a href="'.e($detailUrl.'?section=medicalExceptionsSection'.$linkDateQs).'" class="sl-count">Medical</a>';
                    }
                    if ($showOther) {
                        return '<a href="'.e($detailUrl.($linkDateQs !== '' ? '?'.ltrim($linkDateQs, '&') : '')).'" class="sl-count">Other</a>';
                    }
                    // Inline Other Exemption from the mark-attendance screen (Absent +
                    // a typed reason) — show the reason text in this column.
                    $otherComment = trim((string) ($studentMap->other_exemption_comments ?? ''));
                    if ($otherComment !== '') {
                        return '<span class="text-muted small" title="Other Exemption">'.e($otherComment).'</span>';
                    }

                    return '-';
                })(),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'counts' => $counts,
            // Fresh summary-card counts + the window they cover, so the cards above
            // the table follow every filter change instead of staying on the value
            // rendered at page load.
            'cardCounts' => $cardCounts,
            'cardWindow' => ['from' => $cardWindow[0], 'to' => $cardWindow[1]],
            // Fresh cascading dropdown options for the current date range + session.
            'filterOptions' => [
                'session' => $sessionOptions->values()->all(),
                'topic' => $topicOptions->values()->all(),
                'participant' => $participantOptions->values()->all(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Derive an "Absent Reason" for each row on the current page. Attendance
     * itself records no reason, so we look for a PT Exemption / Stationed Leave
     * that covers the absent session's own date. Returns a label per row index
     * ("PT Exemption" / "Stationed Leave"), or "-" when nothing overlaps.
     * Medical Exemption is deliberately excluded — it has its own "Other
     * Exemptions" column, so showing it here too was redundant. Only absent rows
     * are considered.
     *
     * @param  Collection  $pagedStudents
     * @return array<int, string>
     */
    /**
     * Roster students who are on a PT Exemption / Stationed Leave that overlaps the
     * given window. Such a student is "absent with reason" for that day even when no
     * attendance session was marked for them, so they must still surface on the
     * Absent list. Returns [studentPk => a covered date (Y-m-d) inside the window],
     * which the Absent Reason lookup then resolves to "PT Exemption" / "Stationed Leave".
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, string>
     */
    private function leaveBasedAbsentees(array $studentPks, ?string $fromDate, ?string $toDate): array
    {
        if (empty($studentPks) || (! $fromDate && ! $toDate)) {
            return [];
        }
        $from = $fromDate ?: $toDate;
        $to = $toDate ?: $fromDate;

        $rows = DB::table('leave_application')
            ->whereIn('student_master_pk', $studentPks)
            ->where('active_inactive', 1)
            ->whereIn('leave_type', ['PT_EXEMPTION', 'STATIONED_LEAVE'])
            // Overlap: leave starts on/before the window end AND ends on/after its start.
            ->whereDate('from_date', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('to_date')->orWhereDate('to_date', '>=', $from);
            })
            ->orderBy('from_date')
            ->get(['student_master_pk', 'from_date']);

        $out = [];
        foreach ($rows as $r) {
            $spk = (int) $r->student_master_pk;
            if (isset($out[$spk])) {
                continue;
            }
            $leaveFrom = substr((string) $r->from_date, 0, 10);
            // A date inside the window that the leave covers (for the reason lookup).
            $out[$spk] = $leaveFrom >= $from ? $leaveFrom : $from;
        }

        return $out;
    }

    private function dashboardAbsentReasons(Collection $pagedStudents): array
    {
        $reasons = [];

        $pks = [];
        foreach ($pagedStudents as $idx => $m) {
            if (($m->attendance_present ?? true) === false && ! empty($m->student_master_pk)) {
                $pks[] = (int) $m->student_master_pk;
            }
        }
        $pks = array_values(array_unique($pks));
        if (empty($pks)) {
            return $reasons;
        }

        $leaves = DB::table('leave_application')
            ->whereIn('student_master_pk', $pks)
            ->where('active_inactive', 1)
            ->get(['student_master_pk', 'leave_type', 'from_date', 'to_date'])
            ->groupBy('student_master_pk');

        $covers = function ($from, $to, string $date): bool {
            if (empty($from)) {
                return false;
            }
            $f = \Illuminate\Support\Carbon::parse($from)->toDateString();
            if ($f > $date) {
                return false;
            }
            if (empty($to)) {
                return true; // open-ended
            }

            return \Illuminate\Support\Carbon::parse($to)->toDateString() >= $date;
        };

        foreach ($pagedStudents as $idx => $m) {
            if (($m->attendance_present ?? true) !== false) {
                continue;
            }
            $spk = (int) ($m->student_master_pk ?? 0);
            $date = ! empty($m->session_date) ? \Illuminate\Support\Carbon::parse($m->session_date)->toDateString() : null;
            $reason = '-';

            if ($spk && $date) {
                // Medical Exemption is intentionally NOT surfaced here — it already has
                // its own "Other Exemptions" column ("Medical"), so repeating it under
                // the Absent badge was redundant. Only leave-based reasons (PT Exemption
                // / Stationed Leave), which have no dedicated column, get a subtitle.
                foreach ($leaves[$spk] ?? [] as $r) {
                    if ($covers($r->from_date, $r->to_date, $date)) {
                        $reason = $r->leave_type === 'PT_EXEMPTION' ? 'PT Exemption'
                            : ($r->leave_type === 'STATIONED_LEAVE' ? 'Stationed Leave' : 'Leave');
                        break;
                    }
                }
            }

            $reasons[$idx] = $reason;
        }

        return $reasons;
    }

    /**
     * Per-row MDO / Escort / Medical / Other flags for the current page, mapped from
     * the duty and medical tables by student + session date.
     *
     * The dashboard's attendance status code (4/5/6/7) is only set when attendance
     * is marked with that status; a duty/exemption created separately via
     * mdo-escrot-exemption (mdo_escot_duty_map) or a medical exemption
     * (student_medical_exemption) is NOT reflected in that code. So we cross-reference
     * both source tables here, keyed by student pk + date, and OR the result into the
     * columns. Duty type (mdo_duty_type_master.name) decides MDO vs Escort vs Other.
     *
     * These flags fill the MDO / Escort / Other Exemptions COLUMNS only: they say a
     * duty or exemption exists that day, in any course and at any time. Whether it
     * makes the session Present is a stricter question (same course, overlapping
     * the session) and is answered by dashboardSessionCoverage() — so a row can show
     * an Escort duty that day and still be Absent for a session the duty missed.
     *
     * @return array<int, array{mdo: bool, escort: bool, medical: bool, other: bool}>
     */
    private function dashboardDutyExemptionFlags(Collection $pagedStudents): array
    {
        $flags = [];

        // Students on the current page that have a session date to match against.
        $pks = [];
        foreach ($pagedStudents as $m) {
            if (! empty($m->student_master_pk) && ! empty($m->session_date)) {
                $pks[] = (int) $m->student_master_pk;
            }
        }
        $pks = array_values(array_unique($pks));
        if (empty($pks)) {
            return $flags;
        }

        // MDO / Escort / Other duties for these students, keyed by "spk|Y-m-d".
        // selected_student_list carries the assigned OT pk; mdo_date is the duty day.
        $duties = DB::table('mdo_escot_duty_map as d')
            ->leftJoin('mdo_duty_type_master as m', 'd.mdo_duty_type_master_pk', '=', 'm.pk')
            ->whereIn('d.selected_student_list', $pks)
            ->whereNotNull('d.mdo_date')
            ->get(['d.selected_student_list as spk', 'd.mdo_date', 'm.mdo_duty_type_name as type']);

        $dutyMap = []; // "spk|date" => ['mdo'=>bool,'escort'=>bool,'other'=>bool]
        foreach ($duties as $r) {
            $date = substr((string) $r->mdo_date, 0, 10);
            $key = ((int) $r->spk).'|'.$date;
            if (! isset($dutyMap[$key])) {
                $dutyMap[$key] = ['mdo' => false, 'escort' => false, 'other' => false];
            }
            $type = strtolower(trim((string) ($r->type ?? '')));
            if ($type === 'mdo') {
                $dutyMap[$key]['mdo'] = true;
            } elseif ($type === 'escort') {
                $dutyMap[$key]['escort'] = true;
            } else {
                // "Other" duty type (or any type that isn't MDO/Escort) → Other column.
                $dutyMap[$key]['other'] = true;
            }
        }

        // Medical exemptions (date-range) → Medical in the Other Exemptions column.
        $medical = DB::table('student_medical_exemption')
            ->whereIn('student_master_pk', $pks)
            ->where('active_inactive', 1)
            ->get(['student_master_pk', 'from_date', 'to_date'])
            ->groupBy('student_master_pk');

        $covers = function ($from, $to, string $date): bool {
            if (empty($from)) {
                return false;
            }
            $f = \Illuminate\Support\Carbon::parse($from)->toDateString();
            if ($f > $date) {
                return false;
            }
            if (empty($to)) {
                return true; // open-ended
            }

            return \Illuminate\Support\Carbon::parse($to)->toDateString() >= $date;
        };

        foreach ($pagedStudents as $idx => $m) {
            $spk = (int) ($m->student_master_pk ?? 0);
            $date = ! empty($m->session_date) ? \Illuminate\Support\Carbon::parse($m->session_date)->toDateString() : null;
            $entry = ['mdo' => false, 'escort' => false, 'medical' => false, 'other' => false];

            if ($spk && $date) {
                if ($d = ($dutyMap[$spk.'|'.$date] ?? null)) {
                    $entry['mdo'] = $d['mdo'];
                    $entry['escort'] = $d['escort'];
                    $entry['other'] = $d['other'];
                }
                foreach ($medical[$spk] ?? [] as $r) {
                    if ($covers($r->from_date, $r->to_date, $date)) {
                        $entry['medical'] = true;
                        break;
                    }
                }
            }

            $flags[$idx] = $entry;
        }

        return $flags;
    }

    /**
     * Whether this row counts as Present on account of a duty or exemption, however
     * the attendance row was saved.
     *
     * Saved as MDO / Escort / Medical / Other (4–7), or $covered: a duty or exemption
     * covers the session by OtExemptionResolver's rule. The saved row can still hold
     * a Late/Absent marked before the duty was assigned, so the listing resolves it
     * rather than trusting the code.
     *
     * $covered comes from dashboardSessionCoverage(), not from the date-only column
     * flags: a duty on another course, or at a time the session does not overlap,
     * leaves the absence standing — as it does in AttendanceController::save,
     * My Counsellees and Student Detail (PR #334 F-062).
     */
    private function dashboardRowIsDutyPresent(int $statusCode, bool $covered): bool
    {
        return in_array($statusCode, [4, 5, 6, 7], true) || $covered;
    }

    /**
     * Per row (by position), whether a duty or exemption covers the row's session.
     *
     * Asks OtExemptionResolver::coveredSessions() — the rule isExempt() gives
     * AttendanceController::save: the same course, and an MDO / Escort / Other duty
     * or a medical exemption that overlaps the session. A row without a session
     * (no timetable, e.g. a leave-based absentee) is never covered.
     *
     * @return array<int, bool>
     */
    private function dashboardSessionCoverage(Collection $rows): array
    {
        $rows = $rows->values();
        $sessions = [];
        foreach ($rows as $row) {
            $timetablePk = (int) ($row->session_timetable_pk ?? 0);
            $coursePk = (int) ($row->session_course_pk ?? 0);
            $studentPk = (int) ($row->student_master_pk ?? 0);
            if ($timetablePk && $coursePk && $studentPk) {
                $sessions[] = ['student' => $studentPk, 'course' => $coursePk, 'timetable' => $timetablePk];
            }
        }

        $covered = OtExemptionResolver::coveredSessions($sessions);

        $coverage = [];
        foreach ($rows as $idx => $row) {
            $coverage[$idx] = isset($covered[OtExemptionResolver::sessionKey(
                (int) ($row->student_master_pk ?? 0),
                (int) ($row->session_course_pk ?? 0),
                (int) ($row->session_timetable_pk ?? 0)
            )]);
        }

        return $coverage;
    }

    /**
     * Human label for a session row's attendance status code
     * (1 Present, 2 Late, 3 Absent, 4 MDO, 5 Escort, 6 Medical Exempt,
     * 7 Other Exempt; 0/blank falls back to the present/absent flag).
     */
    private function dashboardAttendanceStatusLabel($studentMap): string
    {
        $status = $studentMap->attendance_status ?? null;
        $present = ($studentMap->attendance_present ?? true) ? 'Present' : 'Absent';

        if ($status === null || (int) $status === 0) {
            return $present;
        }

        return match ((int) $status) {
            1 => 'Present',
            2 => 'Late',
            3 => 'Absent',
            4 => 'MDO',
            5 => 'Escort',
            6 => 'Medical Exempt',
            7 => 'Other Exempt',
            default => $present,
        };
    }

    /**
     * Resolve the faculty name(s) for a session row from the timetable's
     * faculty_master / internal_faculty JSON PK arrays. Cached per PK-set.
     */
    private function dashboardResolveSessionFaculty($facultyMasterRaw, $internalFacultyRaw): string
    {
        static $cache = [];

        $ids = [];
        foreach ([$facultyMasterRaw, $internalFacultyRaw] as $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }
            $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $id) {
                    if (is_numeric($id)) {
                        $ids[] = (int) $id;
                    }
                }
            } elseif (is_numeric($raw)) {
                $ids[] = (int) $raw;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return 'N/A';
        }
        sort($ids);
        $cacheKey = implode(',', $ids);

        if (! array_key_exists($cacheKey, $cache)) {
            $names = FacultyMaster::whereIn('pk', $ids)
                ->pluck('full_name')
                ->filter(static fn ($n) => $n !== null && trim((string) $n) !== '')
                ->values();
            $cache[$cacheKey] = $names->isNotEmpty() ? $names->implode(', ') : 'N/A';
        }

        return $cache[$cacheKey];
    }

    private function dashboardStudentListSortValue($studentMap, string $sortKey)
    {
        $student = $studentMap->studentMaster;

        return match ($sortKey) {
            'ot_code' => strtolower((string) ($student->generated_OT_code ?? '')),
            'name' => strtolower(trim((string) (($student->display_name ?? '') ?: trim(($student->first_name ?? '').' '.($student->last_name ?? ''))))),
            'username' => strtolower((string) ($student->user_id ?? '')),
            'email' => strtolower((string) ($student->email ?? '')),
            'cadre' => strtolower((string) ($student->cadre->cadre_name ?? '')),
            'course' => strtolower((string) ($studentMap->course->course_name ?? '')),
            'status' => (int) ($studentMap->attendance_present ?? true),
            'date' => (string) ($studentMap->session_date ?? ''),
            'session' => (string) ($studentMap->session_time ?? ''),
            'topic' => strtolower((string) ($studentMap->session_topic ?? '')),
            'faculty' => strtolower((string) $this->dashboardResolveSessionFaculty($studentMap->session_faculty_master ?? null, $studentMap->session_internal_faculty ?? null)),
            'mdo' => (int) ($studentMap->attendance_status ?? 0) === 4 ? 1 : 0,
            'escort' => (int) ($studentMap->attendance_status ?? 0) === 5 ? 1 : 0,
            'other_exempt' => in_array((int) ($studentMap->attendance_status ?? 0), [6, 7], true) ? 1 : 0,
            'house' => strtolower((string) ($studentMap->house_name ?? '')),
            'duty' => (int) ($studentMap->total_duty_count ?? 0),
            'medical' => (int) ($studentMap->total_medical_exception_count ?? 0),
            'pt' => (int) ($studentMap->total_pt_exemption_count ?? 0),
            'stationed' => (int) ($studentMap->total_stationed_leave_count ?? 0),
            'notice' => (int) ($studentMap->total_notice_count ?? 0),
            'memo' => (int) ($studentMap->total_memo_count ?? 0),
            default => '',
        };
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    private function dashboardStudentListExportData($students, string $attendance = 'all'): array
    {
        $students = ($students instanceof Collection ? $students : collect($students))->values();

        // Mirror the on-screen Student List exactly: the export must show the SAME
        // per-session columns a viewer sees in the table (Date / Session / Topic /
        // Faculty / Attendance Status and the MDO / Escort / Other-Exemption flags),
        // not lifetime aggregate counts. These lookups reproduce the table's
        // dashboardStudentListDataTableResponse() logic, keyed by row position.
        $absentReasons = $this->dashboardAbsentReasons($students);
        $dutyExemptionFlags = $this->dashboardDutyExemptionFlags($students);
        $sessionCoverage = $this->dashboardSessionCoverage($students);

        $headings = [
            'S. No.',
            'OT Code',
            'Name',
            'Course',
            'User Name',
            'Cadre',
            'Date',
            'Session',
            'Topic',
            'Faculty',
            'Attendance Status',
            'MDO',
            'Escort/Moderator Duty',
            'Other Exemptions',
        ];

        $rows = [];
        foreach ($students as $index => $studentMap) {
            $student = $studentMap->studentMaster;
            if (! $student) {
                continue;
            }

            $displayName = $student->display_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
            $statusCode = (int) ($studentMap->attendance_status ?? 0);
            $isAbsent = ($studentMap->attendance_present ?? true) === false;

            // Attendance status text: Present / Late / Absent, with the leave-based
            // reason (PT Exemption / Stationed Leave) appended for an absent row —
            // matching the on-screen badge (Late = status 2 attended-but-late).
            $statusText = $this->dashboardRowIsDutyPresent($statusCode, $sessionCoverage[$index] ?? false)
                ? 'Present'
                : ($isAbsent ? 'Absent' : ($statusCode === 2 ? 'Late' : 'Present'));
            if ($isAbsent) {
                $reason = $absentReasons[$index] ?? '-';
                if ($reason !== '-') {
                    $statusText .= ' ('.$reason.')';
                }
            }

            // MDO / Escort / Medical / Other: shown when the attendance status code
            // marks it OR a separately-created duty / medical exemption maps to this
            // student + session date (same rule as the on-screen columns).
            $flags = $dutyExemptionFlags[$index] ?? ['mdo' => false, 'escort' => false, 'medical' => false, 'other' => false];
            $showMdo = $statusCode === 4 || $flags['mdo'];
            $showEscort = $statusCode === 5 || $flags['escort'];
            $showMedical = $statusCode === 6 || $flags['medical'];
            $showOther = $statusCode === 7 || $flags['other'];

            // Inline Other Exemption (Absent + typed reason) shows its reason text in
            // the Other Exemptions column, mirroring the on-screen table.
            $otherComment = trim((string) ($studentMap->other_exemption_comments ?? ''));
            $otherExemptionColumn = $showMedical
                ? 'Medical'
                : ($showOther ? 'Other' : ($otherComment !== '' ? $otherComment : '-'));

            $rows[] = [
                $index + 1,
                $student->generated_OT_code ?? 'N/A',
                $displayName,
                $studentMap->course->course_name ?? 'N/A',
                $student->user_id ?? 'N/A',
                $student->cadre->cadre_name ?? 'N/A',
                $studentMap->session_date ? Carbon::parse($studentMap->session_date)->format('d M Y') : 'N/A',
                $studentMap->session_time ?: 'N/A',
                $studentMap->session_topic ?: 'N/A',
                $this->dashboardResolveSessionFaculty(
                    $studentMap->session_faculty_master ?? null,
                    $studentMap->session_internal_faculty ?? null
                ),
                $statusText,
                $showMdo ? 'MDO' : '-',
                $showEscort ? 'Escort' : '-',
                $otherExemptionColumn,
            ];
        }

        return compact('headings', 'rows');
    }

    private function dashboardStudentListFilterSummary(Request $request): string
    {
        $parts = [];

        if ($request->filled('course_id')) {
            $course = CourseMaster::find($request->course_id);
            $parts[] = 'Course: '.($course->course_name ?? $request->course_id);
        }

        if ($request->filled('role_filter')) {
            if ($request->role_filter === 'cc_acc') {
                $parts[] = 'Role: CC/ACC';
            } else {
                $type = DB::table('course_group_type_master')->where('pk', $request->role_filter)->value('type_name');
                $parts[] = 'Role: '.($type ?? $request->role_filter);
            }
        }

        if ($request->filled('faculty_filter')) {
            $facultyName = DB::table('faculty_master')->where('pk', $request->faculty_filter)->value('full_name');
            $parts[] = 'Faculty: '.($facultyName ?? $request->faculty_filter);
        }

        if ($request->filled('group_pk')) {
            $group = DB::table('group_type_master_course_master_map')->where('pk', $request->group_pk)->value('group_name');
            $parts[] = 'Group: '.($group ?? $request->group_pk);
        }

        if ($request->filled('search')) {
            $parts[] = 'Search: '.$request->search;
        }

        return $parts ? implode(' | ', $parts) : 'All students';
    }

    /**
     * Display My Counselee list (counsellor's assigned counselees with phase progress).
     *
     * @return View
     */
    public function myCounselee()
    {
        $userId = Auth::user()->user_id;

        // Only faculty users should see dynamic counselee mapping
        $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
        if (! $faculty) {
            return view('admin.dashboard.my_counselee', ['counselees' => []]);
        }

        $facultyPk = (int) $faculty->pk;
        $today = Carbon::now()->format('Y-m-d');
        $noticeMemoService = app(OTNoticeMemoService::class);

        // Find active group mappings assigned to this faculty for active courses
        $groupMappings = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_master as cm', 'gmap.course_name', '=', 'cm.pk')
            ->where('gmap.active_inactive', 1)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', '>=', $today)
            ->where('gmap.facility_id', $facultyPk)
            ->select('gmap.pk as group_pk', 'gmap.course_name as course_pk')
            ->get();

        if ($groupMappings->isEmpty()) {
            return view('admin.dashboard.my_counselee', ['counselees' => []]);
        }

        $groupPks = $groupMappings->pluck('group_pk')->unique()->values();
        $coursePksByGroup = $groupMappings->keyBy('group_pk')->map(fn ($r) => (int) $r->course_pk);

        // Students in these groups
        $studentGroupRows = StudentCourseGroupMap::with([
            'student.service',
            'student.cadre',
            'groupTypeMasterCourseMasterMap.courseGroup',
        ])
            ->whereIn('group_type_master_course_master_map_pk', $groupPks)
            ->where('active_inactive', 1)
            ->get();

        // Build unique list by student_pk (prefer rows where course is known)
        $byStudent = [];
        foreach ($studentGroupRows as $row) {
            $studentPk = (int) $row->student_master_pk;
            if (! $row->student) {
                continue;
            }
            if (! isset($byStudent[$studentPk])) {
                $byStudent[$studentPk] = $row;
            }
        }

        // Absences on sessions the OT was actually on MDO/Escort/Other duty or
        // medically exempt for count as Present, not against them — the rule
        // AttendanceController::save applies on write. Resolved for every counselee
        // up front: the loop below runs one aggregate per OT already, and asking
        // per OT here would multiply that several times over.
        $absenceRows = CourseStudentAttendance::whereIn('Student_master_pk', array_keys($byStudent))
            ->where('status', '3')
            ->whereNotNull('timetable_pk')
            ->get(['Student_master_pk', 'course_master_pk', 'timetable_pk']);

        $coveredAbsences = OtExemptionResolver::coveredSessions(
            $absenceRows->map(fn ($r) => [
                'student' => (int) $r->Student_master_pk,
                'course' => (int) $r->course_master_pk,
                'timetable' => (int) $r->timetable_pk,
            ])->all()
        );

        // student pk => how many of its absences are duty-covered, per course.
        $dutyCoveredAbsences = [];
        foreach ($absenceRows as $r) {
            $key = OtExemptionResolver::sessionKey(
                (int) $r->Student_master_pk,
                (int) $r->course_master_pk,
                (int) $r->timetable_pk
            );

            if (isset($coveredAbsences[$key])) {
                $bucket = (int) $r->Student_master_pk . '|' . (int) $r->course_master_pk;
                $dutyCoveredAbsences[$bucket] = ($dutyCoveredAbsences[$bucket] ?? 0) + 1;
            }
        }

        $counselees = [];
        foreach ($byStudent as $studentPk => $row) {
            $student = $row->student;
            $coursePk = (int) ($coursePksByGroup[$row->group_type_master_course_master_map_pk] ?? 0);

            // Course details (from relationship if loaded, else lookup from enrollment)
            $course = $row->groupTypeMasterCourseMasterMap?->courseGroup;
            if (! $course && $coursePk) {
                $course = CourseMaster::find($coursePk);
            }

            // Attendance % for this student in this course (present + late) / total.
            // status is ENUM('0'..'7'); compare to quoted strings so buckets aren't
            // shifted by MySQL's enum-ordinal comparison (see studentDetail summary).
            $att = CourseStudentAttendance::where('Student_master_pk', $studentPk)
                ->when($coursePk > 0, fn ($q) => $q->where('course_master_pk', $coursePk))
                ->selectRaw("COUNT(*) as total_sessions,
                    COALESCE(SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END), 0) as present_count,
                    COALESCE(SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END), 0) as late_count,
                    COALESCE(SUM(CASE WHEN status IN ('4', '5', '6', '7') THEN 1 ELSE 0 END), 0) as duty_count
                ")
                ->first();
            $totalSessions = (int) ($att->total_sessions ?? 0);
            $present = (int) ($att->present_count ?? 0);
            $late = (int) ($att->late_count ?? 0);

            // One presence rule with the student list (dashboardRowIsDutyPresent()):
            // a session saved as MDO / Escort / Medical / Other (4-7) is Present, and
            // so is a duty-covered absence. Only the second was counted, so the same
            // duty gave a different % depending on how it was saved (PR #334 F-038).
            $present += (int) ($att->duty_count ?? 0);
            $present += $dutyCoveredAbsences[$studentPk . '|' . $coursePk] ?? 0;

            $attendancePct = $totalSessions > 0 ? (int) round((($present + $late) / $totalSessions) * 100) : 0;

            $exemptionsCount = StudentMedicalExemption::where('student_master_pk', $studentPk)
                ->where('active_inactive', 1)
                ->count();
            $memosCount = $noticeMemoService->getDisciplineMemos($studentPk)->count();

            // Phase list: use active enrolled courses as “completed/active” sequence
            $courseMaps = StudentMasterCourseMap::with('course')
                ->where('student_master_pk', $studentPk)
                ->where('active_inactive', 1)
                ->get()
                ->filter(fn ($m) => $m->course && (int) $m->course->active_inactive === 1)
                ->sortBy(fn ($m) => $m->course->start_year ?? $m->course->start_date ?? 0)
                ->values();

            $phases = [];
            foreach ($courseMaps as $m) {
                $cm = $m->course;
                $isActiveCourse = $cm->end_date && Carbon::parse($cm->end_date)->gte(Carbon::today());
                $label = $cm->couse_short_name ?? $cm->course_name ?? 'Course';
                $phases[] = [
                    'name' => (string) $label,
                    'status' => $isActiveCourse ? 'active' : 'completed',
                ];
            }
            // Ensure at least one entry
            if (empty($phases) && $course) {
                $phases[] = [
                    'name' => (string) ($course->couse_short_name ?? $course->course_name ?? 'Course'),
                    'status' => 'active',
                ];
            }

            $photo = null;
            if (! empty($student->photo_path)) {
                $photo = asset('storage/'.$student->photo_path);
            }

            $displayName = $student->display_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
            $counselees[] = [
                'name' => $displayName ?: ('Student #'.$studentPk),
                'id' => $student->generated_OT_code ?? ('STU-'.$studentPk),
                'service' => $student->service?->service_name ?? 'N/A',
                'cadre' => $student->cadre?->cadre_name ?? 'N/A',
                'email' => $student->email ?? 'N/A',
                'fc_date' => $course ? (($course->couse_short_name ?? $course->course_name ?? 'N/A')) : 'N/A',
                'phase_badge' => $course ? ($course->couse_short_name ?? 'Active') : 'Active',
                'attendance' => $attendancePct,
                'memos' => $memosCount,
                'exemptions' => $exemptionsCount,
                'phases' => $phases,
                'photo' => $photo,
                'active' => true,
            ];
        }

        // Stable sort by name
        usort($counselees, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));

        return view('admin.dashboard.my_counselee', compact('counselees'));
    }

    /**
     * Whether the login is an officer trainee whose user_id is a student_master pk.
     *
     * The Student-OT role alone is not enough: the Moodle token login grants it to
     * whatever account the token names, and for a non-'S' account user_id is an
     * employee / faculty pk that can equal another trainee's pk (PR #334 F-055,
     * the F-047 rule).
     */
    private function isMyGroupsTrainee(): bool
    {
        return hasRole('Student-OT') && (Auth::user()->user_category ?? null) === 'S';
    }
    /**
     * Base query for the groups an OT belongs to, per the Course Group Mapping module.
     *
     * One row of group_type_master_course_master_map IS one group — a named group of a
     * given type within a given course, exactly as the Course Group Mapping listing
     * shows it. The count is therefore DISTINCT on gmap.pk, not on group_name: a name
     * like "Group 2" or "A" is reused across group types, so deduplicating by name
     * would merge, say, "Group 2" of the DM Conference with "Group 2" of the Election
     * Management Session, which are different groups with different members.
     *
     * Deliberately NOT filtered by course active/end date (unlike myCounselee(), which
     * is about a faculty's CURRENT counselees): this card answers "all the groups I am
     * mapped to". Every course in the data has already ended, so an end-date filter
     * would show 0 to every OT.
     *
     * @param  int|string  $studentPk  student_master.pk — for a Student-OT this is
     *                                 auth()->user()->user_id, as used across the
     *                                 attendance, calendar and exemption screens.
     */

    private function myGroupsQuery($studentPk)
    {
        return DB::table('student_course_group_map as scgm')
            ->join('group_type_master_course_master_map as gmap', 'gmap.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->where('scgm.student_master_pk', $studentPk)
            ->where('scgm.active_inactive', 1)
            ->where('gmap.active_inactive', 1);
    }

    /**
     * "My Groups" — the groups the logged-in OT is mapped to.
     *
     * Target of the My Groups dashboard card.
     */
    public function myGroups()
    {
        if (! $this->isMyGroupsTrainee()) {
            return redirect()->route('admin.dashboard');
        }

        $studentPk = Auth::user()->user_id;

        $groups = $this->myGroupsQuery($studentPk)
            // gmap.type_name and gmap.course_name are varchar columns holding the pk of
            // the type / course, not their names — see CourseGroupTypeMaster and the
            // Course Group Mapping grid, which resolve them the same way.
            ->leftJoin('course_group_type_master as gtype', 'gtype.pk', '=', 'gmap.type_name')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'gmap.course_name')
            ->leftJoin('faculty_master as fm', 'fm.pk', '=', 'gmap.facility_id')
            ->select([
                'gmap.pk',
                'gmap.group_name',
                'gtype.type_name as group_type',
                'cm.course_name',
                'fm.full_name as faculty_name',
            ])
            // A handful of students have a duplicate student_course_group_map row for
            // the same group; without this the group would be listed twice.
            ->distinct()
            ->orderBy('cm.course_name')
            ->orderBy('gtype.type_name')
            ->orderBy('gmap.group_name')
            ->get();

        // Total members per group, in one query rather than one per row.
        //
        // COUNT(DISTINCT student_master_pk), not COUNT(*): the same duplicate rows
        // the ->distinct() above guards against would otherwise count a student
        // twice. (The admin Course Group Mapping grid uses a plain withCount, so on
        // the two affected groups its figure reads one higher than this one.)
        $memberCounts = $groups->isEmpty()
            ? collect()
            : DB::table('student_course_group_map')
                ->whereIn('group_type_master_course_master_map_pk', $groups->pluck('pk'))
                ->where('active_inactive', 1)
                ->selectRaw('group_type_master_course_master_map_pk AS map_pk, COUNT(DISTINCT student_master_pk) AS members')
                ->groupBy('group_type_master_course_master_map_pk')
                ->pluck('members', 'map_pk');

        foreach ($groups as $group) {
            $group->total_members = (int) ($memberCounts[$group->pk] ?? 0);
        }

        return view('admin.dashboard.my_groups', compact('groups'));
    }

    /**
     * The officer trainees mapped to one of the viewer's own groups — what the
     * view icon on My Groups opens.
     *
     * Membership is re-checked rather than trusted: the group pk comes from the
     * URL, so without this an OT could read the roster of any group in the
     * Academy by editing it.
     */
    public function myGroupStudents($mapPk)
    {
        $group = $this->assertOwnGroup($mapPk);

        if (! $group) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        return response()->json([
            'group' => [
                'name' => $group->group_name ?? '—',
                'type' => $group->group_type ?? '—',
                'course' => $group->course_name ?? '—',
            ],
            'students' => $this->myGroupRoster((int) $mapPk)->map(fn ($s) => [
                'pk' => $s['pk'],
                'name' => $s['name'],
                'ot_code' => $s['ot_code'],
                'email' => $s['email'],
                'mobile' => $s['mobile'],
            ])->values(),
        ]);
    }

    /**
     * Excel or PDF of one of the viewer's own group rosters.
     */
    public function myGroupStudentsExport(Request $request, $mapPk)
    {
        $group = $this->assertOwnGroup($mapPk);

        if (! $group) {
            abort(403, 'You are not a member of this group.');
        }

        $roster = $this->myGroupRoster((int) $mapPk);

        $headings = ['S. No.', 'Student Name', 'OT Code', 'Email', 'Mobile Number'];
        $centreColumns = [0, 2, 4];

        $rows = $roster->values()->map(fn ($s, $index) => [
            $index + 1, $s['name'], $s['ot_code'], $s['email'], $s['mobile'],
        ])->values();

        $filterLine = 'Course: ' . ($group->course_name ?? '—')
            . '  |  Group: ' . ($group->group_name ?? '—')
            . '  |  Type: ' . ($group->group_type ?? '—');
        $title = 'Group Members — ' . ($group->group_name ?? 'Group');
        $baseName = 'Group_Members_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($group->group_name ?? 'group'))
            . '_' . now()->format('Ymd_His');

        if (is_string($request->get('format')) && strtolower($request->get('format')) === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            return Pdf::loadView('admin.exports.table_pdf', [
                'headings' => $headings,
                'rows' => $rows,
                'reportTitle' => $title,
                'filterLine' => $filterLine,
                'centreColumns' => $centreColumns,
            ])->setPaper('a4', 'portrait')->download($baseName . '.pdf');
        }

        return Excel::download(
            new LbsnaaTableExport($rows, $headings, $title, $filterLine, $centreColumns, 'Group Members'),
            $baseName . '.xlsx'
        );
    }

    /**
     * SMS or email the selected members of one of the viewer's own groups.
     *
     * Both the group and every recipient are re-checked against the viewer's own
     * membership: this endpoint sends real messages, so an OT must not be able to
     * reach anyone outside a group they are themselves in by editing the request.
     */
    public function myGroupSendMessage(Request $request, $mapPk)
    {
        // Whether Officer Trainees may send through the Academy's gateway at all is a
        // Product owner decision that is not on record, so the send stays off until
        // the environment enables it (PR #334 F-005).
        if (! config('my_groups.messaging_enabled')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sending messages from My Groups is not enabled.',
            ], 403);
        }

        $group = $this->assertOwnGroup($mapPk);

        if (! $group) {
            return response()->json(['status' => 'error', 'message' => 'You are not a member of this group.'], 403);
        }

        $validated = $request->validate([
            'channel' => 'required|in:sms,email',
            'message' => 'required|string|max:1000',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        $roster = $this->myGroupRoster((int) $mapPk)->keyBy('pk');
        $selected = collect($validated['student_ids'])->map(fn ($id) => (int) $id)->unique();

        if ($selected->diff($roster->keys())->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some selected officer trainees are not part of this group.',
            ], 422);
        }

        $recipients = $roster->only($selected->all());

        // Every message names its sender: it leaves under the Academy's identity, so
        // without this a recipient could not tell an OT's text from an official one.
        $text = $this->groupMessageAttribution() . "\n\n" . $validated['message'];

        if ($validated['channel'] === 'email') {
            $emails = $recipients->pluck('email')->filter(fn ($e) => filled($e) && $e !== '-');

            if ($emails->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'None of the selected officer trainees have an email address on record.',
                ], 422);
            }

            $failed = app(EmailService::class)->sendBulk($emails->values(), $text);
            $sent = $emails->count() - count($failed);
            $this->logGroupMessage((int) $mapPk, 'email', $emails->count(), $sent);

            return response()->json([
                'status' => $sent > 0 ? 'success' : 'error',
                'message' => $sent > 0 ? "Email sent to {$sent} OT(s)." : 'Unable to send email to the selected OTs.',
            ], $sent > 0 ? 200 : 500);
        }

        $numbers = $recipients->pluck('mobile')->filter(fn ($n) => filled($n) && $n !== '-');

        if ($numbers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'None of the selected officer trainees have a contact number on record.',
            ], 422);
        }

        $failed = app(SmsService::class)->sendBulk($numbers->values(), $text);
        $sent = $numbers->count() - count($failed);
        $this->logGroupMessage((int) $mapPk, 'sms', $numbers->count(), $sent);

        return response()->json([
            'status' => $sent > 0 ? 'success' : 'error',
            'message' => $sent > 0 ? "SMS sent to {$sent} OT(s)." : 'Unable to send SMS to the selected OTs.',
        ], $sent > 0 ? 200 : 500);
    }

    /** "Message from <name> (<OT code>), Officer Trainee, via Sargam My Groups:" */
    private function groupMessageAttribution(): string
    {
        $sender = DB::table('student_master')
            ->where('pk', (int) Auth::user()->user_id)
            ->first(['display_name', 'first_name', 'last_name', 'generated_OT_code']);

        $name = trim((string) ($sender->display_name ?? ''))
            ?: trim(implode(' ', array_filter([$sender->first_name ?? '', $sender->last_name ?? ''])));
        $name = $name !== '' ? $name : 'an Officer Trainee';
        $code = trim((string) ($sender->generated_OT_code ?? ''));

        return 'Message from ' . $name . ($code !== '' ? ' (' . $code . ')' : '')
            . ', Officer Trainee, via Sargam My Groups:';
    }

    /**
     * Audit for every My Groups send, so a message an OT pushes through the
     * institutional gateway is attributable (PR #334 F-005): a durable row in
     * my_group_message_log, plus the log line. The message text is deliberately
     * NOT recorded: it is request text (a raw line feed would forge extra log
     * records — trap 35) and it is the sender's private content.
     */
    private function logGroupMessage(int $mapPk, string $channel, int $recipients, int $sent): void
    {
        DB::table('my_group_message_log')->insert([
            'sender_user_pk' => (int) auth()->id(),
            'sender_student_pk' => (int) Auth::user()->user_id,
            'group_map_pk' => $mapPk,
            'channel' => $channel,
            'recipient_count' => $recipients,
            'sent_count' => $sent,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info('my_groups.message', [
            // user_credentials is keyed on `pk`, so auth()->id() is that pk.
            'user_pk' => auth()->id(),
            'student_pk' => (int) Auth::user()->user_id,
            'group_map_pk' => $mapPk,
            'channel' => $channel,
            'recipients' => $recipients,
            'sent' => $sent,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * The group row, but only if the logged-in officer trainee belongs to it.
     * Returns null otherwise — the group pk travels in the URL, so every entry
     * point has to re-check rather than trust it.
     */
    private function assertOwnGroup($mapPk)
    {
        if (! $this->isMyGroupsTrainee()) {
            return null;
        }

        $isMember = $this->myGroupsQuery(Auth::user()->user_id)
            ->where('gmap.pk', (int) $mapPk)
            ->exists();

        if (! $isMember) {
            return null;
        }

        return DB::table('group_type_master_course_master_map as gmap')
            ->leftJoin('course_group_type_master as gtype', 'gtype.pk', '=', 'gmap.type_name')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'gmap.course_name')
            ->where('gmap.pk', (int) $mapPk)
            ->first(['gmap.group_name', 'gtype.type_name as group_type', 'cm.course_name']);
    }

    /** Officer trainees mapped to a group, with the contact details the roster lists. */
    private function myGroupRoster(int $mapPk): \Illuminate\Support\Collection
    {
        return DB::table('student_course_group_map as scgm')
            ->join('student_master as sm', 'sm.pk', '=', 'scgm.student_master_pk')
            ->where('scgm.group_type_master_course_master_map_pk', $mapPk)
            ->where('scgm.active_inactive', 1)
            ->select('sm.pk', 'sm.display_name', 'sm.first_name', 'sm.last_name',
                'sm.generated_OT_code', 'sm.email', 'sm.contact_no')
            // Same duplicate rows the member count guards against.
            ->distinct()
            ->orderBy('sm.display_name')
            ->get()
            ->map(fn ($row) => [
                'pk' => (int) $row->pk,
                'name' => trim((string) $row->display_name) ?: (trim(implode(' ', array_filter([
                    $row->first_name ?? '', $row->last_name ?? '',
                ]))) ?: 'Officer Trainee'),
                'ot_code' => $row->generated_OT_code ?: '-',
                'email' => $row->email ?: '-',
                'mobile' => $row->contact_no ?: '-',
            ])
            ->values();
    }

    /**
     * Display complete student details
     *
     * @param  int  $id  Student ID (encrypted)
     * @return View
     */
    public function studentDetail($id)
    {
        try {
            $studentPk = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard.students')
                ->with('error', 'Invalid student ID.');
        }

        // Get student basic information
        $student = StudentMaster::with(['service', 'courses'])->find($studentPk);

        if (! $student) {
            return redirect()->route('admin.dashboard.students')
                ->with('error', 'Student not found.');
        }

        if (is_faculty_portal_user()
            && ! hasRole('Super Admin')
            && ! hasRole('Training Induction Admin')
            && ! hasRole('Training MCTP Admin')
            && ! hasRole('Training IST')) {
            $facultyPk = get_auth_faculty_master_pk();
            if (! $facultyPk || ! $this->canFacultyViewStudent($facultyPk, (int) $studentPk)) {
                return redirect()->route('admin.dashboard.students')
                    ->with('error', 'You do not have access to view this student.');
            }
        }

        // Time Period filter carried over from the OT/Participants list, so a
        // clicked section shows only the data within that window. Leaves /
        // exemptions are matched by date-range OVERLAP; duties by their mdo_date.
        $fromDate = request('from_date') ?: null;
        $toDate = request('to_date') ?: null;

        // Get medical exceptions
        $medicalExemptions = StudentMedicalExemption::with(['course', 'category', 'speciality', 'employee'])
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
            ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
            ->orderBy('from_date', 'desc')
            ->get();

        $ptExemptions = LeaveApplication::with(['course', 'nature', 'approvedByFaculty', 'attachments'])
            ->where('student_master_pk', $studentPk)
            ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
            ->where('active_inactive', 1)
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
            ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
            ->orderBy('from_date', 'desc')
            ->get();

        $stationedLeaves = LeaveApplication::with(['course', 'nature', 'approvedByFaculty', 'attachments'])
            ->where('student_master_pk', $studentPk)
            ->where('leave_type', LeaveApplication::TYPE_STATIONED_LEAVE)
            ->where('active_inactive', 1)
            ->whereIn('status', [
                LeaveApplication::STATUS_APPROVED,
                LeaveApplication::STATUS_PENDING,
            ])
            ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
            ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
            ->orderBy('from_date', 'desc')
            ->get();

        // When a Time Period is active, show only the portion of each leave/exemption
        // that falls INSIDE the window ("date wise"), not the whole record. The
        // displayed From/To dates are clipped to the window and total_days recomputed
        // for that clipped span, so the detail page matches the list's day counts
        // (a 13–17 Jul PT Exemption filtered to 13–15 shows 13–15 = 3 days).
        if ($fromDate || $toDate) {
            $clipToWindow = function ($row, bool $clipDays) use ($fromDate, $toDate) {
                if (empty($row->from_date)) {
                    return;
                }
                $from = substr((string) $row->from_date, 0, 10);
                $to = ! empty($row->to_date) ? substr((string) $row->to_date, 0, 10) : $from;
                if ($to < $from) {
                    $to = $from;
                }
                if ($fromDate && $from < $fromDate) {
                    $from = $fromDate;
                }
                if ($toDate && $to > $toDate) {
                    $to = $toDate;
                }
                if ($to < $from) {
                    if ($clipDays) {
                        $row->total_days = 0;
                    }

                    return;
                }
                // Clip the displayed dates to the window.
                $row->from_date = Carbon::parse($from);
                $row->to_date = Carbon::parse($to);
                if ($clipDays) {
                    $row->total_days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
                }
            };
            $medicalExemptions->each(fn ($r) => $clipToWindow($r, false));
            $ptExemptions->each(fn ($r) => $clipToWindow($r, true));
            $stationedLeaves->each(fn ($r) => $clipToWindow($r, true));
        }

        // Show PT Exemption / Station Leave DATE-WISE: a multi-day leave is expanded
        // into one row per day (From = To = that day, Total Days = 1) instead of a
        // single ranged row. The header still sums total_days, so the overall count is
        // unchanged (a 2-day leave → 2 rows, header count still 2). Respects the Time
        // Period window because the dates were already clipped above.
        $expandLeavesByDay = function (Collection $leaves): Collection {
            $expanded = collect();
            foreach ($leaves as $leave) {
                if (empty($leave->from_date)) {
                    $expanded->push($leave);

                    continue;
                }
                $start = Carbon::parse($leave->from_date)->startOfDay();
                $end = ! empty($leave->to_date) ? Carbon::parse($leave->to_date)->startOfDay() : $start->copy();
                if ($end->lt($start)) {
                    $end = $start->copy();
                }
                for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                    $row = clone $leave;
                    $row->from_date = $day->copy();
                    $row->to_date = $day->copy();
                    $row->total_days = 1;
                    $expanded->push($row);
                }
            }

            return $expanded;
        };
        $ptExemptions = $expandLeavesByDay($ptExemptions);
        $stationedLeaves = $expandLeavesByDay($stationedLeaves);

        // Get MDO/Escort duties
        $duties = MDOEscotDutyMap::with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->where('selected_student_list', $studentPk)
            ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
            ->orderBy('mdo_date', 'desc')
            ->get();

        // Get notices using OTNoticeMemoService; memos come from the Discipline Memo
        // module's source (discipline_memo_status) so the detail view matches
        // /memo/discipline and the list "Total Memo" count.
        $noticeMemoService = app(OTNoticeMemoService::class);
        $notices = $noticeMemoService->getNotices($studentPk);
        $memos = $noticeMemoService->getDisciplineMemos($studentPk);

        // Scope notices/memos to the same Time Period window (by their session date).
        if ($fromDate || $toDate) {
            $inDateWindow = function ($item) use ($fromDate, $toDate) {
                $d = $item->session_date ?? null;
                if (! $d) {
                    return false;
                }
                $d = substr((string) $d, 0, 10);
                if ($fromDate && $d < $fromDate) {
                    return false;
                }
                if ($toDate && $d > $toDate) {
                    return false;
                }

                return true;
            };
            $notices = $notices->filter($inDateWindow)->values();
            $memos = $memos->filter($inDateWindow)->values();
        }

        // Get enrolled courses
        $enrolledCourses = StudentMasterCourseMap::with('course')
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->get();

        // Get attendance records summary.
        // NOTE: course_student_attendance.status is an ENUM('0'..'7'); comparing it to
        // an INTEGER makes MySQL match by enum ordinal (1-indexed), shifting every
        // bucket by one. Compare against the quoted string values so the mapping is
        // correct (1 Present, 2 Late, 3 Absent, 4 MDO, 5 Escort, 6 Medical, 7 Other).
        $attendanceSummary = CourseStudentAttendance::where('Student_master_pk', $studentPk)
            ->selectRaw("
                COUNT(*) as total_sessions,
                SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) as mdo_count,
                SUM(CASE WHEN status = '5' THEN 1 ELSE 0 END) as escort_count,
                SUM(CASE WHEN status = '6' THEN 1 ELSE 0 END) as medical_exempt_count,
                SUM(CASE WHEN status = '7' THEN 1 ELSE 0 END) as other_exempt_count,
                SUM(CASE WHEN status = '0' OR status IS NULL THEN 1 ELSE 0 END) as not_marked_count
            ")
            ->first();

        // A session the OT was on MDO/Escort/Other duty or medically exempt for counts
        // as Present, not Late or Absent — the same rule AttendanceController::save
        // applies on write. The saved row can still hold the Late/Absent it was marked
        // with when the duty was assigned after the fact, so those rows are moved into
        // the Present bucket here rather than being counted against the OT.
        if ($attendanceSummary) {
            $dutyCorrectedRows = CourseStudentAttendance::where('Student_master_pk', $studentPk)
                ->whereIn('status', ['2', '3'])
                ->whereNotNull('timetable_pk')
                ->get(['course_master_pk', 'timetable_pk', 'status']);

            $covered = OtExemptionResolver::coveredSessions(
                $dutyCorrectedRows->map(fn ($row) => [
                    'student' => (int) $studentPk,
                    'course' => (int) $row->course_master_pk,
                    'timetable' => (int) $row->timetable_pk,
                ])->all()
            );

            $movedLate = 0;
            $movedAbsent = 0;

            foreach ($dutyCorrectedRows as $row) {
                $key = OtExemptionResolver::sessionKey(
                    (int) $studentPk,
                    (int) $row->course_master_pk,
                    (int) $row->timetable_pk
                );

                if (!isset($covered[$key])) {
                    continue;
                }

                (int) $row->status === 2 ? $movedLate++ : $movedAbsent++;
            }

            $attendanceSummary->present_count = (int) $attendanceSummary->present_count + $movedLate + $movedAbsent;
            $attendanceSummary->late_count = (int) $attendanceSummary->late_count - $movedLate;
            $attendanceSummary->absent_count = (int) $attendanceSummary->absent_count - $movedAbsent;
        }

        // Calculate total expected sessions (timetables) for student's course groups
        $studentGroupPks = StudentCourseGroupMap::where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->pluck('group_type_master_course_master_map_pk')
            ->toArray();

        $totalExpectedSessions = 0;
        if (! empty($studentGroupPks)) {
            // Count only timetables that actually EXIST and are active. The mapping
            // table keeps rows for timetables that were later deleted (orphans) and
            // for cancelled/inactive sessions; counting those inflated Total Sessions
            // and Not Marked. Future-dated sessions are excluded too — a class that
            // hasn't happened yet cannot be "not marked".
            $result = DB::table('course_group_timetable_mapping as m')
                ->join('timetable as t', 't.pk', '=', 'm.timetable_pk')
                ->whereIn('m.group_pk', $studentGroupPks)
                ->where('t.active_inactive', 1)
                ->whereDate('t.START_DATE', '<=', now()->toDateString())
                ->selectRaw('COUNT(DISTINCT m.timetable_pk) as count')
                ->first();
            $totalExpectedSessions = $result ? (int) $result->count : 0;
        }

        // Calculate not marked count: sessions without attendance records or with status 0/NULL.
        // status is an ENUM('0'..'7') — compare against the string '0' (not integer 0,
        // which MySQL would treat as the enum's 0th/invalid index and never exclude '0').
        $markedResult = CourseStudentAttendance::where('Student_master_pk', $studentPk)
            ->whereNotNull('status')
            ->where('status', '!=', '0')
            ->selectRaw('COUNT(DISTINCT timetable_pk) as count')
            ->first();
        $markedSessions = $markedResult ? (int) $markedResult->count : 0;

        $notMarkedCount = max(0, $totalExpectedSessions - $markedSessions);

        // Add not_marked_count to attendance summary if it doesn't exist
        if ($attendanceSummary) {
            $attendanceSummary->not_marked_count = $notMarkedCount;
            $attendanceSummary->total_expected_sessions = $totalExpectedSessions;
        }

        $fcRegUsername = trim((string) ($student->user_id ?? ''));
        $fcJoiningDocuments = ($fcRegUsername !== '' && is_numeric($fcRegUsername))
            ? app(RegistrationService::class)->joiningDocumentChecklistForDisplay((int) $fcRegUsername)
            : collect();

        return view('admin.dashboard.student_detail', compact(
            'student',
            'medicalExemptions',
            'ptExemptions',
            'stationedLeaves',
            'duties',
            'notices',
            'memos',
            'enrolledCourses',
            'attendanceSummary',
            'fcRegUsername',
            'fcJoiningDocuments'
        ));
    }

    /**
     * The page sizes the User Management grid offers, and the only ones it will
     * serve.
     *
     * `(int) $request->input('per_page', 10)` passed straight to paginate() had
     * no ceiling and no allow-list, so `?per_page=20000` returned all 15,108
     * rows - 16.4 MB and 2.9 s in one request, carrying 13,625 email addresses
     * and 11,272 mobile numbers, and more of the directory than the capped
     * export next to it. That made the export's row cap decorative: the same
     * actor could ask the index for the rest.
     *
     * An allow-list rather than a max(): anything outside the dropdown is a
     * value the screen never offers, so it falls back to the default instead of
     * being silently rounded down to a number the user did not choose. The cache
     * key is built from the resolved value, so an out-of-range request can no
     * longer mint its own cache entry either.
     *
     * The footer's <select> is rendered FROM this list (index() passes it to the
     * view) rather than hard-coding its own options. When the two were written
     * out separately they drifted: the select offered 20, which is not on this
     * list, so choosing it silently served 10 - while 25 and 200 were accepted
     * here but never offered on screen.
     *
     * @var int[]
     */
    public const ADMIN_USERS_PER_PAGE_OPTIONS = [10, 20, 25, 50, 100, 200];

    private static function resolveAdminUsersPerPage($raw): int
    {
        $value = is_scalar($raw) ? (int) $raw : 0;

        return in_array($value, self::ADMIN_USERS_PER_PAGE_OPTIONS, true)
            ? $value
            : self::ADMIN_USERS_PER_PAGE_OPTIONS[0];
    }

    public function index(Request $request)
    {
        $perPage = self::resolveAdminUsersPerPage($request->input('per_page'));
        $search = trim((string) ($request->input('search') ?? ''));
        $user_type = trim((string) $request->input('User_type', ''));

        $epoch = DataTableRedisCache::readListEpoch(self::ADMIN_USERS_INDEX_LIST_EPOCH_KEY);
        $cacheKey = 'admin_users_index:v5:'.md5(json_encode([
            'epoch' => $epoch,
            'search' => $search,
            'user_type' => $user_type,
            'per_page' => $perPage,
            'page' => (int) $request->input('page', 1),
        ]));

        $cached = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ADMIN_USERS_INDEX_CACHE_ENABLED',
                'seconds' => 'ADMIN_USERS_INDEX_CACHE_SECONDS',
            ],
            'UserController@adminUsersIndex',
            fn () => $this->buildAdminUsersIndexPaginator($request, $perPage, $search, $user_type)
        );

        $users = new LengthAwarePaginator(
            $cached['items'],
            $cached['total'],
            $cached['perPage'],
            $cached['currentPage'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Live search / pagination: return only the table partial (no full reload).
        if ($request->ajax()) {
            return view('admin.user_management.users._table', compact('users', 'perPage', 'search', 'user_type') + [
                'perPageOptions' => self::ADMIN_USERS_PER_PAGE_OPTIONS,
            ]);
        }

        return view('admin.user_management.users.index', compact('users', 'perPage', 'search', 'user_type') + [
            'perPageOptions' => self::ADMIN_USERS_PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * @return array{items: array<int, mixed>, total: int, perPage: int, currentPage: int}
     */
    private function buildAdminUsersIndexPaginator(Request $request, int $perPage, $search, string $user_type): array
    {
        $paginator = $this->adminUsersBaseQuery($search, $user_type)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
        ];
    }

    /**
     * Build the base query for the admin users listing, applying the same
     * search + user-type filters used by both the paginated index and exports.
     * Keeping this in one place ensures exports honour the active filters.
     */
    private function adminUsersBaseQuery($search, string $user_type)
    {
        // Roles are managed through Spatie (model_has_roles / roles), which is
        // what the assign-role flow writes to — read from there so assigned
        // roles actually surface in the listing.
        $usersQuery = DB::table('user_credentials as uc')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'uc.pk')
                    ->where('mhr.model_type', '=', User::class);
            })
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->select(
                'uc.pk',
                'uc.user_name',
                'uc.first_name',
                'uc.last_name',
                'uc.email_id',
                'uc.mobile_no',
                'uc.user_category as User_type',
                DB::raw("GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') as roles")
            )
            ->groupBy(
                'uc.pk',
                'uc.user_name',
                'uc.first_name',
                'uc.last_name',
                'uc.email_id',
                'uc.mobile_no',
                'uc.user_category'
            );

        $search = trim((string) ($search ?? ''));

        if ($search !== '') {
            $searchLower = strtolower(preg_replace('/\s+/', ' ', $search) ?? $search);

            // Split the query into terms so a multi-word search (e.g. "virender virodia")
            // matches when each term is found in *some* field, even across different
            // columns (one term in user_name, another in last_name).
            $terms = array_filter(explode(' ', $searchLower), fn ($t) => $t !== '');

            $usersQuery->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $like = "%{$term}%";
                    $outer->where(function ($q) use ($like) {
                        $q->whereRaw("LOWER(TRIM(COALESCE(uc.user_name, ''))) LIKE ?", [$like])
                            ->orWhereRaw('LOWER(TRIM(uc.first_name)) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(TRIM(uc.last_name)) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(TRIM(uc.email_id)) LIKE ?', [$like])
                            ->orWhereRaw("LOWER(CONCAT_WS(' ', TRIM(uc.first_name), TRIM(uc.last_name))) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(CONCAT_WS(' ', TRIM(uc.last_name), TRIM(uc.first_name))) LIKE ?", [$like]);
                    });
                }
            });
        }
        if ($user_type !== '') {
            $usersQuery->where('uc.user_category', $user_type);
        }

        return $usersQuery;
    }

    /**
     * Column definitions available for export, keyed by the toggle key used in
     * the listing. Each entry maps to a heading, a value resolver and the print /
     * PDF column width, so all four formats lay the report out the same way.
     *
     * Labels and order mirror _table.blade.php's headers: "User Role" is the
     * Spatie role, "User Type" is the user_category code — two different things
     * that the grid used to run under one heading.
     *
     * @return array<string, array{label: string, value: callable, width: string, centre: bool}>
     */
    private function adminUsersExportColumns(): array
    {
        return [
            'username' => ['label' => 'User Name', 'width' => '13%', 'centre' => false,
                'value' => fn ($u) => $u->user_name ?? ''],
            'name' => ['label' => 'Name', 'width' => '19%', 'centre' => false,
                'value' => fn ($u) => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))],
            'email' => ['label' => 'Email', 'width' => '22%', 'centre' => false,
                'value' => fn ($u) => $u->email_id ?? ''],
            'mobile' => ['label' => 'Contact Number', 'width' => '12%', 'centre' => true,
                'value' => fn ($u) => $u->mobile_no ?: '—'],
            'usertype' => ['label' => 'User Type', 'width' => '11%', 'centre' => true,
                'value' => fn ($u) => self::userTypeLabel($u->User_type ?? '')],
            'roles' => ['label' => 'User Role', 'width' => '17%', 'centre' => false,
                'value' => fn ($u) => $u->roles ?: 'No Role'],
        ];
    }

    /**
     * The applied search / user-type filters, as a line for the report header.
     *
     * @return array{0: string|null, 1: string|null}  [plain text, HTML]
     */
    private function adminUsersFilterLine(string $search, string $userType): array
    {
        $plain = [];
        $html = [];

        if ($userType !== '') {
            $label = self::userTypeLabel($userType);
            $plain[] = 'User Type: ' . $label;
            $html[] = '<strong>User Type:</strong> ' . e($label);
        }
        if ($search !== '') {
            $plain[] = 'Search: ' . $search;
            $html[] = '<strong>Search:</strong> ' . e($search);
        }

        return [
            empty($plain) ? null : implode('  |  ', $plain),
            empty($html) ? null : implode(' &nbsp;|&nbsp; ', $html),
        ];
    }

    /**
     * Export the users listing — csv / xlsx / pdf / print, all four off ONE query
     * and ONE column list so they cannot drift apart
     * (docs/new-design-index-page.md §1).
     *
     * Honours the active search and user-type filter and, where provided, the
     * columns still visible in the grid. Deliberately unpaginated: the grid's
     * Print and Download used to scrape the rendered <table>, which is one page
     * of 10 rows, so every export silently truncated to whatever was on screen.
     */
    /**
     * Truncate a rendered export to $cap rows and say so on the sheet.
     *
     * One helper for both single-request renderers, so the PDF and the print
     * sheet cannot end up with different truncation behaviour or a different
     * wording for it - and so that a format which truncates can never do it
     * silently. The streaming formats (CSV / XLSX) are deliberately uncapped:
     * they are the complete list this note points the reader at.
     *
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function capUserExportRows(array $reportData, int $cap): array
    {
        $total = count($reportData['rows']);

        if ($total <= $cap) {
            return $reportData;
        }

        $reportData['note'] = 'Showing the first '
            . number_format($cap) . ' of '
            . number_format($total)
            . ' matching users. Narrow the filters, or use the Excel / CSV download for the complete list.';
        $reportData['rows'] = array_slice($reportData['rows'], 0, $cap);
        $reportData['totalRows'] = $total;

        return $reportData;
    }

    public function export(Request $request, string $format)
    {
        $search = trim((string) ($request->input('search') ?? ''));
        $user_type = trim((string) $request->input('User_type', ''));

        // Determine which columns to export based on the grid's visible columns.
        $allColumns = $this->adminUsersExportColumns();
        $requested = array_filter(explode(',', (string) $request->input('columns', '')));
        $requested = array_values(array_intersect($requested, array_keys($allColumns)));

        if (empty($requested)) {
            $requested = array_keys($allColumns);
        }

        // S. No. is generated by the export itself, so it leads every format.
        $columns = array_merge(
            [['label' => 'S. No.', 'width' => '6%', 'centre' => true]],
            array_map(
                fn ($key) => [
                    'label' => $allColumns[$key]['label'],
                    'width' => $allColumns[$key]['width'],
                    'centre' => $allColumns[$key]['centre'],
                ],
                $requested
            )
        );
        $headings = array_map(fn (array $c) => $c['label'], $columns);

        // cursor(), not get(): the export stays deliberately unpaginated (scraping the
        // rendered table truncated every download to the 10 rows on screen), but there
        // is no reason to hold the full hydrated model collection AND the flat $rows
        // array at the same time. cursor() hydrates one model at a time, so peak memory
        // is the row array alone rather than both. Keys stay 0-based and sequential, so
        // the S. No. column below is unaffected.
        $records = $this->adminUsersBaseQuery($search, $user_type)
            ->orderBy('uc.pk')
            ->cursor();

        $rows = [];
        foreach ($records as $i => $record) {
            $row = [$i + 1];
            foreach ($requested as $key) {
                $row[] = (string) $allColumns[$key]['value']($record);
            }
            $rows[] = $row;
        }

        [$filterLine, $filterHtml] = $this->adminUsersFilterLine($search, $user_type);
        $generatedAt = now()->format('d-m-Y h:i A');
        $fileBase = 'Users_' . now()->format('YmdHis');

        // Print and PDF render the same branded sheet from the same data; only the
        // renderer differs (a browser that can do @media print vs DomPDF, which
        // cannot — see the note at the top of each blade).
        $reportData = [
            'columns' => $columns,
            'headings' => $headings,
            'rows' => $rows,
            'filterLine' => $filterLine,
            'filterHtml' => $filterHtml,
            'exportDate' => $generatedAt,
        ];

        if ($format === 'print') {
            // Capped, where it used to say "no cap because the browser lays this
            // out itself". That was true of the BROWSER and missed the server: an
            // uncapped print of the whole directory is a 9.6 MB HTML document
            // built, held and written in one request, by any user who asks, as
            // often as they ask. The cap is higher than the PDF's because a
            // browser really does handle more layout than DomPDF, and the sheet
            // says plainly when it has been truncated rather than silently
            // dropping rows.
            $reportData = $this->capUserExportRows($reportData, self::ADMIN_USERS_PRINT_ROW_CAP);

            return view('admin.user_management.users.partials.export_print', $reportData);
        }

        if ($format === 'pdf') {
            $reportData = $this->capUserExportRows($reportData, self::ADMIN_USERS_PDF_ROW_CAP);

            $pdf = Pdf::loadView('admin.user_management.users.partials.export_pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // Never true: isPhpEnabled makes the renderer a PHP
                    // execution context for the whole view, so any raw block
                    // that later appears in an export blade would execute.
                    // Page numbers are stamped on the canvas after render
                    // instead — see PdfPageNumbers.
                    'isPhpEnabled' => false,
                ]);

            return PdfPageNumbers::stamp($pdf, 20)->download("{$fileBase}.pdf");
        }

        if ($format === 'xlsx') {
            // BrandedGridExport is what every other module's spreadsheet uses: it
            // draws the LBSNAA logo over a navy institution band, then a navy
            // header row over zebra rows with a frozen pane. UsersExport (below)
            // only writes plain text, which is why this one export arrived with
            // no logo while Roles / Menus / Topbar Category all had one.
            //
            // It resolves each cell through a column's `value` callable, but the
            // rows here are already flat arrays — so each column just reads its
            // own slot. `key` doubles as the centred-column marker.
            $branded = [];
            $centreKeys = [];
            foreach ($columns as $index => $col) {
                $key = 'col'.$index;
                if (! empty($col['centre'])) {
                    $centreKeys[] = $key;
                }
                $branded[] = [
                    'key' => $key,
                    'heading' => $col['label'],
                    'class' => '',
                    'value' => static fn ($row) => $row[$index] ?? '',
                ];
            }

            return Excel::download(
                BrandedGridExport::fromGrid($rows, $branded, 'Users', $generatedAt, $filterLine, $centreKeys),
                "{$fileBase}.xlsx"
            );
        }

        // CSV keeps the plain writer: it is text, so the .xlsx branding would be
        // dropped anyway. These rows give it the same header band in words.
        $metaRows = [
            ['LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION'],
            ['USERS'],
            [implode('  |  ', array_filter([$filterLine, 'Generated: ' . $generatedAt]))],
            ['Total Records: ' . number_format(count($rows))],
            [],
        ];

        return Excel::download(
            new UsersExport($headings, $rows, $metaRows),
            "{$fileBase}.csv",
            ExcelWriter::CSV
        );
    }

    private static function bumpAdminUsersIndexCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::ADMIN_USERS_INDEX_LIST_EPOCH_KEY, 'UserController@adminUsersIndex');
    }

    /**
     * Show the form for creating a new user.
     *
     * @return View
     */
    public function create()
    {
        $roles = UserRoleMaster::orderBy('pk', 'DESC')->get();

        return view('admin.user_management.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @return RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $roleIds = $request->input('roles', []);
            if (empty($roleIds)) {
                // Default RBAC role when no roles are selected.
                $staffRole = UserRoleMaster::where('user_role_name', 'Staff')
                    ->orWhere('user_role_display_name', 'Staff')
                    ->first();
                if ($staffRole) {
                    $roleIds = [$staffRole->pk];
                }
            }

            $assignedRoleNames = [];
            if (! empty($roleIds)) {
                // $user->assignRole($request->roles);
                foreach ($roleIds as $roleId) {
                    EmployeeRoleMapping::create([
                        'user_credentials_pk' => $user->id,
                        'user_role_master_pk' => $roleId,
                        'active_inactive' => 1,
                        'created_date' => now(),
                        'updated_date' => now(),
                    ]);

                    // Get role name for notification
                    $role = UserRoleMaster::find($roleId);
                    if ($role) {
                        $assignedRoleNames[] = $role->user_role_display_name ?? $role->user_role_name;
                    }
                }

                // Send notification to the user
                if (! empty($assignedRoleNames) && $user->user_id) {
                    try {
                        $notificationService = app(NotificationService::class);
                        $roleNames = implode(', ', $assignedRoleNames);
                        $notificationService->create(
                            (int) $user->user_id,
                            'role_assignment',
                            'Role Assignment',
                            $user->pk,
                            'Role Assigned',
                            "You have been assigned the following role(s): {$roleNames}."
                        );
                    } catch (\Exception $e) {
                        // Log error but don't fail the request
                        \Log::error('Failed to send role assignment notification: '.$e->getMessage());
                    }
                }
            }

            DB::commit();

            self::bumpAdminUsersIndexCacheEpoch();

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to create user: '.$e->getMessage());
        }
    }

    /**
     * Display the specified user.
     *
     * @return View
     */
    public function show(User $user)
    {
        $user->load('roles', 'permissions');

        return view('admin.user_management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @return View
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('admin.user_management.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user in storage.
     *
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            DB::beginTransaction();

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            if ($request->has('roles')) {
                // Remove old roles
                EmployeeRoleMapping::where('user_credentials_pk', $user->id)->delete();

                // Assign new roles
                $assignedRoleNames = [];
                $roleIds = $request->input('roles', []);

                if (empty($roleIds)) {
                    // Default RBAC role when roles are submitted empty.
                    $staffRole = UserRoleMaster::where('user_role_name', 'Staff')
                        ->orWhere('user_role_display_name', 'Staff')
                        ->first();
                    if ($staffRole) {
                        $roleIds = [$staffRole->pk];
                    }
                }

                if (! empty($roleIds)) {
                    foreach ($roleIds as $roleId) {
                        EmployeeRoleMapping::create([
                            'user_credentials_pk' => $user->id,
                            'user_role_master_pk' => $roleId,
                            'active_inactive' => 1,
                            'created_date' => now(),
                            'updated_date' => now(),
                        ]);
                        // Get role name for notification
                        $role = UserRoleMaster::find($roleId);
                        if ($role) {
                            $assignedRoleNames[] = $role->user_role_display_name ?? $role->user_role_name;
                        }
                    }
                }

                // Send notification to the user if roles were assigned
                if (! empty($assignedRoleNames) && $user->user_id) {
                    try {
                        $notificationService = app(NotificationService::class);
                        $roleNames = implode(', ', $assignedRoleNames);
                        $notificationService->create(
                            (int) $user->user_id,
                            'role_assignment',
                            'Role Assignment',
                            $user->pk,
                            'Role Assigned',
                            "You have been assigned the following role(s): {$roleNames}."
                        );
                    } catch (\Exception $e) {
                        // Log error but don't fail the request
                        \Log::error('Failed to send role assignment notification: '.$e->getMessage());
                    }
                }
            }

            DB::commit();

            self::bumpAdminUsersIndexCacheEpoch();

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to update user: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deletion of admin user
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Cannot delete admin user');
            }

            $user->delete();

            self::bumpAdminUsersIndexCacheEpoch();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to delete user: '.$e->getMessage());
        }
    }

    /**
     * Tables this generic status endpoint may write, with the key column and
     * the status column allowed for each.
     *
     * The endpoint takes the table, column and key column straight from the
     * request, so without this list any authenticated session can write any
     * column of any table (accepted-risk record SAST-2026-08-21-01). The list
     * is the complete set of screens that post here - every element carrying
     * the global `.status-toggle` class under resources/views, app/ and
     * public/ - so no screen that worked before is refused.
     *
     * Screens with their own toggle route never reach this method and are
     * deliberately absent: member (`/member/{id}/toggle-status`), the sidebar
     * screens, security vehicle pass/type (own `data-url`), and everything on
     * `.plain-status-toggle`, which posts through a hidden form instead.
     *
     * Adding a status switch to a new screen means adding its row here.
     */
    private const TOGGLE_STATUS_ALLOWED = [
        'appellation_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'building_floor_room_mapping' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'building_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'caste_category_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'city_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'class_session_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'country_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'course_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'course_memo_decision_mapp' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'department_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'designation_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'discipline_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'employee_group_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'employee_type_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'faculty_expertise_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'faculty_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'faculty_type_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'fc_exemption_master' => ['id_column' => 'pk', 'columns' => ['visible'], 'admin_only' => true],
        'fc_registration_master' => ['id_column' => 'pk', 'columns' => ['active_inactive'], 'admin_only' => true],
        'floor_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'group_type_master_course_master_map' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'hostel_building_floor_mapping' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'hostel_building_master' => ['id_column' => 'pk', 'columns' => ['active_room']],
        'hostel_floor_room_mapping' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'hostel_room_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'issue_category_master' => ['id_column' => 'pk', 'columns' => ['status']],
        'issue_priority_master' => ['id_column' => 'pk', 'columns' => ['status']],
        'issue_sub_category_master' => ['id_column' => 'pk', 'columns' => ['status']],
        'memo_conclusion_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'memo_type_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'news' => ['id_column' => 'pk', 'columns' => ['status'], 'admin_only' => true],
        'notices_notification' => ['id_column' => 'pk', 'columns' => ['active_inactive'], 'admin_only' => true],
        'ot_hostel_room_details' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'sec_id_cardno_config_map' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'sec_id_cardno_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'state_district_mapping' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'state_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'states' => ['id_column' => 'pk', 'columns' => ['status']],
        'stream_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'subject_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'subject_module_master' => ['id_column' => 'pk', 'columns' => ['active_inactive']],
        'user_role_master' => ['id_column' => 'pk', 'columns' => ['active_inactive'], 'admin_only' => true],
        'venue_master' => ['id_column' => 'venue_id', 'columns' => ['active_inactive']],
    ];

    /**
     * Whether a course-scoped table's row lies inside the actor's course scope,
     * using the same rule as CourseMasterDataTable and GroupMappingDataTable.
     * Tables that are not course-scoped always pass.
     */
    private function toggleTargetInCourseScope(string $table, $id): bool
    {
        if ($table === 'course_master') {
            $coursePk = $id;
        } elseif ($table === 'group_type_master_course_master_map') {
            // course_name holds the course pk (see GroupTypeMasterCourseMasterMap::courseGroup()).
            $coursePk = DB::table($table)->where('pk', $id)->value('course_name');
        } else {
            return true;
        }

        $scope = get_Role_by_course();

        if (empty($scope)) {
            return true;
        }

        return $coursePk !== null && in_array((int) $coursePk, array_map('intval', $scope), true);
    }

    public function toggleStatus(Request $request)
    {
        try {
            $table = (string) $request->input('table', '');
            $column = (string) $request->input('column', '');
            $idColumn = (string) ($request->input('id_column') ?: 'pk');
            $id = $request->input('id');
            $status = $request->input('status');

            $allowed = self::TOGGLE_STATUS_ALLOWED[$table] ?? null;

            // Refuse anything the UI never asks for: an unlisted table, a column
            // that is not that table's status column, a key column other than the
            // one the screen uses, a non-numeric id, or a status outside 0/1.
            if ($allowed === null
                || ! in_array($column, $allowed['columns'], true)
                || $idColumn !== $allowed['id_column']
                || ! is_numeric($id)
                || ! in_array((int) $status, [0, 1], true)) {

                // Every value below is request text. Without LogSafe::context() a
                // `table` containing %0A would close this record and open a forged
                // one, so the log that exists to show refusals could be used to
                // manufacture them.
                \Log::warning('Rejected a toggle-status request outside the allow-list', LogSafe::context([
                    'user' => optional(auth()->user())->getKey(),
                    'table' => $table,
                    'column' => $column,
                    'id_column' => $idColumn,
                    'status' => $status,
                ]));

                return response()->json([
                    'message' => 'This status change is not permitted.',
                ], 422);
            }

            // Most rows in this list are reference masters whose own screens are
            // reachable by any signed-in user, so gating them here would only make
            // the switch 403 on a page the user can still open. Five are different:
            // flipping user_role_master or fc_registration_master changes who can do
            // what, and news / notices_notification decide what the institute
            // publishes. Those carry admin_only and are refused to everyone but the
            // two roles the application already treats as administrators
            // (authorizeAdmin() in the Setup controllers uses the same pair).
            //
            // This closes the escalation path, not the whole of Trap 29: the
            // remaining tables stay behind `auth` alone until the sidebar permission
            // model covers their screens, and that is still the Engineering lead's
            // call to make rather than this endpoint's.
            //
            // The check reads the role tables, not hasRole(): that helper answers
            // from the session list written at login, so an administrator whose
            // role is revoked would keep these switches until they log out. The
            // Admin / Super Admin entries in that session list are copied from
            // these same Spatie roles at login, so a current administrator gets
            // the same answer either way.
            $actor = auth()->user();
            $isAdministrator = $actor !== null
                && $actor->roles()->whereIn('name', ['Admin', 'Super Admin', 'SuperAdmin'])->exists();

            if (($allowed['admin_only'] ?? false) && ! $isAdministrator) {
                \Log::warning('Refused a toggle-status request on a privileged table', LogSafe::context([
                    'user' => optional(auth()->user())->getKey(),
                    'table' => $table,
                    'column' => $column,
                ]));

                return response()->json([
                    'message' => 'You do not have permission to change this record.',
                ], 403);
            }

            // Course Master and Group Mapping rows are scoped per account: their
            // grids show only the courses get_Role_by_course() returns (empty means
            // every course). The switch must not reach further than the grid does,
            // or a course-scoped account could deactivate a course it cannot see.
            if (! $this->toggleTargetInCourseScope($table, $id)) {
                \Log::warning('Refused a toggle-status request outside the actor\'s course scope', LogSafe::context([
                    'user' => optional(auth()->user())->getKey(),
                    'table' => $table,
                    'id' => (string) $id,
                ]));

                return response()->json([
                    'message' => 'You do not have permission to change this record.',
                ], 403);
            }

            $status = (int) $status;

            $previous = DB::table($table)->where($idColumn, $id)->value($column);

            DB::table($table)
                ->where($idColumn, $id)
                ->update([$column => $status]);

            // The refusals above are logged; so is every change that goes through,
            // or the log would show only the requests that changed nothing. Table
            // and column are allow-listed by now; id is still request text.
            \Log::info('Toggle-status change', LogSafe::context([
                'user' => optional($actor)->getKey(),
                'table' => $table,
                'column' => $column,
                'id' => (string) $id,
                'from' => $previous,
                'to' => $status,
                'privileged' => (bool) ($allowed['admin_only'] ?? false),
            ]));

            if ($table === 'employee_type_master') {
                EmployeeTypeMasterDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'employee_master') {
                MemberDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'faculty_expertise_master') {
                FacultyExpertiseMasterController::bumpListCacheEpoch();
            }
            if ($table === 'faculty_master') {
                FacultyDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'user_role_master') {
                RoleDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'venue_master') {
                VenueMasterController::bumpIndexCacheEpoch();
            }
            if ($table === 'course_master') {
                CourseMasterDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'group_type_master_course_master_map') {
                GroupMappingDataTable::bumpListingCacheEpoch();
            }
            if ($table === 'faculty_type_master') {
                FacultyTypeMasterController::bumpListCacheEpoch();
            }
            /* CENTCOM grids are server-side: their cached page snapshots are keyed by
               search + sort, and both can depend on status (sorting by the Status
               column, or a search term that matches the status pill). Without these
               bumps a toggled row keeps its old position until the TTL expires. */
            if ($table === 'issue_category_master') {
                IssueCategoryController::bumpIndexListCacheEpoch();
                // The matrix lists ACTIVE categories only, so it changes shape too.
                IssueEscalationMatrixController::bumpEscalationMatrixListCacheEpoch();
            }
            if ($table === 'issue_sub_category_master') {
                IssueSubCategoryController::bumpIndexListCacheEpoch();
            }
            if ($table === 'issue_priority_master') {
                IssuePriorityController::bumpIndexListCacheEpoch();
            }

            $newState = ((int) $status === 1) ? 'Active' : 'Inactive';
            session()->flash('success', "Status updated to {$newState}.");

            return response()->json([
                'message' => "Status updated to {$newState}.",
                'state' => $newState,
            ]);
        } catch (\Exception $e) {
            \Log::error('Toggle status error: '.$e->getMessage());

            // The exception text stays in the log only: a QueryException message
            // carries the rendered SQL, the bound values and the server's error.
            return response()->json([
                'message' => 'Status could not be updated.',
            ], 500);
        }
    }

    public function assignRole($id)
    {
        try {
            $decryptedId = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Invalid user ID. Please try again.');
        }

        $user = User::findOrFail($decryptedId);

        $userRoles = $user->roles()->pluck('id')->toArray();

        return view('admin.user_management.users.assign_role',
            compact('user', 'userRoles'));
    }

    public function getAllRoles()
    {
        $roles = Role::all();

        return response()->json($roles);
    }

    public function assignRoleSave(Request $request)
    {
        // The route carries EnsureMenuPermission:users. This re-check is here because
        // the method grants any role, Super Admin included, and a controller is the
        // one place a later route edit cannot quietly un-gate (PR #309 review F-073:
        // on `main` this route carries `auth` alone and any account can make itself
        // Super Admin).
        abort_unless(hasMenuPermission('users'), 403, 'You do not have permission to assign roles.');

        $request->validate([
            'user_id' => 'required|integer|exists:user_credentials,pk',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // The `users` permission admits more than Super Admin, and syncRoles() below
        // writes whatever role ids are posted - so without this a `users` holder could
        // post its own pk with the Super Admin role id and become Super Admin, which
        // then bypasses every EnsureMenuPermission gate (it admits
        // isSidebarPrivilegedUser() before it reads a permission). Same guard as PR #311
        // (18a676afb). Deliberately narrow: a caller who is not Super Admin may not
        // CHANGE anyone's Super Admin membership in either direction - removing it
        // would let a `users` holder strand the only accounts able to undo that.
        //
        // `users` IS A SUPER-ADMIN-GRADE PERMISSION (PR #309 review F-076, decided
        // 2026-09-25: keep and document). Apart from Super Admin itself, a holder may
        // give ANY role to ANY account, its own included, so it effectively holds every
        // permission any role carries. Grant it only to accounts you would make Super
        // Admin.
        if (! isSidebarPrivilegedUser()) {
            $target = User::find($request->user_id);
            $requestedRoleNames = Role::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();

            $wouldHoldSuperAdmin = in_array('Super Admin', $requestedRoleNames, true);
            $holdsSuperAdmin = $target ? $target->hasRole('Super Admin') : false;

            abort_if($wouldHoldSuperAdmin !== $holdsSuperAdmin, 403, 'Only a Super Admin may grant or revoke the Super Admin role.');
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $roleNames = Role::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();
            $user->syncRoles($roleNames);

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            self::bumpAdminUsersIndexCacheEpoch();

            // PR #319 review, F-031, restored after the merge with main's permissions
            // rewrite (which removed the block below's original, permanently-dead
            // version — see git history — rather than repair it, and left a note that
            // it belongs in its own change with $user/$roleNames, which already exist
            // in this scope).
            //
            // PR #319 re-review F-052: $user->user_id is only an employee_master.pk for
            // an 'E'-category login (see MemberController::authorizeMemberRecord()'s F-038
            // docblock) -- a non-'E' account's user_id can collide with an unrelated real
            // employee's pk. Without this check, assigning a role (potentially Super
            // Admin) to a non-employee account could deliver the "Role Assigned"
            // notification to that unrelated employee instead of, or as well as, the
            // actual assignee.
            if (! empty($roleNames) && $user->user_id && strtoupper(trim((string) $user->user_category)) === 'E') {
                try {
                    $notificationService = app(NotificationService::class);
                    $assignedRoleList = implode(', ', $roleNames);
                    $notificationService->create(
                        (int) $user->user_id,
                        'role_assignment',
                        'Role Assignment',
                        $user->pk,
                        'Role Assigned',
                        "You have been assigned the following role(s): {$assignedRoleList}."
                    );
                } catch (\Throwable $e) {
                    // Log error but don't fail the request. \Log (root-namespace alias)
                    // rather than Log:: — this class does not import the Log facade.
                    \Log::error('Failed to send role assignment notification: '.$e->getMessage());
                }
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'Roles assigned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    // public function assignRoleSave(Request $request)
    // {
    //     dd($request->all());
    //     $request->validate([
    //         'user_id' => 'required|integer',
    //         'roles'   => 'nullable|array',
    //     ]);

    //     $userId = $request->user_id;

    //     \DB::beginTransaction();

    //     try {

    //         // Remove old roles
    //         \DB::table('employee_role_mapping')
    //             ->where('user_credentials_pk', $userId)
    //             ->delete();

    //         // Insert new roles
    //         $assignedRoleNames = [];
    //         $roleIds = $request->input('roles', []);

    //         if (empty($roleIds)) {
    //             // Default RBAC role when roles are submitted empty.
    //             $staffRole = UserRoleMaster::where('user_role_name', 'Staff')
    //                 ->orWhere('user_role_display_name', 'Staff')
    //                 ->first();
    //             if ($staffRole) {
    //                 $roleIds = [$staffRole->pk];
    //             }
    //         }

    //         if (!empty($roleIds)) {
    //             foreach ($roleIds as $roleId) {
    //                 \DB::table('employee_role_mapping')->insert([
    //                     'user_credentials_pk'  => $userId,
    //                     'user_role_master_pk'  => $roleId,
    //                     'active_inactive'      => 1,
    //                     'created_date'         => now(),
    //                     'updated_date'        => now(),
    //                 ]);
    //                 // Get role name for notification
    //                 $role = UserRoleMaster::find($roleId);
    //                 if ($role) {
    //                     $assignedRoleNames[] = $role->user_role_display_name ?? $role->user_role_name;
    //                 }
    //             }
    //         }

    //         \DB::commit();

    //         // Send notification to the user if roles were assigned
    //         if (!empty($assignedRoleNames)) {
    //             try {
    //                 // Get user_id from user_credentials table
    //                 $userCredential = \DB::table('user_credentials')
    //                     ->where('pk', $userId)
    //                     ->first();

    //                 if ($userCredential && $userCredential->user_id) {
    //                     $notificationService = app(NotificationService::class);
    //                     $roleNames = implode(', ', $assignedRoleNames);
    //                     $notificationService->create(
    //                         (int)$userCredential->user_id,
    //                         'role_assignment',
    //                         'Role Assignment',
    //                         $userId,
    //                         'Role Assigned',
    //                         "You have been assigned the following role(s): {$roleNames}."
    //                     );
    //                 }
    //             } catch (\Exception $e) {
    //                 // Log error but don't fail the request
    //                 \Log::error('Failed to send role assignment notification: ' . $e->getMessage());
    //             }
    //         }

    //         return redirect()->route('admin.users.index')
    //                          ->with('success', 'Roles assigned successfully.');

    //     } catch (\Exception $e) {
    //         \DB::rollBack();
    //         return back()->with('error', 'Error: '.$e->getMessage());
    //     }
    // }

    public function uploadPdf(Request $request)
    {
        if ($request->hasFile('file')) {

            $file = $request->file('file');

            // Allow only PDF — checked against the file's CONTENT, not its name.
            //
            // The previous check read getClientOriginalExtension(), which the uploader
            // controls, while store() below names the saved file from guessExtension(),
            // which it does not. The two disagreeing meant an HTML document uploaded as
            // "notes.pdf" passed this gate and was then written as <hash>.html onto the
            // PUBLIC disk, where the browser renders it as markup on our own origin.
            // Validating the content closes both halves at once.
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['file' => $file],
                ['file' => ['required', 'file', 'mimes:pdf', 'max:20480']]
            );

            if ($validator->fails()) {
                return response()->json(['error' => 'Only PDF files allowed'], 422);
            }

            $path = $file->store('summernote/pdf', 'public');

            return response()->json([
                'location' => asset('storage/'.$path),
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function change_password()
    {
        return view('admin.password.change_password');

    }

    public function submit_change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                'min:8',
                // Strong password policy (CWE-521): upper + lower + number + special char.
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ], [
            'new_password.regex' => 'Password must include uppercase, lowercase, a number and a special character.',
        ]);
        try {
            $user = Auth::user();
            $username = $user->user_name;

            // 🔹 Verify old password first
            if (! Adldap::auth()->attempt($username, $request->current_password)) {
                return back()
                    ->withErrors([
                        'current_password' => 'Current password is incorrect',
                    ]);
            }

            // 🔹 Find LDAP user
            $ldapUser = Adldap::search()->users()->find($username);

            if (! $ldapUser) {
                return back()->withErrors(['error' => 'LDAP user not found']);
            }

            // 🔹 Change password in LDAP
            $ldapUser->setPassword($request->new_password);

            // 🔹 OPTIONAL: Update local password if stored
            $user->jbp_password = Hash::make($request->new_password);
            $user->save();

            // return redirect()
            //     ->route('profile')
            //     ->with('success', 'Password changed successfully');
            return back()->with('success', 'Password changed successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'ldap_error' => 'LDAP Error: '.$e->getMessage(),
                ]);
        }

    }

    /**
     * Get today's timetable for a specific faculty member
     *
     * @param  int  $facultyUserId
     * @return Collection
     */
    private function getTodayTimetableForFaculty($facultyUserId)
    {
        $today = Carbon::today()->toDateString();

        // Get faculty_master.pk from user_id
        $faculty = FacultyMaster::where('employee_master_pk', $facultyUserId)->first();

        if (! $faculty) {
            return collect([]);
        }

        $facultyPk = $faculty->pk;

        // Simple query: get today's classes assigned to this faculty
        $timetableEntries = CalendarEvent::where('active_inactive', 1)
            ->whereDate('START_DATE', '<=', $today)
            ->whereDate('END_DATE', '>=', $today)
            ->where(function ($query) use ($facultyPk) {
                $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$facultyPk.'"'])
                    ->orWhere('faculty_master', $facultyPk);
            })
            ->with(['faculty', 'venue', 'classSession'])
            ->orderBy('class_session')
            ->get();

        // Format the timetable data
        return $timetableEntries->map(function ($entry, $index) {
            // Format session time based on session_type
            $sessionTime = 'N/A';
            if ($entry->session_type == 1) {
                // session_type 1: class_session is a reference to class_session_master
                if ($entry->classSession) {
                    // Try to get time from class_session_master
                    if (isset($entry->classSession->start_time) && isset($entry->classSession->end_time)) {
                        $sessionTime = $entry->classSession->start_time.' - '.$entry->classSession->end_time;
                    } elseif (isset($entry->classSession->shift_time)) {
                        $sessionTime = $entry->classSession->shift_time;
                    } else {
                        $sessionTime = $entry->class_session ?? 'N/A';
                    }
                } else {
                    $sessionTime = $entry->class_session ?? 'N/A';
                }
            } else {
                // session_type 2: class_session is a manual time string (e.g., "10:00 AM - 11:30 AM")
                $sessionTime = $entry->class_session ?? 'N/A';
            }

            // Format date
            $sessionDate = $entry->START_DATE ? Carbon::parse($entry->START_DATE)->format('Y-m-d') : '';

            // Handle faculty name - faculty_master can be JSON array or single ID
            $facultyName = 'N/A';
            if ($entry->faculty_master) {
                // Check if it's JSON array
                $facultyIds = json_decode($entry->faculty_master, true);
                if (is_array($facultyIds) && ! empty($facultyIds)) {
                    // Get all faculty names from JSON array
                    $facultyNames = FacultyMaster::whereIn('pk', $facultyIds)
                        ->pluck('full_name')
                        ->filter()
                        ->toArray();
                    $facultyName = ! empty($facultyNames) ? implode(', ', $facultyNames) : 'N/A';
                } elseif ($entry->faculty) {
                    // Single ID - use relationship
                    $facultyName = $entry->faculty->full_name ?? 'N/A';
                }
            }

            return [
                'sno' => $index + 1,
                'session_time' => $sessionTime,
                'topic' => $entry->subject_topic ?? 'N/A',
                'faculty_name' => $facultyName,
                'session_date' => $sessionDate,
                'session_venue' => $entry->venue ? $entry->venue->venue_name : 'N/A',
            ];
        });
    }

    /**
     * Get today's timetable for a specific student
     *
     * @param  int  $studentId
     * @return Collection
     */
    private function getTodayTimetableForStudent($studentId)
    {
        $today = Carbon::today()->toDateString();

        // Get student's group mappings
        $studentGroupMaps = StudentCourseGroupMap::with('groupTypeMasterCourseMasterMap')
            ->where('student_master_pk', $studentId)
            ->get();

        if ($studentGroupMaps->isEmpty()) {
            return collect([]);
        }

        // Extract group IDs from student's group mappings
        $groupIds = $studentGroupMaps->pluck('groupTypeMasterCourseMasterMap.pk')
            ->filter()
            ->toArray();
        if (empty($groupIds)) {
            return collect([]);
        }

        // Query timetable entries for today that match the student's groups
        // group_name is stored as JSON array, so we need to check if any of the student's group IDs are in that array
        $timetableEntries = CalendarEvent::where('active_inactive', 1)
            ->whereDate('START_DATE', '<=', $today)
            ->whereDate('END_DATE', '>=', $today)
            ->where(function ($query) use ($groupIds) {
                foreach ($groupIds as $groupId) {
                    // Use JSON_CONTAINS to check if group ID exists in the JSON array
                    // This handles both string and numeric formats
                    $query->orWhereRaw('JSON_CONTAINS(group_name, ?)', ['"'.$groupId.'"']);
                }
            })
            ->with(['faculty', 'venue', 'classSession'])
            ->orderBy('class_session')
            ->get();

        // Format the timetable data
        return $timetableEntries->map(function ($entry, $index) {
            // Format session time based on session_type
            $sessionTime = 'N/A';
            if ($entry->session_type == 1) {
                // session_type 1: class_session is a reference to class_session_master
                if ($entry->classSession) {
                    // Try to get time from class_session_master
                    if (isset($entry->classSession->start_time) && isset($entry->classSession->end_time)) {
                        $sessionTime = $entry->classSession->start_time.' - '.$entry->classSession->end_time;
                    } elseif (isset($entry->classSession->shift_time)) {
                        $sessionTime = $entry->classSession->shift_time;
                    } else {
                        $sessionTime = $entry->class_session ?? 'N/A';
                    }
                } else {
                    $sessionTime = $entry->class_session ?? 'N/A';
                }
            } else {
                // session_type 2: class_session is a manual time string (e.g., "10:00 AM - 11:30 AM")
                $sessionTime = $entry->class_session ?? 'N/A';
            }

            // Format date
            $sessionDate = $entry->START_DATE ? Carbon::parse($entry->START_DATE)->format('Y-m-d') : '';

            // Handle faculty name - faculty_master can be JSON array or single ID
            $facultyName = 'N/A';
            if ($entry->faculty_master) {
                // Check if it's JSON array
                $facultyIds = json_decode($entry->faculty_master, true);
                if (is_array($facultyIds) && ! empty($facultyIds)) {
                    // Get all faculty names from JSON array
                    $facultyNames = FacultyMaster::whereIn('pk', $facultyIds)
                        ->pluck('full_name')
                        ->filter()
                        ->toArray();
                    $facultyName = ! empty($facultyNames) ? implode(', ', $facultyNames) : 'N/A';
                } elseif ($entry->faculty) {
                    // Single ID - use relationship
                    $facultyName = $entry->faculty->full_name ?? 'N/A';
                }
            }

            return [
                'sno' => $index + 1,
                'session_time' => $sessionTime,
                'topic' => $entry->subject_topic ?? 'N/A',
                'faculty_name' => $facultyName,
                'session_date' => $sessionDate,
                'session_venue' => $entry->venue ? $entry->venue->venue_name : 'N/A',
            ];
        });
    }

    /**
     * Course IDs where the faculty is CC or ACC.
     */
    private function getCoordinatorCourseIds(int $facultyPk)
    {
        return CourseCordinatorMaster::where(function ($query) use ($facultyPk) {
            $query->where('Coordinator_name', $facultyPk)
                ->orWhere('Assistant_Coordinator_name', $facultyPk)
                ->orWhereRaw('FIND_IN_SET(?, Assistant_Coordinator_name)', [$facultyPk]);
        })->pluck('courses_master_pk')->unique();
    }

    /**
     * Active course IDs where the faculty is CC or ACC.
     */
    private function getActiveCoordinatorCourseIds(int $facultyPk)
    {
        $coordinatorCourses = $this->getCoordinatorCourseIds($facultyPk);
        if ($coordinatorCourses->isEmpty()) {
            return collect([]);
        }

        return CourseMaster::whereIn('pk', $coordinatorCourses)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', now()->toDateString())
            ->pluck('pk');
    }

    /**
     * Whether a faculty user may view a student's detail page.
     */
    private function canFacultyViewStudent(int $facultyPk, int $studentPk): bool
    {
        $activeCoordinatorCourses = $this->getActiveCoordinatorCourseIds($facultyPk);
        if ($activeCoordinatorCourses->isNotEmpty()) {
            $enrolled = StudentMasterCourseMap::where('student_master_pk', $studentPk)
                ->whereIn('course_master_pk', $activeCoordinatorCourses)
                ->where('active_inactive', 1)
                ->exists();

            if ($enrolled) {
                return true;
            }
        }

        $groupMappings = DB::table('group_type_master_course_master_map')
            ->where('facility_id', $facultyPk)
            ->where('active_inactive', 1)
            ->get();

        if ($groupMappings->isEmpty()) {
            return false;
        }

        $activeCourseIds = CourseMaster::whereIn('pk', $groupMappings->pluck('course_name')->unique())
            ->where('active_inactive', 1)
            ->where('end_date', '>=', now()->toDateString())
            ->pluck('pk');

        if ($activeCourseIds->isEmpty()) {
            return false;
        }

        $activeGroupMappingPks = $groupMappings
            ->whereIn('course_name', $activeCourseIds)
            ->pluck('pk')
            ->unique();

        return StudentCourseGroupMap::where('student_master_pk', $studentPk)
            ->whereIn('group_type_master_course_master_map_pk', $activeGroupMappingPks)
            ->where('active_inactive', 1)
            ->exists();
    }
}
