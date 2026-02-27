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
                $taxPercent = $item->tax_percent ?? 0;
                $subtotal = $qty * $rate;
                $taxAmount = round($subtotal * ($taxPercent / 100), 2);
                $total = $subtotal + $taxAmount;
                $itemName = $item->itemSubcategory->item_name
                    ?? $item->itemSubcategory->subcategory_name
                    ?? $item->itemSubcategory->name
                    ?? 'N/A';
                $itemCode = $item->itemSubcategory->item_code ?? '—';
                $unit = $item->unit ?? '—';

                $rows[] = [
                    $itemName,
                    $itemCode,
                    $unit,
                    number_format($qty, 2),
                    number_format($rate, 2),
                    number_format($taxPercent, 2) . '%',
                    number_format($taxAmount, 2),
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
            'Bill No',
            'PO Date',
            'Store',
            'Vendor',
            'Item Name',
            'Item Code',
            'Unit',
            'Quantity',
            'Unit Price',
            'Tax %',
            'Tax Amount',
            'Total',
        ];
    }
}
