<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Create / Edit form and the detail page must agree with the server.
 *
 * The nodal-employees endpoint used to offer and preselect every Level 1
 * officer, including ones resolveNodalOfficer() discards (cannot sign in, or
 * the complainant / logger), so a complaint was stored under somebody else
 * without a word. show.blade.php compared the complaint's people with
 * user_id alone, while status_update() matches pk and pk_old, so an officer
 * whose complaint is keyed by their pk_old lost the controls the server
 * would accept from them.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable — the same
 * convention as IssueNodalOfficerTamperingTest.
 */
class IssueNodalFormAndShowIdentityTest extends TestCase
{
    private bool $inTransaction = false;

    private User $actor;

    /** Officers who can sign in, neither of them the actor. */
    private int $nodalA;

    private int $nodalB;

    private int $categoryPk;

    private int $subCategoryPk;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('IssueNodalFormAndShowIdentityTest needs the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $this->actor = $this->nonAdminLogin(fn ($q) => $q->whereIn('user_id', fn ($s) => $s->select('pk')->from('employee_master')))
            ?? $this->skip('No non-admin user_credentials row mapped to employee_master.');

        $others = DB::table('employee_master')
            ->where('pk', '!=', $this->actor->user_id)
            ->whereIn('pk', fn ($q) => $q->select('user_id')->from('user_credentials')->whereNotNull('user_id'))
            ->orderBy('pk')
            ->limit(50)
            ->pluck('pk')
            ->filter(fn ($pk) => User::employeeCanSignIn($pk))
            ->take(2)
            ->map(fn ($pk) => (int) $pk)
            ->values()
            ->all();
        if (count($others) < 2) {
            $this->markTestSkipped('Needs two employees besides the actor.');
        }
        [$this->nodalA, $this->nodalB] = $others;

        $row = DB::table('issue_sub_category_master')
            ->whereNotNull('issue_category_master_pk')
            ->select('pk', 'issue_category_master_pk')
            ->first();
        if (! $row) {
            $this->markTestSkipped('Needs an issue sub-category.');
        }
        $this->categoryPk = (int) $row->issue_category_master_pk;
        $this->subCategoryPk = (int) $row->pk;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    private function skip(string $why): never
    {
        $this->markTestSkipped($why);
    }

    private function nonAdminLogin(callable $scope): ?User
    {
        $query = User::query()
            ->whereNotNull('user_id')
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']));
        $scope($query);

        return $query->first();
    }

    /** @param array<int, array<int, int>> $levels priority => employee pks, for Levels 1 to 3 */
    private function setMatrix(array $levels): void
    {
        DB::table('issue_category_employee_map')
            ->where('issue_category_master_pk', $this->categoryPk)
            ->whereIn('priority', [1, 2, 3])
            ->delete();

        foreach ([1, 2, 3] as $priority) {
            foreach ($levels[$priority] ?? [] as $pk) {
                DB::table('issue_category_employee_map')->insert([
                    'issue_category_master_pk' => $this->categoryPk,
                    'employee_master_pk' => $pk,
                    'priority' => $priority,
                    'days_notify' => 1,
                ]);
            }
        }
    }

    /** An employee with no login at all, other than the fixture's officers. */
    private function officerWithNoLogin(): int
    {
        $pk = DB::table('employee_master')
            ->whereNotIn('pk', [$this->nodalA, $this->nodalB, (int) $this->actor->user_id])
            ->whereNotIn('pk', fn ($q) => $q->select('user_id')->from('user_credentials')->whereNotNull('user_id'))
            ->orderBy('pk')
            ->limit(50)
            ->pluck('pk')
            ->first(fn ($pk) => ! User::employeeCanSignIn($pk));
        if ($pk === null) {
            $this->markTestSkipped('Needs an employee without a login.');
        }

        return (int) $pk;
    }

    /** @return array<int, string> employee_pk of every officer the endpoint offers */
    private function offered(array $response): array
    {
        return array_map(fn ($row) => (string) $row['employee_pk'], $response['level1'] ?? []);
    }

    private function nodalEmployees(array $query = []): array
    {
        return $this->actingAs($this->actor)
            ->getJson(route('admin.issue-management.nodal-employees', $this->categoryPk).($query ? '?'.http_build_query($query) : ''))
            ->assertOk()
            ->json();
    }

    /** @return array<string, mixed> */
    private function storePayload(string $description, $nodal): array
    {
        $building = DB::table('building_master')->value('pk') ?? $this->skip('Needs a building_master row.');

        return [
            'description' => $description,
            'issue_category_id' => $this->categoryPk,
            'issue_sub_category_id' => $this->subCategoryPk,
            'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
            'sub_category_name' => 'probe',
            'created_by' => $this->actor->user_id,
            'nodal_employee_id' => $nodal,
            'location' => 'O',
            'building_master_pk' => (int) $building,
        ];
    }

    // ── F-018: the form offers only what the server accepts ────────────────────

    public function test_endpoint_does_not_offer_or_preselect_a_level_one_officer_who_cannot_sign_in(): void
    {
        $noLogin = $this->officerWithNoLogin();
        $this->setMatrix([1 => [$noLogin, $this->nodalB], 3 => [$this->nodalA]]);

        $response = $this->nodalEmployees();

        $this->assertTrue($response['success']);
        $this->assertNotContains((string) $noLogin, $this->offered($response), 'offered a Level 1 officer who cannot sign in');
        $this->assertContains((string) $this->nodalB, $this->offered($response));
        $this->assertSame((string) $this->nodalB, (string) $response['level1_auto_select']);
        $this->assertSame(1, $response['assigned_level']);
    }

    public function test_endpoint_offers_the_level_two_officer_the_server_will_use_when_no_level_one_officer_can_sign_in(): void
    {
        $noLogin = $this->officerWithNoLogin();
        $this->setMatrix([1 => [$noLogin], 2 => [$this->nodalB], 3 => [$this->nodalA]]);

        $response = $this->nodalEmployees();

        $this->assertTrue($response['success']);
        $this->assertSame([(string) $this->nodalB], $this->offered($response));
        $this->assertSame((string) $this->nodalB, (string) $response['level1_auto_select']);
        $this->assertSame(2, $response['assigned_level']);

        // Submitting what the form preselected stores exactly that officer, with no substitution notice.
        $description = 'Form fallback probe '.uniqid();
        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description, $response['level1_auto_select']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Complaint submitted successfully!');
        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored);
        $this->assertSame((string) $this->nodalB, (string) $stored->employee_master_pk);
    }

    public function test_endpoint_excludes_the_signed_in_logger_and_the_named_complainant(): void
    {
        $this->setMatrix([1 => [(int) $this->actor->user_id, $this->nodalA, $this->nodalB]]);

        $response = $this->nodalEmployees(['complainant' => $this->nodalA]);

        $this->assertSame([(string) $this->nodalB], $this->offered($response));
        $this->assertSame((string) $this->nodalB, (string) $response['level1_auto_select']);
    }

    public function test_endpoint_offers_nobody_when_no_level_has_an_eligible_officer(): void
    {
        $noLogin = $this->officerWithNoLogin();
        $this->setMatrix([1 => [$noLogin], 2 => [(int) $this->actor->user_id]]);

        $response = $this->nodalEmployees();

        $this->assertFalse($response['success']);
        $this->assertSame([], $response['level1']);
        $this->assertNull($response['level1_auto_select']);
    }

    public function test_store_says_so_when_the_requested_officer_is_replaced(): void
    {
        $noLogin = $this->officerWithNoLogin();
        $this->setMatrix([1 => [$noLogin, $this->nodalB]]);
        $description = 'Form substitution notice probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description, $noLogin))
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored);
        $this->assertSame((string) $this->nodalB, (string) $stored->employee_master_pk);
        $this->assertStringContainsString('is not on offer for this complaint, so it was assigned to', (string) session('success'), 'the complaint went to another officer without saying so');
    }

    // ── F-007 / F-010: the detail page knows a pk_old-keyed officer ───────────

    /**
     * A non-admin 'E' login of an active employee whose pk_old differs from
     * pk and whose pk is nobody's pk_old, so getEmployeeIdsForUser() resolves
     * the login to exactly that employee.
     *
     * @return array{0: User, 1: object}
     */
    private function pkOldOfficer(): array
    {
        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            $this->markTestSkipped('employee_master has no pk_old column.');
        }

        $login = $this->nonAdminLogin(fn ($q) => $q
            ->where('user_category', 'E')
            ->whereNotIn('user_id', [(int) $this->actor->user_id])
            ->whereIn('user_id', fn ($s) => $s->select('pk')->from('employee_master')
                ->whereNotNull('pk_old')
                ->whereColumn('pk_old', '!=', 'pk')
                ->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 2)))
            ->whereNotIn('user_id', fn ($s) => $s->select('pk_old')->from('employee_master')->whereNotNull('pk_old'))
            ->orderBy('pk'));
        if (! $login) {
            $this->markTestSkipped('Needs an employee login whose employee has a distinct pk_old.');
        }

        $employee = DB::table('employee_master')->where('pk', $login->user_id)->first();
        $this->assertContains((string) $employee->pk_old, getEmployeeIdsForUser($login->user_id), 'fixture: pk_old is not one of the login\'s ids');

        return [$login, $employee];
    }

    /** A complaint the actor raised and logged, whose nodal officer is stored by pk_old. */
    private function pkOldKeyedIssue(object $employee, int $status, ?string $assignedTo = null): IssueLogManagement
    {
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $this->categoryPk,
            'description' => 'pk_old identity probe',
            'issue_status' => $status,
            'created_by' => $this->actor->user_id,
            'issue_logger' => $this->actor->user_id,
            'employee_master_pk' => $employee->pk_old,
            'assigned_to' => $assignedTo,
            'location' => 'O',
            'created_date' => now(),
        ]);
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now(),
            'created_by' => $this->actor->user_id,
            'issue_status' => $status,
        ]);

        return $issue;
    }

    private const UPDATE_STATUS_BUTTON = 'data-bs-target="#updateStatusModal"';

    private const REASSIGN_NOTICE = 'As nodal officer, you can change the assigned person';

    private function reopenedOptionDisabled(string $html): bool
    {
        return (bool) preg_match('/<option value="6"[^>]*\bdisabled\b/', $html);
    }

    public function test_pk_old_keyed_nodal_officer_gets_the_update_status_and_reassign_controls(): void
    {
        [$login, $employee] = $this->pkOldOfficer();
        $issue = $this->pkOldKeyedIssue($employee, IssueLogManagement::STATUS_IN_PROGRESS, (string) $this->nodalB);

        $html = $this->actingAs($login)
            ->get(route('admin.issue-management.show', $issue->pk))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(self::UPDATE_STATUS_BUTTON, $html, 'the nodal officer keyed by pk_old got no Update Status button');
        $this->assertStringContainsString(self::REASSIGN_NOTICE, $html, 'the nodal officer keyed by pk_old got no re-assign control');
        // F-010: the nodal officer may set Reopened; the server accepts it.
        $this->assertFalse($this->reopenedOptionDisabled($html), 'Reopened is disabled for the nodal officer keyed by pk_old');
    }

    public function test_server_accepts_reopened_from_the_pk_old_keyed_nodal_officer(): void
    {
        [$login, $employee] = $this->pkOldOfficer();
        $issue = $this->pkOldKeyedIssue($employee, IssueLogManagement::STATUS_IN_PROGRESS, (string) $this->nodalB);

        $this->actingAs($login)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_REOPENED,
                'remark' => 'pk_old nodal reopen probe',
            ])
            ->assertSessionHas('success');

        $this->assertSame(IssueLogManagement::STATUS_REOPENED, (int) $issue->fresh()->issue_status);
    }

    public function test_complainant_of_an_open_complaint_still_gets_no_status_controls(): void
    {
        [, $employee] = $this->pkOldOfficer();
        $issue = $this->pkOldKeyedIssue($employee, IssueLogManagement::STATUS_IN_PROGRESS, (string) $this->nodalB);

        $html = $this->actingAs($this->actor)
            ->get(route('admin.issue-management.show', $issue->pk))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(self::UPDATE_STATUS_BUTTON, $html);
        $this->assertStringNotContainsString(self::REASSIGN_NOTICE, $html);
        $this->assertTrue($this->reopenedOptionDisabled($html));
    }

    // ── A login with no user_id owns nothing ─────────────────────────────────
    //
    // The ownership checks fell back to [(string) $userId] when
    // getEmployeeIdsForUser() found nothing. For a NULL user_id that is [""],
    // and a NULL assigned_to / employee_master_pk / created_by / issue_logger
    // casts to "" as well, so such a login passed as that complaint's
    // assignee, nodal officer, complainant or logger.

    /** A non-admin login, other than the actor, whose user_id is set NULL inside the transaction. */
    private function loginWithNullUserId(): User
    {
        $login = $this->nonAdminLogin(fn ($q) => $q->where('pk', '!=', $this->actor->pk)->orderBy('pk'))
            ?? $this->skip('Needs a second non-admin login.');

        DB::table('user_credentials')->where('pk', $login->pk)->update(['user_id' => null]);
        $login = $login->fresh();
        $this->assertNull($login->user_id, 'fixture: user_id is not NULL');

        return $login;
    }

    /** @param array<string, mixed> $identity */
    private function issueWith(array $identity): IssueLogManagement
    {
        $issue = IssueLogManagement::create(array_merge([
            'issue_category_master_pk' => $this->categoryPk,
            'description' => 'NULL user_id probe',
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
            'location' => 'O',
            'created_date' => now(),
        ], $identity));
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now(),
            'created_by' => $this->actor->user_id,
            'issue_status' => IssueLogManagement::STATUS_REPORTED,
        ]);

        return $issue;
    }

    private function assertStatusUpdateRefused(User $login, IssueLogManagement $issue): void
    {
        $history = IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count();
        $before = $issue->fresh();

        // A status change plus a first assignment ("Other" flow): what an
        // assignee or nodal officer may do on an unassigned complaint.
        $this->actingAs($login)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => 'other',
                'other_name' => 'NULL user_id probe',
                'other_phone' => '9876543210',
            ])
            ->assertSessionHas('error');

        $after = $issue->fresh();
        $this->assertSame((int) $before->issue_status, (int) $after->issue_status, 'a login with no user_id changed the status');
        $this->assertSame($before->assigned_to, $after->assigned_to, 'a login with no user_id assigned the complaint');
        $this->assertSame($history, IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count());
    }

    public function test_login_with_null_user_id_gets_nothing_on_a_complaint_whose_identity_fields_are_all_null(): void
    {
        $login = $this->loginWithNullUserId();
        $issue = $this->issueWith([
            'created_by' => null,
            'issue_logger' => null,
            'employee_master_pk' => null,
            'assigned_to' => null,
        ]);

        $this->actingAs($login)
            ->get(route('admin.issue-management.show', $issue->pk))
            ->assertRedirect(route('admin.issue-management.index'));

        $this->actingAs($login)
            ->get(route('admin.issue-management.edit', $issue->pk))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');

        $this->actingAs($login)
            ->put(route('admin.issue-management.update', $issue->pk), [
                'issue_category_id' => $this->categoryPk,
                'issue_sub_category_id' => $this->subCategoryPk,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'nodal_employee_id' => $this->nodalA,
                'location' => 'O',
                'description' => 'Edited by a login with no user_id',
            ])
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');
        $this->assertSame('NULL user_id probe', $issue->fresh()->description);

        $this->assertStatusUpdateRefused($login, $issue);
    }

    public function test_login_with_null_user_id_is_not_the_assignee_of_an_unassigned_complaint(): void
    {
        // The common shape: raised, logged and routed, not yet assigned.
        $login = $this->loginWithNullUserId();
        $issue = $this->issueWith([
            'created_by' => $this->actor->user_id,
            'issue_logger' => $this->actor->user_id,
            'employee_master_pk' => $this->nodalA,
            'assigned_to' => null,
        ]);

        $this->actingAs($login)
            ->get(route('admin.issue-management.show', $issue->pk))
            ->assertRedirect(route('admin.issue-management.index'));

        $this->assertStatusUpdateRefused($login, $issue);
    }

    public function test_unrelated_user_gets_no_access_to_a_pk_old_keyed_complaint(): void
    {
        [$login, $employee] = $this->pkOldOfficer();
        $issue = $this->pkOldKeyedIssue($employee, IssueLogManagement::STATUS_IN_PROGRESS, (string) $this->nodalB);

        $outsiderIds = array_merge(
            getEmployeeIdsForUser($login->user_id),
            getEmployeeIdsForUser($this->actor->user_id),
            getEmployeeIdsForUser($this->nodalB)
        );
        $outsider = $this->nonAdminLogin(fn ($q) => $q
            ->where('user_category', 'E')
            ->whereNotIn('user_id', $outsiderIds)
            ->whereIn('user_id', fn ($s) => $s->select('pk')->from('employee_master'))
            ->whereNotIn('user_id', fn ($s) => $s->select('pk_old')->from('employee_master')->whereNotNull('pk_old')))
            ?? $this->skip('Needs an unrelated employee login.');

        $this->actingAs($outsider)
            ->get(route('admin.issue-management.show', $issue->pk))
            ->assertRedirect(route('admin.issue-management.index'));

        $this->actingAs($outsider)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_COMPLETED,
            ])
            ->assertSessionHas('error');
        $this->assertSame(IssueLogManagement::STATUS_IN_PROGRESS, (int) $issue->fresh()->issue_status);
    }
}
