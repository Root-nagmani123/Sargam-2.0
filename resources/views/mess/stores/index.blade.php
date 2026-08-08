@extends('admin.layouts.master')
@section('title', 'Mess Stores')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Store Master — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .store-master-page .store-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    /* Download / Print bar (matches the Attendance download button) */
    .store-master-page .store-master-export-btn {
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

    .store-master-page .store-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .store-master-page .store-master-export-btn i {
        font-size: 1.15rem;
        line-height: 1;
    }

    /* Collapse the leftover (now-emptied) DataTables control wrappers once the
       global enhancer has relocated search / pagination into the slots below. */
    .store-master-page .dt-top:empty,
    .store-master-page .dt-foot:empty { display: none; margin: 0; }

    /* Column visibility is presented as a programme-style modal (see below), so
       the mess Column-manager's own injected dropdown stays hidden — it remains
       the underlying state engine that keeps Download/Print column-sync correct. */
    .store-master-page .mess-col-manager-dropdown { display: none !important; }

    /* Column Visibility modal grid tiles (mirrors programme / attendance). */
    #storesColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    #storesColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }

    #storesColumnToggleGrid .colvis-item .form-check-input {
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Store name secondary line (code) */
    .store-master-page .store-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }
    .store-master-page .store-name-code { font-size: .8125rem; color: var(--ds-ink-muted, #667085); }

    /* Row actions — icon over label (blue Edit, red Delete), matching the mock. */
    .store-master-page .store-actions { gap: 1.25rem; }
    .store-master-page .store-actions form { margin: 0; }

    .store-master-page .store-action-btn {
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
    }

    .store-master-page .store-action-btn i { font-size: 1.2rem; line-height: 1; }
    .store-master-page .store-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .store-master-page .store-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .store-master-page .store-action-btn:hover { opacity: .78; }

    /* Pagination → arrows + numbers only (drop First/Last, swap word labels).
       !important is required, not sloppiness: custom.css sets
       `.programme-dt-footer .paginate_button { display: inline-flex !important }`,
       which outranks any plain rule here however specific. */
    .store-master-page .programme-dt-footer .paginate_button.first,
    .store-master-page .programme-dt-footer .paginate_button.last { display: none !important; }

    .store-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .store-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }

    .store-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .store-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

    /* ── Add / Edit Store modals (clean rounded card, labelled fields, red Cancel + blue submit) ── */
    .store-modal .modal-content {
        border-radius: 16px;
        box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
        overflow: hidden;
    }

    .store-modal .modal-header {
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid var(--ds-line, #eef2f6);
    }

    .store-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .store-modal .modal-body { padding: 1.25rem 1.5rem; }

    .store-modal .store-modal-label {
        font-weight: 600;
        font-size: .875rem;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .375rem;
    }

    .store-modal .store-modal-control {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .store-modal .store-modal-control::placeholder { color: #98a2b3; }

    .store-modal .store-modal-control:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .store-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid var(--ds-line, #eef2f6);
    }

    .store-modal .store-modal-cancel,
    .store-modal .store-modal-submit {
        min-height: 44px;
        border-radius: 8px;
        padding: .5rem 1.75rem;
        font-weight: 600;
        font-size: .9375rem;
    }

    .store-modal .store-modal-cancel {
        color: var(--ds-secondary, #d92d20);
        background: #fff;
        border: 1px solid var(--ds-secondary, #d92d20);
    }

    .store-modal .store-modal-cancel:hover {
        background: #fff5f5;
        color: var(--ds-secondary, #d92d20);
    }
</style>
@endpush

@section('content')
@php
    $storeTypes = \App\Models\Mess\Store::storeTypes();
@endphp
<div class="container-fluid store-master-page">
    <x-breadcrum title="Store Master" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createStoreModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Add Store</span>
        </button>
    </x-breadcrum>

    {{-- Success feedback is rendered as the global green toast — see mess.partials.delete-confirm --}}

    {{-- Status pills (left) + Download / Print (right) — above the card, per §1 of
         docs/new-design-index-page.md. The pills are this grid's only filter:
         Store::storeTypes() holds a single value, so a type select would be dead UI. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter stores by status">
            @foreach(['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                @php $isOn = (string) request('status', '') === (string) $value; @endphp
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $isOn ? 'active' : '' }}"
                            data-store-status="{{ $value }}"
                            aria-pressed="{{ $isOn ? 'true' : 'false' }}"
                            @if($isOn) aria-current="true" @endif>{{ $label }}</button>
                </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn store-master-export-btn border-0" id="storesDownloadBtn">
            <i class="material-symbols-rounded">download</i>
            <span>Download</span>
        </button>
        <button type="button" class="btn store-master-export-btn border-0" id="storesPrintBtn">
            <i class="material-symbols-rounded">print</i>
            <span>Print</span>
        </button>
        </div>
    </div>

    <div class="card store-master-card border-0">
        <div class="card-body">
            {{-- Toolbar: reset on the left, Columns + search on the right (§2).
                 The global enhancer relocates DataTables' own search box into the slot. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <button type="button" class="btn programme-dt-btn-reset {{ request('status') ? '' : 'd-none' }}"
                            id="storesResetFilters">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnStoresColumns"
                            data-bs-toggle="modal" data-bs-target="#storesColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="storesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="storesTable" class="table programme-dt-table text-nowrap align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Store Name</th>
                                <th scope="col">Store Type</th>
                                <th scope="col">Location</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="storesTable"></div>
        </div>
    </div>
</div>

{{-- Column Visibility Modal (programme/attendance style) --}}
<div class="modal fade" id="storesColumnVisibilityModal" tabindex="-1" aria-labelledby="storesColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="storesColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="storesColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Create Store Modal --}}
<div class="modal fade store-modal" id="createStoreModal" tabindex="-1" aria-labelledby="createStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('admin.mess.stores.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title" id="createStoreModalLabel">Add Store</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label store-modal-label">Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" id="create_store_name" class="form-control store-modal-control" required
                                   value="{{ old('store_name') }}"
                                   placeholder="e.g. LBSNAA Store"
                                   pattern="[a-zA-Z0-9\s\-]+"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="create_store_name_error" role="alert">@error('store_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Store Type <span class="text-danger">*</span></label>
                            <select name="store_type" class="form-select store-modal-control">
                                <option value="" {{ old('store_type') ? '' : 'selected' }}>Select Type</option>
                                @foreach($storeTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('store_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('store_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Location</label>
                            <input type="text" name="location" id="create_location" class="form-control store-modal-control"
                                   value="{{ old('location') }}"
                                   placeholder="e.g. LBSNAA Campus"
                                   pattern="[a-zA-Z0-9\s\-\.\,]*"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="create_location_error" role="alert">@error('location'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Status</label>
                            <select name="status" class="form-select store-modal-control">
                                <option value="" {{ old('status') ? '' : 'selected' }}>Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn store-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary store-modal-submit">Add Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Store Modal --}}
<div class="modal fade store-modal" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form id="editStoreForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title" id="editStoreModalLabel">Edit Store</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label store-modal-label">Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" id="edit_store_name" class="form-control store-modal-control" required
                                   placeholder="e.g. LBSNAA Store"
                                   pattern="[a-zA-Z0-9\s\-]+"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_store_name_error" role="alert"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Store Type <span class="text-danger">*</span></label>
                            <select name="store_type" id="edit_store_type" class="form-select store-modal-control">
                                <option value="">Select Type</option>
                                @foreach($storeTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control store-modal-control"
                                   placeholder="e.g. LBSNAA Campus"
                                   pattern="[a-zA-Z0-9\s\-\.\,]*"
                                   autocomplete="off">
                            <div class="text-danger small mt-1" id="edit_location_error" role="alert"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label store-modal-label">Status</label>
                            <select name="status" id="edit_status" class="form-select store-modal-control">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn store-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary store-modal-submit">Update Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Branded delete-confirmation dialog + global success toast --}}
@include('mess.partials.delete-confirm')

@include('components.mess-master-datatables', [
    'tableId' => 'storesTable',
    'searchPlaceholder' => 'Search stores...',
    'orderColumn' => 0,
    'orderDir' => 'desc',
    'actionColumnIndex' => 5,
    'infoLabel' => 'stores',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.stores.index'),
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
    'serverSideColumnDefs' => [
        ['className' => 'text-center', 'targets' => [4, 5]],
    ],
])
@push('scripts')
{{-- Download / Print → branded server-side report (admin.mess.stores.export).
     Passes the live search term + the Column-Visibility-chosen columns so the
     report matches what's on screen. Print opens the PDF inline for printing. --}}
<script>
(function () {
    var TABLE_ID = 'storesTable';
    var BASE = @json(route('admin.mess.stores.export'));
    var $ = window.jQuery;

    function buildUrl(format, inline) {
        var params = ['format=' + format];

        var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
        var search = dt ? dt.search() : '';
        if (search) params.push('search=' + encodeURIComponent(search));

        // Same status pill the grid is showing, so the report matches the screen.
        var status = new URLSearchParams(window.location.search).get('status') || '';
        if (status) params.push('status=' + encodeURIComponent(status));

        var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
            ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
        if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));

        if (inline) params.push('inline=1');
        return BASE + '?' + params.join('&');
    }

    var downloadBtn = document.getElementById('storesDownloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            window.location.href = buildUrl('excel', false);
        });
    }

    var printBtn = document.getElementById('storesPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.open(buildUrl('pdf', true), '_blank');
        });
    }
})();
</script>
{{-- Status pills + Reset. The mess DataTable component builds its ajax URL from
     window.location.search, so pushing the pill into the URL and reloading the
     table is all that is needed — no page navigation, and the filter survives a
     refresh or a shared link. --}}
<script>
(function () {
    var TABLE_ID = 'storesTable';
    var $ = window.jQuery;

    function dt() {
        return ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
    }

    function applyStatus(status) {
        var url = new URL(window.location.href);
        if (status) { url.searchParams.set('status', status); }
        else { url.searchParams.delete('status'); }
        window.history.replaceState({}, '', url.toString());

        document.querySelectorAll('[data-store-status]').forEach(function (btn) {
            var on = btn.getAttribute('data-store-status') === status;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (on) { btn.setAttribute('aria-current', 'true'); } else { btn.removeAttribute('aria-current'); }
        });

        var reset = document.getElementById('storesResetFilters');
        if (reset) { reset.classList.toggle('d-none', !status); }

        var api = dt();
        if (api) { api.page(0).ajax.reload(null, false); }
    }

    document.querySelectorAll('[data-store-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyStatus(btn.getAttribute('data-store-status') || '');
        });
    });

    var reset = document.getElementById('storesResetFilters');
    if (reset) {
        reset.addEventListener('click', function () {
            var api = dt();
            if (api) { api.search(''); }
            applyStatus('');
        });
    }
})();
</script>
{{-- Column Visibility modal ⇄ mess Column-manager bridge.
     The mess Column-manager owns the visibility state (and drives export column
     sync); this modal is just its programme-styled UI. --}}
<script>
(function () {
    var TABLE_ID = 'storesTable';
    var $ = window.jQuery;
    var grid = document.getElementById('storesColumnToggleGrid');
    var modalEl = document.getElementById('storesColumnVisibilityModal');
    if (!$ || !grid || !modalEl) return;

    function getMgr() {
        return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function')
            ? window.MessColumnManager.get(TABLE_ID)
            : null;
    }

    function visibleCount(mgr) {
        return mgr.baseColumns.filter(function (c) {
            return mgr.state.visibility[String(c.index)] !== false;
        }).length;
    }

    function buildGrid() {
        var mgr = getMgr();
        if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;

        grid.innerHTML = '';
        (mgr.state.order || []).forEach(function (idx) {
            var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
            if (!col) return;

            var isVisible = mgr.state.visibility[String(col.index)] !== false;
            var inputId = 'storescolvis_' + col.index;

            var cell = document.createElement('div');
            cell.className = 'col-12 col-sm-6 col-md-4';

            var label = document.createElement('label');
            label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
            label.setAttribute('for', inputId);

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input m-0';
            cb.id = inputId;
            cb.checked = isVisible;
            if (col.locked) cb.disabled = true;

            cb.addEventListener('change', function () {
                var m = getMgr();
                if (!m) return;
                if (!cb.checked && visibleCount(m) <= 1) {
                    cb.checked = true;
                    window.alert('At least one column must remain visible.');
                    return;
                }
                m.state.visibility[String(col.index)] = cb.checked;
                m.saveState();
                m.apply();
            });

            var span = document.createElement('span');
            span.textContent = col.label;

            label.appendChild(cb);
            label.appendChild(span);
            cell.appendChild(label);
            grid.appendChild(cell);
        });
        return true;
    }

    modalEl.addEventListener('show.bs.modal', function () {
        if (buildGrid()) return;
        // Column-manager still initialising — retry briefly.
        var tries = 0;
        var timer = setInterval(function () {
            if (buildGrid() || ++tries > 20) clearInterval(timer);
        }, 100);
    });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation rules (must match server: StoreController)
    var storeNameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var locationRegex = /^[a-zA-Z0-9\s\-\.\,]*$/;
    var storeNameMessage = 'Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var locationMessage = 'Location may only contain letters, numbers, spaces, hyphens, commas and periods. Special characters are not allowed.';

    function validateStoreName(value) {
        if (typeof value !== 'string') return { valid: true };
        value = value.trim();
        if (value.length === 0) return { valid: false, message: 'Store name is required.' };
        return storeNameRegex.test(value) ? { valid: true } : { valid: false, message: storeNameMessage };
    }

    function validateLocation(value) {
        if (typeof value !== 'string') return { valid: true };
        return locationRegex.test(value) ? { valid: true } : { valid: false, message: locationMessage };
    }

    function showLiveError(inputEl, errorEl, result) {
        if (result.valid) {
            inputEl.classList.remove('is-invalid');
            errorEl.textContent = '';
        } else {
            inputEl.classList.add('is-invalid');
            errorEl.textContent = result.message;
        }
    }

    function attachLiveValidation(inputId, errorId, validateFn) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;
        function run() {
            showLiveError(input, errorEl, validateFn(input.value));
        }
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
    }

    // Create modal: real-time validation
    attachLiveValidation('create_store_name', 'create_store_name_error', validateStoreName);
    attachLiveValidation('create_location', 'create_location_error', validateLocation);

    // Edit modal: real-time validation
    attachLiveValidation('edit_store_name', 'edit_store_name_error', validateStoreName);
    attachLiveValidation('edit_location', 'edit_location_error', validateLocation);

    // Create form: prevent submit if store name or location invalid
    var createForm = document.querySelector('#createStoreModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var nameResult = validateStoreName(document.getElementById('create_store_name').value);
            var locResult = validateLocation(document.getElementById('create_location').value);
            showLiveError(document.getElementById('create_store_name'), document.getElementById('create_store_name_error'), nameResult);
            showLiveError(document.getElementById('create_location'), document.getElementById('create_location_error'), locResult);
            if (!nameResult.valid || !locResult.valid) {
                e.preventDefault();
            }
        });
    }

    // Edit form: prevent submit if store name or location invalid
    var editForm = document.getElementById('editStoreForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var nameResult = validateStoreName(document.getElementById('edit_store_name').value);
            var locResult = validateLocation(document.getElementById('edit_location').value);
            showLiveError(document.getElementById('edit_store_name'), document.getElementById('edit_store_name_error'), nameResult);
            showLiveError(document.getElementById('edit_location'), document.getElementById('edit_location_error'), locResult);
            if (!nameResult.valid || !locResult.valid) {
                e.preventDefault();
            }
        });
    }

    // Reset Add Store form when modal is closed (Cancel or backdrop) so next open shows a clean form
    var createStoreModal = document.getElementById('createStoreModal');
    if (createStoreModal) {
        createStoreModal.addEventListener('hidden.bs.modal', function() {
            var form = createStoreModal.querySelector('form');
            if (form) {
                form.reset();
                // Reset to the placeholder options ("Select Type" / "Select Status")
                // so the modal reopens looking like the design; the server defaults
                // store_type→mess and status→active when left blank.
                var storeTypeSelect = form.querySelector('select[name="store_type"]');
                if (storeTypeSelect) storeTypeSelect.value = '';
                var statusSelect = form.querySelector('select[name="status"]');
                if (statusSelect) statusSelect.value = '';
            }
            document.getElementById('create_store_name_error').textContent = '';
            document.getElementById('create_location_error').textContent = '';
            var createNameInput = document.getElementById('create_store_name');
            var createLocInput = document.getElementById('create_location');
            if (createNameInput) createNameInput.classList.remove('is-invalid');
            if (createLocInput) createLocInput.classList.remove('is-invalid');
        });
    }

    // When Add Store modal is shown, run validation once so server-rendered errors get is-invalid styling
    if (createStoreModal) {
        createStoreModal.addEventListener('shown.bs.modal', function() {
            var nameInput = document.getElementById('create_store_name');
            var locInput = document.getElementById('create_location');
            showLiveError(nameInput, document.getElementById('create_store_name_error'), validateStoreName(nameInput.value));
            showLiveError(locInput, document.getElementById('create_location_error'), validateLocation(locInput.value));
        });
    }

    // Clear edit modal errors when it is hidden
    var editStoreModal = document.getElementById('editStoreModal');
    if (editStoreModal) {
        editStoreModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('edit_store_name_error').textContent = '';
            document.getElementById('edit_location_error').textContent = '';
            document.getElementById('edit_store_name').classList.remove('is-invalid');
            document.getElementById('edit_location').classList.remove('is-invalid');
        });
    }

    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-store');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editStoreForm').action = '{{ url("admin/mess/stores") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_store_name').value = btn.getAttribute('data-store-name') || '';
        var storeType = (btn.getAttribute('data-store-type') || '').trim() || 'mess';
        var typeSelect = document.getElementById('edit_store_type');
        typeSelect.value = storeType;
        if (typeSelect.value !== storeType) typeSelect.value = 'mess';
        document.getElementById('edit_location').value = btn.getAttribute('data-location') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editStoreModal')).show();
    }, true);
});
</script>
@endpush
@endsection
