<?php

namespace Tests\Feature;

use App\DataTables\EstateElectricSlabDataTable;
use App\DataTables\EstateHacApprovedDataTable;
use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionDetailsDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\DataTables\EstateRequestForEstateDataTable;
use App\DataTables\EstateRequestPutInHacDataTable;
use Illuminate\Support\Facades\DB;
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
        // F-030
        'put-in-hac' => [EstateRequestPutInHacDataTable::class, 'put-in-hac.export', 'emp_name', 'req_id', 'Request ID'],
        'request-for-estate' => [EstateRequestForEstateDataTable::class, 'request-for-estate.export', 'name_id', 'req_id', 'Request ID'],
    ];

    /**
     * F-030: Update Meter Details and List Meter Reading have hand-rolled feeds that read
     * order[0][column] / dir; their Download built the feed request without them.
     */
    public function test_download_follows_the_sort_of_the_hand_rolled_meter_grids(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $month = $this->busiestMonth('estate_month_reading_details');

        $grids = [
            // feed route, export route, filters, House Number column index in the grid
            'update-meter-no' => ['update-meter-no.list', 'update-meter-no.export', [], 6],
            'list-meter-reading' => ['list-meter-reading.data', 'list-meter-reading.download',
                ['bill_month' => $month, 'employee_type' => 'LBSNAA', 'block_id' => 'all'], 7],
        ];

        $checked = 0;
        $outOfOrder = [];
        foreach ($grids as $name => [$feed, $export, $filters, $column]) {
            $order = [['column' => $column, 'dir' => 'desc']];
            $grid = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.'.$feed, $filters + ['draw' => 1, 'start' => 0, 'length' => 1000,
                    'order' => $order, 'search' => ['value' => '', 'regex' => 'false']]))
                ->assertOk()->json('data');
            $gridKeys = array_map(fn ($r) => trim(html_entity_decode(strip_tags((string) ($r['house_no'] ?? '')))), $grid);
            if (count(array_unique($gridKeys)) < 2) {
                continue;
            }
            $download = $this->csvColumn($actor->get(route('admin.estate.'.$export, $filters + ['format' => 'csv', 'order' => $order])), 'House Number');
            if ($gridKeys !== array_slice($download, 0, count($gridKeys))) {
                $outOfOrder[] = "{$name} (House Number desc)";
            }
            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'at least one meter grid had rows to compare');
        $this->assertSame([], $outOfOrder, 'Download lists rows in the grid order');
    }

    /**
     * F-030: Change Request For House and Bill for Other sort in the browser, so the page sends
     * the rows' keys in display order (?row_order=) and the export lists its rows that way.
     */
    public function test_client_grid_downloads_follow_the_row_order_the_page_sends(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $checked = 0;

        // Change Request For House: every change-request key, ascending (the export's own order is pk desc).
        $pks = DB::table('estate_change_home_req_details')->orderBy('pk')->pluck('pk')->all();
        $default = $this->csvColumn($actor->get(route('admin.estate.request-for-house.download', ['format' => 'csv'])), 'Request ID');
        if (count(array_unique($default)) >= 2) {
            $reordered = $this->csvColumn($actor->get(route('admin.estate.request-for-house.download',
                ['format' => 'csv', 'row_order' => implode(',', $pks)])), 'Request ID');
            $this->assertSame(array_reverse($default), $reordered, 'Change Request For House follows row_order');
            $checked++;
        }

        // Bill for Other: the month's bills, in the reverse of the feed's order.
        $month = $this->busiestMonth('estate_month_reading_details_other');
        $bills = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.estate.generate-estate-bill-for-other.data', ['bill_month' => $month]))
            ->assertOk()->json('data');
        $n = count($bills);
        $default = array_slice($this->csvColumn($actor->get(route('admin.estate.generate-estate-bill-for-other.download',
            ['bill_month' => $month, 'format' => 'csv'])), 'House Number'), 0, $n);
        if (count(array_unique($default)) >= 2) {
            $reordered = array_slice($this->csvColumn($actor->get(route('admin.estate.generate-estate-bill-for-other.download',
                ['bill_month' => $month, 'format' => 'csv', 'row_order' => implode(',', array_reverse(array_column($bills, 'pk')))])), 'House Number'), 0, $n);
            $this->assertSame(array_reverse($default), $reordered, 'Bill for Other follows row_order');
            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'at least one client grid had rows to compare');
    }

    /** "Y-m" of the month with the most readings in $table. */
    private function busiestMonth(string $table): string
    {
        $row = DB::table($table)->select('bill_year', 'bill_month', DB::raw('COUNT(*) c'))
            ->groupBy('bill_year', 'bill_month')->orderByDesc('c')->first();
        if (! $row) {
            $this->markTestSkipped("{$table} has no readings");
        }

        return date('Y-m', strtotime('1 '.$row->bill_month.' '.$row->bill_year));
    }

    public function test_download_follows_the_grid_header_sort(): void
    {
        // The Put in HAC queue is empty on the dev copy: queue three requests (rolled back).
        DB::table('estate_home_request_details')->whereIn('pk', DB::table('estate_home_request_details')
            ->where('status', '!=', 2)->whereNotNull('emp_name')->where('emp_name', '!=', '')
            ->orderBy('pk')->limit(3)->pluck('pk'))->update(['hac_status' => 0, 'change_status' => 0]);
        EstateRequestPutInHacDataTable::bumpListingCacheEpoch();

        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $checked = 0;
        $outOfOrder = [];
        $compared = [];

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
            $compared[] = $route;
        }

        $this->assertGreaterThan(0, $checked, 'at least one grid had rows to compare');
        foreach (['put-in-hac', 'request-for-estate'] as $route) {
            $this->assertContains($route, $compared, "{$route} had rows to compare");
        }
        $this->assertSame([], $outOfOrder, 'Download lists rows in the grid order on every grid');
    }

    /** @return list<string> one column of the CSV Download, by heading (empty cells kept, so rows stay aligned) */
    private function csvColumn($response, string $heading): array
    {
        $response->assertOk();
        // A real CSV parse: a dual-meter cell is quoted with a newline inside it.
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, ltrim($response->streamedContent(), "\xEF\xBB\xBF"));
        rewind($stream);
        $lines = [];
        while (($line = fgetcsv($stream)) !== false) {
            $lines[] = $line;
        }
        fclose($stream);
        foreach ($lines as $i => $line) {
            if (($col = array_search($heading, $line, true)) !== false) {
                return array_map(fn ($r) => trim((string) ($r[$col] ?? '')), array_slice($lines, $i + 1));
            }
        }
        $this->fail("the CSV has no {$heading} column");
    }
}
