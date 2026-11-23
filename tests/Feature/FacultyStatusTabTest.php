<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Faculty listing: All / Active / Inactive tabs.
 *
 * The tabs send ?status_filter=, and FacultyDataTable::resolveStatusFilter()
 * whitelists it for the grid, the Redis cache key and the export. These tests
 * hold the grid and the export to the same counts the database gives.
 *
 * Read-only against the database .env points at (see phpunit.xml).
 */
class FacultyStatusTabTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function admin()
    {
        return $this->as($this->userWithRole('Super Admin'), ['Super Admin']);
    }

    /** @return array<string,int> expected row count per tab value */
    private function dbCounts(): array
    {
        $all = DB::table('faculty_master')->count();
        $active = DB::table('faculty_master')->where('active_inactive', 1)->count();

        return ['' => $all, 'active' => $active, 'inactive' => $all - $active];
    }

    public function test_the_listing_renders_the_three_tabs(): void
    {
        $html = $this->admin()->get(route('faculty.index'))->assertOk()->getContent();

        $this->assertStringContainsString('programme-status-tabs', $html);
        foreach (['data-faculty-status=""', 'data-faculty-status="active"', 'data-faculty-status="inactive"'] as $tab) {
            $this->assertStringContainsString($tab, $html, "missing tab: {$tab}");
        }
    }

    public function test_a_deep_linked_status_filter_selects_its_tab(): void
    {
        $html = $this->admin()->get(route('faculty.index', ['status_filter' => 'inactive']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/faculty-status-tab active"\s+data-faculty-status="inactive"/', $html);
    }

    public function test_the_grid_returns_only_the_rows_of_the_selected_tab(): void
    {
        foreach ($this->dbCounts() + ['bogus' => $this->dbCounts()['']] as $filter => $expected) {
            $json = $this->admin()
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('faculty.index', [
                    'draw' => 1, 'start' => 0, 'length' => 200,
                    'search' => ['value' => '', 'regex' => 'false'],
                    'order' => [],
                    'status_filter' => $filter,
                ]))
                ->assertOk()
                ->json();

            // An unknown value must behave as All, not as an empty or broken filter.
            $this->assertSame($expected, (int) $json['recordsFiltered'], "recordsFiltered for '{$filter}'");

            $badges = collect($json['data'])->map(fn ($row) => str_contains($row['status'], 'Inactive') ? 'Inactive' : 'Active')->unique()->values()->all();
            if ($filter === 'active' && $badges) {
                $this->assertSame(['Active'], $badges);
            }
            if ($filter === 'inactive' && $badges) {
                $this->assertSame(['Inactive'], $badges);
            }
        }
    }

    public function test_the_csv_export_follows_the_selected_tab(): void
    {
        $lines = function (string $filter): int {
            $response = $this->admin()
                ->get(route('faculty.export', array_filter(['format' => 'csv', 'status_filter' => $filter])))
                ->assertOk();
            $body = method_exists($response->baseResponse, 'getCallback')
                ? $response->streamedContent()
                : $response->getContent();

            return count(array_filter(preg_split('/\r\n|\n/', $body), fn ($l) => trim($l) !== ''));
        };

        $counts = $this->dbCounts();
        $all = $lines('');

        // The header block is the same in every file, so the row difference has
        // to equal the database's difference exactly.
        $this->assertSame($counts[''] - $counts['active'], $all - $lines('active'));
        $this->assertSame($counts[''] - $counts['inactive'], $all - $lines('inactive'));
    }
}
