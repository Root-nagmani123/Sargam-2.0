<?php

namespace Tests\Feature;

use App\Models\SidebarMenu\Menu;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A menu that only holds sub-menus must be creatable and editable.
 *
 * MenuRequest requires a Url or an Attachment unless `is_container` is posted,
 * and the back-fill migration set that flag on every existing parent with no
 * destination. The form, however, rendered no such control — it was added in
 * bbd156591 and removed again in d6277bd44 — while the page's JavaScript still
 * bound to `#is_container`. The result was that the structural rows of the menu
 * tree, the thing this module exists to maintain, could not be saved at all:
 * create was refused, and every back-filled container was refused on edit with
 * an error naming a checkbox that was not on the screen.
 *
 * These tests post what the FORM posts. That is the whole point — the validator
 * was fine in isolation; it was the pairing of validator and form that was
 * broken, so a test that posts is_container by hand would have stayed green
 * throughout.
 */
class MenuContainerSaveTest extends TestCase
{
    private bool $inTransaction = false;

    /**
     * The transaction is opened by hand rather than by DatabaseTransactions.
     *
     * That trait is booted from parent::setUp(), so it opens the connection and
     * throws before the guard below can run: with the trait in place this file
     * reported 4 errors on a host with no database instead of skipping, and the
     * markTestSkipped() call was dead code.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /**
     * The container flag AS THE FORM WOULD SEND IT.
     *
     * An unticked checkbox sends nothing; a checkbox that is not on the page at
     * all also sends nothing — and that is precisely the bug. Deriving the
     * payload from the rendered form instead of hard-coding is_container is what
     * makes the create and edit tests below regression tests: delete the control
     * again and they fail, because the payload loses the flag with it.
     *
     * @return array<string, string>
     */
    private function containerFlagAsFormSendsIt(): array
    {
        $html = file_get_contents(resource_path('views/SidebarMenu/menus/index.blade.php'));

        return str_contains($html, 'name="is_container"')
            ? ['is_container' => '1']
            : [];
    }

    private function admin(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    /**
     * The control must exist, with the name and id the rule and the JS expect.
     *
     * Cheap, and it is the exact thing that regressed: the rule and the script
     * both survived the commit that deleted the input.
     */
    public function test_the_form_renders_the_container_control(): void
    {
        $html = file_get_contents(resource_path('views/SidebarMenu/menus/index.blade.php'));

        $this->assertStringContainsString('name="is_container"', $html);
        $this->assertStringContainsString('id="is_container"', $html);
    }

    public function test_a_parent_only_menu_can_be_created(): void
    {
        $group = DB::table('menu_groups')->orderBy('id')->first();

        if (! $group) {
            $this->markTestSkipped('No menu_groups row to attach a probe menu to.');
        }

        $name = 'Container Probe ' . uniqid();

        $response = $this->actingAs($this->admin())
            ->from('/sidebar/menus')
            ->post('/sidebar/menus', [
                // Exactly the fields the form posts for a parent-only menu:
                // a name, its group, the container tick — no route, no file.
                'name'        => $name,
                'category_id' => $group->category_id,
                'group_id'    => $group->id,
                'is_active'   => '1',
            ] + $this->containerFlagAsFormSendsIt());

        $response->assertSessionHasNoErrors();

        $menu = Menu::where('name', $name)->first();

        $this->assertNotNull($menu, 'a parent-only menu must be saved');
        $this->assertSame(1, (int) $menu->is_container);
        $this->assertTrue(blank($menu->route), 'a container has no destination of its own');
    }

    public function test_a_back_filled_container_can_be_edited_without_changing_it(): void
    {
        $menu = Menu::whereNotNull('is_container')
            ->where('is_container', 1)
            ->first();

        if (! $menu) {
            // Make one the same way the migration did, so the test still asserts
            // the property on a database that has not run the back-fill.
            $group = DB::table('menu_groups')->orderBy('id')->first();

            if (! $group) {
                $this->markTestSkipped('No menu_groups row to attach a probe menu to.');
            }

            $menu = new Menu();
            $menu->name         = 'Container Probe Edit ' . uniqid();
            $menu->category_id  = $group->category_id;
            $menu->group_id     = $group->id;
            $menu->is_container = 1;
            $menu->is_active    = 1;
            $menu->save();
        }

        $response = $this->actingAs($this->admin())
            ->from('/sidebar/menus/' . $menu->id . '/edit')
            ->put('/sidebar/menus/' . $menu->id, [
                'name'        => $menu->name,
                'category_id' => $menu->category_id,
                'group_id'    => $menu->group_id,
                'parent_id'   => $menu->parent_id,
                'is_active'   => (string) ((int) $menu->is_active),
            ] + $this->containerFlagAsFormSendsIt());

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            (int) Menu::find($menu->id)->is_container,
            'editing a container must not silently un-flag it'
        );
    }

    /**
     * The rule still does its job: a menu that is neither a container nor given
     * a destination is refused.
     */
    public function test_a_menu_with_no_destination_and_no_tick_is_still_refused(): void
    {
        $group = DB::table('menu_groups')->orderBy('id')->first();

        if (! $group) {
            $this->markTestSkipped('No menu_groups row to attach a probe menu to.');
        }

        $name = 'Container Probe Refused ' . uniqid();

        $this->actingAs($this->admin())
            ->from('/sidebar/menus')
            ->post('/sidebar/menus', [
                'name'        => $name,
                'category_id' => $group->category_id,
                'group_id'    => $group->id,
                'is_active'   => '1',
            ])
            ->assertSessionHasErrors('route');

        $this->assertNull(Menu::where('name', $name)->first());
    }
}
