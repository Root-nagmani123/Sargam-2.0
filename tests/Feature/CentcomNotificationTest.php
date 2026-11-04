<?php

namespace Tests\Feature;

use App\Models\IssueCategoryMaster;
use App\Models\IssueLogManagement;
use App\Models\IssueLogStatus;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CentCom / Issue Management sends in-app (bell) notifications end to end:
 * the assignee hears a complaint is theirs, the complainant hears its status
 * moved, the nodal officer and assignee hear it was reopened, the actor never
 * hears about their own action, and the bell click opens the complaint.
 *
 * Like the other Centcom tests this runs against the real configured database
 * inside a transaction that is always rolled back, and skips — never fails —
 * when the database or a usable fixture is missing.
 */
class CentcomNotificationTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('CentcomNotificationTest needs the application database');
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

    /**
     * Three distinct non-admin employee logins whose user_id is their own
     * employee pk and whom loginUserIdsForEmployee() addresses: complainant,
     * nodal officer and assignee.
     *
     * @return array<int, User>
     */
    private function threeEmployeeLogins(): array
    {
        $candidates = User::query()
            ->where('user_category', 'E')
            ->whereIn('user_id', fn ($q) => $q->select('pk')->from('employee_master')
                ->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 2)))
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']))
            ->orderBy('pk')
            ->limit(40)
            ->get();

        $picked = [];
        foreach ($candidates as $user) {
            if (isset($picked[(int) $user->user_id])) {
                continue;
            }
            if (User::complaintEmployeePkForLogin($user->pk) !== (string) $user->user_id) {
                continue;
            }
            if (! in_array((int) $user->user_id, User::loginUserIdsForEmployee($user->user_id), true)) {
                continue;
            }
            $picked[(int) $user->user_id] = $user;
            if (count($picked) === 3) {
                return array_values($picked);
            }
        }

        $this->markTestSkipped('Needs three active non-admin employee logins.');
    }

    private function seedIssue(User $complainant, User $nodal, int $status, ?User $assignee = null): IssueLogManagement
    {
        $category = IssueCategoryMaster::query()->first();
        if (! $category) {
            $this->markTestSkipped('No issue_category_master row to hang a test issue off.');
        }

        $issue = IssueLogManagement::create([
            'issue_category_master_pk' => $category->pk,
            'description' => 'Centcom notification probe',
            'issue_status' => $status,
            'created_by' => $complainant->user_id,
            'issue_logger' => $complainant->user_id,
            'employee_master_pk' => $nodal->user_id,
            'assigned_to' => $assignee ? (string) $assignee->user_id : null,
            'location' => 'O',
            'created_date' => now(),
        ]);
        IssueLogStatus::create([
            'issue_log_management_pk' => $issue->pk,
            'issue_date' => now(),
            'created_by' => $complainant->user_id,
            'issue_status' => $status,
        ]);

        return $issue;
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function notificationsFor(IssueLogManagement $issue, User $receiver)
    {
        return DB::table('notifications')
            ->where('type', 'issue_management')
            ->where('module_name', 'IssueManagement')
            ->where('reference_pk', $issue->pk)
            ->where('receiver_user_id', $receiver->user_id)
            ->get();
    }

    public function test_assigning_tells_the_assignee_and_the_complainant_but_not_the_nodal_officer(): void
    {
        [$complainant, $nodal, $assignee] = $this->threeEmployeeLogins();
        $issue = $this->seedIssue($complainant, $nodal, IssueLogManagement::STATUS_REPORTED);

        $this->actingAs($nodal)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
                'remark' => 'Electrician on the way',
                'assign_to_type' => 'employee',
                'assigned_to' => (string) $assignee->user_id,
            ])
            ->assertSessionHas('success');

        $toAssignee = $this->notificationsFor($issue, $assignee);
        $this->assertCount(1, $toAssignee, 'the assignee should get exactly one notification');
        $this->assertSame('Complaint assigned to you', $toAssignee->first()->title);

        $toComplainant = $this->notificationsFor($issue, $complainant);
        $this->assertCount(1, $toComplainant, 'the complainant was not told the status moved');
        $this->assertStringContainsString('In Progress', $toComplainant->first()->message);
        $this->assertStringContainsString('Electrician on the way', $toComplainant->first()->message);

        $this->assertCount(0, $this->notificationsFor($issue, $nodal), 'the nodal officer was notified of their own action');
    }

    public function test_reopening_tells_the_nodal_officer_and_assignee(): void
    {
        [$complainant, $nodal, $assignee] = $this->threeEmployeeLogins();
        $issue = $this->seedIssue($complainant, $nodal, IssueLogManagement::STATUS_COMPLETED, $assignee);

        $this->actingAs($complainant)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_REOPENED,
                'remark' => 'Still leaking',
            ])
            ->assertSessionHas('success');

        foreach (['nodal officer' => $nodal, 'assignee' => $assignee] as $role => $user) {
            $rows = $this->notificationsFor($issue, $user);
            $this->assertCount(1, $rows, "the {$role} was not told the complaint was reopened");
            $this->assertStringContainsString('reopened', $rows->first()->message);
        }
        $this->assertCount(0, $this->notificationsFor($issue, $complainant), 'the complainant was notified of their own reopen');
    }

    public function test_the_bell_click_opens_the_complaint(): void
    {
        [$complainant, $nodal, $assignee] = $this->threeEmployeeLogins();
        $issue = $this->seedIssue($complainant, $nodal, IssueLogManagement::STATUS_IN_PROGRESS, $assignee);

        $this->actingAs($nodal)
            ->put(route('admin.issue-management.status_update', $issue->pk), [
                'issue_status' => IssueLogManagement::STATUS_COMPLETED,
            ])
            ->assertSessionHas('success');

        $row = $this->notificationsFor($issue, $complainant)->first();
        $this->assertNotNull($row, 'the complainant was not told the complaint was completed');
        $this->assertSame(
            route('admin.issue-management.show', ['id' => $issue->pk]),
            app(NotificationService::class)->getRedirectUrl((int) $row->pk)
        );
    }
}
