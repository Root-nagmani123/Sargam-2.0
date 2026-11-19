<?php

namespace Tests\Feature;

use App\DataTables\EstateHacApprovedDataTable;
use App\DataTables\EstateRequestPutInHacDataTable;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-009: the HAC Approval and Put in HAC grids find a request by the
 * employee's name and by the employee ID, as their Download already did.
 *
 * Each feed is called the way the page calls it (its own column list), because
 * Yajra's global search only looks at the columns the request names.
 */
class EstateHacGridSearchTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    public function test_hac_approval_grid_finds_a_request_by_name_and_by_employee_id(): void
    {
        $actor = $this->as($this->staffWithRole('HAC Person'), self::AUTHORITY_ROLES);
        $columns = self::columnsOf(new EstateHacApprovedDataTable);
        $feed = fn (string $search) => $this->feed($actor, 'admin.estate.change-request-hac-approved', $columns, $search);

        $row = collect($feed(''))->first(fn ($r) => trim((string) ($r['emp_name'] ?? '')) !== '' && trim((string) ($r['employee_id'] ?? '')) !== '');
        if (! $row) {
            $this->markTestSkipped('the HAC Approval queue has no row with a name and an employee ID');
        }

        $this->assertFound($feed($row['emp_name']), $row['DT_RowId'], 'name');
        $this->assertFound($feed($row['employee_id']), $row['DT_RowId'], 'employee ID');
        $this->assertContains((string) $row['request_id'], $this->exportedIds($actor, 'change-request-hac-approved.export', $row['employee_id']), 'the Download finds the same request');
    }

    public function test_put_in_hac_grid_finds_a_request_by_name_and_by_employee_id(): void
    {
        $pk = DB::table('estate_home_request_details')->where('status', '!=', 2)
            ->where('emp_name', '!=', '')->where('employee_id', '!=', '')
            ->whereNotNull('req_id')->orderByDesc('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no estate request with a name and an employee ID');
        }
        DB::table('estate_home_request_details')->where('pk', $pk)->update(['hac_status' => 0, 'change_status' => 0]);
        $request = DB::table('estate_home_request_details')->where('pk', $pk)->first(['req_id', 'emp_name', 'employee_id']);

        $actor = $this->as($this->staffWithRole('HAC Person'), self::AUTHORITY_ROLES);
        $columns = self::columnsOf(new EstateRequestPutInHacDataTable);
        $feed = fn (string $search) => $this->feed($actor, 'admin.estate.put-in-hac', $columns, $search);

        $this->assertFound($feed($request->emp_name), $pk, 'name');
        $this->assertFound($feed($request->employee_id), $pk, 'employee ID');
        $this->assertContains((string) $request->req_id, $this->exportedIds($actor, 'put-in-hac.export', $request->employee_id), 'the Download finds the same request');
    }

    /** @return list<array<string, mixed>> */
    private function feed($actor, string $route, array $columns, string $search): array
    {
        // Yajra keeps one request object per app; drop it so each call reads its own request.
        $this->app->forgetInstance('datatables.request');

        return $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, ['draw' => 1, 'start' => 0, 'length' => 500, 'columns' => $columns, 'search' => ['value' => $search, 'regex' => 'false']]))
            ->assertOk()->json('data');
    }

    private function assertFound(array $rows, $rowId, string $by): void
    {
        $this->assertContains((string) $rowId, array_map(fn ($r) => (string) $r['DT_RowId'], $rows), "searching by {$by} finds the request");
    }

    /** @return list<string> Request IDs in the CSV Download for this search */
    private function exportedIds($actor, string $route, string $search): array
    {
        $response = $actor->get(route('admin.estate.'.$route, ['format' => 'csv', 'search' => $search]))->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (($col = array_search('Request ID', $line, true)) !== false) {
                return array_map(fn ($r) => (string) ($r[$col] ?? ''), array_slice($lines, $i + 1));
            }
        }
        $this->fail('the CSV has no Request ID column');
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
