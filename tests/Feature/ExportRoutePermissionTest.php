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
    ];

    private function url(string $name): string
    {
        return in_array($name, ['roles.permissions.export', 'roles.dashboard.export'], true)
            ? route($name, ['id' => 1])
            : route($name);
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
        $role = Role::query()->where('name', 'Super Admin')->first();

        if (! $role) {
            $this->markTestSkipped('No Super Admin role in this database.');
        }

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
