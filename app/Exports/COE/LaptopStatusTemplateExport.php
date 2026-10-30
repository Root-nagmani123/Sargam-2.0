<?php

namespace App\Exports\COE;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * The .xlsx an administrator downloads before importing OT laptop status.
 *
 * ── Why it comes pre-filled ──
 * generated_OT_code is NOT unique — 2,712 participants share only 780 distinct codes, and
 * codes repeat even inside a single course+batch. So an OT code cannot identify who a row
 * is about, and a blank template asking for one would silently attach answers to the wrong
 * people. The template therefore ships with one row per participant and a Participant ID
 * the importer matches on; the administrator fills in Laptop Required and Reason, and
 * nothing else.
 *
 * Participant ID is deliberately the FIRST column so it cannot be missed, and the sheet
 * says in its own header comment that it must not be edited.
 */
class LaptopStatusTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    private const HEADING_ROW = 1;

    /** The last column letter — kept in one place so the styling cannot drift. */
    private const LAST_COLUMN = 'H';

    /**
     * @param  Collection<int, object>  $participants
     */
    public function __construct(private Collection $participants)
    {
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->participants->map(fn ($p) => [
            (int) $p->pk,
            (string) ($p->generated_OT_code ?: ''),
            (string) ($p->display_name ?: ''),
            (string) ($p->course_name ?: ''),
            (string) ($p->batch ?: ''),
            (string) ($p->roll_number ?: ''),
            // Pre-filled with whatever is already recorded, so the file doubles as a
            // correction sheet rather than only an entry form. A participant with no
            // answer yet arrives blank, which the importer reads as "skip".
            $p->laptop_required === null ? '' : ((int) $p->laptop_required === 1 ? 'Yes' : 'No'),
            (string) ($p->reason ?: ''),
        ])->all();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Participant ID', 'Code', 'Name', 'Course', 'Batch', 'Roll Number', 'Laptop Required', 'Reason'];
    }

    public function title(): string
    {
        return 'OT Laptop Status';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->participants->count() + self::HEADING_ROW;
                $head = self::HEADING_ROW;
                $last = self::LAST_COLUMN;

                $sheet->getStyle("A{$head}:{$last}{$head}")->getFont()->setBold(true);
                $sheet->getStyle("A{$head}:{$last}{$head}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');

                if ($lastRow > $head) {
                    $first = $head + 1;
                    // Tint the columns that must not be touched, and the two that must.
                    $sheet->getStyle("A{$first}:F{$lastRow}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                    $sheet->getStyle("G{$first}:H{$lastRow}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFBEB');

                    // A dropdown on Laptop Required, so the common case never produces the
                    // "must be Yes or No" failure the importer would otherwise report.
                    for ($row = $first; $row <= $lastRow; $row++) {
                        $validation = $sheet->getCell("G{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST)
                            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setShowErrorMessage(true)
                            ->setErrorTitle('Laptop Required')
                            ->setError('Choose Yes or No, or leave the cell blank to skip this participant.')
                            ->setFormula1('"Yes,No"');
                    }
                }

                // Roll Number as text, so a value like 0012 does not arrive as the number 12.
                $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('@');

                $sheet->getComment("A{$head}")
                    ->getText()->createTextRun('Do not edit or reorder this column — the import matches on it.');
                $sheet->getComment("G{$head}")
                    ->getText()->createTextRun('Yes = this OT needs a laptop provided. Leave blank to skip the participant.');
                $sheet->getComment("H{$head}")
                    ->getText()->createTextRun('Required when Laptop Required is Yes, e.g. "Does not own a laptop".');
            },
        ];
    }
}
