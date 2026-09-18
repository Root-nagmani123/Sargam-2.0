<?php

namespace Tests\Feature;

use App\Models\IssueCategoryEmployeeMap;
use App\Models\IssueCategoryMaster;
use App\Models\User;
use App\Support\DataTableRedisCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The two CENTCOM write paths the CRUD test does not reach:
 *   - the escalation matrix save (delete-then-insert three levels in one
 *     transaction — the block whose catch was widened to \Throwable)
 *   - the shared status toggle, which must bump the grid's cache epoch or a
 *     toggled row keeps its old position until the TTL expires
 *
 * DatabaseTransactions: every write below is rolled back.
 */
class CentcomEscalationAndToggleTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        return $user;
    }

    /** @return array{0:int, 1:array<int,int>} category pk + three employee pks */
    private function categoryAndEmployees(): array
    {
        // Reuse employees already referenced by the matrix: they are known to
        // satisfy EmployeeMaster::findByIdOrPkOld(), which the validator calls.
        $employees = IssueCategoryEmployeeMap::query()
            ->distinct()
            ->limit(3)
            ->pluck('employee_master_pk')
            ->map(fn ($v) => (int) $v)
            ->all();

        $category = IssueCategoryMaster::query()->where('status', 1)->value('pk');

        if (! $category || count($employees) < 3) {
            $this->markTestSkipped('Needs an active category and three mappable employees.');
        }

        return [(int) $category, $employees];
    }

    public function test_escalation_matrix_save_replaces_all_three_levels_atomically(): void
    {
        [$categoryPk, $employees] = $this->categoryAndEmployees();

        // The category already has a hierarchy in this dataset, so store() would
        // bounce with "use Edit"; the update route is the one that rewrites.
        $this->actingAs($this->actor())
            ->put(route('admin.issue-escalation-matrix.update', $categoryPk), [
                'issue_category_master_pk' => $categoryPk,
                'level1_employee_pk' => $employees[0], 'level1_days' => 5,
                'level2_employee_pk' => $employees[1], 'level2_days' => 6,
                'level3_employee_pk' => $employees[2], 'level3_days' => 7,
            ])
            ->assertRedirect(route('admin.issue-escalation-matrix.index'))
            ->assertSessionHas('success');

        $levels = IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryPk)
            ->orderBy('priority')
            ->get();

        $this->assertCount(3, $levels, 'a save must leave exactly three levels, not append');
        $this->assertSame([1, 2, 3], $levels->pluck('priority')->map(fn ($p) => (int) $p)->all());
        $this->assertSame([5, 6, 7], $levels->pluck('days_notify')->map(fn ($d) => (int) $d)->all());
    }

    public function test_escalation_matrix_save_is_rejected_when_an_employee_is_invalid(): void
    {
        [$categoryPk, $employees] = $this->categoryAndEmployees();

        $before = IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryPk)->count();

        $this->actingAs($this->actor())
            ->put(route('admin.issue-escalation-matrix.update', $categoryPk), [
                'issue_category_master_pk' => $categoryPk,
                'level1_employee_pk' => 999999999, 'level1_days' => 1,
                'level2_employee_pk' => $employees[1], 'level2_days' => 2,
                'level3_employee_pk' => $employees[2], 'level3_days' => 3,
            ])
            ->assertSessionHasErrors('level1_employee_pk');

        // Validation runs before the delete-then-insert, so the existing
        // hierarchy must survive a rejected save intact.
        $this->assertSame(
            $before,
            IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryPk)->count(),
            'a rejected save destroyed the existing hierarchy'
        );
    }

    public function test_status_toggle_flips_the_row_and_bumps_the_grid_cache_epoch(): void
    {
        $category = IssueCategoryMaster::query()->first();

        if (! $category) {
            $this->markTestSkipped('No category to toggle.');
        }

        // Point the cache helpers at the array store for this test. Without it
        // they resolve to 'redis', predis is not installed, and both readListEpoch()
        // and bumpListEpoch() swallow the failure — the epoch would read 0 forever
        // and this test would pass or fail for reasons that have nothing to do
        // with the wiring under test.
        config(['cache.redis_backed_unified_store' => 'array']);

        $original = (int) $category->status;
        $target = $original === 1 ? 0 : 1;
        $epochKey = 'admin_issue_categories_index_list_epoch';
        $epochBefore = DataTableRedisCache::readListEpoch($epochKey);

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => 'issue_category_master',
                'column' => 'status',
                'id' => $category->pk,
                'status' => $target,
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'state']);

        $this->assertSame(
            $target,
            (int) DB::table('issue_category_master')->where('pk', $category->pk)->value('status'),
            'the toggle did not persist'
        );

        // The grid caches a {total, ids} snapshot keyed by epoch + search + sort.
        // Sorting by Status, or searching a term that matches the status pill,
        // makes that snapshot depend on the value just changed — so the epoch has
        // to move or the grid serves a stale page for the whole TTL.
        $this->assertNotSame(
            $epochBefore,
            DataTableRedisCache::readListEpoch($epochKey),
            'toggling a category did not bump the categories grid cache epoch'
        );
    }
}
