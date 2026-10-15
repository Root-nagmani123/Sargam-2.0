<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The escalation matrix decides every complaint's nodal officer (see
 * IssueManagementController::resolveNodalOfficer()), and the nodal officer may
 * set any status. Its write routes were reachable by any signed-in user, who
 * could list themselves at Level 1 and so become nodal on their own complaints.
 * Only the Super Admin who sees the Escalation Matrix sidebar link may write it.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable — the same
 * convention as IssueNodalOfficerTamperingTest.
 */
class IssueEscalationMatrixAuthorizationTest extends TestCase
{
    private bool $inTransaction = false;

    private int $categoryPk;

    /** @var array<int, int> four employees: three on the matrix, one to swap in */
    private array $employees;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('IssueEscalationMatrixAuthorizationTest needs the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $category = DB::table('issue_category_master')->value('pk');
        $this->employees = DB::table('employee_master')->orderBy('pk')->limit(4)
            ->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        if (! $category || count($this->employees) < 4) {
            $this->markTestSkipped('Needs an issue category and four employees.');
        }
        $this->categoryPk = (int) $category;

        DB::table('issue_category_employee_map')->where('issue_category_master_pk', $this->categoryPk)->delete();
        foreach ([1, 2, 3] as $level) {
            DB::table('issue_category_employee_map')->insert([
                'issue_category_master_pk' => $this->categoryPk,
                'employee_master_pk' => $this->employees[$level - 1],
                'priority' => $level,
                'days_notify' => $level,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /** @param array<int, string> $roles */
    private function userWithRole(array $roles, bool $has): User
    {
        $roleHolders = fn ($q) => $q->select('model_id')
            ->from('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', $roles);

        $user = User::query()
            ->whereNotNull('user_id')
            ->{$has ? 'whereIn' : 'whereNotIn'}('pk', $roleHolders)
            ->first();
        if (! $user) {
            $this->markTestSkipped($has ? 'No Super Admin user.' : 'No non-admin user.');
        }

        return $user;
    }

    /** @return array<int, array<string, int>> */
    private function matrixRows(): array
    {
        return DB::table('issue_category_employee_map')
            ->where('issue_category_master_pk', $this->categoryPk)
            ->orderBy('priority')
            ->get(['employee_master_pk', 'priority', 'days_notify'])
            ->map(fn ($r) => array_map('intval', (array) $r))
            ->all();
    }

    /** Level 1 becomes $level1, Level 2 / 3 unchanged. */
    private function payload(int $level1): array
    {
        return [
            'issue_category_master_pk' => $this->categoryPk,
            'level1_employee_pk' => $level1,
            'level1_days' => 1,
            'level2_employee_pk' => $this->employees[1],
            'level2_days' => 2,
            'level3_employee_pk' => $this->employees[2],
            'level3_days' => 3,
        ];
    }

    public function test_non_admin_cannot_update_the_matrix(): void
    {
        $user = $this->userWithRole(['Super Admin', 'SuperAdmin', 'Admin'], false);
        $before = $this->matrixRows();

        // Lists the requester at Level 1 — the self-nomination this guards.
        $this->actingAs($user)
            ->put(route('admin.issue-escalation-matrix.update', $this->categoryPk), $this->payload((int) $user->user_id))
            ->assertForbidden();

        $this->assertSame($before, $this->matrixRows(), 'a non-admin rewrote the escalation matrix');
    }

    public function test_non_admin_cannot_create_a_matrix(): void
    {
        $user = $this->userWithRole(['Super Admin', 'SuperAdmin', 'Admin'], false);
        DB::table('issue_category_employee_map')->where('issue_category_master_pk', $this->categoryPk)->delete();

        $this->actingAs($user)
            ->post(route('admin.issue-escalation-matrix.store'), $this->payload((int) $user->user_id))
            ->assertForbidden();

        $this->assertSame([], $this->matrixRows(), 'a non-admin created an escalation matrix');
    }

    public function test_super_admin_can_still_update_the_matrix(): void
    {
        $admin = $this->userWithRole(['Super Admin', 'SuperAdmin'], true);

        $this->actingAs($admin)
            ->put(route('admin.issue-escalation-matrix.update', $this->categoryPk), $this->payload($this->employees[3]))
            ->assertRedirect(route('admin.issue-escalation-matrix.index'))
            ->assertSessionHas('success');

        $this->assertSame([
            ['employee_master_pk' => $this->employees[3], 'priority' => 1, 'days_notify' => 1],
            ['employee_master_pk' => $this->employees[1], 'priority' => 2, 'days_notify' => 2],
            ['employee_master_pk' => $this->employees[2], 'priority' => 3, 'days_notify' => 3],
        ], $this->matrixRows());
    }
}
