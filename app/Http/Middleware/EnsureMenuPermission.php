<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate a route on the same Spatie permission the sidebar already gates its screen on.
 *
 * Deliberately NOT Laravel's `can:` middleware. This application registers no
 * Gate::before, so `can:` has no Super Admin bypass — gating with it denies every
 * user including Super Admin whenever the permission row happens not to exist, which
 * is how 16 routes were taken offline in PR #306. This mirrors EnsureFcRegAdmin
 * instead: Super Admin always passes, everyone else needs the named permission.
 *
 * The permission passed in is the `menus.permission_name` of the screen being
 * protected, so a user who can see a screen in the sidebar can also export it, and
 * the two can never drift apart:
 *
 *     Route::get('roles/export', ...)->middleware('menu.permission:roles');
 *
 * Several may be listed; holding ANY one of them admits the request.
 */
class EnsureMenuPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        // Super Admin is never gated — same rule the sidebar itself applies.
        if (function_exists('isSidebarPrivilegedUser') && isSidebarPrivilegedUser()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            abort(403, 'You do not have access to this resource.');
        }

        // A route wired as 'menu.permission' with no argument is a developer error.
        // Fail CLOSED. An earlier draft let it through as authenticated-only on the
        // grounds that nobody should be locked out by a wiring mistake — but that
        // trades a loud, first-test-run failure for a route that looks gated in the
        // route file and is not, which is the exact defect this middleware exists to
        // remove. Availability does not outrank correctness inside an authorisation
        // gate; Super Admin is already admitted above, so this cannot lock out the
        // person who would need to fix it.
        if (empty($permissions)) {
            abort(403, 'This route is gated by menu.permission but names no permission.');
        }

        $held = $user->getAllPermissions()->pluck('name');

        foreach ($permissions as $permission) {
            if ($held->contains($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to export this data.');
    }
}
