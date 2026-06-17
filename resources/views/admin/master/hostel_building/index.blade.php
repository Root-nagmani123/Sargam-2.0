@extends('admin.layouts.master')

@section('title', 'Hostel Building')

@section('setup_content')
<div class="container-fluid hostel-building-index">

    <x-breadcrum title="Building Master">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" id="hbAddBtn"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                <span>Add Hostel Building</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="hbPrintBtn"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color: #fff;color: var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</i>
            <span>Print</span>
        </button>
        <a href="{{ route('master.hostel.building.export') }}"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color: #fff;color: var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">download</i>
            <span>Download</span>
        </a>
    </div>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="ds-card hostel-building-card">
            <div class="ds-card-body">
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table align-middle mb-0 w-100']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>

    {{-- Add / Edit Hostel Building modal (matches design) --}}
    <div class="modal fade hb-form-modal" id="hbFormModal" tabindex="-1" aria-labelledby="hbFormModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('master.hostel.building.store') }}" method="POST" id="hbBuildingForm">
                    @csrf
                    <input type="hidden" name="pk" id="hbPk" value="{{ old('pk') }}">
                    <div class="modal-header border-0 pb-2">
                        <h5 class="modal-title fw-semibold" id="hbFormModalTitle">Add Hostel Building</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <hr class="mt-0 mb-3">
                        <div class="mb-3">
                            <label for="hbBuildingName" class="form-label fw-semibold">Building Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('building_name') is-invalid @enderror"
                                   id="hbBuildingName" name="building_name" placeholder="eg. Naramada Hostel"
                                   value="{{ old('building_name') }}" required>
                            @error('building_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="hbNoFloors" class="form-label fw-semibold">Number of Floors<span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="hbNoFloors" name="no_of_floors"
                                   placeholder="eg. 25" value="{{ old('no_of_floors') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="hbNoRooms" class="form-label fw-semibold">Number of Rooms<span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="hbNoRooms" name="no_of_rooms"
                                   placeholder="eg. 24" value="{{ old('no_of_rooms') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="hbBuildingType" class="form-label fw-semibold">Building Type<span class="text-danger">*</span></label>
                            <select class="form-select" id="hbBuildingType" name="building_type" required>
                                <option value="">Select Type</option>
                                @foreach(\App\Models\BuildingMaster::$buildingType as $val => $label)
                                    <option value="{{ $val }}" {{ old('building_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-1">
                            <label for="hbStatus" class="form-label fw-semibold">Building Status<span class="text-danger">*</span></label>
                            <select class="form-select" id="hbStatus" name="active_inactive" required>
                                <option value="1" {{ old('active_inactive', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3" id="hbFormSubmit">Add Hostel Building</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Module-scoped refinements — presentation only, no JS hooks / IDs changed */
.hostel-building-card-icon {
    width: 2rem;
    height: 2rem;
    border-radius: var(--ds-radius-1, 0.5rem);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12);
    color: var(--ds-primary, var(--bs-primary));
    flex-shrink: 0;
}

/* Clean, government-portal style table presentation */
.hostel-building-index #hostelbuildingmaster-table thead th {
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

.hostel-building-index #hostelbuildingmaster-table tbody td {
    font-size: 0.9rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--ds-line, #eef1f4);
}

.hostel-building-index #hostelbuildingmaster-table tbody tr {
    transition: background-color 0.15s ease;
}

.hostel-building-index #hostelbuildingmaster-table tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb, 0 74 147), 0.04);
}

/* Row action buttons — soft, modern, consistent sizing */
.hostel-building-index #hostelbuildingmaster-table .btn-sm {
    border-radius: 0.5rem;
    font-weight: 600;
}

/* Status switch alignment */
.hostel-building-index #hostelbuildingmaster-table .form-switch {
    min-height: auto;
}

/* ---- Status pill badges (Active / Inactive) ---- */
.hostel-building-index .hb-badge {
    padding: 0.4em 0.85em;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}

.hostel-building-index .hb-badge-active {
    color: #157347;
    background-color: #d6f5e3;
}

.hostel-building-index .hb-badge-inactive {
    color: #b02a37;
    background-color: #fcdcdf;
}

/* ---- Inline row action icons (edit / toggle / delete) ---- */
.hostel-building-index .hb-row-actions {
    line-height: 1;
}

.hostel-building-index .hb-icon-btn {
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

.hostel-building-index .hb-icon-btn .material-symbols-rounded {
    font-size: 20px;
    line-height: 1;
}

.hostel-building-index .hb-icon-edit {
    color: var(--bs-primary, #4f46e5);
}

.hostel-building-index .hb-icon-edit:hover {
    background: rgba(var(--bs-primary-rgb, 79 70 229), 0.12);
    transform: translateY(-1px);
}

.hostel-building-index .hb-icon-delete {
    color: #dc3545;
}

.hostel-building-index .hb-icon-delete:hover:not(:disabled) {
    background: rgba(220, 53, 69, 0.12);
    transform: translateY(-1px);
}

.hostel-building-index .hb-icon-btn:disabled {
    color: #c4c9d0;
    cursor: not-allowed;
    opacity: 1;
}

/* Status toggle inside the action cell — green when on (matches reference) */
.hostel-building-index .hb-row-switch {
    padding-left: 2.4em;
}

.hostel-building-index .hb-row-switch .form-check-input {
    width: 2.1em;
    height: 1.15em;
    cursor: pointer;
    margin-top: 0.15em;
}

.hostel-building-index .hb-row-switch .form-check-input:checked {
    background-color: #1fae5b;
    border-color: #1fae5b;
}

.hostel-building-index .hb-row-switch .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(31, 174, 91, 0.2);
    border-color: #1fae5b;
}

/* ---- Relocated DataTables chrome: toolbar + footer (reference layout) ---- */
.hostel-building-index .hb-toolbar {
    margin-bottom: var(--ds-space-3, 1rem);
}

.hostel-building-index .hb-footer {
    margin-top: var(--ds-space-3, 1rem);
}

/* Search box — rounded, magnifier icon, "Search" placeholder */
.hostel-building-index .hb-toolbar .dataTables_filter {
    margin: 0;
}

.hostel-building-index .hb-toolbar .dataTables_filter label {
    margin: 0;
    font-size: 0;
    /* hides the native "Search:" label text */
    display: block;
}

.hostel-building-index .hb-toolbar .dataTables_filter input {
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

.hostel-building-index .hb-toolbar .dataTables_filter input::placeholder {
    color: #98a2b3;
}

.hostel-building-index .hb-toolbar .dataTables_filter input:focus {
    outline: 0;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
}

/* Toolbar buttons (Columns / Print) */
.hostel-building-index .hb-btn {
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

.hostel-building-index .hb-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--bs-primary);
}

.hostel-building-index .hb-btn .material-symbols-rounded {
    font-size: 18px;
    line-height: 1;
}

.hostel-building-index .hb-btn.dropdown-toggle::after {
    margin-left: 0.2rem;
}

.hostel-building-index .hb-btn-columns.show {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: #fff;
}

.hostel-building-index .hb-toolbar .dt-buttons {
    margin: 0;
}

.hostel-building-index .hb-toolbar .dt-buttons .dt-button {
    margin: 0;
}

/* Column Visibility modal (appended to <body>, so NOT scoped to the page) */
.hb-cols-modal .modal-content {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
}

.hb-cols-modal .modal-title {
    font-size: 1.15rem;
    color: #1f2937;
}

.hb-cols-modal hr {
    color: #e5e7eb;
    opacity: 1;
}

.hb-cols-modal .hb-col-chip {
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

.hb-cols-modal .hb-col-chip:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.hb-cols-modal .hb-col-chip .form-check-input {
    cursor: pointer;
    flex-shrink: 0;
}

.hb-cols-modal .hb-col-chip .form-check-input:checked {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

/* Footer: pagination (left) + count (right) */
.hostel-building-index .hb-footer .dataTables_paginate {
    margin: 0;
}

/* Pagination — rounded buttons, outlined active page, chevron prev/next.
       These live outside .dataTables_wrapper now, so the global pill rules no
       longer apply; this block fully owns the pagination look for this page. */
.hostel-building-index .hb-footer .pagination {
    margin: 0;
    gap: 0.3rem;
    align-items: center;
    flex-wrap: wrap;
}

.hostel-building-index .hb-footer .page-item {
    margin: 0;
}

.hostel-building-index .hb-footer .page-link {
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

.hostel-building-index .hb-footer .page-link:hover {
    background: #f1f5f9;
    color: var(--bs-primary);
}

.hostel-building-index .hb-footer .page-item.active .page-link {
    background: #fff;
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.hostel-building-index .hb-footer .page-item.disabled .page-link {
    color: #cbd5e1;
    background: transparent;
}

.hostel-building-index .hb-footer .page-link:focus {
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.18);
}

/* Replace "Previous"/"Next" text with chevron glyphs */
.hostel-building-index .hb-footer .paginate_button.previous .page-link,
.hostel-building-index .hb-footer .paginate_button.next .page-link {
    font-size: 0;
}

.hostel-building-index .hb-footer .paginate_button.previous .page-link::before {
    content: '\2039';
    font-size: 1.45rem;
}

.hostel-building-index .hb-footer .paginate_button.next .page-link::before {
    content: '\203A';
    font-size: 1.45rem;
}

/* "Showing [N] of TOTAL items" count */
.hostel-building-index .hb-footer .hb-count {
    font-size: 0.875rem;
    color: var(--ds-ink-muted, #64748b);
    white-space: nowrap;
}

.hostel-building-index .hb-footer .hb-count-select {
    width: auto;
    min-width: 4.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
}

@media (max-width: 575.98px) {
    .hostel-building-index .hb-toolbar {
        justify-content: flex-start;
    }

    .hostel-building-index .hb-toolbar .dataTables_filter,
    .hostel-building-index .hb-toolbar .dataTables_filter input {
        width: 100%;
    }

    .hostel-building-index .hb-footer {
        justify-content: center;
    }
}

/* ---- Add / Edit modal (appended in-page; class-scoped, not page-scoped) ---- */
.hb-form-modal .modal-content {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
}
.hb-form-modal .modal-title { font-size: 1.2rem; color: #1f2937; }
.hb-form-modal hr { color: #e5e7eb; opacity: 1; }
.hb-form-modal .form-label { font-size: 0.875rem; color: #344054; margin-bottom: 0.35rem; }
.hb-form-modal .form-control,
.hb-form-modal .form-select {
    height: 44px;
    border-radius: 0.6rem;
    border-color: #e5e7eb;
    font-size: 0.9rem;
}
.hb-form-modal .form-control:focus,
.hb-form-modal .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
}
.hb-form-modal .modal-footer .btn { font-weight: 600; }
</style>
@endsection
@push('scripts')
{{ $dataTable->scripts() }}
<script>
/*
 * Frontend-only UI enhancer for the Hostel Building DataTable.
 * It runs AFTER the server-side (Yajra) table initialises and only relocates /
 * restyles the native controls — search, length, info, pagination — into a
 * reference-matched toolbar + footer, and adds a Columns toggle and Print button
 * via the DataTables client API. No data, AJAX, routes or column logic is touched;
 * the native nodes are moved (not recreated) so all existing handlers keep working.
 */
(function() {
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) {
        return;
    }

    var TID = 'hostelbuildingmaster-table';

    function enhance() {
        var $table = $('#' + TID);
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) {
            return;
        }

        var el = $table.get(0);
        if (el._hbEnhanced) {
            return;
        } // guard against double-run
        el._hbEnhanced = true;

        var api = $table.DataTable();
        var $wrapper = $('#' + TID + '_wrapper');
        if (!$wrapper.length) {
            return;
        }

        // Keep the toolbar/footer OUTSIDE .table-responsive so the Columns
        // dropdown is not clipped by its horizontal overflow.
        var $responsive = $table.closest('.table-responsive');
        var $host = $responsive.length ? $responsive : $wrapper;

        var $length = $wrapper.find('.dataTables_length');
        var $filter = $wrapper.find('.dataTables_filter');
        var $info = $wrapper.find('.dataTables_info');
        var $paginate = $wrapper.find('.dataTables_paginate');

        // Remember the original control rows so we can drop them once emptied.
        var $topRow = $filter.closest('.row');
        var $botRow = $paginate.closest('.row');

        // ---- Top toolbar (right aligned): Columns + Search ----
        var $toolbar = $(
            '<div class="hb-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2"></div>');
        $toolbar.insertBefore($host);

        // Columns button — opens a "Column Visibility" modal (matches design).
        var modalId = TID + '-cols-modal';
        var $modal = $(
            '<div class="modal fade hb-cols-modal" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
            '<div class="modal-dialog modal-dialog-centered modal-lg">' +
            '<div class="modal-content">' +
            '<div class="modal-header border-0 pb-2">' +
            '<h5 class="modal-title fw-semibold">Column Visibility</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '</div>' +
            '<div class="modal-body pt-0">' +
            '<hr class="mt-0 mb-3">' +
            '<div class="row g-2 hb-cols-grid"></div>' +
            '</div>' +
            '<div class="modal-footer border-0 pt-0">' +
            '<button type="button" class="btn btn-outline-primary px-4 rounded-3" data-bs-dismiss="modal">Close</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
        var $grid = $modal.find('.hb-cols-grid');
        api.columns().every(function(idx) {
            var col = this;
            var title = $(col.header()).text().trim() || ('Column ' + (idx + 1));
            var $cell = $('<div class="col-6 col-md-4"></div>');
            var $chip = $(
                '<label class="hb-col-chip">' +
                '<input type="checkbox" class="form-check-input m-0"' + (col.visible() ? ' checked' :
                    '') + '>' +
                '<span></span></label>'
            );
            $chip.find('span').text(title);
            $chip.find('input').on('change', function() {
                col.visible($(this).is(':checked'));
            });
            $cell.append($chip);
            $grid.append($cell);
        });
        $('body').append($modal);

        var $colsBtn = $(
            '<button type="button" class="btn hb-btn hb-btn-columns d-inline-flex align-items-center gap-1">' +
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
                    buttons: [{
                        extend: 'print',
                        title: 'Building Master'
                    }]
                });
                var $printContainer = $(printButtons.container()).addClass('d-none');
                $wrapper.append($printContainer); // hidden — triggered by the top button
                $('#hbPrintBtn').on('click', function() {
                    $printContainer.find('.dt-button').first().trigger('click');
                });
            } catch (e) {
                $('#hbPrintBtn').on('click', function() {
                    window.print();
                });
            }
        } else {
            $('#hbPrintBtn').on('click', function() {
                window.print();
            });
        }

        // Relocate native search into the toolbar (keeps its keyup handler).
        $filter.appendTo($toolbar);
        $filter.find('input').addClass('form-control').attr('placeholder', 'Search');

        // ---- Footer: pagination (left) + "Showing N of TOTAL items" (right) ----
        var $footer = $(
            '<div class="hb-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>');
        $footer.insertAfter($host);

        // Custom count built around the native length <select> (keeps its handler).
        var $lenSelect = $length.find('select').addClass('form-select form-select-sm hb-count-select');
        var $count = $('<div class="hb-count d-inline-flex align-items-center gap-2"></div>');
        $count.append($('<span>Showing</span>'));
        $count.append($lenSelect);
        var $countText = $('<span class="hb-count-text"></span>');
        $count.append($countText);

        $footer.append($paginate).append($count);

        function hbUpdateCount() {
            try {
                var info = api.page.info();
                $countText.text('of ' + info.recordsDisplay + ' items');
            } catch (e) {
                /* noop */ }
        }
        api.on('draw', hbUpdateCount);
        hbUpdateCount();

        // Drop the now-empty original control rows + leftover length wrapper.
        $length.remove();
        $topRow.remove();
        $botRow.remove();
    }

    // Catch the init event (bubbles to document) — registered before Yajra runs init.
    $(document).on('init.dt', '#' + TID, function() {
        enhance();
    });
    // Fallback in case the table was already initialised by the time we run.
    $(function() {
        enhance();
    });
})();

/*
 * Add / Edit in a modal. The form still POSTs to the existing store route
 * (server-side create/update via the hidden pk); no AJAX, no route changes.
 * Edit values are read from data-* attributes on the row's edit icon, so no
 * extra request is needed. The original create/edit pages remain as fallbacks.
 */
(function() {
    var $ = window.jQuery;
    if (!$) { return; }

    function modal() {
        var el = document.getElementById('hbFormModal');
        return (el && window.bootstrap && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }
    function clearInvalid() {
        $('#hbBuildingForm .is-invalid').removeClass('is-invalid');
    }
    function setMode(isEdit) {
        document.getElementById('hbFormModalTitle').textContent = isEdit ? 'Edit Hostel Building' : 'Add Hostel Building';
        document.getElementById('hbFormSubmit').textContent = isEdit ? 'Update' : 'Add Hostel Building';
    }

    // --- Add ---
    $(document).on('click', '#hbAddBtn', function() {
        var form = document.getElementById('hbBuildingForm');
        if (form) { form.reset(); }
        document.getElementById('hbPk').value = '';
        document.getElementById('hbStatus').value = '1';
        clearInvalid();
        setMode(false);
        var m = modal(); if (m) { m.show(); }
    });

    // --- Edit (values come from the row's edit icon data-* attributes) ---
    $(document).on('click', '.hb-edit-trigger', function(e) {
        e.preventDefault();
        var $b = $(this);
        document.getElementById('hbPk').value = $b.attr('data-pk') || '';
        document.getElementById('hbBuildingName').value = $b.attr('data-name') || '';
        document.getElementById('hbNoFloors').value = $b.attr('data-floors') || '';
        document.getElementById('hbNoRooms').value = $b.attr('data-rooms') || '';
        document.getElementById('hbBuildingType').value = $b.attr('data-type') || '';
        document.getElementById('hbStatus').value = $b.attr('data-status') || '1';
        clearInvalid();
        setMode(true);
        var m = modal(); if (m) { m.show(); }
    });

    // Re-open the modal automatically when the server bounced back with errors.
    @if($errors->any())
    $(function() {
        setMode({{ old('pk') ? 'true' : 'false' }});
        var m = modal(); if (m) { m.show(); }
    });
    @endif
})();
</script>
@endpush