<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use App\Services\MoodleSso;
use App\Services\FC\FcRosterAuthService;
use App\Services\FC\FcRegistrationIntentService;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $debug = config('app.debug');

        if ($debug) {
            Log::info('=== AUTHENTICATE MIDDLEWARE START ===', [
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'has_token' => $request->has('token'),
                'auth_check' => Auth::check(),
                'user' => Auth::check() ? Auth::user()->user_name : 'Not authenticated',
            ]);
        }
        // STEP 1: Moodle token login, only on the landing paths MoodleSso::tokenPaths()
        // lists. The token never expires and is not signed, so accepting it on every
        // route behind `auth` made one leaked token a login everywhere (PR #324 review
        // F-001). On any other path ?token= is ignored.
        $sso = app(MoodleSso::class);

        if ($request->has('token') && $request->is(...$sso->tokenPaths())) {
            try {
                // Fails closed with MOODLE_SHARED_KEY / MOODLE_SHARED_IV unset. A
                // non-string ?token[]= is refused rather than reaching urldecode(),
                // whose TypeError the catch (\Exception) below would not catch.
                $token = $request->query('token', $request->input('token'));
                $user = is_string($token) ? $sso->userFromToken(urldecode($token)) : null;

                if ($user) {
                    // Only a token that resolves to a DIFFERENT account ends the
                    // current session. Before, the mere presence of ?token= logged the
                    // user out, so any link with ?token= appended - even an empty one -
                    // signed a victim out at will (F-003).
                    if (Auth::check() && Auth::id() !== $user->getKey()) {
                        if ($debug) {
                            Log::info('Moodle token names another account; ending the current session', [
                                'old_user' => Auth::user()->user_name,
                            ]);
                        }
                        Auth::logout();
                        Session::flush();
                        Session::regenerate();
                    }

                    if (! Auth::check()) {
                        Session::put('user_roles', $sso->sessionRolesFor($user));
                        Auth::login($user);
                        session()->flash('success', 'Welcome back from Moodle!');
                    }

                    // Strip the token from the address bar.
                    if ($request->isMethod('get')) {
                        return redirect()->to($request->path());
                    }
                } elseif ($debug) {
                    Log::info('Moodle token in auth middleware did not resolve to an account');
                }
            } catch (\Exception $e) {
                Log::error('Error in middleware Moodle authentication: ' . $e->getMessage());
            }
        }

        // STEP 2: FC trainee session from /fc/login (fc_registration_master; not main /login)
        if (! Auth::check()) {
            app(FcRosterAuthService::class)->hydrateStagedUserFromSession();
        }

        // STEP 3: Check if user is now authenticated (main login, Moodle token, or FC roster session)
        if (Auth::check()) {
            return $next($request);
        }

        // STEP 4: User not authenticated, redirect to login
        session(['url.intended' => $request->fullUrl()]);

        return $this->unauthenticated($request, $guards);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    protected function redirectTo($request)
    {
        if ($request->is('fc-reg', 'fc-reg/*')) {
            // Registration falls back to the FC login, carrying the programme (?form=)
            // token so the trainee lands on the correct form login and returns to it.
            return route('fc.login', $this->fcLoginFormQuery($request));
        }

        return route('login');
    }

    /**
     * Build the ?form= query for the /fc/login fallback.
     *
     * This middleware runs before route-model binding, so the {form} route parameter is
     * still the raw encrypted token from the URL path — reusing it keeps the intent even
     * when the session has already expired. Falls back to the bound model / session /
     * existing query token via the intent service.
     *
     * @return array{form?: string}
     */
    private function fcLoginFormQuery($request): array
    {
        $routeToken = $request->route('form');
        if (is_string($routeToken) && $routeToken !== '') {
            return ['form' => $routeToken];
        }

        return app(FcRegistrationIntentService::class)->formQueryForHeaderLinks($request);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        // Registration (fc-reg/*) falls back to the FC login; every other area
        // falls back to the main Sargam portal login. redirectTo() encodes that rule.
        $redirectTo = $this->redirectTo($request);

        if ($request->expectsJson()) {
            throw new \Illuminate\Auth\AuthenticationException(
                'Unauthenticated.', $guards, $redirectTo
            );
        }

        return redirect()->guest($redirectTo);
    }
}