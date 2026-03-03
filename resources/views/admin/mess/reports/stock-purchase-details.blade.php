@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
<div class="container-fluid stock-purchase-report py-3">
    <x-breadcrum title="Stock Purchase Details Report"   />

    <div class="report-page-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                <span class="rounded-2 bg-primary bg-opacity-10 p-2 d-inline-flex align-items-center justify-content-center report-page-icon">
                    <span class="material-icons material-symbols-rounded text-primary icon-sm">shopping_cart</span>
                </span>
                Stock Purchase Details Report
            </h1>
            <p class="text-body-secondary small mb-0">Filter by date range, vendor and store to view purchase details.</p>
        </div>
    </div>

    <div class="card no-print shadow-sm border-0 rounded-3 mb-4 overflow-hidden border-start border-4 border-primary">
        <div class="card-header bg-primary border-0 py-3 px-4">
            <h5 class="card-title mb-0 fw-semibold text-white d-flex align-items-center gap-2">Report Filters</h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}" id="stock-purchase-filters-form">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm fw-medium mb-1">From Date</label>
                        <div class="input-group input-group-refined flex-nowrap">
                            <input type="date" name="from_date" class="form-control stock-purchase-filter-input" value="{{ $fromDate }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm fw-medium mb-1">To Date</label>
                        <div class="input-group input-group-refined flex-nowrap">
                            <input type="date" name="to_date" class="form-control stock-purchase-filter-input" value="{{ $toDate }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm fw-medium mb-1">Vendor</label>
                        <div class="input-group input-group-refined flex-nowrap">
                            <select name="vendor_id" class="form-select select2 stock-purchase-filter-input">
                                <option value="">All Vendors</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label form-label-sm fw-medium mb-1">Store</label>
                        <div class="input-group input-group-refined flex-nowrap">
                            <select name="store_id" class="form-select select2 stock-purchase-filter-input">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <hr class="my-4 border-secondary border-opacity-25">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm btn-refined d-inline-flex align-items-center gap-2 px-3">
                        <span class="material-icons material-symbols-rounded icon-sm">filter_list</span> Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-outline-secondary btn-sm btn-refined d-inline-flex align-items-center gap-2 px-3">
                        <span class="material-icons material-symbols-rounded icon-sm">refresh</span> Reset
                    </a>
                    <span class="vr d-none d-md-inline-block opacity-50 mx-1"></span>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-refined d-inline-flex align-items-center gap-2 px-3" onclick="window.print()" title="Print report or Save as PDF">
                        <span class="material-icons material-symbols-rounded icon-sm">print</span> Print
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details.excel', request()->query()) }}" class="btn btn-success btn-sm btn-refined d-inline-flex align-items-center gap-2 px-3" title="Export to Excel">
                        <span class="material-icons material-symbols-rounded icon-sm">table_chart</span> Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="report-area">

            {{-- Same layout for screen, print/PDF and Excel export --}}
            <div class="report-content card shadow-sm border-0 rounded-3 overflow-hidden report-card" id="stock-purchase-report-document">
                <div class="card-body p-4 p-md-5">
                    <div class="report-header mb-4">
                        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mb-3">
                            <h4 class="report-title-center fw-bold mb-0 text-dark text-center text-md-start fs-4 d-flex align-items-center gap-2">
                                <span class="material-icons material-symbols-rounded text-info report-title-icon d-none d-md-inline" style="font-size:1.25rem;">receipt_long</span>
                                Stock Purchase Details
                            </h4>
                            <span class="badge bg-info text-white fw-medium px-3 py-2 rounded-pill no-print">Report</span>
                        </div>
                        <div class="report-date-bar py-3 px-4 mb-3 text-center rounded-3 small fw-medium">
                            {{ date('d F Y', strtotime($fromDate)) }} — {{ date('d F Y', strtotime($toDate)) }}
                        </div>
                        <p class="report-vendor-name fw-semibold mb-0 text-body-secondary d-flex align-items-center gap-1 flex-wrap">
                            <span class="material-icons material-symbols-rounded text-primary icon-sm">person</span>
                            <span>Vendor:</span>
                            <span class="text-dark">{{ $selectedVendor ? $selectedVendor->name : 'All Vendors' }}</span>
                        </p>
                    </div>

                    <div class="table-responsive rounded-3 overflow-hidden report-table-wrap border border-secondary border-opacity-25 shadow-sm">
                        <table class="table align-middle stock-purchase-table mb-0 ">
                            <thead class="stock-purchase-thead">
                                <tr>
                                    <th class="text-center">Item</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase (₹)</th>
                                    <th class="text-end">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $order)
                                    @php
                                        $storeName = $order->store ? $order->store->store_name : 'N/A';
                                        $billLabel = $storeName . ' (Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
                                        $billTotal = 0;
                                    @endphp
                                    <tr class="bill-header-row">
                                        <td colspan="4" class="bill-header">{{ $billLabel }}</td>
                                    </tr>
                                    @foreach($order->items as $item)
                                        @php
                                            $qty = $item->quantity ?? 0;
                                            $rate = $item->unit_price ?? 0;
                                            $total = $qty * $rate;
                                            $billTotal += $total;
                                        @endphp
                                        <tr>
                                            <td class="ps-3">{{ $item->itemSubcategory->item_name ?? $item->itemSubcategory->subcategory_name ?? $item->itemSubcategory->name ?? 'N/A' }}</td>
                                            <td class="text-end font-monospace">{{ number_format($qty, 2) }}</td>
                                            <td class="text-end font-monospace">₹{{ number_format($rate, 1) }}</td>
                                            <td class="text-end font-monospace fw-medium pe-3">₹{{ number_format($total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bill-total-row table-active">
                                        <td colspan="3" class="text-end fw-bold ps-3">Bill Total</td>
                                        <td class="text-end fw-bold pe-3 font-monospace">₹{{ number_format($billTotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 px-4">
                                            <div class="d-inline-block text-muted">
                                                <span class="material-icons material-symbols-rounded d-block mb-3 opacity-25" style="font-size:3rem;">remove_shopping_cart</span>
                                                <p class="fw-medium mb-1">No purchase details found</p>
                                                <p class="small mb-0 opacity-75">Try adjusting the date range or filters above.</p>
                                            </div>
                                        </td>
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

<style>
/* Base table & report */
.stock-purchase-report .stock-purchase-table { font-size: 0.9rem; }
.stock-purchase-report .bill-header-row .bill-header { background-color: var(--bs-secondary); color: #fff; font-weight: 600; padding: 0.5rem 0.75rem; }
.stock-purchase-report .bill-header-row:hover .bill-header { background-color: var(--bs-secondary); color: #fff; }
.stock-purchase-report .bill-header-row,
.stock-purchase-report .bill-total-row { --bs-table-accent-bg: transparent; }
.stock-purchase-report .bill-total-row { background-color: var(--bs-table-active-bg, rgba(0,0,0,.04)); }
.stock-purchase-report .bill-total-row td { padding: 0.5rem 0.75rem; border-top: 2px solid var(--bs-border-color); }
.stock-purchase-report .stock-purchase-table tbody tr:not(.bill-header-row):not(.bill-total-row):hover { background-color: var(--bs-table-hover-bg); }
.stock-purchase-report .stock-purchase-table td { padding: 0.45rem 0.75rem; vertical-align: middle; }
.stock-purchase-report .page-input { display: inline-block; }
.report-date-bar { background: var(--bs-secondary); color: #fff; font-size: 0.9rem; text-align: center; }
.report-vendor-name { font-size: 1rem; }
.stock-purchase-thead th { font-weight: 600; padding: 0.55rem 0.75rem; text-align: left; }
.stock-purchase-thead th.text-end { text-align: right; }

/* Refinements */
.stock-purchase-report .icon-sm { font-size: 1.1rem !important; }
.stock-purchase-report .form-label-sm { font-size: 0.75rem; letter-spacing: 0.02em; text-transform: uppercase; color: var(--bs-secondary); }
.stock-purchase-report .input-group-refined .input-group-text { border: 1px solid var(--bs-border-color); border-right: 0; background: var(--bs-tertiary-bg); }
.stock-purchase-report .input-group-refined .form-control,
.stock-purchase-report .input-group-refined .form-select { border-left: 0; }
.stock-purchase-report .input-group-refined .form-control:focus,
.stock-purchase-report .input-group-refined .form-select:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15); }
.stock-purchase-report .input-group-refined:focus-within .input-group-text { border-color: var(--bs-primary); }
.stock-purchase-report .btn-refined { transition: color .15s ease, background-color .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.stock-purchase-report .btn-refined:hover:not(:disabled) { transform: translateY(-1px); }
.stock-purchase-report .btn-refined:active:not(:disabled) { transform: translateY(0); }
.stock-purchase-report .btn-refined:focus-visible { box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); outline: 0; }
.stock-purchase-report .focus-ring:focus { box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); outline: 0; }
.stock-purchase-report .btn-pagination { transition: color .15s ease, background-color .15s ease, border-color .15s ease; }
.stock-purchase-report .btn-pagination:hover:not(:disabled) { background-color: var(--bs-secondary-bg); }
.stock-purchase-report .page-jumper .page-input { width: 3.25rem; min-width: 2.75rem; }
.stock-purchase-report .toolbar-refined { background: var(--bs-light); border: 1px solid var(--bs-border-color-translucent); }
.stock-purchase-report .report-card { border-left: 4px solid rgba(var(--bs-info-rgb), 0.5); }
.stock-purchase-report .report-table-wrap { border-color: var(--bs-border-color-translucent); }

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
    /* Report only – same structure as Excel: title, date range, vendor, table */
    .stock-purchase-report { padding: 0 !important; margin: 0 !important; }
    #stock-purchase-report-document { box-shadow: none !important; border: none !important; margin-top: 0 !important; }
    .report-content .card-body { padding: 0.5rem 0 !important; }
    .report-header { margin-top: 0 !important; margin-bottom: 1rem !important; }
    .report-title-center { font-size: 14pt !important; font-weight: bold !important; color: #000 !important; text-align: center !important; }
    .report-title-center .report-title-icon { display: none !important; }
    .report-date-bar { background: #5a6268 !important; color: #fff !important; text-align: center !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 0.4rem 0.5rem !important; }
    .report-vendor-name { font-size: 1rem !important; color: #000 !important; margin-bottom: 0.75rem !important; }
    body { font-size: 12px; }
    .stock-purchase-table { font-size: 11px; border-collapse: collapse !important; }
    .stock-purchase-table td, .stock-purchase-table th { border: 1px solid #333 !important; }
    .stock-purchase-thead th { background: #d3d6d9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .stock-purchase-report .bill-header-row .bill-header { background: #5a6268 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('stock-purchase-filters-form');
    if (!form) return;
    form.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('stock-purchase-filter-input')) {
            form.submit();
        }
    });
});
</script>
@endpush
@endsection
