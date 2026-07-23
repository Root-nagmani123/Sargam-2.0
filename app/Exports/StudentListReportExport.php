<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
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
 * Branded Excel report for the dashboard Student List. Reproduces the same
 * institution header used by the Print/PDF exports — left/right logos, the
 * Hindi + English academy titles, the course line, and a blue report-title band
 * — so the downloaded workbook matches the on-paper report (a plain CSV cannot
 * carry logos, colours or merged/centred titles).
 */
class StudentListReportExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStrictNullComparison,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Banner lines above the blank spacer + column-heading row. */
    protected array $bannerLines;

    /** 1-based row index of the column-heading row. */
    protected int $headingRow;

    public function __construct(
        protected array $headings,
        protected array $rows,
        protected string $reportTitle,
        protected string $courseName,
        protected string $courseDuration,
        protected string $filterSummary,
        protected string $generatedAt,
        protected int $recordCount,
    ) {
        $this->bannerLines = $this->buildBannerLines();
        // banner lines + 1 blank spacer row, then the heading row.
        $this->headingRow = count($this->bannerLines) + 2;
    }

    public function title(): string
    {
        return 'Student List';
    }

    public function startCell(): string
    {
        return 'A' . $this->headingRow;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    /**
     * The centred banner rows (each merged across all columns), in the same
     * order as the Print/PDF header.
     *
     * @return array<int, array{text:string, style:string}>
     */
    protected function buildBannerLines(): array
    {
        $lines = [
            ['text' => 'लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी', 'style' => 'academy_hi'],
            ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'academy_en'],
        ];

        if ($this->courseName !== '') {
            $lines[] = ['text' => $this->courseName, 'style' => 'course'];
        }
        if ($this->courseDuration !== '') {
            $lines[] = ['text' => '(' . $this->courseDuration . ')', 'style' => 'dates'];
        }

        $lines[] = ['text' => $this->reportTitle, 'style' => 'title'];

        $meta = 'Filters: ' . ($this->filterSummary !== '' ? $this->filterSummary : 'All students')
            . '   |   Generated on: ' . $this->generatedAt
            . '   |   Total records: ' . $this->recordCount;
        $lines[] = ['text' => $meta, 'style' => 'meta'];

        return $lines;
    }

    public function styles(Worksheet $sheet)
    {
        $colCount   = max(1, count($this->headings));
        $lastCol    = Coordinate::stringFromColumnIndex($colCount);
        $headingRow = $this->headingRow;
        $dataStart  = $headingRow + 1;
        $lastRow    = $sheet->getHighestRow();

        // Blue column-heading band.
        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A93']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '003A75']]],
        ]);
        $sheet->getRowDimension($headingRow)->setRowHeight(26);

        // Data rows: thin borders + top-aligned wrap + zebra striping.
        if ($lastRow >= $dataStart) {
            $sheet->getStyle("A{$dataStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'font' => ['size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            for ($row = $dataStart; $row <= $lastRow; $row++) {
                if (($row - $dataStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4F7FB');
                }
            }
        }

        $sheet->freezePane('A' . $dataStart);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $colCount = max(1, count($this->headings));
                $lastCol = Coordinate::stringFromColumnIndex($colCount);

                $styleFor = [
                    'academy_hi' => ['size' => 13, 'bold' => true, 'rgb' => '102A43', 'height' => 22],
                    'academy_en' => ['size' => 12, 'bold' => true, 'rgb' => '102A43', 'height' => 20],
                    'course'     => ['size' => 10, 'bold' => true, 'rgb' => '243B53', 'height' => 16],
                    'dates'      => ['size' => 9,  'bold' => false, 'rgb' => '486581', 'height' => 15],
                    'title'      => ['size' => 15, 'bold' => true, 'rgb' => '004A93', 'height' => 30],
                    'meta'       => ['size' => 9,  'bold' => false, 'rgb' => '555555', 'height' => 16],
                ];

                foreach ($this->bannerLines as $i => $line) {
                    $row = $i + 1;
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                    $sheet->setCellValue("A{$row}", $line['text']);
                    $s = $styleFor[$line['style']] ?? $styleFor['meta'];
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => $s['bold'], 'size' => $s['size'], 'color' => ['rgb' => $s['rgb']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight($s['height']);

                    // Blue underline beneath the report title (mirrors the PDF band).
                    if ($line['style'] === 'title') {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '004A93']]],
                        ]);
                    }
                }

                // Blank spacer row between the banner and the column headings.
                $sheet->getRowDimension(count($this->bannerLines) + 1)->setRowHeight(6);

                // Left (Ashoka emblem) and right (75-years) logos floating over the
                // first banner rows — same assets the official report layout uses.
                $this->placeLogo($sheet, $this->firstReadable([
                    public_path('admin_assets/images/logos/ashoka.png'),
                    public_path('images/ashoka.png'),
                    public_path('admin_assets/images/logos/logo_new.png'),
                ]), 'A1', 6, 2);

                $this->placeLogo($sheet, $this->firstReadable([
                    public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'),
                    public_path('admin_assets/images/logos/logo_new.png'),
                ]), $lastCol . '1', 4, 2);
            },
        ];
    }

    private function firstReadable(array $paths): ?string
    {
        foreach ($paths as $p) {
            if (is_file($p) && is_readable($p)) {
                return $p;
            }
        }

        return null;
    }

    private function placeLogo(Worksheet $sheet, ?string $path, string $coordinates, int $offsetX, int $offsetY): void
    {
        if ($path === null) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath($path);
        $drawing->setHeight(46);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY($offsetY);
        $drawing->setWorksheet($sheet);
    }
}
