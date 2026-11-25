<?php

namespace Tests\Feature;

use App\Console\Commands\ReconcileEmployeeRoleMappings;
use App\Http\Controllers\Admin\MemberController;
use App\Models\EmployeeRoleMapping;
use App\Models\User;
use App\Models\UserRoleMaster;
use App\Support\RoleNames;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Role & Permission > Users and the Member wizard's Role Assignment step must show the
 * same roles for an employee.
 *
 * The Users screen wrote Spatie roles only, while Step 3 read employee_role_mapping, so
 * an employee whose roles were changed on the Users screen kept the old ticks in the
 * employee module (seen live: Users listed "Employee, Training IST dk", Step 3 ticked
 * "Mess-Admin").
 */
class EmployeeRoleSyncTest extends TestCase
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

    private function makeTestUser(string $suffix, ?string $category = 'E', ?int $employeePk = null): User
    {
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'role_sync_test_' . $suffix . '_' . uniqid();
        $user->user_category = $category;
        $user->user_id = $employeePk;
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    private function makeSuperAdmin(): User
    {
        $admin = $this->makeTestUser('admin');
        $admin->assignRole('Super Admin');

        return $admin;
    }

    /**
     * Two offered Step 3 options that name grantable roles (not Super Admin), as
     * [[optionPk, roleName], [optionPk, roleName]].
     */
    private function twoOfferedRoles(): array
    {
        $roleByKey = Role::pluck('name')
            ->reject(fn ($name) => RoleNames::isNotGrantableFromMemberWizard($name))
            ->keyBy(fn ($name) => RoleNames::normalize($name));

        $picked = [];

        foreach (UserRoleMaster::getUserRoleList() as $pk => $displayName) {
            $key = RoleNames::normalize((string) $displayName);

            if (isset($roleByKey[$key])) {
                $picked[] = [(int) $pk, $roleByKey[$key]];
            }

            if (count($picked) === 2) {
                return $picked;
            }
        }

        $this->markTestSkipped('Fixture assumption: two offered Role Assignment options naming grantable roles.');
    }

    private function mappedOptions(User $user): array
    {
        return EmployeeRoleMapping::where('user_credentials_pk', $user->pk)
            ->orderBy('user_role_master_pk')
            ->pluck('user_role_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->all();
    }

    private function callPrivate(string $method, ...$args)
    {
        $controller = new MemberController();
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$args);
    }

    /** Users screen → employee_role_mapping: the old tick goes, the new one appears. */
    public function test_assigning_roles_on_the_users_screen_updates_the_member_role_options(): void
    {
        [[$oldOptionPk], [$newOptionPk, $newRoleName]] = $this->twoOfferedRoles();

        $admin = $this->makeSuperAdmin();
        $target = $this->makeTestUser('target');
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $oldOptionPk]);

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [Role::where('name', $newRoleName)->value('id')],
        ])->assertRedirect();

        $this->assertSame([$newOptionPk], $this->mappedOptions($target));
    }

    /** Options Step 3 does not offer (HR-only tags) are kept, as the wizard keeps them. */
    public function test_users_screen_keeps_options_the_member_wizard_does_not_offer(): void
    {
        [[, $roleName]] = $this->twoOfferedRoles();

        $hiddenOptionPk = UserRoleMaster::whereNotIn('pk', UserRoleMaster::getUserRoleList()->keys()->all())
            ->value('pk');

        if ($hiddenOptionPk === null) {
            $this->markTestSkipped('Fixture assumption: a user_role_master option Step 3 does not offer.');
        }

        $admin = $this->makeSuperAdmin();
        $target = $this->makeTestUser('hidden');
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $hiddenOptionPk]);

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [Role::where('name', $roleName)->value('id')],
        ])->assertRedirect();

        $this->assertContains((int) $hiddenOptionPk, $this->mappedOptions($target));
    }

    /** Removing every role on the Users screen clears the offered ticks. */
    public function test_removing_all_roles_on_the_users_screen_clears_the_member_role_options(): void
    {
        [[$optionPk, $roleName]] = $this->twoOfferedRoles();

        $admin = $this->makeSuperAdmin();
        $target = $this->makeTestUser('clear');
        $target->assignRole($roleName);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $optionPk]);

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [],
        ])->assertRedirect();

        $this->assertSame([], $this->mappedOptions($target));
    }

    /** Only employee logins have a Role Assignment step; other accounts are left alone. */
    public function test_users_screen_does_not_touch_mappings_of_non_employee_accounts(): void
    {
        [[$oldOptionPk], [, $newRoleName]] = $this->twoOfferedRoles();

        $admin = $this->makeSuperAdmin();
        $target = $this->makeTestUser('student', 'S');
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $oldOptionPk]);

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [Role::where('name', $newRoleName)->value('id')],
        ])->assertRedirect();

        $this->assertSame([$oldOptionPk], $this->mappedOptions($target));
    }

    /**
     * Existing drift (mapping says one role, the login holds another): Step 3 ticks the
     * role the login actually holds.
     */
    public function test_step3_ticks_the_roles_the_login_holds_not_a_stale_mapping(): void
    {
        [[$staleOptionPk], [$heldOptionPk, $heldRoleName]] = $this->twoOfferedRoles();

        $employeePk = DB::table('employee_master')->insertGetId(['first_name' => 'Sync', 'last_name' => 'Employee']);
        $target = $this->makeTestUser('drift', 'E', $employeePk);
        $target->assignRole($heldRoleName);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $staleOptionPk]);

        $this->assertSame([$heldOptionPk], $this->callPrivate('heldOfferedRoleOptions', $target->pk));
    }

    /**
     * PR #354 review, F-003 / F-006: members:reconcile-role-mappings. The dry run writes
     * nothing and names, per option, the rows it would add and remove (each one changes
     * who receives that option's mapping-driven notifications). --apply mirrors the
     * mapping onto the held roles, and a second run finds nothing to change.
     */
    public function test_reconcile_command_reports_changes_by_option_and_applies_them(): void
    {
        [[$staleOptionPk], [$heldOptionPk, $heldRoleName]] = $this->twoOfferedRoles();
        $names = UserRoleMaster::whereIn('pk', [$staleOptionPk, $heldOptionPk])->pluck('user_role_name', 'pk');

        $target = $this->makeTestUser('reconcile');
        $target->assignRole($heldRoleName);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $staleOptionPk]);

        $this->artisan('members:reconcile-role-mappings', ['--user' => $target->pk])
            ->expectsOutputToContain('Dry run: nothing was written.')
            ->expectsTable(['Option pk', 'Option', 'Logins gaining it', 'Logins losing it', 'Read by'], collect([
                [$staleOptionPk, (string) $names[$staleOptionPk], 0, 1, ReconcileEmployeeRoleMappings::notificationLookupFor((string) $names[$staleOptionPk])],
                [$heldOptionPk, (string) $names[$heldOptionPk], 1, 0, ReconcileEmployeeRoleMappings::notificationLookupFor((string) $names[$heldOptionPk])],
            ])->sortBy(0)->values()->all())
            ->assertSuccessful();

        $this->assertSame([$staleOptionPk], $this->mappedOptions($target), 'A dry run must not write.');

        $this->artisan('members:reconcile-role-mappings', ['--user' => $target->pk, '--apply' => true])
            ->expectsOutputToContain('Applied.')
            ->assertSuccessful();

        $this->assertSame([$heldOptionPk], $this->mappedOptions($target));

        $this->artisan('members:reconcile-role-mappings', ['--user' => $target->pk])
            ->doesntExpectOutputToContain('By option')
            ->assertSuccessful();
    }

    /**
     * PR #354 review, F-015: the "Read by" column tells mapping-only lookups (where an
     * added row adds a recipient) apart from the union lookups (where it does not).
     */
    public function test_reconcile_command_classifies_how_notifications_read_each_option(): void
    {
        $this->assertSame('mapping only', ReconcileEmployeeRoleMappings::notificationLookupFor('HAC Person'));
        $this->assertSame('mapping only', ReconcileEmployeeRoleMappings::notificationLookupFor('Admin'));
        $this->assertSame('mapping + Spatie role', ReconcileEmployeeRoleMappings::notificationLookupFor('Training-Induction'));
        $this->assertSame('mapping + Spatie role', ReconcileEmployeeRoleMappings::notificationLookupFor('Super Admin'));
        $this->assertSame('mapping + Spatie role', ReconcileEmployeeRoleMappings::notificationLookupFor('Estate Admin'));
        $this->assertSame('no lookup', ReconcileEmployeeRoleMappings::notificationLookupFor('Doctor'));
    }
}
