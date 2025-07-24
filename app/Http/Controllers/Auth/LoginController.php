<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Adldap\Laravel\Facades\Adldap;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    public function username()
    {
        return 'username';
    }
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function authenticate_bkp(Request $request) {

        $this->validateLogin($request);

        $loginData = $request->only('username'); // , 'password'

        $user = User::where('user_name', $request->username)->first();
        
        if( $user ) {
            Auth::login($user);
            logger('Redirecting to: ' . url()->previous());

            return redirect()->intended(default: $this->redirectTo);

        }

        return redirect()->back()->with('error', 'Invalid username or password');
    }

    public function authenticate(Request $request)
{
    $this->validateLogin($request);

    $username = $request->input('username');
    $password = $request->input('password');

    $serverHost = request()->getHost(); // gets hostname like localhost or domain.com

    try {
       
            if (in_array($serverHost, ['localhost', '127.0.0.1', 'dev.local'])) {
            // 👨‍💻 Localhost: Normal DB-based login
            $user = User::where('user_name', $username)->first();

            if( $user ) {
            Auth::login($user);
            logger('Redirecting to: ' . url()->previous());

            return redirect()->intended(default: $this->redirectTo);

        }

        } else {
            // 🌐 Production: LDAP authentication
            if (Adldap::auth()->attempt($username, $password)) {
                $user = User::where('user_name', $username)->first();

                if ($user) {
                    Auth::login($user);
                    return redirect()->intended($this->redirectTo);
                }
            }
        }

    } catch (\Exception $e) {
        logger('Authentication failed: ' . $e->getMessage());
    }

    return redirect()->back()->with('error', 'Invalid username or password.');
}
    protected function validateLogin(Request $request) {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }
    
}
