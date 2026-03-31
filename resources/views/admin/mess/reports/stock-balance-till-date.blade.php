@extends('admin.layouts.master')
@section('title', 'Stock Balance as of Till Date')
@section('setup_content')
@php
    /** @var array<int> $storeIds */
    $storeIds = isset($storeIds) ? $storeIds : [];
@endphp
<div class="container-fluid stock-balance-report min-vh-100 d-flex flex-column">
    <x-breadcrum title="Stock Balance as of Till Date"></x-breadcrum>
    <!-- Header Section -->
    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold text-dark">Filter Stock Balance as of Till Date</h5>
                <span class="text-muted small">Refine results by till date &amp; store</span>
            </div>
        </div>
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.reports.stock-balance-till-date') }}">
                <div class="row g-3 g-lg-4 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="till_date" class="form-label small fw-semibold text-uppercase mb-1">Till Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary" id="till_date_addon">
                                <span class="material-symbols-rounded" style="font-size: 20px;" aria-hidden="true">event</span>
                            </span>
                            <input type="date"
                                   name="till_date"
                                   id="till_date"
                                   class="form-control"
                                   value="{{ $tillDate }}"
                                   aria-describedby="till_date_addon">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="store_id" class="form-label small fw-semibold text-uppercase mb-1">Select Store Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary" id="store_id_addon">
                                <span class="material-symbols-rounded" style="font-size: 20px;" aria-hidden="true">storefront</span>
                            </span>
                            <select name="store_id[]"
                                    id="store_id"
                                    class="form-select stock-balance-store-multiselect"
                                    multiple
                                    data-placeholder="All Stores"
                                    aria-describedby="store_id_addon">
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $storeIds, true))>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center justify-content-xl-end">
                            <div class="btn-group shadow-sm" role="group" aria-label="Filter actions">
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1 px-3">
                                    <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">filter_list</span>
                                    <span>Apply Filters</span>
                                </button>
                                <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 px-3">
                                    <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">refresh</span>
                                    <span>Reset</span>
                                </a>
                            </div>
                            <div class="vr d-none d-sm-block text-body-secondary opacity-25 align-self-stretch"></div>
                            <div class="btn-group shadow-sm" role="group" aria-label="Export actions">
                                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1 px-3" onclick="printStockBalance()" title="Print or Save as PDF">
                                    <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">print</span>
                                    <span>Print</span>
                                </button>
                                <a href="{{ route('admin.mess.reports.stock-balance-till-date.pdf', request()->query()) }}" class="btn btn-danger d-inline-flex align-items-center justify-content-center gap-1 px-3" title="Download PDF">
                                    <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">picture_as_pdf</span>
                                    <span>Download PDF</span>
                                </a>
                                <a href="{{ route('admin.mess.reports.stock-balance-till-date.excel', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center justify-content-center gap-1 px-3" title="Export to Excel">
                                    <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">table_view</span>
                                    <span>Export Excel</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

<div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column min-h-0">
    <div class="card-body d-flex flex-column flex-grow-1 min-h-0">
        <!-- Report Heading (Print Only) -->
        <div class="report-header text-center mb-4">
            <h4 class="fw-bold text-uppercase mb-1">Stock Balance as of Till Date</h4>
            <h5 class="text-primary mb-1">Store: {{ $selectedStoreName ?? 'All Stores' }}</h5>
            <p class="mb-0 text-muted">As on: {{ date('d-M-Y', strtotime($tillDate)) }}</p>
        </div>

        <!-- Report Table -->
        <div class="card border-0 shadow-none flex-grow-1 d-flex flex-column min-h-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-shrink-0">
                <span class="fw-semibold text-dark">Stock Balance Details</span>
                <span class="text-muted small">
                    Total items: {{ count($reportData) }}
                </span>
            </div>
            <div class="table-responsive flex-grow-1 overflow-auto">
                <table class="table table-hover align-middle mb-0 stock-balance-table">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th class="text-end">Remaining Quantity</th>
                            <th>Unit</th>
                            <th class="text-end">Avg rate</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalAmount = 0;
                        @endphp
                        @forelse($reportData as $index => $item)
                            @php
                                $totalAmount += $item['amount'];
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item['item_code'] ?? '—' }}</td>
                                <td>{{ $item['item_name'] }}</td>
                                <td class="text-end">{{ number_format($item['remaining_qty'], 2) }}</td>
                                <td>{{ $item['unit'] ?? 'Unit' }}</td>
                                <td class="text-end">₹{{ number_format($item['rate'], 2) }}</td>
                                <td class="text-end">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No stock balance found</td>
                            </tr>
                        @endforelse
                        @if(count($reportData) > 0)
                            <tr class="table-light fw-bold">
                                <td colspan="6" class="text-end">Total Amount:</td>
                                <td class="text-end">₹{{ number_format($totalAmount, 2) }}</td>
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
    /* Auto height/width and proper table view */
    .stock-balance-report {
        width: 100%;
        max-width: 100%;
    }

    .stock-balance-report .card.flex-grow-1,
    .stock-balance-report .card-body.min-h-0,
    .stock-balance-report .card.flex-grow-1 .card.min-h-0 {
        min-height: 0;
    }

    .stock-balance-report .table-responsive {
        min-height: 200px;
        max-height: calc(100vh - 320px);
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        overflow-y: auto;
    }

    .stock-balance-report .stock-balance-table {
        width: 100%;
        min-width: 700px;
        table-layout: auto;
    }

    .stock-balance-report .stock-balance-table thead th {
        font-weight: 600;
        white-space: nowrap;
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 1;
        box-shadow: 0 1px 0 #dee2e6;
        padding: 0.75rem;
    }

    .stock-balance-report .stock-balance-table tbody td {
        white-space: nowrap;
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }

    .stock-balance-report .card {
        border-radius: 0.75rem;
    }

    .stock-balance-report .card-header {
        border-bottom: 1px solid #edf1f5;
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
        }
        th, td {
            padding: 8px !important;
        }
        .stock-balance-report .table-responsive {
            max-height: none !important;
            overflow: visible !important;
        }
        .stock-balance-report .stock-balance-table thead th {
            position: static;
        }
    }

    @media screen {
        .report-header {
            display: none;
        }
    }

    .report-header h4 {
        margin-bottom: 10px;
        color: #000;
    }

    .report-header h5 {
        margin-bottom: 20px;
        color: #af2910;
    }

</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.TomSelect === 'undefined') return;
        document.querySelectorAll('.stock-balance-report select.stock-balance-store-multiselect').forEach(function (el) {
            if (el.dataset.tomselectInitialized === 'true') return;
            var placeholder = el.getAttribute('data-placeholder') || 'Select';
            new TomSelect(el, {
                placeholder: placeholder,
                maxItems: null,
                maxOptions: 500,
                plugins: ['remove_button', 'dropdown_input'],
                sortField: { field: 'text', direction: 'asc' }
            });
            el.dataset.tomselectInitialized = 'true';
        });
    });
</script>
<script>
function printStockBalance() {
    const table = document.querySelector('.stock-balance-report .table-responsive table');
    if (!table) {
        window.print();
        return;
    }

    const title     = 'Stock Balance as of Till Date';
    const dateLabel = @json('As on ' . date('d-F-Y', strtotime($tillDate)));
    const storeName = @json($selectedStoreName ? $selectedStoreName : 'All Stores');

    // Build a new table so the header (with logos + meta + column headings)
    // lives inside <thead> and repeats on every printed page.
    const originalThead = table.querySelector('thead');
    const originalTbody = table.querySelector('tbody');
    const columnsCount  = 7; // current number of visible columns in the report

    const columnHeadHtml = originalThead ? originalThead.innerHTML : '';
    const bodyHtml       = originalTbody ? originalTbody.innerHTML : table.innerHTML;

    const printableTable = `
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th colspan="${columnsCount}">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="40">
                  <div>
                    <div class="brand-line-1">Government of India</div>
                    <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                  </div>
                </div>
                <div class="d-none d-print-block">
                  <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="40">
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center report-meta">
                <span><strong>${title}</strong></span>
                <span>${dateLabel}</span>
                <span><strong>Store:</strong> ${storeName}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
              </div>
            </th>
          </tr>
          ${columnHeadHtml}
        </thead>
        <tbody>
          ${bodyHtml}
        </tbody>
      </table>
    `;

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
      font-size: 11px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* LBSNAA watermark */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: url("https://www.lbsnaa.gov.in/admin_assets/images/logo.png") center center no-repeat;
      background-size: 220px 220px;
      opacity: 0.06;
      z-index: -1;
    }
    .lbsnaa-header { border-bottom: 2px solid #004a93; padding-bottom:.75rem; margin-bottom:1rem; }
    .brand-line-1 { font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#004a93; }
    .brand-line-2 { font-size:1.1rem; font-weight:700; text-transform:uppercase; color:#222; }
    .brand-line-3 { font-size:.8rem; color:#555; }
    .report-meta { font-size:.8rem; margin-bottom:.75rem; }
    .report-meta span { display:inline-block; margin-right:1.5rem; }
    table { width:100%; border-collapse:collapse; font-size: 9px; }
    th, td { padding:4px 6px; border:1px solid #dee2e6; }
    thead th { background:#f8f9fa; font-weight:600; }
    .table,
    .table * {
      white-space: normal !important;
    }
    .table-responsive {
      overflow: visible !important;
    }
    thead { display:table-header-group; }
    @page {
      size: A4 portrait;
      margin: 0.5in;
    }
    @media print {
      body { margin:0; }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="table-responsive">
      ${printableTable}
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
@endsection
