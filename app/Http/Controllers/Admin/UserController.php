<?php

namespace App\Http\Controllers\Admin;

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

use Adldap\Laravel\Facades\Adldap;
use Illuminate\Support\Facades\Auth;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\StudentMedicalExemption;
use App\Models\MDOEscotDutyMap;
use App\Models\StudentCourseGroupMap;
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
use Carbon\Carbon;


class UserController extends Controller
{
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
         
         // Calculate total sessions for Internal Faculty or Guest Faculty
         if(hasRole('Internal Faculty') || hasRole('Guest Faculty')){
             // Get faculty_master.pk from user_id
             $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
             
             if ($faculty) {
                 $facultyPk = $faculty->pk;
                 $totalSessions = CalendarEvent::where('active_inactive', 1)
                     ->where(function ($query) use ($facultyPk) {
                         $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$facultyPk.'"'])
                               ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$facultyPk]);
                     })
                     ->count();
                 
                 // Check if faculty is CC or ACC
                 $coordinatorCourses = CourseCordinatorMaster::where(function ($query) use ($facultyPk) {
                     $query->where('Coordinator_name', $facultyPk)
                           ->orWhere('Assistant_Coordinator_name', $facultyPk);
                 })->pluck('courses_master_pk')->unique();
                 
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
            'fullDuplicateContractualIdCardRequests'
        ));
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
    public function studentList()
    {
        $userId = Auth::user()->user_id;
        $students = collect([]);
        $availableCourses = collect([]);
        $facultyPk = null;
        
        // Check if user is Internal Faculty or Guest Faculty
        if(hasRole('Internal Faculty') || hasRole('Guest Faculty')){
            // Get faculty_master.pk from user_id
            $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
            
            if ($faculty) {
                $facultyPk = $faculty->pk;
                
                // ========== SOURCE 1: CC/ACC Courses ==========
                $source1Students = collect([]);
                $coordinatorCourses = CourseCordinatorMaster::where(function ($query) use ($facultyPk) {
                    $query->where('Coordinator_name', $facultyPk)
                          ->orWhere('Assistant_Coordinator_name', $facultyPk);
                })->pluck('courses_master_pk')->unique();
                
                if ($coordinatorCourses->isNotEmpty()) {
                    // Filter for active courses only
                    $activeCoordinatorCourses = CourseMaster::whereIn('pk', $coordinatorCourses)
                        ->where('active_inactive', 1)
                        ->where('end_date', '>=', now())
                        ->pluck('pk');
                    
                    if ($activeCoordinatorCourses->isNotEmpty()) {
                        // Get students from student_master_course_map for Source 1
                        $source1StudentMaps = StudentMasterCourseMap::with([
                            'studentMaster.cadre', 
                            'course'
                        ])
                            ->whereIn('course_master_pk', $activeCoordinatorCourses)
                            ->where('active_inactive', 1)
                            ->get();
                        
                        // Convert Source 1 to stdClass format for consistency
                        $source1Students = collect([]);
                        foreach ($source1StudentMaps as $studentMap) {
                            $stdObj = new \stdClass();
                            $stdObj->student_master_pk = $studentMap->student_master_pk;
                            $stdObj->course_master_pk = $studentMap->course_master_pk;
                            $stdObj->studentMaster = $studentMap->studentMaster;
                            $stdObj->course = $studentMap->course;
                            $stdObj->source = 'cc_acc'; // Track source
                            $source1Students->push($stdObj);
                        }
                    }
                }
                
                // ========== SOURCE 2: Group Mappings ==========
                $source2Students = collect([]);
                
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
                        
                        // Step 5: Get students from student_course_group_map using group mapping pk
                        if ($activeGroupMappingPks->isNotEmpty()) {
                            $source2GroupMaps = StudentCourseGroupMap::with([
                                'student.cadre',
                                'groupTypeMasterCourseMasterMap.courseGroup',
                                'groupTypeMasterCourseMasterMap.courseGroupType',
                                'groupTypeMasterCourseMasterMap.Faculty'
                            ])
                                ->whereIn('group_type_master_course_master_map_pk', $activeGroupMappingPks)
                                ->where('active_inactive', 1)
                                ->get();
                            
                            // Step 6: Convert to unified format (similar to Source 1 structure)
                            foreach ($source2GroupMaps as $groupMap) {
                                $studentPk = $groupMap->student_master_pk;
                                $coursePk = $groupMap->groupTypeMasterCourseMasterMap->course_name ?? null;
                                
                                if ($coursePk && $groupMap->student) {
                                    // Get course from relationship (already loaded via with())
                                    $course = $groupMap->groupTypeMasterCourseMasterMap->courseGroup ?? null;
                                    
                                    if ($course) {
                                        // Create a stdClass object to mimic StudentMasterCourseMap structure
                                        $studentMap = new \stdClass();
                                        $studentMap->student_master_pk = $studentPk;
                                        $studentMap->course_master_pk = $coursePk;
                                        $studentMap->studentMaster = $groupMap->student; // Use 'student' relationship
                                        $studentMap->course = $course;
                                        $studentMap->groupMapping = $groupMap;
                                        $studentMap->source = 'group_mapping'; // Track source
                                        
                                        $source2Students->push($studentMap);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // ========== MERGE BOTH SOURCES ==========
                // Combine Source 1 and Source 2 students manually to avoid getKey() issues
                // Priority: Source 2 students (they have groupMapping) over Source 1 students
                $seenStudentPks = [];
                $uniqueStudents = collect([]);
                
                // Process Source 2 students FIRST (they have groupMapping, so prioritize them)
                foreach ($source2Students as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    if (!in_array($studentPk, $seenStudentPks)) {
                        $seenStudentPks[] = $studentPk;
                        $uniqueStudents->push($studentMap);
                    }
                }
                
                // Process Source 1 students (only if not already added from Source 2)
                foreach ($source1Students as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    if (!in_array($studentPk, $seenStudentPks)) {
                        $seenStudentPks[] = $studentPk;
                        $uniqueStudents->push($studentMap);
                    }
                }
                
                // Load additional data for each student
                $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);
                
                foreach ($uniqueStudents as $studentMap) {
                    $studentPk = $studentMap->student_master_pk;
                    $coursePk = $studentMap->course_master_pk ?? null;
                    
                    // For Source 1 students, get group mapping if not already set
                    // Load full relationship including group_name
                    if (!isset($studentMap->groupMapping) && $coursePk) {
                        $groupMap = StudentCourseGroupMap::with([
                            'groupTypeMasterCourseMasterMap.courseGroupType',
                            'groupTypeMasterCourseMasterMap.Faculty',
                            'groupTypeMasterCourseMasterMap.courseGroup'
                        ])
                            ->where('student_master_pk', $studentPk)
                            ->where('active_inactive', 1)
                            ->whereHas('groupTypeMasterCourseMasterMap', function($query) use ($coursePk) {
                                $query->where('course_name', $coursePk);
                            })
                            ->first();
                        
                        $studentMap->groupMapping = $groupMap;
                    }
                    
                    // Get counts for each student
                    $studentMap->total_duty_count = MDOEscotDutyMap::where('selected_student_list', $studentPk)->count();
                    
                    $studentMap->total_medical_exception_count = StudentMedicalExemption::where('student_master_pk', $studentPk)
                        ->where('active_inactive', 1)
                        ->count();
                    
                    // Get notices and memos using OTNoticeMemoService
                    $notices = $noticeMemoService->getNotices($studentPk);
                    $memos = $noticeMemoService->getMemos($studentPk);
                    
                    $studentMap->total_notice_count = $notices->count();
                    $studentMap->total_memo_count = $memos->count();
                }
                
                $students = $uniqueStudents;
                
                // Get unique courses from the student list - only active courses
                $availableCourses = $students->pluck('course')
                    ->filter(function($course) {
                        // Filter only active courses
                        return $course && 
                               isset($course->active_inactive) && 
                               $course->active_inactive == 1 &&
                               isset($course->end_date) &&
                               \Carbon\Carbon::parse($course->end_date)->gte(now());
                    })
                    ->unique('pk')
                    ->map(function($course) {
                        return [
                            'pk' => $course->pk,
                            'course_name' => $course->course_name
                        ];
                    })
                    ->values()
                    ->sortBy('course_name');
            }
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
        
        return view('admin.dashboard.student_list', compact('students', 'availableCourses', 'counsellorTypes', 'groupNames'));
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
            $memosCount = $noticeMemoService->getMemos($studentPk)->count();

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

        // Get medical exceptions
        $medicalExemptions = StudentMedicalExemption::with(['course', 'category', 'speciality', 'employee'])
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->orderBy('from_date', 'desc')
            ->get();

        // Get MDO/Escort duties
        $duties = MDOEscotDutyMap::with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->where('selected_student_list', $studentPk)
            ->orderBy('mdo_date', 'desc')
            ->get();

        // Get notices using OTNoticeMemoService
        $noticeMemoService = app(\App\Services\OTNoticeMemoService::class);
        $notices = $noticeMemoService->getNotices($studentPk);
        $memos = $noticeMemoService->getMemos($studentPk);

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
            'duties',
            'notices',
            'memos',
            'enrolledCourses',
            'attendanceSummary'
        ));
    }

   public function index(Request $request)
{
    $perPage = $request->input('per_page', 10); // Default 10 items per page
    $search = $request->input('search');
  $user_type = trim($request->input('User_type'));

    $usersQuery = DB::table('user_credentials as uc')
        ->leftJoin('employee_role_mapping as erm', 'erm.user_credentials_pk', '=', 'uc.pk')
        ->leftJoin('user_role_master as urm', 'urm.pk', '=', 'erm.user_role_master_pk')
        ->select(
            'uc.pk',
            'uc.user_name',
            'uc.first_name',
            'uc.last_name',
            'uc.email_id',
            'uc.mobile_no',
            DB::raw("GROUP_CONCAT(urm.user_role_display_name SEPARATOR ', ') as roles")
        )
        ->groupBy(
            'uc.pk',
            'uc.user_name',
            'uc.first_name',
            'uc.last_name',
            'uc.email_id',
            'uc.mobile_no'
        );

    if ($search) {
        $usersQuery->where(function($q) use ($search) {
            $q->where('uc.user_name', 'like', "%$search%")
              ->orWhere('uc.first_name', 'like', "%$search%")
              ->orWhere('uc.last_name', 'like', "%$search%")
              ->orWhere('uc.email_id', 'like', "%$search%");
        });
    }
   if (!empty($user_type)) {
    $usersQuery->where('uc.user_category', $user_type);
}

    $users = $usersQuery->paginate($perPage)->withQueryString();

    return view('admin.user_management.users.index', compact('users', 'perPage', 'search', 'user_type'));
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
            
            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

public function toggleStatus(Request $request)
{
    $idColumn = $request->id_column ?? 'pk'; 
    $table = $request->table;
    $column = $request->column;
    $id = $request->id;
    $status = $request->status;

    DB::table($request->table)
        ->where($idColumn, $id)
        ->update([$column => $status]);

    $newState = ((int) $status === 1) ? 'Active' : 'Inactive';
    session()->flash('success', "Status updated to {$newState}.");

    return response()->json([
        'message' => "Status updated to {$newState}.",
        'state' => $newState,
    ]);
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
   
    $userRoles = \DB::table('employee_role_mapping')
        ->where('user_credentials_pk', $decryptedId)
        ->pluck('user_role_master_pk')
        ->toArray();

    return view('admin.user_management.users.assign_role',
        compact('user', 'userRoles'));
}
public function getAllRoles()
{
    $roles = UserRoleMaster::select('pk', 'user_role_name', 'user_role_display_name')
        ->orderBy('pk', 'DESC')
        ->get();

    return response()->json($roles);
}



public function assignRoleSave(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer',
        'roles'   => 'nullable|array',
    ]);

    $userId = $request->user_id;

    \DB::beginTransaction();

    try {

        // Remove old roles
        \DB::table('employee_role_mapping')
            ->where('user_credentials_pk', $userId)
            ->delete();

        // Insert new roles
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
                \DB::table('employee_role_mapping')->insert([
                    'user_credentials_pk'  => $userId,
                    'user_role_master_pk'  => $roleId,
                    'active_inactive'      => 1,
                    'created_date'         => now(),
                    'updated_date'        => now(),
                ]);
                // Get role name for notification
                $role = UserRoleMaster::find($roleId);
                if ($role) {
                    $assignedRoleNames[] = $role->user_role_display_name ?? $role->user_role_name;
                }
            }
        }

        \DB::commit();
        
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
        \DB::rollBack();
        return back()->with('error', 'Error: '.$e->getMessage());
    }
}

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


}


