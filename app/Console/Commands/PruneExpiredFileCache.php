<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Delete file-cache entries that have already expired.
 *
 * Laravel's FileStore removes an expired file only when that same key is read again.
 * FeedbackReportCache invalidates by bumping a generation counter, so every key from the
 * previous generation is never read again and its file stays on disk for good — each
 * feedback submission and every typeahead term leaves files behind, growing the cache
 * directory without bound. Redis evicts by TTL and needs none of this.
 *
 * Only files the store itself would already treat as expired are deleted: the first 10
 * bytes are the expiry timestamp FileStore writes, and it discards an entry once
 * time() >= expiry. Entries stored forever (expiry 9999999999) and anything not in that
 * format are left alone. The header is read again immediately before the unlink, so an entry
 * rewritten since the scan (a value re-put, a lock re-acquired) is kept.
 *
 * One race remains and is accepted: a rewrite landing between that second read and the unlink
 * itself, a window of microseconds on an hourly job. For a cached value the cost is one cache
 * miss. For a file-store lock (Cache::lock, or the scheduler's withoutOverlapping mutex when
 * cache.default is file) it is a moment in which a second process could take the same lock.
 * Closing it fully would need the store's own locking, which FileStore does not expose.
 */
class PruneExpiredFileCache extends Command
{
    protected $signature = 'cache:prune-expired-files
                            {--dry-run : Count what would be deleted without deleting anything}';

    protected $description = 'Delete expired entries from every file-driver cache store';

    public function handle(): int
    {
        $now = time();
        $dryRun = (bool) $this->option('dry-run');

        foreach ($this->fileStoreDirectories() as $store => $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $scanned = 0;
            $expired = 0;
            $deleted = 0;
            $rewritten = 0;
            $gone = 0;
            $failed = 0;
            foreach (Finder::create()->files()->in($directory)->ignoreDotFiles(true) as $file) {
                $scanned++;
                $path = $file->getPathname();

                if (! $this->isExpired($path, $now)) {
                    continue;
                }

                $expired++;
                if ($dryRun) {
                    continue;
                }
                // Re-read right before deleting: the entry may have been rewritten or removed since the scan.
                clearstatcache(true, $path);
                if (! is_file($path)) {
                    $gone++;        // removed meanwhile, e.g. FileStore discarding it on a read
                } elseif (! $this->isExpired($path, time())) {
                    $rewritten++;   // re-put or lock re-acquired: live again, kept
                } elseif (@unlink($path)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            }

            if ($dryRun) {
                $this->info(sprintf(
                    '%s: %d file(s) scanned, %d expired (dry run, nothing deleted)',
                    $store,
                    $scanned,
                    $expired
                ));
            } else {
                $this->info(sprintf(
                    '%s: %d file(s) scanned, %d expired: %d deleted, %d rewritten since the scan and kept, %d already removed, %d could not be deleted',
                    $store,
                    $scanned,
                    $expired,
                    $deleted,
                    $rewritten,
                    $gone,
                    $failed
                ));
            }
        }

        return self::SUCCESS;
    }

    /** True when the file carries FileStore's 10-digit expiry header and that time has passed. */
    private function isExpired(string $path, int $now): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $expiry = fread($handle, 10);
        fclose($handle);

        return $expiry !== false && strlen($expiry) === 10 && ctype_digit($expiry) && $now >= (int) $expiry;
    }

    /** @return array<string, string> store name => directory, for every file-driver store */
    private function fileStoreDirectories(): array
    {
        $directories = [];
        foreach ((array) config('cache.stores', []) as $name => $store) {
            if (($store['driver'] ?? null) === 'file' && ! empty($store['path'])) {
                $directories[$name] = $store['path'];
            }
        }

        return $directories;
    }
}
