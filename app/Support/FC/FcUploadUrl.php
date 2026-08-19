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
     * @param  string|null  $basePath  the endpoint that will serve the token.
     *
     * Defaults to the long-standing, deliberately UNAUTHENTICATED photo/signature route, so
     * existing callers are unchanged. Reports whose uploads are identity or medical documents
     * pass their own authenticated endpoint instead — the open route's accepted-risk decision
     * was taken for photographs and does not extend to an Aadhaar card.
     *
     * The endpoint is also written into the token as its audience, so that separation is
     * enforced rather than merely intended. See encode().
     */
    public static function for(?string $path, ?string $basePath = null): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $basePath = $basePath ?: self::DEFAULT_PATH;

        return url($basePath).'?'.self::TOKEN_PARAM.'='.self::encode($path, $basePath);
    }

    /**
     * Encrypted, URL-safe. base64url so the token survives a query string untouched.
     *
     * The payload carries the audience — the one endpoint allowed to redeem this token —
     * alongside the path. Without it the two file routes are interchangeable: they decode
     * with the same key and serve on the same terms, so a token minted for the authenticated
     * step-report endpoint could be redeemed at the unauthenticated descriptive-data one just
     * by editing the path segment in the URL, which handed out Aadhaar cards with no login.
     * The audience travels INSIDE the ciphertext, so it cannot be edited the way the URL can.
     */
    public static function encode(string $path, string $audience = self::DEFAULT_PATH): string
    {
        $payload = json_encode(
            ['aud' => $audience, 'p' => $path],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');
    }

    /**
     * Reverse of encode(). Returns null when the token is missing, malformed, has been
     * tampered with, or was minted for a different endpoint — all of which are treated the
     * same way by the caller: a 404.
     *
     * @param  string|null  $audience  reject the token unless it was minted for this endpoint.
     *                                 Pass null ONLY where the path is used for display; every
     *                                 route that actually serves a file must name its own.
     */
    public static function decode(?string $token, ?string $audience = null): ?string
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

        $claims = json_decode($plain, true);

        if (is_array($claims) && isset($claims['p'], $claims['aud'])) {
            $path = trim((string) $claims['p']);
            $aud = (string) $claims['aud'];
        } else {
            // Legacy token: a bare path, minted before audiences existed. Only the
            // descriptive-data report ever issued one — the step-report endpoint does not
            // exist on main — so that is its audience. Keeps workbooks already emailed
            // under the accepted-risk decision resolving exactly as before.
            $path = trim($plain);
            $aud = self::DEFAULT_PATH;
        }

        if ($path === '') {
            return null;
        }

        if ($audience !== null && ! hash_equals($audience, $aud)) {
            return null;
        }

        return $path;
    }
}
