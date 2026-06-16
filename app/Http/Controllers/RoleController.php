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

        // Permission names stored as "menu.{id}" — extract enabled menu IDs
        $enabledMenuIds = $role->permissions
            ->pluck('name')
            ->filter(fn($name) => str_starts_with($name, 'menu.'))
            ->map(fn($name) => (int) str_replace('menu.', '', $name))
            ->toArray();

        $categories = SidebarCategory::with([
            'groups.menus.children'
        ])->get();

        return view('roles-permissions.assign-permission', compact('role', 'enabledMenuIds', 'categories'));
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
        $menuId = $request->input('menu_id');
        $status = $request->input('status');

        if (!$menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Menu ID missing'
            ]);
        }

        // Unique permission name per menu — avoids collision with same permission_name on multiple menus
        $permissionName = 'menu.' . $menuId;

        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web'
        ]);

        if ($status == 1) {
            if (!$role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permissionName);
            }
        } else {
            if ($role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned successfully.'
        ]);
    }
}
