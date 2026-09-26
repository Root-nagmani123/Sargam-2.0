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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class MenuService
{
    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'categories' => SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get(),
            'groups' => MenuGroup::where('is_active', 1)->orderBy('order', 'asc')->get(),
            'parent_menus' => Menu::whereNull('parent_id')->where('is_active', 1)->orderBy('order', 'asc')->get(),
        ];
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

        // MenuRequest used to check uniqueness of the POSTED permission name, which
        // is discarded. Check the name that is actually stored, with the same scope.
        $clash = Menu::where('permission_name', $permission)
            ->where('group_id', $data['group_id'] ?? null)
            ->where(function ($q) use ($data) {
                empty($data['parent_id'])
                    ? $q->whereNull('parent_id')
                    : $q->where('parent_id', $data['parent_id']);
            })
            ->exists();
        if ($clash) {
            throw ValidationException::withMessages([
                'name' => "A menu in this group already uses the permission \"{$permission}\". Choose a different name.",
            ]);
        }

        $data['permission_name'] = $permission;
        $data['order'] = $data['order'] ?? Menu::max('order') + 1;
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

        // permission_name is never taken from the request (L-9): a posted value
        // could plant a name the slug cannot produce. It only changes when the
        // menu is renamed, or when the menu has none yet. Many live menus carry
        // a name that differs from their slug, so re-deriving it on every Save
        // would rename a live permission on an edit that changed only the icon.
        unset($data['permission_name']);
        $data['order'] = $data['order'] ?? Menu::max('order') + 1;

        $nameChanged = trim((string) $data['name']) !== trim((string) $menu->name);
        $newPermission = ($nameChanged || $oldPermission === null || $oldPermission === '')
            ? Str::slug($data['name'], '_')
            : $oldPermission;

        if ($newPermission !== $oldPermission) {
            // permissions has no unique index, and the sidebar matches by name, so
            // landing on an existing name would merge two sets of holders.
            if (Permission::where('name', $newPermission)->exists()) {
                throw ValidationException::withMessages([
                    'name' => "A permission named \"{$newPermission}\" already exists. Choose a menu name that gives a different permission name.",
                ]);
            }
            $data['permission_name'] = $newPermission;
        }

        DB::transaction(function () use ($menu, $data, $oldPermission, $newPermission) {
            if ($newPermission !== $oldPermission) {
                $this->movePermission($menu, $oldPermission, $newPermission);
            }
            $menu->update($data);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        return $menu;
    }

    /**
     * Give $menu the permission $new in place of $old without changing any other
     * menu. The old row is renamed only when this menu is its sole user; when
     * another live menu shares it (or the name is duplicated), a new row is
     * created and every role and direct holder of the old name is granted it,
     * so the edited menu keeps its audience and the other menus keep theirs.
     */
    private function movePermission(Menu $menu, ?string $old, string $new): void
    {
        $oldRows = ($old === null || $old === '')
            ? collect()
            : Permission::where('name', $old)->get();
        $shared = $oldRows->isNotEmpty()
            && Menu::where('permission_name', $old)->where('id', '!=', $menu->id)->exists();

        if ($oldRows->count() === 1 && ! $shared) {
            $oldRows->first()->update(['name' => $new, 'guard_name' => 'web']);
            $mode = 'renamed';
        } else {
            $created = Permission::create(['name' => $new, 'guard_name' => 'web']);
            foreach ($oldRows as $row) {
                foreach ($row->roles as $role) {
                    $role->givePermissionTo($created);
                }
                $direct = DB::table('model_has_permissions')->where('permission_id', $row->id)->get();
                foreach ($direct as $grant) {
                    DB::table('model_has_permissions')->insertOrIgnore([
                        'permission_id' => $created->id,
                        'model_type' => $grant->model_type,
                        'model_id' => $grant->model_id,
                    ]);
                }
            }
            $mode = $oldRows->isEmpty() ? 'created' : 'copied';
        }

        Log::info('Sidebar menu permission changed', [
            'actor' => optional(auth()->user())->getKey(),
            'menu_id' => $menu->id,
            'old_permission' => $old,
            'new_permission' => $new,
            'mode' => $mode,
        ]);
    }

    public function delete($id)
    {
        $menu = $this->find($id);
        $deleted = $menu->delete();
        SidebarNavResolver::clearCache();
        self::clearStructureCache();
        return $deleted;
    }


    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Category', 'data' => 'category_name'],
            ['title' => 'Group', 'data' => 'group_name'],
            ['title' => 'Parent Menu', 'data' => 'parent_menu'],
            ['title' => 'Name', 'data' => 'name'],
            ['title' => 'Route', 'data' => 'route'],
            ['title' => 'Permission Name', 'data' => 'permission_name'],
            ['title' => 'Icon', 'data' => 'icon'],
            ['title' => 'Order', 'data' => 'order'],
            ['title' => 'Tab', 'data' => 'target'],
            ['title' => 'Created Date', 'data' => 'created_at'],
            ['title' => 'Status', 'data' => 'status','orderable' => false, 'searchable' => false],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'name' => 'category_id',
                'label' => 'Category',
                'options' => SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get()->map(fn($e) => [$e->id => $e->name]),
            ],
            [
                'name' => 'role',
                'label' => 'Role',
                'options' => [
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ],
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
            ],
        ];
    }

    # @ Base Query
    protected function baseQuery(Request $request)
    {
        return Menu::query()->with(['category', 'group.category', 'parent']);
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('category_name', fn ($e) => $this->resolveMenuCategoryName($e))
            ->addColumn('group_name', fn ($e) =>
                optional($e)->group ? optional($e)->group->name : '-'
            )
            ->addColumn('parent_menu', fn ($e) =>
                optional($e)->parent ? optional($e)->parent->name : '-'
            )
            ->addColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->addColumn('action', fn ($e) =>
                $this->actionButtons($e)
            )
            ->addColumn('order', fn ($e) =>
                $e->order == null ? '-' : $this->orderBadge($e)
            )
            ->addColumn('icon', fn ($e) =>
                $e->icon == null ? '-' : $this->iconBadge($e)
            )
            ->editColumn('status', fn ($e) =>
                $this->statusBadge($e)
            )
            ->editColumn('target', fn ($e) =>
                $e->target == 0 ? 'Same' : 'New'
            )
            ->rawColumns(['action','icon','order','status'])
            ->addIndexColumn()
            ->make(true);
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
        return '<span class="badge bg-primary">'.$data->order.'</span>';
    }

    private function iconBadge($data)
    {
        return '<span class="material-symbols-rounded fs-6" aria-hidden="true">'.$data->icon.'</span>';
    }

    private function actionButtons($data)
    {
        $deleteUrl = route('sidebar.menus.destroy', $data->id);
        $editPayload = [
            'id' => $data->id,
            'category_id' => $data->category_id,
            'group_id' => $data->group_id,
            'parent_id' => $data->parent_id,
            'name' => $data->name,
            'route' => $data->route,
            'permission_name' => $data->permission_name,
            'icon' => $data->icon,
            'order' => $data->order,
            'is_active' => $data->is_active,
            'target' => $data->target,
        ];
        $jsonData = htmlspecialchars(json_encode($editPayload), ENT_QUOTES, 'UTF-8');
        $buttons = '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Menu actions">
            <!-- Edit -->
            <a href="javascript:void(0);" class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center edit-btn border-0 bg-transparent text-primary" data-item="'.$jsonData.'" aria-label="Edit menu">
                <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">edit</i>
            </a>
             ';
            if ($data->is_active != 1) {
            $buttons .= '
            <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" aria-label="Delete category">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    <span class="d-none d-md-inline">Delete</span>
                </button>
            </form>
            ';
            }
        $buttons .= '</div>';
        return $buttons;
    }

    private function statusBadge($data)
    {
        $checked = $data->is_active ? 'checked' : '';

        return '
            <div class="form-check form-switch d-inline-block">
                <input 
                    class="form-check-input sidebar-menu-status-toggle" 
                    type="checkbox" 
                    role="switch"
                    '.$checked.'
                    data-id="'.$data->id.'"
                    data-table="sidebar_menu_groups"
                    data-column="is_active"
                >
            </div>
        ';
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