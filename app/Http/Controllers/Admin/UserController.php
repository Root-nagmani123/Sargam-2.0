<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CourseMasterDataTable;
use App\DataTables\FacultyDataTable;
use App\DataTables\GroupMappingDataTable;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\DataTables\MemberDataTable;
use App\DataTables\RoleDataTable;
use App\Http\Controllers\Admin\Master\FacultyExpertiseMasterController;
use App\Http\Controllers\Admin\Master\FacultyTypeMasterController;
use App\DataTables\UserCredentialsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\User;
use App\Models\UserRoleMaster;
use App\Models\EmployeeMaster;
use App\Models\EmployeeRoleMapping;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\Holiday;
use App\Services\NotificationService;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;

use Adldap\Laravel\Facades\Adldap;
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
use App\Models\DashboardCard;
use App\Models\CalendarEvent;
use App\Models\ClassSessionMaster;
use App\Models\VenueMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\StudentMaster;
use App\Services\FC\RegistrationService;
use App\Models\CourseStudentAttendance;
use App\Models\CourseGroupTimetableMapping;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityDupPermIdApply;
use App\Models\SecurityFamilyIdApply;
use App\Models\SecurityFamilyIdApplyApproval;
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassFWApply;
use App\Models\VehiclePassTWApplyApproval;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;


class UserController extends Controller
{
    private const ADMIN_USERS_INDEX_LIST_EPOCH_KEY = 'admin_users_index_list_epoch';

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
     * @return \Illuminate\View\View
     */
    public function dashboard(Request $request)
    {
         $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // Fetch holidays for the selected month/year
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $holidays = Holiday::active()
            ->whereBetween('holiday_date', [$startDate, $endDate])
            ->get();

        // Format events array with holidays
        $events = [];
        foreach ($holidays as $holiday) {
            $dateKey = $holiday->holiday_date->format('Y-m-d');
            if (!isset($events[$dateKey])) {
                $events[$dateKey] = [];
            }
            $events[$dateKey][] = [
                'title' => $holiday->holiday_name,
                'type' => 'holiday',
                'holiday_type' => $holiday->holiday_type,
                'description' => $holiday->description
            ];
        }

        // Add logged-in user's birthday to calendar
        $currentUser = Auth::user();
        $userEmployee = $currentUser ? EmployeeMaster::where('pk', $currentUser->user_id)->where('status', 1)->first() : null;
        if ($userEmployee && $userEmployee->dob) {
            $dob = \Carbon\Carbon::parse($userEmployee->dob);
            $birthdayThisYear = $dob->copy()->year($year);
            if ((int) $birthdayThisYear->month === (int) $month) {
                $bdKey = $birthdayThisYear->format('Y-m-d');
                if (!isset($events[$bdKey])) {
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
          $myDob = \Carbon\Carbon::parse($userEmployee->dob);
          $isMyBirthday = $myDob->format('m-d') === now()->format('m-d');
      }

      // Count wishes received today (birthday notifications for logged-in user)
      $myBirthdayWishCount = 0;
      if ($isMyBirthday) {
          $myBirthdayWishCount = \App\Models\Notification::where('receiver_user_id', Auth::user()->user_id)
              ->where('type', 'birthday')
              ->whereDate('created_at', today())
              ->count();
      }

      // Wish count per birthday person (how many wishes they received today)
      $birthdayWishCounts = [];
      if ($emp_dob_data->isNotEmpty()) {
          $birthdayPks = $emp_dob_data->pluck('pk')->toArray();
          $birthdayWishCounts = \App\Models\Notification::whereIn('receiver_user_id', $birthdayPks)
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

      $totalActiveCourses = CourseMaster::where('active_inactive', 1)->where('start_year', '<', now())->where('end_date', '>=', now())->count();
      $upcomingCourses = CourseMaster::where('active_inactive', 1)->where('start_year', '>', now())->count();
      $upcomingEventsCount = Holiday::active()->where('holiday_date', '>', now())->count();



       $total_guest_faculty = FacultyMaster::where('active_inactive', 1)->where('faculty_type', 2)->count();
       $total_internal_faculty = FacultyMaster::where('active_inactive', 1)->where('faculty_type', 1)->count();
//   print_r($emp_data);exit;
        $exemptionCount = 0;
        $MDO_count = 0;
        $todayTimetable = collect([]);
        $totalSessions = 0;
        $totalStudents = 0;
        $isCCorACC = false;
        $userId = Auth::user()->user_id;
         if(hasRole('Student-OT')){
             $exemptionQuery = StudentMedicalExemption::where('student_master_pk', $userId)
                ->where('active_inactive', 1);
            $exemptionCount = $exemptionQuery->count();

              $MDO_count = MDOEscotDutyMap::where('selected_student_list', $userId)
            ->with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->count();

            // Fetch today's timetable for the logged-in student
            $todayTimetable = $this->getTodayTimetableForStudent($userId);
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
                 $totalStudents = StudentMasterCourseMap::query()
                     ->where('active_inactive', 1)
                     ->when(! empty($roleCourseIds), fn ($q) => $q->whereIn('course_master_pk', $roleCourseIds))
                     ->distinct('student_master_pk')
                     ->count('student_master_pk');
             } else {
                 $totalSessions = 0;
             }

             // Fetch today's timetable for the logged-in faculty
             $todayTimetable = $this->getTodayTimetableForFaculty($userId);
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
        $isFacultyRole  = hasRole('Internal Faculty') || hasRole('Guest Faculty');

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
            'total_inhouse_faculty'   => ['count' => $total_internal_faculty,                      'link' => route('admin.dashboard.inhouse_faculty'),                      'visible' => !$isSecurityRole && !$isStudentOT],
            'session_details'         => ['count' => $totalSessions,                               'link' => route('admin.dashboard.sessions'),                             'visible' => !$isSecurityRole && ($isFacultyRole || $isSuperAdmin)],
            'total_students'          => ['count' => $totalStudents,                               'link' => route('admin.dashboard.students'),                             'visible' => !$isSecurityRole && (isset($isCCorACC) && $isCCorACC)],
            'student_details'         => ['count' => $totalStudents,                               'link' => route('admin.dashboard.students'),                             'visible' => !$isSecurityRole && (isset($isCCorACC) && $isCCorACC)],
            'my_course_participant'   => ['count' => StudentMasterCourseMap::query()->when(!empty($myCourseIds), fn($q) => $q->whereIn('course_master_pk', $myCourseIds))->count(), 'link' => route('my.course.participant'),                                'visible' => true],
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

        $enabledWidgetKeys = $baseCards->filter(fn($c) => str_starts_with($c->key, 'widget_'))->pluck('key')->toArray();

        $cardsToRender = $baseCards->filter(fn($c) => !str_starts_with($c->key, 'widget_'))->map(function ($card) use ($cardDefinitions, $cardCounts) {
            $def = $cardDefinitions[$card->key] ?? null;
            return [
                'key'         => $card->key,
                'label'       => $card->label,
                'icon'        => $card->icon,
                'color_class' => $card->color_class,
                'link'        => $def['link'] ?? null,
                'count'       => $def['count'] ?? ($cardCounts[$card->key] ?? 0),
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
            'todayTimetable',
            'totalSessions',
            'totalStudents',
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
            'enabledWidgetKeys'
        ));
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

        $data = $this->buildDashboardFeedData();
        $data['activeTab'] = $activeTab;

        return view('admin.dashboard.feed', $data);
    }

    /**
     * Shared data for dashboard feed page.
     */
    protected function buildDashboardFeedData(): array
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
            $birthdayWishCounts = \App\Models\Notification::whereIn('receiver_user_id', $birthdayPks)
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

        $notices = get_notice_notification_by_role();

        $noticeTabKeys = ['office-orders', 'work-allocation', 'notice-circular'];
        $noticeTabLabels = [
            'office-orders' => 'Office Orders',
            'work-allocation' => 'Work Allocation',
            'notice-circular' => 'Notice/ Circular/ Order',
        ];
        $noticeTabCounts = ['office-orders' => 0, 'work-allocation' => 0, 'notice-circular' => 0];
        foreach ($notices as $noticeForTab) {
            $tabKey = $this->resolveDashboardNoticeTabKey($noticeForTab->notice_type ?? '');
            $noticeTabCounts[$tabKey]++;
        }
        $defaultNoticeTab = 'office-orders';
        foreach ($noticeTabKeys as $tabKeyCandidate) {
            if ($noticeTabCounts[$tabKeyCandidate] > 0) {
                $defaultNoticeTab = $tabKeyCandidate;
                break;
            }
        }

        $feedExpandedNotifications = collect();
        $feedExpandedWishes = collect();
        $notificationBadgeCount = 0;

        if ($user && $user->user_id) {
            $feedNotificationsQuery = \App\Models\Notification::with('sender')
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
            'noticeTabKeys',
            'noticeTabLabels',
            'noticeTabCounts',
            'defaultNoticeTab',
            'feedExpandedNotifications',
            'feedExpandedWishes',
            'notificationBadgeCount'
        );
    }

    public function resolveDashboardNoticeTabKey(?string $type): string
    {
        $t = strtolower((string) ($type ?? ''));
        if (str_contains($t, 'office order')) {
            return 'office-orders';
        }
        if (str_contains($t, 'course notice')) {
            return 'work-allocation';
        }

        return 'notice-circular';
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

        $isApproval2 = hasRole('Security Card') && !hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && !hasRole('Security Card');

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

        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && !hasRole('Security Card');
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
            return $r->emp_id_apply . '|' . ($r->created_by ?? '') . '|' . $date;
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

        $isLevel1 = hasRole('Security Card') && !hasRole('Admin Security');
        $isLevel2 = hasRole('Admin Security') && !hasRole('Security Card');
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
        $isApproval2 = hasRole('Security Card') && !hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && !hasRole('Security Card');

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
        $isApproval2 = hasRole('Security Card') && !hasRole('Admin Security');
        $isApproval3 = hasRole('Admin Security') && !hasRole('Security Card');

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

    /**
     * Display student list for CC/ACC faculty
     *
     * @return \Illuminate\View\View
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

        $payload = $this->resolveDashboardStudentListPayload($request);
        $students = $payload['students'];
        $availableCourses = $payload['availableCourses'];
        $facultyPk = $payload['facultyPk'];

        if ($request->ajax() && $request->has('draw')) {
            return $this->dashboardStudentListDataTableResponse($request, $students);
        }

        // Attendance summary cards (top of page) reflect TODAY's actual marked
        // attendance only — distinct students marked in a session dated today —
        // independent of the table's live filters. A student with no session
        // marked today is simply not counted here (unlike the per-row roster
        // default further down, which shows unmarked students as "Present").
        $todayStr = now()->toDateString();
        $scopePks = $students->pluck('student_master_pk')->filter()->unique()->values()->all();
        $cardCounts = [
            'total' => count($scopePks),
            'present_today' => 0,
            'absent_today' => 0,
        ];
        // The Present/Absent cards reflect the CURRENT date (the list opens scoped
        // to today). On an off-day with no marked session the counts simply read 0.
        $snapshotDate = $todayStr;
        if (! empty($scopePks)) {
            $dayAttendance = DB::table('course_student_attendance as a')
                ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
                ->whereIn('a.Student_master_pk', $scopePks)
                ->whereDate('t.START_DATE', $snapshotDate)
                ->get(['a.Student_master_pk as spk', 'a.status']);

            // Count per distinct student (a student can have several sessions that
            // day). Only genuinely MARKED sessions count (status 0 = not marked is
            // ignored). Present = attended any non-absent session; Absent = had any
            // Absent (status 3) session. A student with both counts in BOTH — mirrors
            // the Present/Absent tabs (see collapseDateScopedAttendance()).
            $presentSpks = [];
            $absentSpks = [];
            foreach ($dayAttendance->groupBy('spk') as $spk => $rows) {
                $marked = $rows->filter(fn ($r) => (int) $r->status !== 0);
                if ($marked->isEmpty()) {
                    continue;
                }
                if ($marked->contains(fn ($r) => (int) $r->status !== 3)) {
                    $presentSpks[$spk] = true;
                }
                if ($marked->contains(fn ($r) => (int) $r->status === 3)) {
                    $absentSpks[$spk] = true;
                }
            }
            // Students on PT Exemption / Stationed Leave today are absent-with-reason
            // even without an attendance record — mirror the Absent list.
            foreach ($this->leaveBasedAbsentees($scopePks, $snapshotDate, $snapshotDate) as $spk => $d) {
                $absentSpks[$spk] = true;
            }
            $cardCounts['present_today'] = count($presentSpks);
            $cardCounts['absent_today'] = count($absentSpks);
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
            ->where('cm.end_date', '>=', now())
            ->where('fm.active_inactive', 1);

        // Filter by logged-in faculty if available
        if ($facultyPk) {
            $counsellorTypesQuery->where('gmap.facility_id', $facultyPk);
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
                    'faculty_name' => $gmap->Faculty->full_name ?? ('Faculty #' . $gmap->facility_id),
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
            ->where('cm.end_date', '>=', now())
            ->where('fm.active_inactive', 1);

        // Filter by logged-in faculty if available
        if ($facultyPk) {
            $groupMapCoursesQuery->where('gmap.facility_id', $facultyPk);
        }

        $groupMapCourses = $groupMapCoursesQuery
            ->select(
                'cm.pk',
                'cm.course_name'
            )
            ->distinct()
            ->get()
            ->map(function($course) {
                return [
                    'pk' => $course->pk,
                    'course_name' => $course->course_name
                ];
            });

        // Merge courses from students and group_type_master_course_master_map
        $availableCourses = $availableCourses->merge($groupMapCourses)
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
            ->where('cm.end_date', '>=', now())
            ->where('fm.active_inactive', 1)
            ->whereNotNull('gmap.group_name')
            ->where('gmap.group_name', '!=', '');

        // Filter by logged-in faculty if available
        if ($facultyPk) {
            $groupNamesQuery->where('gmap.facility_id', $facultyPk);
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

        // Session (time slot) / Topic options for their filter dropdowns. Built
        // independent of the Time Period filter — see resolveScopedSessionOptions().
        $scopedStudentPks = $students->pluck('student_master_pk')->filter()->unique()->values()->all();
        $sessionOptions = $this->resolveScopedSessionOptions($scopedStudentPks);
        $topicOptions = $this->resolveScopedTopicOptions($scopedStudentPks);

        // OT / Participant options (each distinct student) for that filter dropdown.
        $participantOptions = $students
            ->map(function ($m) {
                $s = $m->studentMaster;
                if (! $s) {
                    return null;
                }
                $name = $s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                $code = $s->generated_OT_code ?? '';
                return (object) [
                    'pk' => (string) $s->pk,
                    'label' => trim(($code !== '' ? $code . ' — ' : '') . $name),
                ];
            })
            ->filter()->unique('pk')->sortBy('label')->values();

        // Server-side filters (Course / ACC / Group / Cadre / House / Session / Participant).
        $students = $this->applyDashboardStudentListFilters($students, $request);

        // Tab counts (All / Present / Absent) for the initial render; the DataTable
        // response refreshes them live on every filter change. A date range
        // (Present/Absent Today cards, or Time Period filter) switches Present/Absent
        // to one-row-per-student, marked-only buckets matching the dashboard cards.
        if ($request->filled('from_date') || $request->filled('to_date')) {
            [$presentStudents, $absentStudents] = $this->collapseDateScopedAttendance($students);
        } else {
            // Present/Absent details require a date; without one these tabs are empty.
            $presentStudents = collect();
            $absentStudents = collect();
        }

        $tabCounts = [
            'all' => $students->count(),
            'present' => $presentStudents->count(),
            'absent' => $absentStudents->count(),
        ];

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
        ];

        return view('admin.dashboard.student_list', compact('students', 'presentStudents', 'absentStudents', 'availableCourses', 'counsellorTypes', 'counsellorFaculties', 'groupNames', 'dutyTypes', 'filters', 'cadreOptions', 'houseOptions', 'sessionOptions', 'topicOptions', 'participantOptions', 'tabCounts', 'cardCounts', 'snapshotDate', 'listTitle'));
    }

    /**
     * OT / Participants List — the drill-down opened from the "OT/ Participants
     * Attendance" dashboard card. One row per participant (not per session),
     * split into Present / Absent tabs, with per-student Medical Exemption,
     * PT Exception and Stationed Leave counts.
     */
    public function otParticipantsList(Request $request)
    {
        // Skip the payload's per-student total_* / notice-memo N+1 loop — this page
        // computes its counts separately via otParticipantsRowMeta (batched).
        $payload = $this->resolveDashboardStudentListPayload($request, false);
        $availableCourses = $payload['availableCourses'];

        // Apply the shared filters to the session rows, then collapse to one row
        // per participant — every student of the selected course is listed. The
        // Time Period filter here only scopes the count columns (see $rowMeta
        // below), so pass false to keep all students visible regardless of dates.
        $sessionRows = $this->applyDashboardStudentListFilters($payload['students'], $request, false);

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
                    'house_name' => $m->house_name ?? null,
                ];
            }
            if (empty($byStudent[$spk]->house_name) && ! empty($m->house_name)) {
                $byStudent[$spk]->house_name = $m->house_name;
            }
        }
        $participants = collect(array_values($byStudent));

        // Show every student of the selected course — no Present/Absent split.
        $rows = $participants;
        $totalParticipants = $participants->count();

        // Time Period scopes the count columns only (all students stay visible).
        $rowMeta = $this->otParticipantsRowMeta(
            $participants->pluck('student_master_pk')->all(),
            $request->input('from_date') ?: null,
            $request->input('to_date') ?: null
        );

        if ($request->ajax() && $request->has('draw')) {
            return $this->otParticipantsDataTableResponse($request, $rows, $rowMeta, $totalParticipants);
        }

        // Filter option lists (mirrors the student list page).
        $students = $payload['students'];
        $cadreOptions = $students
            ->map(fn ($m) => $m->studentMaster->cadre->cadre_name ?? null)
            ->filter()->unique()->sort()->values();
        $houseOptions = $students
            ->map(fn ($m) => $m->house_name ?? null)
            ->filter()->unique()->sort()->values();
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
                $name = $s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                $code = $s->generated_OT_code ?? '';
                return (object) [
                    'pk' => (string) $s->pk,
                    'label' => trim(($code !== '' ? $code . ' — ' : '') . $name),
                ];
            })
            ->filter()->unique('pk')->sortBy('label')->values();

        $status = $request->input('status') === 'archive' ? 'archive' : 'active';

        $filters = [
            'from_date' => (string) $request->input('from_date', ''),
            'to_date' => (string) $request->input('to_date', ''),
            'session' => (string) $request->input('session', ''),
            'participant' => (string) $request->input('participant', ''),
            'course_id' => (string) $request->input('course_id', ''),
            'cadre' => (string) $request->input('cadre', ''),
            'status' => $status,
        ];

        // Course filter scope: Super Admin / Admin / PA can pick ANY course for the
        // current tab (active = not yet ended, archive = ended); a CC/ACC only sees
        // THEIR OWN courses (already scoped to the tab by the payload's $availableCourses).
        $courseDateOp = $status === 'archive' ? '<' : '>=';
        if (hasRole('Super Admin') || hasRole('Admin') || hasRole('PA')) {
            $courseOptions = CourseMaster::where('active_inactive', '1')
                ->where('end_date', $courseDateOp, now())
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
            'sessionOptions', 'participantOptions'
        ));
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
            $rangesByKey[$r->student_master_pk . '|' . $r->leave_type][] = [$from, $to];
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
     * Server-side JSON for the OT / Participants List DataTable.
     */
    private function otParticipantsDataTableResponse(Request $request, $rows, array $rowMeta, int $totalParticipants)
    {
        $searchInput = $request->input('search');
        $search = strtolower(trim((string) (is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput)));
        if ($search !== '') {
            $rows = $rows->filter(function ($p) use ($search, $rowMeta) {
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
                    $s->cadre->cadre_name ?? '',
                    $p->house_name ?? '',
                    $p->topic ?? '',
                    $meta['duty_type'] ?? '',
                    implode(' ', $counts),
                ], fn ($v) => trim((string) $v) !== '')));
                return str_contains($haystack, $search);
            })->values();
        }

        $recordsTotal = $totalParticipants;
        $recordsFiltered = $rows->count();

        // Sorting (S.No / OT Code / Name / Email / Cadre / House).
        $columnMap = [1 => 'ot_code', 2 => 'name', 3 => 'email', 4 => 'cadre', 5 => 'house'];
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortKey = $columnMap[$orderCol] ?? null;
        if ($sortKey !== null) {
            $rows = $rows->sortBy(function ($p) use ($sortKey) {
                $s = $p->studentMaster;
                return match ($sortKey) {
                    'ot_code' => (string) ($s->generated_OT_code ?? ''),
                    'name' => (string) ($s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''))),
                    'email' => (string) ($s->email ?? ''),
                    'cadre' => (string) ($s->cadre->cadre_name ?? ''),
                    'house' => (string) ($p->house_name ?? ''),
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
            $linkDateQs .= '&from_date=' . urlencode($fdParam);
        }
        if ($tdParam !== '') {
            $linkDateQs .= '&to_date=' . urlencode($tdParam);
        }

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
            $name = $s->display_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));

            $data[] = [
                's_no' => $start + $idx + 1,
                'ot_code' => e($s->generated_OT_code ?? 'N/A'),
                'name' => '<a href="' . e($detailUrl) . '" class="sl-count">' . e($name) . '</a>',
                'email' => e($s->email ?? 'N/A'),
                'cadre' => e($s->cadre->cadre_name ?? 'N/A'),
                'house' => e($p->house_name ?: 'N/A'),
                'duty_count' => $this->otCountCell($meta['duty_count'], $detailUrl . '?section=dutiesSection' . $linkDateQs),
                'duty_type' => e($meta['duty_type'] ?: '-'),
                'medical' => $this->otCountCell($meta['medical'], $detailUrl . '?section=medicalExceptionsSection' . $linkDateQs),
                'pt' => $this->otCountCell($meta['pt'], $detailUrl . '?section=ptExemptionsSection' . $linkDateQs),
                'stationed' => $this->otCountCell($meta['stationed'], $detailUrl . '?section=stationedLeavesSection' . $linkDateQs),
                'notice_memo' => $this->otCountCell($meta['notice_memo'], $detailUrl . '?section=noticesSection' . $linkDateQs),
                'discipline_memo' => $this->otCountCell($meta['discipline_memo'], $detailUrl . '?section=memosSection' . $linkDateQs),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
            return '<a href="' . e($url) . '" class="sl-count">' . $label . '</a>';
        }

        return '<span class="sl-count">' . $label . '</span>';
    }

    /**
     * Export the dashboard student list as CSV or PDF, honouring active filters.
     */
    public function studentListExport(Request $request, string $format)
    {
        if (! in_array($format, ['csv', 'pdf', 'print'], true)) {
            abort(404);
        }

        if (! is_faculty_portal_user() && ! hasRole('Super Admin')) {
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

        // Browser-printable report: same clean layout as the PDF, rendered as
        // HTML in a new tab that auto-opens the print dialog.
        if ($format === 'print') {
            return view('admin.dashboard.export.student_list_print', [
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $this->dashboardStudentListFilterSummary($request),
            ]);
        }

        if ($format === 'pdf') {
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('admin.dashboard.export.student_list_pdf', [
                'headings' => $exportData['headings'],
                'rows' => $exportData['rows'],
                'generatedAt' => now()->format('d-m-Y H:i'),
                'filterSummary' => $this->dashboardStudentListFilterSummary($request),
            ])->setPaper('a4', 'landscape');

            return $pdf->download("{$fileBase}.pdf");
        }

        return Excel::download(
            new UsersExport($exportData['headings'], $exportData['rows']),
            "{$fileBase}.csv",
            ExcelWriter::CSV
        );
    }

    /**
     * @return array{students: \Illuminate\Support\Collection, availableCourses: \Illuminate\Support\Collection, facultyPk: int|null}
     */
    private function resolveDashboardStudentListPayload(?Request $request = null, bool $withTotals = true): array
    {
        $students = collect([]);
        $availableCourses = collect([]);
        $facultyPk = null;

        $facultyPk = get_auth_faculty_master_pk();
        $isSuperAdmin = hasRole('Super Admin');

        // Active vs Archive scope. Only the OT-participants page sends status=archive;
        // everything else (student list, export) omits it and stays on "active".
        // Archive = course has ended (end_date < today); Active = still running.
        $archive = ($request?->input('status') === 'archive');
        $dateOp = $archive ? '<' : '>=';

        // Super Admin sees students of every active course; faculty / CC / ACC are
        // scoped to their courses below. A coordinator/ACC is reached via their
        // faculty pk even if their login role isn't a standard faculty-portal role.
        // (Exclude Student-OT: their user_id can collide with a faculty pk.)
        if ($isSuperAdmin || is_faculty_portal_user() || ($facultyPk && ! hasRole('Student-OT'))) {

            if ($isSuperAdmin || $facultyPk) {
                $source1Students = collect([]);

                // Course set feeding the primary (enrollment) student source.
                if ($isSuperAdmin) {
                    $activeCoordinatorCourses = CourseMaster::where('active_inactive', 1)
                        ->where('end_date', $dateOp, now())
                        ->pluck('pk');
                } else {
                    $coordinatorCourses = $this->getCoordinatorCourseIds($facultyPk);
                    $activeCoordinatorCourses = $coordinatorCourses->isNotEmpty()
                        ? CourseMaster::whereIn('pk', $coordinatorCourses)
                            ->where('active_inactive', 1)
                            ->where('end_date', $dateOp, now())
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
                        $stdObj = new \stdClass();
                        $stdObj->student_master_pk = $studentMap->student_master_pk;
                        $stdObj->course_master_pk = $studentMap->course_master_pk;
                        $stdObj->studentMaster = $studentMap->studentMaster;
                        $stdObj->course = $studentMap->course;
                        $stdObj->source = 'cc_acc';
                        $source1Students->push($stdObj);
                    }
                }

                $source2Students = collect([]);
                // Group-mapping source is faculty-specific; Super Admin already has
                // every student via source1, so skip it when there's no faculty pk.
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
                        ->where('end_date', $dateOp, now())
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
                                        $studentMap = new \stdClass();
                                        $studentMap->student_master_pk = $studentPk;
                                        $studentMap->course_master_pk = $coursePk;
                                        $studentMap->studentMaster = $groupMap->student;
                                        $studentMap->course = $course;
                                        $studentMap->groupMapping = $groupMap;
                                        $studentMap->source = 'group_mapping';
                                        $source2Students->push($studentMap);
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
                if ($facultyPk && ! $isSuperAdmin) {
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
                            ->where('end_date', $dateOp, now())
                            ->pluck('pk');
                        $activeTaughtSet = $activeTaughtCourseIds->map(fn ($p) => (string) $p)->all();

                        if (! empty($activeTaughtSet)) {
                            $pairs = $taughtRows->filter(fn ($r) => in_array((string) $r->course_pk, $activeTaughtSet, true));
                            $taughtStudents = \App\Models\StudentMaster::with('cadre')
                                ->whereIn('pk', $pairs->pluck('student_pk')->unique()->filter())
                                ->get()->keyBy(fn ($m) => (string) $m->pk);
                            $taughtCourses = CourseMaster::whereIn('pk', $activeTaughtCourseIds)
                                ->get()->keyBy(fn ($c) => (string) $c->pk);

                            foreach ($pairs as $r) {
                                $student = $taughtStudents->get((string) $r->student_pk);
                                $course = $taughtCourses->get((string) $r->course_pk);
                                if ($student && $course) {
                                    $o = new \stdClass();
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
                    $studentCourseKey = $studentPk . '_' . $coursePk;

                    if (! in_array($studentCourseKey, $seenStudentCourseKeys, true)) {
                        $seenStudentCourseKeys[] = $studentCourseKey;
                        $uniqueStudents->push($studentMap);
                    }
                }

                // The per-student total_* counts + notice/memo lookups below are an
                // N+1 that only the student-list page needs; callers that compute
                // their own counts (e.g. the OT participants page via
                // otParticipantsRowMeta) pass $withTotals = false to skip it.
                $noticeMemoService = $withTotals ? app(\App\Services\OTNoticeMemoService::class) : null;

                // Time Period + Duty Type filters scope the count columns.
                $fromDate = $request?->input('from_date') ?: null;
                $toDate = $request?->input('to_date') ?: null;
                $dutyType = $request?->input('duty_type') ?: null;

                // Include students who have discipline memos but were dropped from the
                // active-course list (their course has expired or their enrolment is
                // inactive), so their memo history still surfaces here. Scoped to what
                // the current viewer is allowed to see. This is a student-list concern
                // (and an N+1); skip it for callers that only want the course roster.
                if ($withTotals) {
                    $this->appendStudentsWithMemos($uniqueStudents, $seenStudentCourseKeys, $isSuperAdmin, $facultyPk);
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

                // Every marked attendance session per student (one row per session is
                // produced after this loop). Scoped to the Time Period filter, so a
                // student with no session in range gets no session rows.
                $attendanceSessions = $this->resolveStudentAttendanceSessions($studentPks, $fromDate, $toDate);

                foreach ($uniqueStudents as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    $coursePk = $studentMap->course_master_pk ?? null;

                    // House Name is cheap (batched above) and always needed.
                    $uid = $studentMap->studentMaster->user_id ?? null;
                    $studentMap->house_name = ($uid && isset($houseByUser[$uid])) ? $houseByUser[$uid] : null;

                    if (! $withTotals) {
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
                // they coordinate. Super Admin (even with a faculty pk) is unscoped.
                $sessionFacultyScope = $isSuperAdmin ? null : $facultyPk;
                $coordinatorCourseIds = $sessionFacultyScope
                    ? $this->getCoordinatorCourseIds($sessionFacultyScope)->map(fn ($id) => (string) $id)->values()->all()
                    : [];

                // One row per marked attendance session (student totals repeat per row).
                $uniqueStudents = $this->expandStudentRowsBySession($uniqueStudents, $attendanceSessions, $sessionFacultyScope, $coordinatorCourseIds);

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
                                ? Carbon::parse($course->end_date)->lt(now())
                                : Carbon::parse($course->end_date)->gte(now()));
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
                ->where('end_date', $dateOp, now())
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
                $stdObj = new \stdClass();
                $stdObj->student_master_pk = $studentMap->student_master_pk;
                $stdObj->course_master_pk = $studentMap->course_master_pk;
                $stdObj->studentMaster = $studentMap->studentMaster;
                $stdObj->course = $studentMap->course;
                $stdObj->source = 'super_admin';

                $key = $stdObj->student_master_pk . '_' . ($stdObj->course_master_pk ?? 0);
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
                            ? Carbon::parse($course->end_date)->lt(now())
                            : Carbon::parse($course->end_date)->gte(now()));
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
     * @param  \Illuminate\Support\Collection  $uniqueStudents
     * @param  array<int, string>  $seenStudentCourseKeys
     * @param  int|null  $facultyPk
     */
    private function appendStudentsWithMemos(\Illuminate\Support\Collection $uniqueStudents, array &$seenStudentCourseKeys, bool $isSuperAdmin, $facultyPk): void
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

            $stdObj = new \stdClass();
            $stdObj->student_master_pk = $studentPk;
            $stdObj->course_master_pk = $courseMap->course_master_pk ?? null;
            $stdObj->studentMaster = $student;
            $stdObj->course = $courseMap?->course;
            $stdObj->source = 'memo';

            $key = $studentPk . '_' . ($stdObj->course_master_pk ?? 0);
            if (in_array($key, $seenStudentCourseKeys, true)) {
                continue;
            }

            $seenStudentCourseKeys[] = $key;
            $uniqueStudents->push($stdObj);
        }
    }

    private function augmentStudentListEntries(\Illuminate\Support\Collection $uniqueStudents, ?Request $request = null): \Illuminate\Support\Collection
    {
        $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);

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
     * Distinct class-session time slots for the given students, across ALL dates.
     *
     * The Session filter dropdown must be independent of the Time Period filter.
     * The dropdown is rendered server-side once at page load, and the DataTable's
     * AJAX reloads refresh only the table — never the dropdown. Previously the
     * options were derived from the (date-scoped) student rows, so on any day with
     * no sessions — a weekend/holiday, or a fresh load which defaults the Time
     * Period to today — the dropdown came up empty and the filter looked broken.
     * Sourcing the options straight from the timetable, unscoped by date, keeps the
     * dropdown populated and usable regardless of the selected period. Row-level
     * scope (faculty / course / date) is still enforced when a session is applied.
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedSessionOptions(array $studentPks): \Illuminate\Support\Collection
    {
        return $this->resolveScopedTimetableOptions($studentPks, 'class_session');
    }

    /**
     * Distinct session topics for the given students, across ALL dates — the Topic
     * filter dropdown, built independent of the Time Period for the same reason as
     * resolveScopedSessionOptions().
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedTopicOptions(array $studentPks): \Illuminate\Support\Collection
    {
        return $this->resolveScopedTimetableOptions($studentPks, 'subject_topic');
    }

    /**
     * Distinct non-empty values of a timetable column for the sessions the given
     * students have attendance for, across ALL dates. Backs the date-independent
     * Session / Topic filter dropdowns.
     *
     * @param  array<int, int>  $studentPks
     */
    private function resolveScopedTimetableOptions(array $studentPks, string $column): \Illuminate\Support\Collection
    {
        if (empty($studentPks)) {
            return collect();
        }

        return DB::table('course_student_attendance as a')
            ->join('timetable as t', 'a.timetable_pk', '=', 't.pk')
            ->whereIn('a.Student_master_pk', $studentPks)
            ->whereNotNull("t.$column")
            ->where("t.$column", '<>', '')
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
     * @return array<int, array<int, array<string, mixed>>>  spk => list of sessions
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
     * @param  \Illuminate\Support\Collection  $uniqueStudents
     * @param  array<int, array<int, array<string, mixed>>>  $attendanceSessions
     * @return \Illuminate\Support\Collection
     */
    private function expandStudentRowsBySession(\Illuminate\Support\Collection $uniqueStudents, array $attendanceSessions, $facultyScopePk = null, array $coordinatorCourseIds = []): \Illuminate\Support\Collection
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
                $studentMap->session_date = null;
                $studentMap->session_time = null;
                $studentMap->session_topic = null;
                $studentMap->session_faculty_master = null;
                $studentMap->session_internal_faculty = null;
                $studentMap->has_session_in_range = false;
                $expanded->push($studentMap);
                continue;
            }

            foreach ($sessions as $session) {
                $row = clone $studentMap;
                $row->attendance_present = $session['present'];
                $row->attendance_status = $session['status'];
                $row->session_date = $session['session_date'];
                $row->session_time = $session['session_time'];
                $row->session_topic = $session['session_topic'];
                $row->session_faculty_master = $session['session_faculty_master'] ?? null;
                $row->session_internal_faculty = $session['session_internal_faculty'] ?? null;
                $row->has_session_in_range = true;
                $expanded->push($row);
                $emittedByStudent[$studentPk][] = (int) ($session['attendance_pk'] ?? 0);
            }
        }

        return $expanded;
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

    private function applyDashboardStudentListFilters($students, Request $request, bool $applySessionDateFilter = true)
    {
        $courseId = $request->input('course_id');
        $roleFilter = $request->input('role_filter');
        $counsellorFaculty = $request->input('counsellor_faculty');
        $groupPk = $request->input('group_pk');
        $cadre = $request->input('cadre');
        $house = $request->input('house');
        $session = (string) $request->input('session', '');
        $topic = (string) $request->input('topic', '');
        $participant = (string) $request->input('participant', '');
        $searchInput = $request->input('search', '');
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        $search = strtolower(trim((string) $searchValue));

        // Time Period (event date) filter: when a range is selected, only students who
        // have a timetable session/event within that range are kept. If no event exists
        // on the chosen day(s), the list ends up empty ("Data not found.").
        // Callers that only want the Time Period to scope count columns (not drop
        // students) pass $applySessionDateFilter = false.
        $hasSessionDateFilter = $applySessionDateFilter
            && $request->filled('from_date') && $request->filled('to_date');

        return $students->filter(function ($studentMap) use ($courseId, $roleFilter, $counsellorFaculty, $groupPk, $cadre, $house, $session, $topic, $participant, $search, $hasSessionDateFilter) {
            $student = $studentMap->studentMaster;
            $course = $studentMap->course;
            $counsellorTypePk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->type_name ?? '');
            $rowFacilityId = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->facility_id ?? '');
            $rowGroupPk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->pk ?? '');
            $rowCourseId = (string) ($course->pk ?? '');

            if ($hasSessionDateFilter && ! ($studentMap->has_session_in_range ?? false)) {
                return false;
            }

            // Session (class-session time slot) filter.
            if ($session !== '' && (string) ($studentMap->session_time ?? '') !== $session) {
                return false;
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

            if ($cadre && (string) ($student->cadre->cadre_name ?? '') !== (string) $cadre) {
                return false;
            }

            if ($house && (string) ($studentMap->house_name ?? '') !== (string) $house) {
                return false;
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
                $name = strtolower(trim((string) (($student->display_name ?? '') ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')))));
                $otCode = strtolower((string) ($student->generated_OT_code ?? ''));
                $email = strtolower((string) ($student->email ?? ''));
                $groupName = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->group_name ?? null;
                $cadre = strtolower((string) ($groupName ?: ($student->cadre->cadre_name ?? '')));

                if (
                    ! str_contains($name, $search)
                    && ! str_contains($otCode, $search)
                    && ! str_contains($email, $search)
                    && ! str_contains($cadre, $search)
                ) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Collapse the per-session rows into ONE ROW PER STUDENT (per tab) for a
     * date-scoped attendance view (the Present/Absent Today cards, or any Time
     * Period filter). Only students GENUINELY MARKED (status != 0) within range are
     * counted; no-session default rows are dropped (not treated as "Present").
     *   - Present = attended any NON-absent marked session in range
     *   - Absent  = had ANY absent (status 3) marked session in range
     * A student can therefore appear in BOTH tabs (present in some sessions, absent
     * in others). The absent row carries its absent session's date so the Absent
     * Reason resolves against that day.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection} [present, absent]
     */
    private function collapseDateScopedAttendance($rows): array
    {
        $present = collect();
        $absent = collect();

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

            // Present bucket: any non-absent marked session.
            $presentRow = $marked->first(fn ($m) => (int) $m->attendance_status !== 3);
            if ($presentRow) {
                $presentRow->attendance_present = true;
                $present->push($presentRow);
            }

            // Absent bucket: any absent (status 3) marked session. Use that session
            // as the row so its date drives the Absent Reason lookup.
            $absentRow = $marked->first(fn ($m) => (int) $m->attendance_status === 3);
            if ($absentRow) {
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
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection} [all, present, absent]
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
            $row->has_session_in_range = true;
            $absentAll->push($row);
        }

        return [$filteredAll, $presentAll->values(), $absentAll->values()];
    }

    /**
     * Server-side JSON for dashboard student list DataTables.
     */
    private function dashboardStudentListDataTableResponse(Request $request, $students)
    {
        $attendance = (string) $request->input('attendance', 'all');

        [$filteredAll, $presentAll, $absentAll] = $this->dashboardStudentListTabSets($request, $students);

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
            1 => 'ot_code',
            2 => 'name',
            3 => 'username',
            4 => 'cadre',
            5 => 'date',
            6 => 'session',
            7 => 'topic',
            8 => 'faculty',
            // 9 => absent_reason (not sortable)
            10 => 'status',
            11 => 'mdo',
            12 => 'escort',
            13 => 'other_exempt',
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

        $data = [];
        foreach ($pagedStudents as $idx => $studentMap) {
            $student = $studentMap->studentMaster;
            if (! $student) {
                continue;
            }

            $detailUrl = route('admin.dashboard.students.detail', encrypt($student->pk));
            $displayName = $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
            $statusCode = (int) ($studentMap->attendance_status ?? 0);
            $isAbsent = ($studentMap->attendance_present ?? true) === false;

            $data[] = [
                's_no' => $start + $idx + 1,
                'ot_code' => $student->generated_OT_code ?? 'N/A',
                'name' => '<a href="' . e($detailUrl) . '" class="sl-count">' . e($displayName) . '</a>',
                'username' => e($student->email ?? 'N/A'),
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
                'status' => (function () use ($studentMap, $isAbsent, $absentReasons, $idx) {
                    $present = ($studentMap->attendance_present ?? true);
                    $html = '<span class="sl-status-badge ' . ($present ? 'sl-status-present' : 'sl-status-absent')
                        . '">' . ($present ? 'Present' : 'Absent') . '</span>';
                    $reason = $isAbsent ? ($absentReasons[$idx] ?? '-') : '-';
                    if ($isAbsent && $reason !== '-') {
                        $html .= '<div class="text-muted small mt-1">' . e($reason) . '</div>';
                    }
                    return $html;
                })(),
                'mdo' => $statusCode === 4 ? 'Yes' : '-',
                'escort' => $statusCode === 5 ? 'Yes' : '-',
                'other_exempt' => $statusCode === 6 ? 'Medical' : ($statusCode === 7 ? 'Other' : '-'),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'counts' => $counts,
            'data' => $data,
        ]);
    }

    /**
     * Derive an "Absent Reason" for each row on the current page. Attendance
     * itself records no reason, so we look for a leave application or medical
     * exemption that covers the absent session's own date. Returns a label per
     * row index ("Medical Exemption" / "PT Exemption" / "Stationed Leave"), or
     * "-" when nothing overlaps. Only absent rows are considered.
     *
     * @param  \Illuminate\Support\Collection  $pagedStudents
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

    private function dashboardAbsentReasons(\Illuminate\Support\Collection $pagedStudents): array
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
            if (($m->attendance_present ?? true) !== false) {
                continue;
            }
            $spk = (int) ($m->student_master_pk ?? 0);
            $date = ! empty($m->session_date) ? \Illuminate\Support\Carbon::parse($m->session_date)->toDateString() : null;
            $reason = '-';

            if ($spk && $date) {
                foreach ($medical[$spk] ?? [] as $r) {
                    if ($covers($r->from_date, $r->to_date, $date)) {
                        $reason = 'Medical Exemption';
                        break;
                    }
                }
                if ($reason === '-') {
                    foreach ($leaves[$spk] ?? [] as $r) {
                        if ($covers($r->from_date, $r->to_date, $date)) {
                            $reason = $r->leave_type === 'PT_EXEMPTION' ? 'PT Exemption'
                                : ($r->leave_type === 'STATIONED_LEAVE' ? 'Stationed Leave' : 'Leave');
                            break;
                        }
                    }
                }
            }

            $reasons[$idx] = $reason;
        }

        return $reasons;
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
            $names = \App\Models\FacultyMaster::whereIn('pk', $ids)
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
            'name' => strtolower(trim((string) (($student->display_name ?? '') ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))))),
            'username', 'email' => strtolower((string) ($student->email ?? '')),
            'cadre' => strtolower((string) ($student->cadre->cadre_name ?? '')),
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
        $students = ($students instanceof \Illuminate\Support\Collection ? $students : collect($students))->values();

        // On the Absent tab include the reason column (Stationed Leave / PT Exemption
        // / Medical Exemption) so the report matches the on-screen Absent list.
        $isAbsentTab = $attendance === 'absent';
        $absentReasons = $isAbsentTab ? $this->dashboardAbsentReasons($students) : [];

        $headings = ['Sl. No.', 'Student Name', 'OT Code', 'Email', 'Cadre', 'Course', 'Session Date', 'Status'];
        if ($isAbsentTab) {
            $headings[] = 'Absent Reason';
        }
        $headings = array_merge($headings, [
            'Total Duty (Count)',
            'Total Medical Exception (Count)',
            'Total PT Exemption (Count)',
            'Total Station Leave (Count)',
            'Total Memo',
            'Notice (Count)',
        ]);

        $rows = [];
        foreach ($students as $index => $studentMap) {
            $student = $studentMap->studentMaster;
            $course = $studentMap->course;
            $groupName = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->group_name ?? null;
            $displayName = $groupName ?: ($student->cadre->cadre_name ?? 'N/A');

            $row = [
                $index + 1,
                $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                $student->generated_OT_code ?? 'N/A',
                $student->email ?? 'N/A',
                $displayName,
                $course->course_name ?? 'N/A',
                $studentMap->session_date ? Carbon::parse($studentMap->session_date)->format('d-m-Y') : 'N/A',
                $this->dashboardAttendanceStatusLabel($studentMap),
            ];
            if ($isAbsentTab) {
                $row[] = $absentReasons[$index] ?? '-';
            }
            $rows[] = array_merge($row, [
                $studentMap->total_duty_count ?? 0,
                $studentMap->total_medical_exception_count ?? 0,
                $studentMap->total_pt_exemption_count ?? 0,
                $studentMap->total_stationed_leave_count ?? 0,
                $studentMap->total_memo_count ?? 0,
                $studentMap->total_notice_count ?? 0,
            ]);
        }

        return compact('headings', 'rows');
    }

    private function dashboardStudentListFilterSummary(Request $request): string
    {
        $parts = [];

        if ($request->filled('course_id')) {
            $course = CourseMaster::find($request->course_id);
            $parts[] = 'Course: ' . ($course->course_name ?? $request->course_id);
        }

        if ($request->filled('role_filter')) {
            if ($request->role_filter === 'cc_acc') {
                $parts[] = 'Role: CC/ACC';
            } else {
                $type = DB::table('course_group_type_master')->where('pk', $request->role_filter)->value('type_name');
                $parts[] = 'Role: ' . ($type ?? $request->role_filter);
            }
        }

        if ($request->filled('faculty_filter')) {
            $facultyName = DB::table('faculty_master')->where('pk', $request->faculty_filter)->value('full_name');
            $parts[] = 'Faculty: ' . ($facultyName ?? $request->faculty_filter);
        }

        if ($request->filled('group_pk')) {
            $group = DB::table('group_type_master_course_master_map')->where('pk', $request->group_pk)->value('group_name');
            $parts[] = 'Group: ' . ($group ?? $request->group_pk);
        }

        if ($request->filled('search')) {
            $parts[] = 'Search: ' . $request->search;
        }

        return $parts ? implode(' | ', $parts) : 'All students';
    }

    /**
     * Display My Counselee list (counsellor's assigned counselees with phase progress).
     *
     * @return \Illuminate\View\View
     */
    public function myCounselee()
    {
        $userId = Auth::user()->user_id;

        // Only faculty users should see dynamic counselee mapping
        $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
        if (!$faculty) {
            return view('admin.dashboard.my_counselee', ['counselees' => []]);
        }

        $facultyPk = (int) $faculty->pk;
        $today = Carbon::now()->format('Y-m-d');
        $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);

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
            if (!$row->student) {
                continue;
            }
            if (!isset($byStudent[$studentPk])) {
                $byStudent[$studentPk] = $row;
            }
        }

        $counselees = [];
        foreach ($byStudent as $studentPk => $row) {
            $student = $row->student;
            $coursePk = (int) ($coursePksByGroup[$row->group_type_master_course_master_map_pk] ?? 0);

            // Course details (from relationship if loaded, else lookup from enrollment)
            $course = $row->groupTypeMasterCourseMasterMap?->courseGroup;
            if (!$course && $coursePk) {
                $course = CourseMaster::find($coursePk);
            }

            // Attendance % for this student in this course (present + late) / total.
            // status is ENUM('0'..'7'); compare to quoted strings so buckets aren't
            // shifted by MySQL's enum-ordinal comparison (see studentDetail summary).
            $att = CourseStudentAttendance::where('Student_master_pk', $studentPk)
                ->when($coursePk > 0, fn ($q) => $q->where('course_master_pk', $coursePk))
                ->selectRaw("COUNT(*) as total_sessions,
                    COALESCE(SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END), 0) as present_count,
                    COALESCE(SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END), 0) as late_count
                ")
                ->first();
            $totalSessions = (int) ($att->total_sessions ?? 0);
            $present = (int) ($att->present_count ?? 0);
            $late = (int) ($att->late_count ?? 0);
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
            if (!empty($student->photo_path)) {
                $photo = asset('storage/' . $student->photo_path);
            }

            $displayName = $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
            $counselees[] = [
                'name' => $displayName ?: ('Student #' . $studentPk),
                'id' => $student->generated_OT_code ?? ('STU-' . $studentPk),
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
     * Display complete student details
     *
     * @param int $id Student ID (encrypted)
     * @return \Illuminate\View\View
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

        if (!$student) {
            return redirect()->route('admin.dashboard.students')
                ->with('error', 'Student not found.');
        }

        if (is_faculty_portal_user()
            && !hasRole('Super Admin')
            && !hasRole('Training Induction Admin')
            && !hasRole('Training MCTP Admin')
            && !hasRole('Training IST')) {
            $facultyPk = get_auth_faculty_master_pk();
            if (!$facultyPk || !$this->canFacultyViewStudent($facultyPk, (int) $studentPk)) {
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
        $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);
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

        // Calculate total expected sessions (timetables) for student's course groups
        $studentGroupPks = StudentCourseGroupMap::where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->pluck('group_type_master_course_master_map_pk')
            ->toArray();

        $totalExpectedSessions = 0;
        if (!empty($studentGroupPks)) {
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
        $markedSessions = $markedResult ? (int)$markedResult->count : 0;

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

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) ($request->input('search') ?? ''));
        $user_type = trim((string) $request->input('User_type', ''));

        $epoch = DataTableRedisCache::readListEpoch(self::ADMIN_USERS_INDEX_LIST_EPOCH_KEY);
        $cacheKey = 'admin_users_index:v5:' . md5(json_encode([
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

        $users = new \Illuminate\Pagination\LengthAwarePaginator(
            $cached['items'],
            $cached['total'],
            $cached['perPage'],
            $cached['currentPage'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Live search / pagination: return only the table partial (no full reload).
        if ($request->ajax()) {
            return view('admin.user_management.users._table', compact('users', 'perPage', 'search', 'user_type'));
        }

        return view('admin.user_management.users.index', compact('users', 'perPage', 'search', 'user_type'));
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
                            ->orWhereRaw("LOWER(TRIM(uc.first_name)) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(TRIM(uc.last_name)) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(TRIM(uc.email_id)) LIKE ?", [$like])
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
     * the listing. Each entry maps to a heading and a value resolver.
     *
     * @return array<string, array{label: string, value: callable}>
     */
    private function adminUsersExportColumns(): array
    {
        return [
            'username'    => ['label' => 'Username',  'value' => fn ($u) => $u->user_name ?? ''],
            'name'        => ['label' => 'Name',      'value' => fn ($u) => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))],
            'email'       => ['label' => 'Email',     'value' => fn ($u) => $u->email_id ?? ''],
            'mobile'      => ['label' => 'Mobile',    'value' => fn ($u) => $u->mobile_no ?: '—'],
            'usertype'    => ['label' => 'User Type', 'value' => fn ($u) => self::userTypeLabel($u->User_type ?? '')],
            'roles'       => ['label' => 'Roles',     'value' => fn ($u) => $u->roles ?: 'No Role'],
        ];
    }

    /**
     * Export the users listing (csv / xlsx / pdf) honouring the active search,
     * user-type filter and — where provided — the columns the user has left
     * visible in the grid.
     */
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

        $headings = array_merge(['S. No.'], array_map(fn ($k) => $allColumns[$k]['label'], $requested));

        $records = $this->adminUsersBaseQuery($search, $user_type)
            ->orderBy('uc.pk')
            ->get();

        $rows = [];
        foreach ($records as $i => $record) {
            $row = [$i + 1];
            foreach ($requested as $key) {
                $row[] = $allColumns[$key]['value']($record);
            }
            $rows[] = $row;
        }

        $timestamp = now()->format('Ymd_His');
        $fileBase = "users_{$timestamp}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.user_management.users.partials.export_pdf', [
                'headings' => $headings,
                'rows' => $rows,
                'generatedAt' => now()->format('d-m-Y H:i'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download("{$fileBase}.pdf");
        }

        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(
            new UsersExport($headings, $rows),
            "{$fileBase}.{$format}",
            $writerType
        );
    }

    private static function bumpAdminUsersIndexCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::ADMIN_USERS_INDEX_LIST_EPOCH_KEY, 'UserController@adminUsersIndex');
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
       $roles = UserRoleMaster::orderBy('pk', 'DESC')->get();
        return view('admin.user_management.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \App\Http\Requests\Admin\User\StoreUserRequest  $request
     * @return \Illuminate\Http\RedirectResponse
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
            if (!empty($roleIds)) {
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
                if (!empty($assignedRoleNames) && $user->user_id) {
                    try {
                        $notificationService = app(NotificationService::class);
                        $roleNames = implode(', ', $assignedRoleNames);
                        $notificationService->create(
                            (int)$user->user_id,
                            'role_assignment',
                            'Role Assignment',
                            $user->pk,
                            'Role Assigned',
                            "You have been assigned the following role(s): {$roleNames}."
                        );
                    } catch (\Exception $e) {
                        // Log error but don't fail the request
                        \Log::error('Failed to send role assignment notification: ' . $e->getMessage());
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
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        $user->load('roles', 'permissions');
        return view('admin.user_management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
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
     * @param  \App\Http\Requests\Admin\User\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
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

            if (!empty($roleIds)) {
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
            if (!empty($assignedRoleNames) && $user->user_id) {
                try {
                    $notificationService = app(NotificationService::class);
                    $roleNames = implode(', ', $assignedRoleNames);
                    $notificationService->create(
                        (int)$user->user_id,
                        'role_assignment',
                        'Role Assignment',
                        $user->pk,
                        'Role Assigned',
                        "You have been assigned the following role(s): {$roleNames}."
                    );
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    \Log::error('Failed to send role assignment notification: ' . $e->getMessage());
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
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
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
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

public function toggleStatus(Request $request)
{
    try {
        $idColumn = $request->id_column ?? 'pk';
        $table = $request->table;
        $column = $request->column;
        $id = $request->id;
        $status = $request->status;

        DB::table($request->table)
            ->where($idColumn, $id)
            ->update([$column => $status]);

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

        $newState = ((int) $status === 1) ? 'Active' : 'Inactive';
        session()->flash('success', "Status updated to {$newState}.");

        return response()->json([
            'message' => "Status updated to {$newState}.",
            'state' => $newState,
        ]);
    } catch (\Exception $e) {
        \Log::error('Toggle status error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update status: ' . $e->getMessage(),
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
    $request->validate([
        'user_id' => 'required|integer|exists:user_credentials,pk',
        'roles'   => 'nullable|array',
        'roles.*' => 'exists:roles,id',
    ]);

    try {
        DB::beginTransaction();

        $user = User::findOrFail($request->user_id);
        $roleNames = Role::whereIn('id', $request->input('roles', []))->pluck('name')->toArray();
        $user->syncRoles($roleNames);

        DB::commit();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        self::bumpAdminUsersIndexCacheEpoch();

        // Send notification to the user if roles were assigned
        if (!empty($assignedRoleNames)) {
            try {
                // Get user_id from user_credentials table
                $userCredential = \DB::table('user_credentials')
                    ->where('pk', $userId)
                    ->first();

                if ($userCredential && $userCredential->user_id) {
                    $notificationService = app(NotificationService::class);
                    $roleNames = implode(', ', $assignedRoleNames);
                    $notificationService->create(
                        (int)$userCredential->user_id,
                        'role_assignment',
                        'Role Assignment',
                        $userId,
                        'Role Assigned',
                        "You have been assigned the following role(s): {$roleNames}."
                    );
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::error('Failed to send role assignment notification: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Roles assigned successfully.');
    } catch (\Exception $e) {
        DB::rollBack();

        return back()->with('error', 'Error: ' . $e->getMessage());
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

            // Allow only PDF
            if ($file->getClientOriginalExtension() != 'pdf') {
                return response()->json(['error' => 'Only PDF files allowed'], 422);
            }

            $path = $file->store('summernote/pdf', 'public');

            return response()->json([
                'location' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
    function change_password(){
    return view('admin.password.change_password');

    }
    function submit_change_password(Request $request) {
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ]);
    try {
      $user = Auth::user();
    $username = $user->user_name;

    // 🔹 Verify old password first
  if (!Adldap::auth()->attempt($username, $request->current_password)) {
    return back()
        ->withErrors([
            'current_password' => 'Current password is incorrect'
        ]);
}


    // 🔹 Find LDAP user
    $ldapUser = Adldap::search()->users()->find($username);

    if (!$ldapUser) {
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
            'ldap_error' => 'LDAP Error: ' . $e->getMessage()
        ]);
}

    }

    /**
     * Get today's timetable for a specific faculty member
     *
     * @param int $facultyUserId
     * @return \Illuminate\Support\Collection
     */
    private function getTodayTimetableForFaculty($facultyUserId)
    {
        $today = Carbon::today()->toDateString();

        // Get faculty_master.pk from user_id
        $faculty = FacultyMaster::where('employee_master_pk', $facultyUserId)->first();

        if (!$faculty) {
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
                        $sessionTime = $entry->classSession->start_time . ' - ' . $entry->classSession->end_time;
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
                if (is_array($facultyIds) && !empty($facultyIds)) {
                    // Get all faculty names from JSON array
                    $facultyNames = FacultyMaster::whereIn('pk', $facultyIds)
                        ->pluck('full_name')
                        ->filter()
                        ->toArray();
                    $facultyName = !empty($facultyNames) ? implode(', ', $facultyNames) : 'N/A';
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
     * @param int $studentId
     * @return \Illuminate\Support\Collection
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
                        $sessionTime = $entry->classSession->start_time . ' - ' . $entry->classSession->end_time;
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
                if (is_array($facultyIds) && !empty($facultyIds)) {
                    // Get all faculty names from JSON array
                    $facultyNames = FacultyMaster::whereIn('pk', $facultyIds)
                        ->pluck('full_name')
                        ->filter()
                        ->toArray();
                    $facultyName = !empty($facultyNames) ? implode(', ', $facultyNames) : 'N/A';
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
            ->where('end_date', '>=', now())
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
            ->where('end_date', '>=', now())
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


