<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Manage Sub-Categories → .xlsx
 *
 * Columns are handed in already resolved by
 * IssueSubCategoryController::resolveExportColumns(), which is the same array the
 * CSV, the PDF and the print view use — so hiding a column in the grid's Columns
 * modal drops it from every format, and the four can't drift apart.
 *
 * Styled to match the print/PDF header: logo, navy institution band, report
 * title, generated stamp, record count, then a navy table header over zebra rows.
 */
class IssueSubCategoryExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Rows the branded header occupies before the data table starts. */
    private const HEADER_ROWS = 5;

    public function __construct(
        private Collection $rows,
        /** @var array<string, array{heading:string, class:string, value:callable}> */
        private array $columns,
        private string $exportDate,
        private string $search = ''
    ) {
    }

    public function title(): string
    {
        return 'Manage Sub-Categories';
    }

    public function startCell(): string
    {
        return 'A' . (self::HEADER_ROWS + 1);
    }

    public function headings(): array
    {
        return array_values(array_map(fn ($col) => $col['heading'], $this->columns));
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $index => $row) {
            $out[] = array_values(array_map(
                fn ($col) => $col['value']($row, $index),
                $this->columns
            ));
        }

        return $out;
    }

    private function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->columns)));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastColLetter();
                $dataHeaderRow = self::HEADER_ROWS + 1;
                $lastRow = $dataHeaderRow + $this->rows->count();

                // ── Branded header ──
                $sheet->mergeCells("A1:{$last}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells("A2:{$last}2");
                $sheet->setCellValue('A2', 'MANAGE SUB-CATEGORIES');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$last}3");
                $meta = 'Generated: ' . $this->exportDate;
                if ($this->search !== '') {
                    $meta = 'Search: ' . $this->search . '  |  ' . $meta;
                }
                $sheet->setCellValue('A3', $meta);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$last}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->rows->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                ]);

                $sheet->getStyle("A1:{$last}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                // ── Data table ──
                $sheet->getStyle("A{$dataHeaderRow}:{$last}{$dataHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($dataHeaderRow)->setRowHeight(22);

                if ($this->rows->count() > 0) {
                    $sheet->getStyle("A{$dataHeaderRow}:{$last}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    ]);

                    // Zebra striping, matching the print/PDF output.
                    for ($r = $dataHeaderRow + 1; $r <= $lastRow; $r++) {
                        if (($r - $dataHeaderRow) % 2 === 0) {
                            $sheet->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                            ]);
                        }
                    }

                    $sheet->getStyle("A" . ($dataHeaderRow + 1) . ":{$last}{$lastRow}")
                        ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                }

                // Centre the columns the grid centres.
                $index = 1;
                foreach ($this->columns as $key => $col) {
                    if (in_array($key, ['sno', 'status'], true)) {
                        $letter = Coordinate::stringFromColumnIndex($index);
                        $sheet->getStyle("{$letter}{$dataHeaderRow}:{$letter}{$lastRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $index++;
                }

                // ── Logo, floated over the header band ──
                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (is_file($logoPath) && is_readable($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA');
                    $drawing->setDescription('LBSNAA');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(46);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(6);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                // Keep the branded header and the column titles on screen while scrolling.
                $sheet->freezePane('A' . ($dataHeaderRow + 1));
            },
        ];
    }
}
