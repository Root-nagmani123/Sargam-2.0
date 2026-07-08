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
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="form-check-input ot-check" value="' . $row->studentsMaster->pk . '">')
            ->addColumn('student_code', fn($row) => e($row->studentsMaster->generated_OT_code ?? 'N/A'))
            ->addColumn('student_name', fn($row) => e($row->studentsMaster->display_name ?? 'N/A'))
            ->addColumn('attendance_status', fn($row) => $this->renderStatusBadge($row))
            ->addColumn('mdo_duty', fn($row) => $this->renderMdoCell($row))
            ->addColumn('escort_duty', fn($row) => $this->renderEscortCell($row))
            ->addColumn('action', fn($row) => $this->renderActionCell($row))
            ->filterColumn('student_name', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('display_name', 'like', "%{$keyword}%")))
            ->filterColumn('student_code', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('generated_OT_code', 'like', "%{$keyword}%")))
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentsMaster', function ($studentQuery) use ($searchValue) {
                            $studentQuery->where('display_name', 'like', "%{$searchValue}%")
                                ->orWhere('generated_OT_code', 'like', "%{$searchValue}%")
                                ->orWhere('user_id', 'like', "%{$searchValue}%");
                        });
                    });
                }
            }, true)
            ->rawColumns(['checkbox', 'attendance_status', 'mdo_duty', 'escort_duty', 'action']);
    }

    public function query(): QueryBuilder
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $this->group_pk)
            ->where('course_name', $this->course_pk)
            ->first();

        if (!$groupTypeMaster) {
            // Return an empty query to avoid throwing ModelNotFoundException
            return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,user_id,cadre_master_pk,pk',
                'studentsMaster.cadre:pk,cadre_name',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('group_type_master_course_master_map_pk', $this->group_pk)
            ])->whereRaw('1=0');
        }

        return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,user_id,cadre_master_pk,pk',
                'studentsMaster.cadre:pk,cadre_name',
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
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'language' => [
                    'emptyTable' => 'No officer trainees found.',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')->title('<input type="checkbox" class="form-check-input" id="otCheckAll">')->addClass('text-center align-middle')->orderable(false)->searchable(false)->width(40),
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-center align-middle')->orderable(false)->searchable(false),
            Column::make('student_code')->title('OT Code')->addClass('align-middle')->orderable(false)->searchable(true),
            Column::make('student_name')->title('OT Name')->addClass('align-middle')->orderable(false)->searchable(true),
            Column::make('attendance_status')->title('Attendance Status')->addClass('align-middle')->orderable(false)->searchable(false),
            Column::make('mdo_duty')->title('MDO Duty')->addClass('align-middle')->orderable(false)->searchable(false),
            Column::make('escort_duty')->title('Escort/Moderator Duty')->addClass('align-middle')->orderable(false)->searchable(false),
            Column::computed('action')->title('Action')->addClass('text-center align-middle')->orderable(false)->searchable(false),
        ];
    }

    protected function getSavedStatus(int $studentId): int
    {
        static $cache = [];
        if (!array_key_exists($studentId, $cache)) {
            $rec = CourseStudentAttendance::where([
                ['Student_master_pk', '=', $studentId],
                ['Course_master_pk', '=', $this->course_pk],
                ['group_type_master_course_master_map_pk', '=', $this->group_pk],
                ['timetable_pk', '=', $this->timetable_pk],
            ])->first();
            $cache[$studentId] = $rec ? (int) $rec->status : 0;
        }
        return $cache[$studentId];
    }

    protected function statusLabelClass(int $status): array
    {
        return match ($status) {
            1 => ['Present', 'att-present'],
            2 => ['Late', 'att-late'],
            3 => ['Absent', 'att-absent'],
            4 => ['MDO', 'att-duty'],
            5 => ['Escort', 'att-duty'],
            6 => ['Medical', 'att-exempt'],
            7 => ['Other', 'att-exempt'],
            default => ['Not Marked', 'att-nm'],
        };
    }

    protected function renderStatusBadge($row): string
    {
        $pk = $row->studentsMaster->pk;
        $status = $this->getSavedStatus($pk);
        [$label, $cls] = $this->statusLabelClass($status);

        return '<input type="hidden" name="student[' . $pk . ']" value="' . $status . '" class="ot-status" data-ot="' . $pk . '">'
            . '<span class="att-badge ' . $cls . '" data-ot="' . $pk . '">' . $label . '</span>';
    }

    protected function renderMdoCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        if ($this->hasMdoDuty($pk)) {
            return '<span class="text-info fw-semibold">Yes</span>';
        }

        // An MDO record exists for the session date but doesn't overlap its time → "No".
        $timetable = Timetable::select('START_DATE')->where('pk', $this->timetable_pk)->first();
        $mdoTypes = MDOEscotDutyMap::getMdoDutyTypes();
        if ($timetable && !empty($mdoTypes['mdo'])) {
            $exists = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoTypes['mdo']],
                ['selected_student_list', '=', $pk],
            ])->whereDate('mdo_date', '=', $timetable->START_DATE)->exists();
            if ($exists) {
                return '<span class="text-muted">No</span>';
            }
        }

        return '<span class="text-muted">NA</span>';
    }

    protected function renderEscortCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        return $this->hasEscortDuty($pk)
            ? '<span class="text-info fw-semibold">Escort/Moderator</span>'
            : '<span class="text-muted">NA</span>';
    }

    protected function renderActionCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        $status = $this->getSavedStatus($pk);
        $name = e($row->studentsMaster->display_name ?? '');

        return '<button type="button" class="att-action-icon js-mark-ot" '
            . 'data-ot="' . $pk . '" data-status="' . $status . '" data-name="' . $name . '" '
            . 'title="Mark attendance" aria-label="Mark attendance"><i class="bi bi-fingerprint"></i></button>';
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

    // Escort nahi hai → N/A. MDO duty ab apne "MDO Duty" column me text ke roop
    // me dikhti hai (is "Escort/Moderator Duty" column me nahi).
    return "<span class='text-muted'>N/A</span>";
}

        // MDO Duty column: agar OT ki MDO duty pehle se hai to radio button ke bajaye
        // yahan "MDO Duty" text dikhao. OT ko Attendance column me "Present" default
        // kiya gaya hai, aur sabhi radios ka name same hone ki wajah se yahan radio
        // auto-check karne se wo Present default override ho jata — isliye text.
        if ($value === 4) {
            if ($this->hasMdoDuty($row->studentsMaster->pk)) {
                return "<span class='text-info fw-bold'>MDO Duty</span>";
            }
            // MDO duty nahi hai → radio button ke bajaye N/A dikhao
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
                        // MDO (4) is intentionally NOT auto-checked: an MDO-duty OT is
                        // defaulted to "Present" in the Attendance column instead. Since all
                        // radios for a student share the same name, auto-checking MDO here
                        // would override that Present default in the browser.
                        4 => $dutyType = null,
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

        // MDO & Escort/Moderator duty ko top priority: agar "MDO Duty" column me MDO
        // Duty ya "Escort/Moderator Duty" column me Escort/Moderator dikh rahi hai to
        // OT duty par hai par attend kar raha mana jata hai — isliye Attendance column
        // me "Present" radio by default select rahega (chahe saved record kuch bhi ho,
        // kyunki MDO/Escort ke liye Attendance column me koi alag radio nahi hota).
        if ($this->hasMdoDuty($studentId) || $this->hasEscortDuty($studentId)) {
            $defaultCheckedValue = 1; // Present
        } elseif ($courseStudent) {
            // If there's an existing attendance record, use its status
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
     * Check if student has an MDO duty overlapping the current class session.
     * Used to default the Attendance column to "Present" for MDO-duty OTs
     * (MDO duty means the OT is still attending, unlike Medical/Other exemptions).
     */
    protected function hasMdoDuty(int $studentId): bool
    {
        $timetable = Timetable::select('START_DATE', 'class_session')->where('pk', $this->timetable_pk)->first();

        if (empty($timetable)) {
            return false;
        }

        $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

        if (empty($mdoDutyTypes['mdo'])) {
            return false;
        }

        $mdoDuty = MDOEscotDutyMap::where([
            ['course_master_pk', '=', $this->course_pk],
            ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['mdo']],
            ['selected_student_list', '=', $studentId]
        ])->whereDate('mdo_date', '=', $timetable->START_DATE)->first();

        return $mdoDuty && $this->checkTimeOverlap($timetable->class_session, $mdoDuty->Time_from, $mdoDuty->Time_to);
    }

    /**
     * Check if student has an Escort/Moderator duty overlapping the current class session.
     * Used to default the Attendance column to "Present" for Escort-duty OTs
     * (an Escort/Moderator OT is on duty but still counted as attending).
     */
    protected function hasEscortDuty(int $studentId): bool
    {
        $timetable = Timetable::select('START_DATE', 'class_session')->where('pk', $this->timetable_pk)->first();

        if (empty($timetable)) {
            return false;
        }

        $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();

        if (empty($mdoDutyTypes['escort'])) {
            return false;
        }

        $escortDuty = MDOEscotDutyMap::where([
            ['course_master_pk', '=', $this->course_pk],
            ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['escort']],
            ['selected_student_list', '=', $studentId]
        ])->whereDate('mdo_date', '=', $timetable->START_DATE)->first();

        return $escortDuty && $this->checkTimeOverlap($timetable->class_session, $escortDuty->Time_from, $escortDuty->Time_to);
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
