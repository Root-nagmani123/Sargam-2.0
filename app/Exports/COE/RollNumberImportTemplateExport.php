<?php

namespace App\Exports\COE;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * The .xlsx an administrator downloads before importing roll numbers.
 *
 * ── Why it comes pre-filled ──
 * generated_OT_code is NOT unique — 2,712 participants share only 780 distinct codes, and
 * codes repeat even inside a single course+batch. So an OT code cannot identify who a row
 * is about, and a blank template asking for one would silently attach roll numbers to the
 * wrong people. The template therefore ships with one row per participant and a
 * Participant ID the importer matches on; the administrator fills in the Roll Number
 * column and nothing else.
 *
 * Participant ID is deliberately the FIRST column so it cannot be missed, and the sheet
 * says in its own header row that it must not be edited.
 */
class RollNumberImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    /** Header row plus the instruction row above the table. */
    private const HEADING_ROW = 1;

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
            // Left blank on purpose — this is the one column the administrator fills.
            (string) ($p->roll_number ?: ''),
        ])->all();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Participant ID', 'OT Code', 'Name', 'Course', 'Batch', 'Roll Number'];
    }

    public function title(): string
    {
        return 'Roll Numbers';
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

                $sheet->getStyle('A' . self::HEADING_ROW . ':F' . self::HEADING_ROW)
                    ->getFont()->setBold(true);
                $sheet->getStyle('A' . self::HEADING_ROW . ':F' . self::HEADING_ROW)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');

                // Tint the two columns that must not be touched, and the one that must.
                if ($lastRow > self::HEADING_ROW) {
                    $sheet->getStyle('A' . (self::HEADING_ROW + 1) . ':E' . $lastRow)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                    $sheet->getStyle('F' . (self::HEADING_ROW + 1) . ':F' . $lastRow)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFBEB');
                }

                // Roll Number as text, so a value like 0012 does not arrive as the number 12.
                $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('@');

                $sheet->getComment('A' . self::HEADING_ROW)
                    ->getText()->createTextRun('Do not edit or reorder this column — the import matches on it.');
                $sheet->getComment('F' . self::HEADING_ROW)
                    ->getText()->createTextRun('Enter the roll number here, e.g. FC101-001. Leave blank to skip a participant.');
            },
        ];
    }
}
