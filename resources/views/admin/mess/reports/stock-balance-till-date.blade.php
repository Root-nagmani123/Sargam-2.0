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
        <!-- Report Heading -->
        <div class="report-header text-center mb-4 pb-3 border-bottom border-body-secondary border-opacity-25">
            <h4 class="fw-bold text-uppercase mb-3 fs-5 text-body-emphasis">Stock Balance as of Till Date</h4>
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
                    Total items: {{ count($reportData) }}
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
                                <th>S. No.</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th class="text-end">Remaining Quantity</th>
                                <th>Unit</th>
                                <th class="text-end">Avg rate</th>
                                <th class="text-end">Amount</th>
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
        min-height: 100%;
        max-height: calc(100vh - 320px);
        -webkit-overflow-scrolling: touch;
        overflow: auto;
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
