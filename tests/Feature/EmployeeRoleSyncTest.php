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
        // The automatic mirror leaves notification-routing options alone (F-006); these
        // tests are about the options it does write.
        $mirrored = array_flip(EmployeeRoleMapping::mirroredOptionPks());

        foreach (UserRoleMaster::getUserRoleList() as $pk => $displayName) {
            if (! isset($mirrored[(int) $pk])) {
                continue;
            }

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

    /**
     * An offered option read by a notification lookup, with the Spatie role of the same
     * name, as [optionPk, roleName, userRoleName]. Prefers HAC Person (mapping-only lookup).
     */
    private function offeredRoutingOption(): array
    {
        $roleByKey = Role::pluck('name')->keyBy(fn ($name) => RoleNames::normalize($name));
        $offered = UserRoleMaster::getUserRoleList();
        $candidates = UserRoleMaster::whereIn('pk', $offered->keys()->all())
            ->whereIn('user_role_name', EmployeeRoleMapping::routingOptionNames())
            ->get(['pk', 'user_role_name'])
            ->sortBy(fn ($row) => $row->user_role_name === 'HAC Person' ? 0 : 1);

        foreach ($candidates as $row) {
            $key = RoleNames::normalize((string) $offered[$row->pk]);

            if (isset($roleByKey[$key]) && ! RoleNames::isNotGrantableFromMemberWizard($roleByKey[$key])) {
                return [(int) $row->pk, $roleByKey[$key], (string) $row->user_role_name];
            }
        }

        $this->markTestSkipped('Fixture assumption: an offered notification-routing option with a grantable Spatie role.');
    }

    /**
     * PR #354 review, F-006: notification routing must not change. Revoking a routing
     * role on the Users screen leaves its mapping row, so the login stays a recipient
     * exactly as at base; granting one adds no row.
     */
    public function test_users_screen_role_changes_do_not_change_notification_routing_rows(): void
    {
        [$routingPk, $routingRole, $routingName] = $this->offeredRoutingOption();
        [[$otherPk, $otherRole]] = $this->twoOfferedRoles();

        $admin = $this->makeSuperAdmin();

        // Revoke: login holds the routing role and its mapping row.
        $revoked = $this->makeTestUser('route_revoke');
        $revoked->assignRole($routingRole);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $revoked->pk, 'user_role_master_pk' => $routingPk]);

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $revoked->pk,
            'roles' => [Role::where('name', $otherRole)->value('id')],
        ])->assertRedirect();

        $expected = [$routingPk, $otherPk];
        sort($expected);
        $this->assertSame($expected, $this->mappedOptions($revoked),
            "The {$routingName} row must stay; the non-routing option is mirrored.");

        // Grant: login gains the routing role on the Users screen; no row is added.
        $granted = $this->makeTestUser('route_grant');

        $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $granted->pk,
            'roles' => [Role::where('name', $routingRole)->value('id')],
        ])->assertRedirect();

        $this->assertNotContains($routingPk, $this->mappedOptions($granted),
            "A Users-screen grant must not add a {$routingName} mapping row.");
    }

    /** F-006: HAC Person recipients are unchanged by a Users-screen revoke. */
    public function test_users_screen_revoke_of_hac_person_keeps_hac_notification_recipients(): void
    {
        $hacPk = UserRoleMaster::whereIn('pk', UserRoleMaster::getUserRoleList()->keys()->all())
            ->where('user_role_name', 'HAC Person')->value('pk');
        $hacRole = $hacPk ? Role::all()->first(fn ($r) => RoleNames::normalize($r->name) === RoleNames::normalize('HAC Person')) : null;

        if (! $hacPk || ! $hacRole) {
            $this->markTestSkipped('Fixture assumption: an offered HAC Person option and role.');
        }

        $employeePk = DB::table('employee_master')->insertGetId(['first_name' => 'Hac', 'last_name' => 'Person']);
        $target = $this->makeTestUser('hac', 'E', $employeePk);
        $target->assignRole($hacRole->name);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => (int) $hacPk]);

        $service = app(\App\Services\NotificationReceiverService::class);
        $before = $service->getEstateHacPersonUserIds();
        $this->assertContains($employeePk, $before);

        $this->actingAs($this->makeSuperAdmin())->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [],
        ])->assertRedirect();

        $this->assertSame($before, $service->getEstateHacPersonUserIds());
    }

    /** F-006: the reconcile command neither adds nor removes routing-option rows. */
    public function test_reconcile_apply_leaves_notification_routing_rows_alone(): void
    {
        [$routingPk, $routingRole] = $this->offeredRoutingOption();

        // Mapped-only routing row (login does not hold the role): kept.
        $mappedOnly = $this->makeTestUser('route_mapped_only');
        EmployeeRoleMapping::insert(['user_credentials_pk' => $mappedOnly->pk, 'user_role_master_pk' => $routingPk]);

        // Held-only routing role (no row): no row added.
        $heldOnly = $this->makeTestUser('route_held_only');
        $heldOnly->assignRole($routingRole);

        foreach ([$mappedOnly, $heldOnly] as $login) {
            $this->artisan('members:reconcile-role-mappings', ['--user' => $login->pk, '--apply' => true])
                ->assertSuccessful();
        }

        $this->assertSame([$routingPk], $this->mappedOptions($mappedOnly));
        $this->assertSame([], $this->mappedOptions($heldOnly));
    }

    /**
     * F-017: --apply mirrors the roles the login holds when it is written, not the roles
     * the dry-run preview saw, and -v reports the rows actually written.
     */
    public function test_reconcile_apply_reports_the_rows_it_actually_wrote(): void
    {
        [[$staleOptionPk], [$heldOptionPk, $heldRoleName]] = $this->twoOfferedRoles();

        $target = $this->makeTestUser('reconcile_v');
        $target->assignRole($heldRoleName);
        EmployeeRoleMapping::insert(['user_credentials_pk' => $target->pk, 'user_role_master_pk' => $staleOptionPk]);

        $this->artisan('members:reconcile-role-mappings', ['--user' => $target->pk, '--apply' => true, '-v' => true])
            ->expectsOutputToContain(sprintf('user_credentials.pk %d: added [%d] removed [%d]', $target->pk, $heldOptionPk, $staleOptionPk))
            ->assertSuccessful();

        $this->assertSame([$heldOptionPk], $this->mappedOptions($target));
    }
}
