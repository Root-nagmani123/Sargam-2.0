<?php

namespace Tests\Feature;

use App\Models\IssueLogManagement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Editing a ticket whose nodal officer is stored by pk_old (PR #337 F-022).
 *
 * The nodal-employees endpoint offers officers by employee_master.pk. The
 * edit form preselects the option whose value equals CURRENT.nodal and, when
 * none does, adds a "Current nodal officer (not on offer)" entry. If
 * CURRENT.nodal carries the stored pk_old, the officer appears twice: once
 * under their name, once as "not on offer".
 *
 * The prefill runs in the browser, so these tests pin its two server-side
 * inputs instead: the value rendered into CURRENT.nodal, and the key the
 * endpoint offers the same officer under. They must be equal.
 *
 * Runs against the real configured database inside a transaction that is
 * always rolled back, and skips when the database is unreachable.
 */
class IssueEditNodalPkOldPrefillTest extends TestCase
{
    private bool $inTransaction = false;

    private User $actor;

    private int $officerPk;

    private int $officerPkOld;

    private int $categoryPk;

    private int $subCategoryPk;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('IssueEditNodalPkOldPrefillTest needs the application database');
        }

        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            $this->markTestSkipped('employee_master has no pk_old column.');
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $actor = User::query()
            ->where('user_category', 'E')
            ->whereIn('user_id', fn ($q) => $q->select('pk')->from('employee_master')->where('status', '!=', 2))
            ->whereNotIn('pk', fn ($q) => $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', ['Super Admin', 'SuperAdmin', 'Admin']))
            ->first();
        $sub = DB::table('issue_sub_category_master')->whereNotNull('issue_category_master_pk')->first();
        if (! $actor || ! $sub) {
            $this->markTestSkipped('Needs a non-admin employee login and an issue sub-category.');
        }
        $this->actor = $actor;
        $this->categoryPk = (int) $sub->issue_category_master_pk;
        $this->subCategoryPk = (int) $sub->pk;

        // An eligible officer with a distinct pk_old that names nobody else,
        // signing in with an E login on their own pk.
        $this->officerPkOld = (int) max(
            DB::table('employee_master')->max('pk'),
            DB::table('employee_master')->max('pk_old'),
            DB::table('user_credentials')->max('user_id'),
        ) + 100000 + random_int(0, 99999);
        $this->officerPk = (int) DB::table('employee_master')->insertGetId([
            'pk_old' => $this->officerPkOld,
            'first_name' => 'F022',
            'last_name' => 'Officer',
            'status' => 1,
        ]);
        DB::table('user_credentials')->insert([
            'user_name' => 'f022-probe-' . uniqid(),
            'user_id' => $this->officerPk,
            'user_category' => 'E',
        ]);
        $this->assertTrue(User::employeeCanSignIn($this->officerPk), 'fixture: the officer cannot sign in');

        DB::table('issue_category_employee_map')->where('issue_category_master_pk', $this->categoryPk)->delete();
        DB::table('issue_category_employee_map')->insert([
            'issue_category_master_pk' => $this->categoryPk,
            'employee_master_pk' => $this->officerPk,
            'priority' => 1,
            'days_notify' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /** An open ticket the actor raised, whose nodal officer is stored by pk_old. */
    private function pkOldKeyedIssue(): IssueLogManagement
    {
        return IssueLogManagement::create([
            'issue_category_master_pk' => $this->categoryPk,
            'description' => 'F-022 pk_old prefill probe',
            'issue_status' => IssueLogManagement::STATUS_IN_PROGRESS,
            'created_by' => $this->actor->user_id,
            'issue_logger' => $this->actor->user_id,
            'employee_master_pk' => $this->officerPkOld,
            'location' => 'O',
            'created_date' => now(),
        ]);
    }

    public function test_the_edit_form_prefills_the_key_the_endpoint_offers_the_officer_under(): void
    {
        $issue = $this->pkOldKeyedIssue();

        $page = $this->actingAs($this->actor)
            ->get(route('admin.issue-management.edit', $issue->pk))
            ->assertOk();
        $page->assertSee('nodal: "' . $this->officerPk . '"', false);
        $page->assertDontSee('nodal: "' . $this->officerPkOld . '"', false);

        // The same request the edit form sends (complainant and logger excluded).
        $offered = $this->actingAs($this->actor)
            ->getJson('/admin/issue-management/nodal-employees/' . $this->categoryPk
                . '?complainant=' . $this->actor->user_id . '&logger=' . $this->actor->user_id)
            ->assertOk()
            ->json();
        $offeredPks = array_map(fn ($r) => (string) $r['employee_pk'], $offered['level1'] ?? []);

        $this->assertContains((string) $this->officerPk, $offeredPks, 'the officer is not offered by name');
        $this->assertSame(1, count(array_keys($offeredPks, (string) $this->officerPk, true)), 'the officer is offered more than once');
    }

    public function test_saving_the_prefilled_officer_keeps_the_stored_key(): void
    {
        $issue = $this->pkOldKeyedIssue();

        $this->actingAs($this->actor)
            ->put(route('admin.issue-management.update', $issue->pk), [
                'issue_category_id' => $this->categoryPk,
                'issue_sub_category_id' => $this->subCategoryPk,
                'issue_priority_id' => (int) DB::table('issue_priority_master')->value('pk'),
                'nodal_employee_id' => $this->officerPk,
                'location' => 'O',
                'description' => 'F-022 edited description',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $fresh = $issue->fresh();
        $this->assertSame('F-022 edited description', $fresh->description);
        $this->assertSame((string) $this->officerPkOld, (string) $fresh->employee_master_pk, 'the same officer was re-keyed from pk_old to pk');
    }
}
