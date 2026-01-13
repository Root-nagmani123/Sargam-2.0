<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PendingFeedbackExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected Builder $query;
    protected int $index = 0;

    /**
     * Receive prepared query from controller
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Fetch data
     */
    public function collection(): Collection
    {
        return $this->query->get();
    }

    /**
     * Excel headings
     */
    public function headings(): array
    {
        return [
            'S.No',
            'Student Name',
            'Email',
            'Phone',
            'OT Code',
            'Course',
            'Session Topic',
            'Faculty',
            'Venue',
            'Start Date',
            'Session Time',
        ];
    }

    /**
     * Row mapping
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
            $row->faculty_name ?? 'N/A',
            $row->venue_name ?? '',
            $row->from_date ? date('d-m-Y', strtotime($row->from_date)) : '',
            $row->class_session ?? '',
        ];
    }

    /**
     * Styling
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        /** 🔹 Header styling */
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFCC00'], // Yellow
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        /** 🔹 Data rows: borders + alignment */
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        /** 🔹 Alternate row coloring */
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F2F2F2'); // Light gray
            }
        }

        /** 🔹 Center align selected columns */
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
