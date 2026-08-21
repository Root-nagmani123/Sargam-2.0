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

    /** The effective memory_limit in bytes; -1 when unlimited. */
    private static function memoryLimitInBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || $raw === '-1') {
            return -1;
        }

        $unit  = strtolower(substr($raw, -1));
        $value = (int) $raw;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * A readable dead end instead of a fatal, when the PDF cannot fit in memory.
     *
     * Deliberately a self-contained response rather than a redirect: the admin
     * layout renders no flash-message region, so `back()->with('error', ...)`
     * would drop the user back on the grid with nothing shown at all.
     */
    private function exportTooLargeResponse(int $rowCount, int $needed, int $limit): Response
    {
        $mb = fn (int $bytes) => number_format($bytes / 1048576) . ' MB';

        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Export too large — LBSNAA</title><style>'
            . 'body{font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;'
            . 'color:#1f2937;background:#f5f7fa;margin:0;padding:48px 20px}'
            . '.card{max-width:640px;margin:0 auto;background:#fff;border:1px solid #e2e7ee;'
            . 'border-radius:10px;padding:28px 30px}'
            . 'h1{margin:0 0 12px;font-size:1.15rem;color:#004384}'
            . 'code{background:#f2f5f9;padding:.1em .4em;border-radius:4px;font-size:.9em}'
            . 'a{color:#004384}</style></head><body><div class="card">'
            . '<h1>This report is too large to render as a PDF here</h1>'
            . '<p>The PDF needs roughly <strong>' . $mb($needed) . '</strong> for '
            . number_format($rowCount) . ' rows, and this server allows <strong>' . $mb($limit) . '</strong>.</p>'
            . '<p>Nothing was lost. Use <strong>Excel</strong> or <strong>CSV</strong> from the same Download menu — '
            . 'they carry identical columns at a fraction of the cost — or narrow the grid with a search or the '
            . 'Columns modal and export the PDF again.</p>'
            . '<p><a href="javascript:history.back()">&larr; Back to the report</a></p>'
            . '</div></body></html>';

        return response($html, 507);
    }

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

            // ini_set can be refused by a hardened php.ini, and then the export
            // dies mid-stream with a blank page and a fatal in the log. Measured
            // on the Faculty grid: ~64 MB fixed plus ~0.35 MB per row. If the
            // limit we actually ended up with cannot hold the job, say so — the
            // CSV and Excel forms of the same report cost a fraction of this.
            $needed = 64 * 1024 * 1024 + (int) ($rows->count() * 0.35 * 1024 * 1024);
            $limit  = self::memoryLimitInBytes();

            if ($limit > 0 && $limit < $needed) {
                return $this->exportTooLargeResponse($rows->count(), $needed, $limit);
            }

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
