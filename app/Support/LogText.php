<?php

namespace App\Support;

/**
 * Renders one request-derived value safe to place in a log line.
 *
 * The default channel formats records as one line each
 * (LineFormatter(..., $allowInlineLineBreaks: true) — see LogManager), so a
 * value carrying a line feed does not stay inside its record: everything after
 * the LF is appended as further lines, which a line-based reader cannot tell
 * from records the application actually wrote. An audit trail whose subject can
 * append entries to it is not an audit trail (CWE-117).
 *
 * Control characters are ESCAPED rather than stripped, so the logged value
 * still shows what was submitted — "a%0Ab" reads as `a\nb` — while occupying
 * exactly one line.
 */
class LogText
{
    /**
     * @param  mixed  $value
     */
    public static function inline($value): string
    {
        $value = (string) $value;

        // Byte-wise on purpose, with no /u: C0 and DEL never occur inside a
        // valid UTF-8 multi-byte sequence (continuation bytes are 0x80-0xBF),
        // so this cannot cut a character in half — and unlike a /u pattern it
        // still returns a string when the input is not valid UTF-8, which a
        // query string is not obliged to be.
        $escaped = preg_replace_callback(
            '/[\x00-\x1F\x7F]/',
            static function (array $match): string {
                switch ($match[0]) {
                    case "\n":
                        return '\n';
                    case "\r":
                        return '\r';
                    case "\t":
                        return '\t';
                    default:
                        return '\x' . strtoupper(bin2hex($match[0]));
                }
            },
            $value
        );

        return $escaped ?? '';
    }
}
