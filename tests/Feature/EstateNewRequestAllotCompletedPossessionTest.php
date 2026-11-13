<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-032: allotting a HAC new request whose possession is already completed is refused
 * with 409 and writes nothing; a still-pending (1900-01-01) possession can be re-allotted.
 */
class EstateNewRequestAllotCompletedPossessionTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_a_completed_possession_is_not_re_allotted(): void
    {
        [$req, $possession, $houseB] = $this->fixture();
        $before = $this->state($req, $possession, $houseB);

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->postJson(route('admin.estate.new-request.allot', $req->pk), ['estate_house_master_pk' => $houseB])
            ->assertStatus(409)->assertJson(['success' => false]);

        $after = $this->state($req, $possession, $houseB);
        $this->assertEquals($before, $after, 'possession, both houses and the request are unchanged');
        $this->assertNotSame('1900-01-01', substr((string) $after['possession']['possession_date'], 0, 10));
    }

    public function test_a_pending_possession_can_still_be_re_allotted(): void
    {
        [$req, $possession, $houseB] = $this->fixture();
        DB::table('estate_possession_details')->where('pk', $possession->pk)
            ->update(['allotment_date' => '1900-01-01', 'possession_date' => '1900-01-01']);

        $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])
            ->postJson(route('admin.estate.new-request.allot', $req->pk), ['estate_house_master_pk' => $houseB])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame($houseB, (int) DB::table('estate_possession_details')->where('pk', $possession->pk)->value('estate_house_master_pk'));
    }

    /**
     * A HAC-approved new request with a completed, unreturned possession, and a vacant,
     * unoccupied house the employee is eligible for (so only the F-032 guard can refuse).
     *
     * @return array{0: object, 1: object, 2: int}
     */
    private function fixture(): array
    {
        $occupied = DB::table('estate_possession_details')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk')
            ->merge(DB::table('estate_possession_other')->where('return_home_status', 0)->whereNotNull('estate_house_master_pk')->pluck('estate_house_master_pk'))
            ->map(fn ($pk) => (int) $pk)->all();
        $eligible = new \ReflectionMethod(EstateController::class, 'getEligibleHousePksByEmployeePk');
        $resolve = new \ReflectionMethod(EstateController::class, 'resolveEmployeeMasterCanonicalPk');
        $controller = app(EstateController::class);

        $candidates = DB::table('estate_home_request_details as h')
            ->join('estate_possession_details as p', 'p.estate_home_request_details', '=', 'h.pk')
            ->where('h.hac_status', 1)->where('h.change_status', 0)->whereNotNull('h.employee_pk')
            ->where('p.possession_date', '>', '1900-01-01')->where('p.return_home_status', 0)
            ->whereNotNull('p.estate_house_master_pk')
            ->orderBy('h.pk')->limit(50)
            ->get(['h.pk', 'h.employee_pk', 'p.pk as possession_pk']);

        foreach ($candidates as $req) {
            $employeePk = $resolve->invoke($controller, (int) $req->employee_pk);
            if (! $employeePk) {
                continue;
            }
            $pool = $eligible->invoke($controller, (int) $employeePk);
            $houses = DB::table('estate_house_master')->where('vacant_renovation_status', 1)->whereNotIn('pk', $occupied)
                ->when($pool->isNotEmpty(), fn ($q) => $q->whereIn('pk', $pool->all()))
                ->orderBy('pk')->limit(1)->pluck('pk');
            if ($houses->isNotEmpty()) {
                return [$req, DB::table('estate_possession_details')->where('pk', $req->possession_pk)->first(), (int) $houses[0]];
            }
        }

        $this->markTestSkipped('needs a HAC new request with a completed possession and an eligible vacant house');
    }

    /** @return array<string, mixed> */
    private function state(object $req, object $possession, int $houseB): array
    {
        $houseA = (int) $possession->estate_house_master_pk;

        return [
            'possession' => (array) DB::table('estate_possession_details')->where('pk', $possession->pk)
                ->first(['estate_house_master_pk', 'emploee_master_pk', 'allotment_date', 'possession_date', 'return_home_status']),
            'possession_count' => DB::table('estate_possession_details')->where('estate_home_request_details', $req->pk)->count(),
            'houseA' => DB::table('estate_house_master')->where('pk', $houseA)->value('used_home_status'),
            'houseB' => DB::table('estate_house_master')->where('pk', $houseB)->value('used_home_status'),
            'request' => (array) DB::table('estate_home_request_details')->where('pk', $req->pk)->first(['status', 'current_alot', 'change_status']),
        ];
    }
}
