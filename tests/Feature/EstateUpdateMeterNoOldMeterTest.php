<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-042: Update Meter Details' Old Meter No is the previous reading's meter number for the same
 * possession. The bill year / month filter must not change it: unfiltered, year-only and month+year must
 * agree, with and without a meter replacement.
 */
class EstateUpdateMeterNoOldMeterTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    public function test_old_meter_no_is_the_same_unfiltered_and_filtered(): void
    {
        $this->seedTwoReadings('111');

        foreach ($this->views() as $label => $row) {
            $this->assertSame('111', $row['old_meter1_no'], "$label: Old Meter No is the previous reading's meter");
            $this->assertSame('111', $row['new_meter1_no'], "$label: no replacement");
        }
    }

    public function test_a_meter_replacement_shows_the_old_and_new_meter_under_every_filter(): void
    {
        $this->seedTwoReadings('222');

        foreach ($this->views() as $label => $row) {
            $this->assertSame('111', $row['old_meter1_no'], "$label: old meter");
            $this->assertSame('222', $row['new_meter1_no'], "$label: new meter");
        }
    }

    /**
     * Two readings of one listed possession, made its first two by date (rolled back): an earlier one in
     * January 1998 on meter 111, and the marked one in February 1999 on $laterMeterNo.
     */
    private function seedTwoReadings(string $laterMeterNo): void
    {
        $possessionPk = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->whereNotNull('epd.estate_house_master_pk')
            ->groupBy('emrd.estate_possession_details_pk')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('emrd.estate_possession_details_pk')
            ->value('emrd.estate_possession_details_pk');
        if (! $possessionPk) {
            $this->markTestSkipped('no listed possession with two meter readings');
        }

        $readings = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $possessionPk);
        // Every other reading of this possession sorts after the two below (NULL to_date would sort first).
        (clone $readings)->update(['to_date' => '2001-01-01']);
        [$earlier, $later] = (clone $readings)->orderBy('pk')->limit(2)->pluck('pk')->all();

        DB::table('estate_month_reading_details')->where('pk', $earlier)->update([
            'to_date' => '1998-01-31', 'bill_month' => 'January', 'bill_year' => '1998', 'meter_one' => '111', 'house_no' => 'F042EARLIER',
        ]);
        DB::table('estate_month_reading_details')->where('pk', $later)->update([
            'to_date' => '1999-02-28', 'bill_month' => 'February', 'bill_year' => '1999', 'meter_one' => $laterMeterNo, 'house_no' => 'F042LATER',
        ]);
    }

    /** @return array<string, array<string, mixed>> the marked reading's feed row, per filter */
    private function views(): array
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $views = [];
        foreach (['unfiltered' => [], 'year only' => ['bill_year' => '1999'], 'month + year' => ['bill_year' => '1999', 'bill_month' => '2']] as $label => $filters) {
            $data = $actor->getJson(route('admin.estate.update-meter-no.list', $filters + [
                'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'F042LATER', 'regex' => 'false'],
            ]))->assertOk()->json('data');
            $this->assertCount(1, $data, "$label: the marked reading is listed once");
            $views[$label] = $data[0];
        }

        return $views;
    }
}
