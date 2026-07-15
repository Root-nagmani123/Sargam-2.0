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
use Carbon\Carbon;

class DisciplineMemoExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected Collection $memos;
    protected array $filters;
    protected string $exportDate;
    protected int $headerRows = 5; // rows used by the LBSNAA header before the data table
    protected string $lastCol = 'M';

    public function __construct(Collection $memos, array $filters, string $exportDate)
    {
        $this->memos = $memos;
        $this->filters = $filters;
        $this->exportDate = $exportDate;
    }

    public function title(): string
    {
        return 'Discipline Memo';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    public function headings(): array
    {
        return [
            '#',
            'Program Name',
            'Student Name',
            'OT/Participant Code',
            'Cadre',
            'Date of Infraction',
            'Infraction',
            'Submitted Marks',
            'Final Marks',
            'Remarks',
            'Conclusion Remark',
            'Created Date',
            'Status',
        ];
    }

    protected function statusLabel(?int $status): string
    {
        return match ($status) {
            1 => 'Recorded',
            2 => 'Memo Sent',
            3 => 'Closed',
            default => 'Closed',
        };
    }

    public function array(): array
    {
        $rows = [];
        $serial = 0;

        foreach ($this->memos as $memo) {
            $serial++;
            $rows[] = [
                $serial,
                $memo->course->course_name ?? 'N/A',
                $memo->student->display_name ?? 'N/A',
                $memo->student->generated_OT_code ?? 'N/A',
                $memo->student->cadre->cadre_name ?? 'N/A',
                $memo->date ? Carbon::parse($memo->date)->format('d M Y') : 'N/A',
                $memo->discipline->discipline_name ?? 'N/A',
                $memo->mark_deduction_submit ?? '',
                $memo->final_mark_deduction ?? '',
                $memo->remarks ?? '',
                $memo->conclusion_remark ?? '',
                $memo->created_date ? Carbon::parse($memo->created_date)->format('d M Y') : 'N/A',
                $this->statusLabel($memo->status),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $this->lastCol;
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
                'font' => ['size' => 10],
            ]);

            // Alternating row shading
            $row = $dataRowStart;
            foreach ($this->memos as $memo) {
                if (($row - $dataRowStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                // Status badge color
                $statusCell = "{$lastCol}{$row}";
                $badgeColor = match ($memo->status) {
                    1 => '198754', // Recorded — green
                    2 => 'FFC107', // Memo Sent — amber
                    default => '6C757D', // Closed — gray
                };
                $sheet->getStyle($statusCell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $memo->status == 2 ? '212529' : 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $badgeColor]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row++;
            }
        }

        // Center a few columns
        $centerCols = ['A', 'F', 'H', 'I', 'L', 'M'];
        foreach ($centerCols as $col) {
            if ($lastRow >= $dataRowStart) {
                $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$lastRow}")
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
                $lastCol = $this->lastCol;

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
                $sheet->setCellValue('A2', 'DISCIPLINE MEMO REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: Filter info
                $sheet->mergeCells("A3:{$lastCol}3");
                $filterText = 'Program: ' . ($this->filters['program'] ?? 'All')
                    . '  |  Discipline: ' . ($this->filters['discipline'] ?? 'All')
                    . '  |  Status: ' . ($this->filters['status'] ?? 'All')
                    . '  |  Category: ' . ($this->filters['category'] ?? 'All')
                    . '  |  Period: ' . ($this->filters['period'] ?? 'All')
                    . '  |  Generated: ' . $this->exportDate;
                $sheet->setCellValue('A3', $filterText);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 4: Summary count
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->memos->count());
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
