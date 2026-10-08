<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The My Groups card row was created only by MyGroupsDashboardCardSeeder, which
 * nothing runs on deploy, so after `migrate` the card never rendered
 * (PR #334 F-064). A migration now creates it.
 */
class MyGroupsCardMigrationTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function migration(): object
    {
        return require base_path('database/migrations/2026_10_08_100000_add_my_groups_dashboard_card.php');
    }

    public function test_up_creates_the_card_and_assigns_it_to_officer_trainee_once(): void
    {
        $roleId = DB::table('roles')->where('name', 'Officer Trainee')->value('id');
        if (! $roleId) {
            $this->markTestSkipped('no "Officer Trainee" role');
        }

        $existing = DB::table('dashboard_cards')->where('key', 'my_groups')->value('id');
        if ($existing) {
            DB::table('role_dashboard_cards')->where('dashboard_card_id', $existing)->delete();
            DB::table('dashboard_cards')->where('id', $existing)->delete();
        }

        $this->migration()->up();
        $this->migration()->up(); // re-run after a rollback: nothing twice

        $cards = DB::table('dashboard_cards')->where('key', 'my_groups')->pluck('id');
        $this->assertCount(1, $cards);
        $this->assertSame(1, DB::table('role_dashboard_cards')
            ->where('dashboard_card_id', $cards[0])->where('role_id', $roleId)->count());
    }

    public function test_up_keeps_an_existing_card_as_an_admin_left_it(): void
    {
        $id = DB::table('dashboard_cards')->where('key', 'my_groups')->value('id');
        if (! $id) {
            $this->migration()->up();
            $id = DB::table('dashboard_cards')->where('key', 'my_groups')->value('id');
        }
        DB::table('dashboard_cards')->where('id', $id)->update(['label' => 'Relabelled', 'sort_order' => 3]);

        $this->migration()->up();

        $card = DB::table('dashboard_cards')->where('id', $id)->first();
        $this->assertSame('Relabelled', $card->label);
        $this->assertSame(3, (int) $card->sort_order);
    }

    public function test_down_leaves_the_card(): void
    {
        $this->migration()->up();

        $this->migration()->down();

        $this->assertTrue(DB::table('dashboard_cards')->where('key', 'my_groups')->exists());
    }
}
