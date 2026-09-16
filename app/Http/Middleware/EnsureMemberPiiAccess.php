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
 *   After:  the four endpoints that hand out that data — show, print,
 *           export/{format} and the legacy excel-export — require a Super
 *           Admin or the holder of a grantable permission. Every other role
 *           keeps the listing grid, the create/edit wizard and its own
 *           profile, and loses the bulk and row-level personal-data reads.
 *
 * Why the split: the LISTING is an operational screen and stays open, exactly
 * as it was. The four gated endpoints are a different exposure — one GET
 * returns a whole roster, or one person's complete record, as a document that
 * leaves the application. Bulk and full-record extraction of personal data is
 * the privileged act; working the grid is not.
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
 * Nothing holds this permission today — it does not have to exist for the gate
 * to work — so behaviour is exactly "Super Admin only" until someone decides
 * otherwise. The lookup is wrapped because Spatie raises rather than returning
 * false when a permission name has never been defined.
 *
 * KNOWN WINDOW — isSidebarPrivilegedUser() resolves through hasRole(), which
 * reads the session's user_roles before it asks the role tables. A Super Admin
 * whose role is revoked mid-session keeps these reads until they log out. That
 * is every hasRole() caller's behaviour, not this gate's; it is recorded here
 * because this gate guards personal data, where the window costs more.
 *
 * Both branches are executed by MemberPiiAccessTest: a non-privileged user gets
 * 403 through the real router on all four routes, a privileged one is passed
 * through.
 */
class EnsureMemberPiiAccess
{
    /** Grant this to a role to restore member personal-data reads for it. */
    public const PII_PERMISSION = 'member.pii.read';

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
