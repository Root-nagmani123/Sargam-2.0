<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRoleAssigned
{
    /**
     * Handle an incoming request.
     * Redirect users with no role assigned to a "contact administrator" page.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Allow logout even if no role is assigned
        if ($request->is('logout')) {
            return $next($request);
        }

        if ($user && $user->user_category !== 'S' && !$user->roles()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No role assigned. Please contact your administrator.'
                ], 403);
            }

            return response()->view('errors.no-role', [], 403);
        }

        return $next($request);
    }
}
