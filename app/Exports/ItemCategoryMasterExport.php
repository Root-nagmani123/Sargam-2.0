<?php

namespace App\Exports;

use App\Models\Mess\ItemCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Category Item Master listing.
 *
 * Mirrors {@see StoreMasterExport}: a formatted workbook whose header block +
 * table styling match the branded Print / PDF layout (institution logos, blue
 * title band, blue column header, bordered zebra rows). Kept in step with
 * {@see \App\Http\Controllers\Mess\ItemCategoryController::export()}.
 */
class ItemCategoryMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected ?string $search;
    protected ?string $categoryType;

    /**
     * On-screen data-column indexes (0..4) the user left visible via the Column
     * Visibility modal, or null when every column shows. S.No (0) is always kept;
     * Action (5) is never exported.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    /** Data-row count, captured while streaming the collection (for the meta line). */
    protected int $rowCount = 0;

    public function __construct(?string $search = null, ?string $categoryType = null, ?array $visibleColumns = null)
    {
        $this->search = ($search !== null && trim($search) !== '') ? trim($search) : null;
        $this->categoryType = ($categoryType !== null && trim($categoryType) !== '') ? trim($categoryType) : null;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * All exportable data columns, keyed by their on-screen table index (0..4).
     *
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',          'width' => 8,  'align' => 'center'],
            1 => ['key' => 'category_name', 'width' => 32, 'align' => null],
            2 => ['key' => 'category_type', 'width' => 20, 'align' => 'center'],
            3 => ['key' => 'description',   'width' => 46, 'align' => null],
            4 => ['key' => 'status',        'width' => 14, 'align' => 'center'],
        ];
    }

    /**
     * The visible subset of {@see columnDefinitions()}, in order (all when
     * unfiltered). S.No (index 0) is always included.
     *
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

    /** Headings for the active columns, in the same order as {@see activeColumns()}. */
    public function activeHeadings(): array
    {
        $headings = array_merge(['S.No.'], $this->columnHeadings());
        $keys = array_column($this->columnDefinitions(), 'key');

        return array_map(
            fn ($col) => $headings[array_search($col['key'], $keys, true)] ?? '',
            $this->activeColumns()
        );
    }

    /** Narrow an associative row down to the active columns, in order. */
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

    /** Rows for the PDF layout (associative order stripped to values, S.No prepended). */
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
        return 'Category Item Master';
    }

    /**
     * Column widths tuned to the content. Only the active columns are written,
     * mapped onto sequential sheet letters so a hidden column never leaves a gap.
     */
    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->activeColumns() as $i => $col) {
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

                // --- Meta lines shown above the table (same content as Print/PDF) ---
                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => 'Category Item Master', 'style' => 'title'];

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
                            $sheet->getRowDimension($r)->setRowHeight(42); // room for the logos
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
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
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

    private function recordsQuery()
    {
        $query = ItemCategory::query();

        if ($this->categoryType !== null) {
            $validTypes = array_keys(ItemCategory::categoryTypes());
            if (in_array($this->categoryType, $validTypes, true) && Schema::hasColumn('mess_item_categories', 'category_type')) {
                $query->where('category_type', $this->categoryType);
            }
        }

        if ($this->search !== null) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('category_name', 'like', '%' . $term . '%')
                  ->orWhere('description', 'like', '%' . $term . '%');
            });
        }

        // Same order the listing uses (ItemCategoryController → orderByDesc('id')).
        return $query->orderByDesc('id')->get();
    }

    private function mapRecord($record): array
    {
        $types = ItemCategory::categoryTypes();
        $type = $record->category_type ?: ItemCategory::TYPE_RAW_MATERIAL;
        $status = $record->status ?: ItemCategory::STATUS_ACTIVE;

        return [
            'category_name' => (string) ($record->category_name ?? ''),
            'category_type' => $types[$type] ?? ucfirst(str_replace('_', ' ', (string) $type)),
            'description'   => $record->description ?: '-',
            'status'        => ucfirst($status),
        ];
    }

    /**
     * Data-column headings (excluding S.No), matching the listing table headers.
     *
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Category Name',
            'Category Type',
            'Item Category Description',
            'Status',
        ];
    }

    /** "Applied Filters: Type: …" line for the header, or '' when unfiltered. */
    private function exportFilterLine(): string
    {
        $parts = [];
        if ($this->categoryType !== null) {
            $types = ItemCategory::categoryTypes();
            $parts[] = 'Type: ' . ($types[$this->categoryType] ?? ucfirst(str_replace('_', ' ', $this->categoryType)));
        }
        if ($this->search !== null) {
            $parts[] = 'Search: ' . $this->search;
        }

        return $parts === [] ? '' : 'Applied Filters:   ' . implode('   |   ', $parts);
    }
}
