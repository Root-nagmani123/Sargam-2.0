<?php

namespace Tests\Feature;

use App\Models\EmployeeGroupMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Employee Group Master: the modernised listing, its four exports, and the
 * Add/Edit modal — which could never save before, because store() wrote a
 * `group_name` column that does not exist on employee_group_master.
 */
class EmployeeGroupMasterTest extends TestCase
{
    private function actor(): User
    {
        $user = User::query()->orderBy('pk')->first();
        if (! $user) {
            $this->markTestSkipped('no user available to authenticate as');
        }

        return $user;
    }

    private function fetch(string $url)
    {
        return $this->actingAs($this->actor())->get($url);
    }

    private function bytes($response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $response->streamedContent();
        }

        return $response->getContent();
    }

    public function test_index_renders_the_new_design_chrome(): void
    {
        $html = $this->fetch('/master/employee-group')->assertOk()->getContent();

        foreach ([
            'employee-group-page',
            'programme-dt-table',
            'programme-dt-panel',
            'data-dt-search-for="employeegroupmaster-table"',
            'data-dt-footer-for="employeegroupmaster-table"',
            'egmColumnToggleGrid',
            'egmFormModal',
            'egmDownloadBtn',
            'egmPrintBtn',
            'Add Employee Group',
            'status-msg',
            'mst-page',
            'master-lookup-admin.css',
            'mst-modal',
            'mst-modal-body',
            'mst-btn-submit',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "missing: {$needle}");
        }

        $this->assertStringNotContainsString('float-end gap-2', $html);

        // Button::make('reset')/('reload') throw during init and make jQuery skip
        // every later init.dt handler, which silently kills the global enhancer.
        $this->assertStringNotContainsString('"reset"', $html);
        $this->assertStringNotContainsString('"reload"', $html);
    }

    public function test_grid_feed_renders_badge_and_action_stack(): void
    {
        $columns = [];
        foreach (['DT_RowIndex', 'emp_group_name', 'status', 'action'] as $i => $name) {
            $columns[$i] = [
                'data' => $name, 'name' => $name,
                'searchable' => $name === 'emp_group_name' ? 'true' : 'false',
                'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $json = $this->actingAs($this->actor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/employee-group?' . http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns, 'order' => [],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json();

        $row = $json['data'][0] ?? null;
        if (! $row) {
            $this->markTestSkipped('no employee groups in this database');
        }

        // Soft badge with data-order, per docs/new-design-index-page.md §3b.
        $this->assertMatchesRegularExpression(
            '/status-pill badge rounded-1 bg-(success|danger)-subtle/',
            $row['status']
        );
        $this->assertMatchesRegularExpression('/data-order="[01]"/', $row['status']);
        $this->assertStringNotContainsString('status-toggle', $row['status'], 'the switch belongs in Action');
        $this->assertStringContainsString('mst-act-group', $row['action']);
        // Icon-over-label stacks, and the caption names the ACTION not the state.
        $this->assertStringContainsString('mst-act__label', $row['action']);
        $this->assertMatchesRegularExpression('/>(Deactivate|Activate)</', $row['action']);
        // §3b trap 1: the switch must NOT sit in a .form-check.form-switch wrapper.
        $this->assertStringNotContainsString('form-switch', $row['action']);
        $this->assertStringContainsString('egm-edit-btn', $row['action']);
        $this->assertStringContainsString('data-table="employee_group_master"', $row['action']);

        fwrite(STDERR, sprintf("\ngrid: total=%s rows=%d\n", $json['recordsTotal'], count($json['data'])));
    }

    public function test_exports_share_one_column_list(): void
    {
        $csv = $this->bytes($this->fetch('/master/employee-group/export/csv')->assertOk());
        $lines = str_getcsv($csv, "\n");

        $this->assertStringContainsString('EMPLOYEE GROUP MASTER', $lines[1]);
        $this->assertSame(['S. No.', 'Employee Group Name', 'Status'], str_getcsv($lines[5]));

        $print = $this->fetch('/master/employee-group/export/print')->assertOk()->getContent();
        $this->assertStringContainsString('window.print()', $print);
        $this->assertStringContainsString('>Employee Group Name<', $print);

        $this->assertStringStartsWith('%PDF-', $this->bytes($this->fetch('/master/employee-group/export/pdf')->assertOk()));
        $this->assertStringStartsWith('PK', $this->bytes($this->fetch('/master/employee-group/export/excel')->assertOk()));

        $narrowed = $this->bytes($this->fetch('/master/employee-group/export/csv?cols=sno,status'));
        $this->assertSame(['S. No.', 'Status'], str_getcsv(str_getcsv($narrowed, "\n")[5]));

        $filtered = $this->bytes($this->fetch('/master/employee-group/export/csv?q=zzzznotagroup'));
        $this->assertStringContainsString('Search: zzzznotagroup', $filtered);

        fwrite(STDERR, 'csv rows: ' . (count($lines) - 6) . "\n");
    }

    public function test_unknown_format_is_rejected(): void
    {
        $this->fetch('/master/employee-group/export/exe')->assertNotFound();
    }

    /**
     * The regression this page needed most: saving used to fail outright with
     * "Unknown column 'group_name' in 'field list'". Wrapped in a transaction —
     * this writes to the real database and must leave nothing behind.
     */
    public function test_modal_create_and_update_actually_save(): void
    {
        $user = $this->actor();
        $before = EmployeeGroupMaster::count();

        DB::beginTransaction();

        try {
            $name = 'Gate Grp ' . substr(uniqid(), -6);

            $created = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-group/store', ['pk' => '', 'emp_group_name' => $name]);

            $created->assertOk()->assertJson(['status' => true]);

            $row = EmployeeGroupMaster::where('emp_group_name', $name)->first();
            $this->assertNotNull($row, 'the row was not written — the column-name bug is back');

            // Duplicate -> 422 keyed to the field the modal renders errors against.
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-group/store', ['pk' => '', 'emp_group_name' => $name])
                ->assertStatus(422)
                ->assertJsonValidationErrors('emp_group_name');

            // Update through the same route, scoped by the encrypted pk.
            $renamed = 'Gate Grp ' . substr(uniqid(), -6);
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-group/store', [
                    'pk' => encrypt($row->pk), 'emp_group_name' => $renamed,
                ])
                ->assertOk()
                ->assertJson(['status' => true]);

            $this->assertSame($renamed, $row->fresh()->emp_group_name);

            // varchar(30): a longer name must be rejected, not truncated by MySQL.
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-group/store', ['pk' => '', 'emp_group_name' => str_repeat('x', 31)])
                ->assertStatus(422)
                ->assertJsonValidationErrors('emp_group_name');

            fwrite(STDERR, "modal create/update/duplicate/length all handled\n");
        } finally {
            DB::rollBack();
        }

        $this->assertSame($before, EmployeeGroupMaster::count(), 'the test left rows behind');
    }
}
