<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-016: the estate:cache_ver bump must land only after the surrounding
 * transaction commits, never inside it, and never for a rolled-back one.
 */
class EstateCacheVersionAfterCommitTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private EstateController $controller;

    protected function setUp(): void
    {
        $this->rollbackSetUp();

        config(['cache.redis_backed_unified_store' => 'array']);
        // Production has no outer transaction; treat the rollback wrapper as absent.
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());

        $this->controller = $this->app->make(EstateController::class);
    }

    public function test_bump_inside_a_transaction_waits_for_commit(): void
    {
        $housePk = (int) DB::table('estate_house_master')->value('pk');
        if ($housePk <= 0) {
            $this->markTestSkipped('needs an estate_house_master row');
        }
        $before = $this->version();

        DB::transaction(function () use ($housePk, $before) {
            $this->invokePrivate('setHouseUsedStatus', $housePk, 1);
            $this->assertSame($before, $this->version(), 'no bump before commit');
        });
        $this->assertSame($before + 1, $this->version(), 'bumped on commit');

        try {
            DB::transaction(function () use ($housePk) {
                $this->invokePrivate('setHouseUsedStatus', $housePk, 1);
                throw new \RuntimeException('roll back');
            });
        } catch (\RuntimeException $e) {
        }
        $this->assertSame($before + 1, $this->version(), 'rolled-back write does not bump');
    }

    public function test_bump_outside_a_transaction_is_immediate(): void
    {
        $before = $this->version();
        $this->invokePrivate('bumpEstateCacheVersion');
        $this->assertSame($before + 1, $this->version());
    }

    private function version(): int
    {
        return $this->invokePrivate('estateCacheVersion');
    }

    private function invokePrivate(string $method, ...$args)
    {
        $m = new \ReflectionMethod($this->controller, $method);
        $m->setAccessible(true);

        return $m->invoke($this->controller, ...$args);
    }
}
