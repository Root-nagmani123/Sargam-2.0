<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\RoleDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index() // RoleDataTable $dataTable
    {
        $roles = Role::with('permissions')->get();
        return view('admin.user_management.roles.index', compact('roles'));
    }

    public function create()
    {
        $all_permissions = Permission::all();
        
        return view('admin.user_management.roles.create', compact('all_permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permission' => ['required', 'array', 'min:1'],
            'permission.*' => ['exists:permissions,id']
        ]);

        $permissionsID = array_map(
            function($value) { return (int)$value; },
            $request->input('permission')
        );
    
        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($permissionsID);
    
        return redirect()->route('admin.roles.index')->with('success','Role created successfully');
    }

    public function edit(Role $role)
    {
        $all_permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.user_management.roles.edit', compact('role', 'all_permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        try {
            $this->validate($request, [
                'name' => [
                    'required', 
                    'string', 
                    'max:255', 
                    Rule::unique('roles', 'name')->ignore($role->id)
                ],
                'permission' => ['required', 'array', 'min:1'],
                'permission.*' => ['exists:permissions,id']
            ]);

            $permissionsID = array_map(
                function($value) { return (int)$value; },
                $request->input('permission')
            );

            $role->update(['name' => $request->input('name')]);

            $role->syncPermissions($permissionsID);

            return redirect()->route('admin.roles.index')->with('success','Role updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
}
