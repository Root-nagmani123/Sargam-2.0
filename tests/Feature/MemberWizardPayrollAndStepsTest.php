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

    /** Same coverage for the Edit Member wizard, against a real employee record. */
    public function test_all_six_edit_member_wizard_steps_render(): void
    {
        $actor = $this->makeActor('render_edit');
        $employeePk = $this->makeEmployee('render_edit_target');

        for ($step = 1; $step <= 6; $step++) {
            $response = $this->actingAs($actor)->get(route('member.edit-step', ['step' => $step, 'id' => $employeePk]));
            $response->assertOk();
        }
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
     * Shared step 1-5 payload valid against combinedMemberRules(). $employeePk, when given,
     * targets update() against an existing record instead of creating a new one.
     */
    private function basicMemberPayload(string $suffix, ?int $employeePk = null): array
    {
        $castePk = DB::table('caste_category_master')->where('active_inactive', 1)->value('pk');
        $countryPk = DB::table('country_master')->value('pk');
        $statePk = DB::table('state_master')->value('pk');
        $departmentPk = DB::table('department_master')->where('pk', '>', 0)->value('pk');
        $designationPk = DB::table('designation_master')->value('pk');
        $groupPk = DB::table('employee_group_master')->value('pk');
        $employeeTypePk = DB::table('employee_type_master')->value('pk');
        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');

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
