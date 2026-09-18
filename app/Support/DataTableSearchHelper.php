<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Shared helpers for DataTables global search (multi-word / space-separated terms).
 */
class DataTableSearchHelper
{
    /**
     * Normalize user search input (trim, collapse whitespace, NBSP → space).
     */
    public static function normalizeRaw(string $raw): string
    {
        $raw = str_replace("\xC2\xA0", ' ', $raw);

        return trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
    }

    /**
     * Split normalized search into non-empty tokens (space-separated keywords).
     *
     * @return string[]
     */
    public static function tokens(string $raw): array
    {
        $normalized = self::normalizeRaw($raw);
        if ($normalized === '') {
            return [];
        }

        return preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Escape a token for SQL LIKE (adds % wildcards).
     */
    public static function likePattern(string $token): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token) . '%';
    }

    /**
     * True when every token appears as a substring in $haystack (case-insensitive).
     * Supports names like "ADESH SINGH KUMAR" matching search "ADESH KUMAR".
     *
     * @param  string[]  $tokens
     */
    public static function haystackMatchesAllTokens(string $haystack, array $tokens): bool
    {
        if ($tokens === []) {
            return true;
        }

        $normalizedHaystack = self::normalizeHaystack($haystack);

        foreach ($tokens as $token) {
            $token = mb_strtolower($token);
            if ($token === '') {
                continue;
            }
            if (! str_contains($normalizedHaystack, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Status codes whose rendered "Active"/"Inactive" pill text contains $term,
     * mirroring what a client-side DataTables search does to that column.
     *
     * For grids that search in the browser but export from the server, the two
     * have to agree — otherwise a searched export comes back empty while the
     * screen shows rows. Note "Inactive" contains "active", so a search for
     * "active" matches BOTH codes; that is precisely the client-side behaviour.
     * Returns [] when the term cannot be part of either word, so callers can
     * leave the clause off entirely.
     *
     * @return int[]
     */
    public static function statusPillMatches(string $term, string $activeText = 'Active', string $inactiveText = 'Inactive'): array
    {
        $needle = mb_strtolower(self::normalizeRaw($term));
        if ($needle === '') {
            return [];
        }

        $matches = [];
        if (str_contains(mb_strtolower($activeText), $needle)) {
            $matches[] = 1;
        }
        if (str_contains(mb_strtolower($inactiveText), $needle)) {
            $matches[] = 0;
        }

        return $matches;
    }

    private static function normalizeHaystack(string $haystack): string
    {
        $haystack = str_replace("\xC2\xA0", ' ', $haystack);
        $haystack = preg_replace('/\s+/u', ' ', $haystack) ?? $haystack;

        return mb_strtolower(trim($haystack));
    }

    public static function orderColumnIndex(Request $request, int $default = 0): int
    {
        return max(0, (int) $request->input('order.0.column', $default));
    }

    public static function orderDirection(Request $request, string $default = 'asc'): string
    {
        return strtolower((string) $request->input('order.0.dir', $default)) === 'desc' ? 'desc' : 'asc';
    }
}
