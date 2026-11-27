<?php

/*
|--------------------------------------------------------------------------
| LDAP user-creation POC (isolated test — NOT part of FC Registration)
|--------------------------------------------------------------------------
|
| Drives the standalone page at /admin/ldap-poc, which manually creates (and
| optionally deletes) clearly-marked test accounts in LDAP to prove whether
| the existing bind account can create users.
|
| It reuses config('ldap.connections.default.settings') — the same host, base
| DN and bind account as the login — but opens its OWN connection, so the
| shared Adldap provider used by LoginController is never touched.
|
| The page is off unless LDAP_POC_ENABLED=true, and Super Admin only.
|
*/

return [

    'enabled' => (bool) env('LDAP_POC_ENABLED', false),

    // Where test accounts are created. Required, and must sit under the
    // ldap base_dn. There is deliberately no fallback: an unset value refuses
    // to run rather than writing into the domain's default Users container.
    // e.g. OU=ERP-LDAP-POC,DC=lbsnaa,DC=gov,DC=in
    'container_dn' => env('LDAP_POC_CONTAINER_DN'),

    // Every test sAMAccountName must start with this. It is one of the
    // cleanup safeguards, so it may not be shorter than 3 characters.
    'username_prefix' => env('LDAP_POC_USERNAME_PREFIX', 'erppoc'),

    // userPrincipalName suffix; falls back to the login's account_suffix.
    'upn_suffix' => env('LDAP_POC_UPN_SUFFIX'),

    // Written into `description` / `info` of every test account; cleanup
    // refuses any entry that does not carry it.
    'marker' => 'ERP-LDAP-POC',

    // Registry of accounts this POC created (storage/app is git-ignored).
    // Cleanup only acts on entries listed here.
    'registry_path' => storage_path('app/ldap-poc/registry.json'),

    /*
    | Transport for the POC connection only — the login connection keeps its
    | own settings. Active Directory refuses to set a password (unicodePwd)
    | over plain LDAP, so the POC defaults to LDAPS on 636. Set
    | LDAP_POC_PORT=389 + LDAP_POC_USE_SSL=false to fall back to plain LDAP
    | (account creation only, no password).
    */
    'connection' => [
        // Space-separated, like LDAP_HOSTS; null inherits LDAP_HOSTS. LDAPS
        // verifies the certificate's host name, so if LDAP_HOSTS is an IP and
        // the DC certificate is issued to its FQDN, put the FQDN here.
        'hosts' => env('LDAP_POC_HOSTS'),
        'port' => env('LDAP_POC_PORT', 636),
        'use_ssl' => env('LDAP_POC_USE_SSL', true),
        'use_tls' => env('LDAP_POC_USE_TLS', false),

        // CA certificate (PEM) that signed the domain controller's LDAPS
        // certificate — the existing LDAP certificate. Optional: when unset,
        // PHP's OpenLDAP uses TLS_CACERT from the system ldap.conf
        // (Linux: /etc/ldap/ldap.conf or /etc/openldap/ldap.conf;
        //  Windows: C:\OpenLDAP\sysconf\ldap.conf).
        // The certificate is always verified; there is no "skip" option.
        'ca_cert' => env('LDAP_POC_CA_CERT'),

        // The login connection inherits a 1000s timeout; a test page should
        // fail fast instead of hanging the request.
        'timeout' => (int) env('LDAP_POC_TIMEOUT', 10),
    ],

];
