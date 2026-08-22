<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Employee Type Master: the modernised listing chrome and its four exports.
 * Mirrors MemberExportFormatsTest — both now run through
 * {@see \App\Http\Controllers\Concerns\ExportsBrandedGrid}, so this also guards
 * the shared trait against a second consumer breaking the first.
 */
class EmployeeTypeMasterTest extends TestCase
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
        $html = $this->fetch('/master/employee-type')->assertOk()->getContent();

        foreach ([
            'employee-type-page',
            'programme-dt-table',
            'programme-dt-panel',
            'data-dt-search-for="employeetypemaster-table"',
            'data-dt-footer-for="employeetypemaster-table"',
            'etmColumnToggleGrid',
            'etmDownloadBtn',
            'etmPrintBtn',
            'Add Employee Type',
            'status-msg',
            'mst-page',
            'master-lookup-admin.css',
            'mst-modal',
            'mst-modal-body',
            'mst-btn-submit',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "missing: {$needle}");
        }

        // The legacy header block and the hand-rolled DataTables dom are gone.
        $this->assertStringNotContainsString('float-end gap-2', $html);
        $this->assertStringNotContainsString('"dom":"frtip"', $html);

        fwrite(STDERR, "\nindex OK (" . strlen($html) . " bytes)\n");
    }

    public function test_grid_feed_renders_badge_and_action_stack(): void
    {
        $columns = [];
        foreach (['DT_RowIndex', 'category_type_name', 'status', 'action'] as $i => $name) {
            $columns[$i] = [
                'data' => $name,
                'name' => $name,
                'searchable' => $name === 'category_type_name' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $json = $this->actingAs($this->actor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/employee-type?' . http_build_query([
                'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns, 'order' => [],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json();

        $row = $json['data'][0] ?? null;
        if (! $row) {
            $this->markTestSkipped('no employee types in this database');
        }

        // Status is a display-only badge…
        // Soft badge with data-order, per docs/new-design-index-page.md §3b.
        $this->assertMatchesRegularExpression(
            '/status-pill badge rounded-1 bg-(success|danger)-subtle/',
            $row['status']
        );
        $this->assertMatchesRegularExpression('/data-order="[01]"/', $row['status']);
        $this->assertStringNotContainsString('status-toggle', $row['status'], 'the switch belongs in Action');

        // …and the switch lives in the action group beside Edit.
        $this->assertStringContainsString('mst-act-group', $row['action']);
        // Icon-over-label stacks, and the caption names the ACTION not the state.
        $this->assertStringContainsString('mst-act__label', $row['action']);
        $this->assertMatchesRegularExpression('/>(Deactivate|Activate)</', $row['action']);
        // §3b trap 1: the switch must NOT sit in a .form-check.form-switch wrapper.
        $this->assertStringNotContainsString('form-switch', $row['action']);
        $this->assertStringContainsString('status-toggle', $row['action']);
        $this->assertStringContainsString('data-table="employee_type_master"', $row['action']);
        $this->assertStringContainsString('bi-pencil', $row['action']);

        fwrite(STDERR, sprintf("grid: total=%s rows=%d\n", $json['recordsTotal'], count($json['data'])));
    }

    public function test_exports_share_one_column_list(): void
    {
        $csv = $this->bytes($this->fetch('/master/employee-type/export/csv')->assertOk());
        $lines = str_getcsv($csv, "\n");

        $this->assertStringContainsString('LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION', $lines[0]);
        $this->assertStringContainsString('EMPLOYEE TYPE MASTER', $lines[1]);
        $this->assertSame(['S. No.', 'Category Type Name', 'Status'], str_getcsv($lines[5]));

        $print = $this->fetch('/master/employee-type/export/print')->assertOk()->getContent();
        $this->assertStringContainsString('window.print()', $print);
        $this->assertStringContainsString('>Category Type Name<', $print);
        $this->assertStringContainsString('Employee Type Master', $print);

        $pdf = $this->fetch('/master/employee-type/export/pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $this->bytes($pdf));

        $excel = $this->fetch('/master/employee-type/export/excel')->assertOk();
        $this->assertStringStartsWith('PK', $this->bytes($excel));

        fwrite(STDERR, 'csv rows: ' . (count($lines) - 6) . "\n");
    }

    public function test_search_and_hidden_columns_reach_the_exports(): void
    {
        $all = $this->bytes($this->fetch('/master/employee-type/export/csv'));
        $filtered = $this->bytes($this->fetch('/master/employee-type/export/csv?q=zzzznotatype'));

        $this->assertStringContainsString('Search: zzzznotatype', $filtered);
        $this->assertLessThan(substr_count($all, "\n"), substr_count($filtered, "\n"));

        $narrowed = $this->bytes($this->fetch('/master/employee-type/export/csv?cols=sno,status'));
        $this->assertSame(['S. No.', 'Status'], str_getcsv(str_getcsv($narrowed, "\n")[5]));

        // A hand-edited ?cols= can neither reorder nor inject a column.
        $tampered = $this->bytes($this->fetch('/master/employee-type/export/csv?cols=status,sno,evil'));
        $this->assertSame(['S. No.', 'Status'], str_getcsv(str_getcsv($tampered, "\n")[5]));
    }

    public function test_unknown_format_is_rejected(): void
    {
        $this->fetch('/master/employee-type/export/exe')->assertNotFound();
    }

    /**
     * The Add/Edit modal posts over AJAX, so store() has to answer in JSON for
     * both outcomes. Wrapped in a transaction: this writes to the real database
     * and must leave nothing behind.
     */
    public function test_modal_create_and_update_answer_in_json(): void
    {
        $user = $this->actor();

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $name = 'Gate Probe Type ' . uniqid();

            // Create
            $created = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-type/store', ['pk' => '', 'employee_type_name' => $name]);

            $created->assertOk()->assertJson(['status' => true]);
            $this->assertStringContainsString('created', strtolower($created->json('message')));

            $row = \App\Models\EmployeeTypeMaster::where('category_type_name', $name)->first();
            $this->assertNotNull($row, 'the row was not written');

            // Duplicate name -> 422 with the field keyed error the modal renders
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-type/store', ['pk' => '', 'employee_type_name' => $name])
                ->assertStatus(422)
                ->assertJsonValidationErrors('employee_type_name');

            // Update through the same route, scoped by the encrypted pk
            $renamed = $name . ' (edited)';
            $updated = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-type/store', [
                    'pk' => encrypt($row->pk),
                    'employee_type_name' => $renamed,
                ]);

            $updated->assertOk()->assertJson(['status' => true]);
            $this->assertStringContainsString('updated', strtolower($updated->json('message')));
            $this->assertSame($renamed, $row->fresh()->category_type_name);

            // Its own name must not trip the unique rule on re-save.
            $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                ->postJson('/master/employee-type/store', [
                    'pk' => encrypt($row->pk),
                    'employee_type_name' => $renamed,
                ])
                ->assertOk();

            fwrite(STDERR, "modal create/update/duplicate all answered in JSON\n");
        } finally {
            \Illuminate\Support\Facades\DB::rollBack();
        }

        $this->assertSame(
            0,
            \App\Models\EmployeeTypeMaster::where('category_type_name', 'like', 'Gate Probe Type %')->count(),
            'the test left rows behind'
        );
    }

    /** The standalone create page still posts normally and still redirects. */
    public function test_non_ajax_post_still_redirects(): void
    {
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $this->actingAs($this->actor())
                ->post('/master/employee-type/store', ['pk' => '', 'employee_type_name' => 'Gate Probe Plain ' . uniqid()])
                ->assertRedirect(route('master.employee.type.index'))
                ->assertSessionHas('success');
        } finally {
            \Illuminate\Support\Facades\DB::rollBack();
        }
    }
}
