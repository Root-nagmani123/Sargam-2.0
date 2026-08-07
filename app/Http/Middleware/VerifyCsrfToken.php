<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * ⚠️  TEMPORARY — local load testing only. Revert before committing:
     *     git checkout app/Http/Middleware/VerifyCsrfToken.php
     *
     * @var array<int, string>
     */
    protected $except = [
        'fc/login-verify',
    ];
}
