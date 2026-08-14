@extends('admin.layouts.master')
@php
    // Print/PDF branding uses LOCAL assets — the emblem used to be pulled from
    // upload.wikimedia.org, which renders as a broken image on an offline network.
    $messEmblemSrc = asset('admin_assets/images/logos/ashoka.png');
    $messLbsnaaLogoSrc = asset('admin_assets/images/logos/logo-web.png');
@endphp
@section('title', 'Low Stock Report')

@push('styles')
<style>
    /* Page chrome per docs/new-design-index-page.md §1–§4; all values from --ds-* tokens. */

    /* Download / Print bar (§1) */
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

    .low-stock-report .ls-export-btn .material-symbols-rounded { font-size: 1.15rem; }

    /* Report context (Till date / Store) — sits where the status pills go in §1 */
    .low-stock-report .ls-context-chip {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-1, 0.25rem);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        border-radius: var(--ds-radius, 4px);
        padding: 0.35rem 0.75rem;
        font-size: 0.82rem;
        color: var(--ds-ink, #1f2937);
    }

    .low-stock-report .ls-context-chip .material-symbols-rounded {
        font-size: 1rem;
        color: var(--ds-primary, #004a93);
    }

    /* Toolbar (§2) */
    .low-stock-report .ls-filter-toolbar,
    .low-stock-report .ls-filter-form {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .low-stock-report .ls-filter-item { flex-shrink: 0; }

    .low-stock-report .ls-filter-date {
        min-height: var(--ds-control-h, 40px);
        height: var(--ds-control-h, 40px);
        width: 11rem;
        border-radius: var(--ds-radius, 4px);
        border: 1px solid var(--ds-line, #e5e7eb);
        font-size: 0.85rem;
    }

    .low-stock-report .ls-filter-toolbar .form-select,
    .low-stock-report .ls-filter-toolbar .ts-wrapper { min-width: 11rem; }

    .low-stock-report .low-stock-store-multiselect + .ts-wrapper { min-height: var(--ds-control-h, 40px); }

    .low-stock-report .ls-colvis-item {
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }

    .low-stock-report .ls-colvis-item:hover {
        border-color: var(--ds-primary, #004384);
        background-color: rgba(0, 67, 132, .04);
    }

    /* The custom `dom` renders f/l/i/p into these wrappers; the global enhancer then
       moves them into the search slot + .programme-dt-footer, leaving empty boxes.
       (docs/design.md — "Applying the design to a mess-master index page", step 2.) */
    .low-stock-report .dt-top:empty,
    .low-stock-report .dt-foot:empty { display: none; }

    /* Status badges: soft tints, rounded-1 — the theme ships the *-subtle fills but
       not the text-*-emphasis colours, so tint them here (§3b). */
    .low-stock-report .ls-state {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: var(--ds-radius, 4px);
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }

    .low-stock-report .ls-state--out { background: #fee4e2; color: #b02a37; }
    .low-stock-report .ls-state--low { background: #fef0c7; color: #b54708; }
    .low-stock-report .ls-state--ok { background: #dcfae6; color: #146c43; }

    .low-stock-report .material-symbols-rounded {
        line-height: 1;
        vertical-align: middle;
    }

    .low-stock-report .icon-16 { font-size: 16px; }

    @media print {
        .no-print { display: none !important; }

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

        table thead { display: table-header-group; }

        th,
        td { padding: 6px !important; }

        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    }
</style>
@endpush

@section('content')
@include('admin.mess.reports.partials.report-styles')
<div class="container-fluid low-stock-report py-3 py-md-4">
    <x-breadcrum title="Low Stock Report" :showBack="false"></x-breadcrum>

    {{-- §1 — report context (left) · Download / Print (right), ABOVE the card --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="ls-context-chip">
                <span class="material-symbols-rounded" aria-hidden="true">event</span>
                Till: {{ date('d-F-Y', strtotime($tillDate)) }}
            </span>
            <span class="ls-context-chip">
                <span class="material-symbols-rounded" aria-hidden="true">store</span>
                {{ $selectedStoreName ?? 'All Stores' }}
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- href is the no-JS fallback; lsBuildExportUrl() rewrites it on click so the
                 PDF carries the live search term, sort and hidden columns. --}}
            <a href="{{ route('admin.mess.reports.low-stock.pdf', request()->query()) }}"
               id="lsDownloadBtn" class="btn ls-export-btn" title="Download (PDF)">
                <i class="material-symbols-rounded">download</i><span>Download</span>
            </a>
            <button type="button" class="btn ls-export-btn" id="lsPrintBtn" title="Print (or Save as PDF)">
                <i class="material-symbols-rounded">print</i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 low-stock-report-card">
        <div class="card-body">
            {{-- §2 — filters left, Columns + search right --}}
            <div class="d-flex align-items-center gap-2 mb-3 ls-filter-toolbar no-print">
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
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $selectedStoreIds ?? [], true))>{{ $store->store_name }}</option>
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
                    @include('mess.partials.search-toggle', ['tableId' => 'lowStockReportTable'])
                </div>
            </div>

            {{-- §3 — table panel --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="lowStockReportTable">
                        <thead>
                            <tr>
                                <th class="text-center">S. No.</th>
                                <th>Item Name</th>
                                <th class="text-center" style="min-width: 90px;">Unit</th>
                                <th class="text-end" style="min-width: 120px;">Available Qty</th>
                                <th class="text-end" style="min-width: 120px;">Alert Qty</th>
                                <th class="text-center" style="min-width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- §4A — pagination (left) + "Showing [N] of M items" (right), filled by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                data-dt-footer-for="lowStockReportTable"></div>
        </div>
    </div>

    {{-- Column visibility modal — drives the DataTable's own column().visible() --}}
    <div class="modal fade" id="lsColumnsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="d-flex flex-column gap-2">
                        <label class="ls-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 ls-col-toggle" data-col-index="2" checked> <span>Unit</span></label>
                        <label class="ls-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 ls-col-toggle" data-col-index="3" checked> <span>Available Qty</span></label>
                        <label class="ls-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 ls-col-toggle" data-col-index="4" checked> <span>Alert Qty</span></label>
                        <label class="ls-colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 ls-col-toggle" data-col-index="5" checked> <span>Status</span></label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
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
    'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
    'serverSide' => true,
    // The server sorts only these columns (ReportController@buildLowStockReportDatatableResponse
    // maps 1/3/4); leaving the others orderable showed a caret that silently sorted by
    // Item Name instead. Alignment is set here too — the JSON rows are plain strings,
    // so without a className the body cells ignored the header alignment.
    'serverSideColumnDefs' => [
        ['targets' => 0, 'orderable' => false, 'className' => 'text-center'],
        ['targets' => 2, 'orderable' => false, 'className' => 'text-center'],
        ['targets' => [3, 4], 'className' => 'text-end'],
        ['targets' => 5, 'orderable' => false, 'className' => 'text-center'],
    ],
    'ajaxUrlBase' => route('admin.mess.reports.low-stock', request()->query()),
    'ajaxJsonCallback' => 'lowStockReportOnDraw',
])

{{-- Tom Select (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
    function lowStockReportOnDraw(json) {
        var table = document.getElementById('lowStockReportTable');
        if (!table) return;
        // Critical rows keep their tint; the status text is the same string the
        // controller emits, so this stays in step with the badge.
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.classList.remove('table-danger');
            var statusCell = row.querySelector('.ls-state');
            if (statusCell && /Out of Stock|Below Minimum/.test(statusCell.textContent)) {
                row.classList.add('table-danger');
            }
        });
    }

    function lsDataTable() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return null;
        if (!window.jQuery.fn.DataTable.isDataTable('#lowStockReportTable')) return null;
        return window.jQuery('#lowStockReportTable').DataTable();
    }

    function lsHiddenColumnIndexes(dt) {
        var hidden = [];
        if (!dt) return hidden;
        dt.columns().every(function (idx) {
            if (!this.visible()) hidden.push(idx);
        });
        return hidden;
    }

    // Export URL = the filter form + whatever the grid is actually showing
    // (search term, sort, hidden columns) — docs/new-design-index-page.md §1.
    function lsBuildExportUrl(base) {
        var url = new URL(base, window.location.origin);
        var form = document.getElementById('lsFilterForm');
        if (form) {
            new FormData(form).forEach(function (value, key) {
                if (key === 'refresh' || value === '' || value === null) return;
                url.searchParams.append(key, value);
            });
        }

        var dt = lsDataTable();
        if (dt) {
            var term = (dt.search() || '').trim();
            if (term) url.searchParams.set('search', term);
            var order = dt.order();
            if (order && order.length) {
                url.searchParams.set('sort_col', order[0][0]);
                url.searchParams.set('sort_dir', order[0][1]);
            }
            var hidden = lsHiddenColumnIndexes(dt);
            if (hidden.length) url.searchParams.set('hide_cols', hidden.join(','));
        }

        return url.toString();
    }

    function printLowStockReport() {
        var dt = lsDataTable();
        var urlFn = (window.messMasterDataTableAjaxUrlByTable || {})['lowStockReportTable'];
        if (!dt || typeof urlFn !== 'function') {
            window.print();
            return;
        }

        // Print the WHOLE filtered result set, not just the page on screen.
        var ajaxData = dt.ajax.params();
        ajaxData.start = 0;
        ajaxData.length = -1;

        window.jQuery.ajax({
            url: urlFn(),
            type: 'GET',
            data: ajaxData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (json) {
            renderLowStockPrintWindow((json && json.data) || [], lsHiddenColumnIndexes(dt));
        }).fail(function () {
            window.print();
        });
    }

    function renderLowStockPrintWindow(rows, hiddenColumns) {
        hiddenColumns = hiddenColumns || [];
        var isHidden = function (idx) { return hiddenColumns.indexOf(idx) !== -1; };
        var alignFor = { 0: 'text-center', 2: 'text-center', 3: 'text-end', 4: 'text-end', 5: 'text-center' };

        var headEl = document.querySelector('#lowStockReportTable thead tr');
        var headHtml = '';
        if (headEl) {
            Array.prototype.forEach.call(headEl.children, function (th, idx) {
                if (isHidden(idx)) return;
                headHtml += '<th class="' + (alignFor[idx] || '') + '">' + th.textContent.trim() + '</th>';
            });
        }

        var bodyHtml = rows.map(function (row) {
            var cells = '';
            for (var i = 0; i < 6; i++) {
                if (isHidden(i)) continue;
                cells += '<td class="' + (alignFor[i] || '') + '">' + (row[i] || '') + '</td>';
            }
            return '<tr' + (/Out of Stock|Below Minimum/.test(row[5] || '') ? ' class="table-danger"' : '') + '>' + cells + '</tr>';
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

    /* rounded-1 (4px), never pills — sargam-app.css mandate */
    .ls-state {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 9px;
      font-weight: 600;
      line-height: 1.2;
      white-space: nowrap;
    }
    .ls-state--out { background: #fee4e2; color: #b02a37; }
    .ls-state--low { background: #fef0c7; color: #b54708; }
    .ls-state--ok { background: #dcfae6; color: #146c43; }

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
        <thead><tr>${headHtml}</tr></thead>
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
        if (typeof window.TomSelect !== 'undefined') {
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
                        sortField: { field: 'text', direction: 'asc' }
                    });

                    el.dataset.tomselectInitialized = 'true';
                });
        }

        // Toolbar: debounced auto-apply (GET reload) on any filter change.
        var form = document.getElementById('lsFilterForm');
        if (form) {
            var lsTimer = null;
            form.addEventListener('change', function (e) {
                if (!e.target || !e.target.classList || !e.target.classList.contains('ls-auto-filter')) return;
                if (lsTimer) clearTimeout(lsTimer);
                lsTimer = setTimeout(function () { form.submit(); }, 500);
            });
        }

        // Column visibility drives the DataTable itself — the old handler toggled
        // [data-col="…"] elements that this grid never rendered, so it did nothing.
        document.querySelectorAll('.ls-col-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var dt = lsDataTable();
                if (!dt) return;
                var idx = parseInt(cb.getAttribute('data-col-index'), 10);
                if (isNaN(idx)) return;
                dt.column(idx).visible(cb.checked);
            });
        });

        // Keep the checkboxes in step if the table is rebuilt.
        var columnsModal = document.getElementById('lsColumnsModal');
        if (columnsModal) {
            columnsModal.addEventListener('show.bs.modal', function () {
                var dt = lsDataTable();
                if (!dt) return;
                document.querySelectorAll('.ls-col-toggle').forEach(function (cb) {
                    var idx = parseInt(cb.getAttribute('data-col-index'), 10);
                    if (isNaN(idx)) return;
                    cb.checked = dt.column(idx).visible();
                });
            });
        }

        var downloadBtn = document.getElementById('lsDownloadBtn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                this.href = lsBuildExportUrl(@json(route('admin.mess.reports.low-stock.pdf')));
            });
        }

        var printBtn = document.getElementById('lsPrintBtn');
        if (printBtn) {
            printBtn.addEventListener('click', function (e) {
                e.preventDefault();
                printLowStockReport();
            });
        }
    });
</script>
@endsection
