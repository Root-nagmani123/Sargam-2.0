@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="{{ asset('css/choices-theme.css') }}?v={{ filemtime(public_path('css/choices-theme.css')) }}">
<style>
/* =====================================================================
   Student Medical Exemption — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Only what Bootstrap utilities can't express lives here.
   ===================================================================== */

/* --- Segmented Active / Archived control ------------------------- */
.sme-segment {
    display: inline-flex;
    gap: var(--ds-space-1);
    padding: var(--ds-space-1);
    background: #fff;
    border-radius: var(--ds-radius-2);
}

.sme-segment .sme-segment-btn {
    border: 0;
    border-radius: var(--ds-radius-1);
    padding: 0.45rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ds-ink-muted);
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
    transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
}

.sme-segment .sme-segment-btn:hover {
    color: var(--ds-ink);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.06);
}

.sme-segment .sme-segment-btn.active {
    background: var(--bs-primary);
    color: #fff;
    box-shadow: var(--ds-shadow-sm);
}

.sme-segment .sme-segment-btn:focus-visible {
    outline: none;
    box-shadow: var(--ds-focus-ring);
}

/* --- Top utility buttons (Print / Download) ---------------------- */
.sme-util-btn {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    padding: 0 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #004a93;
    background: #fff;
    border: 0;
    border-radius: var(--ds-radius-1);
    transition: border-color .15s ease, box-shadow .15s ease, color .15s ease;
}
.sme-util-btn:hover {
    color: var(--bs-primary);
    border-color: var(--bs-primary);
    box-shadow: var(--ds-shadow-sm);
}

/* --- Inline filter toolbar (matches reference) ------------------- */
.sme-filterbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2);
}
.sme-filters-label {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ds-ink);
    margin-right: var(--ds-space-1);
}

/* Shared control footprint so every filter chip lines up exactly */
.sme-filter-control {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
    padding: 0 0.85rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--ds-ink);
    background: #fff;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    line-height: 1;
}
select.sme-filter-control {
    display: inline-block;       /* native select renders best as block */
    min-width: 180px;
    max-width: 240px;
    min-height: 42px;            /* keep level with sibling buttons */
    padding-right: 2.25rem;      /* room for the native chevron */
    text-overflow: ellipsis;
}
.sme-filter-control:hover {
    border-color: #c4ccd6;
}
.sme-filter-control.dropdown-toggle::after {
    margin-left: auto;
}
.sme-icon-btn {
    width: 42px;
    padding: 0;
    justify-content: center;
}
.sme-icon-btn.dropdown-toggle::after { display: none; }

/* Reset = quiet danger, matching the other chips' height */
#resetFilters.sme-filter-control {
    color: var(--bs-danger);
    border-color: var(--bs-danger);
    font-weight: 600;
}
#resetFilters.sme-filter-control:hover {
    background: var(--bs-danger);
    color: #fff;
}

/* Time-period popover holding the dual-month range calendar */
.sme-period-menu { min-width: auto; }

/* --- Dual-month range calendar ----------------------------------- */
.sme-cal { padding: var(--ds-space-3); }
.sme-cal-months {
    display: flex;
    gap: var(--ds-space-4);
}
@media (max-width: 575.98px) {
    .sme-cal-months { flex-direction: column; gap: var(--ds-space-3); }
}
.sme-cal-month { width: 232px; }
.sme-cal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--ds-space-2);
}
.sme-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.sme-cal-nav {
    border: 0;
    background: transparent;
    width: 28px;
    height: 28px;
    border-radius: var(--ds-radius-1);
    color: var(--ds-ink-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.sme-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.sme-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.sme-cal-dow {
    text-align: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--ds-ink-muted);
    padding: 4px 0;
}
.sme-cal-day {
    aspect-ratio: 1 / 1;
    border: 0;
    background: transparent;
    border-radius: var(--ds-radius-1);
    font-size: 0.8125rem;
    color: var(--ds-ink);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.sme-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.sme-cal-day.is-muted { color: var(--ds-ink-muted); opacity: 0.45; }
.sme-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.sme-cal-day.is-start,
.sme-cal-day.is-end {
    background: var(--bs-primary);
    color: #fff;
}
.sme-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.sme-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.sme-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.sme-cal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-2);
    margin-top: var(--ds-space-3);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
.sme-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }

/* --- Add / Edit form modal --------------------------------------- */
/* The injected form carries its own footer (Cancel/Submit); the modal
   provides its own footer instead, so hide the embedded one. */
#smeFormBody .sme-form-footer { display: none !important; }
#smeFormBody .ds-card,
#smeFormBody .ds-card-body { border: 0; box-shadow: none; padding: 0; background: transparent; }
#smeFormBody .sme-section-title:first-child { margin-top: 0; }
#smeFormBody .is-invalid { border-color: var(--bs-danger); }
#smeFormBody .form-control,
#smeFormBody .form-select { min-height: 44px; border-radius: var(--ds-radius-2); }

/* Column Visibility modal — grid of bordered checkbox chips */
.sme-col-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--ds-space-3);
}
.sme-col-chip {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    margin: 0;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    background: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--ds-ink);
    user-select: none;
    transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}
.sme-col-chip:hover {
    border-color: #c4ccd6;
    background: var(--ds-surface-2);
}
.sme-col-chip.is-checked {
    border-color: var(--bs-primary);
    box-shadow: inset 0 0 0 1px var(--bs-primary);
}
.sme-col-chip .form-check-input {
    margin: 0;
    flex-shrink: 0;
    cursor: pointer;
}
.sme-col-chip span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
@media (max-width: 767.98px) {
    .sme-col-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 479.98px) {
    .sme-col-grid { grid-template-columns: 1fr; }
}

/* Always-visible search box with a leading icon */
.sme-search-box {
    position: relative;
    display: inline-flex;
    align-items: center;
}
.sme-search-ico {
    position: absolute;
    left: 12px;
    font-size: 18px;
    color: var(--ds-ink-muted);
    pointer-events: none;
}
.sme-search-field {
    height: 42px;
    width: 240px;
    padding-left: 38px;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    font-size: 0.875rem;
}
.sme-search-field:focus {
    border-color: #86b7fe;
    box-shadow: var(--ds-focus-ring);
}
@media (max-width: 575.98px) {
    .sme-search-field { width: 160px; }
}

/* --- Scrollable table with sticky header ------------------------- */
/* DataTables wraps the table in .sme-scroll (see `dom`), so the scroll
   area holds ONLY the table — pagination/count stay outside it. */
.datatables .sme-scroll {
    max-height: 70vh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
}
.datatables .table-responsive {
    overflow: visible;          /* outer wrapper no longer scrolls */
}

.datatables #medicalExemptionTable {
    min-width: 100%;
    width: max-content;
    margin-bottom: 0;
}

.datatables #medicalExemptionTable thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    white-space: nowrap;
    padding: 12px 14px;
    vertical-align: middle;
}

.datatables #medicalExemptionTable td {
    white-space: nowrap;
    padding: 12px 14px;
    vertical-align: middle;
    font-size: 0.9rem;
    color: var(--ds-ink);
}

/* Row action buttons rendered server-side keep their classes; this
   just gives them a consistent, touch-friendly footprint. */
.datatables #medicalExemptionTable td .btn {
    border-radius: var(--ds-radius-1);
}

/* --- Status pills (server-rendered) ------------------------------ */
.sme-status {
    display: inline-block;
    padding: 0.3rem 0.85rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}
.sme-status-active {
    color: #0f7b3e;
    background: #e3f5ea;
}
.sme-status-inactive {
    color: #c0392b;
    background: #fde6e4;
}

/* --- Row actions: edit (indigo) · toggle (amber) · delete (red) -- */
.sme-row-actions {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
}
.sme-act {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--ds-radius-1);
    text-decoration: none;
    transition: background-color .15s ease;
}
.sme-act i { font-size: 20px; line-height: 1; }
.sme-act-edit { color: #4f46e5; }
.sme-act-edit:hover { background: rgba(79, 70, 229, 0.12); }
.sme-act-delete { color: var(--bs-danger); }
.sme-act-delete:hover { background: rgba(var(--bs-danger-rgb), 0.12); }
.sme-act-delete.disabled { opacity: 0.4; pointer-events: none; }

/* Amber switch, vertically centred with the icons */
.sme-act-switch {
    padding-left: 0;
    min-height: auto;
    margin: 0;
    display: inline-flex;
    align-items: center;
}
.sme-act-switch .form-check-input {
    float: none;
    margin: 0;
    width: 2.1rem;
    height: 1.15rem;
    cursor: pointer;
}
.sme-act-switch .form-check-input:checked {
    background-color: #f0a500;
    border-color: #f0a500;
}
.sme-act-switch .form-check-input:focus {
    border-color: #f0a500;
    box-shadow: 0 0 0 0.2rem rgba(240, 165, 0, 0.25);
}

/* ---------------------------------------------------------------------
   Bottom bar — pagination (left) + "Showing [n] of N items" (right)
   --------------------------------------------------------------------- */
.datatables .sme-table-footer { margin-top: var(--ds-space-3); }

/* Count cluster: length <select> + total, read as one phrase */
.datatables .sme-count {
    gap: var(--ds-space-2);
    color: var(--ds-ink-muted);
    font-size: 0.875rem;
}
.datatables .dataTables_length,
.datatables .dataTables_info {
    margin: 0;
    padding: 0;
    color: var(--ds-ink-muted);
    font-size: 0.875rem;
    white-space: nowrap;
}
.datatables .dataTables_length label {
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
}
.datatables .dataTables_length select.form-select {
    width: auto;
    min-width: 76px;
    display: inline-block;
    border-radius: var(--ds-radius-1);
}

/* Pagination: separated, rounded buttons; current = brand blue */
.datatables .dataTables_paginate { margin: 0; }
.datatables .pagination {
    margin: 0;
    gap: var(--ds-space-1);
    flex-wrap: wrap;
}
.datatables .pagination .page-item .page-link {
    margin-left: 0;                      /* drop Bootstrap's border-collapse */
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    color: var(--ds-ink);
    font-size: 0.875rem;
    background: #fff;
}
.datatables .pagination .page-item .page-link:hover {
    background: var(--ds-surface-2);
    border-color: #c4ccd6;
}
.datatables .pagination .page-item.active .page-link {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: #fff;
}
.datatables .pagination .page-item.disabled .page-link {
    color: var(--ds-ink-muted);
    background: var(--ds-surface-2);
    opacity: 0.6;
}
.datatables .pagination .page-link:focus {
    box-shadow: var(--ds-focus-ring);
    z-index: 2;
}

/* --- Print Styles ------------------------------------------------- */
@media print {
    body * {
        visibility: hidden;
    }

    #medicalExemptionTable,
    #medicalExemptionTable * {
        visibility: visible;
    }

    #medicalExemptionTable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .table thead {
        background-color: #af2910 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }

    .table {
        border-collapse: collapse !important;
        font-size: 12px !important;
    }

    /* Hide action and status columns in print */
    .table th:nth-child(11),
    .table td:nth-child(11),
    .table th:nth-child(12),
    .table td:nth-child(12) {
        display: none;
    }

    @page {
        margin: 1cm;
    }

    .print-header {
        display: block;
        text-align: center;
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: bold;
    }

    .print-footer {
        display: block;
        text-align: center;
        margin-top: 20px;
        font-size: 10px;
    }
}

/* --- sme-act-hardening: guarantee the row-action icons are visible ---
   Bumps specificity past any theme rule on table links and stops the
   Bootstrap switch from pulling left over the edit icon. */
.datatables #medicalExemptionTable .sme-row-actions { display: inline-flex !important; flex-wrap: nowrap; }
.datatables #medicalExemptionTable .sme-act { width: 34px; height: 34px; flex: 0 0 auto; }
.datatables #medicalExemptionTable .sme-act-edit,
.datatables #medicalExemptionTable .sme-act-edit i { color: #4f46e5 !important; }
.datatables #medicalExemptionTable .sme-act-delete,
.datatables #medicalExemptionTable .sme-act-delete i { color: var(--bs-danger) !important; }
.datatables #medicalExemptionTable .sme-act i { font-size: 20px !important; line-height: 1; }
.datatables #medicalExemptionTable .sme-act-switch { padding-left: 0 !important; margin: 0 !important; min-height: 0 !important; }
.datatables #medicalExemptionTable .sme-act-switch .form-check-input { margin: 0 !important; float: none !important; }
</style>

<div class="container-fluid">

    {{-- Page header + primary action (matches reference) --}}
    <x-breadcrum title="Student Medical Exemption">
        <a href="{{ route('student.medical.exemption.create') }}" id="addExemptionBtn"
            class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Student Medical Exemption</span>
        </a>
    </x-breadcrum>
    {{-- Toolbar: status segment (left) + utility actions (right) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">

        <div class="sme-segment" role="group" aria-label="Course Status Filter">
            <button type="button" class="sme-segment-btn active" id="filterActive" aria-pressed="true">
                Active
            </button>
            <button type="button" class="sme-segment-btn" id="filterArchive" aria-pressed="false">
                Archived
            </button>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="sme-util-btn" onclick="printTable()">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">print</i>
                <span class="d-none d-sm-inline">Print</span>
            </button>

            <a href="{{ route('student.medical.exemption.export') }}" class="sme-util-btn">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;"
                    aria-hidden="true">download</i>
                <span class="d-none d-sm-inline">Download</span>
            </a>
        </div>
    </div>
    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body">

                {{-- Filters: single inline toolbar (matches reference) --}}
                <div class="sme-filterbar mb-3">

                    <span class="sme-filters-label">Filters</span>

                    {{-- Course --}}
                    <select name="course_filter" id="course_filter" class="form-select sme-filter-control"
                            aria-label="Course Name">
                        <option value="">Course Name</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>

                    {{-- Time Period: dual-month range calendar (writes to the hidden filters) --}}
                    <div class="dropdown">
                        <button type="button" class="sme-filter-control dropdown-toggle" id="timePeriodToggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded"
                               style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="timePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 sme-period-menu">
                            <div class="sme-cal" id="smeCalendar">
                                <div class="sme-cal-months">
                                    <div class="sme-cal-month" data-month="0"></div>
                                    <div class="sme-cal-month" data-month="1"></div>
                                </div>
                                <div class="sme-cal-footer">
                                    <span class="sme-cal-range" id="smeCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="applyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                            {{-- Hidden filters consumed by the DataTable AJAX (unchanged keys) --}}
                            <input type="hidden" name="from_date_filter" id="from_date_filter" value="">
                            <input type="hidden" name="to_date_filter" id="to_date_filter" value="">
                        </div>
                    </div>

                    {{-- Reset --}}
                    <a href="javascript:void(0)" id="resetFilters" class="sme-filter-control">
                        <i class="material-icons material-symbols-rounded"
                           style="font-size:18px;" aria-hidden="true">restart_alt</i>
                        Reset Filters
                    </a>

                    {{-- Right cluster: Columns + Search --}}
                    <div class="ms-auto d-flex align-items-center gap-2">

                        {{-- Columns visibility (opens modal) --}}
                        <button type="button" class="sme-filter-control" id="columnsToggle"
                                data-bs-toggle="modal" data-bs-target="#columnsModal">
                            <i class="material-icons material-symbols-rounded"
                               style="font-size:18px;" aria-hidden="true">view_column</i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>

                        {{-- Search (always visible) --}}
                        <div class="sme-search-box">
                            <i class="material-icons material-symbols-rounded sme-search-ico"
                               aria-hidden="true">search</i>
                            <input type="text" name="search" id="search" class="form-control sme-search-field"
                                   placeholder="Search student, OT code, course..." value=""
                                   aria-label="Search">
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap" id="medicalExemptionTable">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">OT Code</th>
                                <th class="col">Student Name</th>
                                <th class="col text-wrap">Course</th>
                                <th class="col">Assigned by</th>
                                <th class="col">Category</th>
                                <th class="col">Medical Speciality</th>
                                <th class="col">Duration</th>
                                <th class="col">OPD Type</th>
                                <th class="col">Document</th>
                                <th class="col">Status</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit form modal (form is fetched from create/edit and injected) --}}
    <div class="modal fade" id="smeFormModal" tabindex="-1" aria-labelledby="smeFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="smeFormModalLabel">Add Student Medical Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="smeFormBody">
                    {{-- form injected here --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" id="smeFormSubmit" disabled>Submit</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade" id="columnsModal" tabindex="-1" aria-labelledby="columnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="columnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sme-col-grid" id="columnsGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {

    // ✅ IMPORTANT: global variable (DataTable से पहले)
    let courseStatus = 'active';

    let table = $('#medicalExemptionTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,

        scrollX: false,
        scrollCollapse: false,
        autoWidth: false,

        // We provide our own search/filter toolbar, so DataTables only
        // renders the table (in a scroll wrapper) + a bottom bar holding
        // pagination (left) and "Showing [n] of N items" count (right).
        dom: "<'sme-scroll't>" +
             "<'sme-table-footer row align-items-center g-2 mt-3'" +
                 "<'col-12 col-md-auto me-md-auto order-2 order-md-1'p>" +
                 "<'col-12 col-md-auto order-1 order-md-2 d-flex justify-content-md-end align-items-center sme-count'li>" +
             ">" +
             "<'sme-processing'r>",

        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        pageLength: 10,

        language: {
            lengthMenu: "Showing _MENU_",
            info: "of _TOTAL_ items",
            infoEmpty: "of 0 items",
            infoFiltered: "",
            zeroRecords: "No matching records found",
            emptyTable: "No records available",
            paginate: {
                previous: "<span aria-hidden='true'>&lsaquo;</span>",
                next: "<span aria-hidden='true'>&rsaquo;</span>"
            },
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>'
        },

        ajax: {
            url: "{{ route('student.medical.exemption.index') }}",
            data: function(d) {
                d.course_id = $('#course_filter').val();
                d.custom_search = $('#search').val();
                d.from_date = $('#from_date_filter').val();
                d.to_date = $('#to_date_filter').val();

                // ✅ status now properly passed
                d.status = courseStatus;
            }
        },

        columns: [{
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
             {
                data: 'ot_code',
                name: 'student.generated_OT_code'
            },
            {
                data: 'student',
                name: 'student.display_name'
            },
            {
                data: 'course',
                name: 'course.course_name'
            },
            {
                data: 'assigned_by',
                name: 'employee.first_name'
            },
            {
                data: 'category',
                name: 'category.exemp_category_name'
            },
            {
                data: 'speciality',
                name: 'speciality.speciality_name'
            },
            {
                data: 'from_to',
                orderable: false
            },
            {
                data: 'opd_type',
                name: 'opd_category'
            },
            {
                data: 'document',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                orderable: false,
                searchable: false
            },
            {
                data: 'action',
                orderable: false,
                searchable: false
            }
        ]
    });

    // Reload table when course filter changes
    $('#course_filter').on('change', function() {
        table.ajax.reload(null, false);
    });

    $('#from_date_filter, #to_date_filter').on('change', function() {
        table.ajax.reload(null, false);
    });

    // 🔍 Search with debounce
    let delayTimer;
    $('#search').on('keyup', function() {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            table.ajax.reload(null, false);
        }, 400);
    });

    // 🔄 Reset filters
    $('#resetFilters').on('click', function() {
        $('#search').val('');
        $('#course_filter').val('').trigger('change');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');

        updateTimePeriodLabel();
        table.ajax.reload(null, false);
    });

    // 📅 Time Period: reflect chosen range in the toggle label
    function updateTimePeriodLabel() {
        var from = $('#from_date_filter').val();
        var to   = $('#to_date_filter').val();
        if (from || to) {
            $('#timePeriodLabel').text((from || '…') + ' → ' + (to || '…'));
        } else {
            $('#timePeriodLabel').text('Time Period');
        }
    }
    $('#from_date_filter, #to_date_filter').on('change', updateTimePeriodLabel);

    $('#clearPeriod').on('click', function() {
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');
        updateTimePeriodLabel();
        table.ajax.reload(null, false);
    });

    // ===== Dual-month range calendar (Time Period) =====
    (function initRangeCalendar() {
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
        var view = new Date(); view.setDate(1);
        var startD = null, endD = null;

        function pad(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }

        function buildMonth(base){
            var year = base.getFullYear(), month = base.getMonth();
            var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var html = '<div class="sme-cal-head">' +
                '<button type="button" class="sme-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                '<span class="sme-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                '<button type="button" class="sme-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '</div><div class="sme-cal-grid">';
            DOW.forEach(function(d){ html += '<span class="sme-cal-dow">' + d + '</span>'; });
            for (var i = 0; i < startWeekday; i++) html += '<span></span>';
            for (var day = 1; day <= daysInMonth; day++){
                var d = new Date(year, month, day);
                var cls = 'sme-cal-day';
                if (startD && endD && d > startD && d < endD) cls += ' in-range';
                if (sameDay(d, startD)) cls += ' is-start';
                if (sameDay(d, endD)) cls += ' is-end';
                html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
            }
            return html + '</div>';
        }

        function render(){
            var left = new Date(view.getFullYear(), view.getMonth(), 1);
            var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
            $('#smeCalendar .sme-cal-month[data-month="0"]').html(buildMonth(left));
            $('#smeCalendar .sme-cal-month[data-month="1"]').html(buildMonth(right));
            $('#smeCalendar .sme-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#smeCalendar .sme-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  ->  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  -> ...';
            $('#smeCalRange').text(label);
        }

        $('#smeCalendar').on('click', '.sme-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });

        $('#smeCalendar').on('click', '.sme-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });

        $('#applyPeriod').on('click', function(){
            $('#from_date_filter').val(startD ? ymd(startD) : '');
            $('#to_date_filter').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            table.ajax.reload(null, false);
            if (window.bootstrap) {
                bootstrap.Dropdown.getOrCreateInstance(document.getElementById('timePeriodToggle')).hide();
            }
        });

        $('#clearPeriod').on('click', function(){ startD = null; endD = null; render(); });

        render();
    })();

    // ===== Add / Edit in a modal (fetch form -> inject -> AJAX submit) =====
    var smeFormModalEl = document.getElementById('smeFormModal');
    var smeFormModal = (window.bootstrap && smeFormModalEl) ? new bootstrap.Modal(smeFormModalEl) : null;

    function loadModalForm(url, title){
        $('#smeFormModalLabel').text(title);
        $('#smeFormSubmit').prop('disabled', true);
        $('#smeFormBody').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        smeFormModal.show();

        $.get(url).done(function(html){
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var form = doc.querySelector('form[action*="/store"], form[action*="/update/"]');
            if (!form){
                $('#smeFormBody').html('<div class="alert alert-danger m-0">Unable to load the form.</div>');
                return;
            }
            form.setAttribute('id', 'smeAjaxForm');
            $('#smeFormBody').html(form.outerHTML);
            initModalForm();
            $('#smeFormSubmit').prop('disabled', false);
        }).fail(function(){
            $('#smeFormBody').html('<div class="alert alert-danger m-0">Failed to load the form. Please try again.</div>');
        });
    }

    var modalChoices = [];          // every Choices instance in the modal (for cleanup)
    var modalStudentChoices = null;
    var modalOtMap = {};

    function rebuildModalStudents(list, placeholder, loading){
        if (!modalStudentChoices) return;
        modalOtMap = {};
        var choices = [{ value: '', label: placeholder || 'Search Student', selected: true, disabled: !!loading }];
        (list || []).forEach(function(s){
            modalOtMap[String(s.pk)] = s.generated_OT_code || '';
            choices.push({ value: String(s.pk), label: s.display_name });
        });
        modalStudentChoices.clearStore();
        modalStudentChoices.setChoices(choices, 'value', 'label', true);
        $('#otCodeField').val('');
    }

    function initModalForm(){
        var $form = $('#smeAjaxForm');
        var formEl = $form[0];
        modalChoices = [];
        modalStudentChoices = null;
        modalOtMap = {};

        // ot-code lookup from any server-rendered student options (edit form)
        $form.find('#studentDropdown option').each(function(){
            if (this.value) modalOtMap[this.value] = String($(this).data('ot_code') || '');
        });

        // Turn EVERY <select> in the injected form into a Choices.js dropdown
        if (formEl && window.Choices){
            formEl.querySelectorAll('select').forEach(function(sel){
                sel.classList.remove('select2');
                var inst = new Choices(sel, {
                    searchEnabled: sel.options.length > 5,
                    searchPlaceholderValue: 'Search...',
                    itemSelectText: '',
                    shouldSort: false,
                    allowHTML: false
                });
                modalChoices.push(inst);
                if (sel.id === 'studentDropdown') modalStudentChoices = inst;
            });

            if (modalStudentChoices){
                document.getElementById('studentDropdown').addEventListener('change', function(){
                    $('#otCodeField').val(modalOtMap[modalStudentChoices.getValue(true)] || '');
                });
            }
        }

        // Course -> students (the Add form carries #courseDropdown)
        $form.on('change', '#courseDropdown', function(){
            var courseId = $(this).val();
            if (!courseId){ rebuildModalStudents([], 'Select Course First'); return; }
            rebuildModalStudents(null, 'Loading...', true);
            $.get("{{ route('student.medical.exemption.getStudentsByCourse') }}", { course_id: courseId }).done(function(res){
                rebuildModalStudents(res.students, 'Search Student');
            });
        });
    }

    function submitModalForm(){
        var form = document.getElementById('smeAjaxForm');
        if (!form) return;
        var $btn = $('#smeFormSubmit').prop('disabled', true);
        $('#smeAjaxForm .sme-err').remove();
        $('#smeAjaxForm .is-invalid').removeClass('is-invalid');

        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(){
            smeFormModal.hide();
            table.ajax.reload(null, false);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Saved successfully', timer: 1600, showConfirmButton: false });
            }
        }).fail(function(xhr){
            $btn.prop('disabled', false);
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                var first = null;
                $.each(xhr.responseJSON.errors, function(field, msgs){
                    var $f = $('#smeAjaxForm [name="' + field + '"]');
                    $f.addClass('is-invalid');
                    $f.last().after('<small class="text-danger sme-err d-block mt-1">' + msgs[0] + '</small>');
                    if (!first) first = $f.first();
                });
                if (first && first.length) first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            }
        });
    }

    $('#smeFormSubmit').on('click', submitModalForm);
    $(document).on('submit', '#smeAjaxForm', function(e){ e.preventDefault(); submitModalForm(); });

    // Reset the modal on close so reopening never stacks Choices widgets
    $('#smeFormModal').on('hidden.bs.modal', function(){
        modalChoices.forEach(function(c){ try { c.destroy(); } catch(e){} });
        modalChoices = [];
        modalStudentChoices = null;
        $('#smeFormBody').empty();
        $('#smeFormSubmit').prop('disabled', true);
    });

    $('#addExemptionBtn').on('click', function(e){
        if (!smeFormModal) return;
        e.preventDefault();
        loadModalForm($(this).attr('href'), 'Add Student Medical Exemption');
    });

    $(document).on('click', '.sme-edit-btn', function(e){
        if (!smeFormModal) return;
        e.preventDefault();
        loadModalForm($(this).attr('href'), 'Edit Student Medical Exemption');
    });

    // 🧱 Column Visibility modal (chips built from the live DataTable)
    var $columnsGrid = $('#columnsGrid');
    table.columns().every(function(idx) {
        var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
        var visible = this.visible();
        $columnsGrid.append(
            '<label class="sme-col-chip' + (visible ? ' is-checked' : '') + '" for="colToggle' + idx + '">' +
                '<input class="form-check-input sme-col-toggle" type="checkbox" ' +
                       (visible ? 'checked ' : '') +
                       'id="colToggle' + idx + '" data-column="' + idx + '">' +
                '<span>' + title + '</span>' +
            '</label>'
        );
    });
    $columnsGrid.on('change', '.sme-col-toggle', function() {
        table.column($(this).data('column')).visible(this.checked);
        $(this).closest('.sme-col-chip').toggleClass('is-checked', this.checked);
    });

    // ✅ Active filter
    $('#filterActive').on('click', function() {

        courseStatus = 'active';

        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#filterArchive').removeClass('active').attr('aria-pressed', 'false');

        table.ajax.reload(null, false);
    });

    // ✅ Archive filter
    $('#filterArchive').on('click', function() {

        courseStatus = 'archive';

        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#filterActive').removeClass('active').attr('aria-pressed', 'false');

        table.ajax.reload(null, false);
    });


    $(document).on('click', '.delete-btn', function() {


        let deleteUrl = $(this).data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: "This record will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {

                        Swal.fire(
                            'Deleted!',
                            response.message ?? 'Record deleted successfully.',
                            'success'
                        );

                        table.ajax.reload(null, false);
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    });

});
</script>

<script>
// Builds a short, human-readable summary of the active filters for the
// printout header. Reads the live filter controls; returns '' when none set.
function getFilterInfo() {
    var parts = [];

    var search = (document.getElementById('search') || {}).value;
    if (search) parts.push('Search: ' + search);

    var courseSelect = document.getElementById('course_filter');
    if (courseSelect && courseSelect.value) {
        parts.push('Course: ' + courseSelect.options[courseSelect.selectedIndex].text);
    }

    var fromDate = (document.getElementById('from_date_filter') || {}).value;
    if (fromDate) parts.push('From: ' + fromDate);

    var toDate = (document.getElementById('to_date_filter') || {}).value;
    if (toDate) parts.push('To: ' + toDate);

    var active = document.getElementById('filterActive');
    parts.push('Status: ' + (active && active.classList.contains('active') ? 'Active' : 'Archived'));

    return parts.length ? '<strong>Applied Filters:</strong> ' + parts.join(' &nbsp;|&nbsp; ') : '';
}

// Print function - defined globally so it can be called from onclick
function printTable() {
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    var table = document.getElementById('medicalExemptionTable');

    if (!table) {
        alert('Table not found!');
        return;
    }

    // Clone the table to avoid modifying the original
    var tableClone = table.cloneNode(true);

    // Remove Action and Status columns (11th and 12th columns)
    var rows = tableClone.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        if (cells.length >= 12) {
            // Remove Action column (11th) and Status column (12th)
            if (cells[10]) cells[10].remove(); // Action
            if (cells[10]) cells[10].remove(); // Status (now at index 10 after first removal)
        }
    });

    var tableHTML = tableClone.outerHTML;

    // Get current date for header
    var today = new Date();
    var dateStr = today.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    // Build print content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Medical Exemption Form - Print</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .print-header h2 {
                    margin: 0;
                    color: #004a93;
                }
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .print-info {
                    margin-bottom: 15px;
                    font-size: 12px;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                table thead {
                    background-color: #af2910 !important;
                    color: white !important;
                }
                table th,
                table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                table th {
                    font-weight: bold;
                    background-color: #af2910;
                    color: white;
                }
                table tbody tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
                @media print {
                    @page {
                        margin: 1cm;
                    }
                    body {
                        margin: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>Medical Exemption Form</h2>
                <p>Lal Bahadur Shastri National Academy of Administration</p>
                <p>Print Date: ${dateStr}</p>
            </div>
            <div class="print-info">
                ${getFilterInfo()}
            </div>
            ${tableHTML}
            <div class="print-footer">
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();

    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>

@endpush