<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The menu and dashboard-card migrations of PR #334 (F-027 / F-036).
 *
 * up() leaves any existing row alone and records nothing about what it inserted,
 * so a down() that deleted by name or key removed rows an administrator had made
 * before the migration ran. down() is now forward-fix only: it must change nothing.
 * up() must still create what is missing, grant it, and flush Spatie's cache so a
 * holder sees a raw-inserted grant at once.
 *
 * All of these migrations are DML only (no Schema:: / DDL), so everything runs
 * inside the test's transaction and is rolled back.
 */
class MenuMigrationsReversibleTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public static function menuMigrations(): array
    {
        return [
            'leave on behalf' => ['2026_09_17_000002_add_leave_on_behalf_menu.php', 'apply_leave_on_behalf_of_ot', 'admin/leave-on-behalf', 'stationed_leave_master'],
            'leave nature master' => ['2026_09_18_000001_add_leave_nature_master_menu_and_seed_leave_natures.php', 'master_leave_nature_master', 'master/leave-nature-master', 'master_medical_case_master'],
        ];
    }

    public static function dataMigrations(): array
    {
        return array_map(fn ($f) => [$f], [
            'faculty cards' => '2026_08_31_000000_add_faculty_total_sessions_and_feedback_dashboard_cards.php',
            'ot marks card' => '2026_08_31_000001_add_ot_discipline_marks_deducted_dashboard_card.php',
            'timetable / counsellee cards' => '2026_09_09_120000_add_faculty_timetable_counsellee_dashboard_cards.php',
            'house widget' => '2026_09_09_140000_add_house_wise_performance_dashboard_widget.php',
            'ot timetable / feedback cards' => '2026_09_14_120000_add_ot_timetable_and_feedback_dashboard_cards.php',
            'leave on behalf menu' => '2026_09_17_000002_add_leave_on_behalf_menu.php',
            'whos who card' => '2026_09_17_000004_add_whos_who_faculty_dashboard_card.php',
            'leave nature menu' => '2026_09_18_000001_add_leave_nature_master_menu_and_seed_leave_natures.php',
        ]);
    }

    private function snapshot(): array
    {
        $rows = fn (string $table, array $cols) => DB::table($table)->orderBy($cols[0])->get($cols)
            ->map(fn ($r) => array_values((array) $r))->all();

        return [
            'menus' => $rows('menus', ['id', 'route', 'permission_name']),
            'permissions' => $rows('permissions', ['id', 'name']),
            'role_has_permissions' => $rows('role_has_permissions', ['permission_id', 'role_id']),
            'leave_nature_master' => $rows('leave_nature_master', ['pk', 'nature_name', 'leave_type']),
            'dashboard_cards' => $rows('dashboard_cards', ['id', 'key', 'label']),
            'role_dashboard_cards' => $rows('role_dashboard_cards', ['dashboard_card_id', 'role_id']),
        ];
    }

    /** @dataProvider dataMigrations */
    public function test_down_leaves_every_existing_row_alone(string $file): void
    {
        $before = $this->snapshot();

        (require database_path('migrations/'.$file))->down();

        $this->assertSame($before, $this->snapshot(), "{$file} down() must not delete or detach rows it cannot prove it created");
    }

    /** @dataProvider menuMigrations */
    public function test_up_recreates_a_missing_menu_grants_it_and_flushes_the_cache(string $file, string $permission, string $route, string $sibling): void
    {
        $siblingId = DB::table('permissions')->where('name', $sibling)->value('id');
        if (! $siblingId) {
            $this->markTestSkipped("sibling permission {$sibling} does not exist here");
        }

        $registrar = app(PermissionRegistrar::class);

        try {
            // As on an environment that never ran the migration.
            $permId = DB::table('permissions')->where('name', $permission)->value('id');
            if ($permId) {
                DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
            DB::table('menus')->where('route', $route)->delete();
            $registrar->forgetCachedPermissions();
            $registrar->getPermissions(); // warm the cache WITHOUT the permission

            (require database_path('migrations/'.$file))->up();

            $this->assertTrue(DB::table('menus')->where('route', $route)->exists(), 'up() creates the menu');
            $newId = DB::table('permissions')->where('name', $permission)->value('id');
            $this->assertNotNull($newId, 'up() creates the permission');

            $siblingRoles = DB::table('role_has_permissions')->where('permission_id', $siblingId)->pluck('role_id')->sort()->values()->all();
            $granted = DB::table('role_has_permissions')->where('permission_id', $newId)->pluck('role_id')->sort()->values()->all();
            $this->assertSame($siblingRoles, $granted, 'granted to exactly the roles holding the sibling permission');

            $holder = $siblingRoles === [] ? null : DB::table('model_has_roles')->whereIn('role_id', $siblingRoles)
                ->where('model_type', User::class)->value('model_id');
            if ($holder) {
                $this->assertTrue(User::findOrFail($holder)->hasPermissionTo($permission), 'the flush in up() makes the grant visible');
            }
        } finally {
            // The transaction rolls these rows back; never leave a cache built from them.
            $registrar->forgetCachedPermissions();
        }
    }
}
