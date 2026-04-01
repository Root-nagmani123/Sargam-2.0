@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Purchase Details Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 16px 18px;
            color: #222;
        }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .page-header-top {
            display: table;
            width: 100%;
        }
        .page-header-col {
            display: table-cell;
            vertical-align: middle;
        }
        .page-header-title {
            text-align: center;
        }
        .page-header-title h1 {
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .page-header-title h2 {
            font-size: 10px;
            margin: 2px 0 0;
            text-transform: uppercase;
            color: #004a93;
        }
        .page-header-sub {
            font-size: 8px;
            margin-top: 2px;
            color: #555;
        }
        .meta-row {
            font-size: 8px;
            margin-top: 6px;
        }
        .meta-row span {
            display: inline-block;
            margin-right: 14px;
        }
        .bill-header {
            margin: 6px 0 4px;
            padding: 4px 6px;
            border-radius: 3px;
            background: #f3f6fb;
            border: 1px solid #cfd8ea;
            font-size: 8px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 8px;
        }
        th, td {
            padding: 2px 4px;
            border: 1px solid #dde2ea;
        }
        thead th {
            background: #e6ecf5;
            font-weight: 600;
        }
        tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .bill-total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #d6d8db;
            font-weight: bold;
        }
        .footer {
            border-top: 1px solid #dde2ea;
            font-size: 7px;
            color: #666;
            text-align: center;
            padding-top: 3px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
@if($purchaseOrders->isEmpty())
    <p>No purchase details found for the selected filters.</p>
@else
    @php $grandTotalAmount = 0; @endphp
    <div class="page">
        <div class="page-header">
            <div class="page-header-top">
                <div class="page-header-col page-header-title">
                    <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
                    <h2>Stock Purchase Details Report</h2>
                    <div class="page-header-sub">Lal Bahadur Shastri National Academy of Administration</div>
                </div>
            </div>
            <div class="meta-row">
                @php
                    $vendorName = $selectedVendors->isEmpty()
                        ? 'All Vendors'
                        : $selectedVendors->pluck('name')->implode(', ');
                    $storeName = $selectedStores->isEmpty()
                        ? 'All Stores'
                        : $selectedStores->pluck('store_name')->implode(', ');
                    $fromText = $fromLabel ?? 'Start';
                    $toText = $toLabel ?? 'End';
                @endphp
                <span>
                    <strong>Period:</strong>
                    Between {{ $fromText }} To {{ $toText }}
                </span>
                <span>
                    <strong>Vendor:</strong>
                    {{ $vendorName }}
                </span>
                <span>
                    <strong>Store:</strong>
                    {{ $storeName }}
                </span>
                <span>
                    <strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}
                </span>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Item Code</th>
                <th class="text-center">Unit</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amount</th>
                <th class="text-end">Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse($purchaseOrders as $order)
                @php
                    $storeName = $order->store ? $order->store->store_name : 'N/A';
                    $billLabel = $storeName . ' (Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . ($order->po_date ? $order->po_date->format('d-m-Y') : 'N/A') . ')';
                    $billSubtotal = 0;
                    $billTaxTotal = 0;
                @endphp
                <tr>
                    <td colspan="8" class="bill-header">{{ $billLabel }}</td>
                </tr>
                @foreach($order->items as $item)
                    @php
                        $qty = $item->quantity ?? 0;
                        $rate = $item->unit_price ?? 0;
                        $taxPercent = $item->tax_percent ?? 0;
                        $subtotal = $qty * $rate;
                        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
                        $total = $subtotal + $taxAmount;
                        $billSubtotal += $subtotal;
                        $billTaxTotal += $taxAmount;
                        $grandTotalAmount += $total;
                        $itemName = $item->itemSubcategory->item_name
                            ?? $item->itemSubcategory->subcategory_name
                            ?? $item->itemSubcategory->name
                            ?? 'N/A';
                        $itemCode = $item->itemSubcategory->item_code ?? '—';
                        $unit = $item->unit ?? '—';
                    @endphp
                    <tr>
                        <td>{{ $itemName }}</td>
                        <td>{{ $itemCode }}</td>
                        <td class="text-center">{{ $unit }}</td>
                        <td class="text-end">{{ number_format($qty, 2) }}</td>
                        <td class="text-end">{{ number_format($rate, 2) }}</td>
                        <td class="text-end">{{ number_format($taxPercent, 2) }}%</td>
                        <td class="text-end">{{ number_format($taxAmount, 2) }}</td>
                        <td class="text-end">{{ number_format($total, 2) }}</td>
                    </tr>
                @endforeach
                @php $billTotal = $billSubtotal + $billTaxTotal; @endphp
                <tr class="bill-total-row">
                    <td colspan="7" class="text-end"><strong>Bill Total:</strong></td>
                    <td class="text-end"><strong>{{ number_format($billTotal, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No purchase details found</td>
                </tr>
            @endforelse

            @if($grandTotalAmount > 0)
                <tr class="grand-total-row">
                    <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
                    <td class="text-end"><strong>{{ number_format($grandTotalAmount, 2) }}</strong></td>
                </tr>
            @endif
            </tbody>
        </table>

        <div class="footer">
            <small>Officer's Mess LBSNAA Mussoorie &mdash; Stock Purchase Details Report</small>
        </div>
    </div>
@endif
</body>
</html>

