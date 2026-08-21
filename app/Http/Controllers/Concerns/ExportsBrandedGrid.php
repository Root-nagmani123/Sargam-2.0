<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * CSV / Excel / PDF / Print for an admin listing, off one query and one column
 * list, so the four formats can never disagree about what was exported
 * (docs/new-design-index-page.md §1).
 *
 * A controller supplies the column definitions and the rows; everything below —
 * the ?cols= whitelist, the branded header band, the DomPDF row cap — is shared.
 */
trait ExportsBrandedGrid
{
    /**
     * Rows the PDF lays out before it truncates (with a visible note).
     *
     * Measured on the six-column Members grid (1,833 rows), peak RSS / wall time:
     *
     *            1,000 rows          1,833 rows
     *   DomPDF   276 MB / 10 s       602 MB / 31 s
     *   mPDF     144 MB /  5 s       228 MB / 11 s
     *
     * So DomPDF at this cap already needs ~276 MB, which is a hard OOM fatal on
     * the very common 256 MB production limit — that is uncatchable, so the user
     * gets a blank page rather than an error. Any grid that can reach four
     * figures should pass 'pdfEngine' => 'mpdf'; it costs about half.
     *
     * To raise the cap for one controller, assign in a constructor:
     *
     *     public function __construct() { $this->pdfRowCap = 2500; }
     *
     * NOT by redeclaring the property. A class that uses a trait may not
     * redeclare the trait's typed property with a different default — PHP 8.2
     * makes that a fatal "definition differs and is considered incompatible".
     */
    protected int $pdfRowCap = 1000;

    /**
     * Which columns the export should carry.
     *
     * Intersects the request against the canonical list rather than trusting it,
     * so a hand-edited ?cols= can't reorder the report or inject a column. Empty
     * or absent => every column.
     *
     * @param  array<string, array{heading:string, class:string, value:callable}>  $defs
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    protected function resolveExportColumns(array $defs, Request $request): array
    {
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        // Every column hidden would produce an empty file — fall back to all.
        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    /**
     * Build the response for one of the four formats.
     *
     * @param  string  $format    csv|excel|pdf|print (already validated by the caller)
     * @param  string  $title     report name, e.g. "Members"
     * @param  string  $fileBase  filename stem, e.g. "Members" -> Members_20260816102233.csv
     * @param  array<string, array{heading:string, class:string, value:callable}>  $columns
     * @param  array{columnStyles?:string, emptyText?:string, centeredKeys?:list<string>, textKeys?:list<string>, pdfEngine?:'dompdf'|'mpdf'}  $options
     */
    protected function brandedGridResponse(
        string $format,
        string $title,
        string $fileBase,
        Collection $rows,
        array $columns,
        ?string $filterLine = null,
        array $options = []
    ) {
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');
        $columnStyles = $options['columnStyles'] ?? '';
        $emptyText = $options['emptyText'] ?? 'Nothing to export';

        if ($format === 'print') {
            return view('admin.exports.branded_print', [
                'title' => $title,
                'columns' => $columns,
                'rows' => $rows,
                'total' => $rows->count(),
                'filterLine' => $filterLine,
                'exportDate' => $exportDate,
                'columnStyles' => $columnStyles,
                'emptyText' => $emptyText,
                'note' => null,
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new BrandedGridExport(
                    $title,
                    $rows,
                    $columns,
                    $exportDate,
                    $filterLine,
                    $options['centeredKeys'] ?? [],
                    $options['textKeys'] ?? []
                ),
                $fileBase . '_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            // Past the cap the PDF still downloads, and SAYS what it dropped — a
            // silent truncation would read as a complete report.
            $total = $rows->count();
            $capped = $total > $this->pdfRowCap;
            $note = $capped
                ? 'Showing the first ' . number_format($this->pdfRowCap) . ' of ' . number_format($total)
                    . ' records. Use the CSV or Excel download for the complete list.'
                : null;

            $engine = ($options['pdfEngine'] ?? 'dompdf') === 'mpdf' ? 'mpdf' : 'dompdf';

            $viewData = [
                'title' => $title,
                'columns' => $columns,
                'rows' => $capped ? $rows->take($this->pdfRowCap) : $rows,
                'total' => $total,
                'note' => $note,
                'filterLine' => $filterLine,
                'exportDate' => $exportDate,
                'columnStyles' => $columnStyles,
                'emptyText' => $emptyText,
                'engine' => $engine,
            ];

            if ($engine === 'mpdf') {
                return $this->brandedGridMpdf($viewData, $fileBase . '_' . $stamp . '.pdf');
            }

            return Pdf::loadView('admin.exports.branded_pdf', $viewData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script in the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download($fileBase . '_' . $stamp . '.pdf');
        }

        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));

        // Same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the applied filters too.
        $csvBand = ExportCsvHeader::rows($title, $filterLine, $exportDate, $rows->count());

        return response()->streamDownload(function () use ($columns, $header, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(
                    fn ($col) => $col['value']($row, $index),
                    $columns
                )));
            }

            fclose($handle);
        }, $fileBase . '_' . $stamp . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Render the SAME branded_pdf view through mPDF instead of DomPDF.
     *
     * Only for grids carrying a complex script (Devanagari and the other Indic
     * ones). DomPDF has no text shaper: it draws the codepoints in logical order,
     * so a matra that belongs before its consonant stays after it and a virama
     * silently vanishes — "अन्य पिछड़ा वर्ग" comes out as "अनय पछिडा वरग". That
     * reads as real Hindi while saying something else, which is worse than the
     * blank cells it replaces. Embedding a Devanagari font does NOT fix it; the
     * shaping is the missing piece, not the glyphs.
     *
     * mPDF ships an Indic shaper. autoScriptToLang tags each run by script and
     * autoLangToFont then picks a face that covers it, so no font has to be
     * registered here and Latin runs keep the DejaVu look of every other export.
     *
     * DomPDF stays the default: it is faster, and switching the engine shifts
     * metrics slightly, so a grid with no complex script has nothing to gain.
     */
    private function brandedGridMpdf(array $viewData, string $filename)
    {
        // mPDF writes font subsets here; storage/ is not in git, so on a fresh
        // deploy the directory may not exist yet.
        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // mPDF preprocesses the whole document with PCRE and refuses outright
        // once the HTML exceeds pcre.backtrack_limit (1 MB by default):
        // "The HTML code size is larger than pcre.backtrack_limit". A listing of
        // any size blows past that — the Members grid is ~1.9 MB, most of it the
        // base64 letterhead. Raising it only for this request is cheaper than
        // chunking WriteHTML(), which would break the table across calls.
        if ((int) ini_get('pcre.backtrack_limit') < 50000000) {
            ini_set('pcre.backtrack_limit', '50000000');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            // The pair that makes Hindi render correctly — see the docblock.
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            // autoLangToFont picks the face per script and would otherwise land
            // the Latin runs on mPDF's serif default, so this report would come
            // out serif while every DomPDF one is sans. Devanagari is unaffected:
            // dejavusans does not cover it, so the shaper still switches faces.
            'default_font' => 'dejavusans',
            // Match branded_pdf's @page (12mm 10mm), which mPDF only partly reads,
            // plus room for the page-number footer below the bottom margin.
            'margin_top' => 12,
            'margin_bottom' => 16,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_footer' => 6,
        ]);

        // DomPDF gets its page numbers from the script block in the view; mPDF
        // cannot run that, so it sets the same footer here instead.
        $mpdf->SetHTMLFooter(
            '<div style="text-align:right; font-family:sans-serif; font-size:7pt; color:#6b7280;">'
            . 'Page {PAGENO} of {nbpg}</div>'
        );

        $mpdf->WriteHTML(view('admin.exports.branded_pdf', $viewData)->render());

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
