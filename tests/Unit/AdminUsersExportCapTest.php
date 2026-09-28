<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\UserController;
use ReflectionClass;
use Tests\TestCase;

/**
 * The users export must not render an unbounded sheet in one request.
 *
 * The PDF branch was capped; the print branch was not, on the reasoning that
 * "the browser lays this out itself". That is true of the BROWSER and misses the
 * server: an uncapped print of the whole directory built, held and wrote a
 * 9.6 MB HTML document per request, for ~15,108 rows, at the asking of any user
 * who could reach the route - which, until this change, was all of them.
 *
 * Capping silently would trade one defect for a worse one, so the two things
 * asserted here are that the cap BINDS and that the sheet SAYS it bound.
 *
 * DB-free: the helper takes and returns an array.
 */
class AdminUsersExportCapTest extends TestCase
{
    private function cap(array $rows, int $cap): array
    {
        $method = (new ReflectionClass(UserController::class))->getMethod('capUserExportRows');
        $method->setAccessible(true);

        return $method->invoke(
            (new ReflectionClass(UserController::class))->newInstanceWithoutConstructor(),
            ['rows' => $rows, 'columns' => [], 'headings' => []],
            $cap
        );
    }

    private function constant(string $name): int
    {
        $value = (new ReflectionClass(UserController::class))->getConstant($name);

        $this->assertIsInt($value, "{$name} must exist and be a row count");

        return $value;
    }

    /** Both single-request renderers carry a ceiling. */
    public function test_both_rendered_formats_declare_a_row_cap(): void
    {
        $pdf = $this->constant('ADMIN_USERS_PDF_ROW_CAP');
        $print = $this->constant('ADMIN_USERS_PRINT_ROW_CAP');

        $this->assertGreaterThan(0, $pdf);
        $this->assertGreaterThan(0, $print);

        // user_credentials holds roughly 15k rows, so a cap at or above that
        // would be a cap in name only.
        $this->assertLessThan(15000, $print, 'the print cap must actually bind on this directory');

        // The browser really does lay out more than DomPDF; the two caps are
        // allowed to differ, but not in the wrong direction.
        $this->assertGreaterThanOrEqual($pdf, $print);
    }

    public function test_a_result_under_the_cap_is_untouched_and_carries_no_note(): void
    {
        $result = $this->cap(array_fill(0, 10, ['x']), 100);

        $this->assertCount(10, $result['rows']);
        $this->assertArrayNotHasKey('note', $result);
        $this->assertArrayNotHasKey('totalRows', $result);
    }

    public function test_a_result_over_the_cap_is_truncated_and_reports_the_true_total(): void
    {
        $result = $this->cap(array_fill(0, 9000, ['x']), 5000);

        $this->assertCount(5000, $result['rows'], 'the cap must bind');
        $this->assertSame(9000, $result['totalRows'], 'the sheet must know the true total');
        $this->assertStringContainsString('5,000', $result['note']);
        $this->assertStringContainsString('9,000', $result['note']);
    }

    /**
     * The print branch must actually route through the cap.
     *
     * Read from source because the alternative is rendering a five-thousand-row
     * sheet in a unit test; what is being pinned is that the branch has not
     * drifted back to returning the view directly.
     */
    public function test_the_print_branch_caps_before_it_renders(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));

        // Anchored on the method, not on the branch: UserController carries
        // several exports and more than one of them has an `if ($format ===
        // 'print')`. A search from the top of the file finds the wrong one and
        // this test then passes or fails on unrelated code.
        $method = strpos($source, 'public function export(Request $request, string $format)');
        $this->assertNotFalse($method, 'the users export method should exist');

        $start = strpos($source, "if (\$format === 'print') {", $method);
        $this->assertNotFalse($start, 'the print branch should exist');

        $branch = substr($source, $start, 1200);

        $this->assertStringContainsString('capUserExportRows($reportData, self::ADMIN_USERS_PRINT_ROW_CAP)', $branch,
            'the print branch renders in one request and must be capped like the PDF branch');
    }

    /** The printable sheet renders the truncation note, so a cap is never silent. */
    public function test_the_print_sheet_renders_the_truncation_note(): void
    {
        $blade = file_get_contents(
            resource_path('views/admin/user_management/users/partials/export_print.blade.php')
        );

        $this->assertStringContainsString('$note', $blade,
            'a format that truncates must say so on the sheet');
        $this->assertStringContainsString('$totalRows ?? count($rows)', $blade,
            'Total Records must report the true total, not the capped count');
    }
}
