<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\MasterGridExport;
use App\Support\ExportCellValue;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Master module's four-format export (CSV | Excel | PDF | Print).
 *
 * A listing controller supplies a keyed column definition and the rows; this
 * trait renders all four formats off that ONE definition, so the report a user
 * prints can't drift from the one they download. Columns are keyed, never
 * positional, which is what lets a grid's Columns modal drop a column from
 * every format at once.
 *
 * A column definition entry looks like:
 *
 *     'status' => [
 *         'heading' => 'Status',
 *         'width'   => '20%',            // print/PDF column width
 *         'align'   => 'center',         // 'left' (default) | 'center' | 'right'
 *         'value'   => fn ($row, int $index) => …,
 *     ]
 *
 * See docs/new-design-index-page.md §1 for the surrounding page chrome.
 */
trait ExportsMasterGrid
{
    /** The formats /export/{format} accepts. */
    protected static array $exportFormats = ['csv', 'excel', 'pdf', 'print'];

    /**
     * Which columns this export should carry.
     *
     * Intersects ?cols= against the canonical list rather than trusting it, so a
     * hand-edited query string can't reorder the report or inject a column.
     * Empty or absent => every column.
     *
     * @param  array<string, array<string, mixed>>  $defs
     * @return array<string, array<string, mixed>>
     */
    protected function resolveExportColumns(Request $request, array $defs): array
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
     * Render one report in one of the four formats.
     *
     * @param  array<string, array<string, mixed>>  $columns  already resolved
     * @param  string  $slug  filename stem, e.g. 'FacultyExpertise'
     * @param  string  $orientation  'portrait' (default) or 'landscape' — a grid
     *                 with more than ~5 columns needs landscape or the print and
     *                 PDF crowd. Applies to those two formats only.
     */
    protected function renderMasterExport(
        string $format,
        Collection $rows,
        array $columns,
        string $reportTitle,
        string $slug,
        ?string $filterLine,
        string $emptyText = 'Nothing to export',
        string $orientation = 'portrait'
    ): Response {
        $orientation = $orientation === 'landscape' ? 'landscape' : 'portrait';
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp      = now()->format('YmdHis');

        $payload = [
            'reportTitle' => $reportTitle,
            'columns'     => $columns,
            'rows'        => $rows,
            'filterLine'  => $filterLine,
            'exportDate'  => $exportDate,
            'emptyText'   => $emptyText,
            'orientation' => $orientation,
        ];

        if ($format === 'print') {
            return response()->view('admin.master.partials.export_print', $payload);
        }

        if ($format === 'excel') {
            return Excel::download(
                new MasterGridExport($rows, $columns, $reportTitle, $exportDate, $filterLine),
                $slug . '_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            // DomPDF lays the whole document out in memory before it writes a byte:
            // the Faculty grid alone measured ~9 s and ~260 MB at 668 rows. Without
            // this the export fatals on a 128M default or trips a short
            // max_execution_time, which reads to the user as a broken download.
            // Same guard the other heavy DomPDF exports use (CalendarController,
            // AttendanceController); both calls are silenced because a hardened
            // php.ini may disable them and that must not break the export.
            @ini_set('memory_limit', '512M');
            @set_time_limit(300);

            return Pdf::loadView('admin.master.partials.export_pdf', $payload)
                ->setPaper('a4', $orientation)
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled'         => true,
                ])
                ->download($slug . '_' . $stamp . '.pdf');
        }

        $header  = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $csvBand = ExportCsvHeader::rows($reportTitle, $filterLine, $exportDate, $rows->count());

        return response()->streamDownload(function () use ($columns, $header, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                // ExportCellValue::safe() so a stored value beginning with = + - @
                // arrives in Excel as text rather than as a live formula.
                fputcsv($handle, array_values(array_map(
                    fn ($col) => ExportCellValue::safe($col['value']($row, $index)),
                    $columns
                )));
            }

            fclose($handle);
        }, $slug . '_' . $stamp . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
