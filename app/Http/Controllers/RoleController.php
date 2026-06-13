<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SidebarMenu\SidebarCategory;

class RoleController extends Controller
{
    protected $service;

    public function __construct(RoleService $roleService)
    {
        $this->service = $roleService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('roles-permissions.roles', $pageData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create([
            'name' => $validated['name']
        ]);

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $categories = SidebarCategory::with([
            'groups.menus'
        ])->get();
        // dd($categories);
        return view('roles-permissions.assign-permission', compact('role', 'rolePermissions', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
        ]);

        Role::where('id', $id)->update([
            'name' => $validated['name']
        ]);
        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    public function assignPermission(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $permission = $request->permission;
        $status = $request->status;
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission missing'
            ]);
        }

        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web'
        ]);

        if ($status == 1) {

            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }

        } else {

            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Permission assigned successfully.'
        ]);
    }
}
