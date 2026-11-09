<?php

namespace Tests\Feature;

use App\DataTables\EstateElectricSlabDataTable;
use App\DataTables\EstateHacApprovedDataTable;
use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionDetailsDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-023: after a header sort, Download lists rows in the grid's order.
 *
 * The page sends only order[0][column] + dir to the export (no columns[]), so that is what
 * the export request carries here.
 */
class EstateGridExportSortParityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    /** grid route => [DataTable, export route, column sorted, grid key column, CSV heading of that key] */
    private const GRIDS = [
        'define-electric-slab.index' => [EstateElectricSlabDataTable::class, 'define-electric-slab.download', 'merge_with_house', 'unit_range', 'Unit Range'],
        'change-request-hac-approved' => [EstateHacApprovedDataTable::class, 'change-request-hac-approved.export', 'name_id', 'request_id', 'Request ID'],
        'possession-details' => [EstatePossessionDetailsDataTable::class, 'possession-details.export', 'estate_name', 'request_id', 'Request ID'],
        'possession-for-others' => [EstatePossessionOtherDataTable::class, 'possession-for-others.export', 'name', 'request_id', 'Request ID'],
        'request-for-others' => [EstateOtherRequestDataTable::class, 'request-for-others.export', 'emp_name', 'request_no_oth', 'Request ID'],
    ];

    public function test_download_follows_the_grid_header_sort(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $checked = 0;
        $outOfOrder = [];

        foreach (self::GRIDS as $route => [$table, $export, $sortBy, $key, $heading]) {
            $columns = array_map(fn ($c) => [
                'data' => $c->data, 'name' => $c->name,
                'searchable' => $c->searchable ? 'true' : 'false', 'orderable' => $c->orderable ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ], app($table)->getColumns());
            $index = array_search($sortBy, array_column($columns, 'data'), true);
            $this->assertNotFalse($index, "{$route} has a {$sortBy} column");
            $order = [['column' => $index, 'dir' => 'desc']];

            $this->app->forgetInstance('datatables.request');
            $grid = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.'.$route, ['draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
                    'order' => $order, 'search' => ['value' => '', 'regex' => 'false']]))
                ->assertOk()->json('data');
            $gridKeys = array_map(fn ($r) => trim(html_entity_decode(strip_tags((string) ($r[$key] ?? '')))), $grid);
            if (count(array_unique($gridKeys)) < 2) {
                continue; // nothing whose order could differ
            }

            $this->app->forgetInstance('datatables.request');
            $download = $this->csvColumn($actor->get(route('admin.estate.'.$export, ['format' => 'csv', 'order' => $order])), $heading);

            if ($gridKeys !== array_slice($download, 0, count($gridKeys))) {
                $outOfOrder[] = "{$route} (sorted by {$sortBy} desc)";
            }
            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'at least one grid had rows to compare');
        $this->assertSame([], $outOfOrder, 'Download lists rows in the grid order on every grid');
    }

    /** @return list<string> one column of the CSV Download, by heading (empty cells kept, so rows stay aligned) */
    private function csvColumn($response, string $heading): array
    {
        $response->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (($col = array_search($heading, $line, true)) !== false) {
                return array_map(fn ($r) => trim((string) ($r[$col] ?? '')), array_slice($lines, $i + 1));
            }
        }
        $this->fail("the CSV has no {$heading} column");
    }
}
