<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimetableReportExport implements FromView, WithStyles, WithEvents, WithTitle
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

    public function view(): View
    {
        return view('admin.timetable-report.excel.timetable-report-excel', [
            'rows'           => $this->reportData,
            'filterSummary'  => $this->filterSummary,
            'visibleColumns' => $this->visibleColumns,
        ]);
    }

    public function title(): string
    {
        return 'Timetable Session Report';
    }

    public function styles(Worksheet $sheet)
    {
        $colCount = count($this->visibleColumns);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Merge header cells (rows 1–5 contain header text from the view)
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->mergeCells("A4:{$lastColLetter}4");
        $sheet->mergeCells("A5:{$lastColLetter}5");

        $sheet->getStyle('A1:A5')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3:A5')->getFont()->setSize(11);

        // Table header (row 7 in the view — after blank row 6)
        $headerRange = "A7:{$lastColLetter}7";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0066CC');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Borders for the table
        $lastRow    = $sheet->getHighestRow();
        $sheet->getStyle("A8:{$lastColLetter}{$lastRow}")->getFont()->setSize(11);
        $tableRange = "A7:{$lastColLetter}{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        // Column widths
        $colIdx = 1;
        foreach ($this->visibleColumns as $col) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $width  = self::COL_WIDTHS[$col['key']] ?? 14;
            $sheet->getColumnDimension($letter)->setWidth($width);
            $colIdx++;
        }

        return [
            1 => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $colCount = count($this->visibleColumns);
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

                // Freeze header region
                $sheet->freezePane('A8');

                // Repeat header rows on every printed page
                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, 7);

                // Landscape + print area
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    )
                    ->setPrintArea("A1:{$lastColLetter}{$lastRow}");
            },
        ];
    }
}
