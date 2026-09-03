<?php

namespace App\Support;

/**
 * Resolves one column definition's value into a spreadsheet-safe cell.
 *
 * The directory reports share a single keyed column def across CSV, .xlsx, PDF
 * and print. The two Blade formats are escaped by Blade; the two spreadsheet
 * formats are not, so they route through here — one place to forget, instead of
 * one per writer.
 */
class ExportCell
{
    /**
     * @param  array{value:callable}  $col
     * @param  mixed  $row
     */
    public static function text(array $col, $row, int $index): string
    {
        $value = (string) $col['value']($row, $index);

        // "-" is the grids' own empty placeholder, never user-entered text, and
        // sanitize_export_cell() would otherwise render it as "'-" in every gap.
        return $value === '-' ? $value : sanitize_export_cell($value);
    }
}
