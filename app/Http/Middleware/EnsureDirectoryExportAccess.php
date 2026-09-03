<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the OT / LBSNAA directory downloads (PR #317 F-003).
 *
 * The directory PAGES stay open to every authenticated user — that is the point
 * of a directory. The EXPORTS are a different exposure: one GET returns the
 * whole staff roster's home address, residence phone, mobile and personal email
 * as a file. Mirrors EnsureIssueReportsAdmin, which gates the equally
 * PII-bearing Reported Issues downloads with the same privilege check.
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
