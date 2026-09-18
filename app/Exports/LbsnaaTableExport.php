<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
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
 * The Academy's standard .xlsx layout, for any simple heading/row table.
 *
 * Header block, navy column band, bordered wrapping cells and a frozen header —
 * the same treatment {@see FeedbackDatabaseExport} gives the Feedback Database,
 * which is the format the Academy asked every other export to match. That one
 * stays as it is because its headings are column-toggleable; this is the plain
 * version every fixed-column listing can share, so the leave registers and the
 * faculty listings cannot drift into three different looks.
 *
 * Rows arrive already formatted by the caller, and the same arrays are rendered
 * by admin/exports/table_pdf.blade.php, so a sheet and a PDF of one listing
 * cannot disagree.
 */
class LbsnaaTableExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithTitle, WithCustomStartCell, ShouldAutoSize
{
    /** Rows of the branded block above the table. */
    private const HEADER_ROWS = 5;

    public function __construct(
        protected Collection $rows,
        protected array $headings,
        protected string $reportTitle,
        protected string $filterLine = '',
        /** @var list<int> 0-based column indexes to centre (S.No, dates, counts …) */
        protected array $centreColumns = [],
        protected ?string $sheetTitle = null,
        /**
         * 0-based row indexes rendered as a section band — a grouping heading
         * inside the table, such as a house name. Merged across the sheet.
         *
         * @var list<int>
         */
        protected array $sectionRows = [],
        /**
         * 0-based row indexes rendered as a subtotal / total line.
         *
         * @var list<int>
         */
        protected array $totalRows = []
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function startCell(): string
    {
        return 'A' . (self::HEADER_ROWS + 1);
    }

    public function title(): string
    {
        // Excel rejects sheet names over 31 chars or containing []:*?/\
        $title = $this->sheetTitle ?: $this->reportTitle;

        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $title), 0, 31);
    }

    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->headings)));
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $this->lastColumn();
        $headingRow = self::HEADER_ROWS + 1;
        $firstDataRow = $headingRow + 1;
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '002244']]],
        ]);
        $sheet->getRowDimension($headingRow)->setRowHeight(24);

        if ($lastRow >= $firstDataRow) {
            $sheet->getStyle("A{$firstDataRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);

            foreach ($this->centreColumns as $index) {
                $col = Coordinate::stringFromColumnIndex((int) $index + 1);
                $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Grouping bands: merged across the sheet and filled, so a house (or
            // whatever the caller is grouping by) reads as a heading rather than
            // as a data row with four blank cells after it.
            foreach ($this->sectionRows as $offset) {
                $r = $firstDataRow + (int) $offset;
                if ($r > $lastRow) {
                    continue;
                }
                $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(20);
            }

            foreach ($this->totalRows as $offset) {
                $r = $firstDataRow + (int) $offset;
                if ($r > $lastRow) {
                    continue;
                }
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '003366']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF3FA']],
                ]);
            }
        }

        // Keeps the column band on screen while scrolling a long listing.
        $sheet->freezePane('A' . $firstDataRow);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColumn();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', mb_strtoupper($this->reportTitle));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', trim(
                    ($this->filterLine !== '' ? $this->filterLine . '  |  ' : '')
                    . 'Generated: ' . now()->format('d-m-Y H:i')
                ));
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total records: ' . $this->rows->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                // Thin spacer between the header block and the table.
                $sheet->getRowDimension(5)->setRowHeight(6);

                $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
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
