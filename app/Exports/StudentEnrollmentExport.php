<?php

namespace App\Exports;

use App\Models\StudentMasterCourseMap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class StudentEnrollmentExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $enrollments;
    protected $courseName;
    protected $serialNumber = 1;

    public function __construct($enrollments, $courseName = 'All Active Courses')
    {
        $this->enrollments = $enrollments;
        $this->courseName = $courseName;
    }

    public function collection()
    {
        return $this->enrollments;
    }

    public function map($enrollment): array
    {
        $student = $enrollment->studentMaster ?? null;
        $course = $enrollment->course ?? null;

        static $serial = 1;
        $currentSerial = $serial++;

        return [
            $currentSerial,
            $student->pk ?? 'N/A',
            $course->pk ?? 'N/A',
            $student ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) : 'N/A',
            $student->email ?? 'N/A',
            $student->contact_no ?? 'N/A',
            $student->generated_OT_code ?? 'N/A',
            $student->rank ?? 'N/A',
            $enrollment->created_date ? Carbon::parse($enrollment->created_date)->format('d M Y') : 'N/A',
            $enrollment->active_inactive ? 'Active' : 'Inactive',
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'student_master_pk',
            'course_master_pk',
            'Student Name',
            'Email',
            'Phone',
            'OT Code',  // Changed from 'Course' to 'OT Code'
            'Rank',
            'Enrollment Date',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'e6423d'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        // All cells borders
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

        // Center alignment for serial numbers and status
        $sheet->getStyle("A1:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("G1:G{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Center alignment for OT Code
        $sheet->getStyle("E1:E{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Left alignment for other columns
        $sheet->getStyle("B1:D{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("F1:F{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Auto-size columns
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        // Add some spacing
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }

    public function title(): string
    {
        return 'Enrolled Students - ' . substr($this->courseName, 0, 25);
    }
}
