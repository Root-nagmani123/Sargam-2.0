@extends('admin.layouts.master')
@section('title', 'Client Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Client Master — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .client-master-page .client-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    /* Download / Print bar */
    .client-master-page .client-master-export-btn {
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

    .client-master-page .client-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .client-master-page .client-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Collapse the leftover (now-emptied) DataTables control wrappers once the
       global enhancer has relocated search / pagination into the slots below. */
    .client-master-page .dt-top:empty,
    .client-master-page .dt-foot:empty { display: none; margin: 0; }

    /* Column visibility is presented as a programme-style modal, so the mess
       Column-manager's own injected dropdown stays hidden. */
    .client-master-page .mess-col-manager-dropdown { display: none !important; }

    #clientColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    #clientColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }

    #clientColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .client-master-page .client-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }

    /* Row actions — icon over label (blue Edit, red Delete), matching the mock. */
    .client-master-page .client-actions { gap: 1.1rem; }
    .client-master-page .client-actions form { margin: 0; }

    .client-master-page .client-action-btn {
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

    .client-master-page .client-action-btn i { font-size: 1.2rem; line-height: 1; }
    .client-master-page .client-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .client-master-page .client-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .client-master-page .client-action-btn:hover { opacity: .78; }

    /* Pagination → arrows + numbers only (drop First/Last, swap word labels). */
    .client-master-page .programme-dt-footer .paginate_button.first,
    .client-master-page .programme-dt-footer .paginate_button.last { display: none; }

    .client-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .client-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }

    .client-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .client-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

    /* ── Add / Edit Client Type modals (clean rounded card, red Cancel + blue submit) ── */
    .client-modal .modal-content {
        border-radius: 16px;
        box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
        overflow: hidden;
    }

    .client-modal .modal-header {
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid var(--ds-line, #eef2f6);
    }

    .client-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .client-modal .modal-body { padding: 1.25rem 1.5rem; }

    .client-modal .client-modal-label {
        font-weight: 600;
        font-size: .875rem;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .375rem;
    }

    .client-modal .client-modal-control {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .client-modal textarea.client-modal-control { min-height: 92px; }
    .client-modal .client-modal-control::placeholder { color: #98a2b3; }

    .client-modal .client-modal-control:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .client-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid var(--ds-line, #eef2f6);
    }

    .client-modal .client-modal-cancel,
    .client-modal .client-modal-submit {
        min-height: 44px;
        border-radius: 8px;
        padding: .5rem 1.5rem;
        font-weight: 600;
        font-size: .9375rem;
    }

    .client-modal .client-modal-cancel {
        color: var(--ds-secondary, #d92d20);
        background: #fff;
        border: 1px solid var(--ds-secondary, #d92d20);
    }

    .client-modal .client-modal-cancel:hover {
        background: #fff5f5;
        color: var(--ds-secondary, #d92d20);
    }
</style>
@endpush

@section('content')
@php
    $clientTypeOptions = \App\Models\Mess\ClientType::clientTypes();
    $canDeleteClientType = hasRole('Super Admin') || hasRole('Mess-Admin');
@endphp
<div class="container-fluid client-master-page">
    <x-breadcrum title="Client Master" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createClientTypeModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Add Client Type</span>
        </button>
    </x-breadcrum>

    {{-- Success feedback is rendered as the global green toast — see mess.partials.delete-confirm --}}

    {{-- Download / Print bar (branded server-side exports — see admin.mess.client-types.export) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn client-master-export-btn border-0" id="clientDownloadBtn">
            <i class="material-symbols-rounded">download</i>
            <span>Download</span>
        </button>
        <button type="button" class="btn client-master-export-btn border-0" id="clientPrintBtn">
            <i class="material-symbols-rounded">print</i>
            <span>Print</span>
        </button>
    </div>

    <div class="card client-master-card border-0">
        <div class="card-body">
            {{-- Toolbar: Columns modal trigger + search (the global enhancer relocates the search box here) --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
                <button type="button" class="btn programme-dt-btn-columns" id="btnClientColumns"
                        data-bs-toggle="modal" data-bs-target="#clientColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search" data-dt-search-for="clientTypesTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="clientTypesTable" class="table programme-dt-table align-middle w-100 mb-0">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Client Type</th>
                                <th>Client Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientTypes as $clientType)
                                @php $isActive = ($clientType->status ?? 'active') === 'active'; @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $clientTypeOptions[$clientType->client_type] ?? $clientType->client_type }}</td>
                                    <td><div class="client-name-primary">{{ $clientType->client_name }}</div></td>
                                    <td>
                                        <span class="badge programme-status-badge programme-status-badge--{{ $isActive ? 'active' : 'inactive' }}">
                                            {{ ucfirst($clientType->status_label) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-start justify-content-start client-actions">
                                            <button type="button" class="client-action-btn btn-edit-clienttype text-primary"
                                                    data-id="{{ $clientType->id }}"
                                                    data-client-type="{{ e($clientType->client_type) }}"
                                                    data-client-name="{{ e($clientType->client_name) }}"
                                                    data-description="{{ e($clientType->description ?? '') }}"
                                                    data-status="{{ e($clientType->status ?? 'active') }}"
                                                    title="Edit"><i class="material-symbols-rounded">edit</i><span>Edit</span></button>
                                            @if($canDeleteClientType)
                                                <form method="POST" action="{{ route('admin.mess.client-types.destroy', $clientType->id) }}"
                                                      class="mess-delete-form" data-confirm-title="Delete Client Type?"
                                                      data-confirm-message="Are you sure you want to delete this client type?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="client-action-btn text-danger" title="Delete"><i class="material-symbols-rounded">delete</i><span>Delete</span></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="clientTypesTable"></div>
        </div>
    </div>
</div>

{{-- Create Client Type Modal --}}
<div class="modal fade client-modal" id="createClientTypeModal" tabindex="-1" aria-labelledby="createClientTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('admin.mess.client-types.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title" id="createClientTypeModalLabel">Add Client Type</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label client-modal-label">Client Type <span class="text-danger">*</span></label>
                            <select name="client_type" class="form-select client-modal-control" required>
                                <option value="">Select Type</option>
                                @foreach($clientTypeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('client_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('client_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control client-modal-control" required value="{{ old('client_name') }}" placeholder="e.g. Kalindi Guest House">
                            @error('client_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Status</label>
                            <select name="status" class="form-select client-modal-control">
                                <option value="" {{ old('status') ? '' : 'selected' }}>Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Description</label>
                            <textarea name="description" class="form-control client-modal-control" rows="3" placeholder="e.g. Lorem ipsum dolor sit amet">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn client-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary client-modal-submit">Add Client Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Client Type Modal --}}
<div class="modal fade client-modal" id="editClientTypeModal" tabindex="-1" aria-labelledby="editClientTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form id="editClientTypeForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title" id="editClientTypeModalLabel">Edit Client Type</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label client-modal-label">Client Type <span class="text-danger">*</span></label>
                            <select name="client_type" id="edit_client_type" class="form-select client-modal-control" required>
                                <option value="">Select Type</option>
                                @foreach($clientTypeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" id="edit_client_name" class="form-control client-modal-control" required placeholder="e.g. Kalindi Guest House">
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Status</label>
                            <select name="status" id="edit_status" class="form-select client-modal-control">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label client-modal-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control client-modal-control" rows="3" placeholder="e.g. Lorem ipsum dolor sit amet"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn client-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary client-modal-submit">Update Client Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Column Visibility Modal (programme/attendance style) --}}
<div class="modal fade" id="clientColumnVisibilityModal" tabindex="-1" aria-labelledby="clientColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="clientColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="clientColumnToggleGrid"></div>
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
    'tableId' => 'clientTypesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 0,
    'actionColumnIndex' => 4,
    'infoLabel' => 'items',
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
])

@push('scripts')
{{-- Download / Print → branded server-side report (admin.mess.client-types.export). --}}
<script>
(function () {
    var TABLE_ID = 'clientTypesTable';
    var BASE = @json(route('admin.mess.client-types.export'));
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

    var downloadBtn = document.getElementById('clientDownloadBtn');
    if (downloadBtn) downloadBtn.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
    var printBtn = document.getElementById('clientPrintBtn');
    if (printBtn) printBtn.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
})();
</script>
{{-- Column Visibility modal ⇄ mess Column-manager bridge --}}
<script>
(function () {
    var TABLE_ID = 'clientTypesTable';
    var $ = window.jQuery;
    var grid = document.getElementById('clientColumnToggleGrid');
    var modalEl = document.getElementById('clientColumnVisibilityModal');
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
            var inputId = 'clientcolvis_' + col.index;
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
{{-- Edit modal population + reopen-on-validation-error --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($errors->isNotEmpty())
    new bootstrap.Modal(document.getElementById('createClientTypeModal')).show();
    @endif
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-clienttype');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editClientTypeForm').action = '{{ url("admin/mess/client-types") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_client_type').value = btn.getAttribute('data-client-type') || '';
        document.getElementById('edit_client_name').value = btn.getAttribute('data-client-name') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editClientTypeModal')).show();
    }, true);
});
</script>
@endpush
@endsection
