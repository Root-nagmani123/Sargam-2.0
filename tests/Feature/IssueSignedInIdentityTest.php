<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-006: Issue Management identified the signed-in login with
 * getEmployeeIdsForUser(user_id), which ignores user_category. A student, or a
 * NULL-category login, whose user_id merely equals an employee's pk was
 * treated as that employee: it could open, list, edit and change the status
 * of that employee's complaints.
 *
 * The login is now an employee only as complaintLoginRows() says (see
 * User::complaintEmployeePkForLogin()). Each refusal below sits where the old
 * and the new rule disagree; the controls sit where they agree, so a fix that
 * refused everybody would fail them.
 *
 * Every fixture is a fresh row inside the rolled-back transaction.
 */
class IssueSignedInIdentityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A key no employee pk, employee pk_old or login user_id uses yet. */
    private function unusedKey(): int
    {
        return (int) max(
            DB::table('employee_master')->max('pk'),
            DB::table('employee_master')->max('pk_old'),
            DB::table('user_credentials')->max('user_id'),
        ) + 100000 + random_int(0, 99999);
    }

    private function employee(?int $pkOld = null): int
    {
        return (int) DB::table('employee_master')->insertGetId([
            'pk_old' => $pkOld,
            'first_name' => 'F006',
            'last_name' => 'Probe',
            'status' => 1,
        ]);
    }

    private function login(int $userId, ?string $category): User
    {
        $pk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'f006-probe-'.uniqid(),
            'user_id' => $userId,
            'user_category' => $category,
        ]);

        return User::findOrFail($pk);
    }

    /** Raised, logged and nodal-officered by $employeeKey, handed to a free-text handler. */
    private function issueOf(int $employeeKey, ?int $logger = null): IssueLogManagement
    {
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => (int) DB::table('issue_category_master')->value('pk'),
            'description' => 'F-006 identity probe',
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
            'created_by' => $employeeKey,
            'issue_logger' => $logger ?? $employeeKey,
            'employee_master_pk' => $employeeKey,
            'assigned_to' => 'F-006 handler',
            'location' => 'O',
            'created_date' => now(),
        ]);
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now(),
            'created_by' => $employeeKey,
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
        ]);

        return $issue;
    }

    /** The complaint pks a grid returns when searched for this complaint's pk. */
    private function listed(User $login, string $route, IssueLogManagement $issue): array
    {
        return collect($this->actingAs($login)
            ->getJson(route($route, ['search' => ['value' => (string) $issue->pk], 'length' => 10]))
            ->assertOk()
            ->json('data'))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function assertRefusedEverywhere(User $login, IssueLogManagement $issue): void
    {
        $before = $issue->fresh();
        $history = IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count();

        $this->assertNotContains((string) $issue->pk, $this->listed($login, 'admin.issue-management.data', $issue), 'index grid listed it');
        $this->assertNotContains((string) $issue->pk, $this->listed($login, 'admin.issue-management.centcom.data', $issue), 'Centcom grid listed it');

        $this->actingAs($login)->get(route('admin.issue-management.show', $issue->pk))
            ->assertRedirect(route('admin.issue-management.index'));

        $this->actingAs($login)->get(route('admin.issue-management.edit', $issue->pk))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');

        $this->actingAs($login)->put(route('admin.issue-management.update', $issue->pk), ['description' => 'F-006 tampered'])
            ->assertSessionHas('error');

        $this->actingAs($login)->put(route('admin.issue-management.status_update', $issue->pk), [
            'issue_status' => IssueLogManagement::STATUS_PENDING,
            'remark' => 'F-006 tamper',
        ])->assertSessionHas('error');

        $after = $issue->fresh();
        $this->assertSame((int) $before->issue_status, (int) $after->issue_status, 'the status changed');
        $this->assertSame($before->assigned_to, $after->assigned_to, 'the assignment changed');
        $this->assertSame($before->description, $after->description, 'the description changed');
        $this->assertSame($history, IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count(), 'a status-history row was written');
    }

    private function assertNodalOfficerCanAct(User $login, IssueLogManagement $issue): void
    {
        $this->assertContains((string) $issue->pk, $this->listed($login, 'admin.issue-management.data', $issue), 'index grid did not list it');
        $this->assertContains((string) $issue->pk, $this->listed($login, 'admin.issue-management.centcom.data', $issue), 'Centcom grid did not list it');
        $this->actingAs($login)->get(route('admin.issue-management.show', $issue->pk))->assertOk();
        $this->actingAs($login)->get(route('admin.issue-management.edit', $issue->pk))->assertOk();

        $this->actingAs($login)->put(route('admin.issue-management.status_update', $issue->pk), [
            'issue_status' => IssueLogManagement::STATUS_PENDING,
            'remark' => 'F-006 control',
        ])->assertSessionHas('success');

        $this->assertSame(IssueLogManagement::STATUS_PENDING, (int) $issue->fresh()->issue_status);
    }

    // ── Case A / B: a number shared with an employee's pk is not an identity ──

    public function test_a_student_sharing_the_employees_pk_is_refused(): void
    {
        $employee = $this->employee();

        $this->assertRefusedEverywhere($this->login($employee, 'S'), $this->issueOf($employee));
    }

    public function test_a_null_category_login_sharing_the_employees_pk_is_refused(): void
    {
        $employee = $this->employee();

        $this->assertRefusedEverywhere($this->login($employee, null), $this->issueOf($employee));
    }

    // ── Case C: the employee's own login still works ─────────────────────────

    public function test_control_the_employees_own_login_can_act(): void
    {
        $employee = $this->employee();

        $this->assertNodalOfficerCanAct($this->login($employee, 'E'), $this->issueOf($employee));
    }

    public function test_another_employees_own_login_is_refused(): void
    {
        $employee = $this->employee();

        $this->assertRefusedEverywhere($this->login($this->employee(), 'E'), $this->issueOf($employee));
    }

    /** complaintLoginRows(): on the employee's own pk, any category but 'S' and NULL is that employee. */
    public function test_control_another_non_student_category_on_the_employees_own_pk_can_act(): void
    {
        $employee = $this->employee();

        $this->assertNodalOfficerCanAct($this->login($employee, 'F'), $this->issueOf($employee));
    }

    // ── pk_old: the complaintLoginRows() rule ─────────────────────────────────

    public function test_control_a_null_category_login_on_the_employees_own_pk_old_can_act(): void
    {
        $pkOld = $this->unusedKey();
        $this->employee($pkOld);

        // Complaint stored under pk_old, as most legacy rows are.
        $this->assertNodalOfficerCanAct($this->login($pkOld, null), $this->issueOf($pkOld));
    }

    public function test_a_student_on_the_employees_pk_old_is_refused(): void
    {
        $pkOld = $this->unusedKey();
        $this->employee($pkOld);

        $this->assertRefusedEverywhere($this->login($pkOld, 'S'), $this->issueOf($pkOld));
    }

    public function test_a_login_on_a_pk_old_several_employees_share_is_refused(): void
    {
        $pkOld = $this->unusedKey();
        $this->employee($pkOld);
        $this->employee($pkOld);

        $this->assertRefusedEverywhere($this->login($pkOld, null), $this->issueOf($pkOld));
        $this->assertRefusedEverywhere($this->login($pkOld, 'E'), $this->issueOf($pkOld));
    }

    // ── A login that is no employee still sees what it logged ────────────────

    public function test_control_a_login_that_names_no_employee_still_sees_what_it_logged(): void
    {
        $key = $this->unusedKey();
        $issue = $this->issueOf($this->employee(), $key);

        $login = $this->login($key, null);
        $this->assertContains((string) $issue->pk, $this->listed($login, 'admin.issue-management.data', $issue));
        $this->actingAs($login)->get(route('admin.issue-management.show', $issue->pk))->assertOk();
    }
}
