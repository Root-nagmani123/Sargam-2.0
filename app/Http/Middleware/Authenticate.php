<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use App\Models\User;

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
        if ($request->has('token') && Auth::check()) {
                if ($debug) {
                    Log::info('Token present but user already logged in, logging out old user', [
                        'old_user' => Auth::user()->user_name
                    ]);
                }
                Auth::logout();
                Session::flush();             
                Session::regenerate();        
            }


        // STEP 1: Check for Moodle token authentication if not already authenticated
        if ($request->has('token') && !Auth::check()) {
            if ($debug) {
                Log::info('Moodle token found in auth middleware, attempting authentication');
            }
            try {
                $key = config('services.moodle.key', '1234567890abcdef');
                $iv = config('services.moodle.iv', 'abcdef1234567890');

                $decodedToken = urldecode($request->token);
                $base64Decoded = base64_decode($decodedToken);

                if ($base64Decoded !== false) {
                    $username = openssl_decrypt(
                        $base64Decoded,
                        'AES-128-CBC',
                        $key,
                        0,
                        $iv
                    );

                    if ($username && $username !== false) {
                        $user = User::where('user_name', trim($username))->first();

                        if ($user) {
                            $roles = ['Student-OT'];
                            Session::put('user_roles', $roles);
                            Auth::login($user);
                            session()->flash('success', 'Welcome back from Moodle!');

                            if ($request->isMethod('get')) {
                                return redirect()->to($request->path());
                            }
                        } else {
                            Log::error('User not found in middleware', ['username' => $username]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error in middleware Moodle authentication: ' . $e->getMessage());
            }
        }

        // STEP 2: Check if user is now authenticated (either via token or session)
        if (Auth::check()) {
            return $next($request);
        }

        // STEP 3: User not authenticated, redirect to login
        session(['url.intended' => $request->fullUrl()]);

        return $this->unauthenticated($request, $guards);
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
        if ($request->expectsJson()) {
            throw new \Illuminate\Auth\AuthenticationException(
                'Unauthenticated.', $guards, $this->redirectTo($request)
            );
        }

        return redirect()->guest(route('login'));
    }
}