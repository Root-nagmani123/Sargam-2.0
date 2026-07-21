@extends('admin.layouts.master')

@section('title', 'Student List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}" />
<link rel="stylesheet" href="{{ asset('css/dashboard-stat-cards.css') }}?v=2" />
<style>
    /* ── Title bar (programme name) ── */
    .student-list-page .sl-title-card .sl-title-text { color: #101828; letter-spacing: 0.2px; }

    /* ── Tab counts ── */
    .student-list-page .programme-status-pill .sl-tab-count { font-variant-numeric: tabular-nums; }

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

    /* Inside the +N Filters popover the selects span the menu width. */
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

    /* ── Searchable filter dropdowns (Select2) — sized to match .sl-filter-select chips ── */
    .student-list-page .sl-filter-item .select2-container { width: 200px !important; flex: 0 0 auto; }
    .student-list-page .sl-filter-item .select2-container--default .select2-selection--single {
        height: 40px;
        border-radius: 8px;
        border-color: #d0d5dd;
    }
    .student-list-page .sl-filter-item .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 0.9375rem;
        color: #344054;
    }
    .student-list-page .sl-filter-item .select2-container--default.select2-container--focus .select2-selection--single,
    .student-list-page .sl-filter-item .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }
    /* Inside the +N Filters popover the Select2 widgets span the menu width. */
    .student-list-page .sl-more-menu .sl-filter-item .select2-container { width: 100% !important; }

    /* ── Select2 "clear" (×) — Select2's default float:right drops it to the top-right,
         out of the box's vertical centring and colliding with the caret. Pin it to the
         vertical middle, just left of the chevron, and leave room in the value text. ── */
    .student-list-page .sl-filter-item .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 3.25rem;
    }
    .student-list-page .sl-filter-item .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute;
        top: 50%;
        right: 2.55rem;
        transform: translateY(-50%);
        float: none;
        height: auto;
        width: auto;
        margin: 0;
        padding: 0;
        line-height: 1;
        font-size: 1.05rem;
        font-weight: 700;
        color: #98a2b3;
    }
    .student-list-page .sl-filter-item .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #475467;
    }
    .student-list-page .sl-filter-item .select2-selection__clear > span { line-height: 1; }

    .student-list-page .sl-daterange-wrap { position: relative; }

    .student-list-page .sl-daterange-input {
        width: 200px;
        height: 40px;
        padding: 1.05rem 0.875rem 0.15rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.85rem;
    }

    .student-list-page .sl-daterange-label {
        position: absolute;
        top: 4px;
        left: 0.875rem;
        font-size: 0.68rem;
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
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        background: #fff;
    }

    .student-list-page .sl-toolbar-btn:hover { background: #f9fafb; }
    .student-list-page .sl-toolbar-btn i { font-size: 1rem; line-height: 1; }

    /* +N Filters wrapper sits inline in the flex filter row (JS toggles it on/off). */
    .student-list-page .sl-more-filters-wrap { display: inline-flex; align-items: center; }

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
    .student-list-page .sl-status-late { color: #b54708; background: #fffaeb; }

    /* Wide table with frozen first two columns (S.No., Name — the OT code is
       rendered under the name instead of in its own column). */
    .student-list-page .programme-dt-table { width: 100% !important; }
    .student-list-page .programme-dt-table th,
    .student-list-page .programme-dt-table td { white-space: nowrap; }
    .student-list-page .programme-dt-table th:nth-child(1),
    .student-list-page .programme-dt-table td:nth-child(1) { min-width: 70px; }
    .student-list-page .programme-dt-table th:nth-child(2),
    .student-list-page .programme-dt-table td:nth-child(2) { min-width: 220px; }

    /* scrollX is OFF (it would clone the header into a second table and break
       sticky freezing). The scroll host is the ONE horizontal scroller. */
    .student-list-page .sl-dt-scroll-host { overflow-x: auto; overflow-y: visible; }

    /* CRITICAL: the DataTables bootstrap5 CSS puts overflow:hidden on the
       <table>; force overflow:visible so the sticky cells anchor to the host. */
    .student-list-page .programme-dt-table { overflow: visible !important; }

    /* Freeze the first two columns via sticky positioning on the single table. */
    .student-list-page .programme-dt-table {
        --sl-pin-left-0: 0px;
        --sl-pin-left-1: 70px;
    }
    .student-list-page .programme-dt-table thead th:nth-child(-n+2),
    .student-list-page .programme-dt-table tbody td:nth-child(-n+2) {
        position: sticky;
    }
    .student-list-page .programme-dt-table thead th:nth-child(-n+2) {
        z-index: 6;
        background: #f2f4f7 !important;
    }
    .student-list-page .programme-dt-table tbody td:nth-child(-n+2) {
        z-index: 3;
        background: #fff;
    }
    .student-list-page .programme-dt-table tbody tr:hover td:nth-child(-n+2) {
        background: #f7fafc;
    }
    .student-list-page .programme-dt-table th:nth-child(1),
    .student-list-page .programme-dt-table td:nth-child(1) { left: var(--sl-pin-left-0); }
    .student-list-page .programme-dt-table th:nth-child(2),
    .student-list-page .programme-dt-table td:nth-child(2) { left: var(--sl-pin-left-1); }
    .student-list-page .programme-dt-table th:nth-child(2),
    .student-list-page .programme-dt-table td:nth-child(2) { box-shadow: 1px 0 0 #e5e7eb; }

    /* OT code shown as a muted second line inside the Name cell. */
    .student-list-page .sl-ot-code { color: #667085; font-size: 0.78rem; line-height: 1.2; margin-top: 2px; }

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
    $tabCounts = $tabCounts ?? ['all' => 0, 'present' => 0, 'absent' => 0];
    $activeAtt = in_array(($filters['attendance'] ?? 'all'), ['present', 'absent'], true) ? $filters['attendance'] : 'all';
    $pad = fn ($n) => str_pad((string) (int) $n, 2, '0', STR_PAD_LEFT);
@endphp
<div class="container-fluid student-list-page">
    <x-breadcrum title="{{ $listTitle ?? 'Student List' }}" :showBack="true" />
    <x-session_message />

    {{-- Summary cards — TODAY's actual marked attendance (distinct students),
         resolved server-side in the controller ($cardCounts). These are a
         fixed daily snapshot and intentionally do NOT follow the table filters. --}}
    @php
        $cardCounts = $cardCounts ?? ['total' => 0, 'present_today' => 0, 'absent_today' => 0];
        $snapshotDate = $snapshotDate ?? null;
        $snapshotIso = $snapshotDate ? \Carbon\Carbon::parse($snapshotDate)->toDateString() : \Carbon\Carbon::today()->toDateString();
        $snapshotLabel = $snapshotDate
            ? (\Carbon\Carbon::parse($snapshotDate)->isToday() ? 'Today' : \Carbon\Carbon::parse($snapshotDate)->format('d M Y'))
            : 'Today';
    @endphp
    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-3 sl-summary-cards">
        <div class="col">
            <a href="{{ route('admin.dashboard.ot-participants') }}" class="text-decoration-none d-block h-100">
                <div class="card stat-card h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper stat-icon-amber">
                            <i class="material-symbols-rounded" aria-hidden="true">campaign</i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <p class="stat-title">OT/ Participants Details</p>
                            <p class="stat-value">{{ $pad($cardCounts['total']) }}</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.dashboard.students', ['attendance' => 'present', 'from_date' => $snapshotIso, 'to_date' => $snapshotIso]) }}" class="text-decoration-none d-block h-100">
                <div class="card stat-card h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper stat-icon-green">
                            <i class="material-symbols-rounded" aria-hidden="true">groups</i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <p class="stat-title">Present {{ $snapshotLabel }}</p>
                            <p class="stat-value">{{ $pad($cardCounts['present_today']) }}</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.dashboard.students', ['attendance' => 'absent', 'from_date' => $snapshotIso, 'to_date' => $snapshotIso]) }}" class="text-decoration-none d-block h-100">
                <div class="card stat-card h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper stat-icon-rose">
                            <i class="material-symbols-rounded" aria-hidden="true">badge</i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <p class="stat-title">Absent {{ $snapshotLabel }}</p>
                            <p class="stat-value">{{ $pad($cardCounts['absent_today']) }}</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- All / Present / Absent tabs + Print / Download --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Attendance status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeAtt === 'all' ? 'active' : '' }}"
                    data-attendance="all">All: <span class="sl-tab-count" data-count="all">{{ $pad($tabCounts['all']) }}</span></button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeAtt === 'present' ? 'active' : '' }}"
                    data-attendance="present">Present: <span class="sl-tab-count" data-count="present">{{ $pad($tabCounts['present']) }}</span></button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeAtt === 'absent' ? 'active' : '' }}"
                    data-attendance="absent">Absent: <span class="sl-tab-count" data-count="absent">{{ $pad($tabCounts['absent']) }}</span></button>
            </li>
        </ul>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn sl-toolbar-btn border-0" id="studentListPrintBtn">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn sl-toolbar-btn dropdown-toggle border-0" id="studentListDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="studentListDownloadBtn">
                    <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="studentListDownloadCsv"><i class="bi bi-file-earmark-excel text-success"></i><span>Download Excel</span></button></li>
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

                    {{-- Time Period --}}
                    <div class="sl-filter-item" id="slItemPeriod">
                        <div class="sl-daterange-wrap">
                            <span class="sl-daterange-label">Time Period</span>
                            <input type="text" id="timePeriodFilter" class="form-control sl-filter-select sl-daterange-input"
                                placeholder="Select dates" autocomplete="off" readonly aria-label="Filter by time period"
                                value="{{ (!empty($filters['from_date']) && !empty($filters['to_date'])) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y').' - '.\Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : '' }}">
                        </div>
                    </div>

                    {{-- Course --}}
                    @if(($courseOptions ?? collect())->isNotEmpty())
                    <div class="sl-filter-item" id="slItemCourse">
                        <span class="sl-filter-label-text">Course</span>
                        <select id="courseFilter" class="form-select sl-filter-select" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courseOptions as $course)
                                @php
                                    $cStart = !empty($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('j F Y') : '';
                                    $cEnd = !empty($course->end_date) ? \Carbon\Carbon::parse($course->end_date)->format('j F Y') : '';
                                    $cDuration = ($cStart && $cEnd) ? $cStart . ' to ' . $cEnd : '';
                                @endphp
                                <option value="{{ $course->pk }}"
                                    data-shortname="{{ $course->couse_short_name ?? '' }}"
                                    data-duration="{{ $cDuration }}"
                                    {{ (string)($filters['course_id'] ?? '') === (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Cadre --}}
                    @if(($cadreOptions ?? collect())->isNotEmpty())
                    <div class="sl-filter-item" id="slItemCadre">
                        <span class="sl-filter-label-text">Cadre</span>
                        <select id="cadreFilter" class="form-select sl-filter-select" aria-label="Filter by cadre">
                            <option value="">Cadre</option>
                            @foreach($cadreOptions as $cadre)
                                <option value="{{ $cadre }}" {{ (string)($filters['cadre'] ?? '') === (string)$cadre ? 'selected' : '' }}>{{ $cadre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Session --}}
                    <div class="sl-filter-item" id="slItemSession">
                        <span class="sl-filter-label-text">Session</span>
                        <select id="sessionFilter" class="form-select sl-filter-select" aria-label="Filter by session">
                            <option value="">Session</option>
                            @foreach(($sessionOptions ?? []) as $sess)
                                <option value="{{ $sess }}" {{ (string)($filters['session'] ?? '') === (string)$sess ? 'selected' : '' }}>{{ $sess }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Topic --}}
                    <div class="sl-filter-item" id="slItemTopic">
                        <select id="topicFilter" class="form-select sl-filter-select" aria-label="Filter by topic">
                            <option value="">Topic</option>
                            @foreach(($topicOptions ?? []) as $topic)
                                <option value="{{ $topic }}" {{ (string)($filters['topic'] ?? '') === (string)$topic ? 'selected' : '' }}>{{ $topic }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- OT / Participant --}}
                    <div class="sl-filter-item" id="slItemParticipant">
                        <select id="participantFilter" class="form-select sl-filter-select" aria-label="Filter by OT / participant">
                            <option value="">OT / Participant</option>
                            @foreach(($participantOptions ?? []) as $p)
                                <option value="{{ $p->pk }}" {{ (string)($filters['participant'] ?? '') === (string)$p->pk ? 'selected' : '' }}>{{ $p->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Overflow "+N Filters" popover: any filter that doesn't fit the
                         row is moved in here by the reflow script below. --}}
                    <div class="dropdown sl-more-filters-wrap" id="slMoreFiltersWrap" style="display:none;">
                        <button type="button" class="btn sl-more-filters dropdown-toggle" id="moreFiltersBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false">+0 Filters</button>
                        <div class="dropdown-menu dropdown-menu-end sl-more-menu p-3">
                            <div class="sl-more-menu-header">More Filters</div>
                            <div id="slOverflowSlot"></div>
                        </div>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnStudentColumns" data-bs-toggle="modal" data-bs-target="#studentColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="sl-search-wrap" id="studentSearchWrap">
                        <button type="button" class="sl-search-toggle" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
                        <div id="studentDtSearch" class="programme-dt-search" data-dt-search-for="studentListTable"></div>
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

                <div class="programme-dt-panel">
                    <div class="sl-dt-scroll-host">
                        <table class="table programme-dt-table" id="studentListTable">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>User name</th>
                                    <th>Cadre</th>
                                    <th>Date</th>
                                    <th>Session</th>
                                    <th>Topic</th>
                                    <th>Faculty</th>
                                    <th>Attendance Status</th>
                                    <th>MDO</th>
                                    <th>Escort/Moderator Duty</th>
                                    <th>Other Exemptions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="studentDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="studentListTable"></div>
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
        const LOCKED_COLUMNS = [0, 1];
        let dt = null;
        let currentAttendance = (filters.attendance === 'present' || filters.attendance === 'absent') ? filters.attendance : 'all';
        let loadingRequests = 0;

        function setTableLoading(show) {
            const $loader = $('#studentTableLoading');
            if (!$loader.length) { return; }
            loadingRequests = show ? loadingRequests + 1 : Math.max(loadingRequests - 1, 0);
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
                session: $('#sessionFilter').val() || '',
                topic: $('#topicFilter').val() || '',
                participant: $('#participantFilter').val() || '',
                from_date: (filters.from_date || '').toString(),
                to_date: (filters.to_date || '').toString(),
            };
        }

        function syncUrl() {
            const p = new URLSearchParams(window.location.search);
            const state = getFilterState();
            Object.entries(state).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) { p.delete(k); } else { p.set(k, v); }
            });
            if (currentAttendance && currentAttendance !== 'all') { p.set('attendance', currentAttendance); } else { p.delete('attendance'); }
            const qs = p.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }

        function applyFilter(changes) {
            Object.entries(changes || {}).forEach(([k, v]) => {
                if (k === 'from_date' || k === 'to_date') { filters[k] = v || ''; }
            });
            syncUrl();
            if (dt) { dt.ajax.reload(null, true); }
        }

        // ── Cascading Session / Topic dropdowns ──────────────────────────────
        // The server returns fresh Session/Topic options for the current date range
        // (and selected Session) on every reload; rebuild both dropdowns here so the
        // cascade date range → Session → Topic stays in sync. A still-valid selection
        // is preserved; one that falls outside the new scope is cleared.
        let rebuildingFilters = false;
        function rebuildSelectOptions($sel, values, placeholder) {
            if (!$sel.length) { return { changed: false }; }
            const prev = ($sel.val() || '').toString();
            if ($sel.data('select2')) { $sel.select2('destroy'); }
            $sel.empty().append($('<option>').val('').text(placeholder));
            let prevValid = false;
            (values || []).forEach(function(v) {
                const val = (v === null || v === undefined) ? '' : v.toString();
                const $o = $('<option>').val(val).text(val);   // .text() escapes DB values
                if (val !== '' && val === prev) { $o.prop('selected', true); prevValid = true; }
                $sel.append($o);
            });
            if (!prevValid && prev !== '') { $sel.val(''); }
            return { changed: (!prevValid && prev !== '') };
        }

        function applyCascadingFilterOptions(opts) {
            if (!opts || rebuildingFilters) { return; }
            rebuildingFilters = true;
            const sessRes = rebuildSelectOptions($('#sessionFilter'), opts.session, 'Session');
            const topicRes = rebuildSelectOptions($('#topicFilter'), opts.topic, 'Topic');
            initFilterSelect2();
            rebuildingFilters = false;
            // A previously-selected Session/Topic that fell outside the new scope was
            // cleared above — re-sync the URL and reload once so the table matches.
            if (sessRes.changed || topicRes.changed) {
                syncUrl();
                setTimeout(function() { if (dt) { dt.ajax.reload(null, false); } }, 0);
            }
        }

        function padCount(n) { n = parseInt(n, 10) || 0; return (n < 10 ? '0' : '') + n; }
        function updateTabCounts(counts) {
            if (!counts) { return; }
            ['all', 'present', 'absent'].forEach(function(k) {
                if (counts[k] !== undefined) {
                    $('.sl-tab-count[data-count="' + k + '"]').text(padCount(counts[k]));
                }
            });
        }

        function relayoutPinnedColumns() {
            if (!dt) { return; }
            const tableNode = dt.table().node();
            if (!tableNode) { return; }
            const $headCells = $(tableNode).find('thead th');
            if (!$headCells.length) { return; }
            const fallbackWidths = [70, 220];
            let runningLeft = 0;
            LOCKED_COLUMNS.forEach(function(colIdx, i) {
                const $cell = $headCells.eq(colIdx);
                const width = Math.max(0, Math.round(($cell.outerWidth() || fallbackWidths[i] || 100)));
                tableNode.style.setProperty('--sl-pin-left-' + i, runningLeft + 'px');
                runningLeft += width;
            });
        }

        dt = $('#studentListTable').DataTable({
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
            ajax: {
                url: baseUrl,
                type: 'GET',
                data: function(d) {
                    const state = getFilterState();
                    Object.assign(d, state);
                    d.attendance = currentAttendance;
                    // Present/Absent details require a date — show a hint instead of
                    // the generic "Data not found." when the date filter is empty.
                    try {
                        const dateRequired = (currentAttendance === 'present' || currentAttendance === 'absent')
                            && !(state.from_date || state.to_date);
                        dt.settings()[0].oLanguage.sEmptyTable = dateRequired
                            ? 'Please select a date to view Present / Absent details.'
                            : 'Data not found.';
                    } catch (e) {}
                }
            },
            columns: [
                { data: 's_no', name: 's_no' },
                { data: 'name', name: 'name', orderable: true, searchable: true },
                { data: 'course', name: 'course' },
                { data: 'username', name: 'username' },
                { data: 'cadre', name: 'cadre' },
                { data: 'date', name: 'date', searchable: false },
                { data: 'session', name: 'session', searchable: false },
                { data: 'topic', name: 'topic' },
                { data: 'faculty', name: 'faculty', searchable: false },
                { data: 'status', name: 'status', searchable: false },
                { data: 'mdo', name: 'mdo', searchable: false },
                { data: 'escort', name: 'escort', searchable: false },
                { data: 'other_exempt', name: 'other_exempt', searchable: false },
            ]
        });

        dt.on('preXhr.dt', function() { setTableLoading(true); })
          .on('xhr.dt', function(e, settings, json) {
              setTableLoading(false);
              if (json && json.counts) { updateTabCounts(json.counts); }
              if (json && json.filterOptions) { applyCascadingFilterOptions(json.filterOptions); }
          })
          .on('error.dt', function() { setTableLoading(false); })
          .on('draw.dt column-visibility.dt', function() { relayoutPinnedColumns(); });

        /* ── All / Present / Absent tabs ── */
        $('.programme-status-tabs .programme-status-pill').on('click', function() {
            const att = $(this).data('attendance');
            if (att === currentAttendance) { return; }
            currentAttendance = att;
            $('.programme-status-tabs .programme-status-pill').removeClass('active');
            $(this).addClass('active');
            syncUrl();
            if (dt) { dt.ajax.reload(null, true); }
        });

        /* ── Collapsible search ── */
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

        /* ── Filters ── */
        $('#sessionFilter').on('change', function() { applyFilter({ session: this.value }); });
        $('#topicFilter').on('change', function() { applyFilter({ topic: this.value }); });
        $('#participantFilter').on('change', function() { applyFilter({ participant: this.value }); });
        $('#courseFilter').on('change', function() { applyFilter({ course_id: this.value }); });
        $('#dutyTypeFilter').on('change', function() { applyFilter({ duty_type: this.value }); });
        function toggleCounsellorFaculty() {
            const isCcAcc = $('#roleFilter').val() === 'cc_acc';
            $('#slItemCounsellorFaculty').toggle(isCcAcc);
            if (!isCcAcc) { $('#counsellorFacultyFilter').val(''); }
        }
        $('#roleFilter').on('change', function() { toggleCounsellorFaculty(); applyFilter({ role_filter: this.value }); });
        $('#counsellorFacultyFilter').on('change', function() { applyFilter({ counsellor_faculty: this.value }); });
        toggleCounsellorFaculty();
        $('#cadreFilter').on('change', function() { applyFilter({ cadre: this.value }); });
        $('#houseFilter').on('change', function() { applyFilter({ house: this.value }); });
        $('#resetFilters').on('click', function() { window.location.href = baseUrl; });

        /* ── Searchable filter dropdowns (Select2) ── */
        // Turn every filter <select> into a type-to-search dropdown. Select2 fires the
        // native `change` event, so the handlers bound above keep working unchanged.
        function initFilterSelect2() {
            if (!$.fn.select2) { return; }
            $('#courseFilter, #sessionFilter, #topicFilter, #cadreFilter, #participantFilter').each(function() {
                const $sel = $(this);
                if ($sel.data('select2')) { $sel.select2('destroy'); }
                const placeholder = ($sel.find('option[value=""]').first().text() || 'Select').trim();
                $sel.select2({
                    width: '200px',
                    placeholder: placeholder,
                    allowClear: true,
                    dropdownParent: $('body'),
                });
            });
        }
        initFilterSelect2();

        /* ── Time Period date-range ── */
        const $period = $('#timePeriodFilter');
        // Open the calendar on the currently-applied date (snapshot from a card,
        // else today's default) instead of always jumping to today.
        const pickerStart = filters.from_date ? moment(filters.from_date, 'YYYY-MM-DD') : moment();
        const pickerEnd = filters.to_date ? moment(filters.to_date, 'YYYY-MM-DD') : moment();
        $period.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            startDate: pickerStart,
            endDate: pickerEnd,
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

        /* ── Print / Download ── */
        $('#studentListPrintBtn').on('click', function() { window.open(buildExportUrl('print'), '_blank'); });
        function buildExportUrl(format) {
            const params = new URLSearchParams(window.location.search);
            // Report follows the on-screen view: keep the current filters AND the
            // active Present/Absent tab so the export matches exactly.
            if (currentAttendance && currentAttendance !== 'all') { params.set('attendance', currentAttendance); } else { params.delete('attendance'); }
            const search = dt ? dt.search() : '';
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

        /* ── Column show / hide ── */
        const studentColStorageKey = 'studentListGrid:hiddenColumns:v7:studentListTable';
        function studentGetHiddenCols() {
            try { const raw = localStorage.getItem(studentColStorageKey); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function studentPersistHiddenCols(arr) { try { localStorage.setItem(studentColStorageKey, JSON.stringify(arr)); } catch (e) {} }
        function setupStudentColumns() {
            if (!dt) { return; }
            const hidden = studentGetHiddenCols().filter(idx => LOCKED_COLUMNS.indexOf(idx) === -1);
            studentPersistHiddenCols(hidden);
            dt.columns().every(function() {
                const idx = this.index();
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) { this.visible(true, false); return; }
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();
            relayoutPinnedColumns();
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
                    const h = studentGetHiddenCols();
                    const pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                    studentPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    relayoutPinnedColumns();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        setupStudentColumns();

        $(window).on('resize', function() {
            if (dt) { dt.columns.adjust(); relayoutPinnedColumns(); }
        });

        /* ── Responsive filters: filters that fit stay inline; the rest collapse
              into the "+N Filters" dropdown. Recomputed on load + resize. ── */
        const $filterRow = $('#slFilterRow');
        const $overflowSlot = $('#slOverflowSlot');
        const $moreWrap = $('#slMoreFiltersWrap');
        const $moreBtn = $('#moreFiltersBtn');
        // Visual left-to-right order: leftmost filters stay inline the longest.
        const inlineFilterIds = ['#slItemPeriod', '#slItemCourse', '#slItemCadre', '#slItemSession', '#slItemTopic', '#slItemParticipant'];

        function updateMoreFiltersLabel() {
            const moved = $overflowSlot.children('.sl-filter-item').length;
            if (moved > 0) {
                $moreBtn.text('+' + moved + ' Filters');
                $moreWrap.css('display', '');       // show (falls back to CSS inline-flex)
            } else {
                $moreWrap.css('display', 'none');   // nothing overflowed → hide the button
            }
        }
        function filterRowWraps(baseTop) {
            let wraps = false;
            $filterRow.children(':visible').each(function() {
                if (this.getBoundingClientRect().top > baseTop + 2) { wraps = true; return false; }
            });
            return wraps;
        }
        function reflowFilters() {
            // 1. Pull every filter back inline (drains the overflow slot).
            inlineFilterIds.forEach(function(sel) {
                const el = document.querySelector(sel);
                if (el) { $(el).insertBefore($moreWrap); }
            });
            // 2. Show the button so its own width counts while measuring.
            $moreWrap.css('display', '');
            // 3. Move the trailing filters into the overflow until the row fits on one line.
            const first = $filterRow.children(':visible').first();
            if (first.length) {
                const baseTop = first[0].getBoundingClientRect().top;
                let guard = 0;
                while (filterRowWraps(baseTop) && guard < 12) {
                    const $last = $filterRow.children('.sl-filter-item').last();
                    if (!$last.length) { break; }
                    $overflowSlot.prepend($last);
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

        /* ── Close the "+N Filters" popover on an outside click ──
           The dropdown is data-bs-auto-close="false" because its filters use
           Select2 / the daterangepicker, whose menus render on <body>; Bootstrap's
           built-in auto-close would treat interacting with those as an "outside"
           click and close the popover. So we close it ourselves, but only on a
           genuine outside click — never when the click landed in the popover, a
           Select2 widget/dropdown, or the daterangepicker calendar. */
        $(document).on('mousedown.slMoreFilters', function(e) {
            if ($moreBtn.attr('aria-expanded') !== 'true') { return; } // popover closed
            if ($(e.target).closest('#slMoreFiltersWrap, .select2-container, .select2-dropdown, .daterangepicker').length) {
                return; // click inside the popover or one of its body-rendered menus
            }
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                bootstrap.Dropdown.getOrCreateInstance($moreBtn[0]).hide();
            } else {
                // Fallback: mirror what Bootstrap's hide() does to the markup.
                $moreWrap.find('.dropdown-menu').removeClass('show');
                $moreBtn.attr('aria-expanded', 'false');
            }
        });
    });
</script>
@endpush

@endsection
