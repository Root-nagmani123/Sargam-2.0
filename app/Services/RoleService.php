<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Services;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * The Roles grid declares its own columns in the view (the DataTable is
     * initialised there), so there is nothing for the page to receive.
     * permissionPageData() below still feeds the Permissions listing, which is
     * on the older x-data-table.table component.
     */
    public function pageData(): array
    {
        return [];
    }

    public function permissionPageData(): array
    {
        return [
            'columns' => $this->permissionColumns(),
        ];
    }
    
    
    public function getAll()
    {
        return Role::latest()->get();
    }

    public function getForDropdown()
    {
        return Role::pluck('name', 'id');
    }

    public function store(array $data)
    {
        return Role::create($data);
    }

    public function update($id, array $data)
    {
        $menu = $this->find($id);
        return $menu->update($data);
    }

    public function delete($id)
    {
        $menu = $this->find($id);
        return $menu->delete();
    }

    public function permissionColumns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Permission', 'data' => 'name'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    /**
     * @ Base Query
     *
     * Deliberately UNORDERED. Yajra appends its `order[]` clauses to whatever
     * the query already carries, so an ORDER BY here would stay the primary
     * sort and every header click would silently do nothing. The grid asks for
     * Role ascending by default; exportRows() adds the same default.
     */
    protected function baseQuery(Request $request)
    {
        return Role::query()->withCount('permissions');
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            // editColumn (not addColumn) on the real DB columns: Yajra then still
            // treats them as sortable/searchable SQL columns and only swaps the
            // rendered value.
            ->editColumn('name', fn ($e) =>
                '<span class="rp-role-name">'.e($e->name).'</span>'
            )
            ->editColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->addColumn('permissions_count', fn ($e) => $this->permissionCountBadge($e))
            ->addColumn('action', fn ($e) => $this->actionButtons($e))
            ->rawColumns(['action', 'name', 'permissions_count'])
            ->addIndexColumn()
            ->make(true);
    }

    private function permissionCountBadge($data)
    {
        $count = (int) ($data->permissions_count ?? 0);

        return '<span class="rp-count'.($count === 0 ? ' rp-count--zero' : '').'">'
            .number_format($count).'</span>';
    }

    /**
     * Action column — Assign Permission · Assign Dashboard · Edit, as
     * equal-width stacks of an icon over a caption (§3b).
     *
     * No Delete: RoleController::destroy() is an empty method, so a delete
     * control here would post and silently do nothing.
     */
    private function actionButtons($data)
    {
        $name = e($data->name);
        $payload = htmlspecialchars(json_encode([
            'id' => $data->id,
            'name' => $data->name,
        ]), ENT_QUOTES, 'UTF-8');

        return '
        <div class="rp-act-group" role="group" aria-label="Actions for '.$name.'">
            <a href="'.route('roles.show', $data->id).'"
               class="rp-act rp-act--permission"
               title="Assign permissions to '.$name.'"
               aria-label="Assign permissions to '.$name.'">
                <span class="rp-act__icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                <span class="rp-act__label">Permissions</span>
            </a>

            <a href="'.route('roles.dashboard', $data->id).'"
               class="rp-act rp-act--dashboard"
               title="Assign dashboard cards to '.$name.'"
               aria-label="Assign dashboard cards to '.$name.'">
                <span class="rp-act__icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
                <span class="rp-act__label">Dashboard</span>
            </a>

            <button type="button" class="rp-act rp-act--edit rp-edit-btn"
                    data-item="'.$payload.'"
                    title="Edit '.$name.'" aria-label="Edit role '.$name.'">
                <span class="rp-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="rp-act__label">Edit</span>
            </button>
        </div>';
    }

    /* ------------------------------------------------------------------------
       Assign Permission — the role's permission matrix
       ------------------------------------------------------------------------ */

    /**
     * Flatten categories → groups → menus → children into one row per assignable
     * permission.
     *
     * ONE source of truth: the screen renders this and the export writes it, so
     * a printed matrix can't disagree with what the user just ticked. A menu with
     * children contributes its children (the parent is only a container); a menu
     * without them contributes itself.
     *
     * @param  iterable  $categories       SidebarCategory::with('groups.menus')
     * @param  array     $rolePermissions  permission names the role already holds
     * @return array<int, array<string, mixed>>
     */
    public function permissionMatrix($categories, array $rolePermissions): array
    {
        $enabled = array_flip($rolePermissions);
        $rows = [];

        foreach ($categories as $category) {
            foreach ($category->groups as $group) {
                foreach ($group->menus as $menu) {
                    $targets = $menu->children->count() > 0
                        ? $menu->children->all()
                        : [$menu];

                    foreach ($targets as $target) {
                        $isChild = $target !== $menu;

                        $rows[] = [
                            'id' => $target->id,
                            'category' => (string) $category->name,
                            'group' => (string) $group->name,
                            'menu' => (string) $menu->name,
                            'submenu' => $isChild ? (string) $target->name : '-',
                            'permission' => (string) $target->permission_name,
                            'enabled' => isset($enabled[$target->permission_name]),
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * The grid's tier filters, in the order they narrow each other.
     *
     * Keyed row-field => query-string name, so the controller, the export and the
     * cascade in assign-permission.blade.php all agree on the names.
     */
    public const PERMISSION_TIERS = [
        'category' => 'category',
        'group' => 'group',
        'menu' => 'menu',
        'submenu' => 'submenu',
    ];

    /**
     * Filter the matrix the way the grid does, for the exports: free text, the
     * four tier selects (category / group / menu / sub menu) and enabled/disabled.
     *
     * $tiers is a row-field => value map; a blank value means "no filter on this
     * tier", and an unknown key is ignored rather than trusted, so a hand-edited
     * query string can't filter on a field the grid does not offer.
     *
     * @param  array<string, string|null>  $tiers
     */
    public function filterPermissionMatrix(array $rows, ?string $search, array $tiers, ?string $status): array
    {
        $search = trim((string) $search);
        $status = in_array($status, ['enabled', 'disabled'], true) ? $status : '';

        $tiers = array_filter(
            array_map(fn ($value) => trim((string) $value), array_intersect_key($tiers, self::PERMISSION_TIERS)),
            fn (string $value) => $value !== ''
        );

        return array_values(array_filter($rows, function (array $row) use ($search, $tiers, $status) {
            foreach ($tiers as $field => $value) {
                if (($row[$field] ?? null) !== $value) {
                    return false;
                }
            }

            if ($status === 'enabled' && ! $row['enabled']) {
                return false;
            }
            if ($status === 'disabled' && $row['enabled']) {
                return false;
            }

            if ($search !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $row['category'], $row['group'], $row['menu'], $row['submenu'], $row['permission'],
                ]));
                if (! str_contains($haystack, mb_strtolower($search))) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Columns for the Assign Permission export. Same shape as the grid's own
     * definitions: `key` is what ?cols= sends back.
     */
    public function permissionExportColumnDefs(): array
    {
        return [
            ['key' => 'sno', 'heading' => 'Sr No.', 'class' => 'rp-print-sno',
                'value' => fn (array $row, $index) => $index + 1],
            ['key' => 'category', 'heading' => 'Category', 'class' => 'rp-print-category',
                'value' => fn (array $row) => $row['category']],
            ['key' => 'group', 'heading' => 'Group', 'class' => 'rp-print-group',
                'value' => fn (array $row) => $row['group']],
            ['key' => 'menu', 'heading' => 'Menu', 'class' => 'rp-print-menu',
                'value' => fn (array $row) => $row['menu']],
            ['key' => 'submenu', 'heading' => 'Sub Menu', 'class' => 'rp-print-submenu',
                'value' => fn (array $row) => $row['submenu']],
            ['key' => 'permission', 'heading' => 'Permission', 'class' => 'rp-print-permission',
                'value' => fn (array $row) => $row['permission'] !== '' ? $row['permission'] : '-'],
            ['key' => 'status', 'heading' => 'Status', 'class' => 'rp-print-status',
                'value' => fn (array $row) => $row['enabled'] ? 'Enabled' : 'Disabled'],
        ];
    }

    public function permissionExportColumns(?string $cols): array
    {
        $defs = $this->permissionExportColumnDefs();

        if (! filled($cols)) {
            return $defs;
        }

        $wanted = array_filter(array_map('trim', explode(',', $cols)));
        if (empty($wanted)) {
            return $defs;
        }

        return array_values(array_filter(
            $defs,
            fn (array $def) => in_array($def['key'], $wanted, true)
        ));
    }

    /* ------------------------------------------------------------------------
       Assign Dashboard — the role's dashboard-card assignment
       ------------------------------------------------------------------------ */

    /** Human labels for the stored `color_class` values. */
    public const CARD_COLOURS = [
        'stat-icon-blue' => 'Blue',
        'stat-icon-green' => 'Green',
        'stat-icon-amber' => 'Amber',
        'stat-icon-rose' => 'Rose',
        'stat-icon-navy' => 'Navy',
    ];

    /**
     * One row per dashboard card, stamped with whether THIS role has it.
     *
     * ONE source of truth: the screen renders this and the export writes it, so
     * a printed sheet can't disagree with what the user just toggled.
     *
     * @param  iterable  $cards
     * @param  array     $assignedCardIds
     * @return array<int, array<string, mixed>>
     */
    public function dashboardCardRows($cards, array $assignedCardIds): array
    {
        $assigned = array_flip($assignedCardIds);
        $rows = [];

        foreach ($cards as $card) {
            $rows[] = [
                'id' => $card->id,
                'label' => (string) $card->label,
                'icon' => (string) $card->icon,
                'color_class' => (string) $card->color_class,
                'color_label' => self::CARD_COLOURS[$card->color_class] ?? (string) $card->color_class,
                'sort_order' => $card->sort_order,
                'created_at' => $card->created_at ? $card->created_at->format('d-m-Y') : '-',
                'enabled' => isset($assigned[$card->id]),
            ];
        }

        return $rows;
    }

    /** Filter the card list the way the grid does, for the exports. */
    public function filterDashboardCardRows(array $rows, ?string $search, ?string $status): array
    {
        $search = trim((string) $search);
        $status = in_array($status, ['enabled', 'disabled'], true) ? $status : '';

        return array_values(array_filter($rows, function (array $row) use ($search, $status) {
            if ($status === 'enabled' && ! $row['enabled']) {
                return false;
            }
            if ($status === 'disabled' && $row['enabled']) {
                return false;
            }

            if ($search !== '') {
                $haystack = mb_strtolower($row['label'].' '.$row['icon'].' '.$row['color_label']);
                if (! str_contains($haystack, mb_strtolower($search))) {
                    return false;
                }
            }

            return true;
        }));
    }

    public function dashboardExportColumnDefs(): array
    {
        return [
            ['key' => 'sno', 'heading' => 'S No.', 'class' => 'rp-print-sno',
                'value' => fn (array $row, $index) => $index + 1],
            ['key' => 'label', 'heading' => 'Name', 'class' => 'rp-print-name',
                'value' => fn (array $row) => $row['label']],
            ['key' => 'icon', 'heading' => 'Icon', 'class' => 'rp-print-icon',
                'value' => fn (array $row) => $row['icon'] !== '' ? $row['icon'] : '-'],
            ['key' => 'color', 'heading' => 'Colour', 'class' => 'rp-print-colour',
                'value' => fn (array $row) => $row['color_label']],
            ['key' => 'sort_order', 'heading' => 'Order', 'class' => 'rp-print-order',
                'value' => fn (array $row) => $row['sort_order'] === null ? '-' : (string) $row['sort_order']],
            ['key' => 'created_at', 'heading' => 'Created', 'class' => 'rp-print-created',
                'value' => fn (array $row) => $row['created_at']],
            ['key' => 'status', 'heading' => 'Status', 'class' => 'rp-print-status',
                'value' => fn (array $row) => $row['enabled'] ? 'Enabled' : 'Disabled'],
        ];
    }

    public function dashboardExportColumns(?string $cols): array
    {
        $defs = $this->dashboardExportColumnDefs();

        if (! filled($cols)) {
            return $defs;
        }

        $wanted = array_filter(array_map('trim', explode(',', $cols)));
        if (empty($wanted)) {
            return $defs;
        }

        return array_values(array_filter(
            $defs,
            fn (array $def) => in_array($def['key'], $wanted, true)
        ));
    }

    /* ------------------------------------------------------------------------
       Exports
       One definition feeds BOTH the CSV and the print view, so the two cannot
       drift apart (docs/new-design-index-page.md 1). `key` is what the grid's
       Columns modal sends back as ?cols=; keep the first column identical to
       the grid's.
       ------------------------------------------------------------------------ */

    public function exportColumnDefs(): array
    {
        return [
            ['key' => 'sno', 'heading' => 'Sr No.', 'class' => 'rp-print-sno',
                'value' => fn ($row, $index) => $index + 1],
            ['key' => 'name', 'heading' => 'Role', 'class' => 'rp-print-name',
                'value' => fn ($row) => (string) $row->name],
            ['key' => 'permissions_count', 'heading' => 'Permissions', 'class' => 'rp-print-count',
                'value' => fn ($row) => (string) (int) ($row->permissions_count ?? 0)],
            ['key' => 'created_at', 'heading' => 'Created', 'class' => 'rp-print-created',
                'value' => fn ($row) => $row->created_at ? $row->created_at->format('d-m-Y') : '-'],
        ];
    }

    /**
     * Columns still ticked in the grid's Columns modal, in table order. No
     * ?cols= at all means "every column"; an unknown key is ignored.
     */
    public function exportColumns(?string $cols): array
    {
        $defs = $this->exportColumnDefs();

        if (! filled($cols)) {
            return $defs;
        }

        $wanted = array_filter(array_map('trim', explode(',', $cols)));
        if (empty($wanted)) {
            return $defs;
        }

        return array_values(array_filter(
            $defs,
            fn (array $def) => in_array($def['key'], $wanted, true)
        ));
    }

    /**
     * The rows the export writes — the same search term the grid is showing, so
     * a download matches what is on screen.
     */
    public function exportRows(Request $request)
    {
        $query = $this->baseQuery($request)->orderBy('name', 'asc');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->get();
    }
}
