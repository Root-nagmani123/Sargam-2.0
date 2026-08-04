@extends('admin.layouts.master')

@section('title', 'Country List')

{{--
    Phase E/F — pixel-perfect index redesign (the "Store Master" pattern).
    Client-side DataTable on the shared programme-dt chrome (docs/new-design-index-page.md).
    Status = its own column (soft badge: green Active / red Inactive).
    Action = icon-only: Edit · toggle · Delete.
    Controller returns Country::all() to feed the client-side grid. All functionality preserved.
--}}
@section('setup_content')
<div class="container-fluid country-page">
    <x-breadcrum title="Country List" :showBack="false">
        {{-- Add New — opens the UX4G create modal (no page navigation) --}}
        <button type="button" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm"
            data-bs-toggle="modal" data-bs-target="#countryFormModal" data-mode="create">
            <i class="material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
            Add Country
        </button>
    </x-breadcrum>

    {{-- Download / Print strip — branded CSV / PDF / Print (shared LBSNAA report chrome) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" class="btn country-tool-btn border-0 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('master.country.export', 'csv') }}"><i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('master.country.export', 'pdf') }}"><i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('master.country.export', 'print') }}" target="_blank" rel="noopener" class="btn country-tool-btn border-0">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- Toolbar: Columns + search --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 programme-dt-toolbar">
                <button type="button" class="btn programme-dt-btn-columns" id="countryColumnsBtn"
                    data-bs-toggle="modal" data-bs-target="#countryColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div id="countryDtSearch" class="programme-dt-search" data-dt-search-for="countryTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="countryTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th style="width:5rem">S. No.</th>
                                <th>Country Name</th>
                                <th style="width:8rem">Status</th>
                                <th class="text-center" style="width:10rem">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($countries as $country)
                            <tr>
                                <td></td>
                                <td class="fw-medium">{{ $country->country_name }}</td>

                                {{-- Status: soft badge (green Active / red Inactive) --}}
                                <td data-order="{{ $country->active_inactive }}">
                                    <span class="status-pill badge {{ $country->active_inactive == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $country->active_inactive == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                {{-- Action: Edit (blue) · toggle · Delete (red) — icon + label --}}
                                <td>
                                    <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="Country actions">
                                        {{-- Edit — opens the UX4G edit modal (no page navigation) --}}
                                        <button type="button" class="country-act country-act--edit"
                                            data-bs-toggle="modal" data-bs-target="#countryFormModal" data-mode="edit"
                                            data-id="{{ $country->pk }}" data-name="{{ $country->country_name }}"
                                            data-status="{{ $country->active_inactive }}"
                                            aria-label="Edit {{ $country->country_name }}">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            <span>Edit</span>
                                        </button>

                                        {{-- Status toggle (AJAX via status-toggle-delete.js) --}}
                                        <div class="form-check form-switch m-0">
                                            <input type="checkbox" class="form-check-input status-toggle " role="switch"
                                                data-table="country_master" data-column="active_inactive"
                                                data-id="{{ $country->pk }}" {{ $country->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>

                                        {{-- Delete (guarded: an active country cannot be deleted) --}}
                                        @if($country->active_inactive == 1)
                                        <span class="country-act country-act--del is-disabled"
                                            title="Set the country inactive before deleting" aria-disabled="true">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            <span>Delete</span>
                                        </span>
                                        @else
                                        <form action="{{ route('master.country.delete', $country->pk) }}" method="POST"
                                            class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="country-act country-act--del"
                                                aria-label="Delete {{ $country->country_name }}">
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
            <div id="countryDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                data-dt-footer-for="countryTable"></div>
        </div>
    </div>
</div>

{{-- Column visibility modal --}}
<div class="modal fade" id="countryColumnVisibilityModal" tabindex="-1" aria-labelledby="countryColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="countryColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="countryColumnToggleGrid"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create / Edit modal (UX4G = Bootstrap 5.3). One modal serves both; JS sets the mode.
     Submits to the UNCHANGED store (POST) / update (PUT) routes — no backend change. --}}
<div class="modal fade" id="countryFormModal" tabindex="-1" aria-labelledby="countryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="countryForm" method="POST" action="{{ route('master.country.store') }}">
                @csrf
                <input type="hidden" name="_method" id="cfMethod" value="POST">
                <input type="hidden" name="_form_mode" id="cfFormMode" value="create">
                <input type="hidden" name="_edit_id" id="cfEditId" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="countryFormModalLabel">Add Country</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="cfName" class="form-label">Country Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cfName" name="country_name[]" placeholder="Country Name" required>
                    </div>
                    <div class="mb-0">
                        <label for="cfStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="cfStatus" name="active_inactive" required>
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
    /* Store Master parity — scoped to .country-page (tokens from design.md) */
    .country-page .country-tool-btn {
        background: var(--ds-surface); color: var(--ds-primary);
        border-radius: var(--ds-radius-1); font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: var(--ds-space-2); padding: 0.4rem 0.9rem;
        box-shadow: 0 0 0 1px var(--ds-line) inset;
        border: 0;
    }
    .country-page .country-tool-btn:hover { background: var(--ds-surface-2); }

    /* Soft status badge — theme ships *-subtle backgrounds but not *-emphasis text, so set it */
    .country-page .status-pill { padding: 0.4em 0.85em; font-weight: 600; }
    .country-page .status-pill.bg-success-subtle { color: #146c43; }
    .country-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    /* Row actions — icon over label: Edit (blue) · switch · Delete (red) */
    .country-page .country-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 0.72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .country-page .country-act i { font-size: 1.1rem; }
    .country-page .country-act--edit { color: #2563eb; }
    .country-page .country-act--del  { color: var(--bs-danger, #dc3545); }
    .country-page .country-act--del.is-disabled { color: var(--ds-ink-muted); cursor: not-allowed; }

    @media print {
        .app-header, .left-sidebar, .country-tool-btn, .programme-dt-toolbar,
        .programme-dt-footer, .country-act { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    var $table = $('#countryTable');
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
    var KEY = 'countryGrid:hiddenColumns:v1';
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(KEY, JSON.stringify(a)); } catch (e) {} }
    var hidden = getHidden();
    dt.columns().every(function () { this.visible(hidden.indexOf(this.index()) === -1, false); });
    dt.columns.adjust();
    var $grid = $('#countryColumnToggleGrid').empty();
    dt.columns().every(function () {
        var idx = this.index(), title = $(this.header()).text().replace(/\s+/g, ' ').trim();
        if (!title) return;
        var id = 'countrycolvis_' + idx;
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

    // --- Status toggle. Shared custom.js shows a SweetAlert confirm → AJAX → then
    //     $('.dataTable').ajax.reload(); this grid is client-side (no ajax source) so that
    //     stray call logs "Invalid JSON response". Silence DataTables' logger for this page
    //     (only this table inits here) and reload on a confirmed success for correct fresh
    //     state (badge + active-guard). No optimistic UI flip — the confirm may be cancelled. ---
    $.fn.dataTable.ext.errMode = 'none';
    $(document).ajaxSuccess(function (e, xhr, settings) {
        if (settings && settings.url && /toggle-?status/i.test(settings.url)) {
            window.location.reload();
        }
    });

    // Download (CSV/PDF) and Print are server-side branded exports now — plain links to
    // master.country.export/{format} using the shared LBSNAA report chrome. No client-side JS.

    // --- Create / Edit via UX4G modal. One modal, two modes; submits to the unchanged
    //     store (POST) / update (PUT) routes. Bootstrap passes the trigger as relatedTarget. ---
    var CT_STORE = @json(route('master.country.store'));
    var CT_UPDATE = @json(route('master.country.update', '__ID__'));
    $('#countryFormModal').on('show.bs.modal', function (e) {
        var t = e.relatedTarget; if (!t) return;
        var mode = t.getAttribute('data-mode') || 'create';
        if (mode === 'edit') {
            $('#countryFormModalLabel').text('Edit Country');
            $('#countryForm').attr('action', CT_UPDATE.replace('__ID__', t.getAttribute('data-id')));
            $('#cfMethod').val('PUT'); $('#cfFormMode').val('edit'); $('#cfEditId').val(t.getAttribute('data-id'));
            $('#cfName').attr('name', 'country_name').val(t.getAttribute('data-name') || '');
            // Normalise: the toggle stores inactive as 0, the form uses 2 — anything ≠ 1 = Inactive.
            $('#cfStatus').val(t.getAttribute('data-status') === '1' ? '1' : '2');
        } else {
            $('#countryFormModalLabel').text('Add Country');
            $('#countryForm').attr('action', CT_STORE);
            $('#cfMethod').val('POST'); $('#cfFormMode').val('create'); $('#cfEditId').val('');
            $('#cfName').attr('name', 'country_name[]').val('');
            $('#cfStatus').val('1');
        }
    });

    @if($errors->any())
    // A create/edit submit failed validation → reopen the modal with the errors + old input.
    (function () {
        var t = document.createElement('div');
        t.setAttribute('data-mode', @json(old('_form_mode', 'create')));
        @if(old('_form_mode') === 'edit')
        t.setAttribute('data-id', @json((string) old('_edit_id', '')));
        t.setAttribute('data-name', @json((string) old('country_name', '')));
        t.setAttribute('data-status', @json((string) old('active_inactive', '1')));
        @endif
        bootstrap.Modal.getOrCreateInstance(document.getElementById('countryFormModal')).show(t);
        @if(old('_form_mode') !== 'edit')
        $('#cfName').val(@json(old('country_name.0', '')));
        $('#cfStatus').val(@json((string) old('active_inactive', '1')));
        @endif
    })();
    @endif
});
</script>
@endpush
