<?php

namespace App\Exports;

use App\Models\Mess\ItemCategory;
use App\Models\Mess\ItemSubcategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Sub-Category Item Master listing.
 *
 * Mirrors {@see StoreMasterExport}: a formatted workbook whose header block +
 * table styling match the branded Print / PDF layout. Kept in step with
 * {@see \App\Http\Controllers\Mess\ItemSubcategoryController::export()}.
 */
class ItemSubcategoryMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected ?string $search;
    protected ?string $categoryId;

    /**
     * On-screen data-column indexes (0..6) the user left visible via the Column
     * Visibility modal, or null when every column shows. S.No (0) is always kept;
     * Action (7) is never exported.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    protected int $rowCount = 0;

    public function __construct(?string $search = null, ?string $categoryId = null, ?array $visibleColumns = null)
    {
        $this->search = ($search !== null && trim($search) !== '') ? trim($search) : null;
        $this->categoryId = ($categoryId !== null && trim((string) $categoryId) !== '') ? trim((string) $categoryId) : null;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',             'width' => 8,  'align' => 'center'],
            1 => ['key' => 'category',         'width' => 26, 'align' => null],
            2 => ['key' => 'item_name',        'width' => 28, 'align' => null],
            3 => ['key' => 'item_code',        'width' => 20, 'align' => null],
            4 => ['key' => 'unit_measurement', 'width' => 18, 'align' => 'center'],
            5 => ['key' => 'alert_quantity',   'width' => 14, 'align' => 'center'],
            6 => ['key' => 'status',           'width' => 14, 'align' => 'center'],
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
        return 'Sub-Category Item Master';
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
                $metaLines[] = ['text' => 'Sub-Category Item Master', 'style' => 'title'];

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
        $query = ItemSubcategory::query()->with('category');

        if ($this->categoryId !== null) {
            $validIds = ItemCategory::active()->pluck('id')->all();
            if (in_array((int) $this->categoryId, array_map('intval', $validIds), true)) {
                $query->where('category_id', (int) $this->categoryId);
            }
        }

        if ($this->search !== null) {
            $term = $this->search;
            $nameCol = ItemSubcategory::displayNameColumnForQuery();
            $query->where(function ($q) use ($term, $nameCol) {
                $q->where($nameCol, 'like', '%' . $term . '%')
                  ->orWhere('unit_measurement', 'like', '%' . $term . '%')
                  ->orWhere('item_code', 'like', '%' . $term . '%')
                  ->orWhereHas('category', function ($cat) use ($term) {
                      $cat->where('category_name', 'like', '%' . $term . '%');
                  });
            });
        }

        return $query->orderByDesc('id')->get();
    }

    private function mapRecord($record): array
    {
        $alert = (isset($record->alert_quantity) && $record->alert_quantity !== null && $record->alert_quantity !== '')
            ? number_format((float) $record->alert_quantity, 2)
            : '-';

        return [
            'category'         => optional($record->category)->category_name ?: '-',
            'item_name'        => (string) ($record->item_name ?? ''),
            'item_code'        => $record->item_code ?: '-',
            'unit_measurement' => $record->unit_measurement ?: '-',
            'alert_quantity'   => $alert,
            'status'           => ucfirst($record->status ?: ItemSubcategory::STATUS_ACTIVE),
        ];
    }

    /**
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Category Name',
            'Item Name',
            'Item Code',
            'Unit Measurement',
            'Alert Qty',
            'Status',
        ];
    }

    private function exportFilterLine(): string
    {
        $parts = [];
        if ($this->categoryId !== null) {
            $cat = ItemCategory::find((int) $this->categoryId);
            if ($cat) {
                $parts[] = 'Category: ' . $cat->category_name;
            }
        }
        if ($this->search !== null) {
            $parts[] = 'Search: ' . $this->search;
        }

        return $parts === [] ? '' : 'Applied Filters:   ' . implode('   |   ', $parts);
    }
}
