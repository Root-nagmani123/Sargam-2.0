<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds a session to the browser it was created in, to blunt session hijacking
 * via a copied cookie (CWE-384 family). On the first request the session stores
 * a hash of the client User-Agent; every later request must present the same
 * one. A `laravel_session` cookie exported and imported into a different browser
 * therefore arrives with a different User-Agent, fails the check, and is
 * discarded — the holder is logged out and must sign in again.
 *
 * Scope / limits (documented deliberately):
 *  - This is a raiser of the bar, not an absolute control. An attacker who copies
 *    the User-Agent header alongside the cookie (trivial in a proxy tool) still
 *    matches. Full prevention of token replay is not possible for cookie sessions.
 *  - Only the User-Agent is bound, NOT the IP — IP binding logs out mobile users
 *    (changing IPs), NAT and VPN users constantly, so it is intentionally omitted.
 *  - Toggle with SESSION_BIND_USER_AGENT=false if a proxy rewrites User-Agents.
 */
class BindSessionToUserAgent
{
    private const KEY = '_ua_fp';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('session.bind_user_agent', true)) {
            return $next($request);
        }

        $fingerprint = hash('sha256', (string) $request->userAgent());
        $stored = $request->session()->get(self::KEY);

        if ($stored === null) {
            // First request on this session — bind it to the current browser.
            $request->session()->put(self::KEY, $fingerprint);
        } elseif (! hash_equals($stored, $fingerprint)) {
            // Cookie is being replayed from a different browser than it was issued
            // to. Tear the session down so the copied cookie grants nothing.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $isFc = $request->is('fc/*') || $request->is('fc-reg/*') || $request->is('registration/*');

            return redirect($isFc ? '/fc/login' : '/')
                ->with('error', 'Your session could not be verified on this device. Please sign in again.');
        }

        return $next($request);
    }
}
