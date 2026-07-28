@extends('admin.layouts.master')
@section('title', 'Sub-Category Item Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Sub-Category Item Master — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .itemsub-master-page .itemsub-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    /* Download / Print bar */
    .itemsub-master-page .itemsub-master-export-btn {
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

    .itemsub-master-page .itemsub-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .itemsub-master-page .itemsub-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Native filter <select> pill (the design system styles only the Choices variant) */
    .itemsub-master-page .programme-dt-filter-select .form-select {
        min-height: 40px;
        border-radius: 8px;
        border: 1px solid #d0d5dd;
        font-size: .9375rem;
        color: #344054;
        box-shadow: none;
    }

    .itemsub-master-page .programme-dt-filter-select .form-select:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    /* Collapse the leftover (now-emptied) DataTables control wrappers once the
       global enhancer has relocated search / pagination into the slots below. */
    .itemsub-master-page .dt-top:empty,
    .itemsub-master-page .dt-foot:empty { display: none; margin: 0; }

    /* Column visibility is presented as a programme-style modal, so the mess
       Column-manager's own injected dropdown stays hidden. */
    .itemsub-master-page .mess-col-manager-dropdown { display: none !important; }

    #itemsubColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    #itemsubColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }

    #itemsubColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .itemsub-master-page .itemsub-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }

    /* Row actions — icon over label (blue Edit, red Delete), matching the mock. */
    .itemsub-master-page .itemsub-actions { gap: 1.1rem; }
    .itemsub-master-page .itemsub-actions form { margin: 0; }

    .itemsub-master-page .itemsub-action-btn {
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

    .itemsub-master-page .itemsub-action-btn i { font-size: 1.2rem; line-height: 1; }
    .itemsub-master-page .itemsub-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .itemsub-master-page .itemsub-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .itemsub-master-page .itemsub-action-btn:hover { opacity: .78; }

    /* Pagination → arrows + numbers only (drop First/Last, swap word labels). */
    .itemsub-master-page .programme-dt-footer .paginate_button.first,
    .itemsub-master-page .programme-dt-footer .paginate_button.last { display: none; }

    .itemsub-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .itemsub-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }

    .itemsub-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .itemsub-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

    /* ── Add / Edit Sub-Category Item modals (clean rounded card, red Cancel + blue submit) ── */
    .itemsub-modal .modal-dialog { max-height: calc(100vh - 2rem); }

    .itemsub-modal .modal-content {
        border-radius: 16px;
        box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
        overflow: hidden;
        max-height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
    }

    .itemsub-modal .modal-header,
    .itemsub-modal .modal-footer { flex-shrink: 0; }

    .itemsub-modal .modal-header {
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid var(--ds-line, #eef2f6);
    }

    .itemsub-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .itemsub-modal .modal-body {
        padding: 1.25rem 1.5rem;
        overflow-y: auto;
    }

    .itemsub-modal .itemsub-modal-label {
        font-weight: 600;
        font-size: .875rem;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .375rem;
    }

    .itemsub-modal .itemsub-modal-control {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .itemsub-modal textarea.itemsub-modal-control { min-height: 92px; }
    .itemsub-modal .itemsub-modal-control::placeholder { color: #98a2b3; }
    .itemsub-modal .itemsub-modal-control[readonly] { background: #f2f4f7; color: #667085; }

    .itemsub-modal .itemsub-modal-control:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .itemsub-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid var(--ds-line, #eef2f6);
    }

    .itemsub-modal .itemsub-modal-cancel,
    .itemsub-modal .itemsub-modal-submit {
        min-height: 44px;
        border-radius: 8px;
        padding: .5rem 1.5rem;
        font-weight: 600;
        font-size: .9375rem;
    }

    .itemsub-modal .itemsub-modal-cancel {
        color: var(--ds-secondary, #d92d20);
        background: #fff;
        border: 1px solid var(--ds-secondary, #d92d20);
    }

    .itemsub-modal .itemsub-modal-cancel:hover {
        background: #fff5f5;
        color: var(--ds-secondary, #d92d20);
    }
</style>
@endpush

@section('content')
@php
    $selectedCategoryId = $categoryIdFilter ?? request('category_id', '');
    $canDeleteItemSubcategory = hasRole('Super Admin') || hasRole('Mess-Admin');
@endphp
<div class="container-fluid itemsub-master-page">
    <x-breadcrum title="Sub-Category Item Master" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Add Sub-Category Item</span>
        </button>
    </x-breadcrum>

    {{-- Success feedback is rendered as the global green toast — see mess.partials.delete-confirm --}}

    {{-- Download / Print bar (branded server-side exports — see admin.mess.itemsubcategories.export) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn itemsub-master-export-btn border-0" id="itemsubDownloadBtn">
            <i class="material-symbols-rounded">download</i>
            <span>Download</span>
        </button>
        <button type="button" class="btn itemsub-master-export-btn border-0" id="itemsubPrintBtn">
            <i class="material-symbols-rounded">print</i>
            <span>Print</span>
        </button>
    </div>

    <div class="card itemsub-master-card border-0">
        <div class="card-body">
            {{-- Toolbar: Category filter (left) + Columns modal trigger & search (right) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <form method="GET" action="{{ route('admin.mess.itemsubcategories.index') }}" id="itemSubFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="category_id" id="filter_category_id" class="form-select" aria-label="Filter by category"
                                onchange="document.getElementById('itemSubFilterForm').submit()">
                            <option value="">Category type</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) $selectedCategoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn programme-dt-btn-reset d-inline-flex align-items-center justify-content-center">Reset</a>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnItemsubColumns"
                            data-bs-toggle="modal" data-bs-target="#itemsubColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="itemSubcategoriesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="itemSubcategoriesTable" class="table programme-dt-table align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Category Name</th>
                                <th>Item Name</th>
                                <th>Item Code</th>
                                <th>Unit Measurement</th>
                                <th>Alert Qty</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="itemSubcategoriesTable"></div>
        </div>
    </div>
</div>

{{-- Create Sub-Category Item Modal --}}
<div class="modal fade itemsub-modal" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title" id="createItemSubcategoryModalLabel">Add Sub-Category Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="create_category_id" class="form-select itemsub-modal-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="create_item_name" class="form-control itemsub-modal-control" required
                                   value="{{ old('item_name') }}" pattern="[a-zA-Z0-9\s\-]+" autocomplete="off" placeholder="e.g. Egg Bhurji">
                            <div class="text-danger small mt-1" id="create_item_name_error" role="alert">@error('item_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control itemsub-modal-control" value="" readonly placeholder="Auto-generated on save">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="create_unit_measurement" class="form-control itemsub-modal-control"
                                   value="{{ old('unit_measurement') }}" placeholder="e.g. kg, pkt" required
                                   pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_unit_measurement_error" role="alert">@error('unit_measurement'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Alert Quantity</label>
                            <input type="number" name="alert_quantity" class="form-control itemsub-modal-control" step="0.0001" min="0" value="{{ old('alert_quantity') }}" placeholder="e.g. 5">
                            @error('alert_quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Status</label>
                            <select name="status" id="create_status" class="form-select itemsub-modal-control">
                                <option value="" {{ old('status') ? '' : 'selected' }}>Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label itemsub-modal-label">Description</label>
                            <textarea name="description" class="form-control itemsub-modal-control" rows="3" placeholder="e.g. Lorem ipsum dolor sit amet">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn itemsub-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary itemsub-modal-submit">Add Sub-Category Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Sub-Category Item Modal --}}
<div class="modal fade itemsub-modal" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <form id="editItemSubcategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title" id="editItemSubcategoryModalLabel">Edit Sub-Category Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-select itemsub-modal-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control itemsub-modal-control" required
                                   pattern="[a-zA-Z0-9\s\-]+" autocomplete="off" placeholder="e.g. Egg Bhurji">
                            <div class="text-danger small mt-1" id="edit_item_name_error" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" id="edit_item_code_display" class="form-control itemsub-modal-control" readonly placeholder="e.g. ITM9722">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="edit_unit_measurement" class="form-control itemsub-modal-control" required
                                   pattern="[a-zA-Z0-9\s\-\/\.]+" autocomplete="off" placeholder="e.g. kg, pkt">
                            <div class="text-danger small mt-1" id="edit_unit_measurement_error" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Alert Quantity</label>
                            <input type="number" name="alert_quantity" id="edit_alert_quantity" class="form-control itemsub-modal-control" step="0.0001" min="0" placeholder="e.g. 5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label itemsub-modal-label">Status</label>
                            <select name="status" id="edit_status" class="form-select itemsub-modal-control">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label itemsub-modal-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control itemsub-modal-control" rows="3" placeholder="e.g. Lorem ipsum dolor sit amet"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn itemsub-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary itemsub-modal-submit">Update Sub-Category Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Column Visibility Modal (programme/attendance style) --}}
<div class="modal fade" id="itemsubColumnVisibilityModal" tabindex="-1" aria-labelledby="itemsubColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="itemsubColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="itemsubColumnToggleGrid"></div>
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
    'tableId' => 'itemSubcategoriesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 2,
    'actionColumnIndex' => 7,
    'infoLabel' => 'items',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.itemsubcategories.index'),
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
])

@push('scripts')
{{-- Download / Print → branded server-side report (admin.mess.itemsubcategories.export).
     Passes the live search + Category filter + chosen columns so the report matches
     the screen. Print opens the PDF inline for printing. --}}
<script>
(function () {
    var TABLE_ID = 'itemSubcategoriesTable';
    var BASE = @json(route('admin.mess.itemsubcategories.export'));
    var $ = window.jQuery;

    function buildUrl(format, inline) {
        var params = ['format=' + format];

        var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
        var search = dt ? dt.search() : '';
        if (search) params.push('search=' + encodeURIComponent(search));

        var catSel = document.getElementById('filter_category_id');
        if (catSel && catSel.value) params.push('category_id=' + encodeURIComponent(catSel.value));

        var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
            ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
        if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));

        if (inline) params.push('inline=1');
        return BASE + '?' + params.join('&');
    }

    var downloadBtn = document.getElementById('itemsubDownloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
    }
    var printBtn = document.getElementById('itemsubPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
    }
})();
</script>
{{-- Column Visibility modal ⇄ mess Column-manager bridge --}}
<script>
(function () {
    var TABLE_ID = 'itemSubcategoriesTable';
    var $ = window.jQuery;
    var grid = document.getElementById('itemsubColumnToggleGrid');
    var modalEl = document.getElementById('itemsubColumnVisibilityModal');
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
            var inputId = 'itemsubcolvis_' + col.index;
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
{{-- Modal validation + edit-modal population (native selects) --}}
<script>
(function () {
    function initItemSubcategoryScripts() {
        var itemNameRegex = /^[a-zA-Z0-9\s\-]+$/;
        var unitMeasurementRegex = /^[a-zA-Z0-9\s\-\/\.]+$/;
        var itemNameMessage = 'Item name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
        var unitMeasurementMessage = 'Unit measurement may only contain letters, numbers, spaces, hyphens, slashes and periods. Special characters are not allowed.';

        function validateItemName(value) {
            if (typeof value !== 'string') return { valid: true };
            value = value.trim();
            if (value.length === 0) return { valid: false, message: 'Item name is required.' };
            return itemNameRegex.test(value) ? { valid: true } : { valid: false, message: itemNameMessage };
        }
        function validateUnitMeasurement(value) {
            if (typeof value !== 'string') return { valid: true };
            value = value.trim();
            if (value.length === 0) return { valid: false, message: 'Unit measurement is required.' };
            return unitMeasurementRegex.test(value) ? { valid: true } : { valid: false, message: unitMeasurementMessage };
        }
        function showLiveError(inputEl, errorEl, result) {
            if (!inputEl || !errorEl) return;
            if (result.valid) { inputEl.classList.remove('is-invalid'); errorEl.textContent = ''; }
            else { inputEl.classList.add('is-invalid'); errorEl.textContent = result.message; }
        }
        function attachLiveValidation(inputId, errorId, validateFn) {
            var input = document.getElementById(inputId);
            var errorEl = document.getElementById(errorId);
            if (!input || !errorEl) return;
            function run() { showLiveError(input, errorEl, validateFn(input.value)); }
            input.addEventListener('input', run);
            input.addEventListener('blur', run);
        }

        attachLiveValidation('create_item_name', 'create_item_name_error', validateItemName);
        attachLiveValidation('create_unit_measurement', 'create_unit_measurement_error', validateUnitMeasurement);
        attachLiveValidation('edit_item_name', 'edit_item_name_error', validateItemName);
        attachLiveValidation('edit_unit_measurement', 'edit_unit_measurement_error', validateUnitMeasurement);

        var createForm = document.querySelector('#createItemSubcategoryModal form');
        if (createForm) {
            createForm.addEventListener('submit', function (e) {
                var r1 = validateItemName(document.getElementById('create_item_name').value);
                var r2 = validateUnitMeasurement(document.getElementById('create_unit_measurement').value);
                showLiveError(document.getElementById('create_item_name'), document.getElementById('create_item_name_error'), r1);
                showLiveError(document.getElementById('create_unit_measurement'), document.getElementById('create_unit_measurement_error'), r2);
                if (!r1.valid || !r2.valid) { e.preventDefault(); return; }
                var btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) { btn.disabled = true; btn.textContent = 'Saving...'; }
            });
        }

        var editForm = document.getElementById('editItemSubcategoryForm');
        if (editForm) {
            editForm.addEventListener('submit', function (e) {
                var r1 = validateItemName(document.getElementById('edit_item_name').value);
                var r2 = validateUnitMeasurement(document.getElementById('edit_unit_measurement').value);
                showLiveError(document.getElementById('edit_item_name'), document.getElementById('edit_item_name_error'), r1);
                showLiveError(document.getElementById('edit_unit_measurement'), document.getElementById('edit_unit_measurement_error'), r2);
                if (!r1.valid || !r2.valid) { e.preventDefault(); return; }
                var btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) { btn.disabled = true; btn.textContent = 'Updating...'; }
            });
        }

        var createModal = document.getElementById('createItemSubcategoryModal');
        if (createModal) {
            createModal.addEventListener('hidden.bs.modal', function () {
                var form = createModal.querySelector('form');
                if (form) form.reset();
                ['create_item_name_error', 'create_unit_measurement_error'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.textContent = '';
                });
                ['create_item_name', 'create_unit_measurement'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.classList.remove('is-invalid');
                });
            });
        }

        var editModal = document.getElementById('editItemSubcategoryModal');
        if (editModal) {
            editModal.addEventListener('hidden.bs.modal', function () {
                ['edit_item_name_error', 'edit_unit_measurement_error'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.textContent = '';
                });
                ['edit_item_name', 'edit_unit_measurement'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.classList.remove('is-invalid');
                });
            });
        }

        document.addEventListener('mousedown', function (e) {
            var btn = e.target.closest('.btn-edit-itemsubcategory');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('editItemSubcategoryForm').action = '{{ url("admin/mess/itemsubcategories") }}/' + btn.getAttribute('data-id');
            document.getElementById('edit_category_id').value = (btn.getAttribute('data-category-id') || '').toString().trim();
            document.getElementById('edit_item_name').value = btn.getAttribute('data-item-name') || '';
            document.getElementById('edit_item_code_display').value = btn.getAttribute('data-item-code') || '-';
            document.getElementById('edit_unit_measurement').value = btn.getAttribute('data-unit-measurement') || '';
            document.getElementById('edit_alert_quantity').value = btn.getAttribute('data-alert-quantity') || '';
            document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
            document.getElementById('edit_status').value = (btn.getAttribute('data-status') || 'active').toString().trim();
            new bootstrap.Modal(document.getElementById('editItemSubcategoryModal')).show();
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initItemSubcategoryScripts);
    } else {
        initItemSubcategoryScripts();
    }
})();
</script>
@endpush
@endsection
