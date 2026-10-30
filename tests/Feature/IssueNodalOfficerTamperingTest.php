<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The nodal officer of a complaint is decided by the server, never by the
 * Create / Edit form.
 *
 * The nodal officer may set any status in status_update(); a complainant or
 * logger may only reopen a completed complaint. update() and store() used to
 * copy nodal_employee_id straight from the request, so a complainant could
 * name themselves nodal through Edit and then set any status they liked —
 * including ones the status lock had already used up. update() also rewrote
 * created_by from the request and accepted edits on a completed complaint,
 * which the page only hid.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable — the same
 * convention as CentcomAssignedVisibilityTest.
 */
class IssueNodalOfficerTamperingTest extends TestCase
{
    private bool $inTransaction = false;

    private User $actor;

    /** Level 1 nodal officers of the test category, neither of them the actor. */
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
            $this->markTestSkipped('IssueNodalOfficerTamperingTest needs the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $this->actor = $this->employeeActor();

        // Officers who can sign in: resolveNodalOfficer() passes over anyone
        // else, and the nodal officer acts in the last test. Having some
        // user_credentials row is not enough — a student or inactive login, or
        // a NULL-category one not keyed by the employee's own pk_old, does not
        // count (User::employeeCanSignIn()).
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

        // The matrix lists the actor at Level 1 too: picking themselves is then
        // a choice the dropdown could offer, and must still be refused.
        $this->setLevelOne($this->categoryPk, [$this->nodalA, $this->nodalB, (int) $this->actor->user_id]);
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /**
     * A non-admin login that can sign in. An actor who cannot sign in is
     * dropped by eligibleNodalOfficers()'s sign-in filter before the
     * complainant / logger exclusion is consulted, so the self-nomination
     * tests would pass without that exclusion.
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
            ->orderBy('pk')
            ->limit(50)
            ->get()
            ->first(fn (User $u) => User::employeeCanSignIn($u->user_id));

        if (! $user) {
            $this->markTestSkipped('No non-admin login mapped to employee_master that can sign in.');
        }

        $this->assertTrue(User::employeeCanSignIn($user->user_id), 'fixture: the actor cannot sign in');

        return $user;
    }

    /** @param array<int, int> $employeePks */
    private function setLevelOne(int $categoryPk, array $employeePks): void
    {
        $this->setLevel($categoryPk, 1, $employeePks);
    }

    /** @param array<int, int> $employeePks An empty list leaves the level unconfigured. */
    private function setLevel(int $categoryPk, int $priority, array $employeePks): void
    {
        DB::table('issue_category_employee_map')
            ->where('issue_category_master_pk', $categoryPk)
            ->where('priority', $priority)
            ->delete();

        foreach ($employeePks as $pk) {
            DB::table('issue_category_employee_map')->insert([
                'issue_category_master_pk' => $categoryPk,
                'employee_master_pk' => $pk,
                'priority' => $priority,
                'days_notify' => 1,
            ]);
        }
    }

    /** @param array<int, array<int, int>> $levels priority => employee pks, for Levels 1 to 3 */
    private function setMatrix(int $categoryPk, array $levels): void
    {
        foreach ([1, 2, 3] as $priority) {
            $this->setLevel($categoryPk, $priority, $levels[$priority] ?? []);
        }
    }

    /** @return array<string, mixed> */
    private function storePayload(string $description): array
    {
        return [
            'description' => $description,
            'issue_category_id' => $this->categoryPk,
            'issue_sub_category_id' => $this->subCategoryPk,
            'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
            'sub_category_name' => 'probe',
            'created_by' => $this->actor->user_id,
            'nodal_employee_id' => $this->actor->user_id,
            'location' => 'O',
            'building_master_pk' => $this->buildingPk(),
        ];
    }

    private function buildingPk(): int
    {
        $building = DB::table('building_master')->value('pk');
        if (! $building) {
            $this->markTestSkipped('Needs a building_master row.');
        }

        return (int) $building;
    }

    /** A complaint the actor raised and logged; nodalA is its nodal officer. */
    private function seedIssue(int $status = IssueLogManagement::STATUS_IN_PROGRESS): IssueLogManagement
    {
        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $this->categoryPk,
            'description' => 'Nodal tampering regression probe',
            'issue_status' => $status,
            'created_by' => $this->actor->user_id,
            'issue_logger' => $this->actor->user_id,
            'employee_master_pk' => $this->nodalA,
            'location' => 'O',
            'created_date' => now(),
        ]);

        // History matching the current status, as status_update() would leave it.
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now(),
            'created_by' => $this->nodalA,
            'issue_status' => $status,
        ]);

        return $issue;
    }

    /** @return array<string, mixed> */
    private function editPayload(array $overrides = []): array
    {
        return array_merge([
            'issue_category_id' => $this->categoryPk,
            'issue_sub_category_id' => $this->subCategoryPk,
            'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
            'created_by' => $this->actor->user_id,
            'nodal_employee_id' => $this->nodalA,
            'location' => 'O',
            'description' => 'Edited description',
        ], $overrides);
    }

    private function historyCount(IssueLogManagement $issue): int
    {
        return IssueLogStatus::where('issue_log_management_pk', $issue->pk)->count();
    }

    // ── A + B: self-nomination, then a status only the nodal officer may set ──

    public function test_complainant_cannot_make_themselves_nodal_and_then_set_a_used_status(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_IN_PROGRESS);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->actor->user_id,
            ]));

        $this->assertSame(
            (string) $this->nodalA,
            (string) $issue->fresh()->employee_master_pk,
            'the complainant made themselves the nodal officer through Edit'
        );

        // Pending was used earlier this cycle: locked for everyone but the
        // nodal officer. As complainant they may only reopen a completed one.
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now()->subDay(),
            'created_by' => $this->nodalA,
            'issue_status' => IssueLogManagement::STATUS_PENDING,
        ]);
        $before = $this->historyCount($issue);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_PENDING,
                'remark' => 'tamper',
            ])
            ->assertSessionHas('error');

        $this->assertSame(IssueLogManagement::STATUS_IN_PROGRESS, (int) $issue->fresh()->issue_status);
        $this->assertSame($before, $this->historyCount($issue), 'an unauthorised status-history row was written');
    }

    public function test_complainant_cannot_make_themselves_nodal_on_a_category_change(): void
    {
        $issue = $this->seedIssue();

        $otherCategory = DB::table('issue_sub_category_master')
            ->where('issue_category_master_pk', '!=', $this->categoryPk)
            ->select('pk', 'issue_category_master_pk')
            ->first();
        if (! $otherCategory) {
            $this->markTestSkipped('Needs a second category with a sub-category.');
        }
        $this->setLevelOne((int) $otherCategory->issue_category_master_pk, [(int) $this->actor->user_id, $this->nodalB]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'issue_category_id' => $otherCategory->issue_category_master_pk,
                'issue_sub_category_id' => $otherCategory->pk,
                'nodal_employee_id' => $this->actor->user_id,
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $issue->fresh();
        $this->assertSame((int) $otherCategory->issue_category_master_pk, (int) $fresh->issue_category_master_pk);
        $this->assertSame((string) $this->nodalB, (string) $fresh->employee_master_pk);
    }

    public function test_store_does_not_let_the_logger_name_themselves_nodal(): void
    {
        $building = DB::table('building_master')->value('pk');
        if (! $building) {
            $this->markTestSkipped('Needs a building_master row.');
        }

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), [
                'description' => 'Store tamper probe '.uniqid(),
                'issue_category_id' => $this->categoryPk,
                'issue_sub_category_id' => $this->subCategoryPk,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'sub_category_name' => 'probe',
                'created_by' => $this->actor->user_id,
                'nodal_employee_id' => $this->actor->user_id,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('created_by', $this->actor->user_id)->orderByDesc('pk')->first();
        $this->assertNotNull($stored);
        $this->assertSame((string) $this->nodalA, (string) $stored->employee_master_pk);
        $this->assertSame((string) $this->actor->user_id, (string) $stored->issue_logger);
    }

    public function test_store_ignores_a_nodal_officer_outside_the_category_matrix(): void
    {
        $building = DB::table('building_master')->value('pk');
        $outsider = DB::table('employee_master')
            ->whereNotIn('pk', [$this->nodalA, $this->nodalB, $this->actor->user_id])
            ->value('pk');
        if (! $building || ! $outsider) {
            $this->markTestSkipped('Needs a building_master row and a fourth employee.');
        }

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), [
                'description' => 'Store outsider probe',
                'issue_category_id' => $this->categoryPk,
                'issue_sub_category_id' => $this->subCategoryPk,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'sub_category_name' => 'probe',
                'created_by' => $this->actor->user_id,
                'nodal_employee_id' => $outsider,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('created_by', $this->actor->user_id)->orderByDesc('pk')->first();
        $this->assertSame((string) $this->nodalA, (string) $stored->employee_master_pk);
    }

    // ── The complainant / logger is the category's only Level 1 officer ──────
    //
    // resolveNodalOfficer() used to fall back to the first Level 1 officer when
    // every one was excluded, which then was the complainant or logger.

    /** Pending was used earlier this cycle; only the nodal officer may set it now. */
    private function assertUsedStatusRefused(IssueLogManagement $issue): void
    {
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now()->subDay(),
            'created_by' => $this->nodalA,
            'issue_status' => IssueLogManagement::STATUS_PENDING,
        ]);
        $before = $this->historyCount($issue);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_PENDING,
                'remark' => 'tamper',
            ])
            ->assertSessionHas('error');

        $this->assertSame(IssueLogManagement::STATUS_IN_PROGRESS, (int) $issue->fresh()->issue_status);
        $this->assertSame($before, $this->historyCount($issue), 'an unauthorised status-history row was written');
    }

    private function otherCategory(): object
    {
        $otherCategory = DB::table('issue_sub_category_master')
            ->where('issue_category_master_pk', '!=', $this->categoryPk)
            ->select('pk', 'issue_category_master_pk')
            ->first();
        if (! $otherCategory) {
            $this->markTestSkipped('Needs a second category with a sub-category.');
        }

        return $otherCategory;
    }

    public function test_category_change_is_refused_when_the_complainant_is_its_only_officer_at_every_level(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_IN_PROGRESS);
        $otherCategory = $this->otherCategory();
        $this->setMatrix((int) $otherCategory->issue_category_master_pk, [1 => [(int) $this->actor->user_id]]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'issue_category_id' => $otherCategory->issue_category_master_pk,
                'issue_sub_category_id' => $otherCategory->pk,
                'nodal_employee_id' => $this->actor->user_id,
            ]))
            ->assertSessionHasErrors('nodal_employee_id');

        $fresh = $issue->fresh();
        $this->assertSame((string) $this->nodalA, (string) $fresh->employee_master_pk, 'the complainant became the nodal officer');
        $this->assertSame($this->categoryPk, (int) $fresh->issue_category_master_pk);
        $this->assertSame('Nodal tampering regression probe', $fresh->description);

        $this->assertUsedStatusRefused($issue);
    }

    public function test_category_change_falls_back_to_level_two_when_the_complainant_is_the_only_level_one_officer(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_IN_PROGRESS);
        $otherCategory = $this->otherCategory();
        $this->setMatrix((int) $otherCategory->issue_category_master_pk, [
            1 => [(int) $this->actor->user_id],
            2 => [$this->nodalB],
        ]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'issue_category_id' => $otherCategory->issue_category_master_pk,
                'issue_sub_category_id' => $otherCategory->pk,
                'nodal_employee_id' => $this->actor->user_id,
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $issue->fresh();
        $this->assertSame((int) $otherCategory->issue_category_master_pk, (int) $fresh->issue_category_master_pk);
        $this->assertSame((string) $this->nodalB, (string) $fresh->employee_master_pk);
    }

    public function test_same_category_keeps_the_nodal_officer_when_the_complainant_is_the_only_level_one_officer(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_IN_PROGRESS);
        $this->setLevelOne($this->categoryPk, [(int) $this->actor->user_id]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->actor->user_id,
            ]));

        $this->assertSame(
            (string) $this->nodalA,
            (string) $issue->fresh()->employee_master_pk,
            'the complainant became the nodal officer'
        );

        $this->assertUsedStatusRefused($issue);
    }

    // ── store() never saves a complaint nobody can act on ─────────────────────

    public function test_store_assigns_level_two_when_the_logger_is_the_only_level_one_officer_and_level_two_can_act(): void
    {
        $this->setMatrix($this->categoryPk, [
            1 => [(int) $this->actor->user_id],
            2 => [$this->nodalB],
            3 => [$this->nodalA],
        ]);
        $description = 'Store sole-officer probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $this->nodalB, (string) $stored->employee_master_pk);

        $levelTwo = User::where('user_id', $this->nodalB)->first();
        if (! $levelTwo) {
            $this->markTestSkipped('The Level 2 employee has no login.');
        }
        $before = $this->historyCount($stored);

        $this->actingAs($levelTwo)
            ->put(route('admin.issue-management.status_update', $stored->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'assign_to_type' => 'other',
                'other_name' => 'Level 2 probe handler',
                'other_phone' => '9876543210',
            ])
            ->assertSessionHas('success');

        $this->assertSame(IssueLogManagement::STATUS_IN_PROGRESS, (int) $stored->fresh()->issue_status);
        $this->assertSame('Level 2 probe handler', $stored->fresh()->assigned_to);
        $this->assertSame($before + 1, $this->historyCount($stored));
    }

    public function test_store_assigns_level_three_when_level_two_is_the_logger_too(): void
    {
        $this->setMatrix($this->categoryPk, [
            1 => [(int) $this->actor->user_id],
            2 => [(int) $this->actor->user_id],
            3 => [$this->nodalA],
        ]);
        $description = 'Store level-three probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $this->nodalA, (string) $stored->employee_master_pk);
    }

    public function test_store_assigns_level_two_when_the_category_has_no_level_one(): void
    {
        $this->setMatrix($this->categoryPk, [2 => [$this->nodalB]]);
        $description = 'Store no-level-one probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), array_merge($this->storePayload($description), ['nodal_employee_id' => null]))
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $this->nodalB, (string) $stored->employee_master_pk);
    }

    public function test_store_is_refused_when_the_logger_is_the_only_officer_at_every_level(): void
    {
        $this->setMatrix($this->categoryPk, [1 => [(int) $this->actor->user_id]]);
        $description = 'Store refused probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasErrors('nodal_employee_id');

        $this->assertSame(0, IssueLogManagement::where('description', $description)->count(), 'a complaint nobody can act on was stored');
    }

    public function test_store_is_refused_when_the_category_has_no_escalation_matrix(): void
    {
        $this->setMatrix($this->categoryPk, []);
        $description = 'Store no-matrix probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasErrors('nodal_employee_id');

        $this->assertSame(0, IssueLogManagement::where('description', $description)->count(), 'a complaint nobody can act on was stored');
    }

    // ── store() never makes nodal an officer who cannot sign in ───────────────
    //
    // resolveNodalOfficer() used to accept any officer the matrix listed, so a
    // Level 2 officer with no usable login became nodal ahead of a Level 3
    // officer who could act — and nobody could ever change the status.

    /** An employee with no login at all, other than the fixture's officers. */
    private function officerWithNoLogin(array $except = []): int
    {
        $pk = DB::table('employee_master')
            ->whereNotIn('pk', array_merge([$this->nodalA, $this->nodalB, (int) $this->actor->user_id], $except))
            ->whereNotIn('pk', fn ($q) => $q->select('user_id')->from('user_credentials')->whereNotNull('user_id'))
            ->orderBy('pk')
            ->limit(50)
            ->pluck('pk')
            ->first(fn ($pk) => ! User::employeeCanSignIn($pk));
        if ($pk === null) {
            $this->markTestSkipped('Needs an employee without a login.');
        }

        $this->assertFalse(User::employeeCanSignIn($pk), 'fixture: the no-login officer can sign in');

        return (int) $pk;
    }

    /**
     * An employee whose own 'E' login LoginController would refuse once they
     * are inactive (status 2) — set here, inside the rolled-back transaction.
     */
    private function deactivatedOfficer(): int
    {
        $pk = DB::table('employee_master as e')
            ->join('user_credentials as uc', 'uc.user_id', '=', 'e.pk')
            ->where('uc.user_category', 'E')
            ->whereNotIn('e.pk', [$this->nodalA, $this->nodalB, (int) $this->actor->user_id])
            ->orderBy('e.pk')
            ->value('e.pk');
        if ($pk === null) {
            $this->markTestSkipped('Needs a third employee with an employee login.');
        }

        DB::table('employee_master')->where('pk', $pk)->update(['status' => 2]);
        $this->assertFalse(User::employeeCanSignIn($pk), 'fixture: the deactivated officer can still sign in');

        return (int) $pk;
    }

    private function assertStoredWithNodal(string $description, int $expected): void
    {
        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $expected, (string) $stored->employee_master_pk);
    }

    public function test_store_skips_a_level_two_officer_who_cannot_sign_in_and_assigns_level_three(): void
    {
        $levelTwo = $this->officerWithNoLogin();
        $this->assertTrue(User::employeeCanSignIn($this->nodalA), 'fixture: the Level 3 officer cannot sign in');
        $this->setMatrix($this->categoryPk, [
            1 => [(int) $this->actor->user_id],
            2 => [$levelTwo],
            3 => [$this->nodalA],
        ]);
        $description = 'Store no-login level-two probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasNoErrors();

        $this->assertStoredWithNodal($description, $this->nodalA);
    }

    public function test_store_skips_a_deactivated_level_two_officer_and_assigns_level_three(): void
    {
        $levelTwo = $this->deactivatedOfficer();
        $this->setMatrix($this->categoryPk, [
            1 => [(int) $this->actor->user_id],
            2 => [$levelTwo],
            3 => [$this->nodalA],
        ]);
        $description = 'Store deactivated level-two probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasNoErrors();

        $this->assertStoredWithNodal($description, $this->nodalA);
    }

    public function test_store_is_refused_when_no_fallback_officer_can_sign_in(): void
    {
        $levelTwo = $this->officerWithNoLogin();
        $levelThree = $this->deactivatedOfficer();
        $this->setMatrix($this->categoryPk, [
            1 => [(int) $this->actor->user_id],
            2 => [$levelTwo],
            3 => [$levelThree],
        ]);
        $description = 'Store nobody-can-sign-in probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), $this->storePayload($description))
            ->assertSessionHasErrors('nodal_employee_id');

        $this->assertSame(0, IssueLogManagement::where('description', $description)->count(), 'a complaint nobody can act on was stored');
    }

    public function test_store_does_not_honour_a_requested_level_one_officer_who_cannot_sign_in(): void
    {
        $requested = $this->officerWithNoLogin();
        $this->setMatrix($this->categoryPk, [1 => [$requested, $this->nodalB]]);
        $description = 'Store no-login requested probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), array_merge($this->storePayload($description), ['nodal_employee_id' => $requested]))
            ->assertSessionHasNoErrors();

        $this->assertStoredWithNodal($description, $this->nodalB);
    }

    public function test_store_skips_a_level_one_officer_who_cannot_sign_in_and_assigns_level_two(): void
    {
        $levelOne = $this->officerWithNoLogin();
        $this->setMatrix($this->categoryPk, [1 => [$levelOne], 2 => [$this->nodalB]]);
        $description = 'Store no-login level-one probe '.uniqid();

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), array_merge($this->storePayload($description), ['nodal_employee_id' => null]))
            ->assertSessionHasNoErrors();

        $this->assertStoredWithNodal($description, $this->nodalB);
    }

    // ── C: completed complaints ───────────────────────────────────────────────

    public function test_update_refuses_a_completed_complaint(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_COMPLETED);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->actor->user_id,
            ]))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');

        $fresh = $issue->fresh();
        $this->assertSame('Nodal tampering regression probe', $fresh->description);
        $this->assertSame((string) $this->nodalA, (string) $fresh->employee_master_pk);
    }

    public function test_edit_page_refuses_a_completed_complaint(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_COMPLETED);

        $this->actingAs($this->actor)
            ->get(route('admin.issue-management.edit', $issue->pk))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('error');
    }

    // ── created_by ────────────────────────────────────────────────────────────

    public function test_update_never_changes_the_complainant(): void
    {
        $issue = $this->seedIssue();

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'created_by' => $this->nodalB,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame((string) $this->actor->user_id, (string) $issue->fresh()->created_by);
    }

    // ── D: legitimate behaviour still works ───────────────────────────────────

    public function test_update_still_saves_edits_and_accepts_another_level_one_officer(): void
    {
        $issue = $this->seedIssue();

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->nodalB,
            ]))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('success');

        $fresh = $issue->fresh();
        $this->assertSame('Edited description', $fresh->description);
        $this->assertSame((string) $this->nodalB, (string) $fresh->employee_master_pk);
    }

    public function test_update_keeps_a_nodal_officer_the_matrix_no_longer_lists(): void
    {
        $issue = $this->seedIssue();
        $this->setLevelOne($this->categoryPk, [$this->nodalB]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->nodalA,
            ]))
            ->assertSessionHas('success');

        $this->assertSame((string) $this->nodalA, (string) $issue->fresh()->employee_master_pk);
    }

    // ── F-002: the Level 2/3 fallback the edit form offers is what update() saves ──

    public function test_update_saves_the_level_two_fallback_the_edit_form_offers(): void
    {
        $issue = $this->seedIssue();
        $this->setMatrix($this->categoryPk, [1 => [$this->officerWithNoLogin()], 2 => [$this->nodalB]]);

        $offer = $this->actingAs($this->actor)
            ->getJson(route('admin.issue-management.nodal-employees', $this->categoryPk)
                .'?'.http_build_query(['complainant' => $this->actor->user_id, 'logger' => $this->actor->user_id]))
            ->assertOk()
            ->json();
        $this->assertSame([(string) $this->nodalB], array_map('strval', array_column($offer['level1'], 'employee_pk')), 'the form does not offer the Level 2 officer');
        $this->assertSame((string) $this->nodalB, (string) $offer['level1_auto_select']);
        $this->assertSame(2, $offer['assigned_level']);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $offer['level1_auto_select'],
            ]))
            ->assertRedirect(route('admin.issue-management.show', $issue->pk))
            ->assertSessionHas('success', 'Issue updated successfully.');

        $this->assertSame((string) $this->nodalB, (string) $issue->fresh()->employee_master_pk, 'update() discarded the officer the form offered');
    }

    public function test_update_says_so_when_it_does_not_save_the_officer_sent(): void
    {
        $issue = $this->seedIssue();
        $levelOne = $this->officerWithNoLogin();
        $this->setMatrix($this->categoryPk, [1 => [$levelOne], 2 => [$this->nodalB]]);

        // Never offered (cannot sign in): a stale or tampered pick.
        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $levelOne,
            ]))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'is not on offer for this complaint')
                && str_contains($message, 'was kept') && ! str_contains($message, 'assigned to'));

        $this->assertSame((string) $this->nodalA, (string) $issue->fresh()->employee_master_pk);
    }

    // ── F-007: keeping the stored officer is not reported as a reassignment ──

    public function test_update_says_the_current_officer_was_kept_when_an_unused_level_is_picked(): void
    {
        $issue = $this->seedIssue();
        $stored = $this->officerWithNoLogin();
        $issue->update(['employee_master_pk' => $stored]);
        $this->setMatrix($this->categoryPk, [1 => [$this->nodalA], 2 => [$this->nodalB]]);

        // nodalB can sign in, but Level 2 is not on offer while Level 1 is eligible.
        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->nodalB,
            ]))
            ->assertSessionHas('success', function ($message) {
                return str_contains($message, 'is not on offer for this complaint')
                    && str_contains($message, 'was kept')
                    && ! str_contains($message, 'current')
                    && ! str_contains($message, 'assigned to')
                    && ! str_contains($message, 'cannot sign in');
            });

        $this->assertSame((string) $stored, (string) $issue->fresh()->employee_master_pk);
    }

    // ── A Level 2 officer the form never offered is not saved ────────────────

    public function test_store_and_update_ignore_a_level_two_officer_the_form_did_not_offer(): void
    {
        $this->setMatrix($this->categoryPk, [1 => [$this->officerWithNoLogin()], 2 => [$this->nodalA, $this->nodalB]]);
        $offer = $this->actingAs($this->actor)
            ->getJson(route('admin.issue-management.nodal-employees', $this->categoryPk)
                .'?'.http_build_query(['complainant' => $this->actor->user_id]))
            ->assertOk()
            ->json();
        $offered = (string) $offer['level1_auto_select'];
        $this->assertSame([$offered], array_map('strval', array_column($offer['level1'], 'employee_pk')), 'the form offers more than one Level 2 officer');
        $notOffered = $offered === (string) $this->nodalA ? $this->nodalB : $this->nodalA;

        $description = 'Store unoffered Level 2 probe '.uniqid();
        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), array_merge($this->storePayload($description), ['nodal_employee_id' => $notOffered]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'so it was assigned to'));
        $this->assertStoredWithNodal($description, (int) $offered);

        $issue = $this->seedIssue();
        $stored = $this->officerWithNoLogin();
        $issue->update(['employee_master_pk' => $stored]);
        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload(['nodal_employee_id' => $notOffered]))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'was kept'));
        $this->assertSame((string) $stored, (string) $issue->fresh()->employee_master_pk);
    }

    // ── F-009: a complaint with no nodal officer has none to keep ────────────

    public function test_update_gives_a_complaint_with_no_nodal_officer_the_matrix_officer_and_says_so(): void
    {
        $issue = $this->seedIssue();
        $issue->update(['employee_master_pk' => null]);
        $this->setMatrix($this->categoryPk, [1 => [$this->nodalA], 2 => [$this->nodalB]]);

        // nodalB is not on offer while Level 1 is eligible: a stale or crafted pick.
        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $this->nodalB,
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'is not on offer for this complaint, so it was assigned to')
                && ! str_contains($message, 'was kept'));

        $fresh = $issue->fresh();
        $this->assertSame((string) $this->nodalA, (string) $fresh->employee_master_pk, 'the complaint was left with no nodal officer');
        $this->assertSame('Edited description', $fresh->description);
    }

    public function test_update_saves_the_officer_the_form_preselects_on_a_complaint_with_no_nodal_officer(): void
    {
        $issue = $this->seedIssue();
        $issue->update(['employee_master_pk' => null]);
        $this->setMatrix($this->categoryPk, [1 => [$this->officerWithNoLogin()], 2 => [$this->deactivatedOfficer()], 3 => [$this->nodalB]]);

        $offer = $this->actingAs($this->actor)
            ->getJson(route('admin.issue-management.nodal-employees', $this->categoryPk)
                .'?'.http_build_query(['complainant' => $this->actor->user_id, 'logger' => $this->actor->user_id]))
            ->assertOk()
            ->json();
        $this->assertSame(3, $offer['assigned_level']);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $offer['level1_auto_select'],
            ]))
            ->assertSessionHas('success', 'Issue updated successfully.');

        $this->assertSame((string) $this->nodalB, (string) $issue->fresh()->employee_master_pk, 'the Level 3 officer the form showed was not saved');
    }

    public function test_update_of_a_complaint_with_no_nodal_officer_is_refused_when_no_level_has_one(): void
    {
        $issue = $this->seedIssue();
        $issue->update(['employee_master_pk' => null]);
        $levelOne = $this->officerWithNoLogin();
        $this->setMatrix($this->categoryPk, [1 => [$levelOne]]);

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), $this->editPayload([
                'nodal_employee_id' => $levelOne,
            ]))
            ->assertSessionHasErrors('nodal_employee_id')
            ->assertSessionMissing('success');

        $fresh = $issue->fresh();
        $this->assertNull($fresh->employee_master_pk, 'an officer who cannot sign in was assigned');
        $this->assertSame('Nodal tampering regression probe', $fresh->description);
    }

    public function test_store_accepts_a_level_one_officer_the_complainant_picked(): void
    {
        $building = DB::table('building_master')->value('pk');
        if (! $building) {
            $this->markTestSkipped('Needs a building_master row.');
        }

        $this->actingAs($this->actor)
            ->post(route('admin.issue-management.store'), [
                'description' => 'Store legit probe',
                'issue_category_id' => $this->categoryPk,
                'issue_sub_category_id' => $this->subCategoryPk,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'sub_category_name' => 'probe',
                'created_by' => $this->actor->user_id,
                'nodal_employee_id' => $this->nodalB,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $stored = IssueLogManagement::where('created_by', $this->actor->user_id)->orderByDesc('pk')->first();
        $this->assertSame((string) $this->nodalB, (string) $stored->employee_master_pk);
        $this->assertSame(0, (int) $stored->issue_status);
    }

    public function test_the_nodal_officer_can_still_change_the_status(): void
    {
        $issue = $this->seedIssue(IssueLogManagement::STATUS_IN_PROGRESS);
        $issue->update(['assigned_to' => (string) $this->nodalB]);
        $nodal = User::where('user_id', $this->nodalA)->first();
        if (! $nodal) {
            $this->markTestSkipped('The Level 1 employee has no login.');
        }

        $before = $this->historyCount($issue);

        $this->actingAs($nodal)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_PENDING,
            ])
            ->assertSessionHas('success');

        $this->assertSame(IssueLogManagement::STATUS_PENDING, (int) $issue->fresh()->issue_status);
        $this->assertSame($before + 1, $this->historyCount($issue));
    }
}
