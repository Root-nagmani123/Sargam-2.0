<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockPurchaseDetailsExport implements FromCollection, WithHeadings
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    protected $purchaseOrders;

    public function __construct($purchaseOrders)
    {
        $this->purchaseOrders = $purchaseOrders;
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->purchaseOrders as $order) {
            $storeName = $order->store ? $order->store->store_name : 'N/A';
            $vendorName = $order->vendor ? $order->vendor->name : 'N/A';
            $billNo = $order->po_number ?? $order->id;
            $poDate = $order->po_date ? $order->po_date->format('d-m-Y') : '';

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
                    $billNo,
                    $poDate,
                    $storeName,
                    $vendorName,
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
        }
        return collect($rows);
    }

    public function headings(): array
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
