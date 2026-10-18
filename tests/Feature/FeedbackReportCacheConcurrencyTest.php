<?php

namespace Tests\Feature;

use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * FeedbackReportCache::bust() must not lose bumps or move the generation backwards when
 * several processes bust at once.
 *
 * On the file store, increment() is a read followed by a separate write. Without a lock,
 * overlapping busts write the same value, and a bust that read before two others finished
 * writes back an older one, so entries cached under that older generation are served again.
 *
 * The busts run in separate PHP processes against a throwaway file store, and start together
 * on a barrier file so that they overlap. Needs no database.
 */
class FeedbackReportCacheConcurrencyTest extends TestCase
{
    private const PROCESSES = 8;

    private const BUSTS_EACH = 100;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is disabled.');
        }

        $this->dir = sys_get_temp_dir() . '/feedback-cache-concurrency-' . uniqid('', true);
        mkdir($this->dir . '/store', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_concurrent_busts_lose_no_bump_and_never_go_backwards(): void
    {
        $worker = $this->dir . '/worker.php';
        file_put_contents($worker, $this->workerSource());
        $barrier = $this->dir . '/go';

        $processes = [];
        for ($i = 0; $i < self::PROCESSES; $i++) {
            $processes[] = proc_open(
                [PHP_BINARY, $worker, base_path(), $this->dir . '/store', $barrier, (string) self::BUSTS_EACH],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes[$i]
            );
        }

        // Every worker boots the application and then waits for the barrier, so the busts overlap.
        touch($barrier);

        $backwards = 0;
        $errors = '';
        foreach ($processes as $i => $process) {
            $out = stream_get_contents($pipes[$i][1]);
            $errors .= stream_get_contents($pipes[$i][2]);
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            $this->assertSame(0, proc_close($process), "Worker $i failed: $errors");
            $backwards += (int) trim($out);
        }

        $generation = (new FileStore(new Filesystem(), $this->dir . '/store'))->get('feedback_reports:generation');

        // The first bump creates the key at 1 and then increments it, so N busts end at N + 1.
        $this->assertSame(
            self::PROCESSES * self::BUSTS_EACH + 1,
            (int) $generation,
            'Concurrent busts lost bumps: the generation read-modify-write is not atomic.'
        );
        $this->assertSame(0, $backwards, 'A worker saw the generation move backwards.');
    }

    private function workerSource(): string
    {
        return <<<'PHP'
<?php
[, $base, $storeDir, $barrier, $count] = $argv;
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Point every candidate store at the throwaway directory before anything resolves it.
app('cache')->forgetDriver(['file']);
config([
    'cache.default' => 'file',
    'cache.stores.file.path' => $storeDir,
    'cache.redis_backed_unified_store' => 'file',
]);

$deadline = microtime(true) + 30;
while (! file_exists($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

$backwards = 0;
$highest = 0;
for ($i = 0; $i < (int) $count; $i++) {
    App\Support\FeedbackReportCache::bust();
    $seen = App\Support\FeedbackReportCache::generation();
    if ($seen < $highest) {
        $backwards++;
    }
    $highest = max($highest, $seen);
}
echo $backwards;
PHP;
    }
}
