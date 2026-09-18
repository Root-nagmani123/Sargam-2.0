<?php

namespace Tests\Feature;

use App\Models\DesignationMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Designation Master: the modernised listing, its four exports, and the Add/Edit
 * modal. Third consumer of ExportsBrandedGrid and of the mst-* lookup-master
 * stylesheet, so it also guards those against a change that breaks a sibling.
 */
class DesignationMasterTest extends TestCase
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
        $html = $this->fetch('/master/designation')->assertOk()->getContent();

        foreach ([
            'designation-page',
            'programme-dt-table',
            'programme-dt-panel',
            'data-dt-search-for="designationmaster-table"',
            'data-dt-footer-for="designationmaster-table"',
            'dsgColumnToggleGrid',
            'dsgFormModal',
            'dsgDownloadBtn',
            'dsgPrintBtn',
            'Add Designation',
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

        // This grid used to declare the Department grid's table id, which makes
        // every id-keyed hook (search slot, footer slot, colvis key) ambiguous.
        $this->assertStringNotContainsString('departmentmaster-table', $html);
    }

    public function test_grid_feed_renders_badge_and_action_stack(): void
    {
        $columns = [];
        foreach (['DT_RowIndex', 'designation_name', 'status', 'action'] as $i => $name) {
            $columns[$i] = [
                'data' => $name, 'name' => $name,
                'searchable' => $name === 'designation_name' ? 'true' : 'false',
                'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $json = $this->actingAs($this->actor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/designation?' . http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns, 'order' => [],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json();

        $row = $json['data'][0] ?? null;
        if (! $row) {
            $this->markTestSkipped('no designations in this database');
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
        $this->assertStringContainsString('dsg-edit-btn', $row['action']);
        $this->assertStringContainsString('data-table="designation_master"', $row['action']);

        fwrite(STDERR, sprintf("\ngrid: total=%s rows=%d\n", $json['recordsTotal'], count($json['data'])));
    }

    public function test_exports_share_one_column_list(): void
    {
        $csv = $this->bytes($this->fetch('/master/designation/export/csv')->assertOk());
        $lines = str_getcsv($csv, "\n");

        $this->assertStringContainsString('DESIGNATION MASTER', $lines[1]);
        $this->assertSame(['S. No.', 'Designation Name', 'Status'], str_getcsv($lines[5]));

        $print = $this->fetch('/master/designation/export/print')->assertOk()->getContent();
        $this->assertStringContainsString('window.print()', $print);
        $this->assertStringContainsString('>Designation Name<', $print);

        $this->assertStringStartsWith('%PDF-', $this->bytes($this->fetch('/master/designation/export/pdf')->assertOk()));
        $this->assertStringStartsWith('PK', $this->bytes($this->fetch('/master/designation/export/excel')->assertOk()));

        $narrowed = $this->bytes($this->fetch('/master/designation/export/csv?cols=sno,status'));
        $this->assertSame(['S. No.', 'Status'], str_getcsv(str_getcsv($narrowed, "\n")[5]));

        $filtered = $this->bytes($this->fetch('/master/designation/export/csv?q=zzzznotadesig'));
        $this->assertStringContainsString('Search: zzzznotadesig', $filtered);

        fwrite(STDERR, 'csv rows: ' . (count($lines) - 6) . "\n");
    }

    public function test_unknown_format_is_rejected(): void
    {
        $this->fetch('/master/designation/export/exe')->assertNotFound();
    }

    /**
     * The modal posts over AJAX, so store() must answer in JSON both ways.
     * Wrapped in a transaction: this writes to the real database and must leave
     * nothing behind.
     */
    public function test_modal_create_and_update_actually_save(): void
    {
        $user = $this->actor();
        $before = DesignationMaster::count();

        DB::beginTransaction();

        try {
            $name = 'Gate Desig ' . substr(uniqid(), -6);

            $created = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/designation/store', ['pk' => '', 'designation_name' => $name]);

            $created->assertOk()->assertJson(['status' => true]);

            $row = DesignationMaster::where('designation_name', $name)->first();
            $this->assertNotNull($row, 'the row was not written');

            // Duplicate -> 422 keyed to the field the modal renders errors against.
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/designation/store', ['pk' => '', 'designation_name' => $name])
                ->assertStatus(422)
                ->assertJsonValidationErrors('designation_name');

            // Update through the same route, scoped by the encrypted pk.
            $renamed = 'Gate Desig ' . substr(uniqid(), -6);
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/designation/store', [
                    'pk' => encrypt($row->pk), 'designation_name' => $renamed,
                ])
                ->assertOk()
                ->assertJson(['status' => true]);

            $this->assertSame($renamed, $row->fresh()->designation_name);

            // varchar(100): a longer name must be rejected, not truncated by MySQL.
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/designation/store', ['pk' => '', 'designation_name' => str_repeat('x', 101)])
                ->assertStatus(422)
                ->assertJsonValidationErrors('designation_name');

            fwrite(STDERR, "modal create/update/duplicate/length all handled\n");
        } finally {
            DB::rollBack();
        }

        $this->assertSame($before, DesignationMaster::count(), 'the test left rows behind');
    }
}
