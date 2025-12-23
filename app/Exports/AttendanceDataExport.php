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

class AttendanceDataExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $records;
    protected $courseName;
    protected $topicName;
    protected $facultyName;
    protected $topicDate;
    protected $sessionTime;

    public function __construct($records, $courseName = '', $topicName = '', $facultyName = '', $topicDate = '', $sessionTime = '')
    {
        $this->records = $records;
        $this->courseName = $courseName;
        $this->topicName = $topicName;
        $this->facultyName = $facultyName;
        $this->topicDate = $topicDate;
        $this->sessionTime = $sessionTime;
    }

    public function array(): array
    {
        $data = [];
        $serialNumber = 1;

        foreach ($this->records as $record) {
            $student = $record->studentsMaster ?? null;
            // Handle attendance as collection (hasMany relationship) - get first record
            $attendance = null;
            if ($record->attendance) {
                $attendance = is_iterable($record->attendance) ? $record->attendance->first() : $record->attendance;
            }

            // Get attendance status
            $attendanceStatus = 'Not Marked';
            if ($attendance) {
                $status = $attendance->status;
                $attendanceStatus = match ($status) {
                    1 => 'Present',
                    2 => 'Late',
                    3 => 'Absent',
                    4 => 'Present (MDO)',
                    5 => 'Present (Escort)',
                    6 => 'Present (Medical Exempted)',
                    7 => 'Present (Other Exempted)',
                    default => 'Not Marked',
                };
            }

            // Determine MDO Duty, Escort Duty, Medical Exemption, Other Exemption
            $mdoDuty = 'No';
            $escortDuty = 'No';
            $medicalExempt = 'No';
            $otherExempt = 'No';

            if ($attendance) {
                $status = $attendance->status;
                if ($status == 4) {
                    $mdoDuty = 'Yes';
                } elseif ($status == 5) {
                    $escortDuty = 'Yes';
                } elseif ($status == 6) {
                    $medicalExempt = 'Yes';
                } elseif ($status == 7) {
                    $otherExempt = 'Yes';
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

