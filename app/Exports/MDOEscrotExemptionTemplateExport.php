<?php

namespace App\Exports;

use App\Models\{StudentMaster, StudentMasterCourseMap};
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Sample template for MDO/Escort Exemption bulk upload.
 *
 * Columns: Name, OT Code, Date, Session.
 * When a course is selected, the enrolled OTs are pre-filled (Name + OT Code) so the
 * user only needs to fill Date and Session for the rows they want to assign.
 */
class MDOEscrotExemptionTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private ?int $coursePk = null) {}

    public function headings(): array
    {
        return ['Name', 'OT Code', 'Date', 'Session'];
    }

    public function array(): array
    {
        if (!$this->coursePk) {
            // No course context: provide a single example row to illustrate the format.
            return [['John Doe', 'OT-101', '2026-07-01', 'Session 1']];
        }

        $studentIds = StudentMasterCourseMap::where('course_master_pk', $this->coursePk)
            ->where('active_inactive', 1)
            ->pluck('student_master_pk')
            ->all();

        if (empty($studentIds)) {
            return [['', '', '', '']];
        }

        return StudentMaster::whereIn('pk', $studentIds)
            ->whereNotNull('generated_OT_code')
            ->orderBy('display_name')
            ->get(['display_name', 'generated_OT_code'])
            ->map(fn ($s) => [$s->display_name, $s->generated_OT_code, '', ''])
            ->all();
    }
}
