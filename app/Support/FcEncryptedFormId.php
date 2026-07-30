<?php

namespace App\Support;

/**
 * Encodes FC form primary keys for use in URLs (fc-reg/forms/... and fc-reg/admin/forms/...)
 * and inside the Gupshup Portal_Link SMS variable, which must fit the DLT #uro# tag's
 * 120-character width alongside the rest of the URL — Laravel's Crypt::encryptString()
 * envelope (~100-120+ base64 chars on its own) doesn't leave room for the domain/path.
 *
 * Token = AES-128-GCM over an 8-byte big-endian id, using a random 8-byte nonce prefix
 * (padded to the 12-byte GCM nonce with a fixed counter suffix) and the 16-byte GCM
 * authentication tag. Layout: nonce8 || ciphertext8 || tag16 = 32 bytes, 43 chars
 * unpadded base64url — short enough for the DLT width, and unlike a hand-rolled ECB
 * scheme, decode() fails via a real AEAD tag check (openssl_decrypt returns false on any
 * tampering), not a probabilistic magic-prefix guess.
 *
 * Key is derived from APP_KEY via HKDF (not Laravel's encrypter key material) so a
 * future APP_KEY rotation or encrypter change doesn't cross-invalidate these tokens.
 */
final class FcEncryptedFormId
{
    private const CIPHER = 'aes-128-gcm';

    /** Fixed suffix completing the 8-byte random prefix to GCM's 12-byte nonce. */
    private const NONCE_SUFFIX = "\x00\x00\x00\x00";

    public static function encode(int $id): string
    {
        $noncePrefix = random_bytes(8);
        $nonce = $noncePrefix.self::NONCE_SUFFIX;
        $plaintext = pack('J', $id);

        $tag = '';
        $cipher = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Failed to encode form identifier.');
        }

        return rtrim(strtr(base64_encode($noncePrefix.$cipher.$tag), '+/', '-_'), '=');
    }

    public static function decode(string $encoded): int
    {
        $padded = str_pad(strtr($encoded, '-_', '+/'), (int) (ceil(strlen($encoded) / 4) * 4), '=');
        $raw = base64_decode($padded, true);

        if ($raw === false || strlen($raw) !== 32) {
            throw new \InvalidArgumentException('Invalid form identifier.');
        }

        $noncePrefix = substr($raw, 0, 8);
        $cipher = substr($raw, 8, 8);
        $tag = substr($raw, 16, 16);
        $nonce = $noncePrefix.self::NONCE_SUFFIX;

        $plaintext = openssl_decrypt(
            $cipher,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false || strlen($plaintext) !== 8) {
            throw new \InvalidArgumentException('Invalid form identifier.');
        }

        $id = unpack('J', $plaintext)[1];

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
