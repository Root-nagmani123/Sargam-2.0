<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberController;
use App\Models\EmployeeRoleMapping;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for PR #343 review, F-001 to F-004: keeping the Member wizard's
 * Role Assignment options (user_role_master) in step with Role & Permission > Roles.
 */
class RoleOptionSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not reachable: ' . $e->getMessage());
        }
    }

    private function makeTestUser(string $suffix): User
    {
        // user_credentials has no created_at/updated_at columns, and User::$fillable
        // doesn't cover these HR fields, so both are set directly.
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'role_opt_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    private function superAdmin(): User
    {
        $admin = $this->makeTestUser('admin');
        $admin->assignRole('Super Admin');

        return $admin;
    }

    private function makeOption(string $name, int $active = 1): UserRoleMaster
    {
        $option = new UserRoleMaster();
        $option->forceFill([
            'user_role_name' => $name,
            'user_role_display_name' => $name,
            'active_inactive' => $active,
            'created_date' => now(),
            'updated_date' => now(),
        ])->save();

        return $option;
    }

    private function makeHeldTag(string $name): UserRoleMaster
    {
        $option = $this->makeOption($name);
        EmployeeRoleMapping::insert([
            'user_credentials_pk' => $this->makeTestUser('tagged')->pk,
            'user_role_master_pk' => $option->pk,
        ]);

        return $option;
    }

    public function test_creating_a_role_adds_its_role_assignment_option(): void
    {
        $name = 'Opt Test Role ' . uniqid();

        $this->actingAs($this->superAdmin())
            ->post(route('roles.store'), ['name' => $name])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Role::where('name', $name)->exists());
        $pk = UserRoleMaster::where('user_role_display_name', $name)->value('pk');
        $this->assertNotNull($pk);
        $this->assertTrue(UserRoleMaster::getUserRoleList()->has($pk));
    }

    /** F-001: a new role must not adopt an option employees already hold. */
    public function test_creating_a_role_named_like_a_held_option_is_refused(): void
    {
        $name = 'Opt Test Tag ' . uniqid();
        $this->makeHeldTag($name);

        $this->actingAs($this->superAdmin())
            ->post(route('roles.store'), ['name' => $name])
            ->assertSessionHasErrors('name');

        $this->assertFalse(Role::where('name', $name)->exists());
    }

    /** F-001: nor may a rename. */
    public function test_renaming_a_role_to_a_held_option_name_is_refused(): void
    {
        $roleName = 'Opt Test Role ' . uniqid();
        $role = Role::create(['name' => $roleName]);
        UserRoleMaster::ensureOptionForRole($roleName);
        $tagName = 'Opt Test Tag ' . uniqid();
        $this->makeHeldTag($tagName);

        $this->actingAs($this->superAdmin())
            ->put(route('roles.update', $role->id), ['name' => $tagName])
            ->assertSessionHasErrors('name');

        $this->assertSame($roleName, $role->fresh()->name);
    }

    public function test_renaming_a_role_renames_its_option(): void
    {
        $oldName = 'Opt Test Old ' . uniqid();
        $newName = 'Opt Test New ' . uniqid();
        $role = Role::create(['name' => $oldName]);
        $option = UserRoleMaster::ensureOptionForRole($oldName);

        $this->actingAs($this->superAdmin())
            ->put(route('roles.update', $role->id), ['name' => $newName])
            ->assertSessionHasNoErrors();

        $this->assertSame($newName, $option->fresh()->user_role_display_name);
    }

    /** F-004 S1: renaming keeps a deactivated option deactivated, with no new row. */
    public function test_renaming_keeps_a_deactivated_option_inactive(): void
    {
        $oldName = 'Opt Test Old ' . uniqid();
        $newName = 'Opt Test New ' . uniqid();
        $option = $this->makeOption($oldName, 0);

        UserRoleMaster::renameOptionForRole($oldName, $newName);

        $option->refresh();
        $this->assertSame($newName, $option->user_role_display_name);
        $this->assertSame(0, (int) $option->active_inactive);
        $this->assertSame(1, UserRoleMaster::where('user_role_display_name', $newName)->count());
    }

    /** F-004 S2: no active duplicate of an inactive option. */
    public function test_ensure_option_does_not_duplicate_an_inactive_option(): void
    {
        $name = 'Opt Test Role ' . uniqid();
        $option = $this->makeOption($name, 0);

        $this->assertSame($option->pk, UserRoleMaster::ensureOptionForRole($name)->pk);
        $this->assertSame(1, UserRoleMaster::where('user_role_display_name', $name)->count());
        $this->assertFalse(UserRoleMaster::getUserRoleList()->has($option->pk));
    }

    /** F-003: an active option that is simply not offered must not be called inactive. */
    public function test_warning_does_not_call_an_active_unoffered_option_inactive(): void
    {
        $tag = $this->makeOption('Opt Test Tag ' . uniqid());

        $method = new ReflectionMethod(MemberController::class, 'withoutInactiveRoles');
        $method->setAccessible(true);
        [, $warning] = $method->invoke(new MemberController(), [$tag->pk], true);

        $this->assertStringContainsString('is not managed from the Member wizard', $warning);
        $this->assertStringNotContainsString('no longer an active role', $warning);
    }
}
