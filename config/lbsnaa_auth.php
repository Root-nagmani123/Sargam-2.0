<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passwordless login hosts (development / staging only)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of request hostnames where any valid username logs
    | in without checking the password. Leave empty in production unless you
    | explicitly need this (set AUTH_PASSWORDLESS_HOSTS="").
    |
    */
    'passwordless_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AUTH_PASSWORDLESS_HOSTS', 'localhost,127.0.0.1,dev.local'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Student (OT) shared login secret
    |--------------------------------------------------------------------------
    |
    | Required for users with user_category "S" when not using a passwordless
    | host. Set in .env — do not commit real values.
    |
    */
    'student_login_secret' => env('AUTH_STUDENT_LOGIN_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Staff LDAP bypass secret
    |--------------------------------------------------------------------------
    |
    | If set, staff can authenticate with this password without LDAP.
    | Leave empty to disable bypass (LDAP only for non-students).
    |
    */
    'staff_ldap_bypass_secret' => env('AUTH_STAFF_LDAP_BYPASS_SECRET'),

];
