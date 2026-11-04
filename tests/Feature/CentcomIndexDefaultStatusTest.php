<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The All Requests grid opens filtered to Reported. The grid's ajax and the
 * export links read the status dropdown, so the dropdown's first-paint
 * selection is the default: a link naming a status keeps it, and an explicit
 * empty ?status= shows every status.
 *
 * Read-only against the real configured database; skips when it is missing.
 */
class CentcomIndexDefaultStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('CentcomIndexDefaultStatusTest needs the application database');
        }
    }

    private function actor(): User
    {
        $user = User::query()
            ->whereNotNull('user_id')
            ->where(fn ($q) => $q->whereNull('user_category')->orWhere('user_category', '!=', 'S'))
            ->orderBy('pk')
            ->first();
        if (! $user) {
            $this->markTestSkipped('No non-student login to open the page with.');
        }

        return $user;
    }

    /** The status values the dropdown marks selected. */
    private function selectedStatuses(string $html): array
    {
        preg_match('/<select name="status".*?<\/select>/s', $html, $select);
        $this->assertNotEmpty($select, 'status dropdown not found');
        preg_match_all('/<option value="(\d+)"\s+selected/', $select[0], $m);

        return $m[1];
    }

    public function test_page_opens_filtered_to_reported(): void
    {
        $html = $this->actingAs($this->actor())
            ->get(route('admin.issue-management.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(['0'], $this->selectedStatuses($html));
        // The default is a filter like any other, so it can be removed.
        $this->assertMatchesRegularExpression('/id="imResetFilters"\s+class="btn programme-dt-btn-reset\s*"/', $html);
    }

    public function test_a_status_in_the_link_is_kept(): void
    {
        $html = $this->actingAs($this->actor())
            ->get(route('admin.issue-management.index', ['status' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertSame(['2'], $this->selectedStatuses($html));
    }

    public function test_an_empty_status_shows_every_status(): void
    {
        $html = $this->actingAs($this->actor())
            ->get(route('admin.issue-management.index') . '?status=')
            ->assertOk()
            ->getContent();

        $this->assertSame([], $this->selectedStatuses($html));
    }
}
