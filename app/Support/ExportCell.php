<?php

namespace App\Support;

/**
 * Resolves one column definition's value into a spreadsheet-safe cell.
 *
 * The directory reports share a single keyed column def across CSV, .xlsx, PDF
 * and print. The two Blade formats are escaped by Blade; the two spreadsheet
 * formats are not, so they route through here — one place to forget, instead of
 * one per writer. CSV takes text(); .xlsx takes raw() and declares the cell's
 * type instead, because on .xlsx the apostrophe would be stored as data.
 */
class ExportCell
{
    /**
     * The cell as the column def resolves it, with nothing added.
     *
     * For writers that state the cell TYPE — .xlsx through a string value
     * binder — where a leading "=" is inert because the cell is declared text,
     * so the apostrophe text() adds would be stored as part of the value and
     * printed to the reader.
     *
     * @param  array{value:callable}  $col
     * @param  mixed  $row
     */
    public static function raw(array $col, $row, int $index): string
    {
        return (string) $col['value']($row, $index);
    }

    /**
     * The cell prefixed so a spreadsheet app cannot evaluate it as a formula.
     *
     * For CSV, which carries no type information: the apostrophe IS the type,
     * and Excel consumes it on import rather than showing it. Do not use for
     * .xlsx — see raw().
     *
     * @param  array{value:callable}  $col
     * @param  mixed  $row
     */
    public static function text(array $col, $row, int $index): string
    {
        $value = self::raw($col, $row, $index);

        // "-" is the grids' own empty placeholder, never user-entered text, and
        // sanitize_export_cell() would otherwise render it as "'-" in every gap.
        return $value === '-' ? $value : sanitize_export_cell($value);
    }
}
