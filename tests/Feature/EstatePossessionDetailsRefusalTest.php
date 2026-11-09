<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-019: a refused possession save must reach an AJAX caller as 422 JSON with the
 * reason (it used to be a redirect the modal reported as "saved"); the plain form
 * post keeps its redirect + flash.
 */
class EstatePossessionDetailsRefusalTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const MISMATCH = 'Selected house does not match selected Estate/Building/Unit Sub Type.';

    public function test_ajax_refusal_is_422_json_with_the_reason(): void
    {
        $payload = $this->mismatchPayload();

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('admin.estate.possession-details.store'), $payload)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => self::MISMATCH]);
    }

    public function test_form_refusal_still_redirects_with_the_error(): void
    {
        $payload = $this->mismatchPayload();

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->from(route('admin.estate.possession-details'))
            ->post(route('admin.estate.possession-details.store'), $payload)
            ->assertRedirect(route('admin.estate.possession-details'))
            ->assertSessionHas('error', self::MISMATCH);
    }

    /** A real allotted request + a real house, posted with a block the house is not in. */
    private function mismatchPayload(): array
    {
        $requestPk = DB::table('estate_home_request_details as r')
            ->join('estate_possession_details as p', 'p.estate_home_request_details', '=', 'r.pk')
            ->where('r.hac_status', 1)
            ->orderByDesc('r.pk')
            ->value('r.pk');
        $house = DB::table('estate_house_master')->orderBy('pk')->first();
        if (! $requestPk || ! $house) {
            $this->markTestSkipped('needs an allotted home request and a house');
        }

        return [
            'estate_home_request_details_pk' => $requestPk,
            'estate_campus_master_pk' => (int) $house->estate_campus_master_pk,
            'estate_block_master_pk' => 0,
            'estate_unit_sub_type_master_pk' => (int) $house->estate_unit_sub_type_master_pk,
            'estate_house_master_pk' => (int) $house->pk,
            'allotment_date' => '2026-01-01',
            'possession_date' => '2026-01-02',
            'electric_meter_reading_primary' => '1',
        ];
    }
}
