<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Dashboard cards are assigned per role AND per header module: the dashboard
 * for a module (/dashboard?category=<id>) shows only that module's cards, and a
 * header tab whose module has cards for the viewer lands on its dashboard.
 */
class DashboardModuleAssignmentTest extends TestCase
{
    // Aliased: a plain setUp() here would shadow the trait's and skip its
    // transaction, committing every write below to the application database.
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private int $homeId;

    private int $setupId;

    private int $roleId;

    private int $cardId;

    protected function setUp(): void
    {
        $this->rollbackSetUp();

        $this->homeId = (int) DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $this->setupId = (int) DB::table('sidebar_categories')->where('slug', 'setup')->where('is_active', '1')->whereNull('deleted_at')->value('id');
        $this->cardId = (int) DB::table('dashboard_cards')->where('key', 'upcoming_events')->value('id');
        if (! $this->homeId || ! $this->setupId || ! $this->cardId) {
            $this->markTestSkipped('needs the home and setup modules and the upcoming_events card');
        }

        $this->roleId = DB::table('roles')->insertGetId([
            'name' => 'Module dashboard test role '.uniqid(),
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** A non-trainee login holding only the test role. */
    private function viewer(): User
    {
        $pk = DB::table('user_credentials')->where('user_category', '!=', 'S')
            ->whereNotNull('user_id')->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no non-trainee login');
        }
        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $pk)->delete();
        DB::table('model_has_roles')->insert(['role_id' => $this->roleId, 'model_type' => User::class, 'model_id' => $pk]);

        return User::findOrFail($pk);
    }

    private function assign(int $moduleId): void
    {
        DB::table('role_dashboard_cards')->insert([
            'role_id' => $this->roleId, 'dashboard_card_id' => $this->cardId, 'sidebar_category_id' => $moduleId,
        ]);
    }

    private function renderedKeys(User $user, array $query): array
    {
        $response = $this->as($user, [])->get(route('admin.dashboard', $query));
        $response->assertOk();

        return collect($response->viewData('cardsToRender'))->pluck('key')->all();
    }

    public function test_a_card_shows_only_on_the_module_it_is_assigned_to(): void
    {
        $this->assign($this->setupId);
        $user = $this->viewer();

        $this->assertContains('upcoming_events', $this->renderedKeys($user, ['category' => $this->setupId]));
        $this->assertNotContains('upcoming_events', $this->renderedKeys($user, []), 'Home must not show a Setup-only card');
    }

    public function test_no_or_an_unknown_module_is_home(): void
    {
        $this->assign($this->homeId);
        $user = $this->viewer();

        $this->assertContains('upcoming_events', $this->renderedKeys($user, []));
        $this->assertContains('upcoming_events', $this->renderedKeys($user, ['category' => 999999]));
        $this->assertNotContains('upcoming_events', $this->renderedKeys($user, ['category' => $this->setupId]));
    }

    public function test_an_assignment_that_omits_the_module_means_home(): void
    {
        DB::table('role_dashboard_cards')->insert(['role_id' => $this->roleId, 'dashboard_card_id' => $this->cardId]);

        $this->assertSame($this->homeId, (int) DB::table('role_dashboard_cards')
            ->where('role_id', $this->roleId)->value('sidebar_category_id'));
    }

    public function test_the_header_tab_lands_on_the_module_dashboard_only_when_it_has_cards(): void
    {
        // The header lists only modules the login has menus in, so this needs a
        // login that sees Setup; the test role is added on top of its own.
        $user = $this->staffWithRole('Super Admin');
        DB::table('model_has_roles')->insert(['role_id' => $this->roleId, 'model_type' => User::class, 'model_id' => $user->pk]);
        $landing = json_encode(route('admin.dashboard', ['category' => $this->setupId]));
        // Start from "no Setup cards" whatever the live data holds (rolled back).
        DB::table('role_dashboard_cards')->where('sidebar_category_id', $this->setupId)
            ->whereIn('role_id', DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->pluck('role_id'))
            ->delete();

        $this->as($user, [])->get(route('admin.dashboard'))->assertOk()->assertDontSee($landing, false);

        $this->assign($this->setupId);
        $this->as($user, [])->get(route('admin.dashboard'))->assertOk()->assertSee($landing, false);
    }

    public function test_assign_dashboard_toggles_one_module_and_refuses_an_unknown_one(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $this->assign($this->homeId);
        $url = route('assign.roles.dashboard', $this->roleId);
        $rows = fn () => DB::table('role_dashboard_cards')->where('role_id', $this->roleId)
            ->orderBy('sidebar_category_id')->pluck('sidebar_category_id')->map(fn ($v) => (int) $v)->all();

        $this->as($admin, ['Super Admin'])->post($url, ['card_id' => $this->cardId, 'module' => $this->setupId, 'status' => 1])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame([$this->homeId, $this->setupId], $rows());

        $this->as($admin, ['Super Admin'])->post($url, ['card_id' => $this->cardId, 'module' => $this->setupId, 'status' => 0])
            ->assertOk();
        $this->assertSame([$this->homeId], $rows(), 'disabling on Setup must leave Home alone');

        $this->as($admin, ['Super Admin'])->post($url, ['card_id' => $this->cardId, 'module' => 999999, 'status' => 1])
            ->assertStatus(422);
        $this->as($admin, ['Super Admin'])->post($url, ['card_id' => $this->cardId, 'module' => ['x'], 'status' => 1])
            ->assertStatus(422);
        $this->assertSame([$this->homeId], $rows());
    }

    public function test_assign_dashboard_page_shows_the_selected_modules_assignment(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $this->assign($this->setupId);

        $setup = $this->as($admin, ['Super Admin'])->get(route('roles.dashboard', ['id' => $this->roleId, 'module' => $this->setupId]));
        $setup->assertOk();
        $this->assertSame([$this->cardId], array_map('intval', $setup->viewData('assignedCardIds')));

        $home = $this->as($admin, ['Super Admin'])->get(route('roles.dashboard', ['id' => $this->roleId]));
        $home->assertOk();
        $this->assertSame([], $home->viewData('assignedCardIds'));
        $this->assertSame($this->homeId, (int) $home->viewData('module')->id);
    }

    public function test_exports_are_for_the_selected_module(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $this->assign($this->setupId);
        $setupName = (string) DB::table('sidebar_categories')->where('id', $this->setupId)->value('name');

        $print = $this->as($admin, ['Super Admin'])
            ->get(route('roles.dashboard.export', ['id' => $this->roleId, 'module' => $this->setupId, 'format' => 'print', 'status' => 'enabled']));
        $print->assertOk()->assertSee('— '.$setupName, false);
        $this->assertSame(['upcoming_events'], array_map(
            fn ($r) => DB::table('dashboard_cards')->where('id', $r['id'])->value('key'),
            $print->viewData('rows')
        ));

        $homePrint = $this->as($admin, ['Super Admin'])
            ->get(route('roles.dashboard.export', ['id' => $this->roleId, 'format' => 'print', 'status' => 'enabled']));
        $homePrint->assertOk();
        $this->assertSame([], $homePrint->viewData('rows'), 'Home has nothing enabled for this role');

        $this->as($admin, ['Super Admin'])
            ->get(route('roles.dashboard.export', ['id' => $this->roleId, 'module' => $this->setupId, 'format' => 'csv']))
            ->assertOk();
    }

    private function cardForm(array $extra = []): array
    {
        return array_merge([
            'label' => 'Module tab test card '.uniqid(),
            'icon' => 'star',
            'color_class' => 'stat-icon-blue',
            'sort_order' => 99,
        ], $extra);
    }

    /** sidebar_category_id of every row $roleId has for $cardId, ascending. */
    private function tabsOf(int $roleId, int $cardId): array
    {
        return DB::table('role_dashboard_cards')->where('role_id', $roleId)->where('dashboard_card_id', $cardId)
            ->orderBy('sidebar_category_id')->pluck('sidebar_category_id')->map(fn ($v) => (int) $v)->all();
    }

    public function test_adding_a_card_enables_it_on_the_chosen_tab_for_the_role(): void
    {
        $admin = $this->staffWithRole('Super Admin');

        $response = $this->as($admin, ['Super Admin'])->post(route('dashboard.cards.store'),
            $this->cardForm(['role_id' => $this->roleId, 'module' => $this->setupId]));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame([$this->setupId], $this->tabsOf($this->roleId, (int) $response->json('card.id')));
    }

    public function test_adding_a_card_with_an_unknown_tab_creates_nothing(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $before = DB::table('dashboard_cards')->count();

        foreach ([999999, ['x'], ''] as $bad) {
            $this->as($admin, ['Super Admin'])->post(route('dashboard.cards.store'),
                $this->cardForm(['role_id' => $this->roleId, 'module' => $bad]))->assertStatus(422);
        }
        $this->as($admin, ['Super Admin'])->post(route('dashboard.cards.store'),
            $this->cardForm(['role_id' => 999999999, 'module' => $this->setupId]))->assertStatus(302);

        $this->assertSame($before, DB::table('dashboard_cards')->count());
    }

    public function test_editing_a_card_to_another_tab_moves_only_this_roles_assignment(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $otherRole = DB::table('roles')->insertGetId([
            'name' => 'Module dashboard test role '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assign($this->homeId);
        DB::table('role_dashboard_cards')->insert(['role_id' => $otherRole, 'dashboard_card_id' => $this->cardId, 'sidebar_category_id' => $this->homeId]);
        $label = DB::table('dashboard_cards')->where('id', $this->cardId)->value('label');

        $this->as($admin, ['Super Admin'])->put(route('dashboard.cards.update', $this->cardId),
            $this->cardForm(['label' => $label, 'role_id' => $this->roleId, 'module' => $this->setupId, 'from_module' => $this->homeId]))
            ->assertOk()->assertJson(['success' => true, 'moved' => true]);

        $this->assertSame([$this->setupId], $this->tabsOf($this->roleId, $this->cardId));
        $this->assertSame([$this->homeId], $this->tabsOf($otherRole, $this->cardId), 'another role keeps its tab');
    }

    public function test_editing_a_card_without_switching_tab_leaves_assignments_alone(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $this->assign($this->homeId);
        $label = DB::table('dashboard_cards')->where('id', $this->cardId)->value('label');

        $this->as($admin, ['Super Admin'])->put(route('dashboard.cards.update', $this->cardId),
            $this->cardForm(['label' => $label, 'role_id' => $this->roleId, 'module' => $this->homeId, 'from_module' => $this->homeId]))
            ->assertOk()->assertJson(['success' => true, 'moved' => false]);
        $this->assertSame([$this->homeId], $this->tabsOf($this->roleId, $this->cardId));

        // An unknown destination is refused and nothing changes, the card's label included.
        $this->as($admin, ['Super Admin'])->put(route('dashboard.cards.update', $this->cardId),
            $this->cardForm(['label' => 'Renamed', 'role_id' => $this->roleId, 'module' => 999999, 'from_module' => $this->homeId]))
            ->assertStatus(422);
        $this->assertSame([$this->homeId], $this->tabsOf($this->roleId, $this->cardId));
        $this->assertSame($label, DB::table('dashboard_cards')->where('id', $this->cardId)->value('label'));
    }
}
