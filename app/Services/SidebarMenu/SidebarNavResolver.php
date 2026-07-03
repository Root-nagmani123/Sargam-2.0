<?php

namespace App\Services\SidebarMenu;

use App\Models\SidebarMenu\Menu;
use App\Models\SidebarMenu\MenuGroup;
use App\Models\SidebarMenu\SidebarCategory;
use Illuminate\Support\Facades\Cache;

class SidebarNavResolver
{
    public const HOME_TAB = '#home';

    public function resolve(?string $path = null, ?string $routeName = null): array
    {
        $path = $path ?? request()->path();
        $routeName = $routeName ?? (request()->route()?->getName() ?? '');

        // Fully dynamic, RBAC-driven resolution. The header tab, mini-nav category
        // and active menu are ALL derived from the matched menu's own category_id /
        // group_id in the database — never from hardcoded route/name assumptions.
        // This means relocating a menu to a different tab/category in the Menu
        // manager is reflected here automatically, with no code changes.

        // Exact menu id carried by the sidebar link (?menu=ID). This disambiguates
        // routes shared by more than one menu (e.g. two different menu items that
        // both point to "calendar"), so the menu the user actually clicked — and
        // its tab/group — is selected, not just the first menu that matches the URL.
        $requestMenuId = request()->get('menu');
        if ($requestMenuId) {
            $menu = Menu::where('is_active', 1)->find($requestMenuId);
            if ($menu) {
                return $this->resultFromMenu($menu);
            }
        }

        $requestCategoryId = request()->get('category');
        if ($requestCategoryId) {
            $category = SidebarCategory::where('is_active', 1)->find($requestCategoryId);
            if ($category) {
                return $this->resultFromCategory($category);
            }
        }

        // Sub-pages whose URL ends with a non-numeric segment that is also an
        // independent menu name (e.g. roles/1/dashboard ending with /dashboard)
        // falsely match the shorter menu via the suffix check in entryMatches,
        // opening under the wrong tab. For these routes, resolve nav as if visiting
        // the parent list page so the correct sidebar item stays highlighted.
        $parentRouteMap = [
            'roles.dashboard'       => ['path' => 'roles',       'route' => 'roles.index'],
            'assign.roles.dashboard'=> ['path' => 'roles',       'route' => 'roles.index'],
        ];
        if (isset($parentRouteMap[$routeName])) {
            $p = $parentRouteMap[$routeName];
            $menu = $this->findMenuForRequest($p['path'], $p['route'])
                 ?? $this->findMenuForRouteName($p['route']);
            if ($menu) return $this->resultFromMenu($menu);
            $legacy = $this->legacyFallback($p['path'], $p['route']);
            if ($legacy) return $legacy;
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

        // Nothing matched a menu/category, so there is no breadcrumb trail to show.
        // In that case fall back to the Home tab rather than the first ordered
        // category — an unmapped page should land on Home, not arbitrarily
        // highlight whichever category happens to sort first.
        return $this->resultForHome();
    }

    public function categoryToNavTab(SidebarCategory|string|null $category): string
    {
        if ($category === null) {
            return self::HOME_TAB;
        }

        $slug = is_string($category) ? $category : ($category->slug ?? 'home');

        return $slug === 'home' ? self::HOME_TAB : '#tab-' . $slug;
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

        return ['path' => $this->stripTrailingIdSegment($this->normalizePath($route)), 'route_name' => null, 'query_params' => $queryParams];
    }

    /**
     * Menu routes occasionally hardcode a specific record id (e.g.
     * "member/profile/edit/1", "admin/fc/joining-documents/30"). That literal id
     * prevents the menu from matching the same page for any OTHER id
     * ("member/profile/edit/11382"), so the request falls through to a broader
     * parent menu that happens to sit in a DIFFERENT tab — and the page shows up
     * under the wrong tab. We treat a trailing numeric segment as a wildcard for
     * the match index only (the stored route/href is untouched, so sidebar links
     * are unaffected), keeping at least one base segment so a path is never
     * collapsed to nothing.
     */
    protected function stripTrailingIdSegment(string $path): string
    {
        if (preg_match('#^(.+)/\d+$#', $path, $m)) {
            return $m[1];
        }

        return $path;
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

        // Match only on full path-segment boundaries. Without this, a non-boundary
        // suffix/substring match (e.g. menu path "state" vs route "admin/estate",
        // or "attendance" vs "...user_attendance") would resolve the wrong menu,
        // and therefore the wrong header tab / category.
        if (str_starts_with($normalizedPath, $menuPath . '/')) {
            return true;
        }

        if (str_ends_with($normalizedPath, '/' . $menuPath)) {
            return true;
        }

        return str_contains($normalizedPath, '/' . $menuPath . '/');
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
        // The mini-nav group, category and header tab are anchored to the menu's
        // TOP-LEVEL ancestor, because the sidebar renders a sub-menu under its
        // parent's group. Reading the matched (child) row's own group_id/category_id
        // would pick the wrong group whenever a child row's group/category has drifted
        // from its parent's — so we always resolve them from the ancestor that is
        // actually displayed directly under the mini-nav group.
        $anchor = $this->topLevelAncestor($menu);

        $category = null;
        if ($anchor->category_id) {
            $category = SidebarCategory::where('is_active', 1)->find($anchor->category_id);
        }
        if (!$category && $anchor->group_id) {
            $anchor->loadMissing('group.category');
            $category = $anchor->group?->category;
        }

        if (!$category) {
            // The menu matched but its category is missing/inactive, so we can't
            // build a real breadcrumb trail. Default to the Home tab rather than
            // arbitrarily highlighting the first ordered category.
            return $this->resultForHome();
        }

        return [
            'nav_tab' => $this->categoryToNavTab($category),
            'category_id' => $category->id,
            'category_slug' => $category->slug,
            'group_id' => $anchor->group_id,
            'menu_id' => $menu->id,
        ];
    }

    /**
     * Walk up the parent chain to the top-level menu (the row rendered directly
     * under a mini-nav group). Returns the menu itself when it has no parent.
     */
    protected function topLevelAncestor(Menu $menu): Menu
    {
        $guard = 0;
        while ($menu->parent_id && $guard++ < 20) {
            $menu->loadMissing('parent');
            if (!$menu->parent) {
                break;
            }
            $menu = $menu->parent;
        }

        return $menu;
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

    /**
     * Home-tab result with an empty trail. Used when nothing resolves, so the
     * Home tab is selected and the breadcrumb falls back to its default.
     */
    protected function resultForHome(): array
    {
        $home = SidebarCategory::where('is_active', 1)->where('slug', 'home')->first();

        return [
            'nav_tab' => self::HOME_TAB,
            'category_id' => $home?->id,
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
        // Profile edit belongs in the home/general tab, not setup
        if (request()->routeIs('member.profile.*') || request()->routeIs('member.profile.edit')) {
            return $this->resultForCategorySlug('home');
        }

        if (request()->routeIs('leave.*') || str_starts_with($path, 'leave')) {
            return $this->resultForCategorySlug('home');
        }

        $slug = null;

        if (
            request()->routeIs('admin.employee_idcard.*') || request()->routeIs('admin.issue-management*') ||
            request()->routeIs('member.*') || request()->routeIs('faculty.*') || request()->routeIs('programme.*') ||
            request()->routeIs('admin.roles.*') || request()->routeIs('admin.users.*') ||
            request()->routeIs('roles.*') || request()->routeIs('users.*') ||
            str_starts_with($path, 'roles') || str_starts_with($path, 'users') ||
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

    /**
     * Build the breadcrumb trail from the CURRENT request's actual menu
     * placement in the DB (sidebar_categories → menu_groups → menus, with
     * menus.parent_id nesting). No hardcoded section names — relocating a menu
     * in the Menu manager changes its breadcrumb automatically.
     *
     * Shape:  Home / Category / Group / [parent menus …] / MenuName
     * Levels that don't exist for a page are simply skipped. Home is always
     * first. Returns a list of ['label' => string, 'url' => string|null];
     * the caller renders the last item as the active (non-linked) crumb.
     *
     * @return list<array{label: string, url: string|null}>
     */
    public function breadcrumb(?string $path = null, ?string $routeName = null): array
    {
        $result = $this->resolve($path, $routeName);

        // Home is the permanent root.
        $trail = [[
            'label' => 'Home',
            'url'   => $this->safeRoute('admin.dashboard'),
        ]];

        // Category (the header tab) — skip the Home category, since Home is
        // already the root and we don't want "Home / Home".
        if (! empty($result['category_id']) && ($result['category_slug'] ?? null) !== 'home') {
            $category = SidebarCategory::find($result['category_id']);
            if ($category && filled($category->name)) {
                $trail[] = ['label' => $category->name, 'url' => null];
            }
        }

        // Group (the mid-level sidebar heading), when the menu sits inside one.
        if (! empty($result['group_id'])) {
            $group = MenuGroup::find($result['group_id']);
            if ($group && filled($group->name)) {
                $trail[] = ['label' => $group->name, 'url' => null];
            }
        }

        // The menu itself plus every ancestor menu (parent_id chain), ordered
        // top-level → … → active. Ancestors link to their own page; the active
        // menu is left without a URL so it renders as the current crumb.
        if (! empty($result['menu_id'])) {
            $menu = Menu::find($result['menu_id']);
            if ($menu) {
                foreach ($this->menuAncestryChain($menu) as $node) {
                    $trail[] = [
                        'label' => $node->name,
                        'url'   => $node->id === $menu->id ? null : $this->menuUrl($node),
                    ];
                }
            }
        }

        return $this->dedupeTrail($trail);
    }

    /**
     * Menus from the top-level ancestor down to $menu (root-first).
     *
     * Edge cases: a `visited` guard breaks circular parent references, and a
     * missing parent stops the walk instead of throwing — so a broken row can
     * never hang or crash the breadcrumb.
     *
     * @return list<Menu>
     */
    protected function menuAncestryChain(Menu $menu): array
    {
        $chain = [];
        $visited = [];
        $current = $menu;
        $guard = 0;

        while ($current && $guard++ < 20) {
            if (isset($visited[$current->id])) {
                break; // circular reference guard
            }
            $visited[$current->id] = true;

            array_unshift($chain, $current); // prepend keeps the list root-first

            if (! $current->parent_id) {
                break; // reached the top-level menu
            }

            $current->loadMissing('parent');
            if (! $current->parent) {
                break; // parent_id set but the parent row is missing/inactive
            }

            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * Turn a stored menu route (URL path, named route, or absolute URL) into a
     * clickable href. Returns null for placeholder/blank routes ("#", "").
     */
    protected function menuUrl(Menu $menu): ?string
    {
        $route = trim((string) $menu->route);
        if ($route === '' || $route === '#') {
            return null;
        }

        if (preg_match('#^https?://#i', $route)) {
            return $route;
        }

        // Named route: contains a dot and no slash (e.g. "roles.index").
        if (str_contains($route, '.') && ! str_contains($route, '/')) {
            return $this->safeRoute($route);
        }

        return url($route);
    }

    /**
     * Drop consecutive duplicate labels (case-insensitive) so a group and its
     * top-level menu sharing a name don't produce "Setup / Setup".
     *
     * @param  list<array{label: string, url: string|null}>  $trail
     * @return list<array{label: string, url: string|null}>
     */
    protected function dedupeTrail(array $trail): array
    {
        $out = [];
        foreach ($trail as $item) {
            if (! filled($item['label'])) {
                continue;
            }
            $prev = end($out);
            if ($prev && strcasecmp((string) $prev['label'], (string) $item['label']) === 0) {
                // Prefer the later row's URL (the deeper/active one) on collapse.
                $out[array_key_last($out)]['url'] = $item['url'] ?? $prev['url'];
                continue;
            }
            $out[] = $item;
        }

        return array_values($out);
    }

    protected function safeRoute(string $name): ?string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return null;
        }
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
