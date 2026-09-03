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
use Carbon\Carbon;

/**
 * Same LBSNAA-branded report layout as DisciplineMemoExport, applied to the
 * Send Memo / Notice listing, so both modules' downloads look consistent.
 */
class MemoNoticeExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    protected Collection $memos;
    protected array $filters;
    protected string $exportDate;
    protected int $headerRows = 5; // rows used by the LBSNAA header before the data table

    /**
     * The ONE definition of the export's data columns, in order — shared by the
     * Excel sheet and the PDF (CourseAttendanceNoticeMapController::exportPdf reads
     * this too), so the two exports cannot drift apart in column set or wording.
     */
    public static function columnDefs(): array
    {
        return [
            'program' => ['heading' => 'Program Name', 'pdfClass' => 'col-program', 'center' => false,
                'value' => fn ($m) => $m->course_name ?? 'N/A'],
            'name' => ['heading' => 'Participant Name', 'pdfClass' => 'col-name', 'center' => false,
                'value' => fn ($m) => $m->student_name ?? 'N/A'],
            'ot_code' => ['heading' => 'OT/Participant Code', 'pdfClass' => 'col-code', 'center' => false,
                'value' => fn ($m) => $m->generated_OT_code ?? 'N/A'],
            'type' => ['heading' => 'Type', 'pdfClass' => 'col-type', 'center' => true,
                'value' => fn ($m) => ($m->type_notice_memo ?? '') === 'Memo' ? 'Memo' : 'Notice'],
            'session_date' => ['heading' => 'Session Date', 'pdfClass' => 'col-date', 'center' => true,
                'value' => fn ($m) => ($m->session_date ?? $m->date_ ?? null) ? Carbon::parse($m->session_date ?? $m->date_)->format('d M Y') : 'N/A'],
            'topic' => ['heading' => 'Topic', 'pdfClass' => 'col-topic', 'center' => false,
                'value' => fn ($m) => $m->topic_name ?? 'N/A'],
            'status' => ['heading' => 'Status', 'pdfClass' => 'col-status', 'center' => true,
                'value' => fn ($m) => ($m->status ?? null) == 1 ? 'Open' : 'Close'],
            'conclusion_type' => ['heading' => 'Conclusion Type', 'pdfClass' => 'col-conclusion', 'center' => false,
                'value' => fn ($m) => (($m->type_notice_memo ?? '') === 'Memo') ? 'Memo Generated' : '-'],
            'discussion_name' => ['heading' => 'Discussion Name', 'pdfClass' => 'col-discussion', 'center' => false,
                'value' => fn ($m) => (($m->type_notice_memo ?? '') === 'Memo' && ($m->communication_status ?? null) == 2) ? ($m->discussion_name ?? '-') : '-'],
            'conclusion_remark' => ['heading' => 'Conclusion Remark', 'pdfClass' => 'col-remarks', 'center' => false,
                'value' => fn ($m) => (($m->type_notice_memo ?? '') === 'Memo' && ($m->communication_status ?? null) == 2) ? ($m->conclusion_remark ?? '-') : '-'],
        ];
    }

    public function __construct(Collection $memos, array $filters, string $exportDate)
    {
        $this->memos = $memos;
        $this->filters = $filters;
        $this->exportDate = $exportDate;
    }

    public function title(): string
    {
        return 'Memo Notice';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    protected function columnCount(): int
    {
        return count(static::columnDefs()) + 1; // + serial
    }

    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, $this->columnCount()));
    }

    protected function colLetter(string $key): ?string
    {
        $keys = array_keys(static::columnDefs());
        $i = array_search($key, $keys, true);
        if ($i === false) {
            return null;
        }

        return Coordinate::stringFromColumnIndex($i + 2); // +1 for 1-index, +1 for serial column
    }

    public function headings(): array
    {
        $headings = ['#'];
        foreach (static::columnDefs() as $def) {
            $headings[] = $def['heading'];
        }

        return $headings;
    }

    public function array(): array
    {
        $defs = static::columnDefs();
        $rows = [];
        $serial = 0;

        foreach ($this->memos as $memo) {
            $serial++;
            $row = [$serial];
            foreach ($defs as $def) {
                $row[] = ($def['value'])($memo);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function centerColLetters(): array
    {
        $letters = ['A'];
        foreach (static::columnDefs() as $key => $def) {
            if (!empty($def['center']) && ($letter = $this->colLetter($key)) !== null) {
                $letters[] = $letter;
            }
        }

        return $letters;
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

            // Alternating row shading + the Type/Status badge cells.
            $typeCol = $this->colLetter('type');
            $statusCol = $this->colLetter('status');
            $row = $dataRowStart;
            foreach ($this->memos as $memo) {
                if (($row - $dataRowStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                $isMemo = ($memo->type_notice_memo ?? '') === 'Memo';
                if ($typeCol !== null) {
                    $sheet->getStyle("{$typeCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $isMemo ? '41464B' : '084298']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isMemo ? 'E2E3E5' : 'CFE2FF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $isOpen = ($memo->status ?? null) == 1;
                if ($statusCol !== null) {
                    $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $isOpen ? '0F5132' : '842029']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isOpen ? 'D1E7DD' : 'F8D7DA']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $row++;
            }
        }

        // Center a few columns — derived from columnDefs()' `center` flag.
        foreach ($this->centerColLetters() as $col) {
            if ($lastRow >= $dataRowStart) {
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
                $sheet->setCellValue('A2', 'SEND MEMO / NOTICE REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: Filter info
                $sheet->mergeCells("A3:{$lastCol}3");
                $filterText = 'Program: ' . ($this->filters['program'] ?? 'All')
                    . '  |  Type: ' . ($this->filters['type'] ?? 'All')
                    . '  |  Status: ' . ($this->filters['status'] ?? 'All')
                    . '  |  Period: ' . ($this->filters['period'] ?? 'All')
                    . '  |  Generated: ' . $this->exportDate;
                $sheet->setCellValue('A3', $filterText);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 4: Summary count
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->memos->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                // Row 5: empty spacer
                $sheet->getRowDimension(5)->setRowHeight(6);

                // Header border
                $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
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
