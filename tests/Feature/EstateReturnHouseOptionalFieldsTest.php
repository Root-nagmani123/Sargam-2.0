<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-034: Date of Return and the NOC upload are optional (Estate module owner decision,
 * 2026-11-10). The form must not mark them mandatory, and the server accepts every combination.
 */
class EstateReturnHouseOptionalFieldsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_the_form_does_not_mark_date_or_noc_as_mandatory(): void
    {
        $html = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->get(route('admin.estate.return-house'))->assertOk()->getContent();

        foreach (['Date of Return', 'Upload NOC Document'] as $label) {
            $this->assertMatchesRegularExpression('#>'.preg_quote($label, '#').'</label>#', $html, "{$label} has no required marker");
        }
    }

    /** @return array<string, array{0: bool, 1: bool}> */
    public static function combinations(): array
    {
        return ['neither' => [false, false], 'date only' => [true, false], 'NOC only' => [false, true], 'both' => [true, true]];
    }

    /** @dataProvider combinations */
    public function test_a_return_is_accepted_with_or_without_date_and_noc(bool $withDate, bool $withNoc): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $epd = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')->whereNotNull('estate_home_request_details')
            ->where('possession_date', '>', '1900-01-01')
            ->where(fn ($q) => $q->whereNull('estate_change_id')->orWhere('estate_change_id', -1))
            ->where(fn ($q) => $q->whereNull('return_home_status')->orWhere('return_home_status', 0))
            ->orderByDesc('pk')->first();
        if (! $epd) {
            $this->markTestSkipped('no active LBSNAA possession');
        }

        $payload = array_filter([
            'redirect_to' => 'return-house',
            'employee_type' => 'LBSNAA',
            'employee_select_id' => $epd->estate_home_request_details,
            'returning_date' => $withDate ? date('Y-m-d') : null,
            'noc_document' => $withNoc ? UploadedFile::fake()->create('noc.pdf', 10, 'application/pdf') : null,
        ]);

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->post(route('admin.estate.possession-view.store'), $payload)
            ->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(1, (int) DB::table('estate_possession_details')->where('pk', $epd->pk)->value('return_home_status'), 'the house is returned');
    }
}
