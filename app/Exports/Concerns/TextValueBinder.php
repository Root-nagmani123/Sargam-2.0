<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Writes user-entered text that looks like a formula as an explicit string.
 *
 * PhpSpreadsheet's default binder types a value beginning with "=" as a formula,
 * so a leave reason of =HYPERLINK("https://phish.example","Medical certificate")
 * reaches the approver's Excel as a live link (CWE-1236). setValueExplicit with
 * TYPE_STRING stores the text as typed, with no visible apostrophe — unlike the
 * CSV-oriented sanitize_export_cell(), whose leading quote shows up in .xlsx.
 *
 * Numbers, dates and ordinary text keep their default typing.
 */
class TextValueBinder extends DefaultValueBinder
{
    /**
     * Leading characters Excel and LibreOffice treat as the start of a formula.
     */
    private const FORMULA_PREFIXES = '/^[=+\-@\t\r]/';

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== '' && preg_match(self::FORMULA_PREFIXES, $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
