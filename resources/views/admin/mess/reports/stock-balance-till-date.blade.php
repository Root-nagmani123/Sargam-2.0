@extends('admin.layouts.master')
@section('title', 'Stock Balance as of Till Date')
@section('content')
@php
    /** @var array<int> $storeIds */
    $storeIds = isset($storeIds) ? $storeIds : [];
    $printLogoSrc = asset('images/lbsnaa_logo.jpg');
    if (!is_file(public_path('images/lbsnaa_logo.jpg'))) {
        $printLogoSrc = is_file(public_path('images/lbsnaa_logo.png'))
            ? asset('images/lbsnaa_logo.png')
            : 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    }
@endphp
@include('admin.mess.reports.partials.report-styles')
<div class="container-fluid stock-balance-report min-vh-100 d-flex flex-column">
    <x-breadcrum title="Stock Balance as of Till Date"></x-breadcrum>
    @php $sbExportQ = request()->query(); $sbExportQuery = $sbExportQ ? '?' . http_build_query($sbExportQ) : ''; @endphp
    {{-- Download / Print bar --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.reports.stock-balance-till-date.excel') }}{{ $sbExportQuery }}" class="btn sb-export-btn border-0" title="Download (Excel)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn sb-export-btn border-0" onclick="printStockBalance()" title="Print (or Save as PDF)">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>
<div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column min-h-0">
    <div class="card-body d-flex flex-column flex-grow-1 min-h-0">
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 sb-filter-toolbar">
                <form method="GET" action="{{ route('admin.mess.reports.stock-balance-till-date') }}" id="sbFilterForm" class="d-flex align-items-center gap-2 flex-wrap sb-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                    <div class="sb-filter-item">
                        <input type="date" name="till_date" id="till_date" class="form-control sb-filter-date sb-auto-filter" value="{{ $tillDate }}" aria-label="Till date">
                    </div>
                    <div class="sb-filter-item">
                        <select name="store_id[]" id="store_id" class="form-select stock-balance-store-multiselect sb-auto-filter" multiple data-placeholder="All Stores">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $storeIds, true))>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="search" id="sbSearchHidden" value="{{ request('search') }}">
                    <input type="hidden" name="per_page" id="sbPerPageHidden" value="{{ (int) request('per_page', 10) }}">
                    <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" id="sbRemoveFilter" class="programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center text-decoration-none" title="Remove all filters">Remove Filter</a>
                </form>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <button type="button" class="btn programme-dt-btn-columns" id="sbColumnsBtn" data-bs-toggle="modal" data-bs-target="#sbColumnsModal" title="Show / hide columns">
                        <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                    </button>
                    <input type="search" id="sbSearch" class="form-control sb-search-input" placeholder="Search item…" autocomplete="off">
                </div>
            </div>
            {{-- Column visibility modal --}}
            <div class="modal fade" id="sbColumnsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0 pb-2"><h5 class="modal-title fw-bold">Column Visibility</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body pt-0"><hr class="mt-0"><div class="d-flex flex-column gap-2">
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 sb-col-toggle" data-col="code" checked> <span>Item Code</span></label>
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 sb-col-toggle" data-col="unit" checked> <span>Unit</span></label>
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 sb-col-toggle" data-col="rate" checked> <span>Avg Rate</span></label>
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0"><input type="checkbox" class="form-check-input m-0 sb-col-toggle" data-col="amt" checked> <span>Amount</span></label>
                    </div></div>
                    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button></div>
                </div></div>
            </div>
        </div>
        <!-- Report Heading -->
        <div class="report-header text-center mb-4 pb-3 border-bottom border-body-secondary border-opacity-25">
            <h4 class="fw-bold text-uppercase mb-3 fs-5 text-body-emphasis">Stock Balance as of Till Date</h4>
            @if(config('app.debug') && isset($reportTimingMs))
                <p class="small text-body-secondary mb-2 no-print">
                    Server: {{ $reportTimingMs }} ms
                    @if(isset($reportCacheStatus)) · cache {{ $reportCacheStatus }} @endif
                    @if(isset($reportLineCount)) · {{ $reportLineCount }} item(s) @endif
                </p>
            @endif
            <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
                <span class="badge text-bg-body-secondary text-body-emphasis fw-normal rounded-pill px-3 py-2 border border-body-secondary border-opacity-50">
                    <span class="material-symbols-rounded icon-16 align-text-bottom me-1">event</span>
                    Till: {{ date('d-F-Y', strtotime($tillDate)) }}
                </span>
                <span class="badge text-bg-primary fw-normal rounded-pill px-3 py-2 stock-balance-store-badge">
                    <span class="material-symbols-rounded icon-16 align-text-bottom me-1">store</span>
                    {{ $selectedStoreName ?? 'All Stores' }}
                </span>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card flex-grow-1 d-flex flex-column min-h-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-shrink-0">
                <span class="fw-semibold text-dark">Stock Balance Details</span>
                <span class="text-muted small">
                    Total items: {{ $reportLineCount ?? ($reportPage->total() ?? 0) }}
                </span>
            </div>
            <div class="stock-balance-table-split flex-grow-1 d-flex flex-column min-h-0">
                <div class="stock-balance-table-head-wrap flex-shrink-0">
                    <table class="table table-hover align-middle mb-0 stock-balance-table stock-balance-col-sync">
                        <colgroup>
                            <col class="sb-col-sn" />
                            <col class="sb-col-code" />
                            <col class="sb-col-name" />
                            <col class="sb-col-qty" />
                            <col class="sb-col-unit" />
                            <col class="sb-col-rate" />
                            <col class="sb-col-amt" />
                        </colgroup>
                        <thead>
                            <tr>
                                @include('admin.mess.reports.partials.report-sno-th')
                                <th data-col="code">Item Code</th>
                                @include('admin.mess.reports.partials.report-sort-th', ['sortKey' => 'item_name', 'label' => 'Item Name', 'defaultDir' => 'asc', 'defaultSort' => 'item_name'])
                                <th class="text-end">Remaining Quantity</th>
                                <th data-col="unit">Unit</th>
                                <th class="text-end" data-col="rate">Avg rate</th>
                                <th class="text-end" data-col="amt">Amount</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="stock-balance-table-body-scroll flex-grow-1 min-h-0">
                    <table class="table align-middle mb-0 stock-balance-table stock-balance-col-sync">
                        <colgroup>
                            <col class="sb-col-sn" />
                            <col class="sb-col-code" />
                            <col class="sb-col-name" />
                            <col class="sb-col-qty" />
                            <col class="sb-col-unit" />
                            <col class="sb-col-rate" />
                            <col class="sb-col-amt" />
                        </colgroup>
                        <tbody>
                            @forelse(($reportPage ?? collect()) as $index => $item)
                                <tr>
                                    <td class="text-center mess-report-sno-cell">@include('admin.mess.reports.partials.report-serial-number', ['paginator' => $reportPage ?? null, 'index' => $index])</td>
                                    <td data-col="code">{{ $item['item_code'] ?? '—' }}</td>
                                    <td>{{ $item['item_name'] }}</td>
                                    <td class="text-end">{{ number_format($item['remaining_qty'], 2) }}</td>
                                    <td data-col="unit">{{ $item['unit'] ?? 'Unit' }}</td>
                                    <td class="text-end" data-col="rate">₹{{ number_format($item['rate'], 2) }}</td>
                                    <td class="text-end" data-col="amt">₹{{ number_format($item['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No stock balance found</td>
                                </tr>
                            @endforelse
                            @if(($reportLineCount ?? 0) > 0)
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Total Amount:</td>
                                    <td class="text-end" data-col="amt">₹{{ number_format(($reportTotalAmount ?? 0), 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @if(isset($reportPage))
                <div class="px-3 py-3 border-top no-print d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="sb-pagination-links">
                        @if($reportPage->hasPages())
                            {{ $reportPage->appends(request()->query())->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                    <div class="sb-count-text ms-auto">Showing {{ number_format($reportPage->count()) }} of {{ number_format($reportPage->total()) }} items</div>
                </div>
            @endif
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

    .stock-balance-report .stock-balance-table-body-scroll {
        min-height: 0;
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        overflow-y: auto;
        max-height: min(72vh, calc(100dvh - 12rem));
    }

    .stock-balance-report .stock-balance-table-head-wrap .stock-balance-table {
        margin-bottom: 0 !important;
    }

    .stock-balance-report .stock-balance-table-body-scroll .stock-balance-table {
        margin-bottom: 0 !important;
    }

    .stock-balance-report .stock-balance-table-body-scroll tbody tr:first-child td {
        border-top: 0 !important;
    }

    .stock-balance-report .stock-balance-table.stock-balance-col-sync {
        table-layout: fixed;
        width: 100%;
        min-width: 700px;
    }

    .stock-balance-report .stock-balance-col-sync col.sb-col-sn { width: 5%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-code { width: 12%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-name { width: 28%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-qty { width: 14%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-unit { width: 8%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-rate { width: 14%; }
    .stock-balance-report .stock-balance-col-sync col.sb-col-amt { width: 19%; }

    .stock-balance-report .stock-balance-table thead th {
        font-weight: 600;
        white-space: nowrap;
        background: #f8f9fa;
        padding: 0.75rem;
        border-bottom: 1px solid #dee2e6;
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
        .stock-balance-report .stock-balance-table-body-scroll {
            max-height: none !important;
            overflow: visible !important;
            min-height: 0 !important;
        }
        .stock-balance-report .stock-balance-table-split {
            display: block !important;
            border: none !important;
            overflow: visible !important;
        }
    }

    .stock-balance-report .report-header {
        display: block;
    }

    .stock-balance-report .report-header .badge {
        max-width: 100%;
        white-space: normal;
    }

    .stock-balance-report .icon-16 {
        font-size: 16px;
    }

    .stock-balance-report .stock-balance-store-badge {
        text-align: left;
    }

    /* ── New-design chrome: Download/Print bar + single-row filter toolbar (token-based per design.md) ── */
    .stock-balance-report .sb-export-btn {
        background: var(--ds-surface, #fff); border: 1px solid var(--ds-line, #e5e7eb);
        color: var(--ds-primary, #004a93); border-radius: var(--ds-radius, 4px);
        min-height: var(--ds-control-h, 40px); padding: 0 1rem; font-weight: 500; font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: 0.4rem;
    }
    .stock-balance-report .sb-export-btn:hover { background: var(--ds-surface-2, #f8fafc); border-color: var(--ds-primary, #004a93); }
    .stock-balance-report .sb-export-btn .material-symbols-rounded { font-size: 1.15rem; }
    .stock-balance-report .sb-filter-toolbar,
    .stock-balance-report .sb-filter-form { flex-wrap: wrap; gap: 0.5rem; }
    .stock-balance-report .sb-filter-item { flex-shrink: 0; }
    .stock-balance-report .sb-filter-date {
        min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px); width: 11rem;
        border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: 0.85rem;
    }
    .stock-balance-report .sb-filter-toolbar .form-select,
    .stock-balance-report .sb-filter-toolbar .ts-wrapper { min-width: 11rem; }
    .stock-balance-report .sb-search-input {
        min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px); width: 13rem;
        border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: 0.85rem;
    }
    /* On-screen: hide the print-oriented report header + the inner "Stock Balance Details" bar (count moves to footer) */
    .stock-balance-report .report-header { display: none !important; }
    .stock-balance-report .card .card-header.bg-light { display: none !important; }
    .stock-balance-report .sb-count-text { color: var(--ds-ink-muted, #667085); font-size: 0.85rem; }
    .stock-balance-report .sb-pagination-links p { display: none !important; }
    .stock-balance-report .sb-pagination-links nav > div { justify-content: flex-start !important; }
    /* Match the mock: a plain flowing table (drop the full-height flex + internal scroll on screen) */
    .stock-balance-report.min-vh-100 { min-height: auto !important; }
    .stock-balance-report.d-flex,
    .stock-balance-report .card-body.d-flex,
    .stock-balance-report .card.d-flex,
    .stock-balance-report .stock-balance-table-split.d-flex { display: block !important; }
    .stock-balance-report .card.flex-grow-1 { flex: none !important; }
    .stock-balance-report .stock-balance-table-body-scroll { overflow: visible !important; max-height: none !important; }

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

    // New-design toolbar: debounced auto-apply (this report reloads via GET) + client-side item search.
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('sbFilterForm');
        if (form) {
            var sbTimer = null;
            form.addEventListener('change', function (e) {
                if (!e.target || !e.target.classList || !e.target.classList.contains('sb-auto-filter')) return;
                if (sbTimer) clearTimeout(sbTimer);
                sbTimer = setTimeout(function () { form.submit(); }, 500);
            });
        }
        var sbSearch = document.getElementById('sbSearch');
        if (sbSearch) {
            sbSearch.addEventListener('input', function () {
                var q = sbSearch.value.trim().toLowerCase();
                document.querySelectorAll('.stock-balance-report .stock-balance-table-body-scroll tbody tr').forEach(function (tr) {
                    if (tr.querySelector('td[colspan]')) return; // keep the total row
                    tr.style.display = (!q || tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
        // Column visibility — directly show/hide the tagged header + body cells.
        document.querySelectorAll('.sb-col-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var col = cb.getAttribute('data-col');
                document.querySelectorAll('.stock-balance-report [data-col="' + col + '"]').forEach(function (el) {
                    el.style.display = cb.checked ? '' : 'none';
                });
            });
        });
    });
</script>
<script>
function printStockBalance() {
    var headTable = document.querySelector('.stock-balance-report .stock-balance-table-head-wrap table');
    var bodyTable = document.querySelector('.stock-balance-report .stock-balance-table-body-scroll table');
    if (!bodyTable && !headTable) {
        window.print();
        return;
    }

    var table = bodyTable || headTable;
    var clonedBody = table.cloneNode(true);

    // Remove Material Symbols icons from clone
    clonedBody.querySelectorAll('.material-symbols-rounded, .material-icons').forEach(function(icon) {
        icon.remove();
    });

    var bodyHtml = clonedBody.querySelector('tbody') ? clonedBody.querySelector('tbody').innerHTML : clonedBody.innerHTML;
    var theadSource = headTable ? headTable.querySelector('thead') : table.querySelector('thead');
    var columnHeadHtml = theadSource ? theadSource.innerHTML : '';

    var title = 'Stock Balance as of Till Date';
    var dateLabel = @json('As on ' . date('d-F-Y', strtotime($tillDate)));
    var storeName = @json($selectedStoreName ?? 'All Stores');
    var emblemUrl = '{{ asset("images/ashoka.png") }}';
    var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';

    var printWindow = window.open('', '_blank');
    if (!printWindow) { window.print(); return; }

    printWindow.document.open();
    printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'    <meta charset="utf-8">\n' +
'    <title>' + title + ' - OFFICER\'S MESS LBSNAA MUSSOORIE</title>\n' +
'    <style>\n' +
'        *, *::before, *::after { box-sizing: border-box; }\n' +
'        body {\n' +
'            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;\n' +
'            font-size: 11px;\n' +
'            color: #212529;\n' +
'            -webkit-print-color-adjust: exact;\n' +
'            print-color-adjust: exact;\n' +
'            margin: 0;\n' +
'            padding: 12mm 10mm;\n' +
'        }\n' +
'\n' +
'        /* ── Print Header ── */\n' +
'        .print-header {\n' +
'            display: flex;\n' +
'            align-items: center;\n' +
'            gap: 12px;\n' +
'            border-bottom: 3px solid #004a93;\n' +
'            padding-bottom: 10px;\n' +
'            margin-bottom: 12px;\n' +
'        }\n' +
'        .print-header img { height: 48px; width: auto; object-fit: contain; }\n' +
'        .header-text { flex: 1; }\n' +
'        .header-text .line1 { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #004a93; font-weight: 600; margin: 0; }\n' +
'        .header-text .line2 { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 2px 0 0; }\n' +
'        .header-text .line3 { font-size: 9px; color: #555; margin: 1px 0 0; }\n' +
'\n' +
'        /* ── Report Title & Meta ── */\n' +
'        .report-title-block { text-align: center; margin-bottom: 10px; }\n' +
'        .report-title-block h2 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 4px; color: #1a1a1a; }\n' +
'        .report-meta {\n' +
'            font-size: 10px;\n' +
'            line-height: 1.7;\n' +
'            margin: 8px 0 10px;\n' +
'            color: #333;\n' +
'        }\n' +
'        .report-meta strong { color: #1a1a1a; }\n' +
'\n' +
'        /* ── Data Table ── */\n' +
'        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }\n' +
'        .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #bbb; vertical-align: middle; }\n' +
'        .data-table thead th { background: #004a93; color: #fff; font-weight: 600; font-size: 10px; text-align: left; }\n' +
'        .data-table thead th.text-end { text-align: right; }\n' +
'        .data-table .text-end { text-align: right; }\n' +
'        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }\n' +
'\n' +
'        /* Total row */\n' +
'        .data-table .table-light td {\n' +
'            background: #e8edf4 !important;\n' +
'            font-weight: 700;\n' +
'            border-top: 2px solid #004a93;\n' +
'            color: #004a93;\n' +
'        }\n' +
'\n' +
'        /* ── Repeating header wrapper ── */\n' +
'        .page-wrapper-table { width: 100%; border-collapse: collapse; border: none; }\n' +
'        .page-wrapper-table > thead, .page-wrapper-table > tbody { border: none; }\n' +
'        .page-wrapper-table td { padding: 0; border: none; }\n' +
'\n' +
'        /* ── Print-specific ── */\n' +
'        @page { size: A4 portrait; margin: 8mm; }\n' +
'        @media print {\n' +
'            body { padding: 0; }\n' +
'            .page-wrapper-table > thead { display: table-header-group; }\n' +
'            .page-wrapper-table > thead td { padding-bottom: 8px; }\n' +
'            thead { display: table-header-group; }\n' +
'            tr { page-break-inside: avoid; }\n' +
'        }\n' +
'    </style>\n' +
'</head>\n' +
'<body>\n' +
'\n' +
'<table class="page-wrapper-table"><thead><tr><td>\n' +
'<div class="print-header">\n' +
'    <img src="' + emblemUrl + '" alt="Emblem">\n' +
'    <div class="header-text">\n' +
'        <p class="line1">Government of India</p>\n' +
'        <p class="line2">OFFICER\'S MESS LBSNAA MUSSOORIE</p>\n' +
'        <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>\n' +
'    </div>\n' +
'    <img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">\n' +
'</div>\n' +
'\n' +
'<div class="report-title-block">\n' +
'    <h2>' + title + '</h2>\n' +
'    <p style="font-size:11px;font-weight:700;color:#004a93;margin:4px 0 0;text-align:center;">' + dateLabel + '</p>\n' +
'</div>\n' +
'\n' +
'<div class="report-meta">\n' +
'    <strong>Store:</strong> ' + storeName + ' &nbsp;&nbsp;|&nbsp;&nbsp; ' +
'    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '\n' +
'</div>\n' +
'</td></tr></thead><tbody><tr><td>\n' +
'\n' +
'<table class="data-table">\n' +
'<thead>' + columnHeadHtml + '</thead>\n' +
'<tbody>' + bodyHtml + '</tbody>\n' +
'</table>\n' +
'\n' +
'</td></tr></tbody></table>\n' +
'\n' +
'<script>\n' +
'    window.addEventListener("load", function() {\n' +
'        setTimeout(function() { window.print(); }, 300);\n' +
'    });\n' +
'<\/script>\n' +
'</body>\n' +
'</html>');
    printWindow.document.close();
}
</script>
@endsection
