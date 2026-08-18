<?php

namespace Tests\Feature;

use App\Models\IssueCategoryMaster;
use App\Models\IssuePriorityMaster;
use App\Models\IssueSubCategoryMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Write-path cover for the three CENTCOM master grids.
 *
 * The read paths got tested first because that is what the server-side
 * conversion changed; these are the create / update / delete flows behind the
 * rebuilt modals, which nothing had exercised.
 *
 * DatabaseTransactions is load-bearing here (unlike in the read-only feed
 * tests): every row created below is rolled back at the end of each test.
 * NOT RefreshDatabase — phpunit.xml leaves DB_CONNECTION commented out, so the
 * suite runs against the real database and RefreshDatabase would drop it.
 */
class CentcomMasterCrudTest extends TestCase
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

    private function anyCategoryPk(): int
    {
        $pk = IssueCategoryMaster::query()->value('pk');

        if (! $pk) {
            $this->markTestSkipped('No issue category to hang a sub-category off.');
        }

        return (int) $pk;
    }

    /* ── Categories ──────────────────────────────────────────────────────── */

    public function test_a_category_can_be_created_updated_and_deleted(): void
    {
        $name = 'ZZ Test Category ' . uniqid();

        // The Add modal posts a repeatable categories[] array, not flat fields.
        $this->actingAs($this->actor())
            ->post(route('admin.issue-categories.store'), [
                'categories' => [
                    ['issue_category' => $name, 'description' => 'created by CentcomMasterCrudTest'],
                ],
            ])
            ->assertRedirect(route('admin.issue-categories.index'));

        $row = IssueCategoryMaster::where('issue_category', $name)->first();
        $this->assertNotNull($row, 'store() did not persist the category');
        $this->assertSame(1, (int) $row->status, 'a new category should be active');

        $this->actingAs($this->actor())
            ->put(route('admin.issue-categories.update', $row->pk), [
                'issue_category' => $name . ' edited',
                'description' => 'edited',
                'status' => 0,
            ])
            ->assertRedirect(route('admin.issue-categories.index'));

        $row->refresh();
        $this->assertSame($name . ' edited', $row->issue_category);
        $this->assertSame(0, (int) $row->status);

        // destroy() refuses an ACTIVE category; this one was just deactivated.
        $this->actingAs($this->actor())
            ->delete(route('admin.issue-categories.destroy', $row->pk))
            ->assertRedirect(route('admin.issue-categories.index'));

        $this->assertNull(
            IssueCategoryMaster::find($row->pk),
            'destroy() left an inactive, unreferenced category behind'
        );
    }

    public function test_an_active_category_cannot_be_deleted(): void
    {
        $name = 'ZZ Active Category ' . uniqid();

        $this->actingAs($this->actor())
            ->post(route('admin.issue-categories.store'), [
                'categories' => [['issue_category' => $name, 'description' => 'x']],
            ]);

        $row = IssueCategoryMaster::where('issue_category', $name)->firstOrFail();

        $this->actingAs($this->actor())
            ->delete(route('admin.issue-categories.destroy', $row->pk))
            ->assertSessionHas('error');

        $this->assertNotNull(
            IssueCategoryMaster::find($row->pk),
            'an ACTIVE category was deleted — the guard the grid relies on is gone'
        );
    }

    /* ── Sub-categories ──────────────────────────────────────────────────── */

    public function test_a_sub_category_can_be_created_updated_and_deleted(): void
    {
        $categoryPk = $this->anyCategoryPk();
        $name = 'ZZ Test Sub ' . uniqid();

        $this->actingAs($this->actor())
            ->post(route('admin.issue-sub-categories.store'), [
                'issue_category_master_pk' => $categoryPk,
                'issue_sub_category' => $name,
            ])
            ->assertRedirect(route('admin.issue-sub-categories.index'));

        $row = IssueSubCategoryMaster::where('issue_sub_category', $name)->first();
        $this->assertNotNull($row, 'store() did not persist the sub-category');

        $this->actingAs($this->actor())
            ->put(route('admin.issue-sub-categories.update', $row->pk), [
                'issue_category_master_pk' => $categoryPk,
                'issue_sub_category' => $name . ' edited',
                'status' => 0,
            ])
            ->assertRedirect(route('admin.issue-sub-categories.index'));

        $row->refresh();
        $this->assertSame($name . ' edited', $row->issue_sub_category);
        $this->assertSame(0, (int) $row->status);

        $this->actingAs($this->actor())
            ->delete(route('admin.issue-sub-categories.destroy', $row->pk))
            ->assertRedirect(route('admin.issue-sub-categories.index'));

        $this->assertNull(IssueSubCategoryMaster::find($row->pk), 'destroy() did not remove the sub-category');
    }

    /* ── Priorities ──────────────────────────────────────────────────────── */

    public function test_a_priority_can_be_created_updated_and_deleted(): void
    {
        $name = 'ZZ Test Priority ' . uniqid();

        $this->actingAs($this->actor())
            ->post(route('admin.issue-priorities.store'), [
                'priority' => $name,
                'description' => 'created by CentcomMasterCrudTest',
            ])
            ->assertRedirect(route('admin.issue-priorities.index'));

        $row = IssuePriorityMaster::where('priority', $name)->first();
        $this->assertNotNull($row, 'store() did not persist the priority');

        $this->actingAs($this->actor())
            ->put(route('admin.issue-priorities.update', $row->pk), [
                'priority' => $name . ' edited',
                'description' => 'edited',
                'status' => 0,
            ])
            ->assertRedirect(route('admin.issue-priorities.index'));

        $row->refresh();
        $this->assertSame($name . ' edited', $row->priority);

        $this->actingAs($this->actor())
            ->delete(route('admin.issue-priorities.destroy', $row->pk))
            ->assertRedirect(route('admin.issue-priorities.index'));

        $this->assertNull(IssuePriorityMaster::find($row->pk), 'destroy() did not remove the priority');
    }

    /* ── The grid must reflect a write immediately ───────────────────────── */

    public function test_a_new_category_appears_in_the_grid_feed_straight_away(): void
    {
        $name = 'ZZ Freshness ' . uniqid();

        $this->actingAs($this->actor())
            ->post(route('admin.issue-categories.store'), [
                'categories' => [['issue_category' => $name, 'description' => 'freshness probe']],
            ]);

        // The feed caches a {total, ids} snapshot; store() bumps the cache epoch,
        // so the new row has to show up on the very next draw rather than after
        // the TTL. This is the assertion that catches a missing epoch bump.
        $columns = [];
        foreach (['', 'category', 'description', 'sub_categories', 'status', ''] as $i => $n) {
            $columns[$i] = ['data' => $n, 'name' => $n, 'searchable' => 'true', 'orderable' => 'true'];
        }
        $url = route('admin.issue-categories.data') . '?' . http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'search' => ['value' => $name, 'regex' => 'false'],
            'columns' => $columns,
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]);

        $payload = $this->actingAs($this->actor())->get($url)->assertOk()->json();

        $this->assertSame(1, $payload['recordsFiltered'], 'the newly created category is not in the feed');
        $this->assertStringContainsString($name, $payload['data'][0]['category']);
    }
}
