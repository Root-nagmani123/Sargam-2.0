<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

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
     * Measured against DomPDF, which costs roughly 0.27 MB and 16 ms per row on
     * a six-column A4 table: 1,000 rows peaks near 272 MB, and about 1,800 rows
     * exhausts a 512 MB process outright. Override per controller if a report's
     * rows are much narrower or wider.
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
     * @param  array{columnStyles?:string, emptyText?:string, centeredKeys?:list<string>, textKeys?:list<string>}  $options
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

            return Pdf::loadView('admin.exports.branded_pdf', [
                'title' => $title,
                'columns' => $columns,
                'rows' => $capped ? $rows->take($this->pdfRowCap) : $rows,
                'total' => $total,
                'note' => $note,
                'filterLine' => $filterLine,
                'exportDate' => $exportDate,
                'columnStyles' => $columnStyles,
                'emptyText' => $emptyText,
            ])
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
}
