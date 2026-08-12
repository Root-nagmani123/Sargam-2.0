<?php

namespace App\Exports;

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
 * Branded .xlsx for Estate Request for Others.
 *
 * The Print view (admin/estate/estate_request_for_others_print.blade.php) renders
 * from the SAME columnDefs(), so the two exports always carry the same header row
 * and the same column set — including whatever the user hid in the on-screen
 * "Columns" modal, which arrives as ?cols=.
 */
class EstateOtherRequestExport implements
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
     * exportable.
     *
     * `width` doubles as the Print view's column width (in %), so the two exports
     * lay out the same way.
     */
    public static function columnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'center' => true,
                'width' => 6,
                'value' => fn ($row, int $serial) => $serial,
            ],
            'request_id' => [
                'heading' => 'Request ID',
                'center' => false,
                'width' => 16,
                'value' => fn ($row) => $row->request_no_oth ?: '-',
            ],
            'emp_name' => [
                'heading' => 'Employee Name',
                'center' => false,
                'width' => 32,
                'value' => fn ($row) => $row->emp_name ?: '-',
            ],
            'section' => [
                'heading' => 'Section',
                'center' => false,
                'width' => 32,
                'value' => fn ($row) => self::text($row->section),
            ],
            'doj_acad' => [
                'heading' => 'DOJ in Academy',
                'center' => true,
                'width' => 14,
                'value' => fn ($row) => $row->doj_acad
                    ? \Carbon\Carbon::parse($row->doj_acad)->format('d-m-Y')
                    : '-',
            ],
        ];
    }

    /** A stored value, or the export's dash for the blanks the legacy rows carry. */
    private static function text($value): string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? '-' : $value;
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
        return 'Estate Request for Others';
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

            for ($row = $firstDataRow; $row <= $lastRow; $row++) {
                if (($row - $firstDataRow) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }
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
                $sheet->setCellValue('A2', 'ESTATE REQUEST FOR OTHERS');
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
