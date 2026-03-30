<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PendingFeedbackSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected $students;
    protected $filters;

    public function __construct(Collection $students, array $filters = [])
    {
        $this->students = $students;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'User Name',
            'Email',
            'Contact No.',
            'Course Name',
            'Session Info',
            'Date Range',
            'Pending Feedback Count'
        ];
    }

    public function map($row): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        $pendingCount = $row->pending_count ?? 0;
        
        return [
            $rowNumber,
            $row->user_name ?? '—',
            $row->email ?? '—',
            $row->contact_no ?? '—',
            $row->course_name ?? '—',
            $row->session_info ?? 'Multiple Sessions',
            $row->date_range ?? '—',
            $pendingCount
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        
        // Style for header row (A1:H1)
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Style for data rows
        $sheet->getStyle('A2:H' . $highestRow)->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Special styling for pending count column (Column H)
        $sheet->getStyle('H2:H' . $highestRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ]);

        // Color code pending count based on value
        for ($row = 2; $row <= $highestRow; $row++) {
            $pendingCount = $sheet->getCell('H' . $row)->getValue();
            if ($pendingCount > 10) {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => 'DC3545']],
                ]);
            } elseif ($pendingCount > 5) {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFC107']],
                ]);
            } else {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => '17A2B8']],
                ]);
            }
        }

        // Freeze header row
        $sheet->freezePane('A2');
        
        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // S.No.
            'B' => 30,  // User Name
            'C' => 35,  // Email
            'D' => 15,  // Contact No.
            'E' => 25,  // Course Name
            'F' => 25,  // Session Info
            'G' => 25,  // Date Range
            'H' => 20,  // Pending Count
        ];
    }

    public function title(): string
    {
        return 'Pending Feedback Summary';
    }
}