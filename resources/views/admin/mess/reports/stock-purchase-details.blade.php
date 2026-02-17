@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
<div class="container-fluid stock-purchase-report">
    <!-- Filters Section (Top - same pattern as other report pages) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Stock Purchase Details Report</h4>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Select Vendor Name</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Select Store Name</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Area (full width below filters) -->
    <div class="report-area">
            <!-- Report toolbar: pagination + grand total + actions -->
            <div class="report-toolbar no-print d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    @if($purchaseOrders->hasPages())
                        <nav class="report-pagination d-flex align-items-center gap-1">
                            <a href="{{ $purchaseOrders->url(1) }}" class="btn btn-sm btn-outline-secondary" @if($purchaseOrders->onFirstPage()) disabled @endif aria-label="First"><i class="ti ti-player-skip-back"></i></a>
                            <a href="{{ $purchaseOrders->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary" @if($purchaseOrders->onFirstPage()) disabled @endif aria-label="Previous"><i class="ti ti-chevron-left"></i></a>
                            <span class="px-2 small text-nowrap">Page <input type="number" min="1" max="{{ $purchaseOrders->lastPage() }}" value="{{ $purchaseOrders->currentPage() }}" class="form-control form-control-sm text-center page-input" style="width: 3rem;" onchange="var p=parseInt(this.value,10); if(!isNaN(p) && p>=1 && p<={{ $purchaseOrders->lastPage() }}) { var q=new URLSearchParams(window.location.search); q.set('page',p); window.location='{{ url()->current() }}?'+q.toString(); }"> of {{ $purchaseOrders->lastPage() }}</span>
                            <a href="{{ $purchaseOrders->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary" @if(!$purchaseOrders->hasMorePages()) disabled @endif aria-label="Next"><i class="ti ti-chevron-right"></i></a>
                            <a href="{{ $purchaseOrders->url($purchaseOrders->lastPage()) }}" class="btn btn-sm btn-outline-secondary" @if(!$purchaseOrders->hasMorePages()) disabled @endif aria-label="Last"><i class="ti ti-player-skip-forward"></i></a>
                        </nav>
                    @else
                        <span class="small text-muted">Page 1 of 1</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()" title="Print as PDF - use Save as PDF in print dialog">
                        <i class="ti ti-file-export"></i> Print as PDF
                    </button>
                </div>
            </div>

            <!-- Report content -->
            <div class="report-content card">
                <div class="card-body">
                    <!-- Report header (title centered, date bar, vendor) -->
                    <div class="report-header mb-4">
                        <h4 class="report-title-center fw-bold mb-2 text-dark text-center">Stock Purchase Details</h4>
                        <div class="report-date-bar py-2 px-3 mb-2 text-center">
                            Stock Purchase Details Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}
                        </div>
                        <p class="report-vendor-name fw-semibold mb-0">Vendor Name : {{ $selectedVendor ? $selectedVendor->name : 'All Vendors' }}</p>
                    </div>

                    <!-- Table: grouped by bill -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle stock-purchase-table mb-0">
                            <thead class="stock-purchase-thead">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $order)
                                    @php
                                        $storeName = $order->store ? $order->store->store_name : 'N/A';
                                        $billLabel = $storeName . '(Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
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
                                            <td>{{ $item->itemSubcategory->item_name ?? $item->itemSubcategory->subcategory_name ?? $item->itemSubcategory->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($qty, 2) }}</td>
                                            <td class="text-end">{{ number_format($rate, 1) }}</td>
                                            <td class="text-end">{{ number_format($total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bill-total-row">
                                        <td colspan="3" class="text-end fw-bold">Bill Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($billTotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No purchase details found</td>
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
