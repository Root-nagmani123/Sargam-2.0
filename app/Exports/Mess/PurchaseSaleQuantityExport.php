<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseSaleQuantityExport implements FromCollection, WithHeadings, WithEvents
{
    protected array $reportData;
    protected string $fromDate;
    protected string $toDate;
    protected string $viewType;
    protected ?string $selectedStoreName;
    protected ?string $selectedItemNamesLabel;

    public function __construct(
        array $reportData,
        string $fromDate,
        string $toDate,
        string $viewType,
        ?string $selectedStoreName = null,
        ?string $selectedItemNamesLabel = null
    ) {
        $this->reportData             = $reportData;
        $this->fromDate               = $fromDate;
        $this->toDate                 = $toDate;
        $this->viewType               = $viewType;
        $this->selectedStoreName      = $selectedStoreName;
        $this->selectedItemNamesLabel = $selectedItemNamesLabel;
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->reportData as $index => $row) {
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

                // Insert 4 rows at the top for the header + spacer.
                $sheet->insertNewRowBefore(1, 4);

                // Merge header cells across all 8 columns (A–H).
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                $formattedFrom = \Carbon\Carbon::parse($this->fromDate)->format('d-F-Y');
                $formattedTo   = \Carbon\Carbon::parse($this->toDate)->format('d-F-Y');

                // Row 1: Mess name
                $sheet->setCellValue('A1', "OFFICER'S MESS LBSNAA MUSSOORIE");

                // Row 2: Report title
                $sheet->setCellValue('A2', 'Item Report - Purchase/Sale Quantity');

                // Row 3: Date range + view type
                $viewLabel = match ($this->viewType) {
                    'subcategory_wise' => 'Subcategory-wise',
                    'category_wise'    => 'Category-wise',
                    default            => 'Item-wise',
                };
                $storeLabel = ($this->selectedStoreName !== null && $this->selectedStoreName !== '')
                    ? $this->selectedStoreName
                    : 'All Stores';
                $itemsLabel = ($this->selectedItemNamesLabel !== null && $this->selectedItemNamesLabel !== '')
                    ? $this->selectedItemNamesLabel
                    : 'All Items';
                $sheet->setCellValue(
                    'A3',
                    "From {$formattedFrom} To {$formattedTo} | View: {$viewLabel} | Store: {$storeLabel} | Items: {$itemsLabel}"
                );

                // Basic styling for header
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getFont()->setSize(10);

                // Table header is now on row 5 (because we inserted 4 rows).
                $lastRow    = $sheet->getHighestRow();
                $headerRow  = 5;
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

                // Freeze pane below header + column titles.
                $sheet->freezePane("A" . ($headerRow + 1));

                // Repeat header rows on every printed page.
                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);
            },
        ];
    }
}
