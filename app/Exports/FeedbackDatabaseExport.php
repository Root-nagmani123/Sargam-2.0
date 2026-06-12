<?php

namespace App\Exports;

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

class FeedbackDatabaseExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected array $rows;

    protected array $filters;

    protected string $exportDate;

    protected int $recordCount;

    protected array $visibleKeys;

    protected int $headerRows = 5;

    protected static array $allHeadings = [
        's_no' => 'S.No.',
        'faculty_name' => 'Faculty Name',
        'course_name' => 'Course Name',
        'faculty_address' => 'Faculty Address',
        'topic' => 'Topic',
        'content_pct' => 'Content (%)',
        'presentation_pct' => 'Presentation (%)',
        'participants' => 'Participants',
        'session_date' => 'Session Date',
        'comments' => 'Comments',
    ];

    public function __construct(array $rows, array $filters, string $exportDate, int $recordCount, array $visibleKeys = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
        $this->exportDate = $exportDate;
        $this->recordCount = $recordCount;
        $this->visibleKeys = !empty($visibleKeys) ? $visibleKeys : array_keys(self::$allHeadings);
    }

    public function title(): string
    {
        return 'Feedback Database';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->visibleKeys as $key) {
            $headings[] = self::$allHeadings[$key] ?? $key;
        }
        return $headings;
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->rows as $r) {
            $row = [];
            foreach ($this->visibleKeys as $key) {
                $row[] = $r[$key] ?? '';
            }
            $out[] = $row;
        }
        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $colCount = count($this->visibleKeys);
        $lastCol = chr(64 + $colCount); // A=1, B=2, ...
        $dataStart = $this->headerRows + 1;
        $dataRowStart = $dataStart + 1;
        $lastRow = $sheet->getHighestRow();

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
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
        }

        // Center columns that need centering
        $centerKeys = ['s_no', 'content_pct', 'presentation_pct', 'participants', 'session_date'];
        foreach ($this->visibleKeys as $i => $key) {
            if (in_array($key, $centerKeys)) {
                $col = chr(65 + $i);
                if ($lastRow >= $dataStart) {
                    $sheet->getStyle("{$col}{$dataStart}:{$col}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        $sheet->freezePane('A' . ($dataStart + 1));

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $prog = $this->filters['program'] ?? '—';
                $scope = $this->filters['scope'] ?? 'All records';
                $lastCol = chr(64 + count($this->visibleKeys));

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'FACULTY FEEDBACK DATABASE REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$lastCol}3");
                $filterText = 'Program: ' . $prog . '  |  Scope: ' . $scope
                    . '  |  Generated: ' . $this->exportDate;
                $sheet->setCellValue('A3', $filterText);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total records: ' . $this->recordCount);
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
