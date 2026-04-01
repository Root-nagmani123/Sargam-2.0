@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
@php
    $selectedVendorIds = $selectedVendors->pluck('id')->map(fn ($id) => (int) $id)->all();
    $selectedStoreIds = $selectedStores->pluck('id')->map(fn ($id) => (int) $id)->all();
    $stockPurchasePrintDateRange = 'Stock Purchase Details Report Between '
        . date('d-F-Y', strtotime($fromDate))
        . ' To '
        . date('d-F-Y', strtotime($toDate));
    $stockPurchasePrintVendorLine = $selectedVendors->isEmpty()
        ? 'All Vendors'
        : $selectedVendors->pluck('name')->implode(', ');
    $stockPurchasePrintVendorDetailRows = $selectedVendors->isEmpty()
        ? []
        : $selectedVendors->map(function ($v) {
            return [
                'name' => $v->name ?? '—',
                'contact_person' => $v->contact_person ?? '—',
                'phone' => $v->phone ?? '—',
                'email' => $v->email ?? '—',
                'address' => $v->address ?? '—',
            ];
        })->values()->all();
    $stockPurchasePrintStoreDetails = $selectedStores->isEmpty()
        ? 'All Stores'
        : $selectedStores->pluck('store_name')->implode(', ');
    $stockPurchasePrintConfigJson = json_encode([
        'dateRange' => $stockPurchasePrintDateRange,
        'vendorLine' => $stockPurchasePrintVendorLine,
        'vendorDetailRows' => $stockPurchasePrintVendorDetailRows,
        'storeDetails' => $stockPurchasePrintStoreDetails,
    ], JSON_THROW_ON_ERROR);
@endphp
<div class="container-fluid stock-purchase-report">
    <div id="stock-purchase-print-config" class="d-none" hidden
         data-config="{{ htmlspecialchars($stockPurchasePrintConfigJson, ENT_QUOTES, 'UTF-8') }}"></div>
    <x-breadcrum title="Stock Purchase Details Report"></x-breadcrum>
    <!-- Filters Section (Top - same pattern as other report pages) -->

    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold text-dark">Filter Purchases</h5>
                <span class="text-muted small">Refine results by date, vendor &amp; store</span>
            </div>
        </div>
        <div class="card-body pt-3 p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Select Vendor Name</label>
                        <select name="vendor_id[]" class="form-select form-select-sm rounded-1 choices-select" multiple data-placeholder="All Vendors">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected(in_array((int) $vendor->id, $selectedVendorIds, true))>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Select Store Name</label>
                        <select name="store_id[]" class="form-select form-select-sm rounded-1 choices-select" multiple data-placeholder="All Stores">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds, true))>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">filter_list</span>
                            <span>Apply Filters</span>
                        </button>
                        <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">refresh</span>
                            <span>Reset</span>
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" onclick="printStockPurchaseTable()" title="Print report or Save as PDF">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">print</span>
                            <span>Print</span>
                        </button>
                        <a href="{{ route('admin.mess.reports.stock-purchase-details.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" title="Download PDF">
                             <span class="material-symbols-rounded" style="font-size: 1rem;">picture_as_pdf</span>
                             <span>PDF</span>
                        </a>
                        <a href="{{ route('admin.mess.reports.stock-purchase-details.excel', request()->query()) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" title="Export to Excel">
                             <span class="material-symbols-rounded" style="font-size: 1rem;">table_view</span>
                             <span>Export Excel</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Area (full width below filters) -->
    <div class="report-area">
            <!-- Report content -->
            <div class="report-content card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-lg-4">
                    <!-- Report header (title centered, date bar, vendor) -->
                    <div class="report-header mb-4 border-bottom pb-3 text-center">
                        <h4 class="report-title-center fw-bold mb-2 text-dark text-center text-uppercase tracking-wide">Stock Purchase Details</h4>
                        <div class="report-date-bar py-2 px-3 mb-2 text-center rounded-1 d-inline-block text-white fw-semibold justify-content-center">
                            Stock Purchase Details Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}
                        </div>
                        <div class="report-vendor-name fw-semibold mb-0 mt-2 text-center">
                            <span class="text-muted">Vendor:</span>
                            <span class="ms-1">{{ $selectedVendors->isEmpty() ? 'All Vendors' : $selectedVendors->pluck('name')->implode(', ') }}</span>
                        </div>
                        <div class="report-store-name fw-semibold mb-0 mt-1 text-center small">
                            <span class="text-muted">Store:</span>
                            <span class="ms-1">{{ $selectedStores->isEmpty() ? 'All Stores' : $selectedStores->pluck('store_name')->implode(', ') }}</span>
                        </div>
                    </div>

                    <!-- Table: grouped by bill -->
                    <div class="table-responsive rounded-3 border bg-white stock-purchase-table-wrapper">
                        <table class="table text-nowrap align-middle mb-0 stock-purchase-table">
                            <thead class="stock-purchase-thead">
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
                                @php $grandTotalAmount = 0; @endphp
                                @forelse($purchaseOrders as $order)
                                    @php
                                        $storeName = $order->store ? $order->store->store_name : 'N/A';
                                        $billLabel = $storeName . '(Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
                                        $billSubtotal = 0;
                                        $billTaxTotal = 0;
                                    @endphp
                                    <tr class="bill-header-row">
                                        <td colspan="8" class="bill-header bg-dark text-white small fw-semibold">{{ $billLabel }}</td>
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
                                        @endphp
                                        <tr>
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
                                    @php $billTotal = $billSubtotal + $billTaxTotal; @endphp
                                    <tr class="bill-total-row bg-light fw-semibold">
                                        <td colspan="7" class="text-end fw-bold">Bill Total:</td>
                                        <td class="text-end fw-bold">₹{{ number_format($billTotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No purchase details found</td>
                                    </tr>
                                @endforelse
                                @if($grandTotalAmount > 0)
                                    <tr class="grand-total-row bg-primary fw-bold">
                                        <td colspan="7" class="text-end text-white">Grand Total:</td>
                                        <td class="text-end text-white">₹{{ number_format($grandTotalAmount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination below report (for print: show "Page X of Y" on each logical page via CSS if needed) -->
            @if($purchaseOrders->hasPages())
                <div class="mt-3 no-print d-flex justify-content-center">
                    {{ $purchaseOrders->links('vendor.pagination.custom') }}
                </div>
            @endif
    </div>

    {{-- Tom Select for vendor & store dropdowns (shared with other mess screens) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.TomSelect === 'undefined') return;

            document
                .querySelectorAll('.stock-purchase-report select.choices-select')
                .forEach(function (el) {
                    if (el.dataset.tomselectInitialized === 'true') return;

                    var placeholder = el.getAttribute('data-placeholder') || 'Select';

                    new TomSelect(el, {
                        placeholder: placeholder,
                        maxItems: null,
                        maxOptions: 500,
                        plugins: ['remove_button', 'dropdown_input'],
                        sortField: {
                            field: 'text',
                            direction: 'asc'
                        }
                    });

                    el.dataset.tomselectInitialized = 'true';
                });
        });
    </script>
<script>
function printStockPurchaseTable() {
    var tableEl = document.querySelector('.stock-purchase-report .report-content table');
    if (!tableEl) {
        window.print();
        return;
    }
    var table = tableEl.cloneNode(true);

    var printWindow = window.open('', '_blank');
    if (!printWindow) {
        window.print();
        return;
    }

    var title = 'Stock Purchase Details';
    var cfgEl = document.getElementById('stock-purchase-print-config');
    var printCfg = {};
    try {
        printCfg = cfgEl && cfgEl.getAttribute('data-config')
            ? JSON.parse(cfgEl.getAttribute('data-config'))
            : {};
    } catch (e) {
        printCfg = {};
    }
    var dateRange = printCfg.dateRange || '';
    var vendorLine = printCfg.vendorLine || '';
    var vendorDetailRows = Array.isArray(printCfg.vendorDetailRows) ? printCfg.vendorDetailRows : [];
    var storeDetails = printCfg.storeDetails || '';
    var emblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    var lbsnaaLogoSrc = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';

    var vendorDetailsHtml = vendorDetailRows.length === 0
        ? '<div class="meta-line"><strong>Vendor Details:</strong> All Vendors</div>'
        : (
            '<div class="meta-line"><strong>Vendor Details:</strong></div>' +
            '<table class="vendor-detail-table">' +
            '<thead><tr><th>Vendor</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th></tr></thead>' +
            '<tbody>' +
            vendorDetailRows.map(function (row) {
                return '<tr>' +
                    '<td>' + (row.name || '—') + '</td>' +
                    '<td>' + (row.contact_person || '—') + '</td>' +
                    '<td>' + (row.phone || '—') + '</td>' +
                    '<td>' + (row.email || '—') + '</td>' +
                    '<td>' + (row.address || '—') + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>'
        );

    /* Match PDF: same class on cloned table for styling */
    table.classList.add('stock-purchase-data');
    table.classList.remove('table', 'text-nowrap', 'align-middle', 'mb-0', 'stock-purchase-table');

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 12px 16px;
            color: #222;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .lbsnaa-header-wrap {
            border-bottom: 2px solid #004a93;
            margin-bottom: 12px;
            padding: 2px 0 8px;
        }
        .branding-table { width: 100%; border-collapse: collapse; margin: 0; }
        .branding-table td { border: 0; padding: 0; vertical-align: middle; }
        .branding-logo-left { width: 42px; }
        .branding-text { text-align: left; padding: 0 10px 0 2px; line-height: 1.25; }
        .branding-logo-right { width: 200px; text-align: right; }
        .lbsnaa-brand-line-1 {
            font-size: 9px;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 13px;
            color: #222;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .lbsnaa-brand-line-3 {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .header-img-left { width: 34px; height: 34px; }
        .header-img-right { width: 165px; height: auto; }
        .report-header-block {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title-center {
            font-size: 15px;
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
            font-size: 10px;
            display: inline-block;
        }
        .report-vendor-name, .report-store-name {
            font-size: 10px;
            font-weight: 600;
            margin-top: 8px;
            color: #212529;
        }
        .report-store-name { font-size: 9px; margin-top: 4px; }
        .text-muted { color: #6c757d; font-weight: 600; }
        .report-meta-print { font-size: 9px; margin: 10px 0 12px; line-height: 1.4; }
        .report-meta-print .meta-line { margin-bottom: 4px; word-wrap: break-word; }
        .vendor-detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 8.5px;
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
        table.stock-purchase-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
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
        table.stock-purchase-data thead th.text-end { text-align: right; }
        table.stock-purchase-data .text-center { text-align: center; }
        table.stock-purchase-data .text-end { text-align: right; }
        table.stock-purchase-data .bill-header-row td,
        table.stock-purchase-data td.bill-header {
            background: #5a6268;
            color: #fff;
            font-weight: 700;
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
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        @media print {
            body { margin: 0.5in; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="lbsnaa-header-wrap">
        <table class="branding-table">
            <tr>
                <td class="branding-logo-left">
                    <img src="${emblemSrc}" alt="Emblem of India" class="header-img-left">
                </td>
                <td class="branding-text">
                    <div class="lbsnaa-brand-line-1">Government of India</div>
                    <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                </td>
                <td class="branding-logo-right">
                    <img src="${lbsnaaLogoSrc}" alt="LBSNAA Logo" class="header-img-right">
                </td>
            </tr>
        </table>
    </div>

    <div class="report-header-block">
        <h1 class="report-title-center">${title}</h1>
        <div class="report-date-bar">${dateRange}</div>
        <div class="report-vendor-name">
            <span class="text-muted">Vendor:</span>
            <span>${vendorLine}</span>
        </div>
        <div class="report-store-name">
            <span class="text-muted">Store:</span>
            <span>${storeDetails}</span>
        </div>
    </div>

    <div class="report-meta-print">
        ${vendorDetailsHtml}
        <div class="meta-line"><strong>Store:</strong> ${storeDetails}</div>
        <div class="meta-line"><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
    </div>

    <div class="table-responsive">
        ${table.outerHTML}
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    <\/script>
</body>
</html>`);
    printWindow.document.close();
}
</script>

<style>
/* Auto height and width for report container and content */
.stock-purchase-report {
    width: 100%;
    max-width: 100%;
    min-height: 0;
    height: auto;
}
.stock-purchase-report .report-area {
    width: 100%;
    height: auto;
}
.stock-purchase-report .report-content {
    width: 100%;
    height: auto;
}
.stock-purchase-report .report-content .card-body {
    width: 100%;
    height: auto;
}
.stock-purchase-report .table-responsive {
    width: 100%;
    overflow-x: auto;
}
.stock-purchase-report .table-responsive table {
    width: 100%;
    height: auto;
}

.stock-purchase-report .stock-purchase-table { font-size: 0.9rem; }
.stock-purchase-report .bill-header-row .bill-header { background-color: #5a6268; color: #fff; font-weight: bold; padding: 0.5rem 0.75rem; }
.stock-purchase-report .bill-total-row { background-color: #fff; }
.stock-purchase-report .bill-total-row td { padding: 0.35rem 0.75rem; border-top: 1px solid #dee2e6; }
.stock-purchase-report .grand-total-row td { padding: 0.45rem 0.75rem; border-top: 2px solid #343a40; }
.stock-purchase-report .stock-purchase-table td { padding: 0.35rem 0.75rem; vertical-align: middle; }
.stock-purchase-report .page-input { display: inline-block; }
.report-date-bar { background: #004a93; color: #fff; font-size: 0.9rem; text-align: center; }
.report-vendor-name { font-size: 1rem; }
.stock-purchase-thead th { background: #d3d6d9; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; }
.stock-purchase-thead th.text-end { text-align: right; }

@media print {
    /* Hide entire app chrome – only report content prints */
    .topbar,
    #main-wrapper > .topbar,
    .page-wrapper > #sidebarTabContent,
    .page-wrapper > div:first-child:not(.body-wrapper),
    .sidebarmenu,
    .sargam-loader { display: none !important; }
    .no-print { display: none !important; }
    .stock-purchase-report .report-toolbar { display: none !important; }
    .stock-purchase-report .report-area > .mt-3 { display: none !important; }
    /* Full width, no sidebar offset */
    body, html { margin: 0 !important; padding: 0 !important; }
    #main-wrapper { padding: 0 !important; }
    .page-wrapper { padding: 0 !important; }
    .body-wrapper { margin: 0 !important; margin-left: 0 !important; width: 100% !important; max-width: 100% !important; }
    main#main-content { margin: 0 !important; padding: 0 !important; }
    .tab-content { padding: 0 !important; }
    .tab-pane { display: block !important; }
    /* Report only – no extra padding */
    .stock-purchase-report { padding: 0 !important; margin: 0 !important; }
    .report-content { box-shadow: none !important; border: none !important; }
    .report-content .card-body { padding: 0 !important; }
    .report-header { margin-top: 0 !important; margin-bottom: 1rem !important; }
    .report-title-center { font-size: 1.15rem !important; color: #000 !important; text-align: center !important; }
    .report-date-bar { background: #5a6268 !important; color: #fff !important; text-align: center !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-vendor-name { font-size: 1rem !important; color: #000 !important; margin-bottom: 0.75rem !important; }
    body { font-size: 12px; }
    .stock-purchase-table { font-size: 11px; border-collapse: collapse !important; }
    .stock-purchase-table td, .stock-purchase-table th { border: 1px solid #333 !important; }
    .stock-purchase-thead th { background: #d3d6d9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .bill-header-row .bill-header { background: #5a6268 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endsection
