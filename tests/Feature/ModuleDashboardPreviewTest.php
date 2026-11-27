<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Dashboard Preview: a module's own dashboard (any header tab but Home) embeds the page
 * its first card opens, under the card grid. Nothing on Home, nothing on the module's other pages.
 * Assignments go to throwaway roles inside a transaction that is rolled back.
 */
class ModuleDashboardPreviewTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private const PREVIEW = 'class="module-dashboard-preview ';

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

    private function newRole(User $user): int
    {
        $role = DB::table('roles')->insertGetId([
            'name' => 'Dashboard preview test role '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);

        return $role;
    }

    private function assign(int $roleId, string $key, int $moduleId): void
    {
        $card = (int) DB::table('dashboard_cards')->where('key', $key)->value('id');
        if (! $card) {
            $this->markTestSkipped("no $key card");
        }
        DB::table('role_dashboard_cards')->insert(['role_id' => $roleId, 'dashboard_card_id' => $card, 'sidebar_category_id' => $moduleId]);
    }

    /** A staff login with none of its own Home or Setup cards (rolled back), and a throwaway role. */
    private function viewer(): array
    {
        $user = $this->staffWithRole('Super Admin');
        DB::table('role_dashboard_cards')->whereIn('sidebar_category_id', [$this->homeId, $this->setupId])
            ->whereIn('role_id', DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id'))
            ->delete();

        return [$user->fresh(), $this->newRole($user)];
    }

    private function dashboard(User $user, array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->as($user, ['Super Admin'])->get(route('admin.dashboard', $query))->assertOk();
    }

    private function preview(string $html): string
    {
        $this->assertSame(1, substr_count($html, self::PREVIEW), 'exactly one preview');
        preg_match('#<section class="module-dashboard-preview.*?</section>#s', $html, $m);

        return $m[0];
    }

    public function test_a_module_dashboard_embeds_the_page_its_first_card_opens_under_the_grid(): void
    {
        [$user, $role] = $this->viewer();
        DB::table('dashboard_cards')->where('key', 'upcoming_courses')->update(['sort_order' => 9001]);
        DB::table('dashboard_cards')->where('key', 'upcoming_events')->update(['sort_order' => 9000]);
        $this->assign($role, 'upcoming_courses', $this->setupId);
        $this->assign($role, 'upcoming_events', $this->setupId);

        $response = $this->dashboard($user, ['category' => $this->setupId]);
        $html = $response->getContent();
        $first = $response->viewData('cardsToRender')->first();
        $this->assertSame('upcoming_events', $first['key'], 'first by sort_order');

        // The page behind the first card, embedded, after the grid.
        $preview = $this->preview($html);
        $this->assertStringContainsString('<iframe src="'.e($first['link'].'?embed=1').'"', $preview);
        $this->assertStringContainsString('title="'.e($first['label']).'"', $preview);
        $this->assertSame(1, substr_count($preview, '<iframe'));
        $this->assertGreaterThan(strpos($html, 'dashboard-stats-grid row row-cols-1'), strpos($html, self::PREVIEW));
    }

    public function test_two_page_cards_get_a_tab_row_starting_on_the_first(): void
    {
        [$user, $role] = $this->viewer();
        DB::table('dashboard_cards')->where('key', 'upcoming_courses')->update(['sort_order' => 9001]);
        DB::table('dashboard_cards')->where('key', 'upcoming_events')->update(['sort_order' => 9000]);
        $this->assign($role, 'upcoming_courses', $this->setupId);
        $this->assign($role, 'upcoming_events', $this->setupId);

        $response = $this->dashboard($user, ['category' => $this->setupId]);
        [$first, $second] = $response->viewData('cardsToRender')->all();
        $preview = $this->preview($response->getContent());

        $this->assertSame(2, substr_count($preview, 'class="module-dashboard-preview-tab"'));
        $this->assertMatchesRegularExpression('/aria-pressed="true"\s+data-key="upcoming_events"/', $preview);
        $this->assertMatchesRegularExpression('/aria-pressed="false"\s+data-key="upcoming_courses"/', $preview);
        $this->assertStringContainsString('data-src="'.e($second['link'].'?embed=1').'"', $preview);
        $this->assertSame(1, substr_count($preview, '<iframe'), 'tabs switch the one frame');
        // The hero names the module instead of a bare "Dashboard".
        $response->assertSee('>Setup Dashboard</h1>', false);
    }

    public function test_one_page_card_gets_no_tab_row(): void
    {
        [$user, $role] = $this->viewer();
        $this->assign($role, 'upcoming_events', $this->setupId);

        $preview = $this->preview($this->dashboard($user, ['category' => $this->setupId])->getContent());
        $this->assertStringNotContainsString('module-dashboard-preview-tabs', $preview);
    }

    public function test_the_embedded_page_hides_the_layout_and_sends_links_to_the_full_page(): void
    {
        [$user] = $this->viewer();
        $url = url('master/memo-type-master');

        $embedded = $this->as($user, ['Super Admin'])->get($url.'?embed=1')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<body[^>]*class="[^"]*sargam-embed"/', $embedded);
        $this->assertStringContainsString('<base target="_top">', $embedded);
        $this->assertMatchesRegularExpression('/\.sargam-embed \.modern-breadcrumb-wrapper[^{]*\{\s*display: none !important;/', $embedded, 'no breadcrumb in the preview');

        $normal = $this->as($user, ['Super Admin'])->get($url)->assertOk()->getContent();
        $this->assertStringNotContainsString('sargam-embed', $normal);
        $this->assertStringNotContainsString('<base target="_top">', $normal);
    }

    public function test_a_first_card_without_a_page_falls_back_to_its_tile(): void
    {
        // A card added through the UI with no definition has no link.
        [$user, $role] = $this->viewer();
        DB::table('dashboard_cards')->insert([
            'key' => 'preview_test_card', 'label' => 'Preview Test Card', 'icon' => 'star',
            'color_class' => 'stat-icon-blue', 'sort_order' => -5, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assign($role, 'preview_test_card', $this->setupId);

        $preview = $this->preview($this->dashboard($user, ['category' => $this->setupId])->getContent());
        $this->assertStringNotContainsString('<iframe', $preview);
        $this->assertStringContainsString('Preview Test Card', $preview);
        $this->assertSame(1, substr_count($preview, 'class="card stat-card'));
    }

    public function test_home_shows_no_preview(): void
    {
        [$user, $role] = $this->viewer();
        $this->assign($role, 'upcoming_events', $this->homeId);

        $this->dashboard($user)->assertSee('class="card stat-card', false)->assertDontSee(self::PREVIEW, false);
    }

    public function test_the_modules_other_pages_show_no_preview(): void
    {
        [$user, $role] = $this->viewer();
        $this->assign($role, 'upcoming_events', $this->setupId);

        $this->as($user, ['Super Admin'])->get(url('master/memo-type-master'))->assertOk()->assertDontSee(self::PREVIEW, false);
    }

    public function test_a_dashboard_without_stat_cards_shows_no_preview(): void
    {
        [$user] = $this->viewer();

        $this->dashboard($user, ['category' => $this->setupId])->assertDontSee(self::PREVIEW, false);
    }

    public function test_a_card_this_login_may_not_see_is_never_previewed(): void
    {
        // My Groups is gated to Student-OT logins (DashboardCard::gatedCardVisibility()).
        [$user, $role] = $this->viewer();
        DB::table('dashboard_cards')->where('key', 'my_groups')->update(['sort_order' => -1]);
        $this->assign($role, 'my_groups', $this->setupId);
        $this->dashboard($user, ['category' => $this->setupId])->assertDontSee(self::PREVIEW, false);

        $this->assign($role, 'upcoming_events', $this->setupId);
        $preview = $this->preview($this->dashboard($user, ['category' => $this->setupId])->getContent());
        $this->assertStringNotContainsString(e(route('admin.dashboard.my-groups')), $preview);
        $this->assertStringContainsString(e(route('admin.dashboard.upcoming_events').'?embed=1'), $preview);
    }

    public function test_two_roles_holding_the_same_card_preview_it_once(): void
    {
        [$user, $role] = $this->viewer();
        $second = $this->newRole($user);
        $this->assign($role, 'upcoming_events', $this->setupId);
        $this->assign($second, 'upcoming_events', $this->setupId);

        $preview = $this->preview($this->dashboard($user->fresh(), ['category' => $this->setupId])->getContent());
        $this->assertSame(1, substr_count($preview, '<iframe'));
    }
}
