<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseSaleQuantityExport implements FromCollection, WithCustomStartCell, WithHeadings, WithEvents
{
    /**
     * @var array<int, array{viewType: string, reportData: array<int, array>}>
     */
    protected array $viewSections;

    protected string $fromDate;

    protected string $toDate;

    protected string $combinedViewLabel;

    protected ?string $selectedStoreName;

    protected ?string $selectedItemNamesLabel;

    /**
     * @param  array<int, array{viewType: string, reportData: array<int, array>}>  $viewSections
     */
    public function __construct(
        array $viewSections,
        string $fromDate,
        string $toDate,
        string $combinedViewLabel,
        ?string $selectedStoreName = null,
        ?string $selectedItemNamesLabel = null
    ) {
        $this->viewSections = $viewSections;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->combinedViewLabel = $combinedViewLabel;
        $this->selectedStoreName = $selectedStoreName;
        $this->selectedItemNamesLabel = $selectedItemNamesLabel;
    }

    /**
     * Headings + data start at row 5; rows 1–4 are filled in AfterSheet (avoids insertNewRowBefore, which breaks PhpSpreadsheet cellmap).
     */
    public function startCell(): string
    {
        return 'A5';
    }

    public function collection(): Collection
    {
        $rows = [];
        $sectionIndex = 0;
        $multiView = count($this->viewSections) > 1;
        foreach ($this->viewSections as $section) {
            $viewLabel = match ($section['viewType']) {
                'subcategory_wise' => 'Subcategory-wise',
                'category_wise' => 'Category-wise',
                default => 'Item-wise',
            };
            if ($multiView) {
                if ($sectionIndex > 0) {
                    $rows[] = ['', '', '', '', '', '', '', ''];
                }
                $rows[] = ['', '— '.$viewLabel.' —', '', '', '', '', '', ''];
            }
            foreach ($section['reportData'] as $index => $row) {
                $rows[] = [
                    $index + 1,
                    $row['category_name'] ?? '—',
                    $row['item_name'] ?? '—',
                    $row['unit'] ?? '—',
                    number_format($row['purchase_qty'] ?? 0, 2),
                    $row['avg_purchase_price'] !== null ? number_format($row['avg_purchase_price'], 2) : '—',
                    number_format($row['sale_qty'] ?? 0, 2),
                    $row['avg_sale_price'] !== null ? number_format($row['avg_sale_price'], 2) : '—',
                ];
            }
            $sectionIndex++;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Category',
            'Item Name',
            'Unit',
            'Total Purchase Qty',
            'Avg Purchase Price',
            'Total Sale Qty',
            'Avg Sale Price',
        ];
    }

    /**
     * Add LBSNAA-style header rows above the table and
     * configure print settings so the header repeats on every page.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Merge LBSNAA banner rows (rows 1–3); data/headings already begin at row 5 via startCell().
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                $formattedFrom = \Carbon\Carbon::parse($this->fromDate)->format('d-m-Y');
                $formattedTo = \Carbon\Carbon::parse($this->toDate)->format('d-m-Y');

                // Row 1: Mess name
                $sheet->setCellValue('A1', "OFFICER'S MESS LBSNAA MUSSOORIE");

                // Row 2: Report title
                $sheet->setCellValue('A2', 'Item Report - Purchase/Sale Quantity');

                // Row 3: Date range + view type
                $storeLabel = ($this->selectedStoreName !== null && $this->selectedStoreName !== '')
                    ? $this->selectedStoreName
                    : 'All Stores';
                $itemsLabel = ($this->selectedItemNamesLabel !== null && $this->selectedItemNamesLabel !== '')
                    ? $this->selectedItemNamesLabel
                    : 'All Items';
                $sheet->setCellValue(
                    'A3',
                    "From {$formattedFrom} To {$formattedTo} | View: {$this->combinedViewLabel} | Store: {$storeLabel} | Items: {$itemsLabel}"
                );

                // Basic styling for header
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getFont()->setSize(10);

                // Column headings are on row 5 (WithCustomStartCell); data from row 6.
                $lastRow = $sheet->getHighestRow();
                $headerRow = 5;
                $tableRange = "A{$headerRow}:H{$lastRow}";

                // Borders for the table.
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->getColor()->setARGB('FFDEE2E6');

                // Header row styling.
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:H{$headerRow}")
                    ->getAlignment()->setHorizontal('center');

                // Column widths.
                $sheet->getColumnDimension('A')->setWidth(6);   // S. No.
                $sheet->getColumnDimension('B')->setWidth(18);  // Category
                $sheet->getColumnDimension('C')->setWidth(28);  // Item Name
                $sheet->getColumnDimension('D')->setWidth(10);  // Unit
                $sheet->getColumnDimension('E')->setWidth(18);  // Total Purchase Qty
                $sheet->getColumnDimension('F')->setWidth(18);  // Avg Purchase Price
                $sheet->getColumnDimension('G')->setWidth(18);  // Total Sale Qty
                $sheet->getColumnDimension('H')->setWidth(18);  // Avg Sale Price

                // Right-align numeric columns (E–H).
                $sheet->getStyle("E{$headerRow}:H{$lastRow}")
                    ->getAlignment()->setHorizontal('right');

                // Section title rows: bold category column when it looks like a section marker
                for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
                    $bVal = (string) $sheet->getCell("B{$r}")->getValue();
                    if (str_starts_with($bVal, '— ') && str_ends_with($bVal, ' —')) {
                        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
                    }
                }

                // Freeze pane below header + column titles.
                $sheet->freezePane('A'.($headerRow + 1));

                // Repeat header rows on every printed page.
                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);
            },
        ];
    }
}
