<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic export for the admin users listing. The controller pre-resolves the
 * visible columns into headings + plain rows so the same class can serve both
 * the CSV and XLSX writers while respecting the active filters/search.
 */
class UsersExport implements FromArray, WithHeadings, ShouldAutoSize, WithStrictNullComparison, WithStyles, WithCustomCsvSettings
{
    /** @var array<int, string> */
    protected array $headings;

    /** @var array<int, array<int, mixed>> */
    protected array $rows;

    /** @var array<int, array<int, mixed>> Branded identity rows placed above the column headings. */
    protected array $metaRows;

    public function __construct(array $headings, array $rows, array $metaRows = [])
    {
        $this->headings = $headings;
        $this->rows = $rows;
        $this->metaRows = $metaRows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        // Branded meta rows (institution / report title / course / generated on)
        // sit above the column heading row so the CSV carries the same header as
        // the Print & PDF layouts.
        if (! empty($this->metaRows)) {
            return array_merge($this->metaRows, [$this->headings]);
        }

        return $this->headings;
    }

    /**
     * Write a UTF-8 BOM so Excel (esp. on Windows) renders non-ASCII branded
     * header text — e.g. the Devanagari academy title — instead of mojibake.
     */
    public function getCsvSettings(): array
    {
        return ['use_bom' => true];
    }

    public function styles(Worksheet $sheet)
    {
        // The column heading row is bold; when branded meta rows are present it is
        // pushed down below them.
        $headingRow = empty($this->metaRows) ? 1 : count($this->metaRows) + 1;

        return [
            $headingRow => ['font' => ['bold' => true]],
        ];
    }
}
