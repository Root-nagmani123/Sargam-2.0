<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberController;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for the two High findings raised by the independent review of
 * PR #319 (F-001 and F-002 there), neither of which was in the round 1-8 review.
 *
 * F-001 — confused-deputy privilege escalation. The Spatie grant in
 * syncSpatieRolesFromWizardSelection() was correctly gated on hasRole('Super Admin'),
 * but nothing protected the data that gate trusts. POST member/update carried only the
 * `auth` middleware, MemberController declared no middleware or policy, every
 * StoreMemberStep*Request::authorize() returns true, and emp_id arrived in the request
 * body unvalidated. A zero-role account could therefore rewrite any employee's record
 * and seed employee_role_mapping for them — and the edit wizard pre-checks Step 3 from
 * exactly that table, so the next time a real Super Admin saved that member for any
 * unrelated reason the seeded row was submitted under their authority and became a live
 * Spatie role. Both halves were reproduced end to end before the fix.
 *
 * F-002 — user_credentials.user_id is not unique (611 duplicate groups on the live data,
 * 207 of them real employees, 603 of the affected rows already holding Spatie roles), so
 * where('user_id', ...)->first() handed syncRoles() an arbitrary one of several logins.
 */
class MemberWriteAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeEmployee(string $firstName = 'Authz'): int
    {
        return DB::table('employee_master')->insertGetId([
            'first_name' => $firstName,
            'last_name'  => 'Fixture',
        ]);
    }

    /** An ordinary authenticated account holding no Spatie roles, linked to its own employee row. */
    private function makeZeroRoleActor(int $employeeMasterPk): User
    {
        $pk = DB::table('user_credentials')->insertGetId([
            'user_name'      => 'authz_actor_' . uniqid(),
            'user_id'        => $employeeMasterPk,
            'first_name'     => 'Authz',
            'last_name'      => 'Actor',
            'user_category'  => 'E',
        ]);

        $actor = User::find($pk);

        $this->assertSame([], $actor->getRoleNames()->all(), 'Fixture assumption: the actor holds no Spatie roles.');

        return $actor;
    }

    /** A complete, valid member payload — so a rejection can only be authorisation, never validation. */
    private function memberPayload(int $employeeMasterPk, array $overrides = []): array
    {
        return array_merge([
            'emp_id'              => $employeeMasterPk,
            'first_name'          => 'Authz',
            'last_name'           => 'Fixture',
            'father_husband_name' => 'Father Name',
            'marital_status'      => 'Unmarried',
            'gender'              => 'Male',
            'caste_category'      => DB::table('caste_category_master')->where('active_inactive', 1)->value('pk'),
            'date_of_birth'       => '1990-01-01',
            'type'                => DB::table('employee_type_master')->value('pk'),
            'id'                  => 'AUTHZ-' . substr(uniqid(), -6),
            'group'               => DB::table('employee_group_master')->value('pk'),
            'designation'         => DB::table('designation_master')->value('pk'),
            'userid'              => 'authz_uid_' . uniqid(),
            'section'             => DB::table('department_master')->where('pk', '>', 0)->value('pk'),
            'userrole'            => [UserRoleMaster::getUserRoleList()->keys()->first()],
            'address'             => 'Some Address',
            'country'             => DB::table('country_master')->value('pk'),
            'state'               => DB::table('state_master')->value('pk'),
            'city'                => 'Some City',
            'postal'              => '110001',
            'permanentaddress'    => 'Some Address',
            'permanentcountry'    => DB::table('country_master')->value('pk'),
            'permanentstate'      => DB::table('state_master')->value('pk'),
            'permanentcity'       => 'Some City',
            'permanentpostal'     => '110001',
            'personalemail'       => 'authz_' . uniqid() . '@example.com',
            'officialemail'       => 'authz_off_' . uniqid() . '@example.com',
            'mnumber'             => '9999999999',
        ], $overrides);
    }

    /**
     * F-001, the denied case. Before the fix this returned HTTP 200 with
     * {"message":"Member successfully updated"} and the victim's name changed.
     */
    public function test_a_zero_role_actor_cannot_update_another_employees_record(): void
    {
        $actor = $this->makeZeroRoleActor($this->makeEmployee());
        $victimPk = $this->makeEmployee('Victim');

        $response = $this->actingAs($actor)->post(
            route('member.update'),
            $this->memberPayload($victimPk, ['first_name' => 'HIJACKED'])
        );

        $response->assertForbidden();

        $this->assertSame(
            'Victim',
            DB::table('employee_master')->where('pk', $victimPk)->value('first_name'),
            'The victim record must be untouched by a refused request.'
        );
    }

    /**
     * F-001, the seeding half. Even for a record the actor IS allowed to write (their own),
     * an actor who cannot manage roles must not be able to write employee_role_mapping —
     * that table is what the edit form pre-checks Step 3 from, and what a later privileged
     * save converts into a real Spatie grant.
     */
    public function test_a_zero_role_actors_own_save_does_not_write_role_mappings(): void
    {
        $ownEmployeePk = $this->makeEmployee();
        $actor = $this->makeZeroRoleActor($ownEmployeePk);

        $this->actingAs($actor)->post(
            route('member.update'),
            $this->memberPayload($ownEmployeePk)
        );

        $this->assertSame(
            0,
            DB::table('employee_role_mapping')->where('user_credentials_pk', $actor->pk)->count(),
            'An actor who cannot manage roles must not seed employee_role_mapping.'
        );

        $actor->refresh();
        $this->assertSame([], $actor->getRoleNames()->all(), 'No Spatie role may be granted either.');
    }

    /**
     * F-001, the permitted case — the fix must not lock everyone out. The header's
     * "Edit Profile" link posts to this same endpoint for the actor's own record, so a
     * self-scoped write must not be refused as unauthorised.
     *
     * Asserts "not 403" rather than 200 deliberately: the self-service profile form sends
     * no userrole[] while combinedMemberRules() still marks it required, so that path
     * currently answers 422 for a reason that pre-dates this change and is out of scope
     * here. What this test pins is that authorisation is not what stops it.
     */
    public function test_an_actor_may_still_write_their_own_record(): void
    {
        $ownEmployeePk = $this->makeEmployee();
        $actor = $this->makeZeroRoleActor($ownEmployeePk);

        $response = $this->actingAs($actor)->post(
            route('member.update'),
            $this->memberPayload($ownEmployeePk)
        );

        $this->assertNotSame(403, $response->getStatusCode(), 'A self-scoped write must not be refused.');
    }

    /** F-001. Creating a member has no "own record" to scope to, so it is admin-only. */
    public function test_a_zero_role_actor_cannot_create_a_member(): void
    {
        $actor = $this->makeZeroRoleActor($this->makeEmployee());

        $this->actingAs($actor)
            ->post(route('member.store'), $this->memberPayload($this->makeEmployee()))
            ->assertForbidden();
    }

    /** F-001. An unknown emp_id must be a field error, not a fatal on find()->update(). */
    public function test_an_unknown_emp_id_is_rejected_by_validation(): void
    {
        $actor = $this->makeZeroRoleActor($this->makeEmployee());

        $this->actingAs($actor)
            ->post(route('member.update'), $this->memberPayload(9999999))
            ->assertStatus(422)
            ->assertJsonValidationErrors('emp_id');
    }

    /**
     * F-002. When an employee owns more than one user_credentials row, ->first() picked
     * an arbitrary one and syncRoles() ran against it. The sync must now refuse rather
     * than grant or revoke real permissions on a login nobody chose.
     */
    public function test_spatie_sync_refuses_when_the_employee_has_two_credential_rows(): void
    {
        $employeePk = $this->makeEmployee();

        $firstPk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'ambig_a_' . uniqid(), 'user_id' => $employeePk,
            'first_name' => 'Ambig', 'last_name' => 'One', 'user_category' => 'E',
        ]);
        DB::table('user_credentials')->insertGetId([
            'user_name' => 'ambig_b_' . uniqid(), 'user_id' => $employeePk,
            'first_name' => 'Ambig', 'last_name' => 'Two', 'user_category' => 'E',
        ]);

        $grantable = UserRoleMaster::whereIn(
            'user_role_display_name',
            DB::table('roles')->pluck('name')
        )->value('pk');

        $this->assertNotNull($grantable, 'Fixture assumption: a user_role_master row matching a real Spatie role exists.');

        $controller = new MemberController();
        $method = new ReflectionMethod($controller, 'syncSpatieRolesFromWizardSelection');
        $method->setAccessible(true);
        $method->invoke($controller, $firstPk, [$grantable], []);

        $this->assertSame(
            [],
            User::find($firstPk)->getRoleNames()->all(),
            'No role may be granted while the employee has more than one credential row.'
        );
    }
}
