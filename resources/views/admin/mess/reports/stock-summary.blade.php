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
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.reports.stock-summary') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">From Date</label>
                        <input type="date" name="from_date" class="form-control" 
                               value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">To Date</label>
                        <input type="date" name="to_date" class="form-control" 
                               value="{{ $toDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">Store Type</label>
                        <select name="store_type" id="store_type" class="form-select" data-placeholder="Select Store Type">
                            <option value="main" {{ $storeType == 'main' ? 'selected' : '' }}>Main Store</option>
                            <option value="sub" {{ $storeType == 'sub' ? 'selected' : '' }}>Sub Store</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="main_store_div" style="display: {{ $storeType == 'main' ? 'block' : 'none' }};">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">Main Store</label>
                        <select name="main_store_id" class="form-select form-select-sm" data-placeholder="All Main Stores">
                            <option value="">All Main Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $storeId == $store->id && $storeType == 'main' ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="sub_store_div" style="display: {{ $storeType == 'sub' ? 'block' : 'none' }};">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">Sub Store</label>
                        <select name="sub_store_id" class="form-select form-select-sm" data-placeholder="All Sub Stores">
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
                    <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">filter_list</span>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">refresh</span>
                        Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" onclick="printStockSummary()" title="Print report or choose Save as PDF in print dialog">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">print</span>
                        Print
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" title="Download PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">picture_as_pdf</span>
                        PDF
                    </a>

                    <a href="{{ route('admin.mess.reports.stock-summary.excel', request()->query()) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" title="Export to Excel">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">table_view</span>
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-3 p-lg-4">
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
            </div>
            <span class="text-muted small">
                Total items: {{ count($reportData) }}
            </span>
        </div>
        <div class="table-responsive table-fit-single-view">
        <table class="table table-fit align-middle mb-0">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle">SR.<br>No</th>
                    <th rowspan="2" class="text-center align-middle">Item Name</th>
                    <th rowspan="2" class="text-center align-middle">Item Code</th>
                    <th rowspan="2" class="text-center align-middle">Unit</th>
                    <th colspan="3" class="text-center">Opening</th>
                    <th colspan="3" class="text-center">Purchase</th>
                    <th colspan="3" class="text-center">Sale</th>
                    <th colspan="3" class="text-center">Closing</th>
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
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['item_name'] }}</td>
                        <td>{{ $item['item_code'] ?? '—' }}</td>
                        <td>{{ isset($item['unit']) && is_numeric($item['unit']) ? number_format((float)$item['unit'], 2) : ($item['unit'] ?? '—') }}</td>
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
                    <tr class="table-primary fw-bold">
                        <td colspan="4" class="text-end sticky-col sticky-col-total" style="font-size: 1rem; letter-spacing: 0.02em;">
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
    </div>
    </div>
</div>
</div>

<style>
    .stock-table-wrapper {
        position: relative;
        overflow: auto; /* allow both horizontal and vertical scroll inside */
        max-height: 70vh;
    }

    .stock-fixed-columns-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .stock-fixed-columns-table th,
    .stock-fixed-columns-table td {
        white-space: nowrap;
    }

    .stock-fixed-columns-table .sticky-col {
        position: -webkit-sticky;
        position: sticky;
        background-color: #ffffff;
        z-index: 2;
    }

    .stock-fixed-columns-table thead .sticky-col {
        z-index: 3;
        top: 0;
    }

    /* Column widths and offsets (tune these values as per your design) */
    .stock-table-wrapper {
        --col-1-width: 60px;
        --col-2-width: 220px;
        --col-3-width: 120px;
        --col-4-width: 80px;
    }

    .stock-fixed-columns-table .sticky-col-1 {
        left: 0;
        min-width: var(--col-1-width);
    }

    .stock-fixed-columns-table .sticky-col-2 {
        left: var(--col-1-width);
        min-width: var(--col-2-width);
    }

    .stock-fixed-columns-table .sticky-col-3 {
        left: calc(var(--col-1-width) + var(--col-2-width));
        min-width: var(--col-3-width);
    }

    .stock-fixed-columns-table .sticky-col-4 {
        left: calc(var(--col-1-width) + var(--col-2-width) + var(--col-3-width));
        min-width: var(--col-4-width);
    }
</style>

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

    /* Table fits in single view: no scroll, auto height/width */
    .stock-summary-report .table-fit-single-view {
        overflow: visible;
        max-width: 100%;
    }

    .stock-summary-report .table-fit {
        width: 100%;
        table-layout: fixed;
        font-size: 0.8rem;
    }

    .stock-summary-report .table-fit th,
    .stock-summary-report .table-fit td {
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        padding: 0.4rem 0.5rem;
        vertical-align: middle;
    }

    .stock-summary-report .table-fit th {
        font-weight: 600;
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

{{-- Tom Select (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.TomSelect === 'undefined') return;

        document
            .querySelectorAll('.stock-summary-report select')
            .forEach(function (el) {
                if (el.tomselect) return;

                var placeholder = el.getAttribute('data-placeholder') || 'Select';

                new TomSelect(el, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: placeholder,
                    plugins: ['dropdown_input'],
                    sortField: {
                        field: 'text',
                        direction: 'asc'
                    }
                });
            });
    });
</script>
@endsection
