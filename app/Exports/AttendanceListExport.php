<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * CSV export of the attendance list (index table). Mirrors the on-screen
 * columns; rows are pre-built by AttendanceController::exportAttendanceList().
 */
class AttendanceListExport implements FromArray, WithHeadings
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Topic',
            'Date',
            'Session',
            'Venue',
            'Group',
            'Course Name',
            'Faculty',
            'Status',
        ];
    }
}
