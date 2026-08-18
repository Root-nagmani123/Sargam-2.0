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

class DisciplineMemoExport implements
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

    /** Ordered data-column keys to emit (subset of columnDefs()). */
    protected array $cols;

    /** Whether to prepend the '#' serial column. */
    protected bool $showSerial;

    /**
     * The ONE definition of the export's data columns, in order — shared by the
     * Excel sheet and the PDF (MemoDisciplineController::exportPdf reads this too),
     * so the two exports cannot drift apart in column set, order or heading.
     *
     * Keys are the slugs the index page's Column Visibility modal sends in ?cols=,
     * which is how hiding a column in the table drops it from both downloads.
     *
     *   heading  — column title in both exports
     *   pdfClass — width/alignment class in export_pdf.blade.php
     *   center   — centre-align this column in the Excel sheet
     *   value    — cell value for one memo row
     *
     * NOTE: the table's Category and Action columns are deliberately absent — they
     * have never been part of either export, so hiding them changes nothing.
     */
    public static function columnDefs(): array
    {
        return [
            'program' => ['heading' => 'Program Name', 'pdfClass' => 'col-program', 'center' => false,
                'value' => fn ($m) => $m->course->course_name ?? 'N/A'],
            'name' => ['heading' => 'Student Name', 'pdfClass' => 'col-name', 'center' => false,
                'value' => fn ($m) => $m->student->display_name ?? 'N/A'],
            'ot_code' => ['heading' => 'OT/Participant Code', 'pdfClass' => 'col-code', 'center' => false,
                'value' => fn ($m) => $m->student->generated_OT_code ?? 'N/A'],
            'cadre' => ['heading' => 'Cadre', 'pdfClass' => 'col-cadre', 'center' => false,
                'value' => fn ($m) => $m->student->cadre->cadre_name ?? 'N/A'],
            // ?: rather than ?? — these are blank strings far more often than NULL.
            'email' => ['heading' => 'Email', 'pdfClass' => 'col-email', 'center' => false,
                'value' => fn ($m) => optional($m->student)->email ?: 'N/A'],
            'mobile' => ['heading' => 'Mobile No.', 'pdfClass' => 'col-mobile', 'center' => false,
                'value' => fn ($m) => optional($m->student)->contact_no ?: 'N/A'],
            'date' => ['heading' => 'Date of Infraction', 'pdfClass' => 'col-date', 'center' => true,
                'value' => fn ($m) => $m->date ? Carbon::parse($m->date)->format('d M Y') : 'N/A'],
            'infraction' => ['heading' => 'Infraction', 'pdfClass' => 'col-infraction', 'center' => false,
                'value' => fn ($m) => $m->discipline->discipline_name ?? 'N/A'],
            'submitted' => ['heading' => 'Submitted Marks', 'pdfClass' => 'col-marks', 'center' => true,
                'value' => fn ($m) => $m->mark_deduction_submit ?? ''],
            'final' => ['heading' => 'Final Marks', 'pdfClass' => 'col-marks', 'center' => true,
                'value' => fn ($m) => $m->final_mark_deduction ?? ''],
            'remarks' => ['heading' => 'Remarks', 'pdfClass' => 'col-remarks', 'center' => false,
                'value' => fn ($m) => $m->remarks ?? ''],
            'conclusion_remark' => ['heading' => 'Conclusion Remark', 'pdfClass' => 'col-remarks', 'center' => false,
                'value' => fn ($m) => $m->conclusion_remark ?? ''],
            'created_date' => ['heading' => 'Created Date', 'pdfClass' => 'col-date', 'center' => true,
                'value' => fn ($m) => $m->created_date ? Carbon::parse($m->created_date)->format('d M Y') : 'N/A'],
            'status' => ['heading' => 'Status', 'pdfClass' => 'col-status', 'center' => true,
                'value' => fn ($m) => static::statusLabel($m->status)],
        ];
    }

    /**
     * @param string[]|null $cols       Ordered data columns to emit; null = all of them.
     * @param bool          $showSerial Emit the leading '#' serial column.
     */
    public function __construct(
        Collection $memos,
        array $filters,
        string $exportDate,
        ?array $cols = null,
        bool $showSerial = true
    ) {
        $this->memos = $memos;
        $this->filters = $filters;
        $this->exportDate = $exportDate;
        $this->cols = $cols ?? array_keys(static::columnDefs());
        $this->showSerial = $showSerial;
    }

    public function title(): string
    {
        return 'Discipline Memo';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    /** Total emitted columns, serial included. */
    protected function columnCount(): int
    {
        return count($this->cols) + ($this->showSerial ? 1 : 0);
    }

    /** Sheet letter of the last emitted column — was hard-coded 'M' when the column set was fixed. */
    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, $this->columnCount()));
    }

    /** Sheet letter for a data column, or null when it isn't being emitted. */
    protected function colLetter(string $key): ?string
    {
        $i = array_search($key, $this->cols, true);
        if ($i === false) {
            return null;
        }

        return Coordinate::stringFromColumnIndex($i + 1 + ($this->showSerial ? 1 : 0));
    }

    public function headings(): array
    {
        $defs = static::columnDefs();
        $headings = $this->showSerial ? ['#'] : [];

        foreach ($this->cols as $key) {
            $headings[] = $defs[$key]['heading'];
        }

        return $headings;
    }

    public static function statusLabel(?int $status): string
    {
        return match ($status) {
            1 => 'Recorded',
            2 => 'Memo Sent',
            3 => 'Closed',
            default => 'Closed',
        };
    }

    public function array(): array
    {
        $defs = static::columnDefs();
        $rows = [];
        $serial = 0;

        foreach ($this->memos as $memo) {
            $serial++;
            $row = $this->showSerial ? [$serial] : [];
            foreach ($this->cols as $key) {
                $row[] = ($defs[$key]['value'])($memo);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Sheet letters to centre — the serial plus every column flagged `center`. */
    protected function centerColLetters(): array
    {
        $defs = static::columnDefs();
        $letters = $this->showSerial ? ['A'] : [];

        foreach ($this->cols as $key) {
            if (!empty($defs[$key]['center']) && ($letter = $this->colLetter($key)) !== null) {
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

            // Alternating row shading + the status badge. The badge column is looked
            // up by key rather than assumed to be the last one — Status is no longer
            // guaranteed to be last (or present) once columns can be hidden.
            $statusCol = $this->colLetter('status');
            $row = $dataRowStart;
            foreach ($this->memos as $memo) {
                if (($row - $dataRowStart) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                if ($statusCol !== null) {
                    $badgeColor = match ($memo->status) {
                        1 => '198754', // Recorded — green
                        2 => 'FFC107', // Memo Sent — amber
                        default => '6C757D', // Closed — gray
                    };
                    $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $memo->status == 2 ? '212529' : 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $badgeColor]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $row++;
            }
        }

        // Center a few columns — derived from columnDefs()' `center` flag, so the
        // letters track whichever columns actually got emitted.
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
                $sheet->setCellValue('A2', 'DISCIPLINE MEMO REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Row 3: Filter info
                $sheet->mergeCells("A3:{$lastCol}3");
                $filterText = 'Program: ' . ($this->filters['program'] ?? 'All')
                    . '  |  Discipline: ' . ($this->filters['discipline'] ?? 'All')
                    . '  |  Status: ' . ($this->filters['status'] ?? 'All')
                    . '  |  Category: ' . ($this->filters['category'] ?? 'All')
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
