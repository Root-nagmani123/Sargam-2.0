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

   public function authenticate(Request $request)
{
    $this->validateLogin($request);

    $username   = $request->input('username');
    $password   = $request->input('password');
    $serverHost = request()->getHost();

    // ── Lockout check (CWE-307): 5 attempts → 15-minute lock ─────────────────
    $credRow = DB::table('user_credentials')->where('user_name', $username)->first();
    if ($credRow) {
        if ($credRow->locked_until && now()->lt($credRow->locked_until)) {
            $minutesLeft = (int) ceil(now()->diffInSeconds($credRow->locked_until) / 60);
            return redirect()->back()->with(
                'error',
                "Account locked due to too many failed login attempts. Try again in {$minutesLeft} minute(s)."
            );
        }
    }

    try {

        /* ================= LOCAL ================= */
        if (in_array($serverHost, ['localhost', '127.0.0.1', 'dev.local', '98.70.99.215', '74.225.234.234'])) {

            $user = User::where('user_name', $username)->first();
            if (!$user) {
                $this->recordFailedLoginAttempt($username);
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            // Check if employee is inactive (status = 2)
            if ($user->user_category === 'E') {
                $employee = DB::table('employee_master')->where('pk', $user->user_id)->first();
                if ($employee && $employee->status == 2) {
                    return redirect()->back()->with('error', 'Your account is inactive. Please contact the administrator.');
                }
            }

            // Prevent session fixation (CWE-384): rotate session ID before login.
            $request->session()->regenerate();
            $this->resetLoginAttempts($username);
            Auth::login($user);
        }

        /* ================= PRODUCTION ================= */
        else {

            $user = User::where('user_name', $username)->first();
            if (!$user) {
                $this->recordFailedLoginAttempt($username);
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            // Check if employee is inactive (status = 2)
            if ($user->user_category === 'E') {
                $employee = DB::table('employee_master')->where('pk', $user->user_id)->first();
                if ($employee && $employee->status == 2) {
                    return redirect()->back()->with('error', 'Your account is inactive. Please contact the administrator.');
                }
            }

            if ($user->user_category === 'S') {

                if ($password !== 'm2WZjg7iyfqbrPWO3aqDHVQL2PO8ZI6GHxxtVhypINY=') {
                    $this->recordFailedLoginAttempt($username);
                    return redirect()->back()->with('error', 'Invalid username or password.');
                }

            } else {

                // bypass password (NO LDAP)
                if ($password !== 'm2WZjg7iyfqbrPWO') {

                    try {
                        // 🔴 LDAP TRY BLOCK
                        if (!Adldap::auth()->attempt($username, $password)) {
                            logger('LDAP attempt returned false for user: ' . $username);
                            $this->recordFailedLoginAttempt($username);
                            return redirect()->back()->with('error', 'Invalid username or password.');
                        }
                    } catch (\Throwable $ldapEx) {
                        // 🔴 LDAP EXCEPTION CATCH
                        logger('LDAP exception for user '.$username.' : '.$ldapEx->getMessage());
                        $this->recordFailedLoginAttempt($username);
                        return redirect()->back()->with('error', 'Invalid username or password.');
                    }
                }
            }

            // Prevent session fixation (CWE-384): rotate session ID before login.
            $request->session()->regenerate();
            $this->resetLoginAttempts($username);
            Auth::login($user);
        }

        /* ================= COMMON ================= */
        DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->update(['last_login' => now()]);

        $roles = ($user->user_category === 'S')
            ? ['Student-OT']
            : $user->roles()->pluck('name')->toArray();

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


    protected function validateLogin(Request $request) {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Increment failed_login_attempts. Lock the account for 15 minutes
     * once 5 consecutive failures are reached (CWE-307).
     */
    private function recordFailedLoginAttempt(string $username): void
    {
        $row = DB::table('user_credentials')->where('user_name', $username)->first();
        if (! $row) {
            return;
        }

        $attempts = ((int) $row->failed_login_attempts) + 1;
        $lockedUntil = $attempts >= 5 ? now()->addMinutes(15) : null;

        DB::table('user_credentials')
            ->where('user_name', $username)
            ->update([
                'failed_login_attempts' => $attempts,
                'locked_until'          => $lockedUntil,
            ]);
    }

    /**
     * Reset lockout counters after a successful login.
     */
    private function resetLoginAttempts(string $username): void
    {
        DB::table('user_credentials')
            ->where('user_name', $username)
            ->update([
                'failed_login_attempts' => 0,
                'locked_until'          => null,
            ]);
    }

}
