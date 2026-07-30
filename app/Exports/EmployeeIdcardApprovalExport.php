<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Employee ID Card Approval listings
 * (Approval I and Approval II).
 *
 * A plain CSV can't carry colour/borders/logos, so this builds a formatted
 * workbook whose header block + table styling mirror the Print / PDF layout:
 * institution logos, blue title band, blue column header, bordered zebra rows.
 * Mirrors {@see DuplicateVehiclePassExport}.
 *
 * The rows are supplied by the controller (which owns the multi-source query),
 * so this class only formats — it never re-queries.
 */
class EmployeeIdcardApprovalExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected Collection $records;
    protected string $reportTitle;
    protected string $filterLine;

    /** Data-row count, captured while streaming the collection (for the meta line). */
    protected int $rowCount = 0;

    public function __construct($records, string $reportTitle, string $filterLine = '')
    {
        $this->records = $records instanceof Collection ? $records : collect($records);
        $this->reportTitle = $reportTitle;
        $this->filterLine = $filterLine;
    }

    /**
     * All exportable data columns, in on-screen order. Photo + action columns are
     * never exported so the Excel / PDF / Print match column-for-column.
     *
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            ['key' => 's_no',          'width' => 6,  'align' => 'center'],
            ['key' => 'request_id',    'width' => 12, 'align' => 'center'],
            ['key' => 'name',          'width' => 28, 'align' => null],
            ['key' => 'designation',   'width' => 26, 'align' => null],
            ['key' => 'id_card_no',    'width' => 16, 'align' => null],
            ['key' => 'employee_dob',  'width' => 14, 'align' => 'center'],
            ['key' => 'blood_group',   'width' => 11, 'align' => 'center'],
            ['key' => 'mobile_no',     'width' => 15, 'align' => null],
            ['key' => 'created_date',  'width' => 14, 'align' => 'center'],
            ['key' => 'type',          'width' => 13, 'align' => 'center'],
            ['key' => 'card_type',     'width' => 16, 'align' => null],
        ];
    }

    /** The flat list of column headings (S.No included), same order as the layout. */
    public function activeHeadings(): array
    {
        return [
            'S.No.', 'Request ID', 'Employee Name', 'Designation', 'ID Card No',
            'Date of Birth', 'Blood Group', 'Mobile No', 'Request Date', 'Request Type', 'Card Type',
        ];
    }

    public function collection()
    {
        $data = $this->pdfRows();
        $this->rowCount = $data->count();

        return $data;
    }

    /** Rows as sequential arrays (S.No prepended), reused by the PDF / Print views. */
    public function pdfRows(): Collection
    {
        return $this->records
            ->values()
            ->map(fn ($record, $index) => array_values(
                array_merge(['s_no' => $index + 1], $this->mapRecord($record))
            ));
    }

    public function title(): string
    {
        return 'ID Card Approval';
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->columnDefinitions() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 1)] = $col['width'];
        }

        return $widths;
    }

    /**
     * Build the branded header block + table styling after the data is written.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnHeadings = $this->activeHeadings();
                $colCount = count($columnHeadings);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);

                // --- Meta lines shown above the table (same content as Print / PDF) ---
                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => $this->reportTitle, 'style' => 'title'];

                if ($this->filterLine !== '') {
                    $metaLines[] = ['text' => $this->filterLine, 'style' => 'meta'];
                }

                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                $headerRows = count($metaLines) + 1;
                $sheet->insertNewRowBefore(1, $headerRows);

                $headingRow = count($metaLines) + 1;
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max($this->rowCount, 0);

                $sheet->setShowGridlines(false);

                // --- Meta rows: merge across the table width and style per role ---
                foreach ($metaLines as $i => $line) {
                    $r = $i + 1;
                    $range = "A{$r}:{$lastCol}{$r}";
                    $sheet->mergeCells($range);
                    $sheet->setCellValue("A{$r}", $line['text']);
                    $sheet->getStyle($range)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $font = $sheet->getStyle("A{$r}")->getFont();
                    switch ($line['style']) {
                        case 'inst':
                            $font->setBold(true)->setSize(13)->getColor()->setRGB('102A43');
                            $sheet->getRowDimension($r)->setRowHeight(42);
                            break;
                        case 'title':
                            $font->setBold(true)->setSize(16)->getColor()->setRGB('004A93');
                            $sheet->getStyle($range)->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('004A93');
                            $sheet->getRowDimension($r)->setRowHeight(24);
                            break;
                        case 'spacer':
                            $sheet->getRowDimension($r)->setRowHeight(6);
                            break;
                        default: // meta
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                // --- Column-heading row: blue band, white bold, centred, bordered ---
                foreach ($columnHeadings as $ci => $heading) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, $heading);
                }
                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(26);

                // --- Data rows: borders, top-align + wrap, zebra striping ---
                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(9);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);

                    foreach ($this->columnDefinitions() as $i => $col) {
                        if (($col['align'] ?? null) === 'center') {
                            $letter = Coordinate::stringFromColumnIndex($i + 1);
                            $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                        if (($r - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');
                        }
                    }
                }

                // --- Borders around the whole table (heading + body) ---
                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                // --- Institution logos, floated over the first (institution) row ---
                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);

                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

    /** Anchor an image (if it exists) over the given cell, sized to the header row. */
    private function placeLogo($sheet, string $path, string $coordinates, int $offsetX): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY(3);
        $drawing->setWorksheet($sheet);
    }

    /** Normalise one source row into the exported column order. */
    private function mapRecord($record): array
    {
        $record = is_array($record) ? (object) $record : $record;

        return [
            'request_id'   => $record->id ?? '--',
            'name'         => $record->name ?: '--',
            'designation'  => $record->designation ?: '--',
            'id_card_no'   => $record->id_card_no ?: '--',
            'employee_dob' => $this->formatDate($record->employee_dob ?? null),
            'blood_group'  => $record->blood_group ?: '--',
            'mobile_no'    => $record->mobile_no ?: '--',
            'created_date' => $this->formatDate($record->created_date ?? null),
            'type'         => $record->type ?: '--',
            'card_type'    => $record->card_type ?: '--',
        ];
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '--';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
