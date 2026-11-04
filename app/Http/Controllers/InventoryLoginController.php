<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class InventoryLoginController extends Controller
{
    /**
     * Send the logged-in user to the Inventory Management System login link.
     * The username always comes from the session, never from the request.
     */
    public function redirect()
    {
        $username = trim((string) Auth::user()->user_name);
        abort_if($username === '', 403, 'Your account has no username.');

        $base = rtrim((string) config('services.inventory.login_url'), '/');
        abort_if($base === '', 503, 'Inventory login is not configured.');

        return redirect()->away($base . '/' . rawurlencode($username));
    }
}
