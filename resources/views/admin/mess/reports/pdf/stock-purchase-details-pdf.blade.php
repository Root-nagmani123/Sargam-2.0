@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
    $dateRange = 'Stock Purchase Details Report Between ' . ($fromLabel ?? 'Start') . ' To ' . ($toLabel ?? 'End');
    $vendorLine = $selectedVendors->isEmpty()
        ? 'All Vendors'
        : $selectedVendors->pluck('name')->implode(', ');
    $storeDetails = $selectedStores->isEmpty()
        ? 'All Stores'
        : $selectedStores->pluck('store_name')->implode(', ');
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Purchase Details Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #222;
            background: #fff;
        }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }

        /* Branding header — same layout as category-wise PDF / print (PNG emblem for DOMPDF) */
        .lbsnaa-header-wrap {
            border-bottom: 2px solid #004a93;
            margin-bottom: 12px;
            padding: 2px 0 8px;
        }
        .branding-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .branding-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .branding-logo-left {
            width: 42px;
        }
        .branding-text {
            text-align: left;
            padding: 0 10px 0 2px;
            line-height: 1.25;
        }
        .branding-logo-right {
            width: 200px;
            text-align: right;
        }
        .lbsnaa-brand-line-1 {
            font-size: 8pt;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 13pt;
            color: #222;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .lbsnaa-brand-line-3 {
            font-size: 10pt;
            color: #555;
            margin-top: 2px;
        }
        .header-img-left {
            width: 34px;
            height: 34px;
        }
        .header-img-right {
            width: 165px;
            height: auto;
        }

        /* Report title block — matches on-screen report card */
        .report-header-block {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title-center {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 8px;
            color: #212529;
        }
        .report-date-bar {
            background: #004a93;
            color: #fff;
            padding: 8px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 10pt;
            display: inline-block;
        }
        .report-vendor-name,
        .report-store-name {
            font-size: 10pt;
            font-weight: 600;
            margin-top: 8px;
            color: #212529;
        }
        .report-store-name {
            font-size: 9pt;
            margin-top: 4px;
        }
        .text-muted {
            color: #6c757d;
            font-weight: 600;
        }

        .report-meta-print {
            font-size: 9pt;
            margin: 10px 0 12px;
            line-height: 1.4;
        }
        .report-meta-print .meta-line {
            margin-bottom: 4px;
            word-wrap: break-word;
        }

        /* Data table only — scoped so DOMPDF doesn’t mix rules with header */
        table.stock-purchase-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        table.stock-purchase-data th,
        table.stock-purchase-data td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        table.stock-purchase-data thead th {
            background: #d3d6d9;
            font-weight: 600;
            text-align: left;
        }
        table.stock-purchase-data thead th.text-end {
            text-align: right;
        }
        table.stock-purchase-data .text-center {
            text-align: center;
        }
        table.stock-purchase-data .text-end {
            text-align: right;
        }
        table.stock-purchase-data .bill-header-row td,
        table.stock-purchase-data td.bill-header {
            background: #5a6268;
            color: #fff;
            font-weight: 700;
            font-size: 8.5pt;
            border-color: #5a6268;
        }
        table.stock-purchase-data .bill-total-row td {
            font-weight: 700;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        table.stock-purchase-data .grand-total-row td {
            background: #004a93;
            color: #fff;
            font-weight: 700;
            border-color: #004a93;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #666;
            text-align: center;
            padding-top: 6px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
@if($purchaseOrders->isEmpty())
    <p>No purchase details found for the selected filters.</p>
@else
    @php $grandTotalAmount = 0; @endphp
    <div class="page">
        <div class="lbsnaa-header-wrap">
            <table class="branding-table">
                <tr>
                    <td class="branding-logo-left">
                        <img src="{{ $emblemSrc }}" alt="Emblem of India" class="header-img-left">
                    </td>
                    <td class="branding-text">
                        <div class="lbsnaa-brand-line-1">Government of India</div>
                        <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                        <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                    </td>
                    <td class="branding-logo-right">
                        <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo" class="header-img-right">
                    </td>
                </tr>
            </table>
        </div>

        <div class="report-header-block">
            <h1 class="report-title-center">Stock Purchase Details</h1>
            <div class="report-date-bar">{{ $dateRange }}</div>
            <div class="report-vendor-name">
                <span class="text-muted">Vendor:</span>
                <span>{{ $vendorLine }}</span>
            </div>
            <div class="report-store-name">
                <span class="text-muted">Store:</span>
                <span>{{ $storeDetails }}</span>
            </div>
        </div>

        <div class="report-meta-print">
            @if($selectedVendors->isEmpty())
                <div class="meta-line"><strong>Vendor Details:</strong> All Vendors</div>
            @else
                @foreach($selectedVendors as $i => $v)
                    @php
                        $line = trim(implode(' | ', array_filter([
                            'Name: ' . $v->name,
                            !empty($v->contact_person) ? 'Contact: ' . $v->contact_person : null,
                            !empty($v->phone) ? 'Phone: ' . $v->phone : null,
                            !empty($v->email) ? 'Email: ' . $v->email : null,
                            !empty($v->address) ? 'Address: ' . $v->address : null,
                        ])));
                    @endphp
                    <div class="meta-line"><strong>Vendor {{ $i + 1 }}:</strong> {{ $line }}</div>
                @endforeach
            @endif
            <div class="meta-line"><strong>Store:</strong> {{ $storeDetails }}</div>
            <div class="meta-line"><strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}</div>
        </div>

        <table class="stock-purchase-data">
            <thead>
            <tr>
                <th>Item</th>
                <th>Item Code</th>
                <th class="text-end">Unit</th>
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
                    $billLabel = $storeName . '(Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . ($order->po_date ? $order->po_date->format('d-m-Y') : 'N/A') . ')';
                    $billSubtotal = 0;
                    $billTaxTotal = 0;
                @endphp
                <tr class="bill-header-row">
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
                        <td class="text-end">{{ $unit }}</td>
                        <td class="text-end">{{ number_format($qty, 2) }}</td>
                        <td class="text-end">₹{{ number_format($rate, 1) }}</td>
                        <td class="text-end">{{ number_format($taxPercent, 2) }}%</td>
                        <td class="text-end">₹{{ number_format($taxAmount, 2) }}</td>
                        <td class="text-end">₹{{ number_format($total, 2) }}</td>
                    </tr>
                @endforeach
                @php $billTotal = $billSubtotal + $billTaxTotal; @endphp
                <tr class="bill-total-row">
                    <td colspan="7" class="text-end"><strong>Bill Total:</strong></td>
                    <td class="text-end"><strong>₹{{ number_format($billTotal, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No purchase details found</td>
                </tr>
            @endforelse

            @if($grandTotalAmount > 0)
                <tr class="grand-total-row">
                    <td colspan="7" class="text-end">Grand Total:</td>
                    <td class="text-end"><strong>₹{{ number_format($grandTotalAmount, 2) }}</strong></td>
                </tr>
            @endif
            </tbody>
        </table>

        <div class="footer">
            <small>Officer's Mess LBSNAA Mussoorie — Stock Purchase Details Report</small>
        </div>
    </div>
@endif
</body>
</html>
