<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A NULL-category login whose user_id is the employee's own pk_old is that
 * employee's identity for nodal-officer eligibility (Engineering Lead
 * decision, PR337-14). A NULL-category login that only reaches the employee
 * through employee_master.pk is not: those user_ids share the pk number
 * range and mostly name somebody else.
 *
 * complaintLoginRows() used to drop every NULL-category login, so an officer
 * whose only login was keyed by their pk_old was refused as nodal even
 * though LoginController lets that login in.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable — the same
 * convention as IssueNodalOfficerTamperingTest.
 */
class IssueNodalNullCategoryPkOldIdentityTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('IssueNodalNullCategoryPkOldIdentityTest needs the application database');
        }

        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            $this->markTestSkipped('employee_master has no pk_old column.');
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

    /** A key no employee pk, employee pk_old or login user_id uses yet. */
    private function unusedKey(): int
    {
        return (int) max(
            DB::table('employee_master')->max('pk'),
            DB::table('employee_master')->max('pk_old'),
            DB::table('user_credentials')->max('user_id'),
        ) + 100000 + random_int(0, 99999);
    }

    /** A fresh active employee with no login, inside the rolled-back transaction. */
    private function employee(?int $pkOld): int
    {
        $pk = (int) DB::table('employee_master')->insertGetId([
            'pk_old' => $pkOld,
            'first_name' => 'F017',
            'last_name' => 'Probe',
            'status' => 1,
        ]);

        $this->assertFalse(User::employeeCanSignIn($pk), 'fixture: the new employee can already sign in');

        return $pk;
    }

    private function nullCategoryLogin(int $userId): int
    {
        return (int) DB::table('user_credentials')->insertGetId([
            'user_name' => 'f017-probe-'.uniqid(),
            'user_id' => $userId,
            'user_category' => null,
        ]);
    }

    private function canLoginInDropdown(int $employeePk): ?int
    {
        $row = User::getEmployeesAndFacultyForComplaint()->firstWhere('employee_pk', $employeePk);

        return $row === null ? null : (int) $row->can_login;
    }

    public function test_a_null_category_login_on_the_employees_pk_old_lets_them_sign_in(): void
    {
        $pkOld = $this->unusedKey();
        $employee = $this->employee($pkOld);

        $this->nullCategoryLogin($pkOld);

        $this->assertTrue(User::employeeCanSignIn($employee), 'by pk');
        $this->assertTrue(User::employeeCanSignIn($pkOld), 'by pk_old');
        $this->assertSame(1, $this->canLoginInDropdown($employee), 'the dropdown disagrees with the server');
    }

    public function test_a_null_category_login_on_the_employees_pk_does_not_count(): void
    {
        $employee = $this->employee(null);

        $this->nullCategoryLogin($employee);

        $this->assertFalse(User::employeeCanSignIn($employee));
        $this->assertNull($this->canLoginInDropdown($employee), 'a pk-joined NULL-category login put the employee in the dropdown');
    }

    public function test_a_null_category_login_on_the_pk_does_not_count_when_the_employee_has_a_pk_old(): void
    {
        $pkOld = $this->unusedKey();
        $employee = $this->employee($pkOld);

        $this->nullCategoryLogin($employee);

        $this->assertFalse(User::employeeCanSignIn($employee), 'by pk');
        $this->assertFalse(User::employeeCanSignIn($pkOld), 'by pk_old');
        $this->assertNull($this->canLoginInDropdown($employee));
    }

    public function test_a_student_login_on_the_pk_old_still_does_not_count(): void
    {
        $pkOld = $this->unusedKey();
        $employee = $this->employee($pkOld);

        DB::table('user_credentials')->insert([
            'user_name' => 'f017-probe-'.uniqid(),
            'user_id' => $pkOld,
            'user_category' => 'S',
        ]);

        $this->assertFalse(User::employeeCanSignIn($employee));
    }

    /**
     * Eligible has to mean able to act: once stored as nodal, the officer
     * signs in with that NULL-category login and changes the status.
     */
    public function test_store_makes_the_pk_old_officer_nodal_and_skips_the_pk_joined_one(): void
    {
        $actor = User::query()
            ->where('user_category', 'E')
            ->whereIn('user_id', fn ($q) => $q->select('pk')->from('employee_master')->where('status', '!=', 2))
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']))
            ->first();
        $sub = DB::table('issue_sub_category_master')->whereNotNull('issue_category_master_pk')->first();
        $priority = DB::table('issue_priority_master')->value('pk');
        $building = DB::table('building_master')->value('pk');
        if (! $actor || ! $sub || ! $priority || ! $building) {
            $this->markTestSkipped('Needs a non-admin employee login, a sub-category, a priority and a building.');
        }

        $pkJoined = $this->employee(null);
        $this->nullCategoryLogin($pkJoined);

        $pkOld = $this->unusedKey();
        $pkOldOfficer = $this->employee($pkOld);
        $pkOldLogin = User::findOrFail($this->nullCategoryLogin($pkOld));

        DB::table('issue_category_employee_map')
            ->where('issue_category_master_pk', $sub->issue_category_master_pk)
            ->whereIn('priority', [1, 2, 3])
            ->delete();
        foreach ([1 => $pkJoined, 2 => $pkOldOfficer] as $level => $pk) {
            DB::table('issue_category_employee_map')->insert([
                'issue_category_master_pk' => $sub->issue_category_master_pk,
                'employee_master_pk' => $pk,
                'priority' => $level,
                'days_notify' => 1,
            ]);
        }

        $description = 'F-017 pk_old nodal probe '.uniqid();
        $this->actingAs($actor)
            ->post(route('admin.issue-management.store'), [
                'description' => $description,
                'issue_category_id' => $sub->issue_category_master_pk,
                'issue_sub_category_id' => $sub->pk,
                'issue_priority_id' => $priority,
                'sub_category_name' => 'probe',
                'created_by' => $actor->user_id,
                'nodal_employee_id' => null,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $pkOldOfficer, (string) $stored->employee_master_pk);

        $this->actingAs($pkOldLogin)
            ->put(route('admin.issue-management.status_update', $stored->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => 'other',
                'other_name' => 'F-017 probe handler',
                'other_phone' => '9876543210',
            ])
            ->assertSessionHas('success');

        $this->assertSame(IssueLogManagement::STATUS_IN_PROGRESS, (int) $stored->fresh()->issue_status);
    }
}
