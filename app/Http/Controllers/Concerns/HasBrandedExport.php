<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Shared LBSNAA branded CSV / PDF / Print export (new-design-index-page.md §4b).
 *
 * Every index page's Download (CSV·PDF) + Print produces the SAME academy document —
 * logos + Hindi/English academy name + course/batch line + blue report title + blue
 * table header — via the shared view resources/views/exports/lbsnaa-report.blade.php.
 *
 * A controller `use`s this trait and calls brandedExport($format, $title, $headings, $rows,
 * $filenameBase) from a per-list export method. Read-only; never touches create/update logic.
 */
trait HasBrandedExport
{
    protected function brandedExport(string $format, string $reportTitle, array $headings, array $rows, string $filenameBase)
    {
        $subtitle  = 'IAS Professional Course, Phase - I (2025 Batch)';
        $subtitle2 = '(8 December 2025 to 17 April, 2026)';

        if ($format === 'csv') {
            $filename = $filenameBase . '-' . date('Ymd_His') . '.csv';
            $colCount = max(count($headings), 1);
            return response()->streamDownload(function () use ($headings, $rows, $reportTitle, $subtitle, $subtitle2, $colCount) {
                $out = fopen('php://output', 'w');
                // UTF-8 BOM so Excel renders the Hindi academy name (and any non-ASCII data) correctly.
                fwrite($out, "\xEF\xBB\xBF");
                // Branded header block — same lines/order as the PDF/Print header (logos aside,
                // which a plain .csv cannot carry). Each line is padded across all columns so it
                // spans the sheet width like the design's centred header.
                $span = static function (string $text) use ($colCount) {
                    return array_merge([$text], array_fill(0, $colCount - 1, ''));
                };
                fputcsv($out, $span('लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी'));
                fputcsv($out, $span('Lal Bahadur Shastri National Academy of Administration, Mussoorie'));
                fputcsv($out, $span($subtitle));
                fputcsv($out, $span($subtitle2));
                fputcsv($out, $span($reportTitle));
                fputcsv($out, []);
                fputcsv($out, $headings);
                foreach ($rows as $r) { fputcsv($out, $r); }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $data = array_merge($this->exportAssets(), [
            'reportTitle' => $reportTitle,
            'subtitle'    => $subtitle,
            'subtitle2'   => $subtitle2,
            'headings'    => $headings,
            'rows'        => $rows,
            'printedOn'   => now()->format('d-m-Y H:i'),
        ]);

        if ($format === 'print') {
            return view('exports.lbsnaa-report', array_merge($data, ['autoPrint' => true]));
        }

        // DomPDF is memory/CPU-hungry on large lists (e.g. City ~1.6k rows peaks ~700MB).
        // Match the app's existing heavy-export convention (Calendar/Feedback controllers).
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        return Pdf::loadView('exports.lbsnaa-report', $data)
            ->setPaper('a4', 'landscape')
            ->download($filenameBase . '-' . date('Ymd_His') . '.pdf');
    }

    /** LBSNAA logo/title data-URIs for the branded export chrome. */
    protected function exportAssets(): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) return null;
            $raw = @file_get_contents($path);
            if ($raw === false) return null;
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) { 'svg' => 'image/svg+xml', 'jpg', 'jpeg' => 'image/jpeg', default => 'image/png' };
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };
        $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
        if (! is_file($rightLogo)) $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
        return [
            'logoLeft'   => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'  => $toDataUri($rightLogo),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];
    }
}
