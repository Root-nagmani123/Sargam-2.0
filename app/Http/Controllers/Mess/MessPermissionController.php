<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\MessPermission;
use App\Models\Mess\MessPermissionUser;
use App\Models\UserRoleMaster;
use App\Models\User;
use App\Models\EmployeeRoleMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessPermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index()
    {
        $permissions = MessPermission::with(['role', 'permissionUsers.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.mess.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        $roles = UserRoleMaster::where('active_inactive', 1)
            ->orderBy('user_role_display_name')
            ->get();

        $actions = MessPermission::getAvailableActions();

        return view('admin.mess.permissions.create', compact('roles', 'actions'));
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:user_role_master,pk',
            'action_name' => 'required|string|max:100',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:user_credentials,pk'
        ]);

        // Check for duplicate permission
        $exists = MessPermission::where('role_id', $request->role_id)
            ->where('action_name', $request->action_name)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This permission already exists for the selected role and action.');
        }

        DB::beginTransaction();
        try {
            // Create permission
            $permission = MessPermission::create([
                'role_id' => $request->role_id,
                'action_name' => $request->action_name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'module' => 'mess',
                'is_active' => $request->has('is_active')
            ]);

            // Assign users
            foreach ($request->user_ids as $userId) {
                // Check if user already has this permission
                $userExists = MessPermissionUser::where('mess_permission_id', $permission->id)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$userExists) {
                    MessPermissionUser::create([
                        'mess_permission_id' => $permission->id,
                        'user_id' => $userId
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.mess.permissions.index')
                ->with('success', 'Permission created successfully and assigned to ' . count($request->user_ids) . ' user(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating permission: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing permission
     */
    public function edit($id)
    {
        $permission = MessPermission::with(['role', 'permissionUsers.user'])->findOrFail($id);
        
        $roles = UserRoleMaster::where('active_inactive', 1)
            ->orderBy('user_role_display_name')
            ->get();

        $actions = MessPermission::getAvailableActions();

        // Get users from this role
        $roleUsers = $this->getUsersByRole($permission->role_id);

        // Get currently assigned users
        $assignedUserIds = $permission->permissionUsers->pluck('user_id')->toArray();

        return view('admin.mess.permissions.edit', compact('permission', 'roles', 'actions', 'roleUsers', 'assignedUserIds'));
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, $id)
    {
        $permission = MessPermission::findOrFail($id);

        $request->validate([
            'role_id' => 'required|exists:user_role_master,pk',
            'action_name' => 'required|string|max:100',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:user_credentials,pk'
        ]);

        // Check for duplicate (excluding current)
        $exists = MessPermission::where('role_id', $request->role_id)
            ->where('action_name', $request->action_name)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This permission already exists for the selected role and action.');
        }

        DB::beginTransaction();
        try {
            // Update permission
            $permission->update([
                'role_id' => $request->role_id,
                'action_name' => $request->action_name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'is_active' => $request->has('is_active')
            ]);

            // Delete old user assignments
            MessPermissionUser::where('mess_permission_id', $permission->id)->delete();

            // Add new user assignments
            foreach ($request->user_ids as $userId) {
                MessPermissionUser::create([
                    'mess_permission_id' => $permission->id,
                    'user_id' => $userId
                ]);
            }

            DB::commit();

            return redirect()->route('admin.mess.permissions.index')
                ->with('success', 'Permission updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy($id)
    {
        $permission = MessPermission::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Delete user assignments (cascade will handle this, but being explicit)
            MessPermissionUser::where('mess_permission_id', $id)->delete();
            
            // Delete permission
            $permission->delete();
            
            DB::commit();

            return redirect()->route('admin.mess.permissions.index')
                ->with('success', 'Permission deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error deleting permission: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Get users by role
     */
    public function getUsersByRole(Request $request)
    {
        $roleId = $request->role_id ?? $request;
        
        if ($request instanceof Request) {
            $request->validate([
                'role_id' => 'required|exists:user_role_master,pk'
            ]);
            $roleId = $request->role_id;
        }

        // Get users from employee_role_mapping who have this role
        $users = User::select(
                'user_credentials.pk',
                DB::raw("CONCAT(COALESCE(user_credentials.first_name, ''), ' ', COALESCE(user_credentials.last_name, '')) as name"),
                'user_credentials.email_id as email'
            )
            ->join('employee_role_mapping', 'user_credentials.pk', '=', 'employee_role_mapping.user_credentials_pk')
            ->where('employee_role_mapping.user_role_master_pk', $roleId)
            ->where('user_credentials.Active_inactive', 1)
            ->orderBy('user_credentials.first_name')
            ->get();

        if ($request instanceof Request) {
            return response()->json($users);
        }

        return $users;
    }

    /**
     * Check if current user has permission
     */
    public function checkPermission($actionName)
    {
        $userId = auth()->user()->pk;
        
        $hasPermission = MessPermission::userHasPermission($userId, $actionName);

        return response()->json([
            'has_permission' => $hasPermission
        ]);
    }
}
