<?php

namespace App\Http\Controllers;

use App\Models\SidebarMenu\Menu;
use App\Models\SidebarMenu\MenuGroup;
use App\Models\SidebarMenu\SidebarCategory;
use App\Services\SidebarMenu\MenuRouteMatcher;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function __construct(
        protected MenuRouteMatcher $routeMatcher
    ) {}

    public function getGroups(Request $request)
    {
        $user = auth()->user();
        $isAdmin = isSidebarPrivilegedUser();

        if (! $isAdmin && ! userHasAssignedRoles()) {
            return '<ul class="sidebar-groups-list"><li>No groups found.</li></ul>';
        }

        $permissions = $isAdmin ? [] : $user->getAllPermissions()->pluck('name')->toArray();

        $category = SidebarCategory::with(['groups.menus.children'])->find($request->category_id);

        if (!$category) {
            return '<ul class="sidebar-groups-list"><li>No groups found.</li></ul>';
        }

        $groups = $category->groups->filter(function ($group) use ($permissions, $isAdmin) {
            if ($isAdmin) {
                return true;
            }

            return $group->menus->contains(function ($menu) use ($permissions) {
                $hasMenuPermission = $this->menuVisibleToUser($menu->permission_name, $permissions);

                $hasChildPermission = $menu->children->contains(function ($child) use ($permissions) {
                    return $this->menuVisibleToUser($child->permission_name, $permissions);
                });

                return $hasMenuPermission || $hasChildPermission;
            });
        });

        if ($groups->isEmpty()) {
            return '<ul class="sidebar-groups-list"><li>No groups found.</li></ul>';
        }

        $activeGroupId = $request->get('active_group_id');

        $html = '<ul class="sidebar-groups-list">';

        foreach ($groups as $group) {
            $groupSelected = $activeGroupId && (string) $activeGroupId === (string) $group->id;
            $selectedClass = $groupSelected ? ' selected' : '';
            $ariaSelected = $groupSelected ? 'true' : 'false';

            $html .= '<li class="sidebar-group-item mini-nav-item py-2'.$selectedClass.'" id="'.$group->id.'" data-id="'.$group->id.'">';
            $html .= '<a href="javascript:void(0)" class="sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3 sidebar-group-link'.$selectedClass.'" data-id="'.$group->id.'" data-name="'.e($group->name).'" aria-selected="'.$ariaSelected.'">';
            $html .= '<span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">';
            $html .= '<i class="material-icons menu-icon material-symbols-rounded">'.e($group->icon).'</i>';
            $html .= '</span>';
            $html .= '<span class="sidebar-google-label">'.e($group->name).'</span>';
            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    public function sidebarMenus(Request $request)
    {
        $user = auth()->user();
        $isAdmin = isSidebarPrivilegedUser();

        if (! $isAdmin && ! userHasAssignedRoles()) {
            return $this->renderNoActiveMenuMessage();
        }

        $permissions = $isAdmin ? [] : $user->getAllPermissions()->pluck('name')->toArray();

        $groupId = $request->group_id;
        $currentPath = $this->resolveCurrentPath($request);
        $currentRouteName = $request->input('current_route', request()->route()?->getName() ?? '');

        $group = MenuGroup::with([
            'menus' => function ($q) {
                $q->whereNull('parent_id')
                    ->where('is_active', 1)
                    ->orderBy('order', 'ASC');
            },
            'menus.children' => function ($q) {
                $q->where('is_active', 1)
                    ->orderBy('order', 'ASC');
            },
        ])->find($groupId);

        if (!$group) {
            return $this->renderNoActiveMenuMessage();
        }

        $html = '';

        foreach ($group->menus as $menu) {
            $children = $menu->children->filter(function ($child) use ($permissions, $isAdmin) {
                if ($isAdmin) {
                    return true;
                }

                return $this->menuVisibleToUser($child->permission_name, $permissions);
            });

            $hasMenuPermission = $isAdmin || $this->menuVisibleToUser($menu->permission_name, $permissions);

            if (!$hasMenuPermission && $children->isEmpty()) {
                continue;
            }

            $hasChild = $children->count() > 0;
            $collapseId = 'menu_' . $menu->id;
            $childActive = false;

            if ($hasChild) {
                foreach ($children as $submenu) {
                    if ($this->routeMatcher->isActive($submenu->route, $currentPath, $currentRouteName)) {
                        $childActive = true;
                        break;
                    }
                }

                $collapseClass = $childActive ? 'collapse show' : 'collapse';
                $parentLinkClass = 'sidebar-link';
                $ariaExpanded = $childActive ? 'true' : 'false';

                $html .= '
                <li class="sidebar-item" style="background:#4077ad;
                    border-radius:30px 0 0 30px;
                    width:100%;
                    box-shadow:-2px 3px rgba(251,248,248,0.1);
                    min-width:250px;">

                    <a class="'.$parentLinkClass.' d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse"
                        href="#'.$collapseId.'"
                        aria-expanded="'.$ariaExpanded.'">
                        <span class="hide-menu fw-bold">'.e($menu->name).'</span>
                        <i class="material-icons">keyboard_arrow_down</i>
                    </a>
                </li>
                <ul class="'.$collapseClass.' list-unstyled ps-3" id="'.$collapseId.'">';

                foreach ($children as $submenu) {
                    $activeClass = $this->routeMatcher->isActive($submenu->route, $currentPath, $currentRouteName) ? ' active' : '';
                    $href = $this->routeMatcher->resolveHref($submenu->route, $submenu->id);
                    $html .= '
                <li class="sidebar-item">
                    <a class="sidebar-link'.$activeClass.'" href="'.e($href).'" target="'.($submenu->target == 1 ? '_blank' : '_self').'">
                        <span class="hide-menu">'.e($submenu->name).'</span>
                    </a>
                </li>';
                }

                $html .= '</ul>';
            } else {
                if ($hasMenuPermission) {
                    $activeClass = $this->routeMatcher->isActive($menu->route, $currentPath, $currentRouteName) ? ' active' : '';
                    $href = $this->routeMatcher->resolveHref($menu->route, $menu->id);
                    $html .= '
                <li class="sidebar-item">
                    <a class="sidebar-link'.$activeClass.'" href="'.e($href).'" target="'.($menu->target == 1 ? '_blank' : '_self').'">
                        <span class="hide-menu">'.e($menu->name).'</span>
                    </a>
                </li>';
                }
            }
        }

        return $html ?: $this->renderNoActiveMenuMessage();
    }

    protected function renderNoActiveMenuMessage(): string
    {
        return '<li class="sidebar-item sidebar-empty-state list-unstyled">'
            . '<div class="px-3 py-4 text-center">'
            . '<i class="material-icons material-symbols-rounded sidebar-empty-icon mb-2" aria-hidden="true">info</i>'
            . '<span class="sidebar-empty-message small fw-medium d-block">No active menu</span>'
            . '</div></li>';
    }

    protected function resolveCurrentPath(Request $request): string
    {
        if ($request->filled('current_path')) {
            return $this->routeMatcher->normalizePath($request->input('current_path'));
        }

        $referer = $request->headers->get('Referer');
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH);

            return $this->routeMatcher->normalizePath($path ?? '');
        }

        return $this->routeMatcher->normalizePath($request->path());
    }

    public function getGroupMenus(Request $request, $group_id)
    {
        $query = Menu::query()
            ->select('id', 'name')
            ->where('group_id', $group_id)
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->orderBy('order', 'ASC');

        if ($request->filled('exclude_id')) {
            $query->where('id', '!=', (int) $request->input('exclude_id'));
        }

        $menus = $query->get();

        return response()->json([
            'success' => true,
            'menus' => $menus,
            'message' => $menus->isEmpty() ? 'No parent menus in this group' : 'Menus fetched successfully',
        ]);
    }

    public function getCategoryGroups(Request $request, $category_id)
    {
        $groups = MenuGroup::where('category_id', $category_id)->where('is_active', 1)->orderBy('order', 'ASC')->get();
        if ($groups->count() > 0) {
            return response()->json([
                'success' => true,
                'groups' => $groups,
                'message' => 'Groups fetched successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'groups' => [],
            'message' => 'No groups found',
        ]);
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
}
