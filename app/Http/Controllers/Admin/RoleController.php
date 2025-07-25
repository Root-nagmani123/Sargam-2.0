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
    function __construct() {
        $this->middleware('permission:admin.roles.index', ['only' => ['index']]);
        $this->middleware('permisson:admin.roles.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:admin.roles.edit', ['only' => ['edit', 'update']]);
    }
    public function index(RoleDataTable $dataTable)
    {
        $roles = Role::with('permissions')->get();
        return view('admin.user_management.roles.index', compact('roles'));
    }

    public function create()
    {
        $removePrefixes = [
            'log-viewer::',
            'livewire',
            'ignition',
            'login',
            'post_login',
            'logout',
            'password',
            'verification',
        ];

        // $permissions = Permission::all()->reject(function ($perm) use ($removePrefixes) {
        //     foreach ($removePrefixes as $prefix) {
        //         if (str_starts_with($perm->name, $prefix)) {
        //             return true;
        //         }
        //     }
        //     return false;
        // });
        // $grouped = $permissions->groupBy(function ($item) {
        //     return explode('.', $item->name)[0];
        // });
        $grouped = Permission::where('is_visible', 1)->get()->groupBy(['permission_group', 'permission_sub_group']);

        return view('admin.user_management.roles.create', compact('grouped'));
    }

    public function store(Request $request): RedirectResponse
    {
        // dd($request->all());
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
        $grouped = Permission::where('is_visible', 1)->get()->groupBy(['permission_group', 'permission_sub_group']);
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.user_management.roles.edit', compact('role', 'grouped', 'rolePermissions'));
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
