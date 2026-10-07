<?php

namespace Tests\Feature;

use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\MenuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The Create modal's Permission Name field has to survive the save.
 *
 * MenuService::update() was fixed earlier in this PR to stop re-deriving the
 * permission from the menu name: 61 of the 243 live menus carry a
 * permission_name that is NOT slug(name), and re-deriving it renamed the wrong
 * permission (or threw "A `x` permission already exists for guard `web`" - a
 * hard 500 on Update for 36 of those rows).
 *
 * store() kept doing exactly that. The Create modal shows an editable Permission
 * Name field, MenuRequest validates it - including a uniqueness rule scoped to
 * the group and parent - and then store() overwrote it with slug(name) anyway.
 * So the field was validated and discarded: the menu was created holding a
 * permission nobody asked for, the permission row created alongside it was the
 * derived one, and the value the author typed only took effect if they opened
 * the row again and pressed Update.
 *
 * That also made the uniqueness rule a lie in both directions - it guarded a
 * value that was never written, and nothing guarded the value that was.
 */
class MenuCreatePermissionNameTest extends TestCase
{
    use DatabaseTransactions;

    /** A category/group pair that exists, so the FKs on menus resolve. */
    private function anchor(): array
    {
        $groupId = DB::table('menu_groups')->value('id');
        $categoryId = DB::table('sidebar_categories')->value('id');

        if (! $groupId || ! $categoryId) {
            $this->markTestSkipped('no menu_groups / sidebar_categories row to hang a menu off');
        }

        return [$categoryId, $groupId];
    }

    private function payload(array $overrides = []): array
    {
        [$categoryId, $groupId] = $this->anchor();

        return array_merge([
            'category_id' => $categoryId,
            'group_id' => $groupId,
            'parent_id' => null,
            'name' => 'Zz Test Menu '.uniqid(),
            'route' => '/zz-test',
            'icon' => null,
            'is_active' => 1,
            // menus.target is enum('1','0'); an int 0 is truncated on insert.
            'target' => '0',
        ], $overrides);
    }

    public function test_a_typed_permission_name_is_the_one_stored(): void
    {
        $typed = 'zz_custom_perm_'.uniqid();

        $menu = app(MenuService::class)->store($this->payload([
            'name' => 'Bank Details Report',
            'permission_name' => $typed,
        ]));

        $this->assertSame(
            $typed,
            $menu->fresh()->permission_name,
            'store() discarded the Permission Name the Create modal submitted and wrote slug(name) instead.'
        );

        $this->assertTrue(
            Permission::where('name', $typed)->where('guard_name', 'web')->exists(),
            'the permission row was created under the derived name, so the menu points at a permission that does not exist'
        );

        $this->assertNotSame(
            'bank_details_report',
            $menu->fresh()->permission_name,
            'the row still carries slug(name), which is the value the Create modal was overridden with'
        );
    }

    /**
     * The field is nullable in MenuRequest, so a blank one still has to produce a
     * working permission - that fallback is the behaviour the old code had right.
     */
    public function test_a_blank_permission_name_still_falls_back_to_the_slug(): void
    {
        $name = 'Zz Fallback Menu '.uniqid();

        foreach ([[], ['permission_name' => null], ['permission_name' => '']] as $variant) {
            $menu = app(MenuService::class)->store($this->payload(
                array_merge(['name' => $name.' '.count($variant)], $variant)
            ));

            $expected = \Illuminate\Support\Str::slug($menu->name, '_');

            $this->assertSame($expected, $menu->fresh()->permission_name);
            $this->assertTrue(
                Permission::where('name', $expected)->where('guard_name', 'web')->exists(),
                'a menu created without a permission name must still get its derived permission row'
            );
        }
    }

    /**
     * store() and update() must agree. The bug was that they did not: the same
     * value, through the same field, was kept by one and thrown away by the other.
     *
     * The update here deliberately does NOT resend permission_name - that is the
     * shape that exposes the disagreement. update() keeps whatever the row
     * already carries, so if store() wrote the derived slug instead of the typed
     * name, the typed name is gone for good and a rename never brings it back.
     */
    public function test_a_later_edit_does_not_resurrect_the_derived_permission_name(): void
    {
        $typed = 'zz_stable_perm_'.uniqid();

        $service = app(MenuService::class);
        $menu = $service->store($this->payload(['permission_name' => $typed]));

        $service->update($menu->id, [
            'category_id' => $menu->category_id,
            'group_id' => $menu->group_id,
            'parent_id' => null,
            'name' => $menu->name.' (renamed)',
            'route' => '/zz-test',
            'is_active' => 1,
        ]);

        $this->assertSame(
            $typed,
            Menu::find($menu->id)->permission_name,
            'the permission name drifted between create and the first edit'
        );
    }
}
