<?php

namespace App\Support\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Makes the CELL TYPE the control on the .xlsx path, instead of an apostrophe.
 *
 * Two separate problems this solves, both of which the apostrophe mitigation
 * either causes or cannot reach:
 *
 *  1. PhpSpreadsheet stores a leading apostrophe as DATA, so a mobile written
 *     "+91 98765 43210" opened in Excel reads "'+91 98765 43210". The apostrophe
 *     is the right CSV mitigation and the wrong .xlsx one.
 *  2. The default binder types a digit-only string as a NUMBER. Excel keeps 15
 *     significant digits, so a 16-digit bank account number is displayed and
 *     re-saved rounded — silent corruption of an identifier, in the workbook
 *     that carries Account_No.
 *
 * Binding a string explicitly as TYPE_STRING fixes both: "=1+1" is stored as the
 * four characters someone typed (never a formula cell), leading zeros survive,
 * and a long identifier keeps every digit.
 *
 * Genuine numbers still bind as numbers so they sort and total correctly — see
 * isSpreadsheetSafeNumber(): anything a double can hold exactly, which is the
 * same 15-digit boundary Excel itself uses.
 *
 * Used with Maatwebsite's WithCustomValueBinder, which requires the export class
 * itself to be the binder; extend Maatwebsite\Excel\DefaultValueBinder and use
 * this trait to supply bindValue().
 */
trait BindsExportCellsAsText
{
    /**
     * @param  Cell  $cell
     * @param  mixed  $value
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== '' && ! self::isSpreadsheetSafeNumber($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * True when a string is a plain number Excel can hold without losing a digit.
     *
     * Deliberately strict — a leading zero, a leading +, a space, a 16th digit or
     * any other character makes it an identifier rather than a quantity, and an
     * identifier must stay text.
     */
    public static function isSpreadsheetSafeNumber(string $value): bool
    {
        return preg_match('/^-?(0|[1-9][0-9]{0,14})(\.[0-9]{1,10})?$/', $value) === 1;
    }
}
