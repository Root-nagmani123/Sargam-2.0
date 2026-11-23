<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #356 F-003: 2026_11_19_000000_align_role_dashboard_cards records every row it adds or
 * deletes before it does so, and down() restores role_dashboard_cards to exactly what it was.
 * Runs up() / down() for real inside the test transaction, which is rolled back.
 */
class AlignRoleDashboardCardsMigrationTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private const SNAPSHOTS = 'role_dashboard_card_snapshots';

    private function migration(): object
    {
        return require base_path('database/migrations/2026_11_19_000000_align_role_dashboard_cards.php');
    }

    /** role_dashboard_cards as sorted "role:card:tab" strings. */
    private function table(): array
    {
        return DB::table('role_dashboard_cards')->get()
            ->map(fn ($r) => $r->role_id.':'.$r->dashboard_card_id.':'.$r->sidebar_category_id)
            ->sort()->values()->all();
    }

    /** One of the migration's planned rows, by role name / tab slug / card key, or skip. */
    private function row(string $role, string $tab, string $card): array
    {
        $row = [
            'role_id' => DB::table('roles')->where('name', $role)->value('id'),
            'dashboard_card_id' => DB::table('dashboard_cards')->where('key', $card)->value('id'),
            'sidebar_category_id' => DB::table('sidebar_categories')->where('slug', $tab)->value('id'),
        ];
        if (in_array(null, $row, true)) {
            $this->markTestSkipped("needs role $role, tab $tab and card $card");
        }

        return $row;
    }

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        if (! Schema::hasTable(self::SNAPSHOTS)) {
            // Creating it here would commit the test transaction (MySQL DDL), so it must exist.
            $this->markTestSkipped('run the migration once (or ensureSnapshotTable()) so '.self::SNAPSHOTS.' exists');
        }
        DB::table(self::SNAPSHOTS)->where('migration', '2026_11_19_000000_align_role_dashboard_cards')->delete();
    }

    public function test_deleted_assignments_are_recorded_and_restored_by_down(): void
    {
        // An admin gave Employees "Total Active Courses" on Home: one of the planned removals.
        $adminRow = $this->row('Employee', 'home', 'total_active_courses');
        DB::table('role_dashboard_cards')->insertOrIgnore($adminRow);
        // And one planned addition is not there yet, so up() really adds it.
        $added = $this->row('Employee', 'home', 'widget_campus_tweets');
        DB::table('role_dashboard_cards')->where($added)->delete();
        $before = $this->table();

        $this->migration()->up();

        $this->assertFalse(DB::table('role_dashboard_cards')->where($adminRow)->exists(), 'up() removes it');
        $this->assertTrue(DB::table('role_dashboard_cards')->where($added)->exists(), 'up() adds it');
        $snap = fn (array $row, string $action) => DB::table(self::SNAPSHOTS)->where($row + ['action' => $action])->exists();
        $this->assertTrue($snap($adminRow, 'deleted'), 'the deleted row is in the snapshot');
        $this->assertTrue($snap($added, 'inserted'), 'the added row is in the snapshot');

        // Every row up() deleted is in the snapshot, and nothing it did not touch is.
        $deleted = array_diff($before, $this->table());
        $recorded = DB::table(self::SNAPSHOTS)->where('action', 'deleted')->get()
            ->map(fn ($r) => $r->role_id.':'.$r->dashboard_card_id.':'.$r->sidebar_category_id)->sort()->values()->all();
        $this->assertSame(array_values($deleted), $recorded);

        $this->migration()->down();

        $this->assertSame($before, $this->table(), 'down() restores role_dashboard_cards exactly');
        $this->assertSame(0, DB::table(self::SNAPSHOTS)->where('migration', '2026_11_19_000000_align_role_dashboard_cards')->count());
    }

    public function test_rows_already_there_are_neither_recorded_nor_removed_by_down(): void
    {
        $existing = $this->row('Employee', 'home', 'widget_campus_tweets');
        DB::table('role_dashboard_cards')->insertOrIgnore($existing);

        $this->migration()->up();
        $this->assertFalse(DB::table(self::SNAPSHOTS)->where($existing)->exists(), 'not added by up(), so not recorded');

        $this->migration()->down();
        $this->assertTrue(DB::table('role_dashboard_cards')->where($existing)->exists(), 'down() leaves a row up() did not add');
    }

    public function test_up_twice_is_a_no_op_the_second_time(): void
    {
        $this->migration()->up();
        $after = $this->table();
        $snapshots = DB::table(self::SNAPSHOTS)->count();

        $this->migration()->up();

        $this->assertSame($after, $this->table());
        $this->assertSame($snapshots, DB::table(self::SNAPSHOTS)->count());
    }
}
