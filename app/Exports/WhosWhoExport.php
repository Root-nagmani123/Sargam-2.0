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
 * Branded Excel export for the Who's Who directory. Mirrors the styled
 * banner + blue heading band used by {@see StudentListReportExport} so the
 * workbook visually matches the other report exports in this app.
 */
class WhosWhoExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStrictNullComparison,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected array $bannerLines;

    /** 1-based row index of the column-heading row. */
    protected int $headingRow;

    public function __construct(
        protected array $headings,
        protected array $rows,
        protected string $courseLabel,
        protected string $cadreLabel,
        protected string $serviceLabel,
        protected string $searchLabel,
        protected string $generatedAt,
    ) {
        $this->bannerLines = $this->buildBannerLines();
        $this->headingRow = count($this->bannerLines) + 2;
    }

    public function title(): string
    {
        return "Who's Who";
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
     * @return array<int, array{text:string, style:string}>
     */
    protected function buildBannerLines(): array
    {
        $lines = [
            ['text' => 'लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी', 'style' => 'academy_hi'],
            ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'academy_en'],
            ['text' => "Who's Who Directory", 'style' => 'title'],
        ];

        $filterParts = [
            'Course: ' . ($this->courseLabel !== '' ? $this->courseLabel : 'All Courses'),
            'Cadre: ' . ($this->cadreLabel !== '' ? $this->cadreLabel : 'All Cadres'),
            'Service: ' . ($this->serviceLabel !== '' ? $this->serviceLabel : 'All Services'),
        ];
        if ($this->searchLabel !== '') {
            $filterParts[] = 'Search: "' . $this->searchLabel . '"';
        }

        $meta = implode('   |   ', $filterParts)
            . '   |   Generated on: ' . $this->generatedAt
            . '   |   Total records: ' . count($this->rows);
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

        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B5651D']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '8B4513']]],
        ]);
        $sheet->getRowDimension($headingRow)->setRowHeight(26);

        if ($lastRow >= $dataStart) {
            $sheet->getStyle("A{$dataStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'font' => ['size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            for ($row = $dataStart; $row <= $lastRow; $row++) {
                if (($row - $dataStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAF3E8');
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
                    'academy_hi' => ['size' => 13, 'bold' => true, 'rgb' => '2C1810', 'height' => 22],
                    'academy_en' => ['size' => 12, 'bold' => true, 'rgb' => '2C1810', 'height' => 20],
                    'title'      => ['size' => 15, 'bold' => true, 'rgb' => 'B5651D', 'height' => 30],
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

                    if ($line['style'] === 'title') {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B5651D']]],
                        ]);
                    }
                }

                $sheet->getRowDimension(count($this->bannerLines) + 1)->setRowHeight(6);

                $this->placeLogo($sheet, $this->firstReadable([
                    public_path('admin_assets/images/logos/ashoka.png'),
                    public_path('images/ashoka.png'),
                    public_path('admin_assets/images/logos/logo_new.png'),
                ]), 'A1', 6, 2);

                $this->placeLogo($sheet, $this->firstReadable([
                    public_path('admin_assets/images/logos/logo_new.png'),
                    public_path('admin_assets/images/logos/logo.png'),
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
