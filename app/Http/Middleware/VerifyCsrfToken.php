<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * TEMPORARY (load testing): allow JMeter to POST /fc/login-verify without a CSRF _token.
     * Off by default. Enable only for the load-test run by setting FC_LOADTEST_BYPASS_CSRF=true
     * in .env, then run `php artisan config:clear`. Remove this block when done.
     */
    public function __construct(Application $app, Encrypter $encrypter)
    {
        parent::__construct($app, $encrypter);

        if (filter_var(env('FC_LOADTEST_BYPASS_CSRF', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->except[] = 'fc/login-verify';
        }
    }
}
