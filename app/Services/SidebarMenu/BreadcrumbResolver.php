<?php

namespace App\Services\SidebarMenu;

use App\Models\SidebarMenu\Menu;
use App\Models\SidebarMenu\MenuGroup;
use App\Models\SidebarMenu\SidebarCategory;

class BreadcrumbResolver
{
    public function __construct(
        protected SidebarNavResolver $navResolver
    ) {}

    /**
     * @return list<array{label: string, url: string|null}>
     */
    public function resolve(?string $pageTitle = null, ?string $path = null, ?string $routeName = null): array
    {
        $nav = $this->navResolver->resolve($path, $routeName);
        $items = [];

        if (!empty($nav['category_id'])) {
            $category = SidebarCategory::find($nav['category_id']);
            if ($category) {
                $items[] = ['label' => $category->name, 'url' => null];
            }
        }

        if (!empty($nav['group_id'])) {
            $group = MenuGroup::find($nav['group_id']);
            if ($group) {
                $items[] = ['label' => $group->name, 'url' => null];
            }
        }

        if (!empty($nav['menu_id'])) {
            $menu = Menu::with('parent')->find($nav['menu_id']);
            if ($menu) {
                $parents = [];
                $parent = $menu->parent;
                while ($parent) {
                    array_unshift($parents, $parent);
                    $parent->loadMissing('parent');
                    $parent = $parent->parent;
                }
                foreach ($parents as $p) {
                    $items[] = ['label' => $p->name, 'url' => null];
                }
                if ($pageTitle && $pageTitle !== $menu->name) {
                    $items[] = ['label' => $menu->name, 'url' => null];
                    $items[] = ['label' => $pageTitle, 'url' => null];
                } else {
                    $items[] = ['label' => $pageTitle ?: $menu->name, 'url' => null];
                }

                return $items;
            }
        }

        if ($pageTitle) {
            $items[] = ['label' => $pageTitle, 'url' => null];
        }

        return $items;
    }
}
