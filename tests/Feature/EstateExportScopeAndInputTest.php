<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
 * columns" / "no search" instead of a TypeError or "Array to string conversion" 500. So are
 * the toolbar filters and DataTables' own search[value] / order[0][dir], on the downloads and
 * on the grids' data feeds.
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

    /** The Estate grid data feeds and pages that read DataTables / toolbar input. */
    private const FEEDS = [
        'define-house.data', 'list-meter-reading.data', 'update-meter-no.list', 'generate-estate-bill',
        'reports.bill-report-grid.data', 'reports.bill-report-print', 'reports.bill-report-print.employees',
        'reports.pending-meter-reading.data',
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

    public function test_a_guest_gets_no_estate_download(): void
    {
        foreach (['request-for-estate.export', 'possession-details.export'] as $name) {
            $response = $this->get(route('admin.estate.'.$name, ['format' => 'csv']));
            $this->assertTrue($response->isRedirect(), "{$name}: a guest is sent away, got ".$response->getStatusCode());
            $this->assertStringNotContainsString('Request ID', (string) $response->getContent(), "{$name}: no export body");
        }
    }

    public function test_array_cols_and_search_never_500_on_any_estate_download(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $failures = [];

        foreach (self::ROUTES as $name) {
            foreach ([[], ['cols' => ['req_id']], ['search' => ['x']], ['format' => ['csv']], self::arrayFilters(), ...self::nestedDataTablesInput()] as $query) {
                // Without this, exports built on a Yajra DataTable read the first request of the loop, not $query.
                $this->app->forgetInstance('datatables.request');
                $status = $actor->get(route('admin.estate.'.$name, $query))->getStatusCode();
                if ($status !== 200) {
                    $failures[] = $name.'?'.urldecode(http_build_query($query)).' => '.$status;
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_array_datatables_input_never_500_on_any_estate_grid_feed(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $failures = [];

        foreach (self::FEEDS as $name) {
            foreach ([['draw' => 1, 'start' => 0, 'length' => 10], ['search' => ['x']], self::arrayFilters(), ...self::nestedDataTablesInput()] as $query) {
                $status = $actor->getJson(route('admin.estate.'.$name, $query + ['draw' => 1, 'start' => 0, 'length' => 10]))->getStatusCode();
                if ($status !== 200) {
                    $failures[] = $name.'?'.urldecode(http_build_query($query)).' => '.$status;
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_array_toolbar_filters_never_500_on_the_yajra_grids(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $failures = [];
        $grids = [
            'define-electric-slab.index' => ['unit_type_filter'],
            'possession-details' => ['estate_filter', 'allotment_date_filter'],
            'possession-for-others' => ['bill_year_filter', 'bill_date_filter'],
        ];

        foreach ($grids as $name => $keys) {
            foreach ($keys as $key) {
                foreach ([[$key => '1'], [$key => ['1']]] as $query) {
                    // Yajra keeps one request object per app; drop it so each call reads its own request.
                    $this->app->forgetInstance('datatables.request');
                    $status = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                        ->getJson(route('admin.estate.'.$name, $query + ['draw' => 1, 'start' => 0, 'length' => 10]))->getStatusCode();
                    if ($status !== 200) {
                        $failures[] = $name.'?'.urldecode(http_build_query($query)).' => '.$status;
                    }
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_scalar_datatables_search_and_order_still_apply_on_the_define_house_feed(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $feed = fn (array $q) => $actor->getJson(route('admin.estate.define-house.data', $q + ['draw' => 1, 'start' => 0, 'length' => 5]))
            ->assertOk()->json('data');
        $houseNo = (string) DB::table('estate_house_master')->whereNotNull('house_no')->where('house_no', '!=', '')->orderBy('pk')->value('house_no');

        $asc = $feed(['order' => [['column' => 5, 'dir' => 'asc']]]);
        $desc = $feed(['order' => [['column' => 5, 'dir' => 'desc']]]);
        $this->assertNotEquals(array_column($asc, 'house_no'), array_column($desc, 'house_no'), 'order[0][dir] still reverses the sort');

        $found = $feed(['search' => ['value' => $houseNo]]);
        $this->assertNotEmpty($found, 'search[value] still finds the house');
        foreach ($found as $row) {
            $this->assertStringContainsStringIgnoringCase($houseNo, json_encode($row), 'every row matches the search');
        }
    }

    /** Every toolbar filter the Estate downloads and feeds read, each sent as an array. */
    private static function arrayFilters(): array
    {
        $keys = ['allotment_date_filter', 'bill_date_filter', 'bill_month', 'bill_year', 'bill_year_filter', 'eligibility_filter',
            'estate_filter', 'status_filter', 'type_filter', 'unit_type_filter', 'employee_type', 'employee_category',
            'campus_filter', 'block_filter', 'unit_sub_type_filter', 'block_id'];

        return array_fill_keys($keys, ['x']);
    }

    /** DataTables' nested input with an array where a string belongs. */
    private static function nestedDataTablesInput(): array
    {
        return [
            ['search' => ['value' => ['x']]],
            ['order' => [['column' => '0', 'dir' => ['x']]]],
        ];
    }

    public function test_scalar_search_and_cols_still_filter(): void
    {
        [$user, $own] = $this->staffWithOwnRows();
        $admin = $this->as($this->staffWithRole('Estate Admin'), ['Estate Admin']);

        $csv = $admin->get(route('admin.estate.possession-details.export', ['format' => 'csv', 'search' => $own, 'cols' => 'request_id']));

        $this->assertSame([$own], $this->csvColumn($csv, 'Request ID'), 'search narrows to the one request');
        $this->assertSame(['Request ID'], $this->csvHeader($csv), 'cols narrows to the one column');
    }

    /** F-012: Return House Download and Print hold exactly the grid's rows for the same search. */
    public function test_return_house_download_and_print_match_the_grid_for_the_same_search(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        // A return month ('2024-03'): the grid matches it on the dates, which the old export filter never searched.
        $date = (string) DB::table('estate_possession_details')->where('return_home_status', 1)
            ->whereNotNull('estate_house_master_pk')->whereNotNull('current_meter_reading_date')
            ->orderByDesc('pk')->value('current_meter_reading_date');
        if ($date === '') {
            $this->markTestSkipped('no returned LBSNAA house with a return date');
        }

        // Stored month, the on-screen dd-mm-yyyy form, and '' = after Reset: everything.
        foreach ([substr($date, 0, 7), date('d-m-Y', strtotime($date)), ''] as $search) {
            $this->app->forgetInstance('datatables.request');
            $grid = (int) $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.return-house', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => $search]]))
                ->assertOk()->json('recordsFiltered');
            $this->assertGreaterThan(0, $grid, "grid finds rows for \"{$search}\"");

            $this->app->forgetInstance('datatables.request');
            $csv = $actor->get(route('admin.estate.return-house.download', ['format' => 'csv', 'search' => $search, 'cols' => 'sno,name']));
            $downloaded = count(array_filter(array_slice($this->csvRows($csv, 'S. No.'), 1), fn ($r) => is_numeric($r[0] ?? '')));
            $this->assertSame($grid, $downloaded, "Download holds the grid's rows for \"{$search}\"");

            $this->app->forgetInstance('datatables.request');
            $printed = $actor->get(route('admin.estate.return-house.print', ['search' => $search]))->assertOk()->viewData('rows');
            $this->assertCount($grid, $printed, "Print holds the grid's rows for \"{$search}\"");
        }
    }

    /** The grid's column sort works, and Download/Print list the rows in that same order. */
    public function test_return_house_sort_applies_to_the_grid_and_its_exports(): void
    {
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);
        $norm = fn ($v) => preg_replace('/\s+/', ' ', trim(html_entity_decode((string) $v)));
        $orders = [];

        foreach (['asc', 'desc'] as $dir) {
            $order = [['column' => 1, 'dir' => $dir]]; // 1 = Name & ID
            $this->app->forgetInstance('datatables.request');
            $grid = array_map($norm, array_column($actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('admin.estate.return-house', ['draw' => 1, 'start' => 0, 'length' => -1, 'order' => $order]))
                ->assertOk()->json('data'), 'name'));

            $this->app->forgetInstance('datatables.request');
            $csv = array_slice($this->csvRows($actor->get(route('admin.estate.return-house.download', ['format' => 'csv', 'cols' => 'sno,name', 'order' => $order])), 'S. No.'), 1);
            $downloaded = array_map(fn ($r) => $norm($r[1] ?? ''), array_filter($csv, fn ($r) => is_numeric($r[0] ?? '')));

            $this->app->forgetInstance('datatables.request');
            $printed = collect($actor->get(route('admin.estate.return-house.print', ['order' => $order]))->assertOk()->viewData('rows'))
                ->map(fn ($r) => $norm($r->name))->all();

            $this->assertSame($grid, array_values($downloaded), "Download follows the grid's {$dir} sort");
            $this->assertSame($grid, array_values($printed), "Print follows the grid's {$dir} sort");
            $orders[$dir] = $grid;
        }

        $this->assertNotSame($orders['asc'], $orders['desc'], 'the Name sort actually reorders the grid');
    }

    /** Remarks kept only in the meta file (no remarks column) are searchable and reach Download/Print. */
    public function test_return_house_meta_file_remarks_are_searched_and_exported(): void
    {
        $pk = (int) DB::table('estate_possession_details')->where('return_home_status', 1)
            ->whereNotNull('estate_house_master_pk')->orderByDesc('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no returned LBSNAA house');
        }
        $remark = 'meta-remark-'.uniqid();
        Storage::fake('local')->put('estate/return-house-meta.json', json_encode(['L:'.$pk => ['remarks' => $remark]]));
        $actor = $this->as($this->staffWithRole('Estate Admin'), self::AUTHORITY_ROLES);

        $this->app->forgetInstance('datatables.request');
        $grid = $actor->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.estate.return-house', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => strtoupper($remark)]]))
            ->assertOk()->json('data');
        $this->assertSame([$remark], array_column($grid, 'remarks'), 'the grid finds the row by its meta remark');

        $this->app->forgetInstance('datatables.request');
        $csv = $actor->get(route('admin.estate.return-house.download', ['format' => 'csv', 'search' => $remark, 'cols' => 'sno,remarks']));
        $this->assertSame([['1', $remark]], array_values(array_map(fn ($r) => array_slice($r, 0, 2), array_filter(array_slice($this->csvRows($csv, 'S. No.'), 1), fn ($r) => is_numeric($r[0] ?? '')))), 'Download shows the meta remark');

        $this->app->forgetInstance('datatables.request');
        $printed = $actor->get(route('admin.estate.return-house.print', ['search' => $remark]))->assertOk()->viewData('rows');
        $this->assertSame([$remark], collect($printed)->pluck('remarks')->all(), 'Print shows the meta remark');
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
    private function csvRows($response, string $heading = 'Request ID'): array
    {
        $response->assertOk();
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (in_array($heading, $line, true)) {
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
