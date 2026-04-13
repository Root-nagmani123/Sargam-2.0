<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimetableReportExport implements FromCollection, WithCustomStartCell, WithHeadings, WithEvents
{
    protected array $reportData;
    protected array $filterSummary;
    protected array $visibleColumns;

    /**
     * Default column widths keyed by column key.
     */
    private const COL_WIDTHS = [
        'sno'              => 6,
        'course_name'      => 22,
        'course_group_type'=> 16,
        'group_name'       => 16,
        'subject_name'     => 20,
        'module_name'      => 18,
        'subject_topic'    => 22,
        'faculty_name'     => 20,
        'faculty_code'     => 14,
        'faculty_type'     => 12,
        'class_session'    => 10,
        'start_date'       => 14,
        'end_date'         => 14,
        'venue_name'       => 16,
    ];

    public function __construct(array $reportData, array $filterSummary = [], array $visibleColumns = [])
    {
        $this->reportData     = $reportData;
        $this->filterSummary  = $filterSummary;
        $this->visibleColumns = $visibleColumns;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function collection(): Collection
    {
        $cols = $this->visibleColumns;
        $rows = [];
        foreach ($this->reportData as $index => $item) {
            $row = [];
            foreach ($cols as $col) {
                if ($col['key'] === 'sno') {
                    $row[] = $index + 1;
                } else {
                    $row[] = $item[$col['key']] ?? '';
                }
            }
            $rows[] = $row;
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return array_map(fn($col) => $col['label'], array_values($this->visibleColumns));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet   = $event->sheet->getDelegate();
                $colCount = count($this->visibleColumns);
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

                // Merge LBSNAA banner rows
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->mergeCells("A3:{$lastColLetter}3");

                // Row 1: Institution name
                $sheet->setCellValue('A1', 'LBSNAA MUSSOORIE');

                // Row 2: Report title
                $sheet->setCellValue('A2', 'Timetable Session Report');

                // Row 3: Filter summary
                $filterParts = [];
                if (!empty($this->filterSummary['course_name'])) {
                    $filterParts[] = 'Course: ' . $this->filterSummary['course_name'];
                }
                if (!empty($this->filterSummary['faculty_name'])) {
                    $filterParts[] = 'Faculty: ' . $this->filterSummary['faculty_name'];
                }
                if (!empty($this->filterSummary['date_from'])) {
                    $filterParts[] = 'From: ' . $this->filterSummary['date_from'];
                }
                if (!empty($this->filterSummary['date_to'])) {
                    $filterParts[] = 'To: ' . $this->filterSummary['date_to'];
                }
                $filterLine = !empty($filterParts) ? implode(' | ', $filterParts) : 'No filters applied';
                $sheet->setCellValue('A3', $filterLine);

                // Banner styling
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getFont()->setSize(10);

                // Table range
                $lastRow    = $sheet->getHighestRow();
                $headerRow  = 5;
                $tableRange = "A{$headerRow}:{$lastColLetter}{$lastRow}";

                // Borders
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->getColor()->setARGB('FFDEE2E6');

                // Header row styling
                $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")
                    ->getAlignment()->setHorizontal('center');

                // Column widths based on visible columns
                $colIdx = 1;
                foreach ($this->visibleColumns as $col) {
                    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $width  = self::COL_WIDTHS[$col['key']] ?? 14;
                    $sheet->getColumnDimension($letter)->setWidth($width);
                    $colIdx++;
                }

                // Freeze pane below header + column titles
                $sheet->freezePane("A" . ($headerRow + 1));

                // Repeat header rows on every printed page
                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);

                // Landscape orientation for print
                $sheet->getPageSetup()->setOrientation(
                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                );
            },
        ];
    }
}
