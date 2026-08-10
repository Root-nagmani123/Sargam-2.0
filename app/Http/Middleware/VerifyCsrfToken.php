<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Keep this empty. 'fc/login-verify' was excluded for local load testing, alongside a
     * commented-out @csrf in fc/fc_login.blade.php, and both were committed by mistake — which
     * left the FC login POST unprotected from 14 Jul onwards.
     *
     * The two halves live in different files, so a merge that keeps one and drops the other
     * either reopens the hole (exception back) or breaks login with a 419 (token missing).
     * Both have now been restored together; change neither without the other.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
