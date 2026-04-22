<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\JbpUser;
use App\Models\FC\LoginAttemptsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    // ── Show login page ──────────────────────────────────────────────
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('fc-reg.dashboard');
        }
        return view('auth.login');
    }

    // ── Process login ────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $username = trim($request->username);
        $user     = JbpUser::where('username', $username)->first();

        // Log attempt
        LoginAttemptsLog::create([
            'username'    => $username,
            'ip_address'  => $request->ip(),
            'success'     => 0,
            'user_agent'  => $request->userAgent(),
            'attempted_at'=> now(),
        ]);

        // User not found
        if (! $user) {
            return back()->withErrors(['username' => 'Invalid username or password.'])->withInput(['username' => $username]);
        }

        // Account disabled
        if (! $user->enabled) {
            return back()->withErrors(['username' => 'Your account has been disabled. Please contact administrator.'])->withInput(['username' => $username]);
        }

        // Account locked due to too many attempts
        if ($user->login_attempts >= self::MAX_ATTEMPTS) {
            return back()->withErrors(['username' => 'Account locked after ' . self::MAX_ATTEMPTS . ' failed attempts. Contact administrator.'])->withInput(['username' => $username]);
        }

        // Wrong password
        if (! Hash::check($request->password, $user->password)) {
            $user->incrementLoginAttempts();
            $remaining = self::MAX_ATTEMPTS - $user->login_attempts;
            $msg = $remaining > 0
                ? "Invalid credentials. {$remaining} attempt(s) remaining before lockout."
                : 'Account locked. Contact administrator.';
            return back()->withErrors(['username' => $msg])->withInput(['username' => $username]);
        }

        // Successful login
        $user->resetLoginAttempts();

        // Update login log success
        LoginAttemptsLog::where('username', $username)
            ->latest()->first()?->update(['success' => 1]);

        // Login via Auth guard
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Role-based redirect
        return $this->redirectByRole($user);
    }

    // ── Logout ───────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    // ── Role-based redirect (matches HomeController.java logic) ──────
    private function redirectByRole(JbpUser $user)
    {
        return match ($user->role) {
            'ADMIN', 'SUPERADMIN' => redirect()->route('admin.dashboard'),
            'REPORT'              => redirect()->route('admin.reports'),
            default               => redirect()->route('fc-reg.dashboard'),   // FC officer trainees
        };
    }
}
