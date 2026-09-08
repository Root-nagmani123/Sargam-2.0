<?php

namespace Tests\Unit;

use App\Support\PdfPageNumbers;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

/**
 * Guards the export PDFs against dompdf's isPhpEnabled coming back.
 *
 * That option is not scoped to the page-number script it was switched on for:
 * it makes the renderer a server-side execution context for the whole view, so
 * any raw block that later appears in an export blade would execute
 * admin-entered data. The page numbers that needed it are produced by
 * PdfPageNumbers on the canvas instead.
 *
 * DB-free: every assertion reads source, or renders a document from fixture
 * rows.
 */
class ExportPdfPhpDisabledTest extends TestCase
{
    /** The export paths this module owns: file => the view each one loads. */
    private const EXPORT_PATHS = [
        'Http/Controllers/RoleController.php' => 'exports.branded_grid_pdf',
        'Http/Controllers/SidebarMenu/MenuController.php' => 'exports.branded_grid_pdf',
        'Http/Controllers/SidebarMenu/MenuGroupController.php' => 'exports.branded_grid_pdf',
        'Http/Controllers/SidebarMenu/SidebarCategoryController.php' => 'exports.branded_grid_pdf',
        'Http/Controllers/Admin/UserController.php' => 'admin.user_management.users.partials.export_pdf',
    ];

    private const EXPORT_BLADES = [
        'exports/branded_grid_pdf.blade.php',
        'admin/user_management/users/partials/export_pdf.blade.php',
    ];

    /**
     * The slice of a controller from the Pdf::loadView that opens one export to
     * the download() that closes it — so an unrelated export elsewhere in the
     * same controller cannot satisfy or break these assertions.
     */
    private function exportBlock(string $relativePath, string $view): string
    {
        $source = file_get_contents(app_path($relativePath));

        $start = strpos($source, "Pdf::loadView('".$view."'");
        $this->assertNotFalse($start, "{$relativePath} should load {$view}");

        $end = strpos($source, '->download(', $start);
        $this->assertNotFalse($end, "{$relativePath} should download the {$view} export");

        return substr($source, $start, $end - $start);
    }

    /** @dataProvider exportPaths */
    public function test_the_pdf_renderer_is_not_a_php_execution_context(string $path, string $view): void
    {
        $block = $this->exportBlock($path, $view);

        $this->assertStringContainsString("'isPhpEnabled' => false", $block, "{$path} must disable PHP in the renderer");
        $this->assertStringNotContainsString("'isPhpEnabled' => true", $block, "{$path} must not re-enable PHP in the renderer");
    }

    /** @dataProvider exportPaths */
    public function test_page_numbers_come_from_the_canvas_stamper(string $path, string $view): void
    {
        $source = file_get_contents(app_path($path));

        $this->assertStringContainsString('PdfPageNumbers', $source, "{$path} should stamp page numbers after render");
    }

    public static function exportPaths(): array
    {
        $sets = [];
        foreach (self::EXPORT_PATHS as $path => $view) {
            $sets[$path] = [$path, $view];
        }

        return $sets;
    }

    /** @dataProvider exportBlades */
    public function test_export_blades_carry_no_in_view_php_and_no_raw_echo(string $blade): void
    {
        $source = file_get_contents(resource_path('views/'.$blade));

        // The construct that made isPhpEnabled look necessary.
        $this->assertStringNotContainsString('text/php', $source);
        // And the construct that would make isPhpEnabled dangerous.
        $this->assertStringNotContainsString('{!!', $source);
    }

    public static function exportBlades(): array
    {
        $sets = [];
        foreach (self::EXPORT_BLADES as $blade) {
            $sets[$blade] = [$blade];
        }

        return $sets;
    }

    /**
     * The security fix must not have cost the feature.
     *
     * With the in-view script gone and PHP disabled, the canvas stamp is the
     * only thing that can produce this text — and the count has to be the real
     * one, which is what dompdf's CSS counter route gets wrong ("of 0").
     */
    public function test_a_rendered_export_still_numbers_every_page(): void
    {
        $rows = [];
        for ($i = 1; $i <= 140; $i++) {
            $rows[] = ['name' => 'Fixture Row '.$i];
        }

        $pdf = Pdf::loadView('exports.branded_grid_pdf', [
            'reportTitle' => 'Page Numbering Fixture',
            'columns' => [
                ['key' => 'sno', 'heading' => 'S.No.', 'value' => fn ($row, $i) => $i + 1],
                ['key' => 'name', 'heading' => 'Name', 'value' => fn ($row) => $row['name']],
            ],
            'rows' => $rows,
            'filterLine' => null,
            'exportDate' => '01-01-2026 10:00 AM',
            'widths' => [],
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);

        $output = PdfPageNumbers::stamp($pdf)->output();

        $pageCount = $pdf->getDomPDF()->getCanvas()->get_page_count();
        $this->assertGreaterThan(1, $pageCount, 'the fixture must span several pages for the count to mean anything');

        // page_text() substitutes the placeholders per page as the document is
        // written out, so the finished file carries the resolved text.
        $this->assertStringNotContainsString('{PAGE_NUM}', $output, 'the placeholders must be resolved, not literal');
        $this->assertStringNotContainsString('of 0', $output, 'the page count must be real');
    }
}
