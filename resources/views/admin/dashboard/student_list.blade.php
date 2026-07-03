@extends('admin.layouts.master')

@section('title', 'Student List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .student-list-page .sl-filter-select {
        width: 200px;
        flex: 0 0 auto;
        height: 40px;
        border-radius: 8px;
        font-size: 0.9375rem;
        color: #344054;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem;
        background-position: right 0.75rem center;
    }

    /* Inside the +3 Filters popover the selects span the menu width. */
    .student-list-page .sl-more-menu .sl-filter-select { width: 100%; }

    /* Relocatable filter items: inline by default, floating-label boxes when in the dropdown. */
    .student-list-page .sl-filter-item { display: inline-flex; align-items: center; }
    .student-list-page .sl-filter-label-text { display: none; }

    /* ── Inside the +N Filters popover: each filter is a floating-label box ── */
    .student-list-page .sl-more-menu-header {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #101828;
        padding-bottom: 0.65rem;
        margin-bottom: 0.85rem;
        border-bottom: 1px solid #eef2f6;
    }
    .student-list-page .sl-more-menu .sl-filter-item { display: block; position: relative; margin-bottom: 0.75rem; }
    .student-list-page .sl-more-menu .sl-filter-item:last-child { margin-bottom: 0; }
    .student-list-page .sl-more-menu .sl-filter-label-text {
        display: block;
        position: absolute;
        top: 6px;
        left: 0.875rem;
        z-index: 2;
        font-size: 0.7rem;
        font-weight: 400;
        color: #667085;
        margin: 0;
        pointer-events: none;
    }
    .student-list-page .sl-more-menu .sl-filter-select {
        width: 100%;
        height: 52px;
        border-radius: 10px;
        padding-top: 1.35rem;
        padding-bottom: 0.35rem;
        color: #101828;
        background-position: right 0.75rem center;
    }
    .student-list-page .sl-more-menu .sl-filter-item .sl-daterange-wrap { display: block; width: 100%; }
    .student-list-page .sl-more-menu .sl-filter-item .sl-daterange-input { width: 100%; height: 52px; border-radius: 10px; }
    .student-list-page .sl-more-menu #slOverflowSlot:not(:empty) { margin-bottom: 0.75rem; }

    .student-list-page .sl-filter-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }

    .student-list-page .sl-daterange-wrap { position: relative; }

    .student-list-page .sl-daterange-input {
        width: 230px;
        height: 48px;
        padding: 1.15rem 0.875rem 0.25rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.875rem;
    }

    .student-list-page .sl-daterange-label {
        position: absolute;
        top: 5px;
        left: 0.875rem;
        font-size: 0.7rem;
        color: #667085;
        pointer-events: none;
    }

    .student-list-page .sl-more-menu { min-width: 264px; }
    .student-list-page .sl-more-menu .form-label { margin-bottom: 0.25rem; }
    .student-list-page .sl-more-filters.dropdown-toggle::after { display: none; }

    .student-list-page .sl-toolbar-btn {
        height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 1.1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        color: #004a93;
        border-radius: 8px;
        background: #fff;
    }

    .student-list-page .sl-toolbar-btn:hover { background: #f9fafb; }
    .student-list-page .sl-toolbar-btn i { font-size: 1rem; line-height: 1; }

    .student-list-page .sl-more-filters {
        color: #004a93;
        font-weight: 600;
        font-size: 0.9375rem;
        text-decoration: none;
        white-space: nowrap;
    }
    .student-list-page .sl-more-filters:hover { text-decoration: underline; }

    .student-list-page .sl-table-shell { position: relative; }
    .student-list-page .sl-table-loading {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.74);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 30;
        backdrop-filter: blur(1px);
    }
    .student-list-page .sl-table-loading.is-active { display: flex; }
    .student-list-page .sl-table-loading-card {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.7rem 0.95rem;
        border: 1px solid #d0d5dd;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(16, 24, 40, 0.12);
        color: #344054;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .student-list-page .programme-dt-table tbody td a.sl-count {
        color: #004a93;
        font-weight: 600;
        text-decoration: none;
        white-space: wrap;
    }
    .student-list-page .programme-dt-table tbody td a.sl-count:hover { text-decoration: underline; }

    .student-list-page .sl-extra-filters { border: 1px dashed #e4e7ec; border-radius: 8px; background: #fcfcfd; }

    .student-list-page .sl-status-badge {
        display: inline-flex;
        align-items: center;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1;
        padding: 0.45rem 1.1rem;
        border-radius: 10px;
    }
    .student-list-page .sl-status-absent { color: #b42318; background: #fef3f2; }
    .student-list-page .sl-status-present { color: #027a48; background: #ecfdf3; }

    /* Wide table with frozen first three columns (S.No., OT Code, Name). */
    .student-list-page .programme-dt-table { width: 100% !important; }
    .student-list-page .programme-dt-table th,
    .student-list-page .programme-dt-table td { white-space: nowrap; }
    .student-list-page .programme-dt-table th:nth-child(1),
    .student-list-page .programme-dt-table td:nth-child(1) { min-width: 90px; }
    .student-list-page .programme-dt-table th:nth-child(2),
    .student-list-page .programme-dt-table td:nth-child(2) { min-width: 130px; }
    .student-list-page .programme-dt-table th:nth-child(3),
    .student-list-page .programme-dt-table td:nth-child(3) { min-width: 260px; }

    /* scrollX is OFF (it would clone the header into a second table and break
       sticky freezing). The scroll host is the ONE horizontal scroller. */
    .student-list-page .sl-dt-scroll-host { overflow-x: auto; overflow-y: visible; }

    /* CRITICAL: the DataTables bootstrap5 CSS puts overflow:hidden on the
       <table>, which turns the table into its own scroll container and anchors
       the sticky cells to the (scrolling) table instead of the host — so they
       never freeze. Force the table to overflow:visible so the sticky cells
       anchor to .sl-dt-scroll-host. */
    .student-list-page .programme-dt-table { overflow: visible !important; }

    /* Freeze the first three columns via sticky positioning on the single table. */
    .student-list-page .programme-dt-table {
        --sl-pin-left-0: 0px;
        --sl-pin-left-1: 90px;
        --sl-pin-left-2: 220px;
    }
    .student-list-page .programme-dt-table thead th:nth-child(-n+3),
    .student-list-page .programme-dt-table tbody td:nth-child(-n+3) {
        position: sticky;
    }
    /* Pinned cells need an opaque fill so scrolled columns don't bleed through. */
    .student-list-page .programme-dt-table thead th:nth-child(-n+3) {
        z-index: 6;
        background: #f2f4f7 !important;
    }
    .student-list-page .programme-dt-table tbody td:nth-child(-n+3) {
        z-index: 3;
        background: #fff;
    }
    .student-list-page .programme-dt-table tbody tr:hover td:nth-child(-n+3) {
        background: #f7fafc;
    }
    .student-list-page .programme-dt-table th:nth-child(1),
    .student-list-page .programme-dt-table td:nth-child(1) { left: var(--sl-pin-left-0); }
    .student-list-page .programme-dt-table th:nth-child(2),
    .student-list-page .programme-dt-table td:nth-child(2) { left: var(--sl-pin-left-1); }
    .student-list-page .programme-dt-table th:nth-child(3),
    .student-list-page .programme-dt-table td:nth-child(3) { left: var(--sl-pin-left-2); }
    /* Divider on the last frozen column marks the freeze edge while scrolling. */
    .student-list-page .programme-dt-table th:nth-child(3),
    .student-list-page .programme-dt-table td:nth-child(3) { box-shadow: 1px 0 0 #e5e7eb; }

    /* ── Collapsible search: full field when there's room, icon-only otherwise ── */
    .student-list-page .sl-search-wrap { position: relative; display: inline-flex; align-items: center; }
    .student-list-page .sl-search-toggle {
        display: none;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        background: #fff;
        color: #475467;
    }
    .student-list-page .sl-search-toggle:hover { background: #f9fafb; }

    @media (max-width: 991.98px) {
        .student-list-page .sl-search-toggle { display: inline-flex; }
        /* The actual search field drops into a small popover anchored to the icon. */
        .student-list-page .sl-search-wrap .programme-dt-search {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 1030;
            min-width: 240px;
            padding: 0.5rem;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(16, 24, 40, 0.12);
            display: none;
        }
        .student-list-page .sl-search-wrap.sl-search-open .programme-dt-search { display: block; }
        .student-list-page .sl-search-wrap .programme-dt-search .dataTables_filter { margin: 0; width: 100%; }
        .student-list-page .sl-search-wrap .programme-dt-search .dataTables_filter input { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $filters = $filters ?? [];
    // The Absent tab uses a slim layout (Status badge, no count columns);
    // the Present tab keeps the full duty/exemption count breakdown.
    $isAbsent = ($filters['attendance'] ?? '') === 'absent';
@endphp
<div class="container-fluid student-list-page">
    <x-breadcrum title="Student List" :showBack="true" />
    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Attendance status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ !$isAbsent ? 'active' : '' }}"
                    data-attendance="present">Present</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $isAbsent ? 'active' : '' }}"
                    data-attendance="absent">Absent</button>
            </li>
        </ul>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn sl-toolbar-btn" id="studentListPrintBtn">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn sl-toolbar-btn dropdown-toggle" id="studentListDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="studentListDownloadBtn">
                    <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="studentListDownloadCsv"><i class="bi bi-filetype-csv text-success"></i><span>Download CSV</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="studentListDownloadPdf"><i class="bi bi-filetype-pdf text-danger"></i><span>Download PDF</span></button></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3" id="slFilterRow">
                    <span class="programme-dt-filters-label">Filters</span>
                    @if($availableCourses->isNotEmpty())
                    <div class="sl-filter-item" id="slItemCourse">
                        <span class="sl-filter-label-text">Course</span>
                        <select id="courseFilter" class="form-select sl-filter-select" aria-label="Filter by course">
                            <option value="">Course</option>
                            @foreach($availableCourses as $course)
                                <option value="{{ $course['pk'] }}" {{ (string)($filters['course_id'] ?? '') === (string)$course['pk'] ? 'selected' : '' }}>{{ $course['course_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="sl-filter-item" id="slItemDuty">
                        <span class="sl-filter-label-text">Duty Type</span>
                        <select id="dutyTypeFilter" class="form-select sl-filter-select" aria-label="Filter by duty type">
                            <option value="">Duty Type</option>
                            @foreach(($dutyTypes ?? []) as $dt)
                                <option value="{{ $dt->pk }}" {{ (string)($filters['duty_type'] ?? '') === (string)$dt->pk ? 'selected' : '' }}>{{ $dt->mdo_duty_type_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sl-filter-item" id="slItemPeriod">
                        <div class="sl-daterange-wrap">
                            <span class="sl-daterange-label">Time Period</span>
                            <input type="text" id="timePeriodFilter" class="form-control sl-filter-select sl-daterange-input"
                                placeholder="Select dates" autocomplete="off" readonly aria-label="Filter by time period"
                                value="{{ (!empty($filters['from_date']) && !empty($filters['to_date'])) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y').' - '.\Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : '' }}">
                        </div>
                    </div>
                    <div class="dropdown" id="slMoreFiltersWrap">
                        <a href="javascript:void(0)" class="sl-more-filters dropdown-toggle" id="moreFiltersBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+3 Filters</a>
                        <div class="dropdown-menu sl-more-menu p-3 shadow-sm border rounded-3" aria-labelledby="moreFiltersBtn">
                            <div class="sl-more-menu-header">Filters</div>
                            <div id="slOverflowSlot"></div>
                            <div class="sl-filter-item">
                                <span class="sl-filter-label-text">ACC</span>
                                <select id="roleFilter" class="form-select sl-filter-select w-100" aria-label="Filter by ACC">
                                    <option value="">All</option>
                                    <option value="cc_acc" {{ ($filters['role_filter'] ?? '') === 'cc_acc' ? 'selected' : '' }}>CC/ACC</option>
                                    @if(isset($counsellorTypes) && $counsellorTypes->isNotEmpty())
                                        @foreach($counsellorTypes as $type)
                                            <option value="{{ $type->type_pk }}" {{ (string)($filters['role_filter'] ?? '') === (string)$type->type_pk ? 'selected' : '' }}>{{ $type->counsellor_type_name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="sl-filter-item" id="slItemCounsellorFaculty" @unless(($filters['role_filter'] ?? '') === 'cc_acc') style="display:none;" @endunless>
                                <span class="sl-filter-label-text">CC/ACC Faculty</span>
                                <select id="counsellorFacultyFilter" class="form-select sl-filter-select w-100" aria-label="Filter by CC/ACC faculty">
                                    <option value="">All Faculty</option>
                                    @foreach(($counsellorFaculties ?? []) as $faculty)
                                        <option value="{{ $faculty->faculty_pk }}" {{ (string)($filters['counsellor_faculty'] ?? '') === (string)$faculty->faculty_pk ? 'selected' : '' }}>{{ $faculty->faculty_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sl-filter-item">
                                <span class="sl-filter-label-text">Cadre</span>
                                <select id="cadreFilter" class="form-select sl-filter-select w-100" aria-label="Filter by cadre">
                                    <option value="">All</option>
                                    @foreach(($cadreOptions ?? []) as $cadre)
                                        <option value="{{ $cadre }}" {{ (string)($filters['cadre'] ?? '') === (string)$cadre ? 'selected' : '' }}>{{ $cadre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sl-filter-item">
                                <span class="sl-filter-label-text">House Name</span>
                                <select id="houseFilter" class="form-select sl-filter-select w-100" aria-label="Filter by house name">
                                    <option value="">All</option>
                                    @foreach(($houseOptions ?? []) as $house)
                                        <option value="{{ $house }}" {{ (string)($filters['house'] ?? '') === (string)$house ? 'selected' : '' }}>{{ $house }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnStudentColumns" data-bs-toggle="modal" data-bs-target="#studentColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="sl-search-wrap" id="studentSearchWrap" @if($isAbsent) style="display:none;" @endif>
                        <button type="button" class="sl-search-toggle" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
                        <div id="studentDtSearch" class="programme-dt-search" data-dt-search-for="studentListTable"></div>
                    </div>
                    <div class="sl-search-wrap" id="studentSearchWrapAbsent" @unless($isAbsent) style="display:none;" @endunless>
                        <button type="button" class="sl-search-toggle" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
                        <div id="studentDtSearchAbsent" class="programme-dt-search" data-dt-search-for="studentListTableAbsent"></div>
                    </div>
                </div>
            </div>

            <div class="sl-table-shell">
                <div class="sl-table-loading" id="studentTableLoading" aria-live="polite" aria-busy="false">
                    <div class="sl-table-loading-card">
                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        <span>Loading students...</span>
                    </div>
                </div>

            {{-- ── Present panel (full layout) ── --}}
            <div class="programme-dt-panel" id="studentPresentPanel" @if($isAbsent) style="display:none;" @endif>
                <div class="sl-dt-scroll-host">
                    <table class="table programme-dt-table" id="studentListTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>OT Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Cadre</th>
                                <th>Status</th>
                                <th>Date &amp; Session</th>
                                <th>Topic</th>
                                <th>House Name</th>
                                <th>Total Duty (Count)</th>
                                <th>Total Medical Exemption Count</th>
                                <th>Total PT Exemption Count</th>
                                <th>Total Stationed Leave Count</th>
                                <th>Total Notice/Memo</th>
                                <th>Total Discipline Memo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="studentDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="studentListTable"></div>
            </div>

            {{-- ── Absent panel (slim layout) ── --}}
            <div class="programme-dt-panel" id="studentAbsentPanel" @unless($isAbsent) style="display:none;" @endunless>
                <div class="sl-dt-scroll-host">
                    <table class="table programme-dt-table" id="studentListTableAbsent">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>OT Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Cadre</th>
                                <th>Status</th>
                                <th>Date &amp; Session</th>
                                <th>Topic</th>
                                <th>Total Medical Exemption Count</th>
                                <th>Total PT Exemption Count</th>
                                <th>Total Stationed Leave Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="studentDtFooterAbsent" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="studentListTableAbsent"></div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="studentColumnVisibilityModal" tabindex="-1" aria-labelledby="studentColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="studentColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="studentColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        const filters = @json($filters ?? []);
        const baseUrl = "{{ route('admin.dashboard.students') }}";
        const LOCKED_COLUMNS = [0, 1, 2];
        let dtPresent = null;
        let dtAbsent = null;
        let activeDt = null;
        let loadingRequests = 0;

        function setTableLoading(show) {
            const $loader = $('#studentTableLoading');
            if (!$loader.length) { return; }
            if (show) {
                loadingRequests += 1;
            } else {
                loadingRequests = Math.max(loadingRequests - 1, 0);
            }
            const active = loadingRequests > 0;
            $loader.toggleClass('is-active', active).attr('aria-busy', active ? 'true' : 'false');
        }

        function getFilterState() {
            return {
                course_id: $('#courseFilter').val() || '',
                duty_type: $('#dutyTypeFilter').val() || '',
                role_filter: $('#roleFilter').val() || '',
                counsellor_faculty: ($('#roleFilter').val() === 'cc_acc') ? ($('#counsellorFacultyFilter').val() || '') : '',
                cadre: $('#cadreFilter').val() || '',
                house: $('#houseFilter').val() || '',
                from_date: (filters.from_date || '').toString(),
                to_date: (filters.to_date || '').toString(),
            };
        }

        function syncUrl(attendanceOverride) {
            const p = new URLSearchParams(window.location.search);
            const state = getFilterState();

            Object.entries(state).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) {
                    p.delete(k);
                } else {
                    p.set(k, v);
                }
            });

            const activeAttendance = attendanceOverride || (activeDt === dtAbsent ? 'absent' : 'present');
            if (activeAttendance === 'absent') {
                p.set('attendance', 'absent');
            } else {
                p.delete('attendance');
            }

            const qs = p.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }

        function applyFilter(changes) {
            Object.entries(changes || {}).forEach(([k, v]) => {
                if (k === 'from_date' || k === 'to_date') {
                    filters[k] = v || '';
                }
            });
            syncUrl();
            if (activeDt) {
                activeDt.ajax.reload(null, true);
            }
        }

        function buildAjaxConfig(attendance) {
            return {
                url: baseUrl,
                type: 'GET',
                data: function(d) {
                    const state = getFilterState();
                    d.course_id = state.course_id;
                    d.duty_type = state.duty_type;
                    d.role_filter = state.role_filter;
                    d.counsellor_faculty = state.counsellor_faculty;
                    d.cadre = state.cadre;
                    d.house = state.house;
                    d.from_date = state.from_date;
                    d.to_date = state.to_date;
                    d.attendance = attendance;
                }
            };
        }

        const dtBaseOpts = {
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            order: [[0, 'asc']],
            language: { emptyTable: 'Data not found.' },
            responsive: false,
            autoWidth: false,
        };

        // Measure the actual widths of the three frozen columns and feed them
        // back as the sticky `left` offsets so each pinned column butts up
        // against the previous one regardless of content width.
        function relayoutPinnedColumns(dt) {
            if (!dt) { return; }
            const tableNode = dt.table().node();
            if (!tableNode) { return; }
            const $headCells = $(tableNode).find('thead th');
            if (!$headCells.length) { return; }

            const fallbackWidths = [90, 130, 260];
            let runningLeft = 0;
            LOCKED_COLUMNS.forEach(function(colIdx, i) {
                const $cell = $headCells.eq(colIdx);
                const width = Math.max(0, Math.round(($cell.outerWidth() || fallbackWidths[i] || 100)));
                tableNode.style.setProperty('--sl-pin-left-' + i, runningLeft + 'px');
                runningLeft += width;
            });
        }

        dtPresent = $('#studentListTable').DataTable($.extend(true, {}, dtBaseOpts, {
            ajax: buildAjaxConfig('present'),
            columns: [
                { data: 's_no', name: 's_no' },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'name', name: 'name', orderable: true, searchable: true },
                { data: 'email', name: 'email' },
                { data: 'cadre', name: 'cadre' },
                { data: 'status', name: 'status', searchable: false },
                { data: 'date_session', name: 'date_session' },
                { data: 'topic', name: 'topic' },
                { data: 'house_name', name: 'house_name' },
                { data: 'total_duty_count', name: 'total_duty_count', searchable: false },
                { data: 'total_medical_exception_count', name: 'total_medical_exception_count', searchable: false },
                { data: 'total_pt_exemption_count', name: 'total_pt_exemption_count', searchable: false },
                { data: 'total_stationed_leave_count', name: 'total_stationed_leave_count', searchable: false },
                { data: 'total_notice_count', name: 'total_notice_count', searchable: false },
                { data: 'total_memo_count', name: 'total_memo_count', searchable: false },
            ]
        }));

        dtAbsent = $('#studentListTableAbsent').DataTable($.extend(true, {}, dtBaseOpts, {
            ajax: buildAjaxConfig('absent'),
            columns: [
                { data: 's_no', name: 's_no' },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'name', name: 'name', orderable: true, searchable: true },
                { data: 'email', name: 'email' },
                { data: 'cadre', name: 'cadre' },
                { data: 'status', name: 'status', searchable: false },
                { data: 'date_session', name: 'date_session' },
                { data: 'topic', name: 'topic' },
                { data: 'total_medical_exception_count', name: 'total_medical_exception_count', searchable: false },
                { data: 'total_pt_exemption_count', name: 'total_pt_exemption_count', searchable: false },
                { data: 'total_stationed_leave_count', name: 'total_stationed_leave_count', searchable: false },
            ]
        }));

        $('#studentListTable, #studentListTableAbsent')
            .on('preXhr.dt', function() { setTableLoading(true); })
            .on('xhr.dt error.dt', function() { setTableLoading(false); });

        activeDt = (filters.attendance || 'present') === 'absent' ? dtAbsent : dtPresent;

        /* ── Present / Absent tab switch (no page reload) ── */
        function switchStudentTab(att) {
            const isAbsent = att === 'absent';
            $('.programme-status-tabs .programme-status-pill').removeClass('active');
            $('.programme-status-tabs .programme-status-pill[data-attendance="' + att + '"]').addClass('active');
            $('#studentPresentPanel').toggle(!isAbsent);
            $('#studentAbsentPanel').toggle(isAbsent);
            // Toggle the search wrappers via display (not jQuery .toggle) so the
            // CSS inline-flex / collapsible behaviour is preserved.
            $('#studentSearchWrap').css('display', isAbsent ? 'none' : '');
            $('#studentSearchWrapAbsent').css('display', isAbsent ? '' : 'none');
            $('.sl-search-wrap').removeClass('sl-search-open');
            activeDt = isAbsent ? dtAbsent : dtPresent;
            if (activeDt) {
                activeDt.columns.adjust();
                relayoutPinnedColumns(activeDt);
                activeDt.ajax.reload(null, false);
                setupStudentColumns(activeDt);
            }
            syncUrl(att);
        }
        $('.programme-status-tabs .programme-status-pill').on('click', function() {
            switchStudentTab($(this).data('attendance'));
        });

        /* ── Collapsible search: icon expands the input on small screens ── */
        $('.sl-search-toggle').on('click', function(e) {
            e.stopPropagation();
            const $wrap = $(this).closest('.sl-search-wrap');
            $('.sl-search-wrap').not($wrap).removeClass('sl-search-open');
            $wrap.toggleClass('sl-search-open');
            if ($wrap.hasClass('sl-search-open')) { $wrap.find('input').trigger('focus'); }
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.sl-search-wrap').length) {
                $('.sl-search-wrap').removeClass('sl-search-open');
            }
        });
        $('#courseFilter').on('change', function() { applyFilter({ course_id: this.value }); });
        $('#dutyTypeFilter').on('change', function() { applyFilter({ duty_type: this.value }); });
        function toggleCounsellorFaculty() {
            const isCcAcc = $('#roleFilter').val() === 'cc_acc';
            $('#slItemCounsellorFaculty').toggle(isCcAcc);
            if (!isCcAcc) { $('#counsellorFacultyFilter').val(''); }
        }
        $('#roleFilter').on('change', function() {
            toggleCounsellorFaculty();
            applyFilter({ role_filter: this.value });
        });
        $('#counsellorFacultyFilter').on('change', function() { applyFilter({ counsellor_faculty: this.value }); });
        toggleCounsellorFaculty();
        $('#cadreFilter').on('change', function() { applyFilter({ cadre: this.value }); });
        $('#houseFilter').on('change', function() { applyFilter({ house: this.value }); });
        $('#resetFilters').on('click', function() { window.location.href = baseUrl; });

        /* ── Time Period date-range ── */
        const $period = $('#timePeriodFilter');
        $period.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            locale: { format: 'DD-MM-YYYY', cancelLabel: 'Clear', applyLabel: 'Apply' },
            ranges: {
                'Today': [moment(), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
        });
        $period.on('apply.daterangepicker', function(ev, picker) {
            $period.val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            applyFilter({ from_date: picker.startDate.format('YYYY-MM-DD'), to_date: picker.endDate.format('YYYY-MM-DD') });
        });
        $period.on('cancel.daterangepicker', function() {
            $period.val('');
            applyFilter({ from_date: '', to_date: '' });
        });

        // Print opens the clean report (same data as the PDF export) in a new
        // tab that auto-triggers the browser print dialog — not the whole page.
        $('#studentListPrintBtn').on('click', function() { window.open(buildExportUrl('print'), '_blank'); });

        function buildExportUrl(format) {
            const params = new URLSearchParams(window.location.search);
            params.delete('attendance');
            const search = activeDt ? activeDt.search() : '';
            if (search) params.set('search', search);
            const base = format === 'csv'
                ? "{{ route('admin.dashboard.students.export', ['format' => 'csv']) }}"
                : format === 'print'
                    ? "{{ route('admin.dashboard.students.export', ['format' => 'print']) }}"
                    : "{{ route('admin.dashboard.students.export', ['format' => 'pdf']) }}";
            const q = params.toString();
            return q ? `${base}?${q}` : base;
        }
        $('#studentListDownloadCsv').on('click', function(e) { e.preventDefault(); window.location.href = buildExportUrl('csv'); });
        $('#studentListDownloadPdf').on('click', function(e) { e.preventDefault(); window.open(buildExportUrl('pdf'), '_blank'); });

        /* ---------------- Column show / hide (per active table) ---------------- */
        function studentColKey(dt) { return 'studentListGrid:hiddenColumns:v1:' + dt.table().node().id; }
        function studentGetHiddenCols(key) {
            try { const raw = localStorage.getItem(key); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function studentPersistHiddenCols(key, arr) { try { localStorage.setItem(key, JSON.stringify(arr)); } catch (e) {} }
        function setupStudentColumns(dt) {
            if (!dt) { return; }
            const key = studentColKey(dt);
            const hidden = studentGetHiddenCols(key).filter(idx => LOCKED_COLUMNS.indexOf(idx) === -1);
            studentPersistHiddenCols(key, hidden);
            dt.columns().every(function() {
                const idx = this.index();
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) {
                    this.visible(true, false);
                    return;
                }
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();
            relayoutPinnedColumns(dt);
            const $grid = $('#studentColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function() {
                const idx = this.index();
                const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                const isLocked = LOCKED_COLUMNS.indexOf(idx) !== -1;
                const inputId = 'studentcolvis_' + idx;
                const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
                const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', isLocked || hidden.indexOf(idx) === -1)
                    .prop('disabled', isLocked);
                $cb.on('change', function() {
                    if (isLocked) { return; }
                    const h = studentGetHiddenCols(key);
                    const pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                    studentPersistHiddenCols(key, h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    relayoutPinnedColumns(dt);
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        if (activeDt) { setupStudentColumns(activeDt); }

        [dtPresent, dtAbsent].forEach(function(dt) {
            if (!dt) { return; }
            dt.on('draw.dt column-visibility.dt', function() {
                relayoutPinnedColumns(dt);
            });
        });

        $(window).on('resize', function() {
            if (activeDt) {
                activeDt.columns.adjust();
                relayoutPinnedColumns(activeDt);
            }
        });

        /* ── Responsive filters: overflow inline filters into the +Filters dropdown ── */
        const $filterRow = $('#slFilterRow');
        const $overflowSlot = $('#slOverflowSlot');
        const $moreWrap = $('#slMoreFiltersWrap');
        const $moreBtn = $('#moreFiltersBtn');
        const inlineFilterIds = ['#slItemCourse', '#slItemDuty', '#slItemPeriod'];
        const STATIC_DROPDOWN_COUNT = 3; // ACC, Cadre, House — always live in the menu.

        function updateMoreFiltersLabel() {
            const moved = $overflowSlot.children('.sl-filter-item').length;
            $moreBtn.text('+' + (STATIC_DROPDOWN_COUNT + moved) + ' Filters');
        }
        function filterRowWraps(baseTop) {
            let wraps = false;
            $filterRow.children(':visible').each(function() {
                if (this.getBoundingClientRect().top > baseTop + 2) { wraps = true; return false; }
            });
            return wraps;
        }
        function reflowFilters() {
            // 1. Put every inline filter back in its original order before the dropdown.
            inlineFilterIds.forEach(function(sel) {
                const el = document.querySelector(sel);
                if (el) { $(el).insertBefore($moreWrap); }
            });
            // 2. Move trailing filters into the dropdown until the row stops wrapping.
            const first = $filterRow.children(':visible').first()[0];
            if (first) {
                const baseTop = first.getBoundingClientRect().top;
                let guard = 0;
                while (filterRowWraps(baseTop) && guard < 10) {
                    const $last = $filterRow.children('.sl-filter-item').last();
                    if (!$last.length) { break; }
                    $overflowSlot.prepend($last); // prepend keeps Course → Duty → Time order
                    guard++;
                }
            }
            updateMoreFiltersLabel();
        }
        reflowFilters();
        $(window).on('load', reflowFilters);
        let filterReflowTimer = null;
        $(window).on('resize', function() {
            clearTimeout(filterReflowTimer);
            filterReflowTimer = setTimeout(reflowFilters, 150);
        });
    });
</script>
@endpush

@endsection
