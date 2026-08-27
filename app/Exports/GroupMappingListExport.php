<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * The Course Group Mapping listing as a styled Excel report — the LBSNAA header,
 * filter line and record count the PDF shows, which a plain CSV cannot carry.
 *
 * Columns are handed in already resolved by
 * GroupMappingController::resolveExportColumns(), so the sheet and the PDF emit
 * the same set in the same order and both honour the on-screen "Columns" choice.
 */
class GroupMappingListExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected Collection $rows;

    /** Ordered ['title' => …, 'align' => …, 'value' => fn ($row, $serial)] definitions. */
    protected array $columns;

    protected string $filterLine;
    protected string $exportDate;

    /** Rows used by the LBSNAA header before the data table. */
    protected int $headerRows = 5;

    public function __construct(Collection $rows, array $columns, string $filterLine, string $exportDate)
    {
        $this->rows = $rows;
        $this->columns = array_values($columns);
        $this->filterLine = $filterLine;
        $this->exportDate = $exportDate;
    }

    public function title(): string
    {
        return 'Course Group Mapping';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->columns)));
    }

    /** Sheet letter of the column with this title, or null when it isn't emitted. */
    protected function colLetterByTitle(string $title): ?string
    {
        foreach ($this->columns as $i => $column) {
            if (($column['title'] ?? '') === $title) {
                return Coordinate::stringFromColumnIndex($i + 1);
            }
        }

        return null;
    }

    public function headings(): array
    {
        return array_column($this->columns, 'title');
    }

    public function array(): array
    {
        $out = [];
        $serial = 1;

        foreach ($this->rows as $row) {
            $line = [];
            foreach ($this->columns as $column) {
                $line[] = ($column['value'])($row, $serial);
            }
            $out[] = $line;
            $serial++;
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $this->lastColLetter();
        $dataStart = $this->headerRows + 1; // heading row
        $dataRowStart = $dataStart + 1;     // first data row

        // Heading row styling
        $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '003366'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '002244']],
            ],
        ]);

        if ($lastRow >= $dataRowStart) {
            $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 10],
            ]);

            // Alternating row shading + the Active/Inactive badge. The badge column
            // is looked up by title rather than assumed last — Status can be hidden.
            $statusCol = $this->colLetterByTitle('Status');
            $row = $dataRowStart;
            foreach ($this->rows as $record) {
                if (($row - $dataRowStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                if ($statusCol !== null) {
                    $isActive = (int) ($record->active_inactive ?? 0) === 1;
                    $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $isActive ? '198754' : '6C757D'],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $row++;
            }

            // Centre whichever columns the controller flagged `align => center`.
            foreach ($this->columns as $i => $column) {
                if (($column['align'] ?? 'left') !== 'center') {
                    continue;
                }
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->getStyle("{$letter}{$dataRowStart}:{$letter}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Freeze pane below the header + heading row
        $sheet->freezePane('A' . ($dataStart + 1));

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColLetter();

                // ── LBSNAA Header ──
                // Row 1: Institution name (merged)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Row 2: Report title
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'COURSE GROUP MAPPING REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: Applied filters
                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', $this->filterLine . '  |  Generated: ' . $this->exportDate);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 4: Summary count
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->rows->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                // Row 5: empty spacer
                $sheet->getRowDimension(5)->setRowHeight(6);

                // Header border
                $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']],
                    ],
                ]);

                // Logo
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
