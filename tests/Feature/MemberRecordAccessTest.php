<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMemberPiiAccess;
use App\Models\EmployeeMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The second round of member findings, executed rather than read.
 *
 *   F-019  the four DOCUMENT endpoints were gated while the edit wizard served
 *          the same fields as a form, by raw integer pk, to anybody.
 *   F-020  the emp_id uniqueness rule was evaluated on every save, so the 585
 *          members whose Employee ID is shared with another row could not be
 *          edited at all - not even to correct a mobile number.
 *   F-021  the listing's five export controls rendered for accounts the gate
 *          refuses, so its most prominent buttons returned 403.
 *   F-022  the documented "grant a permission, no code change" remedy named a
 *          permission no screen in this application could produce.
 *   F-023  a refused duplicate create left its uploads on the public disk.
 *
 * Skips - never fails - when the application database is unreachable. The guard
 * sits BEFORE the transaction opens: a skip after that point is unreachable and
 * the file errors instead of skipping. Every write is rolled back.
 */
class MemberRecordAccessTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the member record tests need the application database');
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

    private function user(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        return $user;
    }

    private function actAsNonEntitled(): User
    {
        $user = $this->user();
        $this->actingAs($user);
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->assertFalse(
            isSidebarPrivilegedUser(),
            'this case is meaningless unless the actor is genuinely non-privileged'
        );

        return $user;
    }

    private function actAsSuperAdmin(): User
    {
        $user = $this->user();
        $this->actingAs($user);
        session(['user_roles' => ['Super Admin']]);

        return $user;
    }

    /**
     * A member that is NOT the acting account's own record.
     *
     * Drawn by excluding the actor's own employee pk rather than by taking the
     * first row, or the test would pass for the wrong reason on a database
     * where those happen to coincide.
     */
    private function someoneElsesMemberPk(User $actor): int
    {
        $pk = EmployeeMaster::query()
            ->when($actor->user_id !== null, fn ($q) => $q->where('pk', '!=', $actor->user_id))
            ->orderBy('pk')
            ->value('pk');

        if ($pk === null) {
            $this->markTestSkipped('no employee_master row other than the actor\'s own');
        }

        return (int) $pk;
    }

    // ------------------------------------------------------------------ F-019

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function wizardRoutes(): array
    {
        return [
            'edit' => ['member.edit', []],
            'profile edit' => ['member.profile.edit', []],
            'edit-step 1' => ['member.edit-step', ['step' => 1]],
            'edit-step 4 (addresses, email)' => ['member.edit-step', ['step' => 4]],
        ];
    }

    /**
     * F-019, the denied case: the wizard refuses a non-entitled account asking
     * for somebody else's record. These returned HTTP 200 with the member's
     * permanent address, current address and personal email before this change.
     *
     * @dataProvider wizardRoutes
     */
    public function test_the_edit_wizard_refuses_another_members_record(string $name, array $params): void
    {
        $actor = $this->actAsNonEntitled();
        $params['id'] = $this->someoneElsesMemberPk($actor);

        $this->get(route($name, $params))->assertForbidden();
    }

    /**
     * And the self-service case still works, which is why this is an
     * object-level check and not the PII gate: member.profile.edit.self sends
     * every user to their own record.
     */
    public function test_a_user_may_still_open_their_own_record(): void
    {
        $actor = $this->actAsNonEntitled();

        if ($actor->user_id === null || ! EmployeeMaster::query()->where('pk', $actor->user_id)->exists()) {
            $this->markTestSkipped('the acting credential has no employee_master row of its own');
        }

        $this->get(route('member.profile.edit', ['id' => $actor->user_id]))->assertOk();
    }

    /** An entitled account keeps the whole module. */
    public function test_an_entitled_account_may_open_any_record(): void
    {
        $actor = $this->actAsSuperAdmin();

        $this->get(route('member.edit', ['id' => $this->someoneElsesMemberPk($actor)]))->assertOk();
    }

    /**
     * The write twin. member.update takes the member's key from the BODY, so no
     * route middleware can see it; gating the read path and leaving this open
     * would have been the larger half of the same hole.
     */
    public function test_the_member_update_post_refuses_another_members_record(): void
    {
        $actor = $this->actAsNonEntitled();

        $this->postJson(route('member.update'), ['emp_id' => $this->someoneElsesMemberPk($actor)])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------ F-020

    /**
     * A member drawn from a REAL duplicate emp_id group, with its stored
     * Employee ID. Constructing one would not exercise the case that failed.
     *
     * @return array{0: int, 1: string}
     */
    private function memberFromADuplicateGroup(): array
    {
        $shared = DB::table('employee_master')
            ->select('emp_id')
            ->whereNotNull('emp_id')
            ->where('emp_id', '!=', '')
            ->groupBy('emp_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('emp_id');

        if ($shared === null) {
            $this->markTestSkipped('no duplicate emp_id group on this database to exercise');
        }

        $pk = DB::table('employee_master')->where('emp_id', $shared)->orderBy('pk')->value('pk');

        return [(int) $pk, (string) $shared];
    }

    /**
     * F-020: editing a member whose Employee ID is shared with other rows must
     * succeed when that field is not being changed.
     *
     * This returned 422 "This employee ID already exists" for 585 of 1,833
     * members, and the wizard could not advance past step 2 - so the only way to
     * save an unrelated edit was to alter the identity field that made it fail.
     */
    public function test_a_member_sharing_an_employee_id_can_be_saved_unchanged(): void
    {
        $this->actAsSuperAdmin();
        [$pk, $sharedEmpId] = $this->memberFromADuplicateGroup();

        $peers = DB::table('employee_master')->where('emp_id', $sharedEmpId)->count();
        $this->assertGreaterThan(1, $peers, 'the subject must really share its Employee ID');

        $response = $this->postJson(
            "/member/update-validate-step/2/{$pk}",
            $this->step2Payload($pk, $sharedEmpId)
        );

        $this->assertNotSame(
            422,
            $response->getStatusCode(),
            'an unchanged Employee ID must not be refused: '.$response->getContent()
        );
    }

    /** But changing it to a value another member already holds is still refused. */
    public function test_changing_an_employee_id_to_a_taken_one_is_still_refused(): void
    {
        $this->actAsSuperAdmin();
        [$pk, $sharedEmpId] = $this->memberFromADuplicateGroup();

        $taken = DB::table('employee_master')
            ->whereNotNull('emp_id')
            ->where('emp_id', '!=', '')
            ->where('emp_id', '!=', $sharedEmpId)
            ->value('emp_id');

        if ($taken === null) {
            $this->markTestSkipped('no second Employee ID to collide with');
        }

        $response = $this->postJson(
            "/member/update-validate-step/2/{$pk}",
            $this->step2Payload($pk, (string) $taken)
        );

        $response->assertStatus(422);
        $this->assertArrayHasKey('id', $response->json('errors') ?? []);
    }

    /** A complete step-2 payload for the member at $pk, with $empId as the Employee ID. */
    private function step2Payload(int $pk, string $empId): array
    {
        $member = DB::table('employee_master')->where('pk', $pk)->first();
        $credential = DB::table('user_credentials')->where('user_id', $pk)->first();

        return [
            'emp_id' => $pk,
            'id' => $empId,
            'type' => $member->emp_type ?? DB::table('employee_type_master')->value('pk'),
            'group' => $member->emp_group_pk ?? DB::table('employee_group_master')->value('pk'),
            'designation' => $member->designation_master_pk ?? DB::table('designation_master')->value('pk'),
            'section' => $member->department_master_pk ?? DB::table('department_master')->value('pk'),
            'userid' => $credential->user_name ?? ('probe'.substr((string) uniqid(), -8)),
        ];
    }

    // ------------------------------------------------------------------ F-021

    /** The listing's export controls are rendered only for an account the gate admits. */
    public function test_the_listing_hides_the_export_controls_from_a_non_entitled_account(): void
    {
        $this->actAsNonEntitled();
        $html = $this->get(route('member.index'))->assertOk()->getContent();

        // The rendered controls, not the `.member-export-link` class: that name
        // also appears once in the page's own script, as the selector the
        // href-sync loop iterates. A selector that matches nothing is inert, and
        // asserting on it would be asserting about the JS rather than about what
        // the user is offered.
        foreach (['memberDownloadBtn', 'memberPrintBtn', 'Full Details (Excel)', 'Download CSV'] as $control) {
            $this->assertStringNotContainsString(
                $control,
                $html,
                "{$control} is offered to an account the export gate refuses"
            );
        }

        // And the grid is still there: this hides a capability, not the screen.
        $this->assertStringContainsString('member-table', $html);
    }

    /** And still rendered for one it admits - a gate that hides them from everybody is an outage. */
    public function test_the_listing_still_offers_the_export_controls_to_an_entitled_account(): void
    {
        $this->actAsSuperAdmin();
        $html = $this->get(route('member.index'))->assertOk()->getContent();

        foreach (['memberDownloadBtn', 'memberPrintBtn', 'Full Details (Excel)'] as $control) {
            $this->assertStringContainsString($control, $html, "{$control} is missing for an entitled account");
        }
    }

    // ------------------------------------------------------------------ F-022

    /**
     * F-022: the documented remedy must be performable.
     *
     * The permission name has to survive the slug the menu machinery applies,
     * or it can never appear on the screen that grants it.
     */
    public function test_the_permission_name_survives_the_menu_slug(): void
    {
        $permission = EnsureMemberPiiAccess::PII_PERMISSION;

        $this->assertSame(
            $permission,
            \Illuminate\Support\Str::slug($permission, '_'),
            'a permission name that does not round-trip through Str::slug cannot be granted from the roles screen'
        );
    }

    /**
     * The migration ships both halves of the grant path: the permission row, and
     * a menus row that puts a toggle for it on the role-assignment screen.
     *
     * Run inside this test's transaction and rolled back with it, so the check
     * is of the migration itself rather than of whatever the database happens to
     * hold already.
     */
    public function test_the_migration_makes_the_permission_grantable(): void
    {
        $permission = EnsureMemberPiiAccess::PII_PERMISSION;

        DB::table('menus')->where('permission_name', $permission)->delete();
        DB::table('permissions')->where('name', $permission)->delete();

        $migration = require database_path(
            'migrations/2026_09_16_090000_add_member_pii_read_permission.php'
        );
        $migration->up();

        $this->assertTrue(
            DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists(),
            'the permission row must exist after the migration'
        );

        // Running twice must not duplicate either row.
        $migration->up();
        $this->assertSame(1, DB::table('permissions')->where('name', $permission)->count());
        $this->assertSame(1, DB::table('menus')->where('permission_name', $permission)->count());

        $menu = DB::table('menus')->where('permission_name', $permission)->first();
        $this->assertSame(1, (int) $menu->is_active, 'an inactive menu row is invisible to the role-assignment screen');
        $this->assertSame(1, (int) $menu->exclude_from_admin, 'the capability row must not change an administrator sidebar');

        $this->assertTrue(
            $this->permissionAppearsOnTheRoleScreen($permission),
            'the permission must reach the role-assignment matrix, or it cannot be granted'
        );

        $migration->down();
        $this->assertSame(0, DB::table('permissions')->where('name', $permission)->count());
        $this->assertSame(0, DB::table('menus')->where('permission_name', $permission)->count());
    }

    /** Walks the exact relation RoleController::show() hands the matrix blade. */
    private function permissionAppearsOnTheRoleScreen(string $permission): bool
    {
        foreach (\App\Models\SidebarMenu\SidebarCategory::with('groups.menus')->get() as $category) {
            foreach ($category->groups as $group) {
                foreach ($group->menus as $menu) {
                    if ($menu->permission_name === $permission) {
                        return true;
                    }

                    foreach ($menu->children as $child) {
                        if ($child->permission_name === $permission) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    // ------------------------------------------------------------------ F-023

    /** A refused duplicate create must not leave its uploads on the public disk. */
    public function test_a_refused_duplicate_create_removes_its_uploads(): void
    {
        $this->actAsSuperAdmin();

        $taken = DB::table('employee_master')
            ->whereNotNull('emp_id')->where('emp_id', '!=', '')->value('emp_id');

        if ($taken === null) {
            $this->markTestSkipped('no employee_master row carries an emp_id to collide with');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $before = $disk->exists('members') ? $disk->files('members') : [];

        $payload = array_merge($this->newMemberPayload(), ['id' => $taken]);
        $payload['picture'] = \Illuminate\Http\UploadedFile::fake()->image('probe.png', 8, 8);

        $this->post(route('member.store'), $payload)->assertStatus(422);

        $after = $disk->exists('members') ? $disk->files('members') : [];

        $this->assertSame(
            [],
            array_values(array_diff($after, $before)),
            'a refused create left an unreferenced personal document on the public disk'
        );
    }

    /**
     * A valid create payload for a member who does not exist. Mirrors
     * MemberPiiAccessTest::newMemberPayload(); kept local so neither file can
     * break the other by editing it.
     *
     * @return array<string, mixed>
     */
    private function newMemberPayload(): array
    {
        $marker = substr((string) uniqid(), -8);

        $type = DB::table('employee_type_master')->value('pk');
        $group = DB::table('employee_group_master')->value('pk');
        $designation = DB::table('designation_master')->value('pk');
        $department = DB::table('department_master')->value('pk');
        $role = DB::table('user_role_master')->value('pk');
        $caste = \App\Models\CasteCategoryMaster::GetSeatName()->keys()->first();

        $location = DB::table('employee_master')
            ->select('country_master_pk', 'state_master_pk', 'state_district_mapping_pk', 'city')
            ->whereNotNull('country_master_pk')
            ->whereNotNull('state_master_pk')
            ->whereNotNull('city')
            ->first();

        foreach (compact('type', 'group', 'designation', 'department', 'role', 'caste', 'location') as $label => $value) {
            if ($value === null) {
                $this->markTestSkipped("no {$label} row to build a valid member payload from");
            }
        }

        return [
            'first_name' => 'Dup',
            'middle_name' => '',
            'last_name' => 'Guardtest',
            'father_husband_name' => 'Guard Senior',
            'marital_status' => 'Unmarried',
            'gender' => 'Male',
            'caste_category' => $caste,
            'appellation' => DB::table('appellation_master')->where('active_inactive', 1)->value('pk'),
            'height' => '170',
            'date_of_birth' => '1990-01-01',
            'type' => $type,
            'id' => 'DUP'.$marker,
            'group' => $group,
            'designation' => $designation,
            'userid' => 'dup'.$marker,
            'section' => $department,
            'userrole' => [$role],
            'address' => 'Test current address',
            'country' => (string) $location->country_master_pk,
            'state' => (string) $location->state_master_pk,
            'district' => (string) $location->state_district_mapping_pk,
            'city' => (string) $location->city,
            'postal' => '248179',
            'permanentaddress' => 'Test permanent address',
            'permanentcountry' => (string) $location->country_master_pk,
            'permanentstate' => (string) $location->state_master_pk,
            'permanentdistrict' => (string) $location->state_district_mapping_pk,
            'permanentcity' => (string) $location->city,
            'permanentpostal' => '248179',
            'personalemail' => 'dup'.$marker.'@example.invalid',
            'officialemail' => 'off'.$marker.'@example.invalid',
            'mnumber' => '9000000000',
            'homeaddress' => 'Test address',
            'residencenumber' => '1234567',
        ];
    }
}
