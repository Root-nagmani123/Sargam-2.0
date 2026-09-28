<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SidebarMenu\MenuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pins the authorisation on the screens that decide what every other gate is
 * worth (PR #317 L-8 and L-9).
 *
 * Before this change POST roles/permissions/{id} carried `web` and Authenticate
 * and nothing else, and the controller behind it called Permission::firstOrCreate()
 * on whatever name it was posted and granted it to the role in the URL without
 * looking at the caller. Any authenticated account could hand its own role any
 * permission in one request.
 *
 * The actor below is deliberately an authenticated NON Super Admin: that is the
 * only actor for which the old rule ("anyone signed in") and the new one ("Super
 * Admin") disagree. A role-less or logged-out actor would be refused by the
 * unfixed code too, and the test would pass without the fix.
 *
 * Fixture-defensive by the convention of FcStepReportGuardsTest: a database
 * without a Super Admin, without an ordinary user, or without a role to aim at
 * is a skip, never a failure.
 */
class RolePermissionAdministrationGuardTest extends TestCase
{
    // Every test here posts to endpoints that WRITE when the guard is missing. Without
    // this, running the file against unfixed code (the red half of red/green) granted
    // directory.export to a real role in the shared database, and nothing rolled it back.
    use DatabaseTransactions {
        beginDatabaseTransaction as openTransaction;
    }

    /**
     * The trait opens its transaction inside parent::setUp(), before any test body
     * runs, so with no database it throws there and none of the skips below can
     * fire. Probe first, so a missing database is a skip rather than an error.
     */
    public function beginDatabaseTransaction()
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('role administration tests need the application database');
        }

        $this->openTransaction();
    }

    /** Routes that must refuse an ordinary authenticated user, on BOTH mounts. */
    private const GUARDED_GET_ROUTES = [
        'roles.index',
        'admin.roles.index',
        'sidebar.menus.index',
    ];

    public function test_the_permission_grant_endpoint_refuses_an_ordinary_authenticated_user(): void
    {
        $actor = $this->ordinaryUser();
        $roleId = $this->roleIdOf($actor);

        $this->actingAs($actor)
            ->post("/roles/permissions/{$roleId}", [
                'permission' => 'directory.export',
                'status' => 1,
            ])
            ->assertForbidden();

        // The endpoint must not have run far enough to create the row it is asked for.
        $this->assertDatabaseMissing('permissions', ['name' => 'directory.export']);
    }

    public function test_an_ordinary_user_cannot_reach_role_administration_on_either_mount(): void
    {
        $actor = $this->ordinaryUser();

        foreach (self::GUARDED_GET_ROUTES as $name) {
            $this->actingAs($actor)
                ->get(route($name))
                ->assertForbidden();
        }
    }

    public function test_an_ordinary_user_cannot_edit_a_menu_row(): void
    {
        $actor = $this->ordinaryUser();
        $menu = DB::table('menus')->whereNotNull('permission_name')->first();

        if (! $menu) {
            $this->markTestSkipped('no menus row in this database');
        }

        // A complete, valid edit-form payload, so that on unguarded code the request
        // reaches the write rather than failing validation first.
        $this->actingAs($actor)
            ->put("/sidebar/menus/{$menu->id}", [
                'category_id' => $menu->category_id,
                'group_id' => $menu->group_id,
                'parent_id' => $menu->parent_id,
                'name' => $menu->name . ' X',
                'route' => $menu->route,
                'permission_name' => 'directory.export',
                'order' => $menu->order,
                'icon' => $menu->icon,
                'is_active' => (string) $menu->is_active,
                'target' => (string) ($menu->target ?? '0'),
            ])
            ->assertForbidden();

        $this->assertSame(
            $menu->permission_name,
            DB::table('menus')->where('id', $menu->id)->value('permission_name'),
            'a refused menu edit must leave the row alone'
        );
    }

    /**
     * The guard tests hasRole('Super Admin'), so the role-assignment endpoint is a
     * second way past it: post your own user_id with the Super Admin role id, and
     * every screen above opens. It must refuse, and must not have written the row.
     */
    public function test_an_ordinary_user_cannot_assign_themselves_super_admin(): void
    {
        $actor = $this->ordinaryUser();
        $superAdminRole = DB::table('roles')->where('name', 'Super Admin')->value('id');

        if (! $superAdminRole) {
            $this->markTestSkipped('no Super Admin role in this database');
        }

        $this->actingAs($actor)
            ->get(route('admin.users.assignRole', encrypt($actor->getKey())))
            ->assertForbidden();

        $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $actor->getKey(),
                'roles' => [$superAdminRole],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $actor->getKey(),
            'role_id' => $superAdminRole,
        ]);
    }

    /** The control for the test above: a Super Admin still reaches role assignment. */
    public function test_a_super_admin_can_still_open_role_assignment(): void
    {
        $superAdmin = $this->superAdmin();

        $level = ob_get_level();
        $response = $this->actingAs($superAdmin)
            ->get(route('admin.users.assignRole', encrypt($superAdmin->getKey())));

        // Until PR #322 lands, the admin layout leaves one output buffer open per
        // render; close it here so this test is not reported as risky for it.
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $response->assertOk();
    }

    /**
     * The control. Without it a guard that refuses EVERYONE would pass the
     * refusal tests above, and a review of those alone could not tell the two apart.
     */
    public function test_a_super_admin_can_still_grant_a_permission(): void
    {
        $superAdmin = $this->superAdmin();
        $roleId = $this->roleIdOf($superAdmin);

        DB::beginTransaction();

        try {
            $this->actingAs($superAdmin)
                ->post("/roles/permissions/{$roleId}", [
                    'permission' => 'zz_review_probe_317',
                    'status' => 1,
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('permissions', ['name' => 'zz_review_probe_317']);
        } finally {
            DB::rollBack();
            // The grant above wrote through Spatie's model, which caches. After a
            // rollback the cache would otherwise describe a row that is gone.
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->assertDatabaseMissing('permissions', ['name' => 'zz_review_probe_317']);
    }

    /**
     * L-9: a menus row's permission_name is derived, never taken from the request.
     *
     * store() has always slugged it. update() computed the slug, used it only to
     * rename the permissions row, and then saved the posted value verbatim - so a
     * name the slug cannot produce (anything with a dot) could be planted on a
     * menus row, and the Roles matrix offers exactly those names.
     */
    public function test_menu_update_derives_the_permission_name_instead_of_saving_what_was_posted(): void
    {
        $category = DB::table('sidebar_categories')->value('id');
        $group = DB::table('menu_groups')->value('id');

        if (! $category || ! $group) {
            $this->markTestSkipped('no sidebar category or menu group in this database');
        }

        $service = new MenuService();

        DB::beginTransaction();

        try {
            $attributes = [
                'category_id' => $category,
                'group_id' => $group,
                'parent_id' => null,
                'name' => 'ZZ Review Probe 317',
                'route' => null,
                'order' => 999999,
                'is_active' => 1,
            ];

            $menu = $service->store($attributes + ['permission_name' => 'ignored_by_store']);

            $this->assertSame(
                'zz_review_probe_317',
                DB::table('menus')->where('id', $menu->id)->value('permission_name'),
                'store() has always derived the permission name'
            );

            $service->update($menu->id, $attributes + ['permission_name' => 'directory.export']);

            $this->assertSame(
                'zz_review_probe_317',
                DB::table('menus')->where('id', $menu->id)->value('permission_name'),
                'update() saved the posted permission_name verbatim, which is how a dotted name '
                . 'reaches a menus row and then the Roles matrix (PR #317 L-9)'
            );
        } finally {
            DB::rollBack();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->assertDatabaseMissing('menus', ['name' => 'ZZ Review Probe 317']);
    }

    /** PR #323 F-006: a refusal leaves a record naming the actor and route, not the payload. */
    public function test_a_refused_request_is_logged_without_its_payload(): void
    {
        $actor = $this->ordinaryUser();
        $roleId = $this->roleIdOf($actor);
        Log::spy();

        $this->actingAs($actor)
            ->post("/roles/permissions/{$roleId}", ['permission' => 'zz_secret_payload_value', 'status' => 1])
            ->assertForbidden();

        Log::shouldHaveReceived('warning')->once()->withArgs(function ($message, $context) use ($actor, $roleId) {
            return $message === 'Refused role/permission administration request'
                && $context['actor'] === $actor->getKey()
                && $context['route'] === 'assign.roles.permissions'
                && $context['method'] === 'POST'
                && (string) ($context['target']['id'] ?? '') === (string) $roleId
                && ! str_contains(json_encode($context), 'zz_secret_payload_value');
        });
    }

    /** Must-succeed control for the guarded assign-role-save write. */
    public function test_a_super_admin_can_still_save_a_role_assignment(): void
    {
        $superAdmin = $this->superAdmin();
        $target = $this->ordinaryUser();
        $roles = DB::table('model_has_roles')->where('model_id', $target->getKey())
            ->pluck('role_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $target->getKey(),
                'roles' => $roles,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($roles, DB::table('model_has_roles')->where('model_id', $target->getKey())
            ->pluck('role_id')->map(fn ($id) => (int) $id)->sort()->values()->all());
    }

    /** Must-succeed control for a write on the admin/ mount of RoleController. */
    public function test_a_super_admin_can_still_create_a_role_on_the_admin_mount(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.roles.store'), ['name' => 'ZZ Review Probe Role 323'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'ZZ Review Probe Role 323']);
    }

    /**
     * PR #323 F-001. Many live menus carry a permission name that differs from
     * the slug of their name. Saving such a menu without renaming it must not
     * touch any permission: re-deriving the name renamed a live permission.
     */
    public function test_an_unchanged_menu_save_renames_no_permission(): void
    {
        $this->permission('zz_probe_drift_old');
        $menuId = $this->menuFixture('ZZ Probe Drift', 'zz_probe_drift_old');

        (new MenuService())->update($menuId, $this->editPayload($menuId));

        $this->assertSame('zz_probe_drift_old', DB::table('menus')->where('id', $menuId)->value('permission_name'));
        $this->assertDatabaseHas('permissions', ['name' => 'zz_probe_drift_old']);
        $this->assertDatabaseMissing('permissions', ['name' => 'zz_probe_drift']);
    }

    /** The same case through the real route and MenuRequest, as the edit form posts it. */
    public function test_an_unchanged_menu_save_over_http_renames_no_permission(): void
    {
        $this->permission('zz_probe_http_old');
        $menuId = $this->menuFixture('ZZ Probe Http', 'zz_probe_http_old');

        $this->actingAs($this->superAdmin())
            ->put("/sidebar/menus/{$menuId}", $this->editPayload($menuId))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('zz_probe_http_old', DB::table('menus')->where('id', $menuId)->value('permission_name'));
        $this->assertDatabaseMissing('permissions', ['name' => 'zz_probe_http']);
    }

    /** A rename that would land on an existing permission name is refused, not merged. */
    public function test_a_menu_rename_onto_an_existing_permission_name_is_refused(): void
    {
        $this->permission('zz_probe_one');
        $this->permission('zz_probe_two');
        $menuId = $this->menuFixture('ZZ Probe One', 'zz_probe_one');

        try {
            (new MenuService())->update($menuId, ['name' => 'ZZ Probe Two'] + $this->editPayload($menuId));
            $this->fail('a rename onto an existing permission name must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }

        $this->assertSame('zz_probe_one', DB::table('menus')->where('id', $menuId)->value('permission_name'));
        $this->assertSame(1, DB::table('permissions')->where('name', 'zz_probe_one')->count());
        $this->assertSame(1, DB::table('permissions')->where('name', 'zz_probe_two')->count());
    }

    /**
     * Renaming a menu whose permission row another live menu also uses must leave
     * that row, its name and its holders alone, and give the renamed menu a new
     * row that the same roles hold.
     */
    public function test_renaming_a_menu_leaves_a_shared_permission_row_alone(): void
    {
        $roleId = $this->roleIdOf($this->ordinaryUser());
        $shared = $this->permission('zz_probe_shared');
        DB::table('role_has_permissions')->insert(['permission_id' => $shared, 'role_id' => $roleId]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $renamed = $this->menuFixture('ZZ Probe Shared A', 'zz_probe_shared');
        $sibling = $this->menuFixture('ZZ Probe Shared B', 'zz_probe_shared');

        (new MenuService())->update($renamed, ['name' => 'ZZ Probe Gamma'] + $this->editPayload($renamed));

        $this->assertSame('zz_probe_shared', DB::table('menus')->where('id', $sibling)->value('permission_name'));
        $this->assertSame('zz_probe_gamma', DB::table('menus')->where('id', $renamed)->value('permission_name'));
        $this->assertSame('zz_probe_shared', DB::table('permissions')->where('id', $shared)->value('name'));
        $this->assertDatabaseHas('role_has_permissions', ['permission_id' => $shared, 'role_id' => $roleId]);

        $gamma = DB::table('permissions')->where('name', 'zz_probe_gamma')->value('id');
        $this->assertNotNull($gamma, 'the renamed menu needs its own permission row');
        $this->assertDatabaseHas('role_has_permissions', ['permission_id' => $gamma, 'role_id' => $roleId]);
    }

    /**
     * PR #323 F-009. Menu groups and sidebar categories share the sidebar/ route
     * group with menus, and an inactive group's menus drop out of the Roles
     * matrix, so an ordinary account must not reach them either.
     */
    public function test_an_ordinary_user_cannot_change_menu_groups_or_categories(): void
    {
        $actor = $this->ordinaryUser();
        $group = DB::table('menu_groups')->whereNull('deleted_at')->first();
        $category = DB::table('sidebar_categories')->whereNull('deleted_at')->first();

        if (! $group || ! $category) {
            $this->markTestSkipped('no menu group or sidebar category in this database');
        }

        $this->actingAs($actor);

        $this->get(route('sidebar.menu-groups.index'))->assertForbidden();
        $this->post(route('sidebar.menu-groups.store'), [
            'category_id' => $category->id, 'name' => 'ZZ Probe Group 323', 'icon' => 'label', 'is_active' => '1',
        ])->assertForbidden();
        $this->put(route('sidebar.menu-groups.update', $group->id), [
            'category_id' => $group->category_id, 'name' => $group->name . ' ZZ', 'icon' => $group->icon ?: 'label',
            'order' => $group->order, 'is_active' => (string) $group->is_active,
        ])->assertForbidden();
        $this->get(route('sidebar.menu-groups.status', $group->id) . '?is_active=0')->assertForbidden();
        $this->delete(route('sidebar.menu-groups.destroy', $group->id))->assertForbidden();

        $this->get(route('sidebar.categories.index'))->assertForbidden();
        $this->post(route('sidebar.categories.store'), [
            'name' => 'ZZ Probe Category 323', 'slug' => 'zz-probe-category-323', 'is_active' => '1',
        ])->assertForbidden();
        $this->put(route('sidebar.categories.update', $category->id), [
            'name' => $category->name . ' ZZ', 'slug' => $category->slug, 'icon' => $category->icon,
            'order' => $category->order, 'is_active' => (string) $category->is_active,
        ])->assertForbidden();
        $this->get(route('sidebar.categories.status', $category->id) . '?is_active=0')->assertForbidden();
        $this->delete(route('sidebar.categories.destroy', $category->id))->assertForbidden();

        $this->assertEquals($group, DB::table('menu_groups')->where('id', $group->id)->first());
        $this->assertEquals($category, DB::table('sidebar_categories')->where('id', $category->id)->first());
        $this->assertDatabaseMissing('menu_groups', ['name' => 'ZZ Probe Group 323']);
        $this->assertDatabaseMissing('sidebar_categories', ['name' => 'ZZ Probe Category 323']);
    }

    /** The control for the test above: a Super Admin still edits groups and categories. */
    public function test_a_super_admin_can_still_change_menu_groups_and_categories(): void
    {
        $superAdmin = $this->superAdmin();
        $group = DB::table('menu_groups')->whereNull('deleted_at')->first();
        $category = DB::table('sidebar_categories')->whereNull('deleted_at')->first();

        if (! $group || ! $category) {
            $this->markTestSkipped('no menu group or sidebar category in this database');
        }

        $this->actingAs($superAdmin);

        $this->put(route('sidebar.menu-groups.update', $group->id), [
            'category_id' => $group->category_id, 'name' => $group->name . ' ZZ', 'icon' => $group->icon ?: 'label',
            'order' => $group->order, 'is_active' => (string) $group->is_active,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->getJson(route('sidebar.menu-groups.status', $group->id) . '?is_active=' . $group->is_active)
            ->assertOk()->assertJson(['success' => true]);

        $this->put(route('sidebar.categories.update', $category->id), [
            'name' => $category->name . ' ZZ', 'slug' => $category->slug, 'icon' => $category->icon,
            'order' => $category->order, 'is_active' => (string) $category->is_active,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->getJson(route('sidebar.categories.status', $category->id) . '?is_active=' . $category->is_active)
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame($group->name . ' ZZ', DB::table('menu_groups')->where('id', $group->id)->value('name'));
        $this->assertSame($category->name . ' ZZ', DB::table('sidebar_categories')->where('id', $category->id)->value('name'));
    }

    /** The menu-groups grid is a Super Admin screen; a stored icon must reach it as text. */
    public function test_the_menu_groups_grid_escapes_a_stored_icon(): void
    {
        $category = DB::table('sidebar_categories')->whereNull('deleted_at')->value('id');

        if (! $category) {
            $this->markTestSkipped('no sidebar category in this database');
        }

        $groupId = (int) DB::table('menu_groups')->insertGetId([
            'category_id' => $category, 'name' => 'ZZ Probe Icon 323', 'icon' => '<img src=x onerror=alert(1)>',
            'order' => 900000 + DB::table('menu_groups')->count(), 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $rows = $this->actingAs($this->superAdmin())
            ->getJson(route('sidebar.menu-groups.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('id', $groupId);
        $this->assertNotNull($row, 'the fixture group must be in the grid feed');
        $this->assertStringNotContainsString('<img', $row['icon']);
        $this->assertStringContainsString('&lt;img', $row['icon']);
    }

    /**
     * PR #323 F-007. A permission name that route middleware checks (can:<name>)
     * must survive a label rename of its menu, or every holder - Super Admin
     * included - is locked out of those routes.
     */
    public function test_renaming_the_menu_of_a_route_checked_permission_keeps_the_route_open(): void
    {
        $superAdmin = $this->superAdmin();
        $superAdminRole = (int) DB::table('roles')->where('name', 'Super Admin')->value('id');
        $gated = $this->permission('zz_probe_gated');
        DB::table('role_has_permissions')->insert(['permission_id' => $gated, 'role_id' => $superAdminRole]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Route::middleware(['web', 'auth', 'can:zz_probe_gated'])->get('/zz-probe-gated-323', fn () => 'ok');
        $menuId = $this->menuFixture('ZZ Probe Gated', 'zz_probe_gated');

        $this->actingAs($superAdmin)->get('/zz-probe-gated-323')->assertOk();

        $this->put("/sidebar/menus/{$menuId}", ['name' => 'ZZ Probe Gated Reports'] + $this->editPayload($menuId))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('ZZ Probe Gated Reports', DB::table('menus')->where('id', $menuId)->value('name'));
        $this->assertSame('zz_probe_gated', DB::table('menus')->where('id', $menuId)->value('permission_name'));
        $this->assertSame('zz_probe_gated', DB::table('permissions')->where('id', $gated)->value('name'));
        $this->get('/zz-probe-gated-323')->assertOk();
    }

    /** The live can: names (the FC step reports) are recognised the same way. */
    public function test_live_route_checked_permissions_are_kept_on_rename(): void
    {
        $gatedNames = collect(app('router')->getRoutes()->getRoutes())
            ->flatMap(fn ($route) => (array) $route->middleware())
            ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))
            ->map(fn ($m) => substr($m, 4))
            ->unique();
        $menus = \App\Models\SidebarMenu\Menu::whereIn('permission_name', $gatedNames)->get();

        if ($menus->isEmpty()) {
            $this->markTestSkipped('no menu carries a can:-checked permission in this database');
        }

        foreach ($menus as $menu) {
            $this->assertSame(
                $menu->permission_name,
                (new MenuService())->permissionFor($menu->name . ' Renamed', $menu),
                "menu {$menu->id} would rename {$menu->permission_name}, which route middleware checks"
            );
        }
    }

    /** PR #323 F-010. A name with nothing sluggable must not become an empty permission name. */
    public function test_a_menu_name_with_no_letters_or_digits_is_refused(): void
    {
        $this->permission('zz_probe_symbols');
        $menuId = $this->menuFixture('ZZ Probe Symbols', 'zz_probe_symbols');
        $this->actingAs($this->superAdmin());

        $this->put("/sidebar/menus/{$menuId}", ['name' => '!!!'] + $this->editPayload($menuId))
            ->assertSessionHasErrors('name');

        $this->assertSame('zz_probe_symbols', DB::table('menus')->where('id', $menuId)->value('permission_name'));
        $this->assertDatabaseHas('permissions', ['name' => 'zz_probe_symbols']);

        $this->post('/sidebar/menus', ['name' => '!!!'] + $this->createPayload())
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('menus', ['name' => '!!!']);
        $this->assertDatabaseMissing('permissions', ['name' => '']);
    }

    /**
     * PR #323 F-008. Creating a menu whose derived name is already a permission -
     * in another group, or with no menu at all - is refused as update() refuses it,
     * rather than silently sharing that permission's holders.
     */
    public function test_creating_a_menu_on_an_existing_permission_name_is_refused(): void
    {
        $groups = DB::table('menu_groups')->whereNull('deleted_at')->orderBy('id')->limit(2)->get();

        if ($groups->count() < 2) {
            $this->markTestSkipped('needs two menu groups');
        }

        $this->permission('zz_probe_taken');
        $this->menuFixture('ZZ Probe Taken', 'zz_probe_taken');   // in the first group
        $this->permission('zz_probe_orphan');                      // no menu uses it

        $this->actingAs($this->superAdmin());
        $other = ['group_id' => $groups[1]->id, 'category_id' => $groups[1]->category_id];

        $this->post('/sidebar/menus', ['name' => 'ZZ Probe Taken'] + $other + $this->createPayload())
            ->assertSessionHasErrors('name');
        $this->post('/sidebar/menus', ['name' => 'ZZ Probe Orphan'] + $other + $this->createPayload())
            ->assertSessionHasErrors('name');

        $this->assertSame(1, DB::table('menus')->where('permission_name', 'zz_probe_taken')->whereNull('deleted_at')->count());
        $this->assertDatabaseMissing('menus', ['permission_name' => 'zz_probe_orphan']);
    }

    /** Must-succeed control for the refusals above: a new name still creates a menu. */
    public function test_a_super_admin_can_still_create_a_menu_with_a_new_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/sidebar/menus', ['name' => 'ZZ Probe Fresh 323'] + $this->createPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menus', ['name' => 'ZZ Probe Fresh 323', 'permission_name' => 'zz_probe_fresh_323']);
        $this->assertDatabaseHas('permissions', ['name' => 'zz_probe_fresh_323']);
    }

    /**
     * PR #323 F-003. The form's permission-name preview comes from the server,
     * so it matches what Save stores - including transliteration and the cases
     * where an existing menu keeps its permission.
     */
    public function test_the_permission_preview_matches_what_save_stores(): void
    {
        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin);

        foreach (['ZZ A - B', 'ZZ Café Menu', 'ZZ Reports @ FC'] as $name) {
            $preview = $this->getJson(route('sidebar.menus.permission-preview', ['name' => $name]))
                ->assertOk()->json('permission');

            $this->post('/sidebar/menus', ['name' => $name] + $this->createPayload())
                ->assertSessionHasNoErrors();

            $this->assertSame(
                DB::table('menus')->where('name', $name)->value('permission_name'),
                $preview,
                "preview for \"{$name}\" differs from the stored name"
            );
        }

        $this->getJson(route('sidebar.menus.permission-preview', ['name' => '!!!']))
            ->assertOk()->assertJson(['permission' => null])->assertJsonStructure(['error']);

        $this->permission('zz_probe_preview_old');
        $menuId = $this->menuFixture('ZZ Probe Preview', 'zz_probe_preview_old');
        $this->getJson(route('sidebar.menus.permission-preview', ['name' => 'ZZ Probe Preview', 'menu_id' => $menuId]))
            ->assertOk()->assertJson(['permission' => 'zz_probe_preview_old']);

        $level = ob_get_level();
        $page = $this->get(route('sidebar.menus.index'));
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
        $page->assertOk()->assertSee(json_encode(route('sidebar.menus.permission-preview')), false);

        $this->actingAs($this->ordinaryUser())
            ->getJson(route('sidebar.menus.permission-preview', ['name' => 'x']))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        // The transaction rolls back inside parent::tearDown(), after which the
        // container is gone. Nothing reads permissions in between, so flushing
        // here leaves no cache describing the rolled-back rows.
        if ($this->app) {
            $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        }
        parent::tearDown();
    }

    // -- fixtures ---------------------------------------------------------------

    private function permission(string $name): int
    {
        $id = (int) DB::table('permissions')->insertGetId([
            'name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $id;
    }

    /** A menus row inside the test transaction, with a chosen permission name. */
    private function menuFixture(string $name, string $permission): int
    {
        $category = DB::table('sidebar_categories')->value('id');
        $group = DB::table('menu_groups')->value('id');

        if (! $category || ! $group) {
            $this->markTestSkipped('no sidebar category or menu group in this database');
        }

        return (int) DB::table('menus')->insertGetId([
            'category_id' => $category, 'group_id' => $group, 'parent_id' => null,
            'name' => $name, 'route' => null, 'permission_name' => $permission,
            'order' => 900000 + DB::table('menus')->count(), 'icon' => null,
            'is_active' => 1, 'target' => '0', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** What the edit modal posts for an untouched row. */
    private function editPayload(int $menuId): array
    {
        $row = DB::table('menus')->where('id', $menuId)->first();

        return [
            'category_id' => $row->category_id, 'group_id' => $row->group_id, 'parent_id' => $row->parent_id,
            'name' => $row->name, 'route' => $row->route, 'order' => $row->order,
            'icon' => $row->icon, 'is_active' => (string) $row->is_active, 'target' => (string) ($row->target ?? '0'),
        ];
    }

    /** What the create modal posts, minus the name. */
    private function createPayload(): array
    {
        $group = DB::table('menu_groups')->whereNull('deleted_at')->orderBy('id')->first();

        if (! $group) {
            $this->markTestSkipped('no menu group in this database');
        }

        return [
            'category_id' => $group->category_id, 'group_id' => $group->id, 'parent_id' => '',
            'route' => '', 'order' => '', 'icon' => '', 'is_active' => '1', 'target' => '0',
        ];
    }

    private function superAdmin(): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return $user;
    }

    /** An authenticated user holding a role that is NOT Super Admin. */
    private function ordinaryUser(): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', '!=', 'Super Admin')
            ->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no non-Super-Admin user in this database');
        }

        if ($user->hasRole('Super Admin')) {
            $this->markTestSkipped('the only available actor is also a Super Admin');
        }

        return $user;
    }

    private function roleIdOf(User $user): int
    {
        $id = DB::table('model_has_roles')->where('model_id', $user->getKey())->value('role_id');

        if (! $id) {
            $this->markTestSkipped('the actor holds no role to aim the grant at');
        }

        return (int) $id;
    }
}
