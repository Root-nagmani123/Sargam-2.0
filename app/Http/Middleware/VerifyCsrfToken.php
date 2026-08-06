<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Keep this empty. 'fc/login-verify' was excluded for local load testing
     * (alongside a commented-out @csrf in fc/fc_login.blade.php) and both were
     * committed by mistake, leaving the FC login POST unprotected. The form
     * sends its token again, so nothing needs to be excluded here.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
