<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Mess\MessPermission;

class CheckMessPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // Check if RBAC is enabled
        if (!config('mess.rbac_enabled', false)) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $userId = auth()->user()->pk;

        // Check if user has the required permission
        $hasPermission = MessPermission::userHasPermission($userId, $permission);

        if (!$hasPermission) {
            // Log unauthorized access attempt
            \Log::warning('Unauthorized mess access attempt', [
                'user_id' => $userId,
                'permission' => $permission,
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You do not have permission to perform this action.'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
