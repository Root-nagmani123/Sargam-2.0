<?php

namespace Modules\GuestTaClaim\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\GuestTaClaim\Services\PermissionService;

/** Usage: ->middleware('gtc.can:claim.create|claim.forward') (any of). */
class EnsureGtcPermission
{
    public function __construct(protected PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $permissions)
    {
        $user = $request->user();
        abort_unless($user && $this->permissions->hasAny($user, explode('|', $permissions)), 403,
            'You don’t have permission for this part of Guest TA Claim. Ask the administrator to map it to you.');

        return $next($request);
    }
}
