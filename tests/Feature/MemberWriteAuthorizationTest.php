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

    /**
     * PR #319 review (F-002): with no database reachable, DatabaseTransactions'
     * beginDatabaseTransaction() (invoked from parent::setUp()) threw a raw PDOException
     * and every test in this file errored rather than skipping — indistinguishable from a
     * real defect on a run where the environment, not the code, is the reason nothing ran.
     */
    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not reachable: ' . $e->getMessage());
        }
    }

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

    /**
     * F-002 residual: the refusal above must be VISIBLE.
     *
     * Refusing to sync was the right call — granting real permissions to an arbitrarily
     * chosen one of several logins is worse than not acting. But it was done silently:
     * the administrator ticked a role, got "Member successfully updated", and nothing had
     * happened, for a measured 207 employees on the live data. A refusal nobody is told
     * about is indistinguishable from success, which is the same failure shape as F-005.
     */
    public function test_an_ambiguous_member_save_tells_the_administrator_roles_were_not_changed(): void
    {
        $employeePk = $this->makeEmployee();

        // Two logins for one employee — the condition that makes the target ambiguous.
        foreach (['dup_a_', 'dup_b_'] as $prefix) {
            DB::table('user_credentials')->insertGetId([
                'user_name' => $prefix . uniqid(), 'user_id' => $employeePk,
                'first_name' => 'Dup', 'last_name' => 'Login', 'user_category' => 'E',
            ]);
        }

        $admin = $this->makeZeroRoleActor($this->makeEmployee());
        $admin->assignRole('Super Admin');

        $grantable = UserRoleMaster::whereIn(
            'user_role_display_name',
            DB::table('roles')->pluck('name')
        )->value('pk');
        $this->assertNotNull($grantable, 'Fixture assumption: a user_role_master row matching a real Spatie role exists.');

        $response = $this->actingAs($admin)->post(
            route('member.update'),
            $this->memberPayload($employeePk, ['userrole' => [$grantable]])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'warning']);

        $this->assertStringContainsString(
            'Role permissions were NOT changed',
            $response->json('warning'),
            'An administrator whose role change was refused must be told, not shown plain success.'
        );
    }

    /**
     * F-038, the denied case that the previous fixture could not express.
     *
     * authorizeMemberWrite() compared Auth::user()->user_id to emp_id and stopped there,
     * which treats user_id as an employee_master.pk for EVERY login. It is scoped per
     * user_category: measured on the live data, 328 logins that are NOT employee accounts
     * carry a user_id equal to some unrelated employee's pk, so each of them passed the
     * "my own record" test for a stranger. The independent review reproduced it — a
     * trainee login renamed employee 11056, and another rewrote employee 11058's own
     * credential row.
     *
     * The earlier tests in this file could not catch it: their actor fixtures are built
     * as category 'E' with user_id = their own employee, which is exactly the case where
     * the old and new predicates agree.
     */
    public function test_a_non_employee_login_cannot_write_the_employee_whose_pk_matches_its_user_id(): void
    {
        $victimPk = $this->makeEmployee('Victim');

        // A trainee-style login: NULL category, and a user_id that happens to equal an
        // unrelated employee's primary key. This is the real shape on the live data.
        $attackerPk = DB::table('user_credentials')->insertGetId([
            'user_name'     => 'collide_' . uniqid(),
            'user_id'       => $victimPk,
            'first_name'    => 'Trainee',
            'user_category' => null,
        ]);

        $attacker = User::find($attackerPk);
        $this->assertSame([], $attacker->getRoleNames()->all(), 'Fixture assumption: the actor holds no Spatie roles.');

        $response = $this->actingAs($attacker)->post(
            route('member.update'),
            $this->memberPayload($victimPk, ['first_name' => 'Hijacked'])
        );

        $response->assertForbidden();

        $this->assertSame(
            'Victim',
            DB::table('employee_master')->where('pk', $victimPk)->value('first_name'),
            'A login whose user_id merely collides with an employee pk must not be able to rewrite that employee.'
        );
    }

    /**
     * F-038, second half: a self-service save must not rename the login, and must touch
     * only the actor's OWN credential row.
     *
     * update() resolved the row with where('user_id', emp_id)->orderBy('pk')->first() and
     * always carried user_name in the payload, so a self-service save could rewrite a
     * different person's login name and email.
     */
    public function test_a_self_service_save_cannot_change_the_login_name(): void
    {
        $employeePk = $this->makeEmployee('Selfsvc');
        $actor = $this->makeZeroRoleActor($employeePk);

        $originalUserName = $actor->user_name;

        $response = $this->actingAs($actor)->post(
            route('member.update'),
            $this->memberPayload($employeePk, ['userid' => 'renamed_by_self_' . uniqid()])
        );

        $response->assertStatus(200);   // the actor's own record — the save itself is allowed

        $this->assertSame(
            $originalUserName,
            DB::table('user_credentials')->where('pk', $actor->pk)->value('user_name'),
            'A self-service save must not rename the login.'
        );
    }

    /**
     * R-001: the warning has to be RENDERED, not merely returned.
     *
     * Round 3 found that the previous fix stopped at the JSON response. The wizard's
     * success handler took no argument, discarded the body and showed a hardcoded
     * "Member updated successfully!", so for the 207 employees with duplicate logins the
     * administrator still saw plain success. A warning that only exists in the response
     * body is not a fix, so the view is asserted here alongside the controller.
     *
     * This is a source assertion rather than a browser test because there is no JS test
     * harness in this repository; it pins the two properties that regressed — the handler
     * receives the response, and it branches on `warning`.
     */
    public function test_the_wizard_view_renders_the_warning_rather_than_discarding_it(): void
    {
        // All THREE views that post to member.update / member.store. edit_profile was
        // missed on the first pass of this fix: an ordinary employee can never trigger a
        // warning there (the role sync and the payroll write are both admin-only), but an
        // administrator editing their own profile reaches the same handler and can.
        $handlers = [
            'edit'         => '/success:\s*function\s*\(\s*res\s*\)/',
            'create'       => '/success:\s*function\s*\(\s*res\s*\)/',
            'edit_profile' => '/const\s+res\s*=\s*await\s+\$\.ajax/',
        ];

        foreach ($handlers as $view => $receivesResponse) {
            $source = file_get_contents(resource_path("views/admin/member/{$view}.blade.php"));

            $this->assertMatchesRegularExpression(
                $receivesResponse,
                $source,
                "{$view}.blade.php must receive the response — discarding it is what silently "
                . 'dropped the warning.'
            );
            $this->assertStringContainsString(
                'res.warning',
                $source,
                "{$view}.blade.php must branch on the response's warning field."
            );
        }
    }
}
