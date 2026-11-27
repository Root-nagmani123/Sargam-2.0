<?php

namespace App\Http\Middleware;

use App\Models\AccountDeletionRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends any session still open for an account whose deletion was approved. Sessions are file-based,
 * so they cannot be found and deleted per user at approval time; instead the next request from such
 * a session is logged out here. Login itself is refused separately (LoginController).
 */
class LogoutClosedAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $pk = Auth::id(); // the session's id only - does not load the user

        if ($pk === null || ! isset(AccountDeletionRequest::closedAccountPks()[(int) $pk])) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'This account was deleted on request. Please contact the Computer Section, LBSNAA.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect('/')->with('error', $message);
    }
}
