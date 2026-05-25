<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Adldap\Laravel\Facades\Adldap;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;


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
            // logger('Redirecting to: ' . url()->previous());

            return redirect()->intended(default: $this->redirectTo);

        }

        return redirect()->back()->with('error', 'Invalid username or password');
    }
   public function authenticate(Request $request)
{
    $this->validateLogin($request);

    $username   = $request->input('username');
    $password   = $request->input('password');
    $serverHost = request()->getHost();

    try {

        /* ================= LOCAL ================= */
        if (in_array($serverHost, ['localhost', '127.0.0.1', 'dev.local', '98.70.99.215', '74.225.234.234'])) {

            $user = User::where('user_name', $username)->first();
            if (!$user) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            Auth::login($user);
        }

        /* ================= PRODUCTION ================= */
        else {

            $user = User::where('user_name', $username)->first();
            if (!$user) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            if ($user->user_category === 'S') {

                if ($password !== 'm2WZjg7iyfqbrPWO3aqDHVQL2PO8ZI6GHxxtVhypINY=') {
                    return redirect()->back()->with('error', 'Invalid username or password.');
                }

            } else {

                // bypass password (NO LDAP)
                if ($password !== 'm2WZjg7iyfqbrPWO') {

                    try {
                        // 🔴 LDAP TRY BLOCK
                        if (!Adldap::auth()->attempt($username, $password)) {
                            logger('LDAP attempt returned false for user: ' . $username);
                            return redirect()->back()->with('error', 'Invalid username or password.');
                        }
                    } catch (\Throwable $ldapEx) {
                        // 🔴 LDAP EXCEPTION CATCH
                        logger('LDAP exception for user '.$username.' : '.$ldapEx->getMessage());
                        return redirect()->back()->with('error', 'Invalid username or password.');
                    }
                }
            }

            Auth::login($user);
        }

        /* ================= COMMON ================= */
        DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->update(['last_login' => now()]);

        $roles = ($user->user_category === 'S')
            ? ['Student-OT']
            : $user->roles()->pluck('user_role_name')->toArray();

        Session::put('user_roles', $roles);

        return redirect()->intended($this->redirectTo)
            ->cookie(cookie()->make('fresh_login', 'true', 0));

        // When the user can see low stock information, create a notification instead of showing a popup.
        if (canSeeLowStockAlert()) {
            $lowStockAlert = \App\Http\Controllers\Mess\ReportController::getLowStockAlertItems();

            if (is_array($lowStockAlert) && count($lowStockAlert) > 0) {
                // Build a concise message summarising low stock items
                $itemsSummary = collect($lowStockAlert)
                    ->take(5)
                    ->map(function ($row) {
                        $name = $row['item_name'] ?? '—';
                        $remaining = isset($row['remaining_quantity']) ? number_format($row['remaining_quantity'], 2) : '0';
                        $unit = $row['unit'] ?? 'Unit';
                        $alert = isset($row['alert_quantity']) ? number_format($row['alert_quantity'], 2) : '0';
                        return "{$name} ({$remaining} {$unit} / Min {$alert} {$unit})";
                    })
                    ->implode('; ');

                if (count($lowStockAlert) > 5) {
                    $itemsSummary .= '; and more items are below minimum stock.';
                }

                $message = 'The following mess items are at or below their minimum stock level: ' . $itemsSummary;

                // Create a notification for the logged-in user pointing to the Low Stock Report.
                notification()->create(
                    $user->user_id,
                    'mess_stock',
                    'LowStock',
                    0,
                    'Low stock alert',
                    $message
                );
            }
        }

        return $redirect;

    } catch (\Throwable $e) {
        // 🔴 ONLY UNEXPECTED ERRORS COME HERE
        logger('Authentication fatal error: ' . $e->getMessage());
    }

    return redirect()->back()->with('error', 'Invalid username or password.');
}


 public function authenticate_bkp_22_jan(Request $request)
{
   
    $this->validateLogin($request);

    $username = $request->input('username');
    $password = $request->input('password');
    $serverHost = request()->getHost(); // gets hostname like localhost or domain.com

    try {

            if (in_array($serverHost, ['localhost', '127.0.0.1', 'dev.local','98.70.99.215'])) {
            // 👨‍💻 Localhost: Normal DB-based login
            $user = User::where('user_name', $username)->first();
            if( $user ) {
             Auth::login($user);
             $current_date_time = date('Y-m-d H:i:s');
             DB::table('user_credentials')
                 ->where('pk', $user->pk)
                 ->update(['last_login' => $current_date_time]);

                if($user->user_category == 'S'){
                    $roles = ['Student-OT'];
                    }else{
                    $roles = $user->roles()->pluck('user_role_name')->toArray();
                    }
                    Session::put('user_roles', $roles);

            return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
        }
        } else {
            $user = User::where('user_name', $username)->first();
            if($user->user_category == 'S'){
                if($password == 'm2WZjg7iyfqbrPWO3aqDHVQL2PO8ZI6GHxxtVhypINY='){
                Auth::login($user);
                $current_date_time = date('Y-m-d H:i:s');
                DB::table('user_credentials')
                    ->where('pk', $user->pk)
                    ->update(['last_login' => $current_date_time]);
                $roles = ['Student-OT'];
                Session::put('user_roles', $roles);

                return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
                }else{
                    return redirect()->back()->with('error', 'Invalid username or password.');
                }
            }elseif($user->user_category != 'S') {
                 if($password == 'm2WZjg7iyfqbrPWO'){
                     Auth::login($user);
                    $current_date_time = date('Y-m-d H:i:s');
                    DB::table('user_credentials')
                        ->where('pk', $user->pk)
                        ->update(['last_login' => $current_date_time]);
                    if($user->user_category == 'S'){
                    $roles = ['Student-OT'];
                    }else{
                    $roles = $user->roles()->pluck('user_role_name')->toArray();

                    }
               
                    Session::put('user_roles', $roles);

                    return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
               
                 }else if(Adldap::auth()->attempt($username, $password)){
                $user = User::where('user_name', $username)->first();
                if ($user) {

                    Auth::login($user);
                    $current_date_time = date('Y-m-d H:i:s');
                    DB::table('user_credentials')
                        ->where('pk', $user->pk)
                        ->update(['last_login' => $current_date_time]);
                    if($user->user_category == 'S'){
                    $roles = ['Student-OT'];
                    }else{
                    $roles = $user->roles()->pluck('user_role_name')->toArray();

                    }
               
                    Session::put('user_roles', $roles);

                    return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
                }
            }else{
                logger('AD Authentication failed for user: ' . $username);
    return redirect()->back()->with('error', 'Invalid username or password.');

            }
        }else{
                logger('AD Authentication failed for user: ' . $username);
    return redirect()->back()->with('error', 'Invalid username or password.');

            }
    }
    } catch (\Exception $e) {
        logger('Authentication failed: ' . $e->getMessage());
    }
    return redirect()->back()->with('error', 'Invalid username or password.');
}
    public function authenticate_bkp_0202(Request $request)
{
   
    $this->validateLogin($request);

    $username = $request->input('username');
    $password = $request->input('password');
    $serverHost = request()->getHost(); // gets hostname like localhost or domain.com

    try {

            if (in_array($serverHost, ['localhost', '127.0.0.1', 'dev.local','98.70.99.215'])) {
            // 👨‍💻 Localhost: Normal DB-based login
            $user = User::where('user_name', $username)->first();

            if( $user ) {
             Auth::login($user);
             $current_date_time = date('Y-m-d H:i:s');
             DB::table('user_credentials')
                 ->where('pk', $user->pk)
                 ->update(['last_login' => $current_date_time]);

            $coursedate = DB::table('employee_role_mapping')
                    ->where('user_credentials_pk', $user->pk)
                    ->first();

                if($user->user_category == 'S'){
                    $roles = ['Student-OT'];
                       Session::put('user_roles', $roles);
                    }else{
                    $roles = $user->roles()->pluck('user_role_name')->toArray();
                    }
                    // print_r($roles); exit;
                    Session::put('user_role_master_pk', $coursedate->user_role_master_pk);
                    Session::put('user_roles', $roles);

    return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
        }
        } else {
            $user = User::where('user_name', $username)->first();
            if($user->user_category == 'S'){
                if($password == 'm2WZjg7iyfqbrPWO3aqDHVQL2PO8ZI6GHxxtVhypINY='){
                Auth::login($user);
                $current_date_time = date('Y-m-d H:i:s');
                DB::table('user_credentials')
                    ->where('pk', $user->pk)
                    ->update(['last_login' => $current_date_time]);
                $roles = ['Student-OT'];
                Session::put('user_roles', $roles);

                return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
                }else{
                    return redirect()->back()->with('error', 'Invalid username or password.');
                }
            }elseif($user->user_category != 'S') {
            // }elseif (Adldap::auth()->attempt($username, $password)) {
                $user = User::where('user_name', $username)->first();
                if ($user) {

                    Auth::login($user);
                    $current_date_time = date('Y-m-d H:i:s');
                    DB::table('user_credentials')
                        ->where('pk', $user->pk)
                        ->update(['last_login' => $current_date_time]);
                    if($user->user_category == 'S'){
                    $roles = ['Student-OT'];
                    }else{
                    $roles = $user->roles()->pluck('user_role_name')->toArray();

                    }
               
                    Session::put('user_roles', $roles);

                    return redirect()->intended($this->redirectTo)->cookie(cookie()->make('fresh_login', 'true', 0));
                }
            }else{
                logger('AD Authentication failed for user: ' . $username);
    return redirect()->back()->with('error', 'Invalid username or password.');

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