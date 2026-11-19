<?php

namespace Tests\Feature;

use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-045: Request for Others and Possession for Others take Bill Year / Bill Date toolbar
 * filters on the grid feed and on Print. An array, malformed, impossible or empty value must be
 * ignored (same rows as no filter), never an exception; a valid value still applies.
 */
class EstateOthersBillFilterInputTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    private const GRIDS = ['request-for-others', 'possession-for-others'];

    /** Inputs that must read as "no filter". */
    private const IGNORED = [
        'array year' => ['bill_year_filter' => ['2026']],
        'array date' => ['bill_date_filter' => ['2026-01-15']],
        'malformed date' => ['bill_date_filter' => '2026-1-5'],
        'text date' => ['bill_date_filter' => 'yesterday'],
        'impossible date' => ['bill_date_filter' => '2026-99-99'],
        'impossible day' => ['bill_date_filter' => '2026-02-30'],
        'empty date' => ['bill_date_filter' => ''],
        'empty year' => ['bill_year_filter' => ''],
    ];

    public function test_bad_bill_filters_are_ignored_by_the_grid_feeds(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $failures = [];

        foreach (self::GRIDS as $grid) {
            $all = $this->feed($actor, $grid, []);
            foreach (self::IGNORED as $label => $query) {
                $json = $this->feed($actor, $grid, $query);
                if (array_key_exists('error', $json) || ($json['recordsFiltered'] ?? null) !== $all['recordsFiltered']) {
                    $failures[] = "$grid $label: ".json_encode(['error' => $json['error'] ?? null, 'filtered' => $json['recordsFiltered'] ?? null, 'all' => $all['recordsFiltered']]);
                }
            }

            foreach (['valid date' => ['bill_date_filter' => '2026-01-15'], 'valid year + date' => ['bill_year_filter' => '2026', 'bill_date_filter' => '2026-01-15']] as $label => $query) {
                $json = $this->feed($actor, $grid, $query);
                if (array_key_exists('error', $json) || $json['recordsFiltered'] > $all['recordsFiltered']) {
                    $failures[] = "$grid $label: ".json_encode(['error' => $json['error'] ?? null]);
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_bad_bill_dates_are_ignored_by_the_prints_and_a_valid_one_applies(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);

        foreach (self::GRIDS as $grid) {
            $all = $actor->get(route('admin.estate.'.$grid.'.print'))->assertOk();
            foreach (self::IGNORED as $label => $query) {
                $print = $actor->get(route('admin.estate.'.$grid.'.print', $query));
                $this->assertSame(200, $print->getStatusCode(), "$grid print, $label");
                $this->assertStringNotContainsString('Bill Date:', $print->viewData('filterLine'), "$grid print, $label: no date named");
                $this->assertCount(count($all->viewData('rows')), $print->viewData('rows'), "$grid print, $label: filter ignored");
            }

            $valid = $actor->get(route('admin.estate.'.$grid.'.print', ['bill_date_filter' => '2026-01-15']))->assertOk();
            $this->assertStringContainsString('Bill Date: 15-01-2026', $valid->viewData('filterLine'));
            $this->assertLessThanOrEqual(count($all->viewData('rows')), count($valid->viewData('rows')));
        }
    }

    /** @return array<string, mixed> */
    private function feed($actor, string $grid, array $query): array
    {
        // Yajra keeps one request object per app; drop it so each call reads its own request.
        $this->app->forgetInstance('datatables.request');

        return $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.estate.'.$grid, $query + ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->json();
    }
}
