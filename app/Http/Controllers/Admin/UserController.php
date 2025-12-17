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



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

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
        return view('admin.dashboard', compact('year', 'month', 'events','emp_dob_data', 'totalActiveCourses', 'upcomingCourses', 'total_guest_faculty', 'total_internal_faculty'));
    }

    public function index(UserCredentialsDataTable $request)
    {
        return $request->render('admin.user_management.users.index');
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
}


