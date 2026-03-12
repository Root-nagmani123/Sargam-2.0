@extends('admin.layouts.master')
@section('title', 'Stock Summary Report')
@section('setup_content')
<div class="container-fluid stock-summary-report">
    <x-breadcrum title="Stock Summary Report"></x-breadcrum>
    <!-- Filters Section (Hide on Print) -->
    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold text-dark">Filter Stock Summary</h5>
                <span class="text-muted small">Refine results by date, store type &amp; store</span>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.stock-summary') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" 
                               value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" 
                               value="{{ $toDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Store Type</label>
                        <select name="store_type" id="store_type" class="form-select choices-select" data-placeholder="Select Store Type">
                            <option value="main" {{ $storeType == 'main' ? 'selected' : '' }}>Main Store</option>
                            <option value="sub" {{ $storeType == 'sub' ? 'selected' : '' }}>Sub Store</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="main_store_div" style="display: {{ $storeType == 'main' ? 'block' : 'none' }};">
                        <label class="form-label">Main Store</label>
                        <select name="store_id" class="form-select choices-select" data-placeholder="All Main Stores">
                            <option value="">All Main Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $storeId == $store->id && $storeType == 'main' ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4" id="sub_store_div" style="display: {{ $storeType == 'sub' ? 'block' : 'none' }};">
                        <label class="form-label">Sub Store</label>
                        <select name="store_id" class="form-select choices-select" data-placeholder="All Sub Stores">
                            <option value="">All Sub Stores</option>
                            @foreach($subStores as $subStore)
                                <option value="{{ $subStore->id }}" {{ $storeId == $subStore->id && $storeType == 'sub' ? 'selected' : '' }}>
                                    {{ $subStore->sub_store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">filter_list</span>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">refresh</span>
                        Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" onclick="printStockSummary()" title="Print report or choose Save as PDF in print dialog">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">print</span>
                        Print
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary.pdf', request()->query()) }}" class="btn btn-outline-danger d-inline-flex align-items-center" title="Download PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">picture_as_pdf</span>
                        PDF
                    </a>

                    <a href="{{ route('admin.mess.reports.stock-summary.excel', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center" title="Export to Excel">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">table_view</span>
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

<div class="card">
    <div class="card-body">
            <!-- Report Header -->
    <div class="report-header text-center mb-4">
        <h4 class="fw-bold text-uppercase mb-1">Stock Summary Report</h4>
        <p class="mb-1 text-muted">
            <span class="badge bg-light text-dark fw-normal px-3 py-2">
                Period: {{ date('d-F-Y', strtotime($fromDate)) }} to {{ date('d-F-Y', strtotime($toDate)) }}
            </span>
        </p>
        <p class="mb-0">
            <span class="badge bg-primary-subtle text-primary-emphasis fw-normal px-3 py-2">
                <strong>Store:</strong>
                {{ $selectedStoreName ?? ($storeType == 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores') }}
            </span>
        </p>
    </div>

    <!-- Report Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold text-dark">Stock Movement Summary</span>
                <span class="badge bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center gap-1" 
                      style="font-size: 0.7rem; padding: 0.25rem 0.5rem;"
                      title="First 4 columns stay fixed while scrolling horizontally">
                    <i class="material-symbols-rounded" style="font-size: 0.875rem;">push_pin</i>
                    Fixed Columns
                </span>
                <span class="badge bg-info-subtle text-info-emphasis d-inline-flex align-items-center gap-1" 
                      style="font-size: 0.7rem; padding: 0.25rem 0.5rem;"
                      title="Scroll horizontally to view all columns">
                    <i class="material-symbols-rounded" style="font-size: 0.875rem;">swipe_left</i>
                    Scroll to view more
                </span>
            </div>
            <span class="text-muted small">
                Total items: {{ count($reportData) }}
            </span>
        </div>
        <div class="table-responsive stock-table-wrapper">
        <table class="table text-nowrap align-middle mb-0 stock-fixed-columns-table">
            <thead>
                <tr>
                    <th rowspan="2" class="sticky-col sticky-col-1">
                        SR. No
                    </th>
                    <th rowspan="2" class="sticky-col sticky-col-2">Item Name
                    </th>
                    <th rowspan="2" class="sticky-col sticky-col-3">
                        Item Code
                    </th>
                    <th rowspan="2" class="sticky-col sticky-col-4">
                        Unit
                    </th>
                    <th colspan="3">
                        Opening
                    </th>
                    <th colspan="3">
                        Purchase
                    </th>
                    <th colspan="3">
                        Sale
                    </th>
                    <th colspan="3">
                        <i class="material-symbols-rounded d-inline-block me-1" style="font-size: 1rem; vertical-align: -2px;">inventory</i>
                        Closing
                    </th>
                </tr>
                <tr>
                    <!-- Opening -->
                    <th class="text-center">Qty</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Amount</th>
                    <!-- Purchase -->
                    <th class="text-center">Qty</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Amount</th>
                    <!-- Sale -->
                    <th class="text-center">Qty</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Amount</th>
                    <!-- Closing -->
                    <th class="text-center">Qty</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData as $index => $item)
                    <tr>
                        <td class="text-center sticky-col sticky-col-1">{{ $index + 1 }}</td>
                        <td class="sticky-col sticky-col-2">{{ $item['item_name'] }}</td>
                        <td class="sticky-col sticky-col-3">{{ $item['item_code'] ?? '—' }}</td>
                        <td class="sticky-col sticky-col-4">{{ $item['unit'] ?? '—' }}</td>
                        <!-- Opening -->
                        <td class="text-end">{{ number_format($item['opening_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['opening_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['opening_amount'], 2) }}</td>
                        <!-- Purchase -->
                        <td class="text-end">{{ number_format($item['purchase_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['purchase_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['purchase_amount'], 2) }}</td>
                        <!-- Sale -->
                        <td class="text-end">{{ number_format($item['sale_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['sale_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['sale_amount'], 2) }}</td>
                        <!-- Closing -->
                        <td class="text-end">{{ number_format($item['closing_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['closing_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['closing_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="d-flex flex-column align-items-center gap-3">
                                <i class="material-symbols-rounded" style="font-size: 4rem; color: #adb5bd; opacity: 0.5;">inbox</i>
                                <div>
                                    <h6 class="text-muted mb-1">No Stock Movement Found</h6>
                                    <p class="text-muted small mb-0">No transactions recorded for the selected period</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                @if(count($reportData) > 0)
                    @php
                        $totals = [
                            'opening_amount' => collect($reportData)->sum('opening_amount'),
                            'purchase_amount' => collect($reportData)->sum('purchase_amount'),
                            'sale_amount' => collect($reportData)->sum('sale_amount'),
                            'closing_amount' => collect($reportData)->sum('closing_amount'),
                        ];
                    @endphp
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end sticky-col sticky-col-total" style="font-size: 1rem; letter-spacing: 0.02em;">
                            <i class="material-symbols-rounded align-middle me-1" style="font-size: 1.125rem; vertical-align: -2px;">calculate</i>
                            Total
                        </td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format($totals['opening_amount'], 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format($totals['purchase_amount'], 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format($totals['sale_amount'], 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format($totals['closing_amount'], 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        </div>
        
        {{-- Scroll Hint --}}
        <div class="card-footer bg-light border-0 py-2 px-3 no-print">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i class="material-symbols-rounded" style="font-size: 1rem; opacity: 0.7;">info</i>
                <span>Tip: Scroll horizontally to view all columns. First 4 columns (SR No, Item Name, Code, Unit) remain fixed.</span>
            </div>
        </div>
    </div>
    </div>
</div>
</div>

<script>
function printStockSummary() {
    const table = document.querySelector('.stock-summary-report .table-responsive table');
    if (!table) {
        window.print();
        return;
    }

    const title     = 'Stock Summary Report';
    const dateRange = 'Stock Summary Report Between {{ date('d-F-Y', strtotime($fromDate)) }} To {{ date('d-F-Y', strtotime($toDate)) }}';
    const storeName = '{{ $selectedStoreName ?? ($storeType == 'main' ? "Officer\'s Main Mess(Primary)" : 'All Sub Stores') }}';

    const printWindow = window.open('', '_blank');
    if (!printWindow) { window.print(); return; }

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 9px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* LBSNAA watermark */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: url("https://www.lbsnaa.gov.in/admin_assets/images/logo.png") center center no-repeat;
      background-size: 240px 240px;
      opacity: 0.06;
      z-index: -1;
    }
    .lbsnaa-header { border-bottom: 2px solid #004a93; padding-bottom:.75rem; margin-bottom:1rem; }
    .brand-line-1 { font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#004a93; }
    .brand-line-2 { font-size:1.1rem; font-weight:700; text-transform:uppercase; color:#222; }
    .brand-line-3 { font-size:.8rem; color:#555; }
    .report-meta { font-size:.8rem; margin-bottom:.75rem; }
    .report-meta span { display:inline-block; margin-right:1.5rem; }
    table { width:100%; border-collapse:collapse; font-size: 8px; }
    th, td { padding:2px 4px; border:1px solid #dee2e6; }
    thead th { background:#f8f9fa; font-weight:600; }
    /* Allow wrapping so all columns stay on the page */
    .table,
    .table * {
      white-space: normal !important;
    }

    /* Ensure full table prints, not scrollable area only */
    .table-responsive {
      overflow: visible !important;
    }
    thead { display:table-header-group; }
    @page {
      size: A4 landscape;
      margin: 0.5in;
    }
    @media print {
      body { margin:0; }
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
        <span><strong>Store:</strong> ${storeName}</span>
        <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
      </div>
    </div>

    <div class="table-responsive">
      ${table.outerHTML}
    </div>
  </div>

  <script>
    window.addEventListener('load', function() { window.print(); });
  <\/script>
</body>
</html>`);
    printWindow.document.close();
}
</script>

<style>
    /* Enhanced Sticky Column Styles */
    .stock-table-wrapper {
        position: relative;
        overflow-x: auto;
        overflow-y: visible;
        border-radius: 8px;
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
        /* Force horizontal scroll when needed */
        max-width: 100%;
        -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
        scroll-behavior: smooth; /* Smooth scrolling */
        will-change: scroll-position; /* Optimize for scrolling */
    }
    
    /* Ensure table is wide enough to trigger scrolling */
    .stock-fixed-columns-table {
        position: relative;
        margin-bottom: 0;
        min-width: 1200px; /* Force minimum width to enable scrolling */
        width: max-content; /* Table takes full content width */
        border-collapse: separate; /* Required for sticky positioning */
        border-spacing: 0; /* No spacing between cells */
        table-layout: auto; /* Allow natural column sizing */
    }
    
    /* Ensure non-sticky columns don't have position styles */
    .stock-fixed-columns-table th:not(.sticky-col),
    .stock-fixed-columns-table td:not(.sticky-col) {
        position: relative; /* Default positioning */
        min-width: 100px; /* Minimum width for data columns */
    }
    
    /* Scroll indicator shadows */
    .stock-table-wrapper::before,
    .stock-table-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 20px;
        pointer-events: none;
        z-index: 15;
        transition: opacity 0.3s ease;
    }
    
    .stock-table-wrapper::before {
        left: 460px;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.12), transparent);
    }
    
    .stock-table-wrapper::after {
        right: 0;
        background: linear-gradient(to left, rgba(0, 0, 0, 0.08), transparent);
        opacity: 0;
    }
    
    .stock-table-wrapper.scrolled::after {
        opacity: 1;
    }
    
    /* Enhanced sticky columns with gradient background */
    .sticky-col {
        position: sticky !important; /* Force sticky */
        position: -webkit-sticky !important; /* Safari support */
        background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
        z-index: 10;
        border-right: 2px solid #e9ecef !important;
        transition: all 0.2s ease;
    }
    
    /* Individual column positions with optimized widths */
    .sticky-col-1 {
        left: 0 !important;
        min-width: 70px;
        max-width: 70px;
        width: 70px;
        text-align: center;
        font-weight: 600;
    }
    
    .sticky-col-2 {
        left: 70px !important;
        min-width: 200px;
        max-width: 200px;
        width: 200px;
        font-weight: 500;
    }
    
    .sticky-col-3 {
        left: 270px !important;
        min-width: 110px;
        max-width: 110px;
        width: 110px;
    }
    
    .sticky-col-4 {
        left: 380px !important;
        min-width: 80px;
        max-width: 80px;
        width: 80px;
        text-align: center;
    }
    
    /* Total row enhanced sticky column */
    .sticky-col-total {
        position: sticky !important;
        position: -webkit-sticky !important;
        left: 0 !important;
        width: 460px !important;
        min-width: 460px;
        max-width: 460px;
        background: linear-gradient(135deg, #e2e3e5 0%, #d3d4d6 100%) !important;
        z-index: 10;
        font-weight: 700;
        border-right: 3px solid #adb5bd !important;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Enhanced header sticky columns */
    thead .sticky-col {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        z-index: 20;
        z-index: 20;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8125rem;
        letter-spacing: 0.02em;
        color: #495057;
        vertical-align: middle;
        padding: 12px 8px !important;
    }
    
    /* Premium shadow effect for visual separation */
    .sticky-col::after {
        content: '';
        position: absolute;
        top: 0;
        right: -2px;
        bottom: 0;
        width: 8px;
        background: linear-gradient(to right, 
            rgba(0, 0, 0, 0.15) 0%, 
            rgba(0, 0, 0, 0.08) 50%, 
            transparent 100%);
        pointer-events: none;
        opacity: 0.8;
    }
    
    .sticky-col-4::after {
        width: 12px;
        background: linear-gradient(to right, 
            rgba(13, 110, 253, 0.15) 0%, 
            rgba(13, 110, 253, 0.08) 30%, 
            rgba(0, 0, 0, 0.06) 60%, 
            transparent 100%);
    }
    
    /* Enhanced borders */
    .stock-fixed-columns-table th,
    .stock-fixed-columns-table td {
        border: 1px solid #dee2e6;
        padding: 10px 8px;
        background-clip: padding-box; /* Prevent background from showing through borders */
    }
    
    .stock-fixed-columns-table thead th {
        border-bottom: 2px solid #adb5bd;
        position: sticky; /* Make all header rows sticky if needed */
        top: 0;
        vertical-align: middle;
    }
    
    /* Override Bootstrap table styles that might interfere */
    .stock-table-wrapper .table {
        margin-bottom: 0;
    }
    
    .stock-table-wrapper .table > :not(caption) > * > * {
        background-color: transparent; /* Prevent Bootstrap default backgrounds */
    }
    
    /* Premium hover effects */
    .stock-fixed-columns-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .stock-fixed-columns-table tbody tr:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transform: translateY(-1px);
    }
    
    .stock-fixed-columns-table tbody tr:hover .sticky-col {
        background: linear-gradient(135deg, #f0f7ff 0%, #e7f3ff 100%);
        border-right-color: #0d6efd !important;
    }
    
    .stock-fixed-columns-table tbody tr:hover td:not(.sticky-col) {
        background-color: transparent;
    }
    
    /* Striped rows enhancement */
    .stock-fixed-columns-table tbody tr:nth-child(even) .sticky-col {
        background: linear-gradient(135deg, #fafbfc 0%, #f1f3f5 100%);
    }
    
    /* Active/Focus state */
    .stock-fixed-columns-table tbody tr:active {
        transform: translateY(0);
    }
    
    /* Column group headers styling */
    .stock-fixed-columns-table thead tr:first-child th:not(.sticky-col) {
        background: linear-gradient(135deg, #e7f3ff 0%, #cfe2ff 100%);
        color: #084298;
        font-weight: 700;
        border-bottom: 2px solid #0d6efd;
    }
    
    /* Enhanced scrollbar styling */
    .stock-table-wrapper::-webkit-scrollbar {
        height: 12px;
    }
    
    .stock-table-wrapper::-webkit-scrollbar-track {
        background: #f1f3f5;
        border-radius: 6px;
    }
    
    .stock-table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border-radius: 6px;
        border: 2px solid #f1f3f5;
    }
    
    .stock-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    }
    
    /* Text truncation for long item names */
    .sticky-col-2 {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .sticky-col-2:hover {
        overflow: visible;
        white-space: normal;
        word-wrap: break-word;
        z-index: 25;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Loading state (optional) */
    @keyframes shimmer {
        0% { background-position: -468px 0; }
        100% { background-position: 468px 0; }
    }
    
    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .sticky-col-1 { min-width: 50px !important; max-width: 50px !important; width: 50px !important; left: 0 !important; }
        .sticky-col-2 { min-width: 150px !important; max-width: 150px !important; width: 150px !important; left: 50px !important; }
        .sticky-col-3 { min-width: 90px !important; max-width: 90px !important; width: 90px !important; left: 200px !important; }
        .sticky-col-4 { min-width: 60px !important; max-width: 60px !important; width: 60px !important; left: 290px !important; }
        .sticky-col-total { width: 350px !important; min-width: 350px !important; max-width: 350px !important; }
        .stock-table-wrapper::before { left: 350px !important; }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .sticky-col {
            background: linear-gradient(135deg, #1a1d23 0%, #23262d 100%) !important;
            border-right-color: #3a3d45 !important;
        }
        
        thead .sticky-col {
            background: linear-gradient(135deg, #2a2d35 0%, #343740 100%) !important;
            color: #e9ecef !important;
        }
        
        .sticky-col-total {
            background: linear-gradient(135deg, #3a3d45 0%, #4a4d55 100%) !important;
        }
        
        .stock-fixed-columns-table tbody tr:hover .sticky-col {
            background: linear-gradient(135deg, #2a3f5f 0%, #1e2d48 100%) !important;
        }
        
        .stock-table-wrapper::before {
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1), transparent);
        }
    }
    
    @media print {
        .no-print { 
            display: none !important; 
        }
        .report-header { 
            display: block !important;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        body { 
            font-size: 12px; 
        }
        table { 
            font-size: 11px;
            page-break-inside: auto;
        }
        table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table thead {
            display: table-header-group;
        }
        th, td { 
            padding: 6px !important; 
        }
        @page {
            margin: 1cm;
            size: A4 landscape;
        }
        
        /* Remove sticky positioning and effects for print */
        .sticky-col {
            position: static !important;
            background: #fff !important;
            border-right: 1px solid #dee2e6 !important;
        }
        
        .sticky-col::after,
        .stock-table-wrapper::before,
        .stock-table-wrapper::after {
            display: none !important;
        }
        
        .stock-table-wrapper {
            overflow: visible !important;
            box-shadow: none !important;
        }
    }
    
    .report-header h4 {
        margin-bottom: 10px;
        color: #000;
        font-weight: bold;
    }
    
    .report-header p {
        color: #333;
        font-size: 14px;
    }

    /* Table styling for better visibility */
    .table th {
        font-weight: 600;
        white-space: nowrap;
    }

    .table td {
        white-space: nowrap;
    }

    /* Error highlighting */
    .table-danger {
        background-color: #f8d7da !important;
    }

    .table-danger:hover {
        background-color: #f5c2c7 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    /* Alert styling */
    .alert-danger {
        border-left: 4px solid #dc3545;
    }

    @media print {
        .table-danger {
            background-color: #ffcccc !important;
            border: 2px solid #ff0000 !important;
        }
    }
</style>

<script>
    // Enhanced scroll indicator for table
    document.addEventListener('DOMContentLoaded', function() {
        const tableWrapper = document.querySelector('.stock-table-wrapper');
        if (tableWrapper) {
            tableWrapper.addEventListener('scroll', function() {
                if (this.scrollLeft > 10) {
                    this.classList.add('scrolled');
                } else {
                    this.classList.remove('scrolled');
                }
            });
        }
    });
</script>

<script>
    // Store Type Selection Handler
    document.addEventListener('DOMContentLoaded', function() {
        const storeTypeSelect = document.getElementById('store_type');
        const mainStoreDiv = document.getElementById('main_store_div');
        const subStoreDiv = document.getElementById('sub_store_div');

        if (storeTypeSelect) {
            storeTypeSelect.addEventListener('change', function() {
                if (this.value === 'main') {
                    mainStoreDiv.style.display = 'block';
                    subStoreDiv.style.display = 'none';
                } else {
                    mainStoreDiv.style.display = 'none';
                    subStoreDiv.style.display = 'block';
                }
            });
        }
    });
</script>

{{-- Choices.js (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Choices === 'undefined') return;

            document
                .querySelectorAll('.stock-summary-report select.choices-select')
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
@endsection
