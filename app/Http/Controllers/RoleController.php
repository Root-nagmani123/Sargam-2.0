<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SidebarMenu\SidebarCategory;
use App\Models\DashboardCard;
use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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
     * Download / Print — one action, four formats (csv, excel, pdf, print), off
     * the same query and the same column definitions, so a spreadsheet, a PDF and
     * a printout can't drift apart (docs/new-design-index-page.md §1). ?q and
     * ?cols are stamped on by the grid so every format carries what the user is
     * looking at.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

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

        return $this->gridExport(
            $format,
            $rows,
            $columns,
            'Roles & Permissions',
            'Roles',
            $search !== '' ? 'Search: '.$search : null,
            // Mirrors export_print.blade.php's column widths, so the PDF and the
            // printout lay out the same.
            ['sno' => '10%', 'name' => '48%', 'permissions_count' => '20%', 'created_at' => '22%']
        );
    }

    /**
     * CSV / .xlsx / PDF off one resolved row set and one resolved column list.
     *
     * `print` is deliberately NOT routed through here — each report keeps its own
     * print blade, because a browser printout is styled with @media print rules
     * and print-color-adjust that DomPDF does not understand.
     *
     * @param  iterable  $rows
     * @param  array<int, array{key?:string, heading:string, class:string, value:callable}>  $columns
     * @param  string|null  $filterLine  PLAIN text ("Search: foo  |  Status: Enabled"), null when unfiltered
     * @param  array<string, string>  $widths  column key => CSS width, for the fixed-layout PDF table
     */
    private function gridExport(
        string $format,
        iterable $rows,
        array $columns,
        string $reportTitle,
        string $baseFilename,
        ?string $filterLine = null,
        array $widths = []
    ) {
        $exportDate = now()->format('d-m-Y h:i A');
        $filename = $baseFilename.'_'.now()->format('YmdHis');

        if ($format === 'excel') {
            return Excel::download(
                new BrandedGridExport($rows, $columns, $reportTitle, $exportDate, $filterLine),
                $filename.'.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('exports.branded_grid_pdf', [
                'reportTitle' => $reportTitle,
                'columns' => $columns,
                'rows' => $rows,
                'filterLine' => $filterLine,
                'exportDate' => $exportDate,
                'widths' => $widths,
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script in the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download($filename.'.pdf');
        }

        // The same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the report and its applied filters too instead of arriving as bare columns.
        $band = ExportCsvHeader::rows(
            $reportTitle,
            $filterLine,
            $exportDate,
            is_countable($rows) ? count($rows) : null
        );

        return response()->streamDownload(function () use ($rows, $columns, $band) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII value.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($band as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, array_column($columns, 'heading'));

            $index = 0;
            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
                $index++;
            }

            fclose($handle);
        }, $filename.'.csv', [
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
     * Download / Print the role's permission matrix — one action, four formats,
     * off the same rows and the same column definitions as the screen
     * (docs/new-design-index-page.md §1). ?q, the four tier selects
     * (?category / ?group / ?menu / ?submenu), ?status and ?cols are stamped on by
     * the grid so the export carries what the user is looking at.
     */
    public function exportPermissions(Request $request, $id)
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $categories = SidebarCategory::with(['groups.menus'])->get();

        // Row-field => value, straight off the query string. filterPermissionMatrix()
        // intersects this against its own PERMISSION_TIERS list, so an extra key here
        // is ignored rather than trusted.
        $tiers = [];
        foreach (RoleService::PERMISSION_TIERS as $field => $param) {
            $tiers[$field] = (string) $request->input($param, '');
        }

        $rows = $this->service->filterPermissionMatrix(
            $this->service->permissionMatrix($categories, $rolePermissions),
            $request->input('q'),
            $tiers,
            $request->input('status')
        );
        $columns = $this->service->permissionExportColumns($request->input('cols'));

        // Two renderings of the same filters: the print sheet gets the bold HTML
        // one, the CSV / .xlsx / PDF band gets plain text. Driven by the same
        // PERMISSION_TIERS list as the filtering, so a tier can never be applied
        // and then go unmentioned on the sheet.
        $tierLabels = [
            'category' => 'Category',
            'group' => 'Group',
            'menu' => 'Menu',
            'submenu' => 'Sub Menu',
        ];

        $bits = [];
        $plain = [];
        foreach ($tiers as $field => $value) {
            if ($value === '') {
                continue;
            }
            // The matrix writes '-' for "this menu has no sub menu"; spell that out
            // rather than printing a bare dash next to the label.
            $shown = ($field === 'submenu' && $value === '-') ? 'None' : $value;
            $bits[] = '<strong>'.$tierLabels[$field].':</strong> '.e($shown);
            $plain[] = $tierLabels[$field].': '.$shown;
        }
        if (in_array($request->input('status'), ['enabled', 'disabled'], true)) {
            $bits[] = '<strong>Status:</strong> '.ucfirst($request->input('status'));
            $plain[] = 'Status: '.ucfirst($request->input('status'));
        }
        if (filled(trim((string) $request->input('q')))) {
            $bits[] = '<strong>Search:</strong> '.e(trim((string) $request->input('q')));
            $plain[] = 'Search: '.trim((string) $request->input('q'));
        }

        if ($format === 'print') {
            return view('roles-permissions.assign_permission_print', [
                'role' => $role,
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $slug = \Illuminate\Support\Str::slug($role->name) ?: 'role';

        return $this->gridExport(
            $format,
            $rows,
            $columns,
            'Permissions — '.$role->name,
            'Permissions_'.$slug,
            empty($plain) ? null : implode('  |  ', $plain),
            // Mirrors assign_permission_print.blade.php's column widths, but with a
            // point taken off Permission for Sr No. — the print sheet's 5% wraps the
            // heading onto two lines in the PDF's narrower font.
            [
                'sno' => '6%', 'category' => '12%', 'group' => '16%', 'menu' => '17%',
                'submenu' => '17%', 'permission' => '22%', 'status' => '10%',
            ]
        );
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
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

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

        // Two renderings of the same filters: the print sheet gets the bold HTML
        // one, the CSV / .xlsx / PDF band gets plain text.
        $bits = [];
        $plain = [];
        if (in_array($request->input('status'), ['enabled', 'disabled'], true)) {
            $bits[] = '<strong>Status:</strong> '.ucfirst($request->input('status'));
            $plain[] = 'Status: '.ucfirst($request->input('status'));
        }
        if (filled(trim((string) $request->input('q')))) {
            $bits[] = '<strong>Search:</strong> '.e(trim((string) $request->input('q')));
            $plain[] = 'Search: '.trim((string) $request->input('q'));
        }

        if ($format === 'print') {
            return view('roles-permissions.assign_dashboard_print', [
                'role' => $role,
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $slug = \Illuminate\Support\Str::slug($role->name) ?: 'role';

        return $this->gridExport(
            $format,
            $rows,
            $columns,
            'Dashboard Cards — '.$role->name,
            'DashboardCards_'.$slug,
            empty($plain) ? null : implode('  |  ', $plain),
            // Mirrors assign_dashboard_print.blade.php's column widths.
            [
                'sno' => '7%', 'label' => '30%', 'icon' => '20%', 'color' => '12%',
                'sort_order' => '8%', 'created_at' => '12%', 'status' => '11%',
            ]
        );
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
