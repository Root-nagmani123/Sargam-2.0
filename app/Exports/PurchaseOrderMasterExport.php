<?php

namespace App\Exports;

use App\Models\Mess\PurchaseOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Purchase Order listing.
 *
 * Mirrors {@see StoreMasterExport}. Kept in step with
 * {@see \App\Http\Controllers\Mess\PurchaseOrderController::export()} — same
 * filters (date range, vendor, store, search) and column order as the on-screen list.
 */
class PurchaseOrderMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected ?string $search;
    /** @var array<int,int> */
    protected array $vendorIds;
    /** @var array<int,int> */
    protected array $storeIds;
    protected ?string $dateFrom;
    protected ?string $dateTo;

    /**
     * On-screen data-column indexes (0..4) left visible via the Column Visibility
     * modal, or null when every column shows. S.No (0) is always kept; Action (5)
     * is never exported.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    protected int $rowCount = 0;

    /**
     * @param array<int,int> $vendorIds
     * @param array<int,int> $storeIds
     */
    public function __construct(
        ?string $search = null,
        array $vendorIds = [],
        array $storeIds = [],
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?array $visibleColumns = null
    ) {
        $this->search = ($search !== null && trim($search) !== '') ? trim($search) : null;
        $this->vendorIds = array_values(array_filter(array_map('intval', $vendorIds), fn ($v) => $v > 0));
        $this->storeIds = array_values(array_filter(array_map('intval', $storeIds), fn ($v) => $v > 0));
        $this->dateFrom = ($dateFrom !== null && trim($dateFrom) !== '') ? trim($dateFrom) : null;
        $this->dateTo = ($dateTo !== null && trim($dateTo) !== '') ? trim($dateTo) : null;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',      'width' => 8,  'align' => 'center'],
            1 => ['key' => 'po_number', 'width' => 22, 'align' => null],
            2 => ['key' => 'vendor',    'width' => 32, 'align' => null],
            3 => ['key' => 'store',     'width' => 32, 'align' => null],
            4 => ['key' => 'status',    'width' => 16, 'align' => 'center'],
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
        $data = $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));

        $this->rowCount = $data->count();

        return $data;
    }

    public function pdfRows(): Collection
    {
        return $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));
    }

    public function title(): string
    {
        return 'Purchase Order';
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
                $metaLines[] = ['text' => 'Purchase Order', 'style' => 'title'];

                $filterLine = $this->exportFilterLine();
                if ($filterLine !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
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

                if ($this->rowCount > 0) {
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

    private function recordsQuery()
    {
        $query = PurchaseOrder::query()->with(['vendor', 'store']);

        if ($this->dateFrom !== null) {
            $query->whereDate('po_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== null) {
            $query->whereDate('po_date', '<=', $this->dateTo);
        }
        if ($this->vendorIds !== []) {
            $query->whereIn('vendor_id', $this->vendorIds);
        }
        if ($this->storeIds !== []) {
            $query->whereIn('store_id', $this->storeIds);
        }

        if ($this->search !== null) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('po_number', 'like', '%' . $term . '%')
                  ->orWhere('status', 'like', '%' . $term . '%')
                  ->orWhereHas('vendor', function ($v) use ($term) {
                      $v->where('name', 'like', '%' . $term . '%');
                  })
                  ->orWhereHas('store', function ($s) use ($term) {
                      $s->where('store_name', 'like', '%' . $term . '%');
                  });
            });
        }

        return $query->orderByDesc('po_date')->orderByDesc('id')->get();
    }

    private function mapRecord($record): array
    {
        return [
            'po_number' => (string) ($record->po_number ?? ''),
            'vendor'    => optional($record->vendor)->name ?: 'N/A',
            'store'     => optional($record->store)->store_name ?: 'N/A',
            'status'    => ucfirst((string) ($record->status ?? '')),
        ];
    }

    /**
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Order No.',
            'Vendor',
            'Store',
            'Status',
        ];
    }

    private function exportFilterLine(): string
    {
        $parts = [];
        if ($this->dateFrom !== null || $this->dateTo !== null) {
            $parts[] = 'Period: ' . ($this->dateFrom ?: '…') . ' to ' . ($this->dateTo ?: '…');
        }
        if ($this->vendorIds !== []) {
            $names = \App\Models\Mess\Vendor::whereIn('id', $this->vendorIds)->pluck('name')->all();
            if ($names !== []) {
                $parts[] = 'Vendor: ' . implode(', ', $names);
            }
        }
        if ($this->storeIds !== []) {
            $names = \App\Models\Mess\Store::whereIn('id', $this->storeIds)->pluck('store_name')->all();
            if ($names !== []) {
                $parts[] = 'Store: ' . implode(', ', $names);
            }
        }
        if ($this->search !== null) {
            $parts[] = 'Search: ' . $this->search;
        }

        return $parts === [] ? '' : 'Applied Filters:   ' . implode('   |   ', $parts);
    }
}
