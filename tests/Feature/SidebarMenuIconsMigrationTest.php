<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * 2026_11_13_100000_fill_missing_sidebar_menu_icons gives every icon-less menu
 * an icon, without touching one an admin has already chosen.
 */
class SidebarMenuIconsMigrationTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const FILE = '2026_11_13_100000_fill_missing_sidebar_menu_icons.php';

    private function icon(string $route, string $name): ?string
    {
        return DB::table('menus')->where('route', $route)->where('name', $name)->value('icon');
    }

    public function test_up_fills_blank_icons_and_keeps_an_admins_choice_and_down_undoes_only_its_own(): void
    {
        $blank = ['leave/apply', 'Apply Leave'];
        $chosen = ['leave/my-leave', 'My Leave'];
        if (! DB::table('menus')->where('route', $blank[0])->where('name', $blank[1])->exists()
            || ! DB::table('menus')->where('route', $chosen[0])->where('name', $chosen[1])->exists()) {
            $this->markTestSkipped('needs the Apply Leave and My Leave menus');
        }

        DB::table('menus')->where('route', $blank[0])->where('name', $blank[1])->update(['icon' => null]);
        DB::table('menus')->where('route', $chosen[0])->where('name', $chosen[1])->update(['icon' => 'admin_pick']);
        $migration = require database_path('migrations/'.self::FILE);

        $migration->up();
        $this->assertSame('event_available', $this->icon(...$blank));
        $this->assertSame('admin_pick', $this->icon(...$chosen), 'an icon set in the Menus screen is kept');

        $migration->down();
        $this->assertNull($this->icon(...$blank));
        $this->assertSame('admin_pick', $this->icon(...$chosen));
    }

    public function test_every_icon_it_sets_exists_in_the_material_symbols_font(): void
    {
        $names = [];
        foreach (file(resource_path('data/material-symbols-rounded.codepoints'), FILE_IGNORE_NEW_LINES) as $line) {
            $names[strtok(trim($line), ' ')] = true;
        }

        $source = file_get_contents(database_path('migrations/'.self::FILE));
        preg_match_all("/\\['[^']*', '[^']*', '([a-z0-9_]+)'\\]/", $source, $m);

        $this->assertCount(31, $m[1]);
        foreach ($m[1] as $icon) {
            $this->assertArrayHasKey($icon, $names, "'{$icon}' is not a Material Symbols Rounded icon");
        }
    }
}
