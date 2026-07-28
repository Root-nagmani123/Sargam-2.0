<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Selling Voucher listing.
 *
 * Unlike the other mess exports, the source rows are prepared by the controller
 * (which owns the complex joined query) and passed in as plain associative arrays,
 * so Download / Print stay exactly in step with the filtered on-screen list.
 * Kept in step with {@see \App\Http\Controllers\Mess\KitchenIssueController::export()}.
 */
class SellingVoucherMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    /** @var array<int,array<string,string>> */
    protected array $records;
    protected string $filterLine;
    protected string $reportTitle;

    /**
     * On-screen data-column indexes (0..9) left visible via the Column Visibility
     * modal, or null when every column shows. S.No (0) is always kept.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    protected int $rowCount = 0;

    /**
     * @param array<int,array<string,string>> $records
     */
    public function __construct(array $records, string $filterLine = '', ?array $visibleColumns = null, string $reportTitle = 'Selling Voucher')
    {
        $this->records = array_values($records);
        $this->filterLine = $filterLine;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
        $this->reportTitle = $reportTitle;
    }

    /**
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',         'width' => 8,  'align' => 'center'],
            1 => ['key' => 'item_name',    'width' => 24, 'align' => null],
            2 => ['key' => 'item_qty',     'width' => 10, 'align' => 'center'],
            3 => ['key' => 'return_qty',   'width' => 10, 'align' => 'center'],
            4 => ['key' => 'store',        'width' => 26, 'align' => null],
            5 => ['key' => 'client_type',  'width' => 14, 'align' => null],
            6 => ['key' => 'client_name',  'width' => 24, 'align' => null],
            7 => ['key' => 'payment',      'width' => 12, 'align' => 'center'],
            8 => ['key' => 'request_date', 'width' => 14, 'align' => 'center'],
            9 => ['key' => 'status',       'width' => 14, 'align' => 'center'],
        ];
    }

    /**
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function activeColumns(): array
    {
        $cols = $this->columnDefinitions();
        if ($this->visibleColumns === null) {
            return array_values($cols);
        }

        $keep = array_merge([0], $this->visibleColumns);

        $filtered = [];
        foreach ($cols as $idx => $col) {
            if (in_array($idx, $keep, true)) {
                $filtered[] = $col;
            }
        }

        return $filtered !== [] ? $filtered : array_values($cols);
    }

    public function activeHeadings(): array
    {
        $headings = array_merge(['S.No.'], $this->columnHeadings());
        $keys = array_column($this->columnDefinitions(), 'key');

        return array_map(
            fn ($col) => $headings[array_search($col['key'], $keys, true)] ?? '',
            $this->activeColumns()
        );
    }

    private function pickActive(array $row): array
    {
        $out = [];
        foreach ($this->activeColumns() as $col) {
            $out[$col['key']] = $row[$col['key']] ?? '';
        }

        return $out;
    }

    public function collection()
    {
        $data = $this->rows();
        $this->rowCount = $data->count();

        return $data;
    }

    public function pdfRows(): Collection
    {
        return $this->rows();
    }

    private function rows(): Collection
    {
        return collect($this->records)->values()->map(fn ($record, $index) => array_values(
            $this->pickActive(array_merge(['s_no' => (string) ($index + 1)], $record))
        ));
    }

    public function title(): string
    {
        return substr($this->reportTitle, 0, 31);
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->activeColumns() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 1)] = $col['width'];
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnHeadings = $this->activeHeadings();
                $colCount = count($columnHeadings);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);

                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => $this->reportTitle, 'style' => 'title'];

                if (trim($this->filterLine) !== '') {
                    $metaLines[] = ['text' => $this->filterLine, 'style' => 'meta'];
                }

                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . count($this->records),
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                $headerRows = count($metaLines) + 1;
                $sheet->insertNewRowBefore(1, $headerRows);

                $headingRow = count($metaLines) + 1;
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max(count($this->records), 0);

                $sheet->setShowGridlines(false);

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
                        default:
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                foreach ($columnHeadings as $ci => $heading) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, $heading);
                }
                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(26);

                $recCount = count($this->records);
                if ($recCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(10);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);
                    foreach ($this->activeColumns() as $i => $col) {
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

                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);

                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

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

    /**
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Item Name',
            'Item Qty',
            'Return Qty',
            'Transfer From Store',
            'Client Type',
            'Client Name',
            'Payment',
            'Request Date',
            'Status',
        ];
    }
}
