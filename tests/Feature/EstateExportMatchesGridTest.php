<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EstateController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 F-024: Download rows and fields match what the grid shows.
 */
class EstateExportMatchesGridTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    /** Change House Request searches in the browser: a name word plus the employee ID finds the row. */
    public function test_change_house_request_download_keeps_a_two_word_match(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $row = collect($this->invoke('requestForHouseRows'))->map(fn ($r) => (object) $r)
            ->first(fn ($r) => trim((string) ($r->name ?? '')) !== '' && trim((string) ($r->emp_id ?? '')) !== '' && ($r->request_id ?? '') !== '');
        if (! $row) {
            $this->markTestSkipped('needs a change request with a name and an employee ID');
        }
        $search = strtok(trim($row->name), ' ').' '.trim($row->emp_id);

        $ids = $this->csvColumn($actor->get(route('admin.estate.request-for-house.download', ['format' => 'csv', 'search' => $search])), 'Request ID');

        $this->assertContains((string) $row->request_id, $ids, "search \"{$search}\" keeps the request the grid shows");
    }

    /** Generate Estate Bill for Other searches in the browser too: name word plus house number. */
    public function test_bill_for_other_download_keeps_a_two_word_match(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        foreach (range(0, 12) as $back) {
            $month = now()->startOfMonth()->subMonths($back);
            $row = collect($this->invoke('computeGenerateEstateBillForOtherDataRows', $month->format('F'), $month->format('Y'), true))
                ->first(fn ($r) => trim((string) ($r['name'] ?? '')) !== '' && trim((string) ($r['house_no'] ?? '')) !== '');
            if ($row) {
                break;
            }
        }
        if (! $row) {
            $this->markTestSkipped('no Other bill with a name and house number in the last year');
        }
        $search = strtok(trim($row['name']), ' ').' '.trim($row['house_no']);

        $houses = $this->csvColumn($actor->get(route('admin.estate.generate-estate-bill-for-other.download', [
            'format' => 'csv', 'bill_month' => $month->format('Y-m'), 'search' => $search,
        ])), 'House Number');

        $this->assertContains(trim($row['house_no']), $houses, "search \"{$search}\" keeps the bill the grid shows");
    }

    /** A request saved without emp_name: the grid looks the name up in employee_master, so must the Download. */
    public function test_request_for_estate_download_names_a_legacy_row_like_the_grid(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $row = DB::table('estate_home_request_details as r')
            ->join('employee_master as e', 'e.pk', '=', 'r.employee_pk')
            ->whereNotNull('r.req_id')->where('r.req_id', '!=', '')
            ->whereNotNull('e.first_name')->where('e.first_name', '!=', '')
            ->orderByDesc('r.pk')
            ->first(['r.pk', 'r.req_id', 'e.first_name', 'e.middle_name', 'e.last_name']);
        if (! $row) {
            $this->markTestSkipped('needs a request linked to an employee_master row');
        }
        DB::table('estate_home_request_details')->where('pk', $row->pk)->update(['emp_name' => '']);
        // The name the grid builds from employee_master for such a row.
        $expected = trim($row->first_name.' '.$row->middle_name.' '.$row->last_name);

        [$ids, $names] = $this->csvColumns($actor->get(route('admin.estate.request-for-estate.export', ['format' => 'csv', 'search' => $row->req_id])), ['Request ID', 'Name & ID']);
        $at = array_search((string) $row->req_id, $ids, true);

        $this->assertNotFalse($at, 'the Download lists the request');
        $this->assertStringContainsString($expected, $names[$at], 'the Download names the employee the grid shows');
    }

    /** An empty-string house_no: the grid shows a dash, so the Download must too. */
    public function test_possession_for_others_download_shows_the_grid_dash_for_an_empty_house_no(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $row = DB::table('estate_possession_other as epo')
            ->join('estate_other_req as eor', 'eor.pk', '=', 'epo.estate_other_req_pk')
            ->join('estate_house_master as ehm', 'ehm.pk', '=', 'epo.estate_house_master_pk')
            ->whereNotNull('ehm.house_no')->where('ehm.house_no', '!=', '')
            ->whereNotNull('eor.request_no_oth')->where('eor.request_no_oth', '!=', '')
            ->orderByDesc('epo.pk')
            ->first(['epo.pk', 'eor.request_no_oth']);
        if (! $row) {
            $this->markTestSkipped('needs a Possession for Others row on a house with a number');
        }
        DB::table('estate_possession_other')->where('pk', $row->pk)->update(['house_no' => '']);

        [$ids, $houses] = $this->csvColumns($actor->get(route('admin.estate.possession-for-others.export', ['format' => 'csv', 'search' => $row->request_no_oth])), ['Request ID', 'House Number']);
        $at = array_search((string) $row->request_no_oth, $ids, true);

        $this->assertNotFalse($at, 'the Download lists the possession');
        $this->assertSame('-', $houses[$at], 'the Download shows the dash the grid shows');
    }

    private function invoke(string $method, ...$args)
    {
        $m = new \ReflectionMethod(EstateController::class, $method);
        $m->setAccessible(true);

        return $m->invoke($this->app->make(EstateController::class), ...$args);
    }

    /** @return list<string> */
    private function csvColumn($response, string $heading): array
    {
        return $this->csvColumns($response, [$heading])[0];
    }

    /** @return list<list<string>> the named CSV columns, row-aligned */
    private function csvColumns($response, array $headings): array
    {
        $response->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            $cols = array_map(fn ($h) => array_search($h, $line, true), $headings);
            if (! in_array(false, $cols, true)) {
                $body = array_slice($lines, $i + 1);

                return array_map(fn ($c) => array_map(fn ($r) => trim((string) ($r[$c] ?? '')), $body), $cols);
            }
        }
        $this->fail('the CSV has no '.implode(' / ', $headings).' column');
    }
}
