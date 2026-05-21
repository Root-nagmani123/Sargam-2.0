<?php

namespace App\Services\SidebarMenu;

use Illuminate\Support\Facades\Route;

class MenuRouteMatcher
{
    public function normalizePath(string $path): string
    {
        $path = strtolower(trim($path));
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;
        $path = parse_url($path, PHP_URL_PATH) ?? $path;

        return trim($path, '/');
    }

    public function isActive(?string $menuRoute, string $currentPath, ?string $currentRouteName = null): bool
    {
        if (!$menuRoute || $menuRoute === '#' || trim($menuRoute) === '') {
            return false;
        }

        $menuRoute = trim($menuRoute);

        if (str_contains($menuRoute, '.') && !str_contains($menuRoute, '/')) {
            if ($currentRouteName && $this->routeNamesMatch($currentRouteName, $menuRoute)) {
                return true;
            }
        }

        $menuPath = $this->normalizePath(
            preg_match('#^https?://#i', $menuRoute)
                ? (parse_url($menuRoute, PHP_URL_PATH) ?? $menuRoute)
                : $menuRoute
        );

        if ($menuPath === '' || $currentPath === '') {
            return false;
        }

        if ($menuPath === $currentPath) {
            return true;
        }

        if (str_ends_with($currentPath, '/' . $menuPath) || str_ends_with($currentPath, $menuPath)) {
            return true;
        }

        if (str_starts_with($currentPath, $menuPath . '/')) {
            return true;
        }

        return str_contains($currentPath, '/' . $menuPath . '/')
            || str_contains($currentPath, '/' . $menuPath);
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

        return str_starts_with($currentRoute, $menuRoute . '.')
            || str_starts_with($menuRoute, $currentRoute . '.');
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
                    return route($menuRoute);
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

        return url($path);
    }
}
