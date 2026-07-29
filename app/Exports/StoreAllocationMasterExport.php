<?php

namespace App\Exports;

use App\Support\DataTableSearchHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Mess Store Allocation listing.
 *
 * Mirrors {@see ItemSubcategoryMasterExport}: a formatted workbook whose header
 * block + table styling match the branded Print / PDF layout. Kept in step with
 * {@see \App\Http\Controllers\Mess\StoreAllocationController::export()}.
 */
class StoreAllocationMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected ?string $search;

    /**
     * On-screen data-column indexes (0..5) the user left visible via the Column
     * Visibility modal, or null when every column shows. S.No (0) is always kept;
     * Action (6) is never exported.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    protected int $rowCount = 0;

    public function __construct(?string $search = null, ?array $visibleColumns = null)
    {
        $this->search = ($search !== null && trim($search) !== '') ? trim($search) : null;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',       'width' => 8,  'align' => 'center'],
            1 => ['key' => 'store_name', 'width' => 28, 'align' => null],
            2 => ['key' => 'item_name',  'width' => 28, 'align' => null],
            3 => ['key' => 'item_type',  'width' => 22, 'align' => null],
            4 => ['key' => 'quantity',   'width' => 16, 'align' => 'center'],
            5 => ['key' => 'date',       'width' => 16, 'align' => 'center'],
            6 => ['key' => 'total',      'width' => 16, 'align' => 'right'],
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
        return 'Mess Store Allocation';
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
                $metaLines[] = ['text' => 'Mess Store Allocation', 'style' => 'title'];

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
                        $align = $col['align'] ?? null;
                        if ($align === 'center' || $align === 'right') {
                            $letter = Coordinate::stringFromColumnIndex($i + 1);
                            $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                                ->getAlignment()->setHorizontal(
                                    $align === 'right' ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_CENTER
                                );
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
     * One row per allocation item line — mirrors
     * {@see \App\Http\Controllers\Mess\StoreAllocationController::storeAllocationRowsBaseQuery()}.
     */
    private function recordsQuery(): Collection
    {
        $itemLabelSql = $this->itemSubcategoryLabelSql('mis');

        $query = DB::table('mess_store_allocation_items as sai')
            ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
            ->leftJoin('mess_sub_stores as mss', 'sa.sub_store_id', '=', 'mss.id')
            ->leftJoin('mess_item_subcategories as mis', 'sai.item_subcategory_id', '=', 'mis.id')
            ->leftJoin('mess_item_categories as mic', 'mis.category_id', '=', 'mic.id')
            ->whereNotNull('sa.sub_store_id')
            ->select([
                'mss.sub_store_name',
                DB::raw("{$itemLabelSql} as item_name"),
                'mic.category_name',
                'sai.quantity',
                'sai.total_price',
                'sa.allocation_date',
            ]);

        if ($this->search !== null) {
            $tokens = DataTableSearchHelper::tokens($this->search);
            foreach ($tokens as $token) {
                $like = DataTableSearchHelper::likePattern($token);
                $query->where(function ($q) use ($like, $itemLabelSql) {
                    $q->where('mss.sub_store_name', 'like', $like)
                        ->orWhereRaw("{$itemLabelSql} LIKE ?", [$like])
                        ->orWhere('mic.category_name', 'like', $like)
                        ->orWhere('sai.quantity', 'like', $like)
                        ->orWhere('sa.allocation_date', 'like', $like);
                });
            }
        }

        return $query->orderByDesc('sa.allocation_date')
            ->orderByDesc('sa.id')
            ->orderByDesc('sai.id')
            ->get();
    }

    private function itemSubcategoryLabelSql(string $alias = 'mis'): string
    {
        $parts = [];
        foreach (['item_name', 'subcategory_name', 'name'] as $col) {
            if (Schema::hasColumn('mess_item_subcategories', $col)) {
                $parts[] = "NULLIF(TRIM({$alias}.{$col}), '')";
            }
        }

        if ($parts === []) {
            return "'-'";
        }

        return 'COALESCE(' . implode(', ', $parts) . ", '-')";
    }

    private function mapRecord($record): array
    {
        $date = '-';
        if (! empty($record->allocation_date)) {
            try {
                $date = \Carbon\Carbon::parse($record->allocation_date)->format('d-m-Y');
            } catch (\Throwable $e) {
                $date = (string) $record->allocation_date;
            }
        }

        $total = ($record->total_price !== null && $record->total_price !== '')
            ? number_format((float) $record->total_price, 2)
            : '-';

        return [
            'store_name' => (string) ($record->sub_store_name ?? '-'),
            'item_name'  => (string) ($record->item_name ?? '-'),
            'item_type'  => (string) ($record->category_name ?? '-'),
            'quantity'   => (string) ($record->quantity ?? ''),
            'date'       => $date,
            'total'      => $total,
        ];
    }

    /**
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Store Name',
            'Item Name',
            'Item Type',
            'Number of Items',
            'Date',
            'Total',
        ];
    }

    private function exportFilterLine(): string
    {
        return $this->search !== null
            ? 'Applied Filters:   Search: ' . $this->search
            : '';
    }
}
