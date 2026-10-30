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

    // ── L-69: what a colliding login writes is not attributed to the employee ──

    /** A category whose matrix lists only $officer, at Level 1. @return array{0: int, 1: int} [category pk, sub-category pk] */
    private function categoryWithOfficer(int $officer): array
    {
        $row = DB::table('issue_sub_category_master')->whereNotNull('issue_category_master_pk')->first(['pk', 'issue_category_master_pk']);
        if (! $row) {
            $this->markTestSkipped('Needs an issue sub-category.');
        }
        DB::table('issue_category_employee_map')->where('issue_category_master_pk', $row->issue_category_master_pk)->delete();
        DB::table('issue_category_employee_map')->insert([
            'issue_category_master_pk' => $row->issue_category_master_pk,
            'employee_master_pk' => $officer,
            'priority' => 1,
            'days_notify' => 1,
        ]);

        return [(int) $row->issue_category_master_pk, (int) $row->pk];
    }

    public function test_a_null_category_login_sharing_an_employees_pk_does_not_log_complaints_as_that_employee(): void
    {
        $officer = $this->employee();
        $this->login($officer, 'E');
        [$category, $subCategory] = $this->categoryWithOfficer($officer);
        $victim = $this->employee();
        $victimLogin = $this->login($victim, 'E');
        $building = DB::table('building_master')->value('pk');
        if (! $building) {
            $this->markTestSkipped('Needs a building_master row.');
        }
        $description = 'L-69 logger probe '.uniqid();

        $this->actingAs($this->login($victim, null))
            ->post(route('admin.issue-management.store'), [
                'description' => $description,
                'issue_category_id' => $category,
                'issue_sub_category_id' => $subCategory,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'sub_category_name' => 'probe',
                'created_by' => $this->employee(),
                'nodal_employee_id' => $officer,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors();

        $issue = IssueLogManagement::where('description', $description)->firstOrFail();
        $this->assertNotSame((string) $victim, (string) $issue->issue_logger, 'the complaint was logged in the colliding employee\'s name');
        $this->actingAs($victimLogin)->get(route('admin.issue-management.edit', $issue->pk))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');
    }

    public function test_a_student_sharing_an_employees_pk_does_not_drop_that_employee_from_the_nodal_officers(): void
    {
        $officer = $this->employee();
        $this->login($officer, 'E');
        [$category] = $this->categoryWithOfficer($officer);

        $offer = $this->actingAs($this->login($officer, 'S'))
            ->getJson(route('admin.issue-management.nodal-employees', $category))
            ->assertOk()
            ->json();

        $this->assertSame((string) $officer, (string) $offer['level1_auto_select'], 'the employee was excluded as if they were the signed-in student');
    }

    public function test_the_create_form_does_not_preselect_the_employee_a_colliding_login_shares_a_pk_with(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->login($employee, null))
            ->get(route('admin.issue-management.create'))
            ->assertOk()
            // A closure: assertViewHas($key, null) only checks the key exists.
            ->assertViewHas('currentUserEmployeeId', fn ($id) => $id === null);

        $this->actingAs($this->login($employee, 'E'))
            ->get(route('admin.issue-management.create'))
            ->assertViewHas('currentUserEmployeeId', (string) $employee);
    }
}
