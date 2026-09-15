<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MemberIndexSmokeTest extends TestCase
{
    private function actor(): User
    {
        $user = User::query()->orderBy('pk')->first();
        if (! $user) {
            $this->markTestSkipped('no user available to authenticate as');
        }

        return $user;
    }

    public function test_member_index_renders_new_design_chrome(): void
    {
        $response = $this->actingAs($this->actor())->get('/member');
        $response->assertOk();

        $html = $response->getContent();

        foreach ([
            'member-index-page',
            'programme-dt-table',
            'programme-dt-panel',
            'data-dt-search-for="member-table"',
            'data-dt-footer-for="member-table"',
            'memberColumnToggleGrid',
            'Add Member',
            'Download',
            'memberPrintBtn',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "missing: {$needle}");
        }

        $this->assertStringNotContainsString('float-end gap-2', $html, 'legacy header block still present');

        // The All / Active / Inactive tabs were removed from this grid: no markup
        // and no leftover JS hooks. (The server still honours a ?status_filter=
        // on the exports — see MemberExportFormatsTest — it is just not a tab.)
        $this->assertStringNotContainsString('programme-status-pill', $html);
        $this->assertStringNotContainsString('programme-status-tabs', $html);
        $this->assertStringNotContainsString('data-member-status', $html);
        $this->assertStringNotContainsString('memberStatusFilter', $html);
        fwrite(STDERR, "\npage OK (".strlen($html)." bytes)\n");
    }

    /**
     * @dataProvider statusFilters
     */
    public function test_member_ajax_payload(string $filter, string $label): void
    {
        $columns = [];
        foreach (['DT_RowIndex', 'employee_name', 'employee_id', 'mobile_no', 'email', 'status', 'actions'] as $i => $name) {
            $columns[$i] = [
                'data' => $name,
                'name' => $name,
                'searchable' => in_array($name, ['employee_name', 'mobile_no', 'email'], true) ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $query = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'columns' => $columns,
            'order' => [],
        ];
        if ($filter !== '') {
            $query['status_filter'] = $filter;
        }

        $response = $this->actingAs($this->actor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/member?'.http_build_query($query));

        $response->assertOk();
        if (! str_starts_with(trim($response->getContent()), '{')) {
            $this->fail('non-JSON body: '.substr(strip_tags($response->getContent()), 0, 400));
        }
        $json = $response->json();
        $row = $json['data'][0] ?? null;
        if (! $row) {
            $this->markTestSkipped("no member rows for the '{$label}' filter");
        }

        // Status column is a display-only soft badge.
        $this->assertMatchesRegularExpression('/status-pill badge rounded-1 bg-(success|danger)-subtle/', $row['status']);
        // The switch lives in the action stack, with no .form-check/.form-switch wrapper.
        $this->assertStringContainsString('member-status-toggle', $row['actions']);
        $this->assertStringNotContainsString('form-switch', $row['actions']);
        $this->assertStringContainsString('mbr-act-group', $row['actions']);
        // Plain-text cells must no longer ship raw <label> markup.
        $this->assertStringNotContainsString('<label', (string) $row['employee_name']);

        $badge = preg_match('/subtle">([A-Za-z]+)</', $row['status'], $m) ? $m[1] : '?';
        $delete = str_contains($row['actions'], 'is-disabled') ? 'disabled' : 'enabled';

        if ($filter === 'active') {
            $this->assertSame('Active', $badge);
            $this->assertSame('disabled', $delete, 'active rows must not offer delete');
        }
        if ($filter === 'inactive') {
            $this->assertSame('Inactive', $badge);
        }

        fwrite(STDERR, sprintf(
            "ajax[%-9s] total=%s filtered=%s rows=%d badge=%s delete=%s name=%s\n",
            $label,
            $json['recordsTotal'],
            $json['recordsFiltered'],
            count($json['data']),
            $badge,
            $delete,
            var_export($row['employee_name'], true)
        ));
    }

    /** Same process, same cache store: the pills must not share a cache entry. */
    public function test_status_filters_do_not_share_a_cache_entry(): void
    {
        $user = $this->actor();
        $totals = [];

        foreach (['', 'active', 'inactive', 'active'] as $i => $filter) {
            $query = ['draw' => $i + 1, 'start' => 0, 'length' => 5, 'columns' => [], 'order' => []];
            foreach (['DT_RowIndex', 'employee_name', 'employee_id', 'mobile_no', 'email', 'status', 'actions'] as $c => $name) {
                $query['columns'][$c] = [
                    'data' => $name, 'name' => $name, 'searchable' => 'false', 'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ];
            }
            if ($filter !== '') {
                $query['status_filter'] = $filter;
            }

            $json = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson('/member?'.http_build_query($query))
                ->assertOk()
                ->json();

            $totals[] = [$filter ?: 'all', $json['recordsFiltered'], $json['draw']];
        }

        fwrite(STDERR, 'cache keys: '.json_encode($totals)."\n");

        if ((int) $totals[0][1] === 0) {
            $this->markTestSkipped('no member rows to compare');
        }

        $this->assertNotEquals($totals[0][1], $totals[1][1], 'All and Active returned the same count — cache key collision');
        $this->assertNotEquals($totals[1][1], $totals[2][1], 'Active and Inactive returned the same count — cache key collision');
        $this->assertSame($totals[1][1], $totals[3][1], 'repeating Active changed the count');
        $this->assertSame($totals[0][1], $totals[1][1] + $totals[2][1], 'Active + Inactive should account for every member');
    }

    public static function statusFilters(): array
    {
        return [
            'all' => ['', 'All'],
            'active' => ['active', 'Active'],
            'inactive' => ['inactive', 'Inactive'],
            'bogus' => ['bogus', 'bogus->All'],
        ];
    }
}
