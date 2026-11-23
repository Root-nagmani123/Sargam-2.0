<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\Feature\Concerns\SeedsModuleDashboardCards;
use Tests\TestCase;

/**
 * Queue cards with work in them lead a dashboard's single card row, and Home lists the
 * other modules where such work waits. Counts are live, so each test seeds every queue card
 * onto Setup and skips when none of them has anything in it on this database.
 */
class DashboardCardGroupsTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }
    use SeedsModuleDashboardCards;

    private int $homeId;

    private int $setupId;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        $this->seedModuleDashboardCards();
        config(['cache.default' => 'array']);

        $this->homeId = (int) DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $this->setupId = (int) DB::table('sidebar_categories')->where('slug', 'setup')->where('is_active', '1')->value('id');
        if (! $this->homeId || ! $this->setupId) {
            $this->markTestSkipped('needs the home and setup modules');
        }
    }

    /** A Super Admin login with no Home/Setup cards of its own, holding every queue card and Upcoming Events on Setup. */
    private function viewer(): User
    {
        $user = $this->staffWithRole('Super Admin');
        $roleIds = DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id');
        DB::table('role_dashboard_cards')->whereIn('sidebar_category_id', [$this->homeId, $this->setupId])->whereIn('role_id', $roleIds)->delete();

        $role = DB::table('roles')->insertGetId([
            'name' => 'Dashboard groups test role '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);

        $isQueue = new \ReflectionMethod(UserController::class, 'isActionableCardKey');
        $isQueue->setAccessible(true);
        $cards = DB::table('dashboard_cards')->get(['id', 'key'])
            ->filter(fn ($c) => in_array($c->key, ['upcoming_events', 'total_guest_faculty', 'total_inhouse_faculty'], true) || $isQueue->invoke(null, $c->key));
        foreach ($cards as $card) {
            DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card->id, 'sidebar_category_id' => $this->setupId]);
        }

        return $user->fresh();
    }

    private function dashboard(User $user, array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->as($user, ['Super Admin'])->get(route('admin.dashboard', $query))->assertOk();
    }

    /** What the page fetches for its figures: counts plus the grid, hero line and module strip HTML. */
    private function cards(User $user, array $query = []): array
    {
        return $this->as($user, ['Super Admin'])->getJson(route('admin.dashboard.cards', $query))->assertOk()->json();
    }

    public function test_the_page_opens_without_running_the_counts(): void
    {
        $response = $this->dashboard($this->viewer(), ['category' => $this->setupId]);

        $counted = $response->viewData('cardsToRender')->filter(fn ($c) => $c['show_count']);
        $this->assertNotEmpty($counted->all());
        $this->assertTrue($counted->every(fn ($c) => $c['count'] === null), 'no figure on the first paint');
        $response->assertSee('stat-value-skeleton', false)
            ->assertSee('data-src="'.e(route('admin.dashboard.cards', ['category' => $this->setupId])).'"', false)
            ->assertDontSee('stat-action-badge', false);
    }

    public function test_queue_cards_with_work_lead_the_single_card_row(): void
    {
        $user = $this->viewer();
        $cards = $this->dashboard($user, ['category' => $this->setupId])->viewData('cardsToRender');
        $data = $this->cards($user, ['category' => $this->setupId]);
        $busy = $cards->first(fn ($c) => $c['actionable'] && ($data['counts'][$c['key']] ?? 0) > 0);
        if (! $busy) {
            $this->markTestSkipped('no queue card has work in it on this database');
        }
        $this->assertFalse($cards->firstWhere('key', 'upcoming_events')['actionable'], 'a plain total is not a queue');
        $total = $cards->first(fn ($c) => ! $c['actionable'] && ($data['counts'][$c['key']] ?? 0) > 0);
        if (! $total) {
            $this->markTestSkipped('no plain total above 0 on this database');
        }

        $html = $data['grid'];
        // Action and overview cards share one row: no group headings, one grid before the fold.
        $this->assertStringNotContainsString('dashboard-card-group-title', $html);
        $fold = strpos($html, '<details') ?: strlen($html);
        $this->assertSame(1, substr_count(substr($html, 0, $fold), 'dashboard-stats-grid'), 'one row of live cards');
        $busyAt = strpos($html, 'title="'.e($busy['label']).'"');
        $this->assertLessThan(strpos($html, 'title="'.e($total['label']).'"'), $busyAt, 'the busy queue comes first');
        // With no heading, the badge marks the queue.
        $this->assertStringContainsString('stat-card--action', $html);
        $this->assertStringContainsString('stat-action-badge', $html);
        $this->assertStringNotContainsString('stat-value-skeleton', $html);
        $this->assertStringContainsString('need', $data['hero']);
        $this->assertSame('', trim($data['queues']), 'the strip is for Home only');
    }

    public function test_cards_at_zero_fold_under_the_live_ones(): void
    {
        $user = $this->viewer();
        $cards = $this->dashboard($user, ['category' => $this->setupId])->viewData('cardsToRender');
        $data = $this->cards($user, ['category' => $this->setupId]);
        $zero = $cards->filter(fn ($c) => $c['show_count'] && array_key_exists($c['key'], $data['counts']) && (float) $data['counts'][$c['key']] == 0);
        $live = $cards->first(fn ($c) => ($data['counts'][$c['key']] ?? 0) > 0);
        if ($zero->isEmpty() || ! $live) {
            $this->markTestSkipped('needs cards both at zero and above it on this database');
        }

        $html = $data['grid'];
        $fold = strpos($html, '<details class="dashboard-empty-cards');
        $this->assertNotFalse($fold);
        $this->assertStringContainsString($zero->count().' '.\Illuminate\Support\Str::plural('card', $zero->count()).' with nothing to show', $html);
        foreach ($zero as $card) {
            $this->assertGreaterThan($fold, strpos($html, 'title="'.e($card['label']).'"'), $card['key'].' is folded');
        }
        $this->assertLessThan($fold, strpos($html, 'title="'.e($live['label']).'"'), 'a live card stays out of the fold');
    }

    public function test_nothing_folds_when_every_card_is_zero(): void
    {
        // Cards added through the UI with no definition count 0.
        $user = $this->staffWithRole('Super Admin');
        $roleIds = DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id');
        DB::table('role_dashboard_cards')->whereIn('role_id', $roleIds)->where('sidebar_category_id', $this->setupId)->delete();
        foreach (['groups_zero_a', 'groups_zero_b'] as $i => $key) {
            $id = DB::table('dashboard_cards')->insertGetId([
                'key' => $key, 'label' => 'Zero card '.$i, 'icon' => 'star', 'color_class' => 'stat-icon-blue',
                'sort_order' => 9100 + $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('role_dashboard_cards')->insert(['role_id' => $roleIds->first(), 'dashboard_card_id' => $id, 'sidebar_category_id' => $this->setupId]);
        }

        $html = $this->cards($user->fresh(), ['category' => $this->setupId])['grid'];
        $this->assertStringNotContainsString('dashboard-empty-cards', $html);
        $this->assertSame(2, substr_count($html, 'class="card stat-card'));
    }

    public function test_home_lists_the_modules_with_work_waiting(): void
    {
        $user = $this->viewer();
        $setup = $this->cards($user, ['category' => $this->setupId]);
        $cards = $this->dashboard($user, ['category' => $this->setupId])->viewData('cardsToRender');
        $waiting = $cards->filter(fn ($c) => $c['actionable'] && ($setup['counts'][$c['key']] ?? 0) > 0);
        if ($waiting->isEmpty()) {
            $this->markTestSkipped('no queue card has work in it on this database');
        }

        $queues = $this->cards($user)['queues'];
        $this->assertSame(1, substr_count($queues, 'class="dashboard-module-queue"'), 'only the module holding the cards');
        $this->assertStringContainsString('href="'.e(route('admin.dashboard', ['category' => $this->setupId])).'" class="dashboard-module-queue"', $queues);
        $total = $waiting->sum(fn ($c) => $setup['counts'][$c['key']]);
        $this->assertStringContainsString('dashboard-module-queue__count">'.number_format($total).'</span>', $queues);
    }

    public function test_home_shows_no_strip_without_waiting_work(): void
    {
        $user = $this->staffWithRole('Super Admin');
        $roleIds = DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id');
        DB::table('role_dashboard_cards')->whereIn('role_id', $roleIds)->where('sidebar_category_id', '!=', $this->homeId)->delete();

        $this->assertStringNotContainsString('dashboard-module-queues', $this->cards($user->fresh())['queues']);
    }
}
