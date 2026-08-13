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
     */
    public static function for(?string $path, ?string $basePath = null): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        return url($basePath ?: self::DEFAULT_PATH).'?'.self::TOKEN_PARAM.'='.self::encode($path);
    }

    /** Encrypted, URL-safe. base64url so the token survives a query string untouched. */
    public static function encode(string $path): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString($path)), '+/', '-_'), '=');
    }

    /**
     * Reverse of encode(). Returns null when the token is missing, malformed, or has been
     * tampered with — all of which are treated the same way by the caller: a 404.
     */
    public static function decode(?string $token): ?string
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
            $path = Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return null;
        }

        return trim($path) !== '' ? $path : null;
    }
}
