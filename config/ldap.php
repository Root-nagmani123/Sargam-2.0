<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | This option enables logging all LDAP operations on all configured
    | connections such as bind requests and CRUD operations.
    |
    | Log entries will be created in your default logging stack.
    |
    | This option is extremely helpful for debugging connectivity issues.
    |
    */

    'logging' => env('LDAP_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | This array stores the connections that are added to Adldap. You can add
    | as many connections as you like.
    |
    | The key is the name of the connection you wish to use and the value is
    | an array of configuration settings.
    |
    */

    'connections' => [

    'default' => [

        'auto_connect' => env('LDAP_AUTO_CONNECT', true),

        'connection' => Adldap\Connections\Ldap::class,

        'settings' => [

            'schema' => Adldap\Schemas\ActiveDirectory::class,

            'account_prefix' => env('LDAP_ACCOUNT_PREFIX', ''),

            'account_suffix' => env('LDAP_ACCOUNT_SUFFIX', '@lbsnaa.gov.in'), // Your domain suffix

            'hosts' => explode(' ', env('LDAP_HOSTS', '103.225.204.25')), // Your LDAP server IP or hostname

            'port' => env('LDAP_PORT', 389), // Use 636 for LDAPS if required

            'timeout' => env('LDAP_TIMEOUT', 5),

            'base_dn' => env('LDAP_BASE_DN', 'DC=lbsnaa,DC=gov,DC=in'), // Your domain base DN

            'username' => env('LDAP_USERNAME', 'adtest@lbsnaa.gov.in'), // Admin username with domain

            'password' => env('LDAP_PASSWORD', 'india@@#$^'), // Admin password

            'follow_referrals' => false,

            'use_ssl' => env('LDAP_USE_SSL', false), // Set to true for LDAPS

            'use_tls' => env('LDAP_USE_TLS', false), // Set to true for TLS

        ],

    ],

],

];
