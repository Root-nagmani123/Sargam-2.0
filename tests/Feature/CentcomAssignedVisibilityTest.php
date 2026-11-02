<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\IssueManagement\IssueManagementController;
use App\Models\IssueCategoryMaster;
use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Tests\TestCase;

/**
 * A ticket assigned to an employee has to be visible to that employee.
 *
 * It previously was not: the All Issues scope matched only employee_master_pk /
 * issue_logger / created_by, so an assignee saw nothing on the page the sidebar
 * lands on. The assigned queue lived solely behind the CENTCOM tab, whose own
 * sidebar entry (menus.permission_name = 'centcom_assigned') is granted to
 * Super Admin and Centcom Admin only — so an ordinary Employee had no listing
 * that showed the ticket at all.
 *
 * Like CentcomGridFeedsTest this runs against the real configured database
 * (phpunit.xml leaves DB_CONNECTION commented out, so RefreshDatabase would drop
 * the development schema). Every write happens inside a transaction that is
 * always rolled back.
 *
 * Skips - never fails - when the database is unreachable, per the suite
 * convention in phpunit.xml. That is why this does not use DatabaseTransactions:
 * the trait connects inside parent::setUp(), before any skip could run.
 */
class CentcomAssignedVisibilityTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('CentcomAssignedVisibilityTest needs the application database');
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

    private const COLUMNS = ['id', 'date', 'category', 'description', 'complainant', 'nodal', 'priority', 'status', 'action'];

    /**
     * A non-admin user whose user_credentials.user_id resolves to an
     * employee_master row — admins skip the scope entirely, so they cannot show
     * this regression.
     */
    private function employeeActor(): User
    {
        $user = User::query()
            ->whereNotNull('user_id')
            ->whereIn('user_id', fn ($q) => $q->select('pk')->from('employee_master'))
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']))
            ->first();

        if (! $user) {
            $this->markTestSkipped('No non-admin user_credentials row mapped to employee_master.');
        }

        return $user;
    }

    private function seedIssueAssignedTo(User $assignee): IssueLogManagement
    {
        $category = IssueCategoryMaster::query()->first();

        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        // Raised by somebody else, so the row can only reach the assignee's grid
        // through assigned_to — created_by / issue_logger must not rescue it.
        $other = (int) DB::table('employee_master')
            ->where('pk', '!=', $assignee->user_id)
            ->value('pk');

        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Assigned-visibility regression probe',
            // Deliberately not STATUS_REPORTED (0): a filter that wrongly casts an
            // absent status to 0 would still match a 0 row, so seeding Reported
            // here would hide exactly the bug these feeds need to catch.
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
            'created_by' => $other,
            'issue_logger' => $other,
            'employee_master_pk' => $other,
            // VARCHAR column: the employee pk is stored as a string.
            'assigned_to' => (string) $assignee->user_id,
            'created_date' => now(),
        ]);

        // Any mutation through the controller bumps the listing epoch; seeding
        // straight through the model has to do the same or the cached snapshot
        // from a previous draw is served and the assertion reads stale rows.
        IssueManagementController::bumpIndexListCacheEpoch();

        return $issue;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<int, int>
     */
    private function idsFromFeed(User $actor, string $routeName, array $extra = []): array
    {
        $cols = [];
        foreach (self::COLUMNS as $i => $name) {
            $cols[$i] = ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true'];
        }

        $query = array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => '', 'regex' => 'false'],
            'columns' => $cols,
            'order' => [['column' => 0, 'dir' => 'desc']],
            // The grids post every toolbar filter on each draw, empty or not.
            // Sending them matters: ConvertEmptyStringsToNull turns "" into null
            // before the controller reads it, and a guard that only rejects ""
            // then applies a (int) null = 0 filter. Omitting these here hid that
            // bug from the suite entirely.
            'status' => '',
            'category' => '',
            'priority' => '',
            'date_from' => '',
            'date_to' => '',
            'sort' => 'id',
            'dir' => 'desc',
        ], $extra);

        $payload = $this->actingAs($actor)
            ->getJson(route($routeName).'?'.http_build_query($query))
            ->assertOk()
            ->json();

        return array_map('intval', array_column($payload['data'], 'id'));
    }

    public function test_assignee_sees_the_ticket_on_the_all_issues_grid(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedIssueAssignedTo($actor);

        $this->assertContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, 'admin.issue-management.data'),
            'An issue assigned to the logged-in employee is missing from All Issues.'
        );
    }

    public function test_assignee_sees_the_ticket_on_the_centcom_grid(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedIssueAssignedTo($actor);

        $this->assertContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, 'admin.issue-management.centcom.data'),
            'An issue assigned to the logged-in employee is missing from the CENTCOM queue.'
        );
    }

    /**
     * An untouched toolbar must not filter anything.
     *
     * The grid posts status/category/priority on every draw; empty ones reach
     * the controller as null (ConvertEmptyStringsToNull). A guard that only
     * rejects "" then ran `where issue_status = (int) null` — pinning every
     * draw to status 0, so an assigned complaint in any other state disappeared
     * from Assign to You while All Requests, which uses filled(), still showed
     * it. Picking a real status must still filter.
     */
    public function test_empty_toolbar_filters_do_not_filter_the_centcom_grid(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedIssueAssignedTo($actor);   // STATUS_IN_PROGRESS
        $route = 'admin.issue-management.centcom.data';

        $this->assertContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, $route),
            'Empty toolbar filters silently filtered the CENTCOM grid.'
        );

        $this->assertContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, $route, ['status' => (string) IssueLogManagement::STATUS_IN_PROGRESS]),
            'Filtering by the issue own status dropped it.'
        );

        $this->assertNotContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, $route, ['status' => (string) IssueLogManagement::STATUS_REPORTED]),
            'The status filter stopped working — a Reported filter returned an In Progress issue.'
        );
    }

    public function test_raised_by_you_stays_raised_only(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedIssueAssignedTo($actor);

        // "Raised By You" answers a different question — widening the default
        // tab must not leak assigned work into it.
        $this->assertNotContains(
            (int) $issue->pk,
            $this->idsFromFeed($actor, 'admin.issue-management.data', ['raised_by' => 'self']),
            'An issue merely assigned to the employee leaked into "Raised By You".'
        );
    }

    /**
     * Every dropdown row has to say whether that account can sign in, so the
     * assignment list can grey out the ones that cannot while the complainant
     * list still names everybody.
     */
    public function test_the_dropdown_flags_employees_who_cannot_sign_in(): void
    {
        $offered = User::getEmployeesAndFacultyForComplaint();

        $this->assertNotEmpty($offered, 'The employee dropdown came back empty.');

        $missing = $offered->reject(fn ($r) => property_exists($r, 'can_login'));
        $this->assertCount(0, $missing, 'Some dropdown rows are missing can_login.');

        // Expected values come from a PHP restatement of LoginController, not
        // from the SQL under test, so a shared mistake cannot pass both.
        $employees = DB::table('employee_master')->get()->keyBy(fn ($e) => (string) $e->pk);
        $loginsByUserId = DB::table('user_credentials')
            ->whereNotNull('user_id')
            ->get(['user_id', 'user_category'])
            ->groupBy(fn ($c) => (string) $c->user_id);

        $wrong = $offered->filter(function ($r) use ($employees, $loginsByUserId) {
            $e = $employees[(string) $r->employee_pk] ?? null;
            $logins = collect($loginsByUserId[(string) $r->employee_pk] ?? []);
            if ($e && isset($e->pk_old) && $e->pk_old !== null && (string) $e->pk_old !== (string) $e->pk) {
                $logins = $logins->merge($loginsByUserId[(string) $e->pk_old] ?? []);
            }
            $expected = $logins
                ->filter(fn ($c) => $this->isEmployeesOwnLogin($c, $e, $employees))
                ->contains(fn ($c) => $this->loginControllerAdmits($c, $employees));

            return (bool) $r->can_login !== $expected;
        });

        $this->assertCount(0, $wrong, 'can_login disagrees with what LoginController would admit for some rows.');

        // The flag is worth nothing if it is true for everyone. A database with
        // no inactive employee cannot exercise that, which is a data gap, not a
        // failure — skip with the reason rather than fail.
        if (! $offered->contains(fn ($r) => ! $r->can_login)) {
            $this->markTestSkipped('No inactive employee in this database, so the cannot-sign-in flag is untested.');
        }
    }

    /**
     * The dropdown only disables those options in markup. The server has to
     * refuse them too, or the complaint lands on somebody who can never open
     * the app — the failure this whole path exists to prevent.
     */
    public function test_assigning_to_an_account_that_cannot_sign_in_is_rejected(): void
    {
        $actor = $this->employeeActor();

        $blockedPk = $this->loginBlockedEmployeePk();
        $category = IssueCategoryMaster::query()->first();
        if (! $blockedPk || ! $category) {
            $this->markTestSkipped('Need an employee none of whose logins can sign in, and an issue category.');
        }

        // Actor is the nodal officer on an unassigned issue, so status_update()
        // lets them through to the assignment step.
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Blocked-assignee rejection probe',
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
            'created_by' => $actor->user_id,
            'issue_logger' => $actor->user_id,
            'employee_master_pk' => $actor->user_id,
            'assigned_to' => null,
            'created_date' => now(),
        ]);
        IssueManagementController::bumpIndexListCacheEpoch();

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => (string) $blockedPk,
                'assigned_to' => (string) $blockedPk,
            ])
            ->assertSessionHas('error');

        $this->assertNull(
            $issue->fresh()->assigned_to,
            'The complaint was handed to an account that cannot sign in.'
        );
    }

    /**
     * LoginController checks the inactive flag for category 'E' only, so a
     * faculty login on a status-2 employee still signs in. That employee has
     * to stay assignable — in the dropdown and on the server.
     */
    public function test_a_faculty_login_on_an_inactive_employee_can_be_assigned(): void
    {
        $actor = $this->employeeActor();

        $pk = $this->loginBlockedEmployeePk();
        $category = IssueCategoryMaster::query()->first();
        if (! $pk || ! $category) {
            $this->markTestSkipped('Need an employee none of whose logins can sign in, and an issue category.');
        }

        // Give that employee a faculty login; the test transaction rolls it back.
        DB::table('user_credentials')->insert([
            'user_name' => 'pr337-faculty-probe-'.$pk,
            'user_id' => $pk,
            'user_category' => 'F',
        ]);
        $this->assertTrue($this->employeeAdmitted($pk), 'The fixture login is not one LoginController admits.');

        $row = User::getEmployeesAndFacultyForComplaint()
            ->first(fn ($r) => (string) $r->employee_pk === (string) $pk);
        $this->assertNotNull($row, 'The employee is missing from the dropdown.');
        $this->assertSame(1, (int) $row->can_login, 'A faculty login that can sign in was greyed out.');

        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Faculty-login assignee acceptance probe',
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
            'created_by' => $actor->user_id,
            'issue_logger' => $actor->user_id,
            'employee_master_pk' => $actor->user_id,
            'assigned_to' => null,
            'created_date' => now(),
        ]);
        IssueManagementController::bumpIndexListCacheEpoch();

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => (string) $pk,
                'assigned_to' => (string) $pk,
            ])
            ->assertSessionMissing('error');

        $this->assertSame(
            (string) $pk,
            (string) $issue->fresh()->assigned_to,
            'The server refused an assignee whose faculty login can sign in.'
        );
    }

    /**
     * A NULL-category login whose user_id happens to equal an inactive
     * employee's pk belongs to somebody else. LoginController admits it, but
     * as its own holder, so it must not make the employee assignable: the
     * ticket would reach nobody, or reach the stranger who holds that login.
     */
    public function test_a_colliding_null_category_login_does_not_make_an_inactive_employee_assignable(): void
    {
        $actor = $this->employeeActor();

        $pk = $this->loginBlockedEmployeePk();
        $category = IssueCategoryMaster::query()->first();
        if (! $pk || ! $category) {
            $this->markTestSkipped('Need an employee none of whose logins can sign in, and an issue category.');
        }

        // Status-2 employee with an 'E' login; add a NULL-category login that
        // collides on user_id but names a different person. The test
        // transaction rolls it back.
        $employeeFirstName = (string) DB::table('employee_master')->where('pk', $pk)->value('first_name');
        $strangerFirstName = strcasecmp(trim($employeeFirstName), 'Collision') === 0 ? 'Stranger' : 'Collision';
        DB::table('user_credentials')->insert([
            'user_name' => 'pr337-null-collision-probe-'.$pk,
            'user_id' => $pk,
            'user_category' => null,
            'first_name' => $strangerFirstName,
            'last_name' => 'Probe',
        ]);
        $collision = DB::table('user_credentials')->where('user_name', 'pr337-null-collision-probe-'.$pk)->first();

        // LoginController would let the colliding login in - the reason it does
        // not count is identity, not sign-in.
        $this->assertTrue($this->loginControllerAdmits($collision), 'The fixture login is not one LoginController admits.');
        $this->assertFalse($this->isEmployeesOwnLogin($collision), 'The oracle counts a NULL-category login as the employee\'s own.');
        $this->assertFalse($this->employeeAdmitted($pk), 'The oracle treats the employee as able to sign in through a stranger\'s login.');

        $this->assertFalse(User::employeeCanSignIn($pk), 'employeeCanSignIn() counted a colliding NULL-category login.');

        $row = User::getEmployeesAndFacultyForComplaint()
            ->first(fn ($r) => (string) $r->employee_pk === (string) $pk);
        $this->assertNotNull($row, 'The employee (listed through their own E login) is missing from the dropdown.');
        $this->assertSame(0, (int) $row->can_login, 'A colliding NULL-category login made an inactive employee look loginable.');

        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'NULL-category collision rejection probe',
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
            'created_by' => $actor->user_id,
            'issue_logger' => $actor->user_id,
            'employee_master_pk' => $actor->user_id,
            'assigned_to' => null,
            'created_date' => now(),
        ]);
        IssueManagementController::bumpIndexListCacheEpoch();

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => (string) $pk,
                'assigned_to' => (string) $pk,
            ])
            ->assertSessionHas('error');

        $this->assertNull(
            $issue->fresh()->assigned_to,
            'The server assigned the complaint to an inactive employee on the strength of a stranger\'s login.'
        );
    }

    /**
     * LoginController's inactive-account check, restated in PHP so the SQL rule
     * in User is tested against an independent copy: only category 'E' is
     * checked, the employee is found by pk = user_id (never pk_old), and only
     * status 2 is refused.
     *
     * @param  Collection|null  $employees  employee_master rows keyed by (string) pk
     */
    private function loginControllerAdmits(object $credential, $employees = null): bool
    {
        if ($credential->user_category === 'E') {
            $employee = $employees !== null
                ? ($employees[(string) $credential->user_id] ?? null)
                : DB::table('employee_master')->where('pk', $credential->user_id)->first();
            if ($employee && $employee->status == 2) {
                return false;
            }
        }

        return true;
    }

    /** Whether any non-student login belonging to the employee (by pk or pk_old) gets in. */
    private function employeeAdmitted(int $employeePk): bool
    {
        $employee = DB::table('employee_master')->where('pk', $employeePk)->first();
        if (! $employee) {
            return false;
        }

        $keys = array_values(array_filter(
            [$employee->pk, $employee->pk_old ?? null],
            fn ($k) => $k !== null && $k !== ''
        ));

        return DB::table('user_credentials')
            ->whereIn('user_id', $keys)
            ->get(['user_id', 'user_category'])
            ->filter(fn ($c) => $this->isEmployeesOwnLogin($c, $employee))
            ->contains(fn ($c) => $this->loginControllerAdmits($c));
    }

    /**
     * The identity rule, stated on its own so neither the oracle nor the SQL
     * can assume it silently: a user_credentials row whose user_id equals an
     * employee key is that employee's login only when it has a category and the
     * category is not 'S'. NULL-category rows are a separate population whose
     * user_id values collide numerically with employee_master.pk; on the
     * development copy they share the joined employee's first name in 6 of 331
     * pairs, against 1,213 of 1,219 for 'E' logins. The one exception is a
     * NULL-category row whose user_id is the employee's own pk_old: that is
     * the employee's login (Engineering Lead decision, PR337-14). Without the
     * employee, a NULL-category row is never theirs.
     *
     * A row reached through the employee's pk_old (any category but 'S')
     * counts only when that key names this employee alone: nobody has it as
     * pk, and nobody else has it as pk_old. Otherwise the signed-in login may
     * resolve to the other employee (PR #337 F-021).
     *
     * @param  Collection|null  $employees  employee_master rows keyed by (string) pk
     */
    private function isEmployeesOwnLogin(object $credential, ?object $employee = null, $employees = null): bool
    {
        if ($credential->user_category === 'S') {
            return false;
        }
        if ($employee === null) {
            return $credential->user_category !== null;
        }

        $key = (string) $credential->user_id;
        if ($key === (string) $employee->pk) {
            return $credential->user_category !== null;
        }

        $viaPkOld = isset($employee->pk_old)
            && $employee->pk_old !== null
            && (string) $employee->pk_old === $key;

        return $viaPkOld && $this->pkOldKeyNamesOnlyThisEmployee($key, $employees);
    }

    /** No employee has $key as pk, and exactly one has it as pk_old. */
    private function pkOldKeyNamesOnlyThisEmployee(string $key, $employees = null): bool
    {
        if ($employees !== null) {
            return ! isset($employees[$key])
                && $employees->filter(fn ($e) => isset($e->pk_old) && (string) $e->pk_old === $key)->count() === 1;
        }

        return ! DB::table('employee_master')->where('pk', $key)->exists()
            && DB::table('employee_master')->where('pk_old', $key)->count() === 1;
    }

    /** An employee with an 'E' login on status 2 and no other login that gets in. */
    private function loginBlockedEmployeePk(): ?int
    {
        $candidates = DB::table('user_credentials as uc')
            ->join('employee_master as e', 'e.pk', '=', 'uc.user_id')
            ->where('uc.user_category', 'E')
            ->where('e.status', 2)
            ->limit(50)
            ->pluck('e.pk');

        foreach ($candidates as $pk) {
            if (! $this->employeeAdmitted((int) $pk)) {
                return (int) $pk;
            }
        }

        return null;
    }

    /**
     * Greying an account out must not erase an assignment already made to
     * somebody who has since been deactivated.
     */
    public function test_a_login_blocked_current_assignee_is_still_listed_on_the_detail_page(): void
    {
        $actor = $this->employeeActor();

        $blockedPk = $this->loginBlockedEmployeePk();
        if (! $blockedPk) {
            $this->markTestSkipped('No employee none of whose logins can sign in.');
        }

        $category = IssueCategoryMaster::query()->first();
        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        // The real shape of the bug: the actor is the nodal officer handing the
        // complaint to a deactivated employee. Nodal officer, so show() still
        // grants them the page after the assignment moves away.
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Deactivated-assignee regression probe',
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
            'created_by' => $actor->user_id,
            'issue_logger' => $actor->user_id,
            'employee_master_pk' => $actor->user_id,
            'assigned_to' => (string) $blockedPk,
            'created_date' => now(),
        ]);
        IssueManagementController::bumpIndexListCacheEpoch();

        // Read the view data rather than rendering: the show blade leaves an
        // output buffer open, which PHPUnit reports as a risky test.
        $this->actingAs($actor);
        $view = app(IssueManagementController::class)->show($issue->pk);

        $this->assertInstanceOf(
            View::class,
            $view,
            'The nodal officer was redirected away from their own issue.'
        );
        $employees = $view->getData()['employees'];

        $this->assertTrue(
            $employees->contains(fn ($e) => (string) $e->employee_pk === (string) $blockedPk),
            'A deactivated current assignee dropped off the detail page, so re-saving would lose the assignment.'
        );
    }

    /**
     * Render the status <select> the detail page shows $actor for $issue.
     *
     * @return array<string, bool> option label => disabled
     */
    private function statusOptionsFor(User $actor, int $issueId): array
    {
        $this->actingAs($actor);
        $view = app(IssueManagementController::class)->show($issueId);
        $this->assertInstanceOf(View::class, $view, 'Actor was redirected away from the issue.');

        // The show blade leaves an output buffer open; unwind whatever it adds
        // so PHPUnit does not flag the test as risky.
        $level = ob_get_level();
        $html = $view->render();
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $this->assertSame(1, preg_match('/<select name="issue_status".*?<\/select>/s', $html, $m), 'Status select not rendered.');
        preg_match_all('/<option value="(\d+)"([^>]*)>([^<]*)</', $m[0], $opts, PREG_SET_ORDER);

        $out = [];
        foreach ($opts as $o) {
            $out[trim($o[3])] = str_contains($o[2], 'disabled');
        }

        return $out;
    }

    /**
     * A reopened complaint starts its cycle again. Counting the whole history
     * left the assignee unable to close it: Completed had been used before the
     * reopen, so the option stayed disabled and the work could never be marked
     * done.
     */
    public function test_assignee_can_still_complete_a_reopened_ticket_after_starting_work(): void
    {
        $actor = $this->employeeActor();
        $category = IssueCategoryMaster::query()->first();
        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        // Actor is the assignee, somebody else is the nodal officer — the nodal
        // officer is exempt from the lock, so they cannot show this bug.
        $other = (int) DB::table('employee_master')->where('pk', '!=', $actor->user_id)->value('pk');
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Reopened-completion regression probe',
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
            'created_by' => $other,
            'issue_logger' => $other,
            'employee_master_pk' => $other,
            'assigned_to' => (string) $actor->user_id,
            'created_date' => now(),
        ]);

        // Reported → In Progress → Completed → Reopened → In Progress.
        $cycle = [
            IssueLogManagement::STATUS_REPORTED,
            IssueLogManagement::STATUS_IN_PROGRESS,
            IssueLogManagement::STATUS_COMPLETED,
            IssueLogManagement::STATUS_REOPENED,
            IssueLogManagement::STATUS_IN_PROGRESS,
        ];
        foreach ($cycle as $i => $status) {
            IssueLogStatus::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_date' => now()->addSeconds($i),
                'created_by' => $other,
                'issue_status' => $status,
            ]);
        }
        IssueManagementController::bumpIndexListCacheEpoch();

        $options = $this->statusOptionsFor($actor, (int) $issue->pk);

        $this->assertFalse(
            $options['Completed'] ?? true,
            'The assignee cannot mark a reopened complaint Completed — the option is disabled.'
        );
    }

    /**
     * The forward-only rule still has to hold on a complaint that was never
     * reopened, or the reopen fix would have unlocked every old status.
     */
    public function test_used_statuses_stay_locked_when_the_ticket_was_never_reopened(): void
    {
        $actor = $this->employeeActor();
        $category = IssueCategoryMaster::query()->first();
        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        $other = (int) DB::table('employee_master')->where('pk', '!=', $actor->user_id)->value('pk');
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Forward-only regression probe',
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
            'created_by' => $other,
            'issue_logger' => $other,
            'employee_master_pk' => $other,
            'assigned_to' => (string) $actor->user_id,
            'created_date' => now(),
        ]);

        foreach ([IssueLogManagement::STATUS_REPORTED, IssueLogManagement::STATUS_IN_PROGRESS] as $i => $status) {
            IssueLogStatus::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_date' => now()->addSeconds($i),
                'created_by' => $other,
                'issue_status' => $status,
            ]);
        }
        IssueManagementController::bumpIndexListCacheEpoch();

        $options = $this->statusOptionsFor($actor, (int) $issue->pk);

        $this->assertTrue($options['Reported'] ?? false, 'A used status is selectable again without any reopen.');
        $this->assertFalse($options['Completed'] ?? true, 'Completed was never used, so it must stay selectable.');
    }

    /**
     * Seed an issue assigned to $actor (somebody else is nodal officer) with the
     * given status history, oldest first, and the last status as current.
     *
     * @param  array<int, int>  $history
     */
    private function seedAssignedIssueWithHistory(User $actor, array $history): IssueLogManagement
    {
        $category = IssueCategoryMaster::query()->first();
        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        $other = (int) DB::table('employee_master')->where('pk', '!=', $actor->user_id)->value('pk');
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Server-side status-lock probe',
            'issue_status' => end($history),
            'created_by' => $other,
            'issue_logger' => $other,
            'employee_master_pk' => $other,
            'assigned_to' => (string) $actor->user_id,
            'created_date' => now(),
        ]);

        foreach ($history as $i => $status) {
            IssueLogStatus::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_date' => now()->subMinutes(10)->addSeconds($i),
                'created_by' => $other,
                'issue_status' => $status,
            ]);
        }
        IssueManagementController::bumpIndexListCacheEpoch();

        return $issue;
    }

    /**
     * The disabled option is only markup: a crafted PUT of a status already
     * used in this cycle must be refused by the server, with nothing written.
     */
    public function test_the_server_refuses_a_used_status_from_the_assignee(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedAssignedIssueWithHistory($actor, [
            IssueLogManagement::STATUS_REPORTED,
            IssueLogManagement::STATUS_IN_PROGRESS,
            IssueLogManagement::STATUS_COMPLETED,
        ]);

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_REPORTED,
                'remark' => 'crafted move back to Reported',
            ])
            ->assertSessionHas('error');

        $this->assertSame(IssueLogManagement::STATUS_COMPLETED, (int) $issue->fresh()->issue_status,
            'A used status was accepted by the server.');
        $this->assertSame(3, IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count(),
            'A refused status change still wrote a history row.');
    }

    /**
     * Reopened restarts the cycle, so if the assignee could set it they could
     * reset their own lock: PUT 6, then PUT a used status. Both must be
     * refused, with nothing written.
     */
    public function test_the_assignee_cannot_reopen_to_reset_the_status_lock(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedAssignedIssueWithHistory($actor, [
            IssueLogManagement::STATUS_REPORTED,
            IssueLogManagement::STATUS_IN_PROGRESS,
            IssueLogManagement::STATUS_COMPLETED,
        ]);

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_REOPENED,
                'remark' => 'crafted reopen by the assignee',
            ])
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_REPORTED,
                'remark' => 'move back to Reported after the reopen',
            ])
            ->assertSessionHas('error');

        $this->assertSame(IssueLogManagement::STATUS_COMPLETED, (int) $issue->fresh()->issue_status,
            'The assignee reset the status lock by reopening.');
        $this->assertSame(3, IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count(),
            'A refused status change still wrote a history row.');
    }

    /** The modal must not offer the assignee the Reopened option the server refuses. */
    public function test_the_modal_does_not_offer_reopen_to_the_assignee(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedAssignedIssueWithHistory($actor, [
            IssueLogManagement::STATUS_REPORTED,
            IssueLogManagement::STATUS_IN_PROGRESS,
        ]);

        $options = $this->statusOptionsFor($actor, (int) $issue->pk);

        $this->assertTrue($options['Reopened'] ?? false, 'The assignee is offered Reopened.');
    }

    /** After a Reopen the cycle restarts, so the server must accept Completed again. */
    public function test_the_server_accepts_completing_a_reopened_ticket(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedAssignedIssueWithHistory($actor, [
            IssueLogManagement::STATUS_REPORTED,
            IssueLogManagement::STATUS_IN_PROGRESS,
            IssueLogManagement::STATUS_COMPLETED,
            IssueLogManagement::STATUS_REOPENED,
        ]);

        $this->actingAs($actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_COMPLETED,
                'remark' => 'done again after reopen',
            ])
            ->assertSessionMissing('error');

        $this->assertSame(IssueLogManagement::STATUS_COMPLETED, (int) $issue->fresh()->issue_status,
            'The assignee could not complete a reopened ticket.');
    }

    public function test_an_unrelated_employee_still_cannot_see_the_ticket(): void
    {
        $actor = $this->employeeActor();
        $issue = $this->seedIssueAssignedTo($actor);

        $stranger = User::query()
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $actor->user_id)
            ->whereIn('user_id', fn ($q) => $q->select('pk')->from('employee_master'))
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']))
            ->where('user_id', '!=', $issue->created_by)
            ->first();

        if (! $stranger) {
            $this->markTestSkipped('No second non-admin employee user to test isolation against.');
        }

        $this->assertNotContains(
            (int) $issue->pk,
            $this->idsFromFeed($stranger, 'admin.issue-management.data'),
            'The widened scope exposed an issue to an employee with no part in it.'
        );
    }
}
