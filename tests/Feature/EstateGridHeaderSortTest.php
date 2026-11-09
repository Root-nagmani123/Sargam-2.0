<?php

namespace Tests\Feature;

use App\DataTables\EligibilityCriteriaDataTable;
use App\DataTables\EstateElectricSlabDataTable;
use App\DataTables\EstateMigrationReportDataTable;
use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionDetailsDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\DataTables\EstateRequestPutInHacDataTable;
use App\DataTables\EstateReturnHouseDataTable;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Header sort on Estate grids whose query() has its own ORDER BY: Yajra only appended the
 * clicked column after it, so ascending and descending returned the same rows.
 */
class EstateGridHeaderSortTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** route => [DataTable, column data to sort by] */
    private const GRIDS = [
        'possession-details' => [EstatePossessionDetailsDataTable::class, 'house_no'],
        'possession-for-others' => [EstatePossessionOtherDataTable::class, 'name'],
        'define-electric-slab.index' => [EstateElectricSlabDataTable::class, 'unit_range'],
        'reports.migration-report' => [EstateMigrationReportDataTable::class, 'campus_name'],
        // F-022: query() ends in orderBy, so a plain orderColumn was appended after it.
        'eligibility-criteria.index' => [EligibilityCriteriaDataTable::class, 'pay_scale'],
        'put-in-hac' => [EstateRequestPutInHacDataTable::class, 'emp_name'],
        'request-for-others' => [EstateOtherRequestDataTable::class, 'DT_RowIndex'],
    ];

    /**
     * Without sargamServerOrder, datatable-global-ui.js switches a server-side grid's ordering
     * off and sorts only the loaded page in the browser, so the fixes above never reach the user
     * (and Return House's Download/Print cannot carry the sort).
     */
    public function test_the_estate_grids_keep_header_sorting_on_the_server(): void
    {
        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate']);

        foreach ([EstatePossessionDetailsDataTable::class, EstatePossessionOtherDataTable::class,
            EstateElectricSlabDataTable::class, EstateReturnHouseDataTable::class, EligibilityCriteriaDataTable::class,
            EstateRequestPutInHacDataTable::class, EstateOtherRequestDataTable::class] as $table) {
            $this->assertTrue(app($table)->html()->getAttributes()['sargamServerOrder'] ?? false,
                class_basename($table).' sets sargamServerOrder');
        }
    }

    public function test_a_header_click_reverses_the_estate_grids(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate']);

        foreach (self::GRIDS as $route => [$table, $data]) {
            $columns = [];
            foreach (app($table)->getColumns() as $i => $c) {
                $columns[$i] = ['data' => $c->data, 'name' => $c->name, 'orderable' => 'true', 'searchable' => 'true'];
            }
            $index = array_search($data, array_column($columns, 'data'), true);

            $values = [];
            foreach (['asc', 'desc'] as $dir) {
                $this->app->forgetInstance('datatables.request');
                $rows = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                    ->getJson(route('admin.estate.'.$route, ['draw' => 1, 'start' => 0, 'length' => 50, 'columns' => $columns,
                        'order' => [['column' => $index, 'dir' => $dir]], 'search' => ['value' => '']]))
                    ->assertOk()->json('data');
                $values[$dir] = array_map(fn ($r) => (string) ($r[$data] ?? ''), $rows);
                // Row ids, not cell values: S. No. renumbers 1..n whatever the order.
                $ids[$dir] = array_map(fn ($r) => (string) ($r['DT_RowId'] ?? $r[$data] ?? ''), $rows);
            }

            if (count(array_unique($values['asc'])) < 2) {
                continue; // nothing to reorder on this data
            }
            $this->assertNotSame($ids['asc'], $ids['desc'], "{$route}: sorting {$data} asc vs desc changes the rows");
        }
    }

    /** Define House's own JSON feed (column 5 = House No.). Guards the server half; F-022's fix is the client flag. */
    public function test_define_house_sorts_the_whole_list_on_the_server(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin', 'Super Admin', 'Estate']);

        $pks = [];
        foreach (['asc', 'desc'] as $dir) {
            $rows = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.define-house.data', ['draw' => 1, 'start' => 0, 'length' => 25,
                    'order' => [['column' => 5, 'dir' => $dir]], 'search' => ['value' => '']]))
                ->assertOk()->json('data');
            $pks[$dir] = array_map(fn ($r) => (int) $r['pk'], $rows);
            $houses[$dir] = array_map(fn ($r) => (string) $r['house_no'], $rows);
        }
        if (count(array_unique($houses['asc'])) < 2) {
            $this->markTestSkipped('needs two houses with different numbers');
        }
        $this->assertNotSame($pks['asc'], $pks['desc'], 'House No. asc vs desc changes the first page');
    }

    /** The Put in HAC queue is empty on the dev copy, so queue three real requests (rolled back). */
    public function test_put_in_hac_sorts_a_queued_list(): void
    {
        $pks = DB::table('estate_home_request_details')->where('status', '!=', 2)
            ->whereNotNull('emp_name')->where('emp_name', '!=', '')
            ->orderBy('pk')->limit(3)->pluck('pk')->all();
        if (count($pks) < 3) {
            $this->markTestSkipped('needs three home requests');
        }
        DB::table('estate_home_request_details')->whereIn('pk', $pks)->update(['hac_status' => 0, 'change_status' => 0]);
        EstateRequestPutInHacDataTable::bumpListingCacheEpoch();

        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate']);
        $columns = [];
        foreach (app(EstateRequestPutInHacDataTable::class)->getColumns() as $i => $c) {
            $columns[$i] = ['data' => $c->data, 'name' => $c->name, 'orderable' => 'true', 'searchable' => 'true'];
        }
        $index = array_search('emp_name', array_column($columns, 'data'), true);

        $names = [];
        foreach (['asc', 'desc'] as $dir) {
            $this->app->forgetInstance('datatables.request');
            $rows = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.put-in-hac', ['draw' => 1, 'start' => 0, 'length' => 500, 'columns' => $columns,
                    'order' => [['column' => $index, 'dir' => $dir]], 'search' => ['value' => '']]))
                ->assertOk()->json('data');
            $names[$dir] = array_map(fn ($r) => strip_tags((string) $r['emp_name']), $rows);
        }

        $this->assertGreaterThanOrEqual(3, count($names['asc']), 'the queued requests are listed');
        $sorted = $names['asc'];
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), $names['asc'], 'Name ascending is in name order');
        $this->assertSame(array_reverse($names['asc']), $names['desc'], 'descending is the reverse');
    }
}
