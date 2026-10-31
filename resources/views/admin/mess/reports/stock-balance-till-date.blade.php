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
<div class="container-fluid stock-balance-report mess-master-page mess-report-page">
    <x-breadcrum title="Stock Balance as of Till Date"></x-breadcrum>

    @include('admin.mess.reports.partials.report-export-bar', [
        'excelUrl' => route('admin.mess.reports.stock-balance-till-date.excel', request()->query()),
        'pdfUrl' => route('admin.mess.reports.stock-balance-till-date.pdf', request()->query()),
        'printOnclick' => 'printStockBalance()',
    ])

    <div class="ds-card mess-dt-card">
        <x-mess-master-toolbar table-id="stockBalanceTable" :columns="false">
            <form method="GET" action="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="mess-filterbar" aria-label="Stock balance filters">
                <span class="mess-filterbar-label">Filter</span>
                <input type="date" name="till_date" id="till_date" class="form-control" value="{{ $tillDate }}" aria-label="Till date">
                <div class="mess-filter-field">
                    <select name="store_id[]" id="store_id" class="form-select stock-balance-store-multiselect"
                            multiple data-placeholder="Store" aria-label="Store">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $storeIds, true))>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" name="refresh" value="1" class="btn btn-outline-primary">Apply</button>
                @if(request()->hasAny(['till_date', 'store_id']))
                    <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="btn btn-outline-danger">Remove Filter</a>
                @endif
            </form>
        </x-mess-master-toolbar>

        <div class="table-responsive">
            <table id="stockBalanceTable" class="table align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>S. No.</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th class="text-end">Remaining Quantity</th>
                        <th>Unit</th>
                        <th class="text-end">Avg Rate</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end">Total Amount:</td>
                        <td class="text-end" id="stockBalanceTotalAmount">₹0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'stockBalanceTable',
    'searchPlaceholder' => 'Search items...',
    'orderColumn' => 2,
    'actionColumnIndex' => -1,
    'infoLabel' => 'items',
    'ordering' => true,
    'columnManager' => false,
    'colReorder' => false,
    'searchHighlight' => false,
    'pageLength' => 50,
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.reports.stock-balance-till-date', request()->query()),
    'ajaxJsonCallback' => 'stockBalanceReportOnDraw',
])
<script>
    function stockBalanceReportOnDraw(json) {
        var totals = (json && json.totals) || { amount: '0.00' };
        var meta = (json && json.meta) || {};
        var totalEl = document.getElementById('stockBalanceTotalAmount');
        if (totalEl) totalEl.textContent = '₹' + totals.amount;
        var countEl = document.getElementById('stockBalanceReportCount');
        if (countEl) countEl.textContent = 'Total items: ' + (json.recordsFiltered ?? 0);
        var metaEl = document.getElementById('stockBalanceReportMeta');
        if (metaEl) {
            @if(config('app.debug'))
            metaEl.textContent = 'Server: ' + (meta.timingMs ?? '-') + ' ms · cache ' + (meta.cacheStatus ?? '-') + ' · ' + (meta.lineCount ?? 0) + ' item(s)';
            @endif
        }
    }
</script>

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
        min-height: 0;
        -webkit-overflow-scrolling: touch;
        max-height: min(72vh, calc(100dvh - 12rem));
    }

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
        .stock-balance-report .table-responsive {
            max-height: none !important;
            overflow: visible !important;
            min-height: 0 !important;
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
    var table = document.getElementById('stockBalanceTable');
    if (!table || typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable.isDataTable('#stockBalanceTable')) {
        window.print();
        return;
    }

    var dtApi = window.jQuery('#stockBalanceTable').DataTable();
    var urlFn = (window.messMasterDataTableAjaxUrlByTable || {})['stockBalanceTable'];
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
        renderStockBalancePrintWindow(table, (json && json.data) || [], (json && json.totals) || { amount: '0.00' });
    }).fail(function () {
        window.print();
    });
}

function renderStockBalancePrintWindow(table, rows, totals) {
    var theadSource = table.querySelector('thead');
    var columnHeadHtml = theadSource ? theadSource.innerHTML : '';

    var bodyHtml = rows.map(function (row) {
        return '<tr>' +
            '<td>' + row[0] + '</td>' +
            '<td>' + row[1] + '</td>' +
            '<td>' + row[2] + '</td>' +
            '<td class="text-end">' + row[3] + '</td>' +
            '<td>' + row[4] + '</td>' +
            '<td class="text-end">' + row[5] + '</td>' +
            '<td class="text-end">' + row[6] + '</td>' +
            '</tr>';
    }).join('');
    bodyHtml += '<tr class="table-light"><td colspan="6" class="text-end">Total Amount:</td><td class="text-end">₹' + (totals.amount ?? '0.00') + '</td></tr>';

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
