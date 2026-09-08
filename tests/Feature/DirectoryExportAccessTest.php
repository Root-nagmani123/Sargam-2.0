<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DirectoryController;
use App\Models\EmployeeMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Tests\TestCase;

/**
 * The half of the directory export guarantees that only a database can prove.
 *
 * DirectoryExportGuardTest covers the resolvers and the gate without a
 * connection; these cases need real rows: that a privileged user actually
 * receives a file, that the pk-0 section filter narrows the result AND matches
 * the header band, that every download leaves an audit line, and that the row
 * cap bounds the SELECT rather than PHP.
 *
 * Skips — never fails — when the application database is unreachable, per the
 * suite convention in phpunit.xml. Writes happen inside a transaction that is
 * always rolled back: this suite runs against the development schema.
 */
class DirectoryExportAccessTest extends TestCase
{
    /** department_master really holds this row; it is the reason 0 cannot mean "absent". */
    private const NIAR_SECTION_PK = 0;

    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('directory export tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /**
     * Sign in as a Super Admin.
     *
     * hasRole() reads the session before the role tables, and that is a real
     * production path (login writes user_roles into the session), so the actor
     * needs no particular role row to stand for a privileged user.
     */
    private function actAsSuperAdmin(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        $this->actingAs($user);
        session(['user_roles' => ['Super Admin']]);
    }

    /**
     * One active employee in the pk-0 section, so that section is offered by the
     * dropdown and can be filtered on. Rolled back with the test.
     */
    private function employeeInNiar(): string
    {
        // last_name is varchar(20), so the unique marker has to stay short.
        $surname = 'Gate' . substr(uniqid(), -8);

        DB::table('employee_master')->insert([
            'first_name' => 'Directory',
            'last_name' => $surname,
            'status' => 1,
            'department_master_pk' => self::NIAR_SECTION_PK,
            'officalemail' => 'directory.gatetest@example.invalid',
            'mobile' => '9000000000',
        ]);

        return $surname;
    }

    public function test_the_pk_zero_section_is_a_real_row(): void
    {
        $section = DB::table('department_master')->where('pk', self::NIAR_SECTION_PK)->first();

        $this->assertNotNull($section, 'the premise of the null-sentinel fix: pk 0 is a real department');
        $this->assertSame('NIAR', $section->department_name);
    }

    /**
     * The grid narrows to the pk-0 section instead of ignoring the filter.
     *
     * Under the 0-as-"absent" sentinel this returned every active employee.
     */
    public function test_filtering_on_the_pk_zero_section_narrows_the_grid(): void
    {
        $this->actAsSuperAdmin();
        $surname = $this->employeeInNiar();

        $unfiltered = $this->getJson(route('admin.directory.lbsnaa.data'))->json('recordsFiltered');
        $filtered = $this->getJson(route('admin.directory.lbsnaa.data', ['section' => self::NIAR_SECTION_PK]));

        $filtered->assertOk();
        $this->assertSame(1, $filtered->json('recordsFiltered'), 'only the NIAR employee belongs to section 0');
        $this->assertGreaterThan(1, $unfiltered, 'the unfiltered grid must be the wider set');
        $this->assertStringContainsString($surname, json_encode($filtered->json('data')));
    }

    /**
     * The header band and the rows must describe the same set: the band naming
     * "NIAR" over a dump of every employee was the defect.
     */
    public function test_the_export_band_and_the_rows_agree_on_the_pk_zero_section(): void
    {
        $this->actAsSuperAdmin();
        $surname = $this->employeeInNiar();

        $response = $this->get(route('admin.directory.lbsnaa.export', ['format' => 'csv', 'section' => self::NIAR_SECTION_PK]));
        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Section: NIAR', $csv, 'the band names the filter');
        $this->assertSame(1, substr_count($csv, $surname), 'the file holds that section, once');

        // chr(10), not PHP_EOL: fputcsv writes LF, and on Windows PHP_EOL is
        // CRLF — counting that would score every file as one line and pass
        // whatever the export contained.
        $lines = substr_count(trim($csv), chr(10)) + 1;

        $this->assertGreaterThan(1, $lines, 'sanity: the CSV is more than one line');
        $this->assertLessThan(
            20,
            $lines,
            'a header band plus one employee — not the whole directory under a NIAR label'
        );
    }

    /** A Super Admin is served the file: the allowed side of the export gate. */
    public function test_a_super_admin_receives_the_export(): void
    {
        $this->actAsSuperAdmin();

        $this->get(route('admin.directory.lbsnaa.export', ['format' => 'csv']))->assertOk();
        $this->get(route('admin.directory.ot.export', ['format' => 'csv']))->assertOk();
    }

    /** Every download of personal data leaves one structured line — and no row data. */
    public function test_an_export_writes_an_audit_line(): void
    {
        $this->actAsSuperAdmin();
        $surname = $this->employeeInNiar();

        Log::spy();

        $this->get(route('admin.directory.lbsnaa.export', [
            'format' => 'csv', 'section' => self::NIAR_SECTION_PK,
        ]))->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = null) use ($surname) {
            if ($message !== 'directory.export' || ! is_array($context)) {
                return false;
            }

            return $context['grid'] === 'lbsnaa'
                && $context['format'] === 'csv'
                && $context['rows'] === 1
                && $context['capped'] === false
                && $context['filters'] === 'Section: NIAR'
                && ! str_contains(json_encode($context), $surname);
        })->once();
    }

    /**
     * The cap bounds the SELECT, not just the rendered file: the export must ask
     * for EXPORT_ROW_CAP + 1 rows rather than hydrating the whole directory and
     * truncating afterwards.
     */
    public function test_the_export_query_is_bounded_by_the_row_cap(): void
    {
        $this->actAsSuperAdmin();

        $statements = [];
        DB::listen(function ($query) use (&$statements) {
            $statements[] = $query->sql;
        });

        $this->get(route('admin.directory.lbsnaa.export', ['format' => 'csv']))->assertOk();

        $bounded = array_filter($statements, fn ($sql) => str_contains($sql, 'from `employee_master`')
            && str_contains($sql, 'limit 1501'));

        $this->assertNotEmpty($bounded, 'the export SELECT must carry limit 1501 (EXPORT_ROW_CAP + 1)');
    }

    /**
     * The truncation branch itself, on a query that really does exceed the
     * cap: 1,500 rows come back and the note reports the TRUE total, not the
     * 1,501 that were fetched.
     *
     * employee_master unfiltered is the only table in the change path that is
     * reliably over the cap; the directory's own status = 1 scope is not.
     */
    public function test_a_query_over_the_cap_is_truncated_and_reports_the_true_total(): void
    {
        $total = EmployeeMaster::query()->count();

        if ($total <= 1501) {
            $this->markTestSkipped('employee_master is under the export cap here');
        }

        $method = (new ReflectionClass(DirectoryController::class))->getMethod('fetchCappedExportRows');
        $method->setAccessible(true);

        [$rows, $note] = $method->invoke(new DirectoryController(), EmployeeMaster::query());

        $this->assertCount(1500, $rows);
        $this->assertStringContainsString(number_format($total), $note);
        $this->assertStringNotContainsString('1,501', $note);
    }
}
