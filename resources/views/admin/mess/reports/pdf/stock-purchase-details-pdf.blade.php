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
            margin: 12mm 10mm 12mm 10mm;
        }
        * { box-sizing: border-box; }
        html { font-size: 9pt; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #212529;
            background: #fff;
            line-height: 1.4;
        }

        /* ── Header ── */
        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 40px; height: 40px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 40px; height: 40px; }
        .brand-1 { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }
        .brand-2 { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }
        .brand-3 { font-size: 7.5pt; color: #555; margin-top: 2px; }

        /* ── Report title block ── */
        .report-title-block {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 5px;
        }
        .report-date-pill {
            display: inline-block;
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 8pt;
            padding: 3px 12px;
            border-radius: 10px;
        }

        /* ── Meta section ── */
        .report-meta {
            font-size: 8pt;
            margin-bottom: 8px;
            line-height: 1.5;
            color: #334155;
        }
        .report-meta .meta-label { font-weight: 700; color: #0f172a; }

        /* ── Vendor detail table ── */
        .vendor-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin: 4px 0 8px;
        }
        .vendor-detail-table th,
        .vendor-detail-table td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: top;
        }
        .vendor-detail-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #334155;
            text-align: left;
        }

        /* ── Main data table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .data-table th,
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
        }

        /* Column widths */
        .data-table .col-item { width: 22%; text-align: left; }
        .data-table .col-code { width: 12%; text-align: left; }
        .data-table .col-unit { width: 7%; text-align: right; }
        .data-table .col-qty  { width: 9%; text-align: right; }
        .data-table .col-rate { width: 10%; text-align: right; }
        .data-table .col-tax  { width: 8%; text-align: right; }
        .data-table .col-tamt { width: 12%; text-align: right; }
        .data-table .col-total { width: 14%; text-align: right; }

        /* Header row */
        .data-table thead th {
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 8pt;
            text-align: left;
            white-space: nowrap;
        }
        .data-table thead th.text-end { text-align: right; }

        /* Vendor section header */
        .data-table .vendor-hdr td {
            background: #eef2f6;
            color: #0f172a;
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-color: #cbd5e1;
            padding: 5px 6px;
        }

        /* Bill header */
        .data-table .bill-hdr td {
            background: #475569;
            color: #fff;
            font-weight: 600;
            font-size: 7.5pt;
            border-color: #475569;
            padding: 4px 6px;
        }

        /* Item rows */
        .data-table .item-row td {
            font-size: 8pt;
            background: #fff;
        }
        .data-table .item-row-alt td {
            background: #f9fafb;
        }

        /* Bill total */
        .data-table .bill-total td {
            background: #f8fafc;
            font-weight: 600;
            font-size: 8pt;
            border-top: 1px dashed #cbd5e1;
            color: #334155;
        }

        /* Vendor total */
        .data-table .vendor-total td {
            background: #e8edf4;
            font-weight: 700;
            font-size: 8pt;
            border-top: 2px solid #0b4a7e;
            color: #0b4a7e;
        }

        /* Grand total */
        .data-table .grand-total td {
            background: #0b4a7e;
            color: #fff;
            font-weight: 700;
            font-size: 8.5pt;
            border-color: #0b4a7e;
            padding: 5px 6px;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    @php $grandTotalAmount = 0; $globalRowIndex = 0; @endphp

    {{-- Header --}}
    <div class="pdf-header">
        <table>
            <tr>
                <td class="hdr-left">
                    <img src="{{ $emblemSrc }}" alt="Emblem of India">
                </td>
                <td class="hdr-center">
                    <div class="brand-1">Government of India</div>
                    <div class="brand-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
                </td>
                <td class="hdr-right">
                    <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">
                </td>
            </tr>
        </table>
    </div>

    {{-- Title --}}
    <div class="report-title-block">
        <h1 class="report-title">Stock Purchase Details</h1>
        <div class="report-date-pill">{{ $dateRange }}</div>
    </div>

    {{-- Meta --}}
    <div class="report-meta">
        <span class="meta-label">{{ $vendorHeaderLabel }}</span> {{ $vendorLine }}<br>
        @if($vendorDetailRows->isNotEmpty())
            <table class="vendor-detail-table">
                <thead>
                    <tr>
                        <th>Name</th>
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
        <span class="meta-label">Store:</span> {{ $storeDetails }}<br>
        <span class="meta-label">Printed on:</span> {{ now()->format('d-m-Y H:i') }}
    </div>

    {{-- Data table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th class="col-code">Item Code</th>
                <th class="col-unit text-end">Unit</th>
                <th class="col-qty text-end">Quantity</th>
                <th class="col-rate text-end">Rate</th>
                <th class="col-tax text-end">Tax %</th>
                <th class="col-tamt text-end">Tax Amt</th>
                <th class="col-total text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrdersByVendor as $vendorGroup)
                <tr class="vendor-hdr">
                    <td colspan="8">VENDOR : {{ $vendorGroup['vendor_name'] }}</td>
                </tr>
                @php $vendorSectionTotal = 0; @endphp
                @foreach($vendorGroup['orders'] as $order)
                    @php
                        $billSubtotal = 0;
                        $billTaxTotal = 0;
                    @endphp
                    <tr class="bill-hdr">
                        <td colspan="8">{{ $order->stockPurchaseReportBillLabel() }}</td>
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
                            $globalRowIndex++;
                        @endphp
                        <tr class="{{ $globalRowIndex % 2 === 0 ? 'item-row-alt' : 'item-row' }}">
                            <td>{{ $item->itemSubcategory->item_name ?? $item->itemSubcategory->subcategory_name ?? $item->itemSubcategory->name ?? 'N/A' }}</td>
                            <td>{{ $item->itemSubcategory->item_code ?? '—' }}</td>
                            <td class="text-end">{{ $item->unit ?? '—' }}</td>
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
                    <tr class="bill-total">
                        <td colspan="7" class="text-end">Bill Total:</td>
                        <td class="text-end">₹{{ number_format($billTotal, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="vendor-total">
                    <td colspan="7" class="text-end">Vendor Total ({{ $vendorGroup['vendor_name'] }}):</td>
                    <td class="text-end">₹{{ number_format($vendorSectionTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:20px 6px;">No purchase details found</td>
                </tr>
            @endforelse

            @if($grandTotalAmount > 0)
                <tr class="grand-total">
                    <td colspan="7" class="text-end">Grand Total:</td>
                    <td class="text-end">₹{{ number_format($grandTotalAmount, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
