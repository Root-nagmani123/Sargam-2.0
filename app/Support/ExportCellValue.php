<?php

namespace App\Support;

/**
 * Neutralises spreadsheet formula injection in exported cell values.
 *
 * Excel and LibreOffice treat a cell whose text begins with `=`, `+`, `-` or `@`
 * as a formula, and PhpSpreadsheet's default value binder goes further: a string
 * starting with `=` is stored in the .xlsx as a real formula cell, not as text.
 * So a stored name of `=HYPERLINK("http://…","Click")` becomes live content in
 * the downloaded file rather than the characters someone typed.
 *
 * Prefixing with an apostrophe is the standard fix: spreadsheets read it as
 * "the rest of this cell is literal text" and do not display it. Values that
 * cannot begin a formula are returned untouched, so numbers stay numbers and
 * dates stay dates.
 *
 * Used by every format of the Master-module export (see ExportsMasterGrid and
 * MasterGridExport) so the CSV, the .xlsx and the on-screen report cannot
 * disagree about what a cell contains.
 */
class ExportCellValue
{
    /** Leading characters a spreadsheet may read as the start of a formula. */
    private const RISKY = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  mixed  $value  whatever a column definition's `value` closure returned
     * @return mixed          strings are neutralised; everything else is returned as-is
     */
    public static function safe($value)
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        // Leading whitespace does not stop Excel parsing what follows as a
        // formula, so test the trimmed value but neutralise the original.
        $probe = ltrim($value, " \t\r\n");

        if ($probe === '' || ! in_array($probe[0], self::RISKY, true)) {
            return $value;
        }

        // Two things start with a risky character but cannot be a formula, and
        // quoting them would visibly damage the report:
        //   - the "no value" placeholder the column definitions emit ("-"),
        //     which is 13 of the 18 hits in a full Faculty export;
        //   - a genuine negative number, which would become left-aligned text
        //     and stop sorting numerically.
        if (is_numeric($probe) || preg_match('/^[-–—]+$/u', trim($probe)) === 1) {
            return $value;
        }

        return "'" . $value;
    }
}
