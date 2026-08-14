@extends('admin.layouts.master')
@section('title', 'Selling Voucher')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Selling Voucher — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .sv-master-page .sv-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    .sv-master-page .sv-master-export-btn {
        height: var(--ds-control-h, 40px);
        display: inline-flex; align-items: center; gap: .5rem;
        padding: 0 1.1rem; font-size: .9375rem; font-weight: 500;
        color: var(--ds-primary, #004a93);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius-2, 8px);
        background: var(--ds-surface, #fff);
    }
    .sv-master-page .sv-master-export-btn:hover { background: #f2f7fc; border-color: var(--ds-primary, #004a93); color: var(--ds-primary, #004a93); }
    .sv-master-page .sv-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Filter pills */
    .sv-master-page .sv-filter-select .form-select,
    .sv-master-page .sv-filter-date {
        min-height: 40px; border-radius: 8px; border: 1px solid #d0d5dd;
        font-size: .9375rem; color: #344054; box-shadow: none;
    }
    .sv-master-page .sv-filter-select { width: 150px; }
    .sv-master-page .sv-filter-date { padding: .5rem .6rem; width: 150px; }
    .sv-master-page .sv-filter-select .form-select:focus,
    .sv-master-page .sv-filter-date:focus { border-color: var(--ds-primary, #004a93); box-shadow: 0 0 0 3px rgba(0, 74, 147, .12); }
    .sv-master-page .sv-filter-dash { color: #98a2b3; }
    .sv-master-page .sv-filter-apply {
        height: 40px; border-radius: 8px; padding: 0 1rem; font-weight: 600; font-size: .9375rem;
        background: var(--ds-primary, #004a93); border: 1px solid var(--ds-primary, #004a93); color: #fff;
        display: inline-flex; align-items: center; gap: .35rem;
    }

    /* Native single-select filter pill */
    .sv-master-page .sv-filter-select-native {
        min-height: 40px; width: 150px; border-radius: 8px; border: 1px solid #d0d5dd;
        font-size: .9375rem; color: #344054; box-shadow: none;
    }
    .sv-master-page .sv-filter-select-native:focus { border-color: var(--ds-primary, #004a93); box-shadow: 0 0 0 3px rgba(0, 74, 147, .12); }

    /* Single-row responsive toolbar: never wrap; filters overflow into "+Filter". */
    .sv-master-page .sv-toolbar { flex-wrap: nowrap; }
    .sv-master-page .sv-filter-form { flex: 1 1 auto; min-width: 0; flex-wrap: nowrap; overflow: visible; }
    /* Pinned (flex-shrink:0) so trailing filters overflow the FORM cleanly — the JS
       then measures that overflow and moves them into "+Filter" (no visual overlap). */
    .sv-master-page .sv-filter-items { flex-wrap: nowrap; flex-shrink: 0; overflow: visible; }
    .sv-master-page .sv-filter-item { flex-shrink: 0; }
    .sv-master-page .sv-filter-item .sv-filter-item-label { display: none; }   /* inline: label hidden (placeholder carries it) */

    /* Below md the single row cannot hold even the fixed chrome — the label,
       the "+N Filter" toggle and Remove Filter alone overflow once Columns and
       search share the line. Let the toolbar stack there so the filter form
       gets a full-width line of its own, and keep Remove Filter reachable. */
    @media (max-width: 767.98px) {
        .sv-master-page .sv-toolbar { flex-wrap: wrap; }
        .sv-master-page .sv-filter-form { flex: 1 1 100%; width: 100%; }
        /* custom.css:640 stretches these to 100% for toolbars whose filters STACK
           on mobile. This toolbar keeps one row and spills into "+Filter", so a
           full-width reset button alone would push the row past the screen. */
        .sv-master-page .programme-dt-btn-reset { width: auto; }
    }

    /* "+N Filter" popover trigger — clearly separated pill */
    .sv-master-page .sv-more-filters {
        flex-shrink: 0; color: #004a93; font-weight: 600; font-size: .9375rem;
        text-decoration: none; white-space: nowrap; padding: 0 .35rem; height: 40px;
        display: inline-flex; align-items: center; border-radius: 8px;
    }
    .sv-master-page .sv-more-filters:hover { text-decoration: underline; }
    .sv-master-page .sv-more-filters.dropdown-toggle::after { display: none !important; }
    .sv-more-menu { min-width: 280px; }
    .sv-more-menu .sv-more-header { font-size: .95rem; font-weight: 700; color: #101828; padding-bottom: .6rem; margin-bottom: .75rem; border-bottom: 1px solid #eef2f6; }
    /* Filters moved into the popover: full-width block with label above */
    .sv-more-menu .sv-filter-item { display: block; margin-bottom: .85rem; }
    .sv-more-menu .sv-filter-item:last-child { margin-bottom: 0; }
    .sv-more-menu .sv-filter-item .sv-filter-item-label { display: block; font-size: .8rem; color: #667085; margin-bottom: .25rem; }
    .sv-more-menu .sv-filter-item .form-select,
    .sv-more-menu .sv-filter-item .sv-filter-date { width: 100% !important; }
    .sv-more-menu .sv-filter-item[data-filter="date"] .sv-filter-date { flex: 1 1 0; }

    .sv-master-page .dt-top:empty, .sv-master-page .dt-foot:empty { display: none; margin: 0; }
    .sv-master-page .mess-col-manager-dropdown { display: none !important; }
    #svColumnToggleGrid .colvis-item { cursor: pointer; transition: border-color 0.15s ease, background-color 0.15s ease; }
    #svColumnToggleGrid .colvis-item:hover { border-color: var(--ds-primary, #004a93) !important; background-color: rgba(0, 74, 147, 0.04); }
    #svColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .sv-master-page .sv-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }

    /* Soft status / payment pills (rendered server-side) */
    .sv-master-page .sv-pill { display: inline-block; font-size: .8125rem; font-weight: 500; padding: .3rem .7rem; border-radius: 6px; line-height: 1.2; }
    .sv-master-page .sv-pill--green  { background: #ecfdf3; color: #027a48; }
    .sv-master-page .sv-pill--amber  { background: #fffaeb; color: #b54708; }
    .sv-master-page .sv-pill--orange { background: #fff4ed; color: #c4320a; }
    .sv-master-page .sv-pill--blue   { background: #eff8ff; color: #175cd3; }
    .sv-master-page .sv-pill--gray   { background: #f2f4f7; color: #475467; }

    /* Row actions — icon over label (blue View/Edit, amber Return, red Delete) */
    .sv-master-page .sv-actions { gap: .9rem; }
    .sv-master-page .sv-actions form { margin: 0; }
    .sv-master-page .sv-action-btn {
        display: inline-flex; flex-direction: column; align-items: center; gap: .1rem;
        padding: 0; border: 0; background: transparent; line-height: 1.1; font-size: .72rem; font-weight: 500;
    }
    .sv-master-page .sv-action-btn i { font-size: 1.15rem; line-height: 1; }
    .sv-master-page .sv-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .sv-master-page .sv-action-btn.text-warning { color: #dc6803 !important; }
    .sv-master-page .sv-action-btn.text-danger  { color: var(--ds-secondary, #d92d20) !important; }
    .sv-master-page .sv-action-btn:hover { opacity: .78; }
    .sv-master-page .sv-action-btn:disabled { opacity: .4; pointer-events: none; }

    /* Pagination → arrows + numbers only. */
    .sv-master-page .programme-dt-footer .paginate_button.first,
    .sv-master-page .programme-dt-footer .paginate_button.last { display: none; }
    .sv-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .sv-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }
    .sv-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .sv-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }
</style>
@endpush

@section('content')
@php
    $canDeleteSellingVoucher = hasRole('Super Admin') || hasRole('Mess-Admin');
@endphp
<div class="container-fluid py-3 sv-master-page">
    <x-breadcrum title="Selling Voucher" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSellingVoucherModal">
            <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
            <span>Add Selling Voucher</span>
        </button>
    </x-breadcrum>

    {{-- Success/error feedback is rendered as the global toast — see mess.partials.delete-confirm --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mt-2" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Download / Print bar (branded server-side exports — see admin.mess.material-management.selling-vouchers-export) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn sv-master-export-btn" id="svDownloadBtn">
            <i class="material-symbols-rounded">download</i>
            <span>Download</span>
        </button>
        <button type="button" class="btn sv-master-export-btn" id="svPrintBtn">
            <i class="material-symbols-rounded">print</i>
            <span>Print</span>
        </button>
    </div>

    <div class="card sv-master-card border-0">
        <div class="card-body">
            @php
                $selectedStatuses = request('status', []);
                if (!is_array($selectedStatuses)) { $selectedStatuses = $selectedStatuses !== null ? [$selectedStatuses] : []; }
                $selectedStores = request('store', []);
                if (!is_array($selectedStores)) { $selectedStores = $selectedStores !== null ? [$selectedStores] : []; }
                $hasSvFilter = request()->hasAny(['status', 'store', 'client_type', 'client_type_pk', 'buyer_name', 'return_status', 'start_date', 'end_date'])
                    && collect(request()->only(['status', 'store', 'client_type', 'client_type_pk', 'buyer_name', 'return_status', 'start_date', 'end_date']))
                        ->flatten()->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
            @endphp
            @php
                $svSelStatus = is_array(request('status')) ? (string) (request('status')[0] ?? '') : (string) request('status', '');
                $svSelStore  = is_array(request('store'))  ? (string) (request('store')[0] ?? '')  : (string) request('store', '');
                $filterClientTypes = [
                    (string) \App\Models\KitchenIssueMaster::CLIENT_EMPLOYEE => 'Employee',
                    (string) \App\Models\KitchenIssueMaster::CLIENT_OT => 'OT',
                    (string) \App\Models\KitchenIssueMaster::CLIENT_COURSE => 'Course',
                    (string) \App\Models\KitchenIssueMaster::CLIENT_SECTION => 'Section',
                    (string) \App\Models\KitchenIssueMaster::CLIENT_OTHER => 'Other',
                ];
            @endphp
    {{-- Responsive single-row toolbar: filters auto-apply on change; overflow spills into "+Filter"; Columns + search on the same row. --}}
            <div class="d-flex align-items-center gap-2 mb-3 programme-dt-toolbar sv-toolbar">
                <form method="GET" action="{{ route('admin.mess.material-management.index') }}" id="svFilterForm"
                      class="d-flex align-items-center gap-2 sv-filter-form">
                    <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                    {{-- Every filter lives inside #svFilterItems: the overflow
                         manager only collects `.sv-filter-item` from within this
                         wrapper, so anything left outside it can never move into
                         "+Filter" and would pin the row wider than the screen. --}}
                    <div id="svFilterItems" class="d-flex align-items-center gap-2 sv-filter-items">
                        <div class="sv-filter-item" data-filter="status">
                            <label class="sv-filter-item-label">Status</label>
                            <select name="status" id="filter_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Status">
                                <option value="">Status</option>
                                <option value="0" {{ $svSelStatus === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="2" {{ $svSelStatus === '2' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                         <div class="sv-filter-item" data-filter="store">
                            <label class="sv-filter-item-label">Store</label>
                            <select name="store" id="filter_store" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Store">
                                <option value="">Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}" {{ $svSelStore === (string) $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                         <div class="sv-filter-item" data-filter="client_type">
                            <label class="sv-filter-item-label">Client Type</label>
                            <select name="client_type" id="filter_client_type" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Client type" data-clears="filter_client_type_pk,filter_buyer_name">
                                <option value="" {{ $selectedClientType === '' ? 'selected' : '' }}>Client Type</option>
                                @foreach($filterClientTypes as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedClientType === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sv-filter-item" data-filter="client_type_pk">
                            <label class="sv-filter-item-label">Client Category</label>
                            <select name="client_type_pk" id="filter_client_type_pk" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Client category" data-clears="filter_buyer_name">
                                <option value="">Client Category</option>
                                @foreach(($filterClientTypePkOptions ?? collect()) as $option)
                                    <option value="{{ $option['value'] }}" {{ (string) ($selectedClientTypePk ?? '') === (string) $option['value'] ? 'selected' : '' }}>{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sv-filter-item" data-filter="buyer">
                            <label class="sv-filter-item-label">Buyer Name</label>
                            <select name="buyer_name" id="filter_buyer_name" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Buyer name">
                                <option value="">Buyer Name</option>
                                @foreach(($filterBuyerNames ?? collect()) as $buyerName)
                                @php
                                    $buyerValue = is_array($buyerName) ? (string) ($buyerName['value'] ?? '') : (string) $buyerName;
                                    $buyerLabel = is_array($buyerName) ? (string) ($buyerName['text'] ?? $buyerValue) : (string) $buyerName;
                                @endphp
                                    <option value="{{ $buyerValue }}" {{ (string) ($selectedBuyerName ?? '') === $buyerValue ? 'selected' : '' }}>{{ $buyerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sv-filter-item" data-filter="return_status">
                            <label class="sv-filter-item-label">Return Status</label>
                            <select name="return_status" id="filter_return_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Return status">
                                <option value="" {{ request('return_status', '') === '' ? 'selected' : '' }}>Return Status</option>
                                <option value="returned" {{ request('return_status') === 'returned' ? 'selected' : '' }}>Returned</option>
                                <option value="not_returned" {{ request('return_status') === 'not_returned' ? 'selected' : '' }}>Not returned</option>
                            </select>
                        </div>
                        <div class="sv-filter-item" data-filter="date">
                            <label class="sv-filter-item-label">Date Range</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" name="start_date" id="filter_start_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('start_date') }}" aria-label="Start date">
                                <span class="sv-filter-dash">–</span>
                                <input type="date" name="end_date" id="filter_end_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('end_date') }}" aria-label="End date" @if(request()->filled('start_date')) min="{{ request('start_date') }}" @endif>
                            </div>
                        </div>
                    </div>

                    {{-- Overflow "+N Filter" popover (populated by the toolbar overflow manager) --}}
                    <div class="dropdown flex-shrink-0 d-none" id="svMoreFilterWrap">
                        <a href="javascript:void(0)" class="sv-more-filters dropdown-toggle" id="svMoreFilterToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+ Filter</a>
                        <div class="dropdown-menu sv-more-menu p-3 shadow border rounded-3">
                            <div class="sv-more-header">Filters</div>
                            <div id="svMoreFilterItems"></div>
                        </div>
                    </div>

                    <a href="{{ route('admin.mess.material-management.index') }}" class="btn programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center">Remove Filter</a>
                </form>

                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnSvColumns"
                            data-bs-toggle="modal" data-bs-target="#svColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    @include('mess.partials.search-toggle', ['tableId' => 'sellingVouchersTable'])
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table programme-dt-table align-middle mb-0 w-100" id="sellingVouchersTable">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Item Name</th>
                                <th scope="col" class="text-end">Item Qty</th>
                                <th scope="col" class="text-end">Return Qty</th>
                                <th scope="col">Transfer From Store</th>
                                <th scope="col">Client Type</th>
                                <th scope="col">Client Name</th>
                                <th scope="col">Payment</th>
                                <th scope="col" class="text-nowrap">Request Date</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="sellingVouchersTable"></div>
        </div>
    </div>

    {{-- Column Visibility Modal (programme/attendance style) --}}
    <div class="modal fade" id="svColumnVisibilityModal" tabindex="-1" aria-labelledby="svColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="svColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="svColumnToggleGrid"></div>
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
        'tableId' => 'sellingVouchersTable',
        'searchPlaceholder' => 'Search',
        'actionColumnIndex' => 10,
        'infoLabel' => 'items',
        'searchDelay' => 0,
        'searchSmart' => false,
        'serverSide' => true,
        'ajaxUrlBase' => route('admin.mess.material-management.selling-vouchers-datatable'),
        'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
        'serverSideColumnDefs' => [
            ['className' => 'text-end', 'targets' => [2, 3]],
            ['className' => 'text-center', 'targets' => [9, 10]],
        ],
    ])
    @include('mess.partials.modal-dropdown-stability')

    @push('scripts')
    {{-- Download / Print → branded server-side report (admin.mess.material-management.selling-vouchers-export). --}}
    <script>
    (function () {
        var TABLE_ID = 'sellingVouchersTable';
        var BASE = @json(route('admin.mess.material-management.selling-vouchers-export'));
        var $ = window.jQuery;

        function buildUrl(format, inline) {
            var params = ['format=' + format];
            var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID)) ? $('#' + TABLE_ID).DataTable() : null;
            var search = dt ? dt.search() : '';
            if (search) params.push('search=' + encodeURIComponent(search));

            var form = document.getElementById('svFilterForm');
            if (form) {
                new FormData(form).forEach(function (value, key) {
                    if (value !== '' && value !== null) params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                });
            }

            var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
                ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
            if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));

            if (inline) params.push('inline=1');
            return BASE + '?' + params.join('&');
        }

        var downloadBtn = document.getElementById('svDownloadBtn');
        if (downloadBtn) downloadBtn.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
        var printBtn = document.getElementById('svPrintBtn');
        if (printBtn) printBtn.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
    })();
    </script>
    {{-- Column Visibility modal ⇄ mess Column-manager bridge --}}
    <script>
    (function () {
        var TABLE_ID = 'sellingVouchersTable';
        var $ = window.jQuery;
        var grid = document.getElementById('svColumnToggleGrid');
        var modalEl = document.getElementById('svColumnVisibilityModal');
        if (!$ || !grid || !modalEl) return;
        function getMgr() { return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function') ? window.MessColumnManager.get(TABLE_ID) : null; }
        function visibleCount(mgr) { return mgr.baseColumns.filter(function (c) { return mgr.state.visibility[String(c.index)] !== false; }).length; }
        function buildGrid() {
            var mgr = getMgr();
            if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;
            grid.innerHTML = '';
            (mgr.state.order || []).forEach(function (idx) {
                var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
                if (!col) return;
                var isVisible = mgr.state.visibility[String(col.index)] !== false;
                var inputId = 'svcolvis_' + col.index;
                var cell = document.createElement('div'); cell.className = 'col-12 col-sm-6 col-md-4';
                var label = document.createElement('label');
                label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
                label.setAttribute('for', inputId);
                var cb = document.createElement('input'); cb.type = 'checkbox'; cb.className = 'form-check-input m-0'; cb.id = inputId; cb.checked = isVisible;
                if (col.locked) cb.disabled = true;
                cb.addEventListener('change', function () {
                    var m = getMgr(); if (!m) return;
                    if (!cb.checked && visibleCount(m) <= 1) { cb.checked = true; window.alert('At least one column must remain visible.'); return; }
                    m.state.visibility[String(col.index)] = cb.checked; m.saveState(); m.apply();
                });
                var span = document.createElement('span'); span.textContent = col.label;
                label.appendChild(cb); label.appendChild(span); cell.appendChild(label); grid.appendChild(cell);
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
    {{-- Auto-apply filters + responsive "+Filter" overflow (filters spill into the popover when the row is tight) --}}
    <script>
    (function () {
        var form = document.getElementById('svFilterForm');
        if (!form) return;

        // ── Auto-apply: refresh the grid in place, never reload the page ──
        // The DataTable builds its ajax URL from window.location.search (see
        // components/mess-master-datatables), so pushing the filters into the URL
        // with history.replaceState and reloading the table gives the server the
        // same query string a submit would have — without the round trip.
        var CATEGORY_OPTIONS_BY_TYPE = @json($filterClientTypePkMap ?? []);
        var BUYER_NAMES_URL = @json(route('admin.mess.material-management.filter-buyer-names'));

        function svTable() {
            var $ = window.jQuery;
            if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#sellingVouchersTable')) return null;
            return $('#sellingVouchersTable').DataTable();
        }

        function currentQuery() {
            var params = new URLSearchParams();
            new FormData(form).forEach(function (value, key) {
                if (value !== null && String(value).trim() !== '') params.set(key, value);
            });
            return params.toString();
        }

        /** Mirror the filters into the address bar so the grid's ajax — and a
         *  refresh, a bookmark or the Download/Print links — all see them. */
        function syncUrl() {
            var qs = currentQuery();
            var url = window.location.pathname + (qs ? '?' + qs : '');
            window.history.replaceState({}, '', url);
        }

        function refreshGrid() {
            syncUrl();
            var dt = svTable();
            if (!dt) { window.location.reload(); return; }   // grid missing: fall back
            dt.ajax.reload(null, true);                       // true = back to page 1
        }

        function fillSelect(el, options, placeholder) {
            if (!el) return;
            // These selects are Choices-managed, which ignores innerHTML — go
            // through the helper so the widget rebuilds with the new list.
            if (window.SvFilterChoices && window.SvFilterChoices.isManaged(el)) {
                window.SvFilterChoices.setOptions(el, options, placeholder);
                return;
            }
            var html = '<option value="">' + placeholder + '</option>';
            (options || []).forEach(function (opt) {
                html += '<option value="' + String(opt.value).replace(/"/g, '&quot;') + '">'
                    + String(opt.text).replace(/</g, '&lt;') + '</option>';
            });
            el.innerHTML = html;
        }

        /** Client Category depends on Client Type — served from the embedded map,
         *  so no request is needed. */
        function repopulateCategories() {
            var type = (document.getElementById('filter_client_type') || {}).value || '';
            fillSelect(document.getElementById('filter_client_type_pk'),
                CATEGORY_OPTIONS_BY_TYPE[type] || [], 'Client Category');
        }

        /** Buyer Name depends on Client Category — that list is not knowable up
         *  front, so it comes from the existing buyer-names endpoint. */
        function repopulateBuyers() {
            var buyerEl = document.getElementById('filter_buyer_name');
            if (!buyerEl) return;
            var type = (document.getElementById('filter_client_type') || {}).value || '';
            var pk = (document.getElementById('filter_client_type_pk') || {}).value || '';
            if (!type || !pk) { fillSelect(buyerEl, [], 'Buyer Name'); return; }

            // filter-buyer-names shares the page's own builder, so it covers every
            // client type — employee buckets and OT courses included, which the
            // create form's buyer-names endpoint does not.
            fetch(BUYER_NAMES_URL + '?client_type=' + encodeURIComponent(type)
                    + '&client_type_pk=' + encodeURIComponent(pk),
                { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (res) { fillSelect(buyerEl, res.options || [], 'Buyer Name'); })
                .catch(function () { fillSelect(buyerEl, [], 'Buyer Name'); });
        }

        form.addEventListener('change', function (e) {
            var t = e.target;
            if (!t || !t.classList || !t.classList.contains('sv-auto-filter')) return;

            var clears = t.getAttribute('data-clears');
            if (clears) {
                clears.split(',').forEach(function (id) {
                    var el = document.getElementById(id.trim());
                    if (el) el.value = ''; // programmatic reset — does not re-fire change
                });
            }

            // A full reload used to re-render these dependent lists; rebuild them
            // here instead, or they would keep the previous type's options.
            if (t.id === 'filter_client_type') { repopulateCategories(); repopulateBuyers(); }
            else if (t.id === 'filter_client_type_pk') { repopulateBuyers(); }

            refreshGrid();
        });

        // "Remove Filter" clears in place too, for the same reason.
        var resetLink = form.querySelector('.programme-dt-btn-reset');
        if (resetLink) {
            resetLink.addEventListener('click', function (e) {
                e.preventDefault();
                form.reset();
                form.querySelectorAll('select, input[type="date"]').forEach(function (el) {
                    el.value = '';
                    // form.reset() restores the native <select>, but the Choices
                    // widget keeps showing the old item until it is told.
                    if (window.SvFilterChoices && window.SvFilterChoices.isManaged(el)) {
                        window.SvFilterChoices.clear(el);
                    }
                });
                repopulateCategories();
                fillSelect(document.getElementById('filter_buyer_name'), [], 'Buyer Name');
                refreshGrid();
            });
        }

        // ── Responsive overflow: keep the toolbar to one row; move trailing filters into "+Filter" ──
        var itemsWrap = document.getElementById('svFilterItems');
        var moreWrap = document.getElementById('svMoreFilterWrap');
        var moreMenu = document.getElementById('svMoreFilterItems');
        var moreToggle = document.getElementById('svMoreFilterToggle');
        if (!itemsWrap || !moreWrap || !moreMenu || !moreToggle) return;

        var allItems = Array.prototype.slice.call(itemsWrap.querySelectorAll('.sv-filter-item'));

        function fits() { return form.scrollWidth <= form.clientWidth + 1; }

        function layout() {
            // 1. All filters back inline, popover hidden.
            allItems.forEach(function (it) { itemsWrap.appendChild(it); });
            moreWrap.classList.add('d-none');
            if (fits()) { return; }
            // 2. Overflow: reveal "+Filter" and move trailing items into it until the row fits.
            moreWrap.classList.remove('d-none');
            var moved = 0;
            for (var i = allItems.length - 1; i >= 0; i--) {
                if (fits()) break;
                moreMenu.insertBefore(allItems[i], moreMenu.firstChild); // prepend keeps original order
                moved++;
            }
            moreToggle.textContent = moved > 0 ? ('+' + moved + ' Filter') : '+ Filter';
            if (moved === 0) { moreWrap.classList.add('d-none'); }
        }

        var raf = null;
        function scheduleLayout() {
            if (raf) return;
            raf = window.requestAnimationFrame(function () { raf = null; layout(); });
        }

        layout();
        window.addEventListener('resize', scheduleLayout);

        // Re-measure when the row's available width actually changes — sidebar
        // collapse, zoom, split-screen. Observes the form's PARENT, whose width
        // layout() never alters; observing the items row we mutate would re-fire
        // on every move.
        if (window.ResizeObserver && form.parentElement) {
            new ResizeObserver(scheduleLayout).observe(form.parentElement);
        }

        // Choices.js is fetched from a CDN and rewrites these selects long after
        // DOMContentLoaded, changing their widths. Without a re-measure past that
        // point the row keeps every filter inline and overflows the screen.
        window.addEventListener('load', scheduleLayout);
        [150, 500, 1000, 2000, 3000].forEach(function (ms) { window.setTimeout(layout, ms); });

    })();
    </script>
    @endpush
</div>

{{-- Choices.js (Bootstrap-aligned styling below) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

{{-- Add Selling Voucher Modal (same UI/UX as Create Purchase Order) --}}
<style>
/* Equal-width filter columns (grid); min-width 0 so Choices/Tom shrink inside fr tracks */
.sv-selling-voucher-filters .sv-filter-fields-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1rem;
    align-items: end;
}
@media (max-width: 1199.98px) {
    .sv-selling-voucher-filters .sv-filter-fields-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 767.98px) {
    .sv-selling-voucher-filters .sv-filter-fields-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 575.98px) {
    .sv-selling-voucher-filters .sv-filter-fields-grid {
        grid-template-columns: 1fr;
    }
}
.sv-selling-voucher-filters .sv-filter-field {
    min-width: 0;
}
.sv-selling-voucher-filters .sv-filter-field .choices {
    width: 100%;
    max-width: 100%;
}

/* All filter fields: same surface, border, focus as theme form-select (admin styles.css) */
.sv-selling-voucher-filters .form-select,
.sv-selling-voucher-filters .form-control {
    background-color: #fff;
    box-shadow: none;
    border: var(--bs-border-width) solid #e0e6eb;
    color: #526b7a;
}
.sv-selling-voucher-filters .form-select:focus,
.sv-selling-voucher-filters .form-control:focus {
    background-color: #fff;
    border-color: #b1adff;
    color: #526b7a;
    box-shadow: 0 0 0 0.25rem rgba(99, 91, 255, 0.25);
}
.sv-selling-voucher-filters input[type="date"].form-control {
    color-scheme: light;
}
.sv-selling-voucher-filters input[type="date"].form-control::-webkit-calendar-picker-indicator {
    opacity: 0.75;
}

/* Filter dropdowns: the searchable widget must be visually identical to the
   native pill it replaced (.sv-filter-select-native above) — same box, same
   chevron, same focus ring. Adding search must not change the design. */
.sv-master-page .choices.sv-filter-choices {
    margin-bottom: 0;
    width: 150px;
    flex-shrink: 0;
}
.sv-master-page .choices.sv-filter-choices .choices__inner {
    min-height: 40px;
    padding: 0 2.25rem 0 0.75rem;
    display: flex;
    align-items: center;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    box-shadow: none;
    font-size: .9375rem;
    color: #344054;
}
/* Choices adds its own dropdown arrow; the chevron above is the native one. */
.sv-master-page .choices.sv-filter-choices[data-type*="select-one"]::after {
    display: none;
}
.sv-master-page .choices.sv-filter-choices .choices__list--single {
    padding: 0;
}
.sv-master-page .choices.sv-filter-choices .choices__list--single .choices__item {
    font-size: .9375rem;
    color: #344054;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sv-master-page .choices.sv-filter-choices .choices__placeholder {
    color: #344054;
    opacity: 1;
}
.sv-master-page .choices.sv-filter-choices.is-open .choices__inner,
.sv-master-page .choices.sv-filter-choices.is-focused .choices__inner {
    border-color: var(--ds-primary, #004a93);
    box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
}
/* Open state keeps the same rounded box rather than squaring off. */
.sv-master-page .choices.sv-filter-choices.is-open .choices__inner {
    border-radius: 8px;
}
.sv-master-page .choices.sv-filter-choices .choices__list--dropdown {
    z-index: 1050;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: .9375rem;
    margin-top: 0.25rem;
}
.sv-master-page .choices.sv-filter-choices .choices__list--dropdown .choices__item {
    font-size: .9375rem;
    color: #344054;
}
/* The search field inside the dropdown — the only visible addition. */
.sv-master-page .choices.sv-filter-choices .choices__list--dropdown .choices__input {
    min-height: 34px;
    margin: 0.5rem;
    padding: 0 0.5rem;
    width: calc(100% - 1rem);
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    font-size: .875rem;
    color: #344054;
    background-color: #fff;
}
/* Inside the "+Filter" popover the filters are full width, as before. */
.sv-more-menu .sv-filter-item .choices.sv-filter-choices {
    width: 100%;
}

#addSellingVoucherModal .modal-dialog,
#editSellingVoucherModal .modal-dialog,
#viewSellingVoucherModal .modal-dialog,
#returnItemModal .modal-dialog {
    width: calc(100vw - 1rem);
    max-width: min(var(--bs-modal-width), calc(100vw - 1rem));
}
@media (min-width: 576px) {
    #addSellingVoucherModal .modal-dialog,
    #editSellingVoucherModal .modal-dialog,
    #viewSellingVoucherModal .modal-dialog,
    #returnItemModal .modal-dialog {
        width: calc(100vw - 2rem);
        max-width: min(var(--bs-modal-width), calc(100vw - 2rem));
    }
}
#addSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#addSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; }
#addSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); position: relative; z-index: 2; }
#editSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); position: relative; z-index: 2; }
#addSellingVoucherModal:not(.sv-choices-dropdown-open) .modal-body,
#editSellingVoucherModal:not(.sv-choices-dropdown-open) .modal-body {
    overflow-x: auto;
}
/* Body subtree must stack above modal-footer or the footer paints over overflowing dropdowns */
#addSellingVoucherModal .modal-footer,
#editSellingVoucherModal .modal-footer {
    position: relative;
    z-index: 1;
}
/* While dropdown is open keep modal width/scroll stable on small screens */
#addSellingVoucherModal.sv-choices-dropdown-open .modal-dialog,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-dialog {
    overflow-x: hidden !important;
}
#addSellingVoucherModal.sv-choices-dropdown-open .modal-content,
#addSellingVoucherModal.sv-choices-dropdown-open .modal-body,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-content,
#editSellingVoucherModal.sv-choices-dropdown-open .modal-body {
    overflow-x: hidden !important;
}
/* Item Details: do not use .table-responsive here — overflow-x:auto makes overflow-y compute to auto and clips Choices */
#addSellingVoucherModal .sv-item-details-table-wrap,
#editSellingVoucherModal .sv-item-details-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}
#addSellingVoucherModal .sv-item-details-table-wrap .table,
#editSellingVoucherModal .sv-item-details-table-wrap .table {
    min-width: 920px;
    margin-bottom: 0;
}
@media (max-width: 991.98px) {
    #addSellingVoucherModal .sv-item-details-table-wrap,
    #editSellingVoucherModal .sv-item-details-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #addSellingVoucherModal .sv-item-details-table-wrap .table,
    #editSellingVoucherModal .sv-item-details-table-wrap .table {
        min-width: 980px;
    }
}
#addSellingVoucherModal.sv-choices-dropdown-open .card:has(#modalItemsBody) .card-body,
#editSellingVoucherModal.sv-choices-dropdown-open .card:has(#editModalItemsBody) .card-body {
    overflow-x: hidden !important;
}
#addSellingVoucherModal.sv-choices-dropdown-open #modalItemsBody .choices,
#editSellingVoucherModal.sv-choices-dropdown-open #editModalItemsBody .choices {
    overflow: visible !important;
}
/* Item card: table sits in .card-body; .card-footer (grand total) was painting over the list */
#addSellingVoucherModal .card:has(#modalItemsBody) .card-body,
#editSellingVoucherModal .card:has(#editModalItemsBody) .card-body {
    position: relative;
    z-index: 2;
}
#addSellingVoucherModal .card:has(#modalItemsBody) .card-footer,
#editSellingVoucherModal .card:has(#editModalItemsBody) .card-footer {
    position: relative;
    z-index: 1;
}
#addSellingVoucherModal.sv-choices-dropdown-open .card:has(#modalItemsBody),
#editSellingVoucherModal.sv-choices-dropdown-open .card:has(#editModalItemsBody) {
    overflow-x: hidden !important;
}
/* Choices default --choices-z-index is 1; raise for modals + item table row stacking */
#addSellingVoucherModal .choices,
#editSellingVoucherModal .choices {
    --choices-z-index: 6100;
}
#modalItemsBody tr:has(.choices.is-open),
#editModalItemsBody tr:has(.choices.is-open) {
    position: relative;
    z-index: 50;
}
.ts-dropdown,
.ts-wrapper.choices .choices__list--dropdown,
.choices__list--dropdown.is-active {
    z-index: 6100 !important;
}
.ts-wrapper.choices { margin-bottom: 0; }
.ts-wrapper.choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius, 0.375rem);
    background-color: var(--bs-body-bg, #fff);
    font-size: 1rem;
}
#modalItemsBody .ts-wrapper.choices .choices__inner,
#editModalItemsBody .ts-wrapper.choices .choices__inner {
    min-height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: var(--bs-border-radius-sm, 0.25rem);
}
.ts-wrapper.choices.is-open .choices__inner,
.ts-wrapper.choices.is-focused .choices__inner {
    border-color: var(--bs-primary, #86b7fe);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb, 13, 110, 253), 0.25);
}
.ts-wrapper.choices .choices__list--single { padding: 0; }
.ts-wrapper.choices[data-type*="select-one"] .choices__input {
    display: block !important;
    width: 100% !important;
    min-width: 100% !important;
}
/* Niche open: search upar | Uper (flipped) open: search niche */
.ts-wrapper.choices .choices__list--dropdown.is-active {
    display: flex;
    flex-direction: column;
}
.ts-wrapper.choices.is-flipped .choices__list--dropdown.is-active { flex-direction: column-reverse; }
.ts-wrapper.choices .choices__list--dropdown.is-active .choices__list {
    flex: 1 1 auto;
    min-height: 0;
}
.ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown .choices__input--cloned,
.ts-wrapper.choices[data-type*="select-one"] .choices__list--dropdown .choices__input {
    border-top: none !important;
    border-bottom: 1px solid #ced4da !important;
    margin-bottom: 0 !important;
}
.ts-wrapper.choices.is-flipped[data-type*="select-one"] .choices__list--dropdown .choices__input--cloned,
.ts-wrapper.choices.is-flipped[data-type*="select-one"] .choices__list--dropdown .choices__input {
    border-bottom: none !important;
    border-top: 1px solid #ced4da !important;
    margin-bottom: 0 !important;
}
.ts-wrapper.choices .choices__list--dropdown .choices__input--cloned {
    display: block !important;
    position: relative !important;
    opacity: 1 !important;
    flex-shrink: 0;
    min-height: 34px;
    width: 100% !important;
}
.ts-dropdown .choices__item--selectable.is-highlighted {
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.12);
}
/* Item Name: dropdown positioned with JS (position:fixed) — class for inner scroll cap */
#modalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed,
#editModalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed {
    box-sizing: border-box;
}
#modalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed .choices__list,
#editModalItemsBody .choices__list--dropdown.sv-item-choices-dropdown-fixed .choices__list {
    max-height: min(280px, 42vh) !important;
}
</style>
<style>
/* ══ Add / Edit Selling Voucher — compact spec design ══
   Same visual language as the Purchase Order modals. Selectors are ID-grouped so
   they outrank the older ID-scoped rules above (notably the Choices sizing).
   Never write a literal Blade directive in a comment here — Blade compiles it
   even inside a comment, which opens a stray output buffer and blanks the page. */
#addSellingVoucherModal .modal-content,
#editSellingVoucherModal .modal-content {
    border: 0;
    border-radius: 8px;
    box-shadow: 0 16px 40px rgba(16, 24, 40, .16);
}

#addSellingVoucherModal .modal-header,
#editSellingVoucherModal .modal-header {
    align-items: center;
    padding: .875rem 1.25rem;
    background: #fff !important;
    border-bottom: 1px solid #e9ecef;
}

#addSellingVoucherModal .modal-title,
#editSellingVoucherModal .modal-title {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
    color: #212529;
}

#addSellingVoucherModal .btn-close,
#editSellingVoucherModal .btn-close {
    padding: .5rem;
    font-size: .8rem;
    opacity: .55;
}

#addSellingVoucherModal .modal-body,
#editSellingVoucherModal .modal-body {
    padding: 1.125rem 1.25rem 1.25rem;
    background: #fff;
}

/* ── Labels ── */
#addSellingVoucherModal .sv-label,
#editSellingVoucherModal .sv-label {
    display: block;
    margin-bottom: .25rem;
    font-size: .75rem;
    font-weight: 400;
    line-height: 1.2;
    color: #212529;
}

#addSellingVoucherModal .sv-req,
#editSellingVoucherModal .sv-req {
    color: #dc3545;
}

/* ── Client-type radio row ── */
#addSellingVoucherModal .sv-radio-row,
#editSellingVoucherModal .sv-radio-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    align-items: center;
    min-height: 32px;
}

#addSellingVoucherModal .sv-radio-row .form-check,
#editSellingVoucherModal .sv-radio-row .form-check {
    display: flex;
    align-items: center;
    gap: .375rem;
    margin: 0;
    padding: 0;
    min-height: 0;
}

#addSellingVoucherModal .sv-radio-row .form-check-input,
#editSellingVoucherModal .sv-radio-row .form-check-input {
    width: 14px;
    height: 14px;
    margin: 0;
    float: none;
    border-color: #adb5bd;
}

#addSellingVoucherModal .sv-radio-row .form-check-input:checked,
#editSellingVoucherModal .sv-radio-row .form-check-input:checked {
    background-color: var(--ds-primary, #004384);
    border-color: var(--ds-primary, #004384);
}

#addSellingVoucherModal .sv-radio-row .form-check-input:focus,
#editSellingVoucherModal .sv-radio-row .form-check-input:focus {
    border-color: var(--ds-primary, #004384);
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12);
}

#addSellingVoucherModal .sv-radio-row .form-check-label,
#editSellingVoucherModal .sv-radio-row .form-check-label {
    font-size: .78125rem;
    line-height: 1.2;
    color: #212529;
    cursor: pointer;
}

/* ── Controls ── */
#addSellingVoucherModal .form-control,
#addSellingVoucherModal .form-select,
#editSellingVoucherModal .form-control,
#editSellingVoucherModal .form-select {
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

#addSellingVoucherModal .form-select,
#editSellingVoucherModal .form-select {
    padding-right: 1.75rem;
    background-size: 12px 9px;
    background-position: right .5rem center;
}

#addSellingVoucherModal .form-control::placeholder,
#editSellingVoucherModal .form-control::placeholder {
    color: #adb5bd;
    opacity: 1;
}

#addSellingVoucherModal .form-control:focus,
#addSellingVoucherModal .form-select:focus,
#editSellingVoucherModal .form-control:focus,
#editSellingVoucherModal .form-select:focus {
    border-color: var(--ds-primary, #004384) !important;
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12) !important;
}

#addSellingVoucherModal input[type="date"]::-webkit-calendar-picker-indicator,
#editSellingVoucherModal input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: .5;
    cursor: pointer;
}

/* Choices.js controls → same box as a native select (double-ID beats the
   #modalItemsBody .ts-wrapper.choices sizing rules defined above) */
#addSellingVoucherModal .choices,
#editSellingVoucherModal .choices {
    margin-bottom: 0;
}

#addSellingVoucherModal .ts-wrapper.choices .choices__inner,
#editSellingVoucherModal .ts-wrapper.choices .choices__inner,
#addSellingVoucherModal #modalItemsBody .ts-wrapper.choices .choices__inner,
#editSellingVoucherModal #editModalItemsBody .ts-wrapper.choices .choices__inner {
    display: flex;
    align-items: center;
    height: 32px;
    min-height: 32px;
    padding: 0 1.75rem 0 .5rem;
    font-size: .78125rem;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-shadow: none;
}

#addSellingVoucherModal #modalItemsBody .ts-wrapper.choices .choices__inner,
#editSellingVoucherModal #editModalItemsBody .ts-wrapper.choices .choices__inner {
    height: 28px;
    min-height: 28px;
    padding: 0 1.5rem 0 .375rem;
    font-size: .71875rem;
}

#addSellingVoucherModal .ts-wrapper.choices.is-open .choices__inner,
#addSellingVoucherModal .ts-wrapper.choices.is-focused .choices__inner,
#editSellingVoucherModal .ts-wrapper.choices.is-open .choices__inner,
#editSellingVoucherModal .ts-wrapper.choices.is-focused .choices__inner {
    border-color: var(--ds-primary, #004384);
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12);
}

#addSellingVoucherModal .choices__list--single .choices__item,
#editSellingVoucherModal .choices__list--single .choices__item {
    font-size: .78125rem;
    line-height: 1.4;
    color: #212529;
}

#addSellingVoucherModal #modalItemsBody .choices__list--single .choices__item,
#editSellingVoucherModal #editModalItemsBody .choices__list--single .choices__item {
    font-size: .71875rem;
}

#addSellingVoucherModal .choices__placeholder,
#editSellingVoucherModal .choices__placeholder {
    color: #adb5bd;
    opacity: 1;
}

#addSellingVoucherModal .choices[data-type*="select-one"]::after,
#editSellingVoucherModal .choices[data-type*="select-one"]::after {
    right: .625rem;
    border-width: 4px;
    border-top-color: #6c757d;
}

#addSellingVoucherModal .choices__list--dropdown .choices__item,
#editSellingVoucherModal .choices__list--dropdown .choices__item {
    font-size: .78125rem;
}

/* ── Item table ── */
#addSellingVoucherModal .sv-items-box,
#editSellingVoucherModal .sv-items-box {
    margin-top: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

#addSellingVoucherModal .sv-items-table,
#editSellingVoucherModal .sv-items-table {
    margin: 0;
    font-size: .75rem;
}

#addSellingVoucherModal .sv-items-table>thead>tr>th,
#editSellingVoucherModal .sv-items-table>thead>tr>th {
    padding: .5rem;
    font-size: .71875rem;
    font-weight: 600;
    color: #212529;
    white-space: nowrap;
    vertical-align: middle;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#addSellingVoucherModal .sv-items-table>tbody>tr>td,
#editSellingVoucherModal .sv-items-table>tbody>tr>td {
    padding: .3125rem .375rem;
    vertical-align: middle;
    background: #fff;
    border-bottom: 1px solid #f1f3f5;
}

#addSellingVoucherModal .sv-items-table>tbody>tr:last-child>td,
#editSellingVoucherModal .sv-items-table>tbody>tr:last-child>td {
    border-bottom: 0;
}

#addSellingVoucherModal .sv-items-table th:first-child,
#addSellingVoucherModal .sv-items-table td:first-child,
#editSellingVoucherModal .sv-items-table th:first-child,
#editSellingVoucherModal .sv-items-table td:first-child {
    padding-left: .625rem;
}

#addSellingVoucherModal .sv-items-table th:last-child,
#addSellingVoucherModal .sv-items-table td:last-child,
#editSellingVoucherModal .sv-items-table th:last-child,
#editSellingVoucherModal .sv-items-table td:last-child {
    padding-right: .625rem;
}

#addSellingVoucherModal .sv-items-table .form-control,
#addSellingVoucherModal .sv-items-table .form-select,
#editSellingVoucherModal .sv-items-table .form-control,
#editSellingVoucherModal .sv-items-table .form-select {
    height: 28px;
    min-height: 28px;
    padding: .1875rem .375rem;
    font-size: .71875rem;
}

/* readonly cells stay white — nothing in this table is a greyed-out field */
#addSellingVoucherModal .sv-items-table input[readonly],
#editSellingVoucherModal .sv-items-table input[readonly],
#addSellingVoucherModal .sv-items-table input[readonly].bg-light,
#editSellingVoucherModal .sv-items-table input[readonly].bg-light {
    background-color: #fff !important;
    color: #495057;
}

#addSellingVoucherModal .sv-items-table .invalid-feedback,
#editSellingVoucherModal .sv-items-table .invalid-feedback {
    font-size: .65rem;
}

/* Row actions — remove on every row, add on the last row only */
#addSellingVoucherModal .sv-act-cell,
#editSellingVoucherModal .sv-act-cell {
    white-space: nowrap;
}

#addSellingVoucherModal .sv-icon-btn,
#editSellingVoucherModal .sv-icon-btn {
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

#addSellingVoucherModal .sv-icon-btn+.sv-icon-btn,
#editSellingVoucherModal .sv-icon-btn+.sv-icon-btn {
    margin-left: .375rem;
}

#addSellingVoucherModal .sv-icon-btn--remove,
#editSellingVoucherModal .sv-icon-btn--remove {
    background: #dc3545;
}

#addSellingVoucherModal .sv-icon-btn--remove:hover:not(:disabled),
#editSellingVoucherModal .sv-icon-btn--remove:hover:not(:disabled) {
    background: #bb2d3b;
}

#addSellingVoucherModal .sv-icon-btn--remove:disabled,
#editSellingVoucherModal .sv-icon-btn--remove:disabled {
    opacity: .45;
}

#addSellingVoucherModal .sv-icon-btn--add,
#editSellingVoucherModal .sv-icon-btn--add {
    background: #0d6efd;
}

#addSellingVoucherModal .sv-icon-btn--add:hover,
#editSellingVoucherModal .sv-icon-btn--add:hover {
    background: #0b5ed7;
}

#addSellingVoucherModal #modalItemsBody tr:not(:last-child) .sv-add-row,
#editSellingVoucherModal #editModalItemsBody tr:not(:last-child) .sv-add-row {
    visibility: hidden;
}

/* Total strip */
#addSellingVoucherModal .sv-total-bar,
#editSellingVoucherModal .sv-total-bar {
    padding: .4375rem .75rem;
    font-size: .78125rem;
    font-weight: 600;
    text-align: right;
    color: var(--ds-primary, #004384);
    background: #e7f0fb;
    border-top: 1px solid #dee2e6;
}

#modalGrandTotal,
#editModalGrandTotal {
    font-weight: 700;
    color: var(--ds-primary, #004384);
}

/* ── Footer ── */
#addSellingVoucherModal .modal-footer,
#editSellingVoucherModal .modal-footer {
    gap: .5rem;
    padding: .75rem 1.25rem;
    background: #fff;
    border-top: 1px solid #e9ecef;
}

#addSellingVoucherModal .modal-footer>*,
#editSellingVoucherModal .modal-footer>* {
    margin: 0;
}

#addSellingVoucherModal .modal-footer .btn,
#editSellingVoucherModal .modal-footer .btn {
    padding: .375rem 1.125rem;
    font-size: .8125rem;
    font-weight: 500;
    border-radius: 4px;
}

#addSellingVoucherModal .modal-footer .sv-btn-primary,
#editSellingVoucherModal .modal-footer .sv-btn-primary {
    color: #fff;
    background: var(--ds-primary, #004384);
    border: 1px solid var(--ds-primary, #004384);
}

#addSellingVoucherModal .modal-footer .sv-btn-cancel,
#editSellingVoucherModal .modal-footer .sv-btn-cancel {
    color: #dc3545;
    background: #fff;
    border: 1px solid #dc3545;
}

/* ══ View Selling Voucher — read-only twin of the Add / Edit design ══ */
#viewSellingVoucherModal .modal-content {
    border: 0;
    border-radius: 8px;
    box-shadow: 0 16px 40px rgba(16, 24, 40, .16);
}

#viewSellingVoucherModal .modal-header {
    align-items: center;
    padding: .875rem 1.25rem;
    background: #fff !important;
    border-bottom: 1px solid #e9ecef;
}

#viewSellingVoucherModal .modal-title {
    font-size: 1rem !important;
    font-weight: 600 !important;
    line-height: 1.3;
    color: #212529 !important;
}

#viewSellingVoucherModal .btn-close {
    padding: .5rem;
    font-size: .8rem;
    opacity: .55;
}

#viewSellingVoucherModal .modal-body {
    padding: 1.125rem 1.25rem 1.25rem;
    background: #fff;
}

#viewSellingVoucherModal .sv-label {
    display: block;
    margin-bottom: .25rem;
    font-size: .75rem;
    font-weight: 400;
    line-height: 1.2;
    color: #212529;
}

#viewSellingVoucherModal .sv-value {
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

#viewSellingVoucherModal .sv-status-pill {
    display: inline-flex;
    align-items: center;
    padding: .1875rem .625rem;
    font-size: .71875rem;
    font-weight: 600;
    border-radius: 999px;
}

#viewSellingVoucherModal .sv-status-pill--approved {
    color: #067647;
    background: #ecfdf3;
}

#viewSellingVoucherModal .sv-status-pill--completed {
    color: var(--ds-primary, #004384);
    background: #e7f0fb;
}

#viewSellingVoucherModal .sv-status-pill--pending {
    color: #b54708;
    background: #fffaeb;
}

#viewSellingVoucherModal .sv-status-pill--other {
    color: #475467;
    background: #f2f4f7;
}

#viewSellingVoucherModal .sv-items-box {
    margin-top: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

#viewSellingVoucherModal .sv-items-table {
    margin: 0;
    font-size: .75rem;
}

#viewSellingVoucherModal .sv-items-table>thead>tr>th {
    padding: .5rem;
    font-size: .71875rem;
    font-weight: 600;
    color: #212529 !important;
    white-space: nowrap;
    vertical-align: middle;
    background: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
}

#viewSellingVoucherModal .sv-items-table>tbody>tr>td {
    padding: .4375rem .375rem;
    font-size: .75rem;
    color: #212529 !important;
    vertical-align: middle;
    background: #fff !important;
    border-bottom: 1px solid #f1f3f5;
}

#viewSellingVoucherModal .sv-items-table>tbody>tr:last-child>td {
    border-bottom: 0;
}

#viewSellingVoucherModal .sv-items-table th:first-child,
#viewSellingVoucherModal .sv-items-table td:first-child {
    padding-left: .625rem;
}

#viewSellingVoucherModal .sv-items-table th:last-child,
#viewSellingVoucherModal .sv-items-table td:last-child {
    padding-right: .625rem;
}

#viewSellingVoucherModal .sv-total-bar {
    padding: .4375rem .75rem;
    font-size: .78125rem;
    font-weight: 600;
    text-align: right;
    color: var(--ds-primary, #004384) !important;
    background: #e7f0fb !important;
    border-top: 1px solid #dee2e6;
}

#viewModalGrandTotal {
    font-weight: 700;
    color: var(--ds-primary, #004384) !important;
}

#viewSellingVoucherModal .sv-meta {
    margin-top: .875rem;
    font-size: .71875rem;
    color: #6c757d;
}

#viewSellingVoucherModal .modal-footer {
    gap: .5rem;
    padding: .75rem 1.25rem;
    background: #fff;
    border-top: 1px solid #e9ecef;
}

#viewSellingVoucherModal .modal-footer>* {
    margin: 0;
}

#viewSellingVoucherModal .modal-footer .btn {
    padding: .375rem 1.125rem;
    font-size: .8125rem;
    font-weight: 500;
    border-radius: 4px;
}

#viewSellingVoucherModal .modal-footer .sv-btn-primary {
    color: #fff;
    background: var(--ds-primary, #004384);
    border: 1px solid var(--ds-primary, #004384);
}

#viewSellingVoucherModal .modal-footer .sv-btn-cancel {
    color: #dc3545;
    background: #fff;
    border: 1px solid #dc3545;
}

/* ══ Transfer To (return items) — same spec design as Add / Edit / View ══ */
#returnItemModal .modal-content {
    border: 0;
    border-radius: 8px;
    box-shadow: 0 16px 40px rgba(16, 24, 40, .16);
}

#returnItemModal .modal-header {
    align-items: center;
    padding: .875rem 1.25rem;
    background: #fff !important;
    border-bottom: 1px solid #e9ecef;
}

#returnItemModal .modal-title {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
    color: #212529;
}

#returnItemModal .btn-close {
    padding: .5rem;
    font-size: .8rem;
    opacity: .55;
}

#returnItemModal .modal-body {
    padding: 1.125rem 1.25rem 1.25rem;
    background: #fff;
}

#returnItemModal .sv-label {
    display: block;
    margin-bottom: .25rem;
    font-size: .75rem;
    font-weight: 400;
    line-height: 1.2;
    color: #212529;
}

#returnItemModal .sv-value {
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

#returnItemModal .sv-items-box {
    margin-top: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

#returnItemModal .sv-items-table {
    margin: 0;
    font-size: .75rem;
}

#returnItemModal .sv-items-table>thead>tr>th {
    padding: .5rem;
    font-size: .71875rem;
    font-weight: 600;
    color: #212529;
    white-space: nowrap;
    vertical-align: middle;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#returnItemModal .sv-items-table>tbody>tr>td {
    padding: .3125rem .375rem;
    font-size: .75rem;
    color: #212529;
    vertical-align: middle;
    background: #fff;
    border-bottom: 1px solid #f1f3f5;
}

#returnItemModal .sv-items-table>tbody>tr:last-child>td {
    border-bottom: 0;
}

#returnItemModal .sv-items-table th:first-child,
#returnItemModal .sv-items-table td:first-child {
    padding-left: .625rem;
}

#returnItemModal .sv-items-table th:last-child,
#returnItemModal .sv-items-table td:last-child {
    padding-right: .625rem;
}

#returnItemModal .sv-items-table .form-control {
    height: 28px;
    min-height: 28px;
    padding: .1875rem .375rem;
    font-size: .71875rem;
    line-height: 1.4;
    color: #212529;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-shadow: none;
}

#returnItemModal .sv-items-table .form-control:focus {
    border-color: var(--ds-primary, #004384);
    box-shadow: 0 0 0 .15rem rgba(0, 67, 132, .12);
}

#returnItemModal .sv-items-table input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: .5;
    cursor: pointer;
}

#returnItemModal .sv-items-table .invalid-feedback {
    font-size: .65rem;
}

#returnItemModal .modal-footer {
    gap: .5rem;
    padding: .75rem 1.25rem;
    background: #fff;
    border-top: 1px solid #e9ecef;
}

#returnItemModal .modal-footer>* {
    margin: 0;
}

#returnItemModal .modal-footer .btn {
    padding: .375rem 1.125rem;
    font-size: .8125rem;
    font-weight: 500;
    border-radius: 4px;
}

#returnItemModal .modal-footer .sv-btn-primary {
    color: #fff;
    background: var(--ds-primary, #004384);
    border: 1px solid var(--ds-primary, #004384);
}

#returnItemModal .modal-footer .sv-btn-cancel {
    color: #dc3545;
    background: #fff;
    border: 1px solid #dc3545;
}
</style>
<div class="modal fade" id="addSellingVoucherModal" tabindex="-1" aria-labelledby="addSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.mess.material-management.store') }}" method="POST" id="sellingVoucherModalForm" enctype="multipart/form-data">
                @csrf
                {{-- Forces JSON response from store() so the modal can reset without a full page redirect --}}
                <input type="hidden" name="respond_json" value="1">
                <input type="hidden" name="client_id" id="modalClientId" value="{{ old('client_id') }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSellingVoucherModalLabel">Add Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                            <ul class="mb-0 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="sv-label">Client Type<span class="sv-req">*</span></label>
                            <div class="sv-radio-row">
                                @foreach($clientTypes as $slug => $label)
                                    <div class="form-check">
                                        <input class="form-check-input client-type-radio" type="radio" name="client_type_slug" id="modal_ct_{{ $slug }}" value="{{ $slug }}" {{ old('client_type_slug', 'employee') === $slug ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="modal_ct_{{ $slug }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Payment Type<span class="sv-req">*</span></label>
                            <select name="payment_type" class="form-select" required>
                                <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit</option>
                                <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash</option>
                                <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="modalClientNameWrap">
                            <label class="sv-label">Client Name<span class="sv-req">*</span></label>
                            <select name="client_type_pk" class="form-select" id="modalClientNameSelect">
                                <option value="">Select Client</option>
                                @foreach($clientNamesByType as $type => $list)
                                    @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <select id="modalOtCourseSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                            <select id="modalCourseSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="modalNameFieldWrap">
                            <label class="sv-label">Name<span class="sv-req">*</span></label>
                            <input type="text" name="client_name" id="modalClientNameInput" class="form-control" value="{{ old('client_name') }}" placeholder="e.g. John Doe" required>
                            <datalist id="modalCourseBuyerNames"></datalist>
                            <datalist id="modalGenericBuyerNames"></datalist>
                            <select id="modalFacultySelect" class="form-select" style="display:none;">
                                <option value="">Select Faculty</option>
                                @foreach($faculties ?? [] as $f)
                                    <option value="{{ e($f->full_name) }}" data-pk="{{ $f->pk }}">{{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="modalAcademyStaffSelect" class="form-select" style="display:none;">
                                <option value="">Select Academy Staff</option>
                                @foreach($employees ?? [] as $e)
                                    <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="modalMessStaffSelect" class="form-select" style="display:none;">
                                <option value="">Select Mess Staff</option>
                                @foreach($messStaff ?? [] as $e)
                                    <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="modalOtStudentSelect" class="form-select" style="display:none;">
                                <option value="">Select Student</option>
                            </select>
                            <select id="modalCourseNameSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}">{{ e($course->course_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Transfer from Store<span class="sv-req">*</span></label>
                            <select name="store_id" class="form-select" required>
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}" {{ old('store_id') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Remarks/ Reference Number/ Order By</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="e.g. Reference number, order by, etc.">
                        </div>
                    </div>

                    <div class="sv-items-box">
                        <div class="sv-item-details-table-wrap">
                            <table class="table sv-items-table" id="svItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Item<span class="sv-req">*</span></th>
                                        <th style="min-width: 80px;">Unit</th>
                                        <th style="min-width: 110px;">Available Qty</th>
                                        <th style="min-width: 100px;">Issue Qty</th>
                                        <th style="min-width: 100px;">Left Qty</th>
                                        <th style="min-width: 100px;">Rate<span class="sv-req">*</span></th>
                                        <th style="min-width: 110px;">Line Total</th>
                                        <th style="min-width: 86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="modalItemsBody">
                                    <tr class="sv-item-row">
                                        <td>
                                            <select name="items[0][item_subcategory_id]" class="form-select sv-item-select" required>
                                                <option value="">Item</option>
                                                @foreach($itemSubcategories as $s)
                                                    <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">{{ e($s['item_name'] ?? '-') }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[0][unit]" class="form-control sv-unit" readonly placeholder="-"></td>
                                        <td><input type="text" name="items[0][available_quantity]" class="form-control sv-avail bg-light" readonly placeholder="-"></td>
                                        <td>
                                            <input type="text" name="items[0][quantity]" class="form-control sv-qty" placeholder="-" required>
                                            <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                        </td>
                                        <td><input type="text" class="form-control sv-left bg-light" readonly placeholder="-"></td>
                                        <td><input type="text" name="items[0][rate]" class="form-control sv-rate" placeholder="-" required></td>
                                        <td><input type="text" class="form-control sv-total" readonly placeholder="-"></td>
                                        <td class="sv-act-cell">
                                            <button type="button" class="sv-icon-btn sv-icon-btn--remove sv-remove-row" disabled title="Remove line" aria-label="Remove line">&minus;</button>
                                            <button type="button" class="sv-icon-btn sv-icon-btn--add sv-add-row" title="Add line" aria-label="Add line">+</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="sv-total-bar">Total: <span id="modalGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn sv-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sv-btn-primary">Add Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Selling Voucher Modal (body z-index / overflow: shared rules with Add modal above) --}}
<style>
#editSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#editSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; }
</style>
<div class="modal fade" id="editSellingVoucherModal" tabindex="-1" aria-labelledby="editSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="editSellingVoucherForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="client_id" id="editModalClientId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSellingVoucherModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="sv-label">Client Type<span class="sv-req">*</span></label>
                            <div class="sv-radio-row">
                                @foreach($clientTypes as $slug => $label)
                                    <div class="form-check">
                                        <input class="form-check-input edit-client-type-radio" type="radio" name="client_type_slug" id="edit_ct_{{ $slug }}" value="{{ $slug }}" required>
                                        <label class="form-check-label" for="edit_ct_{{ $slug }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Payment Type<span class="sv-req">*</span></label>
                            <select name="payment_type" class="form-select edit-payment-type" required>
                                <option value="1">Credit</option>
                                <option value="0">Cash</option>
                                <option value="2">UPI</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="editModalClientNameWrap">
                            <label class="sv-label">Client Name<span class="sv-req">*</span></label>
                            <select name="client_type_pk" class="form-select" id="editClientNameSelect">
                                <option value="">Select Client</option>
                                @foreach($clientNamesByType as $type => $list)
                                    @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <select id="editModalOtCourseSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                            <select id="editModalCourseSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="editModalNameFieldWrap">
                            <label class="sv-label">Name<span class="sv-req">*</span></label>
                            <input type="text" name="client_name" class="form-control edit-client-name" id="editModalClientNameInput" placeholder="e.g. John Doe" required>
                            <datalist id="editCourseBuyerNames"></datalist>
                            <datalist id="editGenericBuyerNames"></datalist>
                            <select id="editModalFacultySelect" class="form-select" style="display:none;">
                                <option value="">Select Faculty</option>
                                @foreach($faculties ?? [] as $f)
                                    <option value="{{ e($f->full_name) }}" data-pk="{{ $f->pk }}">{{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="editModalAcademyStaffSelect" class="form-select" style="display:none;">
                                <option value="">Select Academy Staff</option>
                                @foreach($employees ?? [] as $e)
                                    <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="editModalMessStaffSelect" class="form-select" style="display:none;">
                                <option value="">Select Mess Staff</option>
                                @foreach($messStaff ?? [] as $e)
                                    <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">{{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                @endforeach
                            </select>
                            <select id="editModalOtStudentSelect" class="form-select" style="display:none;">
                                <option value="">Select Student</option>
                            </select>
                            <select id="editModalCourseNameSelect" class="form-select" style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control edit-issue-date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Transfer from Store<span class="sv-req">*</span></label>
                            <select name="store_id" class="form-select edit-store" required>
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="sv-label">Remarks/ Reference Number/ Order By</label>
                            <input type="text" name="remarks" class="form-control edit-remarks" placeholder="e.g. Reference number, order by, etc.">
                        </div>
                    </div>

                    <div class="sv-items-box">
                        <div class="sv-item-details-table-wrap">
                            <table class="table sv-items-table" id="editSvItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Item<span class="sv-req">*</span></th>
                                        <th style="min-width: 80px;">Unit</th>
                                        <th style="min-width: 110px;">Available Qty</th>
                                        <th style="min-width: 100px;">Issue Qty</th>
                                        <th style="min-width: 100px;">Left Qty</th>
                                        <th style="min-width: 100px;">Rate<span class="sv-req">*</span></th>
                                        <th style="min-width: 110px;">Line Total</th>
                                        <th style="min-width: 86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editModalItemsBody"></tbody>
                            </table>
                        </div>
                        <div class="sv-total-bar">Total: <span id="editModalGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn sv-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sv-btn-primary">Update Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Selling Voucher Modal (read-only twin of the Add / Edit design) --}}
<style>
#viewSellingVoucherModal .modal-dialog { max-height: calc(100dvh - 2rem); margin: 1rem auto; }
#viewSellingVoucherModal .modal-content { max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; }
#viewSellingVoucherModal .modal-body { overflow-y: auto; max-height: calc(100dvh - 10rem); }
</style>
<div class="modal fade" id="viewSellingVoucherModal" tabindex="-1" aria-labelledby="viewSellingVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSellingVoucherModalLabel">View Selling Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="sv-label">Client Type</label>
                        <p class="sv-value mb-0" id="viewClientType">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Client Name</label>
                        <p class="sv-value mb-0" id="viewClientName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Payment Type</label>
                        <p class="sv-value mb-0" id="viewPaymentType">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Request Date</label>
                        <p class="sv-value mb-0" id="viewRequestDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Issue Date</label>
                        <p class="sv-value mb-0" id="viewIssueDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Transfer from Store</label>
                        <p class="sv-value mb-0" id="viewStoreName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Reference Number</label>
                        <p class="sv-value mb-0" id="viewReferenceNumber">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Order By</label>
                        <p class="sv-value mb-0" id="viewOrderBy">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="sv-label">Status</label>
                        <p class="mb-0"><span class="sv-status-pill sv-status-pill--pending" id="viewStatus">-</span></p>
                    </div>
                    <div class="col-12" id="viewRemarksWrap" style="display:none;">
                        <label class="sv-label">Remarks</label>
                        <p class="sv-value mb-0" id="viewRemarks"></p>
                    </div>
                </div>

                <div class="sv-items-box" id="viewItemsCard">
                    <div class="table-responsive">
                        <table class="table sv-items-table">
                            <thead>
                                <tr>
                                    <th style="width:26%;">Item</th>
                                    <th style="width:10%;">Unit</th>
                                    <th style="width:14%;">Issue Qty</th>
                                    <th style="width:14%;">Return Qty</th>
                                    <th style="width:16%;">Rate</th>
                                    <th style="width:20%;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody id="viewModalItemsBody"></tbody>
                        </table>
                    </div>
                    <div class="sv-total-bar">Total: <span id="viewModalGrandTotal">0.00</span>/-</div>
                </div>

                <div class="sv-meta">
                    Created: <span id="viewCreatedAt">-</span>
                    <span class="ms-3" id="viewUpdatedAtWrap" style="display:none;">Last Updated: <span id="viewUpdatedAt"></span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn sv-btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn sv-btn-primary btn-print-view-modal" data-print-target="#viewSellingVoucherModal" title="Print">Print</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="returnItemForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="returnItemModalLabel">Transfer To</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="sv-label">Transfer From Store</label>
                            <p class="sv-value mb-0" id="returnTransferFromStore">-</p>
                        </div>
                    </div>

                    <div class="sv-items-box">
                        <div class="table-responsive">
                            <table class="table sv-items-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 160px;">Item</th>
                                        <th style="min-width: 90px;">Issued Qty</th>
                                        <th style="min-width: 70px;">Unit</th>
                                        <th style="min-width: 100px;">Issue Date</th>
                                        <th style="min-width: 110px;">Return Qty</th>
                                        <th style="min-width: 140px;">Return Date</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItemModalBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn sv-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sv-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Selling Voucher script loaded');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');

    function safeFocus(el) {
        if (!el || typeof el.focus !== 'function') return;
        try {
            el.focus({ preventScroll: true });
        } catch (e) {
            try { el.focus(); } catch (e2) {}
        }
    }

    // Keep modal scroll stable; don't toggle overflow classes on dropdown open/close.
    function installModalScrollGuard(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var last = { winTop: 0, bodyTop: 0, has: false };
        function capture() {
            var body = modal.querySelector('.modal-body');
            last.winTop = (typeof window !== 'undefined') ? (window.scrollY || window.pageYOffset || 0) : 0;
            last.bodyTop = body ? body.scrollTop : 0;
            last.has = true;
        }
        function restoreSoon() {
            if (!last.has) return;
            var body = modal.querySelector('.modal-body');
            function restoreOnce() {
                try { window.scrollTo(0, last.winTop); } catch (e) {}
                if (body) body.scrollTop = last.bodyTop;
            }
            requestAnimationFrame(restoreOnce);
            setTimeout(restoreOnce, 0);
            setTimeout(restoreOnce, 50);
            setTimeout(restoreOnce, 150);
        }

        modal.addEventListener('pointerdown', function() {
            capture();
            restoreSoon();
        }, true);
        modal.addEventListener('focusin', function() {
            capture();
            restoreSoon();
        }, true);
    }

    installModalScrollGuard('addSellingVoucherModal');
    installModalScrollGuard('editSellingVoucherModal');

    /** Sync modal class when a Choices root (.choices) opens/closes only — not on every list item highlight (avoids huge MutationObserver churn). */
    function initSellingVoucherModalChoicesOpenSync() {
        ['addSellingVoucherModal', 'editSellingVoucherModal'].forEach(function(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            var flag = 'sv-choices-dropdown-open';
            function sync() {
                if (modal.querySelector('.choices.is-open')) modal.classList.add(flag);
                else modal.classList.remove(flag);
            }
            var mo = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (m.type !== 'attributes' || m.attributeName !== 'class') continue;
                    var t = m.target;
                    if (t && t.classList && t.classList.contains('choices')) {
                        sync();
                        return;
                    }
                }
            });
            mo.observe(modal, { subtree: true, attributes: true, attributeFilter: ['class'] });
            sync();
        });
    }
    // Disabled to prevent modal jump on dropdown open/close caused by overflow toggles.
    // initSellingVoucherModalChoicesOpenSync();

    /**
     * Item rows: Choices list is position:absolute inside nested overflow/table contexts.
     * Pin the panel to viewport with fixed + getBoundingClientRect so it is never clipped.
     */
    function bindSvItemChoicesFixedDropdown(selectEl, choices, api) {
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
            dd.classList.add('sv-item-choices-dropdown-fixed');
            dd.style.setProperty('position', 'fixed', 'important');
            dd.style.setProperty('left', Math.max(margin, Math.min(r.left, window.innerWidth - Math.max(r.width, 200) - margin)) + 'px', 'important');
            dd.style.setProperty('width', Math.max(r.width, 220) + 'px', 'important');
            dd.style.setProperty('max-height', Math.max(120, flipped ? spaceAbove : spaceBelow) + 'px', 'important');
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
            window.addEventListener('resize', onScrollOrResize, { passive: true });
            document.addEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.addEventListener('scroll', onScrollOrResize, { passive: true });
        }
        function onHide() {
            var dd = getDropdownEl();
            if (dd) {
                dd.classList.remove('sv-item-choices-dropdown-fixed');
                ['position', 'left', 'top', 'right', 'bottom', 'width', 'max-height', 'z-index'].forEach(function(p) {
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

    /**
     * Item dropdown: allow Tab (in addition to Enter) to select the currently highlighted option.
     * Reuses Choices' own Enter handler so selection behaviour is identical, then lets Tab move focus on.
     */
    function bindSvItemChoicesTabSelect(selectEl, choices, api) {
        if (!api || !api.wrapper) return;
        api.wrapper.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab' || e.shiftKey) return;
            var dropdown = choices.dropdown ? choices.dropdown.element : null;
            var isOpen = dropdown && dropdown.classList && dropdown.classList.contains('is-active');
            if (!isOpen) return;
            var highlighted = dropdown.querySelector('.choices__item--selectable.is-highlighted');
            if (!highlighted) return;
            // Trigger Choices' native selection of the highlighted option (same as pressing Enter).
            var enter = new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true });
            e.target.dispatchEvent(enter);
            // Default Tab behaviour proceeds, moving focus to the next field.
        });
    }

    /**
     * Selling voucher dropdowns: type-to-search using whole words only.
     * Each space-separated token must match a label word from the start (prefix while typing),
     * so e.g. "rice" matches "Basmati Rice" but not "price".
     */
    function svNormalizeSearchQuery(q) {
        return String(q || '').trim().replace(/\s{2,}/g, ' ');
    }

    function svLabelMatchesExactWordTokens(label, query) {
        var q = svNormalizeSearchQuery(query).toLowerCase();
        if (!q) return true;
        var labelStr = String(label || '');
        var words = labelStr.toLowerCase().match(/[\u0900-\u0FFF\w]+/g);
        if (!words || !words.length) {
            return labelStr.toLowerCase().indexOf(q) >= 0;
        }
        var tokens = q.split(/\s+/).filter(Boolean);
        var allMatch = tokens.every(function(tok) {
            return words.some(function(w) {
                return w === tok || w.indexOf(tok) === 0;
            });
        });
        if (allMatch) return true;
        // Fallback: substring match so short queries and labels without word boundaries still filter
        return labelStr.toLowerCase().indexOf(q) >= 0;
    }

    function patchChoicesSearcherExactWordTokens(choicesInstance) {
        try {
            var searcher = choicesInstance._searcher;
            var store = choicesInstance._store;
            if (!searcher || !store || searcher._svExactWordPatched) return;
            searcher._svExactWordPatched = true;
            var origSearch = searcher.search.bind(searcher);
            searcher.search = function(needle) {
                var nv = svNormalizeSearchQuery(needle);
                if (!nv.length) return origSearch(needle);
                var list = store.searchableChoices;
                if (!list || !list.length) return origSearch(needle);
                var out = [];
                for (var i = 0; i < list.length; i++) {
                    var item = list[i];
                    if (item.placeholder) continue;
                    var lab = item.label != null ? String(item.label) : '';
                    if (svLabelMatchesExactWordTokens(lab, nv)) {
                        out.push({ item: item, score: 0, rank: out.length + 1 });
                    }
                }
                return out;
            };
        } catch (e) {
            console.warn('patchChoicesSearcherExactWordTokens', e);
        }
    }

    function createChoicesInstance(selectEl, settings) {
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.choicesInstance && selectEl.tomselect && selectEl.tomselect._choices) {
            return selectEl.choicesInstance;
        }
        selectEl.choicesInstance = null;
        selectEl.tomselect = null;
        settings = settings || {};

        var choiceConfig = {
            allowHTML: false,
            itemSelectText: '',
            shouldSort: false,
            searchEnabled: settings.searchEnabled !== false,
            searchChoices: settings.searchChoices !== false,
            searchFloor: typeof settings.searchFloor === 'number' ? settings.searchFloor : 0,
            searchResultLimit: typeof settings.maxOptions === 'number' ? settings.maxOptions : -1,
            placeholder: true,
            placeholderValue: settings.placeholder || (selectEl.getAttribute('placeholder') || ''),
            searchPlaceholderValue: typeof settings.searchPlaceholderValue === 'string' ? settings.searchPlaceholderValue : ''
        };

        if (settings.removeItemButton === true) {
            choiceConfig.removeItemButton = true;
        }

        if (Array.isArray(settings.searchFields)) {
            choiceConfig.searchFields = settings.searchFields;
        }

        var choices = new window.Choices(selectEl, choiceConfig);
        if (settings.exactWordTokenSearch === true) {
            patchChoicesSearcherExactWordTokens(choices);
        }
        var api = {
            _choices: choices,
            selectEl: selectEl,
            settings: settings,
            activeOption: null,
            items: [],
            wrapper: choices.containerOuter ? choices.containerOuter.element : null,
            control_input: null,
            getValue: function() { return this.selectEl ? (this.selectEl.value || '') : ''; },
            setValue: function(v) {
                var value = (v === null || typeof v === 'undefined') ? '' : String(v);
                this._choices.removeActiveItems();
                if (value !== '') this._choices.setChoiceByValue(value);
                this.syncItems();
            },
            clear: function() {
                this._choices.removeActiveItems();
                this.syncItems();
            },
            addOption: function(opt) {
                if (!opt) return;
                var val = (opt.value === null || typeof opt.value === 'undefined') ? '' : String(opt.value);
                this._choices.setChoices([{ value: val, label: opt.text || val, selected: false, disabled: false }], 'value', 'label', false);
            },
            destroy: function() {
                try {
                    if (this._choices) this._choices.destroy();
                } catch (e) {
                    console.warn('Choices destroy failed', e);
                } finally {
                    if (this.selectEl) {
                        this.selectEl.choicesInstance = null;
                        this.selectEl.tomselect = null;
                    }
                    this._choices = null;
                }
            },
            setTextboxValue: function(v) {
                if (this.control_input) this.control_input.value = v || '';
            },
            onSearchChange: function() {},
            refreshOptions: function() {},
            syncItems: function() {
                var v = this.getValue();
                this.items = (v === '' || v === null || typeof v === 'undefined') ? [] : [String(v)];
            }
        };
        api.control_input = api.wrapper ? api.wrapper.querySelector('input.choices__input--cloned') : null;
        if (api.wrapper && api.wrapper.classList) api.wrapper.classList.add('ts-wrapper');
        if (choices.dropdown && choices.dropdown.element && choices.dropdown.element.classList) {
            choices.dropdown.element.classList.add('ts-dropdown');
        }
        api.syncItems();

        selectEl.addEventListener('change', function() { api.syncItems(); });
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

        if (selectEl.classList.contains('sv-item-select')) {
            bindSvItemChoicesFixedDropdown(selectEl, choices, api);
            bindSvItemChoicesTabSelect(selectEl, choices, api);
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
            exactWordTokenSearch: true,
            searchFields: ['label'],
            searchPlaceholderValue: 'Type to search...',
            onInitialize: function () {
                this.activeOption = null;
            },
            onDropdownOpen: function (dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = { scrollTop: modalBody.scrollTop };
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
                        try { input.setSelectionRange(0, 0); } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState.scrollTop === 'number') {
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
                    setTimeout(function () {
                        var opts = dropdown.querySelectorAll('.option.active, .option.selected, .option[aria-selected="true"], .choices__item--selectable[aria-selected="true"]');
                        opts.forEach(function (opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            },
            onDropdownClose: function () {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                if (helper && modalEl) {
                    helper.onClose(modalEl, self._modalDropdownState);
                } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState.scrollTop === 'number') {
                    modalBody.scrollTop = self._modalDropdownState.scrollTop;
                }
                self._modalDropdownState = null;
            }
        }, extra || {});
    }

    function createItemSelectConfig() {
        return createBlankSearchConfig({
            placeholder: 'Item',
            maxOptions: null,
            clearOnOpen: false
        });
    }

    function createEditModalItemSelectConfig() {
        // Same as Add item select: clear search box on open, NEVER clear the selected item.
        // (Previously self.clear() on open wiped Item Name + Available/Left Qty in Edit.)
        return createItemSelectConfig();
    }

    // Cache original Client Name options so we can rebuild the select per Client Type.
    var clientNameOptionsAdd = [];
    var clientNameOptionsEdit = [];
    function cacheClientNameOptions() {
        clientNameOptionsAdd = [];
        clientNameOptionsEdit = [];
        var addSel = document.getElementById('modalClientNameSelect');
        if (addSel) {
            addSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsAdd.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
        var editSel = document.getElementById('editClientNameSelect');
        if (editSel) {
            editSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsEdit.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
    }
    cacheClientNameOptions();

    var addModalTomSelectInstances = { payment: null, client: null, store: null };
    var editModalTomSelectInstances = { payment: null, client: null, store: null };

    function rebuildClientNameSelect(selectEl, optionsList, slug) {
        if (!selectEl || !Array.isArray(optionsList)) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = optionsList.filter(function(o) { return (o.type || '').toLowerCase().trim() === slugLower; });

        // Preserve a valid selection if possible; otherwise clear.
        var preserved = '';
        if (selectEl.tomselect) preserved = selectEl.tomselect.getValue() || '';
        else preserved = selectEl.value || '';

        if (selectEl.tomselect) { try { selectEl.tomselect.destroy(); } catch (e) {} }
        if (selectEl.id === 'modalClientNameSelect') addModalTomSelectInstances.client = null;
        if (selectEl.id === 'editClientNameSelect') editModalTomSelectInstances.client = null;

        selectEl.innerHTML = '<option value="">Select Client</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', (o.type || '').toLowerCase().trim());
            opt.setAttribute('data-client-name', (o.clientName || '').toLowerCase().trim());
            selectEl.appendChild(opt);
        });

        var inst = null;
        if (typeof Choices !== 'undefined') {
            inst = createChoicesInstance(selectEl, createBlankSearchConfig({
                placeholder: 'Select Client',
                clearOnOpen: true
            }));
            if (selectEl.id === 'modalClientNameSelect') addModalTomSelectInstances.client = inst;
            if (selectEl.id === 'editClientNameSelect') editModalTomSelectInstances.client = inst;
        }

        // Restore preserved selection if it still exists.
        if (preserved) {
            var stillExists = Array.from(selectEl.options).some(function(o) { return String(o.value) === String(preserved); });
            if (stillExists) {
                if (selectEl.tomselect) selectEl.tomselect.setValue(preserved, true);
                else selectEl.value = preserved;
            }
        }
    }

    function setSelectValue(selectEl, value) {
        if (!selectEl) return;
        var v = (value === null || value === undefined) ? '' : String(value);
        if (selectEl.tomselect) selectEl.tomselect.setValue(v);
        else selectEl.value = v;
    }

    /** After Choices.js init on Edit Selling Voucher modal, push API values into instances (store/payment/client/course/name). */
    function syncEditSellingVoucherChoicesFromVoucher(v, editSlug) {
        editSlug = String(editSlug || 'employee').toLowerCase();
        var paySel = document.querySelector('#editSellingVoucherModal select.edit-payment-type');
        if (paySel && paySel.tomselect) {
            try { paySel.tomselect.setValue(String(v.payment_type ?? 1)); } catch (e) {}
        }
        var stSel = document.querySelector('#editSellingVoucherModal select.edit-store');
        var sid = v.store_id || v.inve_store_master_pk || '';
        if (stSel && stSel.tomselect && sid !== '') {
            try { stSel.tomselect.setValue(String(sid)); } catch (e) {}
        }
        var ecs = document.getElementById('editClientNameSelect');
        if (ecs && ecs.tomselect && editSlug !== 'ot' && editSlug !== 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { ecs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var eot = document.getElementById('editModalOtCourseSelect');
        if (eot && eot.tomselect && editSlug === 'ot' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { eot.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var crs = document.getElementById('editModalCourseSelect');
        if (crs && crs.tomselect && editSlug === 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { crs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var cn = String(v.client_name || '').trim();
        if (cn) {
            ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el || !el.tomselect) return;
                try { el.tomselect.setValue(cn); } catch (e) {}
            });
        }
        // Keep hidden client_id in sync (API value, or data-pk from selected Name option)
        var clientIdEl = document.getElementById('editModalClientId');
        if (clientIdEl) {
            if (v.client_id != null && String(v.client_id) !== '') {
                clientIdEl.value = String(v.client_id);
            } else if (editSlug === 'employee' || editSlug === 'ot') {
                var resolvedPk = '';
                ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect'].forEach(function(id) {
                    if (resolvedPk) return;
                    var el = document.getElementById(id);
                    if (!el || el.style.display === 'none') return;
                    var opt = el.options[el.selectedIndex];
                    if (opt && opt.dataset && opt.dataset.pk) resolvedPk = String(opt.dataset.pk);
                });
                if (resolvedPk) clientIdEl.value = resolvedPk;
            }
        }
    }

    /** Ensure edit form posts client_id for employee/OT (from hidden field or selected Name option). */
    function syncEditModalClientIdBeforeSubmit() {
        var slugRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var slug = slugRadio ? String(slugRadio.value || '').toLowerCase() : '';
        if (slug !== 'employee' && slug !== 'ot') return;
        var clientIdEl = document.getElementById('editModalClientId');
        if (!clientIdEl) return;
        if (clientIdEl.value && String(clientIdEl.value).trim() !== '') return;
        ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect'].forEach(function(id) {
            if (clientIdEl.value) return;
            var el = document.getElementById(id);
            if (!el || el.style.display === 'none') return;
            var opt = el.options[el.selectedIndex];
            if (opt && opt.dataset && opt.dataset.pk) {
                clientIdEl.value = String(opt.dataset.pk);
            }
        });
    }

    // When user clicks any Cancel/Close button in a modal (secondary button),
    // close the modal and refresh the page to reset all filters/state (only for Add/Edit Selling Voucher modals).
    document.querySelectorAll('#addSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"], #editSellingVoucherModal button.btn-secondary[data-bs-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.location.reload();
        });
    });

    /** Keep Choices.js multi-select UI in sync with server-rendered selected options after filter submit. */
    function syncFilterChoicesMultiFromNative(selectEl) {
        if (!selectEl || !selectEl.choicesInstance || !selectEl.choicesInstance._choices) return;
        var choicesApi = selectEl.choicesInstance._choices;
        Array.from(selectEl.options).forEach(function(opt) {
            if (opt.selected && String(opt.value) !== '') {
                try {
                    choicesApi.setChoiceByValue(String(opt.value));
                } catch (e) {}
            }
        });
    }

    // Filter dropdowns (Choices.js): same exact word-token search as selling voucher modals
    // Every filter dropdown is a searchable single-select. The block that used to
    // live here was a MULTI-select config (removeItemButton and friends) that no
    // longer suited the auto-apply toolbar, so it had been switched off with
    // `var filterStatus = null` — leaving the filters with no search at all.
    if (typeof Choices !== 'undefined') {
        function svFilterChoicesConfig(selectEl) {
            var placeholderOption = selectEl.options && selectEl.options[0];
            var label = (placeholderOption ? placeholderOption.text : '') || 'options';

            return {
                allowEmptyOption: true,
                placeholder: label,
                searchEnabled: true,
                searchFields: ['label'],
                exactWordTokenSearch: true,
                searchPlaceholderValue: 'Search ' + label.toLowerCase() + '...',
                highlight: false
            };
        }

        function svInitFilterChoices(selectEl) {
            if (!selectEl) return null;
            if (selectEl.choicesInstance) return selectEl.choicesInstance;

            var inst = createChoicesInstance(selectEl, svFilterChoicesConfig(selectEl));

            // Choices moves the <select> INSIDE its wrapper, so `select + .choices`
            // never matches. Tag the wrapper instead so the skin can reach it.
            var wrapper = (inst && inst.wrapper) || selectEl.closest('.choices');
            if (wrapper && wrapper.classList) wrapper.classList.add('sv-filter-choices');

            return inst;
        }

        Array.prototype.forEach.call(
            document.querySelectorAll('.sv-filter-select-native'),
            function (selectEl) { svInitFilterChoices(selectEl); }
        );

        // A Choices-managed <select> ignores innerHTML, so the dependent filters
        // (Client Category, Buyer Name) have to be rebuilt through here.
        window.SvFilterChoices = {
            isManaged: function (selectEl) {
                return !!(selectEl && selectEl.choicesInstance);
            },
            setOptions: function (selectEl, options, placeholder) {
                if (!selectEl) return;
                var html = '<option value="">' + placeholder + '</option>';
                (options || []).forEach(function (opt) {
                    html += '<option value="' + String(opt.value).replace(/"/g, '&quot;') + '">'
                        + String(opt.text).replace(/</g, '&lt;') + '</option>';
                });

                // Destroy, swap the native options, re-init. Patching in place
                // (setChoices) leaves stale entries behind when the list shrinks.
                var inst = selectEl.choicesInstance;
                if (inst && typeof inst.destroy === 'function') {
                    try { inst.destroy(); } catch (e) {}
                }
                selectEl.innerHTML = html;
                svInitFilterChoices(selectEl);
            },
            clear: function (selectEl) {
                if (!selectEl) return;
                selectEl.value = '';
                var inst = selectEl.choicesInstance;
                if (inst && typeof inst.setValue === 'function') {
                    try { inst.setValue(''); } catch (e) {}
                }
            }
        };
    } else {
        console.warn('Choices.js library not loaded on Selling Voucher page');
    }

    (function initSellingVoucherFilterCascade() {
        var typeEl = document.getElementById('filter_client_type');
        var typePkEl = document.getElementById('filter_client_type_pk');
        var buyerEl = document.getElementById('filter_buyer_name');
        if (!typeEl || !typePkEl || !buyerEl) return;
        // Auto-submit + server-side rendering now drive the Client Type → Category → Buyer
        // cascade (changing a parent reloads with the child options), so the legacy
        // client-side cascade is disabled to avoid double-handling.
        return;

        var selectedClientType = @json((string) ($selectedClientType ?? ''));
        var selectedTypePk = @json((string) ($selectedClientTypePk ?? ''));
        var selectedBuyer = @json((string) ($selectedBuyerName ?? ''));
        var isRestoringSellingVoucherFilters = false;
        var employees = @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE);
        var faculties = @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE);
        var messStaff = @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE);

        var typeSlugMap = {
            '{{ (string) \App\Models\KitchenIssueMaster::CLIENT_EMPLOYEE }}': 'employee',
            '{{ (string) \App\Models\KitchenIssueMaster::CLIENT_OT }}': 'ot',
            '{{ (string) \App\Models\KitchenIssueMaster::CLIENT_COURSE }}': 'course',
            '{{ (string) \App\Models\KitchenIssueMaster::CLIENT_SECTION }}': 'section',
            '{{ (string) \App\Models\KitchenIssueMaster::CLIENT_OTHER }}': 'other'
        };
        var typePkOptionsBySlug = {
@foreach(($clientNamesByType ?? collect()) as $slug => $options)
            '{{ $slug }}': [
    @foreach($options as $option)
                { value: '{{ (string) $option->id }}', text: '{{ addslashes((string) $option->client_name) }}' },
    @endforeach
            ],
@endforeach
        };
        var otCourseOptions = [
@foreach(($otCourses ?? collect()) as $course)
            { value: '{{ (string) $course->pk }}', text: '{{ addslashes((string) $course->course_name) }}' },
@endforeach
        ];

        function fillSelect(selectEl, options, placeholder, selectedValue) {
            selectEl.innerHTML = '';
            var defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = placeholder;
            selectEl.appendChild(defaultOpt);
            (options || []).forEach(function (option) {
                var opt = document.createElement('option');
                opt.value = String(option.value || '');
                opt.textContent = String(option.text || '');
                if (selectedValue !== undefined && selectedValue !== null && String(selectedValue) === opt.value) {
                    opt.selected = true;
                }
                selectEl.appendChild(opt);
            });
        }

        function setBuyerOptions(options, preserveSelection) {
            fillSelect(buyerEl, (options || []).map(function (option) {
                if (typeof option === 'string') {
                    return { value: option, text: option };
                }
                return {
                    value: String((option && option.value) || ''),
                    text: String((option && option.text) || ''),
                };
            }).filter(function (option) {
                return option.value !== '' && option.text !== '';
            }), 'All Buyers', preserveSelection ? selectedBuyer : '');
        }

        function loadBuyerOptions(preserveSelection) {
            var slug = typeSlugMap[String(typeEl.value || '')] || '';
            var pk = String(typePkEl.value || '');
            if (!slug || !pk) {
                setBuyerOptions([], preserveSelection);
                return;
            }

            if (slug === 'employee') {
                var selectedLabel = ((typePkEl.options[typePkEl.selectedIndex] || {}).text || '').toLowerCase().trim();
                if (selectedLabel === 'academy staff') {
                    setBuyerOptions(employees, preserveSelection);
                } else if (selectedLabel === 'faculty') {
                    setBuyerOptions(faculties, preserveSelection);
                } else if (selectedLabel === 'mess staff') {
                    setBuyerOptions(messStaff, preserveSelection);
                } else {
                    setBuyerOptions([], preserveSelection);
                }
                return;
            }

            var url = '';
            if (slug === 'ot') {
                url = '{{ route('admin.mess.material-management.students-by-course', ['course_pk' => '__COURSE__']) }}'.replace('__COURSE__', encodeURIComponent(pk));
            } else if (slug === 'course' || slug === 'section' || slug === 'other') {
                var params = new URLSearchParams({
                    client_type_slug: slug,
                    client_type_pk: pk
                });
                url = '{{ route('admin.mess.material-management.buyer-names') }}' + '?' + params.toString();
            }
            if (!url) {
                setBuyerOptions([], preserveSelection);
                return;
            }

            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.ok ? response.json() : { buyers: [] }; })
                .then(function (payload) {
                    var buyers = [];
                    if (slug === 'ot') {
                        buyers = (payload.students || []).map(function (student) { return student.display_name || ''; }).filter(Boolean);
                    } else {
                        buyers = (payload.buyers || []).map(function (name) { return String(name || '').trim(); }).filter(Boolean);
                    }
                    setBuyerOptions(buyers, preserveSelection);
                })
                .catch(function () {
                    setBuyerOptions([], preserveSelection);
                });
        }

        function loadTypePkOptions(preserveSelection) {
            var slug = typeSlugMap[String(typeEl.value || '')] || '';
            var options = [];
            if (slug === 'ot' || slug === 'course') {
                options = otCourseOptions;
            } else if (slug && typePkOptionsBySlug[slug]) {
                options = typePkOptionsBySlug[slug];
            }
            fillSelect(typePkEl, options, 'All categories', preserveSelection ? selectedTypePk : '');
            loadBuyerOptions(preserveSelection);
        }

        typeEl.addEventListener('change', function () {
            if (isRestoringSellingVoucherFilters) return;
            selectedTypePk = '';
            selectedBuyer = '';
            loadTypePkOptions(false);
        });
        typePkEl.addEventListener('change', function () {
            if (isRestoringSellingVoucherFilters) return;
            selectedBuyer = '';
            loadBuyerOptions(false);
        });

        function restoreSellingVoucherFilterCascade() {
            if (!selectedClientType) return;
            isRestoringSellingVoucherFilters = true;
            typeEl.value = selectedClientType;
            loadTypePkOptions(true);
            isRestoringSellingVoucherFilters = false;
        }

        restoreSellingVoucherFilterCascade();
    })();

    function destroyAddModalTomSelects() {
        // Destroy tracked instances (payment, client, store, item selects only)
        if (addModalTomSelectInstances.payment) {
            try { addModalTomSelectInstances.payment.destroy(); } catch (e) {}
            addModalTomSelectInstances.payment = null;
        }
        if (addModalTomSelectInstances.client) {
            try { addModalTomSelectInstances.client.destroy(); } catch (e) {}
            addModalTomSelectInstances.client = null;
        }
        if (addModalTomSelectInstances.store) {
            try { addModalTomSelectInstances.store.destroy(); } catch (e) {}
            addModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#addSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
            el.tomselect = null;
            el.choicesInstance = null;
        });
    }

    function destroyEditModalTomSelects() {
        // Destroy tracked instances for Edit modal
        if (editModalTomSelectInstances.payment) {
            try { editModalTomSelectInstances.payment.destroy(); } catch (e) {}
            editModalTomSelectInstances.payment = null;
        }
        if (editModalTomSelectInstances.client) {
            try { editModalTomSelectInstances.client.destroy(); } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        if (editModalTomSelectInstances.store) {
            try { editModalTomSelectInstances.store.destroy(); } catch (e) {}
            editModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#editSellingVoucherModal select').forEach(function(el) {
            if (el.tomselect) {
                try { el.tomselect.destroy(); } catch (e) {}
            }
        });
    }

    // Show/hide select (or its Choices wrapper) so only one Name dropdown is visible at a time
    function setSelectVisible(select, visible) {
        if (!select) return;
        var wrapper = null;
        if (select.tomselect && select.tomselect.wrapper) wrapper = select.tomselect.wrapper;
        if (!wrapper && select.parentElement) {
            var p = select.parentElement;
            if (p.classList && p.classList.contains('ts-wrapper')) wrapper = p;
            else if (p.parentElement && p.parentElement.classList && p.parentElement.classList.contains('ts-wrapper')) wrapper = p.parentElement;
        }
        if (wrapper) wrapper.style.display = visible ? '' : 'none';
        else select.style.display = visible ? 'block' : 'none';
    }

    function initAddModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var modal = document.getElementById('addSellingVoucherModal');
        if (!modal) return;

        var paymentSel = modal.querySelector('select[name="payment_type"]');
        if (paymentSel && !paymentSel.tomselect) {
            addModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('modalClientNameSelect');
        var addRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        var addSlug = addRadio ? (addRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && addSlug !== 'ot' && addSlug !== 'course' && clientNameOptionsAdd.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsAdd, addSlug);
        } else if (clientSel && !clientSel.tomselect) {
            addModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client',
                clearOnOpen: true
            }));
        }
        var storeSel = modal.querySelector('select[name="store_id"]');
        if (storeSel && !storeSel.tomselect) {
            addModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        var nameSelectIds = ['modalFacultySelect', 'modalAcademyStaffSelect', 'modalMessStaffSelect', 'modalOtStudentSelect', 'modalOtCourseSelect', 'modalCourseSelect', 'modalCourseNameSelect'];
        nameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                createChoicesInstance(sel, createBlankSearchConfig({
                    placeholder: sel.id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                        : sel.id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                        : sel.id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                        : sel.id.indexOf('OtStudent') !== -1 ? 'Select Student'
                        : 'Select Course',
                    clearOnOpen: true
                }));
            }
        });
        modal.querySelectorAll('#modalItemsBody .sv-item-select').forEach(function(select) {
            if (select.tomselect) return;
            var hadValue = !!select.value;
            var ts = createChoicesInstance(select, createItemSelectConfig());
            if (!hadValue && ts) ts.clear(true);
        });
        var clientNameWrap = document.getElementById('modalClientNameWrap');
        var nameFieldWrap = document.getElementById('modalNameFieldWrap');
        var clientTypeChecked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        if (clientNameWrap && nameFieldWrap) {
            if (clientTypeChecked) {
                clientNameWrap.style.display = '';
                nameFieldWrap.style.display = '';
            } else {
                clientNameWrap.style.display = 'none';
                nameFieldWrap.style.display = 'none';
            }
        }
        if (typeof updateModalNameField === 'function') {
            updateModalNameField();
        }
    }

    function initEditModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var modal = document.getElementById('editSellingVoucherModal');
        if (!modal) return;

        var paymentSel = modal.querySelector('select.edit-payment-type');
        if (paymentSel && !paymentSel.tomselect) {
            editModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }

        var clientSel = document.getElementById('editClientNameSelect');
        var editRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var editSlug = editRadio ? (editRadio.value || '').toLowerCase().trim() : 'employee';
        if (clientSel && editSlug !== 'ot' && editSlug !== 'course' && clientNameOptionsEdit.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsEdit, editSlug);
        } else if (clientSel && !clientSel.tomselect) {
            editModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client',
                clearOnOpen: true
            }));
        }

        var storeSel = modal.querySelector('select.edit-store');
        if (storeSel && !storeSel.tomselect) {
            editModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }

        var editNameSelectIds = ['editModalFacultySelect', 'editModalAcademyStaffSelect', 'editModalMessStaffSelect', 'editModalOtCourseSelect', 'editModalCourseSelect', 'editModalCourseNameSelect'];
        editNameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (sel && !sel.tomselect) {
                var placeholder = id.indexOf('Faculty') !== -1 ? 'Select Faculty'
                    : id.indexOf('Academy') !== -1 ? 'Select Academy Staff'
                    : id.indexOf('Mess') !== -1 ? 'Select Mess Staff'
                    : 'Select Course';
                createChoicesInstance(sel, createBlankSearchConfig({ placeholder: placeholder, clearOnOpen: true }));
            }
        });
    }

    // After Choices init in Edit modal: show only the active dropdown in Client Name column (hide OT Course / Course when Client Name is active, and vice versa)
    function applyEditModalClientNameColumnVisibility() {
        var radio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        var clientSelect = document.getElementById('editClientNameSelect');
        var otCourseSelect = document.getElementById('editModalOtCourseSelect');
        var editCourseSelect = document.getElementById('editModalCourseSelect');
        if (!radio || !clientSelect) return;
        var isOt = (radio.value || '').toLowerCase() === 'ot';
        var isCourse = (radio.value || '').toLowerCase() === 'course';
        if (isOt) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        } else if (isCourse) {
            setSelectVisible(clientSelect, false);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, true);
        } else {
            setSelectVisible(clientSelect, true);
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (editCourseSelect) setSelectVisible(editCourseSelect, false);
        }
    }

    // Filter: End Date must not be before Start Date
    var filterStart = document.getElementById('filter_start_date');
    var filterEnd = document.getElementById('filter_end_date');
    if (filterStart && filterEnd) {
        filterStart.addEventListener('change', function() {
            filterEnd.min = this.value || '';
            if (filterEnd.value && this.value && filterEnd.value < this.value) {
                filterEnd.value = this.value;
            }
        });
    }

    /** Client Type: force Employee and fix defaultChecked so form.reset() cannot restore OT/Course from initial page HTML. */
    function resetAddModalClientTypeToEmployee(modalEl) {
        if (!modalEl) return;
        var empRadio = null;
        modalEl.querySelectorAll('.client-type-radio').forEach(function(r) {
            var isEmp = String(r.value || '').toLowerCase() === 'employee';
            r.checked = isEmp;
            r.defaultChecked = isEmp;
            if (isEmp) empRadio = r;
        });
        if (empRadio) {
            empRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /** Transfer From Store: clear selection and set only the empty option as defaultSelected (avoids form.reset() restoring old('store_id')). */
    function resetAddModalStoreSelectToEmpty(modalEl) {
        var storeSel = modalEl && modalEl.querySelector('select[name="store_id"]');
        if (!storeSel) return;
        storeSel.querySelectorAll('option').forEach(function(opt) {
            opt.defaultSelected = String(opt.value) === '';
        });
        storeSel.value = '';
    }

    /** Rebuild Choices + item rows from current `filteredItems` (call after reset and/or fetchStoreItems). */
    function reinitAddSellingVoucherModalItemGrid() {
        initAddModalTomSelects();
        if (typeof updateModalNameField === 'function') updateModalNameField();
        updateAddItemDropdowns();
        refreshAllAvailable();
        document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
        updateGrandTotal();
        syncAddModalChoicesToNative();
    }

    // Helper: reset Add Selling Voucher modal form (without closing modal).
    // Keeps modal open; clears fields, item rows, and store-scoped item cache so the next entry starts fresh.
    // @param {boolean} skipDeferredReinit — if true, caller will refetch inventory and call reinitAddSellingVoucherModalItemGrid (e.g. after AJAX save).
    function resetSellingVoucherModalForm(skipDeferredReinit) {
        var modalEl = document.getElementById('addSellingVoucherModal');
        if (!modalEl) return;

        currentStoreId = null;
        filteredItems = itemSubcategories;

        destroyAddModalTomSelects();

        var form = document.getElementById('sellingVoucherModalForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        }
        modalEl.querySelectorAll('.modal-body .alert.alert-danger').forEach(function(a) { a.remove(); });
        resetAddModalStoreSelectToEmpty(modalEl);
        var issueDateInp = modalEl.querySelector('input[name="issue_date"]');
        if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
        var paymentSel = modalEl.querySelector('select[name="payment_type"]');
        if (paymentSel) paymentSel.value = '1';
        // Clear client / name UI on native selects BEFORE firing client-type change (rebuildClientNameSelect preserves current value).
        var clientPkSel = modalEl.querySelector('#modalClientNameSelect');
        if (clientPkSel) clientPkSel.value = '';
        var clientNameInp = document.getElementById('modalClientNameInput');
        if (clientNameInp) clientNameInp.value = '';
        modalEl.querySelectorAll('#modalClientNameWrap select, #modalNameFieldWrap select').forEach(function(s) {
            if (s && typeof s.value !== 'undefined') s.value = '';
        });
        resetAddModalClientTypeToEmployee(modalEl);
        var billInput = document.getElementById('addSvBillFileInput');
        if (billInput) billInput.value = '';
        var billWrap = document.getElementById('addSvBillFileChosenWrap');
        var billName = document.getElementById('addSvBillFileChosenName');
        if (billWrap) billWrap.classList.add('d-none');
        if (billName) billName.textContent = '';
        var tbody = document.getElementById('modalItemsBody');
        if (tbody) {
            tbody.innerHTML = getRowHtml(0);
            rowIndex = 1;
            updateRemoveButtons();
        }
        var grandTotalEl = document.getElementById('modalGrandTotal');
        if (grandTotalEl) grandTotalEl.textContent = '0.00';

        if (skipDeferredReinit) {
            return;
        }

        // Modal stays open after AJAX save; re-init dropdowns and item grid (defer so DOM + destroy settle).
        window.requestAnimationFrame(function () {
            window.setTimeout(function () {
                try {
                    reinitAddSellingVoucherModalItemGrid();
                } catch (err) {
                    console.error('resetSellingVoucherModalForm re-init failed', err);
                }
            }, 10);
        });
    }

    /** Force Choices.js UI to match underlying <select> values (fixes stale labels after reset). */
    function syncAddModalChoicesToNative() {
        var modal = document.getElementById('addSellingVoucherModal');
        if (!modal) return;
        modal.querySelectorAll('select').forEach(function(sel) {
            if (!sel.tomselect || typeof sel.tomselect.clear !== 'function') return;
            try {
                var v = sel.value;
                if (v === null || v === undefined || v === '') {
                    sel.tomselect.clear();
                } else {
                    sel.tomselect.setValue(String(v));
                }
            } catch (e) {}
        });
    }

    // After AJAX save (add/edit), refresh the listing DataTable so new rows show immediately.
    // This fetches the current page HTML and swaps DataTable rows (preserves search/paging).
    var isRefreshingSellingVouchersTable = false;
    function refreshSellingVouchersTable() {
        if (isRefreshingSellingVouchersTable) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;

        var $ = window.jQuery;
        var $table = $('#sellingVouchersTable');
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) return;

        var dt = $table.DataTable();
        var canAjaxReload = dt.ajax && typeof dt.ajax.reload === 'function';

        isRefreshingSellingVouchersTable = true;

        if (canAjaxReload) {
            try {
                dt.ajax.reload(function() {
                    isRefreshingSellingVouchersTable = false;
                }, false);
            } catch (err) {
                console.error('Failed to refresh selling vouchers table', err);
                isRefreshingSellingVouchersTable = false;
            }
            return;
        }

        var expectedCols = $table.find('thead tr:first th').length;
        var url = window.location.pathname + window.location.search;

        fetch(url, { headers: { 'Accept': 'text/html' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newTbody = doc.querySelector('#sellingVouchersTable tbody');
                if (!newTbody) return;

                var newRowData = [];
                newTbody.querySelectorAll('tr').forEach(function(tr) {
                    var cells = Array.from(tr.querySelectorAll('td,th'));
                    if (expectedCols && cells.length !== expectedCols) return;
                    newRowData.push(cells.map(function(td) { return td.innerHTML; }));
                });

                dt.clear();
                if (newRowData.length) dt.rows.add(newRowData);
                dt.draw(false);
            })
            .catch(function(err) {
                console.error('Failed to refresh selling vouchers table', err);
            })
            .finally(function() {
                isRefreshingSellingVouchersTable = false;
            });
    }

    // Prevent double submit on Add Selling Voucher form (stops double entry) + AJAX submit
    var sellingVoucherModalForm = document.getElementById('sellingVoucherModalForm');
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function(e) {
            // If invalid, the capture validation listener will have prevented default.
            if (!this.checkValidity()) return;

            e.preventDefault();

            var form = this;
            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) return;
            if (btn) {
                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.textContent || '';
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            var action = form.getAttribute('action') || window.location.href;
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var formData = new FormData(form);
            var csrf = form.querySelector('input[name="_token"]');

            fetch(action, {
                method: method,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.value : '',
                    Accept: 'application/json, text/javascript, */*;q=0.01'
                },
                body: formData
            })
                .then(function(response) {
                    return response.text().then(function(text) {
                        var data = null;
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = null;
                            }
                        }
                        return {
                            ok: response.ok,
                            status: response.status,
                            data: data,
                            raw: text
                        };
                    });
                })
                .then(function(res) {
                    var data = res.data;
                    var success = !!(data && (data.success === true || data.success === 1 || data.success === '1' || data.voucher_id != null));
                    if (res.ok && success) {
                        var modalRoot = document.getElementById('addSellingVoucherModal');
                        var storeSel = modalRoot ? modalRoot.querySelector('select[name="store_id"]') : null;
                        var savedStoreId = '';
                        if (storeSel) {
                            if (storeSel.tomselect && typeof storeSel.tomselect.getValue === 'function') {
                                var gv = storeSel.tomselect.getValue();
                                savedStoreId = Array.isArray(gv) ? (gv[0] || '') : (gv == null ? '' : String(gv));
                            } else {
                                savedStoreId = storeSel.value || '';
                            }
                        }

                        resetSellingVoucherModalForm(true);

                        function finishAddModalAfterSave() {
                            try {
                                reinitAddSellingVoucherModalItemGrid();
                            } catch (err) {
                                console.error('reinit after save failed', err);
                            }
                            var body = modalRoot && modalRoot.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }

                        if (savedStoreId) {
                            if (storeSel) {
                                storeSel.value = String(savedStoreId);
                            }
                            currentStoreId = String(savedStoreId);
                            fetchStoreItems(String(savedStoreId), function() {
                                finishAddModalAfterSave();
                            });
                        } else {
                            currentStoreId = null;
                            filteredItems = itemSubcategories;
                            window.requestAnimationFrame(function() {
                                window.setTimeout(finishAddModalAfterSave, 10);
                            });
                        }

                        refreshSellingVouchersTable();
                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message : 'Failed to save voucher. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        if (!data && res.raw && res.raw.indexOf('<!DOCTYPE') !== -1) {
                            msg = 'Server returned a page instead of JSON. Try refreshing the page or check your session.';
                        }
                        alert(msg);
                    }
                })
                .catch(function() {
                    alert('Failed to save voucher. Please try again.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText || 'Save Selling Voucher';
                    }
                });
        });
    }

    // Prevent double submit on Edit Selling Voucher form
    var editSellingVoucherForm = document.getElementById('editSellingVoucherForm');
    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }
    
    let itemSubcategories = @json($itemSubcategories);
    let filteredItems = itemSubcategories;
    const editSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    const viewSvBaseUrl = "{{ url('admin/mess/material-management') }}";

    // View modal rows are built with insertAdjacentHTML from DB values — escape them.
    function escapeSvHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
    const returnSvBaseUrl = "{{ url('admin/mess/material-management') }}";
    let rowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;

    function enforceQtyWithinAvailable(row) {
        if (!row) return;
        const availEl = row.querySelector('.sv-avail');
        const qtyEl = row.querySelector('.sv-qty');
        if (!availEl || !qtyEl) return;

        let avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        // In edit modal: effective available = current stock + this row's original issue qty
        // (so saving without changes does not fail when current stock already reflects the voucher)
        const isEditRow = row.closest('#editModalItemsBody') !== null;
        const originalQty = isEditRow ? (parseFloat(row.getAttribute('data-original-qty')) || 0) : 0;
        const effectiveAvail = isEditRow ? (avail + originalQty) : avail;

        // Keep browser constraint in sync
        qtyEl.max = String(effectiveAvail);

        // If empty, don't force an error yet
        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > effectiveAvail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
    }

    function getBaseAvailableForItem(itemId) {
        if (!itemId) return 0;
        const item = filteredItems.find(function(i) { return String(i.id) === String(itemId); });
        if (item) return parseFloat(item.available_quantity) || 0;
        const master = (itemSubcategories || []).find(function(i) { return String(i.id) === String(itemId); });
        if (master) return parseFloat(master.available_quantity) || 0;
        const opt = document.querySelector('#editModalItemsBody .sv-item-select option[value="' + String(itemId).replace(/"/g, '\\"') + '"]');
        if (opt && opt.dataset && opt.dataset.available != null && opt.dataset.available !== '') {
            return parseFloat(opt.dataset.available) || 0;
        }
        return 0;
    }

    function refreshAllAvailable() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        const usedByItem = {};

        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const base = getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, base - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function fetchStoreItems(storeId, callback) {
        if (!storeId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        
        fetch(editSvBaseUrl + '/store/' + storeId + '/items', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            filteredItems = data;
            if (callback) callback();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load store items.');
            filteredItems = itemSubcategories || [];
            if (callback) callback();
        });
    }

    function updateAddItemDropdowns() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        console.log('Updating dropdowns, found rows:', rows.length); // Debug log
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;
            if (select.tomselect) {
                select.tomselect.destroy();
            }
            const currentValue = select.value;
            select.innerHTML = '<option value="">Item</option>';
            filteredItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            if (typeof Choices !== 'undefined') {
                createChoicesInstance(select, createItemSelectConfig());
            }
            updateUnit(row);
        });
    }

    // Add and Edit share one action cell: remove on every row, add on the last
    // (CSS hides the + on every row but the last).
    function svActionCell(extraRemoveClass) {
        return '<td class="sv-act-cell">' +
            '<button type="button" class="sv-icon-btn sv-icon-btn--remove sv-remove-row' +
            (extraRemoveClass || '') + '" title="Remove line" aria-label="Remove line">&minus;</button>' +
            '<button type="button" class="sv-icon-btn sv-icon-btn--add sv-add-row" title="Add line" aria-label="Add line">+</button>' +
            '</td>';
    }

    // Add and Edit share one action cell: remove on every row, add on the last
    // (CSS hides the + on every row but the last).
    function svActionCell(extraRemoveClass) {
        return '<td class="sv-act-cell">' +
            '<button type="button" class="sv-icon-btn sv-icon-btn--remove sv-remove-row' +
            (extraRemoveClass || '') + '" title="Remove line" aria-label="Remove line">&minus;</button>' +
            '<button type="button" class="sv-icon-btn sv-icon-btn--add sv-add-row" title="Add line" aria-label="Add line">+</button>' +
            '</td>';
    }

    function getRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        return '<tr class="sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select sv-item-select" required><option value="">Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control sv-unit" readonly placeholder="-"></td>' +
            '<td><input type="text" name="items[' + index + '][available_quantity]" class="form-control sv-avail bg-light" readonly placeholder="-"></td>' +
            '<td><input type="text" name="items[' + index + '][quantity]" class="form-control sv-qty" step="0.01" min="0.01" placeholder="-" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control sv-left bg-light" readonly placeholder="-"></td>' +
            '<td><input type="text" name="items[' + index + '][rate]" class="form-control sv-rate" step="0.01" min="0" placeholder="-" required></td>' +
            '<td><input type="text" class="form-control sv-total bg-light" readonly placeholder="-"></td>' +
            svActionCell() +
            '</tr>';
    }

    function updateUnit(row) {
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.sv-unit');
        const rateInp = row.querySelector('.sv-rate');
        const availInp = row.querySelector('.sv-avail');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
        // Only auto-set rate if user has not manually overridden it
        if (rateInp && rateInp.dataset.manualRate !== '1' && opt && opt.dataset.rate) {
            rateInp.value = opt.dataset.rate;
        }
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        if (row.closest('#editModalItemsBody')) {
            refreshEditAllAvailable();
        } else {
            refreshAllAvailable();
        }
        enforceQtyWithinAvailable(row);
    }

    function calcFifoAmount(tiers, qty) {
        if (!tiers || tiers.length === 0 || qty <= 0) return null;
        let remaining = qty;
        let amount = 0;
        for (let i = 0; i < tiers.length && remaining > 0; i++) {
            const take = Math.min(remaining, parseFloat(tiers[i].quantity) || 0);
            amount += take * (parseFloat(tiers[i].unit_price) || 0);
            remaining -= take;
        }
        return remaining <= 0 ? amount : null;
    }

    function calcRow(row) {
        const avail = parseFloat(row.querySelector('.sv-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
        const rateInp = row.querySelector('.sv-rate');
        let rate = parseFloat(rateInp.value) || 0;
        const isManualRate = rateInp && rateInp.dataset.manualRate === '1';
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function(){ try { return JSON.parse(tiersJson); } catch(e) { return null; } })() : null;
        let total;
        if (!isManualRate && tiers && tiers.length > 0 && qty > 0) {
            const fifoAmount = calcFifoAmount(tiers, qty);
            if (fifoAmount !== null) {
                total = fifoAmount;
                rate = qty > 0 ? total / qty : 0;
                rateInp.value = rate.toFixed(2);
            } else {
                total = qty * rate;
            }
        } else {
            total = qty * rate;
        }
        const left = Math.max(0, avail - qty);
        row.querySelector('.sv-left').value = left;
        row.querySelector('.sv-total').value = (total || 0).toFixed(2);
        enforceQtyWithinAvailable(row);
    }

    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(row => {
            const t = row.querySelector('.sv-total');
            if (t && t.value) sum += parseFloat(t.value) || 0;
        });
        const el = document.getElementById('modalGrandTotal');
        // Rendered as "Total: 0.00/-" — label and /- suffix live in the markup.
        if (el) el.textContent = sum.toFixed(2);
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#modalItemsBody .sv-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.sv-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    // Store selection change in ADD modal
    const addModalStoreSelect = document.querySelector('#addSellingVoucherModal select[name="store_id"]');
    if (addModalStoreSelect) {
        addModalStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
            currentStoreId = storeId;
            
            console.log('Store changed:', storeId); // Debug log
            
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateAddItemDropdowns();
                return;
            }
            
            fetchStoreItems(storeId, function() {
                console.log('Filtered items count:', filteredItems.length); // Debug log
                updateAddItemDropdowns();
            });
        });
    }

    function appendModalItemRow() {
        const tbody = document.getElementById('modalItemsBody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('beforeend', getRowHtml(rowIndex));
        rowIndex++;
        updateRemoveButtons();

        var newRow = tbody.querySelector('.sv-item-row:last-child');
        var newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
        if (newSelect && typeof Choices !== 'undefined') {
            createChoicesInstance(newSelect, createItemSelectConfig());
        }
    }

    function appendEditModalItemRow() {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, null));
        editRowIndex++;
        const newRow = tbody.querySelector('.sv-item-row:last-child');
        const newSelect = newRow ? newRow.querySelector('.sv-item-select') : null;
        if (newSelect && typeof Choices !== 'undefined') {
            if (newSelect.tomselect) {
                try { newSelect.tomselect.destroy(); } catch (e) {}
            }
            createChoicesInstance(newSelect, createEditModalItemSelectConfig());
        }
        updateEditRemoveButtons();
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // Neither modal has a toolbar "Add Item" button any more — the last row's
    // blue + adds the next line, via the delegated handlers on each tbody.
    window.svAddItemRow = appendModalItemRow;
    window.svAddEditItemRow = appendEditModalItemRow;

    /** Enter appends item row everywhere in modal except Choices dropdowns, buttons/links, and submit controls. */
    function svEnterShouldAppendItemRow(modalEl, activeEl) {
        if (!modalEl || !activeEl || !modalEl.contains(activeEl)) return false;
        if (activeEl.tagName === 'TEXTAREA') return false;
        if (activeEl.closest('button, a')) return false;
        if (activeEl.closest('.choices')) return false;
        if (activeEl.matches && activeEl.matches('select')) return false;
        if (activeEl.tagName === 'INPUT') {
            var it = (activeEl.type || '').toLowerCase();
            if (it === 'submit' || it === 'button' || it === 'reset' || it === 'image') return false;
        }
        return true;
    }

    const modalAddItemBtn = document.getElementById('modalAddItemRow');
    if (modalAddItemBtn) {
        modalAddItemBtn.addEventListener('click', function() {
            appendModalItemRow();
        });
    }

    const modalItemsBody = document.getElementById('modalItemsBody');
    const addSvModal = document.getElementById('addSellingVoucherModal');
    if (modalItemsBody) {
        modalItemsBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('sv-item-select')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    const rateInp = row.querySelector('.sv-rate');
                    if (rateInp) rateInp.dataset.manualRate = '';
                    updateUnit(row);
                    calcRow(row);
                    updateGrandTotal();
                }
            }
        });

        modalItemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    if (e.target.classList.contains('sv-rate')) {
                        const rateInp = row.querySelector('.sv-rate');
                        if (rateInp) rateInp.dataset.manualRate = '1';
                    }
                    refreshAllAvailable();
                    enforceQtyWithinAvailable(row);
                    calcRow(row);
                    updateGrandTotal();
                }
            }
        });

        modalItemsBody.addEventListener('click', function(e) {
            if (e.target.closest('.sv-add-row')) {
                if (typeof window.svAddItemRow === 'function') window.svAddItemRow();
                return;
            }
            // closest() (not e.target) so a click on the glyph inside the button still counts
            if (e.target.closest('.sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#modalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshAllAvailable();
                    updateGrandTotal();
                    updateRemoveButtons();
                }
                return;
            }
        });
    }

    // Delegate input/change from modal so qty/rate updates always run (Left Qty + Total)
    if (addSvModal) {
        function onAddModalQtyOrRateInput(e) {
            if (!e.target.matches('.sv-avail, .sv-qty, .sv-rate')) return;
            const row = e.target.closest('.sv-item-row');
            if (!row) return;
            refreshAllAvailable();
            calcRow(row);
            updateGrandTotal();
        }
        addSvModal.addEventListener('input', onAddModalQtyOrRateInput);
        addSvModal.addEventListener('change', onAddModalQtyOrRateInput);
    }

    // Enter (outside Choices + buttons): append item row anywhere in Add modal; prevents accidental form submit
    if (addSvModal) {
        addSvModal.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            var activeEl = document.activeElement;
            if (!svEnterShouldAppendItemRow(addSvModal, activeEl)) return;
            e.preventDefault();
            appendModalItemRow();
        });
    }

    function updateModalNameField() {
        const clientTypeRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        const clientNameSelect = document.getElementById('modalClientNameSelect');
        const nameInput = document.getElementById('modalClientNameInput');
        const facultySelect = document.getElementById('modalFacultySelect');
        const academyStaffSelect = document.getElementById('modalAcademyStaffSelect');
        const messStaffSelect = document.getElementById('modalMessStaffSelect');
        const otStudentSelect = document.getElementById('modalOtStudentSelect');
        const courseSelect = document.getElementById('modalCourseSelect');
        const courseNameSelect = document.getElementById('modalCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = clientNameSelect.options[clientNameSelect.selectedIndex];
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;

        // Pehle high-level Client Name / OT Course / Course select ko control karo
        if (isOt) {
            // OT: sirf OT Course + OT Student dikhna chahiye
            setSelectVisible(clientNameSelect, false);
            if (courseSelect) setSelectVisible(courseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, true); }
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, true);
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
        } else if (isCourse) {
            // Course: sirf Course select + text Name field
            setSelectVisible(clientNameSelect, false);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, true);
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.setAttribute('required', 'required');
        } else {
            // Employee / Section / Other: sirf Client Name + (Faculty/Staff/Mess) dropdown ya text field
            setSelectVisible(clientNameSelect, true);
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            if (otCourseSelect) setSelectVisible(otCourseSelect, false);
            if (otStudentSelect) { setSelectVisible(otStudentSelect, false); }
            if (courseSelect) setSelectVisible(courseSelect, false);
            nameInput.style.display = showAny ? 'none' : 'block';
        }

        // Ab niche ke detailed faculty/academy/mess/course-name dropdowns handle karo
        [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
            if (!sel) return;
            const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
            setSelectVisible(sel, show);
            sel.removeAttribute('required');
            if (show) {
                sel.setAttribute('required', 'required');
                sel.value = nameInput.value || '';
                if (sel.value) nameInput.value = sel.value;
            } else {
                sel.value = '';
            }
        });
        if (otStudentSelect) { setSelectVisible(otStudentSelect, isOt); if (!isOt) { otStudentSelect.value = ''; otStudentSelect.removeAttribute('required'); } }
        if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.value = ''; courseNameSelect.removeAttribute('required'); }
        if (!showAny && !isOt && !isCourse) {
            nameInput.setAttribute('required', 'required');
        }
    }

    function loadAddModalGenericBuyerNames() {
        const clientTypeRadio = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
        const clientNameSelect = document.getElementById('modalClientNameSelect');
        const nameInput = document.getElementById('modalClientNameInput');
        const dataList = document.getElementById('modalGenericBuyerNames');
        if (!clientTypeRadio || !clientNameSelect || !nameInput || !dataList) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        if (slug !== 'section' && slug !== 'other') {
            nameInput.removeAttribute('list');
            dataList.innerHTML = '';
            return;
        }

        const pk = clientNameSelect.value || '';
        nameInput.setAttribute('list', 'modalGenericBuyerNames');
        dataList.innerHTML = '';
        if (!pk) return;

        fetch(editSvBaseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' + encodeURIComponent(pk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(function(data) {
                dataList.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    dataList.appendChild(opt);
                });
            })
            .catch(function() {
                dataList.innerHTML = '';
            });
    }
    document.querySelectorAll('#addSellingVoucherModal .client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Show Client Name & Name columns as soon as a Client Type is selected
            var clientNameWrap = document.getElementById('modalClientNameWrap');
            var nameFieldWrap = document.getElementById('modalNameFieldWrap');
            if (clientNameWrap) clientNameWrap.style.display = '';
            if (nameFieldWrap) nameFieldWrap.style.display = '';
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('modalClientNameSelect');
            const otCourseSelect = document.getElementById('modalOtCourseSelect');
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const courseSelect = document.getElementById('modalCourseSelect');
            const courseNameSelect = document.getElementById('modalCourseNameSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (isOt) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, true); otStudentSelect.innerHTML = '<option value="">Select course first</option>'; otStudentSelect.setAttribute('required', 'required'); otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.removeAttribute('name'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, true); courseSelect.setAttribute('required', 'required'); courseSelect.setAttribute('name', 'client_type_pk'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Name';
                    nameInput.setAttribute('required', 'required');
                    nameInput.setAttribute('list', 'modalCourseBuyerNames');
                }
                const dl = document.getElementById('modalCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (otStudentSelect) { setSelectVisible(otStudentSelect, false); otStudentSelect.removeAttribute('required'); otStudentSelect.innerHTML = '<option value="">Select Student</option>'; otStudentSelect.value = ''; }
                if (courseSelect) { setSelectVisible(courseSelect, false); courseSelect.removeAttribute('required'); courseSelect.removeAttribute('name'); courseSelect.value = ''; }
                if (courseNameSelect) { setSelectVisible(courseNameSelect, false); courseNameSelect.removeAttribute('required'); courseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsAdd.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsAdd, this.value);
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'e.g. John Doe';
                    nameInput.setAttribute('required', 'required');
                    nameInput.removeAttribute('list');
                }
                const dl = document.getElementById('modalCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            }
            updateModalNameField();
            loadAddModalGenericBuyerNames();
        });
    });
    function reinitNameSelectTomSelect(select, placeholder) {
        if (!select || typeof Choices === 'undefined') return;
        if (select.tomselect) {
            try { select.tomselect.destroy(); } catch (e) {}
        }
        createChoicesInstance(select, createBlankSearchConfig({
            placeholder: placeholder || 'Select',
            clearOnOpen: false
        }));
    }
    const modalOtCourseSelect = document.getElementById('modalOtCourseSelect');
    if (modalOtCourseSelect) {
        modalOtCourseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const otStudentSelect = document.getElementById('modalOtStudentSelect');
            const nameInput = document.getElementById('modalClientNameInput');
            if (!otStudentSelect || !nameInput) return;
            if (otStudentSelect.tomselect) { try { otStudentSelect.tomselect.destroy(); } catch (e) {} }
            otStudentSelect.innerHTML = '<option value="">Loading...</option>';
            otStudentSelect.value = '';
            const selectedOpt = this.options[this.selectedIndex];
            nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
            if (!coursePk) {
                otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                setSelectVisible(otStudentSelect, true);
                return;
            }
            fetch(editSvBaseUrl + '/students-by-course/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    (data.students || []).forEach(function(s) {
                        const opt = document.createElement('option');
                        opt.value = s.display_name || '';
                        opt.textContent = s.display_name || '—';
                        opt.dataset.pk = s.pk || '';
                        otStudentSelect.appendChild(opt);
                    });
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                })
                .catch(function() {
                    otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                    reinitNameSelectTomSelect(otStudentSelect, 'Select Student');
                    setSelectVisible(otStudentSelect, true);
                });
        });
    }
    
    const modalOtStudentSelect = document.getElementById('modalOtStudentSelect');
    if (modalOtStudentSelect) {
        modalOtStudentSelect.addEventListener('change', function() {
            const inp = document.getElementById('modalClientNameInput');
            if (inp) inp.value = this.value || '';
            const clientIdField = document.getElementById('modalClientId');
            const selectedOpt = this.options[this.selectedIndex];
            if (clientIdField && selectedOpt) {
                clientIdField.value = (selectedOpt.dataset.pk || '');
            }
        });
    }
    
    const modalCourseSelect = document.getElementById('modalCourseSelect');
    if (modalCourseSelect) {
        modalCourseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const nameInput = document.getElementById('modalClientNameInput');
            const dataList = document.getElementById('modalCourseBuyerNames');
            if (!nameInput || !dataList) return;

            nameInput.setAttribute('list', 'modalCourseBuyerNames');
            dataList.innerHTML = '';

            if (!coursePk) return;
            fetch(editSvBaseUrl + '/buyer-names?client_type_slug=course&client_type_pk=' + encodeURIComponent(coursePk), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    dataList.innerHTML = '';
                    (data.buyers || []).forEach(function(b) {
                        const opt = document.createElement('option');
                        opt.value = b;
                        dataList.appendChild(opt);
                    });
                })
                .catch(function() {
                    dataList.innerHTML = '';
                });
        });
    }
    
    const modalClientNameSelect = document.getElementById('modalClientNameSelect');
    if (modalClientNameSelect) {
        modalClientNameSelect.addEventListener('change', function() {
            updateModalNameField();
            loadAddModalGenericBuyerNames();
        });
    }
    
    const modalFacultySelect = document.getElementById('modalFacultySelect');
    if (modalFacultySelect) {
        modalFacultySelect.addEventListener('change', function() {
            const inp = document.getElementById('modalClientNameInput');
            if (inp) inp.value = this.value || '';
            const clientIdField = document.getElementById('modalClientId');
            const selectedOpt = this.options[this.selectedIndex];
            if (clientIdField && selectedOpt) {
                clientIdField.value = (selectedOpt.dataset.pk || '');
            }
        });
    }
    const modalAcademyEl = document.getElementById('modalAcademyStaffSelect');
    if (modalAcademyEl) modalAcademyEl.addEventListener('change', function() {
        const inp = document.getElementById('modalClientNameInput');
        if (inp) inp.value = this.value || '';
        const clientIdField = document.getElementById('modalClientId');
        const selectedOpt = this.options[this.selectedIndex];
        if (clientIdField && selectedOpt) {
            clientIdField.value = (selectedOpt.dataset.pk || '');
        }
    });
    const modalMessEl = document.getElementById('modalMessStaffSelect');
    if (modalMessEl) modalMessEl.addEventListener('change', function() {
        const inp = document.getElementById('modalClientNameInput');
        if (inp) inp.value = this.value || '';
        const clientIdField = document.getElementById('modalClientId');
        const selectedOpt = this.options[this.selectedIndex];
        if (clientIdField && selectedOpt) {
            clientIdField.value = (selectedOpt.dataset.pk || '');
        }
    });
    const checked = document.querySelector('#addSellingVoucherModal .client-type-radio:checked');
    if (checked) checked.dispatchEvent(new Event('change'));

    // Edit modal: same Faculty / Academy Staff / Mess Staff dropdown logic
    function updateEditModalNameField() {
        const clientTypeRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editClientNameSelect');
        const nameInput = document.getElementById('editModalClientNameInput');
        const facultySelect = document.getElementById('editModalFacultySelect');
        const academyStaffSelect = document.getElementById('editModalAcademyStaffSelect');
        const messStaffSelect = document.getElementById('editModalMessStaffSelect');
        const editCourseSelect = document.getElementById('editModalCourseSelect');
        const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = clientNameSelect.options[clientNameSelect.selectedIndex];
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;
        if (isOt) {
            nameInput.style.display = 'block';
            nameInput.readOnly = true;
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Course name';
            nameInput.removeAttribute('readonly');
            nameInput.readOnly = false;
            nameInput.setAttribute('required', 'required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) { if (sel) { setSelectVisible(sel, false); sel.value = ''; sel.removeAttribute('required'); } });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, true); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ? showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) { sel.setAttribute('required', 'required'); sel.value = nameInput.value || ''; if (sel.value) nameInput.value = sel.value; } else sel.value = '';
            });
            if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.value = ''; editCourseSelect.removeAttribute('required'); }
            if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.value = ''; editCourseNameSelect.removeAttribute('required'); }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }

    function loadEditModalGenericBuyerNames() {
        const clientTypeRadio = document.querySelector('#editSellingVoucherModal .edit-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editClientNameSelect');
        const nameInput = document.getElementById('editModalClientNameInput');
        const dataList = document.getElementById('editGenericBuyerNames');
        if (!clientTypeRadio || !clientNameSelect || !nameInput || !dataList) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        if (slug !== 'section' && slug !== 'other') {
            nameInput.removeAttribute('list');
            dataList.innerHTML = '';
            return;
        }

        const pk = clientNameSelect.value || '';
        nameInput.setAttribute('list', 'editGenericBuyerNames');
        dataList.innerHTML = '';
        if (!pk) return;

        fetch(editSvBaseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' + encodeURIComponent(pk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(function(data) {
                dataList.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    dataList.appendChild(opt);
                });
            })
            .catch(function() {
                dataList.innerHTML = '';
            });
    }
    document.querySelectorAll('#editSellingVoucherModal .edit-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('editClientNameSelect');
            const otCourseSelect = document.getElementById('editModalOtCourseSelect');
            const editCourseSelect = document.getElementById('editModalCourseSelect');
            const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
            const nameInput = document.getElementById('editModalClientNameInput');
            if (isOt) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, true); otCourseSelect.setAttribute('required', 'required'); otCourseSelect.setAttribute('name', 'client_type_pk'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) { nameInput.style.display = 'block'; nameInput.readOnly = true; nameInput.placeholder = 'Select course above'; nameInput.value = nameInput.value || ''; nameInput.removeAttribute('required'); }
            } else if (isCourse) {
                if (clientSelect) { setSelectVisible(clientSelect, false); clientSelect.removeAttribute('required'); clientSelect.value = ''; clientSelect.removeAttribute('name'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, true); editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.readOnly = false;
                    nameInput.placeholder = 'Name';
                    nameInput.value = nameInput.value || '';
                    nameInput.setAttribute('required', 'required');
                    nameInput.setAttribute('list', 'editCourseBuyerNames');
                }
                const dl = document.getElementById('editCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            } else {
                if (clientSelect) { setSelectVisible(clientSelect, true); clientSelect.setAttribute('required', 'required'); clientSelect.setAttribute('name', 'client_type_pk'); }
                if (otCourseSelect) { setSelectVisible(otCourseSelect, false); otCourseSelect.removeAttribute('required'); otCourseSelect.removeAttribute('name'); otCourseSelect.value = ''; }
                if (editCourseSelect) { setSelectVisible(editCourseSelect, false); editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                if (editCourseNameSelect) { setSelectVisible(editCourseNameSelect, false); editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                if (clientSelect && clientNameOptionsEdit.length) {
                    rebuildClientNameSelect(clientSelect, clientNameOptionsEdit, this.value);
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.readOnly = false;
                    nameInput.placeholder = 'e.g. John Doe';
                    nameInput.setAttribute('required', 'required');
                    nameInput.removeAttribute('list');
                }
                const dl = document.getElementById('editCourseBuyerNames');
                if (dl) dl.innerHTML = '';
            }
            updateEditModalNameField();
            loadEditModalGenericBuyerNames();
        });
    });
    const editModalOtCourseSelect = document.getElementById('editModalOtCourseSelect');
    if (editModalOtCourseSelect) {
        editModalOtCourseSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName : '';
        });
    }
    
    const editModalCourseSelect = document.getElementById('editModalCourseSelect');
    if (editModalCourseSelect) {
        editModalCourseSelect.addEventListener('change', function() {
            const inp = document.getElementById('editModalClientNameInput');
            const coursePk = this.value;
            const dataList = document.getElementById('editCourseBuyerNames');
            if (!inp || !dataList) return;

            inp.setAttribute('list', 'editCourseBuyerNames');
            dataList.innerHTML = '';

            if (!coursePk) return;
            fetch(editSvBaseUrl + '/buyer-names?client_type_slug=course&client_type_pk=' + encodeURIComponent(coursePk), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    dataList.innerHTML = '';
                    (data.buyers || []).forEach(function(b) {
                        const opt = document.createElement('option');
                        opt.value = b;
                        dataList.appendChild(opt);
                    });
                })
                .catch(function() {
                    dataList.innerHTML = '';
                });
        });
    }
    
    const editClientNameSelect = document.getElementById('editClientNameSelect');
    if (editClientNameSelect) {
        editClientNameSelect.addEventListener('change', function() {
            updateEditModalNameField();
            loadEditModalGenericBuyerNames();
        });
    }
    
    const editModalFacultySelect = document.getElementById('editModalFacultySelect');
    if (editModalFacultySelect) {
        editModalFacultySelect.addEventListener('change', function() {
            const inp = document.getElementById('editModalClientNameInput');
            if (inp) inp.value = this.value || '';
            const clientIdField = document.getElementById('editModalClientId');
            const selectedOpt = this.options[this.selectedIndex];
            if (clientIdField && selectedOpt) {
                clientIdField.value = (selectedOpt.dataset.pk || '');
            }
        });
    }
    const editModalAcademyEl = document.getElementById('editModalAcademyStaffSelect');
    if (editModalAcademyEl) editModalAcademyEl.addEventListener('change', function() {
        const inp = document.getElementById('editModalClientNameInput');
        if (inp) inp.value = this.value || '';
        const clientIdField = document.getElementById('editModalClientId');
        const selectedOpt = this.options[this.selectedIndex];
        if (clientIdField && selectedOpt) {
            clientIdField.value = (selectedOpt.dataset.pk || '');
        }
    });
    const editModalMessEl = document.getElementById('editModalMessStaffSelect');
    if (editModalMessEl) editModalMessEl.addEventListener('change', function() {
        const inp = document.getElementById('editModalClientNameInput');
        if (inp) inp.value = this.value || '';
        const clientIdField = document.getElementById('editModalClientId');
        const selectedOpt = this.options[this.selectedIndex];
        if (clientIdField && selectedOpt) {
            clientIdField.value = (selectedOpt.dataset.pk || '');
        }
    });

    function getEditRowHtml(index, item) {
        const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems.slice() : (itemSubcategories || []).slice();
        // Keep saved voucher item visible even if it is missing from current store stock list
        if (item && item.item_subcategory_id) {
            const exists = sourceItems.some(function(s) { return String(s.id) === String(item.item_subcategory_id); });
            if (!exists) {
                sourceItems.unshift({
                    id: item.item_subcategory_id,
                    item_name: item.item_name || ('Item #' + item.item_subcategory_id),
                    unit_measurement: item.unit || '',
                    standard_cost: item.rate || 0,
                    available_quantity: item.available_quantity || 0,
                    price_tiers: []
                });
            }
        }
        const options = sourceItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity || 0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g, '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + (item && String(item.item_subcategory_id) === String(s.id) ? ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        }).join('');
        const returnQty = item ? (parseFloat(item.return_quantity) || 0) : 0;
        const qty = item ? (parseFloat(item.quantity) || 0) : '';
        const avail = item ? item.available_quantity : 0;
        const rate = item ? item.rate : '';
        const total = item
            ? (item.amount != null ? item.amount : ((parseFloat(qty) || 0) * (parseFloat(rate) || 0)))
            : '';
        const unit = item ? (item.unit || '') : '';
        const left = item && qty !== '' && (avail - qty) >= 0 ? (avail - qty) : 0;
        // original-qty = net issued (stock currently held by this voucher line)
        const originalQtyAttr = item ? (' data-original-qty="' + (parseFloat(qty) || 0) + '" data-return-qty="' + returnQty + '"') : '';
        return '<tr class="sv-item-row edit-sv-item-row"' + originalQtyAttr + '>' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select sv-item-select" required><option value="">Item</option>' + options + '</select>' +
            '<input type="hidden" name="items[' + index + '][return_quantity]" class="sv-return-qty-hidden" value="' + returnQty + '"></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control sv-unit" readonly placeholder="-" value="' + (unit || '') + '"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control sv-avail bg-light" step="0.01" min="0" value="' + avail + '" placeholder="-" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control sv-qty" step="0.01" min="0.01" placeholder="-" value="' + qty + '" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control sv-left bg-light" readonly placeholder="-" value="' + left + '"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control sv-rate" step="0.01" min="0" placeholder="-" value="' + rate + '" required></td>' +
            '<td><input type="text" class="form-control sv-total bg-light" readonly placeholder="-" value="' + (total !== '' ? Number(total).toFixed(2) : '') + '"></td>' +
            svActionCell(' edit-sv-remove-row') +
            '</tr>';
    }

    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editModalItemsBody .sv-item-row').forEach(row => {
            const t = row.querySelector('.sv-total');
            if (t && t.value) sum += parseFloat(t.value) || 0;
        });
        const el = document.getElementById('editModalGrandTotal');
        // Rendered as "Total: 0.00/-" — label and /- suffix live in the markup.
        if (el) el.textContent = sum.toFixed(2);
    }

    function updateEditRemoveButtons() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.sv-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    /**
     * Recalculate Available Qty and Left Qty for all rows in the Edit modal.
     * Effective base per item = current stock + sum of original qtys (from this voucher) for that item.
     * Then each row gets available = base - already used in previous rows (same logic as Add mode).
     */
    function refreshEditAllAvailable() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        if (!rows.length) return;

        const effectiveBaseByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            if (!itemId) return;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            if (!effectiveBaseByItem.hasOwnProperty(itemId)) {
                effectiveBaseByItem[itemId] = getBaseAvailableForItem(itemId);
            }
            effectiveBaseByItem[itemId] += originalQty;
        });

        const usedByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.sv-item-select');
            const itemId = select ? select.value : '';
            const availInp = row.querySelector('.sv-avail');
            const leftInp = row.querySelector('.sv-left');
            if (!itemId || !availInp) return;

            const effectiveBase = effectiveBaseByItem[itemId] != null ? effectiveBaseByItem[itemId] : getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, effectiveBase - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row);
        });
    }

    function updateEditItemDropdowns() {
        const rows = document.querySelectorAll('#editModalItemsBody .sv-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.sv-item-select');
            if (!select) return;

            const currentValue = select.value;
            select.innerHTML = '<option value="">Item</option>';

            const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems.slice() : (itemSubcategories || []).slice();
            if (currentValue && !sourceItems.some(function(s) { return String(s.id) === String(currentValue); })) {
                const optLabel = (row.querySelector('.sv-unit') && row.querySelector('.sv-unit').value) ? ('Item #' + currentValue) : ('Item #' + currentValue);
                sourceItems.unshift({
                    id: currentValue,
                    item_name: optLabel,
                    unit_measurement: (row.querySelector('.sv-unit') || {}).value || '',
                    standard_cost: (row.querySelector('.sv-rate') || {}).value || 0,
                    available_quantity: (row.querySelector('.sv-avail') || {}).value || 0,
                    price_tiers: []
                });
            }
            sourceItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (String(item.id) === String(currentValue)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            if (typeof Choices !== 'undefined') {
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                var ts = createChoicesInstance(select, createEditModalItemSelectConfig());
                if (currentValue && ts && typeof ts.setValue === 'function') {
                    try { ts.setValue(String(currentValue)); } catch (e) {}
                }
            }
            updateUnit(row);
        });
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    function buildEditItemsTable(items) {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(0, null));
            editRowIndex = 1;
        } else {
            items.forEach((item, i) => {
                tbody.insertAdjacentHTML('beforeend', getEditRowHtml(i, item));
            });
            editRowIndex = items.length;
        }
        if (typeof Choices !== 'undefined') {
            tbody.querySelectorAll('.sv-item-select').forEach(function(select) {
                var selectedVal = select.value || '';
                if (select.tomselect) {
                    try { select.tomselect.destroy(); } catch (e) {}
                }
                var ts = createChoicesInstance(select, createEditModalItemSelectConfig());
                if (selectedVal && ts && typeof ts.setValue === 'function') {
                    try { ts.setValue(String(selectedVal)); } catch (e) {}
                }
            });
        }
        updateEditRemoveButtons();
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // View button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found');
                return;
            }
            console.log('Fetching voucher:', voucherId);
            fetch(viewSvBaseUrl + '/' + voucherId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP error ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('Voucher data:', data);
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('viewSellingVoucherModalLabel').textContent = 'View Selling Voucher #' + (v.pk || voucherId);
                    document.getElementById('viewRequestDate').textContent = v.request_date || '-';
                    document.getElementById('viewIssueDate').textContent = v.issue_date || '-';
                    document.getElementById('viewStoreName').textContent = v.store_name || '-';
                    document.getElementById('viewReferenceNumber').textContent = v.reference_number || '-';
                    document.getElementById('viewOrderBy').textContent = v.order_by || '-';
                    document.getElementById('viewClientType').textContent = v.client_type || '-';
                    document.getElementById('viewClientName').textContent = v.client_name || '-';
                    document.getElementById('viewPaymentType').textContent = v.payment_type || '-';
                    const statusEl = document.getElementById('viewStatus');
                    const statusMap = { 0: ['pending', 'Pending'], 2: ['approved', 'Approved'], 4: ['completed', 'Completed'] };
                    const statusInfo = statusMap[v.status] || ['other', String(v.status_label || v.status || '-')];
                    statusEl.className = 'sv-status-pill sv-status-pill--' + statusInfo[0];
                    statusEl.textContent = statusInfo[1];
                    if (v.remarks) {
                        document.getElementById('viewRemarksWrap').style.display = 'block';
                        document.getElementById('viewRemarks').textContent = v.remarks;
                    } else {
                        document.getElementById('viewRemarksWrap').style.display = 'none';
                    }
                    // Bill display removed; keep view logic resilient if elements are absent
                    const tbody = document.getElementById('viewModalItemsBody');
                    tbody.innerHTML = '';
                    if (data.has_items && items.length > 0) {
                        document.getElementById('viewItemsCard').style.display = '';
                        items.forEach(function(item) {
                            tbody.insertAdjacentHTML('beforeend', '<tr><td>' + escapeSvHtml(item.item_name || '-') + '</td><td>' + escapeSvHtml(item.unit || '-') + '</td><td>' + escapeSvHtml(item.quantity) + '</td><td>' + escapeSvHtml(item.return_quantity || 0) + '</td><td>' + escapeSvHtml(item.rate) + '</td><td>' + escapeSvHtml(item.amount) + '</td></tr>');
                        });
                        document.getElementById('viewModalGrandTotal').textContent = data.grand_total || '0.00';
                    } else {
                        document.getElementById('viewItemsCard').style.display = 'none';
                    }
                    document.getElementById('viewCreatedAt').textContent = v.created_at || '-';
                    if (v.updated_at) {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'inline';
                        document.getElementById('viewUpdatedAt').textContent = v.updated_at;
                    } else {
                        document.getElementById('viewUpdatedAtWrap').style.display = 'none';
                    }
                    const modal = new bootstrap.Modal(document.getElementById('viewSellingVoucherModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading voucher:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    }, true);

    // Return button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for return');
                return;
            }
            console.log('Loading return data for voucher:', voucherId);
            fetch(returnSvBaseUrl + '/' + voucherId + '/return', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP error ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('Return data:', data);
                    document.getElementById('returnTransferFromStore').textContent = data.store_name || '-';
                    const issueDate = data.issue_date || '';
                    const todayYmd = new Date().toISOString().slice(0, 10);
                    const tbody = document.getElementById('returnItemModalBody');
                    tbody.innerHTML = '';
                    function ymdToDmY(ymd) {
                        if (!ymd) return '-';
                        var p = String(ymd).split('-');
                        if (p.length !== 3) return ymd;
                        return p[2] + '/' + p[1] + '/' + p[0];
                    }
                    (data.items || []).forEach(function(item, i) {
                        const id = (item.id != null) ? item.id : '';
                        const name = (item.item_name || '-').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                        const qty = item.quantity != null ? item.quantity : '';
                        const unit = (item.unit || '-').replace(/</g, '&lt;');
                        const retQty = item.return_quantity != null ? item.return_quantity : 0;
                        const retDate = item.return_date || '';
                        const issuedQty = parseFloat(qty) || 0;
                        const rowIssueYmd = (item.issue_date || issueDate || '').trim();
                        const issueDisp = ymdToDmY(rowIssueYmd);
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr><td>' + name + '<input type="hidden" name="items[' + i + '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit + '</td><td class="text-nowrap">' + issueDisp + '</td>' +
                            '<td><input type="number" name="items[' + i + '][return_quantity]" class="form-control sv-return-qty" step="0.01" min="0" max="' + issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty + '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                            '<td><input type="date" name="items[' + i + '][return_date]" class="form-control sv-return-date" max="' + todayYmd + '" ' + (rowIssueYmd ? ('min="' + rowIssueYmd + '" data-issue-date="' + rowIssueYmd + '"') : '') + ' value="' + retDate + '"><div class="invalid-feedback">Return date must be between issue date and today.</div></td></tr>');
                    });
                    document.getElementById('returnItemForm').action = returnSvBaseUrl + '/' + voucherId + '/return';
                    const modal = new bootstrap.Modal(document.getElementById('returnItemModal'));
                    modal.show();
                })
                .catch(err => { 
                    console.error('Error loading return data:', err); 
                    alert('Failed to load return data: ' + err.message); 
                });
        }
    }, true);

    function enforceReturnQtyWithinIssued(inputEl) {
        if (!inputEl) return;
        const issued = parseFloat(inputEl.dataset.issued) || 0;
        const raw = inputEl.value;
        const val = parseFloat(raw);
        inputEl.max = String(issued);
        if (raw === '' || Number.isNaN(val)) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (val > issued) {
            inputEl.setCustomValidity('Return Qty cannot exceed Issued Qty.');
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
        }
    }

    function enforceReturnDateWithinRange(inputEl) {
        if (!inputEl) return;
        const issue = inputEl.dataset.issueDate || '';
        const raw = inputEl.value;
        const today = new Date().toISOString().slice(0, 10);
        inputEl.max = today;

        if (!raw) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (raw > today) {
            inputEl.setCustomValidity('Return date cannot be in the future.');
            inputEl.classList.add('is-invalid');
            return;
        }
        if (issue && raw < issue) {
            inputEl.setCustomValidity('Return date cannot be earlier than issue date.');
            inputEl.classList.add('is-invalid');
            return;
        }

        inputEl.setCustomValidity('');
        inputEl.classList.remove('is-invalid');
    }

    const returnItemModalBody = document.getElementById('returnItemModalBody');
    if (returnItemModalBody) {
        returnItemModalBody.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('sv-return-qty')) {
                enforceReturnQtyWithinIssued(e.target);
            }
            if (e.target && e.target.classList.contains('sv-return-date')) {
                enforceReturnDateWithinRange(e.target);
            }
        });
    }

    const returnItemForm = document.getElementById('returnItemForm');
    if (returnItemForm) {
        returnItemForm.addEventListener('submit', function(e) {
            this.querySelectorAll('.sv-return-qty').forEach(enforceReturnQtyWithinIssued);
            this.querySelectorAll('.sv-return-date').forEach(enforceReturnDateWithinRange);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
            }
        }, true);
    }

    // Edit button handler (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-sv');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const voucherId = btn.getAttribute('data-voucher-id');
            if (!voucherId) {
                console.error('No voucher ID found for edit');
                return;
            }
            console.log('Loading edit data for voucher:', voucherId);
            fetch(editSvBaseUrl + '/' + voucherId + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
                .then(({ ok, status, data }) => {
                    if (!ok) {
                        alert(data && data.error ? data.error : 'Failed to load voucher (HTTP ' + status + ').');
                        return;
                    }
                    console.log('Edit data:', data);
                    if (data.error) { alert(data.error); return; }
                    destroyEditModalTomSelects();
                    const v = data.voucher;
                    const items = data.items || [];
                    document.getElementById('editSellingVoucherForm').action = editSvBaseUrl + '/' + voucherId;
                    
                    // Set client type radio (do not dispatch "change" — it resets fields and fights this loader)
                    const clientTypeRadio = document.querySelector('#editSellingVoucherModal input[name="client_type_slug"][value="' + (v.client_type_slug || 'employee') + '"]');
                    if (clientTypeRadio) {
                        clientTypeRadio.checked = true;
                    }
                    
                    document.querySelector('#editSellingVoucherModal select.edit-payment-type').value = String(v.payment_type ?? 1);
                    const editSlug = (v.client_type_slug || 'employee');

                    // Required for employee/OT updates — must be set from saved voucher (not only on Name change)
                    var editClientIdEl = document.getElementById('editModalClientId');
                    if (editClientIdEl) {
                        editClientIdEl.value = (v.client_id != null && String(v.client_id) !== '') ? String(v.client_id) : '';
                    }
                    
                    document.getElementById('editModalClientNameInput').value = v.client_name || '';
                    document.getElementById('editModalFacultySelect').value = v.client_name || '';
                    const editAcademyEl = document.getElementById('editModalAcademyStaffSelect');
                    if (editAcademyEl) editAcademyEl.value = v.client_name || '';
                    const editMessEl = document.getElementById('editModalMessStaffSelect');
                    if (editMessEl) editMessEl.value = v.client_name || '';
                    const editOtCourseEl = document.getElementById('editModalOtCourseSelect');
                    if (editOtCourseEl) editOtCourseEl.value = v.client_type_pk || '';
                    const editCourseEl = document.getElementById('editModalCourseSelect');
                    if (editCourseEl) {
                        editCourseEl.value = v.client_type_pk || '';
                        if ((v.client_type_slug || '') === 'course') {
                            editCourseEl.dispatchEvent(new Event('change'));
                        }
                    }
                    const editCourseNameEl = document.getElementById('editModalCourseNameSelect');
                    if (editCourseNameEl) editCourseNameEl.value = v.client_type_pk || '';
                    document.querySelector('#editSellingVoucherModal input.edit-issue-date').value = v.issue_date || '';
                    
                    const storeSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
                    if (storeSelect) storeSelect.value = v.inve_store_master_pk || v.store_id || '';
                    
                    document.querySelector('#editSellingVoucherModal input.edit-remarks').value = v.remarks || '';
                    const editRefNum = document.querySelector('#editSellingVoucherModal input.edit-reference-number');
                    if (editRefNum) editRefNum.value = v.reference_number || '';
                    const editOrderBy = document.querySelector('#editSellingVoucherModal input.edit-order-by');
                    if (editOrderBy) editOrderBy.value = v.order_by || '';
                    var editBillFileNameEl = document.getElementById('editBillCurrentFileName');
                    if (editBillFileNameEl) {
                        if (v.bill_path) {
                            var billFileName = v.bill_path.split('/').pop() || v.bill_path;
                            editBillFileNameEl.textContent = billFileName;
                            editBillFileNameEl.setAttribute('title', billFileName);
                        } else {
                            editBillFileNameEl.textContent = 'No file chosen';
                            editBillFileNameEl.removeAttribute('title');
                        }
                    }
                    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
                    if (editSvBillFileInputEl) editSvBillFileInputEl.value = '';
                    var editRemoveBillFlagEl = document.getElementById('editRemoveBillFlag');
                    if (editRemoveBillFlagEl) editRemoveBillFlagEl.value = '0';
                    var editBillLinkEl = document.getElementById('editCurrentBillLink');
                    if (editBillLinkEl) {
                        if (v.bill_url) {
                            editBillLinkEl.innerHTML = 'Current bill: <a href="' + (v.bill_url || '').replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                        } else {
                            editBillLinkEl.innerHTML = '';
                        }
                    }
                    editCurrentStoreId = storeSelect ? storeSelect.value || '' : null;

                    // Align native fields / visibility BEFORE Choices init (same for store + no-store paths)
                    (function applyEditSvClientTypeLayout() {
                        const isOt = (v.client_type_slug || '') === 'ot';
                        const isCourse = (v.client_type_slug || '') === 'course';
                        const editClientSelect = document.getElementById('editClientNameSelect');
                        const editOtSelect = document.getElementById('editModalOtCourseSelect');
                        const editCourseSelect = document.getElementById('editModalCourseSelect');
                        const editCourseNameSelect = document.getElementById('editModalCourseNameSelect');
                        const editNameInp = document.getElementById('editModalClientNameInput');
                        if (isOt) {
                            if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                            if (editOtSelect) { editOtSelect.style.display = 'block'; editOtSelect.setAttribute('required', 'required'); editOtSelect.setAttribute('name', 'client_type_pk'); editOtSelect.value = v.client_type_pk || ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = true; editNameInp.placeholder = 'Name (from course/student)'; editNameInp.value = v.client_name || ''; editNameInp.removeAttribute('required'); }
                        } else if (isCourse) {
                            if (editClientSelect) { editClientSelect.style.display = 'none'; editClientSelect.removeAttribute('required'); editClientSelect.removeAttribute('name'); }
                            if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'block'; editCourseSelect.setAttribute('required', 'required'); editCourseSelect.setAttribute('name', 'client_type_pk'); editCourseSelect.value = v.client_type_pk || ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'Course name'; editNameInp.value = v.client_name || ''; editNameInp.setAttribute('required', 'required'); }
                        } else {
                            if (editClientSelect) {
                                editClientSelect.style.display = 'block';
                                editClientSelect.setAttribute('required', 'required');
                                editClientSelect.setAttribute('name', 'client_type_pk');
                                if (clientNameOptionsEdit.length) {
                                    rebuildClientNameSelect(editClientSelect, clientNameOptionsEdit, (v.client_type_slug || 'employee'));
                                }
                                setSelectValue(document.getElementById('editClientNameSelect'), v.client_type_pk || '');
                            }
                            if (editOtSelect) { editOtSelect.style.display = 'none'; editOtSelect.removeAttribute('required'); editOtSelect.removeAttribute('name'); editOtSelect.value = ''; }
                            if (editCourseSelect) { editCourseSelect.style.display = 'none'; editCourseSelect.removeAttribute('required'); editCourseSelect.removeAttribute('name'); editCourseSelect.value = ''; }
                            if (editCourseNameSelect) { editCourseNameSelect.style.display = 'none'; editCourseNameSelect.removeAttribute('required'); editCourseNameSelect.value = ''; }
                            if (editNameInp) { editNameInp.style.display = 'block'; editNameInp.readOnly = false; editNameInp.placeholder = 'e.g. John Doe'; editNameInp.setAttribute('required', 'required'); }
                        }
                    })();

                    const openEditModalWithItems = function() {
                        buildEditItemsTable(items);
                        // Initialize Choices in Edit modal (payment, client, store, name dropdowns)
                        if (typeof initEditModalTomSelects === 'function') {
                            initEditModalTomSelects();
                        }
                        // Ensure Client Name dropdown is rebuilt for current Client Type, then select saved value.
                        if (editSlug !== 'ot' && editSlug !== 'course') {
                            const editClientSelect = document.getElementById('editClientNameSelect');
                            if (editClientSelect && clientNameOptionsEdit.length) {
                                rebuildClientNameSelect(editClientSelect, clientNameOptionsEdit, editSlug);
                            }
                            setSelectValue(document.getElementById('editClientNameSelect'), v.client_type_pk || '');
                        }
                        // After Choices init, show only the active dropdowns in Client Name column and Name column
                        if (typeof applyEditModalClientNameColumnVisibility === 'function') {
                            applyEditModalClientNameColumnVisibility();
                        }
                        if (typeof updateEditModalNameField === 'function') {
                            updateEditModalNameField();
                        }
                        syncEditSellingVoucherChoicesFromVoucher(v, editSlug);
                        // Re-apply item avail/left after voucher field sync (store items already loaded)
                        refreshEditAllAvailable();
                        updateEditGrandTotal();
                        const modal = new bootstrap.Modal(document.getElementById('editSellingVoucherModal'));
                        modal.show();
                    };
                    if (editCurrentStoreId) {
                        fetchStoreItems(editCurrentStoreId, function() {
                            openEditModalWithItems();
                        });
                    } else {
                        filteredItems = itemSubcategories;
                        openEditModalWithItems();
                    }
                })
                .catch(err => { 
                    console.error('Error loading voucher for edit:', err); 
                    alert('Failed to load selling voucher: ' + err.message); 
                });
        }
    }, true);

    const editModalAddItemRow = document.getElementById('editModalAddItemRow');
    if (editModalAddItemRow) {
        editModalAddItemRow.addEventListener('click', function() {
            appendEditModalItemRow();
        });
    }

    // Add modal: show selected bill file name and Remove button
    var addSvBillFileInputEl = document.getElementById('addSvBillFileInput');
    if (addSvBillFileInputEl) {
        addSvBillFileInputEl.addEventListener('change', function() {
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
            if (wrap && nameEl) {
                if (this.files && this.files[0]) {
                    nameEl.textContent = this.files[0].name;
                    wrap.classList.remove('d-none');
                } else {
                    nameEl.textContent = '';
                    wrap.classList.add('d-none');
                }
            }
        });
    }
    var addSvBillFileRemoveEl = document.getElementById('addSvBillFileRemove');
    if (addSvBillFileRemoveEl) {
        addSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('addSvBillFileInput');
            var wrap = document.getElementById('addSvBillFileChosenWrap');
            var nameEl = document.getElementById('addSvBillFileChosenName');
            if (input) input.value = '';
            if (nameEl) nameEl.textContent = '';
            if (wrap) wrap.classList.add('d-none');
        });
    }

    // Edit modal: show selected file name when user picks a new bill (same as Selling Voucher with Date Range)
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
            if (removeFlag) removeFlag.value = '0';
        });
    }
    var editSvBillFileRemoveEl = document.getElementById('editSvBillFileRemove');
    if (editSvBillFileRemoveEl) {
        editSvBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('editSvBillFileInput');
            var pathEl = document.getElementById('editBillCurrentFileName');
            var removeFlag = document.getElementById('editRemoveBillFlag');
            if (input) input.value = '';
            if (pathEl) pathEl.textContent = 'No file chosen';
            if (removeFlag) removeFlag.value = '1';
        });
    }

    const editModalItemsBody = document.getElementById('editModalItemsBody');
    if (editModalItemsBody) {
        editModalItemsBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('sv-item-select')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    // Changing item clears previous line's return history for this row.
                    const retHidden = row.querySelector('.sv-return-qty-hidden');
                    if (retHidden) retHidden.value = '0';
                    row.setAttribute('data-return-qty', '0');
                    updateUnit(row); calcRow(row); updateEditGrandTotal();
                }
            }
        });
        
        editModalItemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
                const row = e.target.closest('.sv-item-row');
                if (row) {
                    if (e.target.classList.contains('sv-rate')) {
                        const rateInp = row.querySelector('.sv-rate');
                        if (rateInp) rateInp.dataset.manualRate = '1';
                    }
                    refreshEditAllAvailable();
                    calcRow(row);
                    updateEditGrandTotal();
                }
            }
        });
        
        editModalItemsBody.addEventListener('click', function(e) {
            if (e.target.closest('.sv-add-row')) {
                if (typeof window.svAddEditItemRow === 'function') window.svAddEditItemRow();
                return;
            }
            // closest() (not e.target) so a click on the glyph inside the button still counts
            if (e.target.closest('.sv-remove-row')) {
                const row = e.target.closest('.sv-item-row');
                if (row && document.querySelectorAll('#editModalItemsBody .sv-item-row').length > 1) {
                    row.remove();
                    refreshEditAllAvailable();
                    updateEditGrandTotal();
                    updateEditRemoveButtons();
                }
            }
        });
    }

    // Enter (outside Choices + buttons): append item row anywhere in Edit modal
    const editSvModalEl = document.getElementById('editSellingVoucherModal');
    if (editSvModalEl) {
        editSvModalEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            var activeEl = document.activeElement;
            if (!svEnterShouldAppendItemRow(editSvModalEl, activeEl)) return;
            e.preventDefault();
            appendEditModalItemRow();
        });
    }

    // Store selection change in EDIT modal
    const editStoreSelect = document.querySelector('#editSellingVoucherModal select.edit-store');
    if (editStoreSelect) {
        editStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
            editCurrentStoreId = storeId;
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateEditItemDropdowns();
                return;
            }
            fetchStoreItems(storeId, function() {
                updateEditItemDropdowns();
            });
        });
    }

    // Reset add selling voucher modal when closed (so next open starts fresh)
    const addSellingVoucherModal = document.getElementById('addSellingVoucherModal');
    if (addSellingVoucherModal) {
        addSellingVoucherModal.addEventListener('hidden.bs.modal', function() {
            addSellingVoucherModal.classList.remove('sv-choices-dropdown-open');
            currentStoreId = null;
            filteredItems = itemSubcategories;
            destroyAddModalTomSelects();
            const form = document.getElementById('sellingVoucherModalForm');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
            }
            resetAddModalStoreSelectToEmpty(addSellingVoucherModal);
            const issueDateInp = addSellingVoucherModal.querySelector('input[name="issue_date"]');
            if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
            const paymentSel = addSellingVoucherModal.querySelector('select[name="payment_type"]');
            if (paymentSel) paymentSel.value = '1';
            const clientPkSel = addSellingVoucherModal.querySelector('#modalClientNameSelect');
            if (clientPkSel) clientPkSel.value = '';
            const clientNameInp = document.getElementById('modalClientNameInput');
            if (clientNameInp) clientNameInp.value = '';
            addSellingVoucherModal.querySelectorAll('#modalClientNameWrap select, #modalNameFieldWrap select').forEach(function(s) { if (s.value !== undefined) s.value = ''; });
            resetAddModalClientTypeToEmployee(addSellingVoucherModal);
            const billInput = document.getElementById('addSvBillFileInput');
            if (billInput) billInput.value = '';
            const billWrap = document.getElementById('addSvBillFileChosenWrap');
            const billName = document.getElementById('addSvBillFileChosenName');
            if (billWrap) billWrap.classList.add('d-none');
            if (billName) billName.textContent = '';
            const tbody = document.getElementById('modalItemsBody');
            if (tbody) {
                tbody.innerHTML = getRowHtml(0);
                rowIndex = 1;
                updateRemoveButtons();
            }
            const grandTotalEl = document.getElementById('modalGrandTotal');
            if (grandTotalEl) grandTotalEl.textContent = '0.00';
        });

        var editSellingVoucherModalEl = document.getElementById('editSellingVoucherModal');
        if (editSellingVoucherModalEl) {
            editSellingVoucherModalEl.addEventListener('hidden.bs.modal', function() {
                editSellingVoucherModalEl.classList.remove('sv-choices-dropdown-open');
            });
        }

        addSellingVoucherModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addSellingVoucherModal.querySelector('select[name="store_id"]');
            const preSelectedStore = storeSelect ? storeSelect.value : null;
            
            console.log('Modal opening, pre-selected store:', preSelectedStore); // Debug log
            
            // If there's a pre-selected store, fetch its items
            if (preSelectedStore) {
                currentStoreId = preSelectedStore;
                fetchStoreItems(preSelectedStore, function() {
                    console.log('Pre-fetched items for store:', preSelectedStore, 'Count:', filteredItems.length);
                    updateAddItemDropdowns();
                    refreshAllAvailable();
                    document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
                    updateGrandTotal();
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
        });
        addSellingVoucherModal.addEventListener('shown.bs.modal', function() {
            initAddModalTomSelects();
            refreshAllAvailable();
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(function(row) { calcRow(row); });
            updateGrandTotal();
        });
    }

    // Before disabling submit buttons, ensure form is valid (includes qty <= available)
    if (sellingVoucherModalForm) {
        sellingVoucherModalForm.addEventListener('submit', function(e) {
            // sync validity for all rows
            document.querySelectorAll('#modalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    if (editSellingVoucherForm) {
        editSellingVoucherForm.addEventListener('submit', function(e) {
            syncEditModalClientIdBeforeSubmit();
            document.querySelectorAll('#editModalItemsBody .sv-item-row').forEach(enforceQtyWithinAvailable);
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
    }

    @if(session('open_selling_voucher_modal') || ($errors->any() && old('_method') !== 'PUT'))
    var modal = document.getElementById('addSellingVoucherModal');
    if (modal && typeof bootstrap !== 'undefined') {
        (new bootstrap.Modal(modal)).show();
    }
    @elseif($errors->any() && old('_method') === 'PUT')
    (function() {
        var msgs = @json($errors->all());
        var text = (msgs && msgs.length) ? msgs.join('\n') : 'Failed to update selling voucher.';
        if (window.toastr) {
            toastr.error(text);
        } else {
            alert(text);
        }
    })();
    @endif

    // Print View modal content (Selling Voucher) - branded header + spec-design body
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modalEl = document.querySelector(sel);
        if (!modalEl) return;
        var bodyEl = modalEl.querySelector('.modal-body');
        if (!bodyEl) return;

        var title = (modalEl.querySelector('.modal-title') || {}).textContent || 'Selling Voucher';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2, '0') + '/' +
            (printedOn.getMonth() + 1).toString().padStart(2, '0') + '/' + printedOn.getFullYear() +
            ', ' + printedOn.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });

        var wrap = document.createElement('div');
        wrap.innerHTML = bodyEl.innerHTML;
        wrap.querySelectorAll('button, .btn-close').forEach(function(el) { el.remove(); });
        // The screen layout drops section headings; print reads better with them.
        var firstRow = wrap.querySelector('.row');
        if (firstRow) {
            var h = document.createElement('div');
            h.className = 'sv-print-title';
            h.textContent = 'Voucher Details';
            firstRow.parentNode.insertBefore(h, firstRow);
        }
        var itemsBox = wrap.querySelector('.sv-items-box');
        if (itemsBox) {
            var h2 = document.createElement('div');
            h2.className = 'sv-print-title';
            h2.textContent = 'Item Details';
            itemsBox.parentNode.insertBefore(h2, itemsBox);
        }

        var logoUrl = @json(asset('images/lbsnaa_logo.jpg'));
        var printHeader =
            '<div class="print-doc-header" style="text-align:center;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="margin-bottom:10px;"><img src="' + logoUrl + '" alt="LBSNAA Logo" style="height:60px;width:auto;"></div>' +
            '<div style="font-size:18px;font-weight:700;color:#1a1a1a;margin-bottom:6px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#004384;color:#fff;padding:8px 16px;font-size:14px;display:inline-block;margin:4px 0;border-radius:4px;-webkit-print-color-adjust:exact;print-color-adjust:exact;">Selling Voucher</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:8px;">' +
            (title ? title.replace(/</g, '&lt;') + ' &nbsp;|&nbsp; ' : '') + 'Printed on ' + dateStr +
            '</div></div>';

        var ink = ' -webkit-print-color-adjust: exact; print-color-adjust: exact;';
        // Mirrors the on-screen View classes (.sv-label/.sv-value/.sv-items-table/.sv-total-bar).
        var printCss = '<style>' +
            '@page { size: A4; margin: 14mm; }' +
            'body { font-family: Arial, sans-serif; font-size: 12px; color: #212529; margin: 0; padding: 0; background: #fff; }' +
            '.print-doc-header {' + ink + ' }' +
            '.print-doc-header img {' + ink + ' }' +
            '.modal-header, .modal-footer, .btn-close { display: none !important; }' +
            '.sv-print-title { margin: 16px 0 8px; font-size: 13px; font-weight: 700; color: #004384;' +
            ' border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }' +
            '.sv-print-title:first-child { margin-top: 0; }' +
            '.row { display: flex; flex-wrap: wrap; margin: 0 -6px; }' +
            '.row > [class*="col-"] { box-sizing: border-box; padding: 0 6px 10px; width: 33.33%; }' +
            '.row > .col-12 { width: 100%; }' +
            '.sv-label { display: block; margin-bottom: 2px; font-size: 10px; font-weight: 600;' +
            ' color: #6c757d; text-transform: uppercase; letter-spacing: .02em; }' +
            '.sv-value { margin: 0; padding: 4px 8px; font-size: 12px; color: #212529; background: #f8f9fa;' +
            ' border: 1px solid #e9ecef; border-radius: 3px; min-height: 20px;' + ink + ' }' +
            '.sv-status-pill { display: inline-block; padding: 3px 10px; font-size: 10px; font-weight: 700;' +
            ' border-radius: 999px;' + ink + ' }' +
            '.sv-status-pill--approved { color: #067647 !important; background: #d7f5e5 !important; }' +
            '.sv-status-pill--completed { color: #004384 !important; background: #dbe9f8 !important; }' +
            '.sv-status-pill--pending { color: #b54708 !important; background: #fdf0d5 !important; }' +
            '.sv-status-pill--other { color: #475467 !important; background: #eceff3 !important; }' +
            '.sv-items-box { border: 1px solid #adb5bd; border-radius: 3px; overflow: hidden; }' +
            'table { width: 100%; border-collapse: collapse; font-size: 11px; page-break-inside: auto; }' +
            'th, td { border: 1px solid #adb5bd; padding: 5px 8px; text-align: left; }' +
            'thead th { background: #004384 !important; color: #fff !important; border-color: #003468;' +
            ' font-weight: 600;' + ink + ' }' +
            'thead { display: table-header-group; }' +
            'tbody tr { page-break-inside: avoid; }' +
            'tbody tr:nth-child(even) td { background-color: #f4f6f8 !important;' + ink + ' }' +
            /* Issue Qty / Return Qty / Rate / Line Total read as numbers */
            'th:nth-child(n+3), td:nth-child(n+3) { text-align: right; }' +
            '.sv-total-bar { padding: 7px 10px; font-size: 12px; font-weight: 700; text-align: right;' +
            ' color: #004384 !important; background: #dbe9f8 !important; border-top: 1px solid #adb5bd;' +
            ' page-break-inside: avoid;' + ink + ' }' +
            '.sv-meta { margin-top: 12px; font-size: 10px; color: #6c757d; }' +
            '@media print { .print-doc-header { margin-bottom: 16px; } }' +
            '</style>';

        var docHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' +
            title.replace(/</g, '&lt;') + '</title>' + printCss + '</head><body>' + printHeader +
            '<div class="modal-content-wrap">' + wrap.innerHTML + '</div></body></html>';

        // Printed from a hidden same-page iframe rather than window.open(): a popup
        // blocker silently kills the popup route, so the button just does nothing.
        var frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        document.body.appendChild(frame);

        var fired = false;
        var cleaned = false;

        function cleanup() {
            if (cleaned) return;
            cleaned = true;
            setTimeout(function() {
                if (frame && frame.parentNode) frame.parentNode.removeChild(frame);
            }, 500);
        }

        function doPrint() {
            if (fired) return;
            fired = true;
            var fw = frame.contentWindow;
            if (!fw) { cleanup(); return; }
            try { fw.addEventListener('afterprint', cleanup); } catch (err) {}
            try {
                fw.focus();
                fw.print();
            } catch (err) {
                console.error('Print failed', err);
            }
            // afterprint is not fired by every browser - always reclaim the iframe.
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

    document.addEventListener('shown.bs.tab', function (e) {
        var t = e.target;
        if (!t || !t.getAttribute || t.getAttribute('href') !== '#tab-setup') return;
        var wrap = document.querySelector('#sellingVouchersTable_wrapper');
        if (!wrap || wrap.offsetParent === null) return;
        var inp = wrap.querySelector('.dataTables_filter input[type="search"]');
        if (inp) window.setTimeout(function () { inp.focus(); }, 120);
    });
});
</script>
@endsection