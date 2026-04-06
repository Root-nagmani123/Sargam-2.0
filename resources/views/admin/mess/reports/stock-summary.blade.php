@extends('admin.layouts.master')
@section('title', 'Stock Summary Report')
@section('setup_content')
@php
    /** @var array<int> $storeIds */
    $storeIds = $storeIds ?? [];
@endphp
<div class="container-fluid stock-summary-report pb-4">
    <div id="stock-summary-print-meta" class="d-none" hidden data-store-name="{{ e($selectedStoreName ?? ($storeType == 'main' ? 'Officer\'s Main Mess(Primary)' : 'All Sub Stores')) }}"></div>
    <x-breadcrum title="Stock Summary Report"></x-breadcrum>

    <div class="row no-print mb-3 mb-md-4">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="d-flex align-items-start gap-2 p-3 rounded-3 bg-primary-subtle bg-opacity-50 border border-primary-subtle ssr-info-banner">
                <span class="material-symbols-rounded text-primary flex-shrink-0" style="font-size:1.25rem;margin-top:1px;" aria-hidden="true">info</span>
                <p class="mb-0 text-primary-emphasis small lh-sm">
                    Opening, purchase, sale, and closing stock for the selected period and stores. Use filters to narrow the view, then print or export if needed.
                </p>
            </div>
        </div>
    </div>

    <!-- Filters Section (Hide on Print) -->
    <div class="card mb-4 border-0 shadow-sm rounded-3 overflow-hidden no-print">
        <div style="height:3px;background:linear-gradient(90deg,#0b4a7e 0%,#2980b9 50%,#0b4a7e 100%);" aria-hidden="true"></div>
        <div class="card-header bg-body-tertiary border-0 py-3 px-3 px-lg-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <div class="d-flex align-items-start gap-3">
                    <span class="d-none d-sm-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-primary-subtle text-primary p-2" aria-hidden="true">
                        <span class="material-symbols-rounded" style="font-size: 1.5rem;">tune</span>
                    </span>
                    <div>
                        <h5 class="mb-1 fw-semibold text-body">Filters</h5>
                        <p class="mb-0 small text-body-secondary">Refine results by date, store type, and store selection.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-3 p-lg-4">
            <form method="GET" action="{{ route('admin.mess.reports.stock-summary') }}">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="stock_summary_from_date" class="form-label small fw-semibold text-uppercase text-body-secondary mb-1">From Date</label>
                        <input type="date" name="from_date" id="stock_summary_from_date" class="form-control rounded-2"
                               value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="stock_summary_to_date" class="form-label small fw-semibold text-uppercase text-body-secondary mb-1">To Date</label>
                        <input type="date" name="to_date" id="stock_summary_to_date" class="form-control rounded-2"
                               value="{{ $toDate }}" required>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="store_type" class="form-label small fw-semibold text-uppercase text-body-secondary mb-1">Store Type</label>
                        <select name="store_type" id="store_type" class="form-select rounded-2 stock-summary-store-type" data-placeholder="Select Store Type">
                            <option value="main" @selected($storeType == 'main')>Main Store</option>
                            <option value="sub" @selected($storeType == 'sub')>Sub Store</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3{{ $storeType == 'main' ? '' : ' d-none' }}" id="main_store_div">
                        <label for="stock_summary_main_store" class="form-label small fw-semibold text-uppercase text-body-secondary mb-1">Main Store</label>
                        <select name="main_store_id[]" id="stock_summary_main_store" class="form-select form-select-sm rounded-2 stock-summary-store-multiselect" multiple data-placeholder="All Main Stores">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected($storeType === 'main' && in_array((int) $store->id, $storeIds, true))>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3{{ $storeType == 'sub' ? '' : ' d-none' }}" id="sub_store_div">
                        <label for="stock_summary_sub_store" class="form-label small fw-semibold text-uppercase text-body-secondary mb-1">Sub Store</label>
                        <select name="sub_store_id[]" id="stock_summary_sub_store" class="form-select form-select-sm rounded-2 stock-summary-store-multiselect" multiple data-placeholder="All Sub Stores">
                            @foreach($subStores as $subStore)
                                <option value="{{ $subStore->id }}" @selected($storeType === 'sub' && in_array((int) $subStore->id, $storeIds, true))>
                                    {{ $subStore->sub_store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 pt-3 mt-3 border-top border-light-subtle">
                    <button type="submit" class="btn btn-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">filter_list</span>
                        <span>Apply filters</span>
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary') }}" class="btn btn-outline-secondary btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">refresh</span>
                        <span>Reset</span>
                    </a>
                    <div class="vr d-none d-md-block text-secondary mx-1 align-self-stretch my-1" role="separator" aria-hidden="true"></div>
                    <div class="btn-group shadow-sm rounded-2" role="group" aria-label="Print or download PDF">
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-start-2" onclick="printStockSummary()" title="Print report or choose Save as PDF in print dialog">
                            <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;" aria-hidden="true">print</span>
                            <span>Print</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-end-2" title="Download PDF" data-stock-summary-pdf-url="{{ route('admin.mess.reports.stock-summary.pdf', request()->query()) }}" onclick="window.location.href=this.getAttribute('data-stock-summary-pdf-url')">
                            <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;" aria-hidden="true">picture_as_pdf</span>
                            <span>PDF</span>
                        </button>
                    </div>
                    <a href="{{ route('admin.mess.reports.stock-summary.excel', request()->query()) }}" class="btn btn-success btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3" title="Export to Excel">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">table_view</span>
                        <span>Export Excel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-lg-4">
            <!-- Report Header -->
            <div class="report-header text-center mb-4 rounded-3 p-4" style="background:linear-gradient(135deg,#f0f4f8 0%,#e8edf4 50%,#f0f4f8 100%);border:1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                    <span class="material-symbols-rounded text-primary" style="font-size:1.75rem;" aria-hidden="true">assessment</span>
                    <h4 class="fw-bold text-uppercase mb-0 text-body">Stock Summary Report</h4>
                </div>
                <div class="d-flex flex-column flex-sm-row flex-wrap align-items-center justify-content-center gap-2">
                    <span class="badge rounded-pill bg-white text-body-emphasis fw-normal px-3 py-2 shadow-sm border">
                        <span class="material-symbols-rounded align-middle me-1" style="font-size:1rem;" aria-hidden="true">date_range</span>
                        Period: {{ date('d-F-Y', strtotime($fromDate)) }} to {{ date('d-F-Y', strtotime($toDate)) }}
                    </span>
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-normal px-3 py-2 text-wrap text-start shadow-sm" style="max-width: min(100%, 42rem);">
                        <span class="material-symbols-rounded align-middle me-1" style="font-size:1rem;" aria-hidden="true">store</span>
                        <span class="fw-semibold">Store:</span>
                        {{ $selectedStoreName ?? ($storeType == 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores') }}
                    </span>
                </div>
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
                if (typeof window.initStockSummaryStickyHeader === 'function') {
                    window.initStockSummaryStickyHeader();
                }
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
        .table-responsive,
        .ssr-table-scroller {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            margin: 0 !important;
            border: none !important;
            background: transparent !important;
        }

        .ssr-sticky-head {
            display: none !important;
        }

        .stock-summary-report .ssr-card {
            box-shadow: none !important;
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

    /* —— Stock summary card + table (redesigned) —— */
    .stock-summary-report .ssr-card {
        border-radius: 0.75rem;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.07), 0 1px 3px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        background: #fff;
    }

    .stock-summary-report .ssr-card-topbar {
        height: 3px;
        background: linear-gradient(90deg, #0b4a7e 0%, #1a6fa0 100%);
    }

    .stock-summary-report .ssr-toolbar-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.65rem;
        background: #eef2f6;
        border: 1px solid #e2e8f0;
        color: #0b4a7e;
        align-items: center;
        justify-content: center;
    }

    .stock-summary-report .ssr-toolbar-icon .material-symbols-rounded {
        font-size: 1.25rem;
        line-height: 1;
    }

    .stock-summary-report .ssr-toolbar-title {
        font-size: clamp(0.94rem, 0.88rem + 0.28vw, 1.1rem);
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .stock-summary-report .ssr-toolbar-sub {
        margin-top: 0.12rem;
        font-size: 0.72rem;
        line-height: 1.35;
    }

    .stock-summary-report .ssr-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.32rem 0.7rem 0.32rem 0.55rem;
        border-radius: 2rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
    }

    .stock-summary-report .ssr-count-badge-icon {
        font-size: 1rem;
        color: #0b4a7e;
        opacity: 0.9;
    }

    .stock-summary-report .ssr-count-badge-label {
        font-variant-numeric: tabular-nums;
        color: #0f172a;
    }

    .stock-summary-report .ssr-count-badge-text {
        font-weight: 500;
        color: #64748b;
        font-size: 0.75rem;
    }

    .stock-summary-report .stock-summary-scroll-hint {
        color: #64748b;
        font-size: 0.72rem;
    }

    .stock-summary-report .ssr-scroll-hint-inner {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 2rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
    }

    .stock-summary-report .ssr-scroll-hint-icon {
        font-size: 1rem;
        opacity: 0.85;
    }

    .stock-summary-report .ssr-table-scroller {
        overflow-x: auto;
        overflow-y: auto;
        max-width: 100%;
        height: auto;
        min-height: 0;
        max-height: min(70vh, calc(100dvh - 13rem));
        margin: 0 0.75rem 0.75rem;
        border-radius: 0.5rem;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        border: 1px solid #e2e8f0;
        outline: none;
        scrollbar-gutter: stable;
        background: #f8fafc;
    }

    @media (min-width: 992px) {
        .stock-summary-report .ssr-table-scroller {
            margin: 0 1rem 1rem;
        }
    }

    @media (max-width: 767.98px) {
        .stock-summary-report .ssr-table-scroller {
            max-height: min(62vh, calc(100dvh - 17rem));
            margin-left: 0.5rem;
            margin-right: 0.5rem;
        }
    }

    @media (min-width: 1400px) {
        .stock-summary-report .ssr-table-scroller {
            max-height: min(74vh, calc(100dvh - 11rem));
        }
    }

    .stock-summary-report .ssr-table-scroller:focus-visible {
        box-shadow: 0 0 0 3px rgba(11, 74, 126, 0.28);
    }

    .stock-summary-report .ssr-table-scroller::-webkit-scrollbar {
        height: 10px;
        width: 10px;
    }

    .stock-summary-report .ssr-table-scroller::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }

    .stock-summary-report .ssr-table-scroller::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }

    .stock-summary-report .ssr-table-scroller::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .stock-summary-report .stock-summary-data-table {
        min-width: 1280px;
        --ssr-opening: #eef4fb;
        --ssr-purchase: #eefbf3;
        --ssr-sale: #fef8ee;
        --ssr-closing: #f3eefb;
    }

    .stock-summary-report .stock-summary-table-root .ssr-table,
    .stock-summary-report .ssr-sticky-head .table-fit {
        width: 100%;
        table-layout: fixed;
        font-size: clamp(11.5px, 0.7rem + 0.22vw, 12.5px) !important;
        border-collapse: separate;
        border-spacing: 0;
        --bs-table-bg: transparent;
        --bs-table-accent-bg: transparent;
    }

    /* Ensure sticky header clone uses same col widths */
    .stock-summary-report .ssr-sticky-head table col {
        /* widths inherited from colgroup in the source table clone */
    }

    /* Column width definitions for alignment */
    .stock-summary-report .ssr-table col:nth-child(1)  { width: 52px; }
    .stock-summary-report .ssr-table col:nth-child(2)  { width: 180px; }
    .stock-summary-report .ssr-table col:nth-child(3)  { width: 56px; }
    .stock-summary-report .ssr-table col:nth-child(4),
    .stock-summary-report .ssr-table col:nth-child(7),
    .stock-summary-report .ssr-table col:nth-child(10),
    .stock-summary-report .ssr-table col:nth-child(13) { width: 72px; }   /* Qty */
    .stock-summary-report .ssr-table col:nth-child(5),
    .stock-summary-report .ssr-table col:nth-child(8),
    .stock-summary-report .ssr-table col:nth-child(11),
    .stock-summary-report .ssr-table col:nth-child(14) { width: 76px; }   /* Rate */
    .stock-summary-report .ssr-table col:nth-child(6),
    .stock-summary-report .ssr-table col:nth-child(9),
    .stock-summary-report .ssr-table col:nth-child(12),
    .stock-summary-report .ssr-table col:nth-child(15) { width: 100px; }  /* Amount */

    .stock-summary-report .ssr-table > :not(caption) > * > * {
        border-bottom-color: #e2e8f0;
        box-shadow: none;
    }

    .stock-summary-report .ssr-thead th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: none;
        letter-spacing: 0.01em;
        border-color: #d8dee6;
        vertical-align: middle;
    }

    .stock-summary-report .ssr-thead .sss-th-fixed {
        background: #0b4a7e;
        color: #fff;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .stock-summary-report .ssr-thead .sss-grp,
    .stock-summary-report .ssr-thead .sss-sub {
        color: #fff;
    }

    /* Distinct color-coded group headers */
    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-opening,
    .stock-summary-report .ssr-thead .sss-sub.ssr-grp-opening {
        background: #1a73b5;
        border-color: rgba(255, 255, 255, 0.18);
    }

    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-purchase,
    .stock-summary-report .ssr-thead .sss-sub.ssr-grp-purchase {
        background: #198754;
        border-color: rgba(255, 255, 255, 0.18);
    }

    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-sale,
    .stock-summary-report .ssr-thead .sss-sub.ssr-grp-sale {
        background: #d97706;
        border-color: rgba(255, 255, 255, 0.18);
    }

    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-closing,
    .stock-summary-report .ssr-thead .sss-sub.ssr-grp-closing {
        background: #6f42c1;
        border-color: rgba(255, 255, 255, 0.18);
    }

    .stock-summary-report .ssr-thead .sss-grp {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
    }

    .stock-summary-report .ssr-thead .sss-sub {
        font-size: 0.6875rem;
        font-weight: 600;
        opacity: 0.95;
    }

    .stock-summary-report .ssr-table td,
    .stock-summary-report .ssr-table .sss-th-fixed,
    .stock-summary-report .ssr-table .sss-grp,
    .stock-summary-report .ssr-table .sss-sub {
        padding: 0.5rem 0.55rem;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .stock-summary-report .ssr-num {
        font-variant-numeric: tabular-nums;
    }

    .stock-summary-report .ssr-table tbody .ssr-cell-fixed {
        background: #fff;
    }

    .stock-summary-report .ssr-table tbody .ssr-grp-opening {
        background-color: var(--ssr-opening);
    }

    .stock-summary-report .ssr-table tbody .ssr-grp-purchase {
        background-color: var(--ssr-purchase);
    }

    .stock-summary-report .ssr-table tbody .ssr-grp-sale {
        background-color: var(--ssr-sale);
    }

    .stock-summary-report .ssr-table tbody .ssr-grp-closing {
        background-color: var(--ssr-closing);
    }

    /* Group column left borders for visual separation */
    .stock-summary-report .ssr-table td.ssr-grp-opening:first-of-type,
    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-opening:first-of-type {
        border-left: 2px solid #1a73b5;
    }

    .stock-summary-report .ssr-table tbody td.ssr-grp-purchase:nth-child(7),
    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-purchase {
        border-left: 2px solid #198754;
    }

    .stock-summary-report .ssr-table tbody td.ssr-grp-sale:nth-child(10),
    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-sale {
        border-left: 2px solid #d97706;
    }

    .stock-summary-report .ssr-table tbody td.ssr-grp-closing:nth-child(13),
    .stock-summary-report .ssr-thead .sss-grp.ssr-grp-closing {
        border-left: 2px solid #6f42c1;
    }

    .stock-summary-report .sss-body-row:nth-child(even) td.ssr-cell-fixed {
        background: #fafbfc;
    }

    .stock-summary-report .sss-body-row:nth-child(even) td.ssr-grp-opening {
        background-color: #e5eff8;
    }

    .stock-summary-report .sss-body-row:nth-child(even) td.ssr-grp-purchase {
        background-color: #e5f8ed;
    }

    .stock-summary-report .sss-body-row:nth-child(even) td.ssr-grp-sale {
        background-color: #fef3e0;
    }

    .stock-summary-report .sss-body-row:nth-child(even) td.ssr-grp-closing {
        background-color: #ece5f8;
    }

    .stock-summary-report .sss-body-row:hover td.ssr-cell-fixed {
        background-color: #f0f4f8;
    }

    .stock-summary-report .sss-body-row:hover td.ssr-grp-opening {
        background-color: #d6e8f7 !important;
    }

    .stock-summary-report .sss-body-row:hover td.ssr-grp-purchase {
        background-color: #d1f2e0 !important;
    }

    .stock-summary-report .sss-body-row:hover td.ssr-grp-sale {
        background-color: #fde9c9 !important;
    }

    .stock-summary-report .sss-body-row:hover td.ssr-grp-closing {
        background-color: #e0d4f5 !important;
    }

    /* Negative values in red */
    .stock-summary-report .ssr-negative {
        color: #dc3545 !important;
        font-weight: 600;
    }

    .stock-summary-report .ssr-table .ssr-item-name {
        font-size: 0.8125rem !important;
        color: #0f172a;
        text-align: left !important;
    }

    .stock-summary-report .ssr-amt {
        font-weight: 600;
        color: #0f172a;
    }

    .stock-summary-report .ssr-empty-state {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 0.5rem;
        border: 1px dashed #cbd5e1;
        margin: 0.5rem;
    }

    .stock-summary-report .ssr-empty-icon {
        font-size: 2.125rem;
        color: #94a3b8;
        display: block;
        margin: 0 auto 0.5rem;
    }

    .stock-summary-report .ssr-empty-title {
        font-weight: 600;
        color: #475569;
        font-size: 0.875rem;
    }

    .stock-summary-report .ssr-empty-text {
        font-size: 0.72rem;
    }

    .stock-summary-report .sss-totals-row td {
        padding-top: 0.7rem;
        padding-bottom: 0.7rem;
        font-size: 0.8125rem;
        font-weight: 700;
        border-top: 3px double #0b4a7e !important;
        border-bottom: none !important;
        color: #0f172a;
    }

    .stock-summary-report .sss-totals-row td.ssr-cell-fixed {
        background: #e8edf4 !important;
    }

    .stock-summary-report .sss-totals-row td.ssr-grp-opening {
        background: #dbe8f5 !important;
    }

    .stock-summary-report .sss-totals-row td.ssr-grp-purchase {
        background: #d4f0e1 !important;
    }

    .stock-summary-report .sss-totals-row td.ssr-grp-sale {
        background: #fde9c9 !important;
    }

    .stock-summary-report .sss-totals-row td.ssr-grp-closing {
        background: #e0d4f5 !important;
    }

    .stock-summary-report .sss-totals-row .ssr-totals-dash {
        font-weight: 500;
        color: #94a3b8;
    }

    .stock-summary-report .sss-totals-row .ssr-grp-opening .ssr-amt,
    .stock-summary-report .sss-totals-row td.ssr-grp-opening.ssr-amt {
        color: #1a73b5;
        font-size: 0.8125rem;
    }

    .stock-summary-report .sss-totals-row .ssr-grp-purchase .ssr-amt,
    .stock-summary-report .sss-totals-row td.ssr-grp-purchase.ssr-amt {
        color: #198754;
        font-size: 0.8125rem;
    }

    .stock-summary-report .sss-totals-row .ssr-grp-sale .ssr-amt,
    .stock-summary-report .sss-totals-row td.ssr-grp-sale.ssr-amt {
        color: #d97706;
        font-size: 0.8125rem;
    }

    .stock-summary-report .sss-totals-row .ssr-grp-closing .ssr-amt,
    .stock-summary-report .sss-totals-row td.ssr-grp-closing.ssr-amt {
        color: #6f42c1;
        font-size: 0.8125rem;
    }

    .stock-summary-report .sss-totals-row .ssr-totals-label {
        font-size: 0.8125rem !important;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .stock-summary-report .ssr-pagination-bar {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 0.8125rem;
    }

    .stock-summary-report .ssr-pagination-links .pagination {
        margin-bottom: 0;
        --bs-pagination-font-size: 0.8125rem;
    }

    @media (hover: none) and (pointer: coarse) {
        .stock-summary-report .ssr-table td,
        .stock-summary-report .ssr-table .sss-th-fixed,
        .stock-summary-report .ssr-table .sss-grp,
        .stock-summary-report .ssr-table .sss-sub {
            padding: 0.62rem 0.52rem;
        }
    }

    /* Sticky header clone (cloned THEAD) */
    .stock-summary-report .ssr-sticky-head {
        position: sticky;
        top: 0;
        z-index: 50;
        overflow: hidden;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .stock-summary-report .ssr-sticky-head table {
        width: 100%;
        table-layout: fixed;
        margin: 0;
    }

    .stock-summary-report .ssr-sticky-head th {
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.08);
    }

    .stock-summary-report .ssr-sticky-head .sss-th-fixed {
        background: #0b4a7e !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.22) !important;
    }

    /* Group headers in sticky clone — preserve distinct group colors */
    .stock-summary-report .ssr-sticky-head .sss-grp.ssr-grp-opening,
    .stock-summary-report .ssr-sticky-head .sss-sub.ssr-grp-opening {
        background: #1a73b5 !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .stock-summary-report .ssr-sticky-head .sss-grp.ssr-grp-purchase,
    .stock-summary-report .ssr-sticky-head .sss-sub.ssr-grp-purchase {
        background: #198754 !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .stock-summary-report .ssr-sticky-head .sss-grp.ssr-grp-sale,
    .stock-summary-report .ssr-sticky-head .sss-sub.ssr-grp-sale {
        background: #d97706 !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    .stock-summary-report .ssr-sticky-head .sss-grp.ssr-grp-closing,
    .stock-summary-report .ssr-sticky-head .sss-sub.ssr-grp-closing {
        background: #6f42c1 !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
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

    /* ── Enhanced UX: transitions & micro-interactions ── */
    .stock-summary-report .btn {
        transition: all 0.2s ease-in-out;
    }

    .stock-summary-report .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .stock-summary-report .btn:active:not(:disabled) {
        transform: translateY(0);
        box-shadow: none;
    }

    .stock-summary-report .form-control,
    .stock-summary-report .form-select {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .stock-summary-report .form-control:focus,
    .stock-summary-report .form-select:focus {
        border-color: #0b4a7e;
        box-shadow: 0 0 0 0.2rem rgba(11, 74, 126, 0.12);
    }

    .stock-summary-report > .card {
        transition: box-shadow 0.3s ease;
    }

    .stock-summary-report .badge {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .stock-summary-report .report-header .badge:hover {
        transform: scale(1.03);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stock-summary-report .sss-body-row td {
        transition: background-color 0.15s ease;
    }

    .stock-summary-report .ssr-info-banner {
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .stock-summary-report .ssr-count-badge {
        transition: background-color 0.2s ease, transform 0.15s ease;
        cursor: default;
    }

    .stock-summary-report .ssr-count-badge:hover {
        background: #e2e8f0;
        transform: scale(1.05);
    }

    @keyframes ssr-fade-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stock-summary-report > .card,
    .stock-summary-report > .row {
        animation: ssr-fade-in 0.35s ease-out both;
    }

    .stock-summary-report > .card:nth-child(2) {
        animation-delay: 0.08s;
    }

    .stock-summary-report > .card:nth-child(3) {
        animation-delay: 0.16s;
    }

    /* Enhanced report header */
    .stock-summary-report .report-header {
        position: relative;
        overflow: hidden;
    }

    .stock-summary-report .report-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 50%, rgba(11, 74, 126, 0.04) 0%, transparent 60%);
        pointer-events: none;
    }

    /* Better focus-visible for accessibility */
    .stock-summary-report .btn:focus-visible {
        outline: 2px solid #0b4a7e;
        outline-offset: 2px;
        box-shadow: 0 0 0 0.25rem rgba(11, 74, 126, 0.2);
    }

    .stock-summary-report .form-control:focus-visible,
    .stock-summary-report .form-select:focus-visible {
        outline: none;
        border-color: #0b4a7e;
        box-shadow: 0 0 0 0.25rem rgba(11, 74, 126, 0.18);
    }

    /* Enhanced filter card header */
    .stock-summary-report .card-header .material-symbols-rounded {
        transition: transform 0.3s ease;
    }

    .stock-summary-report .card-header:hover .material-symbols-rounded {
        transform: rotate(15deg);
    }

    /* Pagination hover effects */
    .stock-summary-report .ssr-pagination-links .page-link {
        transition: all 0.15s ease;
    }

    .stock-summary-report .ssr-pagination-links .page-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
</style>

<script>
    window.initStockSummaryStickyHeader = function () {
        try {
            const scroller = document.querySelector('.stock-summary-report .table-fit-single-view');
            const table = scroller ? scroller.querySelector('table.table-fit') : null;
            const thead = table ? table.querySelector('thead') : null;
            if (!scroller || !table || !thead) {
                return;
            }

            const old = scroller.querySelector('.ssr-sticky-head');
            if (old) {
                old.remove();
            }

            thead.style.display = '';

            const stickyWrap = document.createElement('div');
            stickyWrap.className = 'ssr-sticky-head';

            const stickyTable = document.createElement('table');
            stickyTable.className = table.className;

            // Clone colgroup for consistent column widths
            const colgroup = table.querySelector('colgroup');
            if (colgroup) {
                stickyTable.appendChild(colgroup.cloneNode(true));
            }

            stickyTable.appendChild(thead.cloneNode(true));
            stickyWrap.appendChild(stickyTable);

            scroller.insertBefore(stickyWrap, table);

            const syncWidths = function () {
                stickyTable.style.width = scroller.clientWidth + 'px';

                const origThs = thead.querySelectorAll('th');
                const stickyThs = stickyTable.querySelectorAll('th');
                if (!origThs.length || origThs.length !== stickyThs.length) {
                    return;
                }

                for (let i = 0; i < origThs.length; i++) {
                    const w = origThs[i].getBoundingClientRect().width;
                    stickyThs[i].style.width = w + 'px';
                    stickyThs[i].style.minWidth = w + 'px';
                    stickyThs[i].style.maxWidth = w + 'px';
                }

                const row1 = stickyTable.querySelector('thead tr:first-child');
                if (row1) {
                    row1.style.height = row1.getBoundingClientRect().height + 'px';
                }
            };

            syncWidths();
            thead.style.display = 'none';

            if (window._stockSummarySsrResize) {
                window.removeEventListener('resize', window._stockSummarySsrResize);
            }
            window._stockSummarySsrResize = function () {
                if (!document.body.contains(scroller)) {
                    window.removeEventListener('resize', window._stockSummarySsrResize);
                    window._stockSummarySsrResize = null;
                    return;
                }
                thead.style.display = '';
                syncWidths();
                thead.style.display = 'none';
            };
            window.addEventListener('resize', window._stockSummarySsrResize);

            if (scroller._ssrScrollBound) {
                scroller.removeEventListener('scroll', scroller._ssrOnScroll);
            }
            scroller._ssrOnScroll = function () {
                stickyTable.style.transform = 'translateX(' + (-scroller.scrollLeft) + 'px)';
            };
            scroller.addEventListener('scroll', scroller._ssrOnScroll);
            scroller._ssrScrollBound = true;
        } catch (e) {}
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initStockSummaryStickyHeader();
    });
    document.addEventListener('shown.bs.tab', function () {
        window.setTimeout(function () {
            if (typeof window.initStockSummaryStickyHeader === 'function') {
                window.initStockSummaryStickyHeader();
            }
        }, 150);
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
