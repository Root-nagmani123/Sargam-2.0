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

                 // ========== SOURCE 1: CC/ACC Courses Students ==========
                 $source1StudentPks = collect([]);
                 if ($coordinatorCourses->isNotEmpty()) {
                     $isCCorACC = true;
                     // Get active courses where faculty is CC/ACC
                    $activeCourseIds = CourseMaster::whereIn('pk', $coordinatorCourses)
                        ->where('active_inactive', 1)
                        ->where('end_date', '>=', now())
                        ->pluck('pk');

                     // Count total students enrolled in these courses (Source 1)
                     if ($activeCourseIds->isNotEmpty()) {
                         $source1StudentPks = StudentMasterCourseMap::whereIn('course_master_pk', $activeCourseIds)
                             ->where('active_inactive', 1)
                             ->pluck('student_master_pk')
                             ->unique();
                     }
                 }

                 // ========== SOURCE 2: Group Mappings Students ==========
                 $source2StudentPks = collect([]);

                 // Step 1: Find group mappings where faculty is assigned
                 $groupMappings = DB::table('group_type_master_course_master_map')
                     ->where('facility_id', $facultyPk)
                     ->where('active_inactive', 1)
                     ->get();

                 if ($groupMappings->isNotEmpty()) {
                     // Step 2: Get course_name (course_pk) from group mappings
                     $groupMapCourseIds = $groupMappings->pluck('course_name')->unique();

                     // Step 3: Check in course_master if these courses are active
                     $activeCourseIds = CourseMaster::whereIn('pk', $groupMapCourseIds)
                         ->where('active_inactive', 1)
                         ->where('end_date', '>=', now())
                         ->pluck('pk');

                     if ($activeCourseIds->isNotEmpty()) {
                         // Step 4: Get group_type_master_course_master_map.pk for active courses
                         $activeGroupMappingPks = $groupMappings
                             ->whereIn('course_name', $activeCourseIds)
                             ->pluck('pk')
                             ->unique();

                         // Step 5: Get students from student_course_group_map (Source 2)
                         if ($activeGroupMappingPks->isNotEmpty()) {
                             $source2StudentPks = StudentCourseGroupMap::whereIn('group_type_master_course_master_map_pk', $activeGroupMappingPks)
                                 ->where('active_inactive', 1)
                                 ->pluck('student_master_pk')
                                 ->unique();
                         }
                     }
                 }

                 // ========== MERGE BOTH SOURCES ==========
                 // Combine Source 1 and Source 2 student PKs and get unique count
                 $allStudentPks = $source1StudentPks->merge($source2StudentPks)->unique();
                 $totalStudents = $allStudentPks->count();
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
        $payload = $this->resolveDashboardStudentListPayload($request);
        $students = $payload['students'];
        $availableCourses = $payload['availableCourses'];
        $facultyPk = $payload['facultyPk'];

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

        // Server-side filters (Course / ACC / Group / Cadre / House) + Present-Absent attendance tab.
        $students = $this->applyDashboardStudentListFilters($students, $request);

        // Split into both tabs so the view can render both panels and switch
        // between them client-side (no page reload on tab change).
        $presentStudents = $students->filter(fn ($m) => ($m->attendance_present ?? true) === true)->values();
        $absentStudents = $students->filter(fn ($m) => ($m->attendance_present ?? true) === false)->values();

        $attendance = $request->input('attendance', 'present');
        $students = $attendance === 'absent' ? $absentStudents : $presentStudents;

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
            'attendance' => (string) $request->input('attendance', 'present'),
            'cadre' => (string) $request->input('cadre', ''),
            'house' => (string) $request->input('house', ''),
            'counsellor_faculty' => (string) $request->input('counsellor_faculty', ''),
        ];

        return view('admin.dashboard.student_list', compact('students', 'presentStudents', 'absentStudents', 'availableCourses', 'counsellorTypes', 'counsellorFaculties', 'groupNames', 'dutyTypes', 'filters', 'cadreOptions', 'houseOptions'));
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

        $payload = $this->resolveDashboardStudentListPayload($request);
        $students = $this->applyDashboardStudentListFilters($payload['students'], $request);
        $exportData = $this->dashboardStudentListExportData($students);

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
    private function resolveDashboardStudentListPayload(?Request $request = null): array
    {
        $students = collect([]);
        $availableCourses = collect([]);
        $facultyPk = null;

        $facultyPk = get_auth_faculty_master_pk();
        $isSuperAdmin = hasRole('Super Admin');

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
                        ->where('end_date', '>=', now())
                        ->pluck('pk');
                } else {
                    $coordinatorCourses = $this->getCoordinatorCourseIds($facultyPk);
                    $activeCoordinatorCourses = $coordinatorCourses->isNotEmpty()
                        ? CourseMaster::whereIn('pk', $coordinatorCourses)
                            ->where('active_inactive', 1)
                            ->where('end_date', '>=', now())
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
                        ->where('end_date', '>=', now())
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

                $seenStudentCourseKeys = [];
                $uniqueStudents = collect([]);

                foreach ($source2Students->concat($source1Students) as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    $coursePk = $studentMap->course_master_pk ?? 0;
                    $studentCourseKey = $studentPk . '_' . $coursePk;

                    if (! in_array($studentCourseKey, $seenStudentCourseKeys, true)) {
                        $seenStudentCourseKeys[] = $studentCourseKey;
                        $uniqueStudents->push($studentMap);
                    }
                }

                $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);

                // Time Period + Duty Type filters scope the count columns.
                $fromDate = $request?->input('from_date') ?: null;
                $toDate = $request?->input('to_date') ?: null;
                $dutyType = $request?->input('duty_type') ?: null;

                // Include students who have discipline memos but were dropped from the
                // active-course list (their course has expired or their enrolment is
                // inactive), so their memo history still surfaces here. Scoped to what
                // the current viewer is allowed to see.
                $this->appendStudentsWithMemos($uniqueStudents, $seenStudentCourseKeys, $isSuperAdmin, $facultyPk);

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
                $uniqueStudents = $this->expandStudentRowsBySession($uniqueStudents, $attendanceSessions);

                $students = $uniqueStudents->filter(function ($studentMap) {
                    return ! empty($studentMap->studentMaster);
                })->values();

                $availableCourses = $students->pluck('course')
                    ->filter(function ($course) {
                        return $course
                            && isset($course->active_inactive)
                            && $course->active_inactive == 1
                            && isset($course->end_date)
                            && Carbon::parse($course->end_date)->gte(now());
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
                ->where('end_date', '>=', now())
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
                ->filter(function ($course) {
                    return $course
                        && isset($course->active_inactive)
                        && $course->active_inactive == 1
                        && isset($course->end_date)
                        && Carbon::parse($course->end_date)->gte(now());
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
    private function expandStudentRowsBySession(\Illuminate\Support\Collection $uniqueStudents, array $attendanceSessions): \Illuminate\Support\Collection
    {
        $expanded = collect();

        foreach ($uniqueStudents as $studentMap) {
            $studentPk = $studentMap->student_master_pk;
            $coursePk = $studentMap->course_master_pk ?? null;

            $sessions = $attendanceSessions[$studentPk] ?? [];

            // Keep only this course-row's own sessions so a student enrolled in
            // multiple courses doesn't repeat another course's sessions.
            if ($coursePk !== null && (int) $coursePk !== 0) {
                $sessions = array_values(array_filter($sessions, function ($s) use ($coursePk) {
                    return (string) ($s['course_master_pk'] ?? '') === (string) $coursePk;
                }));
            }

            if (empty($sessions)) {
                $studentMap->attendance_present = true;
                $studentMap->attendance_status = null;
                $studentMap->session_date = null;
                $studentMap->session_time = null;
                $studentMap->session_topic = null;
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
                $row->has_session_in_range = true;
                $expanded->push($row);
            }
        }

        return $expanded;
    }

    private function applyDashboardStudentListFilters($students, Request $request)
    {
        $courseId = $request->input('course_id');
        $roleFilter = $request->input('role_filter');
        $counsellorFaculty = $request->input('counsellor_faculty');
        $groupPk = $request->input('group_pk');
        $cadre = $request->input('cadre');
        $house = $request->input('house');
        $searchInput = $request->input('search', '');
        $searchValue = is_array($searchInput) ? ($searchInput['value'] ?? '') : $searchInput;
        $search = strtolower(trim((string) $searchValue));

        // Time Period (event date) filter: when a range is selected, only students who
        // have a timetable session/event within that range are kept. If no event exists
        // on the chosen day(s), the list ends up empty ("Data not found.").
        $hasSessionDateFilter = $request->filled('from_date') && $request->filled('to_date');

        return $students->filter(function ($studentMap) use ($courseId, $roleFilter, $counsellorFaculty, $groupPk, $cadre, $house, $search, $hasSessionDateFilter) {
            $student = $studentMap->studentMaster;
            $course = $studentMap->course;
            $counsellorTypePk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->type_name ?? '');
            $rowFacilityId = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->facility_id ?? '');
            $rowGroupPk = (string) ($studentMap->groupMapping->groupTypeMasterCourseMasterMap->pk ?? '');
            $rowCourseId = (string) ($course->pk ?? '');

            if ($hasSessionDateFilter && ! ($studentMap->has_session_in_range ?? false)) {
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
     * Server-side JSON for dashboard student list DataTables.
     */
    private function dashboardStudentListDataTableResponse(Request $request, $students)
    {
        $attendance = (string) $request->input('attendance', 'present');
        $attendanceStudents = $attendance === 'absent'
            ? $students->filter(fn ($m) => ($m->attendance_present ?? true) === false)->values()
            : $students->filter(fn ($m) => ($m->attendance_present ?? true) === true)->values();

        $recordsTotal = $attendanceStudents->count();
        $filteredStudents = $this->applyDashboardStudentListFilters($attendanceStudents, $request)->values();
        $recordsFiltered = $filteredStudents->count();

        $columnMap = $attendance === 'absent'
            ? [
                0 => 'serial_no',
                1 => 'ot_code',
                2 => 'name',
                3 => 'email',
                4 => 'cadre',
                5 => 'status',
                6 => 'session',
                7 => 'topic',
                8 => 'medical',
                9 => 'pt',
                10 => 'stationed',
            ]
            : [
                0 => 'serial_no',
                1 => 'ot_code',
                2 => 'name',
                3 => 'email',
                4 => 'cadre',
                5 => 'status',
                6 => 'session',
                7 => 'topic',
                8 => 'house',
                9 => 'duty',
                10 => 'medical',
                11 => 'pt',
                12 => 'stationed',
                13 => 'notice',
                14 => 'memo',
            ];

        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortKey = $columnMap[$orderCol] ?? 'serial_no';

        if ($sortKey !== 'serial_no') {
            $filteredStudents = $filteredStudents->sortBy(function ($studentMap) use ($sortKey) {
                return $this->dashboardStudentListSortValue($studentMap, $sortKey);
            }, SORT_NATURAL | SORT_FLAG_CASE, $orderDir === 'desc')->values();
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length < 0) {
            $pagedStudents = $filteredStudents->slice($start)->values();
        } else {
            $pagedStudents = $filteredStudents->slice($start, $length)->values();
        }

        $data = [];
        foreach ($pagedStudents as $idx => $studentMap) {
            $student = $studentMap->studentMaster;
            if (! $student) {
                continue;
            }

            $detailUrl = route('admin.dashboard.students.detail', encrypt($student->pk));
            $displayName = $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
            $sessionDate = $studentMap->session_date ? Carbon::parse($studentMap->session_date)->format('d-m-Y') : 'N/A';
            $sessionTime = $studentMap->session_time ?? '';
            $sessionHtml = '<div class="fw-medium">' . e($sessionDate) . '</div>'
                . ($sessionTime !== '' ? '<div class="small text-muted">' . e($sessionTime) . '</div>' : '');

            $baseRow = [
                's_no' => $start + $idx + 1,
                'ot_code' => $student->generated_OT_code ?? 'N/A',
                'name' => '<a href="' . e($detailUrl) . '" class="sl-count">' . e($displayName) . '</a>',
                'email' => $student->email ?? 'N/A',
                'cadre' => $student->cadre->cadre_name ?? 'N/A',
                'status' => '<span class="sl-status-badge '
                    . (($studentMap->attendance_present ?? true) ? 'sl-status-present' : 'sl-status-absent')
                    . '">' . e($this->dashboardAttendanceStatusLabel($studentMap)) . '</span>',
                'date_session' => $sessionHtml,
                'topic' => $studentMap->session_topic ?: 'N/A',
            ];

            $sectionUrl = fn ($section) => $detailUrl . '?section=' . $section;

            if ($attendance === 'absent') {
                $data[] = $baseRow + [
                    'total_medical_exception_count' => '<a href="' . e($sectionUrl('medicalExceptionsSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_medical_exception_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                    'total_pt_exemption_count' => '<a href="' . e($sectionUrl('ptExemptionsSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_pt_exemption_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                    'total_stationed_leave_count' => '<a href="' . e($sectionUrl('stationedLeavesSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_stationed_leave_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                ];
                continue;
            }

            $data[] = $baseRow + [
                'house_name' => $studentMap->house_name ?? 'N/A',
                'total_duty_count' => '<a href="' . e($sectionUrl('dutiesSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_duty_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                'total_medical_exception_count' => '<a href="' . e($sectionUrl('medicalExceptionsSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_medical_exception_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                'total_pt_exemption_count' => '<a href="' . e($sectionUrl('ptExemptionsSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_pt_exemption_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                'total_stationed_leave_count' => '<a href="' . e($sectionUrl('stationedLeavesSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_stationed_leave_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                'total_notice_count' => '<a href="' . e($sectionUrl('noticesSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_notice_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
                'total_memo_count' => '<a href="' . e($sectionUrl('memosSection')) . '" class="sl-count">' . str_pad((string) ($studentMap->total_memo_count ?? 0), 2, '0', STR_PAD_LEFT) . '</a>',
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

    private function dashboardStudentListSortValue($studentMap, string $sortKey)
    {
        $student = $studentMap->studentMaster;

        return match ($sortKey) {
            'ot_code' => strtolower((string) ($student->generated_OT_code ?? '')),
            'name' => strtolower(trim((string) (($student->display_name ?? '') ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))))),
            'email' => strtolower((string) ($student->email ?? '')),
            'cadre' => strtolower((string) ($student->cadre->cadre_name ?? '')),
            'status' => (int) ($studentMap->attendance_present ?? true),
            'session' => (string) ($studentMap->session_date ?? ''),
            'topic' => strtolower((string) ($studentMap->session_topic ?? '')),
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
    private function dashboardStudentListExportData($students): array
    {
        $headings = [
            'Sl. No.',
            'Student Name',
            'OT Code',
            'Email',
            'Cadre',
            'Course',
            'Session Date',
            'Status',
            'Total Duty (Count)',
            'Total Medical Exception (Count)',
            'Total PT Exemption (Count)',
            'Total Station Leave (Count)',
            'Total Memo',
            'Notice (Count)',
        ];

        $rows = [];
        foreach ($students as $index => $studentMap) {
            $student = $studentMap->studentMaster;
            $course = $studentMap->course;
            $groupName = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->group_name ?? null;
            $displayName = $groupName ?: ($student->cadre->cadre_name ?? 'N/A');

            $rows[] = [
                $index + 1,
                $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                $student->generated_OT_code ?? 'N/A',
                $student->email ?? 'N/A',
                $displayName,
                $course->course_name ?? 'N/A',
                $studentMap->session_date ? Carbon::parse($studentMap->session_date)->format('d-m-Y') : 'N/A',
                $this->dashboardAttendanceStatusLabel($studentMap),
                $studentMap->total_duty_count ?? 0,
                $studentMap->total_medical_exception_count ?? 0,
                $studentMap->total_pt_exemption_count ?? 0,
                $studentMap->total_stationed_leave_count ?? 0,
                $studentMap->total_memo_count ?? 0,
                $studentMap->total_notice_count ?? 0,
            ];
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

            // Attendance % for this student in this course (present + late) / total
            $att = CourseStudentAttendance::where('Student_master_pk', $studentPk)
                ->when($coursePk > 0, fn ($q) => $q->where('course_master_pk', $coursePk))
                ->selectRaw('COUNT(*) as total_sessions,
                    COALESCE(SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END), 0) as present_count,
                    COALESCE(SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END), 0) as late_count
                ')
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

        // Get medical exceptions
        $medicalExemptions = StudentMedicalExemption::with(['course', 'category', 'speciality', 'employee'])
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->orderBy('from_date', 'desc')
            ->get();

        $ptExemptions = LeaveApplication::with(['course', 'nature', 'approvedByFaculty', 'attachments'])
            ->where('student_master_pk', $studentPk)
            ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
            ->where('active_inactive', 1)
            ->where('status', LeaveApplication::STATUS_APPROVED)
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
            ->orderBy('from_date', 'desc')
            ->get();

        // Get MDO/Escort duties
        $duties = MDOEscotDutyMap::with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->where('selected_student_list', $studentPk)
            ->orderBy('mdo_date', 'desc')
            ->get();

        // Get notices using OTNoticeMemoService; memos come from the Discipline Memo
        // module's source (discipline_memo_status) so the detail view matches
        // /memo/discipline and the list "Total Memo" count.
        $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);
        $notices = $noticeMemoService->getNotices($studentPk);
        $memos = $noticeMemoService->getDisciplineMemos($studentPk);

        // Get enrolled courses
        $enrolledCourses = StudentMasterCourseMap::with('course')
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->get();

        // Get attendance records summary
        $attendanceSummary = CourseStudentAttendance::where('Student_master_pk', $studentPk)
            ->selectRaw('
                COUNT(*) as total_sessions,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as mdo_count,
                SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) as escort_count,
                SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) as medical_exempt_count,
                SUM(CASE WHEN status = 7 THEN 1 ELSE 0 END) as other_exempt_count,
                SUM(CASE WHEN status = 0 OR status IS NULL THEN 1 ELSE 0 END) as not_marked_count
            ')
            ->first();

        // Calculate total expected sessions (timetables) for student's course groups
        $studentGroupPks = StudentCourseGroupMap::where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->pluck('group_type_master_course_master_map_pk')
            ->toArray();

        $totalExpectedSessions = 0;
        if (!empty($studentGroupPks)) {
            $result = CourseGroupTimetableMapping::whereIn('group_pk', $studentGroupPks)
                ->selectRaw('COUNT(DISTINCT timetable_pk) as count')
                ->first();
            $totalExpectedSessions = $result ? (int)$result->count : 0;
        }

        // Calculate not marked count: sessions without attendance records or with status 0/NULL
        $markedResult = CourseStudentAttendance::where('Student_master_pk', $studentPk)
            ->whereNotNull('status')
            ->where('status', '!=', 0)
            ->selectRaw('COUNT(DISTINCT timetable_pk) as count')
            ->first();
        $markedSessions = $markedResult ? (int)$markedResult->count : 0;

        $notMarkedCount = max(0, $totalExpectedSessions - $markedSessions);

        // Add not_marked_count to attendance summary if it doesn't exist
        if ($attendanceSummary) {
            $attendanceSummary->not_marked_count = $notMarkedCount;
            $attendanceSummary->total_expected_sessions = $totalExpectedSessions;
        }

        return view('admin.dashboard.student_detail', compact(
            'student',
            'medicalExemptions',
            'ptExemptions',
            'stationedLeaves',
            'duties',
            'notices',
            'memos',
            'enrolledCourses',
            'attendanceSummary'
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


