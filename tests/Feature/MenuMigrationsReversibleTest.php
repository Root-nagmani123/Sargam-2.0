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

    public static function cardMigrations(): array
    {
        return array_map(fn ($f) => [$f], [
            'faculty cards' => '2026_08_31_000000_add_faculty_total_sessions_and_feedback_dashboard_cards.php',
            'ot marks card' => '2026_08_31_000001_add_ot_discipline_marks_deducted_dashboard_card.php',
            'timetable / counsellee cards' => '2026_09_09_120000_add_faculty_timetable_counsellee_dashboard_cards.php',
            'house widget' => '2026_09_09_140000_add_house_wise_performance_dashboard_widget.php',
            'ot timetable / feedback cards' => '2026_09_14_120000_add_ot_timetable_and_feedback_dashboard_cards.php',
            'whos who card' => '2026_09_17_000004_add_whos_who_faculty_dashboard_card.php',
            'my groups card' => '2026_10_08_100000_add_my_groups_dashboard_card.php',
        ]);
    }

    /** The card keys a card migration creates, read from its own constants. */
    private function cardKeys(object $migration): array
    {
        $c = (new \ReflectionClass($migration))->getConstants();

        return array_values(array_filter(array_merge(
            array_keys($c['CARDS'] ?? []),
            array_keys($c['NEW_CARDS'] ?? []),
            [$c['CARD_KEY'] ?? null, $c['KEY'] ?? null],
        )));
    }

    /** @dataProvider cardMigrations */
    public function test_up_keeps_an_existing_card_as_an_admin_left_it_and_adds_a_missing_one(string $file): void
    {
        $migration = require database_path('migrations/'.$file);
        $keys = $this->cardKeys($migration);
        $this->assertNotEmpty($keys, "{$file}: no card keys found");
        $role = DB::table('roles')->value('id');

        // Pre-existing: an admin relabelled, re-iconed and reordered the first card and linked a role.
        $kept = array_shift($keys);
        $edited = ['label' => 'Admin label', 'icon' => 'admin_icon', 'color_class' => 'admin-colour', 'sort_order' => 4242];
        DB::table('dashboard_cards')->where('key', $kept)->exists()
            ? DB::table('dashboard_cards')->where('key', $kept)->update($edited)
            : DB::table('dashboard_cards')->insert(['key' => $kept] + $edited);
        $keptId = DB::table('dashboard_cards')->where('key', $kept)->value('id');
        DB::table('role_dashboard_cards')->where('dashboard_card_id', $keptId)->delete();
        DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $keptId]);

        // Missing (positive control): as on an environment that never ran the migration.
        $missing = DB::table('dashboard_cards')->whereIn('key', $keys)->pluck('id');
        DB::table('role_dashboard_cards')->whereIn('dashboard_card_id', $missing)->delete();
        DB::table('dashboard_cards')->whereIn('id', $missing)->delete();

        $migration->up();

        $row = DB::table('dashboard_cards')->where('key', $kept)->first(['id', 'label', 'icon', 'color_class', 'sort_order']);
        $this->assertSame($keptId, $row->id);
        $this->assertSame($edited, ['label' => $row->label, 'icon' => $row->icon, 'color_class' => $row->color_class, 'sort_order' => (int) $row->sort_order],
            "{$file} up() must not overwrite a card that already exists");
        $this->assertSame(1, DB::table('role_dashboard_cards')->where('dashboard_card_id', $keptId)->where('role_id', $role)->count(),
            'the existing role link is kept, not duplicated');

        foreach ($keys as $key) {
            $this->assertSame(1, DB::table('dashboard_cards')->where('key', $key)->count(), "{$file} up() creates missing card {$key}");
        }

        $migration->down();
        $this->assertSame($keptId, DB::table('dashboard_cards')->where('key', $kept)->value('id'), 'down() keeps the pre-existing card');
        $this->assertSame(1, DB::table('role_dashboard_cards')->where('dashboard_card_id', $keptId)->where('role_id', $role)->count(),
            'down() keeps the pre-existing role link');
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
