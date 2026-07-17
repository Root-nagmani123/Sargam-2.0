<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromArray, WithColumnWidths, WithEvents, WithHeadings, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Models\{MDOEscotDutyMap, StudentMedicalExemption, Timetable};

/**
 * Styled .xlsx of a topic's attendance sheet. The header block + table styling
 * mirror the Medical Exemption Report export for a consistent look.
 */
class AttendanceDataExport implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithTitle
{
    /** Attendance status => [font colour, fill colour]; mirrors the .att-badge styles on the mark-attendance page. */
    private const STATUS_COLOURS = [
        'Present'    => ['027A48', 'ECFDF3'],
        'Late'       => ['B54708', 'FFF6ED'],
        'Absent'     => ['B42318', 'FEF3F2'],
        'Not Marked' => ['B54708', 'FFFAEB'],
    ];

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

    /** Data rows written by {@see array()}; used for the "Total OTs" line and row styling. */
    protected int $rowCount = 0;

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
            // Priority: Exemptions from Tables > Saved Attendance (Medical/Other) > Saved Attendance (MDO/Escort/Present/Late/Absent) > Default Present
            $attendanceStatus = 'Not Marked';
            $mdoDuty = 'No';
            $escortDuty = 'No';

            // First, check exemptions from tables (these take priority)
            if ($hasMedicalExempt || $hasOtherExempt) {
                $attendanceStatus = 'Not Marked';
            } elseif ($hasMdoDuty) {
                $attendanceStatus = 'Not Marked';
                $mdoDuty = 'MDO Duty';
            } elseif ($hasEscortDuty) {
                $attendanceStatus = 'Not Marked';
                $escortDuty = 'Yes';
            } elseif ($attendance) {
                // No exemptions from tables, check saved attendance
                $status = $attendance->status;
                
                // Handle Medical (6) and Other (7) exemptions - show "Not Marked"
                if ($status == 6 || $status == 7) {
                    $attendanceStatus = 'Not Marked';
                } else {
                    // Handle Present, Late, Absent, MDO (4), Escort (5)
                    // Ensure Late (2) and Absent (3) are properly displayed
                    switch ($status) {
                        case 1:
                            $attendanceStatus = 'Present';
                            break;
                        case 2:
                            $attendanceStatus = 'Late';
                            break;
                        case 3:
                            $attendanceStatus = 'Absent';
                            break;
                        case 4:
                            $attendanceStatus = 'Not Marked'; // MDO Duty
                            $mdoDuty = 'MDO Duty';
                            break;
                        case 5:
                            $attendanceStatus = 'Not Marked'; // Escort Duty
                            $escortDuty = 'Yes';
                            break;
                        default:
                            $attendanceStatus = 'Present';
                            break;
                    }
                }
            } else {
                // No saved attendance and no exemptions from tables - default to Present
                $attendanceStatus = 'Present';
            }

            $row = [
                $serialNumber++,
                $student->display_name ?? 'N/A',
                $student->generated_OT_code ?? 'N/A',
                $attendanceStatus,
                $mdoDuty,
                $escortDuty,
            ];

            $data[] = $row;
        }

        $this->rowCount = count($data);

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

    public function headings(): array
    {
        return [
            'S.No',
            'OT Name',
            'OT Code',
            'Attendance Status',
            'MDO Duty',
            'Escort Duty',
        ];
    }

    public function title(): string
    {
        return 'Attendance';
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 40, 'C' => 18, 'D' => 20, 'E' => 14, 'F' => 18];
    }

    /** Session context shown under the report title, mirroring the on-screen info cards. */
    public function filterLine(): string
    {
        $parts = [];
        foreach ([
            'Course'  => $this->courseName,
            'Topic'   => $this->topicName,
            'Faculty' => $this->facultyName,
        ] as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '' && $value !== 'N/A') {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts ? 'Applied Filters:   ' . implode('   |   ', $parts) : '';
    }

    /** Date + session-time line shown under the filter line. */
    public function sessionLine(): string
    {
        $parts = [];
        foreach (['Topic Date' => $this->topicDate, 'Session Time' => $this->sessionTime] as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '' && $value !== 'N/A') {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts ? implode('   |   ', $parts) : '';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));

                $metaLines = [
                    ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'],
                    ['text' => 'Attendance Report', 'style' => 'title'],
                ];
                if (($filterLine = $this->filterLine()) !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
                }
                if (($sessionLine = $this->sessionLine()) !== '') {
                    $metaLines[] = ['text' => $sessionLine, 'style' => 'meta'];
                }
                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total OTs: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                // WithHeadings already wrote the column headings at row 1; push them
                // down to make room for the header block above.
                $sheet->insertNewRowBefore(1, count($metaLines));

                $headingRow = count($metaLines) + 1;
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max($this->rowCount, 0);

                $sheet->setShowGridlines(false);

                foreach ($metaLines as $i => $line) {
                    $r = $i + 1;
                    $range = "A{$r}:{$lastCol}{$r}";
                    $sheet->mergeCells($range);
                    $sheet->setCellValue("A{$r}", $line['text']);
                    $sheet->getStyle($range)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $font = $sheet->getStyle("A{$r}")->getFont();
                    switch ($line['style']) {
                        case 'inst':
                            $font->setBold(true)->setSize(13)->getColor()->setRGB('102A43');
                            $sheet->getRowDimension($r)->setRowHeight(42);
                            break;
                        case 'title':
                            $font->setBold(true)->setSize(16)->getColor()->setRGB('004A93');
                            $sheet->getStyle($range)->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('004A93');
                            $sheet->getRowDimension($r)->setRowHeight(24);
                            break;
                        case 'spacer':
                            $sheet->getRowDimension($r)->setRowHeight(6);
                            break;
                        default:
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(24);

                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(10);
                    $sheet->getStyle($bodyRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                    // S.No + OT Code + status/duty columns centre; OT Name stays left-aligned.
                    foreach (['A', 'C', 'D', 'E', 'F'] as $letter) {
                        $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                        if (($r - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');
                        }

                        // Highlight the duty columns (E = MDO, F = Escort) whenever a duty
                        // is set. Matches on "not No" rather than a specific label, so the
                        // fill survives the cell text changing.
                        foreach (['E', 'F'] as $letter) {
                            $dutyValue = strtoupper(trim((string) $sheet->getCell($letter . $r)->getValue()));
                            if ($dutyValue !== '' && $dutyValue !== 'NO') {
                                $sheet->getStyle($letter . $r)->getFill()
                                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90');
                            }
                        }

                        // Colour-code Attendance Status (column D) to match the on-screen badges.
                        $status = trim((string) ($sheet->getCell('D' . $r)->getValue() ?? ''));
                        if (isset(self::STATUS_COLOURS[$status])) {
                            [$fontColour, $fillColour] = self::STATUS_COLOURS[$status];
                            $sheet->getStyle('D' . $r)->getFont()->setBold(true)->getColor()->setRGB($fontColour);
                            $sheet->getStyle('D' . $r)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fillColour);
                        }
                    }
                }

                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);
                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

    private function placeLogo($sheet, string $path, string $coordinates, int $offsetX): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY(3);
        $drawing->setWorksheet($sheet);
    }
}

