<?php

namespace App\Exports;

use App\DataTables\EstatePossessionDetailsDataTable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Branded .xlsx for the Possession Details listing.
 *
 * The Print view (admin/estate/possession_details_print.blade.php)
 * renders from the SAME columnDefs(), so the two downloads always carry the
 * same header row and the same column set — including whatever the user hid in
 * the on-screen "Columns" modal, which arrives as ?cols=.
 */
class EstatePossessionDetailsExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Rows used by the LBSNAA banner before the table's heading row. */
    protected int $headerRows = 5;

    protected Collection $rows;

    /** Ordered column keys to emit (subset of columnDefs()). */
    protected array $cols;

    protected string $filterLine;

    protected string $generatedAt;

    /**
     * The one definition of the export's columns, in table order.
     *
     * Keys are the slugs the index page's Column Visibility modal sends in ?cols=.
     * The table's Action column is deliberately absent — it has never been
     * exportable, so hiding it changes nothing. Decision is export-only: on
     * screen it is implied by which actions are still live.
     */
    public static function columnDefs(): array
    {
        return [
            'sno' => ['heading' => 'S. No.', 'center' => true, 'width' => 5,
                'value' => fn ($row, int $serial) => $serial],
            'request_id' => ['heading' => 'Request ID', 'center' => false, 'width' => 12,
                'value' => fn ($row) => $row->request_id ?: '-'],
            'name_id' => ['heading' => 'Name & ID', 'center' => false, 'width' => 15,
                'value' => function ($row) {
                    $name = trim((string) ($row->emp_name ?? ''));
                    $id = trim((string) ($row->employee_id ?? ''));
                    if ($name === '' && $id === '') {
                        return '-';
                    }

                    return $id !== '' ? trim($name . ' - ' . $id) : $name;
                }],
            'emp_designation' => ['heading' => 'Designation', 'center' => false, 'width' => 12,
                'value' => fn ($row) => $row->emp_designation ?: '-'],
            'estate_name' => ['heading' => 'Estate Name', 'center' => false, 'width' => 11,
                'value' => fn ($row) => $row->estate_name ?: '-'],
            'building_name' => ['heading' => 'Building Name', 'center' => false, 'width' => 11,
                'value' => fn ($row) => $row->building_name ?: '-'],
            'unit_type' => ['heading' => 'Unit Type', 'center' => false, 'width' => 8,
                'value' => fn ($row) => $row->unit_type ?: '-'],
            'unit_sub_type' => ['heading' => 'Unit Sub Type', 'center' => false, 'width' => 8,
                'value' => fn ($row) => $row->unit_sub_type ?: '-'],
            'house_no' => ['heading' => 'House Number', 'center' => true, 'width' => 8,
                'value' => fn ($row) => $row->house_no ?: '-'],
            'allotment_date' => ['heading' => 'Allotment Date', 'center' => true, 'width' => 9,
                'value' => fn ($row) => $row->allotment_date ? \Carbon\Carbon::parse($row->allotment_date)->format('d-m-Y') : '-'],
            'possession_date' => ['heading' => 'Possession Date', 'center' => true, 'width' => 9,
                'value' => function ($row) {
                    if (! $row->possession_date) {
                        return '-';
                    }
                    $d = \Carbon\Carbon::parse($row->possession_date);

                    return $d->format('Y-m-d') <= '1900-01-01' ? '-' : $d->format('d-m-Y');
                }],
            'electric_meter_reading' => ['heading' => 'Last Electric Bill Reading', 'center' => true, 'width' => 10,
                'value' => fn ($row) => EstatePossessionDetailsDataTable::meterReadingLabel($row)],
        ];
    }

    /**
     * Narrow a raw `?cols=` value to the known keys, in definition order.
     * An empty / unparseable value means "everything".
     *
     * @return string[]
     */
    public static function resolveCols(?string $raw): array
    {
        $all = array_keys(static::columnDefs());
        $requested = array_filter(array_map('trim', explode(',', (string) $raw)));
        if (! $requested) {
            return $all;
        }

        $picked = array_values(array_intersect($all, $requested));

        return $picked ?: $all;
    }

    /**
     * @param  string[]|null  $cols  Ordered columns to emit; null = all of them.
     */
    public function __construct(Collection $rows, string $filterLine, string $generatedAt, ?array $cols = null)
    {
        $this->rows = $rows;
        $this->filterLine = $filterLine;
        $this->generatedAt = $generatedAt;
        $this->cols = $cols ?: array_keys(static::columnDefs());
    }

    public function title(): string
    {
        return 'Possession Details';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    public function headings(): array
    {
        $defs = static::columnDefs();

        return array_map(fn (string $key) => $defs[$key]['heading'], $this->cols);
    }

    public function array(): array
    {
        $defs = static::columnDefs();
        $out = [];
        $serial = 0;

        foreach ($this->rows as $row) {
            $serial++;
            $line = [];
            foreach ($this->cols as $key) {
                $line[] = ($defs[$key]['value'])($row, $serial);
            }
            $out[] = $line;
        }

        return $out;
    }

    /** Sheet letter of the last emitted column. */
    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->cols)));
    }

    /** Sheet letter for a column key, or null when it isn't being emitted. */
    protected function colLetter(string $key): ?string
    {
        $i = array_search($key, $this->cols, true);

        return $i === false ? null : Coordinate::stringFromColumnIndex($i + 1);
    }

    public function styles(Worksheet $sheet)
    {
        $defs = static::columnDefs();
        $lastRow = $sheet->getHighestRow();
        $lastCol = $this->lastColLetter();
        $headingRow = $this->headerRows + 1;
        $firstDataRow = $headingRow + 1;

        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A93']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '003366']],
            ],
        ]);

        if ($lastRow >= $firstDataRow) {
            $sheet->getStyle("A{$firstDataRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 10],
            ]);

            // Zebra striping.
            $row = $firstDataRow;
            foreach ($this->rows as $ignored) {
                if (($row - $firstDataRow) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                $row++;
            }

            foreach ($this->cols as $key) {
                if (empty($defs[$key]['center'])) {
                    continue;
                }
                if (($letter = $this->colLetter($key)) !== null) {
                    $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        $sheet->freezePane('A' . $firstDataRow);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColLetter();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'POSSESSION DETAILS');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', $this->filterLine . '  |  Generated: ' . $this->generatedAt);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->rows->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']],
                    ],
                ]);

                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA Logo');
                    $drawing->setDescription('LBSNAA Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }
}
