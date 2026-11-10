<?php

namespace App\Exports\COE;

use App\Services\COE\ResultMarksService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * The .xlsx a faculty downloads from Evaluation & Marks Entry, fills in and imports.
 *
 * The sheet's pending table as it is on screen — OT Unique Code, Course,
 * Subject / Component, Attendance, Marks (and Remarks) — pre-filled with what is
 * saved. EvaluationMarksImport finds each row's cell by its first three columns.
 */
class EvaluationMarksTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    private const HEADING_ROW = 1;

    /**
     * @param  list<array<string, mixed>>  $entries  ResultMarksService::entrySheet()['entries'], pending only
     * @param  string  $marksHead  the sheet's Marks heading, e.g. "Marks / 50"
     */
    public function __construct(private array $entries, private string $marksHead)
    {
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return array_map(function (array $e) {
            $mark = $e['mark'];
            $row = [
                $e['code'],
                $e['course'],
                self::label($e),
                $mark?->is_absent ? 'Absent' : 'Present',
                $mark === null || $mark->is_absent || $mark->marks === null ? '' : $mark->marks,
            ];
            if (ResultMarksService::remarksEnabled()) {
                $row[] = (string) ($mark?->remarks ?? '');
            }

            return $row;
        }, $this->entries);
    }

    /** "Subject — Component", as the sheet's Subject / Component column shows it. */
    public static function label(array $entry): string
    {
        return $entry['subject'].' — '.$entry['component'];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_merge(
            ['OT Unique Code', 'Course', 'Subject / Component', 'Attendance', $this->marksHead],
            ResultMarksService::remarksEnabled() ? ['Remarks'] : [],
        );
    }

    public function title(): string
    {
        return 'Evaluation & Marks';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $head = self::HEADING_ROW;
                $last = ResultMarksService::remarksEnabled() ? 'F' : 'E';
                $lastRow = count($this->entries) + $head;

                $sheet->getStyle("A{$head}:{$last}{$head}")->getFont()->setBold(true);
                $sheet->getStyle("A{$head}:{$last}{$head}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');

                if ($lastRow > $head) {
                    $first = $head + 1;
                    // Grey: do not touch. Yellow: fill in.
                    $sheet->getStyle("A{$first}:C{$lastRow}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                    $sheet->getStyle("D{$first}:{$last}{$lastRow}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFBEB');

                    for ($row = $first; $row <= $lastRow; $row++) {
                        $sheet->getCell("D{$row}")->getDataValidation()
                            ->setType(DataValidation::TYPE_LIST)
                            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setShowErrorMessage(true)
                            ->setErrorTitle('Attendance')
                            ->setError('Choose Present or Absent.')
                            ->setFormula1('"Present,Absent"');
                    }
                }

                // OT codes as text, so 0012 does not arrive as 12.
                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode('0.00');

                $sheet->getComment("A{$head}")
                    ->getText()->createTextRun('Do not edit OT Unique Code, Course or Subject / Component — the import finds each row by them.');
                $sheet->getComment("D{$head}")
                    ->getText()->createTextRun('Present or Absent. An Absent row needs no marks.');
                $sheet->getComment("E{$head}")
                    ->getText()->createTextRun('Up to the maximum marks, 2 decimals at most. Leave blank to skip the row.');
            },
        ];
    }
}
