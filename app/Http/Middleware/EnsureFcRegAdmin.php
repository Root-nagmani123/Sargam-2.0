<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Server-side mirror of the sidebar gate for fc-reg/admin/* screens
 * (resources/views/components/menu/fc-sidebar.blade.php: hasRole('Admin') ||
 * hasRole('Training-Induction')). The menu already hides these links from FC
 * officer trainees; this middleware stops them reaching the routes directly.
 *
 * 'Admin' is not an assignable DB role in this app (see isSidebarPrivilegedUser()
 * in app/helpers.php) — kept only to match the sidebar check verbatim. 'Super
 * Admin' is added here (it is not in the sidebar's own check) so the actual
 * super-admin role used elsewhere in the app is never locked out of this group.
 */
class EnsureFcRegAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! hasRole('Admin') && ! hasRole('Training-Induction') && ! hasRole('Super Admin')) {
            abort(403, 'You do not have access to FC registration admin.');
        }

        return $next($request);
    }
}
