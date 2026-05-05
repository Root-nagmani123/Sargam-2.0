@extends('admin.layouts.master')
@php
    $messEmblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $messLbsnaaLogoSrc = asset('images/lbsnaa_logo.jpg');
    if (! is_file(public_path('images/lbsnaa_logo.jpg'))) {
        $messLbsnaaLogoSrc = is_file(public_path('images/lbsnaa_logo.png'))
            ? asset('images/lbsnaa_logo.png')
            : 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    }
    $printOutOfStock = 0;
    $printBelowMin = 0;
    foreach (is_array($items ?? null) ? $items : [] as $_row) {
        $_rem = (float) ($_row['remaining_quantity'] ?? 0);
        $_alt = (float) ($_row['alert_quantity'] ?? 0);
        if ($_rem <= 0) {
            $printOutOfStock++;
        } elseif ($_rem <= $_alt) {
            $printBelowMin++;
        }
    }
@endphp
@section('title', 'Low Stock Report')
@section('content')
<div class="container-fluid low-stock-report py-3 py-md-4">
    <x-breadcrum title="Low Stock Report"></x-breadcrum>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow rounded-4 no-print">
        <div class="card-header bg-body-secondary border-0 py-3 px-3 px-md-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center">
                        <span class="material-symbols-rounded icon-24 text-primary">tune</span>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-semibold text-body-emphasis d-inline-flex align-items-center gap-2">
                            Filter Low Stock Items
                        </h5>
                        <p class="mb-0 text-body-secondary small mt-1 opacity-90">
                            Items where available stock is at or below alert quantity
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0 px-3 px-md-4 pb-4">
            <form method="GET" action="{{ route('admin.mess.reports.low-stock') }}">
                <div class="row g-3 g-md-4 align-items-end">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="till_date" class="form-label small text-uppercase fw-semibold text-body-secondary mb-1">Till Date</label>
                        <input
                            type="date"
                            id="till_date"
                            name="till_date"
                            class="form-control"
                            value="{{ $tillDate }}"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="store_id" class="form-label small text-uppercase fw-semibold text-body-secondary mb-1">Store</label>
                        <select
                            id="store_id"
                            name="store_id[]"
                            class="form-select choices-select low-stock-store-multiselect"
                            multiple
                            data-placeholder="All Stores"
                        >
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds ?? [], true))>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end pt-md-2">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded icon-18">filter_list</span>
                                Apply Filters
                            </button>
                            <a href="{{ route('admin.mess.reports.low-stock') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded icon-18">refresh</span>
                                Reset
                            </a>
                            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" onclick="printLowStockReport()" title="Print report">
                                <span class="material-symbols-rounded icon-18">print</span>
                                Print
                            </button>
                            <a href="{{ route('admin.mess.reports.low-stock.pdf', request()->query()) }}" class="btn btn-danger d-inline-flex align-items-center gap-1" title="Download PDF">
                                <span class="material-symbols-rounded icon-18">picture_as_pdf</span>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden low-stock-report-card">
        <div class="card-body p-4 p-md-5">

            <div class="report-header text-center mb-4 pb-3 border-bottom border-body-secondary border-opacity-25">
                <h4 class="fw-bold text-uppercase mb-3 fs-5 text-body-emphasis">Low Stock Report</h4>
                <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
                    <span class="badge text-bg-body-secondary text-body-emphasis fw-normal rounded-pill px-3 py-2 border border-body-secondary border-opacity-50">
                        <span class="material-symbols-rounded icon-16 align-text-bottom me-1">event</span>
                        Till: {{ date('d-F-Y', strtotime($tillDate)) }}
                    </span>
                    <span class="badge text-bg-primary fw-normal rounded-pill px-3 py-2">
                        <span class="material-symbols-rounded icon-16 align-text-bottom me-1">store</span>
                        {{ $selectedStoreName ?? 'All Stores' }}
                    </span>
                </div>
            </div>

            <div class="card border border-body-secondary border-opacity-25 rounded-4 overflow-hidden">
                <div class="card-header bg-body-tertiary border-bottom border-body-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4">
                    <span class="fw-semibold text-body-emphasis d-inline-flex align-items-center gap-2">
                        <span class="material-symbols-rounded icon-20 text-primary">inventory_2</span>
                        Items at or below minimum stock
                    </span>
                    <span class="badge text-bg-body-secondary text-body-emphasis rounded-pill px-3 py-2 border border-body-secondary border-opacity-50 fw-semibold">
                        Total: {{ is_array($items) ? count($items) : 0 }} items
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" id="lowStockReportTable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3 ps-3" style="width: 70px;">Sr. No.</th>
                                <th class="text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 220px;">Item Name</th>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 90px;">Unit</th>
                                <th class="text-end text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 120px;">Available Qty</th>
                                <th class="text-end text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 120px;">Alert Qty</th>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3 pe-3" style="min-width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $row)
                                @php
                                    $remaining = $row['remaining_quantity'] ?? 0;
                                    $alert = $row['alert_quantity'] ?? 0;
                                @endphp
                                <tr class="{{ $remaining < $alert ? 'table-danger' : '' }}">
                                    <td class="text-center ps-3">{{ $index + 1 }}</td>
                                    <td class="fw-semibold text-body-emphasis">{{ $row['item_name'] ?? '-' }}</td>
                                    <td class="text-center">{{ $row['unit'] ?? 'Unit' }}</td>
                                    <td class="text-end fw-medium">{{ number_format($remaining, 2) }}</td>
                                    <td class="text-end">{{ number_format($alert, 2) }}</td>
                                    <td class="text-center pe-3">
                                        @if($remaining <= 0)
                                            <span class="badge text-bg-danger rounded-pill px-3 py-2">Out of Stock</span>
                                        @elseif($remaining <= $alert)
                                            <span class="badge text-bg-warning text-dark rounded-pill px-3 py-2">Below Minimum</span>
                                        @else
                                            <span class="badge text-bg-success rounded-pill px-3 py-2">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-body-secondary py-5 px-4">
                                        <div class="d-inline-flex flex-column align-items-center gap-2">
                                            <span class="material-symbols-rounded icon-48 text-body-tertiary opacity-75">inventory_2</span>
                                            <span class="small fw-medium">No items are currently below their minimum stock level for the selected filters.</span>
                                        </div>
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
    .low-stock-report .mess-official-header {
        border-bottom: 2px solid #004a93;
        padding-bottom: 10px;
    }
    .low-stock-report .mess-official-header-table td {
        border: 0;
        vertical-align: middle;
    }
    .low-stock-report .mess-official-emblem-img {
        width: 42px;
        height: 42px;
        object-fit: contain;
        display: block;
    }
    .low-stock-report .mess-official-seal-img {
        width: auto;
        max-width: 160px;
        max-height: 72px;
        object-fit: contain;
        display: inline-block;
    }
    .low-stock-report .mess-official-line-1 {
        font-size: 0.7rem;
        color: #004a93;
        letter-spacing: 0.04em;
        line-height: 1.3;
    }
    .low-stock-report .mess-official-line-2 {
        font-size: 1rem;
        color: #111;
        line-height: 1.25;
        margin-top: 2px;
    }
    .low-stock-report .mess-official-line-3 {
        font-size: 0.8rem;
        color: #5c6370;
        line-height: 1.3;
        margin-top: 2px;
    }

    .low-stock-report .material-symbols-rounded {
        line-height: 1;
        vertical-align: middle;
    }

    .low-stock-report .icon-16 { font-size: 16px; }
    .low-stock-report .icon-18 { font-size: 18px; }
    .low-stock-report .icon-20 { font-size: 20px; }
    .low-stock-report .icon-24 { font-size: 24px; }
    .low-stock-report .icon-48 { font-size: 48px; }

    .low-stock-report .low-stock-store-multiselect + .ts-wrapper {
        min-height: 38px;
    }

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

{{-- Tom Select (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    function printLowStockReport() {
        var tableEl = document.getElementById('lowStockReportTable');
        if (!tableEl) {
            window.print();
            return;
        }

        var table = tableEl.cloneNode(true);
        table.removeAttribute('id');
        table.classList.add('low-stock-data');
        table.classList.remove('table', 'table-hover', 'table-striped', 'align-middle', 'mb-0');

        var tillDateText = @json(date('d-F-Y', strtotime($tillDate)));
        var storeNameText = @json($selectedStoreName ?? 'All Stores');
        var totalItems = @json(is_array($items) ? count($items) : 0);
        var outOfStockCount = @json($printOutOfStock);
        var belowMinCount = @json($printBelowMin);
        var emblemSrc = @json($messEmblemSrc);
        var lbsnaaLogoSrc = @json($messLbsnaaLogoSrc);

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
    * { box-sizing: border-box; }
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      color: #222;
      margin: 0;
      background: #fff;
      font-size: 11pt;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .page { padding: 12mm 10mm; position: relative; }
    .content { position: relative; }

    /* Match mess PDF (stock-purchase-details / low-stock-pdf) */
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
      font-size: 8pt;
      color: #004a93;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 600;
    }
    .lbsnaa-brand-line-2 {
      font-size: 13pt;
      color: #222;
      font-weight: 700;
      text-transform: uppercase;
      margin-top: 2px;
    }
    .lbsnaa-brand-line-3 {
      font-size: 10pt;
      color: #555;
      margin-top: 2px;
    }
    .header-img-left { width: 34px; height: 34px; object-fit: contain; display: block; }
    .header-img-right { width: 165px; max-width: 100%; height: auto; object-fit: contain; }

    .report-header-block {
      text-align: center;
      margin-bottom: 14px;
      padding-bottom: 10px;
      border-bottom: 1px solid #dee2e6;
    }
    .report-title-center {
      font-size: 14pt;
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
      font-size: 10pt;
      display: inline-block;
    }
    .report-store-line {
      font-size: 10pt;
      font-weight: 600;
      margin-top: 8px;
      color: #212529;
    }
    .text-muted { color: #6c757d; font-weight: 600; }

    .report-meta-print {
      font-size: 9pt;
      margin: 10px 0 12px;
      line-height: 1.45;
      text-align: left;
    }
    .report-meta-print .meta-line { margin-bottom: 4px; }

    table.low-stock-data {
      width: 100%;
      border-collapse: collapse;
      font-size: 9pt;
      margin-bottom: 10px;
    }
    table.low-stock-data th,
    table.low-stock-data td {
      padding: 5px 8px;
      border: 1px solid #dee2e6;
      vertical-align: middle;
    }
    table.low-stock-data thead th {
      background: #d3d6d9;
      font-weight: 600;
      text-align: left;
    }
    table.low-stock-data thead th.text-center { text-align: center; }
    table.low-stock-data thead th.text-end { text-align: right; }
    table.low-stock-data tbody tr:nth-child(even) td { background: #fafbfc; }
    table.low-stock-data tbody tr.table-danger td { background: #fdeaea !important; }

    .text-center { text-align: center; }
    .text-end { text-align: right; }

    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 9px;
      font-weight: 600;
      line-height: 1.2;
      white-space: nowrap;
    }
    .text-bg-danger { background: #dc3545; color: #fff; }
    .text-bg-warning { background: #f59e0b; color: #1f2937; }
    .text-bg-success { background: #198754; color: #fff; }

    .footer {
      border-top: 1px solid #dee2e6;
      margin-top: 8px;
      padding-top: 6px;
      font-size: 8pt;
      color: #666;
      text-align: center;
    }

    @page { size: A4 portrait; margin: 12mm; }
    @media print {
      .page { padding: 0; }
      thead { display: table-header-group; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="content">
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
              <img src="${lbsnaaLogoSrc}" alt="LBSNAA" class="header-img-right">
            </td>
          </tr>
        </table>
      </div>

      <div class="report-header-block">
        <h1 class="report-title-center">Low Stock Report</h1>
        <div class="report-date-bar">Low Stock Report As Of ${tillDateText}</div>
        <div class="report-store-line"><span class="text-muted">Store:</span> ${storeNameText}</div>
      </div>

      <div class="report-meta-print">
        <div class="meta-line"><strong>Printed on:</strong> ${new Date().toLocaleString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true })}</div>
        ${totalItems > 0 ? '<div class="meta-line"><strong>Summary:</strong> Total items ' + totalItems + ' | Out of stock ' + outOfStockCount + ' | Below minimum ' + belowMinCount + '</div>' : ''}
      </div>

      ${table.outerHTML}

      <div class="footer"><small>Officer's Mess LBSNAA Mussoorie — Low Stock Report</small></div>
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

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.TomSelect === 'undefined') return;

        document
            .querySelectorAll('.low-stock-report select.choices-select')
            .forEach(function (el) {
                if (el.dataset.tomselectInitialized === 'true') return;

                var placeholder = el.getAttribute('data-placeholder') || 'Select';
                var isMultiple = el.hasAttribute('multiple');

                new TomSelect(el, {
                    create: false,
                    allowEmptyOption: !isMultiple,
                    placeholder: placeholder,
                    maxItems: isMultiple ? null : 1,
                    maxOptions: 500,
                    plugins: isMultiple ? ['remove_button', 'dropdown_input'] : ['dropdown_input'],
                    sortField: {
                        field: 'text',
                        direction: 'asc'
                    }
                });

                el.dataset.tomselectInitialized = 'true';
            });
    });
</script>
@endsection
