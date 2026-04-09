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
        $data['permission_name'] = $permission;
        $data['order'] = $data['order'] ?? Menu::max('order') + 1;
        $menu = Menu::create($data);
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
        return $menu;
    }

    public function update($id, array $data)
    {
        $menu = $this->find($id);
        $oldPermission = $menu->permission_name;
        $newPermission = Str::slug($data['name'], '_');
        $data['order'] = $data['order'] ?? Menu::max('order') + 1;
        $menu->update($data);

        if ($oldPermission !== $newPermission) {

            $permission = Permission::where('name', $oldPermission)->first();

            if ($permission) {
                $permission->update([
                    'name' => $newPermission,
                    'guard_name' => 'web'
                ]);
            } else {
                Permission::create([
                    'name' => $newPermission,
                    'guard_name' => 'web'
                ]);
            }
        }
        return $menu;
    }

    public function delete($id)
    {
        $menu = $this->find($id);
        return $menu->delete();
    }


    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Group', 'data' => 'group_id'],
            ['title' => 'Parent Menu', 'data' => 'parent_id'],
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
        return Menu::query()->with('group','parent');
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('group_id', fn ($e) =>
                optional($e)->group ? optional($e)->group->name : '-'
            )
            ->addColumn('parent_id', fn ($e) =>
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
        $jsonData = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
        $buttons = '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Menu actions">
            <!-- Edit -->
            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 edit-btn" data-item="'.$jsonData.'" aria-label="Edit menu">
                <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                <span class="d-none d-md-inline">Edit</span>
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


    public function getMenus()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('Admin');

        # if Admin then load all menus else load only user permissions
        $permissions = $isAdmin ? [] : $user->getAllPermissions()->pluck('name')->toArray();


        $categories = SidebarCategory::select('id', 'icon', 'name', 'slug')
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


        if ($isAdmin) {
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

        return $categories->map(function ($category) use ($permissions) {
            $category->groups = $category->groups->map(function ($group) use ($permissions) {
                $group->menus = $group->menus->map(function ($menu) use ($permissions) {
                    $menu->children = $menu->children->filter(function ($child) use ($permissions) {
                        return !$child->permission_name || in_array($child->permission_name, $permissions);
                    })->values();
                    $hasMenuPermission = !$menu->permission_name || in_array($menu->permission_name, $permissions);
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