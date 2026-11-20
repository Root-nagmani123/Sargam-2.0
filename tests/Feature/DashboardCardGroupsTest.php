<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Queue cards with work in them lead a dashboard under "Needs your action", and Home lists the
 * other modules where such work waits. Counts are live, so each test seeds every queue card
 * onto Setup and skips when none of them has anything in it on this database.
 */
class DashboardCardGroupsTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private int $homeId;

    private int $setupId;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
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
            ->filter(fn ($c) => $c->key === 'upcoming_events' || $isQueue->invoke(null, $c->key));
        foreach ($cards as $card) {
            DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card->id, 'sidebar_category_id' => $this->setupId]);
        }

        return $user->fresh();
    }

    private function dashboard(User $user, array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->as($user, ['Super Admin'])->get(route('admin.dashboard', $query))->assertOk();
    }

    public function test_queue_cards_with_work_lead_under_needs_your_action(): void
    {
        $response = $this->dashboard($this->viewer(), ['category' => $this->setupId]);
        $cards = $response->viewData('cardsToRender');
        $busy = $cards->first(fn ($c) => $c['actionable'] && is_numeric($c['count']) && $c['count'] > 0);
        if (! $busy) {
            $this->markTestSkipped('no queue card has work in it on this database');
        }
        $this->assertFalse($cards->firstWhere('key', 'upcoming_events')['actionable'], 'a plain total is not a queue');

        $html = $response->getContent();
        $action = strpos($html, '>Needs your action</h2>');
        $overview = strpos($html, '>Overview</h2>');
        $this->assertNotFalse($action);
        $this->assertNotFalse($overview);
        $busyAt = strpos($html, 'title="'.e($busy['label']).'"', $action);
        $this->assertTrue($busyAt > $action && $busyAt < $overview, 'the busy queue sits under Needs your action');
        $this->assertGreaterThan($overview, strpos($html, 'title="'.e($cards->firstWhere('key', 'upcoming_events')['label']).'"', $action));
        $this->assertStringContainsString('stat-action-badge', $html);
        $this->assertSame([], $response->viewData('moduleQueues')->all(), 'the strip is for Home only');
    }

    public function test_home_lists_the_modules_with_work_waiting(): void
    {
        $response = $this->dashboard($this->viewer());
        $queues = $response->viewData('moduleQueues');
        if ($queues->isEmpty()) {
            $this->markTestSkipped('no queue card has work in it on this database');
        }

        $this->assertSame([$this->setupId], $queues->pluck('module.id')->all(), 'only the module holding the cards');
        $setup = $queues->first();
        $this->assertSame($setup['cards']->sum('count'), $setup['total']);
        $this->assertTrue($setup['cards']->every(fn ($c) => $c['actionable'] && $c['count'] > 0));
        $response->assertSee('href="'.e(route('admin.dashboard', ['category' => $this->setupId])).'" class="dashboard-module-queue"', false);
    }

    public function test_home_shows_no_strip_without_waiting_work(): void
    {
        $user = $this->staffWithRole('Super Admin');
        $roleIds = DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id');
        DB::table('role_dashboard_cards')->whereIn('role_id', $roleIds)->where('sidebar_category_id', '!=', $this->homeId)->delete();

        $response = $this->dashboard($user->fresh());
        $this->assertTrue($response->viewData('moduleQueues')->isEmpty());
        $response->assertDontSee('class="dashboard-module-queues', false);
    }
}
