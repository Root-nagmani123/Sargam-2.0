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
        // Per-request memo. The breadcrumb view composer is registered for
        // ['admin.*', 'components.breadcrum'], so it fires once per admin view
        // rendered — layout, partials and the page itself — and each firing
        // resolved the trail from scratch (category + group + menu + parent
        // chain lookups). Every input here is fixed for the life of a request,
        // so the trail cannot change within one.
        static $memo = [];

        $memoKey = ($pageTitle ?? '')."\0".($path ?? '')."\0".($routeName ?? '')
            ."\0".request()->path()
            ."\0".(string) request()->get('menu')
            ."\0".(string) request()->get('category');

        if (array_key_exists($memoKey, $memo)) {
            return $memo[$memoKey];
        }

        return $memo[$memoKey] = $this->resolveUncached($pageTitle, $path, $routeName);
    }

    /**
     * The real breadcrumb build; wrapped by resolve()'s per-request memo.
     *
     * @return list<array{label: string, url: string|null}>
     */
    protected function resolveUncached(?string $pageTitle, ?string $path, ?string $routeName): array
    {
        $nav = $this->navResolver->resolve($path, $routeName);
        $items = [];

        if (!empty($nav['category_id'])) {
            $category = SidebarNavResolver::structureMap('categories')->get((int) $nav['category_id']);
            if ($category) {
                $items[] = ['label' => $category->name, 'url' => null];
            }
        }

        if (!empty($nav['group_id'])) {
            $group = SidebarNavResolver::structureMap('groups')->get((int) $nav['group_id']);
            if ($group) {
                $items[] = ['label' => $group->name, 'url' => null];
            }
        }

        if (!empty($nav['menu_id'])) {
            $menu = SidebarNavResolver::structureMap('menus')->get((int) $nav['menu_id']);
            if ($menu) {
                // Parent chain resolved from the cached menu map — no per-hop query.
                $parents = [];
                $parent = SidebarNavResolver::parentOf($menu);
                $guard = 0;
                while ($parent && $guard++ < 20) {
                    array_unshift($parents, $parent);
                    $parent = SidebarNavResolver::parentOf($parent);
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
