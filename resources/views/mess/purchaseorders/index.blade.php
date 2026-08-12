@extends('admin.layouts.master')
@section('title', 'Purchase Order')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Purchase Order — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .po-master-page .po-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    .po-master-page .po-master-export-btn {
        height: var(--ds-control-h, 40px);
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: 0 1.1rem;
        font-size: .9375rem;
        font-weight: 500;
        color: var(--ds-primary, #004a93);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius-2, 8px);
        background: var(--ds-surface, #fff);
    }

    .po-master-page .po-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .po-master-page .po-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Native filter pills (Vendor / Store selects + date inputs) */
    .po-master-page .programme-dt-filter-select .form-select,
    .po-master-page .po-filter-date {
        min-height: 40px;
        border-radius: 8px;
        border: 1px solid #d0d5dd;
        font-size: .9375rem;
        color: #344054;
        box-shadow: none;
    }
    .po-master-page .po-filter-date { padding: .5rem .75rem; width: 165px; }
    .po-master-page .programme-dt-filter-select .form-select:focus,
    .po-master-page .po-filter-date:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }
    .po-master-page .po-filter-dash { color: #98a2b3; }

    .po-master-page .dt-top:empty,
    .po-master-page .dt-foot:empty { display: none; margin: 0; }

    .po-master-page .mess-col-manager-dropdown { display: none !important; }

    #poColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    #poColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }
    #poColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .po-master-page .po-order-no { color: var(--ds-ink, #1f2937); }

    /* Pending status pill (amber) — approved/completed reuse --active, rejected --inactive */
    .po-master-page .programme-status-badge--pending { background: #fffaeb !important; color: #b54708 !important; }

    /* Row actions — icon over label (blue View/Edit, red Delete). */
    .po-master-page .po-actions-cell { gap: 1.1rem; }
    .po-master-page .po-actions-cell form { margin: 0; }

    .po-master-page .po-action-btn {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: .1rem;
        padding: 0;
        border: 0;
        background: transparent;
        line-height: 1.1;
        font-size: .75rem;
        font-weight: 500;
        width: auto;
        height: auto;
    }
    .po-master-page .po-action-btn i { font-size: 1.2rem; line-height: 1; }
    .po-master-page .po-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .po-master-page .po-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .po-master-page .po-action-btn:hover { opacity: .78; transform: none; box-shadow: none; }

    /* Pagination → arrows + numbers only. */
    .po-master-page .programme-dt-footer .paginate_button.first,
    .po-master-page .programme-dt-footer .paginate_button.last { display: none; }
    .po-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .po-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }
    .po-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .po-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

</style>
@endpush

@section('content')
@php
$canDeletePurchaseOrder = hasRole('Super Admin') || hasRole('Mess-Admin');
$filterVendorId = ($filterVendorIds ?? [])[0] ?? '';
$filterStoreId = ($filterStoreIds ?? [])[0] ?? '';
$hasPoFilter = ($filterDateFrom ?? '') !== '' || ($filterDateTo ?? '') !== '' || $filterVendorId !== '' || $filterStoreId !== '';
@endphp
<div class="container-fluid po-ux po-master-page">
    <x-breadcrum title="Purchase Order" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createPurchaseOrderModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Create Purchase Order</span>
        </button>
    </x-breadcrum>

    {{-- Success feedback is rendered as the global green toast — see mess.partials.delete-confirm --}}

    {{-- Download / Print bar (branded server-side exports — see admin.mess.purchaseorders.export) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn po-master-export-btn" id="poDownloadBtn">
            <i class="material-symbols-rounded">download</i>
            <span>Download</span>
        </button>
        <button type="button" class="btn po-master-export-btn" id="poPrintBtn">
            <i class="material-symbols-rounded">print</i>
            <span>Print</span>
        </button>
    </div>

    <div class="card po-master-card border-0">
        <div class="card-body">
            {{-- Toolbar: Vendor / Store / date-range filters (left) + Columns & search (right) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <form method="GET" action="{{ route('admin.mess.purchaseorders.index') }}" id="poFilterForm"
                      class="d-flex flex-wrap align-items-center gap-2">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select" style="width:170px;">
                        <select name="vendor_id" class="form-select" aria-label="Filter by vendor" onchange="document.getElementById('poFilterForm').submit()">
                            <option value="">Vendor</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ (string) $filterVendorId === (string) $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select" style="width:170px;">
                        <select name="store_id" class="form-select" aria-label="Filter by store" onchange="document.getElementById('poFilterForm').submit()">
                            <option value="">Store</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ (string) $filterStoreId === (string) $s->id ? 'selected' : '' }}>{{ $s->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="date" name="date_from" class="form-control po-filter-date" value="{{ $filterDateFrom }}"
                           aria-label="From date" max="{{ date('Y-m-d') }}" onchange="document.getElementById('poFilterForm').submit()">
                    <span class="po-filter-dash">–</span>
                    <input type="date" name="date_to" class="form-control po-filter-date" value="{{ $filterDateTo }}"
                           aria-label="To date" max="{{ date('Y-m-d') }}" onchange="document.getElementById('poFilterForm').submit()">
                    <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn programme-dt-btn-reset d-inline-flex align-items-center justify-content-center">Remove Filter</a>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnPoColumns"
                            data-bs-toggle="modal" data-bs-target="#poColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    @include('mess.partials.search-toggle', ['tableId' => 'purchaseOrdersTable'])
                </div>
            </div>

            <div class="po-print-area">
                {{-- Print header: LBSNAA / Sargam branding (shown only when printing) --}}
                <div class="print-only report-header text-center mb-3" style="display: none;">
                    <div class="logo-container mb-2 d-flex justify-content-center align-items-center gap-3 flex-wrap">
                        <img src="{{ asset('images/ashoka.webp') }}" alt="" class="po-print-emblem" width="52" height="52" style="height: 52px; width: auto; object-fit: contain;">
                        <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Lal Bahadur Shastri National Academy of Administration" class="po-print-wordmark" style="height: 44px; width: auto;">
                    </div>
                    <h3 class="report-mess-title mb-1">OFFICER'S MESS LBSNAA MUSSOORIE</h3>
                    <p class="small text-muted mb-2 mb-md-3">Sargam 2.0</p>
                    <div class="report-title-bar">Purchase Orders</div>
                    <div class="report-print-date small text-muted mt-1">Printed on {{ now()->format('d-m-Y, h:i A') }}</div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table id="purchaseOrdersTable" class="table programme-dt-table align-middle mb-0 w-100 po-data-table">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Order No.</th>
                                    <th scope="col">Vendor</th>
                                    <th scope="col">Store</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="d-print-none">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>{{-- /.po-print-area --}}

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="purchaseOrdersTable"></div>
        </div>
    </div>
</div>

<style>
.po-ux .letter-spacing-1 {
    letter-spacing: 0.04em;
}

/* ── Navy table header ── */

/* ── Row hover / transition ── */
.po-row {
    transition: background-color .18s ease;
}

.po-row:hover {
    background-color: rgba(11, 74, 126, .04) !important;
}

/* ── Action buttons ── */
.po-action-btn {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all .2s ease;
}

.po-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
}

/* ── Create button hover ── */
.po-btn-create {
    transition: all .25s ease;
}

.po-btn-create:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, .25);
}

/* ── Fade-in animation ── */
@keyframes po-fade-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.po-ux .datatables {
    animation: po-fade-in .4s ease-out;
}

.po-filter-card {
    animation: po-fade-in .35s ease-out;
}

@media (max-width: 575.98px) {
    .po-ux .datatables .table thead th {
        font-size: 0.65rem;
    }
}

/* ── Modal form field focus — navy ring ── */
#createPurchaseOrderModal .form-control:focus,
#createPurchaseOrderModal .form-select:focus {
    border-color: #0b4a7e !important;
    box-shadow: 0 0 0 .2rem rgba(11, 74, 126, .15);
}

/* Print header – standard level (matches category-wise-print-slip) */
.report-mess-title {
    color: #1a1a1a;
    font-size: 1.25rem;
    font-weight: bold;
}

.report-title-bar {
    background-color: #004a93;
    color: #fff;
    padding: 8px 16px;
    font-size: 0.95rem;
    border-radius: 4px;
    display: inline-block;
}

.report-print-date {
    color: #6c757d;
}

@media print {

    html,
    body {
        background: #fff !important;
        height: auto !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        position: relative !important;
    }

    /* Remove app chrome from layout flow (visibility:hidden still reserves space) */
    .sargam-loader,
    #sargamLoader,
    .topbar,
    header.topbar,
    .left-sidebar,
    .side-mini-panel,
    aside.side-mini-panel,
    #sidebarTabContent,
    .navbar,
    #mainNavbarContent>.tab-pane:not(.show.active) {
        display: none !important;
    }

    .page-wrapper,
    .body-wrapper,
    #main-content,
    .tab-content {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    .no-print {
        display: none !important;
    }

    /* Hide chrome without visibility:hidden (that forced icon ligature text to print) */
    .po-ux>.no-print,
    .po-ux .card-header,
    .po-ux .po-filter-card,
    .po-ux .alert,
    .po-ux .mess-col-manager-dropdown,
    .po-ux .dataTables_length,
    .po-ux .dataTables_filter,
    .po-ux .dataTables_info,
    .po-ux .dataTables_paginate,
    #purchaseOrdersTable_wrapper table:not(#purchaseOrdersTable),
    #purchaseOrdersTable_wrapper .DTCR_clonedTable,
    #purchaseOrdersTable_wrapper .dt-order-columns {
        display: none !important;
    }

    .po-ux .card,
    .po-ux .card-body,
    .po-print-area {
        border: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 12px !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .print-only {
        display: block !important;
    }

    .report-header {
        margin-top: 0;
        border-bottom: 2px solid #004a93;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }

    .logo-container {
        margin-bottom: 12px;
    }

    .logo-container .po-print-emblem {
        height: 52px !important;
        width: auto !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .logo-container .po-print-wordmark {
        height: 44px !important;
        width: auto !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-mess-title {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .report-title-bar {
        font-size: 14px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        display: inline-block;
        background-color: #004a93 !important;
    }

    .report-print-date {
        font-size: 11px;
        color: #6c757d;
        margin-top: 8px;
    }

    /* Table styling for print */
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        page-break-inside: auto;
    }

    .table thead th {
        background-color: #004a93 !important;
        color: #fff !important;
        font-weight: 600;
        padding: 10px 8px;
        border: 1px solid #003d7a;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .table tbody td {
        padding: 8px;
        border: 1px solid #dee2e6;
        color: #212529;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Badge colors in print */
    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        padding: 4px 10px;
        font-size: 11px;
        border-radius: 4px;
    }

    .bg-success {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    .bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .bg-primary {
        background-color: #004a93 !important;
        color: #fff !important;
    }

    .text-bg-success {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    .text-bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    .text-bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .text-bg-primary {
        background-color: #004a93 !important;
        color: #fff !important;
    }

    #purchaseOrdersTable th.d-print-none,
    #purchaseOrdersTable td.po-actions-col,
    #purchaseOrdersTable td:has(.po-action-btn),
    #purchaseOrdersTable .po-action-btn,
    #purchaseOrdersTable .po-actions-cell,
    #purchaseOrdersTable i.material-icons,
    #purchaseOrdersTable i.material-symbol-rounded,
    #purchaseOrdersTable i[class*="material"] {
        display: none !important;
    }

    /* Hide unnecessary elements */
    .card {
        box-shadow: none;
        border: none;
    }

    .datatables {
        margin: 0;
    }

    /* Page breaks */
    @page {
        size: A4;
        margin: 15mm;
    }
}
</style>

{{-- Column Visibility Modal (programme/attendance style) --}}
<div class="modal fade" id="poColumnVisibilityModal" tabindex="-1" aria-labelledby="poColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="poColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="poColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Branded delete-confirmation dialog + global success toast --}}
@include('mess.partials.delete-confirm')

@include('components.mess-master-datatables', [
'tableId' => 'purchaseOrdersTable',
'searchPlaceholder' => 'Search',
'orderColumn' => 1,
'actionColumnIndex' => 5,
'infoLabel' => 'items',
'serverSide' => true,
'ajaxUrlBase' => route('admin.mess.purchaseorders.index'),
'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
])
@include('mess.partials.modal-dropdown-stability')

@push('scripts')
{{-- Download / Print → branded server-side report (admin.mess.purchaseorders.export). --}}
<script>
(function () {
    var TABLE_ID = 'purchaseOrdersTable';
    var BASE = @json(route('admin.mess.purchaseorders.export'));
    var $ = window.jQuery;

    function buildUrl(format, inline) {
        var params = ['format=' + format];
        var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
        var search = dt ? dt.search() : '';
        if (search) params.push('search=' + encodeURIComponent(search));

        var form = document.getElementById('poFilterForm');
        if (form) {
            ['vendor_id', 'store_id', 'date_from', 'date_to'].forEach(function (n) {
                var el = form.elements[n];
                if (el && el.value) params.push(n + '=' + encodeURIComponent(el.value));
            });
        }

        var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
            ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
        if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));

        if (inline) params.push('inline=1');
        return BASE + '?' + params.join('&');
    }

    var downloadBtn = document.getElementById('poDownloadBtn');
    if (downloadBtn) downloadBtn.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
    var printBtn = document.getElementById('poPrintBtn');
    if (printBtn) printBtn.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
})();
</script>
{{-- Column Visibility modal ⇄ mess Column-manager bridge --}}
<script>
(function () {
    var TABLE_ID = 'purchaseOrdersTable';
    var $ = window.jQuery;
    var grid = document.getElementById('poColumnToggleGrid');
    var modalEl = document.getElementById('poColumnVisibilityModal');
    if (!$ || !grid || !modalEl) return;

    function getMgr() {
        return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function')
            ? window.MessColumnManager.get(TABLE_ID) : null;
    }
    function visibleCount(mgr) {
        return mgr.baseColumns.filter(function (c) { return mgr.state.visibility[String(c.index)] !== false; }).length;
    }
    function buildGrid() {
        var mgr = getMgr();
        if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;
        grid.innerHTML = '';
        (mgr.state.order || []).forEach(function (idx) {
            var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
            if (!col) return;
            var isVisible = mgr.state.visibility[String(col.index)] !== false;
            var inputId = 'pocolvis_' + col.index;
            var cell = document.createElement('div');
            cell.className = 'col-12 col-sm-6 col-md-4';
            var label = document.createElement('label');
            label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
            label.setAttribute('for', inputId);
            var cb = document.createElement('input');
            cb.type = 'checkbox'; cb.className = 'form-check-input m-0'; cb.id = inputId; cb.checked = isVisible;
            if (col.locked) cb.disabled = true;
            cb.addEventListener('change', function () {
                var m = getMgr(); if (!m) return;
                if (!cb.checked && visibleCount(m) <= 1) {
                    cb.checked = true;
                    window.alert('At least one column must remain visible.');
                    return;
                }
                m.state.visibility[String(col.index)] = cb.checked;
                m.saveState();
                m.apply();
            });
            var span = document.createElement('span'); span.textContent = col.label;
            label.appendChild(cb); label.appendChild(span);
            cell.appendChild(label); grid.appendChild(cell);
        });
        return true;
    }
    modalEl.addEventListener('show.bs.modal', function () {
        if (buildGrid()) return;
        var tries = 0;
        var timer = setInterval(function () { if (buildGrid() || ++tries > 20) clearInterval(timer); }, 100);
    });
})();
</script>
@endpush

@push('scripts')
<script>
(function() {
    var poListPrintRestore = null;
    var PO_ACTION_COL_INDEX = 5;

    function poMarkActionColumnCells() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
        var $t = window.jQuery('#purchaseOrdersTable');
        if (!$t.length || !window.jQuery.fn.DataTable.isDataTable($t)) return;
        $t.DataTable().cells(null, PO_ACTION_COL_INDEX).nodes().to$().addClass('po-actions-col');
    }

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(document).on('init.dt draw.dt', '#purchaseOrdersTable', poMarkActionColumnCells);
    }

    window.PO_PRINT_SUPPRESS_ICON_CSS =
        'i[class*="material"],span[class*="material"],.material-icons,.material-symbol-rounded,' +
        '.material-symbols-rounded{display:none!important;font-size:0!important;width:0!important;height:0!important;' +
        'overflow:hidden!important;visibility:hidden!important;color:transparent!important;line-height:0!important;}' +
        '.po-action-btn,.po-actions-cell,.modal-footer,.modal-header button{display:none!important;}';

    window.poSanitizePrintDom = function (root) {
        if (!root) {
            return '';
        }
        root.querySelectorAll(
            'i[class*="material"], span[class*="material"], .material-icons, .material-symbol-rounded, ' +
            '.material-symbols-rounded'
        ).forEach(function (el) {
            el.remove();
        });
        root.querySelectorAll('.modal-header, .modal-footer').forEach(function (el) {
            el.remove();
        });
        root.querySelectorAll('button').forEach(function (el) {
            el.remove();
        });
        root.querySelectorAll('.card-header .rounded-3, .card-header .rounded-2').forEach(function (box) {
            var text = (box.textContent || '').replace(/\s+/g, '').toLowerCase();
            if (!text || /^(visibility|edit|delete|print|add|view_column|receipt_long|inventory_2|assignment|attach_file)$/.test(text)) {
                box.remove();
            }
        });
        var html = root.innerHTML;
        if (window.MessColumnManager && typeof window.MessColumnManager._cleanupMaterialLigatureText === 'function') {
            html = window.MessColumnManager._cleanupMaterialLigatureText(html);
        } else {
            html = html.replace(
                />\s*(visibility|edit|delete|print|add|view_column|receipt_long|inventory_2|assignment|attach_file|unfold_more|arrow_upward|arrow_downward)\s*</gi,
                '><'
            );
        }
        return html;
    };

    function poHidePrintArtifacts() {
        var root = document.querySelector('.po-print-area');
        if (!root) return;
        root.querySelectorAll(
            'i.material-icons, i.material-symbol-rounded, i.material-symbols-rounded, i[class*="material"]'
        ).forEach(function(icon) {
            icon.remove();
        });
        root.querySelectorAll('.po-action-btn, .po-actions-cell').forEach(function(el) {
            el.remove();
        });
        root.querySelectorAll('th.d-print-none, td.po-actions-col').forEach(function(cell) {
            cell.style.setProperty('display', 'none', 'important');
        });
        var wrapper = document.getElementById('purchaseOrdersTable_wrapper');
        if (wrapper) {
            wrapper.querySelectorAll('table:not(#purchaseOrdersTable)').forEach(function(tbl) {
                tbl.style.setProperty('display', 'none', 'important');
            });
        }
    }

    function poPrepareBrowserPrint() {
        // The View-modal print runs in its own iframe; some browsers still bubble
        // beforeprint to the top window, and re-laying-out the list DataTable there
        // is both pointless and visible to the user.
        if (window.PO_MODAL_PRINT_ACTIVE) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
        var $t = window.jQuery('#purchaseOrdersTable');
        if (!$t.length || !window.jQuery.fn.DataTable.isDataTable($t)) return;
        var dt = $t.DataTable();
        poListPrintRestore = {
            actionVisible: dt.column(PO_ACTION_COL_INDEX).visible()
        };
        dt.column(PO_ACTION_COL_INDEX).visible(false);
        poHidePrintArtifacts();
    }

    function poRestoreAfterBrowserPrint() {
        if (!poListPrintRestore) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
            poListPrintRestore = null;
            return;
        }
        var $t = window.jQuery('#purchaseOrdersTable');
        if ($t.length && window.jQuery.fn.DataTable.isDataTable($t)) {
            $t.DataTable().column(PO_ACTION_COL_INDEX).visible(poListPrintRestore.actionVisible);
        }
        poListPrintRestore = null;
    }

    window.addEventListener('beforeprint', poPrepareBrowserPrint);
    window.addEventListener('afterprint', poRestoreAfterBrowserPrint);

    document.getElementById('poPrintListBtn')?.addEventListener('click', function() {
        if (!window.MessColumnManager || typeof window.MessColumnManager.printDataTable !== 'function') {
            window.print();
            return;
        }
        window.MessColumnManager.printDataTable('purchaseOrdersTable', {
            template: 'lbsnaa',
            title: 'Purchase Orders',
            periodText: document.title || ''
        });
    });
})();
</script>
@endpush

{{-- Choices.js (Bootstrap-aligned styling below) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" />
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- Create Purchase Order Modal --}}
<style>
/* Create PO: use nearly full viewport — one scroll area (header/footer fixed via modal-dialog-scrollable) */
#createPurchaseOrderModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#createPurchaseOrderModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
}

#createPurchaseOrderModal .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
}

#editPurchaseOrderModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#editPurchaseOrderModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
}

#editPurchaseOrderModal .modal-body {
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
}

#viewPurchaseOrderModal .modal-dialog {
    max-height: calc(100dvh - 2rem);
    margin: 1rem auto;
}

#viewPurchaseOrderModal .modal-content {
    max-height: calc(100dvh - 2rem);
    display: flex;
    flex-direction: column;
}

#viewPurchaseOrderModal .modal-body {
    overflow-y: auto;
    max-height: calc(100dvh - 10rem);
}

#createPurchaseOrderModal .modal-dialog,
#editPurchaseOrderModal .modal-dialog,
#viewPurchaseOrderModal .modal-dialog {
    width: calc(100vw - 1rem);
    max-width: min(var(--bs-modal-width), calc(100vw - 1rem));
}

@media (min-width: 576px) {

    #createPurchaseOrderModal .modal-dialog,
    #editPurchaseOrderModal .modal-dialog,
    #viewPurchaseOrderModal .modal-dialog {
        width: calc(100vw - 2rem);
        max-width: min(var(--bs-modal-width), calc(100vw - 2rem));
    }
}

/* Tom Select Dropdown Fix - Ensure dropdowns appear above everything */
.ts-dropdown {
    z-index: 10000 !important;
}

.ts-control {
    z-index: 1;
}

/* Performance optimizations for Tom Select */
.ts-dropdown .option {
    will-change: auto;
}

.ts-dropdown-content {
    contain: layout style paint;
}

/* Keep table scroll stable inside modals (Tom Select uses dropdownParent: body) */
#createPurchaseOrderModal .modal-body .table-responsive,
#editPurchaseOrderModal .modal-body .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

#createPurchaseOrderModal .card-body,
#editPurchaseOrderModal .card-body {
    overflow: visible;
}

#createPurchaseOrderModal .modal-content,
#editPurchaseOrderModal .modal-content {
    overflow: visible;
}

#createPurchaseOrderModal .card,
#editPurchaseOrderModal .card {
    overflow: visible;
}


/* ========================================
   Choices.js-like Styling for Tom Select
   ======================================== */

/* Control (Input Container) - Enhanced Bootstrap Style */
.ts-wrapper .ts-control {
    background-color: #fff;
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 6px 12px;
    min-height: 42px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.ts-wrapper.single .ts-control {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.25rem;
}

/* Focus state - Enhanced Bootstrap Style */
.ts-wrapper.focus .ts-control {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

/* Dropdown container - Enhanced Bootstrap Style */
.ts-dropdown {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    background-color: #fff;
    margin-top: 0.25rem;
}

/* Search input inside dropdown - Choices.js style */
.ts-dropdown .ts-dropdown-content {
    padding: 0;
}

.ts-control>input {
    color: #333;
    font-size: 14px;
    padding: 4px 0;
}

/* Dropdown input (search field) - Enhanced Bootstrap Style */
.ts-dropdown-content input {
    border: 2px solid #dee2e6 !important;
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem !important;
    margin: 0.5rem !important;
    width: calc(100% - 1rem) !important;
    font-size: 0.875rem;
    background-color: #f8f9fa;
    box-sizing: border-box;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.ts-dropdown-content input:focus {
    outline: none;
    border-color: #86b7fe !important;
    background-color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

/* Options list - Enhanced Bootstrap Style */
.ts-dropdown .option {
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: #212529;
    cursor: pointer;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.15s ease;
    background-color: transparent;
}

.ts-dropdown .option:last-child {
    border-bottom: none;
}

/* Option hover state - Enhanced Bootstrap Style */
.ts-dropdown .option:hover {
    background-color: rgba(13, 110, 253, 0.08);
    color: #0d6efd;
}

/* Prevent default active state highlighting */
.ts-dropdown .option.active {
    background-color: transparent;
    color: #212529;
}

/* Only show active state on hover */
.ts-dropdown .option.active:hover {
    background-color: rgba(13, 110, 253, 0.08);
    color: #0d6efd;
}

/* Selected option highlight - Enhanced Bootstrap Style */
.ts-dropdown .option.selected {
    background-color: #0d6efd;
    color: #fff;
    font-weight: 600;
}

.ts-dropdown .option.selected:hover {
    background-color: #0b5ed7;
    color: #fff;
}

/* Aria-selected ko bhi visually normal rakho (auto selected highlight hide) */
.ts-dropdown .option[aria-selected="true"]:not(.selected) {
    background-color: transparent;
    color: #212529;
}

/* No results message - Enhanced Bootstrap Style */
.ts-dropdown .no-results {
    padding: 1rem;
    color: #6c757d;
    font-size: 0.875rem;
    text-align: center;
    background-color: #f8f9fa;
    font-style: italic;
}

.po-item-select+.choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 4px);
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.ts-wrapper.choices[data-type*="select-multiple"] .choices__inner {
    padding: 0.375rem 0.75rem;
    border-width: 2px;
}

.form-select-sm+.choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 4px);
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.po-filter-multi+.choices .choices__inner {
    min-height: 3rem;
    border-width: 2px;
}

.po-ux .po-filter-multiselect-wrap .input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    border-width: 2px;
}

.po-ux .po-filter-card {
    overflow: visible;
}

.po-ux .po-filter-card .card-body,
.po-ux .po-filter-card .card-header {
    overflow: visible;
}

.po-ux .po-filter-multiselect-wrap {
    overflow: visible;
}

.po-ux .po-filter-multiselect-wrap .ts-wrapper {
    flex: 1 1 auto;
    min-width: 0;
    width: 1%;
}

.po-ux .po-filter-multiselect-wrap .ts-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    min-height: calc(1.5em + .5rem + calc(var(--bs-border-width) * 2));
    font-size: .875rem;
    border-left-width: 0;
}

.choices__list--multiple .choices__item {
    background-color: var(--bs-primary) !important;
    border: none !important;
    border-radius: var(--bs-border-radius-pill) !important;
    color: #fff !important;
    font-size: .8rem !important;
    padding: .25rem .625rem !important;
    font-weight: 500 !important;
}

.choices__list--multiple .choices__item:hover {
    opacity: .85;
}

/* ========================================
       Native Bootstrap Select / Input tweaks
       ======================================== */
.form-select,
.form-control {
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}

.form-control[type="file"] {
    cursor: pointer;
}

/* ========================================
       Minimal UI polish
       ======================================== */
.modal-body {
    scroll-behavior: smooth;
}
</style>
<style>
/* ══ Purchase Order modals — compact spec design ══
   One stylesheet for Create / Edit / View so all three read as the same form.
   Selectors are ID-grouped (not a shared class) so they outrank the older
   ID-scoped rules further up this file. Create-only or View-only bits are
   marked. Never write a literal Blade directive in a comment here — Blade
   compiles it even inside a comment, which opens a stray output buffer and
   blanks the gzipped page. */
#createPurchaseOrderModal .modal-content,
#editPurchaseOrderModal .modal-content,
#viewPurchaseOrderModal .modal-content {
    border: 0;
    border-radius: 8px;
    box-shadow: 0 16px 40px rgba(16, 24, 40, .16);
}

#createPurchaseOrderModal .modal-header,
#editPurchaseOrderModal .modal-header,
#viewPurchaseOrderModal .modal-header {
    align-items: center;
    padding: .875rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
}

#createPurchaseOrderModal .modal-title,
#editPurchaseOrderModal .modal-title,
#viewPurchaseOrderModal .modal-title {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
    color: #212529;
}

#createPurchaseOrderModal .btn-close,
#editPurchaseOrderModal .btn-close,
#viewPurchaseOrderModal .btn-close {
    padding: .5rem;
    font-size: .8rem;
    opacity: .55;
}

#createPurchaseOrderModal .modal-body,
#editPurchaseOrderModal .modal-body,
#viewPurchaseOrderModal .modal-body {
    padding: 1.125rem 1.25rem 1.25rem;
    background: #fff;
}

/* ── Field labels ── */
#createPurchaseOrderModal .po-label,
#editPurchaseOrderModal .po-label,
#viewPurchaseOrderModal .po-label {
    display: block;
    margin-bottom: .25rem;
    font-size: .75rem;
    font-weight: 400;
    line-height: 1.2;
    color: #212529;
}

#createPurchaseOrderModal .po-req,
#editPurchaseOrderModal .po-req,
#viewPurchaseOrderModal .po-req {
    color: #dc3545;
}

/* ── Controls ── */
#createPurchaseOrderModal .form-control,
#createPurchaseOrderModal .form-select,
#editPurchaseOrderModal .form-control,
#editPurchaseOrderModal .form-select,
#viewPurchaseOrderModal .form-control {
    height: 32px;
    min-height: 32px;
    padding: .25rem .5rem;
    font-size: .78125rem;
    line-height: 1.4;
    color: #212529;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-shadow: none;
}

#createPurchaseOrderModal .form-select,
#editPurchaseOrderModal .form-select {
    padding-right: 1.75rem;
    background-size: 12px 9px;
    background-position: right .5rem center;
}

#createPurchaseOrderModal .form-control::placeholder,
#editPurchaseOrderModal .form-control::placeholder {
    color: #adb5bd;
    opacity: 1;
}

#createPurchaseOrderModal .form-control:focus,
#createPurchaseOrderModal .form-select:focus,
#editPurchaseOrderModal .form-control:focus,
#editPurchaseOrderModal .form-select:focus {
    border-color: var(--ds-primary, #004384) !important;
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12) !important;
}

/* Auto-generated / read-only header fields */
#createPurchaseOrderModal .po-readonly,
#editPurchaseOrderModal .po-readonly,
#viewPurchaseOrderModal .po-readonly {
    background-color: #f1f3f5;
    color: #868e96;
}

/* Attachment picker — native "Choose File" chrome, full width */
#createPurchaseOrderModal input[type="file"].form-control,
#editPurchaseOrderModal input[type="file"].form-control {
    padding: 0;
    font-size: .78125rem;
    line-height: 30px;
    color: #495057;
}

#createPurchaseOrderModal input[type="file"].form-control::file-selector-button,
#editPurchaseOrderModal input[type="file"].form-control::file-selector-button {
    height: 30px;
    margin: 0 .625rem 0 0;
    padding: 0 .75rem;
    font-size: .78125rem;
    color: #212529;
    background: #e9ecef;
    border: 0;
    border-right: 1px solid #ced4da;
    border-radius: 3px 0 0 3px;
}

/* Edit only: the current attachment sits beside the picker */
#editPurchaseOrderModal .po-file-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
}

#editPurchaseOrderModal .po-file-row .form-control {
    flex: 1 1 260px;
    min-width: 0;
}

#editPurchaseOrderModal .po-file-current,
#viewPurchaseOrderModal .po-file-current {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    font-size: .75rem;
    color: #6c757d;
}

#editPurchaseOrderModal .po-file-current a {
    color: var(--ds-primary, #004384);
    text-decoration: underline;
}

#editPurchaseOrderModal .po-btn-linkish {
    padding: 0 .5rem;
    font-size: .75rem;
    font-weight: 500;
    line-height: 30px;
    color: #dc3545;
    background: none;
    border: 1px solid #dc3545;
    border-radius: 4px;
}

/* Date fields — native inputs have no placeholder, so overlay one while empty */
#createPurchaseOrderModal .po-date-wrap,
#editPurchaseOrderModal .po-date-wrap {
    position: relative;
}

#createPurchaseOrderModal .po-date-wrap.is-empty .po-date,
#createPurchaseOrderModal .po-date-wrap.is-empty .po-date::-webkit-datetime-edit,
#editPurchaseOrderModal .po-date-wrap.is-empty .po-date,
#editPurchaseOrderModal .po-date-wrap.is-empty .po-date::-webkit-datetime-edit {
    color: transparent;
}

#createPurchaseOrderModal .po-date-ph,
#editPurchaseOrderModal .po-date-ph {
    position: absolute;
    top: 50%;
    left: .5rem;
    display: none;
    font-size: .78125rem;
    color: #adb5bd;
    pointer-events: none;
    transform: translateY(-50%);
}

#createPurchaseOrderModal .po-date-wrap.is-empty .po-date-ph,
#editPurchaseOrderModal .po-date-wrap.is-empty .po-date-ph {
    display: block;
}

#createPurchaseOrderModal .po-date::-webkit-calendar-picker-indicator,
#editPurchaseOrderModal .po-date::-webkit-calendar-picker-indicator {
    opacity: .5;
    cursor: pointer;
}

/* Choices.js controls (Store / Vendor / Payment mode / Item) → same box as a native select */
#createPurchaseOrderModal .choices,
#editPurchaseOrderModal .choices {
    margin-bottom: 0;
}

#createPurchaseOrderModal .choices__inner,
#editPurchaseOrderModal .choices__inner {
    display: flex;
    align-items: center;
    height: 32px;
    min-height: 32px !important;
    padding: 0 1.75rem 0 .5rem !important;
    font-size: .78125rem !important;
    background: #fff !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    box-shadow: none !important;
}

#createPurchaseOrderModal .choices.is-focused .choices__inner,
#editPurchaseOrderModal .choices.is-focused .choices__inner {
    border-color: var(--ds-primary, #004384) !important;
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12) !important;
}

#createPurchaseOrderModal .choices__list--single,
#editPurchaseOrderModal .choices__list--single {
    padding: 0 !important;
}

#createPurchaseOrderModal .choices__list--single .choices__item,
#editPurchaseOrderModal .choices__list--single .choices__item {
    font-size: .78125rem;
    line-height: 1.4;
    color: #212529;
}

#createPurchaseOrderModal .choices__placeholder,
#editPurchaseOrderModal .choices__placeholder {
    color: #adb5bd;
    opacity: 1;
}

#createPurchaseOrderModal .choices[data-type*="select-one"]::after,
#editPurchaseOrderModal .choices[data-type*="select-one"]::after {
    right: .625rem;
    border-width: 4px;
    border-top-color: #6c757d;
}

#createPurchaseOrderModal .choices__list--dropdown .choices__item,
#editPurchaseOrderModal .choices__list--dropdown .choices__item {
    font-size: .78125rem;
}

/* ── Order Items ── */
#createPurchaseOrderModal .po-items-title,
#editPurchaseOrderModal .po-items-title,
#viewPurchaseOrderModal .po-items-title {
    margin: 1.125rem 0 .5rem;
    font-size: .8125rem;
    font-weight: 600;
    color: #212529;
}

#createPurchaseOrderModal .po-items-box,
#editPurchaseOrderModal .po-items-box,
#viewPurchaseOrderModal .po-items-box {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

#createPurchaseOrderModal .po-items-table,
#editPurchaseOrderModal .po-items-table,
#viewPurchaseOrderModal .po-items-table {
    margin: 0;
    font-size: .75rem;
}

#createPurchaseOrderModal .po-items-table>thead>tr>th,
#editPurchaseOrderModal .po-items-table>thead>tr>th,
#viewPurchaseOrderModal .po-items-table>thead>tr>th {
    padding: .5rem;
    font-size: .71875rem;
    font-weight: 600;
    color: #212529;
    white-space: nowrap;
    vertical-align: middle;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#createPurchaseOrderModal .po-items-table>tbody>tr>td,
#editPurchaseOrderModal .po-items-table>tbody>tr>td,
#viewPurchaseOrderModal .po-items-table>tbody>tr>td {
    padding: .3125rem .375rem;
    vertical-align: middle;
    background: #fff;
    border-bottom: 1px solid #f1f3f5;
}

#createPurchaseOrderModal .po-items-table>tbody>tr:last-child>td,
#editPurchaseOrderModal .po-items-table>tbody>tr:last-child>td,
#viewPurchaseOrderModal .po-items-table>tbody>tr:last-child>td {
    border-bottom: 0;
}

#createPurchaseOrderModal .po-items-table th:first-child,
#createPurchaseOrderModal .po-items-table td:first-child,
#editPurchaseOrderModal .po-items-table th:first-child,
#editPurchaseOrderModal .po-items-table td:first-child,
#viewPurchaseOrderModal .po-items-table th:first-child,
#viewPurchaseOrderModal .po-items-table td:first-child {
    padding-left: .625rem;
}

#createPurchaseOrderModal .po-items-table th:last-child,
#createPurchaseOrderModal .po-items-table td:last-child,
#editPurchaseOrderModal .po-items-table th:last-child,
#editPurchaseOrderModal .po-items-table td:last-child,
#viewPurchaseOrderModal .po-items-table th:last-child,
#viewPurchaseOrderModal .po-items-table td:last-child {
    padding-right: .625rem;
}

#createPurchaseOrderModal .po-items-table .form-control,
#createPurchaseOrderModal .po-items-table .form-select,
#editPurchaseOrderModal .po-items-table .form-control,
#editPurchaseOrderModal .po-items-table .form-select {
    height: 28px !important;
    min-height: 28px !important;
    padding: .1875rem .375rem;
    font-size: .71875rem !important;
}

#createPurchaseOrderModal .po-items-table .choices__inner,
#editPurchaseOrderModal .po-items-table .choices__inner {
    height: 28px;
    min-height: 28px !important;
    padding: 0 1.5rem 0 .375rem !important;
    font-size: .71875rem !important;
}

#createPurchaseOrderModal .po-items-table .choices__list--single .choices__item,
#editPurchaseOrderModal .po-items-table .choices__list--single .choices__item {
    font-size: .71875rem;
}

/* readonly cells stay white here (only header Order number is greyed) */
#createPurchaseOrderModal .po-items-table input[readonly],
#editPurchaseOrderModal .po-items-table input[readonly] {
    background-color: #fff;
    color: #495057;
}

/* Row action buttons — remove on every row, add on the last row only */
#createPurchaseOrderModal .po-act-cell,
#editPurchaseOrderModal .po-act-cell {
    white-space: nowrap;
}

#createPurchaseOrderModal .po-icon-btn,
#editPurchaseOrderModal .po-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    padding: 0;
    font-size: 15px;
    font-weight: 600;
    line-height: 1;
    color: #fff;
    border: 0;
    border-radius: 4px;
}

#createPurchaseOrderModal .po-icon-btn+.po-icon-btn,
#editPurchaseOrderModal .po-icon-btn+.po-icon-btn {
    margin-left: .375rem;
}

#createPurchaseOrderModal .po-icon-btn--remove,
#editPurchaseOrderModal .po-icon-btn--remove {
    background: #dc3545;
}

#createPurchaseOrderModal .po-icon-btn--remove:hover:not(:disabled),
#editPurchaseOrderModal .po-icon-btn--remove:hover:not(:disabled) {
    background: #bb2d3b;
}

#createPurchaseOrderModal .po-icon-btn--remove:disabled,
#editPurchaseOrderModal .po-icon-btn--remove:disabled {
    opacity: .45;
}

#createPurchaseOrderModal .po-icon-btn--add,
#editPurchaseOrderModal .po-icon-btn--add {
    background: #0d6efd;
}

#createPurchaseOrderModal .po-icon-btn--add:hover,
#editPurchaseOrderModal .po-icon-btn--add:hover {
    background: #0b5ed7;
}

#createPurchaseOrderModal #poItemsBody tr:not(:last-child) .po-add-row,
#editPurchaseOrderModal #editPoItemsBody tr:not(:last-child) .po-add-row {
    visibility: hidden;
}

/* Total strip */
#createPurchaseOrderModal .po-total-bar,
#editPurchaseOrderModal .po-total-bar,
#viewPurchaseOrderModal .po-total-bar {
    padding: .4375rem .75rem;
    font-size: .78125rem;
    font-weight: 600;
    text-align: right;
    color: var(--ds-primary, #004384);
    background: #e7f0fb;
    border-top: 1px solid #dee2e6;
}

#poGrandTotal,
#editPoGrandTotal,
#viewPoGrandTotal {
    font-weight: 700;
    color: var(--ds-primary, #004384);
}

/* ── View only: read-only value boxes, status pill, bill link ── */
#viewPurchaseOrderModal .po-value {
    display: flex;
    align-items: center;
    min-height: 32px;
    padding: .25rem .5rem;
    font-size: .78125rem;
    line-height: 1.4;
    color: #212529;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

#viewPurchaseOrderModal .po-status-pill {
    display: inline-flex;
    align-items: center;
    padding: .1875rem .625rem;
    font-size: .71875rem;
    font-weight: 600;
    border-radius: 999px;
}

#viewPurchaseOrderModal .po-status-pill--approved {
    color: #067647;
    background: #ecfdf3;
}

#viewPurchaseOrderModal .po-status-pill--rejected {
    color: #b42318;
    background: #fef3f2;
}

#viewPurchaseOrderModal .po-status-pill--completed {
    color: var(--ds-primary, #004384);
    background: #e7f0fb;
}

#viewPurchaseOrderModal .po-status-pill--pending {
    color: #b54708;
    background: #fffaeb;
}

#viewPurchaseOrderModal .po-bill-link {
    display: inline-flex;
    align-items: center;
    padding: .3125rem .875rem;
    font-size: .75rem;
    font-weight: 500;
    color: var(--ds-primary, #004384);
    background: #fff;
    border: 1px solid var(--ds-primary, #004384);
    border-radius: 4px;
    text-decoration: none;
}

#viewPurchaseOrderModal .po-items-table>tbody>tr>td {
    font-size: .75rem;
    color: #212529;
}

/* ── Footers ── */
#createPurchaseOrderModal .modal-footer,
#editPurchaseOrderModal .modal-footer,
#viewPurchaseOrderModal .modal-footer {
    gap: .5rem;
    padding: .75rem 1.25rem;
    background: #fff;
    border-top: 1px solid #e9ecef;
}

#createPurchaseOrderModal .modal-footer>*,
#editPurchaseOrderModal .modal-footer>*,
#viewPurchaseOrderModal .modal-footer>* {
    margin: 0;
}

#createPurchaseOrderModal .modal-footer .btn,
#editPurchaseOrderModal .modal-footer .btn,
#viewPurchaseOrderModal .modal-footer .btn {
    padding: .375rem 1.125rem;
    font-size: .8125rem;
    font-weight: 500;
    border-radius: 4px;
}

#createPurchaseOrderModal .modal-footer .po-btn-primary,
#editPurchaseOrderModal .modal-footer .po-btn-primary,
#viewPurchaseOrderModal .modal-footer .po-btn-primary {
    color: #fff;
    background: var(--ds-primary, #004384);
    border: 1px solid var(--ds-primary, #004384);
}

#createPurchaseOrderModal .modal-footer .po-btn-cancel,
#editPurchaseOrderModal .modal-footer .po-btn-cancel,
#viewPurchaseOrderModal .modal-footer .po-btn-cancel {
    color: #dc3545;
    background: #fff;
    border: 1px solid #dc3545;
}
</style>

<div class="modal fade" id="createPurchaseOrderModal" tabindex="-1" aria-labelledby="createPurchaseOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.purchaseorders.store') }}" id="createPOForm"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createPurchaseOrderModalLabel">Create Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="po_number" value="{{ $po_number }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="po-label" for="createPoNumber">Order Number</label>
                            <input type="text" id="createPoNumber" class="form-control po-readonly"
                                value="{{ $po_number }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createPoDate">Order Date<span class="po-req">*</span></label>
                            <div class="po-date-wrap">
                                <input type="date" name="po_date" id="createPoDate" class="form-control po-date"
                                    value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createStoreId">Store</label>
                            <select name="store_id" id="createStoreId" class="form-select">
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createVendorId">Vendor<span class="po-req">*</span></label>
                            <select name="vendor_id" id="createVendorId" class="form-select" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createPaymentCode">Payment Mode</label>
                            <select name="payment_code" id="createPaymentCode" class="form-select">
                                <option value="">Select Mode</option>
                                @foreach($paymentModes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createBillNo">Bill/ Invoice No.</label>
                            <input type="text" name="bill_no" id="createBillNo" class="form-control" maxlength="100"
                                placeholder="e.g. BILL749943">
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createBillDate">Bill Date</label>
                            <div class="po-date-wrap">
                                <input type="date" name="bill_date" id="createBillDate" class="form-control po-date"
                                    max="{{ date('Y-m-d') }}">
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createChallanNo">Challan/ Reference</label>
                            <input type="text" name="challan_no" id="createChallanNo" class="form-control"
                                maxlength="100" placeholder="e.g.">
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="createChallanDate">Challan Date</label>
                            <div class="po-date-wrap">
                                <input type="date" name="challan_date" id="createChallanDate"
                                    class="form-control po-date" max="{{ date('Y-m-d') }}">
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="po-label" for="createBillFileInput">Bill Attachment</label>
                            <input type="file" name="bill_file" id="createBillFileInput" class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>
                    </div>

                    <div class="po-items-title">Order Items</div>

                    <div class="po-items-box">
                        <div class="table-responsive">
                            <table class="table po-items-table" id="poItemsTable">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width:20%;">Item<span class="po-req">*</span></th>
                                        <th scope="col" style="width:10%;">Unit</th>
                                        <th scope="col" style="width:14%;">Code</th>
                                        <th scope="col" style="width:9%;">Qty<span class="po-req">*</span></th>
                                        <th scope="col" style="width:9%;">Rate<span class="po-req">*</span></th>
                                        <th scope="col" style="width:8%;">Tax%</th>
                                        <th scope="col" style="width:14%;">Line Total</th>
                                        <th scope="col" style="width:86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="poItemsBody">
                                    <tr class="po-item-row">
                                        <td>
                                            <select name="items[0][item_subcategory_id]"
                                                class="form-select po-item-select" required aria-label="Select item">
                                                <option value="">Item</option>
                                                @foreach($itemSubcategories as $sub)
                                                <option value="{{ $sub['id'] }}"
                                                    data-unit="{{ e($sub['unit_measurement']) }}"
                                                    data-code="{{ e($sub['item_code']) }}">
                                                    {{ $sub['item_name'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[0][unit]" class="form-control po-unit"
                                                readonly placeholder="-"></td>
                                        <td><input type="text" name="items[0][item_code_display]"
                                                class="form-control po-item-code" readonly placeholder="-"></td>
                                        <td><input type="text" name="items[0][quantity]" class="form-control po-qty"
                                                placeholder="-" required></td>
                                        <td><input type="text" name="items[0][unit_price]"
                                                class="form-control po-unit-price" placeholder="-" required></td>
                                        <td><input type="text" name="items[0][tax_percent]" class="form-control po-tax"
                                                placeholder="-"></td>
                                        <td><input type="text" name="items[0][total_display]"
                                                class="form-control po-line-total" readonly placeholder="-"></td>
                                        <td class="po-act-cell">
                                            <button type="button" class="po-icon-btn po-icon-btn--remove po-remove-row"
                                                title="Remove line" aria-label="Remove line" disabled>&minus;</button>
                                            <button type="button" class="po-icon-btn po-icon-btn--add po-add-row"
                                                title="Add line" aria-label="Add line">+</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="po-total-bar">Total: <span id="poGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn po-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn po-btn-primary">Create Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Purchase Order Modal --}}
<div class="modal fade" id="editPurchaseOrderModal" tabindex="-1" aria-labelledby="editPurchaseOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editPOForm" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editPurchaseOrderModalLabel">Edit Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="po-label" for="editPoNumber">Order Number</label>
                            <input type="text" id="editPoNumber" class="form-control po-readonly" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editPoDate">Order Date<span class="po-req">*</span></label>
                            <div class="po-date-wrap">
                                <input type="date" name="po_date" id="editPoDate" class="form-control po-date"
                                    max="{{ date('Y-m-d') }}" required>
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editStoreId">Store</label>
                            <select name="store_id" id="editStoreId" class="form-select">
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editVendorId">Vendor<span class="po-req">*</span></label>
                            <select name="vendor_id" id="editVendorId" class="form-select" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editPaymentCode">Payment Mode</label>
                            <select name="payment_code" id="editPaymentCode" class="form-select">
                                <option value="">Select Mode</option>
                                @foreach($paymentModes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editBillNo">Bill/ Invoice No.</label>
                            <input type="text" name="bill_no" id="editBillNo" class="form-control" maxlength="100"
                                placeholder="e.g. BILL749943">
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editBillDate">Bill Date</label>
                            <div class="po-date-wrap">
                                <input type="date" name="bill_date" id="editBillDate" class="form-control po-date"
                                    max="{{ date('Y-m-d') }}">
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editChallanNo">Challan/ Reference</label>
                            <input type="text" name="challan_no" id="editChallanNo" class="form-control"
                                maxlength="100" placeholder="e.g.">
                        </div>
                        <div class="col-md-4">
                            <label class="po-label" for="editChallanDate">Challan Date</label>
                            <div class="po-date-wrap">
                                <input type="date" name="challan_date" id="editChallanDate" class="form-control po-date"
                                    max="{{ date('Y-m-d') }}">
                                <span class="po-date-ph">Select Date</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="po-label" for="editBillFileInput">Bill Attachment</label>
                            <div class="po-file-row">
                                <input type="file" name="bill_file" id="editBillFileInput" class="form-control"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <button type="button" class="po-btn-linkish" id="editBillClearBtn">Remove</button>
                                {{-- Only shown when the order already has a stored bill --}}
                                <span class="po-file-current" id="editCurrentBillWrap" style="display:none;">
                                    Current: <span id="editCurrentBillPath">No file chosen</span>
                                    <span id="editCurrentBillLink"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="po-items-title">Order Items</div>

                    <div class="po-items-box">
                        <div class="table-responsive">
                            <table class="table po-items-table" id="editPoItemsTable">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width:20%;">Item<span class="po-req">*</span></th>
                                        <th scope="col" style="width:10%;">Unit</th>
                                        <th scope="col" style="width:14%;">Code</th>
                                        <th scope="col" style="width:9%;">Qty<span class="po-req">*</span></th>
                                        <th scope="col" style="width:9%;">Rate<span class="po-req">*</span></th>
                                        <th scope="col" style="width:8%;">Tax%</th>
                                        <th scope="col" style="width:14%;">Line Total</th>
                                        <th scope="col" style="width:86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editPoItemsBody"></tbody>
                            </table>
                        </div>
                        <div class="po-total-bar">Total: <span id="editPoGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn po-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn po-btn-primary">Update Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Purchase Order Modal (read-only) --}}
<div class="modal fade" id="viewPurchaseOrderModal" tabindex="-1" aria-labelledby="viewPurchaseOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPurchaseOrderModalLabel">Purchase Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="po-label">Order Number</label>
                        <p class="po-value mb-0" id="viewPoNumber">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Order Date</label>
                        <p class="po-value mb-0" id="viewPoDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Store</label>
                        <p class="po-value mb-0" id="viewStoreName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Vendor</label>
                        <p class="po-value mb-0" id="viewVendorName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Payment Mode</label>
                        <p class="po-value mb-0" id="viewPaymentCode">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Bill/ Invoice No.</label>
                        <p class="po-value mb-0" id="viewBillNo">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Bill Date</label>
                        <p class="po-value mb-0" id="viewBillDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Challan/ Reference</label>
                        <p class="po-value mb-0" id="viewChallanNo">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Challan Date</label>
                        <p class="po-value mb-0" id="viewChallanDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="po-label">Status</label>
                        <p class="mb-0"><span class="po-status-pill po-status-pill--pending"
                                id="viewStatus">-</span></p>
                    </div>
                    <div class="col-md-8">
                        <label class="po-label">Bill</label>
                        <p class="mb-0" id="viewBillWrap">
                            <a href="#" id="viewBillLink" target="_blank" rel="noopener" class="po-bill-link"
                                style="display: none;">View / Download Bill</a>
                            <span id="viewBillNone" class="po-file-current">No bill uploaded</span>
                        </p>
                    </div>
                </div>

                <div class="po-items-title">Order Items</div>

                <div class="po-items-box">
                    <div class="table-responsive">
                        <table class="table po-items-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width:20%;">Item</th>
                                    <th scope="col" style="width:10%;">Unit</th>
                                    <th scope="col" style="width:14%;">Code</th>
                                    <th scope="col" style="width:9%;">Qty</th>
                                    <th scope="col" style="width:13%;">Rate</th>
                                    <th scope="col" style="width:10%;">Tax%</th>
                                    <th scope="col" style="width:14%;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody id="viewPoItemsBody"></tbody>
                        </table>
                    </div>
                    <div class="po-total-bar">Total: <span id="viewPoGrandTotal">0.00</span>/-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn po-btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn po-btn-primary btn-print-view-modal"
                    data-print-target="#viewPurchaseOrderModal" title="Print">Print</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    let editModalItems = null;
    const editPoBaseUrl = "{{ url('admin/mess/purchaseorders') }}";
    let itemRowIndex = 1;
    let editItemRowIndex = 0;
    let currentVendorId = null;
    let editCurrentVendorId = null;
    let hasInitialCreateErrors = {{ $errors->any() ? 'true' : 'false' }};

    let choicesInstances = {
        filter: {},
        create: {},
        edit: {},
        items: []
    };

    function safeFocus(el) {
        if (!el || typeof el.focus !== 'function') return;
        try {
            el.focus({
                preventScroll: true
            });
        } catch (e) {
            try {
                el.focus();
            } catch (e2) {}
        }
    }

    /** Pin Choices dropdown for line items inside modal tables (avoids clipping). */
    function bindPoItemChoicesFixedDropdown(selectEl, choices, api) {
        var modalBody = null;
        var placeScheduled = false;

        function getDropdownEl() {
            return choices.dropdown && choices.dropdown.element;
        }

        function place() {
            var dd = getDropdownEl();
            var wrap = api.wrapper;
            if (!dd || !wrap || !wrap.classList.contains('is-open')) return;
            var inner = wrap.querySelector('.choices__inner');
            if (!inner) return;
            var r = inner.getBoundingClientRect();
            var flipped = wrap.classList.contains('is-flipped');
            var margin = 8;
            var spaceBelow = window.innerHeight - r.bottom - margin * 2;
            var spaceAbove = r.top - margin * 2;
            dd.classList.add('po-item-choices-dropdown-fixed');
            dd.style.setProperty('position', 'fixed', 'important');
            dd.style.setProperty('left', Math.max(margin, Math.min(r.left, window.innerWidth - Math.max(r.width,
                200) - margin)) + 'px', 'important');
            dd.style.setProperty('width', Math.max(r.width, 220) + 'px', 'important');
            dd.style.setProperty('max-height', Math.max(120, flipped ? spaceAbove : spaceBelow) + 'px',
            'important');
            dd.style.setProperty('z-index', '200000', 'important');
            if (flipped) {
                dd.style.setProperty('top', 'auto', 'important');
                dd.style.setProperty('bottom', (window.innerHeight - r.top + 2) + 'px', 'important');
            } else {
                dd.style.setProperty('top', (r.bottom + 2) + 'px', 'important');
                dd.style.setProperty('bottom', 'auto', 'important');
            }
        }

        function onScrollOrResize() {
            if (placeScheduled) return;
            placeScheduled = true;
            requestAnimationFrame(function() {
                placeScheduled = false;
                place();
            });
        }

        function onShow() {
            modalBody = selectEl.closest('.modal-body');
            requestAnimationFrame(function() {
                place();
                requestAnimationFrame(place);
            });
            setTimeout(place, 0);
            setTimeout(place, 80);
            window.addEventListener('resize', onScrollOrResize, {
                passive: true
            });
            document.addEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.addEventListener('scroll', onScrollOrResize, {
                passive: true
            });
        }

        function onHide() {
            var dd = getDropdownEl();
            if (dd) {
                dd.classList.remove('po-item-choices-dropdown-fixed');
                ['position', 'left', 'top', 'right', 'bottom', 'width', 'max-height', 'z-index'].forEach(function(
                p) {
                    dd.style.removeProperty(p);
                });
            }
            window.removeEventListener('resize', onScrollOrResize);
            document.removeEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.removeEventListener('scroll', onScrollOrResize);
            modalBody = null;
        }
        selectEl.addEventListener('showDropdown', onShow);
        selectEl.addEventListener('hideDropdown', onHide);
    }

    function createChoicesInstance(selectEl, settings) {
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.choicesInstance) return selectEl.choicesInstance;
        settings = settings || {};
        var isMulti = !!selectEl.multiple;

        var choiceConfig = {
            allowHTML: false,
            itemSelectText: '',
            shouldSort: false,
            searchEnabled: settings.searchEnabled !== false,
            searchChoices: settings.searchChoices !== false,
            searchFloor: typeof settings.searchFloor === 'number' ? settings.searchFloor : 0,
            searchResultLimit: typeof settings.maxOptions === 'number' ? settings.maxOptions : -1,
            placeholder: true,
            placeholderValue: settings.placeholder || (selectEl.getAttribute('data-placeholder') || selectEl
                .getAttribute('placeholder') || ''),
            searchPlaceholderValue: '',
            removeItemButton: isMulti
        };

        var choices = new window.Choices(selectEl, choiceConfig);
        var api = {
            _choices: choices,
            selectEl: selectEl,
            input: selectEl,
            settings: settings,
            activeOption: null,
            items: [],
            wrapper: choices.containerOuter ? choices.containerOuter.element : null,
            control_input: null,
            getValue: function() {
                if (!this.selectEl) return isMulti ? [] : '';
                if (isMulti) {
                    try {
                        var v = this._choices.getValue(true);
                        if (Array.isArray(v)) return v.map(String).filter(Boolean);
                        return v ? [String(v)] : [];
                    } catch (e) {
                        return Array.from(this.selectEl.selectedOptions).map(function(o) {
                            return o.value;
                        }).filter(Boolean);
                    }
                }
                return this.selectEl.value || '';
            },
            setValue: function(v) {
                this._choices.removeActiveItems();
                if (isMulti) {
                    var arr = Array.isArray(v) ? v : (v !== '' && v !== null && typeof v !== 'undefined' ? [
                        v
                    ] : []);
                    arr.forEach(function(x) {
                        if (x === '' || x === null || typeof x === 'undefined') return;
                        try {
                            this._choices.setChoiceByValue(String(x));
                        } catch (e) {}
                    }, this);
                } else {
                    var value = (v === null || typeof v === 'undefined') ? '' : String(v);
                    if (value !== '') this._choices.setChoiceByValue(value);
                }
                this.syncItems();
            },
            clear: function() {
                this._choices.removeActiveItems();
                this.syncItems();
            },
            addOption: function(opt) {
                if (!opt) return;
                var val = (opt.value === null || typeof opt.value === 'undefined') ? '' : String(opt.value);
                this._choices.setChoices([{
                    value: val,
                    label: opt.text || val,
                    selected: false,
                    disabled: false
                }], 'value', 'label', false);
            },
            destroy: function() {
                if (this._choices) this._choices.destroy();
                if (this.selectEl) {
                    this.selectEl.choicesInstance = null;
                    this.selectEl.tomselect = null;
                }
            },
            setTextboxValue: function(v) {
                if (this.control_input) this.control_input.value = v || '';
            },
            onSearchChange: function() {},
            refreshOptions: function() {},
            syncItems: function() {
                var v = this.getValue();
                if (isMulti) {
                    this.items = Array.isArray(v) ? v.map(String) : [];
                } else {
                    this.items = (v === '' || v === null || typeof v === 'undefined') ? [] : [String(v)];
                }
            }
        };
        api.control_input = api.wrapper ? api.wrapper.querySelector('input.choices__input--cloned') : null;
        if (api.wrapper && api.wrapper.classList) api.wrapper.classList.add('ts-wrapper');
        if (choices.dropdown && choices.dropdown.element && choices.dropdown.element.classList) {
            choices.dropdown.element.classList.add('ts-dropdown');
        }
        api.syncItems();

        selectEl.addEventListener('change', function() {
            api.syncItems();
        });
        selectEl.addEventListener('showDropdown', function() {
            if (typeof settings.onDropdownOpen === 'function') {
                settings.onDropdownOpen.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        selectEl.addEventListener('hideDropdown', function() {
            if (typeof settings.onDropdownClose === 'function') {
                settings.onDropdownClose.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        if (typeof settings.onInitialize === 'function') settings.onInitialize.call(api);

        if (selectEl.classList.contains('po-item-select')) {
            bindPoItemChoicesFixedDropdown(selectEl, choices, api);
        }

        selectEl.choicesInstance = api;
        selectEl.tomselect = api;
        return api;
    }

    function createBlankSearchConfig(extra) {
        return Object.assign({
            allowEmptyOption: true,
            dropdownParent: 'body',
            searchField: ['text'],
            controlInput: '<input>',
            highlight: false,
            onInitialize: function() {
                this.activeOption = null;
            },
            onDropdownOpen: function(dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = {
                    scrollTop: modalBody.scrollTop
                };

                function clearInputAndCursor() {
                    var input = (dropdown && dropdown.querySelector('input.choices__input--cloned')) ||
                        (dropdown && dropdown.querySelector('input')) ||
                        self.control_input;
                    if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                    if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                    if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                    if (input) {
                        input.style.display = 'block';
                        input.style.visibility = 'visible';
                        input.style.opacity = '1';
                        input.value = '';
                        safeFocus(input);
                        try {
                            input.setSelectionRange(0, 0);
                        } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                        .scrollTop === 'number') {
                        modalBody.scrollTop = self._modalDropdownState.scrollTop;
                    }
                }
                if (self.settings && self.settings.clearOnOpen) {
                    self.clear(true);
                }
                clearInputAndCursor();
                setTimeout(clearInputAndCursor, 0);
                setTimeout(clearInputAndCursor, 50);
                setTimeout(clearInputAndCursor, 100);
                if (dropdown) {
                    setTimeout(function() {
                        var opts = dropdown.querySelectorAll(
                            '.option.active, .option.selected, .option[aria-selected="true"], .choices__item--selectable[aria-selected="true"]'
                            );
                        opts.forEach(function(opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            },
            onDropdownClose: function() {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                if (helper && modalEl) {
                    helper.onClose(modalEl, self._modalDropdownState);
                } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                    .scrollTop === 'number') {
                    modalBody.scrollTop = self._modalDropdownState.scrollTop;
                }
                self._modalDropdownState = null;
            }
        }, extra || {});
    }

    function initChoicesSingle(selectEl, opts) {
        opts = opts || {};
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.tomselect) {
            try {
                selectEl.tomselect.destroy();
            } catch (e) {}
        }
        var base = createBlankSearchConfig({
            placeholder: opts.placeholder || 'Select',
            maxOptions: opts.maxOptions,
            clearOnOpen: opts.clearOnOpen === true
        });
        return createChoicesInstance(selectEl, Object.assign(base, opts));
    }

    // List filters: Tom Select multiselect + search (matches Item Report / store multiselect pattern)
    function initFilterDropdowns() {
        var filterVendor = document.getElementById('poFilterVendor');
        var filterStore = document.getElementById('poFilterStore');
        if (typeof window.TomSelect === 'undefined') return;
        var tsOpts = {
            dropdownParent: 'body',
            maxItems: null,
            maxOptions: 500,
            plugins: ['remove_button', 'dropdown_input'],
            sortField: {
                field: 'text',
                direction: 'asc'
            },
            closeAfterSelect: false
        };
        if (filterVendor && !filterVendor.tomselect) {
            var phV = filterVendor.getAttribute('data-placeholder') || 'All vendors';
            choicesInstances.filter.vendor = new TomSelect(filterVendor, Object.assign({}, tsOpts, {
                placeholder: phV
            }));
        }
        if (filterStore && !filterStore.tomselect) {
            var phS = filterStore.getAttribute('data-placeholder') || 'All stores';
            choicesInstances.filter.store = new TomSelect(filterStore, Object.assign({}, tsOpts, {
                placeholder: phS
            }));
        }
    }

    function initCreateModalDropdowns() {
        var createStore = document.querySelector('#createPurchaseOrderModal select[name="store_id"]');
        var createVendor = document.querySelector('#createPurchaseOrderModal select[name="vendor_id"]');
        var createPayment = document.querySelector('#createPurchaseOrderModal select[name="payment_code"]');
        if (createStore) {
            choicesInstances.create.store = initChoicesSingle(createStore, {
                placeholder: 'Select Store'
            });
        }
        if (createVendor) {
            choicesInstances.create.vendor = initChoicesSingle(createVendor, {
                placeholder: 'Select Vendor'
            });
        }
        if (createPayment) {
            choicesInstances.create.payment = initChoicesSingle(createPayment, {
                placeholder: 'Select Mode'
            });
        }
    }

    function initEditModalDropdowns() {
        var editStore = document.getElementById('editStoreId');
        var editVendor = document.getElementById('editVendorId');
        var editPayment = document.getElementById('editPaymentCode');
        if (editStore) {
            choicesInstances.edit.store = initChoicesSingle(editStore, {
                placeholder: 'Select Store'
            });
        }
        if (editVendor) {
            choicesInstances.edit.vendor = initChoicesSingle(editVendor, {
                placeholder: 'Select Vendor'
            });
        }
        if (editPayment) {
            choicesInstances.edit.payment = initChoicesSingle(editPayment, {
                placeholder: 'Select Payment Mode'
            });
        }
    }

    // Create modal shows a bare "Item" placeholder (per design); Edit keeps "Select Item".
    function itemPlaceholder(isEditModal) {
        return isEditModal ? 'Select Item' : 'Item';
    }

    function refreshRowItemChoices(select, itemsToUse, currentValue) {
        var api = select.tomselect;
        var multi = !!select.multiple;
        var selectedIds = multi ?
            (Array.isArray(currentValue) ? currentValue.map(String) : []) :
            (currentValue ? [String(currentValue)] : []);
        var selSet = new Set(selectedIds);
        var list = [{
            value: '',
            label: itemPlaceholder(select.closest('#editPoItemsBody') !== null),
            disabled: true,
            selected: false
        }];
        itemsToUse.forEach(function(item) {
            var sid = String(item.id);
            list.push({
                value: sid,
                label: item.item_name || '—',
                selected: selSet.has(sid)
            });
        });
        api._choices.clearChoices();
        api._choices.setChoices(list, 'value', 'label', true);
        itemsToUse.forEach(function(item) {
            var opt = select.querySelector('option[value="' + String(item.id).replace(/"/g, '\\"') + '"]');
            if (opt) {
                opt.setAttribute('data-unit', item.unit_measurement || '');
                opt.setAttribute('data-code', item.item_code || '');
            }
        });
        if (multi) {
            selectedIds.forEach(function(id) {
                try {
                    api._choices.setChoiceByValue(String(id));
                } catch (e) {}
            });
        } else if (currentValue) {
            try {
                api._choices.setChoiceByValue(String(currentValue));
            } catch (e) {}
        }
        api.syncItems();
    }

    function initItemDropdownInRow(row) {
        var select = row.querySelector('.po-item-select');
        if (select && !select.tomselect) {
            var hadValueBefore = select.multiple ?
                (select.selectedOptions && select.selectedOptions.length > 0) :
                !!select.value;

            // Proper Choices.js config for multi-select
            var instance = createChoicesInstance(select, {
                placeholder: itemPlaceholder(row.closest('#editPoItemsBody') !== null),
                maxOptions: 200,
                searchEnabled: true,
                searchChoices: true,
                searchFloor: 0,
                removeItemButton: !!select.multiple,
                shouldSort: false,
                itemSelectText: '',
                allowHTML: false
            });

            if (instance) {
                choicesInstances.items.push(instance);
                if (!hadValueBefore) {
                    instance.clear();
                }

                // Note: Change event is already handled by delegated listener on tbody
            }
        }
    }

    function initAllItemDropdowns(tbody) {
        tbody.querySelectorAll('.po-item-row').forEach(function(row) {
            initItemDropdownInRow(row);
        });
    }

    function destroyAllItemDropdowns() {
        choicesInstances.items.forEach(function(instance) {
            if (instance) instance.destroy();
        });
        choicesInstances.items = [];
    }

    function findItemMeta(id, isEditModal) {
        var list = isEditModal ?
            (editModalItems && editModalItems.length ? editModalItems : itemSubcategories) :
            filteredItems;
        return (list || []).find(function(s) {
            return String(s.id) === String(id);
        });
    }

    function reindexPoItemRows(tbody, isEdit) {
        tbody.querySelectorAll('.po-item-select').forEach(function(sel) {
            if (sel.tomselect) sel.tomselect.destroy();
        });
        choicesInstances.items = choicesInstances.items.filter(function(inst) {
            return !(inst.selectEl && tbody.contains(inst.selectEl));
        });
        var rows = tbody.querySelectorAll('.po-item-row');
        rows.forEach(function(row, i) {
            row.querySelectorAll('[name^="items["]').forEach(function(el) {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
            initItemDropdownInRow(row);
            updateUnitAndCode(row);
            calcLineTotal(row);
        });
        if (isEdit) {
            editItemRowIndex = rows.length;
            updateEditRemoveButtons();
            updateEditGrandTotal();
        } else {
            itemRowIndex = rows.length;
            updateRemoveButtons();
            updateGrandTotal();
        }
    }

    function maybeSplitMultiItemRow(row) {
        var select = row.querySelector('.po-item-select');
        if (!select || !select.multiple) return false;
        var vals = Array.from(select.selectedOptions).map(function(o) {
            return o.value;
        }).filter(Boolean);
        if (vals.length <= 1) return false;
        var tbody = row.closest('tbody');
        if (!tbody) return false;
        var isEdit = tbody.id === 'editPoItemsBody';
        var qty = (row.querySelector('.po-qty') || {}).value || '';
        var price = (row.querySelector('.po-unit-price') || {}).value || '';
        var tax = (row.querySelector('.po-tax') || {}).value || '0';
        if (select.tomselect) select.tomselect.destroy();
        choicesInstances.items = choicesInstances.items.filter(function(inst) {
            return inst.selectEl !== select;
        });
        var rowsSnap = Array.prototype.slice.call(tbody.querySelectorAll('.po-item-row'));
        var rowIndex = rowsSnap.indexOf(row);
        row.remove();
        vals.forEach(function(id, j) {
            var meta = findItemMeta(id, isEdit);
            var editItem = {
                item_subcategory_id: id,
                quantity: qty,
                unit_price: price,
                tax_percent: tax,
                total_price: '',
                unit: meta ? meta.unit_measurement : '',
                item_code: meta ? meta.item_code : ''
            };
            var tpl = document.createElement('template');
            tpl.innerHTML = getItemRowHtml(0, editItem, isEdit).trim();
            var newRow = tpl.content.firstElementChild;
            var ref = tbody.children[rowIndex + j] || null;
            tbody.insertBefore(newRow, ref);
        });
        reindexPoItemRows(tbody, isEdit);
        return true;
    }

    function getItemRowHtml(index, editItem, isEditModal) {
        const selected = editItem && editItem.item_subcategory_id ? editItem.item_subcategory_id : '';
        const itemsToUse = isEditModal ? (editModalItems && editModalItems.length ? editModalItems :
            itemSubcategories) : filteredItems;
        const options = itemsToUse.map(s =>
            `<option value="${s.id}" data-unit="${(s.unit_measurement || '').replace(/"/g, '&quot;')}" data-code="${(s.item_code || '').replace(/"/g, '&quot;')}" ${s.id == selected ? 'selected' : ''}>${(s.item_name || '—').replace(/</g, '&lt;')}</option>`
        ).join('');
        const qty = editItem ? editItem.quantity : '';
        const price = editItem ? editItem.unit_price : '';
        // New rows start blank so the "-" placeholder shows (spec).
        const tax = editItem ? editItem.tax_percent : '';
        const unit = editItem && editItem.unit ? editItem.unit.replace(/"/g, '&quot;') : '';
        const code = editItem && editItem.item_code ? editItem.item_code.replace(/"/g, '&quot;') : '';
        const lineTotal = editItem ? editItem.total_price : '';
        // Create and Edit share one row shape — remove on every row, add on the last.
        return `
        <tr class="po-item-row ${isEditModal ? 'edit-po-item-row' : ''}">
            <td>
                <select name="items[${index}][item_subcategory_id]" class="form-select po-item-select" required aria-label="Select item for this line">
                    <option value="">${itemPlaceholder(isEditModal)}</option>
                    ${options}
                </select>
            </td>
            <td><input type="text" name="items[${index}][unit]" class="form-control po-unit" readonly placeholder="-" value="${unit}"></td>
            <td><input type="text" class="form-control po-item-code" readonly placeholder="-" value="${code}"></td>
            <td><input type="text" name="items[${index}][quantity]" class="form-control po-qty" placeholder="-" value="${qty}" required></td>
            <td><input type="text" name="items[${index}][unit_price]" class="form-control po-unit-price" placeholder="-" value="${price}" required></td>
            <td><input type="text" name="items[${index}][tax_percent]" class="form-control po-tax" max="100" placeholder="-" value="${tax}"></td>
            <td><input type="text" class="form-control po-line-total" readonly placeholder="-" value="${lineTotal}"></td>
            <td class="po-act-cell"><button type="button" class="po-icon-btn po-icon-btn--remove po-remove-row" title="Remove line" aria-label="Remove line">&minus;</button><button type="button" class="po-icon-btn po-icon-btn--add po-add-row" title="Add line" aria-label="Add line">+</button></td>
        </tr>`;
    }

    function fetchVendorItems(vendorId, callback) {
        if (!vendorId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }

        fetch(`{{ url('admin/mess/purchaseorders/vendor') }}/${vendorId}/items`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                filteredItems = data;
                if (callback) callback();
            })
            .catch(err => {
                console.error(err);
                filteredItems = itemSubcategories || [];
                if (callback) callback();
            });
    }

    function updateItemDropdowns(tbody, isEditModal) {
        var rows = tbody.querySelectorAll('.po-item-row');
        var itemsToUse = isEditModal ? (editModalItems && editModalItems.length ? editModalItems :
            itemSubcategories) : filteredItems;
        rows.forEach(function(row) {
            var select = row.querySelector('.po-item-select');
            if (!select) return;
            var currentValue;
            if (select.multiple) {
                currentValue = Array.from(select.selectedOptions).map(function(o) {
                    return o.value;
                }).filter(Boolean);
            } else {
                currentValue = select.tomselect ? select.tomselect.getValue() : select.value;
            }
            if (select.tomselect && select.tomselect._choices) {
                refreshRowItemChoices(select, itemsToUse, currentValue);
            } else {
                select.innerHTML = '<option value="">' + itemPlaceholder(isEditModal) + '</option>';
                itemsToUse.forEach(function(item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.item_name || '—';
                    option.setAttribute('data-unit', item.unit_measurement || '');
                    option.setAttribute('data-code', item.item_code || '');
                    if (select.multiple) {
                        if (Array.isArray(currentValue) && currentValue.some(function(v) {
                                return String(v) === String(item.id);
                            })) {
                            option.selected = true;
                        }
                    } else if (String(item.id) === String(currentValue)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                // Properly initialize Choices with correct config
                var instance = createChoicesInstance(select, {
                    placeholder: 'Select Item',
                    maxOptions: 200,
                    searchEnabled: true,
                    searchChoices: true,
                    searchFloor: 0,
                    removeItemButton: !!select.multiple,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false
                });

                if (instance) {
                    choicesInstances.items.push(instance);
                }
            }
            updateUnitAndCode(row);
        });
    }

    function updateUnitAndCode(row) {
        var select = row.querySelector('.po-item-select');
        if (!select) return;
        var ids;
        if (select.multiple) {
            ids = Array.from(select.selectedOptions).map(function(o) {
                return o.value;
            }).filter(Boolean);
        } else {
            var sv = select.tomselect ? select.tomselect.getValue() : select.value;
            ids = sv ? [String(sv)] : [];
        }
        var unitInput = row.querySelector('.po-unit');
        var codeInput = row.querySelector('.po-item-code');
        if (ids.length === 0) {
            if (unitInput) unitInput.value = '';
            if (codeInput) codeInput.value = '';
            return;
        }
        var opt = select.querySelector('option[value="' + String(ids[0]).replace(/\\/g, '\\\\').replace(/"/g,
            '\\"') + '"]');
        if (unitInput) unitInput.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        if (codeInput) codeInput.value = opt && opt.dataset.code ? opt.dataset.code : '';
    }

    function calcLineTotal(row) {
        const qty = parseFloat(row.querySelector('.po-qty').value) || 0;
        const price = parseFloat(row.querySelector('.po-unit-price').value) || 0;
        const tax = parseFloat(row.querySelector('.po-tax').value) || 0;
        const total = qty * price * (1 + tax / 100);
        const totalInput = row.querySelector('.po-line-total');
        if (totalInput) totalInput.value = total.toFixed(2);
    }

    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#poItemsBody .po-item-row').forEach(row => {
            const totalInput = row.querySelector('.po-line-total');
            if (totalInput && totalInput.value) sum += parseFloat(totalInput.value) || 0;
        });
        const el = document.getElementById('poGrandTotal');
        // Rendered as "Total: 0.00/-" — the label and the /- suffix live in the markup.
        if (el) el.textContent = sum.toFixed(2);
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#poItemsBody .po-item-row');
        rows.forEach((row, i) => {
            const btn = row.querySelector('.po-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    // Native date inputs have no placeholder — overlay "Select Date" while empty.
    function syncPoDatePlaceholders() {
        document.querySelectorAll(
            '#createPurchaseOrderModal .po-date-wrap, #editPurchaseOrderModal .po-date-wrap'
        ).forEach(function(wrap) {
            var input = wrap.querySelector('.po-date');
            if (input) wrap.classList.toggle('is-empty', !input.value);
        });
    }
    window.syncPoDatePlaceholders = syncPoDatePlaceholders;

    (function() {
        ['createPurchaseOrderModal', 'editPurchaseOrderModal'].forEach(function(id) {
            var modalEl = document.getElementById(id);
            if (!modalEl) return;
            ['input', 'change'].forEach(function(evt) {
                modalEl.addEventListener(evt, function(e) {
                    if (e.target && e.target.classList && e.target.classList.contains('po-date')) {
                        syncPoDatePlaceholders();
                    }
                });
            });
            // after the show handlers have run form.reset() / populated fields (neither fires events)
            modalEl.addEventListener('show.bs.modal', function() {
                setTimeout(syncPoDatePlaceholders, 0);
            });
            modalEl.addEventListener('shown.bs.modal', syncPoDatePlaceholders);
        });
        syncPoDatePlaceholders();
    })();

    // Vendor selection change in CREATE modal
    document.addEventListener('DOMContentLoaded', function() {
        const createVendorSelect = document.querySelector(
            '#createPurchaseOrderModal select[name="vendor_id"]');
        if (createVendorSelect) {
            createVendorSelect.addEventListener('change', function() {
                const vendorId = this.value;
                currentVendorId = vendorId;

                if (!vendorId) {
                    filteredItems = itemSubcategories;
                    const tbody = document.getElementById('poItemsBody');
                    updateItemDropdowns(tbody, false);
                    return;
                }

                fetchVendorItems(vendorId, function() {
                    const tbody = document.getElementById('poItemsBody');
                    updateItemDropdowns(tbody, false);
                });
            });
        }
    });

    // Create modal has no toolbar "Add line" button — the last row's blue + adds the next line.
    function addCreateItemRow() {
        const tbody = document.getElementById('poItemsBody');
        if (!tbody) return null;
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(itemRowIndex, null, false));
        const newRow = tbody.lastElementChild;
        initItemDropdownInRow(newRow);
        itemRowIndex++;
        updateRemoveButtons();
        return newRow;
    }
    window.addCreatePoItemRow = addCreateItemRow;

    const legacyAddPoItemRow = document.getElementById('addPoItemRow');
    if (legacyAddPoItemRow) legacyAddPoItemRow.addEventListener('click', addCreateItemRow);

    document.getElementById('poItemsBody').addEventListener('change', function(e) {
        // Some browsers/users (spinner, blur) trigger change more reliably than input
        if (
            e.target.classList.contains('po-item-select') ||
            e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-unit-price') ||
            e.target.classList.contains('po-tax')
        ) {
            const row = e.target.closest('.po-item-row');
            if (!row) return;
            if (e.target.classList.contains('po-item-select')) {
                if (maybeSplitMultiItemRow(row)) return;
                updateUnitAndCode(row);
            }
            calcLineTotal(row);
            updateGrandTotal();
        }
    });

    document.getElementById('poItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('po-qty') || e.target.classList.contains('po-unit-price') || e
            .target.classList.contains('po-tax')) {
            const row = e.target.closest('.po-item-row');
            if (row) {
                calcLineTotal(row);
                updateGrandTotal();
            }
        }
    });

    document.getElementById('poItemsBody').addEventListener('click', function(e) {
        // closest() (not e.target) so a click on the glyph inside the button still counts
        if (e.target.closest('.po-add-row')) {
            addCreateItemRow();
            return;
        }
        if (e.target.closest('.po-remove-row')) {
            const row = e.target.closest('.po-item-row');
            if (row && document.querySelectorAll('#poItemsBody .po-item-row').length > 1) {
                row.remove();
                updateGrandTotal();
                updateRemoveButtons();
            }
        }
    });

    // Edit modal: grand total and remove buttons
    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editPoItemsBody .po-item-row').forEach(row => {
            const totalInput = row.querySelector('.po-line-total');
            if (totalInput && totalInput.value) sum += parseFloat(totalInput.value) || 0;
        });
        const el = document.getElementById('editPoGrandTotal');
        // Rendered as "Total: 0.00/-" — label and /- suffix live in the markup.
        if (el) el.textContent = sum.toFixed(2);
    }

    function updateEditRemoveButtons() {
        const rows = document.querySelectorAll('#editPoItemsBody .po-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.po-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    // View button: fetch PO and open view modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-po');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const poId = btn.getAttribute('data-po-id');
        fetch(editPoBaseUrl + '/' + poId + '/edit', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                const po = data.po;
                const items = data.items || [];
                document.getElementById('viewPoNumber').textContent = po.po_number || '-';
                document.getElementById('viewPoDate').textContent = po.po_date ? new Date(po.po_date)
                    .toLocaleDateString('en-IN') : '-';
                document.getElementById('viewStoreName').textContent = po.store_name || '-';
                document.getElementById('viewVendorName').textContent = po.vendor_name || '-';
                document.getElementById('viewPaymentCode').textContent = po.payment_code || '-';
                document.getElementById('viewBillNo').textContent = po.bill_no || '-';
                document.getElementById('viewBillDate').textContent = po.bill_date ? new Date(po
                    .bill_date).toLocaleDateString('en-IN') : '-';
                document.getElementById('viewChallanNo').textContent = po.challan_no || '-';
                document.getElementById('viewChallanDate').textContent = po.challan_date ? new Date(po
                    .challan_date).toLocaleDateString('en-IN') : '-';
                const billLink = document.getElementById('viewBillLink');
                const billNone = document.getElementById('viewBillNone');
                if (po.bill_url) {
                    billLink.href = po.bill_url;
                    billLink.style.display = '';
                    if (billNone) billNone.style.display = 'none';
                } else {
                    billLink.href = '#';
                    billLink.style.display = 'none';
                    if (billNone) billNone.style.display = '';
                }
                const statusEl = document.getElementById('viewStatus');
                statusEl.textContent = (po.status || '-').charAt(0).toUpperCase() + (po.status || '')
                    .slice(1);
                statusEl.className = 'po-status-pill po-status-pill--' + (['approved', 'rejected',
                    'completed'].indexOf(po.status) !== -1 ? po.status : 'pending');
                const tbody = document.getElementById('viewPoItemsBody');
                tbody.innerHTML = '';
                let grandTotal = 0;
                items.forEach(item => {
                    grandTotal += parseFloat(item.total_price) || 0;
                    tbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${escapeHtml(item.item_name || '-')}</td>
                                <td>${escapeHtml(item.unit || '-')}</td>
                                <td>${escapeHtml(item.item_code || '-')}</td>
                                <td>${escapeHtml(String(item.quantity ?? '-'))}</td>
                                <td>${(parseFloat(item.unit_price) || 0).toFixed(2)}</td>
                                <td>${(parseFloat(item.tax_percent) || 0).toFixed(2)}</td>
                                <td>${(parseFloat(item.total_price) || 0).toFixed(2)}</td>
                            </tr>`);
                });
                // Rendered as "Total: 0.00/-" — label and /- suffix live in the markup.
                document.getElementById('viewPoGrandTotal').textContent = grandTotal.toFixed(2);
                new bootstrap.Modal(document.getElementById('viewPurchaseOrderModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load purchase order.');
            });
    }, true);

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Vendor selection change in EDIT modal: load vendor-mapped items and refresh dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const editVendorSelect = document.querySelector('#editPurchaseOrderModal select[name="vendor_id"]');
        if (editVendorSelect) {
            editVendorSelect.addEventListener('change', function() {
                const vendorId = this.value;
                editCurrentVendorId = vendorId;
                const tbody = document.getElementById('editPoItemsBody');

                if (!vendorId) {
                    editModalItems = itemSubcategories;
                    updateItemDropdowns(tbody, true);
                    return;
                }

                fetchVendorItems(vendorId, function() {
                    const currentIds = [];
                    tbody.querySelectorAll('.po-item-select').forEach(sel => {
                        if (sel.multiple) {
                            Array.from(sel.selectedOptions).forEach(o => {
                                if (o.value) currentIds.push(o.value);
                            });
                        } else {
                            const v = sel.tomselect ? sel.tomselect.getValue() : sel
                                .value;
                            if (v) currentIds.push(v);
                        }
                    });
                    const merged = (filteredItems || []).slice();
                    currentIds.forEach(id => {
                        if (id && !merged.some(m => m.id == id)) {
                            const fromAll = itemSubcategories.find(s => s.id == id);
                            if (fromAll) merged.push(fromAll);
                        }
                    });
                    editModalItems = merged.length ? merged : itemSubcategories;
                    updateItemDropdowns(tbody, true);
                });
            });
        }
    });

    // Edit button: fetch PO and open modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-po');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const poId = btn.getAttribute('data-po-id');
        fetch(editPoBaseUrl + '/' + poId + '/edit', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                const po = data.po;
                const items = data.items || [];
                document.getElementById('editPOForm').action = editPoBaseUrl + '/' + poId;
                document.getElementById('editPoNumber').value = po.po_number || '';
                document.getElementById('editPoDate').value = po.po_date || '';
                var storeVal = (po.store_id != null && po.store_id !== '') ? String(po.store_id) : '';
                var vendorVal = (po.vendor_id != null && po.vendor_id !== '') ? String(po.vendor_id) :
                    '';
                var paymentVal = (po.payment_code != null && po.payment_code !== '') ? String(po
                    .payment_code) : '';
                if (choicesInstances.edit.store) {
                    choicesInstances.edit.store.setValue(storeVal);
                } else {
                    document.getElementById('editStoreId').value = storeVal;
                }
                if (choicesInstances.edit.vendor) {
                    choicesInstances.edit.vendor.setValue(vendorVal);
                } else {
                    document.getElementById('editVendorId').value = vendorVal;
                }
                if (choicesInstances.edit.payment) {
                    choicesInstances.edit.payment.setValue(paymentVal);
                } else {
                    document.getElementById('editPaymentCode').value = paymentVal;
                }
                document.getElementById('editBillNo').value = po.bill_no || '';
                const editBillDateEl = document.getElementById('editBillDate');
                if (editBillDateEl) editBillDateEl.value = po.bill_date || '';
                document.getElementById('editChallanNo').value = po.challan_no || '';
                const editChallanDateEl = document.getElementById('editChallanDate');
                if (editChallanDateEl) editChallanDateEl.value = po.challan_date || '';
                var editBillPathEl = document.getElementById('editCurrentBillPath');
                var editBillWrapEl = document.getElementById('editCurrentBillWrap');
                if (editBillPathEl) {
                    editBillPathEl.textContent = po.bill_path ? (po.bill_path.split('/').pop() || po
                        .bill_path) : 'No file chosen';
                }
                // The "Current: …" hint only makes sense when a bill is already stored —
                // otherwise it duplicates the file input's own "No file chosen".
                if (editBillWrapEl) editBillWrapEl.style.display = po.bill_path ? '' : 'none';
                var editBillFileInput = document.getElementById('editBillFileInput');
                if (editBillFileInput) {
                    editBillFileInput.value = '';
                }
                var editBillLinkEl = document.getElementById('editCurrentBillLink');
                if (editBillLinkEl) {
                    if (po.bill_url) {
                        editBillLinkEl.innerHTML = ' · <a href="' + escapeHtml(po.bill_url) +
                            '" target="_blank" rel="noopener">View</a>';
                    } else {
                        editBillLinkEl.innerHTML = '';
                    }
                }
                editCurrentVendorId = po.vendor_id;

                function buildEditRows(vendorItemList) {
                    const merged = (vendorItemList || []).slice();
                    items.forEach(poItem => {
                        const id = poItem.item_subcategory_id;
                        if (id && !merged.some(m => m.id == id)) {
                            const fromAll = itemSubcategories.find(s => s.id == id);
                            if (fromAll) merged.push(fromAll);
                        }
                    });
                    editModalItems = merged.length ? merged : itemSubcategories;

                    // Destroy existing item dropdowns
                    destroyAllItemDropdowns();

                    const tbody = document.getElementById('editPoItemsBody');
                    tbody.innerHTML = '';
                    if (items.length === 0) {
                        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, true));
                        editItemRowIndex = 1;
                    } else {
                        items.forEach((item, i) => {
                            tbody.insertAdjacentHTML('beforeend', getItemRowHtml(i, item,
                            true));
                        });
                        editItemRowIndex = items.length;
                    }

                    // Initialize Choices for all item dropdowns
                    initAllItemDropdowns(tbody);
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                    new bootstrap.Modal(document.getElementById('editPurchaseOrderModal')).show();
                }

                // Show modal immediately with all items; vendor-specific list loads in background
                buildEditRows(itemSubcategories);
                if (po.vendor_id) {
                    fetchVendorItems(po.vendor_id, function() {
                        const tbody = document.getElementById('editPoItemsBody');
                        if (tbody && document.getElementById('editPurchaseOrderModal').classList
                            .contains('show')) {
                            editModalItems = (filteredItems && filteredItems.length) ?
                                filteredItems : itemSubcategories;
                            updateItemDropdowns(tbody, true);
                        }
                    });
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load purchase order.');
            });
    }, true);

    // Edit modal has no toolbar "Add line" button either — the last row's blue + adds the next line.
    function addEditItemRow() {
        const tbody = document.getElementById('editPoItemsBody');
        if (!tbody) return null;
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(editItemRowIndex, null, true));
        const newRow = tbody.lastElementChild;
        initItemDropdownInRow(newRow);
        editItemRowIndex++;
        updateEditRemoveButtons();
        return newRow;
    }
    window.addEditPoItemRow = addEditItemRow;

    const legacyAddEditPoItemRow = document.getElementById('addEditPoItemRow');
    if (legacyAddEditPoItemRow) legacyAddEditPoItemRow.addEventListener('click', addEditItemRow);

    var createBillFileInputEl = document.getElementById('createBillFileInput');
    var createBillClearBtnEl = document.getElementById('createBillClearBtn');
    if (createBillClearBtnEl && createBillFileInputEl) {
        createBillClearBtnEl.addEventListener('click', function() {
            createBillFileInputEl.value = '';
        });
    }

    // Bill file client-side validation (extension & size)
    function validateBillFileInput(fileInput, pathLabelEl) {
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }
        var file = fileInput.files[0];
        var allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        var nameParts = file.name.split('.');
        var ext = nameParts.length > 1 ? nameParts.pop().toLowerCase() : '';
        var maxBytes = 5 * 1024 * 1024; // 5 MB

        if (!allowedExt.includes(ext)) {
            alert('Only PDF, JPG, JPEG, PNG or WEBP files are allowed for Bill.');
            fileInput.value = '';
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }
        if (file.size > maxBytes) {
            alert('Bill file size must not exceed 5 MB.');
            fileInput.value = '';
            if (pathLabelEl) pathLabelEl.textContent = 'No file chosen';
            return;
        }

        if (pathLabelEl) {
            pathLabelEl.textContent = file.name;
        }
    }

    if (createBillFileInputEl) {
        createBillFileInputEl.addEventListener('change', function() {
            // For create modal we don't show a file-name label; just validate
            validateBillFileInput(createBillFileInputEl, null);
        });
    }

    var editBillFileInputEl = document.getElementById('editBillFileInput');
    if (editBillFileInputEl) {
        editBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editCurrentBillPath');
            validateBillFileInput(editBillFileInputEl, pathEl);
        });
    }

    var editBillClearBtnEl = document.getElementById('editBillClearBtn');
    if (editBillClearBtnEl && editBillFileInputEl) {
        editBillClearBtnEl.addEventListener('click', function() {
            editBillFileInputEl.value = '';
            var pathEl = document.getElementById('editCurrentBillPath');
            if (pathEl) pathEl.textContent = 'No file chosen';
        });
    }

    var editBillClearBtnEl = document.getElementById('editBillClearBtn');
    if (editBillClearBtnEl && editBillFileInputEl) {
        editBillClearBtnEl.addEventListener('click', function() {
            editBillFileInputEl.value = '';
            var pathEl = document.getElementById('editCurrentBillPath');
            if (pathEl) pathEl.textContent = 'No file chosen';
        });
    }

    document.getElementById('editPoItemsBody').addEventListener('change', function(e) {
        if (
            e.target.classList.contains('po-item-select') ||
            e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-unit-price') ||
            e.target.classList.contains('po-tax')
        ) {
            const row = e.target.closest('.po-item-row');
            if (!row) return;
            if (e.target.classList.contains('po-item-select')) {
                if (maybeSplitMultiItemRow(row)) return;
                updateUnitAndCode(row);
            }
            calcLineTotal(row);
            updateEditGrandTotal();
        }
    });
    document.getElementById('editPoItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('po-qty') || e.target.classList.contains('po-unit-price') || e
            .target.classList.contains('po-tax')) {
            const row = e.target.closest('.po-item-row');
            if (row) {
                calcLineTotal(row);
                updateEditGrandTotal();
            }
        }
    });
    document.getElementById('editPoItemsBody').addEventListener('click', function(e) {
        // closest() (not e.target) so a click on the glyph inside the button still counts
        if (e.target.closest('.po-add-row')) {
            addEditItemRow();
            return;
        }
        if (e.target.closest('.po-remove-row')) {
            const row = e.target.closest('.po-item-row');
            if (row && document.querySelectorAll('#editPoItemsBody .po-item-row').length > 1) {
                row.remove();
                updateEditGrandTotal();
                updateEditRemoveButtons();
            }
        }
    });

    // In create modal, treat Enter like Tab; on Rate Enter append a new item row
    const createPOModal = document.getElementById('createPurchaseOrderModal');
    const poItemsTable = document.getElementById('poItemsTable');
    if (createPOModal) {
        createPOModal.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            if (e.target && e.target.tagName === 'TEXTAREA') return;

            const activeEl = document.activeElement;
            if (!activeEl || !createPOModal.contains(activeEl)) return;
            if (activeEl.matches('button, [type="submit"], [type="button"]')) return;

            const isDropdownInteraction =
                activeEl.matches('select') ||
                !!activeEl.closest('.ts-wrapper') ||
                !!activeEl.closest('.choices__list--dropdown') ||
                !!activeEl.closest('[class*="choices"]');
            if (isDropdownInteraction) return;

            e.preventDefault();

            if (poItemsTable && poItemsTable.contains(activeEl) && activeEl.classList.contains(
                    'po-unit-price')) {
                if (typeof window.addCreatePoItemRow === 'function') {
                    const newRow = window.addCreatePoItemRow();
                    const firstInput = newRow ? newRow.querySelector(
                        '.po-item-select, .po-qty, .po-unit-price, input, select') : null;
                    if (firstInput) firstInput.focus();
                }
                return;
            }

            const focusable = Array.from(
                createPOModal.querySelectorAll(
                    'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter(function(el) {
                return el.offsetParent !== null;
            });

            const currentIndex = focusable.indexOf(activeEl);
            if (currentIndex !== -1 && currentIndex < focusable.length - 1) {
                focusable[currentIndex + 1].focus();
            }
        });
    }

    // In edit modal, treat Enter like Tab; on Rate Enter append a new item row
    const editPOModal = document.getElementById('editPurchaseOrderModal');
    const editPoItemsTable = document.getElementById('editPoItemsTable');
    if (editPOModal) {
        editPOModal.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            if (e.target && e.target.tagName === 'TEXTAREA') return;

            const activeEl = document.activeElement;
            if (!activeEl || !editPOModal.contains(activeEl)) return;
            if (activeEl.matches('button, [type="submit"], [type="button"]')) return;

            const isDropdownInteraction =
                activeEl.matches('select') ||
                !!activeEl.closest('.ts-wrapper') ||
                !!activeEl.closest('.choices__list--dropdown') ||
                !!activeEl.closest('[class*="choices"]');
            if (isDropdownInteraction) return;

            e.preventDefault();

            if (editPoItemsTable && editPoItemsTable.contains(activeEl) && activeEl.classList.contains(
                    'po-unit-price')) {
                if (typeof window.addEditPoItemRow === 'function') {
                    const newRow = window.addEditPoItemRow();
                    const firstInput = newRow ? newRow.querySelector(
                        '.po-item-select, .po-qty, .po-unit-price, input, select') : null;
                    if (firstInput) firstInput.focus();
                }
                return;
            }

            const focusable = Array.from(
                editPOModal.querySelectorAll(
                    'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter(function(el) {
                return el.offsetParent !== null;
            });

            const currentIndex = focusable.indexOf(activeEl);
            if (currentIndex !== -1 && currentIndex < focusable.length - 1) {
                focusable[currentIndex + 1].focus();
            }
        });
    }

    // Contact number: restrict to digits only, max 10
    function initContactNumberValidation(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('keydown', function(e) {
            const key = e.key;
            if (key === 'Backspace' || key === 'Tab' || key === 'ArrowLeft' || key === 'ArrowRight' ||
                key === 'Delete') return;
            if (key.length === 1 && !/^[0-9]$/.test(key)) {
                e.preventDefault();
            }
        });
        inputEl.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            if (validateContactNumber(this.value)) {
                this.classList.remove('is-invalid');
                const fb = this.parentNode.querySelector('.invalid-feedback.d-block');
                if (fb) fb.textContent = '';
            }
        });
        inputEl.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/[^0-9]/g, '').slice(0, 10);
            const start = this.selectionStart,
                end = this.selectionEnd;
            this.value = this.value.slice(0, start) + digits + this.value.slice(end);
            this.setSelectionRange(start + digits.length, start + digits.length);
        });
    }
    initContactNumberValidation(document.getElementById('createContactNumber'));
    document.getElementById('editPurchaseOrderModal').addEventListener('shown.bs.modal', function() {
        initContactNumberValidation(document.getElementById('editContactNumber'));
    }, {
        once: false
    });

    // Validate contact number before form submit (optional field: if provided, must be exactly 10 digits)
    function validateContactNumber(val) {
        if (!val || val.trim() === '') return true;
        return /^[0-9]{10}$/.test(val.replace(/\s/g, ''));
    }
    document.getElementById('createPOForm').addEventListener('submit', function(e) {
        const input = document.getElementById('createContactNumber');
        if (input && !validateContactNumber(input.value)) {
            e.preventDefault();
            input.classList.add('is-invalid');
            const msg = input.parentNode.querySelector('.invalid-feedback.d-block') || document
                .createElement('div');
            if (!msg.classList || !msg.classList.contains('invalid-feedback')) {
                const m = document.createElement('div');
                m.className = 'invalid-feedback d-block';
                m.textContent = 'Contact number must be exactly 10 digits (numbers only).';
                input.parentNode.appendChild(m);
            } else {
                msg.textContent = 'Contact number must be exactly 10 digits (numbers only).';
            }
            input.focus();
            return false;
        }
    });

    // AJAX submit: Create Purchase Order (keep modal open until user closes)
    (function() {
        var form = document.getElementById('createPOForm');
        if (!form) return;

        function resetCreatePurchaseOrderForm() {
            // Reuse existing reset logic by triggering modal show handler logic:
            // - clears Choices selections
            // - resets items table to one row
            // - clears bill file input
            var createModal = document.getElementById('createPurchaseOrderModal');
            if (!createModal) return;

            // Reset vendor selection + filtered items
            currentVendorId = null;
            filteredItems = itemSubcategories;

            // Reset native form fields
            form.reset();

            // Reset Choices dropdowns
            if (choicesInstances && choicesInstances.create) {
                if (choicesInstances.create.vendor) choicesInstances.create.vendor.clear();
                if (choicesInstances.create.store) choicesInstances.create.store.clear();
                if (choicesInstances.create.payment) choicesInstances.create.payment.clear();
            }

            // Clear selected bill file (if any)
            if (createBillFileInputEl) createBillFileInputEl.value = '';

            if (typeof window.syncPoDatePlaceholders === 'function') window.syncPoDatePlaceholders();

            // Reset items table to a single fresh row
            destroyAllItemDropdowns();
            var tbody = document.getElementById('poItemsBody');
            if (tbody) {
                tbody.innerHTML = '';
                tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, false));
                itemRowIndex = 1;
                initAllItemDropdowns(tbody);
                updateGrandTotal();
                updateRemoveButtons();
            }
        }

        form.addEventListener('submit', function(e) {
            // If any earlier listener prevented default, do nothing.
            if (e.defaultPrevented) return;

            if (!form.checkValidity()) {
                // Let browser/Bootstrap validations do their job
                return;
            }

            e.preventDefault();

            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) return;
            if (btn) {
                if (!btn.dataset.originalText) btn.dataset.originalText = btn.textContent || '';
                btn.disabled = true;
                btn.textContent = 'Creating...';
            }

            var action = form.getAttribute('action') || window.location.href;
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var formData = new FormData(form);
            var csrf = form.querySelector('input[name="_token"]');

            fetch(action, {
                    method: method,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf ? csrf.value : '',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(response) {
                    return response.json().then(function(payload) {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: payload
                        };
                    }).catch(function() {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: null
                        };
                    });
                })
                .then(function(res) {
                    var data = res.payload;
                    if (res.ok && data && data.success) {
                        resetCreatePurchaseOrderForm();
                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message :
                            'Failed to create purchase order. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        alert(msg);
                    }
                })
                .catch(function() {
                    alert('Failed to create purchase order. Please try again.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText || 'Create Purchase Order';
                    }
                });
        });
    })();
    document.getElementById('editPOForm').addEventListener('submit', function(e) {
        const input = document.getElementById('editContactNumber');
        if (input && !validateContactNumber(input.value)) {
            e.preventDefault();
            input.classList.add('is-invalid');
            let msg = input.parentNode.querySelector('.invalid-feedback.d-block');
            if (!msg) {
                msg = document.createElement('div');
                msg.className = 'invalid-feedback d-block';
                input.parentNode.appendChild(msg);
            }
            msg.textContent = 'Contact number must be exactly 10 digits (numbers only).';
            input.focus();
            return false;
        }
    });

    // Auto-open create modal when validation errors exist (e.g. after failed submit)
    @if($errors->any() || session('open_create_po_modal'))
    document.addEventListener('DOMContentLoaded', function() {
        const createModal = document.getElementById('createPurchaseOrderModal');
        if (createModal && (document.getElementById('createPOForm') || document.querySelector(
                '[name="po_number"]'))) {
            new bootstrap.Modal(createModal).show();
        }
    });
    @endif

    // Reset create modal when opened (except first open after validation errors)
    if (createPOModal) {
        createPOModal.addEventListener('show.bs.modal', function() {
            if (hasInitialCreateErrors) {
                // Preserve previously entered values on first open after validation error
                hasInitialCreateErrors = false;
                return;
            }

            // Reset vendor selection
            currentVendorId = null;
            filteredItems = itemSubcategories;

            // Reset form fields and Choices instances
            const form = document.getElementById('createPOForm');
            if (form) {
                form.reset();

                // Reset Choices dropdowns
                if (choicesInstances.create.vendor) {
                    choicesInstances.create.vendor.clear();
                }
                if (choicesInstances.create.store) {
                    choicesInstances.create.store.clear();
                }
                if (choicesInstances.create.payment) {
                    choicesInstances.create.payment.clear();
                }
            }

            // Clear selected bill file (if any)
            if (createBillFileInputEl) {
                createBillFileInputEl.value = '';
            }

            // Reset items table to a single fresh row
            destroyAllItemDropdowns();
            const tbody = document.getElementById('poItemsBody');
            if (tbody) {
                tbody.innerHTML = '';
                tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null, false));
                itemRowIndex = 1;

                // Note: Initialize dropdowns in 'shown.bs.modal' event instead (when modal is visible)
                updateGrandTotal();
                updateRemoveButtons();
            }
        });
    }

    // Print View modal content – correct design with standard header
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modal = document.querySelector(sel);
        if (!modal) return;
        var bodyEl = modal.querySelector('.modal-body');
        if (!bodyEl) return;
        var title = (modal.querySelector('.modal-title') || {}).textContent || 'Purchase Order Details';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2, '0') + '/' + (printedOn.getMonth() + 1)
            .toString().padStart(2, '0') + '/' + printedOn.getFullYear() + ', ' + printedOn
            .toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        var bodyWrap = document.createElement('div');
        bodyWrap.innerHTML = bodyEl.innerHTML;

        // A "View / Download Bill" button means nothing on paper — state whether one exists.
        var billWrap = bodyWrap.querySelector('#viewBillWrap');
        if (billWrap) {
            var hasBill = !!bodyEl.querySelector('#viewBillLink') &&
                bodyEl.querySelector('#viewBillLink').style.display !== 'none';
            billWrap.textContent = hasBill ? 'Attached' : 'Not uploaded';
            billWrap.className = 'po-value';
        }
        // The screen layout drops section headings; print reads better with them.
        var firstRow = bodyWrap.querySelector('.row');
        if (firstRow) {
            var head = document.createElement('div');
            head.className = 'po-items-title';
            head.textContent = 'Order Details';
            firstRow.parentNode.insertBefore(head, firstRow);
        }

        var bodyContent = typeof window.poSanitizePrintDom === 'function'
            ? window.poSanitizePrintDom(bodyWrap)
            : bodyWrap.innerHTML;
        var poPrintLogoUrl = @json(asset('images/lbsnaa_logo.jpg'));
        var poNumber = ((modal.querySelector('#viewPoNumber') || {}).textContent || '').trim();
        var subLine = (poNumber && poNumber !== '-' ? 'Order No. ' + poNumber + ' &nbsp;|&nbsp; ' : '') +
            'Printed on ' + dateStr;
        var printHeader =
            '<div class="print-doc-header" style="text-align:center;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="margin-bottom:10px;"><img src="' + poPrintLogoUrl + '" alt="LBSNAA Logo" style="height:60px;width:auto;"></div>' +
            '<div style="font-size:18px;font-weight:700;color:#1a1a1a;margin-bottom:6px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#004384;color:#fff;padding:8px 16px;font-size:14px;display:inline-block;margin:4px 0;border-radius:4px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">Purchase Order Details</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:8px;">' + subLine +
            '</div></div>';
        var iconCss = window.PO_PRINT_SUPPRESS_ICON_CSS || '';
        var ink = ' -webkit-print-color-adjust: exact; print-color-adjust: exact;';
        // Styles below mirror the on-screen View modal classes (.po-label/.po-value/
        // .po-items-table/.po-total-bar) on the branded report header used by the list print.
        var printCss = '<style>' + iconCss +
            '@page { size: A4; margin: 14mm; }' +
            'body { font-family: Arial, sans-serif; font-size: 12px; color: #212529; padding: 0; margin: 0; background: #fff; }' +
            '.print-doc-header {' + ink + ' }' +
            '.print-doc-header img {' + ink + ' }' +
            '.modal-header, .modal-footer, .btn-close { display: none !important; }' +
            /* section heading */
            '.po-items-title { margin: 16px 0 8px; font-size: 13px; font-weight: 700; color: #004384;' +
            ' border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }' +
            '.po-items-title:first-child { margin-top: 0; }' +
            /* field grid — Bootstrap columns are not available in the print window */
            '.row { display: flex; flex-wrap: wrap; margin: 0 -6px; }' +
            '.row > [class*="col-"] { box-sizing: border-box; padding: 0 6px 10px; width: 33.33%; }' +
            '.row > .col-md-8 { width: 66.66%; }' +
            '.row > .col-12 { width: 100%; }' +
            '.po-label { display: block; margin-bottom: 2px; font-size: 10px; font-weight: 600;' +
            ' color: #6c757d; text-transform: uppercase; letter-spacing: .02em; }' +
            '.po-value { margin: 0; padding: 4px 8px; font-size: 12px; color: #212529;' +
            ' background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 3px; min-height: 20px;' + ink + ' }' +
            /* status pill keeps its tint on paper */
            '.po-status-pill { display: inline-block; padding: 3px 10px; font-size: 10px; font-weight: 700;' +
            ' border-radius: 999px;' + ink + ' }' +
            '.po-status-pill--approved { color: #067647 !important; background: #d7f5e5 !important; }' +
            '.po-status-pill--rejected { color: #b42318 !important; background: #fbe1de !important; }' +
            '.po-status-pill--completed { color: #004384 !important; background: #dbe9f8 !important; }' +
            '.po-status-pill--pending { color: #b54708 !important; background: #fdf0d5 !important; }' +
            /* line items */
            '.po-items-box { border: 1px solid #adb5bd; border-radius: 3px; overflow: hidden; }' +
            'table { width: 100%; border-collapse: collapse; font-size: 11px; page-break-inside: auto; }' +
            'th, td { border: 1px solid #adb5bd; padding: 5px 8px; text-align: left; }' +
            'thead th { background: #004384 !important; color: #fff !important; border-color: #003468;' +
            ' font-weight: 600;' + ink + ' }' +
            'thead { display: table-header-group; }' +
            'tbody tr { page-break-inside: avoid; }' +
            'tbody tr:nth-child(even) td { background-color: #f4f6f8 !important;' + ink + ' }' +
            /* Qty / Rate / Tax% / Line Total read as numbers */
            'th:nth-child(n+4), td:nth-child(n+4) { text-align: right; }' +
            '.po-total-bar { padding: 7px 10px; font-size: 12px; font-weight: 700; text-align: right;' +
            ' color: #004384 !important; background: #dbe9f8 !important; border-top: 1px solid #adb5bd;' +
            ' page-break-inside: avoid;' + ink + ' }' +
            '.po-file-current, .po-bill-link { font-size: 12px; color: #212529; border: 0; padding: 0;' +
            ' text-decoration: none; }' +
            '@media print { .print-doc-header { margin-bottom: 16px; } }' +
            '</style>';
        var docHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' +
            title.replace(/</g, '&lt;') + '</title>' + printCss + '</head><body>' + printHeader +
            '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>';

        // Printed from a hidden same-page iframe rather than window.open(): a popup
        // blocker silently kills the popup route (the button then does nothing), and
        // the popup also had to be closed on a timer that raced the print dialog.
        window.PO_MODAL_PRINT_ACTIVE = true;
        var frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        document.body.appendChild(frame);

        var fired = false;
        var cleaned = false;

        function cleanup() {
            if (cleaned) return;
            cleaned = true;
            window.PO_MODAL_PRINT_ACTIVE = false;
            setTimeout(function() {
                if (frame && frame.parentNode) frame.parentNode.removeChild(frame);
            }, 500);
        }

        function doPrint() {
            if (fired) return;
            fired = true;
            var fw = frame.contentWindow;
            if (!fw) {
                cleanup();
                return;
            }
            try {
                fw.addEventListener('afterprint', cleanup);
            } catch (err) {}
            try {
                fw.focus();
                fw.print();
            } catch (err) {
                console.error('Print failed', err);
            }
            // afterprint is not fired by every browser — always reclaim the iframe.
            setTimeout(cleanup, 60000);
        }

        var fdoc = frame.contentWindow.document;
        fdoc.open();
        fdoc.write(docHtml);
        fdoc.close();

        // Print once the logo has decoded, otherwise the header prints blank.
        var logo = fdoc.querySelector('.print-doc-header img');
        if (!logo || logo.complete) {
            setTimeout(doPrint, 60);
        } else {
            logo.addEventListener('load', doPrint);
            logo.addEventListener('error', doPrint);
            setTimeout(doPrint, 2000);
        }
    });

    // Initialize Choices on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize filter dropdowns only (always visible)
        initFilterDropdowns();

        // Initialize create modal dropdowns immediately
        initCreateModalDropdowns();

        // Initialize edit modal dropdowns immediately  
        initEditModalDropdowns();

        // Setup modal event listeners
        const createPOModal = document.getElementById('createPurchaseOrderModal');
        if (createPOModal) {
            createPOModal.addEventListener('show.bs.modal', function() {
                // Ensure dropdowns are initialized when modal opens
                if (!choicesInstances.create.vendor || !choicesInstances.create.vendor.input) {
                    initCreateModalDropdowns();
                }
            });

            // Initialize item dropdowns when modal is SHOWN (not hidden)
            createPOModal.addEventListener('shown.bs.modal', function() {
                const createTbody = document.getElementById('poItemsBody');
                if (createTbody) {
                    // Destroy any existing instances first
                    createTbody.querySelectorAll('.po-item-select').forEach(function(sel) {
                        if (sel.tomselect) {
                            try {
                                sel.tomselect.destroy();
                            } catch (e) {}
                        }
                    });
                    // Re-initialize all item dropdowns
                    initAllItemDropdowns(createTbody);
                }
            });
        }

        // Setup edit modal event listeners
        const editPOModal = document.getElementById('editPurchaseOrderModal');
        if (editPOModal) {
            editPOModal.addEventListener('shown.bs.modal', function() {
                // Reinitialize edit modal dropdowns to ensure they work properly
                if (!choicesInstances.edit.store || !choicesInstances.edit.store.input) {
                    initEditModalDropdowns();
                }
            });
        }
    });

})();
</script>
@endsection