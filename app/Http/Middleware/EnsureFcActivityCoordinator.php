<?php

namespace App\Http\Middleware;

use App\Services\FC\FcPostArrivalAccessService;
use Closure;
use Illuminate\Http\Request;

class EnsureFcActivityCoordinator
{
    public function handle(Request $request, Closure $next)
    {
        $svc = app(FcPostArrivalAccessService::class);
        if (! $svc->canManageActivitySetup()) {
            abort(403, 'You do not have access to FC activity setup (departments and activity master).');
        }

        return $next($request);
    }
}
