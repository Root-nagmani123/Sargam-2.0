<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A login reached through an employee's pk_old is only that employee's when
 * the key names them alone (PR #337 F-021).
 *
 * The collision: employee A has pk = X, employee B has pk_old = X, and a login
 * has user_id = X. Once signed in, getEmployeeIdsForUser('X') resolves the
 * login to A or B, whichever row MySQL returns first. Resolved to A, its ids
 * are [X, A.pk_old], which do not contain B.pk, so a complaint stored under B
 * reaches a login that cannot act on it. B must therefore not count as able
 * to sign in through that login, and the resolver must not route to B.
 *
 * X is A's real pk, as the database assigns it, and B's pk_old is set to
 * that same value: the collision is a real one, not a key chosen to avoid it.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable — the same
 * convention as IssueNodalNullCategoryPkOldIdentityTest.
 */
class IssueNodalLoginKeyCollisionTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('IssueNodalLoginKeyCollisionTest needs the application database');
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

    /** A fresh active employee, inside the rolled-back transaction. */
    private function employee(?int $pkOld = null): int
    {
        return (int) DB::table('employee_master')->insertGetId([
            'pk_old' => $pkOld,
            'first_name' => 'F021',
            'last_name' => 'Probe',
            'status' => 1,
        ]);
    }

    private function login(int $userId, ?string $category): int
    {
        return (int) DB::table('user_credentials')->insertGetId([
            'user_name' => 'f021-probe-' . uniqid(),
            'user_id' => $userId,
            'user_category' => $category,
        ]);
    }

    /** A key that is nobody's pk, pk_old or login user_id. */
    private function unusedKey(): int
    {
        return (int) max(
            DB::table('employee_master')->max('pk'),
            DB::table('employee_master')->max('pk_old'),
            DB::table('user_credentials')->max('user_id'),
        ) + 100000 + random_int(0, 99999);
    }

    private function canLoginInDropdown(int $employeePk): ?int
    {
        $row = User::getEmployeesAndFacultyForComplaint()->firstWhere('employee_pk', $employeePk);

        return $row === null ? null : (int) $row->can_login;
    }

    /** @return array{0:int, 1:int, 2:int} A.pk (= X), B.pk, and the login id */
    private function nullCategoryCollision(): array
    {
        $a = $this->employee();
        $x = $a;
        $b = $this->employee($x);
        $login = $this->login($x, null);

        $this->assertSame((string) $x, (string) DB::table('employee_master')->where('pk', $b)->value('pk_old'), 'fixture: B.pk_old is not A.pk');
        $this->assertTrue(DB::table('employee_master')->where('pk', $x)->exists(), 'fixture: X is not an employee pk');

        return [$a, $b, $login];
    }

    public function test_a_null_category_login_on_a_shared_key_counts_for_neither_employee(): void
    {
        [$a, $b] = $this->nullCategoryCollision();

        $this->assertFalse(User::employeeCanSignIn($b), 'B counts as able to sign in through a login that may resolve to A');
        $this->assertFalse(User::employeeCanSignIn($a), 'A counts as able to sign in through a NULL-category login on their pk');
        $this->assertNotSame(1, $this->canLoginInDropdown($b), 'the dropdown offers B as loginable');
        $this->assertNotSame(1, $this->canLoginInDropdown($a), 'the dropdown offers A as loginable');
    }

    public function test_store_does_not_route_to_the_pk_old_officer_of_an_ambiguous_login(): void
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

        [, $b] = $this->nullCategoryCollision();

        // Level 2: an officer who plainly can sign in, with an E login on their own pk.
        $c = $this->employee();
        $this->login($c, 'E');
        $this->assertTrue(User::employeeCanSignIn($c), 'fixture: the Level 2 officer cannot sign in');

        $category = (int) $sub->issue_category_master_pk;
        DB::table('issue_category_employee_map')->where('issue_category_master_pk', $category)->delete();
        foreach ([1 => $b, 2 => $c] as $level => $pk) {
            DB::table('issue_category_employee_map')->insert([
                'issue_category_master_pk' => $category,
                'employee_master_pk' => $pk,
                'priority' => $level,
                'days_notify' => 1,
            ]);
        }

        $offered = $this->actingAs($actor)
            ->getJson('/admin/issue-management/nodal-employees/' . $category)
            ->assertOk()
            ->json();
        $this->assertNotContains((string) $b, array_map(fn ($r) => (string) $r['employee_pk'], $offered['level1'] ?? []), 'the form offers B');
        $this->assertSame((string) $c, (string) ($offered['level1_auto_select'] ?? ''), 'the form does not preselect the Level 2 officer');

        $description = 'F-021 collision probe ' . uniqid();
        $this->actingAs($actor)
            ->post(route('admin.issue-management.store'), [
                'description' => $description,
                'issue_category_id' => $category,
                'issue_sub_category_id' => $sub->pk,
                'issue_priority_id' => $priority,
                'sub_category_name' => 'probe',
                'created_by' => $actor->user_id,
                'nodal_employee_id' => $b,
                'location' => 'O',
                'building_master_pk' => $building,
            ])
            ->assertSessionHasNoErrors();

        $stored = IssueLogManagement::where('description', $description)->first();
        $this->assertNotNull($stored, 'no complaint was stored');
        $this->assertSame((string) $c, (string) $stored->employee_master_pk, 'the complaint went to B, whom the ambiguous login may not act as');
    }

    public function test_an_employee_login_on_a_shared_key_counts_for_its_own_employee_only(): void
    {
        $a = $this->employee();
        $b = $this->employee($a);
        $this->login($a, 'E');

        // getEmployeeIdsForUser() may resolve this login to B, but B's ids
        // still contain A.pk (B.pk_old = A.pk), so A can always act on a
        // complaint stored under A.pk. B.pk is in neither resolution's ids
        // only when it resolves to A, so B does not count.
        $this->assertTrue(User::employeeCanSignIn($a), 'A lost their own E login');
        $this->assertFalse(User::employeeCanSignIn($b), 'B counts as able to sign in through A\'s login');
    }

    public function test_a_null_category_login_on_a_duplicated_pk_old_counts_for_neither_employee(): void
    {
        // Y is nobody's pk, so this is the pure duplicated-pk_old shape.
        $y = $this->unusedKey();
        $b = $this->employee($y);
        $c = $this->employee($y);
        $this->login($y, null);

        $this->assertFalse(User::employeeCanSignIn($b), 'B counts through a pk_old shared with C');
        $this->assertFalse(User::employeeCanSignIn($c), 'C counts through a pk_old shared with B');
        $this->assertFalse(User::employeeCanSignIn($y), 'a pk_old two employees share resolved to one of them');
        $this->assertNull(User::resolveEmployeePk($y));
    }

    public function test_an_unshared_pk_old_login_still_counts(): void
    {
        // The PR337-14 case stays valid when nothing else uses the key.
        $y = $this->unusedKey();
        $b = $this->employee($y);
        $this->login($y, null);

        $this->assertTrue(User::employeeCanSignIn($b), 'by pk');
        $this->assertTrue(User::employeeCanSignIn($y), 'by pk_old');
        $this->assertSame((string) $b, User::resolveEmployeePk($y));
    }

    // Non-NULL categories keep the behaviour they had before the collision
    // rules: an employee login on the employee's own pk or own pk_old counts.

    public function test_an_employee_login_on_the_employees_own_pk_counts(): void
    {
        $a = $this->employee();
        $this->login($a, 'E');

        $this->assertTrue(User::employeeCanSignIn($a));
        $this->assertSame(1, $this->canLoginInDropdown($a));
    }

    public function test_an_employee_login_on_the_employees_own_unshared_pk_old_counts(): void
    {
        $y = $this->unusedKey();
        $b = $this->employee($y);
        $this->login($y, 'E');

        $this->assertTrue(User::employeeCanSignIn($b), 'by pk');
        $this->assertTrue(User::employeeCanSignIn($y), 'by pk_old');
        $this->assertSame(1, $this->canLoginInDropdown($b), 'the dropdown disagrees with the server');
    }

    public function test_an_employee_login_on_a_duplicated_pk_old_counts_for_neither_employee(): void
    {
        $y = $this->unusedKey();
        $b = $this->employee($y);
        $c = $this->employee($y);
        $this->login($y, 'E');

        $this->assertFalse(User::employeeCanSignIn($b), 'B counts through a pk_old shared with C');
        $this->assertFalse(User::employeeCanSignIn($c), 'C counts through a pk_old shared with B');
    }
}
