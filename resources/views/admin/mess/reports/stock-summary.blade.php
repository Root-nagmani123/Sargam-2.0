@extends('admin.layouts.master')

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
                        Print as PDF
                    </button>
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
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold text-dark">Stock Movement Summary</span>
            <span class="text-muted small">
                Total items: {{ count($reportData) }}
            </span>
        </div>
        <div class="table-responsive">
        <table class="table text-nowrap align-middle mb-0">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle" style="width: 60px;">SR.<br>No</th>
                    <th rowspan="2" class="text-center align-middle" style="min-width: 150px;">Item Name</th>
                    <th rowspan="2" class="text-center align-middle" style="min-width: 100px;">Item Code</th>
                    <th rowspan="2" class="text-center align-middle" style="min-width: 80px;">Unit</th>
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
                        <td>{{ $item['unit'] ?? '—' }}</td>
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
                        <td colspan="16" class="text-center text-muted py-4">
                            No stock movement found for the selected period
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
                        <td colspan="4" class="text-end">Total</td>
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
