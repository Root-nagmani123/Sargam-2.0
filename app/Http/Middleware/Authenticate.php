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
        // Log middleware entry
        Log::info('=== AUTHENTICATE MIDDLEWARE START ===', [
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'has_token' => $request->has('token'),
            'token' => $request->token ? substr($request->token, 0, 30) . '...' : null,
            'auth_check' => Auth::check(),
            'user' => Auth::check() ? Auth::user()->user_name : 'Not authenticated',
            'session_id' => session()->getId()
        ]);

        // STEP 1: Check for Moodle token authentication if not already authenticated
        if ($request->has('token') && !Auth::check()) {
            Log::info('Moodle token found in auth middleware, attempting authentication');
            
            try {
                $key = config('services.moodle.key', '1234567890abcdef');
                $iv = config('services.moodle.iv', 'abcdef1234567890');

                // URL decode the token
                $decodedToken = urldecode($request->token);
                $base64Decoded = base64_decode($decodedToken);
                
                Log::info('Token processing in middleware', [
                    'decoded_token' => $decodedToken,
                    'base64_success' => $base64Decoded !== false
                ]);
                
                if ($base64Decoded !== false) {
                    // Decrypt
                    $username = openssl_decrypt(
                        $base64Decoded,
                        'AES-128-CBC',
                        $key,
                        0,
                        $iv
                    );
                    
                    Log::info('Decryption result in middleware', [
                        'username' => $username,
                        'success' => $username !== false
                    ]);
                    
                    if ($username && $username !== false) {
                        // Find user
                        $user = User::where('user_name', trim($username))->first();
                        
                        if ($user) {
                            Log::info('User found in middleware, logging in', [
                                'user_id' => $user->user_id,
                                'user_name' => $user->user_name
                            ]);
                            
                              $roles = ['Student-OT'];
                            Session::put('user_roles', $roles);
                            // Login the user
                            Auth::login($user);
                            
                            // Store success in session
                            session()->flash('success', 'Welcome back from Moodle!');
                            
                            Log::info('Login successful in middleware', [
                                'auth_check_after' => Auth::check(),
                                'user' => Auth::check() ? Auth::user()->user_name : 'No user'
                            ]);
                            
                            // Remove token from URL to avoid issues
                            if ($request->isMethod('get')) {
                                Log::info('Redirecting to remove token from URL');
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
            Log::info('User authenticated in middleware, allowing access', [
                'user' => Auth::user()->user_name,
                'user_id' => Auth::id()
            ]);
            return $next($request);
        }

        // STEP 3: User not authenticated, redirect to login
        Log::warning('User not authenticated in middleware, redirecting to login');
        
        // Store intended URL
        session(['url.intended' => $request->fullUrl()]);
        
        Log::info('Stored intended URL in session', [
            'intended_url' => session('url.intended')
        ]);

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
        Log::info('Calling unauthenticated method');
        
        if ($request->expectsJson()) {
            Log::info('Request expects JSON, returning 401');
            throw new \Illuminate\Auth\AuthenticationException(
                'Unauthenticated.', $guards, $this->redirectTo($request)
            );
        }
        
        Log::info('Redirecting to login page');
        return redirect()->guest(route('login'));
    }
}