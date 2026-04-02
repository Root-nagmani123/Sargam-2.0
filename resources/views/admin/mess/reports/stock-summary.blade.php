@extends('admin.layouts.master')
@section('title', 'Stock Summary Report')
@section('setup_content')
@php
    /** @var array<int> $storeIds */
    $storeIds = $storeIds ?? [];
@endphp
<div class="container-fluid stock-summary-report">
    <div id="stock-summary-print-meta" class="d-none" hidden
         data-store-name="{{ e($selectedStoreName ?? ($storeType == 'main' ? 'Officer\'s Main Mess(Primary)' : 'All Sub Stores')) }}"></div>
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
                        <label class="form-label fw-semibold text-uppercase mb-1 text-muted">Store Type</label>
                        <select name="store_type" id="store_type" class="form-select stock-summary-store-type" data-placeholder="Select Store Type">
                            <option value="main" @selected($storeType == 'main')>Main Store</option>
                            <option value="sub" @selected($storeType == 'sub')>Sub Store</option>
                        </select>
                    </div>
                    <div class="col-md-3{{ $storeType == 'main' ? '' : ' d-none' }}" id="main_store_div">
                        <label class="form-label fw-semibold text-uppercase mb-1 text-muted">Main Store</label>
                        <select name="main_store_id[]" id="stock_summary_main_store" class="form-select form-select-sm stock-summary-store-multiselect" multiple data-placeholder="All Main Stores">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected($storeType === 'main' && in_array((int) $store->id, $storeIds, true))>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3{{ $storeType == 'sub' ? '' : ' d-none' }}" id="sub_store_div">
                        <label class="form-label small fw-semibold text-uppercase mb-1 text-muted">Sub Store</label>
                        <select name="sub_store_id[]" id="stock_summary_sub_store" class="form-select form-select-sm stock-summary-store-multiselect" multiple data-placeholder="All Sub Stores">
                            @foreach($subStores as $subStore)
                                <option value="{{ $subStore->id }}" @selected($storeType === 'sub' && in_array((int) $subStore->id, $storeIds, true))>
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
                    <div class="btn-group shadow-sm" role="group" aria-label="Print or download PDF">
                    <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3" onclick="printStockSummary()" title="Print report or choose Save as PDF in print dialog">
                        <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;">print</span>
                        <span>Print</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3" title="Download PDF" data-stock-summary-pdf-url="{{ route('admin.mess.reports.stock-summary.pdf', request()->query()) }}" onclick="window.location.href=this.getAttribute('data-stock-summary-pdf-url')">
                        <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;">picture_as_pdf</span>
                        <span>PDF</span>
                    </button>
                    </div>

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
    <div id="stock-summary-table-wrap">
        @include('admin.mess.reports.partials.stock-summary-table', [
            'reportData' => $reportData,
            'reportPage' => $reportPage,
            'reportTotals' => $reportTotals,
        ])
    </div>
</div>
</div>


<script>
function printStockSummary() {
    var title = 'Stock Summary Report';
    var dateRange = 'Stock Summary Report Between {{ date("d-F-Y", strtotime($fromDate)) }} To {{ date("d-F-Y", strtotime($toDate)) }}';
    var metaEl = document.getElementById('stock-summary-print-meta');
    var storeName = metaEl && metaEl.getAttribute('data-store-name') ? metaEl.getAttribute('data-store-name') : '';

    function openPrintWithTable(table) {
        if (!table) {
            alert('Unable to find table data for printing. Please try again.');
            return;
        }
        var tbody = table.querySelector('tbody');
        if (!tbody || tbody.querySelectorAll('tr').length === 0) {
            alert('No data available to print. Please apply filters and ensure data is loaded.');
            return;
        }

        var tableForPrint = table.cloneNode(true);
        var printThead = tableForPrint.querySelector('thead');
        if (printThead) {
            printThead.style.display = '';
            printThead.removeAttribute('hidden');
        }

        var printWindow = window.open('about:blank', '_blank', 'width=1200,height=900');
        if (!printWindow) {
            window.print();
            return;
        }
        try {
            printWindow.opener = null;
        } catch (ignore) {}

        printWindow.document.open();
        printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 12px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      margin: 0;
      padding: 1rem;
    }
    .lbsnaa-header { 
      border-bottom: 2px solid #004a93; 
      padding-bottom:.75rem; 
      margin-bottom:1rem; 
    }
    .brand-line-1 { 
      font-size:0.9rem; 
      text-transform:uppercase; 
      letter-spacing:.06em; 
      color:#004a93; 
      font-weight: 600;
    }
    .brand-line-2 { 
      font-size:1.2rem; 
      font-weight:700; 
      text-transform:uppercase; 
      color:#222; 
    }
    .brand-line-3 { 
      font-size:0.9rem; 
      color:#555; 
    }
    .report-meta { 
      font-size:0.9rem; 
      margin-bottom:.75rem; 
      line-height: 1.6;
    }
    .report-meta span { 
      display:inline-block; 
      margin-right:1.5rem; 
    }
    .print-report-title { 
      font-size: 1.1rem; 
      font-weight: 600; 
    }
    table { 
      width:100%; 
      border-collapse:collapse; 
      font-size: 10px;
      margin-top: 0.5rem;
    }
    th, td { 
      padding:5px 6px; 
      border:1px solid #dee2e6; 
      vertical-align: middle;
    }
    thead th { 
      background:#f8f9fa !important; 
      font-weight:600;
      text-align: center;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    tbody td {
      background: #fff !important;
    }
    .table-primary {
      background: #cfe2ff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* Ensure text alignment is preserved */
    .text-end { text-align: right !important; }
    .text-center { text-align: center !important; }
    
    /* Ensure all table content is visible */
    .table-responsive {
      overflow: visible !important;
      max-height: none !important;
    }
    table { display: table !important; width: 100% !important; }
    thead { display: table-header-group !important; }
    tbody { display: table-row-group !important; }
    tr { display: table-row !important; page-break-inside: avoid; }
    th, td { display: table-cell !important; }
    
    @page {
      size: A4 landscape;
      margin: 0.5in;
    }
    @media print {
      body { margin:0; padding: 0.5rem; }
      thead { display: table-header-group !important; }
      tr { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row align-items-center lbsnaa-header">
      <div class="col-auto">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="42">
      </div>
      <div class="col">
        <div class="brand-line-1">Government of India</div>
        <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
        <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
      </div>
      <div class="col-auto">
        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="42" onerror="this.style.display='none'">
      </div>
    </div>

    <div class="mb-2">
      <h5 class="mb-1 print-report-title">${title}</h5>
      <div class="report-meta">
        <span><strong>Period:</strong> ${dateRange}</span><br>
        <span><strong>Store:</strong> ${storeName}</span>
        <span><strong>Printed:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
      </div>
    </div>

    <div class="table-responsive">
      ${tableForPrint.outerHTML}
    </div>
  </div>

  <script>
    window.addEventListener('load', function() { 
      setTimeout(function() {
        window.print(); 
      }, 250);
    });
  <\/script>
</body>
</html>`);
        printWindow.document.close();
    }

    var url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    url.searchParams.set('print_all', '1');

    fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var fetched = doc.querySelector('.table-fit-single-view > table.table-fit');
            if (fetched) {
                openPrintWithTable(fetched);
                return;
            }
            var table = document.querySelector('#stock-summary-table-wrap table.table-fit')
                || document.querySelector('#stock-summary-table-wrap table');
            var scroller = document.querySelector('.stock-summary-report .table-fit-single-view');
            if (!table && scroller) {
                table = scroller.querySelector(':scope > table.table-fit');
            }
            if (!table) {
                var wrapScroller = document.querySelector('#stock-summary-table-wrap .table-fit-single-view');
                table = wrapScroller ? wrapScroller.querySelector(':scope > table.table-fit') : null;
            }
            openPrintWithTable(table);
        })
        .catch(function () {
            var table = document.querySelector('#stock-summary-table-wrap table.table-fit')
                || document.querySelector('#stock-summary-table-wrap table');
            var scroller = document.querySelector('.stock-summary-report .table-fit-single-view');
            if (!table && scroller) {
                table = scroller.querySelector(':scope > table.table-fit');
            }
            if (!table) {
                var wrapScroller = document.querySelector('#stock-summary-table-wrap .table-fit-single-view');
                table = wrapScroller ? wrapScroller.querySelector(':scope > table.table-fit') : null;
            }
            openPrintWithTable(table);
        });
}

// AJAX pagination: only reload the table section, not whole page
document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('stock-summary-table-wrap');
    if (!container) return;

    function ajaxLoad(url) {
        if (!url) return;
        var targetUrl = url;
        if (!/[?&]ajax=1(?:&|$)/.test(url)) {
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            targetUrl = url + sep + 'ajax=1';
        }
        fetch(targetUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                container.innerHTML = html;
                hookLinks();
            })
            .catch(function (e) {
                console.error('Failed to load stock summary page via AJAX', e);
            });
    }

    function hookLinks() {
        container.querySelectorAll('.pagination a').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                ajaxLoad(this.href);
            });
        });
    }

    hookLinks();
});
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
            font-size: 14px; 
        }
        table { 
            font-size: 14px;
            page-break-inside: auto;
            width: 100% !important;
        }
        table thead {
            display: table-header-group !important;
        }
        table tbody {
            display: table-row-group !important;
        }
        table tr {
            display: table-row !important;
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table th, table td {
            display: table-cell !important;
            padding: 10px 12px !important; 
        }
        /* Override any overflow/height restrictions on print */
        .table-fit-single-view,
        .table-responsive {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
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
        font-size: 1.5rem;
    }
    
    .report-header p {
        color: #333;
        font-size: 1.125rem;
    }

    /* Fixed table height with inner scroll */
    .stock-summary-report .table-fit-single-view {
        overflow-x: auto;
        overflow-y: auto;
        max-width: 100%;
        height: 70vh;
        border-radius: .5rem;
    }

    /* px + !important: admin layout / .table often shrink rem; align with print (14px body) & PDF (~12–14px) */
    .stock-summary-report .stock-summary-table-root .table-fit,
    .stock-summary-report .ssr-sticky-head .table-fit {
        width: 100%;
        table-layout: fixed;
        font-size: 14px !important;
    }

    .stock-summary-report .stock-summary-table-root .table-fit th,
    .stock-summary-report .stock-summary-table-root .table-fit td,
    .stock-summary-report .ssr-sticky-head .table-fit th {
        padding: 0.65rem 0.8rem;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        vertical-align: middle;
        font-size: inherit;
    }

    .stock-summary-report .stock-summary-table-root .table-fit .ssr-item-name {
        font-size: 15px !important;
    }

    .stock-summary-report .stock-summary-table-root .table-fit .ssr-totals-label {
        font-size: 15px !important;
    }

    .stock-summary-report .stock-summary-table-meta {
        font-size: 14px !important;
    }

    /* Robust sticky header (cloned THEAD) */
    .stock-summary-report .ssr-sticky-head {
        position: sticky;
        top: 0;
        z-index: 50;
        background: #0b4a7e;
        overflow: hidden;
    }
    .stock-summary-report .ssr-sticky-head table {
        width: 100%;
        table-layout: fixed;
        margin: 0;
    }
    .stock-summary-report .ssr-sticky-head th {
        background: #0b4a7e !important;
        color: #fff !important;
        box-shadow: 0 1px 0 rgba(0,0,0,.08);
        border-color: rgba(255,255,255,.25) !important;
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
    document.addEventListener('DOMContentLoaded', function() {
        // Robust sticky header inside scroll container (works reliably with rowspan/colspan headers)
        try {
            const scroller = document.querySelector('.stock-summary-report .table-fit-single-view');
            const table = scroller ? scroller.querySelector('table.table-fit') : null;
            const thead = table ? table.querySelector('thead') : null;
            if (!scroller || !table || !thead) {
                return;
            }

            // Remove existing sticky header (if any)
            const old = scroller.querySelector('.ssr-sticky-head');
            if (old) old.remove();

            const stickyWrap = document.createElement('div');
            stickyWrap.className = 'ssr-sticky-head';

            const stickyTable = document.createElement('table');
            stickyTable.className = table.className;

            // Clone THEAD only
            stickyTable.appendChild(thead.cloneNode(true));
            stickyWrap.appendChild(stickyTable);

            // Insert sticky header before the real table
            scroller.insertBefore(stickyWrap, table);

            const syncWidths = function() {
                // Ensure sticky table matches the visible width of the scroller
                stickyTable.style.width = scroller.clientWidth + 'px';

                // Map widths from ORIGINAL thead th to sticky th
                const origThs = thead.querySelectorAll('th');
                const stickyThs = stickyTable.querySelectorAll('th');
                if (!origThs.length || origThs.length !== stickyThs.length) return;

                for (let i = 0; i < origThs.length; i++) {
                    const w = origThs[i].getBoundingClientRect().width;
                    stickyThs[i].style.width = w + 'px';
                    stickyThs[i].style.minWidth = w + 'px';
                    stickyThs[i].style.maxWidth = w + 'px';
                }

                // Also set the first row height so header looks consistent
                const row1 = stickyTable.querySelector('thead tr:first-child');
                if (row1) {
                    row1.style.height = row1.getBoundingClientRect().height + 'px';
                }
            };

            // Sync once before hiding original THEAD
            syncWidths();

            // Hide original THEAD so there is no blank space under sticky header
            thead.style.display = 'none';

            // Re-sync on resize (layout changes)
            window.addEventListener('resize', function() {
                // Temporarily show THEAD to measure widths again
                thead.style.display = '';
                syncWidths();
                thead.style.display = 'none';
            });

            // Sync horizontal scroll by translating sticky table
            scroller.addEventListener('scroll', function() {
                stickyTable.style.transform = 'translateX(' + (-scroller.scrollLeft) + 'px)';
            });
        } catch (e) {}
    });
</script>

{{-- Tom Select: store type (single); main/sub stores (multiselect; inactive side cleared + disabled so it is not submitted) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.TomSelect === 'undefined') return;

        var storeTypeSelect = document.getElementById('store_type');
        var mainStoreDiv = document.getElementById('main_store_div');
        var subStoreDiv = document.getElementById('sub_store_div');

        function initStoreMultiselect(el) {
            if (!el || el.tomselect) return;
            var placeholder = el.getAttribute('data-placeholder') || 'Select';
            new TomSelect(el, {
                create: false,
                maxItems: null,
                placeholder: placeholder,
                plugins: ['remove_button', 'dropdown_input'],
                sortField: { field: 'text', direction: 'asc' }
            });
        }

        function syncStoreMultiselects() {
            var isMain = storeTypeSelect && storeTypeSelect.value === 'main';
            var mainSel = document.getElementById('stock_summary_main_store');
            var subSel = document.getElementById('stock_summary_sub_store');
            if (mainSel) {
                mainSel.disabled = !isMain;
                if (isMain) {
                    initStoreMultiselect(mainSel);
                    if (mainSel.tomselect) mainSel.tomselect.enable();
                } else if (mainSel.tomselect) {
                    try { mainSel.tomselect.clear(true); } catch (e) {}
                    mainSel.tomselect.disable();
                }
            }
            if (subSel) {
                subSel.disabled = isMain;
                if (!isMain) {
                    initStoreMultiselect(subSel);
                    if (subSel.tomselect) subSel.tomselect.enable();
                } else if (subSel.tomselect) {
                    try { subSel.tomselect.clear(true); } catch (e) {}
                    subSel.tomselect.disable();
                }
            }
        }

        if (storeTypeSelect) {
            storeTypeSelect.addEventListener('change', function () {
                if (this.value === 'main') {
                    if (mainStoreDiv) mainStoreDiv.classList.remove('d-none');
                    if (subStoreDiv) subStoreDiv.classList.add('d-none');
                } else {
                    if (mainStoreDiv) mainStoreDiv.classList.add('d-none');
                    if (subStoreDiv) subStoreDiv.classList.remove('d-none');
                }
                syncStoreMultiselects();
            });
        }

        var typeEl = document.getElementById('store_type');
        if (typeEl && !typeEl.tomselect) {
            new TomSelect(typeEl, {
                create: false,
                maxItems: 1,
                allowEmptyOption: false,
                placeholder: typeEl.getAttribute('data-placeholder') || 'Select',
                plugins: ['dropdown_input'],
                sortField: { field: 'text', direction: 'asc' }
            });
        }

        syncStoreMultiselects();
    });
</script>
@endsection
