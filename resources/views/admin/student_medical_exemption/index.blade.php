@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ filemtime(public_path('css/select2-theme.css')) }}">
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
.sme-util-btn.dropdown-toggle::after {
    margin-left: 0.35rem;
}
.sme-download-menu {
    min-width: 11rem;
    border-radius: var(--ds-radius-1);
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

/* Searchable course filter (Select2) is themed in css/select2-theme.css. */
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
/* Let Select2 panels escape the modal body instead of being clipped or offset. */
#smeFormModal .modal-content,
#smeFormModal .modal-body { overflow: visible; }
#smeFormBody .sme-field {
    display: flex;
    flex-direction: column;
    position: relative;
}
#smeFormBody .row > [class*="col-"] {
    position: relative;
}
/* Select2 dropdowns in the Add / Edit modal are themed in css/select2-theme.css
   (Bootstrap .form-select look, chevron caret, matching panel). */
#smeFormBody textarea.form-control {
    min-height: 88px;
    resize: vertical;
    line-height: 1.5;
}
#smeFormBody .sme-remarks-row {
    margin-top: var(--ds-space-1);
    padding-top: var(--ds-space-3);
    border-top: 1px dashed var(--ds-line);
}

/* --- View details modal ------------------------------------------ */
.sme-view-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: 0 0 var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.sme-view-section-title:not(:first-child) { margin-top: var(--ds-space-4); }
.sme-view-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-height: 100%;
}
.sme-view-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--ds-ink-muted);
}
.sme-view-value {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--ds-ink);
    word-break: break-word;
}
.sme-view-text {
    min-height: 72px;
    padding: 0.65rem 0.75rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    background: var(--ds-surface-2, #f8f9fa);
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
}
.sme-view-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
}
.sme-view-status.is-active { color: #0f7b3e; background: #e3f5ea; }
.sme-view-status.is-inactive { color: #c0392b; background: #fde6e4; }

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
.sme-act-edit { color: #004a93; }
.sme-act-edit:hover { background: rgba(0, 74, 147, 0.12); }
.sme-act-delete {
    color: #af2910;
}
.sme-act-delete:hover { background: rgba(175, 41, 16, 0.12); }
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

.datatables #medicalExemptionTable .sme-col-text {
    white-space: normal;
    min-width: 140px;
    max-width: 220px;
    word-break: break-word;
}

.datatables #medicalExemptionTable .sme-doc-view {
    color: #0d6efd;
    font-weight: 600;
    text-decoration: underline;
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
    .table th.sme-col-no-print,
    .table td.sme-col-no-print {
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
.datatables #medicalExemptionTable .sme-act-edit i { color: #004a93 !important; }
.datatables #medicalExemptionTable .sme-act-delete,
.datatables #medicalExemptionTable .sme-act-delete i { color: #af2910 !important; }
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

            <div class="dropdown">
                <button type="button" class="sme-util-btn dropdown-toggle" id="smeDownloadBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">download</i>
                    <span class="d-none d-sm-inline">Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm sme-download-menu py-2" aria-labelledby="smeDownloadBtn">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="smeExportPdf">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;" aria-hidden="true">picture_as_pdf</i>
                            <span>Download PDF</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="smeExportCsv">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;" aria-hidden="true">table_chart</i>
                            <span>Download Excel</span>
                        </button>
                    </li>
                </ul>
            </div>
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
                    <table class="table align-middle" id="medicalExemptionTable">
                        <thead style="position:relative; z-index: 0;">
                            <tr>
                                <th class="col">S. No.</th>
                                <th class="col">Date</th>
                                <th class="col">Officer Trainee</th>
                                <th class="col" style="white-space: normal;width: 12%;">Course</th>
                                <th class="col">Doctor Name</th>
                                <th class="col">Medical Speciality</th>
                                <th class="col">Duration</th>
                                <th class="col">Days</th>
                                <th class="col">Category</th>
                                <th class="col">IPD/OPD/After OPD/Referral/PT Exemption</th>
                                <th class="col">PT/ Outdoor Advise</th>
                                <th class="col">Diagnosis / Remarks</th>
                                <th class="col sme-col-no-print">Document</th>
                                <th class="col sme-col-no-print">Action</th>
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
                    <button type="button" class="btn btn-primary px-4" id="smeFormSubmit" disabled>Add Student Medical Exemption</button>
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

    {{-- View details modal --}}
    <div class="modal fade" id="smeViewModal" tabindex="-1" aria-labelledby="smeViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="smeViewModalLabel">Student Medical Exemption Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="smeViewBody">
                    <div class="text-center py-4 text-muted">Loading...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Select2 (select2.full.min.js) is already loaded globally in the admin footer. --}}
<script>
$(document).ready(function() {

    // ✅ IMPORTANT: global variable (DataTable से पहले)
    let courseStatus = 'active';

    // Course filter options per tab: Active = running courses, Archive = ended
    // courses (like Course Master). The dropdown swaps when the tab changes.
    const smeCourseLists = {
        active: @json($courses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->course_name])->values()),
        archive: @json(($archivedCourses ?? collect())->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->course_name])->values()),
    };

    // Searchable course filter (Select2). Every filter dropdown gets a search box.
    let courseFilterSelect2 = false;
    if ($.fn.select2 && $('#course_filter').length) {
        $('#course_filter').select2({
            width: '210px',
            placeholder: 'Course Name',
            allowClear: false,
        });
        courseFilterSelect2 = true;
    }

    function populateCourseFilter(status) {
        const list = smeCourseLists[status] || [];
        const $sel = $('#course_filter');
        if (!$sel.length) { return; }
        // Rebuild the option list (a course from the other tab won't exist here).
        $sel.empty().append($('<option>').val('').text('Course Name'));
        list.forEach(function (c) { $sel.append($('<option>').val(String(c.pk)).text(c.name)); });
        $sel.val('');
        if (courseFilterSelect2) { $sel.trigger('change.select2'); }
    }

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

        columnDefs: [{
            defaultContent: '—',
            targets: '_all'
        }],

        columns: [{
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'date',
                orderable: false
            },
            {
                data: 'student',
                name: 'student.display_name',
                className: 'text-wrap'
            },
            {
                data: 'course',
                name: 'course.course_name',
                className: 'text-wrap'
            },
            {
                data: 'assigned_by',
                name: 'employee.first_name'
            },
            {
                data: 'speciality',
                name: 'speciality.speciality_name'
            },
            {
                data: 'duration',
                className: 'text-wrap',
                orderable: false
            },
            {
                data: 'days',
                name: 'days'
            },
            {
                data: 'category',
                name: 'category.exemp_category_name'
            },
            {
                data: 'opd_type',
                name: 'opd_category'
            },
            {
                data: 'pt_advise',
                name: 'pt_outdoor_advise',
                className: 'sme-col-text text-wrap',
                orderable: false
            },
            {
                data: 'description',
                name: 'Description',
                className: 'sme-col-text text-wrap',
                orderable: false
            },
            {
                data: 'document',
                orderable: false,
                searchable: false,
                className: 'sme-col-no-print'
            },
            {
                data: 'action',
                orderable: false,
                searchable: false,
                className: 'sme-col-no-print'
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
        $('#course_filter').val('');
        if (courseFilterSelect2) { $('#course_filter').trigger('change.select2'); }
        else { $('#course_filter').trigger('change'); }
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
        // Submit button mirrors the context: "Add ..." on add, "Edit ..." on edit.
        $('#smeFormSubmit').text(title).prop('disabled', true);
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

    var modalOtMap = {};

    function rebuildModalStudents(list, placeholder, loading){
        var $sel = $('#smeAjaxForm #studentDropdown');
        if (!$sel.length) return;
        modalOtMap = {};
        // The empty option's text doubles as the Select2 placeholder/hint.
        var $opts = $('<div>').append($('<option>').val('').text(placeholder || 'Search Student'));
        (list || []).forEach(function(s){
            modalOtMap[String(s.pk)] = s.generated_OT_code || '';
            var label = s.display_name + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '');
            $opts.append($('<option>').val(String(s.pk)).text(label));
        });
        $sel.html($opts.html()).val('');
        if ($sel.hasClass('select2-hidden-accessible')) { $sel.trigger('change.select2'); }
        $('#otCodeField').val('');
    }

    function initModalForm(){
        var $form = $('#smeAjaxForm');
        modalOtMap = {};

        // ot-code lookup from any server-rendered student options (edit form)
        $form.find('#studentDropdown option').each(function(){
            if (this.value) modalOtMap[this.value] = String($(this).data('ot_code') || '');
        });

        // Turn EVERY <select> in the injected form into a Select2 dropdown, styled
        // (via CSS) to match Bootstrap's .form-select. Attach the panel to the field
        // wrapper (not the modal root) so it sits flush under the control with no gap.
        if ($form.length && $.fn.select2){
            $form.find('select').each(function(){
                var $sel = $(this).removeClass('select2');
                var $parent = $sel.closest('.sme-field');
                if (!$parent.length) { $parent = $sel.parent(); }
                $sel.select2({
                    width: '100%',
                    dropdownParent: $parent,
                    allowClear: false
                });
            });

            // Auto-fill the OT code when the officer trainee changes.
            $form.on('change', '#studentDropdown', function(){
                $('#otCodeField').val(modalOtMap[$(this).val()] || '');
            });
        }

        // Client-side file validation for Doc_upload
        $form.on('change', '#Doc_upload', function(){
            var $input = $(this);
            $input.siblings('.sme-file-err').remove();
            if (!this.files.length) return;
            var file = this.files[0];
            var ext  = file.name.split('.').pop().toLowerCase();
            var allowed = ['pdf','jpg','jpeg','png','doc','docx'];
            if (!allowed.includes(ext)) {
                $input.after('<small class="text-danger sme-file-err d-block mt-1">Invalid file type. Allowed: PDF, JPG, JPEG, PNG, DOC, DOCX.</small>');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                $input.after('<small class="text-danger sme-file-err d-block mt-1">File size exceeds 5 MB limit. Please choose a smaller file.</small>');
                this.value = '';
            }
        });

        // Clear a field's validation error as soon as the user edits it, so a
        // message never lingers until the next submit attempt.
        $form.on('input change', '.form-control, .form-select, select', function(){
            var $f = $(this);
            $f.removeClass('is-invalid');   // Select2 border clears via the adjacent-sibling CSS
            var $col = $f.closest('[class*="col-"]').first();
            ($col.length ? $col : $f.parent()).find('.sme-err').remove();
        });

        // Days = inclusive span between the Start and End dates (read-only field).
        $form.on('change', '#arrivalDate, #departureDate', function(){
            var a = document.getElementById('arrivalDate');
            var d = document.getElementById('departureDate');
            var out = document.getElementById('daysField');
            if (!a || !d || !out) return;
            if (a.value && d.value) {
                var diff = Math.floor((new Date(d.value) - new Date(a.value)) / 86400000);
                out.value = (diff >= 0) ? (diff + 1) : '';
            } else {
                out.value = '';
            }
        });

        // Course -> students (legacy Add/Edit form carried #courseDropdown; harmless no-op
        // for the current single officer-trainee dropdown which has no #courseDropdown)
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
        // Block if client-side file error is still visible
        if ($('#smeAjaxForm .sme-file-err').length) return;
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
                var errors = xhr.responseJSON.errors;
                var first = null;

                // The backend validates combined datetimes (from_date/to_date) while the
                // form has split Start/End date+time inputs. Fold each derived error onto
                // its split field, and skip it when that split field already errored —
                // otherwise the same message stacks twice under one control.
                var placement = { from_date: 'arrival_date', to_date: 'departure_date' };

                // Collect one de-duplicated message list per visible field.
                var byField = {};
                $.each(errors, function(field, msgs){
                    var target = placement[field] || field;
                    if (placement[field] && errors[target]) return; // split field owns it
                    var list = byField[target] || (byField[target] = []);
                    if (msgs[0] && list.indexOf(msgs[0]) === -1) list.push(msgs[0]);
                });

                var shown = false;
                $.each(byField, function(name, msgs){
                    var $field = $('#smeAjaxForm [name="' + name + '"]');
                    if (!$field.length) return;
                    shown = true;

                    // Mark the control (plain input, or a Select2-wrapped <select>,
                    // which colours via the adjacent-sibling CSS rule).
                    $field.addClass('is-invalid');

                    // Place the message at the bottom of the field's own column so it
                    // reads directly under the control it belongs to.
                    var $col = $field.closest('[class*="col-"]').first();
                    var $anchor = $col.length ? $col : $field.parent();
                    msgs.forEach(function(m){
                        $anchor.append('<small class="text-danger sme-err d-block mt-1">' + m + '</small>');
                    });

                    if (!first) first = $field.first();
                });

                if (!shown) {
                    var topMsg = (xhr.responseJSON.message || 'Please review the highlighted fields.');
                    $('#smeAjaxForm').prepend('<div class="alert alert-danger sme-err mb-3">' + topMsg + '</div>');
                }

                if (first && first.length) first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (typeof Swal !== 'undefined') {
                var errMsg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.';
                Swal.fire('Error', errMsg, 'error');
            }
        });
    }

    $('#smeFormSubmit').on('click', submitModalForm);
    $(document).on('submit', '#smeAjaxForm', function(e){ e.preventDefault(); submitModalForm(); });

    // Reset the modal on close so reopening never stacks Select2 widgets
    $('#smeFormModal').on('hidden.bs.modal', function(){
        $('#smeFormBody select').each(function(){
            if ($(this).hasClass('select2-hidden-accessible')) {
                try { $(this).select2('destroy'); } catch(e){}
            }
        });
        $('#smeFormBody').empty();
        $('#smeFormSubmit').prop('disabled', true);
    });

    $('#addExemptionBtn').on('click', function(e){
        if (!smeFormModal) return;
        e.preventDefault();
        loadModalForm($(this).attr('href'), 'Add Student Medical Exemption');
    });

    var smeExportBase = @json(route('student.medical.exemption.export'));

    function smeExportUrl(format) {
        var params = new URLSearchParams();
        params.set('format', format);
        params.set('filter', courseStatus);

        var course = $('#course_filter').val();
        var search = $('#search').val();
        var from = $('#from_date_filter').val();
        var to = $('#to_date_filter').val();

        if (course) params.set('course_filter', course);
        if (search) params.set('search', search);
        if (from) params.set('from_date_filter', from);
        if (to) params.set('to_date_filter', to);

        return smeExportBase + '?' + params.toString();
    }

    $('#smeExportPdf').on('click', function(e) {
        e.preventDefault();
        window.location.href = smeExportUrl('pdf');
    });

    $('#smeExportCsv').on('click', function(e) {
        e.preventDefault();
        window.location.href = smeExportUrl('excel');
    });

    $(document).on('click', '.sme-edit-btn', function(e){
        if (!smeFormModal) return;
        e.preventDefault();
        loadModalForm($(this).attr('href'), 'Edit Student Medical Exemption');
    });

    var smeViewModalEl = document.getElementById('smeViewModal');
    var smeViewModal = (window.bootstrap && smeViewModalEl) ? new bootstrap.Modal(smeViewModalEl) : null;

    function smeEsc(text) {
        return $('<div>').text(text == null || text === '' ? '—' : text).html();
    }

    function smeViewField(label, value, colClass) {
        return '<div class="' + (colClass || 'col-md-6') + '">' +
            '<div class="sme-view-field">' +
                '<span class="sme-view-label">' + smeEsc(label) + '</span>' +
                '<span class="sme-view-value">' + smeEsc(value) + '</span>' +
            '</div>' +
        '</div>';
    }

    function renderSmeViewDetail(data) {
        var statusClass = data.status === 'Active' ? 'is-active' : 'is-inactive';
        var docHtml = data.document_url
            ? '<a href="' + data.document_url + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">' +
                '<i class="material-icons material-symbols-rounded" style="font-size:16px;">description</i> View Attachment' +
              '</a>'
            : '<span class="text-muted">No attachment</span>';

        return '' +
            '<h6 class="sme-view-section-title">Basic Information</h6>' +
            '<div class="row g-3">' +
                smeViewField('Course Name', data.course_name, 'col-12') +
                smeViewField('Name of Officer Trainee', data.student_name) +
                smeViewField('OT Code', data.ot_code) +
                smeViewField('Treating Doctor Name', data.doctor_name) +
                smeViewField('Exemption Category', data.category) +
            '</div>' +
            '<h6 class="sme-view-section-title">Exemption and Other Information</h6>' +
            '<div class="row g-3">' +
                smeViewField('IPD/OPD/After OPD/Referral/PT Exemption', data.opd_category) +
                smeViewField('Start Date', data.arrival_date) +
                smeViewField('Start Time', data.arrival_time) +
                smeViewField('End Date', data.departure_date) +
                smeViewField('End Time', data.departure_time) +
                smeViewField('Medical Speciality', data.speciality) +
                smeViewField('Days', data.days) +
                '<div class="col-md-6"><div class="sme-view-field"><span class="sme-view-label">Status</span>' +
                    '<span class="sme-view-status ' + statusClass + '">' + smeEsc(data.status) + '</span></div></div>' +
                (data.created_date ? smeViewField('Created On', data.created_date) : '') +
            '</div>' +
            '<div class="row g-3 mt-1">' +
                '<div class="col-md-6"><div class="sme-view-field"><span class="sme-view-label">Diagnosis / Remarks</span>' +
                    '<div class="sme-view-text">' + smeEsc(data.description) + '</div></div></div>' +
                '<div class="col-md-6"><div class="sme-view-field"><span class="sme-view-label">PT/Outdoor Advise</span>' +
                    '<div class="sme-view-text">' + smeEsc(data.pt_outdoor_advise) + '</div></div></div>' +
            '</div>' +
            '<h6 class="sme-view-section-title">Attachment</h6>' +
            '<div>' + docHtml + '</div>';
    }

    $(document).on('click', '.sme-view-btn', function(e) {
        e.preventDefault();
        if (!smeViewModal) return;
        var url = $(this).data('url');
        $('#smeViewBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        smeViewModal.show();
        $.get(url).done(function(data) {
            $('#smeViewBody').html(renderSmeViewDetail(data));
        }).fail(function() {
            $('#smeViewBody').html('<div class="alert alert-danger m-0">Failed to load record details. Please try again.</div>');
        });
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
        populateCourseFilter('active');

        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#filterArchive').removeClass('active').attr('aria-pressed', 'false');

        table.ajax.reload(null, false);
    });

    // ✅ Archive filter
    $('#filterArchive').on('click', function() {

        courseStatus = 'archive';
        populateCourseFilter('archive');

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
    var table = document.getElementById('medicalExemptionTable');

    if (!table) {
        alert('Table not found!');
        return;
    }

    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('Please allow pop-ups for this site to print the report.');
        return;
    }

    // Clone the table to avoid modifying the original
    var tableClone = table.cloneNode(true);

    // Remove Action and Status columns for print
    tableClone.querySelectorAll('tr').forEach(function(row) {
        row.querySelectorAll('.sme-col-no-print').forEach(function(cell) {
            cell.remove();
        });
    });

    var tableHTML = tableClone.outerHTML;

    // Get current date for header
    var today = new Date();
    var dateStr = today.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    // Branded LBSNAA header assets (same layout as the official report PDF).
    var logoLeft   = @json(asset('admin_assets/images/logos/logo_new.png'));
    var logoRight  = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
        ? asset('admin_assets/images/logos/constitution-75.png')
        : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
    var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));

    // Selected course line (skip the placeholder "Course Name" option).
    var courseName = '';
    var selCourse = $('#course_filter option:selected');
    if (selCourse.val()) { courseName = (selCourse.text() || '').trim(); }

    // Build print content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Student Medical Exemption - Print</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 16px;
                    color: #1f2937;
                }
                .pdf-hdr {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 4px;
                }
                .pdf-hdr td { vertical-align: middle; }
                .pdf-hdr .logo { width: 90px; text-align: center; }
                .pdf-hdr .logo img { max-height: 64px; max-width: 84px; }
                .pdf-hdr .center { text-align: center; padding: 0 8px; }
                .pdf-hdr .inst-hi-img { height: 18px; width: auto; margin-bottom: 2px; }
                .pdf-hdr .inst-en {
                    font-size: 16px; font-weight: bold; color: #102a43; line-height: 1.25;
                }
                .pdf-hdr .course-line {
                    font-size: 12px; font-weight: bold; color: #243b53; margin-top: 4px;
                }
                .report-title {
                    text-align: center;
                    font-size: 20px;
                    font-weight: bold;
                    color: #004a93;
                    margin: 8px 0 6px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #004a93;
                }
                .print-info {
                    margin-bottom: 12px;
                    font-size: 11px;
                    color: #666;
                    text-align: center;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                table th,
                table td {
                    border: 1px solid #8fa3bd;
                    padding: 6px 8px;
                    text-align: left;
                    font-size: 11px;
                }
                table thead th {
                    font-weight: bold;
                    background-color: #004a93 !important;
                    color: #fff !important;
                    text-align: center;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                table tbody tr:nth-child(even) {
                    background-color: #eef2f8;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .print-footer {
                    margin-top: 18px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
                @media print {
                    @page { size: A4 landscape; margin: 10mm; }
                    body { margin: 0; }
                }
            </style>
        </head>
        <body onload="window.focus(); window.print();">
            <table class="pdf-hdr">
                <tr>
                    <td class="logo"><img src="${logoLeft}" alt=""></td>
                    <td class="center">
                        <img class="inst-hi-img" src="${titleHindi}" alt="">
                        <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
                        ${courseName ? '<div class="course-line">' + courseName + '</div>' : ''}
                    </td>
                    <td class="logo"><img src="${logoRight}" alt=""></td>
                </tr>
            </table>
            <div class="report-title">Student Medical Exemption</div>
            <div class="print-info">
                ${getFilterInfo()}
                <div>Print Date: ${dateStr}</div>
            </div>
            ${tableHTML}
            <div class="print-footer">
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
    // Printing is triggered by the written document's own <body onload> so it
    // fires only after the header logos have finished loading.
}
</script>

@endpush