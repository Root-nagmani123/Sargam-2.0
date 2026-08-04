@extends('admin.layouts.master')

@section('title', 'State List')

{{--
    Phase E/F — pixel-perfect index redesign (the "Store Master" / country pattern).
    Client-side DataTable on the shared programme-dt chrome (docs/new-design-index-page.md).
    Status = its own column (soft badge: green Active / red Inactive).
    Action = Edit (modal) · toggle · Delete. Create/Edit open in a UX4G modal.
    Controller returns State::get() to feed the client-side grid. All functionality preserved.
--}}
@section('setup_content')
<div class="container-fluid state-page">
    <x-breadcrum title="State List" :showBack="false">
        {{-- Add New — opens the UX4G create modal (no page navigation) --}}
        <button type="button" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm"
            data-bs-toggle="modal" data-bs-target="#stateFormModal" data-mode="create">
            <i class="material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
            Add State
        </button>
    </x-breadcrum>

    {{-- Download / Print strip — branded CSV / PDF / Print (shared LBSNAA report chrome) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" class="btn state-tool-btn border-0 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('master.state.export', 'csv') }}"><i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('master.state.export', 'pdf') }}"><i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('master.state.export', 'print') }}" target="_blank" rel="noopener" class="btn state-tool-btn border-0">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- Toolbar: Columns + search --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 programme-dt-toolbar">
                <button type="button" class="btn programme-dt-btn-columns" id="stateColumnsBtn"
                    data-bs-toggle="modal" data-bs-target="#stateColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div id="stateDtSearch" class="programme-dt-search" data-dt-search-for="stateTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="stateTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th style="width:5rem">S. No.</th>
                                <th>State Name</th>
                                <th style="width:8rem">Status</th>
                                <th class="text-center" style="width:10rem">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($states as $state)
                            <tr>
                                <td></td>
                                <td class="fw-medium">{{ $state->state_name }}</td>

                                {{-- Status: soft badge (green Active / red Inactive) --}}
                                <td data-order="{{ $state->active_inactive }}">
                                    <span class="status-pill badge {{ $state->active_inactive == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $state->active_inactive == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                {{-- Action: Edit (blue) · toggle · Delete (red) — icon + label --}}
                                <td>
                                    <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="State actions">
                                        {{-- Edit — opens the UX4G edit modal (no page navigation) --}}
                                        <button type="button" class="state-act state-act--edit"
                                            data-bs-toggle="modal" data-bs-target="#stateFormModal" data-mode="edit"
                                            data-id="{{ $state->pk }}" data-name="{{ $state->state_name }}"
                                            data-country="{{ $state->country_master_pk }}"
                                            data-status="{{ $state->active_inactive }}"
                                            aria-label="Edit {{ $state->state_name }}">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            <span>Edit</span>
                                        </button>

                                        {{-- Status toggle (AJAX via status-toggle-delete.js) --}}
                                        <div class="form-check form-switch m-0">
                                            <input type="checkbox" class="form-check-input status-toggle" role="switch"
                                                data-table="state_master" data-column="active_inactive"
                                                data-id="{{ $state->pk }}" {{ $state->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>

                                        {{-- Delete (guarded: an active state cannot be deleted) --}}
                                        @if($state->active_inactive == 1)
                                        <span class="state-act state-act--del is-disabled"
                                            title="Set the state inactive before deleting" aria-disabled="true">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            <span>Delete</span>
                                        </span>
                                        @else
                                        <form action="{{ route('master.state.delete', $state->pk) }}" method="POST"
                                            class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="state-act state-act--del"
                                                aria-label="Delete {{ $state->state_name }}">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                                <span>Delete</span>
                                            </button>
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

            {{-- Footer: pagination + page-size + "Showing N of M items" (built by datatable-global-ui.js) --}}
            <div id="stateDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                data-dt-footer-for="stateTable"></div>
        </div>
    </div>
</div>

{{-- Column visibility modal --}}
<div class="modal fade" id="stateColumnVisibilityModal" tabindex="-1" aria-labelledby="stateColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="stateColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="stateColumnToggleGrid"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create / Edit modal (UX4G = Bootstrap 5.3). One modal serves both; JS sets the mode.
     Submits to the UNCHANGED store (POST) / update (POST) routes — no backend change. --}}
<div class="modal fade" id="stateFormModal" tabindex="-1" aria-labelledby="stateFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="stateForm" method="POST" action="{{ route('master.state.store') }}">
                @csrf
                <input type="hidden" name="_form_mode" id="sfFormMode" value="create">
                <input type="hidden" name="_edit_id" id="sfEditId" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="stateFormModalLabel">Add State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="sfCountry" class="form-label">Country <span class="text-danger">*</span></label>
                        <select class="form-select" id="sfCountry" name="country_master_pk" required>
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $country)
                            <option value="{{ $country->pk }}">{{ $country->country_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sfName" class="form-label">State Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sfName" name="state_name" placeholder="State Name" required>
                    </div>
                    <div class="mb-0">
                        <label for="sfStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="sfStatus" name="active_inactive" required>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Store Master parity — scoped to .state-page (tokens from design.md) */
    .state-page .state-tool-btn {
        background: var(--ds-surface); color: var(--ds-primary);
        border-radius: var(--ds-radius-1); font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: var(--ds-space-2); padding: 0.4rem 0.9rem;
        box-shadow: 0 0 0 1px var(--ds-line) inset;
        border: 0;
    }
    .state-page .state-tool-btn:hover { background: var(--ds-surface-2); }

    /* Soft status badge — theme ships *-subtle backgrounds but not *-emphasis text, so set it */
    .state-page .status-pill { padding: 0.4em 0.85em; font-weight: 600; }
    .state-page .status-pill.bg-success-subtle { color: #146c43; }
    .state-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    /* Row actions — icon over label: Edit (blue) · switch · Delete (red) */
    .state-page .state-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 0.72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .state-page .state-act i { font-size: 1.1rem; }
    .state-page .state-act--edit { color: #2563eb; }
    .state-page .state-act--del  { color: var(--bs-danger, #dc3545); }
    .state-page .state-act--del.is-disabled { color: var(--ds-ink-muted); cursor: not-allowed; }

    @media print {
        .app-header, .left-sidebar, .state-tool-btn, .programme-dt-toolbar,
        .programme-dt-footer, .state-act { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    var $table = $('#stateTable');
    if (!$table.length || $.fn.dataTable.isDataTable($table)) return;

    var dt = $table.DataTable({
        autoWidth: false,
        pageLength: 10,
        // ‹ 1 2 3 › — prev + numbers + next, no First/Last (see new-design-index-page.md §5).
        pagingType: 'simple_numbers',
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[1, 'asc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false },
            { targets: -1, orderable: false, searchable: false }
        ]
    });

    dt.on('draw.dt', function () {
        var start = dt.page.info().start;
        dt.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.innerHTML = start + i + 1; });
    });
    dt.draw(false);

    // --- Column visibility modal (persisted) ---
    var KEY = 'stateGrid:hiddenColumns:v1';
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(KEY, JSON.stringify(a)); } catch (e) {} }
    var hidden = getHidden();
    dt.columns().every(function () { this.visible(hidden.indexOf(this.index()) === -1, false); });
    dt.columns.adjust();
    var $grid = $('#stateColumnToggleGrid').empty();
    dt.columns().every(function () {
        var idx = this.index(), title = $(this.header()).text().replace(/\s+/g, ' ').trim();
        if (!title) return;
        var id = 'statecolvis_' + idx;
        var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', hidden.indexOf(idx) === -1);
        $cb.on('change', function () {
            var h = getHidden(), pos = h.indexOf(idx);
            if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else if (pos === -1) { h.push(idx); }
            setHidden(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
        });
        $('<div class="col-12 col-sm-6"></div>').append(
            $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', id).append($cb).append($('<span></span>').text(title))
        ).appendTo($grid);
    });

    // --- Status toggle. Client-side grid: silence the stray ajax.reload() from custom.js and
    //     reload on a confirmed success (see new-design-index-page.md §3b). ---
    $.fn.dataTable.ext.errMode = 'none';
    $(document).ajaxSuccess(function (e, xhr, settings) {
        if (settings && settings.url && /toggle-?status/i.test(settings.url)) {
            window.location.reload();
        }
    });

    // --- Create / Edit via UX4G modal. One modal, two modes; submits to the unchanged
    //     store (POST) / update (POST) routes. Bootstrap passes the trigger as relatedTarget. ---
    var ST_STORE = @json(route('master.state.store'));
    var ST_UPDATE = @json(route('master.state.update', '__ID__'));
    $('#stateFormModal').on('show.bs.modal', function (e) {
        var t = e.relatedTarget; if (!t) return;
        var mode = t.getAttribute('data-mode') || 'create';
        if (mode === 'edit') {
            $('#stateFormModalLabel').text('Edit State');
            $('#stateForm').attr('action', ST_UPDATE.replace('__ID__', t.getAttribute('data-id')));
            $('#sfFormMode').val('edit'); $('#sfEditId').val(t.getAttribute('data-id'));
            $('#sfCountry').val(t.getAttribute('data-country') || '');
            $('#sfName').val(t.getAttribute('data-name') || '');
            // Normalise: the toggle stores inactive as 0, the form uses 2 — anything ≠ 1 = Inactive.
            $('#sfStatus').val(t.getAttribute('data-status') === '1' ? '1' : '2');
        } else {
            $('#stateFormModalLabel').text('Add State');
            $('#stateForm').attr('action', ST_STORE);
            $('#sfFormMode').val('create'); $('#sfEditId').val('');
            $('#sfCountry').val(''); $('#sfName').val(''); $('#sfStatus').val('1');
        }
    });

    @if($errors->any())
    // A create/edit submit failed validation → reopen the modal with the errors + old input.
    (function () {
        var t = document.createElement('div');
        t.setAttribute('data-mode', @json(old('_form_mode', 'create')));
        @if(old('_form_mode') === 'edit')
        t.setAttribute('data-id', @json((string) old('_edit_id', '')));
        @endif
        bootstrap.Modal.getOrCreateInstance(document.getElementById('stateFormModal')).show(t);
        $('#sfCountry').val(@json((string) old('country_master_pk', '')));
        $('#sfName').val(@json((string) old('state_name', '')));
        $('#sfStatus').val(@json((string) old('active_inactive', '1')));
    })();
    @endif
});
</script>
@endpush
