<?php

namespace App\Exports;

use App\Models\{StudentMaster, StudentMasterCourseMap};
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

/**
 * Sample template for MDO/Escort Exemption bulk upload.
 *
 * Columns: Name, OT Code, Date, Session.
 * When a course is selected, the enrolled OTs are pre-filled (Name + OT Code) so the
 * user only needs to fill Date and Session for the rows they want to assign.
 *
 * The Session column is a dropdown (Excel data validation) listing the active
 * class session times as "HH:MM to HH:MM", so users pick a valid time instead of
 * typing one. The matching parser lives in MDOEscrotExemptionImport.
 */
class MDOEscrotExemptionTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    /** Rows the Session dropdown is applied to (covers a generous upload size). */
    private const DROPDOWN_LAST_ROW = 500;

    public function __construct(private ?int $coursePk = null) {}

    public function headings(): array
    {
        return ['Name', 'OT Code', 'Date', 'Session'];
    }

    public function array(): array
    {
        $options = $this->sessionOptions();
        // Use the earliest and latest sessions for the two illustrative rows so the
        // examples are always real, selectable dropdown values.
        $morning = $options[0] ?? '';
        $evening = empty($options) ? '' : end($options);

        if (!$this->coursePk) {
            // No course context: provide example rows to illustrate the format.
            return [
                ['John Doe', 'OT-101', '01-07-2026', $morning],
                ['Jane Smith', 'OT-102', '01-07-2026', $evening],
            ];
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $options = $this->sessionOptions();
                if (empty($options)) {
                    return;
                }

                // Inline Excel list validation (quoted, comma-separated). The session
                // labels contain no commas, so the list stays unambiguous.
                $list = '"' . implode(',', $options) . '"';
                $sheet = $event->sheet->getDelegate();

                for ($row = 2; $row <= self::DROPDOWN_LAST_ROW; $row++) {
                    $validation = $sheet->getCell("D{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid session');
                    $validation->setError('Please pick a session from the dropdown list.');
                    $validation->setPromptTitle('Session');
                    $validation->setPrompt('Select a session from the list.');
                    $validation->setFormula1($list);
                }
            },
        ];
    }

    /**
     * Session dropdown options as "MDO <Shift> (HH:MM to HH:MM)".
     * MDO/Escort duties run as two full shifts: morning 06:00–14:00 and
     * evening 14:00–22:00. The time inside the parenthesis is authoritative;
     * the importer reads it directly (see MDOEscrotExemptionImport::resolveSession).
     *
     * @return string[]
     */
    private function sessionOptions(): array
    {
        return [
            'MDO Morning (06:00 to 14:00)', // morning duty
            'MDO Evening (14:00 to 22:00)', // evening duty
        ];
    }
}
