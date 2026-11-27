<?php

namespace App\Services\SidebarMenu;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-user variables in a menu Url: {username}, {email}, {phone}.
 *
 * The menu row stores the TEMPLATE ("...?uname={username}"); the signed-in user's
 * value is put in when the link is built for that request. Nothing resolved is
 * ever written back or cached - the sidebar caches (MenuService::categoryStructure,
 * SidebarNavResolver's maps) hold raw menu rows, and every href is built from them
 * per request, so each user gets their own Url.
 *
 * Rules, enforced on save (problem()) and again at click time (href()):
 *  - only the three tokens below are known; anything else in braces is refused on save;
 *  - one token per Url, used once;
 *  - tokens may only sit in the query string, so path-based active-menu matching
 *    (MenuRouteMatcher::matchScore) keeps working;
 *  - the Url must be https on a host in config('menu_url_variables.allowed_hosts');
 *  - a user with no value for a token gets the navigation-error page
 *    (reason=missing_profile), never a Url with the value left empty.
 */
class MenuUrlVariables
{
    /** token => user_credentials column. */
    public const TOKENS = [
        'username' => 'user_name',
        'email' => 'email_id',
        'phone' => 'mobile_no',
    ];

    /** token => label shown in the Add / Edit Menu form. */
    public const LABELS = [
        'username' => 'Username',
        'email' => 'Email',
        'phone' => 'Phone Number',
    ];

    /** Does the Url carry at least one known token? Static Urls never do. */
    public static function hasVariables(?string $url): bool
    {
        if ($url === null || ! str_contains($url, '{')) {
            return false;
        }

        foreach (array_keys(self::TOKENS) as $token) {
            if (str_contains($url, '{'.$token.'}')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Why this Url cannot be saved, or null when it can.
     *
     * Only a Url that looks like it uses variables is checked, so every static
     * Url saves exactly as before.
     */
    public static function problem(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! str_contains($url, '{')) {
            return null;
        }

        preg_match_all('/\{\s*([A-Za-z_][A-Za-z0-9_.\-]*)\s*\}/', $url, $matches);
        $unknown = array_values(array_diff(array_unique($matches[0]), self::tokenStrings()));
        if ($unknown !== []) {
            return 'Unknown variable '.implode(', ', $unknown).'. Use '.implode(', ', self::tokenStrings()).'.';
        }

        if (! self::hasVariables($url)) {
            return null;
        }

        if (self::tokenCount($url) > 1) {
            return 'Use only one variable per Url ('.implode(', ', self::tokenStrings()).'), once.';
        }

        if (! self::tokensOnlyInQuery($url)) {
            return 'Variables can only be used in the query string (after "?"), e.g. ?uname={username}.';
        }

        if (! self::isAllowedHost($url)) {
            $hosts = implode(', ', self::allowedHosts());

            return 'Variables can only be used in an https:// Url on an approved host'
                .($hosts !== '' ? ' ('.$hosts.')' : '').'.';
        }

        return null;
    }

    /**
     * The href for a Url that carries variables: the resolved Url, or the
     * navigation-error page when the Url is not allowed (invalid_route) or the
     * user is missing a value it needs (missing_profile).
     */
    public static function href(string $url, ?int $menuId = null, ?Model $user = null): string
    {
        $url = trim($url);

        if (self::tokenCount($url) > 1 || ! self::tokensOnlyInQuery($url) || ! self::isAllowedHost($url)) {
            return self::errorHref('invalid_route', $menuId);
        }

        return self::resolve($url, $user ?? auth()->user())
            ?? self::errorHref('missing_profile', $menuId);
    }

    /**
     * Put the user's values into the template, each one URL-encoded. Null when
     * there is no user or the user has no value for a token the Url uses.
     */
    public static function resolve(string $url, ?Model $user): ?string
    {
        if (! $user) {
            return null;
        }

        $replace = [];
        foreach (self::TOKENS as $token => $column) {
            $placeholder = '{'.$token.'}';
            if (! str_contains($url, $placeholder)) {
                continue;
            }

            $value = trim((string) $user->getAttribute($column));
            if ($value === '') {
                return null;
            }

            $replace[$placeholder] = rawurlencode($value);
        }

        return strtr($url, $replace);
    }

    /** @return list<string> */
    public static function allowedHosts(): array
    {
        return array_values(array_filter(array_map(
            static fn ($host) => strtolower(trim((string) $host)),
            (array) config('menu_url_variables.allowed_hosts', [])
        )));
    }

    /** @return list<string> e.g. ['{username}', '{email}', '{phone}'] */
    public static function tokenStrings(): array
    {
        return array_map(static fn ($token) => '{'.$token.'}', array_keys(self::TOKENS));
    }

    /** How many known tokens the Url carries, repeats included. */
    private static function tokenCount(string $url): int
    {
        $count = 0;
        foreach (self::tokenStrings() as $placeholder) {
            $count += substr_count($url, $placeholder);
        }

        return $count;
    }

    /** Every known token sits after "?" and before any "#". */
    private static function tokensOnlyInQuery(string $url): bool
    {
        $queryStart = strpos($url, '?');
        $fragmentStart = strpos($url, '#');

        foreach (self::tokenStrings() as $placeholder) {
            $offset = 0;
            while (($pos = strpos($url, $placeholder, $offset)) !== false) {
                if ($queryStart === false || $pos < $queryStart) {
                    return false;
                }
                if ($fragmentStart !== false && $pos > $fragmentStart) {
                    return false;
                }
                $offset = $pos + strlen($placeholder);
            }
        }

        return true;
    }

    /** https, no credentials in the Url, and an exact match on the allowlist. */
    private static function isAllowedHost(string $url): bool
    {
        // Tokens out first: parse_url is not guaranteed to cope with braces.
        $parts = parse_url(strtr($url, array_fill_keys(self::tokenStrings(), 'x')));
        if (! is_array($parts)) {
            return false;
        }

        if (strtolower($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');

        return $host !== '' && in_array($host, self::allowedHosts(), true);
    }

    private static function errorHref(string $reason, ?int $menuId): string
    {
        return route('admin.navigation.error', [
            'reason' => $reason,
            'menu_id' => $menuId,
        ]);
    }
}
