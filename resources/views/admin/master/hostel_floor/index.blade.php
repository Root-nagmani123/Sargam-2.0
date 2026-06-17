@extends('admin.layouts.master')

@section('title', 'Hostel Floor')

@section('setup_content')
<div class="container-fluid hostel-floor-index">

    <x-breadcrum title="Hostel Floor">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" id="hfAddBtn"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                <span>Add Hostel Floor</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="hfPrintBtn"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</i>
            <span>Print</span>
        </button>
        <a href="{{ route('master.hostel.floor.export') }}"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">download</i>
            <span>Download</span>
        </a>
    </div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="ds-card hostel-floor-card">
            <div class="ds-card-body">
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table align-middle mb-0 w-100']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>

    {{-- Add / Edit Hostel Floor modal (matches design) --}}
    <div class="modal fade hf-form-modal" id="hfFormModal" tabindex="-1" aria-labelledby="hfFormModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('master.hostel.floor.store') }}" method="POST" id="hfFloorForm">
                    @csrf
                    <input type="hidden" name="pk" id="hfPk" value="{{ old('pk') }}">
                    <div class="modal-header border-0 pb-2">
                        <h5 class="modal-title fw-semibold" id="hfFormModalTitle">Add Hostel Floor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <hr class="mt-0 mb-3">
                        <div class="mb-3">
                            <label for="hfFloorName" class="form-label fw-semibold">Floor Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('floor_name') is-invalid @enderror"
                                   id="hfFloorName" name="floor_name" placeholder="eg. A1"
                                   value="{{ old('floor_name') }}" required>
                            @error('floor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1">
                            <label for="hfStatus" class="form-label fw-semibold">Floor Status<span class="text-danger">*</span></label>
                            <select class="form-select" id="hfStatus" name="active_inactive" required>
                                <option value="1" {{ old('active_inactive', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3" id="hfFormSubmit">Add Hostel Floor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Module-scoped refinements — presentation only, no JS hooks / IDs changed */

    /* Clean, government-portal style table presentation */
    .hostel-floor-index #hostelfloormaster-table thead th {
        background: var(--ds-surface-2, #f8fafc);
        color: var(--ds-ink-muted, #64748b);
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        border-bottom: 1px solid var(--ds-line, #e5e7eb);
        white-space: nowrap;
        vertical-align: middle;
    }
    .hostel-floor-index #hostelfloormaster-table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--ds-line, #eef1f4);
    }
    .hostel-floor-index #hostelfloormaster-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .hostel-floor-index #hostelfloormaster-table tbody tr:hover {
        background-color: rgba(var(--bs-primary-rgb, 0 74 147), 0.04);
    }

    /* ---- Status pill badges (Active / Inactive) ---- */
    .hostel-floor-index .hf-badge {
        padding: 0.4em 0.85em;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.01em;
    }
    .hostel-floor-index .hf-badge-active { color: #157347; background-color: #d6f5e3; }
    .hostel-floor-index .hf-badge-inactive { color: #b02a37; background-color: #fcdcdf; }

    /* ---- Inline row action icons (edit / toggle) ---- */
    .hostel-floor-index .hf-row-actions { line-height: 1; }
    .hostel-floor-index .hf-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        padding: 0;
        border: 0;
        border-radius: 0.5rem;
        background: transparent;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
    }
    .hostel-floor-index .hf-icon-btn .material-symbols-rounded { font-size: 20px; line-height: 1; }
    .hostel-floor-index .hf-icon-edit { color: var(--bs-primary, #4f46e5); }
    .hostel-floor-index .hf-icon-edit:hover {
        background: rgba(var(--bs-primary-rgb, 79 70 229), 0.12);
        transform: translateY(-1px);
    }
    .hostel-floor-index .hf-icon-delete { color: #dc3545; }
    .hostel-floor-index .hf-icon-delete:hover:not(:disabled) {
        background: rgba(220, 53, 69, 0.12);
        transform: translateY(-1px);
    }
    .hostel-floor-index .hf-icon-btn:disabled {
        color: #c4c9d0;
        cursor: not-allowed;
        opacity: 1;
    }

    /* Status toggle inside the action cell — green when on (matches reference) */
    .hostel-floor-index .hf-row-switch { padding-left: 2.4em; }
    .hostel-floor-index .hf-row-switch .form-check-input {
        width: 2.1em;
        height: 1.15em;
        cursor: pointer;
        margin-top: 0.15em;
    }
    .hostel-floor-index .hf-row-switch .form-check-input:checked {
        background-color: #1fae5b;
        border-color: #1fae5b;
    }
    .hostel-floor-index .hf-row-switch .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(31, 174, 91, 0.2);
        border-color: #1fae5b;
    }

    /* ---- Relocated DataTables chrome: toolbar + footer (reference layout) ---- */
    .hostel-floor-index .hf-toolbar { margin-bottom: var(--ds-space-3, 1rem); }
    .hostel-floor-index .hf-footer { margin-top: var(--ds-space-3, 1rem); }

    /* Search box — rounded, magnifier icon, "Search" placeholder */
    .hostel-floor-index .hf-toolbar .dataTables_filter { margin: 0; }
    .hostel-floor-index .hf-toolbar .dataTables_filter label {
        margin: 0;
        font-size: 0;           /* hides the native "Search:" label text */
        display: block;
    }
    .hostel-floor-index .hf-toolbar .dataTables_filter input {
        width: 280px;
        max-width: 100%;
        height: 42px;
        margin: 0 !important;
        font-size: 0.9rem;
        color: var(--ds-ink, #344054);
        border: 1px solid var(--ds-line, #e5e7eb);
        border-radius: 0.65rem;
        padding: 0.5rem 0.9rem 0.5rem 2.4rem;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%2398a2b3'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 0.85rem center;
        background-size: 1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .hostel-floor-index .hf-toolbar .dataTables_filter input::placeholder { color: #98a2b3; }
    .hostel-floor-index .hf-toolbar .dataTables_filter input:focus {
        outline: 0;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
    }

    /* Toolbar buttons (Columns) */
    .hostel-floor-index .hf-btn {
        height: 42px;
        padding: 0 0.95rem;
        border-radius: 0.65rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1;
        color: var(--ds-ink, #344054);
        background: #fff;
        border: 1px solid var(--ds-line, #e5e7eb);
        box-shadow: var(--ds-shadow-sm, 0 1px 2px rgba(16, 24, 40, 0.05));
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .hostel-floor-index .hf-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--bs-primary); }
    .hostel-floor-index .hf-btn .material-symbols-rounded { font-size: 18px; line-height: 1; }

    /* Footer: pagination (left) + count (right) */
    .hostel-floor-index .hf-footer .dataTables_paginate { margin: 0; }
    .hostel-floor-index .hf-footer .pagination { margin: 0; gap: 0.3rem; align-items: center; flex-wrap: wrap; }
    .hostel-floor-index .hf-footer .page-item { margin: 0; }
    .hostel-floor-index .hf-footer .page-link {
        min-width: 2.1rem;
        height: 2.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.55rem;
        border: 1px solid transparent;
        border-radius: 0.6rem;
        background: transparent;
        color: #475467;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1;
        box-shadow: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .hostel-floor-index .hf-footer .page-link:hover { background: #f1f5f9; color: var(--bs-primary); }
    .hostel-floor-index .hf-footer .page-item.active .page-link {
        background: #fff; border-color: var(--bs-primary); color: var(--bs-primary);
    }
    .hostel-floor-index .hf-footer .page-item.disabled .page-link { color: #cbd5e1; background: transparent; }
    .hostel-floor-index .hf-footer .page-link:focus { box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.18); }
    .hostel-floor-index .hf-footer .paginate_button.previous .page-link,
    .hostel-floor-index .hf-footer .paginate_button.next .page-link { font-size: 0; }
    .hostel-floor-index .hf-footer .paginate_button.previous .page-link::before { content: '\2039'; font-size: 1.45rem; }
    .hostel-floor-index .hf-footer .paginate_button.next .page-link::before { content: '\203A'; font-size: 1.45rem; }

    .hostel-floor-index .hf-footer .hf-count {
        font-size: 0.875rem;
        color: var(--ds-ink-muted, #64748b);
        white-space: nowrap;
    }
    .hostel-floor-index .hf-footer .hf-count-select {
        width: auto;
        min-width: 4.25rem;
        border-radius: 0.5rem;
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .hostel-floor-index .hf-toolbar { justify-content: flex-start; }
        .hostel-floor-index .hf-toolbar .dataTables_filter,
        .hostel-floor-index .hf-toolbar .dataTables_filter input { width: 100%; }
        .hostel-floor-index .hf-footer { justify-content: center; }
    }

    /* ---- Column Visibility modal (appended to <body>, so NOT page-scoped) ---- */
    .hf-cols-modal .modal-content,
    .hf-form-modal .modal-content {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
    }
    .hf-cols-modal .modal-title,
    .hf-form-modal .modal-title { font-size: 1.2rem; color: #1f2937; }
    .hf-cols-modal hr,
    .hf-form-modal hr { color: #e5e7eb; opacity: 1; }
    .hf-cols-modal .hf-col-chip {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        width: 100%;
        margin: 0;
        padding: 0.6rem 0.85rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.6rem;
        font-size: 0.92rem;
        color: #344054;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .hf-cols-modal .hf-col-chip:hover { border-color: #cbd5e1; background: #f8fafc; }
    .hf-cols-modal .hf-col-chip .form-check-input { cursor: pointer; flex-shrink: 0; }
    .hf-cols-modal .hf-col-chip .form-check-input:checked,
    .hf-form-modal .form-check-input:checked { background-color: var(--bs-primary); border-color: var(--bs-primary); }

    /* Add / Edit modal form controls */
    .hf-form-modal .form-label { font-size: 0.875rem; color: #344054; margin-bottom: 0.35rem; }
    .hf-form-modal .form-control,
    .hf-form-modal .form-select { height: 44px; border-radius: 0.6rem; border-color: #e5e7eb; font-size: 0.9rem; }
    .hf-form-modal .form-control:focus,
    .hf-form-modal .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
    }
    .hf-form-modal .modal-footer .btn { font-weight: 600; }
</style>
@endsection
@push('scripts')
{{ $dataTable->scripts() }}
<script>
/*
 * Frontend-only UI enhancer for the Hostel Floor DataTable.
 * Runs AFTER the server-side (Yajra) table initialises and only relocates /
 * restyles the native controls (search, length, pagination) into a reference
 * toolbar + footer, and adds a Columns modal. The native nodes are moved (not
 * recreated), so all existing handlers keep working. No data/AJAX/route changes.
 */
(function() {
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) { return; }

    var TID = 'hostelfloormaster-table';

    function enhance() {
        var $table = $('#' + TID);
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) { return; }

        var el = $table.get(0);
        if (el._hfEnhanced) { return; }
        el._hfEnhanced = true;

        var api = $table.DataTable();
        var $wrapper = $('#' + TID + '_wrapper');
        if (!$wrapper.length) { return; }

        var $responsive = $table.closest('.table-responsive');
        var $host = $responsive.length ? $responsive : $wrapper;

        var $length   = $wrapper.find('.dataTables_length');
        var $filter   = $wrapper.find('.dataTables_filter');
        var $paginate = $wrapper.find('.dataTables_paginate');

        var $topRow = $filter.closest('.row');
        var $botRow = $paginate.closest('.row');

        // ---- Top toolbar (right): Columns + Search ----
        var $toolbar = $('<div class="hf-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2"></div>');
        $toolbar.insertBefore($host);

        // Columns modal
        var modalId = TID + '-cols-modal';
        var $modal = $(
            '<div class="modal fade hf-cols-modal" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog modal-dialog-centered modal-lg">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header border-0 pb-2">' +
                            '<h5 class="modal-title fw-semibold">Column Visibility</h5>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                        '</div>' +
                        '<div class="modal-body pt-0">' +
                            '<hr class="mt-0 mb-3">' +
                            '<div class="row g-2 hf-cols-grid"></div>' +
                        '</div>' +
                        '<div class="modal-footer border-0 pt-0">' +
                            '<button type="button" class="btn btn-outline-primary px-4 rounded-3" data-bs-dismiss="modal">Close</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        var $grid = $modal.find('.hf-cols-grid');
        api.columns().every(function(idx) {
            var col = this;
            var title = $(col.header()).text().trim() || ('Column ' + (idx + 1));
            var $cell = $('<div class="col-6 col-md-4"></div>');
            var $chip = $('<label class="hf-col-chip"><input type="checkbox" class="form-check-input m-0"' + (col.visible() ? ' checked' : '') + '><span></span></label>');
            $chip.find('span').text(title);
            $chip.find('input').on('change', function() { col.visible($(this).is(':checked')); });
            $cell.append($chip);
            $grid.append($cell);
        });
        $('body').append($modal);

        var $colsBtn = $(
            '<button type="button" class="btn hf-btn hf-btn-columns d-inline-flex align-items-center gap-1">' +
                '<i class="material-icons material-symbols-rounded" aria-hidden="true">view_column</i><span>Columns</span>' +
            '</button>'
        );
        $colsBtn.on('click', function() {
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
            }
        });
        $toolbar.append($colsBtn);

        // ---- Wire the top "Print" button to the DataTables print button ----
        if ($.fn.dataTable && $.fn.dataTable.Buttons) {
            try {
                var printButtons = new $.fn.dataTable.Buttons(api, {
                    buttons: [{ extend: 'print', title: 'Hostel Floor' }]
                });
                var $printContainer = $(printButtons.container()).addClass('d-none');
                $wrapper.append($printContainer);
                $('#hfPrintBtn').on('click', function() {
                    $printContainer.find('.dt-button').first().trigger('click');
                });
            } catch (e) {
                $('#hfPrintBtn').on('click', function() { window.print(); });
            }
        } else {
            $('#hfPrintBtn').on('click', function() { window.print(); });
        }

        // Relocate native search into the toolbar (keeps its keyup handler).
        $filter.appendTo($toolbar);
        $filter.find('input').addClass('form-control').attr('placeholder', 'Search');

        // ---- Footer: pagination (left) + "Showing N of TOTAL items" (right) ----
        var $footer = $('<div class="hf-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>');
        $footer.insertAfter($host);

        var $lenSelect = $length.find('select').addClass('form-select form-select-sm hf-count-select');
        var $count = $('<div class="hf-count d-inline-flex align-items-center gap-2"></div>');
        $count.append($('<span>Showing</span>'));
        $count.append($lenSelect);
        var $countText = $('<span class="hf-count-text"></span>');
        $count.append($countText);

        $footer.append($paginate).append($count);

        function hfUpdateCount() {
            try {
                var info = api.page.info();
                $countText.text('of ' + info.recordsDisplay + ' items');
            } catch (e) { /* noop */ }
        }
        api.on('draw', hfUpdateCount);
        hfUpdateCount();

        $length.remove();
        $topRow.remove();
        $botRow.remove();
    }

    $(document).on('init.dt', '#' + TID, function() { enhance(); });
    $(function() { enhance(); });
})();

/*
 * Add / Edit in a modal. The form still POSTs to the existing store route
 * (server-side create/update via the hidden pk); no AJAX, no route changes.
 * Edit values come from data-* attributes on the row's edit icon.
 */
(function() {
    var $ = window.jQuery;
    if (!$) { return; }

    function modal() {
        var el = document.getElementById('hfFormModal');
        return (el && window.bootstrap && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }
    function clearInvalid() { $('#hfFloorForm .is-invalid').removeClass('is-invalid'); }
    function setMode(isEdit) {
        document.getElementById('hfFormModalTitle').textContent = isEdit ? 'Edit Hostel Floor' : 'Add Hostel Floor';
        document.getElementById('hfFormSubmit').textContent = isEdit ? 'Update' : 'Add Hostel Floor';
    }

    $(document).on('click', '#hfAddBtn', function() {
        var form = document.getElementById('hfFloorForm');
        if (form) { form.reset(); }
        document.getElementById('hfPk').value = '';
        document.getElementById('hfStatus').value = '1';
        clearInvalid();
        setMode(false);
        var m = modal(); if (m) { m.show(); }
    });

    $(document).on('click', '.hf-edit-trigger', function(e) {
        e.preventDefault();
        var $b = $(this);
        document.getElementById('hfPk').value = $b.attr('data-pk') || '';
        document.getElementById('hfFloorName').value = $b.attr('data-name') || '';
        document.getElementById('hfStatus').value = $b.attr('data-status') || '1';
        clearInvalid();
        setMode(true);
        var m = modal(); if (m) { m.show(); }
    });

    @if($errors->any())
    $(function() {
        setMode({{ old('pk') ? 'true' : 'false' }});
        var m = modal(); if (m) { m.show(); }
    });
    @endif
})();
</script>
@endpush
