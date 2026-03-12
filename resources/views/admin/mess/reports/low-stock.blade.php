@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid low-stock-report py-2">
    <x-breadcrum title="Low Stock Report"></x-breadcrum>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm rounded-4 no-print overflow-hidden">
        <div class="card-header bg-body-tertiary border-0 py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-semibold text-dark d-inline-flex align-items-center gap-2">
                        <span class="material-symbols-rounded icon-20">tune</span>
                        Filter Low Stock Items
                    </h5>
                    <p class="mb-0 text-body-secondary small">
                        Items where available stock is at or below alert quantity
                    </p>
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            <form method="GET" action="{{ route('admin.mess.reports.low-stock') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="form-label text-uppercase fw-semibold text-body-secondary mb-1">Till Date</label>
                        <input
                            type="date"
                            name="till_date"
                            class="form-control"
                            value="{{ $tillDate }}"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="form-label small text-uppercase fw-semibold text-body-secondary mb-1">Store</label>
                        <select name="store_id" class="form-select choices-select" data-placeholder="All Stores">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ ($storeId ?? null) == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                                <span class="material-symbols-rounded icon-18 me-1">filter_list</span>
                                Apply Filters
                            </button>
                            <a href="{{ route('admin.mess.reports.low-stock') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
                                <span class="material-symbols-rounded icon-18 me-1">refresh</span>
                                Reset
                            </a>
                            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" onclick="printLowStockReport()" title="Print report">
                                <span class="material-symbols-rounded icon-18 me-1">print</span>
                                Print
                            </button>
                            <a href="{{ route('admin.mess.reports.low-stock.pdf', request()->query()) }}" class="btn btn-danger d-inline-flex align-items-center" title="Download PDF">
                                <span class="material-symbols-rounded icon-18 me-1">picture_as_pdf</span>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="report-header text-center mb-4">
                <h4 class="fw-bold text-uppercase mb-1">Low Stock Report</h4>
                <p class="mb-2 text-body-secondary">
                    <span class="badge text-bg-light border text-dark fw-normal rounded-pill px-3 py-2">
                        Till: {{ date('d-F-Y', strtotime($tillDate)) }}
                    </span>
                </p>
                <p class="mb-0">
                    <span class="badge text-bg-primary fw-normal rounded-pill px-3 py-2">
                        <strong>Store:</strong> {{ $selectedStoreName ?? 'All Stores' }}
                    </span>
                </p>
            </div>

            <div class="card border rounded-4 overflow-hidden">
                <div class="card-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                    <span class="fw-semibold text-dark d-inline-flex align-items-center gap-2">
                        <span class="material-symbols-rounded icon-20">inventory_2</span>
                        Items at or below minimum stock
                    </span>
                    <span class="badge text-bg-light border text-dark rounded-pill px-3 py-2">
                        Total items: {{ is_array($items) ? count($items) : 0 }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="lowStockReportTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center text-uppercase small fw-semibold text-secondary" style="width: 70px;">Sr. No.</th>
                                <th class="text-uppercase small fw-semibold text-secondary" style="min-width: 220px;">Item Name</th>
                                <th class="text-center text-uppercase small fw-semibold text-secondary" style="min-width: 90px;">Unit</th>
                                <th class="text-end text-uppercase small fw-semibold text-secondary" style="min-width: 120px;">Available Qty</th>
                                <th class="text-end text-uppercase small fw-semibold text-secondary" style="min-width: 120px;">Alert Qty</th>
                                <th class="text-center text-uppercase small fw-semibold text-secondary" style="min-width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $row)
                                @php
                                    $remaining = $row['remaining_quantity'] ?? 0;
                                    $alert = $row['alert_quantity'] ?? 0;
                                @endphp
                                <tr class="{{ $remaining < $alert ? 'table-danger' : '' }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $row['item_name'] ?? '-' }}</td>
                                    <td class="text-center">{{ $row['unit'] ?? 'Unit' }}</td>
                                    <td class="text-end">{{ number_format($remaining, 2) }}</td>
                                    <td class="text-end">{{ number_format($alert, 2) }}</td>
                                    <td class="text-center">
                                        @if($remaining <= 0)
                                            <span class="badge text-bg-danger rounded-pill">Out of Stock</span>
                                        @elseif($remaining <= $alert)
                                            <span class="badge text-bg-warning rounded-pill">Below Minimum</span>
                                        @else
                                            <span class="badge text-bg-success rounded-pill">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-body-secondary py-5">
                                        <span class="material-symbols-rounded d-block mb-2 icon-24">inventory_2</span>
                                        No items are currently below their minimum stock level for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .low-stock-report .material-symbols-rounded {
        line-height: 1;
        vertical-align: middle;
    }

    .low-stock-report .icon-18 { font-size: 18px; }
    .low-stock-report .icon-20 { font-size: 20px; }
    .low-stock-report .icon-24 { font-size: 24px; }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            font-size: 12px;
            background: #fff !important;
        }

        .card {
            border: 0 !important;
            box-shadow: none !important;
        }

        table {
            font-size: 11px;
            page-break-inside: auto;
        }

        table thead {
            display: table-header-group;
        }

        th, td {
            padding: 6px !important;
        }

        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    }
</style>

{{-- Choices.js (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
    function printLowStockReport() {
        var table = document.getElementById('lowStockReportTable');
        if (!table) {
            window.print();
            return;
        }

        var tillDateText = @json(date('d-F-Y', strtotime($tillDate)));
        var storeNameText = @json($selectedStoreName ?? 'All Stores');
        var totalItems = @json(is_array($items) ? count($items) : 0);
        var logoUrl = @json(asset('images/lbsnaa_logo.jpg'));

        var printWindow = window.open('', '_blank', 'width=1200,height=900');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.write(`
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Low Stock Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <style>
    :root {
      --lbsnaa-blue: #004a93;
      --lbsnaa-light: #f4f8fc;
      --text: #1f2937;
      --border: #d7dee7;
    }
    * { box-sizing: border-box; }
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      color: var(--text);
      margin: 0;
      background: #fff;
      font-size: 12px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .page {
      padding: 12mm 10mm;
      position: relative;
    }
    .watermark::before {
      content: "";
      position: fixed;
      inset: 0;
      background: url("${logoUrl}") center center no-repeat;
      background-size: 200px;
      opacity: 0.05;
      z-index: 0;
      pointer-events: none;
    }
    .content {
      position: relative;
      z-index: 1;
    }
    .brand-header {
      border-bottom: 2px solid var(--lbsnaa-blue);
      padding-bottom: 10px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .brand-text {
      text-align: center;
      flex: 1;
      margin: 0 10px;
    }
    .brand-line-1 {
      font-size: 10px;
      color: #3f3f46;
      margin-bottom: 2px;
    }
    .brand-line-2 {
      font-size: 16px;
      font-weight: 700;
      color: var(--lbsnaa-blue);
      letter-spacing: 0.3px;
    }
    .brand-line-3 {
      font-size: 10px;
      color: #4b5563;
      margin-top: 2px;
    }
    .brand-logo {
      width: 58px;
      height: 58px;
      object-fit: contain;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: #fff;
      padding: 4px;
      flex-shrink: 0;
    }
    .report-meta {
      margin: 8px 0 12px;
      padding: 8px 10px;
      background: var(--lbsnaa-light);
      border: 1px solid var(--border);
      border-radius: 8px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      font-size: 11px;
    }
    .report-meta strong {
      color: #0f172a;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
      font-size: 11px;
    }
    .table th,
    .table td {
      border: 1px solid var(--border);
      padding: 6px 8px;
      vertical-align: middle;
    }
    .table thead th {
      background: #eef3f8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 0.2px;
    }
    .table tbody tr:nth-child(even) td {
      background: #fcfdff;
    }
    .table-danger td {
      background: #fce7e7 !important;
    }

    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 600;
      line-height: 1.2;
      border: 1px solid transparent;
      white-space: nowrap;
    }
    .text-bg-danger { background: #dc3545; color: #fff; }
    .text-bg-warning { background: #f59e0b; color: #1f2937; }
    .text-bg-success { background: #198754; color: #fff; }

    .text-center { text-align: center; }
    .text-end { text-align: right; }

    .footer {
      margin-top: 10px;
      font-size: 10px;
      color: #6b7280;
      text-align: center;
    }

    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    @media print {
      .page {
        padding: 0;
      }
      thead {
        display: table-header-group;
      }
    }
  </style>
</head>
<body>
  <div class="page watermark">
    <div class="content">
      <div class="brand-header">
        <img src="${logoUrl}" alt="LBSNAA Logo" class="brand-logo">
        <div class="brand-text">
          <div class="brand-line-1">Government of India</div>
          <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
          <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
        </div>
        <img src="${logoUrl}" alt="LBSNAA Logo" class="brand-logo">
      </div>

      <div class="report-meta">
        <span><strong>Report:</strong> Low Stock Report</span>
        <span><strong>Till Date:</strong> ${tillDateText}</span>
        <span><strong>Store:</strong> ${storeNameText}</span>
        <span><strong>Total Items:</strong> ${totalItems}</span>
        <span><strong>Printed On:</strong> ${new Date().toLocaleString()}</span>
      </div>

      ${table.outerHTML}

      <div class="footer">Officer's Mess LBSNAA Mussoorie</div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        window.print();
      }, 200);
    });
  <\/script>
</body>
</html>
        `);

        printWindow.document.close();
    }

    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Choices === 'undefined') return;

            document
                .querySelectorAll('.low-stock-report select.choices-select')
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
