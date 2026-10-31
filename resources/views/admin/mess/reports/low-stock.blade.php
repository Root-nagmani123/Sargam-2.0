@extends('admin.layouts.master')
@php
    $messEmblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $messLbsnaaLogoSrc = asset('images/lbsnaa_logo.jpg');
    if (! is_file(public_path('images/lbsnaa_logo.jpg'))) {
        $messLbsnaaLogoSrc = is_file(public_path('images/lbsnaa_logo.png'))
            ? asset('images/lbsnaa_logo.png')
            : 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    }
@endphp
@section('title', 'Low Stock Report')
@section('content')
@include('admin.mess.reports.partials.report-styles')
<div class="container-fluid low-stock-report mess-master-page mess-report-page">
    <x-breadcrum title="Low Stock Report"></x-breadcrum>

    @include('admin.mess.reports.partials.report-export-bar', [
        'pdfUrl' => route('admin.mess.reports.low-stock.pdf', request()->query()),
        'printOnclick' => 'printLowStockReport()',
    ])

    <div class="ds-card mess-dt-card low-stock-report-card">
        <x-mess-master-toolbar table-id="lowStockReportTable" :columns="false">
            <form method="GET" action="{{ route('admin.mess.reports.low-stock') }}" class="mess-filterbar" aria-label="Low stock filters">
                <span class="mess-filterbar-label">Filter</span>
                <input type="date" id="till_date" name="till_date" class="form-control" value="{{ $tillDate }}" required aria-label="Till date">
                <div class="mess-filter-field">
                    <select id="store_id" name="store_id[]" class="form-select choices-select low-stock-store-multiselect"
                            multiple data-placeholder="Store" aria-label="Store">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds ?? [], true))>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-primary">Apply</button>
                @if(request()->hasAny(['till_date', 'store_id']))
                    <a href="{{ route('admin.mess.reports.low-stock') }}" class="btn btn-outline-danger">Remove Filter</a>
                @endif
            </form>
        </x-mess-master-toolbar>

        <div class="table-responsive">
            <table class="table align-middle mb-0" id="lowStockReportTable">
                <thead class="text-nowrap">
                    <tr>
                        <th>S. No.</th>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th>Available Qty</th>
                        <th>Alert Qty</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'lowStockReportTable',
    'searchPlaceholder' => 'Search items...',
    'orderColumn' => 1,
    'actionColumnIndex' => -1,
    'infoLabel' => 'items',
    'ordering' => true,
    'columnManager' => false,
    'colReorder' => false,
    'searchHighlight' => false,
    'pageLength' => 25,
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.reports.low-stock', request()->query()),
])

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
        font-size: 0.82rem;
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
        font-size: 0.95rem;
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
        if (!tableEl || typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable.isDataTable('#lowStockReportTable')) {
            window.print();
            return;
        }

        var dtApi = window.jQuery('#lowStockReportTable').DataTable();
        var urlFn = (window.messMasterDataTableAjaxUrlByTable || {})['lowStockReportTable'];
        if (typeof urlFn !== 'function') {
            window.print();
            return;
        }

        var ajaxData = dtApi.ajax.params();
        ajaxData.start = 0;
        ajaxData.length = -1;

        window.jQuery.ajax({
            url: urlFn(),
            type: 'GET',
            data: ajaxData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (json) {
            renderLowStockPrintWindow((json && json.data) || []);
        }).fail(function () {
            window.print();
        });
    }

    function renderLowStockPrintWindow(rows) {
        var theadHtml = document.querySelector('#lowStockReportTable thead') ? document.querySelector('#lowStockReportTable thead').innerHTML : '';
        var bodyHtml = rows.map(function (row) {
            return '<tr' + (/Out of Stock|Below Minimum/.test(row[5] || '') ? ' class="table-danger"' : '') + '>' +
                '<td class="text-center">' + row[0] + '</td>' +
                '<td>' + row[1] + '</td>' +
                '<td class="text-center">' + row[2] + '</td>' +
                '<td class="text-end">' + row[3] + '</td>' +
                '<td class="text-end">' + row[4] + '</td>' +
                '<td class="text-center">' + row[5] + '</td>' +
                '</tr>';
        }).join('');

        var tillDateText = @json(date('d-F-Y', strtotime($tillDate)));
        var storeNameText = @json($selectedStoreName ?? 'All Stores');
        var totalItems = rows.length;
        var outOfStockCount = 0;
        var belowMinCount = 0;
        rows.forEach(function (row) {
            var statusText = row[5] || '';
            if (/Out of Stock/.test(statusText)) {
                outOfStockCount++;
            } else if (/Below Minimum/.test(statusText)) {
                belowMinCount++;
            }
        });
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

    .badge, .mess-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 9px;
      font-weight: 600;
      line-height: 1.2;
      white-space: nowrap;
    }
    .text-bg-danger, .mess-status--danger { background: #dc3545; color: #fff; }
    .text-bg-warning, .mess-status--warning { background: #f59e0b; color: #1f2937; }
    .text-bg-success, .mess-status--success { background: #198754; color: #fff; }

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

      <table class="low-stock-data">
        <thead>${theadHtml}</thead>
        <tbody>${bodyHtml}</tbody>
      </table>

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
