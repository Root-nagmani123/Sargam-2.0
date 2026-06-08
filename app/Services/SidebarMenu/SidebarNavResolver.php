<?php

namespace App\Services\SidebarMenu;

use App\Models\SidebarMenu\Menu;
use App\Models\SidebarMenu\SidebarCategory;
use Illuminate\Support\Facades\Cache;

class SidebarNavResolver
{
    public const HOME_TAB = '#home';

    public function resolve(?string $path = null, ?string $routeName = null): array
    {
        $path = $path ?? request()->path();
        $routeName = $routeName ?? (request()->route()?->getName() ?? '');

        if ($this->isDashboardRoute($routeName, $path)) {
            return $this->resultForCategorySlug('home');
        }

        $requestCategoryId = request()->get('category');
        if ($requestCategoryId) {
            $category = SidebarCategory::where('is_active', 1)->find($requestCategoryId);
            if ($category) {
                return $this->resultFromCategory($category);
            }
        }

        $menu = $this->findMenuForRequest($path, $routeName);
        if ($menu) {
            return $this->resultFromMenu($menu);
        }

        // Named routes (e.g. student.medical.exemption.*) when menu URL is stored differently
        if ($routeName !== '') {
            $menu = $this->findMenuForRouteName($routeName);
            if ($menu) {
                return $this->resultFromMenu($menu);
            }
        }

        $legacy = $this->legacyFallback($path, $routeName);
        if ($legacy) {
            return $legacy;
        }

        return $this->resultFromFirstCategory();
    }

    public function categoryToNavTab(SidebarCategory|string|null $category): string
    {
        if ($category === null) {
            return self::HOME_TAB;
        }

        $slug = is_string($category) ? $category : ($category->slug ?? 'home');

        return $slug === 'home' ? self::HOME_TAB : '#tab-' . $slug;
    }

    protected function isDashboardRoute(string $routeName, string $path): bool
    {
        return request()->routeIs('admin.dashboard')
            || request()->routeIs('admin.dashboard.*')
            || request()->routeIs('calendar.index')
            || $this->normalizePath($path) === 'admin/dashboard'
            || $this->normalizePath($path) === 'dashboard';
    }

    protected function findMenuForRequest(string $path, string $routeName): ?Menu
    {
        $normalizedPath = $this->normalizePath($path);
        $requestParams = request()->query();
        $best = null;
        $bestLength = -1;
        $bestQueryScore = -1; // Higher = more query params matched exactly

        foreach ($this->routeMenuIndex() as $entry) {
            if (!$this->entryMatches($entry, $normalizedPath, $routeName)) {
                continue;
            }

            $length = strlen($entry['path'] ?? '');
            $entryQueryParams = $entry['query_params'] ?? [];

            // Score: how well do query params match?
            // +2 for each param in entry that matches request value exactly
            // -1 for each param in entry that is NOT in request (penalise scope=self when request has no scope)
            $queryScore = 0;
            foreach ($entryQueryParams as $key => $val) {
                if (isset($requestParams[$key]) && (string) $requestParams[$key] === (string) $val) {
                    $queryScore += 2;
                } else {
                    $queryScore -= 1; // Entry requires this param but request doesn't have it
                }
            }

            // Prefer entry with better query score, then longer path
            $isBetter = ($queryScore > $bestQueryScore)
                || ($queryScore === $bestQueryScore && $length > $bestLength);

            if ($isBetter) {
                $bestLength = $length;
                $bestQueryScore = $queryScore;
                $best = $entry['menu'];
            }
        }

        return $best;
    }

    protected function findMenuForRouteName(string $routeName): ?Menu
    {
        foreach ($this->routeMenuIndex() as $entry) {
            if (empty($entry['route_name'])) {
                continue;
            }

            if ($this->entryMatches($entry, '', $routeName)) {
                return $entry['menu'];
            }
        }

        return null;
    }

    /**
     * @return list<array{menu: Menu, path: string|null, route_name: string|null, query_params: array<string,string>}>
     */
    protected function routeMenuIndex(): array
    {
        return Cache::remember('sidebar_nav_route_index', 300, function () {
            $entries = [];

            $menus = Menu::query()
                ->where('is_active', 1)
                ->whereNotNull('route')
                ->where('route', '!=', '')
                ->where('route', '!=', '#')
                ->with(['group.category'])
                ->get(['id', 'category_id', 'group_id', 'parent_id', 'route']);

            foreach ($menus as $menu) {
                $parsed = $this->parseMenuRoute($menu->route);
                if ($parsed === null) {
                    continue;
                }

                $entries[] = [
                    'menu'         => $menu,
                    'path'         => $parsed['path'],
                    'route_name'   => $parsed['route_name'],
                    'query_params' => $parsed['query_params'],
                ];
            }

            return $entries;
        });
    }

    /**
     * @return array{path: string|null, route_name: string|null, query_params: array<string,string>}|null
     */
    protected function parseMenuRoute(string $route): ?array
    {
        $route = trim($route);
        if ($route === '' || $route === '#') {
            return null;
        }

        // Extract query string before any URL parsing
        $queryParams = [];
        if (str_contains($route, '?')) {
            [$routePath, $queryString] = explode('?', $route, 2);
            parse_str($queryString, $queryParams);
            $route = $routePath;
        }

        if (preg_match('#^https?://#i', $route)) {
            $path = parse_url($route, PHP_URL_PATH);

            return $path ? ['path' => $this->normalizePath($path), 'route_name' => null, 'query_params' => $queryParams] : null;
        }

        if (str_contains($route, '.') && !str_contains($route, '/')) {
            return ['path' => null, 'route_name' => $route, 'query_params' => $queryParams];
        }

        return ['path' => $this->normalizePath($route), 'route_name' => null, 'query_params' => $queryParams];
    }

    protected function entryMatches(array $entry, string $normalizedPath, string $routeName): bool
    {
        if (!empty($entry['route_name']) && $routeName !== '') {
            if ($this->routeNamesMatch($routeName, $entry['route_name'])) {
                return true;
            }
        }

        $menuPath = $entry['path'] ?? '';
        if ($menuPath === '' || $normalizedPath === '') {
            return false;
        }

        if ($menuPath === $normalizedPath) {
            return true;
        }

        if (str_ends_with($normalizedPath, '/' . $menuPath) || str_ends_with($normalizedPath, $menuPath)) {
            return true;
        }

        if (str_starts_with($normalizedPath, $menuPath . '/')) {
            return true;
        }

        return str_contains($normalizedPath, '/' . $menuPath . '/')
            || str_contains($normalizedPath, '/' . $menuPath);
    }

    protected function routeNamesMatch(string $currentRoute, string $menuRoute): bool
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

    protected function normalizePath(string $path): string
    {
        $path = strtolower(trim($path));
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;
        $path = parse_url($path, PHP_URL_PATH) ?? $path;

        return trim($path, '/');
    }

    protected function resultFromMenu(Menu $menu): array
    {
        $category = null;
        if ($menu->category_id) {
            $category = SidebarCategory::where('is_active', 1)->find($menu->category_id);
        }
        if (!$category && $menu->relationLoaded('group') && $menu->group) {
            $category = $menu->group->category ?? null;
        }
        if (!$category && $menu->group_id) {
            $menu->loadMissing('group.category');
            $category = $menu->group?->category;
        }

        if (!$category) {
            return $this->resultFromFirstCategory();
        }

        return [
            'nav_tab' => $this->categoryToNavTab($category),
            'category_id' => $category->id,
            'category_slug' => $category->slug,
            'group_id' => $menu->group_id,
            'menu_id' => $menu->id,
        ];
    }

    protected function resultFromCategory(SidebarCategory $category): array
    {
        return [
            'nav_tab' => $this->categoryToNavTab($category),
            'category_id' => $category->id,
            'category_slug' => $category->slug,
            'group_id' => null,
            'menu_id' => null,
        ];
    }

    protected function resultForCategorySlug(string $slug): array
    {
        $category = SidebarCategory::where('is_active', 1)->where('slug', $slug)->first();

        return $category
            ? $this->resultFromCategory($category)
            : [
                'nav_tab' => $slug === 'home' ? self::HOME_TAB : '#tab-' . $slug,
                'category_id' => null,
                'category_slug' => $slug,
                'group_id' => null,
                'menu_id' => null,
            ];
    }

    protected function resultFromFirstCategory(): array
    {
        $category = SidebarCategory::where('is_active', 1)->orderBy('order')->first();

        return $category
            ? $this->resultFromCategory($category)
            : [
                'nav_tab' => self::HOME_TAB,
                'category_id' => null,
                'category_slug' => 'home',
                'group_id' => null,
                'menu_id' => null,
            ];
    }

  /**
     * Fallback for pages not yet registered in dynamic menus.
     */
    protected function legacyFallback(string $path, string $routeName): ?array
    {
        $slug = null;

        if (
            request()->routeIs('admin.employee_idcard.*') || request()->routeIs('admin.issue-management*') ||
            request()->routeIs('member.*') || request()->routeIs('faculty.*') || request()->routeIs('programme.*') ||
            request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*') ||
            str_starts_with($path, 'setup/') || str_starts_with($path, 'admin/setup') ||
            str_starts_with($path, 'admin/employee-idcard') || str_starts_with($path, 'admin/issue-management') ||
            str_starts_with($path, 'courseAttendanceNoticeMap') || str_starts_with($path, 'course_memo') ||
            str_starts_with($path, 'building_floor') || str_starts_with($path, 'group_mapping') ||
            str_starts_with($path, 'course-repository') || str_starts_with($path, 'feedback') ||
            str_starts_with($path, 'admin/notice') || str_starts_with($path, 'attendance') ||
            str_starts_with($path, 'security') || str_starts_with($path, 'ot_notice') ||
            str_starts_with($path, 'forms') || str_starts_with($path, 'registration') ||
            str_starts_with($path, 'mdo_escrot') ||
            str_starts_with($path, 'medical_exception') || str_starts_with($path, 'memo_discipline') ||
            str_starts_with($path, 'country') || str_starts_with($path, 'state') || str_starts_with($path, 'city') ||
            str_starts_with($path, 'stream') || str_starts_with($path, 'subject') || str_starts_with($path, 'Venue-Master') ||
            str_starts_with($path, 'batch') || str_starts_with($path, 'curriculum') || str_starts_with($path, 'mapping') ||
            str_starts_with($path, 'admin/master') || str_contains($path, 'breadcrumb-showcase') || str_starts_with($path, 'password') ||
            str_starts_with($path, 'expertise') || str_starts_with($path, 'faculty_notice') || str_starts_with($path, 'faculty_mdo') ||
            str_starts_with($path, 'sidebar/')
        ) {
            $slug = 'setup';
        } elseif (str_starts_with($path, 'communications') || request()->routeIs('*communications*')) {
            $slug = 'communications';
        } elseif (str_starts_with($path, 'academics') || request()->routeIs('*academics*')) {
            $slug = 'academics';
        } elseif (str_starts_with($path, 'material') || request()->routeIs('*material*')) {
            return [
                'nav_tab' => '#tab-material-management',
                'category_id' => null,
                'category_slug' => 'material-management',
                'group_id' => null,
                'menu_id' => null,
            ];
        }

        return $slug ? $this->resultForCategorySlug($slug) : null;
    }

    public static function clearCache(): void
    {
        Cache::forget('sidebar_nav_route_index');
    }

    /**
     * Blade @section name for the active category tab pane.
     */
    public function contentSectionForSlug(?string $slug): string
    {
        return match ($slug) {
            'setup' => 'setup_content',
            'communications' => 'communications_content',
            'academics' => 'academics_content',
            'material-management' => 'material_management_content',
            default => 'content',
        };
    }
}
