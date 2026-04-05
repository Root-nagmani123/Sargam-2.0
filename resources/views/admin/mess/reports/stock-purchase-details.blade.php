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
    $stockPurchasePrintVendorHeaderLabel = $selectedVendors->isEmpty() || $selectedVendors->count() === 1
        ? 'Vendor:'
        : 'Filtered vendors:';
    $stockPurchasePrintConfigJson = json_encode([
        'dateRange' => $stockPurchasePrintDateRange,
        'vendorLine' => $stockPurchasePrintVendorLine,
        'vendorHeaderLabel' => $stockPurchasePrintVendorHeaderLabel,
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
                    <div class="report-header mb-4 pb-3 text-center border-bottom">
                        <h4 class="report-title-center h5 fw-bold mb-3 text-dark text-uppercase mess-title-tracking">Stock Purchase Details</h4>
                        <div class="report-vendor-name fw-semibold mb-0 mt-2 text-center">
                            <span class="text-muted">{{ $stockPurchasePrintVendorHeaderLabel }} {{ $stockPurchasePrintVendorLine }}</span>
                        </div>
                        <div class="report-store-name fw-semibold mb-0 mt-2 text-center">
                            <span class="text-muted">Store: {{ $stockPurchasePrintStoreDetails }}</span>
                        </div>
                        <div class="d-inline-block px-3 py-2 mb-2 fw-semibold small h4">
                            Stock Purchase Details Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}
                        </div>
                    </div>

                    <!-- Table: grouped by bill -->
                    <div class="table-responsive rounded-3 border shadow-sm bg-white stock-purchase-table-wrapper">
                        <table class="table table-sm table-bordered align-middle mb-0 stock-purchase-table">
                            <thead class="stock-purchase-thead table-light">
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
                                @forelse($purchaseOrdersByVendor as $vendorGroup)
                                    <tr class="vendor-section-header-row">
                                        <td colspan="8" class="vendor-section-header small fw-semibold">
                                            VENDOR : {{ $vendorGroup['vendor_name'] }}
                                        </td>
                                    </tr>
                                    @php $vendorSectionTotal = 0; @endphp
                                    @foreach($vendorGroup['orders'] as $order)
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
                                        @php
                                            $billTotal = $billSubtotal + $billTaxTotal;
                                            $vendorSectionTotal += $billTotal;
                                        @endphp
                                        <tr class="bill-total-row bg-light fw-semibold">
                                            <td colspan="7" class="text-end fw-bold">Bill Total:</td>
                                            <td class="text-end fw-bold">₹{{ number_format($billTotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="vendor-total-row fw-semibold">
                                        <td colspan="7" class="text-end">Vendor Total ({{ $vendorGroup['vendor_name'] }}):</td>
                                        <td class="text-end">₹{{ number_format($vendorSectionTotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-body-secondary py-5">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <span class="text-muted" aria-hidden="true">—</span>
                                                No purchase details found
                                            </span>
                                        </td>
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
    var vendorHeaderLabel = printCfg.vendorHeaderLabel || 'Vendor:';
    var vendorDetailRows = Array.isArray(printCfg.vendorDetailRows) ? printCfg.vendorDetailRows : [];
    var storeDetails = printCfg.storeDetails || '';
    var emblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    var lbsnaaLogoSrc = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';

    var vendorDetailsHtml = vendorDetailRows.length === 0
        ? '<p class="small mb-0"><span class="text-secondary fw-semibold">Vendor details:</span> All Vendors</p>'
        : (
            '<p class="small fw-semibold text-secondary mb-2">Vendor details</p>' +
            '<div class="table-responsive">' +
            '<table class="table table-sm table-bordered align-middle mb-0">' +
            '<thead class="table-light"><tr><th>Vendor</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th></tr></thead>' +
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
            '</tbody></table></div>'
        );

    /* Bootstrap table utilities + data scope for bill / totals */
    table.classList.add('stock-purchase-data', 'table', 'table-sm', 'table-bordered', 'align-middle', 'mb-0');
    table.classList.remove('text-nowrap', 'stock-purchase-table');
    var thead = table.querySelector('thead');
    if (thead) {
        thead.classList.add('table-light');
    }

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        html { font-size: 11pt; }
        body {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .mess-title-tracking { letter-spacing: 0.04em; }
        .mess-print-head {
            border-bottom: 3px solid #003366 !important;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
        }
        .mess-date-pill {
            background-color: #003366 !important;
            color: #fff !important;
            font-weight: 600;
            font-size: 0.8125rem;
        }
        .mess-brand-line-1 {
            color: #1d70b8;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .mess-brand-line-2 {
            color: #000;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 0.35rem;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
        }
        .mess-brand-line-3 {
            color: #505a5f;
            margin-top: 0.35rem;
            font-size: 0.8rem;
        }
        .header-img-left { width: 46px; height: 46px; object-fit: contain; display: block; }
        .header-img-right-seal { width: 48px; height: 48px; object-fit: contain; display: block; }
        .branding-hindi { font-size: 9px; color: #7b2d26; font-weight: 600; }
        .branding-en-side { font-size: 8px; color: #7b2d26; margin-top: 4px; font-weight: normal; }
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
            background: #003366;
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
            background: #0066cc;
            color: #fff;
            font-weight: 600;
            text-align: left;
        }
        table.stock-purchase-data thead th.text-end { text-align: right; }
        table.stock-purchase-data .text-center { text-align: center; }
        table.stock-purchase-data .text-end { text-align: right; }
        table.stock-purchase-data .vendor-section-header-row td,
        table.stock-purchase-data td.vendor-section-header {
            background: #e9ecef;
            color: #212529;
            font-weight: 700;
            font-size: 9.5px;
            border-color: #adb5bd;
        }
        table.stock-purchase-data .bill-header-row td,
        table.stock-purchase-data td.bill-header {
            background: #5a6268;
            color: #fff;
            font-weight: 700;
            border-color: #5a6268;
        }
        table.stock-purchase-data .vendor-total-row td {
            font-weight: 700;
            background: #dee2e6;
            border-top: 1px solid #adb5bd;
        }
        table.stock-purchase-data .bill-total-row td {
            background-color: #f8f9fa !important;
            font-weight: 700;
        }
        table.stock-purchase-data .grand-total-row td {
            background-color: #004a93 !important;
            color: #fff !important;
            border-color: #004a93 !important;
            font-weight: 700;
        }
        table.stock-purchase-data td.py-4,
        table.stock-purchase-data td.py-5 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        @media print {
            body { margin: 0.5in !important; padding: 0 !important; }
            .bg-body-tertiary { background-color: #fff !important; }
        }
    </style>
</head>
<body class="bg-body-tertiary">
<div class="container-fluid px-2 px-sm-3 py-3">
    <header class="mess-print-head">
        <div class="row g-3 align-items-center">
            <div class="col">
                <div class="d-flex align-items-start gap-3">
                    <img src="${emblemSrc}" alt="Emblem of India" class="flex-shrink-0 rounded" width="46" height="46" style="object-fit:contain;">
                    <div class="lh-sm text-start">
                        <div class="mess-brand-line-1">Government of India</div>
                        <div class="mess-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                        <div class="mess-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-start gap-2 justify-content-end">
                    <img src="${lbsnaaLogoSrc}" alt="LBSNAA" class="flex-shrink-0 rounded" height="48" style="object-fit:contain;">
                </div>
            </div>
                    <div class="branding-left-clear"></div>
                </td>
            </tr>
        </table>
    </div>

    </header>

    <section class="card border-0 shadow-sm mb-3 bg-white">
        <div class="card-body text-center pb-3 border-bottom">
            <h1 class="h5 fw-bold text-uppercase text-dark mb-3 mess-title-tracking">${title}</h1>
            <div><span class="badge rounded-pill mess-date-pill px-3 py-2">${dateRange}</span></div>
            <p class="fw-semibold small mb-0 mt-3"><span class="text-secondary">Vendor:</span> <span class="text-dark">${vendorLine}</span></p>
            <p class="fw-semibold small mb-0 mt-2"><span class="text-secondary">Store:</span> <span class="text-dark">${storeDetails}</span></p>
        </div>
    </section>

    <section class="card border shadow-sm mb-3 bg-white">
        <div class="card-header py-2 small fw-semibold bg-body-secondary border-bottom">
            Report context
        </div>
        <div class="card-body py-3 small">
            ${vendorDetailsHtml}
            <hr class="my-3 opacity-25">
            <p class="mb-1"><span class="text-secondary fw-semibold">Store:</span> ${storeDetails}</p>
            <p class="mb-0"><span class="text-secondary fw-semibold">Printed on:</span> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
        </div>
    </section>

    <section class="card border shadow-sm overflow-hidden bg-white">
        <div class="card-header py-2 small fw-semibold bg-body-secondary border-bottom">
            Purchase line items
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                ${table.outerHTML}
            </div>
        </div>
    </section>
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
@media screen {
    .stock-purchase-report .stock-purchase-table-wrapper {
        max-height: min(72vh, 760px);
        overflow: auto !important;
    }
    .stock-purchase-report .stock-purchase-table-wrapper .stock-purchase-thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }
}
.stock-purchase-report .table-responsive table {
    width: 100%;
    height: auto;
}

.mess-title-tracking { letter-spacing: 0.04em; }
.mess-report-date-pill { background-color: #003366 !important; }
.stock-purchase-report .stock-purchase-table { font-size: 0.9rem; }
.stock-purchase-report .vendor-section-header-row .vendor-section-header {
    background-color: #f1f3f5;
    color: #212529;
    font-weight: 700;
    padding: 0.5rem 0.75rem;
    border-top: 2px solid #adb5bd;
}
.stock-purchase-report .vendor-total-row td { background-color: #e9ecef; padding: 0.4rem 0.75rem; border-top: 1px solid #ced4da; }
.stock-purchase-report .bill-header-row .bill-header { background-color: #5a6268; color: #fff; font-weight: bold; padding: 0.5rem 0.75rem; }
.stock-purchase-report .bill-total-row { background-color: #fff; }
.stock-purchase-report .bill-total-row td { padding: 0.35rem 0.75rem; border-top: 1px solid #dee2e6; }
.stock-purchase-report .grand-total-row td { padding: 0.45rem 0.75rem; border-top: 2px solid #343a40; }
.stock-purchase-report .stock-purchase-table td { padding: 0.35rem 0.75rem; vertical-align: middle; }
.stock-purchase-report .page-input { display: inline-block; }
.report-date-bar { background: #004a93; color: #fff; font-size: 0.9rem; text-align: center; }
.stock-purchase-table-wrapper .stock-purchase-thead th { border-bottom-width: 2px; }
.report-vendor-name { font-size: 1rem; }
.stock-purchase-thead th { background: #0066cc; color: #fff; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; }
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
    html { font-size: 11pt !important; }
    .report-title-center { font-size: 11pt !important; color: #000 !important; text-align: center !important; }
    .report-date-bar { background: #5a6268 !important; color: #fff !important; text-align: center !important; font-size: 11pt !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-vendor-name { font-size: 11pt !important; color: #000 !important; margin-bottom: 0.75rem !important; }
    .report-store-name { font-size: 11pt !important; }
    body { font-size: 11pt !important; font-family: "DejaVu Sans", Arial, Helvetica, sans-serif !important; }
    .stock-purchase-table { font-size: 11pt !important; border-collapse: collapse !important; }
    .stock-purchase-table th, .stock-purchase-table td { font-size: 11pt !important; }
    .stock-purchase-table td, .stock-purchase-table th { border: 1px solid #333 !important; }
    .stock-purchase-thead th { background: #0066cc !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; position: static !important; box-shadow: none !important; }
    .stock-purchase-report .stock-purchase-table-wrapper { max-height: none !important; overflow: visible !important; }
    .stock-purchase-report .bill-header-row .bill-header { background: #5a6268 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .vendor-section-header { background: #e9ecef !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .vendor-total-row td { background: #dee2e6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endsection
