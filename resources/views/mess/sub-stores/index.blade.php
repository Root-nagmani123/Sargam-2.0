@extends('admin.layouts.master')
@section('title', 'Sub Store Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Sub Store Master — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .substore-master-page .substore-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    /* Download / Print bar */
    .substore-master-page .substore-master-export-btn {
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

    .substore-master-page .substore-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .substore-master-page .substore-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Collapse the leftover (now-emptied) DataTables control wrappers once the
       global enhancer has relocated search / pagination into the slots below. */
    .substore-master-page .dt-top:empty,
    .substore-master-page .dt-foot:empty { display: none; margin: 0; }

    /* Column visibility is presented as a programme-style modal, so the mess
       Column-manager's own injected dropdown stays hidden. */
    .substore-master-page .mess-col-manager-dropdown { display: none !important; }

    #substoreColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    #substoreColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }

    #substoreColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .substore-master-page .substore-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }

    /* Row actions — icon over label (blue Edit, red Delete), matching the mock. */
    .substore-master-page .substore-actions { gap: 1.1rem; }
    .substore-master-page .substore-actions form { margin: 0; }

    .substore-master-page .substore-action-btn {
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

    .substore-master-page .substore-action-btn i { font-size: 1.2rem; line-height: 1; }
    .substore-master-page .substore-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .substore-master-page .substore-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .substore-master-page .substore-action-btn:hover { opacity: .78; }

    /* Pagination → arrows + numbers only (drop First/Last, swap word labels). */
    .substore-master-page .programme-dt-footer .paginate_button.first,
    .substore-master-page .programme-dt-footer .paginate_button.last { display: none; }

    .substore-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .substore-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }

    .substore-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .substore-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

    /* ── Add / Edit Sub-Store modals (clean rounded card, red Cancel + blue submit) ── */
    .substore-modal .modal-content {
        border-radius: 16px;
        box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
        overflow: hidden;
    }

    .substore-modal .modal-header {
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid var(--ds-line, #eef2f6);
    }

    .substore-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .substore-modal .modal-body { padding: 1.25rem 1.5rem; }

    .substore-modal .substore-modal-label {
        font-weight: 600;
        font-size: .875rem;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .375rem;
    }

    .substore-modal .substore-modal-control {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .substore-modal .substore-modal-control::placeholder { color: #98a2b3; }

    .substore-modal .substore-modal-control:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .substore-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid var(--ds-line, #eef2f6);
    }

    .substore-modal .substore-modal-cancel,
    .substore-modal .substore-modal-submit {
        min-height: 44px;
        border-radius: 8px;
        padding: .5rem 1.5rem;
        font-weight: 600;
        font-size: .9375rem;
    }

    .substore-modal .substore-modal-cancel {
        color: var(--ds-secondary, #d92d20);
        background: #fff;
        border: 1px solid var(--ds-secondary, #d92d20);
    }

    .substore-modal .substore-modal-cancel:hover {
        background: #fff5f5;
        color: var(--ds-secondary, #d92d20);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Sub Store Master"></x-breadcrum>
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header border-0 bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 fw-semibold">Sub Store Master</h4>
                    <p class="mb-0 text-muted small">Manage all mess sub stores in one place.</p>
                </div>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createSubStoreModal">
                    <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
                    <span>Add Sub Store</span>
                </button>
                <div class="programme-dt-search" data-dt-search-for="subStoresTable"></div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

            <div class="table-responsive">
                <table id="subStoresTable" class="table  align-middle w-100">
                    <thead>
                        <tr>
                            <th>Sub Store Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Sub Store Modal --}}
<div class="modal fade substore-modal" id="createSubStoreModal" tabindex="-1" aria-labelledby="createSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('admin.mess.sub-stores.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title" id="createSubStoreModalLabel">Add Sub-Store</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label substore-modal-label">Sub Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_store_name" class="form-control substore-modal-control" required value="{{ old('sub_store_name') }}" placeholder="e.g. Kalindi Guest House">
                            @error('sub_store_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label substore-modal-label">Status</label>
                            <select name="status" class="form-select substore-modal-control">
                                <option value="" {{ old('status') ? '' : 'selected' }}>Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn substore-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary substore-modal-submit">Add Sub-Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Sub Store Modal --}}
<div class="modal fade substore-modal" id="editSubStoreModal" tabindex="-1" aria-labelledby="editSubStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form id="editSubStoreForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title" id="editSubStoreModalLabel">Edit Sub-Store</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label substore-modal-label">Sub Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="sub_store_name" id="edit_sub_store_name" class="form-control substore-modal-control" required placeholder="e.g. Kalindi Guest House">
                        </div>
                        <div class="col-12">
                            <label class="form-label substore-modal-label">Status</label>
                            <select name="status" id="edit_status" class="form-select substore-modal-control">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn substore-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary substore-modal-submit">Update Sub-Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'subStoresTable',
    'searchPlaceholder' => 'Search sub stores...',
    'orderColumn' => 0,
    'actionColumnIndex' => 2,
    'infoLabel' => 'sub stores',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.sub-stores.index'),
])
@push('scripts')
{{-- Download / Print → branded server-side report (admin.mess.sub-stores.export). --}}
<script>
(function () {
    var TABLE_ID = 'subStoresTable';
    var BASE = @json(route('admin.mess.sub-stores.export'));
    var $ = window.jQuery;

    function buildUrl(format, inline) {
        var params = ['format=' + format];
        var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
        var search = dt ? dt.search() : '';
        if (search) params.push('search=' + encodeURIComponent(search));
        var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
            ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
        if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));
        if (inline) params.push('inline=1');
        return BASE + '?' + params.join('&');
    }

    var downloadBtn = document.getElementById('substoreDownloadBtn');
    if (downloadBtn) downloadBtn.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
    var printBtn = document.getElementById('substorePrintBtn');
    if (printBtn) printBtn.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
})();
</script>
{{-- Column Visibility modal ⇄ mess Column-manager bridge --}}
<script>
(function () {
    var TABLE_ID = 'subStoresTable';
    var $ = window.jQuery;
    var grid = document.getElementById('substoreColumnToggleGrid');
    var modalEl = document.getElementById('substoreColumnVisibilityModal');
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
            var inputId = 'substorecolvis_' + col.index;
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
{{-- Edit modal population --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-substore');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editSubStoreForm').action = '{{ url("admin/mess/sub-stores") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_sub_store_name').value = btn.getAttribute('data-sub-store-name') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editSubStoreModal')).show();
    }, true);
});
</script>
@endpush
@endsection
