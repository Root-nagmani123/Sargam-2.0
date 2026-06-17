<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};

/**
 * Read-only preview reader for the "Assign Student Hostel via Import" wizard.
 *
 * Used with Excel::toCollection() to parse the uploaded sheet for the step-2
 * preview WITHOUT writing anything to the database. It mirrors the heading/start
 * row configuration of AssignHostelToStudent so the preview matches the real
 * import; collection() is intentionally a no-op.
 */
class HostelImportPreview implements ToCollection, WithHeadingRow, WithStartRow
{
    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection)
    {
        // No-op: parsing only, no persistence.
    }
}
