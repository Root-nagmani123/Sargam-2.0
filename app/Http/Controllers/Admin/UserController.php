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
use Carbon\Carbon;


class UserController extends Controller
{
    /**
     * Display a listing of users.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
         $year = request('year', now()->year);
        $month = request('month', now()->month);
        
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

      $emp_dob_data = EmployeeMaster::where('status', 1)->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
        ->leftjoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
        ->select('employee_master.first_name','employee_master.email','employee_master.mobile','employee_master.profile_picture', 'employee_master.last_name', 'designation_master.designation_name', 'employee_master.dob')
      ->get();

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
                 
                 if ($coordinatorCourses->isNotEmpty()) {
                     $isCCorACC = true;
                     // Get active courses where faculty is CC/ACC
                    $activeCourseIds = CourseMaster::whereIn('pk', $coordinatorCourses)
                        ->where('active_inactive', 1)
                        ->where('end_date', '>=', now())
                        ->pluck('pk');
                     
                     // Count total students enrolled in these courses
                     if ($activeCourseIds->isNotEmpty()) {
                         $totalStudents = StudentMasterCourseMap::whereIn('course_master_pk', $activeCourseIds)
                             ->where('active_inactive', 1)
                             ->pluck('student_master_pk')
                             ->unique()
                             ->count();
                     }
                 }
             } else {
                 $totalSessions = 0;
             }
             
             // Fetch today's timetable for the logged-in faculty
             $todayTimetable = $this->getTodayTimetableForFaculty($userId);
         }

        return view('admin.dashboard', compact('year', 'month', 'events','emp_dob_data', 'totalActiveCourses', 'upcomingCourses', 'total_guest_faculty', 'total_internal_faculty', 'exemptionCount', 'MDO_count', 'todayTimetable', 'totalSessions', 'totalStudents', 'isCCorACC'));
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
        
        // Check if user is Internal Faculty or Guest Faculty
        if(hasRole('Internal Faculty') || hasRole('Guest Faculty')){
            // Get faculty_master.pk from user_id
            $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
            
            if ($faculty) {
                $facultyPk = $faculty->pk;
                
                // Check if faculty is CC or ACC
                $coordinatorCourses = CourseCordinatorMaster::where(function ($query) use ($facultyPk) {
                    $query->where('Coordinator_name', $facultyPk)
                          ->orWhere('Assistant_Coordinator_name', $facultyPk);
                })->pluck('courses_master_pk')->unique();
                
                if ($coordinatorCourses->isNotEmpty()) {
                    // Get active courses where faculty is CC/ACC
                    $activeCourseIds = CourseMaster::whereIn('pk', $coordinatorCourses)
                        ->where('active_inactive', 1)
                        ->where('end_date', '>=', now())
                        ->pluck('pk');
                    
                    // Get students enrolled in these courses
                    if ($activeCourseIds->isNotEmpty()) {
                        $students = StudentMasterCourseMap::with([
                            'studentMaster', 
                            'course'
                        ])
                            ->whereIn('course_master_pk', $activeCourseIds)
                            ->where('active_inactive', 1)
                            ->get()
                            ->unique('student_master_pk')
                            ->values();
                        
                        // Load group information for each student
                        foreach ($students as $studentMap) {
                            $studentPk = $studentMap->student_master_pk;
                            $coursePk = $studentMap->course_master_pk;
                            
                            // Get the active group mapping for this student-course combination
                            $groupMap = StudentCourseGroupMap::with([
                                'groupTypeMasterCourseMasterMap.courseGroupType',
                                'groupTypeMasterCourseMasterMap.Faculty'
                            ])
                                ->where('student_master_pk', $studentPk)
                                ->where('active_inactive', 1)
                                ->whereHas('groupTypeMasterCourseMasterMap', function($query) use ($coursePk) {
                                    $query->where('course_name', $coursePk);
                                })
                                ->first();
                            
                            // Attach group info to the student map
                            $studentMap->groupMapping = $groupMap;
                        }
                        
                        // Get unique courses from the student list
                        $availableCourses = $students->pluck('course')
                            ->filter()
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
            }
        }
        
        return view('admin.dashboard.student_list', compact('students', 'availableCourses'));
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
            
            if ($request->has('roles') && !empty($request->roles)) {
                // $user->assignRole($request->roles);
                $assignedRoleNames = [];
                foreach ($request->roles as $roleId) {
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
            if (!empty($request->roles)) {
                foreach ($request->roles as $roleId) {
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
    DB::table($request->table)
        ->where($idColumn, $request->id)
        ->update([$request->column => $request->status]);
        

    return response()->json(['message' => 'Status updated successfully']);
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
        if (!empty($request->roles)) {
            foreach ($request->roles as $roleId) {
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


