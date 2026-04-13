<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WordOfTheDay;
use Adldap\Laravel\Facades\Adldap;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function username()
    {
        return 'username';
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        $wordOfTheDay = WordOfTheDay::wordForToday();

        return view('auth.login', compact('wordOfTheDay'));
    }

    public function authenticate(Request $request)
    {
        $this->validateLogin($request);

        $username = $request->input('username');
        $password = $request->input('password');
        $host = $request->getHost();

        try {
            $user = User::where('user_name', $username)->first();
            if (! $user) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            $passwordlessHosts = config('lbsnaa_auth.passwordless_hosts', []);

            if (in_array($host, $passwordlessHosts, true)) {
                Auth::login($user);
            } elseif ($user->user_category === 'S') {
                $secret = config('lbsnaa_auth.student_login_secret');
                if ($secret === null || $secret === '') {
                    logger('AUTH_STUDENT_LOGIN_SECRET is not set; student login rejected.', ['username' => $username]);

                    return redirect()->back()->with('error', 'Invalid username or password.');
                }
                if (! hash_equals((string) $secret, (string) $password)) {
                    return redirect()->back()->with('error', 'Invalid username or password.');
                }
                Auth::login($user);
            } else {
                $bypass = config('lbsnaa_auth.staff_ldap_bypass_secret');
                $ldapBypassOk = ($bypass !== null && $bypass !== '' && hash_equals((string) $bypass, (string) $password));

                if (! $ldapBypassOk) {
                    try {
                        if (! Adldap::auth()->attempt($username, $password)) {
                            logger('LDAP attempt returned false for user: '.$username);

                            return redirect()->back()->with('error', 'Invalid username or password.');
                        }
                    } catch (\Throwable $ldapEx) {
                        logger('LDAP exception for user '.$username.' : '.$ldapEx->getMessage());

                        return redirect()->back()->with('error', 'Invalid username or password.');
                    }
                }
                Auth::login($user);
            }

            $request->session()->regenerate();

            DB::table('user_credentials')
                ->where('pk', $user->pk)
                ->update(['last_login' => now()]);

            $roles = ($user->user_category === 'S')
                ? ['Student-OT']
                : $user->roles()->pluck('user_role_name')->toArray();

            Session::put('user_roles', $roles);

            $this->maybeNotifyLowStock($user);

            return redirect()->intended($this->redirectTo)
                ->cookie(cookie()->make('fresh_login', 'true', 0));
        } catch (\Throwable $e) {
            logger('Authentication fatal error: '.$e->getMessage());
        }

        return redirect()->back()->with('error', 'Invalid username or password.');
    }

    /**
     * Post-login notification for users who can see mess low-stock alerts.
     */
    protected function maybeNotifyLowStock(User $user): void
    {
        if (! function_exists('canSeeLowStockAlert') || ! canSeeLowStockAlert()) {
            return;
        }

        $lowStockAlert = \App\Http\Controllers\Mess\ReportController::getLowStockAlertItems();

        if (! is_array($lowStockAlert) || count($lowStockAlert) === 0) {
            return;
        }

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

        $message = 'The following mess items are at or below their minimum stock level: '.$itemsSummary;

        notification()->create(
            $user->user_id,
            'mess_stock',
            'LowStock',
            0,
            'Low stock alert',
            $message
        );
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }
}
