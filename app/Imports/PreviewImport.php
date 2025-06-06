<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PreviewImport implements ToArray, WithHeadingRow
{
    /**
     * Return the array of rows from the uploaded Excel file.
     *
     * @param array $rows
     * @return array
     */
    public function array(array $rows)
    {
        return $rows;
    }
}
