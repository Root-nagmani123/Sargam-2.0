<?php

namespace App\Exports;

use App\Support\ExportCell;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell as SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * OT Directory → .xlsx
 *
 * Columns are handed in already resolved by
 * DirectoryController::otExportColumnDefs() + resolveOtExportCols(), which is the
 * same array the CSV, the PDF and the print view use — so hiding a column in the
 * grid's Columns modal drops it from every format and the four can't drift apart.
 * "Full Details (Excel)" is the same class fed the unfiltered def list.
 *
 * Styled to match the print/PDF header: logo, navy institution band, report
 * title, generated stamp, record count, the row-cap note when the query was
 * truncated, then a navy table header over zebra rows.
 * Mirrors IssueCategoryExport deliberately — same visual language, but that class
 * hard-codes its own title and centred columns, so this one parameterises both
 * rather than reaching into a Centcom export from the directory module.
 */
class DirectoryGridExport extends DefaultValueBinder implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithTitle,
    WithCustomStartCell,
    WithCustomValueBinder
{
    /** Rows the branded header occupies before the data table starts. */
    private const HEADER_ROWS = 5;

    /**
     * @param  array<string, array{heading:string, width:string, align:string, value:callable}>  $columns
     * @param  string|null  $note  the row-cap warning, when the query was truncated
     */
    public function __construct(
        private Collection $rows,
        private array $columns,
        private string $exportDate,
        private string $filterLine = '',
        private string $sheetTitle = 'OT Directory',
        private ?string $note = null
    ) {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function startCell(): string
    {
        return 'A' . (self::HEADER_ROWS + 1);
    }

    public function headings(): array
    {
        return array_values(array_map(fn ($col) => $col['heading'], $this->columns));
    }

    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $index => $row) {
            $out[] = array_values(array_map(
                fn ($col) => ExportCell::raw($col, $row, $index),
                $this->columns
            ));
        }

        return $out;
    }

    /**
     * Every string is written as an explicit text cell.
     *
     * The default binder infers a type from the value, which is wrong twice
     * over for a directory: "9000000000" and "0245" become NUMBERS (the
     * extension loses its leading zero, the mobile can render in scientific
     * notation), and a leading "=" would become a formula. Declaring the type
     * fixes both AND makes the apostrophe sanitize_export_cell() adds for CSV
     * unnecessary here — on .xlsx that apostrophe is not a type marker, it is
     * stored as the first character of the value and printed to the reader.
     * Hence ExportCell::raw() above.
     *
     * @param  mixed  $value
     */
    public function bindValue(SpreadsheetCell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->columns)));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastColLetter();
                $dataHeaderRow = self::HEADER_ROWS + 1;
                $lastRow = $dataHeaderRow + $this->rows->count();

                // ── Branded header ──
                $sheet->mergeCells("A1:{$last}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells("A2:{$last}2");
                $sheet->setCellValue('A2', mb_strtoupper($this->sheetTitle));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$last}3");
                $meta = 'Generated: ' . $this->exportDate;
                if ($this->filterLine !== '') {
                    $meta = $this->filterLine . '  |  ' . $meta;
                }
                $sheet->setCellValue('A3', $meta);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$last}4");
                $sheet->setCellValue('A4', 'Total Records: ' . number_format($this->rows->count()));
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                ]);

                $sheet->getStyle("A1:{$last}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
                ]);

                // ── Row-cap note ──
                // Row 5 is the spacer under the header band, and it doubles as
                // the truncation notice so the sheet layout below is unchanged
                // (HEADER_ROWS, and therefore startCell(), stay put).
                //
                // Without this the .xlsx was the one format of the four that
                // truncated SILENTLY: 1,500 rows under a header reading "Total
                // Records: 1,500", where the CSV, PDF and print sheet all said
                // plainly that the rest had been dropped. A personal-data
                // extract that is incomplete and does not say so is worse than
                // one that fails, because it gets reconciled against.
                if (filled($this->note)) {
                    $sheet->mergeCells("A5:{$last}5");
                    $sheet->setCellValue('A5', $this->note);
                    $sheet->getStyle('A5')->applyFromArray([
                        // Same amber the print and PDF sheets use for this note.
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '92400E']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            // A merged cell cannot overflow into its neighbours -
                            // they are part of the merge - so without wrapping the
                            // note renders on one line and is cut off at the merge
                            // width. That is wide enough at the default column set
                            // (151-178 character-widths at 8-9 columns) and at a
                            // single column, but not in between: a Columns-modal
                            // selection of 2, 3 or 4 columns gives 36.4, 55.1 or
                            // 72.7 against a ~76-character sentence. Truncating the
                            // truncation warning is the F-001 defect one layer down.
                            'wrapText' => true,
                        ],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                        'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FCD34D']]],
                    ]);
                    // -1 is PhpSpreadsheet's "size to the content": pinning 20pt
                    // would hide the second line that wrapping just created.
                    $sheet->getRowDimension(5)->setRowHeight(-1);
                } else {
                    $sheet->getRowDimension(5)->setRowHeight(6);
                }

                // ── Data table ──
                $sheet->getStyle("A{$dataHeaderRow}:{$last}{$dataHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($dataHeaderRow)->setRowHeight(22);

                if ($this->rows->count() > 0) {
                    $sheet->getStyle("A{$dataHeaderRow}:{$last}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    ]);

                    // Zebra striping, matching the print/PDF output.
                    for ($r = $dataHeaderRow + 1; $r <= $lastRow; $r++) {
                        if (($r - $dataHeaderRow) % 2 === 0) {
                            $sheet->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                            ]);
                        }
                    }

                    $sheet->getStyle('A' . ($dataHeaderRow + 1) . ":{$last}{$lastRow}")
                        ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                }

                // Centre the columns the grid and the print sheet centre — read
                // from the column defs so the three can't disagree.
                $index = 1;
                foreach ($this->columns as $col) {
                    if (($col['align'] ?? 'left') === 'center') {
                        $letter = Coordinate::stringFromColumnIndex($index);
                        $sheet->getStyle("{$letter}{$dataHeaderRow}:{$letter}{$lastRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $index++;
                }

                // ── Logo, floated over the header band ──
                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (is_file($logoPath) && is_readable($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA');
                    $drawing->setDescription('LBSNAA');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(46);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(6);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                // Keep the branded header and the column titles on screen while scrolling.
                $sheet->freezePane('A' . ($dataHeaderRow + 1));

                // PhpSpreadsheet's value binder is global and Maatwebsite never
                // puts it back, so without this every later export in the same
                // process would inherit this class's text typing. Safe here:
                // all cell writing for this sheet is done by AfterSheet.
                SpreadsheetCell::setValueBinder(new DefaultValueBinder());
            },
        ];
    }
}
