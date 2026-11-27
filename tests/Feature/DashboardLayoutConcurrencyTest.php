<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #356 F-005 across two database connections: another request commits this login's first
 * layout row for the module immediately before this request's write. The save must update that
 * row (one row, the later save's content), not fail on the unique key with a 500.
 *
 * The other request is a second, autocommitting connection, so its row is really committed and is
 * deleted afterwards. This test's own transaction is READ COMMITTED, so it takes no gap lock that
 * would make the other connection's insert wait.
 */
class DashboardLayoutConcurrencyTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private const OTHER = 'dashboard_layout_other_request';

    private ?int $otherRowId = null;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        DB::rollBack();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        config(['cache.default' => 'array']);
        config(['database.connections.'.self::OTHER => config('database.connections.'.config('database.default'))]);
        DB::connection(self::OTHER)->statement('SET SESSION innodb_lock_wait_timeout = 5');

        $this->beforeApplicationDestroyed(function () {
            // Runs after the trait's rollback has released this test's lock on the row.
            if ($this->otherRowId) {
                DB::connection(self::OTHER)->table('user_dashboard_layouts')->where('id', $this->otherRowId)->delete();
            }
            DB::purge(self::OTHER);
        });
    }

    public function test_a_first_save_committed_by_another_request_just_before_this_write_is_updated_not_a_500(): void
    {
        $setupId = (int) DB::table('sidebar_categories')->where('slug', 'setup')->where('is_active', '1')->value('id');
        if (! $setupId) {
            $this->markTestSkipped('needs the setup module');
        }
        $user = $this->staffWithRole('Super Admin');
        $where = ['user_id' => $user->pk, 'sidebar_category_id' => $setupId];
        if (DB::table('user_dashboard_layouts')->where($where)->exists()) {
            $this->markTestSkipped('this login already has a committed layout for the setup module');
        }
        $card = (string) DB::table('dashboard_cards')->join('role_dashboard_cards as rdc', 'rdc.dashboard_card_id', '=', 'dashboard_cards.id')
            ->whereIn('rdc.role_id', DB::table('model_has_roles')->where('model_id', $user->pk)->pluck('role_id'))
            ->where('rdc.sidebar_category_id', $setupId)->value('dashboard_cards.key');

        DB::connection()->beforeExecuting(function (string $sql) use ($where) {
            if ($this->otherRowId === null && str_contains($sql, 'user_dashboard_layouts') && preg_match('/^\s*insert/i', $sql)) {
                $this->otherRowId = DB::connection(self::OTHER)->table('user_dashboard_layouts')->insertGetId($where + [
                    'card_order' => '["from the other request"]', 'hidden_cards' => '[]',
                    'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00',
                ]);
            }
        });

        $this->as($user, ['Super Admin'])->postJson(route('admin.dashboard.layout.store'), [
            'category' => $setupId, 'order' => $card !== '' ? [$card] : [], 'hidden' => [],
        ])->assertOk();

        $this->assertNotNull($this->otherRowId, 'the other request committed its row before this save wrote');
        $rows = DB::table('user_dashboard_layouts')->where($where)->get();
        $this->assertCount(1, $rows, 'one layout row per login and module');
        $this->assertSame($this->otherRowId, (int) $rows[0]->id, 'the committed row was updated in place');
        $this->assertSame($card !== '' ? [$card] : [], json_decode($rows[0]->card_order, true), 'the later save wins');
        $this->assertSame('2026-01-01 00:00:00', (string) $rows[0]->created_at, 'created_at is the first save\'s');
    }
}
