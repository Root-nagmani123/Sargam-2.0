<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Services\SidebarMenu;
use App\Models\SidebarMenu\Menu;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\SidebarMenu\{MenuGroup,SidebarCategory};
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * The two dropdowns the page renders server-side. Parent Menu is NOT here:
     * it depends on the chosen group and is fetched over AJAX
     * (sidebar.getMenus), so shipping every menu up front would be dead weight.
     */
    public function pageData(): array
    {
        $groups = MenuGroup::where('is_active', 1)
            ->with('category')
            ->orderBy('order', 'asc')
            ->get();

        return [
            'categories' => SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get(),
            // Qualified by category / group so repeated names are tellable apart.
            'groups' => self::disambiguateLabels($groups, fn ($g) => optional($g->category)->name),
            'parents' => self::disambiguateLabels(
                $this->parentMenuOptions(),
                fn ($m) => optional($m->group)->name
            ),
        ];
    }

    /**
     * The next free Display order WITHIN a group (and parent), not across the table.
     *
     * `order` is the sidebar's sequence inside one group, so a global
     * `max(order) + 1` handed a new menu ~147 while its siblings sat at 1-20 — it
     * sorted to the very end of the grid (page 25 of 25) and read as "my menu was
     * not saved". It also matches MenuRequest's uniqueness rule, which scopes
     * `order` to group_id + parent_id.
     */
    public static function nextOrderIn($groupId, $parentId): int
    {
        $query = Menu::query()->where('group_id', $groupId);

        $parentId
            ? $query->where('parent_id', $parentId)
            : $query->whereNull('parent_id');

        return (int) $query->max('order') + 1;
    }

    /**
     * Make repeated option labels tellable apart.
     *
     * Five group names are used by more than one group ("General" sits in both
     * Home and Communications, "Course Repository" three times) and "Exemption"
     * names two different parent menus. In a flat dropdown those render as
     * identical rows and picking one is a coin toss.
     *
     * Only names that ARE duplicated get decorated, so the common case reads
     * exactly as before. The id is appended as a last resort for rows that
     * collide even within the same context (Course Repository exists twice under
     * Setup), because a label a user cannot distinguish is worse than an ugly one.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @param  callable  $context  row -> the qualifier to append, e.g. its category name
     */
    public static function disambiguateLabels($rows, callable $context)
    {
        // Trimmed: one group is stored as "Course Repository " with a trailing
        // space, which HTML collapses — untrimmed it looks unique to countBy and
        // then renders identically to its twin.
        $counts = $rows->countBy(fn ($row) => trim((string) $row->name));

        $decorated = $rows->map(function ($row) use ($counts, $context) {
            $row->label = trim((string) $row->name);

            if (($counts[trim((string) $row->name)] ?? 0) > 1) {
                $qualifier = trim((string) $context($row));
                $row->label .= $qualifier !== '' ? ' ('.$qualifier.')' : '';
            }

            return $row;
        });

        // Second pass: anything still identical gets its id.
        $labelCounts = $decorated->countBy(fn ($row) => $row->label);

        return $decorated->map(function ($row) use ($labelCounts) {
            if (($labelCounts[$row->label] ?? 0) > 1) {
                $row->label .= ' #'.$row->id;
            }

            return $row;
        });
    }

    /**
     * Menus that actually have children, for the grid's Parent Menu filter.
     *
     * Read off parent_id rather than whereHas('children') because the children()
     * relation is scoped to is_active = 1 — a parent whose only sub-menu is
     * currently disabled would drop out of the list, and then its rows could not
     * be filtered for at all. Carries group_id / category_id so the select can be
     * narrowed by the Category and Group filters beside it.
     */
    public function parentMenuOptions()
    {
        $parentIds = Menu::query()
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        return Menu::query()
            ->whereIn('id', $parentIds)
            ->with('group')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'group_id', 'category_id']);
    }
    
    
    public function getAll()
    {
        return Menu::with('parent')->latest()->get();
    }

    public function getForDropdown()
    {
        return Menu::pluck('name', 'id');
    }

    public function store(array $data)
    {
        $permission = Str::slug($data['name'], '_');
        $data['permission_name'] = $permission;
        $data['order'] = $data['order'] ?? self::nextOrderIn($data['group_id'] ?? null, $data['parent_id'] ?? null);
        $menu = Menu::create($data);
        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web'
        ]);
        return $menu;
    }

    public function find($id)
    {
        return Menu::findOrFail($id);
    }

    public function status($id, $status)
    {
        $menu = $this->find($id);
        $menu->update(['is_active' => $status]);
        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        return $menu;
    }

    public function update($id, array $data)
    {
        $menu = $this->find($id);
        $oldPermission = $menu->permission_name;
        $data['order'] = $data['order'] ?? self::nextOrderIn(
            $data['group_id'] ?? $menu->group_id,
            $data['parent_id'] ?? $menu->parent_id
        );
        $menu->update($data);

        // The permission to keep in step is the one the row now carries — the form
        // has an editable Permission Name field, and 61 of the 243 live menus have
        // a permission_name that is NOT slug(name). Re-deriving it from the name
        // here (as this used to) renamed the wrong permission and, when the target
        // name already existed, threw "A `x` permission already exists for guard
        // `web`" — a hard 500 on Update for 36 of those rows.
        $newPermission = $menu->permission_name;

        if (filled($newPermission) && $oldPermission !== $newPermission) {
            $alreadyExists = Permission::where('name', $newPermission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $alreadyExists) {
                $permission = Permission::where('name', $oldPermission)
                    ->where('guard_name', 'web')
                    ->first();

                if ($permission) {
                    $permission->update(['name' => $newPermission]);
                } else {
                    Permission::create([
                        'name' => $newPermission,
                        'guard_name' => 'web',
                    ]);
                }
            }
            // else: a permission by that name is already on record. Reuse it —
            // renaming the old one onto it is what Spatie rejects, and every role
            // already granted the existing permission keeps working.
        }

        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        return $menu;
    }

    public function delete($id)
    {
        $menu = $this->find($id);
        $deleted = $menu->delete();
        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        return $deleted;
    }


    /**
     * @ Base Query
     *
     * Two things here are load-bearing:
     *
     * 1. Deliberately UNORDERED. Yajra appends its `order[]` clauses to whatever
     *    the query already carries, so an ORDER BY here would stay the primary
     *    sort and every header click would silently do nothing. The grid asks for
     *    `order` ascending by default; exportRows() adds the same default.
     *
     * 2. `select('menus.*')`. Sorting or searching by Group makes Yajra leftJoin
     *    `menu_groups`, and with a bare `select *` that table's `name` / `icon` /
     *    `order` columns overwrite this one's in every row — the Name column
     *    silently starts showing the GROUP name. Qualifying the select keeps the
     *    join to ordering only.
     */
    protected function baseQuery(Request $request)
    {
        $query = Menu::query()->select('menus.*')->with(['category', 'group.category', 'parent']);

        $categoryId = $request->input('category_id');
        if (filled($categoryId) && ctype_digit((string) $categoryId)) {
            // Mirror resolveMenuCategoryName(): a menu belongs to its own category
            // when it has one, otherwise to its group's. Filtering on category_id
            // alone would hide every row that only inherits it.
            $query->where(function ($q) use ($categoryId) {
                $q->where('category_id', (int) $categoryId)
                    ->orWhere(function ($q2) use ($categoryId) {
                        $q2->whereNull('category_id')
                            ->whereHas('group', fn ($g) => $g->where('category_id', (int) $categoryId));
                    });
            });
        }

        $groupId = $request->input('group_id');
        if (filled($groupId) && ctype_digit((string) $groupId)) {
            $query->where('group_id', (int) $groupId);
        }

        // Parent Menu. "0" is the sentinel the select uses for "top level only" —
        // a real parent_id is never 0, so it cannot collide with a menu id.
        $parentId = $request->input('parent_id');
        if (filled($parentId) && ctype_digit((string) $parentId)) {
            if ((int) $parentId === 0) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $parentId);
            }
        }

        return $query;
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request)->withCount('children'))
            ->addColumn('category_name', fn ($e) => e($this->resolveMenuCategoryName($e)))
            ->addColumn('group_name', fn ($e) =>
                optional($e->group)->name ?: '<span class="sbm-muted">&mdash;</span>'
            )
            ->addColumn('parent_menu', fn ($e) =>
                optional($e->parent)->name ?: '<span class="sbm-muted">&mdash;</span>'
            )
            // editColumn (not addColumn) on the real DB columns: Yajra then still
            // treats them as sortable/searchable SQL columns and only swaps the
            // rendered value.
            ->editColumn('route', fn ($e) =>
                filled($e->route)
                    ? '<span class="sbm-slug">'.e($e->route).'</span>'
                    : '<span class="sbm-muted">&mdash;</span>'
            )
            ->editColumn('attachment', fn ($e) => $this->attachmentLink($e))
            ->editColumn('permission_name', fn ($e) =>
                filled($e->permission_name)
                    ? '<span class="sbm-slug">'.e($e->permission_name).'</span>'
                    : '<span class="sbm-muted">&mdash;</span>'
            )
            ->editColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->editColumn('order', fn ($e) =>
                $e->order === null ? '<span class="sbm-muted">&mdash;</span>' : $this->orderBadge($e)
            )
            ->editColumn('icon', fn ($e) => $this->iconBadge($e))
            ->editColumn('target', fn ($e) => $this->targetBadge($e))
            ->addColumn('status', fn ($e) => $this->statusBadge($e))
            ->addColumn('action', fn ($e) => $this->actionButtons($e))
            ->rawColumns([
                'action', 'status', 'icon', 'order', 'target',
                'route', 'attachment', 'permission_name', 'group_name', 'parent_menu',
            ])
            ->addIndexColumn()
            ->make(true);
    }

    /**
     * Attachment cell — a link to the stored file, or an em dash.
     *
     * The sidebar does NOT render this yet: its links are hand-written in
     * resources/views/components/menu/*.blade.php, not built from menus.route.
     * The grid link is therefore the only way to reach an uploaded file today.
     */
    private function attachmentLink($data)
    {
        if (! filled($data->attachment)) {
            return '<span class="sbm-muted">&mdash;</span>';
        }

        return '<a href="'.e(asset('storage/'.$data->attachment)).'" target="_blank" rel="noopener"'
            .' class="sbm-attachment" title="'.e(basename((string) $data->attachment)).'">'
            .'<i class="bi bi-paperclip" aria-hidden="true"></i>'
            .'<span>'.e(basename((string) $data->attachment)).'</span>'
            .'</a>';
    }

    /**
     * Category label for list/export (menu.category_id, else group's category).
     */
    protected function resolveMenuCategoryName($menu): string
    {
        if ($menu->category?->name) {
            return $menu->category->name;
        }

        if ($menu->group?->category?->name) {
            return $menu->group->category->name;
        }

        return '-';
    }

    private function orderBadge($data)
    {
        return '<span class="sbm-order">'.e($data->order).'</span>';
    }

    private function targetBadge($data)
    {
        return (int) $data->target === 1
            ? '<span class="sbm-tab sbm-tab--new">New tab</span>'
            : '<span class="sbm-tab">Same tab</span>';
    }

    private function iconBadge($data)
    {
        if ($data->icon === null || trim((string) $data->icon) === '') {
            return '<span class="sbm-muted">&mdash;</span>';
        }

        $icon = trim($data->icon);
        if (str_contains($icon, 'bi-') || str_starts_with($icon, 'bi ')) {
            $iconClass = str_contains($icon, 'bi ') ? $icon : 'bi '.$icon;

            return '<span class="sbm-icon-chip" title="'.e($icon).'">'
                .'<i class="'.e($iconClass).'" aria-hidden="true"></i></span>';
        }

        return '<span class="sbm-icon-chip" title="'.e($icon).'">'
            .'<span class="material-icons material-symbols-rounded" aria-hidden="true">'.e($icon).'</span></span>';
    }

    /**
     * Status column — a soft badge, display only. The control that changes it is
     * the switch in the Action column (docs/new-design-index-page.md 3b).
     */
    private function statusBadge($data)
    {
        $isActive = (int) $data->is_active === 1;

        return '<span class="status-pill badge rounded-1 '
            .($isActive ? 'bg-success-subtle' : 'bg-danger-subtle').'">'
            .($isActive ? 'Active' : 'Inactive')
            .'</span>';
    }

    /**
     * Action column — Edit, status switch, Delete: three equal-width stacks of an
     * icon over a caption (3b).
     *
     * Delete is guarded twice: an ACTIVE menu still drives the live sidebar, and
     * a menu that has sub-menus would orphan them. Both render as a disabled span
     * saying why, rather than a red icon that leaves broken data behind.
     */
    private function actionButtons($data)
    {
        $isActive = (int) $data->is_active === 1;
        $childCount = (int) ($data->children_count ?? 0);
        $name = e($data->name);
        $switchId = 'sbmMenuStatus'.(int) $data->id;

        // The Edit modal needs the whole row: its Category -> Group -> Parent
        // dropdowns are populated from these before it opens.
        $payload = htmlspecialchars(json_encode([
            'id' => $data->id,
            'category_id' => $data->category_id,
            'group_id' => $data->group_id,
            'parent_id' => $data->parent_id,
            'name' => $data->name,
            'route' => $data->route,
            'attachment' => $data->attachment,
            'permission_name' => $data->permission_name,
            'icon' => $data->icon,
            'order' => $data->order,
            'is_active' => $data->is_active,
            'target' => $data->target,
        ]), ENT_QUOTES, 'UTF-8');

        $html = '<div class="sbm-act-group" role="group" aria-label="Actions for '.$name.'">';

        $html .= '
            <button type="button" class="sbm-act sbm-act--edit sbm-edit-btn"
                    data-item="'.$payload.'"
                    title="Edit menu" aria-label="Edit menu '.$name.'">
                <span class="sbm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Edit</span>
            </button>';

        // No .form-check/.form-switch wrapper — that combination yanks the input
        // -2.375rem left (custom.css:107-112) and breaks this layout (3b trap 1).
        //
        // Two classes, two jobs:
        //   plain-status-toggle        — the design-system TOGGLE skin
        //     (custom.css:41-95), the wrapper-less variant. Drop it and the input
        //     falls back to Bootstrap's square checkbox.
        //   sidebar-menu-status-toggle — this page's JS hook. Deliberately NOT
        //     `status-toggle`: that one is bound globally by custom.js:170 to the
        //     generic table/column endpoint, and this grid posts to its own route.
        $html .= '
            <label class="sbm-act sbm-act--toggle" for="'.$switchId.'">
                <span class="sbm-act__icon">
                    <input class="form-check-input plain-status-toggle sidebar-menu-status-toggle"
                           type="checkbox" role="switch"
                           id="'.$switchId.'"
                           data-table="menus" data-column="is_active"
                           data-id="'.(int) $data->id.'" data-name="'.$name.'"
                           '.($isActive ? 'checked' : '').'
                           aria-label="'.($isActive ? 'Deactivate' : 'Activate').' menu '.$name.'">
                </span>
                <span class="sbm-act__label">'.($isActive ? 'Deactivate' : 'Activate').'</span>
            </label>';

        if ($isActive || $childCount > 0) {
            $reason = $isActive
                ? 'Deactivate this menu before deleting it'
                : 'This menu has '.$childCount.' sub-menu'.($childCount === 1 ? '' : 's').' - remove them first';

            $html .= '
            <span class="sbm-act sbm-act--del is-disabled" title="'.e($reason).'" aria-disabled="true">
                <span class="sbm-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Delete</span>
            </span>';
        } else {
            $html .= '
            <form action="'.route('sidebar.menus.destroy', $data->id).'" method="POST" class="sbm-delete-form">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="sbm-act sbm-act--del" data-name="'.$name.'"
                        aria-label="Delete menu '.$name.'">
                    <span class="sbm-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                    <span class="sbm-act__label">Delete</span>
                </button>
            </form>';
        }

        return $html.'</div>';
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
            ['key' => 'sno', 'heading' => 'Sr No.', 'class' => 'sbm-print-sno',
                'value' => fn ($row, $index) => $index + 1],
            ['key' => 'category', 'heading' => 'Category', 'class' => 'sbm-print-category',
                'value' => fn ($row) => $this->resolveMenuCategoryName($row)],
            ['key' => 'group', 'heading' => 'Group', 'class' => 'sbm-print-group',
                'value' => fn ($row) => optional($row->group)->name ?: '-'],
            ['key' => 'parent', 'heading' => 'Parent Menu', 'class' => 'sbm-print-parent',
                'value' => fn ($row) => optional($row->parent)->name ?: '-'],
            ['key' => 'name', 'heading' => 'Name', 'class' => 'sbm-print-name',
                'value' => fn ($row) => (string) $row->name],
            ['key' => 'route', 'heading' => 'Url', 'class' => 'sbm-print-route',
                'value' => fn ($row) => filled($row->route) ? (string) $row->route : '-'],
            ['key' => 'attachment', 'heading' => 'Attachment', 'class' => 'sbm-print-attachment',
                'value' => fn ($row) => filled($row->attachment) ? basename((string) $row->attachment) : '-'],
            ['key' => 'permission_name', 'heading' => 'Permission', 'class' => 'sbm-print-permission',
                'value' => fn ($row) => filled($row->permission_name) ? (string) $row->permission_name : '-'],
            ['key' => 'icon', 'heading' => 'Icon', 'class' => 'sbm-print-icon',
                'value' => fn ($row) => filled($row->icon) ? (string) $row->icon : '-'],
            ['key' => 'order', 'heading' => 'Order', 'class' => 'sbm-print-order',
                'value' => fn ($row) => $row->order === null ? '-' : (string) $row->order],
            ['key' => 'target', 'heading' => 'Tab', 'class' => 'sbm-print-target',
                'value' => fn ($row) => (int) $row->target === 1 ? 'New tab' : 'Same tab'],
            ['key' => 'created_at', 'heading' => 'Created', 'class' => 'sbm-print-created',
                'value' => fn ($row) => $row->created_at ? $row->created_at->format('d-m-Y') : '-'],
            ['key' => 'status', 'heading' => 'Status', 'class' => 'sbm-print-status',
                'value' => fn ($row) => (int) $row->is_active === 1 ? 'Active' : 'Inactive'],
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
     * The rows the export writes — the same filters and search term the grid is
     * showing, so a download matches what is on screen.
     */
    public function exportRows(Request $request)
    {
        $query = $this->baseQuery($request)->orderBy('order', 'asc');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('route', 'like', $like)
                    ->orWhere('attachment', 'like', $like)
                    ->orWhere('permission_name', 'like', $like)
                    ->orWhere('icon', 'like', $like)
                    ->orWhereHas('group', fn ($g) => $g->where('name', 'like', $like));
            });
        }

        return $query->get();
    }

    /**
     * Per-request memoization. The global view()->composer('*') calls getMenus()
     * for every view/Blade component rendered on a page; without this the full
     * RBAC menu tree (incl. getAllPermissions()) is rebuilt dozens of times per
     * request. Keyed by user id; result is identical, just computed once.
     */
    private array $menusCache = [];

    public function getMenus()
    {
        $key = (string) (auth()->id() ?? 'guest');

        if (array_key_exists($key, $this->menusCache)) {
            return $this->menusCache[$key];
        }

        return $this->menusCache[$key] = $this->buildMenus();
    }

    /** Cache key for the sidebar category → group → menu → children structure. */
    public const STRUCTURE_CACHE_KEY = 'fc_sidebar_structure';

    /**
     * The sidebar structure (categories → groups → menus → children).
     *
     * This is global data — identical for every user — but it cost 5 queries on
     * EVERY page in the application, including login. Per-user permission
     * filtering still happens live in buildMenus(), so no permission data is
     * cached here and a revoked permission takes effect immediately.
     *
     * The payload is stored serialized and unserialized on every read, so each
     * caller gets its own object graph. buildMenus() mutates what it receives
     * (it reassigns ->groups and unsets ->menus); handing out a shared instance
     * — as the array cache driver would — would corrupt the next caller's menu.
     */
    private function categoryStructure()
    {
        $build = static fn () => SidebarCategory::select('id', 'icon', 'name', 'slug')
            ->with(['groups' => function ($q) {
                $q->select('id', 'category_id', 'icon', 'name')
                    ->orderBy('order', 'asc')
                    ->with(['menus' => function ($mq) {
                        $mq->select('id', 'group_id', 'parent_id', 'icon', 'name', 'route', 'permission_name', 'order')
                            ->orderBy('order', 'asc')
                            ->with(['children' => function ($cq) {
                                $cq->select('id', 'parent_id', 'icon', 'name', 'route', 'permission_name', 'order')
                                    ->orderBy('order', 'asc');
                            }]);
                    }]);
            }])
            ->orderBy('order', 'asc')
            ->where('is_active', 1)
            ->get();

        $ttl = (int) config('fc.menu_cache_ttl', 600);
        if ($ttl <= 0) {
            return $build();
        }

        try {
            $payload = Cache::remember(self::STRUCTURE_CACHE_KEY, $ttl, static fn () => serialize($build()));
            $restored = is_string($payload) ? @unserialize($payload) : null;

            if ($restored instanceof \Illuminate\Support\Collection) {
                return $restored;
            }
        } catch (\Throwable $e) {
            // Fall through to a live query — never break the sidebar over a cache issue.
        }

        return $build();
    }

    /** Drop the cached sidebar structure (called whenever a menu row changes). */
    public static function clearStructureCache(): void
    {
        try {
            Cache::forget(self::STRUCTURE_CACHE_KEY);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function buildMenus()
    {
        $user = auth()->user();
        $isAdmin = isSidebarPrivilegedUser();

        # if Admin then load all menus else load only user permissions
        $permissions = $isAdmin ? [] : $user->getAllPermissions()->pluck('name')->toArray();


        $categories = $this->categoryStructure();

        // No role assigned → only Home (no Setup / Academic / Time Table tabs).
        if (! $isAdmin && ! userHasAssignedRoles()) {
            return $this->mapCategoriesForNav(
                $categories->filter(fn ($category) => $category->slug === 'home')->values()
            );
        }

        if ($isAdmin) {
            return $this->mapCategoriesForNav($categories);
        }

        return $categories->map(function ($category) use ($permissions) {
            $category->groups = $category->groups->map(function ($group) use ($permissions) {
                $group->menus = $group->menus->map(function ($menu) use ($permissions) {
                    $menu->children = $menu->children->filter(function ($child) use ($permissions) {
                        return $this->menuVisibleToUser($child->permission_name, $permissions);
                    })->values();
                    $hasMenuPermission = $this->menuVisibleToUser($menu->permission_name, $permissions);
                    if ($menu->children->count() > 0 || $hasMenuPermission) {
                        $menu->url = $menu->route ? url($menu->route) : url($menu->slug ?? '#');
                        return $menu;
                    }
                    return null;
                })->filter()->values();
                return $group;
            })->filter(function ($group) {
                return $group->menus->count() > 0;
            })->values();
            return $category;
        })->filter(function ($category) {
            return $category->groups->count() > 0;
        })->values();
    }

    /**
     * Non-admin users must have an explicit permission; empty permission_name is not public.
     */
    protected function menuVisibleToUser(?string $permissionName, array $permissions): bool
    {
        if ($permissionName === null || $permissionName === '') {
            return false;
        }

        return in_array($permissionName, $permissions, true);
    }

    /**
     * Header tabs: categories with group icons (menus stripped).
     */
    protected function mapCategoriesForNav($categories)
    {
        return $categories->map(function ($category) {
            $category->groups = $category->groups->map(function ($group) {
                unset($group->menus);
                $group->url = url($group->slug ?? '#');
                return $group;
            });
            $category->url = url($category->slug ?? '#');
            return $category;
        });
    }

    // public function getMenus()
    // {
    //     $user = auth()->user();

    //     // Get all permissions once
    //     $permissions = $user->getAllPermissions()->pluck('name')->toArray();

    //     return SidebarCategory::select('id', 'icon', 'name', 'slug')
    //         ->with(['groups' => function ($q) {
    //             $q->select('id', 'category_id', 'icon', 'name')
    //                 ->orderBy('order', 'asc')
    //                 ->with(['menus' => function ($mq) {
    //                     $mq->select('id', 'group_id', 'icon', 'name', 'permission_name', 'order')
    //                         ->orderBy('order', 'asc')
    //                         ->with(['children' => function ($cq) {
    //                             $cq->select('id', 'parent_id', 'icon', 'name', 'route', 'permission_name', 'order')
    //                                 ->orderBy('order', 'asc');
    //                         }]);
    //                 }]);
    //         }])
    //         ->orderBy('order', 'asc')
    //         ->get()
    //         ->map(function ($category) use ($permissions) {

    //             $category->groups = $category->groups->map(function ($group) use ($permissions) {

    //                 $group->menus = $group->menus->map(function ($menu) use ($permissions) {

    //                     // Filter children
    //                     $menu->children = $menu->children->filter(function ($child) use ($permissions) {
    //                         return !$child->permission_name || in_array($child->permission_name, $permissions);
    //                     })->values();

    //                     $hasMenuPermission = !$menu->permission_name || in_array($menu->permission_name, $permissions);

    //                     if ($menu->children->count() > 0 || $hasMenuPermission) {
    //                         $menu->url = url($menu->slug); // ⚠️ make sure slug exists
    //                         return $menu;
    //                     }

    //                     return null;

    //                 })->filter()->values();

    //                 $group->url = url($group->slug);

    //                 return $group;

    //             })->filter(function ($group) {
    //                 return $group->menus->count() > 0;
    //             })->values();

    //             $category->url = url($category->slug);

    //             return $category;

    //         })->filter(function ($category) {
    //             return $category->groups->count() > 0;
    //         })->values();
    // }

    public function clearCache($userId = null)
    {
        if ($userId) {
            Cache::forget('sidebar_menu_user_'.$userId);
        } else {
            Cache::flush();
        }
    }
}