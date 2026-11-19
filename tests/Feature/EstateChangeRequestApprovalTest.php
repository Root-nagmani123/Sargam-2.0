<?php

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-004: HAC approval of a change request is one atomic decision.
 *
 * A second approval of the same request is refused and allots nothing, and a write that
 * fails part-way through leaves the request, the possession and both houses exactly as
 * they were. The real two-process race is EstateChangeRequestConcurrentApprovalTest.
 */
class EstateChangeRequestApprovalTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_a_second_approval_is_refused_and_allots_nothing(): void
    {
        [$cr, $houseA, $houseB] = $this->fixture();
        $before = $this->state($cr, $houseA, $houseB);
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $actor->postJson(route('admin.estate.change-request.approve', $cr->pk), ['estate_house_master_pk' => $houseA])
            ->assertOk()->assertJson(['success' => true]);
        $actor->postJson(route('admin.estate.change-request.approve', $cr->pk), ['estate_house_master_pk' => $houseB])
            ->assertStatus(409)->assertJson(['success' => false]);

        $after = $this->state($cr, $houseA, $houseB);
        $this->assertSame(1, $after['decision']);
        $this->assertCount(count($before['possessions']), $after['possessions'], 'no second possession row');
        $this->assertSame([$houseA], array_values(array_unique(array_column($after['possessions'], 'house'))), 'the possession is on the first approval\'s house');
        $this->assertSame($before['houseB'], $after['houseB'], 'the refused approval did not touch its house');
    }

    public function test_an_approved_request_cannot_then_be_rejected(): void
    {
        [$cr, $houseA] = $this->fixture();
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $actor->postJson(route('admin.estate.change-request.approve', $cr->pk), ['estate_house_master_pk' => $houseA])->assertOk();
        $actor->postJson(route('admin.estate.change-request.disapprove', $cr->pk), ['disapprove_reason' => 'too late'])
            ->assertStatus(409)->assertJson(['success' => false]);

        $this->assertSame(1, (int) DB::table('estate_change_home_req_details')->where('pk', $cr->pk)->value('change_ap_dis_status'));
        $this->assertNull(DB::table('estate_change_home_req_details')->where('pk', $cr->pk)->value('remarks'));
    }

    public function test_a_rejected_request_cannot_be_rejected_again_or_approved(): void
    {
        [$cr, $houseA, $houseB] = $this->fixture();
        $before = $this->state($cr, $houseA, $houseB);
        $actor = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $actor->postJson(route('admin.estate.change-request.disapprove', $cr->pk), ['disapprove_reason' => 'first reason'])
            ->assertOk()->assertJson(['success' => true]);
        $actor->postJson(route('admin.estate.change-request.disapprove', $cr->pk), ['disapprove_reason' => 'second reason'])->assertStatus(409);
        $actor->postJson(route('admin.estate.change-request.approve', $cr->pk), ['estate_house_master_pk' => $houseA])->assertStatus(409);

        $this->assertSame('first reason', DB::table('estate_change_home_req_details')->where('pk', $cr->pk)->value('remarks'), 'the first decision\'s remark stands');
        $after = $this->state($cr, $houseA, $houseB);
        $this->assertSame(2, $after['decision']);
        $this->assertSame($before['possessions'], $after['possessions'], 'a rejection allots nothing');
        $this->assertSame($before['houseA'], $after['houseA']);
    }

    public function test_a_failed_write_rolls_back_the_whole_decision(): void
    {
        [$cr, $houseA, $houseB] = $this->fixture();
        $before = $this->state($cr, $houseA, $houseB);

        // Fail the decision's last write, after the possession, house and request writes.
        DB::listen(function (QueryExecuted $query) {
            if (str_starts_with($query->sql, 'update `estate_home_request_details`') && str_contains($query->sql, '`change_status`')) {
                throw new \RuntimeException('simulated failure of the home-request write');
            }
        });

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->postJson(route('admin.estate.change-request.approve', $cr->pk), ['estate_house_master_pk' => $houseA])
            ->assertStatus(500);

        $this->assertEquals($before, $this->state($cr, $houseA, $houseB), 'nothing of the failed decision is left behind');
    }

    /**
     * A pending change request already at HAC, and two vacant, unoccupied houses.
     *
     * @return array{0: object, 1: int, 2: int}
     */
    private function fixture(): array
    {
        $cr = DB::table('estate_change_home_req_details as c')
            ->join('estate_home_request_details as h', 'h.pk', '=', 'c.estate_home_req_details_pk')
            ->where('c.change_ap_dis_status', 0)->where('c.estate_change_hac_status', 1)
            ->whereNotNull('h.employee_pk')
            ->orderBy('c.pk')
            ->first(['c.pk', 'c.estate_home_req_details_pk as home_req']);
        $occupied = DB::table('estate_possession_details')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk')
            ->merge(DB::table('estate_possession_other')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk'))
            ->all();
        $houses = DB::table('estate_house_master')->where('vacant_renovation_status', 1)->whereNotIn('pk', $occupied)
            ->orderBy('pk')->limit(2)->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        if (! $cr || count($houses) < 2) {
            $this->markTestSkipped('needs a pending change request at HAC and two vacant houses');
        }

        return [$cr, $houses[0], $houses[1]];
    }

    /** @return array<string, mixed> */
    private function state(object $cr, int $houseA, int $houseB): array
    {
        $house = fn (int $pk) => DB::table('estate_house_master')->where('pk', $pk)->value('used_home_status');

        return [
            'decision' => (int) DB::table('estate_change_home_req_details')->where('pk', $cr->pk)->value('change_ap_dis_status'),
            'change_status' => DB::table('estate_home_request_details')->where('pk', $cr->home_req)->value('change_status'),
            'possessions' => DB::table('estate_possession_details')->where('estate_home_request_details', $cr->home_req)->orderBy('pk')
                ->get(['pk', 'estate_house_master_pk as house'])->map(fn ($r) => ['pk' => (int) $r->pk, 'house' => (int) $r->house])->all(),
            'houseA' => $house($houseA),
            'houseB' => $house($houseB),
        ];
    }
}
