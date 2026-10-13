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

        // Officers with a login, so the nodal officer can act in the last test.
        $others = DB::table('employee_master')
            ->where('pk', '!=', $this->actor->user_id)
            ->whereIn('pk', fn ($q) => $q->select('user_id')->from('user_credentials')->whereNotNull('user_id'))
            ->orderBy('pk')
            ->limit(2)
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
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

    /** @param array<int, int> $employeePks */
    private function setLevelOne(int $categoryPk, array $employeePks): void
    {
        DB::table('issue_category_employee_map')
            ->where('issue_category_master_pk', $categoryPk)
            ->where('priority', 1)
            ->delete();

        foreach ($employeePks as $pk) {
            DB::table('issue_category_employee_map')->insert([
                'issue_category_master_pk' => $categoryPk,
                'employee_master_pk' => $pk,
                'priority' => 1,
                'days_notify' => 1,
            ]);
        }
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
                'description' => 'Store tamper probe ' . uniqid(),
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
