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
use App\Models\UserRoleMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(RoleDataTable $dataTable)
    {
        return $dataTable->render('admin.user_management.roles.index');
    }

    // public function create()
    // {
    //     $all_permissions = Permission::all();
        
    //     return view('admin.user_management.roles.create', compact('all_permissions'));
    // }

    // public function store(Request $request): RedirectResponse
    // {
    //     $validated = $request->validate([
    //         'name' => ['required', 'string', 'max:255', 'unique:user_role_master,user_role_name'],
    //         // 'permission' => ['required', 'array', 'min:1'],
    //         // 'permission.*' => ['exists:permissions,id']
    //     ]);

    //     // $permissionsID = array_map(
    //     //     function($value) { return (int)$value; },
    //     //     $request->input('permission')
    //     // );
    
    //     $role = UserRoleMaster::create(['user_role_display_name' => $request->input('name')]);
    //     // $role->syncPermissions($permissionsID);
    
    //     return redirect()->route('admin.roles.index')->with('success','Role created successfully');
    // }

    // public function edit(UserRoleMaster $role)
    // {
    //     // $all_permissions = Permission::all();
    //     $rolePermissions = $role->permissions->pluck('id')->toArray();
        
    //     return view('admin.user_management.roles.edit', compact('role', 'rolePermissions'));
    // }

    // public function update(Request $request, Role $role)
    // {
    //     try {
    //         $this->validate($request, [
    //             'name' => [
    //                 'required', 
    //                 'string', 
    //                 'max:255', 
    //                 Rule::unique('roles', 'name')->ignore($role->id)
    //             ],
    //             'permission' => ['required', 'array', 'min:1'],
    //             'permission.*' => ['exists:permissions,id']
    //         ]);

    //         $permissionsID = array_map(
    //             function($value) { return (int)$value; },
    //             $request->input('permission')
    //         );

    //         $role->update(['name' => $request->input('name')]);

    //         $role->syncPermissions($permissionsID);

    //         return redirect()->route('admin.roles.index')->with('success','Role updated successfully');
    //     } catch (\Exception $e) {
    //         return redirect()
    //             ->route('admin.roles.index')
    //             ->with('error', 'Failed to delete role: ' . $e->getMessage());
    //     }
    // }
     public function create()
    {
        return view('admin.user_management.roles.create'); // No permissions needed
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:user_role_master,user_role_name'
            ],
            'display_name' => [
                'required',
                'string',
                'max:255',
                'unique:user_role_master,user_role_display_name'
            ],
        ]);

        UserRoleMaster::create([
            'user_role_name'         => $request->name,
            'user_role_display_name' => $request->display_name,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully');
    }

   public function edit($id)
{
       $pk = decrypt($id);
       $role = UserRoleMaster::findOrFail($pk);
    // print_r($role->toArray()); // Debug line to check the contents of $role
    // die(); // Stop execution to see the output
    return view('admin.user_management.roles.edit', compact('role'));
}


    public function update(Request $request, $id)
    {
         $pk = decrypt($id);
    $role = UserRoleMaster::findOrFail($pk);
       $request->validate([
    'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('user_role_master', 'user_role_name')->ignore($role->pk, 'pk')
    ],
    'display_name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('user_role_master', 'user_role_display_name')->ignore($role->pk, 'pk')
    ],
]);



        $role->update([
            'user_role_name'         => $request->name,
            'user_role_display_name' => $request->display_name,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully');
    }

    public function destroy($id)
    {
         $pk = decrypt($id);
         $role = UserRoleMaster::findOrFail($pk);
    
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
