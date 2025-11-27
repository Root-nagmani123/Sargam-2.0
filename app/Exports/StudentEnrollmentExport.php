<?php

namespace App\Exports;

use App\Models\StudentMasterCourseMap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class StudentEnrollmentExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $enrollments;

    public function __construct($enrollments)
    {
        $this->enrollments = $enrollments;
    }

    public function collection()
    {
        // Return the pre-filtered enrollments
        return $this->enrollments;
    }

    public function map($enrollment): array
    {
        $student = $enrollment->studentMaster;
        $course = $enrollment->course;

        return [
            $enrollment->id ?? '-', // Add serial number or unique identifier
            trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')),
            $student->email ?? '-',
            $course->course_name ?? 'N/A',
            $student->generated_OT_code ?? '-',
            $student->service->service_name ?? 'N/A',
            (int) $enrollment->active_inactive === 1 ? 'Active' : 'Inactive',
            $enrollment->created_date ? Carbon::parse($enrollment->created_date)->format('d M Y H:i') : '-',
            $enrollment->modified_date ? Carbon::parse($enrollment->modified_date)->format('d M Y H:i') : '-',
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Student Name',
            'Email',
            'Course',
            'OT Code',
            'Service',
            'Status',
            'Created Date',
            'Modified Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")
            ->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        // All cells borders and alignment
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        // Data rows left alignment for better readability
        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }
}