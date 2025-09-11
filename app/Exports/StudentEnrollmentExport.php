<?php

namespace App\Exports;

use App\Models\StudentMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentEnrollmentExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $courseId;
    protected $status;

    public function __construct($courseId = null, $status = null)
    {
        $this->courseId = $courseId;
        $this->status = $status;
    }

    public function collection()
    {
        $query = StudentMaster::with(['courses', 'service']);

        if ($this->courseId) {
            $query->whereHas('courses', function ($q) {
                $q->where('course_master.pk', $this->courseId);
            });
        }

        if ($this->status !== null && $this->status !== '') {
            $query->whereHas('courses', function ($q) {
                $q->where('student_master_course__map.active_inactive', $this->status);
            });
        }

        // Flatten pivoted course data for export
        $enrollments = [];
        foreach ($query->get() as $student) {
            foreach ($student->courses as $course) {
                $enrollments[] = (object)[
                    'student' => $student,
                    'course' => $course,
                    'active_inactive' => $course->pivot->active_inactive,
                    'created_date' => $course->pivot->created_date,
                    'modified_date' => $course->pivot->modified_date
                ];
            }
        }

        return collect($enrollments);
    }

    public function map($enrollment): array
    {
        $student = $enrollment->student;
        $course = $enrollment->course;

        return [
            $student->display_name,
            $student->email,
            $course->course_name ?? 'N/A',
            $student->generated_OT_code ?? '-',
            $student->service->service_name ?? 'N/A',
            $enrollment->active_inactive == 1 ? 'Active' : 'Inactive',
            $enrollment->created_date,
            $enrollment->modified_date,
        ];
    }

    public function headings(): array
    {
        return [
            'Student',
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

        // Borders + alignment for all cells
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

        // Header styling
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'], // Light Yellow
                ],
            ],
        ];
    }
}
