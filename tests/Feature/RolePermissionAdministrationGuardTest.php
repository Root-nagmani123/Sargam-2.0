<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SidebarMenu\MenuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
    use DatabaseTransactions;

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

        $this->actingAs($actor)
            ->put("/sidebar/menus/{$menu->id}", [
                'category_id' => $menu->category_id,
                'group_id' => $menu->group_id,
                'name' => $menu->name,
                'permission_name' => 'directory.export',
            ])
            ->assertForbidden();

        $this->assertSame(
            $menu->permission_name,
            DB::table('menus')->where('id', $menu->id)->value('permission_name'),
            'a refused menu edit must leave the row alone'
        );
    }

    /**
     * The control. Without it a guard that refuses EVERYONE would pass the three
     * tests above, and a review of those alone could not tell the two apart.
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

    // -- fixtures ---------------------------------------------------------------

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
