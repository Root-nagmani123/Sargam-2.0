<?php

namespace App\Exports;

use App\Models\StudentMedicalExemption;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};
use Carbon\Carbon;

class StudentMedicalExemptionExport implements FromCollection, WithHeadings
{
    protected $filter;
    protected $courseFilter;
    protected $dateFilter;
    protected $search;

    public function __construct($filter = 'active', $courseFilter = null, $search = null, $dateFilter = null)
    {
        $this->filter = $filter;
        $this->courseFilter = $courseFilter;
        $this->search = $search;
        $this->dateFilter = $dateFilter;
    }

    public function collection()
    {
        $query = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course', 'employee']);
        
        // Filter by course status (Active/Archive)
        $currentDate = now()->format('Y-m-d');
        
        if ($this->filter === 'active') {
            // Active Courses: end_date > current date
            $query->whereHas('course', function($q) use ($currentDate) {
                $q->where('end_date', '>', $currentDate);
            });
        } elseif ($this->filter === 'archive') {
            // Archive Courses: end_date < current date
            $query->whereHas('course', function($q) use ($currentDate) {
                $q->where('end_date', '<', $currentDate);
            });
        }
        
        // Filter by specific course if selected
        if ($this->courseFilter) {
            $query->where('course_master_pk', $this->courseFilter);
        }
        
        // Filter by today's date if date_filter is 'today'
        if ($this->dateFilter === 'today') {
            // Show records where today's date falls within the exemption period
            $query->where('from_date', '<=', $currentDate)
                  ->where(function($q) use ($currentDate) {
                      $q->where('to_date', '>=', $currentDate)
                        ->orWhereNull('to_date');
                  });
        }
        
        // Search functionality
        if ($this->search && $this->search != '') {
            $query->where(function($q) {
                // Search in student name
                $q->whereHas('student', function($studentQuery) {
                    $studentQuery->where('display_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('generated_OT_code', 'like', '%' . $this->search . '%');
                })
                // Search in course name
                ->orWhereHas('course', function($courseQuery) {
                    $courseQuery->where('course_name', 'like', '%' . $this->search . '%');
                })
                // Search in employee name
                ->orWhereHas('employee', function($employeeQuery) {
                    $employeeQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                // Search in category name
                ->orWhereHas('category', function($categoryQuery) {
                    $categoryQuery->where('exemp_category_name', 'like', '%' . $this->search . '%');
                })
                // Search in speciality name
                ->orWhereHas('speciality', function($specialityQuery) {
                    $specialityQuery->where('speciality_name', 'like', '%' . $this->search . '%');
                })
                // Search in OPD category
                ->orWhere('opd_category', 'like', '%' . $this->search . '%');
            });
        }
        
        $data = $query->orderBy('pk', 'desc')->get();

        return $data->map(function ($record) {
            return [
                'student_name' => optional($record->student)->display_name ?? 'N/A',
                'ot_code' => optional($record->student)->generated_OT_code ?? 'N/A',
                'course' => optional($record->course)->course_name ?? 'N/A',
                'faculty' => ($record->employee && $record->employee->first_name && $record->employee->last_name) ? trim($record->employee->first_name . ' ' . $record->employee->last_name) : 'N/A',
                'category' => optional($record->category)->exemp_category_name ?? 'N/A',
                'medical_speciality' => optional($record->speciality)->speciality_name ?? 'N/A',
                'from_date' => $record->from_date ? Carbon::parse($record->from_date)->format('d-m-Y') : 'N/A',
                'to_date' => $record->to_date ? Carbon::parse($record->to_date)->format('d-m-Y') : 'N/A',
                'opd_category' => $record->opd_category ?? 'N/A',
                'document' => $record->Doc_upload ? asset('storage/' . $record->Doc_upload) : 'N/A',
                'description' => $record->Description ?? 'N/A',
                'status' => $record->active_inactive == 1 ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'OT Code',
            'Course',
            'Faculty',
            'Category',
            'Medical Speciality',
            'From Date',
            'To Date',
            'OPD Category',
            'Document',
            'Description',
            'Status'
        ];
    }
}

