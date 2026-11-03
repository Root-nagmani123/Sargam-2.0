<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #349 Estate downloads.
 *
 * F-005: Request For Estate and Possession Details exports keep the grid's row scope —
 * a login without an Estate role gets only its own employee's rows, while the Estate
 * authority still gets everyone's (control).
 *
 * F-006: ?cols[]= and ?search[]= on every Estate export/print route are treated as "all
 * columns" / "no search" instead of a TypeError or "Array to string conversion" 500.
 */
class EstateExportScopeAndInputTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const AUTHORITY_ROLES = ['Estate Admin', 'Super Admin', 'HAC Person', 'Estate'];

    /** Every Estate export and print route added by PR #349. */
    private const ROUTES = [
        'request-for-others.export', 'request-for-others.print',
        'request-for-estate.export', 'request-for-estate.print',
        'put-in-hac.export',
        'change-request-hac-approved.export', 'change-request-hac-approved.print',
        'request-for-house.download', 'request-for-house.print',
        'define-house.download', 'define-house.print',
        'possession-details.export', 'possession-details.print',
        'possession-for-others.export', 'possession-for-others.print',
        'return-house.download', 'return-house.print',
        'update-meter-no.export', 'update-meter-no.print',
        'list-meter-reading.download', 'list-meter-reading.print',
        'generate-estate-bill-for-other.download', 'generate-estate-bill-for-other.print',
        'define-electric-slab.download', 'define-electric-slab.print',
    ];

    public function test_request_for_estate_export_is_limited_to_the_staff_users_own_requests(): void
    {
        [$user, $own, $others] = $this->staffWithOwnRows();

        $ids = $this->csvColumn($this->as($user, ['Staff'])->get(route('admin.estate.request-for-estate.export', ['format' => 'csv'])), 'Request ID');

        $this->assertContains($own, $ids, 'the user still sees their own request');
        $this->assertSame([], array_values(array_intersect($ids, $others)), "another employee's request leaked into the export");

        $all = $this->csvColumn($this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->get(route('admin.estate.request-for-estate.export', ['format' => 'csv'])), 'Request ID');
        $this->assertNotEmpty(array_intersect($all, $others), 'control: the Estate authority sees other employees');
    }

    public function test_possession_details_export_is_limited_to_the_staff_users_own_possession(): void
    {
        [$user, $own, $others] = $this->staffWithOwnRows();

        $ids = $this->csvColumn($this->as($user, ['Staff'])->get(route('admin.estate.possession-details.export', ['format' => 'csv'])), 'Request ID');

        $this->assertContains($own, $ids, 'the user still sees their own possession');
        $this->assertSame([], array_values(array_intersect($ids, $others)), "another employee's possession leaked into the export");

        $all = $this->csvColumn($this->as($this->staffWithRole('Estate Admin'), ['Estate Admin'])->get(route('admin.estate.possession-details.export', ['format' => 'csv'])), 'Request ID');
        $this->assertNotEmpty(array_intersect($all, $others), 'control: the Estate authority sees other employees');
    }

    public function test_array_cols_and_search_never_500_on_any_estate_download(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $failures = [];

        foreach (self::ROUTES as $name) {
            foreach ([[], ['cols' => ['req_id']], ['search' => ['x']], ['format' => ['csv']], self::arrayFilters()] as $query) {
                $status = $actor->get(route('admin.estate.'.$name, $query))->getStatusCode();
                if ($status !== 200) {
                    $failures[] = $name.'?'.urldecode(http_build_query($query)).' => '.$status;
                }
            }
        }

        $this->assertSame([], $failures);
    }

    /** Every toolbar filter the Estate downloads read, each sent as an array. */
    private static function arrayFilters(): array
    {
        $keys = ['allotment_date_filter', 'bill_date_filter', 'bill_month', 'bill_year', 'bill_year_filter', 'eligibility_filter',
            'estate_filter', 'status_filter', 'type_filter', 'unit_type_filter'];

        return array_fill_keys($keys, ['x']);
    }

    public function test_scalar_search_and_cols_still_filter(): void
    {
        [$user, $own] = $this->staffWithOwnRows();
        $admin = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $csv = $admin->get(route('admin.estate.possession-details.export', ['format' => 'csv', 'search' => $own, 'cols' => 'request_id']));

        $this->assertSame([$own], $this->csvColumn($csv, 'Request ID'), 'search narrows to the one request');
        $this->assertSame(['Request ID'], $this->csvHeader($csv), 'cols narrows to the one column');
    }

    /**
     * A non-Estate staff login with an active possession, its own req_id and the req_ids
     * of other employees' active possessions.
     *
     * @return array{0: User, 1: string, 2: list<string>}
     */
    private function staffWithOwnRows(): array
    {
        $privileged = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->whereIn('r.name', ['Estate Admin', 'Super Admin', 'SuperAdmin', 'HAC Person'])
            ->pluck('m.model_id')->all();
        $possessions = DB::table('estate_possession_details as epd')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->where('epd.possession_date', '>', '1900-01-01')->where('epd.return_home_status', 0)
            ->where('ehrd.status', '!=', 2)->whereNotNull('ehrd.employee_pk')
            ->get(['ehrd.employee_pk', 'ehrd.req_id']);

        $logins = DB::table('user_credentials')->whereNotIn('pk', $privileged)->whereNotNull('user_id')
            ->where(fn ($q) => $q->whereNull('user_category')->orWhere('user_category', '!=', 'S'))
            ->orderBy('pk')->limit(500)->get(['pk', 'user_id']);
        foreach ($logins as $login) {
            $ids = getEmployeeIdsForUser($login->user_id);
            $own = $possessions->whereIn('employee_pk', $ids);
            if ($ids && $own->isNotEmpty()) {
                $others = $possessions->whereNotIn('employee_pk', $ids)->pluck('req_id')->filter()->values()->all();

                return [User::findOrFail($login->pk), (string) $own->first()->req_id, $others];
            }
        }

        $this->markTestSkipped('no non-Estate staff login holds an active possession');
    }

    /** @return list<list<string>> CSV rows from the heading row on */
    private function csvRows($response): array
    {
        $response->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (in_array('Request ID', $line, true)) {
                return array_slice($lines, $i);
            }
        }
        $this->fail('the CSV has no heading row');
    }

    /** @return list<string> */
    private function csvHeader($response): array
    {
        return array_values(array_filter($this->csvRows($response)[0], fn ($h) => $h !== ''));
    }

    /** @return list<string> */
    private function csvColumn($response, string $heading): array
    {
        $rows = $this->csvRows($response);
        $col = array_search($heading, $rows[0], true);

        return array_values(array_map(fn ($r) => (string) ($r[$col] ?? ''), array_slice($rows, 1)));
    }
}
