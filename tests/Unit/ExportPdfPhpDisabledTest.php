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

    /** The multi-page fixture both halves of the page-number test render. */
    private function pageNumberFixture()
    {
        $rows = [];
        for ($i = 1; $i <= 140; $i++) {
            $rows[] = ['name' => 'Fixture Row '.$i];
        }

        return Pdf::loadView('exports.branded_grid_pdf', [
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
    }

    /**
     * Count text-drawing blocks in a PDF's content streams.
     *
     * Every piece of text dompdf draws is one BT ... ET block, and the content
     * streams are Flate-compressed, so they have to be inflated first — which is
     * exactly why a naive assertStringContainsString() on the finished file can
     * never see any of this.
     */
    private function textBlocksIn(string $pdf): int
    {
        $plainText = '';

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            foreach ($matches[1] as $chunk) {
                $inflated = @gzuncompress($chunk);

                if ($inflated === false) {
                    $inflated = @gzinflate($chunk);
                }

                if ($inflated !== false) {
                    $plainText .= $inflated;
                }
            }
        }

        return preg_match_all('/\bBT\b/', $plainText);
    }

    /**
     * The security fix must not have cost the feature.
     *
     * With the in-view script gone and PHP disabled, the canvas stamp is the
     * only thing that can produce the page number — and the count has to be the
     * real one, which is what dompdf's CSS counter route gets wrong ("of 0").
     *
     * This asserts the stamp DIFFERENTIALLY: the same fixture is rendered with
     * and without stamp(), and the stamped file must carry exactly one extra
     * text block per page.
     *
     * The two string assertions this replaces could not fail. dompdf Flate-
     * compresses its content streams, so no text in the document is ever a
     * literal substring of the file — "{PAGE_NUM}" and "of 0" were absent from
     * an UNSTAMPED render too, and the test passed whether or not the stamp
     * ran. Nor can the text be recovered by inflating: the embedded DejaVu Sans
     * subset encodes glyph ids, not characters, so "Page 1 of 4" does not appear
     * even in the decompressed stream. Counting the blocks is what is actually
     * observable, and it is enough: remove the stamp and the count drops by one
     * per page.
     */
    public function test_a_rendered_export_still_numbers_every_page(): void
    {
        $stampedPdf = $this->pageNumberFixture();
        $stamped    = PdfPageNumbers::stamp($stampedPdf)->output();

        $pageCount = $stampedPdf->getDomPDF()->getCanvas()->get_page_count();
        $this->assertGreaterThan(1, $pageCount, 'the fixture must span several pages for the count to mean anything');

        // The same document, rendered without the stamp.
        $unstampedPdf = $this->pageNumberFixture();
        $unstampedPdf->render();
        $unstamped = $unstampedPdf->output();

        $this->assertSame(
            $pageCount,
            $this->textBlocksIn($stamped) - $this->textBlocksIn($unstamped),
            'stamp() must add exactly one piece of text to every page'
        );
    }
}
