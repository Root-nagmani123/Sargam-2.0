<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic export for the admin users listing. The controller pre-resolves the
 * visible columns into headings + plain rows so the same class can serve both
 * the CSV and XLSX writers while respecting the active filters/search.
 */
class UsersExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /** @var array<int, string> */
    protected array $headings;

    /** @var array<int, array<int, mixed>> */
    protected array $rows;

    public function __construct(array $headings, array $rows)
    {
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
