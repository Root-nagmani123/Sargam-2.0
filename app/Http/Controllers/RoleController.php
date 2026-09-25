<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRoleAdmin;
use App\Models\DashboardCard;
use App\Models\SidebarMenu\SidebarCategory;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $service;

    public function __construct(RoleService $roleService)
    {
        $this->service = $roleService;

        // Everything that CHANGES what a role can do requires Super Admin.
        //
        // Registered HERE and not only on the route, because this controller is
        // mounted TWICE: `roles/*` at routes/web.php:162-171 and a second,
        // hand-written `admin/roles/*` block at routes/web.php:180-185. A gate
        // attached to one route group protects that URL and nothing else, so
        // gating only the first would have left store/update/destroy reachable
        // through the second - and left the next mount unprotected as well.
        // Constructor middleware runs for every route that resolves to this
        // class, which is the property the fix needs.
        //
        // assignPermission() is the one that made this urgent: it
        // firstOrCreate()d whatever permission name it was posted and granted
        // it to the role in the URL, with no check on the caller, so any
        // authenticated account could grant itself any permission and defeat
        // every `can()`-based gate in the application. PR #309 F-027 /
        // PR #317 L-8.
        //
        // Reads are deliberately NOT included: listing roles is not escalation,
        // and refusing the screen to an account that can already open it would
        // be a different defect rather than a fix.
        $this->middleware(EnsureRoleAdmin::class)->only([
            'store',
            'update',
            'destroy',
            'assignPermission',
            'assignDashboardCard',
            'storeDashboardCard',
            'updateDashboardCard',
            'destroyDashboardCard',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
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
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create([
            'name' => $validated['name'],
        ]);

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $categories = SidebarCategory::with([
            'groups.menus',
        ])->get();

        // dd($categories);
        return view('roles-permissions.assign-permission', compact('role', 'rolePermissions', 'categories'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
        ]);

        Role::where('id', $id)->update([
            'name' => $validated['name'],
        ]);

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {}

    public function destroyDashboardCard($id)
    {
        $card = DashboardCard::findOrFail($id);
        $card->roles()->detach();
        $card->delete();

        return response()->json(['success' => true, 'message' => 'Card deleted successfully.']);
    }

    public function updateDashboardCard(Request $request, $id)
    {
        $card = DashboardCard::findOrFail($id);
        $request->validate([
            'label' => 'required|string|max:200',
            'icon' => 'required|string|max:100',
            'color_class' => 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        $card->update($request->only('label', 'icon', 'color_class', 'sort_order'));

        return response()->json([
            'success' => true,
            'message' => 'Card updated successfully.',
            'card' => $card->fresh(),
        ]);
    }

    public function storeDashboardCard(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:200',
            'icon' => 'required|string|max:100',
            'color_class' => 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        $baseKey = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($request->label)), '_');
        $key = $baseKey;
        $i = 1;
        while (DashboardCard::where('key', $key)->exists()) {
            $key = $baseKey.'_'.$i++;
        }

        $card = DashboardCard::create(array_merge(
            $request->only('label', 'icon', 'color_class', 'sort_order'),
            ['key' => $key]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Card created successfully.',
            'card' => $card,
        ]);
    }

    public function showDashboard($id)
    {
        $role = Role::findOrFail($id);
        $allCards = DashboardCard::orderBy('id', 'desc')->get();
        $assignedCardIds = $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
            ->pluck('dashboard_cards.id')
            ->toArray();
        $materialIcons = $this->materialIconNames();

        return view('roles-permissions.assign-dashboard', compact('role', 'allCards', 'assignedCardIds', 'materialIcons'));
    }

    private function materialIconNames(): array
    {
        $path = resource_path('data/material-symbols-rounded.codepoints');
        if (! is_readable($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return [];
        }
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 2);
            if (! empty($parts[0])) {
                $names[] = $parts[0];
            }
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    public function assignDashboardCard(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $cardId = $request->card_id;
        $status = $request->status;

        if (! $cardId) {
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
        if (! $permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission missing',
            ]);
        }

        // Only a name this application actually has a screen for, or one that is
        // already a permission. firstOrCreate() on its own accepted ANY string and
        // minted a permission row for it, so a single request could invent a
        // permission and attach it to any role by id - and the typos already in the
        // table ('dashbaord', 'faculty_test', and 46 more with no menus row behind
        // them) are what that looks like after a few years.
        //
        // Existing names stay writable, including the orphans: this endpoint is the
        // only way to revoke them, and refusing those would strand them granted.
        // What is refused is INVENTING a name that no menus row defines.
        $existing = Permission::where('name', $permission)->where('guard_name', 'web')->first();

        if (! $existing) {
            $definedByAScreen = DB::table('menus')
                ->where('permission_name', $permission)
                ->exists();

            if (! $definedByAScreen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown permission.',
                ], 422);
            }
        }

        // Privilege-amplification guard. On this branch the route is reachable by Super
        // Admin only (EnsureRoleAdmin, registered in the constructor), so this guard is
        // defence in depth: it was written for PR #311, where the route is gated on the
        // `roles` permission and that permission is granted to Training-Induction (10
        // accounts), and it keeps this method safe if the gate is ever widened the same
        // way here. The check above constrains WHICH names may be written; it says
        // nothing about who may write them, so a `roles` holder could grant its own
        // role any permission in the table and walk through the gate it was excluded
        // from. Confirmed by executed probe against the review database: an account
        // holding only Training-Induction went 403 -> 200 on `/sidebar/menus` in one
        // request by granting itself `menus`.
        //
        // This is the same shape as the assignRoleSave() guard (review finding F-023):
        // the route gate answered "may this caller use this screen" and nothing
        // answered "may this caller hand out THIS". The rule is deliberately narrow:
        //
        //   - the Super Admin role's permission set is not editable by anyone else, and
        //   - a caller may only grant or revoke a permission it already holds itself,
        //     so administering permissions can spread authority sideways but never
        //     amplify it.
        //
        // Revoking is covered as well as granting: a permission the caller does not
        // hold is not theirs to strip from another role either. Super Admin is exempt
        // from both rules, so the orphaned names this endpoint exists to clean up stay
        // revocable by the people who would do it.
        if (! isSidebarPrivilegedUser()) {
            $actor = Auth::user();

            if ($role->name === 'Super Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only a Super Admin may change the Super Admin role.',
                ], 403);
            }

            if (! $actor || ! $actor->getAllPermissions()->pluck('name')->contains($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You may only assign a permission you hold yourself.',
                ], 403);
            }
        }

        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);

        if ($status == 1) {

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }

        } else {

            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned successfully.',
        ]);
    }
}
