<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FcAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('fc_user_id')) {
            return redirect()->route('fc.login')->withErrors(['login' => 'Please login to continue.']);
        }

        return $next($request);
    }
}
