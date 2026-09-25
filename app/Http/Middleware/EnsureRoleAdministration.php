<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the screens that decide what every other gate is worth: role CRUD,
 * the Roles -> Assign permissions matrix, the role dashboard-card assignment,
 * and the sidebar menu rows those permission names are derived from.
 *
 * WHY THIS EXISTS. Before this, `POST roles/permissions/{id}` carried `web` and
 * Authenticate and nothing else, and RoleController::assignPermission() called
 * Permission::firstOrCreate() on whatever name it was posted and granted it to
 * the role in the URL without looking at the caller. Any authenticated account
 * could therefore hand its own role any permission in one request, which defeats
 * every permission-based gate in this application rather than one of them. It
 * was found while reviewing an unrelated export gate that happened to rest on a
 * named permission, and recorded as PR #317 L-8; the menu half is L-9.
 *
 * WHY THE CHECK IS A ROLE AND NOT A PERMISSION. Gating this on a permission
 * would be circular - the endpoint's own purpose is handing out permissions -
 * and `can:` middleware has no Super Admin bypass in this codebase, so it would
 * refuse the one account that must always be able to repair a broken grant.
 * isSidebarPrivilegedUser() is hasRole('Super Admin'), which is also exactly who
 * can reach these screens today: the `roles`, `users` and `menus` permissions
 * are each held by the Super Admin role alone (measured against the application
 * database), so this narrows nobody's access in practice - it just stops the
 * screens being reachable without them.
 *
 * IT ALSO GUARDS ROLE ASSIGNMENT (UserController::assignRole/assignRoleSave).
 * A guard that tests a role is only as strong as the weakest writer of role
 * assignments. `POST admin/users/assign-role-save` called syncRoles() on any
 * user_id and role ids it was sent, so without this any account could grant
 * itself Super Admin and then pass this check everywhere. Guarding the
 * permission matrix alone closes the permission self-grant, not self-elevation.
 *
 * WHY IT IS APPLIED IN THE CONTROLLER CONSTRUCTOR rather than on the routes:
 * RoleController is mounted twice, once bare and once under the admin/ prefix,
 * and a route-level guard protects one URL rather than the method behind it.
 * The constructor covers every mount, including any added later.
 *
 * KNOWN WINDOW - hasRole() reads the session's user_roles before it asks the
 * role tables, so a Super Admin whose role is revoked mid-session keeps these
 * screens until they log out. That is every hasRole() caller's behaviour and is
 * tracked repository-wide as PR311-L-2, not introduced here.
 */
class EnsureRoleAdministration
{
    public function handle(Request $request, Closure $next)
    {
        if (! isSidebarPrivilegedUser()) {
            abort(403, 'You do not have access to role and permission administration.');
        }

        return $next($request);
    }
}
