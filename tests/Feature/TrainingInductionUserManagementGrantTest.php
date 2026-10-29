<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cover for PR #311 review condition 1 (finding F-017).
 *
 * PR #311 gated every `admin.users.*` route on `menu.permission:users` and the Roles
 * screen on `menu.permission:roles`. That narrowed both screens to Super Admin, while
 * `resources/views/components/menu/setup_activities.blade.php` still advertises them to
 * Training-Induction — 10 accounts that got HTTP 403 on a link the sidebar offered them.
 *
 * The migration `2026_09_17_000001_grant_user_management_to_training_induction`
 * closes that. This test runs the migration inside the test transaction and asserts the
 * before/after, rather than asserting the granted state directly: the suite runs against
 * an existing database with `DatabaseTransactions`, so a test that simply expected
 * Training-Induction to hold `users` would be red on every machine where the migration
 * has not been run yet, and green for reasons unrelated to the migration where it has.
 * Running it here ties the assertion to the migration itself.
 */
class TrainingInductionUserManagementGrantTest extends TestCase
{
    use DatabaseTransactions;

    private const MIGRATION = 'database/migrations/2026_09_17_000001_grant_user_management_to_training_induction.php';

    private const ROLE = 'Training-Induction';

    /** The two screens the sidebar offers this role, and the permission each needs. */
    private const SCREENS = [
        'admin.users.index' => 'users',
        'admin.roles.index' => 'roles',
    ];

    public function test_the_migration_restores_both_screens_for_training_induction(): void
    {
        $role = $this->role();
        $actor = $this->actorHoldingOnly($role);

        foreach (array_keys(self::SCREENS) as $route) {
            $this->assertSame(403, $this->status($actor, $route),
                "Baseline: {$route} should be denied before the grant. If this fails the "
                .'permission was granted by some other means, and the rest of this test proves nothing.');
        }

        $this->migration()->up();
        $this->forgetPermissionCache();
        $actor = $actor->fresh();

        foreach (array_keys(self::SCREENS) as $route) {
            $this->assertSame(200, $this->status($actor, $route),
                "After the migration, {$route} must be reachable by a Training-Induction account.");
        }
    }

    /** Running it twice must not duplicate the pivot rows or fail. */
    public function test_the_migration_is_idempotent(): void
    {
        $role = $this->role();

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertSame(
            count(self::SCREENS),
            DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $role->getKey())
                ->whereIn('permissions.name', array_values(self::SCREENS))
                ->count(),
            'A second up() must leave exactly one pivot row per permission.'
        );
    }

    /** down() must put the role back where it was, so the change is reversible. */
    public function test_the_migration_is_reversible(): void
    {
        $role = $this->role();
        $actor = $this->actorHoldingOnly($role);

        $migration = $this->migration();
        $migration->up();
        $this->forgetPermissionCache();
        $this->assertSame(200, $this->status($actor->fresh(), 'admin.users.index'));

        $migration->down();
        $this->forgetPermissionCache();

        $this->assertSame(403, $this->status($actor->fresh(), 'admin.users.index'),
            'down() must revoke the grant, or the migration cannot be rolled back.');
    }

    // ---------------------------------------------------------------- helpers

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function role(): Role
    {
        $role = Role::where('name', self::ROLE)->where('guard_name', 'web')->first();

        if (! $role) {
            $this->markTestSkipped('The '.self::ROLE.' role does not exist in this database.');
        }

        return $role;
    }

    private function actorHoldingOnly(Role $role): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->assignRole($role);
        $this->forgetPermissionCache();

        return $user->fresh();
    }

    /**
     * Dispatch and return the status code.
     *
     * The buffer unwind is not incidental: every admin page in this application leaves
     * one output-buffer level open holding stray whitespace, and PHPUnit reports any test
     * that renders one as *risky* rather than passing. Restoring the level keeps this
     * test's result about this test.
     */
    private function status(User $actor, string $routeName): int
    {
        $level = ob_get_level();

        $response = $this->actingAs($actor)->get(route($routeName));

        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        return $response->getStatusCode();
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
