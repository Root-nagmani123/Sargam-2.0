@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('setup_content')
<style>
/* ─── Status Toggle Group ─── */
.mdo-toggle-group {
    display: inline-flex;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}
.mdo-toggle-group .btn {
    border-radius: 0;
    border: none;
    padding: 8px 28px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}
.mdo-toggle-group .btn.active-toggle {
    background-color: #004a93;
    color: #fff;
}
.mdo-toggle-group .btn.inactive-toggle {
    background-color: #f8f9fa;
    color: #6c757d;
}
.mdo-toggle-group .btn.inactive-toggle:hover {
    background-color: #e9ecef;
    color: #495057;
}

/* ─── Action Buttons (Print / Download) ─── */
.mdo-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #212529;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.mdo-action-btn:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #212529;
}
.mdo-action-btn .material-symbols-rounded {
    font-size: 18px;
}

/* ─── Filter Bar ─── */
.mdo-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.mdo-filter-bar .filter-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    white-space: nowrap;
}
.mdo-filter-bar .form-select {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-size: 0.875rem;
    padding: 7px 32px 7px 12px;
    min-width: 140px;
    width: auto;
}

/* ─── Date Range (Time Period) ─── */
.mdo-date-range .form-control {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-size: 0.875rem;
    padding: 7px 12px;
    min-width: 170px;
    width: auto;
    cursor: pointer;
    background: #fff;
}

/* ─── +2 Filters Button ─── */
.btn-extra-filters {
    border: 1px solid #0d6efd;
    color: #0d6efd;
    background: transparent;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 6px;
    padding: 7px 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    line-height: 1.5;
}
.btn-extra-filters:hover {
    background-color: #e7f0ff;
}

/* ─── Extra Filters Panel (dropdown on small, inline on large) ─── */
.extra-filters-panel {
    padding: 14px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    border: 1px solid #dee2e6;
    min-width: 230px;
}

@media (min-width: 1200px) {
    /* Hide the +2 toggle button — items render inline instead */
    .mdo-extra-filters-wrap > .btn-extra-filters {
        display: none !important;
    }
    /* Override Bootstrap dropdown: show panel as static flex row */
    .mdo-extra-filters-wrap > .extra-filters-panel {
        display: flex !important;
        position: static !important;
        inset: auto !important;
        transform: none !important;
        float: none !important;
        flex-direction: row;
        align-items: flex-end;
        gap: 10px;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        min-width: unset !important;
    }
    .mdo-extra-filters-wrap .extra-filter-item {
        display: flex !important;
        flex-direction: column;
        margin-bottom: 0 !important;
    }
    .mdo-extra-filters-wrap .extra-filter-label {
        display: none !important;
    }
}

/* ─── Reset Filters Button ─── */
.btn-reset-filters {
    border: 2px solid #dc3545;
    color: #dc3545;
    font-weight: 600;
    border-radius: 6px;
    padding: 6px 18px;
    font-size: 0.875rem;
    background: transparent;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-reset-filters:hover {
    background-color: #dc3545;
    color: #fff;
}

/* ─── Search Box ─── */
.mdo-search-wrap {
    display: inline-flex;
    align-items: center;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
    overflow: hidden;
    transition: width 0.3s ease;
    width: 36px;        /* collapsed: icon only */
}
.mdo-search-wrap.is-open {
    width: 220px;       /* expanded */
    border-color: #adb5bd;
}
@media (min-width: 992px) {
    /* on large screens show full width if there is space */
    .mdo-filter-bar:not(.search-collapsed) .mdo-search-wrap {
        width: 220px;
        border-color: #adb5bd;
    }
}
.mdo-search-btn {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    transition: color 0.2s ease;
    padding: 0;
}
.mdo-search-btn:hover {
    color: #0d6efd;
}
.mdo-search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.875rem;
    padding: 0 8px 0 0;
    background: transparent;
    width: 0;
    opacity: 0;
    transition: opacity 0.2s ease;
    color: #212529;
}
.mdo-search-wrap.is-open .mdo-search-input {
    width: auto;
    opacity: 1;
}
@media (min-width: 992px) {
    .mdo-filter-bar:not(.search-collapsed) .mdo-search-input {
        width: auto;
        opacity: 1;
    }
}

/* ─── Flatpickr ─── */
.flatpickr-calendar {
    box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important;
    border-radius: 10px !important;
    border: none !important;
}

/* ─── Hide DataTable default controls ─── */
#mdoescot-table_wrapper .dataTables_length,
#mdoescot-table_wrapper .dataTables_filter,
#mdoescot-table_wrapper .dataTables_info,
#mdoescot-table_wrapper .dataTables_paginate {
    display: none !important;
}

/* ─── Accessibility ─── */
.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

/* ─── Student List Modal ─── */
#studentListModal .modal-content {
    border-radius: 14px;
    border: none;
    box-shadow: 0 12px 40px rgba(0,0,0,0.18);
}
#studentListModal .student-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background 0.12s;
}
#studentListModal .student-item:last-child { border-bottom: none; }
#studentListModal .student-item:hover { background: #f8f9fa; border-radius: 6px; }
#studentListModal .student-item input[type="checkbox"] { flex-shrink: 0; }
#studentListModal .student-list-body {
    max-height: 300px;
    overflow-y: auto;
    padding: 0 4px;
}
#studentListModal .student-list-body::-webkit-scrollbar { width: 5px; }
#studentListModal .student-list-body::-webkit-scrollbar-thumb {
    background: #c7d2e0; border-radius: 10px;
}
/* Chips */
.mdo-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e8f0fe;
    color: #1b3a5c;
    border-radius: 50rem;
    padding: 3px 10px 3px 12px;
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
}
.mdo-chip .chip-remove {
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
    color: #6c757d;
    cursor: pointer;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}
.mdo-chip .chip-remove:hover { color: #dc3545; }
/* Select Students trigger button */
.mdo-student-trigger {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 7px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    background: #fff;
    user-select: none;
    font-size: 0.875rem;
    min-height: 40px;
    transition: border-color 0.15s;
}
.mdo-student-trigger:hover { border-color: #adb5bd; }
</style>

<div class="container-fluid">
    <x-breadcrum title="MDO/Escort Exemption">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addMdoModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add New MDO/Escort Exemption</span>
        </button>
    </x-breadcrum>

    <!-- ─── Top Row: Status Toggle + Action Buttons ─── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="mdo-toggle-group" role="group" aria-label="Status filter">
            <button type="button" id="btn-active" class="btn active-toggle">Active</button>
            <button type="button" id="btn-archived" class="btn inactive-toggle">Archived</button>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="mdo-action-btn text-primary" id="printBtn">
                <i class="material-icons material-symbols-rounded text-primary">print</i>
                <span>Print</span>
            </button>
            <button type="button" class="mdo-action-btn text-primary" id="downloadBtn">
                <i class="material-icons material-symbols-rounded text-primary">download</i>
                <span>Download</span>
            </button>
        </div>
    </div>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                <!-- ─── Filter Bar ─── -->
                <div class="mdo-filter-bar mb-4">
                    <span class="filter-label">Filters</span>

                    <!-- Course Name -->
                    <select id="course_filter" class="form-select">
                        <option value="">Course Name</option>
                        @foreach ($courseMaster as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <!-- Year -->
                    <select id="year_filter" class="form-select">
                        <option value="">Year</option>
                        @foreach ($years as $year => $yearValue)
                        <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>

                    <!-- Duty Type -->
                    <select id="duty_type_filter" class="form-select">
                        <option value="">Duty Type</option>
                        @foreach ($dutyTypes as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <!-- Time Period (Flatpickr Date Range) -->
                    <div class="mdo-date-range">
                        <input type="text" id="dateRangePicker" class="form-control" placeholder="Time Period" readonly>
                        <input type="hidden" id="from_date_filter" value="{{ request('from_date_filter') }}">
                        <input type="hidden" id="to_date_filter" value="{{ request('to_date_filter') }}">
                    </div>

                    <!-- +2 Filters: inline on ≥xl, dropdown on smaller screens -->
                    <div class="dropdown mdo-extra-filters-wrap">
                        <button class="btn-extra-filters" type="button"
                            id="extraFiltersToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            +2 Filters
                        </button>
                        <div class="dropdown-menu extra-filters-panel" aria-labelledby="extraFiltersToggle">
                            <div class="extra-filter-item mb-3">
                                <label class="form-label fw-semibold small text-muted extra-filter-label">Time From</label>
                                <input type="time" id="time_from_filter" class="form-control">
                            </div>
                            <div class="extra-filter-item">
                                <label class="form-label fw-semibold small text-muted extra-filter-label">Time To</label>
                                <input type="time" id="time_to_filter" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Reset Filters -->
                    <button type="button" id="resetFilters" class="btn-reset-filters">Reset Filters</button>

                    <!-- Search Box (icon + expanding input) -->
                    <div class="mdo-search-wrap" id="mdoSearchWrap">
                        <button type="button" class="mdo-search-btn" id="searchToggleBtn" title="Search">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i>
                        </button>
                        <input type="text" class="mdo-search-input" id="mdoSearchInput"
                            placeholder="Search…" autocomplete="off">
                    </div>
                </div>

                <!-- Hidden input for filter status -->
                <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover mb-0']) !!}
                </div>

            </div>
        </div>
    </div>

</div>

{{-- ═════════════════════ STUDENT LIST MODAL (stacked) ══════════════════════ --}}
<div class="modal fade" id="studentListModal" tabindex="-1" aria-labelledby="studentListModalLabel"
    aria-hidden="true" data-bs-backdrop="false" style="z-index:1080;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px;">
        <div class="modal-content border-0 rounded-4" style="box-shadow:0 12px 40px rgba(0,0,0,0.22);">

            {{-- Header: title + count + chips --}}
            <div class="modal-header border-0 d-block px-4 pt-4 pb-0">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="modal-title fw-bold mb-0" id="studentListModalLabel">Student List</h6>
                    <button type="button" class="btn-close" id="smlClose" aria-label="Close"></button>
                </div>
                <hr class="mt-2 mb-0">
                <div class="d-flex align-items-center gap-1 flex-wrap pt-2 pb-1">
                    <span class="fw-semibold small text-muted" id="smlSelectedCount">0 Selected</span>
                    <div class="d-flex gap-1 flex-wrap" id="smlSelectedChips"></div>
                </div>
            </div>

            {{-- Search --}}
            <div class="px-4 py-2 border-bottom">
                <div class="position-relative">
                    <i class="material-icons material-symbols-rounded position-absolute text-muted"
                        style="font-size:17px;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;">search</i>
                    <input type="text" id="smlSearch" class="form-control form-control-sm ps-5"
                        placeholder="Search" autocomplete="off">
                </div>
            </div>

            {{-- List body --}}
            <div class="modal-body px-4 py-2">
                <div class="student-list-body" id="smlStudentList">
                    <div class="text-center text-muted py-3 small fst-italic"
                        id="smlStudentListMsg">Select a course and date first</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 px-4 pb-4 pt-2 justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-danger px-4 fw-semibold"
                    id="smlClearAll">Clear All</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4 fw-semibold"
                        id="smlSelectAll">Select All</button>
                    <button type="button" class="btn btn-sm px-4 fw-semibold" id="smlSave"
                        style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Save</button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Backdrop shim for stacked modal --}}
<div class="modal-backdrop fade" id="smlBackdrop" style="display:none;z-index:1075;"></div>
@php
    $modalFaculty = \App\Models\FacultyMaster::where('active_inactive', 1)
        ->orderBy('full_name')
        ->pluck('full_name', 'pk')
        ->toArray();
@endphp

<div class="modal fade" id="addMdoModal" tabindex="-1" aria-labelledby="addMdoModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 520px;">
        <div class="modal-content border-0 rounded-4" style="box-shadow: 0 12px 40px rgba(0,0,0,0.18);">

            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold fs-6" id="addMdoModalLabel">Add MDO/Escort Exemption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 pt-3 pb-2">
                <form action="{{ route('mdo-escrot-exemption.store') }}" method="POST" id="mdoModalForm"
                    novalidate>
                    @csrf

                    {{-- Course Name --}}
                    <div class="mb-3">
                        <label for="modal_course_pk" class="form-label fw-semibold small">Course Name
                            <span class="text-danger">*</span></label>
                        <select name="course_master_pk" id="modal_course_pk" class="form-select" required>
                            <option value="">Select Course Name</option>
                            @foreach ($courseMaster as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Duty Type --}}
                    <div class="mb-3">
                        <label for="modal_duty_type" class="form-label fw-semibold small">Duty Type
                            <span class="text-danger">*</span></label>
                        <select name="mdo_duty_type_master_pk" id="modal_duty_type" class="form-select" required>
                            <option value="">Select Duty Type</option>
                            @foreach ($dutyTypes as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Faculty (Escort only) --}}
                    <div class="mb-3" id="modal_faculty_wrap" style="display:none;">
                        <label for="modal_faculty" class="form-label fw-semibold small">Faculty
                            <span class="text-danger">*</span></label>
                        <select name="faculty_master_pk" id="modal_faculty" class="form-select">
                            <option value="">Select Faculty</option>
                            @foreach ($modalFaculty as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Start Date --}}
                    <div class="mb-3">
                        <label for="modal_mdo_date" class="form-label fw-semibold small">Start Date</label>
                        <div class="input-group">
                            <input type="date" name="mdo_date" id="modal_mdo_date" class="form-control" required>
                            <span class="input-group-text bg-white">
                                <i class="material-icons material-symbols-rounded text-muted"
                                    style="font-size:18px;">calendar_today</i>
                            </span>
                        </div>
                    </div>

                    {{-- Start Time / End Time --}}
                    <div class="row mb-3 g-3">
                        <div class="col-6">
                            <label for="modal_time_from" class="form-label fw-semibold small">Start Time</label>
                            <div class="input-group">
                                <input type="time" name="Time_from" id="modal_time_from" class="form-control"
                                    required>
                                <span class="input-group-text bg-white">
                                    <i class="material-icons material-symbols-rounded text-muted"
                                        style="font-size:18px;">schedule</i>
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="modal_time_to" class="form-label fw-semibold small">End Time</label>
                            <div class="input-group">
                                <input type="time" name="Time_to" id="modal_time_to" class="form-control" required>
                                <span class="input-group-text bg-white">
                                    <i class="material-icons material-symbols-rounded text-muted"
                                        style="font-size:18px;">schedule</i>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Assign Students --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Assign Students
                            <span class="text-danger">*</span></label>

                        {{-- Trigger: opens Student List modal --}}
                        <div class="mdo-student-trigger" id="mdoStudentTrigger"
                            role="button" tabindex="0" aria-haspopup="dialog">
                            <span class="text-muted small" id="mdoStudentTriggerText">Select Students</span>
                            <i class="material-icons material-symbols-rounded text-muted"
                                style="font-size:18px;">expand_more</i>
                        </div>

                        {{-- Chips row (shown after students saved) --}}
                        <div class="d-flex align-items-center gap-1 flex-wrap mt-2" id="mdoChipsRow" style="display:none !important;">
                            <span class="fw-semibold small text-muted" id="mdoSelectedCount">0 Selected</span>
                            <div class="d-flex gap-1 flex-wrap" id="mdoSelectedChips"></div>
                        </div>

                        {{-- Hidden select for form submission --}}
                        <select name="selected_student_list[]" id="modal_hiddenStudentSelect" multiple
                            style="display:none;"></select>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label for="modal_remark" class="form-label fw-semibold small">Description</label>
                        <textarea name="Remark" id="modal_remark" class="form-control" rows="3"
                            placeholder="eg. Lorem ipsum dolor"></textarea>
                    </div>

                </form>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-1 justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="mdoModalForm" class="btn fw-semibold px-4"
                    style="background:#1b3a5c; color:#fff; border-color:#1b3a5c;">
                    Add MDO/Escort Exemption
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ═════════════════════ EDIT MDO/ESCORT EXEMPTION MODAL ══════════════════════ --}}
<div class="modal fade" id="editMdoModal" tabindex="-1" aria-labelledby="editMdoModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 520px;">
        <div class="modal-content border-0 rounded-4" style="box-shadow: 0 12px 40px rgba(0,0,0,0.18);">

            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold fs-6" id="editMdoModalLabel">Edit MDO/Escort Exemption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 pt-3 pb-2">

                {{-- Student info (read-only) --}}
                <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
                    <i class="material-icons material-symbols-rounded me-2" style="font-size:18px;">person</i>
                    <div class="small">
                        <span class="fw-semibold">Student:&nbsp;</span>
                        <span id="edit_student_name">—</span>
                    </div>
                </div>

                <form action="{{ route('mdo-escrot-exemption.update') }}" method="POST" id="editMdoForm" novalidate>
                    @csrf
                    <input type="hidden" name="pk" id="edit_pk">

                    {{-- Duty Type --}}
                    <div class="mb-3">
                        <label for="edit_duty_type" class="form-label fw-semibold small">Duty Type
                            <span class="text-danger">*</span></label>
                        <select name="mdo_duty_type_master_pk" id="edit_duty_type" class="form-select js-choice" required>
                            <option value="">Select Duty Type</option>
                            @foreach ($dutyTypes as $dtId => $dtName)
                            <option value="{{ $dtId }}">{{ $dtName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Faculty (Escort only) --}}
                    <div class="mb-3" id="edit_faculty_wrap" style="display:none;">
                        <label for="edit_faculty" class="form-label fw-semibold small">Faculty
                            <span class="text-danger">*</span></label>
                        <select name="faculty_master_pk" id="edit_faculty" class="form-select js-choice">
                            <option value="">Select Faculty</option>
                            @foreach ($modalFaculty as $fId => $fName)
                            <option value="{{ $fId }}">{{ $fName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date --}}
                    <div class="mb-3">
                        <label for="edit_mdo_date" class="form-label fw-semibold small">Date
                            <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" name="mdo_date" id="edit_mdo_date" class="form-control" required>
                            <span class="input-group-text bg-white">
                                <i class="material-icons material-symbols-rounded text-muted"
                                    style="font-size:18px;">calendar_today</i>
                            </span>
                        </div>
                    </div>

                    {{-- Start Time / End Time --}}
                    <div class="row mb-3 g-3">
                        <div class="col-6">
                            <label for="edit_time_from" class="form-label fw-semibold small">Start Time
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="time" name="Time_from" id="edit_time_from" class="form-control" required>
                                <span class="input-group-text bg-white">
                                    <i class="material-icons material-symbols-rounded text-muted"
                                        style="font-size:18px;">schedule</i>
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="edit_time_to" class="form-label fw-semibold small">End Time
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="time" name="Time_to" id="edit_time_to" class="form-control" required>
                                <span class="input-group-text bg-white">
                                    <i class="material-icons material-symbols-rounded text-muted"
                                        style="font-size:18px;">schedule</i>
                                </span>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-1 justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editMdoForm" class="btn fw-semibold px-4"
                    style="background:#1b3a5c; color:#fff; border-color:#1b3a5c;">
                    Update MDO/Escort Exemption
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function () {
    var table = $('#mdoescot-table').DataTable();

    // ---- Active / Archived Toggle ----
    function updateStatusToggle(status) {
        if (status === 'archived') {
            $('#btn-active').removeClass('active-toggle').addClass('inactive-toggle');
            $('#btn-archived').removeClass('inactive-toggle').addClass('active-toggle');
        } else {
            $('#btn-active').removeClass('inactive-toggle').addClass('active-toggle');
            $('#btn-archived').removeClass('active-toggle').addClass('inactive-toggle');
        }
    }
    updateStatusToggle($('#filter_status').val() || 'active');

    $('#btn-active').on('click', function () {
        $('#filter_status').val('active');
        updateStatusToggle('active');
        table.ajax.reload();
    });

    $('#btn-archived').on('click', function () {
        $('#filter_status').val('archived');
        updateStatusToggle('archived');
        table.ajax.reload();
    });

    // ---- Flatpickr Date Range (Time Period) ----
    flatpickr('#dateRangePicker', {
        mode: 'range',
        showMonths: 2,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        allowInput: false,
        onChange: function (selectedDates) {
            if (selectedDates.length === 2) {
                var fmt = function (d) { return d.toISOString().slice(0, 10); };
                $('#from_date_filter').val(fmt(selectedDates[0]));
                $('#to_date_filter').val(fmt(selectedDates[1]));
                table.ajax.reload();
            } else if (selectedDates.length === 0) {
                $('#from_date_filter').val('');
                $('#to_date_filter').val('');
                table.ajax.reload();
            }
        },
        onClose: function (selectedDates) {
            if (selectedDates.length === 1) {
                var fmt = function (d) { return d.toISOString().slice(0, 10); };
                $('#from_date_filter').val(fmt(selectedDates[0]));
                $('#to_date_filter').val(fmt(selectedDates[0]));
                table.ajax.reload();
            }
        }
    });

    // ---- Filter Selects Reload ----
    $('#course_filter, #year_filter, #duty_type_filter').on('change', function () {
        table.ajax.reload();
    });

    $('#time_from_filter, #time_to_filter').on('change', function () {
        table.ajax.reload();
    });

    // ---- Search Box Toggle ----
    var $searchWrap = $('#mdoSearchWrap');
    var $searchInput = $('#mdoSearchInput');
    var $filterBar   = $searchWrap.closest('.mdo-filter-bar');

    // On large screens, auto-expand if there is room (remove collapsed class)
    function checkAutoExpand() {
        if ($(window).width() >= 992) {
            $searchWrap.addClass('is-open');
            $filterBar.removeClass('search-collapsed');
        } else {
            $filterBar.addClass('search-collapsed');
            if (!$searchWrap.hasClass('is-open')) {
                $searchInput.val('');
            }
        }
    }
    checkAutoExpand();
    $(window).on('resize', checkAutoExpand);

    $('#searchToggleBtn').on('click', function () {
        $searchWrap.toggleClass('is-open');
        if ($searchWrap.hasClass('is-open')) {
            $searchInput.trigger('focus');
        } else {
            $searchInput.val('');
            table.search('').draw();
        }
    });

    var searchDelay;
    $searchInput.on('input', function () {
        clearTimeout(searchDelay);
        var q = $(this).val();
        searchDelay = setTimeout(function () {
            table.search(q).draw();
        }, 350);
    });

    $searchInput.on('keydown', function (e) {
        if (e.key === 'Escape') {
            $(this).val('');
            table.search('').draw();
            if ($(window).width() < 992) {
                $searchWrap.removeClass('is-open');
            }
        }
    });

    // ---- Reset Filters ----
    $('#resetFilters').on('click', function () {
        window.location.href = '{{ route("mdo-escrot-exemption.index", ["filter" => "active"]) }}';
    });

    // ---- Pass Filters to Server ----
    $('#mdoescot-table').on('preXhr.dt', function (e, settings, data) {
        data.filter           = $('#filter_status').val() || 'active';
        data.course_filter    = $('#course_filter').val();
        data.year_filter      = $('#year_filter').val();
        data.duty_type_filter = $('#duty_type_filter').val();
        data.time_from_filter = $('#time_from_filter').val();
        data.time_to_filter   = $('#time_to_filter').val();
        data.from_date_filter = $('#from_date_filter').val();
        data.to_date_filter   = $('#to_date_filter').val();
    });

    // ---- Print Button ----
    $('#printBtn').on('click', function () {
        var tableClone = $('#mdoescot-table').clone();
        tableClone.find('th:last-child, td:last-child').remove();

        var printWindow = window.open('', '_blank');
        var tableHtml = '<!DOCTYPE html><html><head><title>MDO/Escort Exemption</title>';
        tableHtml += '<style>';
        tableHtml += 'body { font-family: Arial, sans-serif; margin: 20px; }';
        tableHtml += 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
        tableHtml += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
        tableHtml += 'th { background-color: #1b3a5c; color: white; font-weight: bold; }';
        tableHtml += 'tr:nth-child(even) { background-color: #f2f2f2; }';
        tableHtml += 'h2 { color: #004a93; margin-bottom: 20px; }';
        tableHtml += '@media print { body { margin: 0; } @page { margin: 1cm; } }';
        tableHtml += '</style></head><body>';
        tableHtml += '<h2>MDO/Escort Exemption</h2>';

        var cleanTable = tableClone[0].outerHTML;
        cleanTable = cleanTable.replace(/<th[^>]*>Actions<\/th>/gi, '');
        cleanTable = cleanTable.replace(/<td[^>]*>[\s\S]*?(edit|delete|Actions)[\s\S]*?<\/td>/gi, '');

        tableHtml += cleanTable;
        tableHtml += '</body></html>';

        printWindow.document.write(tableHtml);
        printWindow.document.close();
        setTimeout(function () { printWindow.print(); }, 250);
    });

    // ---- Download Button ----
    $('#downloadBtn').on('click', function () {
        var csvBtn = table.button('.buttons-csv');
        if (csvBtn && csvBtn.length) {
            csvBtn.trigger();
        } else {
            $('#printBtn').trigger('click');
        }
    });

    // ════════════════════════════════════════
    // ADD MDO/ESCORT EXEMPTION MODAL
    // ════════════════════════════════════════

    // ── Duty Type → Faculty toggle ──
    $('#modal_duty_type').on('change', function () {
        var txt = $(this).find('option:selected').text().toLowerCase().trim();
        if (txt === 'escort') {
            $('#modal_faculty_wrap').show();
            $('#modal_faculty').prop('required', true);
        } else {
            $('#modal_faculty_wrap').hide();
            $('#modal_faculty').val('').prop('required', false);
        }
    });

    // ── Load students via AJAX when course + date change ──
    function loadModalStudents() {
        var courseId = $('#modal_course_pk').val();
        var date     = $('#modal_mdo_date').val();
        if (!courseId || !date) return;

        $('#smlStudentListMsg').text('Loading students…').show();
        $('#smlStudentList .student-item').remove();

        $.ajax({
            url: "{{ route('mdo-escrot-exemption.get.student.list.according.to.course') }}",
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                selectedCourses: courseId,
                selectedDate: date
            },
            success: function (res) {
                $('#smlStudentList .student-item').remove();

                if (!res.status || !res.students || res.students.length === 0) {
                    $('#smlStudentListMsg').text('No students found for selected course & date').show();
                    return;
                }

                $('#smlStudentListMsg').hide();
                res.students.forEach(function (s) {
                    if (!s) return;
                    var $item = $('<div class="student-item" tabindex="0"></div>')
                        .attr('data-id', s.pk)
                        .attr('data-name', s.display_name || '')
                        .attr('data-ot', s.ot_code || '');

                    var $cb = $('<input type="checkbox" class="form-check-input student-modal-cb">')
                        .val(s.pk)
                        .attr('id', 'msc_' + s.pk);

                    var $lbl = $('<label class="mb-0 flex-grow-1"></label>')
                        .attr('for', 'msc_' + s.pk)
                        .text(s.display_name || 'N/A')
                        .css('cursor', 'pointer');

                    $item.append($cb).append($lbl);
                    $('#smlStudentList').append($item);
                });

                // Restore previously checked state
                $('#modal_hiddenStudentSelect option').each(function () {
                    var id = $(this).val();
                    $('#smlStudentList .student-modal-cb[value="' + id + '"]').prop('checked', true);
                });
            },
            error: function () {
                $('#smlStudentListMsg').text('Error loading students. Please try again.').show();
            }
        });
    }

    $('#modal_course_pk, #modal_mdo_date').on('change', function () {
        resetModalStudents();
        loadModalStudents();
    });

    // ── Open Student List modal on trigger click ──
    $('#mdoStudentTrigger').on('click keydown', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        // Show shim backdrop
        $('#smlBackdrop').css('display', 'block').addClass('show');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('studentListModal')).show();
    });

    // ── Close button on student modal ──
    $('#smlClose').on('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('studentListModal')).hide();
    });

    // ── Hide backdrop when student modal closes ──
    document.getElementById('studentListModal').addEventListener('hidden.bs.modal', function () {
        $('#smlBackdrop').removeClass('show').css('display', 'none');
        $('#smlSearch').val('');
        // Filter reset
        $('#smlStudentList .student-item').show();
    });

    // ── Search inside Student modal ──
    $('#smlSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('#smlStudentList .student-item').each(function () {
            var name = ($(this).data('name') || '').toLowerCase();
            var ot   = ($(this).data('ot')   || '').toLowerCase();
            $(this).toggle(name.includes(q) || ot.includes(q));
        });
    });

    // ── Select All ──
    $('#smlSelectAll').on('click', function () {
        $('#smlStudentList .student-item:visible .student-modal-cb').prop('checked', true);
        refreshStudentChips();
    });

    // ── Clear All ──
    $('#smlClearAll').on('click', function () {
        $('#smlStudentList .student-modal-cb').prop('checked', false);
        refreshStudentChips();
    });

    // ── Save: apply selection, close student modal ──
    $('#smlSave').on('click', function () {
        refreshStudentChips();
        bootstrap.Modal.getInstance(document.getElementById('studentListModal')).hide();
    });

    // ── Chip remove ──
    $(document).on('click', '.mdo-chip .chip-remove', function () {
        var id = $(this).data('id');
        $('#smlStudentList .student-modal-cb[value="' + id + '"]').prop('checked', false);
        refreshStudentChips();
    });

    // ── Rebuild chips + hidden select ──
    function refreshStudentChips() {
        var selected = [];
        $('#smlStudentList .student-modal-cb:checked').each(function () {
            selected.push({ id: $(this).val(), name: $(this).closest('.student-item').data('name') });
        });

        // Chips row in addMdoModal
        var chipsHtml = '';
        selected.forEach(function (s) {
            chipsHtml += '<span class="mdo-chip">' + $('<div>').text(s.name).html() +
                '<button type="button" class="chip-remove" data-id="' + s.id + '" aria-label="Remove">' +
                '&times;</button></span>';
        });
        $('#mdoSelectedChips').html(chipsHtml);
        $('#mdoSelectedCount').text(selected.length + ' Selected');

        // Count chips in student modal header
        $('#smlSelectedCount').text(selected.length + ' Selected');
        var smlChips = '';
        selected.forEach(function (s) {
            smlChips += '<span class="mdo-chip">' + $('<div>').text(s.name).html() +
                '<button type="button" class="chip-remove" data-id="' + s.id + '" aria-label="Remove">' +
                '&times;</button></span>';
        });
        $('#smlSelectedChips').html(smlChips);

        if (selected.length === 0) {
            $('#mdoStudentTriggerText').text('Select Students').addClass('text-muted');
            $('#mdoChipsRow').hide();
        } else {
            $('#mdoStudentTriggerText').text(selected.length + ' student(s) selected').removeClass('text-muted');
            $('#mdoChipsRow').css('display', 'flex');
        }

        // Hidden select
        $('#modal_hiddenStudentSelect').empty();
        selected.forEach(function (s) {
            $('#modal_hiddenStudentSelect').append(new Option(s.name, s.id, true, true));
        });
    }

    function resetModalStudents() {
        $('#smlStudentList .student-item').remove();
        $('#smlStudentListMsg').text('Loading students…').show();
        $('#modal_hiddenStudentSelect').empty();
        $('#mdoSelectedChips').html('');
        $('#smlSelectedChips').html('');
        $('#mdoSelectedCount, #smlSelectedCount').text('0 Selected');
        $('#mdoStudentTriggerText').text('Select Students').addClass('text-muted');
        $('#mdoChipsRow').hide();
    }

    // ── Reset entire modal on close ──
    $('#addMdoModal').on('hidden.bs.modal', function () {
        $('#mdoModalForm')[0].reset();
        $('#modal_faculty_wrap').hide();
        $('#modal_faculty').prop('required', false);
        $('#smlSearch').val('');
        resetModalStudents();
        $('#smlStudentListMsg').text('Select a course and date first').show();
    });

    // ════════════════════════════════════════
    // EDIT MDO/ESCORT EXEMPTION MODAL
    // ════════════════════════════════════════

    // ── Initialise Choices.js instances for edit modal selects ──
    var editDutyTypeChoice = null;
    var editFacultyChoice  = null;

    document.getElementById('editMdoModal').addEventListener('shown.bs.modal', function () {
        if (!editDutyTypeChoice) {
            editDutyTypeChoice = new Choices(document.getElementById('edit_duty_type'), {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: ''
            });
        }
        if (!editFacultyChoice) {
            editFacultyChoice = new Choices(document.getElementById('edit_faculty'), {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: ''
            });
        }
    });

    // ── Populate fields when modal opens ──
    document.getElementById('editMdoModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        if (!btn) return;

        var pk       = btn.getAttribute('data-pk');
        var dutyType = btn.getAttribute('data-duty-type');
        var faculty  = btn.getAttribute('data-faculty');
        var date     = btn.getAttribute('data-date');
        var timeFrom = btn.getAttribute('data-time-from');
        var timeTo   = btn.getAttribute('data-time-to');
        var student  = btn.getAttribute('data-student');

        $('#edit_pk').val(pk);
        $('#edit_student_name').text(student || '—');
        $('#edit_mdo_date').val(date);
        $('#edit_time_from').val(timeFrom);
        $('#edit_time_to').val(timeTo);

        // Set Choices values after instances are available (shown.bs.modal fires after show.bs.modal)
        // Store pending values so shown.bs.modal can apply them
        document.getElementById('editMdoModal').dataset.pendingDutyType = dutyType || '';
        document.getElementById('editMdoModal').dataset.pendingFaculty  = faculty  || '';
    });

    // ── After instances are ready, apply pending values ──
    document.getElementById('editMdoModal').addEventListener('shown.bs.modal', function () {
        var dutyType = this.dataset.pendingDutyType || '';
        var faculty  = this.dataset.pendingFaculty  || '';

        if (editDutyTypeChoice) editDutyTypeChoice.setChoiceByValue(dutyType);
        toggleEditFaculty(dutyType, faculty);
    });

    // ── Duty Type → Faculty toggle (Choices-aware) ──
    function toggleEditFaculty(dutyTypeVal, facultyVal) {
        var txt = $('#edit_duty_type option[value="' + dutyTypeVal + '"]').text().toLowerCase().trim();
        if (txt === 'escort') {
            $('#edit_faculty_wrap').show();
            $('#edit_faculty').prop('required', true);
            if (editFacultyChoice) {
                editFacultyChoice.enable();
                editFacultyChoice.setChoiceByValue(facultyVal || '');
            } else {
                $('#edit_faculty').val(facultyVal || '');
            }
        } else {
            $('#edit_faculty_wrap').hide();
            $('#edit_faculty').prop('required', false);
            if (editFacultyChoice) {
                editFacultyChoice.setChoiceByValue('');
                editFacultyChoice.disable();
            } else {
                $('#edit_faculty').val('');
            }
        }
    }

    // Choices.js fires a native 'change' event
    document.getElementById('edit_duty_type').addEventListener('change', function () {
        toggleEditFaculty(this.value, '');
    });

    // ── Reset edit modal on close ──
    $('#editMdoModal').on('hidden.bs.modal', function () {
        $('#editMdoForm')[0].reset();
        $('#edit_faculty_wrap').hide();
        $('#edit_faculty').prop('required', false);
        $('#edit_student_name').text('—');
        if (editDutyTypeChoice) editDutyTypeChoice.setChoiceByValue('');
        if (editFacultyChoice)  { editFacultyChoice.setChoiceByValue(''); editFacultyChoice.disable(); }
    });

});
</script>
@endpush