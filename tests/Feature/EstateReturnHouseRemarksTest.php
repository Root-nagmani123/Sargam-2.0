<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-050: the Return House form had replaced the Remarks textarea with an empty hidden input, so no
 * new return could carry a remark while the grid and its exports kept a Remarks column. Product decision:
 * keep Remarks.
 */
class EstateReturnHouseRemarksTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as setUpRollback;
    }

    protected function setUp(): void
    {
        $this->setUpRollback();
        // The remark is written to the meta file after commit; let afterCommit run inside the rollback transaction.
        $manager = $this->app->make('db.transactions');
        $manager->callbacksShouldIgnore($manager->getTransactions()->first());
    }

    public function test_the_form_has_a_remarks_field_an_officer_can_type_into(): void
    {
        $html = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->get(route('admin.estate.return-house'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<label for="request_remarks"[^>]*>Remarks</label>#', $html);
        $this->assertMatchesRegularExpression('#<textarea[^>]*\bname="remarks"#', $html);
        $this->assertDoesNotMatchRegularExpression('#<input[^>]*type="hidden"[^>]*name="remarks"#', $html, 'no empty hidden remarks input');
    }

    public function test_a_remark_entered_on_return_reaches_the_grid_and_the_export(): void
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
        $remark = 'F050 keys returned to the caretaker '.uniqid();
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $actor->post(route('admin.estate.possession-view.store'), [
            'redirect_to' => 'return-house',
            'employee_type' => 'LBSNAA',
            'employee_select_id' => $epd->estate_home_request_details,
            'returning_date' => date('Y-m-d'),
            'remarks' => $remark,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(1, (int) DB::table('estate_possession_details')->where('pk', $epd->pk)->value('return_home_status'), 'the house is returned');

        $grid = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('admin.estate.return-house', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => $remark],
        ]))->assertOk()->json('data');
        $this->assertContains($remark, array_column((array) $grid, 'remarks'), 'the grid shows the remark');

        $csv = $actor->get(route('admin.estate.return-house.download', ['format' => 'csv', 'search' => $remark]));
        $csv->assertOk();
        $this->assertStringContainsString($remark, $csv->streamedContent(), 'the export carries the remark');
    }
}
