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
     * Download / Print — one action, two formats, off the same query and the
     * same column definitions, so the CSV and the printout can't drift apart
     * (docs/new-design-index-page.md §1). ?q and ?cols are stamped on by the
     * grid so the export carries what the user is looking at.
     */
    public function export(Request $request)
    {
        $format = $request->input('format') === 'print' ? 'print' : 'csv';
        $columns = $this->service->exportColumns($request->input('cols'));
        $rows = $this->service->exportRows($request);
        $search = trim((string) $request->input('q', ''));

        if ($format === 'print') {
            return view('roles-permissions.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $filename = 'roles-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII role name.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'heading'));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

        // One flat matrix drives both the grid and the export, so a printed
        // sheet can't disagree with what is on screen.
        $rows = $this->service->permissionMatrix($categories, $rolePermissions);

        return view('roles-permissions.assign-permission', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'categories' => $categories,
            'rows' => $rows,
            'enabledCount' => count(array_filter($rows, fn (array $r) => $r['enabled'])),
        ]);
    }

    /**
     * Download / Print the role's permission matrix — one action, two formats,
     * off the same rows and the same column definitions as the screen
     * (docs/new-design-index-page.md §1). ?q, ?category, ?status and ?cols are
     * stamped on by the grid so the export carries what the user is looking at.
     */
    public function exportPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $categories = SidebarCategory::with(['groups.menus'])->get();

        $rows = $this->service->filterPermissionMatrix(
            $this->service->permissionMatrix($categories, $rolePermissions),
            $request->input('q'),
            $request->input('category'),
            $request->input('status')
        );
        $columns = $this->service->permissionExportColumns($request->input('cols'));

        if ($request->input('format') === 'print') {
            $bits = [];
            if (filled($request->input('category'))) {
                $bits[] = '<strong>Category:</strong> '.e($request->input('category'));
            }
            if (in_array($request->input('status'), ['enabled', 'disabled'], true)) {
                $bits[] = '<strong>Status:</strong> '.ucfirst($request->input('status'));
            }
            if (filled(trim((string) $request->input('q')))) {
                $bits[] = '<strong>Search:</strong> '.e(trim((string) $request->input('q')));
            }

            return view('roles-permissions.assign_permission_print', [
                'role' => $role,
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $slug = \Illuminate\Support\Str::slug($role->name) ?: 'role';
        $filename = 'permissions-'.$slug.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII menu name.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'heading'));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    public function showDashboard($id)
    {
        $role = Role::findOrFail($id);
        $allCards = DashboardCard::orderBy('id', 'desc')->get();
        $assignedCardIds = $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
            ->pluck('dashboard_cards.id')
            ->toArray();
        $materialIcons = $this->materialIconNames();

        return view('roles-permissions.assign-dashboard', [
            'role' => $role,
            'allCards' => $allCards,
            'assignedCardIds' => $assignedCardIds,
            'materialIcons' => $materialIcons,
            'enabledCount' => $allCards->whereIn('id', $assignedCardIds)->count(),
        ]);
    }

    /**
     * Download / Print the role's dashboard-card assignment — one action, two
     * formats, off the same rows and column definitions as the screen
     * (docs/new-design-index-page.md §1). ?q, ?status and ?cols are stamped on
     * by the grid so the export carries what the user is looking at.
     */
    public function exportDashboardCards(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $assignedCardIds = $role->belongsToMany(DashboardCard::class, 'role_dashboard_cards', 'role_id', 'dashboard_card_id')
            ->pluck('dashboard_cards.id')
            ->toArray();

        $rows = $this->service->filterDashboardCardRows(
            $this->service->dashboardCardRows(DashboardCard::orderBy('id', 'desc')->get(), $assignedCardIds),
            $request->input('q'),
            $request->input('status')
        );
        $columns = $this->service->dashboardExportColumns($request->input('cols'));

        if ($request->input('format') === 'print') {
            $bits = [];
            if (in_array($request->input('status'), ['enabled', 'disabled'], true)) {
                $bits[] = '<strong>Status:</strong> '.ucfirst($request->input('status'));
            }
            if (filled(trim((string) $request->input('q')))) {
                $bits[] = '<strong>Search:</strong> '.e(trim((string) $request->input('q')));
            }

            return view('roles-permissions.assign_dashboard_print', [
                'role' => $role,
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $slug = \Illuminate\Support\Str::slug($role->name) ?: 'role';
        $filename = 'dashboard-cards-'.$slug.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII card label.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'heading'));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
