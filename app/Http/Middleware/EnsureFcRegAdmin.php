<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for the fc-reg/admin/sms/* bulk-send screens (H-02). Mirrors the
 * dynamic-permission pattern used by fc.activity.coordinator instead of a
 * hardcoded role list: Super Admin always passes (isSidebarPrivilegedUser()),
 * everyone else needs the 'bulk_smsemail' Spatie permission — the same
 * permission the "Assign Permission (FC Admin)" screen and the sidebar
 * (App\Services\SidebarMenu\MenuService) already gate this menu item on.
 */
class EnsureFcRegAdmin
{
    private const PERMISSION = 'bulk_smsemail';

    public function handle(Request $request, Closure $next)
    {
        if (isSidebarPrivilegedUser()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user || ! $user->getAllPermissions()->contains('name', self::PERMISSION)) {
            abort(403, 'You do not have access to FC registration admin.');
        }

        return $next($request);
    }
}
