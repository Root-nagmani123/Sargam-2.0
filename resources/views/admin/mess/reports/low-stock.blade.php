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
<div class="container-fluid low-stock-report py-3 py-md-4">
    <x-breadcrum title="Low Stock Report"></x-breadcrum>

    @php $lsExportQ = request()->query(); $lsExportQuery = $lsExportQ ? '?' . http_build_query($lsExportQ) : ''; @endphp
    {{-- Download / Print bar --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.reports.low-stock.pdf') }}{{ $lsExportQuery }}" class="btn ls-export-btn border-0"
            title="Download (PDF)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn ls-export-btn border-0" onclick="printLowStockReport()"
            title="Print (or Save as PDF)">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>
    <!-- Report Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden low-stock-report-card">
        <div class="card-body">
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 ls-filter-toolbar">
                    <form method="GET" action="{{ route('admin.mess.reports.low-stock') }}" id="lsFilterForm"
                        class="d-flex align-items-center gap-2 flex-wrap ls-filter-form">
                        <input type="hidden" name="refresh" value="1">
                        <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                        <div class="ls-filter-item">
                            <input type="date" id="till_date" name="till_date"
                                class="form-control ls-filter-date ls-auto-filter" value="{{ $tillDate }}"
                                aria-label="Till date">
                        </div>
                        <div class="ls-filter-item">
                            <select id="store_id" name="store_id[]"
                                class="form-select choices-select low-stock-store-multiselect ls-auto-filter" multiple
                                data-placeholder="All Stores">
                                @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds
                                    ?? [], true))>{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('admin.mess.reports.low-stock') }}" id="lsRemoveFilter"
                            class="programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center text-decoration-none"
                            title="Remove all filters">Remove Filter</a>
                    </form>
                    <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                        <button type="button" class="btn programme-dt-btn-columns" id="lsColumnsBtn"
                            data-bs-toggle="modal" data-bs-target="#lsColumnsModal" title="Show / hide columns">
                            <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                        </button>
                        <input type="search" id="lsSearch" class="form-control ls-search-input"
                            placeholder="Search item…" autocomplete="off">
                    </div>
                </div>
                {{-- Column visibility modal --}}
                <div class="modal fade" id="lsColumnsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-0 pb-2">
                                <h5 class="modal-title fw-bold">Column Visibility</h5><button type="button"
                                    class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-0">
                                <hr class="mt-0">
                                <div class="d-flex flex-column gap-2">
                                    <label
                                        class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input
                                            type="checkbox" class="form-check-input m-0 ls-col-toggle" data-col="unit"
                                            checked> <span>Unit</span></label>
                                </div>
                            </div>
                            <div class="modal-footer border-0"><button type="button"
                                    class="btn btn-outline-primary rounded-3 px-4"
                                    data-bs-dismiss="modal">Close</button></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="report-header text-center mb-4 pb-3 border-bottom border-body-secondary border-opacity-25">
                <h4 class="fw-bold text-uppercase mb-3 fs-5 text-body-emphasis">Low Stock Report</h4>
                <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
                    <span
                        class="badge text-bg-body-secondary text-body-emphasis fw-normal rounded-pill px-3 py-2 border border-body-secondary border-opacity-50">
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
                <div
                    class="card-header bg-body-tertiary border-bottom border-body-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4">
                    <span class="fw-semibold text-body-emphasis d-inline-flex align-items-center gap-2">
                        <span class="material-symbols-rounded icon-20 text-primary">inventory_2</span>
                        Items at or below minimum stock
                    </span>
                    <span class="badge text-bg-body-secondary text-body-emphasis rounded-pill px-3 py-2 border border-body-secondary border-opacity-50 fw-semibold" id="lowStockReportCount">
                        Total: 0 items
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table programme-dt-table align-middle mb-0" id="lowStockReportTable">
                        <thead class="text-nowrap">
                            <tr>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3">S. No.</th>
                                <th class="text-uppercase small fw-bold text-body-secondary py-3">Item Name</th>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 90px;">Unit</th>
                                <th class="text-end text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 120px;">Available Qty</th>
                                <th class="text-end text-uppercase small fw-bold text-body-secondary py-3" style="min-width: 120px;">Alert Qty</th>
                                <th class="text-center text-uppercase small fw-bold text-body-secondary py-3 pe-3" style="min-width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
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
    'ajaxJsonCallback' => 'lowStockReportOnDraw',
])
<script>
    function lowStockReportOnDraw(json) {
        var countEl = document.getElementById('lowStockReportCount');
        if (countEl) countEl.textContent = 'Total: ' + (json.recordsFiltered ?? 0) + ' items';

        var table = document.getElementById('lowStockReportTable');
        if (!table) return;
        table.querySelectorAll('tbody tr').forEach(function (row) {
            var statusCell = row.children[5];
            row.classList.remove('table-danger');
            if (statusCell && /Out of Stock|Below Minimum/.test(statusCell.textContent)) {
                row.classList.add('table-danger');
            }
        });
    }
</script>

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

    .low-stock-report .icon-16 {
        font-size: 16px;
    }

    .low-stock-report .icon-18 {
        font-size: 18px;
    }

    .low-stock-report .icon-20 {
        font-size: 20px;
    }

    .low-stock-report .icon-24 {
        font-size: 24px;
    }

    .low-stock-report .icon-48 {
        font-size: 48px;
    }

    .low-stock-report .low-stock-store-multiselect+.ts-wrapper {
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

        th,
        td {
            padding: 6px !important;
        }

        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    }

    /* ── New-design chrome: Download/Print bar + single-row filter toolbar (token-based per design.md) ── */
    .low-stock-report .ls-export-btn {
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        color: var(--ds-primary, #004a93);
        border-radius: var(--ds-radius, 4px);
        min-height: var(--ds-control-h, 40px);
        padding: 0 1rem;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .low-stock-report .ls-export-btn:hover {
        background: var(--ds-surface-2, #f8fafc);
        border-color: var(--ds-primary, #004a93);
    }

    .low-stock-report .ls-export-btn .material-symbols-rounded {
        font-size: 1.15rem;
    }

    .low-stock-report .ls-filter-toolbar,
    .low-stock-report .ls-filter-form {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .low-stock-report .ls-filter-item {
        flex-shrink: 0;
    }

    .low-stock-report .ls-filter-date {
        min-height: var(--ds-control-h, 40px);
        height: var(--ds-control-h, 40px);
        width: 11rem;
        border-radius: var(--ds-radius, 4px);
        border: 1px solid var(--ds-line, #e5e7eb);
        font-size: 0.85rem;
    }

    .low-stock-report .ls-filter-toolbar .form-select,
    .low-stock-report .ls-filter-toolbar .ts-wrapper {
        min-width: 11rem;
    }

    .low-stock-report .ls-search-input {
        min-height: var(--ds-control-h, 40px);
        height: var(--ds-control-h, 40px);
        width: 13rem;
        border-radius: var(--ds-radius, 4px);
        border: 1px solid var(--ds-line, #e5e7eb);
        font-size: 0.85rem;
    }

    /* On-screen: hide the print-oriented report header + inner card header (mock goes straight to the table) */
    .low-stock-report .report-header {
        display: none !important;
    }

    .low-stock-report .card .card-header.bg-body-tertiary {
        display: none !important;
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

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.TomSelect === 'undefined') return;

        document
            .querySelectorAll('.low-stock-report select.choices-select')
            .forEach(function(el) {
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

    // New-design toolbar: debounced auto-apply (GET reload) + client-side search + column visibility.
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('lsFilterForm');
        if (form) {
            var lsTimer = null;
            form.addEventListener('change', function(e) {
                if (!e.target || !e.target.classList || !e.target.classList.contains('ls-auto-filter'))
                    return;
                if (lsTimer) clearTimeout(lsTimer);
                lsTimer = setTimeout(function() {
                    form.submit();
                }, 500);
            });
        }
        var lsSearch = document.getElementById('lsSearch');
        if (lsSearch) {
            lsSearch.addEventListener('input', function() {
                var q = lsSearch.value.trim().toLowerCase();
                document.querySelectorAll('#lowStockReportTable tbody tr').forEach(function(tr) {
                    if (tr.querySelector('td[colspan]')) return;
                    tr.style.display = (!q || tr.textContent.toLowerCase().indexOf(q) !== -1) ?
                        '' : 'none';
                });
            });
        }
        document.querySelectorAll('.ls-col-toggle').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var col = cb.getAttribute('data-col');
                document.querySelectorAll('.low-stock-report [data-col="' + col + '"]').forEach(
                    function(el) {
                        el.style.display = cb.checked ? '' : 'none';
                    });
            });
        });
    });
    </script>
    @endsection