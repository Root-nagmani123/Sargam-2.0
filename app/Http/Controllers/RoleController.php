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
            $pdf = Pdf::loadView('exports.branded_grid_pdf', [
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
                    // Never true: isPhpEnabled makes the renderer a PHP
                    // execution context for the whole view, so any raw block
                    // that later appears in an export blade would execute.
                    // Page numbers are stamped on the canvas after render
                    // instead — see PdfPageNumbers.
                    'isPhpEnabled' => false,
                ]);

            return PdfPageNumbers::stamp($pdf)->download($filename.'.pdf');
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
                    fn (array $col) => sanitize_export_cell($col['value']($row, $index)),
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

        // `dashboard_cards.key` carries a unique index, so the exists() probe below
        // is a convenience for picking a readable suffix, not the thing that makes
        // the key unique. Two requests with the same label can both pass the probe
        // and the loser's INSERT then raises SQLSTATE 23000 - which reached the
        // user as a 500 carrying a raw SQL error. Recompute and retry instead: the
        // database stays the authority and the caller gets a card.
        $card = null;

        for ($attempt = 0; $attempt < 5 && $card === null; $attempt++) {
            $key = $baseKey;
            $i = 1;
            while (DashboardCard::where('key', $key)->exists()) {
                $key = $baseKey . '_' . $i++;
            }

            try {
                $card = DashboardCard::create(array_merge(
                    $request->only('label', 'icon', 'color_class', 'sort_order'),
                    ['key' => $key]
                ));
            } catch (\Illuminate\Database\QueryException $e) {
                // Only a uniqueness collision is worth retrying; anything else is a
                // real failure and must not be swallowed.
                if ((string) $e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        if ($card === null) {
            return response()->json([
                'success' => false,
                'message' => 'Could not allocate a unique key for this card. Please try again.',
            ], 409);
        }

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
        // No route reaches this branch today (PR #309 review F-069): every caller has
        // already passed EnsureRoleAdmin. It is kept on purpose (decided 2026-09-25) and
        // is executed by tests/Feature/PermissionAmplificationGuardDirectTest, which
        // calls this method without the route gate, so it cannot rot unseen.
        //
        // This is the same shape as the assignRoleSave() guard (PR #311 review F-023):
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
