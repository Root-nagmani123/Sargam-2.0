<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the endpoints that decide what a ROLE can do.
 *
 * WHAT WAS WRONG. `POST roles/permissions/{id}` carried `auth` and nothing
 * else, and RoleController::assignPermission() did no checking of its own: it
 * took a role id from the URL, a permission name from the body,
 * `firstOrCreate()`d that permission if it did not exist, and granted it. So
 * any authenticated account could post the name of any permission — including
 * one it had just invented — to any role, including a role it holds, and come
 * back through the front door of every gate built on `can()`.
 *
 * That is not one module's bug. It is the reason a permission-based gate
 * anywhere in this application could not be relied on, including
 * `member_pii_read`, whose own middleware docblock had to say so. Recorded as
 * PR #309 F-027 and PR #317 L-8.
 *
 * WHAT THIS ENFORCES, and why it is not a new rule. The roles screen is
 * already Super-Admin-only: the sidebar offers `users` and `roles` to that role
 * alone. The endpoints behind the screen simply never checked. This middleware
 * closes the gap between what the navigation assumes and what the route
 * enforces — it does not narrow anything a role could legitimately reach by
 * following the UI.
 *
 * WHY isSidebarPrivilegedUser() AND NOT `can:`. `can:` has no Super Admin
 * bypass in this application. It refuses everybody unless the permission row
 * exists AND a menus row makes it grantable, which has locked administrators
 * out of whole modules here before (PR #306 broke sixteen routes exactly this
 * way). Worse, it would be circular: gating the permission-granting endpoint on
 * a permission that the endpoint itself grants. isSidebarPrivilegedUser()
 * resolves through hasRole('Super Admin'), which is the same test
 * EnsureMemberPiiAccess uses and the same role the roles screen is offered to.
 *
 * WHY THIS IS APPLIED BY CLASS NAME IN routes/web.php AND NOT BY A KERNEL
 * ALIAS. `$middlewareAliases` in app/Http/Kernel.php is the one file this
 * branch conflicts with `main` on, and the conflict is inside that array. A
 * gate registered there can be dropped by a careless conflict resolution
 * without anything failing loudly — the route simply stops being protected.
 * Referencing the class directly from the route file removes that failure mode
 * for this gate, at the cost of a longer line.
 *
 * SCOPE — MUTATIONS ONLY. The reads in the same route group (the roles listing,
 * the dashboard-card screen) are left alone deliberately. Reading the list of
 * roles is not privilege escalation, and gating the GETs risks breaking
 * navigation for an account that can already see the screen, which would be a
 * different defect rather than a fix.
 *
 * KNOWN WINDOW — hasRole() reads the session's user_roles before it asks the
 * role tables, so a Super Admin whose role is revoked mid-session keeps these
 * endpoints until they log out. That is every hasRole() caller's behaviour in
 * this application, not this gate's; it is written down here because the
 * endpoints behind this gate decide what every other gate is worth.
 *
 * Both branches are executed by RolePermissionAuthorisationTest: a non-
 * privileged account is refused on every mutating endpoint AND the database is
 * checked to prove nothing was granted, and a Super Admin is passed through.
 */
class EnsureRoleAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (isSidebarPrivilegedUser()) {
            return $next($request);
        }

        abort(403, 'You do not have access to role and permission administration.');
    }
}
