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
 * format are left alone, so no live cache value is ever removed. Safe to run at any time,
 * including alongside requests: a read that finds its file gone is an ordinary cache miss.
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
            foreach (Finder::create()->files()->in($directory)->ignoreDotFiles(true) as $file) {
                $scanned++;
                $path = $file->getPathname();

                $handle = @fopen($path, 'rb');
                if ($handle === false) {
                    continue;
                }
                $expiry = fread($handle, 10);
                fclose($handle);

                if ($expiry === false || strlen($expiry) !== 10 || ! ctype_digit($expiry) || $now < (int) $expiry) {
                    continue;
                }

                $expired++;
                if (! $dryRun) {
                    @unlink($path);
                }
            }

            $this->info(sprintf(
                '%s: %d file(s) scanned, %d expired%s',
                $store,
                $scanned,
                $expired,
                $dryRun ? ' (dry run, nothing deleted)' : ' and deleted'
            ));
        }

        return self::SUCCESS;
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
