<?php

namespace App\DataTables;

use App\Models\{StudentCourseGroupMap, GroupTypeMasterCourseMasterMap, MDOEscotDutyMap, CourseStudentAttendance, StudentMedicalExemption};
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

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
            ->first();

        if (!$groupTypeMaster) {
            // Return an empty query to avoid throwing ModelNotFoundException
            return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,pk',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('group_type_master_course_master_map_pk', $this->group_pk)
            ])->whereRaw('1=0');
        }

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
                'searching' => false,         
                'info' => false,             
                'scrollY' => '100vh',        
                'scrollCollapse' => true,
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'paginationType' => 'full_numbers',

                'language' => [
                    'search' => '_INPUT_',
                    'searchPlaceholder' => 'Search OT Name or OT Code...'
                ],

            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('student_name')->title('OT/Participant Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('student_code')->title('OT/Participant Code')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('attendance_status')->title('Attendance')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_duty')->title('MDO Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('escort_duty')->title('Escort/Moderator Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('medical_exempt')->title('Medical Exemption')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('other_exempt')->title('Other Exemption')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function renderRadio($row, int $value, string $label, string $labelClass = 'text-dark'): string
    {
        if ($value === 5) {

    $studentId = $row->studentsMaster->pk;
    $timetable = Timetable::select('START_DATE', 'class_session')
        ->where('pk', $this->timetable_pk)
        ->first();

    if ($timetable) {
        $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();
        $escortType = $mdoDutyTypes['escort'] ?? null;

        if ($escortType) {
            $escortDuty = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $escortType],
                ['selected_student_list', '=', $studentId]
            ])
            ->whereDate('mdo_date', '=', $timetable->START_DATE)
            ->first();

            if ($escortDuty && $this->checkTimeOverlap(
                $timetable->class_session,
                $escortDuty->Time_from,
                $escortDuty->Time_to
            )) {
                // Escort laga hua hai → sirf text dikhao
                return "<span class='text-info fw-bold'>Escort/Moderator</span>";
            }
        }
    }

    // Escort nahi hai → N/A
    return "<span class='text-muted'>N/A</span>";
}

        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where([
            ['Student_master_pk', '=', $studentId],
            ['Course_master_pk', '=', $this->course_pk],
            ['group_type_master_course_master_map_pk', '=', $this->group_pk],
            ['timetable_pk', '=', $this->timetable_pk]
        ])->first();

        // First check if attendance is already marked with this status value
        $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';
        
        // Even if attendance is marked, we should still check for duty/exemption to show radio buttons
        // But only if this radio button is not already checked
        if (!$checked) {
            $timetable = Timetable::select('START_DATE', 'class_session')->where('pk', $this->timetable_pk)->first();
            
            if(!empty($timetable)) {
                // Handle Medical Exemption (value 6) - stored in StudentMedicalExemption table
                if ($value == 6) {
                    $medicalExemption = StudentMedicalExemption::where([
                        ['course_master_pk', '=', $this->course_pk],
                        ['student_master_pk', '=', $studentId],
                        ['active_inactive', '=', 1]
                    ])
                    ->where(function($query) use ($timetable) {
                        $query->where(function($q) use ($timetable) {
                            // Check if date falls within the exemption period (date only)
                            $q->whereDate('from_date', '<=', $timetable->START_DATE)
                              ->where(function($subQ) use ($timetable) {
                                  $subQ->whereNull('to_date')
                                       ->orWhereDate('to_date', '>=', $timetable->START_DATE);
                              });
                        });
                    })->first();

                    if ($medicalExemption) {
                        // Extract time from from_date and to_date, then check time overlap with class_session
                        $exemptionTimeFrom = $medicalExemption->from_date ? date('H:i:s', strtotime($medicalExemption->from_date)) : null;
                        $exemptionTimeTo = $medicalExemption->to_date ? date('H:i:s', strtotime($medicalExemption->to_date)) : null;
                        
                        if ($exemptionTimeFrom && $exemptionTimeTo) {
                            // Check if class_session time overlaps with medical exemption time range
                            if ($this->checkTimeOverlap($timetable->class_session, $exemptionTimeFrom, $exemptionTimeTo)) {
                                $checked = 'checked';
                            }
                        } else {
                            // If times are not available, just check date match
                            $checked = 'checked';
                        }
                    }
                } else {
                    // Handle MDO, Escort, and Other exemptions (values 4, 5, 7) - stored in MDOEscotDutyMap
                    $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();
                    
                    match ($value) {
                        4 => $dutyType = $mdoDutyTypes['mdo'] ?? null,
                        5 => $dutyType = $mdoDutyTypes['escort'] ?? null,
                        7 => $dutyType = $mdoDutyTypes['other'] ?? null,
                        default => $dutyType = null,
                    };

                    if ($dutyType !== null && $dutyType > 0) {
                        $mdoEscot = MDOEscotDutyMap::where([
                            ['course_master_pk', '=', $this->course_pk],
                            ['mdo_duty_type_master_pk', '=', $dutyType],
                            ['selected_student_list', '=', $studentId]
                        ])
                        ->whereDate('mdo_date', '=', $timetable->START_DATE)->first();

                        if ($mdoEscot) {
                            // Check if class_session time overlaps with duty Time_from and Time_to
                            if ($this->checkTimeOverlap($timetable->class_session, $mdoEscot->Time_from, $mdoEscot->Time_to)) {
                                $checked = 'checked';
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
        // print_r($courseStudent);die;
        // If there's an existing attendance record, use its status
        if ($courseStudent) {
            $defaultCheckedValue = $courseStudent->status;
            if( $defaultCheckedValue == 5 ){
                $defaultCheckedValue = 1;
            }
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
        $timetable = Timetable::select('START_DATE', 'class_session')->where('pk', $this->timetable_pk)->first();
        
        if (empty($timetable)) {
            return false;
        }

        $timetableDate = $timetable->START_DATE;
        $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

        // Check for MDO duty (value 4)
        if (!empty($mdoDutyTypes['mdo'])) {
            $mdoDuty = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['mdo']],
                ['selected_student_list', '=', $studentId]
            ])->whereDate('mdo_date', '=', $timetableDate)->first();

            if ($mdoDuty && $this->checkTimeOverlap($timetable->class_session, $mdoDuty->Time_from, $mdoDuty->Time_to)) {
                return true;
            }
        }

        // Check for Escort duty (value 5)
        // if (!empty($mdoDutyTypes['escort'])) {
        //     $escortDuty = MDOEscotDutyMap::where([
        //         ['course_master_pk', '=', $this->course_pk],
        //         ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['escort']],
        //         ['selected_student_list', '=', $studentId]
        //     ])->whereDate('mdo_date', '=', $timetableDate)->first();

        //     if ($escortDuty && $this->checkTimeOverlap($timetable->class_session, $escortDuty->Time_from, $escortDuty->Time_to)) {
        //         return true;
        //     }
        // }

        // Check for Other Exemption (value 7)
        if (!empty($mdoDutyTypes['other'])) {
            $otherExemption = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['other']],
                ['selected_student_list', '=', $studentId]
            ])->whereDate('mdo_date', '=', $timetableDate)->first();

            if ($otherExemption && $this->checkTimeOverlap($timetable->class_session, $otherExemption->Time_from, $otherExemption->Time_to)) {
                return true;
            }
        }

        // Check for Medical Exemption (value 6)
        $medicalExemption = StudentMedicalExemption::where([
            ['course_master_pk', '=', $this->course_pk],
            ['student_master_pk', '=', $studentId],
            ['active_inactive', '=', 1]
        ])
        ->where(function($query) use ($timetableDate) {
            $query->where(function($q) use ($timetableDate) {
                // Check if date falls within the exemption period (date only)
                $q->whereDate('from_date', '<=', $timetableDate)
                  ->where(function($subQ) use ($timetableDate) {
                      $subQ->whereNull('to_date')
                           ->orWhereDate('to_date', '>=', $timetableDate);
                  });
            });
        })->first();

        if ($medicalExemption) {
            // Extract time from from_date and to_date, then check time overlap with class_session
            $exemptionTimeFrom = $medicalExemption->from_date ? date('H:i:s', strtotime($medicalExemption->from_date)) : null;
            $exemptionTimeTo = $medicalExemption->to_date ? date('H:i:s', strtotime($medicalExemption->to_date)) : null;
            
            if ($exemptionTimeFrom && $exemptionTimeTo) {
                // Check if class_session time overlaps with medical exemption time range
                if ($this->checkTimeOverlap($timetable->class_session, $exemptionTimeFrom, $exemptionTimeTo)) {
                    return true;
                }
            } else {
                // If times are not available, just check date match
                return true;
            }
        }

        return false;
    }

    /**
     * Check if class_session time overlaps with duty Time_from and Time_to
     * Returns true if class session time falls within duty time range
     * 
     * @param string|null $classSession - Can be "10:35 AM - 11:30 AM" format or ClassSessionMaster PK
     * @param string|null $dutyTimeFrom - Duty time from (H:i format, e.g., "14:05:00")
     * @param string|null $dutyTimeTo - Duty time to (H:i format, e.g., "15:07:00")
     * @return bool
     */
    protected function checkTimeOverlap(?string $classSession, ?string $dutyTimeFrom, ?string $dutyTimeTo): bool
    {
        if (empty($classSession) || empty($dutyTimeFrom) || empty($dutyTimeTo)) {
            return false;
        }

        // Parse class_session to get start and end times
        $classSessionTimes = $this->parseClassSession($classSession);
        if (!$classSessionTimes) {
            return false;
        }

        $classStartTime = $classSessionTimes['start'];
        $classEndTime = $classSessionTimes['end'];

        // Convert duty times to seconds for comparison
        $dutyStartSeconds = $this->timeToSeconds($dutyTimeFrom);
        $dutyEndSeconds = $this->timeToSeconds($dutyTimeTo);
        $classStartSeconds = $this->timeToSeconds($classStartTime);
        $classEndSeconds = $this->timeToSeconds($classEndTime);

        if ($dutyStartSeconds === false || $dutyEndSeconds === false || 
            $classStartSeconds === false || $classEndSeconds === false) {
            return false;
        }

        // Check if class session time overlaps with duty time range
        // Class session time should overlap with duty Time_from and Time_to for radio to reflect
        // Example: class_session (10:35 to 11:30) does NOT overlap with duty (14:05 to 15:07), so no radio
        // But if class_session (14:10 to 14:50) overlaps with duty (14:05 to 15:07), radio will reflect
        return ($classStartSeconds <= $dutyEndSeconds && $classEndSeconds >= $dutyStartSeconds);
    }

    /**
     * Parse class_session string to extract start and end times
     * Handles formats like: "10:35 AM - 11:30 AM", "10:35 - 11:30", "10:35:00 - 11:30:00"
     * Also handles if class_session is a PK reference to ClassSessionMaster
     * 
     * @param string|null $classSession
     * @return array|null Returns ['start' => 'HH:MM', 'end' => 'HH:MM'] or null
     */
    protected function parseClassSession(?string $classSession): ?array
    {
        if (empty($classSession)) {
            return null;
        }

        // Check if class_session is a numeric ID (PK reference to ClassSessionMaster)
        if (is_numeric($classSession)) {
            $classSessionMaster = \App\Models\ClassSessionMaster::find($classSession);
            if ($classSessionMaster && $classSessionMaster->start_time && $classSessionMaster->end_time) {
                return [
                    'start' => date('H:i', strtotime($classSessionMaster->start_time)),
                    'end' => date('H:i', strtotime($classSessionMaster->end_time))
                ];
            }
            return null;
        }

        // Parse string format like "10:35 AM - 11:30 AM" or "10:35 - 11:30"
        // Handle both "to" and "-" separators
        $separators = [' - ', ' to ', '-'];
        $timeParts = null;
        
        foreach ($separators as $separator) {
            if (strpos($classSession, $separator) !== false) {
                $parts = explode($separator, $classSession);
                if (count($parts) === 2) {
                    $timeParts = [
                        'start' => trim($parts[0]),
                        'end' => trim($parts[1])
                    ];
                    break;
                }
            }
        }

        if (!$timeParts) {
            return null;
        }

        // Convert times to H:i format (24-hour)
        try {
            $startTime = date('H:i', strtotime($timeParts['start']));
            $endTime = date('H:i', strtotime($timeParts['end']));
            
            return [
                'start' => $startTime,
                'end' => $endTime
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert time string to seconds for easy comparison
     * Handles formats: "H:i:s", "H:i", "h:i A", etc.
     * 
     * @param string|null $time
     * @return int|false Returns seconds since midnight or false on error
     */
    protected function timeToSeconds(?string $time): int|false
    {
        if (empty($time)) {
            return false;
        }

        try {
            // First, try to parse as "H:i" or "H:i:s" format directly
            if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
                
                // Validate ranges
                if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59 && $seconds >= 0 && $seconds <= 59) {
                    return ($hours * 3600) + ($minutes * 60) + $seconds;
                }
            }
            
            // Fallback: Try to parse with strtotime (for formats like "10:00 AM")
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                // Extract hours, minutes, seconds
                $hours = (int)date('H', $timestamp);
                $minutes = (int)date('i', $timestamp);
                $seconds = (int)date('s', $timestamp);

                return ($hours * 3600) + ($minutes * 60) + $seconds;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function filename(): string
    {
        return 'StudentAttendanceList_' . date('YmdHis');
    }
}
