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

        // Grid is server-side: rows (and the filter bar's filters) are resolved here.
        if ($request->ajax()) {
            return $this->rolePermissionsDatatable($request, $categories, $rolePermissions);
        }

        return view('roles-permissions.assign-permission', compact('role', 'rolePermissions', 'categories'));
    }

    /**
     * Server-side rows for the role permission grid: one row per menu (or sub-menu),
     * with the page's Category / Status filters applied before paging.
     */
    protected function rolePermissionsDatatable(Request $request, $categories, array $rolePermissions)
    {
        $rows = collect();

        foreach ($categories as $category) {
            foreach ($category->groups as $group) {
                foreach ($group->menus as $menu) {
                    $children = $menu->children;

                    if ($children->count() > 0) {
                        foreach ($children as $child) {
                            $rows->push([
                                'id' => $child->id,
                                'category' => $category->name,
                                'group' => $group->name,
                                'menu' => $menu->name,
                                'submenu' => $child->name,
                                'permission' => $child->permission_name,
                                'enabled' => in_array($child->permission_name, $rolePermissions),
                            ]);
                        }

                        continue;
                    }

                    $rows->push([
                        'id' => $menu->id,
                        'category' => $category->name,
                        'group' => $group->name,
                        'menu' => $menu->name,
                        'submenu' => '-',
                        'permission' => $menu->permission_name,
                        'enabled' => in_array($menu->permission_name, $rolePermissions),
                    ]);
                }
            }
        }

        $categoryFilter = (string) $request->query('category_filter', '');
        if ($categoryFilter !== '') {
            $rows = $rows->where('category', $categoryFilter)->values();
        }

        $statusFilter = (string) $request->query('status_filter', '');
        if ($statusFilter === 'enabled' || $statusFilter === 'disabled') {
            $wanted = $statusFilter === 'enabled';
            $rows = $rows->filter(fn ($row) => $row['enabled'] === $wanted)->values();
        }

        return \Yajra\DataTables\Facades\DataTables::of($rows)
            ->editColumn('submenu', fn ($row) => e($row['submenu']))
            ->addColumn('action', function ($row) {
                return '<div class="form-check form-switch">'
                    .'<input class="form-check-input permission-toggle" type="checkbox" name="permissions[]"'
                    .' data-id="'.e($row['id']).'" value="'.e($row['permission']).'"'
                    .($row['enabled'] ? ' checked' : '').'>'
                    .'<label class="form-check-label"></label></div>';
            })
            ->rawColumns(['action'])
            ->make(true);
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

    public function showDashboard(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $assignedCardIds = $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
            ->pluck('dashboard_cards.id')
            ->toArray();

        // Grid is server-side: rows and the Status filter are resolved here.
        if ($request->ajax()) {
            return $this->dashboardCardsDatatable($request, $assignedCardIds);
        }

        $allCards = DashboardCard::orderBy('id', 'desc')->get();
        $materialIcons = $this->materialIconNames();

        return view('roles-permissions.assign-dashboard', compact('role', 'allCards', 'assignedCardIds', 'materialIcons'));
    }

    /**
     * Server-side rows for the dashboard cards grid (Enabled/Disabled filter applied in SQL).
     *
     * @param  array<int, int>  $assignedCardIds
     */
    protected function dashboardCardsDatatable(Request $request, array $assignedCardIds)
    {
        $query = DashboardCard::orderBy('id', 'desc');

        $statusFilter = (string) $request->query('status_filter', '');
        if ($statusFilter === 'enabled') {
            $query->whereIn('id', $assignedCardIds ?: [0]);
        } elseif ($statusFilter === 'disabled') {
            $query->whereNotIn('id', $assignedCardIds ?: [0]);
        }

        return \Yajra\DataTables\Facades\DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($card) => '<span class="fw-medium">'.e($card->label).'</span>')
            ->addColumn('icon_cell', fn ($card) => '<span class="dc-icon-sm stat-icon-wrapper '.e($card->color_class).' d-inline-flex align-items-center justify-content-center">'
                .'<i class="material-symbols-rounded">'.e($card->icon).'</i></span>')
            ->addColumn('order', fn ($card) => '<span class="badge bg-primary">'.e($card->sort_order).'</span>')
            ->addColumn('created', fn ($card) => $card->created_at ? e($card->created_at->format('d-m-Y')) : '-')
            ->addColumn('enable', function ($card) use ($assignedCardIds) {
                return '<div class="form-check form-switch d-inline-block">'
                    .'<input class="form-check-input card-toggle" type="checkbox" data-id="'.e($card->id).'"'
                    .(in_array($card->id, $assignedCardIds) ? ' checked' : '').'>'
                    .'<label class="form-check-label"></label></div>';
            })
            ->addColumn('action', function ($card) use ($assignedCardIds) {
                return '<div class="d-inline-flex align-items-center gap-1" role="group">'
                    .'<button class="btn btn-sm border-0 bg-transparent text-primary edit-card-btn d-inline-flex align-items-center justify-content-center"'
                    .' data-id="'.e($card->id).'" data-label="'.e($card->label).'" data-icon="'.e($card->icon).'"'
                    .' data-color="'.e($card->color_class).'" data-sort="'.e($card->sort_order).'" title="Edit">'
                    .'<i class="material-symbols-rounded" style="font-size:18px;">edit</i></button>'
                    .'<button class="btn btn-sm border-0 bg-transparent text-danger delete-card-btn d-inline-flex align-items-center justify-content-center'
                    .(in_array($card->id, $assignedCardIds) ? ' d-none' : '').'" data-id="'.e($card->id).'" title="Delete">'
                    .'<i class="material-symbols-rounded" style="font-size:18px;">delete</i></button></div>';
            })
            ->setRowAttr(['data-id' => fn ($card) => $card->id])
            ->filterColumn('name', fn ($q, $keyword) => $q->where('label', 'like', "%{$keyword}%"))
            ->orderColumn('name', 'label $1')
            ->rawColumns(['name', 'icon_cell', 'order', 'enable', 'action'])
            ->make(true);
    }

    private function materialIconNames(): array
    {
        $path = resource_path('data/material-symbols-rounded.codepoints');
        if (!is_readable($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return [];
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $parts = preg_split('/\s+/', $line, 2);
            if (!empty($parts[0])) $names[] = $parts[0];
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        return $names;
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
