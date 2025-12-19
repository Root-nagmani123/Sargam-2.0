<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacultyFeedbackExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Program Name',
            'Course Status',
            'Faculty Name',
            'Faculty Type',
            'Topic',
            'Lecture Date',
            'Start Time',
            'End Time',
            'Total Participants',
            'Content - Excellent',
            'Content - Very Good',
            'Content - Good',
            'Content - Average',
            'Content - Below Average',
            'Content Percentage',
            'Presentation - Excellent',
            'Presentation - Very Good',
            'Presentation - Good',
            'Presentation - Average',
            'Presentation - Below Average',
            'Presentation Percentage',
            'Remarks'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Make header row bold with colors
        $sheet->getStyle('A1:V1')->getFont()->setBold(true);
        $sheet->getStyle('A1:V1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:V1')->getFill()->getStartColor()->setARGB('FFE8F4FF');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25); // Program Name
        $sheet->getColumnDimension('B')->setWidth(15); // Course Status
        $sheet->getColumnDimension('C')->setWidth(25); // Faculty Name
        $sheet->getColumnDimension('D')->setWidth(15); // Faculty Type
        $sheet->getColumnDimension('E')->setWidth(25); // Topic
        $sheet->getColumnDimension('F')->setWidth(15); // Lecture Date
        $sheet->getColumnDimension('G')->setWidth(12); // Start Time
        $sheet->getColumnDimension('H')->setWidth(12); // End Time
        $sheet->getColumnDimension('I')->setWidth(15); // Total Participants
        $sheet->getColumnDimension('J')->setWidth(15); // Content - Excellent
        $sheet->getColumnDimension('K')->setWidth(15); // Content - Very Good
        $sheet->getColumnDimension('L')->setWidth(12); // Content - Good
        $sheet->getColumnDimension('M')->setWidth(12); // Content - Average
        $sheet->getColumnDimension('N')->setWidth(18); // Content - Below Average
        $sheet->getColumnDimension('O')->setWidth(15); // Content Percentage
        $sheet->getColumnDimension('P')->setWidth(18); // Presentation - Excellent
        $sheet->getColumnDimension('Q')->setWidth(18); // Presentation - Very Good
        $sheet->getColumnDimension('R')->setWidth(15); // Presentation - Good
        $sheet->getColumnDimension('S')->setWidth(15); // Presentation - Average
        $sheet->getColumnDimension('T')->setWidth(20); // Presentation - Below Average
        $sheet->getColumnDimension('U')->setWidth(18); // Presentation Percentage
        $sheet->getColumnDimension('V')->setWidth(40); // Remarks

        // Wrap text for remarks column
        $sheet->getStyle('V')->getAlignment()->setWrapText(true);

        // Add borders
        $sheet->getStyle('A1:V' . ($this->data->count() + 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Center align numeric columns
        $sheet->getStyle('I:U')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function title(): string
    {
        return 'Faculty Feedback';
    }
}
