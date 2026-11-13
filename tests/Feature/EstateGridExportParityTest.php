<?php

namespace Tests\Feature;

use App\DataTables\EstateElectricSlabDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-012: a search finds the same rows on the grid and in its Download.
 *
 * Each grid feed is called with the page's own column list, as the browser sends it.
 */
class EstateGridExportParityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    public function test_define_electric_slab_grid_and_download_agree_on_a_search(): void
    {
        $slab = DB::table('estate_electric_slab')->orderBy('pk')->first();
        if (! $slab) {
            $this->markTestSkipped('no electric slab');
        }
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $columns = self::columnsOf(new EstateElectricSlabDataTable);

        // The stored rate ("12.50"), the range as the grid shows it ("0-100"), two words, and a miss.
        foreach ([(string) $slab->rate_per_unit, $slab->start_unit_range.'-'.$slab->end_unit_range, $slab->start_unit_range.' '.$slab->end_unit_range, 'zz-no-such-slab'] as $search) {
            $grid = array_map(fn ($r) => html_entity_decode($r['unit_range']), $this->feed($actor, 'admin.estate.define-electric-slab.index', $columns, $search));
            $download = $this->csvColumn($actor->get(route('admin.estate.define-electric-slab.download', ['format' => 'csv', 'search' => $search])), 'Unit Range');

            sort($grid);
            sort($download);
            $this->assertSame($grid, $download, "search \"{$search}\": the Download lists the grid's slabs");
        }
    }

    public function test_possession_for_others_finds_a_row_by_the_employee_id_it_shows(): void
    {
        if (! Schema::hasColumn('estate_other_req', 'employee_master_emp_id')) {
            $this->markTestSkipped('estate_other_req has no employee_master_emp_id on this database');
        }
        $row = DB::table('estate_possession_other as epo')->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->whereNotNull('eor.employee_master_emp_id')->where('eor.employee_master_emp_id', '!=', '')
            ->orderByDesc('epo.pk')->first(['epo.pk', 'eor.request_no_oth', 'eor.employee_master_emp_id']);
        if (! $row) {
            $this->markTestSkipped('no Possession for Others row with an employee ID');
        }
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);

        $grid = $this->feed($actor, 'admin.estate.possession-for-others', self::columnsOf(new EstatePossessionOtherDataTable), (string) $row->employee_master_emp_id);
        $download = $this->csvColumn($actor->get(route('admin.estate.possession-for-others.export', ['format' => 'csv', 'search' => $row->employee_master_emp_id])), 'Request ID');

        $this->assertContains((string) $row->pk, array_map(fn ($r) => (string) $r['DT_RowId'], $grid), 'the grid finds the row by its employee ID');
        $this->assertContains((string) $row->request_no_oth, $download, 'the Download finds the same row');
    }

    /** @return list<array<string, mixed>> */
    private function feed($actor, string $route, array $columns, string $search): array
    {
        // Yajra keeps one request object per app; drop it so each call reads its own request.
        $this->app->forgetInstance('datatables.request');

        return $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, ['draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns, 'search' => ['value' => $search, 'regex' => 'false']]))
            ->assertOk()->json('data');
    }

    /** @return list<string> one column of the CSV Download, by heading */
    private function csvColumn($response, string $heading): array
    {
        $response->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (($col = array_search($heading, $line, true)) !== false) {
                return array_values(array_filter(array_map(fn ($r) => (string) ($r[$col] ?? ''), array_slice($lines, $i + 1)), fn ($v) => $v !== ''));
            }
        }
        $this->fail("the CSV has no {$heading} column");
    }

    /** The column list the page's DataTable sends with every request. */
    private static function columnsOf($dataTable): array
    {
        return array_map(fn ($c) => [
            'data' => $c->data, 'name' => $c->name,
            'searchable' => $c->searchable ? 'true' : 'false', 'orderable' => $c->orderable ? 'true' : 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ], $dataTable->getColumns());
    }
}
