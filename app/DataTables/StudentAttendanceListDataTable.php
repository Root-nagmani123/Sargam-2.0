<?php

namespace App\DataTables;

use App\Models\{StudentCourseGroupMap, GroupTypeMasterCourseMasterMap, MDOEscotDutyMap, CourseStudentAttendance, StudentMedicalExemption};
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class StudentAttendanceListDataTable extends DataTable
{
    public function __construct(
        protected int $group_pk,
        protected int $course_pk,
        protected int $timetable_pk
    ) {}

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('student_name', fn($row) => '<label class="text-dark">' . $row->studentsMaster->display_name . '</label>')
            ->addColumn('student_code', fn($row) => '<label class="text-dark">' . $row->studentsMaster->generated_OT_code . '</label>')
            ->addColumn('attendance_status', fn($row) => $this->renderRadioGroup($row, 'attendance_status', [1 => 'Present', 2 => 'Late', 3 => 'Absent']))
            ->addColumn('mdo_duty', fn($row) => $this->renderRadio($row, 4, 'MDO'))
            ->addColumn('escort_duty', fn($row) => $this->renderRadio($row, 5, 'Escort'))
            ->addColumn('medical_exempt', fn($row) => $this->renderRadio($row, 6, 'Medical Exempted'))
            ->addColumn('other_exempt', fn($row) => $this->renderRadio($row, 7, 'Other Exempted'))
            ->filterColumn('student_name', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('display_name', 'like', "%{$keyword}%")))
            ->filterColumn('student_code', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('generated_OT_code', 'like', "%{$keyword}%")))
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentsMaster', function ($studentQuery) use ($searchValue) {
                            $studentQuery->where('display_name', 'like', "%{$searchValue}%")
                                ->orWhere('generated_OT_code', 'like', "%{$searchValue}%");
                        });
                    });
                }
            }, true)
            ->rawColumns(['student_name', 'student_code', 'attendance_status', 'mdo_duty', 'escort_duty', 'medical_exempt', 'other_exempt']);
    }

    public function query(): QueryBuilder
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $this->group_pk)
            ->where('course_name', $this->course_pk)

            ->firstOrFail();

        return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,pk',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('group_type_master_course_master_map_pk', $this->group_pk)
            ])
            ->where('group_type_master_course_master_map_pk', $groupTypeMaster->pk);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('studentAttendanceTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters([
                'paging' => false,           
                'searching' => true,         
                'info' => false,             
                'scrollY' => '100vh',        
                'scrollCollapse' => true,
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('student_name')->title('OT Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('student_code')->title('OT Code')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('attendance_status')->title('Attendance')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_duty')->title('MDO Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('escort_duty')->title('Escort Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('medical_exempt')->title('Medical Exemption')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('other_exempt')->title('Other Exemption')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function renderRadio($row, int $value, string $label, string $labelClass = 'text-dark'): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where([
            ['Student_master_pk', '=', $studentId],
            ['Course_master_pk', '=', $this->course_pk],
            ['group_type_master_course_master_map_pk', '=', $this->group_pk],
            ['timetable_pk', '=', $this->timetable_pk]
        ])->first();

        $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';
            
        if (!$courseStudent && !$checked) {
            $timetable = Timetable::with('classSession')->where('pk', $this->timetable_pk)->first();
            
            if(!empty($timetable) && !empty($timetable->START_DATE)) {
                // Handle Medical Exemption (value 6) - stored in StudentMedicalExemption table (date only, no time)
                if ($value == 6) {
                    $medicalExemption = StudentMedicalExemption::where([
                        ['course_master_pk', '=', $this->course_pk],
                        ['student_master_pk', '=', $studentId],
                        ['active_inactive', '=', 1]
                    ])
                    ->where(function($query) use ($timetable) {
                        $query->where(function($q) use ($timetable) {
                            // Check if date falls within the exemption period
                            $q->where('from_date', '<=', $timetable->START_DATE)
                              ->where(function($subQ) use ($timetable) {
                                  $subQ->whereNull('to_date')
                                       ->orWhere('to_date', '>=', $timetable->START_DATE);
                              });
                        });
                    })->first();

                    if ($medicalExemption) {
                        $checked = 'checked';
                    }
                } else {
                    // Handle MDO, Escort, and Other exemptions (values 4, 5, 7) - stored in MDOEscotDutyMap
                    // Check date and time overlap with session time
                    $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();
                    
                    match ($value) {
                        4 => $dutyType = $mdoDutyTypes['mdo'] ?? null,
                        5 => $dutyType = $mdoDutyTypes['escort'] ?? null,
                        7 => $dutyType = $mdoDutyTypes['other'] ?? null,
                        default => $dutyType = null,
                    };

                    if ($dutyType !== null && $dutyType > 0) {
                        $mdoEscotRecords = MDOEscotDutyMap::where([
                            ['course_master_pk', '=', $this->course_pk],
                            ['mdo_duty_type_master_pk', '=', $dutyType],
                            ['selected_student_list', '=', $studentId]
                        ])
                        ->whereDate('mdo_date', '=', $timetable->START_DATE)
                        ->get();

                        // Get manual session time if classSession is not available (for manual sessions)
                        $manualSessionTime = null;
                        if (empty($timetable->classSession) && !empty($timetable->class_session)) {
                            $manualSessionTime = $timetable->class_session;
                        }

                        // Check if any record has time overlap with session time
                        foreach ($mdoEscotRecords as $mdoEscot) {
                            if ($this->checkTimeOverlap(
                                $timetable->classSession?->start_time,
                                $timetable->classSession?->end_time,
                                $mdoEscot->Time_from,
                                $mdoEscot->Time_to,
                                $manualSessionTime
                            )) {
                                $checked = 'checked';
                                break;
                            }
                        }
                    }
                }
            }
        }

        return "<div class='form-check form-check-inline'>
                    <input class='form-check-input' type='radio' name='student[{$studentId}]' value='{$value}' {$checked} id='student[{$studentId}][{$value}]'>
                    <label class='form-check-label {$labelClass}' for='student[{$studentId}][{$value}]'>{$label}</label>
                </div>";
    }

    protected function renderRadioGroup($row, string $field, array $options): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where([
            ['Student_master_pk', '=', $studentId],
            ['Course_master_pk', '=', $this->course_pk],
            ['group_type_master_course_master_map_pk', '=', $this->group_pk],
            ['timetable_pk', '=', $this->timetable_pk]
        ])->first();

        $html = "<input type='hidden' name='student[{$studentId}]' value='0'>";

        // Determine default checked value
        $defaultCheckedValue = null;
        
        // If there's an existing attendance record, use its status
        if ($courseStudent) {
            $defaultCheckedValue = $courseStudent->status;
        } else {
            // Check if student has any exemptions or duties
            $hasExemptionOrDuty = $this->hasExemptionOrDuty($studentId);
            
            // If no exemptions or duties, default to Present (1)
            if (!$hasExemptionOrDuty) {
                $defaultCheckedValue = 1; // Present
            }
        }

        foreach ($options as $value => $label) {
            $labelClass = match ($value) {
                1 => 'text-success',
                2 => 'text-warning',
                3 => 'text-danger',
                default => 'text-dark',
            };

            $checked = ($defaultCheckedValue !== null && $defaultCheckedValue == $value) ? 'checked' : '';

            $html .= "<div class='form-check form-check-inline'>
                        <input class='form-check-input' type='radio' name='student[{$studentId}]' value='{$value}' {$checked} id='student[{$studentId}][{$value}]'>
                        <label class='form-check-label {$labelClass}' for='student[{$studentId}][{$value}]'>{$label}</label>
                    </div>";
        }

        return $html;
    }

    /**
     * Check if student has MDO duty, Escort duty, Medical Exemption, or Other Exemption
     */
    protected function hasExemptionOrDuty(int $studentId): bool
    {
        $timetable = Timetable::with('classSession')->where('pk', $this->timetable_pk)->first();
        
        if (empty($timetable) || empty($timetable->START_DATE)) {
            return false;
        }

        $timetableDate = $timetable->START_DATE;
        $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

        // Get manual session time if classSession is not available (for manual sessions)
        $manualSessionTime = null;
        if (empty($timetable->classSession) && !empty($timetable->class_session)) {
            $manualSessionTime = $timetable->class_session;
        }

        // Check for MDO duty (value 4)
        if (!empty($mdoDutyTypes['mdo'])) {
            $mdoDutyRecords = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['mdo']],
                ['selected_student_list', '=', $studentId]
            ])->whereDate('mdo_date', '=', $timetableDate)->get();

            foreach ($mdoDutyRecords as $mdoDuty) {
                if ($this->checkTimeOverlap(
                    $timetable->classSession?->start_time,
                    $timetable->classSession?->end_time,
                    $mdoDuty->Time_from,
                    $mdoDuty->Time_to,
                    $manualSessionTime
                )) {
                    return true;
                }
            }
        }

        // Check for Escort duty (value 5)
        if (!empty($mdoDutyTypes['escort'])) {
            $escortDutyRecords = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['escort']],
                ['selected_student_list', '=', $studentId]
            ])->whereDate('mdo_date', '=', $timetableDate)->get();

            foreach ($escortDutyRecords as $escortDuty) {
                if ($this->checkTimeOverlap(
                    $timetable->classSession?->start_time,
                    $timetable->classSession?->end_time,
                    $escortDuty->Time_from,
                    $escortDuty->Time_to,
                    $manualSessionTime
                )) {
                    return true;
                }
            }
        }

        // Check for Other Exemption (value 7)
        if (!empty($mdoDutyTypes['other'])) {
            $otherExemptionRecords = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['other']],
                ['selected_student_list', '=', $studentId]
            ])->whereDate('mdo_date', '=', $timetableDate)->get();

            foreach ($otherExemptionRecords as $otherExemption) {
                if ($this->checkTimeOverlap(
                    $timetable->classSession?->start_time,
                    $timetable->classSession?->end_time,
                    $otherExemption->Time_from,
                    $otherExemption->Time_to,
                    $manualSessionTime
                )) {
                    return true;
                }
            }
        }

        // Check for Medical Exemption (value 6) - date only, no time
        $medicalExemption = StudentMedicalExemption::where([
            ['course_master_pk', '=', $this->course_pk],
            ['student_master_pk', '=', $studentId],
            ['active_inactive', '=', 1]
        ])
        ->where(function($query) use ($timetableDate) {
            $query->where(function($q) use ($timetableDate) {
                // Check if date falls within the exemption period
                $q->where('from_date', '<=', $timetableDate)
                  ->where(function($subQ) use ($timetableDate) {
                      $subQ->whereNull('to_date')
                           ->orWhere('to_date', '>=', $timetableDate);
                  });
            });
        })->exists();

        if ($medicalExemption) {
            return true;
        }

        return false;
    }

    /**
     * Check if two time ranges overlap
     * 
     * @param string|null $sessionStart Session start time (H:i:s format) or null for manual sessions
     * @param string|null $sessionEnd Session end time (H:i:s format) or null for manual sessions
     * @param string|null $exemptionStart Exemption start time (H:i format)
     * @param string|null $exemptionEnd Exemption end time (H:i format)
     * @param string|null $manualSessionTime Manual session time string (e.g., "13:05 - 14:00" or "01:05 PM - 02:00 PM")
     * @return bool
     */
    protected function checkTimeOverlap(?string $sessionStart, ?string $sessionEnd, ?string $exemptionStart, ?string $exemptionEnd, ?string $manualSessionTime = null): bool
    {
        // If exemption times are not available, fall back to date-only check (return true)
        if (empty($exemptionStart) || empty($exemptionEnd)) {
            return true; // If no exemption time info, consider it overlapping (date match is enough)
        }

        // Parse session times - either from classSession relationship or manual session string
        $parsedSessionStart = null;
        $parsedSessionEnd = null;

        if (!empty($sessionStart) && !empty($sessionEnd)) {
            // Normal session with classSession relationship
            $parsedSessionStart = $sessionStart;
            $parsedSessionEnd = $sessionEnd;
        } elseif (!empty($manualSessionTime)) {
            // Manual session - parse the time string
            // Format examples: "13:05 - 14:00" or "01:05 PM - 02:00 PM"
            $parsedTimes = $this->parseManualSessionTime($manualSessionTime);
            if ($parsedTimes) {
                $parsedSessionStart = $parsedTimes['start'];
                $parsedSessionEnd = $parsedTimes['end'];
            }
        }

        // If session times are still not available, fall back to date-only check (return true)
        if (empty($parsedSessionStart) || empty($parsedSessionEnd)) {
            return true; // If no session time info, consider it overlapping (date match is enough)
        }

        try {
            // Convert times to Carbon instances for comparison (normalize to same date for comparison)
            $baseDate = Carbon::today();
            $sessionStartTime = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $parsedSessionStart);
            $sessionEndTime = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $parsedSessionEnd);
            
            // Handle Time_from and Time_to which may be in H:i format
            $exemptionStartTime = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $exemptionStart);
            $exemptionEndTime = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $exemptionEnd);

            // Two time ranges overlap if: start1 < end2 AND start2 < end1
            return $exemptionStartTime->lt($sessionEndTime) && $exemptionEndTime->gt($sessionStartTime);
        } catch (\Exception $e) {
            // If time parsing fails, fall back to date-only check
            return true;
        }
    }

    /**
     * Parse manual session time string to extract start and end times
     * 
     * @param string $manualSessionTime Manual session time string (e.g., "13:05 - 14:00" or "01:05 PM - 02:00 PM")
     * @return array|null Array with 'start' and 'end' keys in H:i format, or null if parsing fails
     */
    protected function parseManualSessionTime(string $manualSessionTime): ?array
    {
        // Remove extra spaces and split by common delimiters
        $cleaned = trim($manualSessionTime);
        
        // Try splitting by " - " (common format)
        if (strpos($cleaned, ' - ') !== false) {
            $parts = explode(' - ', $cleaned);
        } elseif (strpos($cleaned, '-') !== false) {
            $parts = explode('-', $cleaned);
        } else {
            return null;
        }

        if (count($parts) !== 2) {
            return null;
        }

        $start = trim($parts[0]);
        $end = trim($parts[1]);

        try {
            // Convert to 24-hour format if in 12-hour format
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            return [
                'start' => $startTime->format('H:i:s'),
                'end' => $endTime->format('H:i:s')
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function filename(): string
    {
        return 'StudentAttendanceList_' . date('YmdHis');
    }
}
