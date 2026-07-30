<?php

namespace App\Support;

/**
 * Encodes FC form primary keys for use in URLs (fc-reg/forms/... and fc-reg/admin/forms/...).
 *
 * Token = single AES-128-ECB block over MAGIC(8 bytes) || id(8 bytes big-endian),
 * unpadded base64url — exactly 22 characters, the floor for any block-cipher-based
 * scheme. The 8-byte magic prefix doubles as an integrity check on decode: a tampered
 * ciphertext has ~2^-64 odds of decrypting to a value starting with MAGIC, so forged
 * tokens are rejected without a separate MAC. This trades Laravel's authenticated
 * AES-256-CBC (longer, non-deterministic) for a shorter deterministic token; acceptable
 * here because the value is only a lookup key behind normal auth/authorization, not a
 * capability grant, and nothing depends on same-id tokens differing across encodes.
 *
 * Key is derived from APP_KEY via HKDF (not Laravel's encrypter key material) so a
 * future APP_KEY rotation or encrypter change doesn't cross-invalidate these tokens.
 */
final class FcEncryptedFormId
{
    private const MAGIC = 'FCFORM01'; // 8 bytes, encode-side constant

    public static function encode(int $id): string
    {
        $plaintext = self::MAGIC.pack('J', $id);
        $cipher = openssl_encrypt(
            $plaintext,
            'aes-128-ecb',
            self::key(),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        if ($cipher === false) {
            throw new \RuntimeException('Failed to encode form identifier.');
        }

        return rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=');
    }

    public static function decode(string $encoded): int
    {
        $padded = str_pad(strtr($encoded, '-_', '+/'), (int) (ceil(strlen($encoded) / 4) * 4), '=');
        $cipher = base64_decode($padded, true);

        if ($cipher === false || strlen($cipher) !== 16) {
            throw new \InvalidArgumentException('Invalid form identifier.');
        }

        $plaintext = openssl_decrypt(
            $cipher,
            'aes-128-ecb',
            self::key(),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        if ($plaintext === false || strlen($plaintext) !== 16 || ! hash_equals(self::MAGIC, substr($plaintext, 0, 8))) {
            throw new \InvalidArgumentException('Invalid form identifier.');
        }

        $id = unpack('J', substr($plaintext, 8, 8))[1];

        return (int) $id;
    }

    private static function key(): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY is not set.');
        }

        return hash_hkdf('sha256', $appKey, 16, 'fc-encrypted-form-id');
    }
}
