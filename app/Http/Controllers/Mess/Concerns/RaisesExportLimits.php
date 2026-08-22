<?php

namespace App\Http\Controllers\Mess\Concerns;

/**
 * Memory and time headroom for the heavy Mess exports.
 *
 * Every PDF branch in this module used to open with a bare
 * `@ini_set('memory_limit', '512M')`. `ini_set` assigns — it does not raise — so
 * on a server configured with 1G or more that line *lowered* the ceiling and
 * made the export fail on data it would otherwise have rendered. The Selling
 * Voucher PDF fatals in DomPDF's Stylesheet.php this way: given 3G on the
 * command line it still died at exactly 536870912 bytes, the 512M the controller
 * had just imposed on itself.
 *
 * These helpers move in one direction only. A host that grants more keeps it; a
 * host that grants less gets topped up to the figure the export needs.
 */
trait RaisesExportLimits
{
    /**
     * Raise the memory limit to at least $target, never below what is already set.
     *
     * An unlimited limit (-1) is left alone — it is already the highest there is.
     *
     * @param  string  $target  A PHP shorthand size such as '512M'.
     */
    protected function raiseMemoryLimit(string $target): void
    {
        $current = trim((string) ini_get('memory_limit'));

        if ($current === '-1') {
            return;
        }

        if (self::toBytes($current) >= self::toBytes($target)) {
            return;
        }

        @ini_set('memory_limit', $target);
    }

    /**
     * Extend the execution time to at least $seconds.
     *
     * A max_execution_time of 0 means "no limit" (the CLI default), so it is left
     * alone for the same reason -1 is above.
     */
    protected function raiseTimeLimit(int $seconds): void
    {
        $current = (int) ini_get('max_execution_time');

        if ($current === 0 || $current >= $seconds) {
            return;
        }

        @set_time_limit($seconds);
    }

    /**
     * The memory ceiling every Mess PDF export asks for.
     *
     * One figure for the whole module, rather than the 256M / 512M the individual
     * controllers used to carry: those were picked reactively, one blank 500 at a
     * time (see the note ItemSubcategoryController still carries), and they have
     * to agree with pdfMaxRows() below or the cap lets through more rows than the
     * ceiling can render.
     */
    protected function pdfMemoryLimit(): string
    {
        $limit = trim((string) env('MESS_PDF_MEMORY_LIMIT', '1024M'));

        return $limit !== '' ? $limit : '1024M';
    }

    /**
     * How many rows a PDF may hold before we stop rather than fatal.
     *
     * DomPDF's cost is roughly 370KB per row on these tables — measured at 418MB
     * for the 1,137-row Sub-Category master. The Selling Voucher grid holds
     * 11,662 rows, which exhausts *three gigabytes* inside Cellmap.php; no
     * memory_limit worth setting on a shared host renders it.
     *
     * 2000 keeps every report that renders today working — the largest is ~1,100
     * rows, well inside the 1024M ceiling above — while turning the unrenderable
     * ones into an explanation instead of a white screen. Set MESS_PDF_MAX_ROWS=0
     * to disable the guard.
     */
    protected function pdfMaxRows(): int
    {
        return max(0, (int) env('MESS_PDF_MAX_ROWS', 2000));
    }

    /**
     * Refuse a PDF that cannot be rendered, and say why.
     *
     * Deliberately not a silent truncation: a report that quietly stops at row
     * 2000 is worse than no report, because nothing on the page says the figures
     * are partial. The CSV and Excel branches stream and have no such limit, so
     * the message points at them.
     *
     * @return \Illuminate\Http\Response|null  null when the PDF is safe to render.
     */
    protected function guardPdfRowCount(\Illuminate\Support\Collection $rows, string $reportTitle)
    {
        $max = $this->pdfMaxRows();

        if ($max === 0 || $rows->count() <= $max) {
            return null;
        }

        return response()->view('mess.partials.export-too-large', [
            'reportTitle' => $reportTitle,
            'rowCount' => $rows->count(),
            'maxRows' => $max,
        ], 422);
    }

    /**
     * PHP shorthand ('512M', '1G', '134217728') to bytes.
     */
    private static function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return $number;
        }
    }
}
