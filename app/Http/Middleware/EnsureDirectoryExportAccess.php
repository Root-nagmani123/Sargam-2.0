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
 *   After:  only a Super Admin may download. Every other role keeps the
 *           grids — search, sort, paging, the on-screen contact details —
 *           and loses the file.
 *
 * Why the split: the PAGES are a directory and are meant to be readable by
 * everyone. The EXPORTS are a different exposure — one GET returns the whole
 * staff roster's home address, residence phone, mobile and personal email as
 * a file that leaves the application entirely. Bulk extraction of personal
 * data is the privileged act, not looking someone up.
 *
 * Why a role and not a permission: this mirrors EnsureIssueReportsAdmin,
 * which gates the equally PII-bearing Reported Issues downloads with the same
 * privilege check, so the two PII download paths stay one rule. If a
 * non-Super-Admin office is later found to need the roster file, swap this
 * for a named permission (the EnsureFcRegAdmin shape) rather than widening
 * the role — and record that as its own decision.
 *
 * Both branches are executed by DirectoryExportGuardTest: a non-privileged
 * user gets 403 through the real router, a privileged one is passed through.
 */
class EnsureDirectoryExportAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! isSidebarPrivilegedUser()) {
            abort(403, 'You do not have access to directory downloads.');
        }

        return $next($request);
    }
}
