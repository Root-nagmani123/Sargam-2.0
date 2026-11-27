<?php

/*
 * Dynamic user variables in sidebar menu Urls ({username}, {email}, {phone}).
 *
 * A menu Url carrying one of these tokens sends the signed-in user's own value
 * to the Url's host, so only the hosts listed here may carry them. MenuRequest
 * refuses to save a token on any other host, and MenuUrlVariables refuses to
 * substitute one at click time, so a row written by other means (a migration, a
 * seeder) cannot send user data elsewhere either.
 *
 * Comma separated, exact host match, no wildcards. Example:
 *   MENU_URL_VARIABLE_HOSTS=sargam.lbsnaa.gov.in,other.lbsnaa.gov.in
 */
return [
    'allowed_hosts' => array_values(array_filter(array_map(
        static fn ($host) => strtolower(trim($host)),
        explode(',', (string) env('MENU_URL_VARIABLE_HOSTS', 'sargam.lbsnaa.gov.in'))
    ))),
];
