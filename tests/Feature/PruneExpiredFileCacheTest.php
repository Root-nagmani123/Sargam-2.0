<?php

namespace Tests\Feature;

use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * cache:prune-expired-files must delete exactly what FileStore would already discard.
 *
 * Runs against a throwaway directory configured as the only file store, so the real cache
 * is never touched. Entries are written through FileStore itself, so the test follows the
 * framework's own on-disk format rather than a copy of it.
 */
class PruneExpiredFileCacheTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/prune-cache-test-' . uniqid('', true);
        mkdir($this->dir, 0777, true);
        config(['cache.stores' => ['file' => ['driver' => 'file', 'path' => $this->dir]]]);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_only_expired_entries_are_deleted(): void
    {
        $store = new FileStore(new Filesystem(), $this->dir);
        $store->put('live', 'still valid', 600);
        $store->forever('forever', 'never expires');
        $store->put('expired', 'old generation', 600);

        // Age the "expired" entry the same way time passing would: rewrite its expiry header.
        $expiredPath = $this->pathFor($store, 'expired');
        file_put_contents($expiredPath, (string) (time() - 5) . substr(file_get_contents($expiredPath), 10));

        // A file that is not a cache entry at all must survive.
        file_put_contents($this->dir . '/not-a-cache-entry.txt', 'hello');

        $this->artisan('cache:prune-expired-files')->assertExitCode(0);

        $this->assertFileDoesNotExist($expiredPath);
        $this->assertSame('still valid', $store->get('live'));
        $this->assertSame('never expires', $store->get('forever'));
        $this->assertFileExists($this->dir . '/not-a-cache-entry.txt');
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $store = new FileStore(new Filesystem(), $this->dir);
        $store->put('expired', 'old generation', 600);
        $path = $this->pathFor($store, 'expired');
        file_put_contents($path, (string) (time() - 5) . substr(file_get_contents($path), 10));

        $this->artisan('cache:prune-expired-files', ['--dry-run' => true])->assertExitCode(0);

        $this->assertFileExists($path);
    }

    private function pathFor(FileStore $store, string $key): string
    {
        $method = new \ReflectionMethod($store, 'path');
        $method->setAccessible(true);

        return $method->invoke($store, $key);
    }
}
