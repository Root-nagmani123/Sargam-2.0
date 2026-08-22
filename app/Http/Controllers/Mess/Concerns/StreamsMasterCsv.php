<?php

namespace App\Http\Controllers\Mess\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The `?format=csv` branch of every Mess master export.
 *
 * Each master controller already whitelists 'csv' alongside 'excel' / 'xlsx' /
 * 'pdf', but before this trait existed every one of them fell through to the
 * styled-workbook branch and handed back an .xlsx under whatever name the
 * browser guessed. Asking for CSV silently produced a spreadsheet.
 *
 * CSV is the one export that deliberately carries no branding. The .xlsx and the
 * PDF share the LBSNAA header block, the blue title band and the zebra rows
 * because they are read as documents; a CSV is read by another program, so the
 * emblem rows that {@see \Maatwebsite\Excel\Concerns\WithEvents} injects above
 * the data would land as junk lines in front of the header row and break every
 * parser pointed at it. This writes the grid and nothing else.
 *
 * What it does keep is the column contract the rest of the export layer honours:
 * the headings and rows come from the same `activeHeadings()` / `pdfRows()` pair
 * the PDF renders, so a CSV taken with three columns hidden holds exactly those
 * three columns — the export never disagrees with the screen it was taken from.
 *
 * @see \App\Exports\StoreMasterExport
 */
trait StreamsMasterCsv
{
    /**
     * Stream the export's active columns as a plain RFC 4180 CSV.
     *
     * Streamed rather than buffered: the selling-voucher and purchase-order
     * grids export tens of thousands of rows, and holding the whole file in
     * memory to hand it to a string response is what makes the PDF branch
     * fragile at that size.
     *
     * @param  object  $export     Any mess master export exposing activeHeadings() + pdfRows().
     * @param  string  $fileName   Base name, without extension.
     */
    protected function streamMasterCsv(object $export, string $fileName): StreamedResponse
    {
        $headings = method_exists($export, 'activeHeadings') ? $export->activeHeadings() : [];
        $rows = method_exists($export, 'pdfRows') ? $export->pdfRows() : collect();

        return response()->stream(
            function () use ($headings, $rows) {
                $handle = fopen('php://output', 'wb');

                // Excel reads a bare UTF-8 CSV as the system codepage and turns
                // every non-ASCII name into mojibake. The BOM is what makes it
                // pick UTF-8, and every other CSV reader skips it.
                fwrite($handle, "\xEF\xBB\xBF");

                if ($headings !== []) {
                    fputcsv($handle, $headings);
                }

                foreach ($rows as $row) {
                    // Name cells carry a second line ("Code: …") for the PDF's
                    // two-line layout. fputcsv quotes them, so the newline stays
                    // inside one field instead of splitting the record.
                    fputcsv($handle, array_map(
                        static fn ($value) => is_scalar($value) || $value === null ? (string) $value : '',
                        (array) $row
                    ));
                }

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
                // Without this the grid's own no-store headers can still let a
                // proxy hand back yesterday's export for the same query string.
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }
}
