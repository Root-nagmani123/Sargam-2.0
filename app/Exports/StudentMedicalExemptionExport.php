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
        return $this->recordsQuery()->map(fn ($record) => $this->mapRecord($record));
    }

    public function pdfRows(): Collection
    {
        return $this->recordsQuery()->map(fn ($record) => $this->mapRecord($record));
    }

    private function recordsQuery()
    {
        $query = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course', 'employee']);

        // Filter by course status (Active/Archive)
        $currentDate = now()->format('Y-m-d');

        if ($this->filter === 'active') {
            $query->whereHas('course', function ($q) use ($currentDate) {
                $q->where('end_date', '>', $currentDate);
            });
        } elseif ($this->filter === 'archive') {
            $query->whereHas('course', function ($q) use ($currentDate) {
                $q->where('end_date', '<', $currentDate);
            });
        }

        if ($this->courseFilter) {
            $query->where('course_master_pk', $this->courseFilter);
        }

        if ($this->fromDateFilter || $this->toDateFilter) {
            if ($this->fromDateFilter && $this->toDateFilter) {
                $query->where(function ($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                })
                ->where('from_date', '<=', $this->toDateFilter);
            } elseif ($this->fromDateFilter) {
                $query->where(function ($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                });
            } elseif ($this->toDateFilter) {
                $query->where('from_date', '<=', $this->toDateFilter);
            }
        }

        if ($this->search && $this->search != '') {
            $query->where(function ($q) {
                $q->whereHas('student', function ($studentQuery) {
                    $studentQuery->where('display_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('generated_OT_code', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('course', function ($courseQuery) {
                    $courseQuery->where('course_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('employee', function ($employeeQuery) {
                    $employeeQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('exemp_category_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('speciality', function ($specialityQuery) {
                    $specialityQuery->where('speciality_name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('opd_category', 'like', '%' . $this->search . '%')
                ->orWhere('Description', 'like', '%' . $this->search . '%')
                ->orWhere('pt_outdoor_advise', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('pk', 'desc')->get();
    }

    private function mapRecord($record): array
    {
        $from = $record->from_date ? Carbon::parse($record->from_date) : null;
        $to = $record->to_date ? Carbon::parse($record->to_date) : null;

        $studentName = optional($record->student)->display_name ?? 'N/A';
        $studentOt = optional($record->student)->generated_OT_code;

        return [
            'student_name' => ($studentOt && $studentName !== 'N/A')
                ? "{$studentName} ({$studentOt})"
                : $studentName,
            'ot_code' => optional($record->student)->generated_OT_code ?? 'N/A',
            'course' => optional($record->course)->course_name ?? 'N/A',
            'faculty' => ($record->employee && $record->employee->first_name && $record->employee->last_name) ? trim($record->employee->first_name . ' ' . $record->employee->last_name) : 'N/A',
            'category' => optional($record->category)->exemp_category_name ?? 'N/A',
            'medical_speciality' => optional($record->speciality)->speciality_name ?? 'N/A',
            'opd_category' => $record->opd_category ?? 'N/A',
            'arrival_date' => $from ? $from->format('d-m-Y') : 'N/A',
            'arrival_time' => $from ? $from->format('h:i A') : 'N/A',
            'departure_date' => $to ? $to->format('d-m-Y') : 'N/A',
            'departure_time' => $to ? $to->format('h:i A') : 'N/A',
            'days' => $record->days ?? 'N/A',
            'description' => $record->Description ?? 'N/A',
            'pt_outdoor_advise' => $record->pt_outdoor_advise ?? 'N/A',
        ];
    }

    /**
     * The flat list of data-column headings (used by the PDF/Print layouts).
     *
     * @return array<int, string>
     */
    public function columnHeadings(): array
    {
        return [
            'Student Name',
            'OT Code',
            'Course',
            'Faculty',
            'Category',
            'Medical Speciality',
            'OPD Category',
            'Arrival Date',
            'Arrival Time',
            'Departure Date',
            'Departure Time',
            'Days',
            'Provisional Diagnosis/ Remarks',
            'PT/Outdoor Advise',
        ];
    }

    /**
     * CSV headings: branded identity rows (institution / report title / course /
     * generated on) followed by the column heading row — mirrors the Print & PDF
     * layout so all three exports carry the same header.
     */
    public function headings(): array
    {
        $meta = [
            ['Lal Bahadur Shastri National Academy of Administration, Mussoorie'],
            ['Student Medical Exemption'],
        ];

        $courseLine = $this->exportCourseLine();
        if ($courseLine !== '') {
            $meta[] = [$courseLine];
        }

        $meta[] = ['Generated on: ' . now()->format('d-m-Y H:i')];
        $meta[] = []; // blank spacer row

        $meta[] = $this->columnHeadings();

        return $meta;
    }

    /**
     * "Course Name (start to end)" for the selected course filter, or ''.
     */
    private function exportCourseLine(): string
    {
        if (empty($this->courseFilter)) {
            return '';
        }

        $course = \App\Models\CourseMaster::find($this->courseFilter);
        if (! $course) {
            return '';
        }

        $line = (string) ($course->course_name ?? '');
        $start = ! empty($course->start_date ?? $course->start_year ?? null)
            ? Carbon::parse($course->start_date ?? $course->start_year)->format('j F Y') : '';
        $end = ! empty($course->end_date)
            ? Carbon::parse($course->end_date)->format('j F Y') : '';
        if ($start && $end) {
            $line = trim($line . ' (' . $start . ' to ' . $end . ')');
        }

        return $line;
    }
}

