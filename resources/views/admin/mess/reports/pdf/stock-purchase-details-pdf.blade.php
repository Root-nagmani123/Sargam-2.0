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
            margin: 0.5in;
        }
        html { font-size: 11pt; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #212529;
            background: #f8f9fa;
        }

        /* Bootstrap 5–style primitives (Dompdf-safe; mirrors print popup) */
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .text-secondary { color: #6c757d; }
        .text-dark { color: #212529; }
        .text-body-secondary { color: #6c757d; }
        .fw-semibold { font-weight: 600; }
        .fw-bold { font-weight: 700; }
        .small { font-size: 0.875rem; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 1rem; }
        .p-0 { padding: 0; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .py-3 { padding-top: 1rem; padding-bottom: 1rem; }
        .px-3 { padding-left: 1rem; padding-right: 1rem; }
        .pb-3 { padding-bottom: 1rem; }
        .border { border: 1px solid #dee2e6; }
        .border-bottom { border-bottom: 1px solid #dee2e6; }
        .border-0 { border: none; }
        .rounded { border-radius: 0.25rem; }
        .rounded-pill { border-radius: 50rem; }
        .bg-white { background: #fff; }
        .bg-light { background: #f8f9fa !important; }
        .bg-body-secondary { background: #e9ecef; }
        .text-white { color: #fff !important; }
        .opacity-25 { opacity: 0.25; }
        hr { border: 0; border-top: 1px solid #dee2e6; margin: 1rem 0; }

        .mess-title-tracking { letter-spacing: 0.04em; }
        .mess-print-head {
            border-bottom: 3px solid #003366;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
        }
        .mess-date-pill {
            background-color: #003366;
            color: #fff;
            font-weight: 600;
            font-size: 0.8125rem;
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 50rem;
        }
        .mess-brand-line-1 {
            color: #1d70b8;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
            font-size: 8.5pt;
        }
        .mess-brand-line-2 {
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
            font-size: 10pt;
            letter-spacing: 0.02em;
        }
        .mess-brand-line-3 {
            color: #505a5f;
            margin-top: 4px;
            font-size: 9pt;
        }
        .mess-hindi { color: #7b2d26; font-weight: 600; font-size: 8.5pt; }
        .mess-en-side { color: #7b2d26; margin-top: 4px; font-size: 8pt; }

        .branding-table { width: 100%; border-collapse: collapse; margin: 0; }
        .branding-table td { border: 0; padding: 0; vertical-align: middle; }
        .branding-left-cell { width: 62%; }
        .branding-left-cell .header-img-left { float: left; margin: 2px 12px 6px 0; }
        .branding-left-cell .branding-text-block { overflow: hidden; line-height: 1.28; padding-top: 1px; }
        .branding-left-clear { clear: both; height: 0; line-height: 0; font-size: 0; }
        .branding-right-cell { width: 38%; text-align: right; }
        .branding-right-cluster { display: inline-block; text-align: left; max-width: 100%; }
        .branding-right-cluster .header-img-right-seal { float: left; margin: 0 10px 0 0; }
        .branding-right-cluster .branding-bilingual { overflow: hidden; max-width: 175px; line-height: 1.22; text-align: right; }
        .branding-right-clear { clear: both; height: 0; line-height: 0; font-size: 0; }
        .header-img-left { width: 46px; height: 46px; object-fit: contain; display: block; }
        .header-img-right-seal { width: 48px; height: 48px; object-fit: contain; display: block; }

        .container-fluid { width: 100%; padding: 0 4px; }
        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
        }
        .card.border-0 {
            border: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        .card.shadow-sm { box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06); }
        .card-header {
            padding: 0.45rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            background: #e9ecef;
            border-bottom: 1px solid #dee2e6;
        }
        .card-body { padding: 0.75rem 1rem; }
        .h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0 0 0.75rem;
            text-transform: uppercase;
        }

        .table-responsive { width: 100%; overflow: visible; }
        .table {
            width: 100%;
            border-collapse: collapse;
            vertical-align: middle;
            margin-bottom: 0;
        }
        .table-sm th,
        .table-sm td {
            padding: 0.3rem 0.45rem;
            font-size: 11pt;
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
        }
        .table-light th {
            background: #e9ecef !important;
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
            background: #f8f9fa !important;
            font-weight: 700;
        }
        table.stock-purchase-data .grand-total-row td {
            background: #004a93 !important;
            color: #fff !important;
            border-color: #004a93 !important;
            font-weight: 700;
        }
        table.stock-purchase-data td.py-4,
        table.stock-purchase-data td.py-5 {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
    </style>
</head>
<body>
    @php $grandTotalAmount = 0; @endphp
    <div class="container-fluid">
        <header class="mess-print-head bg-white px-2 py-2 rounded">
            <table class="branding-table">
                <tr>
                    <td class="branding-left-cell">
                        <img src="{{ $emblemSrc }}" alt="Emblem of India" class="header-img-left rounded">
                        <div class="branding-text-block">
                            <div class="mess-brand-line-1">Government of India</div>
                            <div class="mess-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                            <div class="mess-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                        </div>
                        <div class="branding-left-clear"></div>
                    </td>
                    <td class="branding-right-cell">
                        <div class="branding-right-cluster">
                            <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA" class="header-img-right-seal rounded">
                            <div class="branding-right-clear"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </header>

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
