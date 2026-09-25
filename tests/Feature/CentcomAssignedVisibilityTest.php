<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\IssueManagement\IssueManagementController;
use App\Models\IssueCategoryMaster;
use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
 * the development schema). DatabaseTransactions rolls back the one row seeded
 * below.
 */
class CentcomAssignedVisibilityTest extends TestCase
{
    use DatabaseTransactions;

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
            ->getJson(route($routeName) . '?' . http_build_query($query))
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

        $statuses = DB::table('employee_master')
            ->whereIn('pk', $offered->pluck('employee_pk'))
            ->pluck('status', 'pk');

        $wrong = $offered->filter(
            fn ($r) => (bool) $r->can_login !== ((int) ($statuses[$r->employee_pk] ?? 0) === 1)
        );

        $this->assertCount(0, $wrong, 'can_login disagrees with employee_master.status for some rows.');

        // The flag is worth nothing if it is true for everyone — this database
        // has ~800 dormant accounts, so the list must actually mark some.
        $this->assertTrue(
            $offered->contains(fn ($r) => ! $r->can_login),
            'No row was flagged as unable to sign in, so the guard is untested.'
        );
    }

    /**
     * The dropdown only disables those options in markup. The server has to
     * refuse them too, or the complaint lands on somebody who can never open
     * the app — the failure this whole path exists to prevent.
     */
    public function test_assigning_to_an_account_that_cannot_sign_in_is_rejected(): void
    {
        $actor = $this->employeeActor();

        $blockedPk = DB::table('employee_master')->where('status', '!=', 1)->value('pk');
        $category = IssueCategoryMaster::query()->first();
        if (! $blockedPk || ! $category) {
            $this->markTestSkipped('Need a deactivated employee and an issue category.');
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
     * Greying an account out must not erase an assignment already made to
     * somebody who has since been deactivated.
     */
    public function test_a_login_blocked_current_assignee_is_still_listed_on_the_detail_page(): void
    {
        $actor = $this->employeeActor();

        $blockedPk = DB::table('employee_master')->where('status', '!=', 1)->value('pk');
        if (! $blockedPk) {
            $this->markTestSkipped('No deactivated employee to assign to.');
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
            \Illuminate\View\View::class,
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
        $this->assertInstanceOf(\Illuminate\View\View::class, $view, 'Actor was redirected away from the issue.');

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
