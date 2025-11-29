<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\UserCredentialsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\User;
use App\Models\UserRoleMaster;
use App\Models\EmployeeRoleMapping;

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
            
            if ($request->has('roles')) {
                // $user->assignRole($request->roles);
                foreach ($request->roles as $roleId) {
                    EmployeeRoleMapping::create([
                    'user_credentials_pk' => $user->id,
                    'user_role_master_pk' => $roleId,
                    'active_inactive' => 1,
                    'created_date' => now(),
                    'updated_date' => now(),
                ]);
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
            foreach ($request->roles as $roleId) {
                EmployeeRoleMapping::create([
                    'user_credentials_pk' => $user->id,
                    'user_role_master_pk' => $roleId,
                    'active_inactive' => 1,
                    'created_date' => now(),
                    'updated_date' => now(),
                ]);
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
    $decryptedId = decrypt($id);
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
        if (!empty($request->roles)) {
            foreach ($request->roles as $roleId) {
                \DB::table('employee_role_mapping')->insert([
                    'user_credentials_pk'  => $userId,
                    'user_role_master_pk'  => $roleId,
                    'active_inactive'      => 1,
                    'created_date'         => now(),
                    'updated_date'        => now(),
                ]);
            }
        }

        \DB::commit();

        return redirect()->route('admin.users.index')
                         ->with('success', 'Roles assigned successfully.');

    } catch (\Exception $e) {
        \DB::rollBack();
        return back()->with('error', 'Error: '.$e->getMessage());
    }
}


} 