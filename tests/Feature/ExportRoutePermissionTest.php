<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cover for the ungated-export finding (F-003, PR #311).
 *
 * The gate deliberately does NOT use Laravel's can: middleware. This application
 * registers no Gate::before, so can: has no Super Admin bypass and denies everyone
 * when a permission row is absent — the failure that took 16 routes offline in
 * PR #306. The Super Admin test below is the regression test for THAT, and it is the
 * reason this file exists as much as the 403 test is.
 */
class ExportRoutePermissionTest extends TestCase
{
    use DatabaseTransactions;

    /** Each export route and the menus.permission_name of the screen it belongs to. */
    private const GATED = [
        'roles.export'                  => 'roles',
        'roles.permissions.export'      => 'roles',
        'roles.dashboard.export'        => 'roles',
        'sidebar.categories.export'     => 'topbar_category',
        'sidebar.menu-groups.export'    => 'sidemenu_groups',
        'sidebar.menus.export'          => 'menus',
        // The users directory - user name, name, email, contact number, type and
        // role for every row of user_credentials, in one request. It was absent
        // from this list while the file's stated subject was "every export
        // route", which is how the largest PII egress in the module stayed
        // ungated.
        'admin.users.export'            => 'users',
    ];

    /**
     * The WRITE routes that must carry the same gate as the reads above.
     *
     * This is the finding these entries exist for: gating the read path while
     * the write path of the same resource keeps only `auth` buys very little.
     * assignPermission() could grant any permission to any role - including the
     * ones the export gates read - and the sidebar resources could create,
     * rename, re-point or delete entries in the navigation every user sees, and
     * write a file to the public disk through them.
     *
     * name => [verb, permission]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const GATED_WRITES = [
        'assign.roles.permissions'      => ['post',   'roles'],
        'assign.roles.dashboard'        => ['post',   'roles'],
        'dashboard.cards.store'         => ['post',   'roles'],
        'dashboard.cards.update'        => ['put',    'roles'],
        'dashboard.cards.destroy'       => ['delete', 'roles'],
        'roles.store'                   => ['post',   'roles'],
        'roles.update'                  => ['put',    'roles'],
        'roles.destroy'                 => ['delete', 'roles'],
        // The same controller actions under /admin, serving the one live Roles
        // screen. Gating only one set leaves every write reachable by the other.
        'admin.roles.store'             => ['post',   'roles'],
        'admin.roles.update'            => ['put',    'roles'],
        'admin.roles.destroy'           => ['delete', 'roles'],
        'sidebar.categories.store'      => ['post',   'topbar_category'],
        'sidebar.categories.update'     => ['put',    'topbar_category'],
        'sidebar.categories.destroy'    => ['delete', 'topbar_category'],
        'sidebar.categories.status'     => ['get',    'topbar_category'],
        'sidebar.menu-groups.store'     => ['post',   'sidemenu_groups'],
        'sidebar.menu-groups.update'    => ['put',    'sidemenu_groups'],
        'sidebar.menu-groups.destroy'   => ['delete', 'sidemenu_groups'],
        'sidebar.menu-groups.status'    => ['get',    'sidemenu_groups'],
        'sidebar.menus.store'           => ['post',   'menus'],
        'sidebar.menus.update'          => ['put',    'menus'],
        'sidebar.menus.destroy'         => ['delete', 'menus'],
        'sidebar.menus.status'          => ['get',    'menus'],
    ];

    private function url(string $name): string
    {
        if (in_array($name, ['roles.permissions.export', 'roles.dashboard.export'], true)) {
            return route($name, ['id' => 1]);
        }

        if ($name === 'admin.users.export') {
            return route($name, ['format' => 'csv']);
        }

        return route($name);
    }

    /**
     * A URL for a write route, filling any parameter with 1.
     *
     * The id never has to resolve: the gate runs before the controller, and
     * what is being asserted is that the request is refused BEFORE anything is
     * looked up or written.
     */
    private function writeUrl(string $name): string
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route {$name} should exist.");

        return route($name, array_fill_keys($route->parameterNames(), 1));
    }

    /**
     * An existing user stripped of every role and permission.
     *
     * Not a freshly created row: user_credentials has no updated_at column, so
     * Eloquent inserts fail on it. DatabaseTransactions rolls the pivot changes back,
     * so the real user is unaffected once the test finishes.
     */
    private function nobody(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $this->forgetPermissionCache();

        return $user->fresh();
    }

    /** Spatie caches the permission map; without this a just-granted permission is invisible. */
    private function forgetPermissionCache(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * admin.setup.useful_links.export is deliberately NOT in the list above.
     * UsefulLinksSetupController::export() calls its own authorizeAdmin(), which
     * aborts 403 unless the user holds the Admin or Super Admin role — that route was
     * already gated before this change, and stacking a permission gate on top of a
     * role gate would put two disagreeing authorization models on one route. This
     * test pins that it stays gated by SOMETHING.
     */
    public function test_useful_links_export_is_gated_by_its_controller(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/Setup/UsefulLinksSetupController.php'));
        $exportPos = strpos($source, 'public function export(');

        $this->assertNotFalse($exportPos, 'export() should exist on UsefulLinksSetupController.');
        $this->assertStringContainsString('$this->authorizeAdmin();',
            substr($source, $exportPos, 200),
            'The useful-links export relies on its controller-level role gate; do not remove it '
            .'without adding route middleware in its place.');

        $this->actingAs($this->nobody())
            ->get(route('admin.setup.useful_links.export'))
            ->assertForbidden();
    }

    /** Every gated export route carries the middleware — catches a route added later without it. */
    public function test_every_export_route_is_gated(): void
    {
        foreach (self::GATED as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} should exist.");

            $middleware = implode(' ', $route->gatherMiddleware());

            $this->assertStringContainsString('menu.permission:'.$permission, $middleware,
                "Route {$name} must be gated on the permission its own screen uses ({$permission}).");
            $this->assertStringNotContainsString('can:', $middleware,
                'can: has no Super Admin bypass in this application and must not be used here.');
        }
    }

    /** Every write route on these resources carries the same gate as its reads. */
    public function test_every_write_route_is_gated(): void
    {
        foreach (self::GATED_WRITES as $name => [$verb, $permission]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} should exist.");

            $middleware = implode(' ', $route->gatherMiddleware());

            $this->assertStringContainsString('menu.permission:'.$permission, $middleware,
                "Route {$name} writes to a resource whose reads are gated on {$permission}; it must be gated too.");
            $this->assertStringNotContainsString('can:', $middleware,
                'can: has no Super Admin bypass in this application and must not be used here.');
        }
    }

    /**
     * The write gate actually refuses, executed through the real router.
     *
     * The refusal is the half that matters and the half a permission change can
     * silently lose, so it is asserted per route rather than sampled. Nothing is
     * written even if a gate were missing: a 403 here means the request never
     * reached the controller, and any request that did is rolled back with the
     * test.
     */
    public function test_user_without_the_permission_is_denied_every_write(): void
    {
        $user = $this->nobody();

        foreach (self::GATED_WRITES as $name => [$verb, $permission]) {
            $response = $this->actingAs($user)->$verb($this->writeUrl($name));

            $this->assertSame(403, $response->getStatusCode(),
                "{$name} must refuse a user holding no permission, and returned {$response->getStatusCode()}.");
        }
    }

    /**
     * The specific escalation the blocker described: an account with no roles
     * and no permissions posts to assign.roles.permissions to grant itself the
     * `roles` permission, and then walks through the export gate it just opened.
     *
     * Executed end to end rather than reasoned about, because that is exactly
     * how it was demonstrated against the previous head.
     */
    public function test_an_unprivileged_user_cannot_grant_itself_a_permission(): void
    {
        $user = $this->nobody();
        $role = Role::query()->where('name', 'Officer Trainee')->first()
            ?: Role::create(['name' => 'Officer Trainee', 'guard_name' => 'web']);

        $user->assignRole($role);
        $this->forgetPermissionCache();
        $user = $user->fresh();

        $this->assertFalse($user->hasPermissionTo('roles', 'web') ?? false,
            'premise: the actor must not already hold the permission');

        $this->actingAs($user)
            ->post(route('assign.roles.permissions', ['id' => $role->id]), [
                'permission' => 'roles',
                'status' => 1,
            ])
            ->assertForbidden();

        $this->forgetPermissionCache();

        $this->assertFalse(
            $user->fresh()->getAllPermissions()->pluck('name')->contains('roles'),
            'the account escalated itself: assign.roles.permissions granted a permission to a caller who may not administer roles'
        );

        $this->actingAs($user->fresh())
            ->get(route('roles.export'))
            ->assertForbidden();
    }

    /**
     * A route wired with no permission argument must fail CLOSED.
     *
     * An earlier draft of the middleware let that case through as authenticated-only,
     * so a route could read as gated in routes/web.php while being open to every
     * logged-in user. That is the defect this middleware exists to remove, so it must
     * not be reachable through a wiring mistake.
     */
    public function test_middleware_without_a_permission_argument_denies(): void
    {
        Route::middleware(['web', 'auth', 'menu.permission'])
            ->get('__test/menu-permission-no-arg', fn () => 'reached');

        $this->actingAs($this->nobody())
            ->get('__test/menu-permission-no-arg')
            ->assertForbidden();
    }

    /** The gate actually denies a user without the permission. */
    public function test_user_without_the_permission_is_denied(): void
    {
        $user = $this->nobody();

        foreach (array_keys(self::GATED) as $name) {
            $this->actingAs($user)->get($this->url($name))->assertForbidden();
        }
    }

    /** The gate admits a user holding exactly the screen's permission. */
    public function test_user_holding_the_permission_is_admitted(): void
    {
        foreach (self::GATED as $name => $permission) {
            $user = $this->nobody();
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
            $this->forgetPermissionCache();

            $response = $this->actingAs($user->fresh())->get($this->url($name));

            $this->assertNotEquals(403, $response->getStatusCode(),
                "A user holding '{$permission}' must not be denied {$name}.");
        }
    }

    /**
     * Super Admin must pass every gate WITHOUT holding the permission.
     *
     * This is the PR #306 regression: gating with can: on a permission whose row does
     * not exist denied Super Admin too. If this test ever fails, the gate has been
     * swapped for one with no privileged bypass.
     */
    public function test_super_admin_passes_every_gate_without_holding_the_permission(): void
    {
        // Created when absent rather than skipped: a database without the role
        // is an unseeded fixture, not a reason to stop checking that the
        // privileged bypass exists. DatabaseTransactions rolls this back, so a
        // database that already has the role is untouched.
        $role = Role::query()->where('name', 'Super Admin')->first()
            ?: Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $user = $this->nobody();
        $user->assignRole($role);
        $this->forgetPermissionCache();
        $user = $user->fresh();

        foreach (array_keys(self::GATED) as $name) {
            $response = $this->actingAs($user)->get($this->url($name));

            $this->assertNotEquals(403, $response->getStatusCode(),
                "Super Admin was denied {$name} — the gate has lost its privileged bypass.");
        }
    }
}
