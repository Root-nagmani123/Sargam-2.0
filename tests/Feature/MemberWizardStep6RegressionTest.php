<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberController;
use App\Models\PayrollSalaryMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for PR #319 review, F-022.
 *
 * Rounds 1-2 fixed a cluster of Blocker/High findings on the Member wizard's new
 * Step 6 ("Employee Grade Pay") and shipped no test with any of them, so nothing
 * would catch a reintroduction. F-022 named three specifically:
 *
 *   - the six wizard steps rendering            (F-001, and its F-023 residual)
 *   - a member save writing a payroll row with an auto-assigned pk   (F-004)
 *   - a cleared step-6 field actually clearing  (F-010)
 *
 * plus the schema those fixes depend on         (F-002, F-003).
 *
 * The denied-case authorisation test F-022 also asked for already exists, in
 * MemberWizardRbacSyncTest, and is not duplicated here.
 *
 * SCHEMA DEPENDENCY. Four of these assertions need the three migrations this PR
 * ships (employee_category_master, payroll_salary_master.basic_pay, and
 * AUTO_INCREMENT on payroll_salary_master.pk). On an environment where migrate
 * has not run they skip with a message naming what to run, rather than failing
 * for a reason that is not the code under test. The two that need no new schema
 * -- steps 1-5 rendering and mapStep6Data()'s clearing semantics -- always run.
 */
class MemberWizardStep6RegressionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Skip, with an actionable message, when this PR's migrations have not been applied.
     * Stated as a skip rather than a failure because the absence is an environment fact,
     * not a defect in the code these tests cover.
     */
    private function requireStep6Schema(): void
    {
        $missing = [];

        if (! Schema::hasTable('employee_category_master')) {
            $missing[] = 'employee_category_master (2026_08_18_235800)';
        }

        if (! Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            $missing[] = 'payroll_salary_master.basic_pay (2026_08_19_000001)';
        }

        if ($missing !== []) {
            $this->markTestSkipped(
                'Requires PR #319 schema, absent here: ' . implode(', ', $missing)
                . '. Run: php artisan migrate'
            );
        }
    }

    private function makeActor(string $suffix): User
    {
        // Mirrors MemberWizardRbacSyncTest::makeTestUser() -- user_credentials has no
        // created_at/updated_at and User::$fillable does not cover these HR fields.
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Step6';
        $user->last_name = $suffix;
        $user->user_name = 'step6_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    private function makeEmployee(): int
    {
        return DB::table('employee_master')->insertGetId([
            'first_name' => 'Step6',
            'last_name'  => 'Fixture',
        ]);
    }

    /** Build a Request the way the controller receives one, for the private step-6 helpers. */
    private function step6Request(array $fields): Request
    {
        return new Request($fields);
    }

    private function invokePrivate(string $method, array $args)
    {
        $controller = new MemberController();
        $reflected = new ReflectionMethod($controller, $method);
        $reflected->setAccessible(true);

        return $reflected->invokeArgs($controller, $args);
    }

    /**
     * F-001 / F-023. step6DropdownOptions() used to be called unconditionally from
     * loadStep(), so a missing step-6 reference table took down every step of the
     * wizard -- not just step 6. Steps 1-5 must render whether or not the step-6
     * schema exists, which is why this test is deliberately NOT schema-gated: on an
     * un-migrated environment it is testing exactly the regression that mattered.
     */
    public function test_wizard_steps_one_to_five_render_without_the_step6_schema(): void
    {
        $actor = $this->makeActor('render');

        foreach ([1, 2, 3, 4, 5] as $step) {
            $response = $this->actingAs($actor)->get("/member/step/{$step}");

            $response->assertStatus(200);
        }
    }

    /** F-001. With the schema present, step 6 itself renders too. */
    public function test_wizard_step_six_renders_when_the_schema_is_present(): void
    {
        $this->requireStep6Schema();

        $actor = $this->makeActor('render6');

        $this->actingAs($actor)->get('/member/step/6')->assertStatus(200);
    }

    /**
     * F-002 / F-003. The two columns Step 6 writes must exist on payroll_salary_master,
     * and employee_category_master must exist for the dropdown and the exists: rule.
     * These were the Blocker pair: the code shipped referencing schema nothing created.
     */
    public function test_step6_schema_objects_exist(): void
    {
        $this->requireStep6Schema();

        $this->assertTrue(
            Schema::hasTable('employee_category_master'),
            'employee_category_master must exist -- Step 6 renders a dropdown from it and validates against it.'
        );
        $this->assertTrue(
            Schema::hasColumn('payroll_salary_master', 'employee_category_master_pk'),
            'payroll_salary_master.employee_category_master_pk must exist -- mapStep6Data() writes it.'
        );
        $this->assertTrue(
            Schema::hasColumn('payroll_salary_master', 'basic_pay'),
            'payroll_salary_master.basic_pay must exist -- mapStep6Data() writes it.'
        );
    }

    /**
     * F-004. New payroll rows used to take max(pk) + 1 on a primary key with no
     * AUTO_INCREMENT -- two concurrent saves read the same max and the second INSERT
     * collided. The fix moved key allocation to the database, so the regression test
     * is that an insert which supplies no pk succeeds and comes back with one.
     */
    public function test_saving_step6_creates_a_payroll_row_with_a_database_assigned_pk(): void
    {
        $this->requireStep6Schema();

        $employeePk = $this->makeEmployee();

        $this->invokePrivate('saveStep6PayrollData', [
            $employeePk,
            $this->step6Request([
                'gradepay'   => DB::table('salary_grade_master')->value('pk'),
                'basicpay'   => 56100,
                'bankname'   => 'Regression Bank',
                'accountno'  => '1234567890',
            ]),
        ]);

        $row = PayrollSalaryMaster::where('employee_master_pk', $employeePk)->first();

        $this->assertNotNull($row, 'Step 6 must write a payroll_salary_master row.');
        $this->assertNotNull($row->pk, 'The payroll row must receive a primary key.');
        $this->assertGreaterThan(0, (int) $row->pk, 'The pk must be database-assigned, not 0.');
        $this->assertSame('Regression Bank', $row->bank_name);
        $this->assertSame(56100, (int) $row->basic_pay);
    }

    /**
     * F-004, the concurrency property itself: two payroll rows created back to back must
     * not collide on the primary key. Before the AUTO_INCREMENT migration both would have
     * been computed as max(pk) + 1.
     */
    public function test_two_payroll_rows_created_in_succession_get_distinct_keys(): void
    {
        $this->requireStep6Schema();

        $firstPk = $this->makeEmployee();
        $secondPk = $this->makeEmployee();

        foreach ([$firstPk, $secondPk] as $employeePk) {
            $this->invokePrivate('saveStep6PayrollData', [
                $employeePk,
                $this->step6Request(['basicpay' => 10000]),
            ]);
        }

        $keys = PayrollSalaryMaster::whereIn('employee_master_pk', [$firstPk, $secondPk])
            ->pluck('pk')
            ->all();

        $this->assertCount(2, $keys, 'Both payroll rows must have been written.');
        $this->assertCount(2, array_unique($keys), 'The two payroll rows must not share a primary key.');
    }

    /**
     * F-010, at the mapping layer. mapStep6Data() used to strip empty values before the
     * update, so a field the admin deliberately blanked was absent from the payload and
     * silently kept its old value. Needs no schema -- it is pure request mapping -- so it
     * runs on every environment.
     */
    public function test_map_step6_data_maps_a_blanked_field_to_null_rather_than_dropping_it(): void
    {
        $mapped = $this->invokePrivate('mapStep6Data', [
            $this->step6Request([
                'gradepay'         => 3,
                'employeecategory' => '',
                'basicpay'         => '',
                'bankname'         => '',
                'accountno'        => '',
            ]),
        ]);

        $this->assertArrayHasKey('bank_name', $mapped, 'A blanked field must stay in the payload.');
        $this->assertNull($mapped['bank_name'], 'A blanked field must be written as NULL, not dropped.');
        $this->assertNull($mapped['basic_pay']);
        $this->assertNull($mapped['account_no']);
        $this->assertNull($mapped['employee_category_master_pk']);
        $this->assertSame(3, $mapped['salary_grade_pk'], 'A filled field must survive unchanged.');
    }

    /**
     * F-010's other half, and the reason the all-or-nothing rule exists: a member who never
     * touched Step 6 must not get an empty payroll row, because the Estate module joins this
     * table for house eligibility.
     */
    public function test_map_step6_data_returns_nothing_when_the_step_was_untouched(): void
    {
        $mapped = $this->invokePrivate('mapStep6Data', [
            $this->step6Request([
                'gradepay'         => '',
                'employeecategory' => '',
                'basicpay'         => '',
                'bankname'         => '',
                'accountno'        => '',
            ]),
        ]);

        $this->assertSame([], $mapped, 'An untouched Step 6 must write nothing at all.');
    }

    /**
     * F-010 end to end: save a bank name, clear it, save again, and assert the stored value
     * is NULL. This is the exact closure evidence the finding asked for.
     */
    public function test_clearing_a_saved_step6_field_clears_it_in_the_database(): void
    {
        $this->requireStep6Schema();

        $employeePk = $this->makeEmployee();

        $this->invokePrivate('saveStep6PayrollData', [
            $employeePk,
            $this->step6Request(['bankname' => 'Bank To Be Cleared', 'basicpay' => 42000]),
        ]);

        $this->assertSame(
            'Bank To Be Cleared',
            PayrollSalaryMaster::where('employee_master_pk', $employeePk)->value('bank_name')
        );

        // The admin blanks the bank name and saves again, leaving basic pay in place.
        $this->invokePrivate('saveStep6PayrollData', [
            $employeePk,
            $this->step6Request(['bankname' => '', 'basicpay' => 42000]),
        ]);

        $row = PayrollSalaryMaster::where('employee_master_pk', $employeePk)->first();

        $this->assertNull($row->bank_name, 'A cleared field must be NULL, not its previous value.');
        $this->assertSame(42000, (int) $row->basic_pay, 'An untouched field must keep its value.');
    }
}
