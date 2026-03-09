@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
<div class="container-fluid stock-purchase-report">
    <x-breadcrum title="Stock Purchase Details Report"></x-breadcrum>
    <!-- Filters Section (Top - same pattern as other report pages) -->

    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold text-dark">Filter Purchases</h5>
                <span class="text-muted small">Refine results by date, vendor &amp; store</span>
            </div>
        </div>
        <div class="card-body pt-3">
            <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">From Date</label>
                        <input type="date" name="from_date" class="form-control " value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" name="to_date" class="form-control " value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Select Vendor Name</label>
                        <select name="vendor_id" class="form-select rounded-1 choices-select" data-placeholder="All Vendors">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Select Store Name</label>
                        <select name="store_id" class="form-select rounded-1 choices-select" data-placeholder="All Stores">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
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
            <!-- Report toolbar: pagination only (Print & Export Excel are in filter section above) -->
            <div class="report-toolbar no-print d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 bg-white border rounded-3 px-3 py-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    @if($purchaseOrders->hasPages())
                        <nav class="report-pagination d-flex align-items-center gap-1">
                            <a href="{{ $purchaseOrders->url(1) }}" class="btn btn-sm btn-outline-secondary" @if($purchaseOrders->onFirstPage()) disabled @endif aria-label="First">
                                <span class="material-symbols-rounded" style="font-size: 18px;">first_page</span>
                            </a>
                            <a href="{{ $purchaseOrders->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary" @if($purchaseOrders->onFirstPage()) disabled @endif aria-label="Previous">
                                <span class="material-symbols-rounded" style="font-size: 18px;">chevron_left</span>
                            </a>
                            <span class="px-2 small text-nowrap">Page <input type="number" min="1" max="{{ $purchaseOrders->lastPage() }}" value="{{ $purchaseOrders->currentPage() }}" data-max-page="{{ $purchaseOrders->lastPage() }}" class="form-control  text-center page-input" style="width: 3rem;" onchange="(function(input){ var max=parseInt(input.dataset.maxPage,10)||1; var p=parseInt(input.value,10); if(!isNaN(p) && p>=1 && p<=max){ var q=new URLSearchParams(window.location.search); q.set('page',p); window.location='{{ url()->current() }}?'+q.toString(); }})(this)"> of {{ $purchaseOrders->lastPage() }}</span>
                            <a href="{{ $purchaseOrders->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary" @if(!$purchaseOrders->hasMorePages()) disabled @endif aria-label="Next">
                                <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                            </a>
                            <a href="{{ $purchaseOrders->url($purchaseOrders->lastPage()) }}" class="btn btn-sm btn-outline-secondary" @if(!$purchaseOrders->hasMorePages()) disabled @endif aria-label="Last">
                                <span class="material-symbols-rounded" style="font-size: 18px;">last_page</span>
                            </a>
                        </nav>
                    @else
                        <span class="small text-muted">Page 1 of 1</span>
                    @endif
                </div>
            </div>

            <!-- Report content -->
            <div class="report-content card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <!-- Report header (title centered, date bar, vendor) -->
                    <div class="report-header mb-4 border-bottom pb-3">
                        <h4 class="report-title-center fw-bold mb-2 text-dark text-center text-uppercase tracking-wide">Stock Purchase Details</h4>
                        <div class="report-date-bar py-2 px-3 mb-2 text-center rounded-1 d-inline-block text-white fw-semibold justify-content-center">
                            Stock Purchase Details Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}
                        </div>
                        <div class="report-vendor-name fw-semibold mb-0 mt-2">
                            <span class="text-muted">Vendor:</span>
                            <span class="ms-1">{{ $selectedVendor ? $selectedVendor->name : 'All Vendors' }}</span>
                        </div>
                    </div>

                    <!-- Table: grouped by bill -->
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Item Code</th>
                                    <th class="text-end">Unit</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase</th>
                                    <th class="text-end">Tax %</th>
                                    <th class="text-end">Tax Amount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
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
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Choices === 'undefined') return;

        document
            .querySelectorAll('.stock-purchase-report select.choices-select')
            .forEach(function (el) {
                if (el.dataset.choicesInitialized === 'true') return;

                var placeholder = el.getAttribute('data-placeholder') || 'Select';

                new Choices(el, {
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: placeholder,
                    searchPlaceholderValue: 'Search...',
                });

                el.dataset.choicesInitialized = 'true';
            });
    });
})();
</script>
<script>
function printStockPurchaseTable() {
    var table = document.querySelector('.stock-purchase-report .report-content table');
    if (!table) {
        window.print();
        return;
    }

    var printWindow = window.open('', '_blank');
    if (!printWindow) {
        window.print();
        return;
    }

    var title = 'Stock Purchase Details';
    var dateRange = 'Stock Purchase Details Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}';
    var vendor = '{{ $selectedVendor ? $selectedVendor->name : 'All Vendors' }}';

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .lbsnaa-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: .75rem;
            margin-bottom: 1rem;
        }
        .lbsnaa-header .brand-line-1 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #004a93;
        }
        .lbsnaa-header .brand-line-2 {
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #222;
        }
        .lbsnaa-header .brand-line-3 {
            font-size: 0.8rem;
            color: #555;
        }
        .report-meta {
            font-size: 0.8rem;
            margin-bottom: .75rem;
        }
        .report-meta span {
            display: inline-block;
            margin-right: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #f8f9fa;
            font-weight: 600;
        }
        th, td {
            padding: 4px 6px;
            border: 1px solid #dee2e6;
        }
        /* Allow wrapping so wide content doesn't get cut off */
        .table.text-nowrap,
        .text-nowrap {
            white-space: normal !important;
        }
        /* Repeat header on each printed page */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        @media print {
            body { margin: 0.5in; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row align-items-center lbsnaa-header">
            <div class="col-auto d-none d-print-block">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="48">
            </div>
            <div class="col">
                <div class="brand-line-1">Government of India</div>
                <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </div>
            <div class="col-auto d-none d-print-block">
                <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="48">
            </div>
        </div>

        <div class="mb-2">
            <h5 class="mb-1">${title}</h5>
            <div class="report-meta">
                <span><strong>Period:</strong> ${dateRange}</span>
                <span><strong>Vendor:</strong> ${vendor}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
            </div>
        </div>

        <div class="table-responsive">
            ${table.outerHTML}
        </div>
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
.stock-purchase-report .stock-purchase-table { font-size: 0.9rem; }
.stock-purchase-report .bill-header-row .bill-header { background-color: #5a6268; color: #fff; font-weight: bold; padding: 0.5rem 0.75rem; }
.stock-purchase-report .bill-total-row { background-color: #fff; }
.stock-purchase-report .bill-total-row td { padding: 0.35rem 0.75rem; border-top: 1px solid #dee2e6; }
.stock-purchase-report .stock-purchase-table td { padding: 0.35rem 0.75rem; vertical-align: middle; }
.stock-purchase-report .page-input { display: inline-block; }
.report-date-bar { background: #5a6268; color: #fff; font-size: 0.9rem; text-align: center; }
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
