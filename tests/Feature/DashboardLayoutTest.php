<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Customise: a login reorders and hides the cards and panels its roles have on one module's
 * dashboard, for itself only. Assignments go to a throwaway role, rolled back.
 */
class DashboardLayoutTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private int $setupId;

    private User $user;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        config(['cache.default' => 'array']);

        $this->setupId = (int) DB::table('sidebar_categories')->where('slug', 'setup')->where('is_active', '1')->value('id');
        if (! $this->setupId) {
            $this->markTestSkipped('needs the setup module');
        }

        $user = $this->staffWithRole('Super Admin');
        $roleIds = DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id');
        DB::table('role_dashboard_cards')->where('sidebar_category_id', $this->setupId)->whereIn('role_id', $roleIds)->delete();
        DB::table('user_dashboard_layouts')->where('user_id', $user->pk)->delete();
        $role = DB::table('roles')->insertGetId([
            'name' => 'Dashboard layout test role '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);

        DB::table('dashboard_cards')->where('key', 'upcoming_events')->update(['sort_order' => 9000]);
        DB::table('dashboard_cards')->where('key', 'upcoming_courses')->update(['sort_order' => 9001]);
        foreach (['upcoming_events', 'upcoming_courses', 'widget_calendar'] as $key) {
            $card = (int) DB::table('dashboard_cards')->where('key', $key)->value('id');
            if (! $card) {
                $this->markTestSkipped("no $key card");
            }
            DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $this->setupId]);
        }
        $this->user = $user->fresh();
    }

    private function page(): \Illuminate\Testing\TestResponse
    {
        return $this->as($this->user, ['Super Admin'])->get(route('admin.dashboard', ['category' => $this->setupId]))->assertOk();
    }

    private function save(array $order, array $hidden, $category = null): \Illuminate\Testing\TestResponse
    {
        return $this->as($this->user, ['Super Admin'])->postJson(route('admin.dashboard.layout.store'), [
            'category' => $category ?? $this->setupId, 'order' => $order, 'hidden' => $hidden,
        ]);
    }

    public function test_default_layout_follows_the_assignment_order(): void
    {
        $page = $this->page();
        $this->assertSame(['upcoming_events', 'upcoming_courses'], $page->viewData('cardsToRender')->pluck('key')->all());
        $this->assertContains('widget_calendar', $page->viewData('enabledWidgetKeys'));
        $page->assertSee('id="dashboardCustomiseModal"', false);
    }

    public function test_a_saved_layout_reorders_and_hides_for_this_login(): void
    {
        $this->save(['upcoming_courses', 'upcoming_events'], ['widget_calendar'])->assertOk();

        $page = $this->page();
        $this->assertSame(['upcoming_courses', 'upcoming_events'], $page->viewData('cardsToRender')->pluck('key')->all());
        $this->assertNotContains('widget_calendar', $page->viewData('enabledWidgetKeys'), 'a hidden panel is not drawn');
        // Customise still lists the hidden panel, unticked, so it can be brought back.
        $this->assertTrue($page->viewData('customisePanels')->firstWhere('key', 'widget_calendar')['hidden']);
        // The preview starts on the login's first card.
        $label = DB::table('dashboard_cards')->where('key', 'upcoming_courses')->value('label');
        $page->assertSee('Dashboard Preview: <span class="module-dashboard-preview-label">'.e($label), false);
    }

    public function test_a_hidden_card_is_neither_drawn_nor_counted(): void
    {
        $this->save([], ['upcoming_events'])->assertOk();

        $page = $this->page();
        $this->assertSame(['upcoming_courses'], $page->viewData('cardsToRender')->pluck('key')->all());
        $this->assertTrue($page->viewData('customiseCards')->firstWhere('key', 'upcoming_events')['hidden']);
        $counts = $this->as($this->user, ['Super Admin'])->getJson(route('admin.dashboard.cards', ['category' => $this->setupId]))->json('counts');
        $this->assertArrayNotHasKey('upcoming_events', $counts);
        $this->assertArrayHasKey('upcoming_courses', $counts);
    }

    public function test_hiding_everything_says_so(): void
    {
        $this->save([], ['upcoming_events', 'upcoming_courses', 'widget_calendar'])->assertOk();

        $this->page()->assertSee('You have hidden every card here', false)->assertSee('id="dashboardCustomiseModal"', false);
    }

    public function test_only_keys_assigned_here_are_kept(): void
    {
        $this->save(['total_students', 'upcoming_courses', 'no_such_card'], ['total_students', 'widget_calendar'])->assertOk();

        $row = DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->where('sidebar_category_id', $this->setupId)->first();
        $this->assertSame(['upcoming_courses'], json_decode($row->card_order, true));
        $this->assertSame(['widget_calendar'], json_decode($row->hidden_cards, true));
    }

    public function test_a_second_save_replaces_the_first(): void
    {
        $this->save([], ['upcoming_events'])->assertOk();
        $this->save(['upcoming_courses'], [])->assertOk();

        $this->assertSame(1, DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->where('sidebar_category_id', $this->setupId)->count());
        $this->assertSame(['upcoming_courses', 'upcoming_events'], $this->page()->viewData('cardsToRender')->pluck('key')->all());
    }

    /**
     * PR #356 F-005: a second request inserting this login's first layout between the save's
     * check and its write. Reproduced deterministically: the competing row is written right
     * after the save's first query on user_dashboard_layouts (the old exists() check).
     */
    public function test_a_save_racing_another_first_save_is_not_a_500(): void
    {
        $raced = false;
        DB::listen(function ($query) use (&$raced) {
            if (! $raced && str_starts_with($query->sql, 'select exists') && str_contains($query->sql, 'user_dashboard_layouts')) {
                $raced = true;
                DB::table('user_dashboard_layouts')->insert([
                    'user_id' => $this->user->pk, 'sidebar_category_id' => $this->setupId,
                    'card_order' => '["upcoming_events"]', 'hidden_cards' => '[]', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->save(['upcoming_courses'], ['widget_calendar'])->assertOk();

        $rows = DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->where('sidebar_category_id', $this->setupId)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(['upcoming_courses'], json_decode($rows[0]->card_order, true), 'the later save wins');
        $this->assertSame(['widget_calendar'], json_decode($rows[0]->hidden_cards, true));
    }

    /** The other request already committed its row: this save updates it, keeping created_at. */
    public function test_a_save_over_an_existing_row_updates_it_in_place(): void
    {
        $id = DB::table('user_dashboard_layouts')->insertGetId([
            'user_id' => $this->user->pk, 'sidebar_category_id' => $this->setupId,
            'card_order' => '["upcoming_events"]', 'hidden_cards' => '[]', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00',
        ]);

        foreach (range(1, 3) as $i) {
            $this->save(['upcoming_courses', 'upcoming_events'], [])->assertOk();
        }

        $rows = DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->where('sidebar_category_id', $this->setupId)->get();
        $this->assertCount(1, $rows);
        $this->assertSame($id, (int) $rows[0]->id);
        $this->assertSame('2026-01-01 00:00:00', (string) $rows[0]->created_at);
        $this->assertNotSame('2026-01-01 00:00:00', (string) $rows[0]->updated_at);
        $this->assertSame(['upcoming_courses', 'upcoming_events'], $this->page()->viewData('cardsToRender')->pluck('key')->all());
    }

    public function test_reset_restores_the_default(): void
    {
        $this->save(['upcoming_courses'], ['upcoming_events'])->assertOk();
        $this->as($this->user, ['Super Admin'])->deleteJson(route('admin.dashboard.layout.destroy'), ['category' => $this->setupId])->assertOk();

        $this->assertSame(0, DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->count());
        $this->assertSame(['upcoming_events', 'upcoming_courses'], $this->page()->viewData('cardsToRender')->pluck('key')->all());
    }

    public function test_bad_input_is_refused(): void
    {
        $this->save([], [], 999999)->assertStatus(422);
        $this->save([], [], 'x')->assertStatus(422);
        $this->as($this->user, ['Super Admin'])->postJson(route('admin.dashboard.layout.store'), [
            'category' => $this->setupId, 'order' => 'upcoming_events', 'hidden' => [],
        ])->assertStatus(422);
        $this->as($this->user, ['Super Admin'])->postJson(route('admin.dashboard.layout.store'), [
            'category' => $this->setupId, 'order' => [['nested']], 'hidden' => [],
        ])->assertStatus(422);
        $this->assertSame(0, DB::table('user_dashboard_layouts')->where('user_id', $this->user->pk)->count());
    }
}
