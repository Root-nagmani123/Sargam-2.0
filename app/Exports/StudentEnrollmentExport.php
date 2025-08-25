<?php

namespace App\Exports;

// use App\Models\StudentMaster;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;

// class StudentEnrollmentExport implements FromCollection, WithHeadings, WithMapping
// {
//     protected $course;
//     protected $status;

//     public function __construct($course = null, $status = null)
//     {
//         $this->course = $course;
//         $this->status = $status;
//     }

//     public function collection()
//     {
//         $query = StudentMaster::with(['courses', 'service']);

//         if ($this->course) {
//             $query->whereHas('courses', function ($q) {
//                 $q->where('pk', $this->course); // filter by course pk
//             });
//         }

//         if ($this->status) {
//             $query->whereHas('courses', function ($q) {
//                 $q->where('active_inactive', $this->status);
//             });
//         }

//         return $query->get();
//     }

//     public function map($student): array
//     {
//         // Get all courses joined by comma
//         $courseNames = $student->courses->pluck('course_name')->join(', ');

//         return [
//             $student->pk,
//             trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')),
//             $courseNames,
//             $student->service->service_name ?? 'N/A',
//             $student->pivot->active_inactive ?? '-', // careful: pivot only when using courses()
//             $student->created_date,
//             $student->modified_date,
//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             'ID',
//             'Student',
//             'Course(s)',
//             'Service',
//             'Status',
//             'Created Date',
//             'Modified Date',
//         ];
//     }
// }

use App\Models\StudentMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentEnrollmentExport implements FromCollection, WithHeadings, WithMapping
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
}
