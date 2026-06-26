<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Role-Based URL Access Control — fully database-driven.
 *
 * Every accessible URL is derived from the `menus` table:
 *   menus.url  →  menus.permission_name  →  role_has_permissions  →  roles
 *
 * No URL is hardcoded here.  Access control is managed entirely through
 * the Sidebar Manager (/sidebar/menus) and Role Manager (/admin/roles).
 *
 * Decision tree per request:
 *   1. Unauthenticated          → pass through (let `auth` handle redirect).
 *   2. Super Admin              → always allow.
 *   3. URL not in menus table   → allow (utility routes, AJAX, sub-routes).
 *   4. Menu has no permission_name → allow (item not yet configured).
 *   5. Permission assigned to no role → allow (safety net before seeder runs).
 *   6. Student (user_category=S)  → check Student-OT role's permissions in DB.
 *   7. All other users          → standard Spatie $user->can() check.
 */
class CheckMenuPermission
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // 1. Unauthenticated — pass through.
        if (! $user) {
            return $next($request);
        }

        // 2. Super Admin bypasses every check.
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        $path = strtolower(trim($request->path(), '/'));

        // 3. Resolve permission from the menus table (longest-prefix match).
        $permissionName = $this->resolvePermission($path);

        // No menu entry for this URL → allow (AJAX endpoint, sub-route, utility page).
        if ($permissionName === null) {
            return $next($request);
        }

        // 4. Menu matched but permission_name is empty → allow (not yet configured).
        if ($permissionName === '') {
            return $next($request);
        }

        // 5. Permission exists but is not assigned to any role yet
        //    (bootstrapping safety: allows full access before the seeder runs).
        if (! $this->permissionIsAssignedToAnyRole($permissionName)) {
            return $next($request);
        }

        // 6. Student — has no DB role assignment; check via the Student-OT role directly.
        if (($user->user_category ?? '') === 'S') {
            return $this->handleStudent($request, $next, $permissionName);
        }

        // 7. Standard Spatie permission check for every other role.
        if ($user->can($permissionName)) {
            return $next($request);
        }

        return $this->deny($request);
    }

    /**
     * Students never have rows in model_has_roles, so $user->can() always
     * returns false for them.  Instead we look up the 'Student-OT' role in the
     * DB and check its permissions directly.
     *
     * If the Student-OT role doesn't exist in DB yet (seeder not run):
     *   → allow, so students are not accidentally locked out.
     */
    private function handleStudent(Request $request, Closure $next, string $permissionName)
    {
        $studentOtRole = $this->getStudentOtRole();

        // Seeder hasn't created the role yet → backward-compatible allow.
        if (! $studentOtRole) {
            return $next($request);
        }

        if ($studentOtRole->hasPermissionTo($permissionName)) {
            return $next($request);
        }

        return $this->deny($request);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Find the permission_name of the longest-prefix-matching menu for $path.
     *
     * Returns:
     *   null   — no menu matched  (allow the request through).
     *   ''     — menu matched but has no permission_name (allow).
     *   string — the permission_name of the best-matching menu.
     */
    private function resolvePermission(string $path): ?string
    {
        $index   = $this->getMenuIndex();
        $bestLen = -1;
        $result  = null; // null = no match

        foreach ($index as $entry) {
            $menuPath = $entry['path'];
            if ($menuPath === '') {
                continue;
            }

            if ($path === $menuPath || str_starts_with($path, $menuPath . '/')) {
                if (strlen($menuPath) > $bestLen) {
                    $bestLen = strlen($menuPath);
                    $result  = $entry['permission']; // may be ''
                }
            }
        }

        return $result;
    }

    /**
     * Load and cache the menus table as a flat array of path → permission pairs.
     * Cache TTL: 5 minutes.  Run `php artisan cache:clear` after menu changes.
     *
     * The `menus` table stores URLs in the `route` column (same column used by
     * SidebarNavResolver).  Values may be a path ("/admin/foo"), a full URL
     * ("https://…/admin/foo"), a route name ("admin.foo"), or "#".
     * Route names (dot-notation, no slash) are skipped — they cannot be
     * matched against a request path.
     */
    private function getMenuIndex(): array
    {
        return Cache::remember('rbac_menu_permission_index', 300, function () {
            $rows = DB::table('menus')
                ->whereNull('deleted_at')
                ->where('is_active', 1)
                ->whereNotNull('route')
                ->where('route', '!=', '')
                ->where('route', '!=', '#')
                ->select('route', 'permission_name')
                ->get();

            $index = [];
            foreach ($rows as $row) {
                $route = trim((string) ($row->route ?? ''));

                if ($route === '' || $route === '#') {
                    continue;
                }

                // Strip query string first.
                if (str_contains($route, '?')) {
                    $route = explode('?', $route, 2)[0];
                }

                // Full URL — extract path component only.
                if (preg_match('#^https?://#i', $route)) {
                    $route = (string) parse_url($route, PHP_URL_PATH);
                }

                // Route name (dot-notation, no slash) — cannot match by path; skip.
                if (str_contains($route, '.') && ! str_contains($route, '/')) {
                    continue;
                }

                $path = strtolower(trim($route, '/'));

                // Strip a trailing all-numeric segment (hardcoded record IDs)
                // so "/admin/foo/1" matches as "/admin/foo".
                if (preg_match('#^(.+)/\d+$#', $path, $m)) {
                    $path = $m[1];
                }

                if ($path === '') {
                    continue;
                }

                $index[] = [
                    'path'       => $path,
                    'permission' => trim((string) ($row->permission_name ?? '')),
                ];
            }

            // Longest paths first so the loop finds the best match quickly.
            usort($index, fn($a, $b) => strlen($b['path']) <=> strlen($a['path']));

            return $index;
        });
    }

    /**
     * Returns true if at least one role has been granted this permission.
     * Cached per permission name for 5 minutes.
     */
    private function permissionIsAssignedToAnyRole(string $permissionName): bool
    {
        return Cache::remember('rbac_perm_has_role_' . md5($permissionName), 300, function () use ($permissionName) {
            return DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', $permissionName)
                ->where('permissions.guard_name', 'web')
                ->exists();
        });
    }

    /**
     * Load and cache the Student-OT Role model.
     * Cache key holds only the role id; the actual model is not stored.
     */
    private function getStudentOtRole(): ?Role
    {
        $roleId = Cache::remember('rbac_student_ot_role_id', 300, function () {
            return DB::table('roles')
                ->where('name', 'Student-OT')
                ->where('guard_name', 'web')
                ->value('id');
        });

        return $roleId ? Role::find($roleId) : null;
    }

    /**
     * Deny the request with HTTP 403.
     * AJAX callers receive JSON; browser requests get the rendered 403 view.
     */
    private function deny(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
