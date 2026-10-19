<?php

namespace Tests\Feature;

use App\Models\PayrollSalaryMaster;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for PR #319 review, F-022 (Medium): the Blocker/High schema
 * and payroll fixes from rounds 1-2 (F-001, F-003, F-004, F-010) had no feature
 * test at all, so a later change could silently reintroduce any of them. This
 * covers the three gaps the review named explicitly: the six wizard steps
 * rendering, a member save writing a payroll row with an auto-assigned pk
 * (not a hand-rolled max(pk)+1 — F-004), and a cleared step-6 field actually
 * clearing (F-010).
 */
class MemberWizardPayrollAndStepsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeActor(string $suffix): User
    {
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'wizard_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    private function makeEmployee(string $suffix): int
    {
        return DB::table('employee_master')->insertGetId([
            'first_name' => 'Wizard',
            'last_name' => $suffix,
        ]);
    }

    /** F-001/F-023 residual: every step of the Add Member wizard must render, not just some. */
    public function test_all_six_add_member_wizard_steps_render(): void
    {
        $actor = $this->makeActor('render_add');

        for ($step = 1; $step <= 6; $step++) {
            $response = $this->actingAs($actor)->get(route('member.load-step', ['step' => $step]));
            $response->assertOk();
        }
    }

    /**
     * Same coverage for the Edit Member wizard, against a real employee record.
     *
     * PR #319 re-review F-041: editStep() is gated by authorizeMemberRecord() (added
     * later in this same PR to close an IDOR — any authenticated account could
     * previously read another employee's step content, including Role Assignment
     * state). A render check against an arbitrary employee therefore needs a
     * privileged actor; see the companion 403 test below for the non-owning case.
     */
    public function test_all_six_edit_member_wizard_steps_render(): void
    {
        $actor = $this->makeActor('render_edit');
        $actor->assignRole('Super Admin');
        $employeePk = $this->makeEmployee('render_edit_target');

        for ($step = 1; $step <= 6; $step++) {
            $response = $this->actingAs($actor)->get(route('member.edit-step', ['step' => $step, 'id' => $employeePk]));
            $response->assertOk();
        }
    }

    /** F-041 companion: a non-privileged, non-owning actor must be refused, not shown the content. */
    public function test_edit_wizard_steps_refuse_a_non_privileged_non_owning_actor(): void
    {
        $actor = $this->makeActor('render_edit_stranger');
        $employeePk = $this->makeEmployee('render_edit_victim');

        $response = $this->actingAs($actor)->get(route('member.edit-step', ['step' => 1, 'id' => $employeePk]));
        $response->assertForbidden();
    }

    /**
     * F-004: payroll_salary_master.pk must be assigned by AUTO_INCREMENT, not computed by the
     * application. Two members created back-to-back must land on two distinct, sequential pks
     * with no manual key allocation involved.
     */
    public function test_member_creation_writes_a_payroll_row_with_an_auto_assigned_pk(): void
    {
        $actor = $this->makeActor('payroll_creator');
        $actor->assignRole('Super Admin');

        $payload = $this->basicMemberPayload('PayrollOne') + [
            'basicpay' => 45000,
            'bankname' => 'State Bank',
            'accountno' => '1234567890',
        ];

        $response = $this->actingAs($actor)->post(route('member.store'), $payload);
        $response->assertOk();

        $employeePk = DB::table('employee_master')
            ->where('emp_id', $payload['id'])
            ->value('pk');
        $this->assertNotNull($employeePk, 'The employee row must have been created by store().');

        $payroll = PayrollSalaryMaster::where('employee_master_pk', $employeePk)->first();
        $this->assertNotNull($payroll, 'store() must create a payroll_salary_master row when step 6 has data.');
        $this->assertIsInt($payroll->pk);
        $this->assertGreaterThan(0, $payroll->pk);
        $this->assertSame(45000, (int) $payroll->basic_pay);

        // A second member created immediately after must get a distinct pk purely from
        // AUTO_INCREMENT — nothing in the application computes or reserves this value.
        $payload2 = $this->basicMemberPayload('PayrollTwo') + [
            'basicpay' => 51000,
            'bankname' => 'State Bank',
            'accountno' => '1234567891',
        ];
        $response2 = $this->actingAs($actor)->post(route('member.store'), $payload2);
        $response2->assertOk();

        $employeePk2 = DB::table('employee_master')->where('emp_id', $payload2['id'])->value('pk');
        $payroll2 = PayrollSalaryMaster::where('employee_master_pk', $employeePk2)->first();

        $this->assertNotNull($payroll2);
        $this->assertNotEquals($payroll->pk, $payroll2->pk, 'Two payroll rows must never collide on pk.');
    }

    /**
     * F-010: a step-6 field the admin deliberately blanks must actually clear in the database,
     * while a sibling field left filled in on the same submit is left untouched.
     */
    public function test_a_cleared_step6_field_is_persisted_as_null_without_touching_its_siblings(): void
    {
        $actor = $this->makeActor('payroll_clearer');
        $actor->assignRole('Super Admin');

        $createPayload = $this->basicMemberPayload('ClearCase') + [
            'basicpay' => 30000,
            'bankname' => 'Original Bank',
            'accountno' => '999999',
        ];
        $this->actingAs($actor)->post(route('member.store'), $createPayload)->assertOk();

        $employeePk = DB::table('employee_master')->where('emp_id', $createPayload['id'])->value('pk');
        $this->assertNotNull($employeePk);

        $updatePayload = $this->basicMemberPayload('ClearCase', $employeePk) + [
            'basicpay' => '',              // deliberately cleared
            'bankname' => 'Original Bank', // left as-is
            'accountno' => '999999',
        ];
        $this->actingAs($actor)->post(route('member.update'), $updatePayload)->assertOk();

        $payroll = PayrollSalaryMaster::where('employee_master_pk', $employeePk)->first();
        $this->assertNotNull($payroll);
        $this->assertNull($payroll->basic_pay, 'A field explicitly blanked on submit must be cleared, not left at its old value.');
        $this->assertSame('Original Bank', $payroll->bank_name, 'A field left filled in must survive the same submit untouched.');
    }

    /**
     * PR #319 re-review F-066: a save whose request carries NO step-6 fields at all must
     * leave the payroll row alone. The self-service profile page never renders step 6 yet
     * posts to member.update, and a Super Admin passes saveStep6PayrollData()'s gate — so
     * treating "fields absent" the same as "every field blanked" (F-042) wiped grade,
     * category, basic pay, bank and account on every such save while reporting success.
     * basicMemberPayload() is exactly that shape: steps 1-5 only.
     */
    public function test_a_save_that_omits_step6_leaves_the_payroll_row_untouched(): void
    {
        $actor = $this->makeActor('payroll_profile_save');
        $actor->assignRole('Super Admin');

        $gradePk = DB::table('salary_grade_master')->value('pk');
        $this->assertNotNull($gradePk, 'Fixture assumption: a salary_grade_master row must exist.');

        $createPayload = $this->basicMemberPayload('ProfileSave') + [
            'gradepay' => $gradePk,
            'basicpay' => 41000,
            'bankname' => 'Kept Bank',
            'accountno' => '55555',
        ];
        $this->actingAs($actor)->post(route('member.store'), $createPayload)->assertOk();

        $employeePk = DB::table('employee_master')->where('emp_id', $createPayload['id'])->value('pk');
        $this->assertNotNull($employeePk);

        // No gradepay / employeecategory / basicpay / bankname / accountno keys at all.
        $this->actingAs($actor)
            ->post(route('member.update'), $this->basicMemberPayload('ProfileSave', $employeePk))
            ->assertOk();

        $payroll = PayrollSalaryMaster::where('employee_master_pk', $employeePk)->first();
        $this->assertNotNull($payroll);
        $this->assertSame((int) $gradePk, (int) $payroll->salary_grade_pk, 'A save without step 6 must not clear the grade.');
        $this->assertSame(41000, (int) $payroll->basic_pay, 'A save without step 6 must not clear basic pay.');
        $this->assertSame('Kept Bank', $payroll->bank_name);
        $this->assertSame('55555', $payroll->account_no);
    }

    /**
     * PR #319 re-review F-068: update() reports a role the wizard refused to grant, but
     * store() discarded the same refusal — ticking the blocked "Super Admin" option on a
     * NEW member answered plain success while granting nothing.
     */
    public function test_creating_a_member_with_a_blocked_role_says_it_was_not_granted(): void
    {
        $actor = $this->makeActor('blocked_role_creator');
        $actor->assignRole('Super Admin');

        $blockedOptionPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Super-Admin',
            'user_role_display_name' => 'Super-Admin',
            'active_inactive'        => 1,
        ]);

        $payload = $this->basicMemberPayload('BlockedRole');
        $payload['userrole'][] = $blockedOptionPk;

        $response = $this->actingAs($actor)->post(route('member.store'), $payload);
        $response->assertOk();

        $warning = $response->json('warning');
        $this->assertNotNull($warning, 'A refused role on create must be reported, not swallowed.');
        $this->assertStringContainsString('Super-Admin', $warning);

        $credentialPk = DB::table('user_credentials')->where('user_name', $payload['userid'])->value('pk');
        $this->assertNotNull($credentialPk);
        $this->assertFalse(User::find($credentialPk)->hasRole('Super Admin'), 'And it still must not be granted.');
    }

    /**
     * PR #319 re-review F-045: a role option deactivated while the form was open used to
     * fail the WHOLE save with a 422 ("Invalid role selected"). The rest of the record must
     * save; only the inactive role is skipped, and the response says so.
     */
    public function test_a_deactivated_role_does_not_block_the_save_and_is_reported(): void
    {
        $actor = $this->makeActor('stale_role');
        $actor->assignRole('Super Admin');

        $inactiveRolePk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Retired Option ' . uniqid(),
            'user_role_display_name' => 'Retired Option',
            'active_inactive'        => 0,
        ]);

        $payload = $this->basicMemberPayload('StaleRole');
        $payload['userrole'][] = $inactiveRolePk;

        $response = $this->actingAs($actor)->post(route('member.store'), $payload);
        $response->assertOk();

        $warning = $response->json('warning');
        $this->assertNotNull($warning, 'Skipping a deactivated role must be reported.');
        $this->assertStringContainsString('Retired Option', $warning);

        $credentialPk = DB::table('user_credentials')->where('user_name', $payload['userid'])->value('pk');
        $this->assertNotNull($credentialPk, 'The member must still be created.');
        $this->assertFalse(
            DB::table('employee_role_mapping')->where('user_credentials_pk', $credentialPk)->where('user_role_master_pk', $inactiveRolePk)->exists(),
            'The deactivated role must not be assigned.'
        );
        $this->assertTrue(
            DB::table('employee_role_mapping')->where('user_credentials_pk', $credentialPk)->where('user_role_master_pk', $payload['userrole'][0])->exists(),
            'The active role on the same save must still be assigned.'
        );
    }

    /**
     * PR #319 re-review F-075 (a): if EVERY ticked role is inactive, nothing would be
     * assigned, so the save is refused exactly as "role required" refuses an empty
     * selection — before anything is written.
     */
    public function test_creating_a_member_with_only_inactive_roles_is_rejected(): void
    {
        $actor = $this->makeActor('only_inactive');
        $actor->assignRole('Super Admin');

        $inactiveRolePk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Retired Only ' . uniqid(),
            'user_role_display_name' => 'Retired Only',
            'active_inactive'        => 0,
        ]);

        $payload = $this->basicMemberPayload('OnlyInactive');
        $payload['userrole'] = [$inactiveRolePk];

        $this->actingAs($actor)->postJson(route('member.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['userrole'], 'errors');

        $this->assertNull(
            DB::table('employee_master')->where('emp_id', $payload['id'])->value('pk'),
            'A refused save must not create the member.'
        );
    }

    /**
     * F-075 (b): on an update, a role the member already holds that has since been
     * deactivated is left as it is — so the message must say it was not CHANGED, not that
     * it "was not assigned".
     */
    public function test_an_update_keeps_a_held_role_that_was_deactivated_and_says_so(): void
    {
        $actor = $this->makeActor('held_inactive');
        $actor->assignRole('Super Admin');

        $heldRolePk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Held Then Retired ' . uniqid(),
            'user_role_display_name' => 'Held Then Retired',
            'active_inactive'        => 1,
        ]);

        $createPayload = $this->basicMemberPayload('HeldInactive');
        $createPayload['userrole'][] = $heldRolePk;
        $this->actingAs($actor)->post(route('member.store'), $createPayload)->assertOk();

        $employeePk = DB::table('employee_master')->where('emp_id', $createPayload['id'])->value('pk');
        $credentialPk = DB::table('user_credentials')->where('user_name', $createPayload['userid'])->value('pk');
        $this->assertNotNull($employeePk);
        $this->assertNotNull($credentialPk);

        DB::table('user_role_master')->where('pk', $heldRolePk)->update(['active_inactive' => 0]);

        $updatePayload = $this->basicMemberPayload('HeldInactive', $employeePk);
        $updatePayload['userid'] = $createPayload['userid'];
        $updatePayload['userrole'][] = $heldRolePk;

        $response = $this->actingAs($actor)->post(route('member.update'), $updatePayload);
        $response->assertOk();

        $warning = $response->json('warning');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('Held Then Retired', $warning);
        $this->assertStringContainsString('was not changed', $warning);
        $this->assertStringNotContainsString('not assigned', $warning, 'The member still holds it, so it was not "not assigned".');

        $this->assertTrue(
            DB::table('employee_role_mapping')->where('user_credentials_pk', $credentialPk)->where('user_role_master_pk', $heldRolePk)->exists(),
            'The held, deactivated role must be left in place.'
        );
    }

    /**
     * PR #319 re-review F-077: the update-side refusal. An administrator's update whose
     * only ticked role has been deactivated is refused before anything is written, and the
     * member keeps the role mapping they already had.
     */
    public function test_an_admin_update_with_only_inactive_roles_is_rejected_and_changes_nothing(): void
    {
        $actor = $this->makeActor('update_only_inactive');
        $actor->assignRole('Super Admin');

        $rolePk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Soon Retired ' . uniqid(),
            'user_role_display_name' => 'Soon Retired',
            'active_inactive'        => 1,
        ]);

        $createPayload = $this->basicMemberPayload('UpdateOnlyInactive');
        $createPayload['userrole'] = [$rolePk];
        $this->actingAs($actor)->post(route('member.store'), $createPayload)->assertOk();

        $employeePk = DB::table('employee_master')->where('emp_id', $createPayload['id'])->value('pk');
        $credentialPk = DB::table('user_credentials')->where('user_name', $createPayload['userid'])->value('pk');
        $this->assertNotNull($employeePk);

        DB::table('user_role_master')->where('pk', $rolePk)->update(['active_inactive' => 0]);

        $updatePayload = $this->basicMemberPayload('UpdateOnlyInactive', $employeePk);
        $updatePayload['userid'] = $createPayload['userid'];
        $updatePayload['first_name'] = 'Changed';
        $updatePayload['userrole'] = [$rolePk];

        $this->actingAs($actor)->postJson(route('member.update'), $updatePayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['userrole'], 'errors');

        $this->assertSame('Wizard', DB::table('employee_master')->where('pk', $employeePk)->value('first_name'), 'A refused update must write nothing.');
        $this->assertTrue(
            DB::table('employee_role_mapping')->where('user_credentials_pk', $credentialPk)->where('user_role_master_pk', $rolePk)->exists(),
            'The member keeps the mapping they already had.'
        );
    }

    /**
     * F-077, the other branch: the refusal applies only to an actor whose role selection is
     * saved. A non-admin editing their own record has 'userrole' ignored, so an inactive pk
     * in it must not block their save — and must not be written either.
     */
    public function test_a_non_admin_own_save_with_an_inactive_role_is_not_blocked(): void
    {
        $employeePk = $this->makeEmployee('selfsave');
        $email = 'selfsave_' . uniqid() . '@example.test';
        DB::table('employee_master')->where('pk', $employeePk)->update(['email' => $email]);

        $actor = $this->makeActor('selfsave');
        $actor->user_id = $employeePk;
        $actor->email_id = $email;
        $actor->save();
        $this->assertSame([], $actor->getRoleNames()->all(), 'Fixture assumption: the actor holds no Spatie roles.');

        $inactiveRolePk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Self Retired ' . uniqid(),
            'user_role_display_name' => 'Self Retired',
            'active_inactive'        => 0,
        ]);

        $payload = $this->basicMemberPayload('SelfSave', $employeePk);
        $payload['userrole'] = [$inactiveRolePk];

        $this->actingAs($actor)->post(route('member.update'), $payload)->assertOk();

        $this->assertFalse(
            DB::table('employee_role_mapping')->where('user_credentials_pk', $actor->pk)->exists(),
            "A non-admin's userrole is ignored, so nothing is written."
        );
    }

    /**
     * F-075 (c): two skipped roles read as plural.
     */
    public function test_the_skipped_role_warning_agrees_in_number(): void
    {
        $actor = $this->makeActor('plural_inactive');
        $actor->assignRole('Super Admin');

        $payload = $this->basicMemberPayload('PluralInactive');
        foreach (['Retired One', 'Retired Two'] as $name) {
            $payload['userrole'][] = DB::table('user_role_master')->insertGetId([
                'user_role_name'         => $name . ' ' . uniqid(),
                'user_role_display_name' => $name,
                'active_inactive'        => 0,
            ]);
        }

        $warning = $this->actingAs($actor)->post(route('member.store'), $payload)->assertOk()->json('warning');

        $this->assertStringContainsString('are no longer active roles and were not assigned', $warning);
    }

    /**
     * A role pk that does not exist at all is still rejected, as before.
     */
    public function test_a_role_that_does_not_exist_is_still_rejected(): void
    {
        $actor = $this->makeActor('ghost_role');
        $actor->assignRole('Super Admin');

        $payload = $this->basicMemberPayload('GhostRole');
        $payload['userrole'][] = 987654321;

        $this->actingAs($actor)->postJson(route('member.store'), $payload)->assertStatus(422);
    }

    /**
     * Saving the edit wizard without uploading a new picture or document wrote NULL over
     * the stored paths (mapStep5Data() always returned both keys), so every edit silently
     * dropped the member's existing photo and document. A save without an upload must
     * leave them as they were.
     */
    public function test_an_update_without_a_new_upload_keeps_the_existing_picture_and_document(): void
    {
        $actor = $this->makeActor('keep_uploads');
        $actor->assignRole('Super Admin');

        $createPayload = $this->basicMemberPayload('KeepUploads');
        $this->actingAs($actor)->post(route('member.store'), $createPayload)->assertOk();

        $employeePk = DB::table('employee_master')->where('emp_id', $createPayload['id'])->value('pk');
        $this->assertNotNull($employeePk);

        DB::table('employee_master')->where('pk', $employeePk)->update([
            'profile_picture'       => 'members/existing-photo.jpg',
            'additional_doc_upload' => 'members/existing-doc.pdf',
        ]);

        $this->actingAs($actor)
            ->post(route('member.update'), $this->basicMemberPayload('KeepUploads', $employeePk))
            ->assertOk();

        $row = DB::table('employee_master')->where('pk', $employeePk)->first(['profile_picture', 'additional_doc_upload']);
        $this->assertSame('members/existing-photo.jpg', $row->profile_picture, 'A save without a new picture must keep the stored one.');
        $this->assertSame('members/existing-doc.pdf', $row->additional_doc_upload, 'A save without a new document must keep the stored one.');
    }

    /**
     * Shared step 1-5 payload valid against combinedMemberRules(). $employeePk, when given,
     * targets update() against an existing record instead of creating a new one.
     */
    private function basicMemberPayload(string $suffix, ?int $employeePk = null): array
    {
        // PR #319 re-review F-061: guarded the same way the other wizard fixtures
        // guard an unseeded-test-DB lookup — a null FK here would otherwise fail
        // with a validation 422 indistinguishable from the regression this file
        // exists to catch.
        $castePk = DB::table('caste_category_master')->where('active_inactive', 1)->value('pk');
        $this->assertNotNull($castePk, 'Fixture assumption: an active caste_category_master row must exist.');
        $countryPk = DB::table('country_master')->value('pk');
        $this->assertNotNull($countryPk, 'Fixture assumption: a country_master row must exist.');
        $statePk = DB::table('state_master')->value('pk');
        $this->assertNotNull($statePk, 'Fixture assumption: a state_master row must exist.');
        $departmentPk = DB::table('department_master')->where('pk', '>', 0)->value('pk');
        $this->assertNotNull($departmentPk, 'Fixture assumption: a department_master row (pk > 0) must exist.');
        $designationPk = DB::table('designation_master')->value('pk');
        $this->assertNotNull($designationPk, 'Fixture assumption: a designation_master row must exist.');
        $groupPk = DB::table('employee_group_master')->value('pk');
        $this->assertNotNull($groupPk, 'Fixture assumption: an employee_group_master row must exist.');
        $employeeTypePk = DB::table('employee_type_master')->value('pk');
        $this->assertNotNull($employeeTypePk, 'Fixture assumption: an employee_type_master row must exist.');
        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');
        $this->assertNotNull($doctorPk, 'Fixture assumption: a "Doctor" user_role_master row must exist.');

        $unique = $suffix . '_' . uniqid();

        return [
            'emp_id' => $employeePk,
            // Step 1
            'first_name' => 'Wizard',
            'last_name' => $suffix,
            'father_husband_name' => 'Father Name',
            'marital_status' => 'Unmarried',
            'gender' => 'Male',
            'caste_category' => $castePk,
            'date_of_birth' => '1990-01-01',
            // Step 2
            'type' => $employeeTypePk,
            'id' => 'WZ-' . $unique,
            'group' => $groupPk,
            'designation' => $designationPk,
            'userid' => 'wizard_uid_' . $unique,
            'section' => $departmentPk,
            // Step 3
            'userrole' => [$doctorPk],
            // Step 4
            'address' => 'Some Address',
            'country' => $countryPk,
            'state' => $statePk,
            'city' => 'Some City',
            'postal' => '110001',
            'permanentaddress' => 'Some Address',
            'permanentcountry' => $countryPk,
            'permanentstate' => $statePk,
            'permanentcity' => 'Some City',
            'permanentpostal' => '110001',
            'personalemail' => 'wizard_' . $unique . '@example.com',
            'officialemail' => 'wizard_official_' . $unique . '@example.com',
            'mnumber' => '9999999998',
        ];
    }
}
