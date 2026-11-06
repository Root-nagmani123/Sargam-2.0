<?php

namespace Tests\Feature;

use App\DataTables\EstateElectricSlabDataTable;
use App\DataTables\EstateMigrationReportDataTable;
use App\DataTables\EstatePossessionDetailsDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\DataTables\EstateReturnHouseDataTable;
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
            EstateElectricSlabDataTable::class, EstateReturnHouseDataTable::class] as $table) {
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
            }

            if (count(array_unique($values['asc'])) < 2) {
                continue; // nothing to reorder on this data
            }
            $this->assertNotSame($values['asc'], $values['desc'], "{$route}: sorting {$data} asc vs desc changes the rows");
        }
    }
}
