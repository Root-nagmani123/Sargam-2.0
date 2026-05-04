<?php

namespace App\Http\Middleware;

use App\Services\FC\FcPostArrivalAccessService;
use Closure;
use Illuminate\Http\Request;

/**
 * Institution-wide OT × activity matrix — coordinators only.
 */
class EnsureFcActivityMatrixAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! app(FcPostArrivalAccessService::class)->isCoordinator()) {
            abort(403, 'Combined matrix is only available to coordinators.');
        }

        return $next($request);
    }
}
