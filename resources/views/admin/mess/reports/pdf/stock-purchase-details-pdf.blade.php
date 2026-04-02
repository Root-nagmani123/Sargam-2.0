@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
    $dateRange = 'Stock Purchase Details Report Between ' . ($fromLabel ?? 'Start') . ' To ' . ($toLabel ?? 'End');
    $vendorLine = $selectedVendors->isEmpty()
        ? 'All Vendors'
        : $selectedVendors->pluck('name')->implode(', ');
    $vendorHeaderLabel = $selectedVendors->isEmpty() || $selectedVendors->count() === 1
        ? 'Vendor:'
        : 'Filtered vendors:';
    $vendorDetailRows = $selectedVendors->isEmpty()
        ? collect()
        : $selectedVendors->map(function ($v) {
            return [
                'name' => $v->name ?? '—',
                'contact_person' => $v->contact_person ?? '—',
                'phone' => $v->phone ?? '—',
                'email' => $v->email ?? '—',
                'address' => $v->address ?? '—',
            ];
        });
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
    <title>Stock Purchase Details - OFFICER'S MESS LBSNAA MUSSOORIE</title>
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
        /* Match print popup: repeat header rows on each PDF page */
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }

        /*
         * Letterhead: left block (emblem + titles) | right block (seal + Hindi/English) hugging the right margin.
         * Float + clearfix — avoids nested tables in cells (Dompdf cellmap issues).
         */
        .lbsnaa-header-wrap {
            border-bottom: 3px solid #003366;
            margin-bottom: 14px;
            padding: 6px 0 12px;
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
        .branding-left-cell {
            width: 62%;
            vertical-align: middle;
        }
        .branding-left-cell .header-img-left {
            float: left;
            margin: 2px 12px 6px 0;
        }
        .branding-left-cell .branding-text-block {
            overflow: hidden;
            line-height: 1.28;
            padding-top: 1px;
        }
        .branding-left-clear {
            clear: both;
            height: 0;
            line-height: 0;
            font-size: 0;
        }
        .branding-right-cell {
            width: 38%;
            vertical-align: middle;
            text-align: right;
        }
        .branding-right-cluster {
            display: inline-block;
            text-align: left;
            vertical-align: middle;
            max-width: 100%;
        }
        .branding-right-cluster .header-img-right-seal {
            float: left;
            margin: 0 10px 0 0;
        }
        .branding-right-cluster .branding-bilingual {
            overflow: hidden;
            max-width: 175px;
            line-height: 1.22;
        }
        .branding-right-clear {
            clear: both;
            height: 0;
            line-height: 0;
            font-size: 0;
        }
        .lbsnaa-brand-line-1 {
            font-size: 8.5pt;
            color: #1d70b8;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 13pt;
            color: #000000;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
            letter-spacing: 0.02em;
        }
        .lbsnaa-brand-line-3 {
            font-size: 9pt;
            color: #505a5f;
            margin-top: 4px;
            font-weight: normal;
        }
        .header-img-left {
            width: 46px;
            height: 46px;
            object-fit: contain;
            display: block;
        }
        .header-img-right-seal {
            width: 48px;
            height: 48px;
            object-fit: contain;
            display: block;
        }
        .branding-hindi {
            font-size: 8.5pt;
            color: #7b2d26;
            font-weight: 600;
        }
        .branding-en-side {
            font-size: 7.5pt;
            color: #7b2d26;
            margin-top: 4px;
            font-weight: normal;
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
            background: #003366;
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
        .vendor-detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 8.5pt;
        }
        .vendor-detail-table th,
        .vendor-detail-table td {
            border: 1px solid #dee2e6;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }
        .vendor-detail-table th {
            background: #f1f3f5;
            font-weight: 600;
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
            white-space: normal;
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
        table.stock-purchase-data .vendor-section-header-row td,
        table.stock-purchase-data td.vendor-section-header {
            background: #e9ecef;
            color: #212529;
            font-weight: 700;
            font-size: 8.5pt;
            border-color: #adb5bd;
        }
        table.stock-purchase-data .bill-header-row td,
        table.stock-purchase-data td.bill-header {
            background: #5a6268;
            color: #fff;
            font-weight: 700;
            font-size: 8.5pt;
            border-color: #5a6268;
        }
        table.stock-purchase-data .vendor-total-row td {
            font-weight: 700;
            background: #dee2e6;
            border-top: 1px solid #adb5bd;
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

        .table-responsive {
            width: 100%;
        }
    </style>
</head>
<body>
    @php $grandTotalAmount = 0; @endphp
    <div class="page">
        <div class="lbsnaa-header-wrap">
            <table class="branding-table">
                <tr>
                    <td class="branding-left-cell">
                        <img src="{{ $emblemSrc }}" alt="Emblem of India" class="header-img-left">
                        <div class="branding-text-block">
                            <div class="lbsnaa-brand-line-1">Government of India</div>
                            <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                            <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                        </div>
                        <div class="branding-left-clear"></div>
                    </td>
                    <td class="branding-right-cell">
                        <div class="branding-right-cluster">
                            <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA" class="header-img-right-seal">
                            <div class="branding-bilingual">
                                <div class="branding-hindi" lang="hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</div>
                                <div class="branding-en-side" lang="en">Lal Bahadur Shastri National Academy of Administration</div>
                            </div>
                            <div class="branding-right-clear"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="report-header-block">
            <h1 class="report-title-center">Stock Purchase Details</h1>
            <div class="report-date-bar">{{ $dateRange }}</div>
            <div class="report-vendor-name">
                <span class="text-muted">{{ $vendorHeaderLabel }}</span>
                <span>{{ $vendorLine }}</span>
            </div>
            <div class="report-store-name">
                <span class="text-muted">Store:</span>
                <span>{{ $storeDetails }}</span>
            </div>
        </div>

        <div class="report-meta-print">
            @if($vendorDetailRows->isEmpty())
                <div class="meta-line"><strong>Vendor Details:</strong> All Vendors</div>
            @else
                <div class="meta-line"><strong>Vendor Details:</strong></div>
                <table class="vendor-detail-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Contact</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendorDetailRows as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['contact_person'] }}</td>
                                <td>{{ $row['phone'] }}</td>
                                <td>{{ $row['email'] }}</td>
                                <td>{{ $row['address'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="meta-line"><strong>Store:</strong> {{ $storeDetails }}</div>
            <div class="meta-line"><strong>Printed on:</strong> {{ now()->format('d-m-Y H:i') }}</div>
        </div>

        <div class="table-responsive">
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
            @forelse($purchaseOrdersByVendor as $vendorGroup)
                <tr class="vendor-section-header-row">
                    <td colspan="8" class="vendor-section-header">VENDOR : {{ $vendorGroup['vendor_name'] }}</td>
                </tr>
                @php $vendorSectionTotal = 0; @endphp
                @foreach($vendorGroup['orders'] as $order)
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
                    @php
                        $billTotal = $billSubtotal + $billTaxTotal;
                        $vendorSectionTotal += $billTotal;
                    @endphp
                    <tr class="bill-total-row">
                        <td colspan="7" class="text-end">Bill Total:</td>
                        <td class="text-end">₹{{ number_format($billTotal, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="vendor-total-row">
                    <td colspan="7" class="text-end">Vendor Total ({{ $vendorGroup['vendor_name'] }}):</td>
                    <td class="text-end">₹{{ number_format($vendorSectionTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No purchase details found</td>
                </tr>
            @endforelse

            @if($grandTotalAmount > 0)
                <tr class="grand-total-row">
                    <td colspan="7" class="text-end">Grand Total:</td>
                    <td class="text-end">₹{{ number_format($grandTotalAmount, 2) }}</td>
                </tr>
            @endif
            </tbody>
        </table>
        </div>
    </div>
</body>
</html>
