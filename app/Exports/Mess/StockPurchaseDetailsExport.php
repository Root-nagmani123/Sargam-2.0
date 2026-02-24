<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Export structure matches the on-screen report (and print/PDF):
 * - Row 1: Report title
 * - Row 2: Date range
 * - Row 3: Vendor
 * - Row 4: Empty
 * - Row 5: Table header (Item, Quantity, Purchase (₹), Total (₹))
 * - Then: for each bill — bill header row, item rows, bill total row
 */
class StockPurchaseDetailsExport implements FromCollection, WithStyles, WithColumnWidths
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    protected $purchaseOrders;

    protected string $fromDate;
    protected string $toDate;
    protected string $vendorName;

    public function __construct($purchaseOrders, string $fromDate, string $toDate, string $vendorName = 'All Vendors')
    {
        $this->purchaseOrders = $purchaseOrders;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->vendorName = $vendorName;
    }

    public function collection(): Collection
    {
        $rows = [];

        // Same as view: title
        $rows[] = ['Stock Purchase Details', '', '', ''];

        // Same as view: date range (e.g. "01 January 2025 — 31 January 2025")
        $fromFormatted = $this->formatDateForReport($this->fromDate);
        $toFormatted = $this->formatDateForReport($this->toDate);
        $rows[] = [$fromFormatted . ' — ' . $toFormatted, '', '', ''];

        // Same as view: vendor line
        $rows[] = ['Vendor: ' . $this->vendorName, '', '', ''];

        // Empty row
        $rows[] = ['', '', '', ''];

        // Table header (same columns as view)
        $rows[] = ['Item', 'Quantity', 'Purchase (₹)', 'Total (₹)'];

        foreach ($this->purchaseOrders as $order) {
            $storeName = $order->store ? $order->store->store_name : 'N/A';
            $billLabel = $storeName . ' (Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
            $billTotal = 0;

            // Bill header row (same as view)
            $rows[] = [$billLabel, '', '', ''];

            foreach ($order->items as $item) {
                $qty = $item->quantity ?? 0;
                $rate = $item->unit_price ?? 0;
                $total = $qty * $rate;
                $billTotal += $total;
                $itemName = $item->itemSubcategory->item_name
                    ?? $item->itemSubcategory->subcategory_name
                    ?? $item->itemSubcategory->name
                    ?? 'N/A';

                $rows[] = [
                    $itemName,
                    number_format($qty, 2),
                    number_format($rate, 1),
                    number_format($total, 2),
                ];
            }

            // Bill total row (same as view)
            $rows[] = ['Bill Total', '', '', number_format($billTotal, 2)];
        }

        return collect($rows);
    }

    private function formatDateForReport(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? date('d F Y', $ts) : $date;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 12,
            'C' => 14,
            'D' => 14,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'D';

        // Title row (1): bold, larger
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Date row (2)
        $sheet->getStyle('A2:D2')->getFont()->setSize(11);

        // Vendor row (3)
        $sheet->getStyle('A3:D3')->getFont()->setSize(11);

        // Table header (row 5): bold, fill
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);
        $sheet->getStyle('A5:D5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D3D6D9');
        $sheet->getStyle('A5:D5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B5:D5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Data area: borders and alignment (from row 6)
        if ($lastRow >= 6) {
            $sheet->getStyle('A6:' . $lastCol . $lastRow)->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('B6:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Bill header rows: bold, fill (same as view)
            for ($r = 6; $r <= $lastRow; $r++) {
                $cellA = $sheet->getCell('A' . $r)->getValue();
                if (is_string($cellA) && str_contains($cellA, '(Primary)')) {
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('6C757D');
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFont()->getColor()->setRGB('FFFFFF');
                }
                if (is_string($cellA) && $cellA === 'Bill Total') {
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFont()->setBold(true);
                }
            }
        }

        return [];
    }
}
