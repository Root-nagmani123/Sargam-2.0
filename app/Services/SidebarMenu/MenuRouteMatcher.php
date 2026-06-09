<?php

namespace App\Services\SidebarMenu;

use Illuminate\Support\Facades\Route;

class MenuRouteMatcher
{
    /**
     * Trailing route/path segments that denote an action on a resource rather
     * than a distinct menu (Create User, Edit User, View User …). They let the
     * parent Index/List menu be recognised as the owner of its child pages when
     * a menu is stored as a *named* route (e.g. "admin.users.index"). Path-style
     * menus — which is how every menu in this app is stored — don't need this
     * list at all: their child URLs are path-segment descendants of the index
     * URL, so descendant matching already groups them. This is a generic CRUD
     * vocabulary, not a hardcoded route/menu/module name.
     */
    public const ACTION_SEGMENTS = [
        'index', 'create', 'store', 'edit', 'update', 'show', 'destroy', 'view',
        'detail', 'details', 'history', 'approval', 'approve', 'preview', 'print',
        'export', 'import', 'download', 'list', 'copy', 'duplicate', 'restore',
    ];

    public function normalizePath(string $path): string
    {
        $path = strtolower(trim($path));
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;
        $path = parse_url($path, PHP_URL_PATH) ?? $path;

        return trim($path, '/');
    }

    /**
     * Specificity score for how strongly a menu route matches the current
     * request. Higher = more specific; -1 means no match.
     *
     * A menu matches when the current request is the menu's own page OR a child
     * page nested under it (Create / Edit / View / Show / History / Approval …),
     * because Laravel resource child URLs are path-segment descendants of the
     * index URL: users → users/create, users/15/edit, users/15/view,
     * notice → notice/view/25. The score is the matched menu-path length, so a
     * caller comparing several candidates can keep exactly the most specific one
     * active — e.g. both "users" and a dedicated "users/create" menu match the
     * URL "users/create", and the longer one wins. This keeps the parent
     * Index/List menu highlighted across all of its child pages without
     * hardcoding any route, menu or module name.
     */
    public function matchScore(?string $menuRoute, string $currentPath, ?string $currentRouteName = null): int
    {
        if (!$menuRoute || $menuRoute === '#' || trim($menuRoute) === '') {
            return -1;
        }

        $menuRoute = trim($menuRoute);

        // Named-route menus ("admin.users.index"): match by route name, including
        // sibling action routes that share the same resource base.
        if (str_contains($menuRoute, '.') && !str_contains($menuRoute, '/')) {
            if ($currentRouteName && $this->routeNamesMatch($currentRouteName, $menuRoute)) {
                return strlen($menuRoute);
            }

            return -1;
        }

        $menuPath = $this->normalizePath(
            preg_match('#^https?://#i', $menuRoute)
                ? (parse_url($menuRoute, PHP_URL_PATH) ?? $menuRoute)
                : $menuRoute
        );

        if ($menuPath === '' || $currentPath === '') {
            return -1;
        }

        // Exact page, or a child page nested under this menu. The trailing slash
        // enforces a full path-segment boundary so "subject" never matches
        // "subjects/.." and "state" never matches "estate/..".
        if ($menuPath === $currentPath || str_starts_with($currentPath, $menuPath . '/')) {
            return strlen($menuPath);
        }

        return -1;
    }

    public function isActive(?string $menuRoute, string $currentPath, ?string $currentRouteName = null): bool
    {
        return $this->matchScore($menuRoute, $currentPath, $currentRouteName) >= 0;
    }

    public function routeNamesMatch(string $currentRoute, string $menuRoute): bool
    {
        if ($currentRoute === $menuRoute) {
            return true;
        }

        if (str_ends_with($menuRoute, '.*')) {
            $prefix = rtrim($menuRoute, '.*');

            return $currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '.');
        }

        if (str_ends_with($currentRoute, '.*')) {
            $prefix = rtrim($currentRoute, '.*');

            return $menuRoute === $prefix || str_starts_with($menuRoute, $prefix . '.');
        }

        if (
            str_starts_with($currentRoute, $menuRoute . '.')
            || str_starts_with($menuRoute, $currentRoute . '.')
        ) {
            return true;
        }

        // Sibling action routes belong to the same Index menu: stripping a
        // trailing action segment reduces admin.users.index, admin.users.create
        // and admin.users.edit all to the same resource base "admin.users".
        $base = $this->routeNameBase($menuRoute);

        return $base !== '' && $base === $this->routeNameBase($currentRoute);
    }

    /**
     * Resource base of a named route: the route name with a single trailing
     * action segment removed (admin.users.create ⇒ admin.users). Names whose
     * last segment is not an action verb are returned unchanged.
     */
    protected function routeNameBase(string $routeName): string
    {
        $segments = explode('.', $routeName);
        if (count($segments) > 1 && in_array(end($segments), self::ACTION_SEGMENTS, true)) {
            array_pop($segments);
        }

        return implode('.', $segments);
    }

    /**
     * Resolve href for a menu route; invalid routes point to navigation error page.
     */
    public function resolveHref(?string $menuRoute, ?int $menuId = null): string
    {
        if (!$menuRoute || $menuRoute === '#' || trim($menuRoute) === '') {
            return route('admin.navigation.error', [
                'reason' => 'missing_path',
                'menu_id' => $menuId,
            ]);
        }

        $menuRoute = trim($menuRoute);

        if (preg_match('#^https?://#i', $menuRoute)) {
            return $menuRoute;
        }

        if (str_contains($menuRoute, '.') && !str_contains($menuRoute, '/')) {
            if (Route::has($menuRoute)) {
                try {
                    return $this->withMenuParam(route($menuRoute), $menuId);
                } catch (\Throwable) {
                    return route('admin.navigation.error', [
                        'reason' => 'invalid_route',
                        'menu_id' => $menuId,
                    ]);
                }
            }

            return route('admin.navigation.error', [
                'reason' => 'invalid_route',
                'menu_id' => $menuId,
            ]);
        }

        $path = ltrim($menuRoute, '/');

        return $this->withMenuParam(url($path), $menuId);
    }

    /**
     * Tag an internal menu URL with its menu id so the active-state resolver can
     * tell apart different menu items that share the same route. Active-link
     * matching falls back to path comparison, so the extra param is harmless.
     */
    protected function withMenuParam(string $url, ?int $menuId): string
    {
        if (!$menuId) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'menu=' . $menuId;
    }
}
