<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Encodes FC form primary keys for use in URLs (fc-reg/forms/... and fc-reg/admin/forms/...).
 * Uses APP_KEY via Laravel's encrypter; token is URL-safe (no + or / in path segments).
 */
final class FcEncryptedFormId
{
    public static function encode(int $id): string
    {
        $token = Crypt::encryptString((string) $id);

        return strtr($token, '+/', '-_');
    }

    public static function decode(string $encoded): int
    {
        $token = strtr($encoded, '-_', '+/');
        try {
            $raw = Crypt::decryptString($token);
        } catch (DecryptException|\Throwable $e) {
            throw new \InvalidArgumentException('Invalid form identifier.');
        }

        if (! ctype_digit((string) $raw)) {
            throw new \InvalidArgumentException('Invalid form identifier payload.');
        }

        return (int) $raw;
    }
}
