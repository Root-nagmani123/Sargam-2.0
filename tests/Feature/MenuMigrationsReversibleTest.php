<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-027 (PR #334): the two menu migrations grant their permission with raw
 * inserts, so they must flush Spatie's permission cache — and both must survive
 * down() then up(). Both migrations are DML only (no Schema:: / DDL), so the
 * whole round trip runs inside the test's transaction and is rolled back.
 *
 * This is local evidence only. The staging migrate, and a granted role seeing
 * both menus there, remain a human check (condition 5).
 */
class MenuMigrationsReversibleTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public static function migrations(): array
    {
        return [
            'leave on behalf' => ['2026_09_17_000002_add_leave_on_behalf_menu.php', 'apply_leave_on_behalf_of_ot', 'admin/leave-on-behalf', 'stationed_leave_master'],
            'leave nature master' => ['2026_09_18_000001_add_leave_nature_master_menu_and_seed_leave_natures.php', 'master_leave_nature_master', 'master/leave-nature-master', 'master_medical_case_master'],
        ];
    }

    /** @dataProvider migrations */
    public function test_down_then_up_restores_the_menu_and_a_granted_role_holds_the_permission(string $file, string $permission, string $route, string $sibling): void
    {
        $siblingId = DB::table('permissions')->where('name', $sibling)->value('id');
        if (! $siblingId) {
            $this->markTestSkipped("sibling permission {$sibling} does not exist here");
        }

        $migration = require database_path('migrations/'.$file);

        $migration->down();
        $this->assertFalse(DB::table('menus')->where('route', $route)->exists(), 'down() removes the menu');
        $this->assertFalse(DB::table('permissions')->where('name', $permission)->exists(), 'down() removes the permission');

        $migration->up();
        $this->assertTrue(DB::table('menus')->where('route', $route)->exists(), 'up() restores the menu');
        $permId = DB::table('permissions')->where('name', $permission)->value('id');
        $this->assertNotNull($permId, 'up() restores the permission');

        $siblingRoles = DB::table('role_has_permissions')->where('permission_id', $siblingId)->pluck('role_id')->sort()->values()->all();
        $granted = DB::table('role_has_permissions')->where('permission_id', $permId)->pluck('role_id')->sort()->values()->all();
        $this->assertSame($siblingRoles, $granted, 'granted to exactly the roles holding the sibling permission');

        // Spatie reads its cached map; up() flushed it, so a holder of a granted
        // role sees the new permission straight away.
        if ($siblingRoles !== []) {
            $holder = DB::table('model_has_roles')->whereIn('role_id', $siblingRoles)
                ->where('model_type', User::class)->value('model_id');
            if ($holder) {
                app(PermissionRegistrar::class)->forgetCachedPermissions(); // isolate from earlier tests' cache reads
                $migration->down();
                app(PermissionRegistrar::class)->getPermissions(); // warm the cache WITHOUT the permission
                $migration->up();                                   // must flush it again
                $this->assertTrue(User::findOrFail($holder)->hasPermissionTo($permission), 'the flush in up() makes the grant visible');
            }
        }
    }
}
