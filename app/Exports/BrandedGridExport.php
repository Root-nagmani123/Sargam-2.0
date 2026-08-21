<?php

namespace App\Exports;

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
 * Any new-design index grid -> a branded .xlsx
 *
 * ONE class for every grid that exports the same way: a branded band, then the
 * column set the grid is currently showing. The columns arrive already resolved
 * by the calling service (RoleService::exportColumns(),
 * SidebarCategoryService::exportColumns(), ...) -- the same array the CSV, the
 * PDF and the print view are handed, so hiding a column in the grid's Columns
 * modal drops it from all four and they cannot drift apart
 * (docs/new-design-index-page.md section 1).
 *
 * Pairs with resources/views/exports/branded_grid_pdf.blade.php, which renders
 * the identical report as a PDF.
 *
 * Styled to match the print/PDF header: logo, navy institution band, report
 * title, generated stamp, record count, then a navy table header over zebra rows.
 */
class BrandedGridExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Rows the branded header occupies before the data table starts. */
    private const HEADER_ROWS = 5;

    /** @var array<int, array<int, string>> */
    private array $data;

    /**
     * @param  iterable  $rows        Eloquent collection or plain array of rows
     * @param  array<int, array{key?:string, heading:string, class:string, value:callable}>  $columns
     * @param  string  $title       report name, e.g. "Roles & Permissions"
     * @param  string|null  $filterLine  PLAIN-text applied filters, null when unfiltered
     * @param  list<string>  $centreKeys  column keys the grid centres
     */
    public function __construct(
        iterable $rows,
        private array $columns,
        private string $title,
        private string $exportDate,
        private ?string $filterLine = null,
        private array $centreKeys = ['sno', 'permissions_count', 'created_at', 'status', 'sort_order', 'order']
    ) {
        // Resolved once in the constructor: array() and registerEvents() both need
        // the rows, and an iterable handed in can only be walked a single time.
        $this->data = [];
        $index = 0;
        foreach ($rows as $row) {
            $this->data[] = array_values(array_map(
                fn (array $col) => (string) $col['value']($row, $index),
                $this->columns
            ));
            $index++;
        }
    }

    public function title(): string
    {
        // Excel sheet names cap at 31 chars and reject : \ / ? * [ ]
        $clean = preg_replace('#[:\\\\/?*\[\]]#', '-', $this->title);

        return mb_substr($clean, 0, 31);
    }

    public function startCell(): string
    {
        return 'A'.(self::HEADER_ROWS + 1);
    }

    public function headings(): array
    {
        return array_values(array_map(fn (array $col) => $col['heading'], $this->columns));
    }

    public function array(): array
    {
        return $this->data;
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
                $count = count($this->data);
                $dataHeaderRow = self::HEADER_ROWS + 1;
                $lastRow = $dataHeaderRow + $count;

                // -- Branded header --
                $sheet->mergeCells("A1:{$last}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells("A2:{$last}2");
                $sheet->setCellValue('A2', mb_strtoupper($this->title));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$last}3");
                $meta = 'Generated: '.$this->exportDate;
                if (filled($this->filterLine)) {
                    $meta = $this->filterLine.'  |  '.$meta;
                }
                $sheet->setCellValue('A3', $meta);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$last}4");
                $sheet->setCellValue('A4', 'Total Records: '.number_format($count));
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                ]);

                $sheet->getStyle("A1:{$last}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                // -- Data table --
                $sheet->getStyle("A{$dataHeaderRow}:{$last}{$dataHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($dataHeaderRow)->setRowHeight(22);

                if ($count > 0) {
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

                    $sheet->getStyle('A'.($dataHeaderRow + 1).":{$last}{$lastRow}")
                        ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                }

                // Centre the columns the grid centres.
                $index = 1;
                foreach ($this->columns as $col) {
                    if (in_array($col['key'] ?? '', $this->centreKeys, true)) {
                        $letter = Coordinate::stringFromColumnIndex($index);
                        $sheet->getStyle("{$letter}{$dataHeaderRow}:{$letter}{$lastRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $index++;
                }

                // -- Logo, floated over the header band --
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
                $sheet->freezePane('A'.($dataHeaderRow + 1));
            },
        ];
    }
}
