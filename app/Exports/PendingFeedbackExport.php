<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PendingFeedbackExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected Builder $query;
    protected int $index = 0;

    /**
     * Constructor accepts Query Builder
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Return the query to export
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'S.No.',
            'Student Name',
            'Email',
            'Phone',
            'OT Code',
            'Course',
            'Session Topic',
            // 'Faculty',
            // 'Venue',
            'Start Date',
            'End Date',
            'Session Time',
            'Generated On'
        ];
    }

    /**
     * Map each row to the Excel columns
     */
    public function map($row): array
    {
        return [
            ++$this->index,
            $row->student_name ?? '',
            $row->email ?? '',
            $row->contact_no ?? '',
            $row->generated_OT_code ?? '',
            $row->course_name ?? '',
            $row->subject_topic ?? '',
            // $row->faculty_name ?? 'N/A',
            // $row->venue_name ?? '',
            $row->from_date ? date('d-m-Y', strtotime($row->from_date)) : '',
            $row->to_date ? date('d-m-Y', strtotime($row->to_date)) : '',
            $row->class_session ?? '',
            now()->format('d-m-Y H:i:s')
        ];
    }

    /**
     * Apply styling to the Excel sheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data rows borders
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Alternate row coloring
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2F2F2');
            }
        }

        // Center align specific columns
        $centerColumns = ['A', 'D', 'E', 'J', 'K', 'L', 'M'];
        foreach ($centerColumns as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }
}