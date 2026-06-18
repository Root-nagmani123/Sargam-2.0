<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SidebarMenu\SidebarCategory;
use App\Models\DashboardCard;

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

    public function updateDashboardCard(Request $request, $id)
    {
        $card = DashboardCard::findOrFail($id);
        $request->validate([
            'label'      => 'required|string|max:200',
            'icon'       => 'required|string|max:100',
            'color_class'=> 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        $card->update($request->only('label', 'icon', 'color_class', 'sort_order'));

        return response()->json([
            'success' => true,
            'message' => 'Card updated successfully.',
            'card'    => $card->fresh(),
        ]);
    }

    public function storeDashboardCard(Request $request)
    {
        $request->validate([
            'label'      => 'required|string|max:200',
            'icon'       => 'required|string|max:100',
            'color_class'=> 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        $baseKey = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($request->label)), '_');
        $key = $baseKey;
        $i = 1;
        while (DashboardCard::where('key', $key)->exists()) {
            $key = $baseKey . '_' . $i++;
        }

        $card = DashboardCard::create(array_merge(
            $request->only('label', 'icon', 'color_class', 'sort_order'),
            ['key' => $key]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Card created successfully.',
            'card'    => $card,
        ]);
    }

    public function showDashboard($id)
    {
        $role = Role::findOrFail($id);
        $allCards = DashboardCard::orderBy('sort_order')->get();
        $assignedCardIds = $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
            ->pluck('dashboard_cards.id')
            ->toArray();
        return view('roles-permissions.assign-dashboard', compact('role', 'allCards', 'assignedCardIds'));
    }

    public function assignDashboardCard(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $cardId = $request->card_id;
        $status = $request->status;

        if (!$cardId) {
            return response()->json(['success' => false, 'message' => 'Card ID missing']);
        }

        $card = DashboardCard::findOrFail($cardId);

        if ($status == 1) {
            $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
                ->syncWithoutDetaching([$card->id]);
        } else {
            $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
                ->detach($card->id);
        }

        return response()->json(['success' => true, 'message' => 'Dashboard card updated successfully.']);
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
