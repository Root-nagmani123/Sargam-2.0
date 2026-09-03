<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * "Course Group Mapping — Student List" report for ONE group mapping, styled to
 * match the Discipline Memo report (DisciplineMemoExport): LBSNAA header, report
 * title, a highlighted Course Name / Course Duration / Group Type strip, the
 * record count, then the same student columns the on-screen View modal shows.
 */
class GroupMappingStudentListExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Students of the group, as StudentMaster models. */
    protected Collection $students;

    /** course_name, course_duration, group_type, group_name, faculty — see GroupMappingController::studentListReportData(). */
    protected array $group;

    protected string $exportDate;

    /** Rows used by the LBSNAA header before the data table. */
    protected int $headerRows = 6;

    /**
     * The ONE definition of the report's data columns, in order — shared by the
     * Excel sheet and the PDF (GroupMappingController::exportStudentListPdf reads
     * this too), so the two downloads cannot drift apart in column set, order or
     * heading. Mirrors the View-students modal (student_list_ajax.blade.php).
     *
     *   heading  — column title in both exports
     *   pdfClass — width/alignment class in student_list_pdf.blade.php
     *   center   — centre-align this column in the Excel sheet
     *   value    — cell value for one student
     */
    public static function columnDefs(): array
    {
        return [
            'name' => ['heading' => 'Name', 'pdfClass' => 'col-name', 'center' => false,
                'value' => fn ($s) => $s->display_name ?: 'N/A'],
            'ot_code' => ['heading' => 'OT Code', 'pdfClass' => 'col-code', 'center' => false,
                'value' => fn ($s) => $s->generated_OT_code ?: 'N/A'],
            // ?: rather than ?? — these are blank strings far more often than NULL.
            'email' => ['heading' => 'Email', 'pdfClass' => 'col-email', 'center' => false,
                'value' => fn ($s) => $s->email ?: 'N/A'],
            'mobile' => ['heading' => 'Contact No', 'pdfClass' => 'col-mobile', 'center' => false,
                'value' => fn ($s) => $s->contact_no ?: 'N/A'],
        ];
    }

    public function __construct(Collection $students, array $group, string $exportDate)
    {
        $this->students = $students;
        $this->group = $group;
        $this->exportDate = $exportDate;
    }

    public function title(): string
    {
        return 'Group Student List';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    /** Total emitted columns, the leading '#' serial included. */
    protected function columnCount(): int
    {
        return count(static::columnDefs()) + 1;
    }

    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex($this->columnCount());
    }

    /** Sheet letter for a data column. */
    protected function colLetter(string $key): ?string
    {
        $i = array_search($key, array_keys(static::columnDefs()), true);

        return $i === false ? null : Coordinate::stringFromColumnIndex($i + 2);
    }

    public function headings(): array
    {
        return array_merge(['#'], array_column(static::columnDefs(), 'heading'));
    }

    public function array(): array
    {
        $defs = static::columnDefs();
        $rows = [];
        $serial = 0;

        foreach ($this->students as $student) {
            $serial++;
            $row = [$serial];
            foreach ($defs as $def) {
                $row[] = ($def['value'])($student);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $this->lastColLetter();
        $dataStart = $this->headerRows + 1; // heading row
        $dataRowStart = $dataStart + 1;     // first data row

        // Heading row styling
        $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataStart}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '003366'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '002244']],
            ],
        ]);

        // Data rows
        if ($lastRow >= $dataRowStart) {
            $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 10],
            ]);

            // Alternating row shading
            for ($row = $dataRowStart; $row <= $lastRow; $row++) {
                if (($row - $dataRowStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }
            }

            // Centre the serial plus every column flagged `center`.
            $centerCols = ['A'];
            foreach (static::columnDefs() as $key => $def) {
                if (! empty($def['center']) && ($letter = $this->colLetter($key)) !== null) {
                    $centerCols[] = $letter;
                }
            }

            foreach ($centerCols as $col) {
                $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Freeze pane below the header + heading row
        $sheet->freezePane('A' . ($dataStart + 1));

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColLetter();

                // ── LBSNAA Header ──
                // Row 1: Institution name (merged)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Row 2: Report title
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'COURSE GROUP MAPPING - STUDENT LIST');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: the highlighted Course Name / Course Duration / Group Type strip
                // this report is built around — amber fill so it stands apart from the
                // navy branding above and the navy table heading below.
                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', 'Course Name: ' . ($this->group['course_name'] ?? 'N/A')
                    . '     |     Course Duration: ' . ($this->group['course_duration'] ?? 'N/A')
                    . '     |     Group Type: ' . ($this->group['group_type'] ?? 'N/A'));
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '663C00']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0A800']],
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(24);

                // Row 4: the remaining group info shown in the View modal
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Group Name: ' . ($this->group['group_name'] ?? 'N/A')
                    . '  |  Faculty: ' . ($this->group['faculty'] ?? 'N/A')
                    . '  |  Generated: ' . $this->exportDate);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 5: Summary count
                $sheet->mergeCells("A5:{$lastCol}5");
                $sheet->setCellValue('A5', 'Total Students: ' . $this->students->count());
                $sheet->getStyle('A5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                // Row 6: empty spacer
                $sheet->getRowDimension(6)->setRowHeight(6);

                // Header border
                $sheet->getStyle("A1:{$lastCol}5")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']],
                    ],
                ]);

                // Logo
                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA Logo');
                    $drawing->setDescription('LBSNAA Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }
}
