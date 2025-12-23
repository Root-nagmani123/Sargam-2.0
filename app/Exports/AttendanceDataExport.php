<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\{MDOEscotDutyMap, StudentMedicalExemption, Timetable};

class AttendanceDataExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $records;
    protected $courseName;
    protected $topicName;
    protected $facultyName;
    protected $topicDate;
    protected $sessionTime;
    protected $course_pk;
    protected $group_pk;
    protected $timetable_pk;
    protected $timetableDate;
    protected $timetableClassSession;

    public function __construct($records, $courseName = '', $topicName = '', $facultyName = '', $topicDate = '', $sessionTime = '', $course_pk = null, $group_pk = null, $timetable_pk = null, $timetableDate = null, $timetableClassSession = null)
    {
        $this->records = $records;
        $this->courseName = $courseName;
        $this->topicName = $topicName;
        $this->facultyName = $facultyName;
        $this->topicDate = $topicDate;
        $this->sessionTime = $sessionTime;
        $this->course_pk = $course_pk;
        $this->group_pk = $group_pk;
        $this->timetable_pk = $timetable_pk;
        $this->timetableDate = $timetableDate;
        $this->timetableClassSession = $timetableClassSession;
    }

    public function array(): array
    {
        $data = [];
        $serialNumber = 1;

        foreach ($this->records as $record) {
            $student = $record->studentsMaster ?? null;
            $studentId = $student->pk ?? null;
            
            if (!$studentId) {
                continue;
            }

            // Handle attendance as collection (hasMany relationship) - get first record
            $attendance = null;
            if ($record->attendance) {
                $attendance = is_iterable($record->attendance) ? $record->attendance->first() : $record->attendance;
            }

            // Check for exemptions/duties even if attendance is not saved
            $hasMdoDuty = false;
            $hasEscortDuty = false;
            $hasMedicalExempt = false;
            $hasOtherExempt = false;
            
            if ($this->timetableDate) {
                $mdoDutyTypes = MDOEscotDutyMap::getMdoDutyTypes();
                
                // Check MDO Duty
                if (!empty($mdoDutyTypes['mdo'])) {
                    $mdoDuty = MDOEscotDutyMap::where([
                        ['course_master_pk', '=', $this->course_pk],
                        ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['mdo']],
                        ['selected_student_list', '=', $studentId]
                    ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
                    
                    if ($mdoDuty && $this->checkTimeOverlap($this->timetableClassSession, $mdoDuty->Time_from, $mdoDuty->Time_to)) {
                        $hasMdoDuty = true;
                    }
                }
                
                // Check Escort Duty
                if (!empty($mdoDutyTypes['escort'])) {
                    $escortDuty = MDOEscotDutyMap::where([
                        ['course_master_pk', '=', $this->course_pk],
                        ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['escort']],
                        ['selected_student_list', '=', $studentId]
                    ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
                    
                    if ($escortDuty && $this->checkTimeOverlap($this->timetableClassSession, $escortDuty->Time_from, $escortDuty->Time_to)) {
                        $hasEscortDuty = true;
                    }
                }
                
                // Check Other Exemption
                if (!empty($mdoDutyTypes['other'])) {
                    $otherExemption = MDOEscotDutyMap::where([
                        ['course_master_pk', '=', $this->course_pk],
                        ['mdo_duty_type_master_pk', '=', $mdoDutyTypes['other']],
                        ['selected_student_list', '=', $studentId]
                    ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
                    
                    if ($otherExemption && $this->checkTimeOverlap($this->timetableClassSession, $otherExemption->Time_from, $otherExemption->Time_to)) {
                        $hasOtherExempt = true;
                    }
                }
                
                // Check Medical Exemption
                $medicalExemption = StudentMedicalExemption::where([
                    ['course_master_pk', '=', $this->course_pk],
                    ['student_master_pk', '=', $studentId],
                    ['active_inactive', '=', 1]
                ])
                ->where(function($query) {
                    $query->whereDate('from_date', '<=', $this->timetableDate)
                          ->where(function($q) {
                              $q->whereNull('to_date')
                                ->orWhereDate('to_date', '>=', $this->timetableDate);
                          });
                })->first();
                
                if ($medicalExemption) {
                    $exemptionTimeFrom = $medicalExemption->from_date ? date('H:i:s', strtotime($medicalExemption->from_date)) : null;
                    $exemptionTimeTo = $medicalExemption->to_date ? date('H:i:s', strtotime($medicalExemption->to_date)) : null;
                    
                    if ($exemptionTimeFrom && $exemptionTimeTo) {
                        if ($this->checkTimeOverlap($this->timetableClassSession, $exemptionTimeFrom, $exemptionTimeTo)) {
                            $hasMedicalExempt = true;
                        }
                    } else {
                        $hasMedicalExempt = true;
                    }
                }
            }

            // Get attendance status from saved record or determine based on exemptions
            // Priority: Medical/Other Exemptions > Saved Attendance > MDO/Escort Duties > Default Present
            $attendanceStatus = 'Not Marked';
            $mdoDuty = 'No';
            $escortDuty = 'No';
            $medicalExempt = 'No';
            $otherExempt = 'No';
            
            // First, check if Medical or Other exemption is selected (from saved attendance or exemptions)
            $hasMedicalFromAttendance = false;
            $hasOtherFromAttendance = false;
            
            if ($attendance) {
                $status = $attendance->status;
                if ($status == 6) {
                    $hasMedicalFromAttendance = true;
                } elseif ($status == 7) {
                    $hasOtherFromAttendance = true;
                }
            }
            
            // If Medical or Other exemption is selected (either from saved attendance or from exemptions table), show "Not Marked"
            if ($hasMedicalFromAttendance || $hasMedicalExempt) {
                $attendanceStatus = 'Not Marked';
                $medicalExempt = 'Yes';
            } elseif ($hasOtherFromAttendance || $hasOtherExempt) {
                $attendanceStatus = 'Not Marked';
                $otherExempt = 'Yes';
            } elseif ($attendance) {
                // Use saved attendance status (for Present, Late, Absent, MDO, Escort)
                $status = $attendance->status;
                $attendanceStatus = match ($status) {
                    1 => 'Present',
                    2 => 'Late',
                    3 => 'Absent',
                    4 => 'Present',
                    5 => 'Present',
                    default => 'Present',
                };
                
                // Set duty flags based on saved status
                if ($status == 4) {
                    $mdoDuty = 'Yes';
                } elseif ($status == 5) {
                    $escortDuty = 'Yes';
                }
            } else {
                // No saved attendance - check exemptions/duties
                if ($hasMdoDuty) {
                    $attendanceStatus = 'Present';
                    $mdoDuty = 'Yes';
                } elseif ($hasEscortDuty) {
                    $attendanceStatus = 'Present';
                    $escortDuty = 'Yes';
                } else {
                    // No attendance and no exemptions - default to Present
                    $attendanceStatus = 'Present';
                }
            }

            $row = [
                $serialNumber++,
                $student->display_name ?? 'N/A',
                $student->generated_OT_code ?? 'N/A',
                $attendanceStatus,
                $mdoDuty,
                $escortDuty,
                $medicalExempt,
                $otherExempt,
            ];

            $data[] = $row;
        }

        return $data;
    }
    
    /**
     * Check if class_session time overlaps with duty Time_from and Time_to
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
        return ($classStartSeconds <= $dutyEndSeconds && $classEndSeconds >= $dutyStartSeconds);
    }

    /**
     * Parse class_session string to extract start and end times
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
     */
    protected function timeToSeconds(?string $time): int|false
    {
        if (empty($time)) {
            return false;
        }

        try {
            $timestamp = strtotime($time);
            if ($timestamp === false) {
                return false;
            }

            $hours = (int)date('H', $timestamp);
            $minutes = (int)date('i', $timestamp);
            $seconds = (int)date('s', $timestamp);

            return ($hours * 3600) + ($minutes * 60) + $seconds;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function headings(): array
    {
        return [
            'S.No',
            'OT Name',
            'OT Code',
            'Attendance Status',
            'MDO Duty',
            'Escort Duty',
            'Medical Exemption',
            'Other Exemption',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Apply border + alignment for all cells
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

        // Apply header row style (row 1)
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

