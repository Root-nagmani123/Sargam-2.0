@extends('admin.layouts.master')

@section('title', 'Course Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<style>
/* Fix dropdown visibility in table */
.table-responsive {
    overflow: visible !important;
}

.table td {
    overflow: visible !important;
    vertical-align: middle;
}

.action-dropdown {
    position: static;
}

.action-dropdown .dropdown-menu {
    z-index: 1050 !important;
    position: fixed !important;
}

/* Ensure dropdown items are clickable */
.dropdown-item {
    cursor: pointer;
}

.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
    /* Reset for pill-style container */
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

/* Hover + Active States */
.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

/* Accessibility: Focus ring */
.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

/* Choices + Bootstrap integration for course filter */
.programme-choices-bootstrap .choices__inner.form-select {
    background-color: var(--bs-body-bg);
    border: var(--bs-border-width) solid var(--bs-border-color);
    min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
    background-image: none !important;
    padding-inline-end: 2.25rem;
}

.programme-choices-bootstrap .choices.is-focused .choices__inner.form-select,
.programme-choices-bootstrap .choices.is-open .choices__inner.form-select {
    border-color: var(--bs-focus-border-color);
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
}

.programme-choices-bootstrap .choices__list--dropdown.dropdown-menu,
.programme-choices-bootstrap .choices__list[aria-expanded].dropdown-menu {
    border: var(--bs-border-width) solid var(--bs-border-color);
}

/* ===================== Modern UI enhancement ===================== */

/* Choices control sizing to match the toolbar */
.programme-choices-bootstrap .choices { margin-bottom: 0; }
.programme-choices-bootstrap .choices__inner.form-select {
    border-radius: 8px;
    border-color: #d8dde5;
    min-height: 42px;
}

/* ===== Page tabs (Active / Archived) ===== */
.pm-tabs {
    display: inline-flex;
    gap: 6px;
    background: #fff;
    border: 1px solid #e7eaee;
    border-radius: 12px;
    padding: 5px;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
}
/* Neutralise the Bootstrap colour classes the JS toggles; impose the segmented look. */
.pm-tabs .btn {
    border: none !important;
    border-radius: 8px !important;
    background: transparent !important;
    color: #5a6a7e !important;
    font-weight: 600;
    font-size: 14px;
    margin: 0 !important;
    padding: 8px 28px !important;
    box-shadow: none !important;
    transform: none !important;
    transition: background .18s ease, color .18s ease;
}
.pm-tabs .btn:hover { background: #f1f5f9 !important; color: #1a3255 !important; }
.pm-tabs .btn.active {
    background: #0d47a1 !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(13, 71, 161, .25) !important;
}
.pm-tabs .btn .bi { font-size: 14px; }

/* ===== Card ===== */
.programme-index .pm-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
    overflow: hidden;
}

/* ===== Toolbar ===== */
.pm-toolbar { background: #fff; border-bottom: 1px solid #eef0f3; }
.pm-filters-text { font-size: 14px; font-weight: 500; color: #8a93a2; margin-right: 2px; }
.pm-select-wrap { min-width: 220px; max-width: 280px; }

.pm-reset-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px;
    border: 1px solid #e35d6a; border-radius: 8px;
    background: #fff; color: #d6293e;
    font-size: 14px; font-weight: 600; line-height: 1.2;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.pm-reset-btn:hover { background: #fdecee; color: #b71d2b; border-color: #d6293e; }
.pm-reset-btn .bi { font-size: 14px; }

.pm-columns-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 14px;
    border: 1px solid #d8dde5; border-radius: 8px;
    background: #fff; color: #475467;
    font-size: 14px; font-weight: 500; line-height: 1.2; cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}
.pm-columns-btn:hover { border-color: #b6bfca; background: #f8fafc; }
.pm-columns-btn i { font-size: 16px; color: #667085; }

/* Expandable search */
.pm-search-wrap { display: flex; align-items: center; justify-content: flex-end; }
.pm-search-field {
    width: 0; opacity: 0; padding: 0;
    border: 1px solid transparent; border-radius: 8px;
    font-size: 14px; background: #fff; outline: none;
    transition: width .25s ease, opacity .2s ease, padding .25s ease, border-color .15s ease;
}
.pm-search-wrap.expanded .pm-search-field {
    width: 230px; opacity: 1; padding: 9px 12px; margin-right: 8px; border-color: #d8dde5;
}
.pm-search-wrap.expanded .pm-search-field:focus { border-color: #86b7fe; box-shadow: 0 0 0 3px rgba(13, 110, 253, .12); }
.pm-search-btn {
    flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    width: 42px; height: 42px; border: 1px solid #d8dde5; border-radius: 8px;
    background: #fff; color: #667085; cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.pm-search-btn:hover { border-color: #b6bfca; background: #f8fafc; color: #344054; }
.pm-search-btn i { font-size: 18px; }

/* ===== Table ===== */
.programme-index #coursemaster-table thead th,
.programme-index .dataTables_wrapper thead th {
    font-size: 13px; font-weight: 500; color: #98a2b3;
    text-transform: none; letter-spacing: normal;
    padding: 14px 18px; border-bottom: 1px solid #eef0f3;
    background: #fcfcfd; white-space: nowrap; vertical-align: middle;
}
.programme-index #coursemaster-table tbody td {
    font-size: 14px; color: #475467;
    padding: 14px 18px; border-bottom: 1px solid #f2f4f7; vertical-align: middle;
}
.programme-index #coursemaster-table tbody tr { transition: background-color .15s ease; }
.programme-index #coursemaster-table tbody tr:hover { background: #f9fafb; }
.programme-index #coursemaster-table tbody td:first-child { color: #667085; width: 64px; }

/* Status badge */
.pm-status-badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 5px 14px; border-radius: 999px;
    font-size: 13px; font-weight: 600; line-height: 1.2; white-space: nowrap;
}
.pm-status-active { background: #e7f7ed; color: #1a8245; }
.pm-status-inactive { background: #fdecee; color: #d6293e; }

/* Status toggle (now in the Action column) */
.programme-index #coursemaster-table .status-toggle {
    width: 38px; height: 20px; cursor: pointer; margin: 0;
    border-color: #cbd2dc; background-color: #cbd2dc;
}
.programme-index #coursemaster-table .status-toggle:checked { background-color: #16a34a; border-color: #16a34a; }
.programme-index #coursemaster-table .status-toggle:focus { box-shadow: 0 0 0 3px rgba(22, 163, 74, .18); border-color: #16a34a; }

/* Action icons */
.programme-index #coursemaster-table td .d-inline-flex .btn { transition: transform .15s ease; }
.programme-index #coursemaster-table td .d-inline-flex .btn:hover { transform: translateY(-1px); }
.programme-index #coursemaster-table td .material-symbols-rounded { font-size: 19px !important; }
.programme-index #coursemaster-table .btn-outline-secondary .material-symbols-rounded { color: #5a6a7e; }
.programme-index #coursemaster-table .btn-outline-primary .material-symbols-rounded { color: #2f6fed; }
.programme-index #coursemaster-table .btn-outline-danger .material-symbols-rounded { color: #e5484d !important; }

/* ===== Column Visibility modal ===== */
.pm-columns-modal .modal-content { border: none; border-radius: 16px; box-shadow: 0 24px 48px rgba(16, 24, 40, .18); overflow: hidden; }
.pm-columns-modal .modal-header { border-bottom: 1px solid #eceff3; padding: 22px 28px; }
.pm-columns-modal .modal-title { font-size: 22px; font-weight: 700; color: #101828; }
.pm-columns-modal .modal-body { padding: 24px 28px; }
.pm-columns-modal .modal-footer { border-top: 0; padding: 8px 28px 26px; }
.pm-col-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.pm-col-chip {
    display: flex; align-items: center; gap: 12px; margin: 0; padding: 16px 18px;
    border: 1px solid #d5dae1; border-radius: 12px; font-size: 16px; font-weight: 500;
    color: #1d2939; cursor: pointer; background: #fff;
    transition: border-color .15s ease, background .15s ease;
}
.pm-col-chip:hover { border-color: #9cc2ee; background: #f7faff; }
.pm-col-chip.is-checked { border-color: #9cc2ee; background: #f7faff; }
.pm-col-chip .form-check-input { width: 20px; height: 20px; margin: 0; flex-shrink: 0; border-color: #98a2b3; border-radius: 5px; cursor: pointer; }
.pm-col-chip .form-check-input:checked { background-color: #2f6fed; border-color: #2f6fed; }
.pm-modal-close-btn { border: 1px solid #2f6fed; color: #2f6fed; background: #fff; font-weight: 600; font-size: 15px; padding: 10px 26px; border-radius: 12px; transition: background .15s ease, color .15s ease; }
.pm-modal-close-btn:hover { background: #2f6fed; color: #fff; }

/* ===== DataTables footer / search / pagination ===== */
.programme-index .dataTables_wrapper .dataTables_filter { display: none !important; }
/* Hide the empty top toolbar row (default search box lives there but is hidden) */
.programme-index #coursemaster-table_wrapper > .row:first-child { display: none !important; }
.programme-index .dt-bottom-bar { padding: 14px 18px; border-top: 1px solid #eef0f3; margin-top: 0 !important; }
.programme-index .pm-showing-label { font-size: 13px; color: #667085; font-weight: 500; }
.programme-index .dataTables_wrapper .dataTables_info { font-size: 13px; color: #667085; font-weight: 500; padding: 0 !important; margin: 0 !important; }
.programme-index .dataTables_wrapper .dataTables_length { margin: 0 !important; }
.programme-index .dataTables_wrapper .dataTables_length select {
    border: 1px solid #d8dde5; border-radius: 8px; padding: 5px 10px; font-size: 13px; margin: 0 6px; color: #344054;
}
.programme-index .dataTables_wrapper .dataTables_paginate { margin: 0 !important; }
.programme-index .dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px !important; margin: 0 3px !important;
    border: 1px solid transparent !important; border-radius: 8px !important;
    font-size: 13px !important; color: #667085 !important; background: transparent !important;
    min-width: 34px; text-align: center; transition: none !important;
}
.programme-index .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 1px solid #2f6fed !important; color: #2f6fed !important; font-weight: 600 !important; background: #fff !important;
}
.programme-index .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: #f2f4f7 !important; color: #344054 !important; border-color: #eaecf0 !important;
}
.programme-index .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.programme-index .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    color: #c3c9d2 !important; background: transparent !important; border-color: transparent !important;
}

/* ===== Responsive ===== */
@media (max-width: 991.98px) {
    .pm-toolbar .ms-auto { width: 100%; justify-content: flex-start !important; margin-left: 0 !important; }
    .pm-select-wrap { min-width: 0; max-width: none; flex: 1 1 200px; }
}
@media (max-width: 767.98px) {
    .programme-index #coursemaster-table thead th,
    .programme-index #coursemaster-table tbody td { padding: 11px 12px; font-size: 12.5px; }
    .pm-search-wrap.expanded .pm-search-field { width: 150px; }
    .pm-col-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .programme-index .dt-bottom-bar { row-gap: 12px; }
}
@media (max-width: 480px) {
    .pm-col-grid { grid-template-columns: 1fr; }
}

/* ===== Confirmation modals (SweetAlert2) ===== */
.pm-swal.swal2-popup {
    border-radius: 18px;
    padding: 30px 26px 24px;
    width: 30em;
    max-width: 92vw;
}
.pm-swal .swal2-icon { margin-top: 6px; margin-bottom: 6px; }
.pm-swal .swal2-title { font-size: 22px; font-weight: 700; color: #101828; padding: 10px 0 0; }
.pm-swal .swal2-html-container { font-size: 15px; color: #667085; margin: 6px 0 0; }
.pm-swal .swal2-actions {
    width: 100%;
    gap: 12px;
    margin-top: 24px;
    padding: 0 4px;
    flex-wrap: nowrap;
}
.pm-swal .swal2-actions button { flex: 1 1 0; margin: 0; }
.pm-swal-confirm, .pm-swal-cancel {
    padding: 11px 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    line-height: 1.2;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.pm-swal-confirm-blue { background: #0d47a1; color: #fff; border-color: #0d47a1; }
.pm-swal-confirm-blue:hover { background: #0b3c8a; }
.pm-swal-confirm-red { background: #e5484d; color: #fff; border-color: #e5484d; }
.pm-swal-confirm-red:hover { background: #d23b40; }
.pm-swal-cancel-blue { background: #fff; color: #0d47a1; border-color: #9cc2ee; }
.pm-swal-cancel-blue:hover { background: #f3f8ff; border-color: #0d47a1; }
.pm-swal-cancel-red { background: #fff; color: #e5484d; border-color: #f1b0b3; }
.pm-swal-cancel-red:hover { background: #fdf2f2; border-color: #e5484d; }
</style>
<div class="container-fluid programme-index">
    <x-breadcrum title="Course Master">
        <a href="{{ route('programme.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Course</span>
        </a>
    </x-breadcrum>

    {{-- ===== Status tabs (Active / Archived) — top ===== --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="pm-tabs btn-group" role="group" aria-label="Filter courses by status">
            <button type="button" class="btn btn-success px-4 fw-semibold active" id="filterActive"
                aria-pressed="true" aria-current="true">
                <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                <span>Active</span>
            </button>
            <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="filterArchive" aria-pressed="false">
                <i class="bi bi-archive me-1" aria-hidden="true"></i>
                <span>Archived</span>
            </button>
        </div>
    </div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card border-0 pm-card">
            <div class="card-body p-0">

                {{-- ===== Toolbar ===== --}}
                <div class="pm-toolbar d-flex flex-wrap align-items-center gap-2 px-3 px-lg-4 py-3 programme-choices-bootstrap">
                    <span class="pm-filters-text">Filters</span>

                    <div class="pm-select-wrap">
                        <label for="courseFilter" class="visually-hidden">Course Name</label>
                        <select id="courseFilter" class="form-control js-programme-choice rounded-1">
                            <option value="">Course Name</option>
                            @foreach($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="pm-reset-btn" id="resetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        {{-- Columns visibility --}}
                        <button type="button" id="pmColumnsBtn" class="pm-columns-btn"
                                data-bs-toggle="modal" data-bs-target="#pmColumnsModal">
                            <span>Columns</span><i class="material-icons material-symbols-rounded">view_column</i>
                        </button>

                        {{-- Expandable search (drives the DataTable search) --}}
                        <div class="pm-search-wrap" id="pmSearchWrap">
                            <input id="pmSearchInput" type="text" class="pm-search-field"
                                   placeholder="Search course, short name, year…" autocomplete="off">
                            <button type="button" class="pm-search-btn" id="pmSearchToggle" aria-label="Search">
                                <i class="material-icons material-symbols-rounded">search</i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ===== Table (server-side Yajra DataTable) ===== --}}
                <div class="table-responsive pm-table-scroll">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0']) !!}
                </div>

            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>

    {{-- ===== Column Visibility modal ===== --}}
    <div class="modal fade pm-columns-modal" id="pmColumnsModal" tabindex="-1" aria-labelledby="pmColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pmColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="pm-col-grid" id="pmColumnsGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn pm-modal-close-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course View Modal -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewCourseModalLabel">Course Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="courseDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading course details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
{!! $dataTable->scripts() !!}

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    var table;
    var currentFilter = 'active'; // Set Active as default
    var courseChoices = null;

    var programmeChoiceOpts = {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        classNames: {
            containerOuter: ['choices', 'w-100'],
            containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
            input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
            inputCloned: ['choices__input--cloned'],
            list: ['choices__list'],
            listItems: ['choices__list--multiple'],
            listSingle: ['choices__list--single'],
            listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
            item: ['choices__item', 'dropdown-item', 'rounded-0'],
            itemSelectable: ['choices__item--selectable'],
            itemDisabled: ['choices__item--disabled', 'disabled'],
            itemChoice: ['choices__item--choice'],
            description: ['choices__description', 'small', 'text-muted'],
            placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
            group: ['choices__group'],
            groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
            button: ['choices__button'],
            activeState: ['is-active'],
            focusState: ['is-focused'],
            openState: ['is-open'],
            disabledState: ['is-disabled'],
            highlightedState: ['is-highlighted', 'active'],
            flippedState: ['is-flipped'],
            loadingState: ['is-loading'],
            invalidState: ['is-invalid'],
            notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
            addChoice: ['choices__item--selectable', 'add-choice'],
            noResults: ['has-no-results'],
            noChoices: ['has-no-choices'],
        }
    };

    function initCourseFilterChoices() {
        if (typeof Choices === 'undefined') {
            return;
        }

        var courseFilterEl = document.getElementById('courseFilter');
        if (!courseFilterEl || courseFilterEl.dataset.choicesInitialized === 'true') {
            return;
        }

        courseChoices = new Choices(courseFilterEl, programmeChoiceOpts);
        courseFilterEl._choicesInstance = courseChoices;
        courseFilterEl.dataset.choicesInitialized = 'true';
    }

    function rebuildCourseFilterChoices() {
        if (typeof Choices === 'undefined') {
            return;
        }

        var courseFilterEl = document.getElementById('courseFilter');
        if (!courseFilterEl || !courseFilterEl._choicesInstance) {
            initCourseFilterChoices();
            return;
        }

        courseFilterEl._choicesInstance.destroy();
        courseFilterEl.dataset.choicesInitialized = 'false';
        courseFilterEl._choicesInstance = null;
        courseChoices = null;
        initCourseFilterChoices();
    }

    // Wait for DataTable to be initialized
    setTimeout(function() {
        table = $('#coursemaster-table').DataTable();
        initCourseFilterChoices();

        // Initialize dropdowns after table loads
        initializeDropdowns();

        // Set initial active state - Active button is already styled as active in HTML
        // No need to change styling initially

        // Function to load courses by status
        function loadCoursesByStatus(status) {
            $.ajax({
                url: '{{ route("programme.get.courses.by.status") }}',
                type: 'GET',
                data: {
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        var courseFilter = $('#courseFilter');
                        var currentValue = courseFilter.val();

                        // Clear existing options except "All Courses"
                        courseFilter.find('option:not(:first)').remove();

                        // Add new course options
                        $.each(response.courses, function(pk, name) {
                            courseFilter.append($('<option>', {
                                value: pk,
                                text: name
                            }));
                        });

                        // Reset to "All Courses" when status changes
                        courseFilter.val('');
                        rebuildCourseFilterChoices();

                        // Reload table
                        table.ajax.reload();
                        initializeDropdowns();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading courses:', xhr);
                }
            });
        }

        // Filter button click handlers
        $('#filterActive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'active';
            loadCoursesByStatus('active');
        });

        $('#filterArchive').on('click', function() {
            setActiveButton($(this));
            currentFilter = 'archive';
            loadCoursesByStatus('archive');
        });

        // Function to initialize dropdowns
        function initializeDropdowns() {
            var dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdownElementList.forEach(function(dropdownToggleEl) {
                // Dispose of existing dropdown instance if any
                try {
                    var existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                    if (existingDropdown) {
                        existingDropdown.dispose();
                    }
                } catch (e) {
                    // Instance doesn't exist, continue
                }

                // Create new dropdown instance
                try {
                    new bootstrap.Dropdown(dropdownToggleEl);
                } catch (e) {
                    console.error('Error initializing dropdown:', e);
                }
            });
        }

        // Function to set active button styling
        function setActiveButton(activeBtn) {
            // Reset all buttons to outline style
            $('#filterActive')
                .removeClass('btn-success active text-white')
                .addClass('btn-outline-success')
                .attr('aria-pressed', 'false');

            $('#filterArchive')
                .removeClass('btn-secondary active text-white')
                .addClass('btn-outline-secondary')
                .attr('aria-pressed', 'false');

            // Set the active button
            if (activeBtn.attr('id') === 'filterActive') {
                activeBtn.removeClass('btn-outline-success')
                    .addClass('btn-success text-white active')
                    .attr('aria-pressed', 'true');
            } else if (activeBtn.attr('id') === 'filterArchive') {
                activeBtn.removeClass('btn-outline-secondary')
                    .addClass('btn-secondary text-white active')
                    .attr('aria-pressed', 'true');
            }
        }

        // Pass filter parameter to server
        $('#coursemaster-table').on('preXhr.dt', function(e, settings, data) {
            data.status_filter = currentFilter;
            var courseFilter = $('#courseFilter').val();
            if (courseFilter) {
                data.course_filter = courseFilter;
            }
        });

        // Reinitialize dropdowns after table draw
        $('#coursemaster-table').on('draw.dt', function() {
            initializeDropdowns();
        });

        // Handle dropdown toggle with event delegation
        $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
            // Bootstrap will handle the toggle, just ensure it's initialized
            var el = this;
            if (!bootstrap.Dropdown.getInstance(el)) {
                new bootstrap.Dropdown(el);
            }
        });

        // Handle course filter change
        $('#courseFilter').on('change', function() {
            table.ajax.reload();
            initializeDropdowns();
        });

        // Handle reset filters
        $('#resetFilters').on('click', function() {
            $('#courseFilter').val('');
            rebuildCourseFilterChoices();
            currentFilter = 'active'; // Reset to active by default
            setActiveButton($('#filterActive'));
            loadCoursesByStatus('active');
        });

        // Handle view course button click
        $(document).on('click', '.view-course-btn', function() {
            var courseId = $(this).data('id');
            console.log('Course ID:', courseId); // Debug log
            loadCourseDetails(courseId);
        });
    }, 100);

    // Function to load course details
    function loadCourseDetails(courseId) {
        var url = '{{ route("programme.view", ":id") }}'.replace(':id', courseId);
        console.log('Request URL:', url); // Debug log

        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function() {
                $('#courseDetailsContent').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading course details...</p>
                        </div>
                    `);
            },
            success: function(response) {
                if (response.success) {
                    var course = response.course;
                    var content = `
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="text-primary mb-4">${course.course_name}</h4>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Short Name:</strong>
                                    <p class="text-muted">${course.course_short_name || 'Not Available'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Course Year:</strong>
                                    <p class="text-muted">${course.course_year || 'Not Available'}</p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Start Date:</strong>
                                    <p class="text-muted">${course.start_date}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>End Date:</strong>
                                    <p class="text-muted">${course.end_date}</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Coordinator:</strong>
                                    <div class="d-flex align-items-center mt-2">
                                        ${course.coordinator_photo ? 
                                            `<img src="${course.coordinator_photo}" alt="Coordinator Photo" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${course.coordinator_name}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Assistant Coordinators:</strong>
                                    <div class="mt-2">
                        `;

                    if (course.assistant_coordinators && course.assistant_coordinators.length > 0) {
                        course.assistant_coordinators.forEach(function(coordinator, index) {
                            var photo = course.assistant_coordinator_photos[index] || null;
                            content += `
                                    <div class="d-flex align-items-center mb-2">
                                        ${photo ? 
                                            `<img src="${photo}" alt="Assistant Coordinator Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${coordinator}</span>
                                    </div>
                                `;
                        });
                    } else {
                        content += '<p class="text-muted">No Assistant Coordinators assigned</p>';
                    }

                    content += `
                                    </div>
                                </div>
                            </div>
                        `;

                    $('#courseDetailsContent').html(content);
                } else {
                    $('#courseDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                ${response.message || 'Failed to load course details'}
                            </div>
                        `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });

                var errorMessage = 'Error loading course details. Please try again.';
                if (xhr.status === 404) {
                    errorMessage = 'Course not found.';
                } else if (xhr.status === 400) {
                    errorMessage = 'Invalid course ID.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }

                $('#courseDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${errorMessage}
                        </div>
                    `);
            }
        });
    }
});
</script>

{{-- ===== Frontend-only UI wiring: expandable search, Columns modal, footer layout ===== --}}
<script>
(function () {
    function onReady(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    onReady(function () {
        var $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.dataTable) return;

        var tableSel = '#coursemaster-table';

        function getDT() {
            var el = document.querySelector(tableSel);
            if (el && $.fn.dataTable.isDataTable(el)) return $(el).DataTable();
            return null;
        }

        /* --- Expandable search (drives the server-side DataTable search) --- */
        var wrap = document.getElementById('pmSearchWrap');
        var toggle = document.getElementById('pmSearchToggle');
        var input = document.getElementById('pmSearchInput');
        if (toggle && wrap) {
            toggle.addEventListener('click', function () {
                wrap.classList.toggle('expanded');
                if (wrap.classList.contains('expanded') && input) { input.focus(); }
            });
        }
        if (input) {
            input.addEventListener('keyup', function () {
                var dt = getDT();
                if (dt) { dt.search(this.value).draw(); }
            });
        }

        /* --- Styled confirmation modals (Activate / Deactivate / Delete) --- */
        var hasSwal = (typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function');

        function reloadCourseTable() {
            var dt = getDT();
            if (dt) { try { dt.ajax.reload(null, false); } catch (e) {} }
        }

        // Activate / Deactivate — replace the generic global handler on this page only.
        $(document).off('change', '.status-toggle');
        $(document).on('change', '.status-toggle', function () {
            var $cb = $(this);
            var table = $cb.data('table');
            var column = $cb.data('column');
            var id = $cb.data('id');
            var idColumn = $cb.data('id_column');
            var status = $cb.is(':checked') ? 1 : 0;       // 1 = activate, 0 = deactivate
            var activating = status === 1;

            function doToggle() {
                $.ajax({
                    url: routes.toggleStatus,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        table: table, column: column, id: id, id_column: idColumn, status: status
                    },
                    success: function () { reloadCourseTable(); },
                    error: function () {
                        $cb.prop('checked', !status);
                        if (hasSwal) { Swal.fire('Error', 'Status update failed', 'error'); }
                    }
                });
            }

            if (!hasSwal) {
                if (window.confirm('Are you sure you want to ' + (activating ? 'activate' : 'deactivate') + ' this course?')) { doToggle(); }
                else { $cb.prop('checked', !status); }
                return;
            }

            Swal.fire({
                icon: activating ? 'info' : 'warning',
                iconColor: activating ? '#16a34a' : '#f0ad4e',
                title: activating ? 'Activate Course?' : 'Deactivate Course?',
                text: 'Are you sure you want to ' + (activating ? 'activate' : 'deactivate') + ' this course?',
                showCancelButton: true,
                reverseButtons: true,
                buttonsStyling: false,
                focusCancel: true,
                confirmButtonText: activating ? 'Yes, Activate' : 'Yes, Deactivate',
                cancelButtonText: activating ? 'Cancel, Keep it deactive' : 'Cancel, Keep it active',
                customClass: {
                    popup: 'pm-swal',
                    confirmButton: 'pm-swal-confirm pm-swal-confirm-blue',
                    cancelButton: 'pm-swal-cancel pm-swal-cancel-blue'
                }
            }).then(function (result) {
                if (result.isConfirmed) { doToggle(); }
                else { $cb.prop('checked', !status); }   // revert
            });
        });

        // Delete — intercept the row's delete form and confirm with a styled modal.
        $(document).on('submit', 'form.js-course-delete', function (e) {
            var form = this;
            if (form.dataset.pmConfirmed === '1') { return; }   // already confirmed -> allow native submit
            e.preventDefault();

            if (!hasSwal) {
                if (window.confirm('Are you sure you want to delete this course?')) { form.dataset.pmConfirmed = '1'; form.submit(); }
                return;
            }

            Swal.fire({
                icon: 'warning',
                iconColor: '#e5484d',
                title: 'Delete Course?',
                text: 'Are you sure you want to delete this course?',
                showCancelButton: true,
                reverseButtons: true,
                buttonsStyling: false,
                focusCancel: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel, Keep it',
                customClass: {
                    popup: 'pm-swal',
                    confirmButton: 'pm-swal-confirm pm-swal-confirm-red',
                    cancelButton: 'pm-swal-cancel pm-swal-cancel-red'
                }
            }).then(function (result) {
                if (result.isConfirmed) { form.dataset.pmConfirmed = '1'; form.submit(); }
            });
        });

        /* --- Footer: reorder bottom-right group to "Showing [length] of N items" --- */
        function arrangeFooter() {
            var box = document.querySelector('#coursemaster-table_wrapper .dt-bottom-info');
            if (!box || box.dataset.pmArranged) return false;
            var length = box.querySelector('.dataTables_length');
            var info = box.querySelector('.dataTables_info');
            if (!length || !info) return false;
            var label = document.createElement('span');
            label.className = 'pm-showing-label';
            label.textContent = 'Showing';
            box.insertBefore(label, length);   // "Showing" before the length select
            box.appendChild(info);             // move info to the end -> Showing [len] of N items
            box.dataset.pmArranged = '1';
            return true;
        }

        /* --- Column Visibility modal --- */
        var grid = document.getElementById('pmColumnsGrid');
        var modalEl = document.getElementById('pmColumnsModal');
        function buildColumnsGrid() {
            if (!grid || grid.getAttribute('data-built')) return;
            var dt = getDT();
            if (!dt) return;
            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().trim() || ('Column ' + (idx + 1));
                var checked = this.visible();
                var label = document.createElement('label');
                label.className = 'pm-col-chip' + (checked ? ' is-checked' : '');
                label.innerHTML =
                    '<input type="checkbox" class="form-check-input" data-col="' + idx + '" ' +
                        (checked ? 'checked' : '') + '>' +
                    '<span>' + title + '</span>';
                grid.appendChild(label);
            });
            grid.addEventListener('change', function (e) {
                var cb = e.target;
                if (cb && cb.matches && cb.matches('input[data-col]')) {
                    var dt2 = getDT();
                    if (dt2) {
                        dt2.column(parseInt(cb.getAttribute('data-col'), 10)).visible(cb.checked);
                        cb.closest('.pm-col-chip').classList.toggle('is-checked', cb.checked);
                    }
                }
            });
            grid.setAttribute('data-built', '1');
        }
        if (modalEl) { modalEl.addEventListener('show.bs.modal', buildColumnsGrid); }

        /* --- Arrange the footer once the table is ready (and re-apply if redrawn) --- */
        $(document).on('draw.dt', tableSel, arrangeFooter);

        var tries = 0;
        var poll = setInterval(function () {
            tries++;
            if (arrangeFooter() || tries > 40) { clearInterval(poll); }
        }, 150);
    });
})();
</script>
@endpush