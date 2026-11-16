<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A real two-connection race on dashboard card keys (PR #353 F-002).
 *
 * Connection B commits the key storeDashboardCard() has just probed as free, so
 * the request's INSERT collides. The retry must see B's row, which under
 * REPEATABLE READ needs a fresh transaction per attempt: one transaction around
 * the loop kept the first snapshot and returned 409 on every retry.
 *
 * No DatabaseTransactions on purpose: inside a test transaction every attempt is
 * a savepoint of one long transaction, which is the bug itself. Rows are removed
 * in tearDown instead.
 */
class DashboardCardKeyRaceTest extends TestCase
{
    private array $labels = [];

    protected function tearDown(): void
    {
        $ids = DB::table('dashboard_cards')->whereIn('label', $this->labels)->pluck('id');
        DB::table('role_dashboard_cards')->whereIn('dashboard_card_id', $ids)->delete();
        DB::table('dashboard_cards')->whereIn('id', $ids)->delete();
        DB::purge('race');

        parent::tearDown();
    }

    public function test_a_key_another_connection_commits_mid_request_is_retried_not_409(): void
    {
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $pk = $roleId ? DB::table('model_has_roles')->where('role_id', $roleId)->value('model_id') : null;
        $admin = $pk ? User::where('pk', $pk)->first() : null;
        if (! $admin) {
            $this->markTestSkipped('no Super Admin account in this database');
        }
        $this->assertSame(0, DB::transactionLevel(), 'must run outside a test transaction or the race is not real');
        $default = config('database.default');
        config(['database.connections.race' => config('database.connections.'.$default)]);

        $locking = [];
        DB::listen(function (QueryExecuted $q) use (&$locking) {
            if (preg_match('/for update|lock in share mode|for share/i', $q->sql)) {
                $locking[] = $q->sql;
            }
        });

        foreach ([1, 2, 3] as $run) {
            $label = 'Race Fixture '.uniqid();
            $this->labels[] = $label;
            $baseKey = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');

            // After the request's first probe for $baseKey, connection B commits it.
            $fired = false;
            DB::listen(function (QueryExecuted $q) use (&$fired, $default, $baseKey, $label) {
                if ($fired || $q->connectionName !== $default || ! in_array($baseKey, $q->bindings, true)) {
                    return;
                }
                $fired = true;
                DB::connection('race')->table('dashboard_cards')->insert([
                    'key' => $baseKey, 'label' => $label, 'icon' => 'dashboard', 'color_class' => 'bg-primary', 'sort_order' => 1,
                ]);
            });

            $response = $this->actingAs($admin)->post(route('dashboard.cards.store'), [
                'label' => $label, 'icon' => 'dashboard', 'color_class' => 'bg-primary', 'sort_order' => 1,
            ]);

            $this->assertTrue($fired, "run $run: connection B never raced the request");
            $response->assertOk();
            $this->assertSame($baseKey.'_1', $response->json('card.key'), "run $run");
            $keys = DB::table('dashboard_cards')->where('label', $label)->orderBy('key')->pluck('key')->all();
            $this->assertSame([$baseKey, $baseKey.'_1'], $keys, "run $run: one row per key, none lost");
        }

        $this->assertSame([], $locking, 'allocation takes no row locks');
    }
}
