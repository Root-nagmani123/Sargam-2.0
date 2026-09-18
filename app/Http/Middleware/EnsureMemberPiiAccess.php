<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the member (employee) personal-data egress endpoints.
 *
 * ACCESS DECISION — this narrows an existing capability, so it is stated in
 * full rather than left to be inferred from the code:
 *
 *   Before: every route under /member was protected by `auth` and nothing
 *           else. Any authenticated account — including roles with no
 *           business relationship to HR data — could GET
 *           /member/export/pdf and receive every member's name, employee id,
 *           type, group, department, mobile and email, or GET
 *           /member/show/<id> and /member/print/<id> and receive one
 *           member's full profile sheet: date of birth, both addresses,
 *           father's name and personal email.
 *   After:  SIX endpoints require a Super Admin or the holder of a grantable
 *           permission. Four are the reads that hand out that data — show,
 *           print, export/{format} and the legacy excel-export. Two are the
 *           destructive WRITES — POST {id}/toggle-status and DELETE
 *           delete/{id} — which sat outside every gate until PR #309 round 12
 *           (F-038 / R11-001): any authenticated account could deactivate any
 *           member and then delete them, taking the member's user_credentials
 *           row and every EmployeeRoleMapping with it. Every other role keeps
 *           the listing grid and the create wizard, reaches the EDIT wizard
 *           for its own record only (see EnsureMemberRecordAccess), and loses
 *           the bulk and row-level personal-data reads, the status toggle and
 *           Delete — in the Action column as well as at the endpoint, so the
 *           screen does not offer a control the route will refuse.
 *
 * Why the split: the LISTING is an operational screen and stays open, exactly
 * as it was. The six gated endpoints are a different exposure — one GET
 * returns a whole roster, or one person's complete record, as a document that
 * leaves the application; one POST or DELETE removes an employee and their
 * login. Bulk and full-record extraction of personal data, and destroying the
 * record, are the privileged acts; working the grid is not.
 *
 * Why the two WRITES take `member.pii` and not `member.record`:
 * `member.record` admits an ordinary account to its OWN record, and destroy()
 * deletes that account's user_credentials row and every role mapping with it,
 * so the own-record branch would hand every user a working self-delete.
 * Deactivating and deleting an employee are administrative acts, so they take
 * the administrative entitlement.
 *
 * Why the PRE-EXISTING endpoints are gated too: `excel-export` and `show`
 * predate this change and return the same data as the two routes added here.
 * Gating only the new routes would have left the same rows reachable through
 * the old ones, which is a gate in name only.
 *
 * Why a role and not `can:` — `can:` has no Super Admin bypass in this
 * application: it 403s everyone unless the permission row exists AND a menus
 * row makes it grantable, which has silently locked administrators out of
 * whole modules here before. isSidebarPrivilegedUser() admits Super Admin
 * directly, and the named permission below is the widening path.
 *
 * REVERSIBLE WITHOUT A DEPLOY — the honest risk in a narrowing is that some
 * office was using one of these endpoints and nobody knew. The remedy must not
 * be to widen the role check in code, so the gate also admits the holder of a
 * named permission: grant PII_PERMISSION to a role and that role has these
 * endpoints back, with no code change and an audit trail in the permission
 * tables.
 *
 * THAT REMEDY IS ONLY REAL IF IT CAN BE PERFORMED, and the first version of
 * this gate named a permission no screen in this application could produce. The
 * role-assignment screen enumerates menus.permission_name, and a menu's
 * permission name is Str::slug($name, '_') — which strips dots, so the original
 * 'member.pii.read' came out as 'memberpiiread' and was grantable from nowhere.
 * The name is therefore slug-shaped now, and the migration
 * 2026_09_16_090000_add_member_pii_read_permission ships both halves the grant
 * needs: the Spatie permissions row, and a menus row under Employee that puts a
 * toggle for it on the role-assignment screen. That menus row carries
 * exclude_from_admin = 1, so it changes no administrator's sidebar — it exists
 * to make the capability grantable and auditable, not to add a screen.
 *
 * WHAT THIS PERMISSION IS NOT - it is not, today, a boundary, and the sentence
 * that used to stand here ("nothing holds this permission, so behaviour is
 * exactly Super Admin only until somebody grants it") described the contents of
 * a table rather than a control. Who may grant it is the missing half: POST
 * roles/permissions/{id} carries `auth` and nothing else, and
 * RoleController::assignPermission() firstOrCreate()s whatever permission name
 * it is handed and gives it to the role named in the URL, with no check on the
 * caller. So an account this gate refuses can grant itself the permission this
 * gate honours and come back through the front door - executed end to end and
 * recorded as PR #309 F-027, with the ungated endpoint itself as the root cause
 * (PR #317 L-8), owner Engineering lead. Fixing that endpoint is a separate
 * change: it defeats every permission-based gate in this application, not only
 * this one, and it must not be smuggled into a redesign branch.
 *
 * Read this gate, then, as what it demonstrably is: it removes a bulk personal
 * data egress from casual reach and puts an audit line on every served
 * download. It does not withstand a deliberate authenticated actor until L-8 is
 * closed.
 *
 * WHY THE LOOKUP BELOW IS WRAPPED - and it is not the reason this comment used
 * to give. A permission name that does not exist does NOT raise: Spatie's
 * PermissionRegistrar registers a Gate::before hook that calls
 * checkPermissionTo(), which is documented in its own source as "an alias to
 * hasPermissionTo(), but avoids throwing an exception" and catches
 * PermissionDoesNotExist to return false. So on a host where this release's
 * migration has not yet run, $user->can() is simply false and this gate closes
 * - no exception is ever raised for that cause. The catch is belt-and-braces
 * for the cases that DO throw: an unreachable cache backend, a database error,
 * or a host carrying no Spatie permission tables at all, where the query
 * raises something that is not PermissionDoesNotExist. Do not read the catch
 * as the thing that closes the gate on a missing permission; Spatie is.
 *
 * KNOWN WINDOW — isSidebarPrivilegedUser() resolves through hasRole(), which
 * reads the session's user_roles before it asks the role tables. A Super Admin
 * whose role is revoked mid-session keeps these reads until they log out. That
 * is every hasRole() caller's behaviour, not this gate's; it is recorded here
 * because this gate guards personal data, where the window costs more.
 *
 * Both branches are executed by MemberPiiAccessTest, through the real router:
 * a non-privileged user gets 403 on all four READ routes and on both WRITE
 * routes, and for the writes the member row is read either side of the call, so
 * a 403 that still wrote would fail the test rather than pass it. A privileged
 * user is passed through on all six, and the destroy path is driven to
 * completion inside a rolled-back transaction rather than stopped at the gate.
 * Both mutations write a logMemberPii() audit line, and the tests assert the
 * record - an unasserted audit line is an audit line nobody will notice losing.
 */
class EnsureMemberPiiAccess
{
    /**
     * Grant this to a role to restore member personal-data reads for it.
     *
     * Slug-shaped on purpose: menu permission names are produced by
     * Str::slug($name, '_'), which strips dots, so a dotted name can never
     * appear on the role-assignment screen and can never be granted there.
     */
    public const PII_PERMISSION = 'member_pii_read';

    public function handle(Request $request, Closure $next)
    {
        if (self::grantsAccess()) {
            return $next($request);
        }

        abort(403, 'You do not have access to member personal data.');
    }

    /**
     * The same decision the gate makes, available to the UI.
     *
     * The listing stays open to everyone, so without this its Action column
     * would keep offering View and Print to accounts the gate then refuses -
     * a dead button that reports a permission error, which reads as a fault
     * rather than as a boundary. One method, so the screen and the gate cannot
     * disagree about who is entitled.
     */
    public static function grantsAccess(): bool
    {
        return isSidebarPrivilegedUser() || (new self())->holdsPiiPermission();
    }

    private function holdsPiiPermission(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        try {
            return (bool) $user->can(self::PII_PERMISSION);
        } catch (\Throwable $e) {
            // The permission has not been created yet: not held, not an error.
            return false;
        }
    }
}
