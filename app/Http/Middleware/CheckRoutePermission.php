<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckRoutePermission
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $routeName = Route::currentRouteName();

        // $excluded = ['dashboard', 'profile.index']; // add more if needed
        // if (in_array($routeName, $excluded)) {
        //     return $next($request);
        // }

        if ($user && $user->can($routeName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
