<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-001: a successful Update Meter Reading save read an undefined $successMsg
 * (HTTP 500 after the rows were already written), and the modal's AJAX save had lost
 * its JSON success response in a merge.
 *
 * Saves one real reading both ways: the page form must redirect to Update Meter No.
 * with the success flash, the modal's AJAX post must get {"success": true}, and the
 * reading must be stored either way.
 */
class EstateMeterReadingSaveTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_page_submit_redirects_with_the_success_message(): void
    {
        [$payload, $row, $reading] = $this->payload();

        $response = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->post(route('admin.estate.update-meter-reading.store'), $payload);

        $response->assertStatus(302);
        $this->assertStringContainsString('/admin/estate/update-meter-no', $response->headers->get('Location'));
        $response->assertSessionHas('success', 'Meter readings updated successfully.');
        $this->assertReadingStored($row, $reading);
    }

    public function test_ajax_submit_returns_json_success(): void
    {
        [$payload, $row, $reading] = $this->payload();

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('admin.estate.update-meter-reading.store'), $payload)
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'Meter readings updated successfully.']);

        $this->assertReadingStored($row, $reading);
    }

    public function test_an_invalid_reading_is_refused_and_nothing_is_written(): void
    {
        [$payload, $row] = $this->payload();
        $payload['readings'][0]['curr_month_elec_red'] = '12abc';
        $before = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $row->estate_possession_details_pk)->get();

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->postJson(route('admin.estate.update-meter-reading.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['readings.0.curr_month_elec_red']);

        $this->assertEquals($before, DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $row->estate_possession_details_pk)->get(), 'no reading row changed');
    }

    /**
     * Possession Details injects ?modal=1 into its dialog. A full page there brings a second
     * copy of the layout, whose page loader is never hidden and covers every control.
     */
    public function test_the_modal_request_gets_the_form_body_without_the_layout(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $modal = $actor->get(route('admin.estate.update-meter-reading', ['modal' => 1]))->assertOk();
        $modal->assertSee('id="meterReadingSaveForm"', false);
        $modal->assertSee('js-umr-save', false);
        $modal->assertDontSee('id="sargamLoader"', false);

        $actor->get(route('admin.estate.update-meter-reading'))->assertOk()->assertSee('id="sargamLoader"', false);
    }

    /**
     * One selected row of a current possession, with a reading above its saved one, for the
     * bill period it already belongs to.
     *
     * @return array{0: array<string, mixed>, 1: object, 2: int}
     */
    private function payload(bool $withStoredMeterAndRoomBelow = false): array
    {
        $row = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where(fn ($q) => $q->whereNull('epd.return_home_status')->orWhere('epd.return_home_status', 0))
            ->whereNotNull('emrd.curr_month_elec_red')
            ->whereNotNull('emrd.bill_month')->whereNotNull('emrd.bill_year')
            // F-040 cases: a stored meter number (emrd ?? ehm, as the controller reads it) and a reading
            // high enough to post one 400 below it.
            ->when($withStoredMeterAndRoomBelow, fn ($q) => $q
                ->whereRaw("COALESCE(emrd.meter_one, ehm.meter_one) REGEXP '^[0-9]+$'")
                ->where('emrd.curr_month_elec_red', '>', 1000))
            ->orderByDesc('emrd.pk')
            ->first(['emrd.pk', 'emrd.estate_possession_details_pk', 'emrd.bill_month', 'emrd.bill_year', 'emrd.curr_month_elec_red',
                DB::raw('COALESCE(emrd.meter_one, ehm.meter_one) as stored_meter_no')]);
        if (! $row) {
            $this->markTestSkipped('no meter reading row on a current possession');
        }

        $reading = (int) $row->curr_month_elec_red + 7;

        return [[
            'readings' => [[
                'pk' => $row->pk,
                'meter_slot' => 1,
                'selected' => 1,
                'curr_month_elec_red' => (string) $reading,
            ]],
            'reading_bill_month' => Carbon::parse('1 '.$row->bill_month.' '.$row->bill_year)->format('Y-m'),
            'reading_current_date' => now()->toDateString(),
        ], $row, $reading];
    }

    /**
     * F-040: outside the List Meter Reading → Edit flow the store writes the stored meter number, so a
     * different new_meter_no must not waive the below-baseline rule (it used to save the lower reading
     * with 0 units and the old meter).
     */
    public function test_a_changed_meter_no_does_not_let_a_lower_reading_through(): void
    {
        [$payload, $row] = $this->payload(true);
        $payload['readings'][0]['curr_month_elec_red'] = (string) ((int) $row->curr_month_elec_red - 400);
        $payload['readings'][0]['new_meter_no'] = $this->differentMeterNo($row);
        $before = DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $row->estate_possession_details_pk)->get();

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('admin.estate.update-meter-reading.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['readings.0.curr_month_elec_red']);

        $this->assertEquals($before, DB::table('estate_month_reading_details')->where('estate_possession_details_pk', $row->estate_possession_details_pk)->get(), 'no reading, meter number or unit changed');
    }

    /** F-040 regression surface: the Edit flow still records a meter replacement, units = the new meter's reading. */
    public function test_the_edit_flow_still_records_a_meter_replacement(): void
    {
        [$payload, $row] = $this->payload(true);
        $reading = (int) $row->curr_month_elec_red - 400;
        $newMeterNo = $this->differentMeterNo($row);
        $payload['readings'][0]['curr_month_elec_red'] = (string) $reading;
        $payload['readings'][0]['new_meter_no'] = $newMeterNo;
        $payload['edit_reading_pk'] = $row->pk;

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('admin.estate.update-meter-reading.store'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $saved = DB::table('estate_month_reading_details')->where('pk', $row->pk)->first();
        $this->assertSame($newMeterNo, (string) $saved->meter_one);
        $this->assertSame($reading, (int) $saved->curr_month_elec_red);
        $this->assertSame($reading, (int) $saved->meter_one_consume_unit, 'a replaced meter starts at 0');
    }

    private function differentMeterNo(object $row): string
    {
        return (string) $row->stored_meter_no === '999' ? '998' : '999';
    }

    /** F-010: the page renders the partial's one handler set, not a second copy of its own. */
    public function test_the_page_binds_one_set_of_handlers(): void
    {
        $html = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->get(route('admin.estate.update-meter-reading'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'function isReadingBelowMinAllowed('));
        $this->assertSame(1, substr_count($html, "\$('#loadMeterReadingsBtn').on('click'"));
    }

    private function assertReadingStored(object $row, int $reading): void
    {
        $this->assertTrue(
            DB::table('estate_month_reading_details')
                ->where('estate_possession_details_pk', $row->estate_possession_details_pk)
                ->where('curr_month_elec_red', $reading)
                ->exists(),
            'the new reading was saved'
        );
    }
}
