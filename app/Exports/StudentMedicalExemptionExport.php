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
    protected $fromDateFilter;
    protected $toDateFilter;
    protected $search;

    public function __construct($filter = 'active', $courseFilter = null, $search = null, $fromDateFilter = null, $toDateFilter = null)
    {
        $this->filter = $filter;
        $this->courseFilter = $courseFilter;
        $this->search = $search;
        $this->fromDateFilter = $fromDateFilter;
        $this->toDateFilter = $toDateFilter;
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
        
        // Filter by date range
        if ($this->fromDateFilter || $this->toDateFilter) {
            if ($this->fromDateFilter && $this->toDateFilter) {
                // Both dates provided: exemption period overlaps if (to_date >= from_date_filter OR to_date IS NULL) AND from_date <= to_date_filter
                $query->where(function($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                })
                ->where('from_date', '<=', $this->toDateFilter);
            } elseif ($this->fromDateFilter) {
                // Only from_date provided: show records where to_date >= from_date_filter OR to_date IS NULL
                $query->where(function($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                });
            } elseif ($this->toDateFilter) {
                // Only to_date provided: show records where from_date <= to_date_filter
                $query->where('from_date', '<=', $this->toDateFilter);
            }
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

