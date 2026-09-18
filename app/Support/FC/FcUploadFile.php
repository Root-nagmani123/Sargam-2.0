<?php

namespace App\Support\FC;

/**
 * Resolve a stored upload path to a real file on disk, or nothing.
 *
 * Two separate jobs, both easy to get wrong on their own:
 *
 *  1. FC uploads live in several places depending on when and how they were stored (the public
 *     disk, storage/app/public, a real public/storage directory, public/ itself, storage/app).
 *     fc_resolve_storage_file_path() knows all of them — but it goes through Flysystem, which
 *     THROWS PathTraversalDetected on a path like ../../../.env rather than returning null.
 *     Uncaught, a malformed stored value becomes a 500 with a stack trace.
 *
 *  2. Even once resolved, nothing outside the upload roots may ever be served or archived.
 *     Checked with realpath() so symlinks and ../ segments cannot walk out.
 */
class FcUploadFile
{
    /** Absolute path of a stored upload, or null if it does not resolve to a real file inside an upload root. */
    public static function resolve(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return null;
        }

        try {
            $full = fc_resolve_storage_file_path($stored);
        } catch (\Throwable $e) {
            return null;
        }

        if ($full === null || ! is_file($full) || ! self::isUnderUploadRoot($full)) {
            return null;
        }

        return $full;
    }

    /** Is this resolved absolute path inside one of the directories uploads may live in? */
    public static function isUnderUploadRoot(string $full): bool
    {
        $real = realpath($full);
        if ($real === false) {
            return false;
        }

        foreach ([
            storage_path('app/public'),
            public_path('storage'),
            public_path(),
            storage_path('app'),
        ] as $root) {
            $realRoot = realpath($root);
            if ($realRoot !== false && str_starts_with($real, $realRoot.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filename-safe form of a ZIP entry name segment.
     *
     * Returns '' when nothing survives stripping — a name written only in Devanagari reduces to
     * nothing, and the caller needs to notice that rather than emit a stray underscore.
     */
    public static function safeName(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_');
    }
}
