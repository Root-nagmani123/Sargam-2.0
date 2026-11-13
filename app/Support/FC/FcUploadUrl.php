<?php

namespace App\Support\FC;

use Illuminate\Support\Facades\Crypt;

/**
 * Opaque, openable URL for a stored FC upload (photo / signature).
 *
 * The report used to publish Storage::url(), i.e. the real path:
 *
 *     /storage/uploads/41118/signature_1782866223.png
 *
 * which hands out three things for free — the internal user id, the on-disk folder layout,
 * and a directory that can be walked. Instead the stored path is encrypted into a single
 * token and served back through a controller:
 *
 *     /admin/reports/descriptive-data/file/eyJpdiI6...
 *
 * Laravel's encrypter is authenticated (AES + HMAC), so a tampered token fails to decrypt
 * rather than resolving to some other file — there is nothing to enumerate and no id to read.
 *
 * The URL is absolutised with url(), not APP_URL: a box serving the app at 127.0.0.1:8000
 * with APP_URL=http://localhost was emitting links to a host the recipient could not reach.
 * Outside an HTTP request (queued export, CLI) url() falls back to APP_URL, which is then the
 * only thing available and the right answer for a link opened later.
 */
final class FcUploadUrl
{
    /** Query parameter carrying the token. */
    public const TOKEN_PARAM = 't';

    /** Where a token is served from unless the caller names its own endpoint. */
    public const DEFAULT_PATH = '/admin/reports/descriptive-data/file';

    /**
     * @param  string  $basePath  the endpoint that will serve the token. REQUIRED, deliberately.
     *
     * Pass self::DEFAULT_PATH for photographs and specimen signatures — the long-standing,
     * deliberately UNAUTHENTICATED route whose accepted-risk decision was taken for images.
     * Pass the report's own authenticated endpoint for anything else; that decision does not
     * extend to an Aadhaar card.
     *
     * There is no default because a default here fails OPEN. This parameter used to be
     * optional and fell back to the unauthenticated route, so a caller who simply forgot it
     * published the documents anonymously: the link rendered, nothing errored, no log line was
     * written and no test failed. Omitting it is now an ArgumentCountError at the call site —
     * loud, immediate, and impossible to ship.
     *
     * The endpoint is also written into the token as its audience, so the separation is
     * enforced at redemption rather than merely intended. See encode() and decode().
     */
    public static function for(?string $path, string $basePath): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        return url($basePath).'?'.self::TOKEN_PARAM.'='.self::encode($path, $basePath);
    }

    /**
     * Encrypted, URL-safe. base64url so the token survives a query string untouched.
     *
     * The payload carries the audience — the one endpoint allowed to redeem this token —
     * ahead of the path. Without it the two file routes are interchangeable: they decode with
     * the same key and serve on the same terms, so a token minted for the authenticated
     * step-report endpoint could be redeemed at the unauthenticated descriptive-data one just
     * by editing the path segment in the URL, which handed out Aadhaar cards with no login.
     * The audience travels INSIDE the ciphertext, so it cannot be edited the way the URL can.
     *
     * A newline separator rather than JSON, deliberately. json_encode() returns false on a
     * path that is not valid UTF-8, which encrypted to an empty string and made that file
     * permanently unreachable — a silent 404 with nothing logged. This form is byte
     * transparent, and an audience is a route path, so it can never contain a newline itself.
     *
     * $audience is required for the same reason it is required on for(): a default here is a
     * default to whichever endpoint is named in DEFAULT_PATH, and that endpoint is the
     * unauthenticated one. decode() has demanded an explicit audience since the cross-endpoint
     * bypass was closed; leaving the minting side permissive kept exactly half of that gap open.
     */
    public static function encode(string $path, string $audience): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString($audience."\n".$path)), '+/', '-_'), '=');
    }

    /**
     * The stored path behind a token, but ONLY for a caller that will serve the file.
     *
     * $audience is mandatory on purpose. It was optional once, defaulting to "accept anything",
     * and that default is the whole protection quietly switched off: the next file endpoint
     * added to this module would call decode($token), inherit the permissive default, and
     * reopen the cross-endpoint bypass with no visible symptom. A caller that genuinely does
     * not serve a file wants pathForDisplay() instead.
     *
     * Returns null when the token is missing, malformed, tampered with, or was minted for a
     * different endpoint — all of which the callers treat the same way: a 404.
     */
    public static function decode(?string $token, string $audience): ?string
    {
        $claims = self::claims($token);

        if ($claims === null || ! hash_equals($audience, $claims['aud'])) {
            return null;
        }

        return $claims['path'];
    }

    /**
     * The stored path behind a token, with no audience check.
     *
     * For callers that read the path but never serve the bytes — the Excel export naming a
     * cell after the file, for instance. Named so that skipping the audience is a deliberate
     * choice a reviewer can see, rather than an omitted argument.
     */
    public static function pathForDisplay(?string $token): ?string
    {
        return self::claims($token)['path'] ?? null;
    }

    /**
     * Decrypt a token into its audience and path, or null if it cannot be trusted.
     *
     * @return array{aud: string, path: string}|null
     */
    private static function claims(?string $token): ?array
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }

        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        try {
            $plain = Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return null;
        }

        // Two parts means an audienced token. One means a legacy token: a bare path, minted
        // before audiences existed. Only the descriptive-data report ever issued one — the
        // step-report endpoint does not exist on main — so that is the audience it is given,
        // which keeps already-emailed workbooks resolving exactly as before.
        $parts = explode("\n", $plain, 2);

        $aud = count($parts) === 2 ? $parts[0] : self::DEFAULT_PATH;
        $path = trim(count($parts) === 2 ? $parts[1] : $parts[0]);

        return $path === '' ? null : ['aud' => $aud, 'path' => $path];
    }
}
