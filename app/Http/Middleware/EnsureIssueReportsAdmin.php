<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the admin Reported Issues console (B-01). index() already redirects
 * non-privileged users to their own list; this applies the same
 * isSidebarPrivilegedUser() check at the route level so the sibling actions
 * (show/status/destroy/export/export-excel/filter-options) can't be reached
 * directly by an ordinary authenticated user.
 */
class EnsureIssueReportsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! isSidebarPrivilegedUser()) {
            abort(403, 'You do not have access to the Reported Issues admin console.');
        }

        return $next($request);
    }
}
