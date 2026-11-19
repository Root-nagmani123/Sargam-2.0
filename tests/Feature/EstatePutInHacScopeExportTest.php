<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-044: Put In HAC opened from Home (?scope=self) lists the caller's own requests. The page now
 * forwards scope to Download / Print; this pins that the export then lists exactly the grid's rows, with
 * and without the scope.
 */
class EstatePutInHacScopeExportTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_the_export_lists_the_same_requests_as_the_grid_for_each_scope(): void
    {
        $user = $this->staffWithRole('Estate Admin');
        $ownIds = getEmployeeIdsForUser($user->user_id ?? $user->pk);
        $pks = DB::table('estate_home_request_details')->orderByDesc('pk')->limit(2)->pluck('pk')->all();
        if ($ownIds === [] || count($pks) < 2) {
            $this->markTestSkipped('needs an employee-linked Estate Admin and two requests');
        }
        // Two requests waiting for HAC (rolled back): one the caller's own, one someone else's.
        $queued = ['hac_status' => 0, 'change_status' => 0, 'status' => 0];
        DB::table('estate_home_request_details')->where('pk', $pks[0])->update($queued + ['req_id' => 'F044SELF', 'employee_pk' => (int) $ownIds[0]]);
        DB::table('estate_home_request_details')->where('pk', $pks[1])->update($queued + ['req_id' => 'F044OTHER', 'employee_pk' => 999999999]);

        $actor = $this->as($user, ['Estate Admin']);
        foreach (['self' => ['scope' => 'self'], 'all' => []] as $label => $scope) {
            $this->app->forgetInstance('datatables.request');
            $grid = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.put-in-hac', $scope + ['draw' => 1, 'start' => 0, 'length' => -1]))
                ->assertOk()->json();
            $gridIds = $this->reqIds(json_encode($grid['data']));

            $csv = $actor->get(route('admin.estate.put-in-hac.export', $scope + ['format' => 'csv']))->assertOk()->streamedContent();
            $exportIds = $this->reqIds($csv);

            $this->assertSame($gridIds, $exportIds, "$label: the export lists the grid's marked requests");
            $this->assertContains('F044SELF', $exportIds, "$label: the caller's own request");
            $label === 'self'
                ? $this->assertNotContains('F044OTHER', $exportIds, 'self: no one else\'s request')
                : $this->assertContains('F044OTHER', $exportIds, 'all: the whole queue');
        }
    }

    /** @return string[] the marked request ids present in $text, in a fixed order */
    private function reqIds(string $text): array
    {
        return array_values(array_filter(['F044SELF', 'F044OTHER'], fn ($id) => str_contains($text, $id)));
    }
}
