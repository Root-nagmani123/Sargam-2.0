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

class PendingFeedbackExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected array $students;
    protected array $filters;
    protected string $exportDate;
    protected int $headerRows = 5; // rows used by LBSNAA header before data

    public function __construct(array $students, array $filters, string $exportDate)
    {
        $this->students = $students;
        $this->filters = $filters;
        $this->exportDate = $exportDate;
    }

    public function title(): string
    {
        return 'Pending Feedback';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    public function headings(): array
    {
        return [
            '#',
            'Student Name',
            'Email',
            'Feedback Given',
            'Feedback Not Given',
            'Session Name',
            'Date',
            'Time',
            'Feedback Status',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $serial = 0;

        foreach ($this->students as $student) {
            $serial++;
            $sessionCount = count($student['sessions']);

            // Student summary row
            $rows[] = [
                $serial,
                $student['student_name'],
                $student['email'] ?? '',
                $student['feedback_given'],
                $student['feedback_not_given'],
                "{$sessionCount} session(s)",
                '',
                '',
                '',
            ];

            // Session detail rows
            foreach ($student['sessions'] as $session) {
                $rows[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    $session['session_name'] ?? '—',
                    $session['date'] ?? '—',
                    $session['time'] ?? '—',
                    $session['feedback_status'] === 'given' ? 'Given' : 'Not Given',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'I';
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

        // Data rows
        if ($lastRow >= $dataRowStart) {
            $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Style student summary rows vs session detail rows
        $currentRow = $dataRowStart;
        foreach ($this->students as $student) {
            // Student row — bold with light bg
            $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8EEF6'],
                ],
            ]);

            // Given badge color
            $sheet->getStyle("D{$currentRow}")->getFont()->getColor()->setRGB('198754');
            // Not Given badge color
            $sheet->getStyle("E{$currentRow}")->getFont()->getColor()->setRGB('DC3545');

            $currentRow++; // move past student row

            // Session rows
            foreach ($student['sessions'] as $session) {
                $sheet->getStyle("F{$currentRow}:I{$currentRow}")->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                ]);

                // Color the status cell
                $statusCell = "I{$currentRow}";
                if ($session['feedback_status'] === 'given') {
                    $sheet->getStyle($statusCell)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
                    ]);
                } else {
                    $sheet->getStyle($statusCell)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']],
                    ]);
                }

                $currentRow++;
            }
        }

        // Center certain columns
        $centerCols = ['A', 'D', 'E', 'G', 'H', 'I'];
        foreach ($centerCols as $col) {
            if ($lastRow >= $dataRowStart) {
                $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Freeze pane below header + heading row
        $sheet->freezePane("A" . ($dataStart + 1));

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── LBSNAA Header ──
                // Row 1: Institution name (merged)
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Row 2: Report title
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'PENDING STUDENT FEEDBACK REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: Filter info
                $sheet->mergeCells('A3:I3');
                $filterText = 'Course: ' . ($this->filters['course'] ?? 'All')
                    . '  |  Session: ' . ($this->filters['session'] ?? 'All')
                    . '  |  Period: ' . ($this->filters['from_date'] ?? 'All') . ' — ' . ($this->filters['to_date'] ?? 'All')
                    . '  |  Generated: ' . $this->exportDate;
                $sheet->setCellValue('A3', $filterText);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 4: Summary counts
                $totalStudents = count($this->students);
                $totalGiven = array_sum(array_column($this->students, 'feedback_given'));
                $totalNotGiven = array_sum(array_column($this->students, 'feedback_not_given'));
                $sheet->mergeCells('A4:I4');
                $sheet->setCellValue('A4', "Total Students: {$totalStudents}  |  Feedback Given: {$totalGiven}  |  Feedback Not Given: {$totalNotGiven}");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                // Row 5: empty spacer
                $sheet->getRowDimension(5)->setRowHeight(6);

                // Header border bottom
                $sheet->getStyle('A1:I4')->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']],
                    ],
                ]);

                // Try to add logo
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