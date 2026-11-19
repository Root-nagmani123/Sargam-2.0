<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-037: on Update Meter Details a Bill Year chosen without a month must limit the
 * grid and the printed report to that year, and the report header must name it.
 */
class EstateUpdateMeterNoYearFilterTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    public function test_a_year_without_a_month_limits_the_grid_and_the_print_to_that_year(): void
    {
        // Move one listed reading into a year nobody has (rolled back), so a second year exists.
        $pk = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->orderByDesc('emrd.pk')
            ->value('emrd.pk');
        if (! $pk) {
            $this->markTestSkipped('estate_month_reading_details has no listed readings');
        }
        DB::table('estate_month_reading_details')->where('pk', $pk)->update(['bill_year' => '1999']);

        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $count = fn (array $filters) => $actor->getJson(route('admin.estate.update-meter-no.list', $filters + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => '', 'regex' => 'false'],
        ]))->assertOk()->json('recordsFiltered');

        $all = $count([]);
        $year = $count(['bill_year' => '1999']);
        $this->assertGreaterThanOrEqual(1, $year, 'the moved reading is listed under its year');
        $this->assertLessThan($all, $year, 'a year without a month filters the grid');

        $print = $actor->get(route('admin.estate.update-meter-no.print', ['bill_year' => '1999']))->assertOk();
        $print->assertSee('Bill Year: 1999');
        $this->assertCount($year, $print->viewData('rows'), 'the print lists only the year it names');
    }
}
