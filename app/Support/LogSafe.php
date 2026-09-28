<?php

namespace App\Support;

/**
 * Neutralises log-injection (CWE-117) in values that came from a request.
 *
 * Laravel's default Monolog LineFormatter writes one record per line but keeps
 * inline line breaks inside the record, so a value containing a real LF ends the
 * genuine record and starts a new one that looks exactly like a record the
 * application wrote. A search term of
 *
 *     x\n[2026-09-15 10:00:00] production.INFO: Master grid export {"actor":42,...}
 *
 * therefore forges an audit line naming another actor. Audit lines that can be
 * forged are worse than no audit lines, because they get trusted.
 *
 * Strips C0/C1 control characters and DEL, and bounds the length so a very long
 * query string cannot push the real record out of a rotated file.
 */
class LogSafe
{
    /** Longest request-derived value kept in a log context. */
    public const MAX_LENGTH = 512;

    /**
     * @param  mixed  $value  request-derived value bound for a log context
     * @return mixed          strings are stripped of control characters; other scalars pass through
     */
    public static function text($value)
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_string($value)) {
            $value = (string) $value;
        }

        // Cc (C0, DEL and the C1 range) is what can end a Monolog record, so
        // that is what gets replaced.
        //
        // NOT \p{C}: that also covers Cf, the invisible FORMAT characters, and
        // Cf contains U+200C/U+200D. Devanagari writes conjuncts with those
        // joiners, so stripping them rewrites a legitimate Hindi search term
        // inside the very audit line that exists to record what was searched
        // for. This is a neutraliser, not a censor: only the characters that
        // can break a record out of its line are touched.
        $clean = preg_replace('/\p{Cc}+/u', ' ', $value);

        // preg_replace returns null on invalid UTF-8; fall back to a byte filter
        // rather than logging nothing at all.
        if ($clean === null) {
            $clean = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        }

        $clean = trim((string) $clean);

        return mb_strlen($clean) > self::MAX_LENGTH
            ? mb_substr($clean, 0, self::MAX_LENGTH) . '…'
            : $clean;
    }

    /**
     * Sanitise every request-derived value in a log context array.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function context(array $context): array
    {
        foreach ($context as $key => $value) {
            $context[$key] = is_array($value) ? self::context($value) : self::text($value);
        }

        return $context;
    }
}
