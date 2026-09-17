<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the OT / LBSNAA directory downloads.
 *
 * ACCESS DECISION — this narrows an existing capability, so it is stated in
 * full rather than left to be inferred from the code:
 *
 *   Before: the directory exports were `?export=csv|excel` on the page routes
 *           themselves, so ANY authenticated user could download either
 *           roster. There was no check beyond `auth`.
 *   After:  a Super Admin may download, and so may the holder of the grantable
 *           `directory.export` permission — see REVERSIBLE WITHOUT A DEPLOY
 *           below, which is the other half of this decision and not an
 *           afterthought. Nothing holds that permission today, so in practice
 *           this reads "Super Admin only" until somebody grants it. Every other
 *           role keeps the grids — search, sort, paging, the on-screen contact
 *           details — and loses the file.
 *
 * Why the split: the PAGES are a directory and are meant to be readable by
 * everyone. The EXPORTS are a different exposure — one GET returns the whole
 * staff roster's home address, residence phone, mobile and personal email as
 * a file that leaves the application entirely. This gate removes the convenient
 * download, and that is all it claims to do.
 *
 * WHAT THIS GATE IS NOT. It does not stop bulk extraction, and the sentence it
 * used to carry — "bulk extraction of personal data is the privileged act, not
 * looking someone up" — read as a security property when it is only a design
 * intent. /directory/lbsnaa/data stays open to every authenticated user by
 * design; it is a JSON feed returning address, mobile, residence number and
 * email per row, and `start` has no upper bound, so three scripted requests at
 * length=200 reconstruct the roster this gate refuses as a file — and without
 * the audit line the gated path writes. The change is still an improvement in
 * both directions (the base rendered all 443 rows into the page markup on every
 * load, with no paging at all, and wrote no audit line). But if bulk extraction
 * genuinely needs gating, that is a change to the FEED routes with its own
 * decision record, not something to read into this one.
 *
 * Why a role FIRST: the role check mirrors EnsureIssueReportsAdmin, which gates
 * the equally PII-bearing Reported Issues downloads with the same privilege
 * check, so the two PII download paths stay one rule. The named permission
 * beside it (the EnsureFcRegAdmin shape) is the widening path for a
 * non-Super-Admin office that turns out to need the roster file — it is already
 * wired, so widening never means editing this class.
 *
 * Both branches are executed by DirectoryExportGuardTest: a non-privileged
 * user gets 403 through the real router, a privileged one is passed through.
 *
 * KNOWN WINDOW — isSidebarPrivilegedUser() resolves through hasRole(), which
 * reads the session's user_roles before it asks the role tables. So a Super
 * Admin whose role is revoked mid-session keeps these downloads until they log
 * out. That is every hasRole() caller's behaviour, not this gate's, and it is
 * tracked repository-wide as PR311-L-2; it is recorded here because this gate
 * guards bulk PII, where the window costs more than it does elsewhere.
 *
 * REVERSIBLE WITHOUT A DEPLOY - the narrowing removes a capability every
 * authenticated user had, and the honest risk is that some office was using the
 * roster CSV and nobody knew. If that turns out to be so, the remedy must not be
 * to widen the role check in code, which is what the decision above argues
 * against. So the gate also admits the holder of a named permission: grant
 * EXPORT_PERMISSION to a role and that role has directory downloads back, with
 * no code change and an audit trail in the permission tables.
 *
 * Nothing holds this permission today - it does not have to exist for the gate
 * to work - so behaviour is exactly "Super Admin only" until someone decides
 * otherwise. The lookup is wrapped because Spatie raises rather than returning
 * false when a permission name has never been defined.
 */
class EnsureDirectoryExportAccess
{
    /** Grant this to a role to restore directory downloads for it. */
    public const EXPORT_PERMISSION = 'directory.export';

    public function handle(Request $request, Closure $next)
    {
        if (isSidebarPrivilegedUser() || $this->holdsExportPermission()) {
            return $next($request);
        }

        abort(403, 'You do not have access to directory downloads.');
    }

    private function holdsExportPermission(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        try {
            return (bool) $user->can(self::EXPORT_PERMISSION);
        } catch (\Throwable $e) {
            // The permission has not been created yet: not held, not an error.
            return false;
        }
    }
}
