<?php

namespace App\Support;

/**
 * The branded leading rows a report CSV carries before its column headings.
 *
 * Mirrors the header band the .xlsx / print / PDF exports already render, so a
 * CSV states the same report scope, applied filters and record count as its
 * siblings instead of arriving as bare columns. One implementation because the
 * four formats of a report must never disagree about what was filtered.
 */
class ExportCsvHeader
{
    /**
     * @param  string       $title       report name, e.g. "Raised By You"
     * @param  string|null  $filterLine  PLAIN-text applied filters ("Search: foo  |  Status: Pending"), null when unfiltered
     * @param  string       $exportDate  already-formatted generation timestamp
     * @param  int|null     $total       record count, omitted when null
     * @param  string|null  $note        e.g. a row-cap note, omitted when null/empty
     * @return array<int, array<int, string>>  rows ready for fputcsv()
     */
    public static function rows(
        string $title,
        ?string $filterLine,
        string $exportDate,
        ?int $total = null,
        ?string $note = null
    ): array {
        $rows = [
            ['LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION'],
            [mb_strtoupper($title)],
            [implode('  |  ', array_filter([$filterLine, 'Generated: ' . $exportDate]))],
        ];

        if ($total !== null) {
            $rows[] = ['Total Records: ' . number_format($total)];
        }

        if ($note !== null && $note !== '') {
            $rows[] = [$note];
        }

        // Blank spacer, so the column headings still read as the start of a table.
        $rows[] = [];

        return $rows;
    }
}
