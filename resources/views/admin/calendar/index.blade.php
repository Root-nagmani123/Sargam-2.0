@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable')

@section('setup_content')

@php
    // Debug: Check if courseMaster is available
    if (!isset($courseMaster) || $courseMaster->isEmpty()) {
        \Log::error('Calendar view: courseMaster is empty or not set');
    }
@endphp

<link rel="stylesheet" href="{{ asset('admin_assets/css/styles.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="{{ asset('css/calendar-admin.css') }}?v={{ @filemtime(public_path('css/calendar-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/cal-event-pill.css') }}">
<link rel="stylesheet" href="{{ asset('css/cal-portal-master.css') }}">
{{-- Weekly timetable (#eventListView) on the design-system tokens (docs/design.md).
     Inline next to the page's own stylesheets rather than @push('styles'): the
     Officer Trainee layout (admin.layouts.timetable) renders no styles stack. --}}
<style>
    #eventListView { --tt-time-w: 6.5rem; --tt-day-min: 11rem; --tt-accent: rgba(var(--bs-primary-rgb, 0 67 132), .45); }

    /* Header */
    #eventListView .tt-header {
        background: var(--ds-surface);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow-sm);
        padding: var(--ds-space-3);
        margin-bottom: var(--ds-space-3);
    }
    #eventListView .tt-header-main {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: var(--ds-space-3);
    }
    #eventListView .tt-title-block { display: flex; align-items: center; gap: var(--ds-space-3); min-width: 0; }
    #eventListView .tt-title-icon {
        width: 2.75rem; height: 2.75rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: var(--ds-radius-card);
        background: rgba(var(--bs-primary-rgb, 0 67 132), .1);
        color: var(--ds-primary); font-size: 1.25rem;
    }
    #eventListView .tt-title { font-size: 1.25rem; font-weight: 600; color: var(--ds-ink); margin: 0; letter-spacing: -.01em; }
    #eventListView .tt-subtitle { font-size: .875rem; color: var(--ds-ink-muted); margin: var(--ds-space-1) 0 0; }
    #eventListView .tt-week-badge {
        font-size: .75rem; font-weight: 600; color: var(--ds-primary);
        background: rgba(var(--bs-primary-rgb, 0 67 132), .1);
        border-radius: var(--ds-radius); padding: .125rem var(--ds-space-2);
    }
    #eventListView .tt-weeknav .btn { border-radius: var(--ds-radius); min-height: var(--ds-control-h-sm); }
    #eventListView .tt-weeknav .btn + .btn { margin-left: var(--ds-space-1); }
    #eventListView .tt-actions {
        display: flex; flex-wrap: wrap; align-items: center; gap: var(--ds-space-2);
        margin-top: var(--ds-space-3); padding-top: var(--ds-space-3);
        border-top: 1px solid var(--ds-line);
    }
    #eventListView .tt-actions .btn { border-radius: var(--ds-radius); }
    #eventListView .tt-legend { display: inline-flex; flex-wrap: wrap; gap: var(--ds-space-3); font-size: .8125rem; color: var(--ds-ink-muted); }
    #eventListView .tt-legend-item { display: inline-flex; align-items: center; gap: var(--ds-space-1); }
    #eventListView .tt-swatch { width: .75rem; height: .75rem; border-radius: 2px; background: var(--tt-accent); }
    #eventListView .tt-swatch--a { background: var(--ds-primary); }
    #eventListView .tt-swatch--b { background: var(--ds-secondary); }
    #eventListView .tt-swatch--ab { background: linear-gradient(var(--ds-primary) 0 50%, var(--ds-secondary) 50% 100%); }
    #eventListView .tt-swatch--break { background: var(--bs-warning-border-subtle, #ffe69c); }

    /* Day navigation */
    #eventListView .tt-daynav { margin-bottom: var(--ds-space-3); }
    #eventListView .tt-daynav .row {
        display: flex; flex-wrap: nowrap; gap: var(--ds-space-2);
        margin: 0; overflow-x: auto; padding-bottom: var(--ds-space-1);
        scrollbar-width: thin;
    }
    #eventListView .tt-daynav .row > * { width: auto; margin: 0; }
    #eventListView .tt-day {
        flex: 1 0 7.5rem;
        display: grid; grid-template-columns: auto 1fr; grid-template-rows: auto auto;
        column-gap: var(--ds-space-2); align-items: center; text-align: left;
        padding: var(--ds-space-2) var(--ds-space-3);
        background: var(--ds-surface); color: var(--ds-ink);
        border: 1px solid var(--ds-line); border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow-sm);
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    #eventListView .tt-day:hover { border-color: rgba(var(--bs-primary-rgb, 0 67 132), .4); box-shadow: var(--ds-shadow); }
    #eventListView .tt-day:focus-visible { outline: 0; box-shadow: var(--ds-focus-ring); }
    #eventListView .tt-day-date { grid-row: 1 / span 2; font-size: 1.5rem; font-weight: 600; line-height: 1; }
    #eventListView .tt-day-name { grid-column: 2; font-size: .8125rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--ds-ink-muted); }
    #eventListView .tt-day-month { display: none; }
    #eventListView .tt-day-count { grid-column: 2; font-size: .75rem; color: var(--ds-ink-muted); }
    #eventListView .tt-day.is-today .tt-day-name::after { content: ' · Today'; color: var(--ds-secondary); }
    #eventListView .tt-day.is-active { background: var(--ds-primary); border-color: var(--ds-primary); color: #fff; }
    #eventListView .tt-day.is-active .tt-day-name,
    #eventListView .tt-day.is-active .tt-day-count,
    #eventListView .tt-day.is-active.is-today .tt-day-name::after { color: rgba(255, 255, 255, .85); }

    /* Grid */
    #eventListView .tt-grid-wrap {
        background: var(--ds-surface);
        border: 1px solid var(--ds-line); border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow-sm); overflow: hidden;
    }
    #eventListView .tt-scroll { max-height: 75vh; overflow: auto; margin: 0; }
    #eventListView .tt-scroll:focus-visible { outline: 0; box-shadow: inset var(--ds-focus-ring); }
    #eventListView .tt-grid {
        width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; table-layout: fixed;
        /* Not .table: the theme forces .table th to #787878 on #F3F3F3 (!important), below AA contrast. */
        min-width: calc(var(--tt-time-w) + 5 * var(--tt-day-min));
    }
    #eventListView .tt-grid thead th {
        position: sticky; top: 0; z-index: 3;
        background: var(--ds-surface-2); color: var(--ds-ink);
        border: 0; border-bottom: 1px solid var(--ds-line);
        padding: var(--ds-space-2) var(--ds-space-3);
        text-align: left; vertical-align: middle; font-weight: 600;
    }
    #eventListView .tt-grid thead th.time-column { width: var(--tt-time-w); left: 0; z-index: 4; color: var(--ds-ink-muted); font-size: .8125rem; }
    #eventListView .tt-grid .tt-th-day { display: block; font-size: .875rem; }
    #eventListView .tt-grid .tt-th-date { display: block; font-size: .75rem; font-weight: 500; color: var(--ds-ink-muted); }
    #eventListView .tt-grid thead th.is-today .tt-th-day { color: var(--ds-secondary); }
    #eventListView .tt-grid thead th.tt-col-active { box-shadow: inset 0 -3px 0 var(--ds-primary); }
    #eventListView .tt-grid tbody th.time-slot {
        position: sticky; left: 0; z-index: 2;
        width: var(--tt-time-w);
        background: var(--ds-surface); color: var(--ds-ink);
        border: 0; border-right: 1px solid var(--ds-line); border-bottom: 1px solid var(--ds-line);
        padding: var(--ds-space-2) var(--ds-space-3); vertical-align: top; font-weight: 600;
    }
    #eventListView .tt-time-start { display: block; font-size: .8125rem; white-space: nowrap; }
    #eventListView .tt-time-end { display: block; font-size: .75rem; font-weight: 500; color: var(--ds-ink-muted); white-space: nowrap; }
    #eventListView .tt-grid td.event-cell {
        border: 0; border-bottom: 1px solid var(--ds-line); border-right: 1px dashed var(--ds-line);
        padding: var(--ds-space-2); vertical-align: top;
        max-height: none; overflow: visible; background: transparent;
    }
    #eventListView .tt-grid td.event-cell:hover { background: transparent; }
    #eventListView .tt-grid td.event-cell.tt-col-active { background: rgba(var(--bs-primary-rgb, 0 67 132), .03); }
    #eventListView .tt-grid td.event-cell::before,
    #eventListView .tt-grid td.event-cell::after { display: none; }

    /* Break band */
    #eventListView .tt-break-row th.time-slot { background: var(--bs-warning-bg-subtle, #fff3cd); }
    #eventListView .tt-break-band {
        background: var(--bs-warning-bg-subtle, #fff3cd);
        color: var(--bs-warning-text-emphasis, #664d03);
        border: 0; border-bottom: 1px solid var(--bs-warning-border-subtle, #ffe69c);
        padding: var(--ds-space-2) var(--ds-space-3);
        font-weight: 600; font-size: .875rem; vertical-align: middle;
    }
    #eventListView .tt-break-band i { margin-right: var(--ds-space-2); }
    #eventListView .tt-break-time { margin-left: var(--ds-space-2); font-weight: 500; opacity: .85; }

    /* Session card */
    #eventListView .tt-card {
        position: relative;
        background: var(--ds-surface);
        border: 1px solid var(--ds-line); border-left: 3px solid var(--tt-accent);
        border-radius: var(--ds-radius);
        padding: var(--ds-space-2);
        transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease;
    }
    #eventListView .tt-card + .tt-card { margin-top: var(--ds-space-2); }
    #eventListView .tt-card[role="button"] { cursor: pointer; }
    #eventListView .tt-card[role="button"]:hover { box-shadow: var(--ds-shadow); transform: translateY(-1px); }
    #eventListView .tt-card:focus-visible { outline: 0; box-shadow: var(--ds-focus-ring); }
    #eventListView .tt-card--group-a { border-left-color: var(--ds-primary); background: rgba(var(--bs-primary-rgb, 0 67 132), .04); }
    #eventListView .tt-card--group-b { border-left-color: var(--ds-secondary); background: rgba(177, 41, 35, .04); }
    #eventListView .tt-card--break {
        border-color: var(--bs-warning-border-subtle, #ffe69c);
        border-left-color: var(--bs-warning-border-subtle, #ffe69c);
        background: var(--bs-warning-bg-subtle, #fff3cd);
    }
    #eventListView .tt-card-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--ds-space-1); margin-bottom: var(--ds-space-1); }
    #eventListView .tt-card-time { font-size: .75rem; font-weight: 600; color: var(--ds-ink-muted); white-space: nowrap; }
    #eventListView .tt-card-time i { margin-right: .25rem; }
    #eventListView .tt-card-group {
        font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap;
        padding: 0 .375rem; border-radius: var(--ds-radius);
        background: rgba(var(--bs-primary-rgb, 0 67 132), .1); color: var(--ds-primary);
    }
    #eventListView .tt-card--group-b .tt-card-group { background: rgba(177, 41, 35, .1); color: var(--ds-secondary); }
    /* Both groups attend: a split accent - Group A over Group B. */
    #eventListView .tt-card--group-ab {
        border-left-color: transparent;
        background:
            linear-gradient(var(--ds-primary) 0 50%, var(--ds-secondary) 50% 100%) left / 3px 100% no-repeat,
            var(--ds-surface);
    }
    #eventListView .tt-card-title { font-size: .875rem; font-weight: 600; line-height: 1.35; color: var(--ds-ink); overflow-wrap: anywhere; }
    #eventListView .tt-card-meta,
    #eventListView .tt-card-venue {
        display: flex; align-items: flex-start; gap: .375rem;
        margin-top: var(--ds-space-1); font-size: .8125rem; line-height: 1.35; color: var(--ds-ink-muted);
        overflow-wrap: anywhere;
    }
    #eventListView .tt-card-venue span {
        background: var(--ds-surface-2); border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius); padding: 0 .375rem; color: var(--ds-ink);
    }
    #eventListView .tt-card-meta i, #eventListView .tt-card-venue i { margin-top: .1rem; }

    #eventListView .tt-empty-cell { border: 0; padding: var(--ds-space-6) var(--ds-space-3); }
    #eventListView .tt-empty { text-align: center; color: var(--ds-ink-muted); }
    #eventListView .tt-empty i { display: block; font-size: 2rem; margin-bottom: var(--ds-space-2); }

    /* Tablet: the grid scrolls sideways under a pinned time column. */
    @media (max-width: 991.98px) {
        #eventListView { --tt-time-w: 5.5rem; --tt-day-min: 10rem; }
    }

    /* Phone: one day at a time, picked from the day chips. */
    @media (max-width: 767.98px) {
        #eventListView .tt-header { padding: var(--ds-space-2) var(--ds-space-3); }
        #eventListView .tt-weeknav { width: 100%; }
        #eventListView .tt-weeknav .btn { flex: 1; }
        #eventListView .tt-actions .btn span { display: none; }
        #eventListView .tt-legend { display: none; }
        #eventListView .tt-day { flex: 0 0 5.5rem; grid-template-columns: 1fr; text-align: center; padding: var(--ds-space-2); }
        #eventListView .tt-day-date { grid-row: auto; }
        #eventListView .tt-day-name, #eventListView .tt-day-count { grid-column: 1; }
        #eventListView .tt-day.is-today .tt-day-name::after { content: ''; }
        /* One day: each row becomes a two-column grid (time | sessions). */
        #eventListView .tt-grid { min-width: 0; table-layout: auto; }
        #eventListView .tt-grid, #eventListView .tt-grid thead, #eventListView .tt-grid tbody { display: block; }
        #eventListView .tt-grid tr { display: grid; grid-template-columns: var(--tt-time-w) minmax(0, 1fr); }
        #eventListView .tt-grid thead th, #eventListView .tt-grid tbody th.time-slot { position: static; width: auto; }
        #eventListView .tt-grid[data-active-day] thead th[data-day]:not(.tt-col-active),
        #eventListView .tt-grid[data-active-day] td.event-cell:not(.tt-col-active),
        #eventListView .tt-grid[data-active-day] tr.tt-row-empty-day { display: none; }
        #eventListView .tt-grid td.event-cell, #eventListView .tt-break-band { display: block; border-right: 0; }
        #eventListView .tt-grid td.event-cell.tt-col-active { background: transparent; }
        #eventListView .tt-grid thead th.tt-col-active { box-shadow: none; }
        #eventListView .tt-scroll { max-height: none; overflow: visible; }
    }

    @media (prefers-reduced-motion: reduce) {
        #eventListView .tt-card, #eventListView .tt-day { transition: none; }
        #eventListView .tt-card[role="button"]:hover { transform: none; }
    }
</style>

<div class="container-fluid calendar-admin-page cal-master-page">
    @if(!isset($courseMaster) || $courseMaster->isEmpty())
        <div class="alert alert-warning mb-3 mt-3">
            <h4 class="h6 mb-1"><i class="bi bi-exclamation-triangle me-2"></i>No Courses Available</h4>
            <p class="mb-0 small">No active courses found. Please contact the administrator.</p>
        </div>
    @endif
    <x-breadcrum title="Calendar Creation">
   @if(hasRole('Training') || hasRole('Super Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST') || hasRole('Training-Induction'))
        <a id="createEventButton"
                href="{{ route('calendar.event.create') }}"
            class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Event</span>
        </a>
        @endif
    </x-breadcrum>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    {{-- Active / Archived courses (same split as Course Master) --}}
    <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group" aria-label="Filter courses by status">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                id="calFilterActive" data-cal-status="active" aria-pressed="true" aria-current="true">Active</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                id="calFilterArchive" data-cal-status="archive" aria-pressed="false">Archived</button>
        </li>
    </ul>

    <a href="#" id="btnTimetablePdf" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap" style="background-color: #fff; border:0;color:#004a93;" title="Download the visible timetable as a PDF">
        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">download</i>
        <span>Download</span>
    </a>

</div>
    <div class="course-header cal-course-context d-none" aria-live="polite">
        <h1>{{ $courseMaster->first()->course_name ?? 'Course Name' }}</h1>
        <p class="mb-0 text-secondary small">
            <span class="badge rounded-1">{{ $courseMaster->first()->couse_short_name ?? 'Course Code' }}</span>
            <span class="mx-1 text-muted" aria-hidden="true">|</span>
            <strong class="text-body-secondary">Year:</strong> {{ $courseMaster->first()->course_year ?? date('Y') }}
        </p>
    </div>

    <main id="main-content" role="main">
        <section class="calendar-container" aria-label="Academic calendar">
            <div class="card cal-portal-card border-0 shadow-sm rounded-3">
                <div class="card-body position-relative p-4">
                    <h2 id="controlPanelHeading" class="visually-hidden">Calendar filters and navigation</h2>

                    {{-- Reference: Filters | Course Name | Reset Filters — left; month nav + view toggles — right --}}
                    <div class="cal-portal-toolbar-row programme-dt-toolbar d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-between gap-3 mb-4 w-100">
                        <div class="d-flex flex-wrap align-items-center gap-3 cal-filters-group">
                            <span class="programme-dt-filters-label mb-0">Filters</span>
                            <div class="programme-dt-filter-select" id="courseFilterWrap">
                                <label for="courseFilter" class="visually-hidden">Course Name</label>
                                <select
                                    class="form-select cal-filter-select cal-filter-empty"
                                    id="courseFilter"
                                    name="course_id"
                                    aria-label="Course name"
                                >
                                    <option value="">Course Name</option>
                                    @foreach($courseMaster as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-secondary cal-filter-reset ms-2 " id="btnResetCalendarFilters">
                                Reset Filters
                            </button>
                        </div>

                        <div id="calPortalToolbar" class="cal-toolbar-nav d-flex flex-wrap align-items-center justify-content-xl-end gap-3 ms-xl-auto" aria-label="Calendar navigation">
                            <div class="cal-portal-nav-cluster d-flex align-items-center gap-1">
                                <button type="button" class="cal-portal-nav-btn" id="calPortalPrev" aria-label="Previous period">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">chevron_left</i>
                                </button>
                                <h2 class="cal-portal-title mb-0" id="calPortalTitle" aria-live="polite"></h2>
                                <button type="button" class="cal-portal-nav-btn" id="calPortalNext" aria-label="Next period">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">chevron_right</i>
                                </button>
                            </div>
                            <div class="btn-group cal-view-switch" role="group" aria-label="Calendar view mode">
                                <button type="button" class="btn" data-view="week" aria-pressed="false" title="Week schedule view">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">list_alt</i>
                                    <span class="visually-hidden">Week schedule</span>
                                </button>
                                <button type="button" class="btn active" data-view="month" aria-pressed="true" title="Month calendar view">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">cards</i>
                                    <span class="visually-hidden">Month calendar</span>
                                </button>
                            </div>
                            <div class="dropdown cal-toolbar-more">
                                <button type="button" class="btn cal-toolbar-more-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More calendar options">
                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">more_vert</i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-2 py-1">
                                    <li>
                                        <button type="button" class="dropdown-item py-2" id="btnTimetableListView" data-view="list">
                                            <i class="material-icons material-symbols-rounded me-2" aria-hidden="true">table_view</i>Weekly Timetable
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item py-2" id="toggleDensityBtn" aria-pressed="false">
                                            <i class="material-icons material-symbols-rounded me-2" aria-hidden="true">density_medium</i>Compact View
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading overlay -->
                    <div id="calendarLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-90 rounded-2" style="min-height: 400px; z-index: 50;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading calendar...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading calendar...</p>
                        </div>
                    </div>
                    
                    <script>
                        // IMMEDIATE fallback - hide loader after 3 seconds
                        (function() {
                            console.log('Inline script: Setting up emergency timeout');
                            setTimeout(function() {
                                var overlay = document.getElementById('calendarLoadingOverlay');
                                if (overlay) {
                                    console.log('EMERGENCY TIMEOUT: Hiding loader');
                                    overlay.style.display = 'none';
                                } else {
                                    console.error('Overlay element not found in timeout');
                                }
                            }, 3000);
                        })();
                    </script>

                    <!-- FullCalendar placeholder (you may initialize FullCalendar separately) -->
                    <div id="calendar" class="fc mb-4" role="application" aria-label="Interactive calendar"></div>

                    <!-- List View -->
                    <div id="eventListView" class="mt-4 d-none" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper tt">
                            {{-- Header: title, week, navigation and the week's exports --}}
                            <header class="tt-header">
                                <div class="tt-header-main">
                                    <div class="tt-title-block">
                                        <span class="tt-title-icon" aria-hidden="true"><i class="bi bi-calendar3-week"></i></span>
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <h1 class="tt-title">Weekly Timetable</h1>
                                                <span class="tt-week-badge">Week <span id="currentWeekNumber">—</span></span>
                                            </div>
                                            <p class="tt-subtitle" id="weekRangeText" aria-live="polite">
                                                <i class="bi bi-calendar-week me-2" aria-hidden="true"></i>—
                                            </p>
                                        </div>
                                    </div>
                                    <div class="btn-group tt-weeknav" role="group" aria-label="Week navigation">
                                        <button type="button" class="btn btn-outline-primary" id="prevWeekBtn" aria-label="Previous week">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary" id="currentWeekBtn" aria-label="Current week">
                                            <i class="bi bi-calendar-check me-1"></i>Today
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="nextWeekBtn" aria-label="Next week">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Whole-week timetable: download / print PDF, info sheet --}}
                                <div class="tt-actions" role="group" aria-label="Timetable export">
                                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="btnWeekTimetablePdf" title="Download the whole week as a PDF">
                                        <i class="bi bi-download"></i><span>Download</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="btnWeekTimetablePrint" title="Print the whole week timetable">
                                        <i class="bi bi-printer"></i><span>Print</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnWeekInfoPdf" title="Course information & faculty for the week (PDF)">
                                        <i class="bi bi-people"></i><span>Info Sheet</span>
                                    </button>
                                    {{-- The roles CalendarController::canEditWeeklyInfo() admits, plus anyone
                                         coordinating a course - the controller decides per course. The
                                         editor modal below is included on the same condition. --}}
                                    @php
                                        $canEditInfoSheet = hasRole('Training') || hasRole('Super Admin') || hasRole('Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST') || hasRole('Training-Induction')
                                            || \App\Models\CourseCordinatorMaster::courseIdsForUser();
                                    @endphp
                                    @if($canEditInfoSheet)
                                        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnEditWeekInfo" title="Edit this week's venues line, notes and P.T.O. page">
                                            <i class="bi bi-pencil-square"></i><span>Edit Info Sheet</span>
                                        </button>
                                    @endif
                                    <span class="tt-legend ms-lg-auto" aria-label="Legend">
                                        <span class="tt-legend-item"><span class="tt-swatch tt-swatch--session"></span>Session</span>
                                        <span class="tt-legend-item"><span class="tt-swatch tt-swatch--a"></span>Group A</span>
                                        <span class="tt-legend-item"><span class="tt-swatch tt-swatch--b"></span>Group B</span>
                                        <span class="tt-legend-item"><span class="tt-swatch tt-swatch--ab"></span>Groups A &amp; B</span>
                                        <span class="tt-legend-item"><span class="tt-swatch tt-swatch--break"></span>Break</span>
                                    </span>
                                </div>
                            </header>

                            {{-- Day navigation: one chip per day (date + sessions). On a phone it picks the day shown. --}}
                            <nav id="weekCards" class="tt-daynav" aria-labelledby="weekCardsTitle">
                                <h2 id="weekCardsTitle" class="visually-hidden">Days of the week</h2>
                                <div class="row" role="tablist" aria-label="Days of the week">
                                    <!-- JS renders the day chips here -->
                                </div>
                            </nav>

                            <!-- Timetable grid -->
                            <div class="timetable-container tt-grid-wrap">
                                <div class="table-responsive tt-scroll" role="region" aria-label="Weekly timetable" tabindex="0">
                                    <table class="timetable-grid tt-grid" id="timetableTable"
                                        aria-describedby="timetableDescription">
                                        <caption class="visually-hidden" id="timetableDescription">
                                            Weekly academic timetable showing events
                                        </caption>
                                        <thead id="timetableHead">
                                            <tr>
                                                <th scope="col" class="time-column">Time</th>
                                                <th scope="col">Monday</th>
                                                <th scope="col">Tuesday</th>
                                                <th scope="col">Wednesday</th>
                                                <th scope="col">Thursday</th>
                                                <th scope="col">Friday</th>
                                                <th scope="col">Saturday</th>
                                                <th scope="col">Sunday</th>
                                            </tr>
                                        </thead>

                                        <tbody id="timetableBody">
                                            <!-- JS will populate body -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </main>
</div>

@include('admin.calendar.partials.add_edit_events')
@include('admin.calendar.partials.events_details')
@include('admin.calendar.partials.event_hover_card')
@include('admin.calendar.partials.confirmation')
@if($canEditInfoSheet ?? false)
@include('admin.calendar.partials.weekly_info_editor')
@endif

  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- Modern JavaScript with improved accessibility -->
@php
    $calendarRequiresCourses = !is_faculty_portal_user()
        && !hasRole('Student-OT')
        && !hasRole('Admin')
        && !hasRole('Super Admin');
@endphp
<script>
console.log('FullCalendar loaded:', typeof FullCalendar !== 'undefined');

// Configuration object
const CalendarConfig = {
    api: {
        events: "{{ route('calendar.event.calendar-details') }}",
        eventDetails: "{{ route('calendar.event.Singlecalendar-details') }}",
        store: "{{ route('calendar.event.store') }}",
        edit: "{{ route('calendar.event.show', ['id' => 'EVENT_ID']) }}",
        update: "{{ route('calendar.event.update', ['hash' => 'EVENT_ID']) }}",
        delete: "{{ route('calendar.event.delete', ['id' => 'EVENT_ID']) }}",
        groupTypes: "{{ route('calendar.get.group.types') }}",
        subjectNames: "{{ route('calendar.get.subject.name') }}",
        eventCard: "{{ route('calendar.event.card', ['id' => 'EVENT_ID']) }}",
        timetablePdf: "{{ route('calendar.timetable.pdf') }}",
        timetablePreview: "{{ route('calendar.timetable.preview') }}",
        weeklyTimetablePdf: "{{ route('calendar.weekly-timetable.pdf') }}",
        weeklyTimetablePreview: "{{ route('calendar.weekly-timetable.preview') }}",
        weeklyInfoPdf: "{{ route('calendar.weekly-info.pdf') }}",
        weeklyInfoMeta: "{{ route('calendar.weekly-info.meta') }}",
        weeklyInfoSave: "{{ route('calendar.weekly-info.save') }}"
    },
    colors: [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
        '#e74a3b', '#858796', '#5a5c69', '#fd7e14',
        '#20c997', '#6f42c1'
    ],
    // Consistent colors per event type (fallbacks to colors list)
    eventTypeColors: {
        lecture: '#4e73df',
        exam: '#e74a3b',
        meeting: '#1cc88a',
        workshop: '#f6c23e',
        seminar: '#6f42c1',
        training: '#20c997'
    },
    minDate: new Date().toISOString().split('T')[0],
    // Expand visible timetable window to cover typical sessions
    minTime: '09:00',
    maxTime: '17:30'
};

function syncCalCourseFilterState() {
    const select = document.getElementById('courseFilter');
    if (!select) return;
    const empty = !select.value;
    select.classList.toggle('cal-filter-empty', empty);
    select.classList.toggle('filter-placeholder', empty);
}

function initCourseFilter() {
    const select = document.getElementById('courseFilter');
    if (!select) return;
    syncCalCourseFilterState();
    select.addEventListener('change', syncCalCourseFilterState);
}

/**
 * Active / Archived course tabs (same split as Course Master): Active =
 * running courses, Archived = courses that have already ended. Switching the
 * tab repopulates the course dropdown from the matching list and reloads the
 * calendar for the newly-selected scope.
 */
function initCalendarStatusTabs() {
    const activeCourses = @json($courseMaster ?? []);
    const archivedCourses = @json($archivedCourseMaster ?? []);
    const select = document.getElementById('courseFilter');
    const pills = document.querySelectorAll('.programme-status-tabs [data-cal-status]');
    if (!select || !pills.length) return;

    function repopulate(list) {
        if (list && list.length) {
            select.innerHTML = '<option value="">Course Name</option>' +
                list.map(function (c) {
                    return '<option value="' + c.pk + '">' + (c.course_name || '') + '</option>';
                }).join('');
        } else {
            select.innerHTML = '<option value="">No courses in this view</option>';
        }
        select.value = '';

        // Keep the calendar manager's course list in sync so the course header
        // resolves the right course, then clear the selection and refetch.
        if (window.calendarManager) {
            window.calendarManager.courses = list || [];
            window.calendarManager.selectedCourseId = null;
        }
        syncCalCourseFilterState();
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            if (this.classList.contains('active')) return;
            pills.forEach(function (p) {
                p.classList.remove('active');
                p.setAttribute('aria-pressed', 'false');
                p.removeAttribute('aria-current');
            });
            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');
            this.setAttribute('aria-current', 'true');
            repopulate(this.dataset.calStatus === 'archive' ? archivedCourses : activeCourses);
        });
    });
}

/** Blur course filter while Add/Edit Event modal is open */
function closeCourseFilterDropdown() {
    const select = document.getElementById('courseFilter');
    if (select && typeof select.blur === 'function') {
        select.blur();
    }
    if (document.activeElement && document.activeElement.closest('#courseFilterWrap')) {
        document.activeElement.blur();
    }
}

function releaseCourseFilterDropdownSuppression() {
    /* native select — no suppression state */
}

// Calendar Manager Class
class CalendarManager {
    constructor() {
        this.calendar = null;
        this.currentEventId = null;
        this.selectedGroupNames = 'ALL';
        this.listViewWeekOffset = 0; // Track week offset for list view
        this.selectedCourseId = null;
        this.courses = @json($courseMaster);
        this.calendarRequiresCourses = @json($calendarRequiresCourses);
        // Weekend columns of the week grid; recomputed from each week's events
        this.weekendDisplay = { showSat: false, showSun: false };
        this.hiddenDaysTimer = null;
        this.eventDetailsCache = new Map();
        this.hoverShowTimer = null;
        this.hoverHideTimer = null;
        this.hoverAnchorEl = null;
        this.init();
    }

    init() {
        try {
            console.log('Initializing calendar manager...');

            if (this.calendarRequiresCourses && (!this.courses || this.courses.length === 0)) {
                console.log('No courses available for this admin — skipping calendar load');
                const loadingOverlay = document.getElementById('calendarLoadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
                return;
            }

            this.initFullCalendar();
            
            try { this.bindEvents(); } catch (e) { console.error('bindEvents error:', e); }
            try { this.setupAccessibility(); } catch (e) { console.error('setupAccessibility error:', e); }
            try { this.validateDates(); } catch (e) { console.error('validateDates error:', e); }
            try { this.updateCurrentWeek(); } catch (e) { console.error('updateCurrentWeek error:', e); }
            try { this.observeMoreLinksChanges(); } catch (e) { console.error('observeMoreLinksChanges error:', e); }
            try { this.initDensity(); } catch (e) { console.error('initDensity error:', e); }
            try { this.initEventHoverCard(); } catch (e) { console.error('initEventHoverCard error:', e); }
            
            console.log('Calendar manager initialized successfully');
        } catch (error) {
            console.error('Error in init():', error);
            // Hide loader on error
            const loadingOverlay = document.getElementById('calendarLoadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.innerHTML = `
                    <div class="text-center">
                        <div class="text-danger mb-3">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-danger">Calendar Initialization Error</h5>
                        <p class="text-muted">${error.message}</p>
                        <button class="btn btn-primary mt-3" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reload Page
                        </button>
                    </div>
                `;
            }
        }
    }

    initFullCalendar() {
        console.log('Starting initFullCalendar...');
        const calendarEl = document.getElementById('calendar');
        const loadingOverlay = document.getElementById('calendarLoadingOverlay');
        
        if (!calendarEl) {
            throw new Error('Calendar element not found');
        }
        
        console.log('Calendar element found:', calendarEl);
        
        // Get initial course ID from filter dropdown
        const courseFilter = document.getElementById('courseFilter');
        this.selectedCourseId = courseFilter && courseFilter.value ? courseFilter.value : null;
        
        console.log('Selected course ID:', this.selectedCourseId);
        
        // Update course header with initial selection
        // this.updateCourseHeader();

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            hiddenDays: [0, 6], // Initially hide Sunday (0) and Saturday (6)
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day'
            },
            allDaySlot: true,
            slotMinTime: CalendarConfig.minTime,
            slotMaxTime: CalendarConfig.maxTime,
            slotDuration: '00:30:00',
            snapDuration: '00:30:00',
            slotLabelInterval: '01:00:00',
            slotLabelFormat: {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: true,
                meridiem: 'short',
                hour12: true
            },
            height: 'auto',
            contentHeight: 'auto',
            editable: true,
            selectable: true,
            dayMaxEvents: false,
            moreLinkClick: 'popover',
            eventOrder: 'start,title',
            displayEventTime: true,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            views: {
                dayGridMonth: {
                    dayMaxEvents: 2, // Show max 2 events, then +x more
                    displayEventEnd: true
                },
                timeGridWeek: {
                    dayMaxEvents: false,
                    eventMaxStack: 8,
                    allDaySlot: true,
                    slotEventOverlap: false,
                    dayHeaderFormat: { weekday: 'short', day: '2-digit', omitCommas: true }
                },
                timeGridDay: {
                    dayMaxEvents: false,
                    eventMaxStack: 8
                }
            },
            events: (info, successCallback, failureCallback) => {
                this.fetchEvents(info, successCallback, failureCallback);
            },
            loading: (isLoading) => {
                console.log('Calendar loading state:', isLoading);
                const loadingOverlay = document.getElementById('calendarLoadingOverlay');
                
                if (!isLoading) {
                    // Events have finished loading
                    console.log('Events loaded, hiding overlay');
                    
                    try {
                        this.updateWeekendVisibility();
                    } catch (error) {
                        console.error('Error updating weekend visibility:', error);
                    }
                    
                    // Hide loading overlay
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                } else {
                    console.log('Loading events...');
                }
            },
            eventContent: this.renderEventContent.bind(this),
            eventClick: this.handleEventClick.bind(this),
            eventMouseEnter: this.handleEventMouseEnter.bind(this),
            eventMouseLeave: this.handleEventMouseLeave.bind(this),
            select: this.handleDateSelect.bind(this),
            eventDidMount: this.onEventMount.bind(this),
            dayCellDidMount: this.setDayCellAccessibility.bind(this),
            datesSet: () => {
                this.updatePortalToolbarTitle();
                this.syncPortalViewButtons();
                this.styleMoreLinks();
                try {
                    this.updateWeekendVisibility();
                } catch (error) {
                    console.error('Error updating weekend visibility:', error);
                }
            }
        });

        this.calendar.render();
        console.log('Calendar rendered');

        this.initPortalToolbar();
        this.updatePortalToolbarTitle();
        this.syncPortalViewButtons();
        this.styleMoreLinks();
        this.applyDenseMode();
        
        // Fallback: Hide loading overlay after calendar renders (in case loading callback doesn't fire)
        setTimeout(() => {
            const loadingOverlay = document.getElementById('calendarLoadingOverlay');
            if (loadingOverlay) {
                console.log('Timeout fallback: hiding loading overlay');
                loadingOverlay.style.display = 'none';
            }
        }, 2000); // Give calendar 2 seconds to load
    }

    /**
     * Ensure week/time-grid views receive timed events (not all-day row).
     */
    normalizeEventForTimeGrid(event) {
        if (event.allDay === true || event.full_day == 1) {
            return event;
        }

        const fixedStart = this.fixCalendarDateTimeString(event.start);
        const fixedEnd = event.end ? this.fixCalendarDateTimeString(event.end) : event.end;

        if (fixedStart && fixedStart.includes('T') && event.allDay === false) {
            return { ...event, start: fixedStart, end: fixedEnd || fixedStart, allDay: false };
        }

        const session = event.class_session_debug || event.class_session || '';
        const dateStr = this.extractEventDateYmd(event.start);
        const times = this.parseClassSessionTimeRange(session);

        if (!times || !dateStr) {
            return event;
        }

        let endDateStr = this.extractEventDateYmd(event.end) || dateStr;
        let endIso = `${endDateStr}T${times.end}`;

        if (times.end <= times.start && endDateStr === dateStr) {
            const next = new Date(`${dateStr}T12:00:00`);
            next.setDate(next.getDate() + 1);
            endDateStr = next.toISOString().slice(0, 10);
            endIso = `${endDateStr}T${times.end}`;
        }

        return {
            ...event,
            start: `${dateStr}T${times.start}`,
            end: endIso,
            allDay: false,
        };
    }

    fixCalendarDateTimeString(value) {
        if (!value) return value;
        const raw = String(value).trim();
        const broken = raw.match(/^(\d{4}-\d{2}-\d{2})\s+[\d:]+\s*T(\d{2}:\d{2}(?::\d{2})?)/);
        if (broken) {
            return `${broken[1]}T${broken[2].length === 5 ? broken[2] + ':00' : broken[2]}`;
        }
        const dateOnly = raw.match(/^(\d{4}-\d{2}-\d{2})$/);
        if (dateOnly) {
            return dateOnly[1];
        }
        const iso = raw.match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}(?::\d{2})?)/);
        if (iso) {
            return `${iso[1]}T${iso[2].length === 5 ? iso[2] + ':00' : iso[2]}`;
        }
        return raw;
    }

    extractEventDateYmd(start) {
        if (!start) return null;
        const fixed = this.fixCalendarDateTimeString(start);
        const match = String(fixed).match(/^(\d{4}-\d{2}-\d{2})/);
        return match ? match[1] : null;
    }

    parseClassSessionTimeRange(session) {
        if (!session || !/[-–—]/.test(String(session))) {
            return null;
        }
        const parts = String(session).trim().split(/\s*[-–—]\s*/);
        if (parts.length < 2) {
            return null;
        }
        const start = this.parseTimePartTo24(parts[0]);
        const end = this.parseTimePartTo24(parts[1]);
        if (!start || !end) {
            return null;
        }
        return {
            start: start.length === 5 ? `${start}:00` : start,
            end: end.length === 5 ? `${end}:00` : end,
        };
    }

    parseTimePartTo24(timeStr) {
        const trimmed = String(timeStr).trim();
        if (/^\d{1,2}:\d{2}$/.test(trimmed)) {
            const [h, m] = trimmed.split(':');
            return `${String(parseInt(h, 10)).padStart(2, '0')}:${m}`;
        }
        if (/^\d{1,2}:\d{2}:\d{2}$/.test(trimmed)) {
            return trimmed;
        }
        const converted = this.convertTo24Hour(trimmed);
        return converted || null;
    }

    fetchEvents(info, successCallback, failureCallback) {
        if (this.calendarRequiresCourses && (!this.courses || this.courses.length === 0)) {
            successCallback([]);
            return;
        }

        // Build URL with course filter
        let url = CalendarConfig.api.events;
        const params = new URLSearchParams();
        
        // LOCAL date strings (toYmd), never toISOString(). At a positive UTC offset - IST
        // is +05:30 - toISOString() rolls local midnight back to the PREVIOUS day, so the
        // feed was requested a day early. That was inert while hiddenDays was fixed at
        // [0,6], but the weekend columns are now decided from this feed
        // (revealWeekendsForData below), so a session on the day BEFORE the view opened a
        // weekend column INSIDE it - e.g. the week of Mon 2026-06-29, which has no weekend
        // session, reached back into the Sunday 2026-06-28 class and showed Sat+Sun.
        // F-021: info.start/info.end arrive ALREADY TRIMMED by hiddenDays, and this feed is
        // what decides hiddenDays (revealWeekendsForData below) - a closed loop. feedRange()
        // undoes the trim. It returns null only when there is no usable range at all, and
        // the range we were given is then used unchanged.
        const feedRange = this.feedRange(info);

        if (info.start) {
            params.append('start', this.toYmd(feedRange ? feedRange.start : info.start));
        }
        if (info.end) {
            // info.end is EXCLUSIVE in FullCalendar, so step back to the last day the user
            // can actually see. toISOString() did this by accident at a positive offset and
            // not at all at a negative one, where it asked for a day beyond the view;
            // doing it explicitly is correct at both. Same shape as openTimetablePdf().
            // feedRange.end has already had that conversion applied.
            const lastVisibleDay = new Date(info.end);
            lastVisibleDay.setDate(lastVisibleDay.getDate() - 1);
            params.append('end', this.toYmd(feedRange ? feedRange.end : lastVisibleDay));
        }
        if (this.selectedCourseId) {
            params.append('course_id', this.selectedCourseId);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }

        console.log('Fetching events from:', url);

        fetch(url, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Events loaded:', data.length);
            // Filter out holidays and restricted holidays
            const filteredData = data.filter(event => {
                const type = (event.type || event.event_type || event.session_type || '').toString().toLowerCase();
                return type !== 'holiday' && type !== 'restricted holiday' && type !== 'restricted' && !type.includes('holiday');
            });
            const normalized = filteredData.map(event => this.normalizeEventForTimeGrid(event));
            console.log('Events after filtering:', normalized.length);
            successCallback(normalized);
            // Decide the weekend columns from the feed we just received:
            // getEvents() is not populated yet at this point.
            this.revealWeekendsForData(filteredData);
        })
        .catch(error => {
            console.error('Error fetching events:', error);
            this.showNotification('Failed to load calendar events. Please refresh the page.', 'danger');
            failureCallback(error);
        });
    }

    /**
     * The weekend rule for this calendar:
     *   - an event on Sunday   -> show Saturday AND Sunday (never a gap after Friday)
     *   - an event on Saturday only -> show Saturday, keep Sunday hidden
     *   - nothing on either    -> Mon-Fri only
     */
    resolveWeekendDisplay(hasSat, hasSun) {
        return { showSat: hasSat || hasSun, showSun: hasSun };
    }

    /** A holiday entry is not a class, so it never opens a weekend column. */
    isHolidayEvent(event) {
        const src = (event && event.extendedProps) ? event.extendedProps : (event || {});
        const type = (src.type || src.event_type || src.session_type || '').toString().toLowerCase();
        return type.includes('holiday');
    }

    /**
     * Local weekday (0 = Sunday .. 6 = Saturday) for a feed row or a FullCalendar event.
     * The feed sends all-day rows as a bare "YYYY-MM-DD", which the Date constructor reads
     * as UTC midnight — that lands on the previous day at any negative UTC offset, and
     * disagrees with FullCalendar, which builds the same event at LOCAL midnight. Parsing
     * the parts explicitly keeps both paths on the same weekday in every timezone.
     */
    eventLocalDate(event) {
        const start = event && event.start;
        if (!start) return null;

        if (start instanceof Date) {
            return new Date(start.getFullYear(), start.getMonth(), start.getDate());
        }

        const ymd = this.extractEventDateYmd(start);
        if (ymd) {
            const [y, m, d] = ymd.split('-').map(Number);
            return new Date(y, m - 1, d);
        }

        const parsed = new Date(start);
        return isNaN(parsed)
            ? null
            : new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
    }

    /** Local weekday (0 = Sunday .. 6 = Saturday), or NaN when the row has no usable date. */
    eventWeekday(event) {
        const date = this.eventLocalDate(event);
        return date ? date.getDay() : NaN;
    }

    /**
     * True when the row carries no time of day. The feed sends all-day rows as a bare
     * "YYYY-MM-DD" and timed rows as "YYYY-MM-DDTHH:MM:SS", so the presence of a time
     * component is what distinguishes them.
     */
    isAllDayEvent(event) {
        if (!event) return false;
        if (event.allDay === true) return true;
        if (event.allDay === false) return false;
        if (event.full_day == 1) return true;

        const start = event.start;
        if (!start || start instanceof Date) return false;
        const fixed = this.fixCalendarDateTimeString(start);
        return typeof fixed === 'string' && !fixed.includes('T');
    }

    /** { showSat, showSun } for a list of events (raw feed rows or FullCalendar events). */
    weekendDisplayForEvents(events) {
        const rows = (events || []).filter(e => e && e.start && !this.isHolidayEvent(e));
        const hasSat = rows.some(e => this.eventWeekday(e) === 6); // 6 = Saturday
        const hasSun = rows.some(e => this.eventWeekday(e) === 0); // 0 = Sunday
        return this.resolveWeekendDisplay(hasSat, hasSun);
    }

    /**
     * { showSat, showSun } counting EVERY row, holidays included.
     * A holiday must not OPEN a weekend column, but it must never be silently swallowed
     * either: the renderers only emit cells for visible days, so any day carrying a row
     * has to stay visible or that row disappears with its column.
     */
    weekendPresenceForEvents(events) {
        const rows = (events || []).filter(e => e && e.start);
        const hasSat = rows.some(e => this.eventWeekday(e) === 6);
        const hasSun = rows.some(e => this.eventWeekday(e) === 0);
        return this.resolveWeekendDisplay(hasSat, hasSun);
    }

    /**
     * The rule as the LIST VIEW must apply it: every row's day stays visible.
     *
     * This is weekendPresenceForEvents() and nothing more, and that is the point. Unioning
     * it with weekendDisplayForEvents() - as this function used to - cannot change the
     * answer for any input: the rule reads a subset of the rows presence reads, and
     * resolveWeekendDisplay() is monotone in both arguments, so the rule can never open a
     * column presence leaves shut. Checked exhaustively over all 27 feeds of
     * {absent, class, holiday} on Fri/Sat/Sun: the holiday exclusion changed the answer in
     * none of them. A union that no input can distinguish from one of its operands is not a
     * rule, it is decoration that reads like one.
     *
     * So the headline rule "a holiday never OPENS a weekend column" governs the FullCalendar
     * grid - revealWeekendsForData() and updateWeekendVisibility(), which are fed the
     * holiday-filtered feed from fetchEvents() - and deliberately NOT this path. It cannot
     * govern this path: the renderers emit cells only for visible days, so a Saturday
     * carrying only a holiday has to keep its column or the holiday goes with it.
     */
    weekendDisplayForRendering(events) {
        return this.weekendPresenceForEvents(events);
    }

    /** Push the weekend rule into FullCalendar's hiddenDays, only when it changes. */
    applyHiddenDays(display) {
        if (!this.calendar) return;
        const hidden = [];
        if (!display.showSun) hidden.push(0);
        if (!display.showSat) hidden.push(6);

        const current = this.calendar.getOption('hiddenDays') || [];
        // setOption re-renders (and fires datesSet again), so skip a no-op write.
        if (JSON.stringify([...hidden].sort()) === JSON.stringify([...current].sort())) return;

        // datesSet/loading fire mid-render; applying on the next tick keeps the
        // re-render out of the cycle that asked for it.
        clearTimeout(this.hiddenDaysTimer);
        this.hiddenDaysTimer = setTimeout(() => {
            this.calendar.setOption('hiddenDays', hidden);
        }, 0);
    }

    /** Weekend columns from a concrete dataset (raw feed objects with a `start`). */
    revealWeekendsForData(data) {
        if (!this.calendar) return;
        try {
            this.applyHiddenDays(this.weekendDisplayForEvents(data));
        } catch (error) {
            console.error('Error revealing weekend columns:', error);
        }
    }

    updateWeekendVisibility() {
        if (!this.calendar) return;
        this.applyHiddenDays(this.weekendDisplayForEvents(this.calendar.getEvents()));
    }

    /** Column indexes of the week grid (0 = Monday .. 6 = Sunday) that stay visible. */
    visibleWeekDayIndexes() {
        const display = this.weekendDisplay || { showSat: false, showSun: false };
        const indexes = [0, 1, 2, 3, 4]; // Mon-Fri always
        if (display.showSat) indexes.push(5);
        if (display.showSun) indexes.push(6);
        return indexes;
    }

    updateCourseHeader() {
        const headerTitle = document.querySelector('.course-header h1');
        const headerBadge = document.querySelector('.course-header .badge');
        const headerYear = document.querySelector('.course-header p');
        
        if (!this.selectedCourseId) {
            // If "All Courses" selected, show default message
            if (headerTitle) {
                headerTitle.textContent = 'All Courses';
            }
            if (headerBadge) {
                headerBadge.textContent = 'All';
            }
            if (headerYear) {
                headerYear.innerHTML = `
                    <span class="badge">All</span>
                    | <strong>Year:</strong> ${new Date().getFullYear()}
                `;
            }
            return;
        }

        const selectedCourse = this.courses.find(c => c.pk == this.selectedCourseId);
        if (selectedCourse) {
            if (headerTitle) {
                headerTitle.textContent = selectedCourse.course_name || 'Course Name';
            }
            if (headerBadge) {
                headerBadge.textContent = selectedCourse.couse_short_name || 'Course Code';
            }
            if (headerYear) {
                headerYear.innerHTML = `
                    <span class="badge">${selectedCourse.couse_short_name || 'Course Code'}</span>
                    | <strong>Year:</strong> ${selectedCourse.course_year || new Date().getFullYear()}
                `;
            }
        }
    }

    styleMoreLinks() {
        const moreLinks = document.querySelectorAll(
            '.fc-daygrid-day-more-link, .fc-more-link, .fc-timegrid-more-link');
        moreLinks.forEach(link => {
            if (link.textContent.includes('+') || link.textContent.toLowerCase().includes('more')) {
                link.classList.add('cal-portal-more-link');
            }
        });
    }

    initPortalToolbar() {
        const prevBtn = document.getElementById('calPortalPrev');
        const nextBtn = document.getElementById('calPortalNext');

        prevBtn?.addEventListener('click', () => {
            const listViewEl = document.getElementById('eventListView');
            if (listViewEl && !listViewEl.classList.contains('d-none')) {
                this.navigateWeek(-1);
                return;
            }
            this.calendar?.prev();
        });

        nextBtn?.addEventListener('click', () => {
            const listViewEl = document.getElementById('eventListView');
            if (listViewEl && !listViewEl.classList.contains('d-none')) {
                this.navigateWeek(1);
                return;
            }
            this.calendar?.next();
        });
    }

    updatePortalToolbarTitle() {
        const titleEl = document.getElementById('calPortalTitle');
        if (!titleEl) {
            return;
        }
        const listViewEl = document.getElementById('eventListView');
        if (listViewEl && !listViewEl.classList.contains('d-none')) {
            const weekText = document.getElementById('weekRangeText');
            titleEl.textContent = weekText ? weekText.textContent.replace(/^\s*[\u{1F4C5}\s]*/u, '').trim() : 'Weekly Timetable';
            return;
        }
        if (this.calendar) {
            titleEl.textContent = this.calendar.view.title;
        }
    }

    syncPortalViewButtons() {
        if (!this.calendar) {
            return;
        }
        const listViewEl = document.getElementById('eventListView');
        if (listViewEl && !listViewEl.classList.contains('d-none')) {
            return;
        }
        const viewType = this.calendar.view.type;
        document.querySelectorAll('.cal-view-switch [data-view]').forEach(btn => {
            const match = (viewType === 'dayGridMonth' && btn.dataset.view === 'month')
                || (viewType === 'timeGridWeek' && btn.dataset.view === 'week');
            btn.classList.toggle('active', match);
            btn.setAttribute('aria-pressed', match ? 'true' : 'false');
        });
    }

    observeMoreLinksChanges() {
        const calendarEl = document.getElementById('calendar');
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    // Check if any added node contains "+ more" links
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            if (node.textContent && node.textContent.includes('+')) {
                                this.styleMoreLinks();
                            }
                            // Re-evaluate dense mode when DOM changes
                            this.applyDenseMode();
                        }
                    });
                }
            });
        });

        observer.observe(calendarEl, {
            childList: true,
            subtree: true,
            characterData: false
        });
    }

    applyDenseMode() {
        // Only apply dense mode when compact mode is active
        if (!document.body.classList.contains('compact-mode')) return;
        // Add/remove dense-day class based on number of events in day cells
        const dayCells = document.querySelectorAll('.fc-daygrid-day');
        dayCells.forEach(cell => {
            const eventEls = cell.querySelectorAll('.fc-daygrid-day-frame .fc-event');
            if (eventEls.length >= 5) {
                cell.classList.add('dense-day');
            } else {
                cell.classList.remove('dense-day');
            }
        });
    }

    formatCalEventTimeRange(arg) {
        const event = arg.event;
        const session = event.extendedProps?.class_session_debug
            || event.extendedProps?.class_session
            || '';
        if (session && String(session).includes('-')) {
            return String(session).trim();
        }
        if (event.start && event.end && !event.allDay) {
            const opts = { hour: '2-digit', minute: '2-digit', hour12: false };
            const start = event.start.toLocaleTimeString('en-GB', opts);
            const end = event.end.toLocaleTimeString('en-GB', opts);
            return `${start} - ${end}`;
        }
        return arg.timeText || '';
    }

    renderEventContent(arg) {
        const type = (arg.event.extendedProps.type || arg.event.extendedProps.event_type || arg.event.extendedProps
            .session_type || '').toString();
        const typeAttr = type.toLowerCase();

        const topic = arg.event.title || '';
        const venue = arg.event.extendedProps.vanue || '';
        const faculty = arg.event.extendedProps.faculty_name || '';
        const timeRange = this.formatCalEventTimeRange(arg);
        const idStr = (arg.event.id || arg.event._def?.publicId || Math.random().toString(36).slice(2));
        const titleId = `fc-evt-${idStr}-title`;
        const descId = `fc-evt-${idStr}-desc`;

        return {
            html: `
                <div class="cal-event-pill"
                     tabindex="0"
                     role="button"
                     aria-labelledby="${titleId}"
                     aria-describedby="${descId}"
                     ${type ? `data-event-type="${typeAttr}"` : ''}>
                    <span class="cal-event-pill__accent" aria-hidden="true"></span>
                    <span class="cal-event-pill__content">
                        <span class="cal-event-pill__title" id="${titleId}">${topic}</span>
                        ${timeRange ? `<span class="cal-event-pill__time">${timeRange}</span>` : ''}
                    </span>
                    ${venue ? `<span class="visually-hidden">${venue}</span>` : ''}
                    ${faculty ? `<span class="visually-hidden">${faculty}</span>` : ''}
                    <span class="visually-hidden" id="${descId}">${type ? `${type} ` : ''}${timeRange ? `${timeRange} ` : ''}${venue ? `at ${venue} ` : ''}${faculty ? `with ${faculty}` : ''}</span>
                </div>
            `
        };
    }

    onEventMount(arg) {
        this.setEventAccessibility(arg);
        arg.el.style.setProperty('background-color', 'transparent', 'important');
        arg.el.style.setProperty('border-color', 'transparent', 'important');
        arg.el.style.setProperty('box-shadow', 'none', 'important');
        arg.el.style.setProperty('color', 'inherit', 'important');
        const main = arg.el.querySelector('.fc-event-main');
        if (main) {
            main.style.setProperty('background-color', 'transparent', 'important');
            main.style.setProperty('border-color', 'transparent', 'important');
            main.style.setProperty('box-shadow', 'none', 'important');
            main.style.setProperty('padding', '0', 'important');
            main.style.setProperty('color', 'inherit', 'important');
        }
    }

    setEventAccessibility(arg) {
        arg.el.setAttribute('role', 'button');
        arg.el.setAttribute('tabindex', '0');
        arg.el.setAttribute('aria-label', `${arg.event.title} - Click for details`);
    }

    setDayCellAccessibility(arg) {
        try {
            const cell = arg.el;
            const date = arg.date; // FullCalendar provides date in v5/v6
            const dayLabel = date ? new Date(date).toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';
            cell.setAttribute('role', 'gridcell');
            cell.setAttribute('tabindex', '0');
            if (dayLabel) cell.setAttribute('aria-label', dayLabel);

            // Keyboard navigation between day cells
            cell.addEventListener('keydown', (e) => {
                const dayCells = Array.from(document.querySelectorAll('.fc-daygrid-day'));
                const idx = dayCells.indexOf(cell);
                const cols = 7;
                if (idx === -1) return;
                let targetIdx = null;
                switch (e.key) {
                    case 'ArrowRight': targetIdx = idx + 1; break;
                    case 'ArrowLeft': targetIdx = idx - 1; break;
                    case 'ArrowDown': targetIdx = idx + cols; break;
                    case 'ArrowUp': targetIdx = idx - cols; break;
                    case 'Enter':
                    case ' ': {
                        // Open "+ more" or focus first event
                        const more = cell.querySelector('.fc-daygrid-day-more-link, .fc-more-link');
                        const evt = cell.querySelector('.fc-event, .cal-event-pill');
                        if (more) { more.click(); e.preventDefault(); }
                        else if (evt) { evt.dispatchEvent(new MouseEvent('click')); e.preventDefault(); }
                        return;
                    }
                }
                if (targetIdx !== null && dayCells[targetIdx]) {
                    e.preventDefault();
                    dayCells[targetIdx].focus();
                }
            });
        } catch {}
    }

    handleEventClick(info) {
        info.jsEvent?.preventDefault();
        this.closePopover();
        this.currentEventId = info.event.id;
        this.showEventHoverCard(info.event, info.el, true);
    }

    handleEventMouseEnter(info) {
        clearTimeout(this.hoverHideTimer);
        this.hoverAnchorEl = info.el;
        clearTimeout(this.hoverShowTimer);
        this.hoverShowTimer = setTimeout(() => {
            if (this.hoverAnchorEl !== info.el) return;
            this.showEventHoverCard(info.event, info.el, false);
        }, 200);
    }

    handleEventMouseLeave() {
        clearTimeout(this.hoverShowTimer);
        const card = document.getElementById('calEventHoverCard');
        if (card?.dataset.pinned === 'true') return;
        clearTimeout(this.hoverHideTimer);
        this.hoverHideTimer = setTimeout(() => {
            const hoverCard = document.getElementById('calEventHoverCard');
            if (hoverCard && !hoverCard.matches(':hover')) {
                this.hideEventHoverCard();
            }
            this.hoverAnchorEl = null;
        }, 280);
    }

    async fetchEventDetailsCached(eventId) {
        if (this.eventDetailsCache.has(eventId)) {
            return this.eventDetailsCache.get(eventId);
        }
        const response = await fetch(`${CalendarConfig.api.eventDetails}?id=${eventId}`, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        if (!response.ok) throw new Error('Failed to load event details');
        const data = await response.json();
        this.eventDetailsCache.set(eventId, data);
        return data;
    }

    populateEventHoverCard(data) {
        const topic = data.topic || '';
        const dateLabel = data.start
            ? new Date(data.start).toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                weekday: 'long'
            })
            : '';
        const timeLabel = data.class_session || '';
        const dateTimeLine = [dateLabel, timeLabel].filter(Boolean).join(' ');

        document.getElementById('calHoverEventTitle').textContent = topic || 'Event';
        document.getElementById('calHoverEventTopic').textContent = topic || '';
        document.getElementById('calHoverEventDate').textContent = dateTimeLine;
        document.getElementById('calHoverFaculty').textContent = data.faculty_name || '—';
        document.getElementById('calHoverGroup').textContent = data.group_name || '—';
        document.getElementById('calHoverVenue').textContent = data.venue_name || '—';

        const editBtn = document.getElementById('calHoverEditBtn');
        const deleteBtn = document.getElementById('calHoverDeleteBtn');
        const modalEdit = document.getElementById('editEventBtn');
        const modalDelete = document.getElementById('deleteEventBtn');
        if (editBtn) editBtn.dataset.editUrl = data.edit_url || '';
        if (deleteBtn) deleteBtn.dataset.id = data.id;
        if (modalEdit) { modalEdit.dataset.editUrl = data.edit_url || ''; modalEdit.dataset.id = data.id; }
        if (modalDelete) modalDelete.dataset.id = data.id;
    }

    positionEventHoverCard(anchorEl) {
        const card = document.getElementById('calEventHoverCard');
        if (!card || !anchorEl) return;

        card.classList.remove('cal-event-hover-card--arrow-left');
        card.style.visibility = 'hidden';
        card.classList.remove('d-none');
        card.setAttribute('aria-hidden', 'false');

        const rect = anchorEl.getBoundingClientRect();
        const gap = 14;
        let left = rect.right + gap;
        const cardWidth = card.offsetWidth || 380;
        const cardHeight = card.offsetHeight || 260;

        if (left + cardWidth > window.innerWidth - 12) {
            left = rect.left - cardWidth - gap;
            card.classList.add('cal-event-hover-card--arrow-left');
        }

        let top = rect.top + (rect.height / 2) - (cardHeight / 2);
        top = Math.max(12, Math.min(top, window.innerHeight - cardHeight - 12));

        card.style.top = `${top}px`;
        card.style.left = `${left}px`;
        card.style.visibility = 'visible';
    }

    async showEventHoverCard(event, anchorEl, pinned = false) {
        const card = document.getElementById('calEventHoverCard');
        if (!card || !event?.id) return;

        try {
            const data = await this.fetchEventDetailsCached(event.id);
            this.currentEventId = event.id;
            this.populateEventHoverCard(data);
            this.positionEventHoverCard(anchorEl);
            card.dataset.pinned = pinned ? 'true' : 'false';
            document.querySelectorAll('.cal-event-pill.is-hover-active').forEach((el) => {
                el.classList.remove('is-hover-active');
            });
            anchorEl.querySelector('.cal-event-pill')?.classList.add('is-hover-active');
        } catch (error) {
            console.error('Event hover card error:', error);
        }
    }

    hideEventHoverCard() {
        const card = document.getElementById('calEventHoverCard');
        if (!card) return;
        card.classList.add('d-none');
        card.setAttribute('aria-hidden', 'true');
        card.dataset.pinned = 'false';
        card.style.visibility = '';
        document.querySelectorAll('.cal-event-pill.is-hover-active').forEach((el) => {
            el.classList.remove('is-hover-active');
        });
    }

    initEventHoverCard() {
        const card = document.getElementById('calEventHoverCard');
        if (!card) return;

        card.addEventListener('mouseenter', () => clearTimeout(this.hoverHideTimer));
        card.addEventListener('mouseleave', () => {
            if (card.dataset.pinned === 'true') return;
            this.hoverHideTimer = setTimeout(() => this.hideEventHoverCard(), 200);
        });

        document.getElementById('calHoverEditBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const url = document.getElementById('calHoverEditBtn')?.dataset.editUrl;
            this.hideEventHoverCard();
            if (url) window.location.href = url;
        });

        document.getElementById('calHoverDeleteBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const btn = document.getElementById('deleteEventBtn');
            if (btn && document.getElementById('calHoverDeleteBtn')?.dataset.id) {
                btn.dataset.id = document.getElementById('calHoverDeleteBtn').dataset.id;
            }
            this.hideEventHoverCard();
            this.confirmDelete();
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('#calEventHoverCard') || e.target.closest('.cal-event-pill')) {
                return;
            }
            this.hideEventHoverCard();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideEventHoverCard();
        });

        window.addEventListener('scroll', () => {
            if (this.hoverAnchorEl && !card.classList.contains('d-none')) {
                this.positionEventHoverCard(this.hoverAnchorEl);
            }
        }, true);
    }

    closePopover() {
        // Find and close any open FullCalendar popovers
        const openPopovers = document.querySelectorAll('.fc-popover');
        openPopovers.forEach(popover => {
            popover.remove();
        });
        
        // Also remove any popover backdrops or overlays
        const popoverBackdrops = document.querySelectorAll('.fc-popover-backdrop');
        popoverBackdrops.forEach(backdrop => {
            backdrop.remove();
        });
    }

    async loadEventDetails(eventId) {
        try {
            const response = await fetch(`${CalendarConfig.api.eventDetails}?id=${eventId}`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) throw new Error('Failed to load event details');

            const data = await response.json();
            this.showEventDetails(data);
            
        } catch (error) {
            // this.showNotification('Error loading event details', 'danger');
            console.error('Event details error:', error);
        }
    }

    showEventDetails(data) {
        const topic = data.topic || '';
        const dateLabel = data.start
            ? new Date(data.start).toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                weekday: 'long'
            })
            : '';
        const timeLabel = data.class_session || '';
        const dateTimeLine = [dateLabel, timeLabel].filter(Boolean).join(' ');

        document.getElementById('eventTitle').textContent = topic || data.group_name || 'Event';
        document.getElementById('eventTopic').textContent = topic || '';
        document.getElementById('eventDate').textContent = dateTimeLine;
        document.getElementById('eventfaculty').textContent = data.faculty_name || '';
        document.getElementById('eventVanue').textContent = data.venue_name || '';
        document.getElementById('eventclasssession').textContent = data.class_session || '';
        document.getElementById('eventgroupname').textContent = data.group_name || '';
        document.getElementById('internal_faculty_name_show').textContent = data.internal_faculty || '';

        // Set edit/delete button data
        const editBtn = document.getElementById('editEventBtn');
        const deleteBtn = document.getElementById('deleteEventBtn');

        if (editBtn) editBtn.dataset.id = data.id;
        if (deleteBtn) deleteBtn.dataset.id = data.id;

        // Event Card (PDF preview) link
        const viewCardBtn = document.getElementById('viewEventCardBtn');
        if (viewCardBtn && data.id) {
            viewCardBtn.href = CalendarConfig.api.eventCard.replace('EVENT_ID', data.id);
        }

        // Show modal. Reuse the single Bootstrap instance for this element
        // (getOrCreateInstance) instead of `new Modal()` on every open — repeated
        // `new` calls stack extra focus-trap/backdrop listeners on the same node,
        // which then fight each other and make the modal blink/flicker.
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eventDetails'));
        modal.show();
    }

    handleDateSelect(info) {
        // Calendar date click should not open any modal.
        // Use the "Add Event" button to create a new event.
    }

    resetEventForm() {
        const form = document.getElementById('eventForm');
        form.reset();

        // Clear validation errors
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        const typeNameContainer = document.getElementById('type_name_container');
        const typeNameError = document.getElementById('type_names_error');
        if (typeNameContainer) {
            typeNameContainer.classList.remove('border-danger');
        }
        if (typeNameError) {
            typeNameError.style.display = 'none';
        }

        // Reset dynamic fields
        document.getElementById('group_type').innerHTML = '<option value="">Select Group Type</option>';
        document.getElementById('type_name_container').innerHTML =
            '<div class="text-center text-muted">Select a Group Type first</div>';

        // Pre-select Course Name based on course filter
        const courseFilter = document.getElementById('courseFilter');
        const courseNameField = document.getElementById('Course_name');
        if (courseFilter && courseNameField && courseFilter.value) {
            courseNameField.value = courseFilter.value;
            // Trigger change event to load group types for the selected course
            courseNameField.dispatchEvent(new Event('change'));
        }

        // Update button text
        document.getElementById('eventModalTitle').textContent = 'Add Event';
        document.querySelector('.btn-text').textContent = 'Add Event';
        document.getElementById('submitEventBtn').dataset.action = 'create';
        if (window.calendarEventModalWizard) {
            window.calendarEventModalWizard.reset();
        }

        // Reset date field
        document.getElementById('start_datetime').removeAttribute('readonly');

        // Show normal shift by default
        this.toggleShiftFields();
    }

    setFormDate(date) {
        const formattedDate = date.toLocaleDateString('en-CA');
        console.log('Selected date for form:', formattedDate);
        document.getElementById('start_datetime').value = formattedDate;
        document.getElementById('start_datetime').setAttribute('readonly', 'true');
    }


    bindEvents() {
        // View toggle buttons
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const viewBtn = e.target.closest('[data-view]');
                if (viewBtn) {
                    this.toggleView(viewBtn);
                }
            });
        });

        // Week navigation buttons (List View)
        document.getElementById('prevWeekBtn')?.addEventListener('click', () => this.navigateWeek(-1));
        document.getElementById('nextWeekBtn')?.addEventListener('click', () => this.navigateWeek(1));
        document.getElementById('currentWeekBtn')?.addEventListener('click', () => this.navigateWeek(0));

        // Academic time table PDF — main page Download button (visible range + course filter)
        document.getElementById('btnTimetablePdf')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.openTimetablePdf(true);
        });

        // Whole-week timetable PDF (download / print) — list-view panel + main toolbar
        document.getElementById('btnWeekTimetablePdf')?.addEventListener('click', () => this.openWeeklyTimetablePdf(false));
        document.getElementById('btnWeekTimetablePrint')?.addEventListener('click', () => {
            const params = this.weeklyExportParams(false);
            window.open(`${CalendarConfig.api.weeklyTimetablePdf}?${params.toString()}`, '_blank', 'noopener');
        });
        document.getElementById('btnToolbarWeekPdf')?.addEventListener('click', () => this.openWeeklyTimetablePdf(true));
        document.getElementById('btnToolbarWeekPrint')?.addEventListener('click', () => this.openWeeklyTimetablePdf(false));
        document.getElementById('btnToolbarWeekInfo')?.addEventListener('click', () => this.openWeeklyInfoPdf(false));
        document.getElementById('btnWeekInfoPdf')?.addEventListener('click', () => this.openWeeklyInfoPdf(false));
        document.getElementById('btnEditWeekInfo')?.addEventListener('click', () => this.openWeeklyInfoEditor());
        document.getElementById('weeklyInfoForm')?.addEventListener('submit', (e) => this.saveWeeklyInfo(e));
        document.getElementById('wiAddNote')?.addEventListener('click', () => this.addWeeklyInfoNoteRow());
        document.getElementById('wiAddLanguage')?.addEventListener('click', () => this.addWeeklyInfoLanguageRow());
        document.getElementById('wiAddVenue')?.addEventListener('click', () => this.addWeeklyInfoVenueRow());

        // Form submission
        document.getElementById('eventForm')?.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Dynamic field dependencies
        document.getElementById('Course_name')?.addEventListener('change', () => this.loadGroupTypes());
        document.getElementById('subject_module')?.addEventListener('change', () => this.loadSubjectNames());
        document.getElementById('faculty')?.addEventListener('change', () => this.updateFacultyType());
        document.getElementById('faculty_type')?.addEventListener('change', () => this.updateCheckboxState());

        // Shift type toggles
        document.querySelectorAll('input[name="shift_type"]').forEach(radio => {
            radio.addEventListener('change', () => this.toggleShiftFields());
        });

        // Full day checkbox
        document.getElementById('fullDayCheckbox')?.addEventListener('change', (e) => {
            this.toggleFullDayFields(e.target.checked);
        });

        // Feedback checkbox
        document.getElementById('feedback_checkbox')?.addEventListener('change', () => {
            this.toggleFeedbackDependencies();
        });

        // Edit/Delete buttons
        document.getElementById('editEventBtn')?.addEventListener('click', () => {
            const url = document.getElementById('editEventBtn')?.dataset.editUrl;
            if (url) window.location.href = url;
        });
        document.getElementById('deleteEventBtn')?.addEventListener('click', () => this.confirmDelete());

        // Create event button
        document.getElementById('createEventButton')?.addEventListener('click', () => {
            this.resetEventForm();
        });

        // List view: open details on click/keyboard
        const listView = document.getElementById('eventListView');
        listView?.addEventListener('click', (e) => {
            const card = e.target.closest('.list-event-card, .tt-card');
            if (card?.dataset?.id) {
                this.loadEventDetails(card.dataset.id);
            }
        });
        listView?.addEventListener('keydown', (e) => {
            const card = e.target.closest('.list-event-card, .tt-card');
            if (!card) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (card.dataset?.id) {
                    this.loadEventDetails(card.dataset.id);
                }
            }
        });

        // Density toggle
        document.getElementById('toggleDensityBtn')?.addEventListener('click', () => this.toggleDensity());

        // Course filter change
        document.getElementById('courseFilter')?.addEventListener('change', (e) => {
            this.handleCourseFilterChange(e.target.value);
        });

        document.getElementById('btnResetCalendarFilters')?.addEventListener('click', () => {
            const courseFilter = document.getElementById('courseFilter');
            if (!courseFilter) return;
            courseFilter.value = '';
            syncCalCourseFilterState();
            courseFilter.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    handleCourseFilterChange(courseId) {
        this.selectedCourseId = courseId || null;
        this.updateCourseHeader();
        
        // Refresh calendar events
        if (this.calendar) {
            this.calendar.refetchEvents();
        }
        
        // If in list view, reload it
        const listViewEl = document.getElementById('eventListView');
        if (listViewEl && !listViewEl.classList.contains('d-none')) {
            this.loadListView();
        }
    }

    initDensity() {
        const saved = localStorage.getItem('calendarDensity');
        let isCompact;
        if (saved === null) {
            isCompact = false; // Default to comfortable mode for full cards
            try { localStorage.setItem('calendarDensity', 'comfortable'); } catch {}
        } else {
            isCompact = saved === 'compact';
        }
        document.body.classList.toggle('compact-mode', isCompact);

        const btn = document.getElementById('toggleDensityBtn');
        if (btn) {
            btn.classList.toggle('active', isCompact);
            btn.setAttribute('aria-pressed', String(isCompact));
        }
    }

    toggleDensity() {
        const isCompact = !document.body.classList.contains('compact-mode');
        document.body.classList.toggle('compact-mode', isCompact);
        localStorage.setItem('calendarDensity', isCompact ? 'compact' : 'comfortable');

        const btn = document.getElementById('toggleDensityBtn');
        if (btn) {
            btn.classList.toggle('active', isCompact);
            btn.setAttribute('aria-pressed', String(isCompact));
        }

        // Re-measure dense days in month view
        this.applyDenseMode();
    }

    toggleView(button) {
        // Update button states
        document.querySelectorAll('.cal-view-switch [data-view], #btnTimetableListView').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        button.classList.add('active');
        button.setAttribute('aria-pressed', 'true');

        const view = button.dataset.view;
        const calendarEl = document.getElementById('calendar');
        const listViewEl = document.getElementById('eventListView');

        const portalToolbar = document.getElementById('calPortalToolbar');

        if (view === 'list') {
            calendarEl.style.display = 'none';
            listViewEl.classList.remove('d-none');
            portalToolbar?.classList.remove('d-none');
            this.loadListView();
            this.updatePortalToolbarTitle();
        } else {
            calendarEl.style.display = '';
            listViewEl.classList.add('d-none');
            portalToolbar?.classList.remove('d-none');
            this.calendar.changeView(this.getCalendarView(view));
            this.updatePortalToolbarTitle();
            this.syncPortalViewButtons();
            setTimeout(() => this.styleMoreLinks(), 100);
        }
    }

    getCalendarView(view) {
        const views = {
            'month': 'dayGridMonth',
            'week': 'timeGridWeek',
            'day': 'timeGridDay'
        };
        return views[view] || 'timeGridDay';
    }

    async loadGroupTypes() {
        const courseId = document.getElementById('Course_name').value;
        if (!courseId) return;

        try {
            const response = await fetch(`${CalendarConfig.api.groupTypes}?course_id=${courseId}`);
            const data = await response.json();

            this.populateGroupTypes(data);
        } catch (error) {
            console.error('Error loading group types:', error);
        }
    }

    populateGroupTypes(data) {
        // Group data by group_type_name
        const grouped = {};
        data.forEach(item => {
            if (!grouped[item.group_type_name]) {
                grouped[item.group_type_name] = [];
            }
            grouped[item.group_type_name].push(item);
        });

        // Populate dropdown
        const select = document.getElementById('group_type');
        select.innerHTML = '<option value="">Select Group Type</option>';

        Object.keys(grouped).forEach(key => {
            const typeName = grouped[key][0].type_name;
            const option = document.createElement('option');
            option.value = key;
            option.textContent = typeName;
            select.appendChild(option);
        });

        if (window.calendarModalChoices?.rebuildById) {
            window.calendarModalChoices.rebuildById('group_type');
        }

        // Set up change handler
        select.onchange = () => {
            this.populateGroupCheckboxes(grouped[select.value] || []);
            // Clear validation error when group type changes
            const typeNameContainer = document.getElementById('type_name_container');
            const typeNameError = document.getElementById('type_names_error');
            if (typeNameContainer) {
                typeNameContainer.classList.remove('border-danger');
            }
            if (typeNameError) {
                typeNameError.style.display = 'none';
            }
        };

        // Return grouped data for use in edit mode
        return grouped;
    }

    populateGroupCheckboxes(groups) {
        const container = document.getElementById('type_name_container');

        if (!groups.length) {
            container.innerHTML = '<div class="text-center text-muted">No groups found</div>';
            return;
        }

        let html = '<div class="row g-2">';

        groups.forEach(group => {
            // Convert group.pk to string for consistent comparison
            const groupPkStr = String(group.pk);
            
            // Check if this group is selected (handle both string and number types)
            let isChecked = false;
            if (this.selectedGroupNames === 'ALL') {
                isChecked = true;
            } else if (Array.isArray(this.selectedGroupNames)) {
                // Convert all selected names to strings for comparison
                const selectedAsStrings = this.selectedGroupNames.map(String);
                isChecked = selectedAsStrings.includes(groupPkStr);
            }
            
            const checked = isChecked ? 'checked' : '';

            html += `
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="type_names[]" 
                               value="${group.pk}" 
                               id="type_${group.pk}" 
                               ${checked}>
                        <label class="form-check-label" for="type_${group.pk}">
                            ${group.group_name} (${group.type_name})
                        </label>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;

        // Add change event listeners to checkboxes to clear validation error
        const checkboxes = container.querySelectorAll('input[name="type_names[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const typeNameContainer = document.getElementById('type_name_container');
                const typeNameError = document.getElementById('type_names_error');
                const checkedCount = container.querySelectorAll('input[name="type_names[]"]:checked').length;
                
                if (checkedCount > 0) {
                    if (typeNameContainer) {
                        typeNameContainer.classList.remove('border-danger');
                    }
                    if (typeNameError) {
                        typeNameError.style.display = 'none';
                    }
                }
            });
        });
    }

    async loadSubjectNames() {
        const moduleId = document.getElementById('subject_module').value;
        if (!moduleId) return;

        try {
            const response = await fetch(`${CalendarConfig.api.subjectNames}?data_id=${moduleId}`);
            const data = await response.json();

            this.populateSubjectNames(data);
        } catch (error) {
            console.error('Error loading subject names:', error);
        }
    }

    populateSubjectNames(subjects) {
        const select = document.getElementById('subject_name');
        select.innerHTML = '<option value="">Select Subject Name</option>';

        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.pk;
            option.textContent = subject.subject_name;
            select.appendChild(option);
        });

        if (window.calendarModalChoices?.rebuildById) {
            window.calendarModalChoices.rebuildById('subject_name');
        }
    }

    updateFacultyType() {
        const facultySelect = document.getElementById('faculty');
        const selectedOption = facultySelect.options[facultySelect.selectedIndex];
        const facultyType = selectedOption?.dataset.faculty_type;

        if (facultyType) {
           
            document.getElementById('faculty_type').value = facultyType;
            this.updateCheckboxState();
        }
    }

    updateCheckboxState() {
        const facultyType = document.getElementById('faculty_type').value;
        switch (facultyType) {
            case '1': // Internal
                this.setCheckboxState('remarkCheckbox', false, false);
                this.setCheckboxState('ratingCheckbox', true, false);
                break;
            case '2': // Guest
                this.setCheckboxState('remarkCheckbox', false, true);
                this.setCheckboxState('ratingCheckbox', false, true);
                break;
            default: // Research/Other
                this.setCheckboxState('remarkCheckbox', true, false);
                this.setCheckboxState('ratingCheckbox', true, false);
        }
    }

    setCheckboxState(id, disabled, checked) {
        const checkbox = document.getElementById(id);
        checkbox.disabled = disabled;
        checkbox.checked = checked;

        if (disabled) {
            checkbox.classList.add('readonly-checkbox');
        } else {
            checkbox.classList.remove('readonly-checkbox');
        }
    }

    toggleShiftFields() {
        const isManual = document.getElementById('manualShift').checked;

        document.getElementById('shiftSelect').classList.toggle('d-none', isManual);
        document.getElementById('manualShiftFields').classList.toggle('d-none', !isManual);

        // Toggle required attributes
        const shiftSelect = document.getElementById('shift');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');

        if (isManual) {
            shiftSelect.removeAttribute('required');
            startTime.setAttribute('required', 'true');
            endTime.setAttribute('required', 'true');
        } else {
            shiftSelect.setAttribute('required', 'true');
            startTime.removeAttribute('required');
            endTime.removeAttribute('required');
        }
    }

    toggleFullDayFields(isFullDay) {
        const dateTimeFields = document.getElementById('dateTimeFields');

        if (isFullDay) {
            dateTimeFields.classList.add('d-none');
            document.getElementById('start_time').value = '09:00';
            document.getElementById('end_time').value = '17:30';
        } else {
            dateTimeFields.classList.remove('d-none');
            document.getElementById('start_time').value = '';
            document.getElementById('end_time').value = '';
        }
    }

    toggleFeedbackDependencies() {
        const isChecked = document.getElementById('feedback_checkbox').checked;
        const remarkCheckbox = document.getElementById('remarkCheckbox');
        const ratingCheckbox = document.getElementById('ratingCheckbox');

        if (!isChecked) {
            remarkCheckbox.checked = false;
            ratingCheckbox.checked = false;
            remarkCheckbox.disabled = true;
            ratingCheckbox.disabled = true;
        } else {
            remarkCheckbox.disabled = false;
            ratingCheckbox.disabled = false;
        }
    }

    validateDates() {
        const dateInput = document.getElementById('start_datetime');
        // dateInput.setAttribute('min', CalendarConfig.minDate);

        // Add real-time validation
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                // this.setCustomValidity('Date cannot be in the past');
                // this.reportValidity();
            } else {
                this.setCustomValidity('');
            }
        });
    }

    async handleFormSubmit(e) {
        e.preventDefault();

        if (!this.validateForm()) {
            return;
        }

        const formData = new FormData(e.target);
        const action = document.getElementById('submitEventBtn').dataset.action;
        const url = action === 'edit' ?
            CalendarConfig.api.update.replace('EVENT_ID', this.currentEventId) :
            CalendarConfig.api.store;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams(formData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Submission failed');
            }

            const result = await response.json();
            this.showNotification(result.message || 'Event saved successfully', 'success');

            // Close modal and refresh calendar
            bootstrap.Modal.getInstance(document.getElementById('eventModal'))?.hide();
            this.calendar.refetchEvents();
            setTimeout(() => {
               window.location.reload(); 
            }, 1000);

        } catch (error) {
            this.showNotification(error.message, 'danger');
            console.error('Form submission error:', error);
        }
    }

    validateForm() {
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Required fields validation
        const requiredFields = [
            'Course_name', 'subject_module', 'group_type', 'subject_name', 'topic',
            'faculty', 'faculty_type', 'vanue', 'start_datetime'
        ];

        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        // Shift validation
        if (document.getElementById('normalShift').checked) {
            const shift = document.getElementById('shift');
            if (!shift.value) {
                shift.classList.add('is-invalid');
                isValid = false;
            }
        } else {
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');

            if (!startTime.value || !endTime.value) {
                startTime.classList.add('is-invalid');
                endTime.classList.add('is-invalid');
                isValid = false;
            }

            // Time validation
            if (startTime.value && endTime.value) {
                if (startTime.value >= endTime.value) {
                    this.showNotification('End time must be after start time', 'warning');
                    isValid = false;
                }
            }
        }

        // Feedback validation
        if (document.getElementById('feedback_checkbox').checked) {
            const remarkChecked = document.getElementById('remarkCheckbox').checked;
            const ratingChecked = document.getElementById('ratingCheckbox').checked;

            if (!remarkChecked && !ratingChecked) {
                this.showNotification('Please select at least Remark or Rating when Feedback is checked',
                    'warning');
                isValid = false;
            }
        }

        // Group Type Name validation
        const groupTypeCheckboxes = document.querySelectorAll('input[name="type_names[]"]:checked');
        const typeNameContainer = document.getElementById('type_name_container');
        const typeNameError = document.getElementById('type_names_error');
        
        if (groupTypeCheckboxes.length === 0) {
            typeNameContainer.classList.add('border-danger');
            if (typeNameError) {
                typeNameError.style.display = 'block';
            }
            isValid = false;
        } else {
            typeNameContainer.classList.remove('border-danger');
            if (typeNameError) {
                typeNameError.style.display = 'none';
            }
        }

        return isValid;
    }

    async loadEventForEdit() {
        const eventId = document.getElementById('calHoverEditBtn')?.dataset.id
            || document.getElementById('editEventBtn')?.dataset.id
            || this.currentEventId;

        try {
            const response = await fetch(CalendarConfig.api.edit.replace('EVENT_ID', eventId));
            if (!response.ok) {
                throw new Error(`Request failed: HTTP ${response.status} for event id "${eventId}"`);
            }
            const event = await response.json();

            await this.populateEditForm(event);

            // Update modal for edit
            document.getElementById('eventModalTitle').textContent = 'Edit Event';
            document.querySelector('.btn-text').textContent = 'Update Event';
            document.getElementById('submitEventBtn').dataset.action = 'edit';
            document.getElementById('start_datetime').removeAttribute('readonly');

            // Show modal — eventDetails may not have an instance when editing from the hover card
            bootstrap.Modal.getInstance(document.getElementById('eventDetails'))?.hide();
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();

        } catch (error) {
            this.showNotification('Error loading event for editing: ' + (error?.message || error), 'danger');
            console.error('Edit load error:', error);
        }
    }

    async populateEditForm(event) {
        // Basic fields
        document.getElementById('Course_name').value = event.course_master_pk;
        document.getElementById('subject_module').value = event.subject_module_master_pk;
        document.getElementById('subject_name').value = event.subject_master_pk;
        document.getElementById('topic').value = event.subject_topic;
        document.getElementById('start_datetime').value = event.START_DATE;
        const ftEl = document.getElementById('faculty_type');
        if (ftEl) ftEl.value = event.faculty_type ?? '';
        document.getElementById('vanue').value = event.venue_id;

        if (window.calendarModalChoices?.syncById) {
            window.calendarModalChoices.syncById('Course_name');
            window.calendarModalChoices.syncById('subject_module');
            window.calendarModalChoices.syncById('subject_name');
            window.calendarModalChoices.syncById('vanue');
            window.calendarModalChoices.syncById('sector');
            window.calendarModalChoices.syncById('shift');
        }

        // Shift settings
        if (event.session_type == 2) {
            document.getElementById('manualShift').checked = true;
            this.toggleShiftFields();

            if (event.class_session) {
                const [start, end] = event.class_session.split(' - ');
                document.getElementById('start_time').value = this.convertTo24Hour(start);
                document.getElementById('end_time').value = this.convertTo24Hour(end);
            }
        } else {
            document.getElementById('normalShift').checked = true;
            document.getElementById('shift').value = event.class_session;
            this.toggleShiftFields();
        }

        // Checkboxes
        document.getElementById('fullDayCheckbox').checked = event.full_day == 1;
        document.getElementById('bio_attendanceCheckbox').checked = event.Bio_attendance == 1;
        
        // Handle feedback checkboxes - set them in correct order
        const feedbackCheckbox = document.getElementById('feedback_checkbox');
        const remarkCheckbox = document.getElementById('remarkCheckbox');
        const ratingCheckbox = document.getElementById('ratingCheckbox');
        const feedbackOptions = document.getElementById('feedbackOptions');
        
        // First, show/hide feedback options div based on saved state
        if (event.feedback_checkbox == 1 && feedbackOptions) {
            feedbackOptions.classList.remove('d-none');
            if (remarkCheckbox) remarkCheckbox.disabled = false;
            if (ratingCheckbox) ratingCheckbox.disabled = false;
        } else if (feedbackOptions) {
            feedbackOptions.classList.add('d-none');
        }
        
        // Then set the checkbox values
        if (feedbackCheckbox) feedbackCheckbox.checked = event.feedback_checkbox == 1;
        if (remarkCheckbox) remarkCheckbox.checked = event.Remark_checkbox == 1;
        if (ratingCheckbox) ratingCheckbox.checked = event.Ratting_checkbox == 1;
        
        // Handle faculty review rating div visibility based on internal faculty div
        if (event.feedback_checkbox == 1) {
            const facultyReviewRatingDiv = document.getElementById('facultyReviewRatingDiv');
            const internalFacultyDiv = document.getElementById('internalFacultyDiv');
            if (facultyReviewRatingDiv && internalFacultyDiv) {
                if (internalFacultyDiv.style.display === 'block') {
                    facultyReviewRatingDiv.classList.remove('d-none');
                } else {
                    facultyReviewRatingDiv.classList.add('d-none');
                }
            }
        }

        // Sector
        const sectorEl = document.getElementById('sector');
        if (sectorEl) sectorEl.value = event.sector_pk ?? '';

        // Break section
        if (event.break_type) {
            const breakRadio = document.querySelector(`input[name="break_type"][value="${event.break_type}"]`);
            if (breakRadio) breakRadio.checked = true;
        } else {
            document.querySelectorAll('input[name="break_type"]').forEach(r => r.checked = false);
        }
        const bStart = document.getElementById('modal_break_start_time');
        const bEnd   = document.getElementById('modal_break_end_time');
        if (bStart) bStart.value = event.break_start_time ?? '';
        if (bEnd)   bEnd.value   = event.break_end_time   ?? '';

        // Faculty rows
        if (typeof window.clearModalFacultyRows === 'function') window.clearModalFacultyRows();
        const facultyDetails = Array.isArray(event.faculty_details) && event.faculty_details.length
            ? event.faculty_details
            : (Array.isArray(event.Faculty_feedback) && event.Faculty_feedback.length
                ? event.Faculty_feedback
                : null);
        if (facultyDetails && facultyDetails.length && typeof window.addModalFacultyRow === 'function') {
            facultyDetails.forEach(row => window.addModalFacultyRow(row));
        } else if (typeof window.addModalFacultyRow === 'function') {
            // Fallback: one row per faculty_master entry
            const fIds = Array.isArray(event.faculty_master) ? event.faculty_master : [];
            fIds.forEach(fId => window.addModalFacultyRow({ faculty_pk: fId, faculty_type: event.faculty_type, role: '', feedback: 'none' }));
        }

        // Trigger dependent loads (await group types to ensure it completes)
        await this.loadGroupTypesForEdit(event);
        this.loadSubjectNamesForEdit(event);

        // Store current event ID
        this.currentEventId = event.pk;
        await this.updateinternal_faculty(event.faculty_type);
        if (event.faculty_type == 2) {
            await this.setInternalFaculty(event.internal_faculty);
        }
    }
async updateinternal_faculty(facultyType) {
    
// console.log(facultyType + 'kkkkk');
        switch (facultyType) {
            case '1': // Internal
                console.log('internal');
              internalFacultyDiv.style.display = 'none';
                break;
            case '2': // Guest
                  console.log('guest');
               internalFacultyDiv.style.display = 'block';
                break;
            default: // Research/Other
            console.log('rtyuio');
                internalFacultyDiv.style.display = 'block';

        }
    }
   async setInternalFaculty_bkp(internalFacultyIds) {

    if (!internalFacultyIds) return;

    // Agar CSV string aa rahi ho
    if (typeof internalFacultyIds === 'string') {
        internalFacultyIds = internalFacultyIds.split(',').map(id => id.trim());
    }

    const select = document.getElementById('internal_faculty');

    Array.from(select.options).forEach(option => {
        option.selected = internalFacultyIds.includes(option.value);
    });
// console.log(internalFacultyIds);
// console.log([...select.options].map(o => o.value));

    // Agar Choices.js / Select2 use kar rahe ho
    select.dispatchEvent(new Event('change'));
}
async setInternalFaculty(internalFacultyIds) {

    if (!internalFacultyIds) return;

    // ✅ FIX 1: agar JSON string aa rahi ho
    if (typeof internalFacultyIds === 'string') {

        internalFacultyIds = internalFacultyIds.trim();

        // JSON array string: '["23","67"]'
        if (internalFacultyIds.startsWith('[')) {
            internalFacultyIds = JSON.parse(internalFacultyIds);
        } 
        // normal CSV: '23,67'
        else {
            internalFacultyIds = internalFacultyIds.split(',').map(id => id.trim());
        }
    }

    // ✅ FIX 2: force string comparison
    internalFacultyIds = internalFacultyIds.map(id => String(id));

    const select = document.getElementById('internal_faculty');

    Array.from(select.options).forEach(option => {
        option.selected = internalFacultyIds.includes(String(option.value));
    });

    // console.log(internalFacultyIds);           // ["23","67"]
    // console.log([...select.options].map(o => o.value));

    select.dispatchEvent(new Event('change'));
}

    async loadGroupTypesForEdit(event) {
        // Set selected group names for edit
        try {
            const parsed = JSON.parse(event.group_name || '[]');
            // Ensure all values are converted to strings for consistent comparison
            this.selectedGroupNames = Array.isArray(parsed) ? parsed.map(String) : parsed;
        } catch {
            this.selectedGroupNames = [];
        }

        // Store the group_type value to set after loading
        const groupTypeValue = event.course_group_type_master ? String(event.course_group_type_master) : null;

        // Load group types first
        const courseId = document.getElementById('Course_name').value;
        if (!courseId) return;

        try {
            const response = await fetch(`${CalendarConfig.api.groupTypes}?course_id=${courseId}`);
            const data = await response.json();

            // Populate group types dropdown and store grouped data for later use
            const groupedData = this.populateGroupTypes(data);

            // Set the group_type value after dropdown is populated
            if (groupTypeValue) {
                const groupTypeSelect = document.getElementById('group_type');
                
                // Try to find matching value (handle both string and number comparisons)
                let matchingValue = null;
                for (let option of groupTypeSelect.options) {
                    if (option.value === groupTypeValue || 
                        option.value === String(groupTypeValue) || 
                        String(option.value) === String(groupTypeValue)) {
                        matchingValue = option.value;
                        break;
                    }
                }
                
                if (matchingValue) {
                    groupTypeSelect.value = matchingValue;
                    if (window.calendarModalChoices?.syncById) {
                        window.calendarModalChoices.syncById('group_type');
                    }
                    
                    // Use the grouped data to populate checkboxes directly with selected values
                    const groups = groupedData[matchingValue] || [];
                    this.populateGroupCheckboxes(groups);
                } else {
                    console.warn('Group type value not found in dropdown:', groupTypeValue);
                }
            }
        } catch (error) {
            console.error('Error loading group types for edit:', error);
        }
    }

    loadSubjectNamesForEdit(event) {
        // Trigger subject module change
        document.getElementById('subject_module').dispatchEvent(new Event('change'));

        // Set subject name after a delay (wait for AJAX)
        setTimeout(() => {
            document.getElementById('subject_name').value = event.subject_master_pk;
            if (window.calendarModalChoices?.syncById) {
                window.calendarModalChoices.syncById('subject_name');
            }
        }, 300);
    }

    confirmDelete() {
        const eventId = document.getElementById('calHoverDeleteBtn')?.dataset.id
            || document.getElementById('deleteEventBtn')?.dataset.id
            || this.currentEventId;

        // Show confirmation modal
        const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal'));
        document.getElementById('confirmAction').onclick = () => this.deleteEvent(eventId);
        confirmModal.show();
    }

    async deleteEvent(eventId) {
        try {
            const response = await fetch(CalendarConfig.api.delete.replace('EVENT_ID', eventId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) throw new Error('Delete failed');

            this.showNotification('Event deleted successfully', 'success');

            // Close modals and refresh — eventDetails may not have an instance when deleting from the hover card
            bootstrap.Modal.getInstance(document.getElementById('eventDetails'))?.hide();
            bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
            this.calendar.refetchEvents();

        } catch (error) {
            this.showNotification('Delete failed', 'danger');
            console.error('Delete error:', error);
        }
    }

    navigateWeek(offset) {
        if (offset === 0) {
            // Reset to current week
            this.listViewWeekOffset = 0;
        } else {
            // Navigate forward or backward
            this.listViewWeekOffset += offset;
        }

        // Reload the list view with the new week
        this.loadListView();
    }

    getEventsForWeek(events, weekOffset) {
        // Calculate the start date of the week based on offset, via the one shared
        // definition (F-020) rather than a private re-derivation of it.
        const today = new Date();
        const weekStart = this.mondayOf(today);

        // Apply week offset
        weekStart.setDate(weekStart.getDate() + (weekOffset * 7));

        // Set week end (Sunday) - adjust to Friday if you want only weekdays
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6); // Monday to Sunday

        // Compare whole calendar days. eventLocalDate() reads a bare "YYYY-MM-DD" as a
        // LOCAL day; the Date constructor would read it as UTC midnight and shift the row
        // to the previous day at a negative UTC offset, moving it into the wrong week.
        const startDateObj = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
        const endDateObj = new Date(weekEnd.getFullYear(), weekEnd.getMonth(), weekEnd.getDate());

        // Filter events that fall within this week
        return (events || []).filter(event => {
            const eventDateObj = this.eventLocalDate(event);
            if (!eventDateObj) return false;

            return eventDateObj >= startDateObj && eventDateObj <= endDateObj;
        });
    }

    /**
     * The range to ask the feed for: the displayed range with FullCalendar's hidden-day
     * trim UNDONE. Returns { start, end } as local dates, end INCLUSIVE.
     *
     * Why this exists (F-021). DateProfileGenerator runs the range through
     * trimHiddenDays(), which skips hidden days inward from both ends, so what arrives at
     * fetchEvents() covers only the days that are currently VISIBLE - and this feed is what
     * revealWeekendsForData() uses to decide which days are visible. In timeGridWeek both
     * weekend days sit at the EDGES of a one-week range, so while they are hidden they are
     * never requested, the rule never sees a weekend row, the column never opens, and the
     * session is not rendered at all. Once shut, the columns could not reopen.
     *
     * How the trim is undone WITHOUT asking the calendar: a week or month grid always
     * renders a whole number of weeks. A span that is not a multiple of 7 is therefore a
     * range trimHiddenDays() has eaten days off the ends of, and widening to the enclosing
     * weeks is that trim undone rather than a guess. A span that is already a multiple of 7
     * is returned exactly as it arrived, so when nothing is hidden this function changes
     * nothing. Measured against the shipped bundle on 2026-09-20:
     *
     *   timeGridWeek  hiddenDays [0,6] -> 2026-09-14..09-18 (span 5)  -> widened to 09-13..09-19
     *   timeGridWeek  hiddenDays []    -> 2026-09-13..09-19 (span 7)  -> unchanged
     *   dayGridMonth  hiddenDays [0,6] -> 2026-08-31..10-09 (span 40) -> widened to 08-30..10-10
     *   dayGridMonth  hiddenDays []    -> 2026-08-30..10-10 (span 42) -> unchanged
     *
     * This deliberately does NOT depend on this.calendar: FullCalendar calls the events
     * function while the Calendar is still being CONSTRUCTED, so on the first fetch of a
     * page load `this.calendar` is still undefined. A first version of this fix read the
     * view's untrimmed currentRange, which is correct but unavailable exactly then - so the
     * first fetch stayed trimmed and the latch survived it. currentRange is still used as a
     * second opinion when a calendar happens to be there.
     *
     * The span >= 5 guard keeps a single-day view - configured under `views`, though not
     * reachable from this toolbar - from being widened into a whole week.
     */
    feedRange(info) {
        if (!info || !info.start || !info.end) return null;

        const MS_PER_DAY = 24 * 60 * 60 * 1000;
        const dayOf = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());

        let start = dayOf(info.start);
        let endExclusive = dayOf(info.end);

        // NOTE: the view's untrimmed currentRange is deliberately NOT consulted here.
        // During navigation this.calendar.view still reports the PREVIOUS period while the
        // feed for the new one is already being fetched, so unioning with it reached a whole
        // week backwards - measured: moving to the week of 2026-09-20 requested
        // 2026-09-13..2026-09-26. That is F-019's defect class returning by another route: a
        // session in the PRECEDING week would open a weekend column inside this one. The
        // span rule below needs nothing but the range it was handed.

        const span = Math.round((endExclusive - start) / MS_PER_DAY);

        const end = new Date(endExclusive);
        end.setDate(end.getDate() - 1);

        if (span >= 5 && span % 7 !== 0) {
            const firstDay = (this.calendar && typeof this.calendar.getOption === 'function')
                ? (this.calendar.getOption('firstDay') || 0)
                : 0;
            start.setDate(start.getDate() - ((start.getDay() - firstDay + 7) % 7));
            end.setDate(end.getDate() + ((firstDay + 6 - end.getDay() + 7) % 7));
        }

        return { start, end };
    }

    /** Format a Date as YYYY-MM-DD (local). */
    toYmd(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /** Monday of the week containing the given date. */
    mondayOf(date) {
        const dayOfWeek = date.getDay(); // 0=Sun..6=Sat
        const diff = date.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
        return new Date(date.getFullYear(), date.getMonth(), diff);
    }

    /** Monday (YYYY-MM-DD) of the week currently shown in the list view. */
    currentListWeekStartYmd() {
        const weekStart = this.mondayOf(new Date());
        weekStart.setDate(weekStart.getDate() + (this.listViewWeekOffset * 7));
        return this.toYmd(weekStart);
    }

    /**
     * Resolve the week to export: the list-view week when that view is open,
     * otherwise the week containing the calendar's currently displayed date.
     */
    resolveExportWeekStartYmd() {
        const listViewEl = document.getElementById('eventListView');
        if (listViewEl && !listViewEl.classList.contains('d-none')) {
            return this.currentListWeekStartYmd();
        }
        if (this.calendar && typeof this.calendar.getDate === 'function') {
            return this.toYmd(this.mondayOf(this.calendar.getDate()));
        }
        return this.toYmd(this.mondayOf(new Date()));
    }

    /** Build week_start + course_id (+ download) params for the current view. */
    weeklyExportParams(download) {
        const params = new URLSearchParams();
        params.append('week_start', this.resolveExportWeekStartYmd());
        if (this.selectedCourseId) {
            params.append('course_id', this.selectedCourseId);
        }
        if (download) {
            params.append('download', '1');
        }
        return params;
    }

    /** Open the whole-week timetable PDF for the current week + course filter. */
    openWeeklyTimetablePdf(download) {
        const params = this.weeklyExportParams(false); // no download flag — preview handles it
        window.open(`${CalendarConfig.api.weeklyTimetablePreview}?${params.toString()}`, '_blank', 'noopener');
    }

    /** Open the academic time table preview page for the calendar's visible range + course filter. */
    openTimetablePdf(download) {
        const params = new URLSearchParams();

        if (this.calendar) {
            const view = this.calendar.view;
            // Use LOCAL date strings (toYmd). toISOString() converts to UTC, which
            // for positive-offset timezones (e.g. IST +5:30) rolls local midnight
            // back to the previous day — shifting the export range a day earlier so
            // it starts on a Sunday and pulls the adjacent Mon–Sun week into the PDF.
            if (view?.activeStart) {
                params.append('start', this.toYmd(view.activeStart));
            }
            if (view?.activeEnd) {
                const end = new Date(view.activeEnd);
                end.setDate(end.getDate() - 1);
                params.append('end', this.toYmd(end));
            }
        }

        if (this.selectedCourseId) {
            params.append('course_id', this.selectedCourseId);
        }

        // Always open the preview page — download button is on the preview page itself.
        window.open(`${CalendarConfig.api.timetablePreview}?${params.toString()}`, '_blank', 'noopener');
    }

    /** Open the Course Information / Faculty-for-the-week PDF for the current week + course filter. */
    openWeeklyInfoPdf(download) {
        window.open(`${CalendarConfig.api.weeklyInfoPdf}?${this.weeklyExportParams(download).toString()}`, '_blank', 'noopener');
    }

    /** Open the info-sheet editor modal, prefilled for the current course + week. */
    async openWeeklyInfoEditor() {
        if (!this.selectedCourseId) {
            this.showNotification('Please select a course first to edit its info-sheet details.', 'warning');
            return;
        }
        const weekStart = this.resolveExportWeekStartYmd();
        const alertEl = document.getElementById('weeklyInfoAlert');
        alertEl?.classList.add('d-none');

        try {
            const url = `${CalendarConfig.api.weeklyInfoMeta}?course_id=${this.selectedCourseId}&week_start=${weekStart}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            const data = await res.json();
            if (!res.ok && res.status !== 403) {
                throw new Error(data.error || 'Failed to load details.');
            }
            if (res.status === 403 || !data.can_edit) {
                this.showNotification('You can edit the info sheet only for courses you coordinate.', 'warning');
                return;
            }

            document.getElementById('wi_course_id').value = data.course_id;
            document.getElementById('wi_week_start').value = data.week_start;
            document.getElementById('wi_director').value = data.director_name || '';
            document.getElementById('wi_joint_director').value = data.joint_director_name || '';
            document.getElementById('wi_participants_profile').value = data.participants_profile || '';
            document.getElementById('wi_sheet_title').value = data.sheet_title || '';
            document.getElementById('wi_mention_of_week').value = data.mention_of_week || '';

            document.getElementById('wi_venue_line').value = data.venue_line || '';
            document.getElementById('wi_outdoor').value = data.outdoor_activities || '';
            document.getElementById('wi_signatory_name').value = data.signatory_name || '';
            document.getElementById('wi_signatory_designation').value = data.signatory_designation || '';
            document.getElementById('wi_signatory_date').value = (data.signatory_date || '').slice(0, 10);

            this.renderWeeklyInfoNotes(data.notes || []);
            this.renderWeeklyInfoLanguages(data.language_venues || []);
            this.renderWeeklyInfoVenues(data.venue_legend || []);
            const legendOrder = document.getElementById('wi_faculty_legend_order');
            legendOrder.value = (data.faculty_legend_order || []).join(', ');
            legendOrder.placeholder = (data.faculty_codes || []).join(', ');
            this.renderWeeklyInfoCounsellors(data.counsellors || [], data.counsellor_meta || {});
            this.renderWeeklyInfoSpeakers(data.speakers || [], data.guest_moderators || {});

            const course = (this.courses || []).find(c => c.pk == data.course_id);
            document.getElementById('weeklyInfoContext').textContent =
                `${course ? course.course_name + ' — ' : ''}Week starting ${data.week_start}`;

            new bootstrap.Modal(document.getElementById('weeklyInfoModal')).show();
        } catch (err) {
            this.showNotification(err.message || 'Failed to load info-sheet details.', 'danger');
        }
    }

    /** One removable text input per note. */
    renderWeeklyInfoNotes(notes) {
        document.getElementById('wiNotesList').innerHTML = '';
        (notes.length ? notes : ['']).forEach(note => this.addWeeklyInfoNoteRow(note));
    }

    addWeeklyInfoNoteRow(value = '') {
        const row = document.createElement('div');
        row.className = 'input-group input-group-sm';
        row.innerHTML = `
            <input type="text" class="form-control" data-wi-note maxlength="1000" placeholder="Note text…">
            <button type="button" class="btn btn-outline-danger" data-wi-remove title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>`;
        row.querySelector('[data-wi-note]').value = value || '';
        row.querySelector('[data-wi-remove]').addEventListener('click', () => row.remove());
        document.getElementById('wiNotesList').appendChild(row);
    }

    renderWeeklyInfoLanguages(rows) {
        document.getElementById('wiLanguageList').innerHTML = '';
        (rows.length ? rows : [{ language: '', venue: '' }]).forEach(r => this.addWeeklyInfoLanguageRow(r));
    }

    addWeeklyInfoLanguageRow(row = { language: '', venue: '' }) {
        const el = document.createElement('div');
        el.className = 'input-group input-group-sm';
        el.innerHTML = `
            <span class="input-group-text">Language</span>
            <input type="text" class="form-control" data-wi-lang maxlength="100" placeholder="e.g. Hindi">
            <span class="input-group-text">Venue</span>
            <input type="text" class="form-control" data-wi-lang-venue maxlength="200" placeholder="e.g. SR-A & B (Karmashila)">
            <button type="button" class="btn btn-outline-danger" data-wi-remove title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>`;
        el.querySelector('[data-wi-lang]').value = row.language || '';
        el.querySelector('[data-wi-lang-venue]').value = row.venue || '';
        el.querySelector('[data-wi-remove]').addEventListener('click', () => el.remove());
        document.getElementById('wiLanguageList').appendChild(el);
    }

    renderWeeklyInfoVenues(rows) {
        document.getElementById('wiVenueList').innerHTML = '';
        rows.forEach(r => this.addWeeklyInfoVenueRow(r));
    }

    addWeeklyInfoVenueRow(row = { abbreviation: '', name: '' }) {
        const el = document.createElement('div');
        el.className = 'input-group input-group-sm';
        el.innerHTML = `
            <input type="text" class="form-control" style="max-width: 7rem;" data-wi-venue-abbr maxlength="20" placeholder="e.g. VH">
            <input type="text" class="form-control" data-wi-venue-name maxlength="200" placeholder="e.g. Vivekanand Hall (Aadharshila Building)">
            <button type="button" class="btn btn-outline-danger" data-wi-remove title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>`;
        el.querySelector('[data-wi-venue-abbr]').value = row.abbreviation || '';
        el.querySelector('[data-wi-venue-name]').value = row.name || '';
        el.querySelector('[data-wi-remove]').addEventListener('click', () => el.remove());
        document.getElementById('wiVenueList').appendChild(el);
    }

    /** Counsellors come from the Counsellor Groups; label, cadre wording and venue are editable. */
    renderWeeklyInfoCounsellors(counsellors, meta) {
        const body = document.getElementById('wiCounsellorRows');
        body.innerHTML = '';
        if (!counsellors.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-secondary small">No Counsellor Groups mapped for this course.</td></tr>';
            return;
        }
        const savedOrder = c => Number((meta[c.faculty_pk] || meta[String(c.faculty_pk)] || {}).order) || 999;
        [...counsellors].sort((a, b) => savedOrder(a) - savedOrder(b)).forEach(c => {
            const saved = meta[c.faculty_pk] || meta[String(c.faculty_pk)] || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="number" class="form-control form-control-sm" data-wi-c-order min="1" max="99"></td>
                <td class="small">${this.escapeHtml(c.name)}</td>
                <td><input type="text" class="form-control form-control-sm" data-wi-c-cadres maxlength="200"></td>
                <td><input type="text" class="form-control form-control-sm" data-wi-c-label maxlength="60"></td>
                <td><input type="text" class="form-control form-control-sm" data-wi-c-venue maxlength="120" placeholder="e.g. SR- I"></td>`;
            tr.dataset.facultyPk = c.faculty_pk;
            const cadres = tr.querySelector('[data-wi-c-cadres]');
            cadres.placeholder = c.cadres || '';
            cadres.value = saved.cadres || '';
            const label = tr.querySelector('[data-wi-c-label]');
            label.placeholder = c.abbreviation || 'e.g. JD(SW)';
            label.value = saved.label || '';
            tr.querySelector('[data-wi-c-venue]').value = saved.venue || '';
            tr.querySelector('[data-wi-c-order]').value = saved.order || '';
            body.appendChild(tr);
        });
    }

    renderWeeklyInfoSpeakers(speakers, moderators) {
        const body = document.getElementById('wiGuestRows');
        body.innerHTML = '';
        if (!speakers.length) {
            body.innerHTML = '<tr><td colspan="2" class="text-secondary small">No speakers scheduled this week.</td></tr>';
            return;
        }
        speakers.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="small">${this.escapeHtml(s.name)}${s.code ? ' <span class="text-secondary">(' + this.escapeHtml(s.code) + ')</span>' : ''}${s.guest ? '' : ' <span class="badge text-bg-light border">In-house</span>'}</td>
                <td><input type="text" class="form-control form-control-sm" data-wi-mod maxlength="200" placeholder="e.g. T Bhuvaneshram, B02"></td>`;
            tr.dataset.facultyPk = s.faculty_pk;
            tr.querySelector('[data-wi-mod]').value =
                moderators[s.faculty_pk] || moderators[String(s.faculty_pk)] || '';
            body.appendChild(tr);
        });
    }

    /**
     * Escape for element content AND quoted attribute values. renderListEvent() puts
     * the result inside data-group="…", aria-label="…" and title="…", so quotes must
     * be escaped too - a textContent/innerHTML round trip leaves them as they are.
     */
    escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    /**
     * Collect the info-sheet form into the payload the API expects.
     *
     * Built by hand rather than from FormData: the repeatable rows would collapse
     * to their last value through Object.fromEntries, and the counsellor and
     * moderator maps have to be keyed by faculty pk.
     */
    collectWeeklyInfoPayload() {
        const val = id => (document.getElementById(id)?.value ?? '').trim();
        const map = (rows, pick) => {
            const out = {};
            rows.forEach(tr => {
                if (!tr.dataset.facultyPk) return;
                const value = pick(tr);
                if (value !== null) out[tr.dataset.facultyPk] = value;
            });
            return out;
        };

        return {
            course_id: val('wi_course_id'),
            week_start: val('wi_week_start'),

            director_name: val('wi_director'),
            joint_director_name: val('wi_joint_director'),
            participants_profile: val('wi_participants_profile'),
            sheet_title: val('wi_sheet_title'),
            mention_of_week: val('wi_mention_of_week'),

            venue_line: val('wi_venue_line'),
            notes: [...document.querySelectorAll('#wiNotesList [data-wi-note]')]
                .map(i => i.value.trim()).filter(Boolean),

            outdoor_activities: val('wi_outdoor'),
            language_venues: [...document.querySelectorAll('#wiLanguageList .input-group')]
                .map(el => ({
                    language: el.querySelector('[data-wi-lang]').value.trim(),
                    venue: el.querySelector('[data-wi-lang-venue]').value.trim(),
                }))
                .filter(r => r.language !== ''),
            venue_legend: [...document.querySelectorAll('#wiVenueList .input-group')]
                .map(el => ({
                    abbreviation: el.querySelector('[data-wi-venue-abbr]').value.trim(),
                    name: el.querySelector('[data-wi-venue-name]').value.trim(),
                }))
                .filter(r => r.abbreviation !== ''),

            counsellor_meta: map([...document.querySelectorAll('#wiCounsellorRows tr')], tr => {
                const label = tr.querySelector('[data-wi-c-label]')?.value.trim() ?? '';
                const venue = tr.querySelector('[data-wi-c-venue]')?.value.trim() ?? '';
                const cadres = tr.querySelector('[data-wi-c-cadres]')?.value.trim() ?? '';
                const order = tr.querySelector('[data-wi-c-order]')?.value.trim() ?? '';
                return (label || venue || cadres || order) ? { label, venue, cadres, order } : null;
            }),

            guest_moderators: map([...document.querySelectorAll('#wiGuestRows tr')], tr => {
                const name = tr.querySelector('[data-wi-mod]')?.value.trim() ?? '';
                return name || null;
            }),

            faculty_legend_order: val('wi_faculty_legend_order'),
            signatory_name: val('wi_signatory_name'),
            signatory_designation: val('wi_signatory_designation'),
            signatory_date: val('wi_signatory_date'),
        };
    }

    /** Persist info-sheet details. */
    async saveWeeklyInfo(e) {
        e.preventDefault();
        const alertEl = document.getElementById('weeklyInfoAlert');
        const saveBtn = document.getElementById('wiSaveBtn');
        alertEl.classList.add('d-none');
        saveBtn.disabled = true;

        try {
            const res = await fetch(CalendarConfig.api.weeklyInfoSave, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.collectWeeklyInfoPayload())
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Save failed.'));
            }
            bootstrap.Modal.getInstance(document.getElementById('weeklyInfoModal'))?.hide();
            this.showNotification('Info-sheet details saved.', 'success');
        } catch (err) {
            alertEl.textContent = err.message || 'Save failed.';
            alertEl.className = 'alert alert-danger';
        } finally {
            saveBtn.disabled = false;
        }
    }

    async loadListView() {
        try {
            // Calculate week start date based on offset. This runs BEFORE the fetch, because
            // the feed has to be requested for the week being DRAWN. With no start/end the
            // endpoint defaults to the CURRENT CALENDAR MONTH
            // (CalendarController::fullCalendarDetails), while this view pages by week without
            // limit - so every week outside that month came back empty, not because it was
            // empty but because it was never asked for, and the weekend rule then hid Saturday
            // and Sunday for a week it had no data on.
            // F-020: the same Monday-of-week rule as getEventsForWeek() and
            // currentListWeekStartYmd(), via mondayOf() rather than a fourth copy of it.
            const today = new Date();
            const weekStart = this.mondayOf(today);
            weekStart.setDate(weekStart.getDate() + (this.listViewWeekOffset * 7));
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);

            // Build URL with the displayed week's bounds and the course filter.
            // The endpoint also filters END_DATE <= end; no timetable row spans more than one
            // day (verified 2026-09-20: 0 of 1005 rows have DATE(START_DATE) <> DATE(END_DATE)),
            // so these bounds cannot drop a row that START_DATE >= start admits.
            let url = CalendarConfig.api.events;
            const params = new URLSearchParams();
            params.append('start', this.toYmd(weekStart));
            params.append('end', this.toYmd(weekEnd));
            if (this.selectedCourseId) {
                params.append('course_id', this.selectedCourseId);
            }
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Week buttons can be clicked faster than the feed answers: only the latest
            // request may draw, or a slower earlier week overwrites the one on screen.
            const requestNo = (this.listViewRequestNo = (this.listViewRequestNo || 0) + 1);

            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const events = await response.json();
            if (requestNo !== this.listViewRequestNo) {
                return;
            }

            // Update week display in header (use same calculation as updateCurrentWeek)
            const date = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
            const jan4 = new Date(date.getFullYear(), 0, 4);
            const monday = new Date(jan4);
            monday.setDate(monday.getDate() - monday.getDay() + 1);
            const timeDiff = date - monday;
            const weekDiff = Math.floor(timeDiff / (7 * 24 * 60 * 60 * 1000));
            const weekNum = weekDiff + 1;

            const weekElement = document.getElementById('currentWeekNumber') || document.getElementById('currentWeek');
            if (weekElement) {
                // The course's own week when a course is chosen - "Week 08" as the printed
                // sheet numbers it - else the calendar week.
                const courseWeek = this.courseWeekNumber(weekStart);
                weekElement.textContent = courseWeek ? String(courseWeek).padStart(2, '0') : weekNum;
                weekElement.parentElement?.setAttribute('title', courseWeek ? 'Week of the course' : 'Calendar week');
            }

            // This week's events decide which weekend columns the header, the
            // body and the day cards render, so resolve them before drawing.
            // The feed reaching the list view is UNFILTERED (fetchEvents strips holidays,
            // this path does not), and the renderers below emit cells only for visible
            // days — so the column is decided by what is actually PRESENT, or a weekend day
            // carrying only a holiday would lose that holiday along with its column.
            // Note what that means: the "a holiday never opens a weekend column" rule does
            // NOT apply here, and cannot. It governs the FullCalendar grid, which is fed the
            // holiday-filtered feed. See weekendDisplayForRendering().
            const filteredEvents = this.getEventsForWeek(events, this.listViewWeekOffset);
            this.weekendDisplay = this.weekendDisplayForRendering(filteredEvents);

            // Update table header with week dates
            this.updateTableHeader(weekStart);

            // Debug: Log the week being displayed
            console.log('List view - Week offset:', this.listViewWeekOffset);
            console.log('Week start:', weekStart);
            console.log('Total events:', events.length);
            console.log('Filtered events for this week:', filteredEvents.length);
            this.renderListView(filteredEvents);
            this.renderWeekCards(events, weekStart);
            this.updateWeekRangeText(weekStart);
            this.updatePortalToolbarTitle();
        } catch (error) {
            console.error('Error loading list view:', error);
        }
    }

    updateTableHeader(weekStart) {
        // Get the table and its header
        const table = document.getElementById('timetableTable');
        if (!table) {
            console.warn('Table #timetableTable not found');
            return;
        }

        const thead = table.querySelector('thead tr');
        if (!thead) {
            console.warn('Table header not found');
            return;
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const keys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const headers = thead.querySelectorAll('th:not(.time-column)');
        const visibleDays = this.visibleWeekDayIndexes();
        const todayYmd = this.toYmd(new Date());

        headers.forEach((header, index) => {
            header.dataset.day = keys[index];
            if (!visibleDays.includes(index)) {
                header.classList.add('d-none');
                return;
            }
            header.classList.remove('d-none');

            const date = new Date(weekStart);
            date.setDate(date.getDate() + index);
            const dateStr = date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            header.classList.toggle('is-today', this.toYmd(date) === todayYmd);
            header.innerHTML = `<span class="tt-th-day">${days[index]}</span><span class="tt-th-date">${dateStr}</span>`;
        });
    }

    renderListView(events) {
        const tbody = document.getElementById('timetableBody');
        const dayKeys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const visibleDays = this.visibleWeekDayIndexes().map(index => dayKeys[index]);

        if (!events.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${visibleDays.length + 1}" class="tt-empty-cell">
                        <div class="ds-empty-state tt-empty">
                            <i class="bi bi-calendar-x" aria-hidden="true"></i>
                            <p class="mb-0">No sessions scheduled this week</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        // Group events by time slot, earliest first.
        const timeSlots = Object.entries(this.groupEventsByTime(events))
            .map(([time, dayEvents]) => {
                const all = Object.values(dayEvents).flat();
                const starts = all.map(e => this.isAllDayEvent(e) ? null : this.eventStartDateTime(e)).filter(Boolean);
                const ends = all.map(e => (!this.isAllDayEvent(e) && e.end) ? new Date(this.fixCalendarDateTimeString(e.end)) : null).filter(Boolean);
                const start = starts.length ? new Date(Math.min(...starts)) : null;
                // An end only when every session in the row ends together: a long session
                // on one day must not stretch the label of the whole row.
                const tod = d => d.getHours() * 60 + d.getMinutes();
                const sameEnd = ends.length === all.length && ends.every(d => tod(d) === tod(ends[0]));
                const end = sameEnd ? ends[0] : null;
                const sortKey = start ? start.getHours() * 60 + start.getMinutes() : -1;
                return { time, dayEvents, all, start, end, sortKey };
            })
            .sort((a, b) => a.sortKey - b.sortKey);

        const hhmm = d => d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });

        let html = '';
        timeSlots.forEach(slot => {
            const label = slot.start
                ? `<span class="tt-time-start">${hhmm(slot.start)}</span>${slot.end ? `<span class="tt-time-end">to ${hhmm(slot.end)}</span>` : ''}`
                : `<span class="tt-time-start">${this.escapeHtml(slot.time)}</span>`;

            // A slot that holds nothing but breaks is a band across the week, not a row of cells.
            const breaksOnly = slot.all.length && slot.all.every(e => this.isBreakEvent(e));
            if (breaksOnly) {
                const names = [...new Set(slot.all.map(e => e.title || 'Break'))].join(' · ');
                const icon = slot.all.some(e => /lunch/i.test(e.break_type || e.title || '')) ? 'bi-egg-fried' : 'bi-cup-hot';
                html += `
                <tr class="tt-break-row break-row">
                    <th scope="row" class="time-slot">${label}</th>
                    <td colspan="${visibleDays.length}" class="tt-break-band">
                        <i class="bi ${icon}" aria-hidden="true"></i>
                        <span>${this.escapeHtml(names)}</span>
                        ${slot.start && slot.end ? `<span class="tt-break-time">${hhmm(slot.start)} – ${hhmm(slot.end)}</span>` : ''}
                    </td>
                </tr>`;
                return;
            }

            html += `
                <tr>
                    <th scope="row" class="time-slot">${label}</th>
                    ${visibleDays.map(day => `
                        <td class="event-cell" data-day="${day}">
                            ${slot.dayEvents[day] ? this.renderListEvent(slot.dayEvents[day]) : ''}
                        </td>
                    `).join('')}
                </tr>
            `;
        });

        tbody.innerHTML = html;
        this.applyTimetableActiveDay();
    }

    /**
     * Week of the selected course containing weekStart, counted from the Monday of the
     * course's start week (week 1) - the numbering the printed timetable uses. Null with
     * no course chosen, no start date known, or a week before the course began.
     */
    courseWeekNumber(weekStart) {
        if (!this.selectedCourseId) return null;
        const archived = (typeof archivedCourses !== 'undefined' && Array.isArray(archivedCourses)) ? archivedCourses : [];
        const course = [...(this.courses || []), ...archived].find(c => String(c.pk) === String(this.selectedCourseId));
        if (!course || !course.start_year) return null;
        const [y, m, d] = String(course.start_year).slice(0, 10).split('-').map(Number);
        if (!y || !m || !d) return null;
        const courseMonday = this.mondayOf(new Date(y, m - 1, d));
        const weeks = Math.round((this.mondayOf(new Date(weekStart)) - courseMonday) / (7 * 864e5)) + 1;
        return weeks >= 1 ? weeks : null;
    }

    /** True for the break rows the feed sends alongside sessions. */
    isBreakEvent(event) {
        const ep = event.extendedProps || event;
        return ep.is_break === true || ep.type === 'break';
    }

    /**
     * Day navigation: one chip per visible day with its date and session count.
     * The chosen day is highlighted in the grid and, on a phone, is the column shown.
     */
    renderWeekCards(events, weekStart) {
        const container = document.querySelector('#weekCards .row');
        if (!container) return;

        const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        const keys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const byDay = new Map();

        // Boundaries: Monday 00:00 inclusive to the following Monday 00:00 EXCLUSIVE.
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 7);

        days.forEach((_, i) => {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            byDay.set(this.toYmd(d), { date: d, events: [] });
        });

        // The row's day is resolved with eventLocalDate(), not the Date constructor - see
        // eventLocalDate(): a bare all-day "YYYY-MM-DD" read as UTC lands on the wrong day.
        (events || []).forEach(evt => {
            const d = this.eventLocalDate(evt);
            if (!d) return;
            if (d < weekStart || d >= weekEnd) return;
            const key = this.toYmd(d);
            if (byDay.has(key)) byDay.get(key).events.push(evt);
        });

        const todayYmd = this.toYmd(new Date());
        const visible = this.visibleWeekDayIndexes();

        // Keep the chosen day when it is still in view; otherwise today, else the first day.
        const visibleKeys = visible.map(i => keys[i]);
        if (!visibleKeys.includes(this.timetableActiveDay)) {
            const todayIndex = visible.find(i => {
                const d = new Date(weekStart);
                d.setDate(d.getDate() + i);
                return this.toYmd(d) === todayYmd;
            });
            this.timetableActiveDay = keys[todayIndex !== undefined ? todayIndex : visible[0]];
        }

        container.innerHTML = '';
        visible.forEach(i => {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            const info = byDay.get(this.toYmd(d)) || { date: d, events: [] };
            const count = info.events.filter(e => !this.isBreakEvent(e)).length;
            const isToday = this.toYmd(d) === todayYmd;
            const fullStr = d.toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'tt-day' + (isToday ? ' is-today' : '');
            chip.dataset.day = keys[i];
            chip.setAttribute('role', 'tab');
            chip.setAttribute('aria-label', `${fullStr}, ${count} session${count !== 1 ? 's' : ''}${isToday ? ', today' : ''}`);
            chip.innerHTML = `
                <span class="tt-day-name">${days[i].slice(0, 3)}</span>
                <span class="tt-day-date">${d.getDate()}</span>
                <span class="tt-day-month">${d.toLocaleDateString('en-IN', { month: 'short' })}</span>
                <span class="tt-day-count">${count} session${count !== 1 ? 's' : ''}</span>
            `;
            chip.addEventListener('click', () => this.setTimetableActiveDay(keys[i], true));
            container.appendChild(chip);
        });

        this.applyTimetableActiveDay();
    }

    /** Choose the day the timetable focuses on (the only column shown on a phone). */
    setTimetableActiveDay(dayKey, scroll = false) {
        this.timetableActiveDay = dayKey;
        this.applyTimetableActiveDay();
        if (scroll) {
            const th = document.querySelector(`#timetableTable thead th[data-day="${dayKey}"]`);
            th?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    applyTimetableActiveDay() {
        const key = this.timetableActiveDay;
        const table = document.getElementById('timetableTable');
        if (table && key) {
            table.dataset.activeDay = key;
            table.querySelectorAll('[data-day]').forEach(el => el.classList.toggle('tt-col-active', el.dataset.day === key));
            table.querySelectorAll('tbody tr').forEach(tr => {
                const cell = tr.querySelector(`td.event-cell[data-day="${key}"]`);
                tr.classList.toggle('tt-row-empty-day', !!cell && !cell.querySelector('.tt-card'));
            });
        }
        document.querySelectorAll('#weekCards .tt-day').forEach(chip => {
            const on = chip.dataset.day === key;
            chip.classList.toggle('is-active', on);
            chip.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    updateWeekRangeText(weekStart) {
        const el = document.getElementById('weekRangeText');
        if (!el) return;
        const startStr = new Date(weekStart).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
        const end = new Date(weekStart); end.setDate(end.getDate() + 6);
        const endStr = end.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
        el.innerHTML = `<i class="bi bi-calendar-week me-2" aria-hidden="true"></i>${startStr} – ${endStr}`;
    }

    /** Group events into { timeSlot: { dayName: [events] } } for the weekly timetable. */
    groupEventsByTime(events) {
        const groups = {};
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        (events || []).forEach(event => {
            // eventWeekday() reads a bare "YYYY-MM-DD" as a LOCAL day. Reading it through
            // the Date constructor gives UTC midnight, which lands on the previous day at
            // a negative UTC offset and files the row under the wrong column - and under a
            // different day than the one the weekend rule used to decide the columns.
            const day = this.eventWeekday(event);
            if (isNaN(day)) return;

            // `event.start` is always truthy for a real feed row, so the old
            // `event.start ? <time> : 'All Day'` test could never reach 'All Day'; an
            // all-day row was labelled with whatever time its bare date happened to parse
            // to - "05:30 am" at IST.
            const time = this.isAllDayEvent(event)
                ? 'All Day'
                : this.eventStartDateTime(event).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

            if (!groups[time]) groups[time] = {};

            const dayName = dayNames[day];
            if (!groups[time][dayName]) {
                groups[time][dayName] = [];
            }

            groups[time][dayName].push(event);
        });

        return groups;
    }

    /** The event's start as a Date, normalising the feed's non-ISO date-time forms. */
    eventStartDateTime(event) {
        const start = event && event.start;
        if (start instanceof Date) return start;
        return new Date(this.fixCalendarDateTimeString(start));
    }

    renderListEvent(events) {
        const arr = Array.isArray(events) ? events : [events];
        const esc = v => this.escapeHtml(v);
        return arr.map(event => {
            // List view fetches the raw JSON feed (flat objects), while
            // FullCalendar nests custom fields under extendedProps — support both.
            const ep = event.extendedProps || event;
            const isBreak = this.isBreakEvent(event);
            const groupName = ep.group_name || ep.group || '';
            const title = event.title || ep.topic || '';
            const faculty = String(ep.faculty_name || '').replace(/\s+/g, ' ').trim();
            const venue = ep.vanue || ep.venue_name || '';
            // An all-day row has no time of day: reading its bare "YYYY-MM-DD" through the
            // Date constructor gives UTC midnight, printed as "05:30 am" at IST. Label it the
            // way the timetable slot does rather than inventing a range from the parse.
            const isAllDay = this.isAllDayEvent(event);
            const fmt = d => d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
            const startTime = (!isAllDay && event.start) ? fmt(this.eventStartDateTime(event)) : '';
            const endTime = (!isAllDay && event.end) ? fmt(new Date(this.fixCalendarDateTimeString(event.end))) : '';
            const timeRange = isAllDay ? 'All Day' : (startTime && endTime ? `${startTime} – ${endTime}` : (ep.class_session || ''));
            // "A" / "B" / "A, B" / "Full Group" / "Group 1" from the feed. A card takes the
            // Group A or B colour only when that group alone attends.
            const groups = String(groupName).split(',').map(g => g.trim()).filter(Boolean);
            const letterOf = g => (g.match(/^(?:group\s*-?\s*)?([ab])$/i) || [])[1];
            const letters = groups.map(letterOf);
            const uniqueLetters = [...new Set(letters.filter(Boolean).map(l => l.toLowerCase()))].sort();
            const group = letters.every(Boolean) ? uniqueLetters.join('') : '';   // 'a', 'b' or 'ab'
            const groupLabel = groups.length && letters.every(Boolean)
                ? (groups.length === 1 ? `Group ${letters[0].toUpperCase()}` : `Groups ${letters.map(l => l.toUpperCase()).join(' & ')}`)
                : groups.join(', ');
            // Break rows ("break_1111") have no event to open.
            const openable = !isBreak && event.id !== undefined && !String(event.id).startsWith('break_');
            const label = [title, timeRange, faculty && `Faculty: ${faculty}`, venue && `Venue: ${venue}`, groupLabel && `For: ${groupLabel}`]
                .filter(Boolean).join(', ');

            return `
                <article class="tt-card ${isBreak ? 'tt-card--break' : ''} ${group ? `tt-card--group-${group}` : ''}"
                    data-group="${esc(groupName)}" ${openable ? `data-id="${esc(event.id)}" role="button" tabindex="0"` : ''}
                    aria-label="${esc(label)}" title="${esc(label)}">
                    <div class="tt-card-top">
                        ${timeRange ? `<span class="tt-card-time"><i class="bi bi-clock" aria-hidden="true"></i>${esc(timeRange)}</span>` : ''}
                        ${groupLabel ? `<span class="tt-card-group">${esc(groupLabel)}</span>` : ''}
                    </div>
                    <div class="tt-card-title">${esc(title)}</div>
                    ${faculty ? `<div class="tt-card-meta"><i class="bi bi-person" aria-hidden="true"></i><span>${esc(faculty)}</span></div>` : ''}
                    ${venue ? `<div class="tt-card-venue"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>${esc(venue)}</span></div>` : ''}
                </article>
            `;
        }).join('');
    }

    initializeScrollIndicators() {
        // Add scroll event listeners to table cells to show/hide scroll indicators
        const cells = document.querySelectorAll('.timetable-grid td.event-cell');
        
        cells.forEach(cell => {
            // Check if cell content exceeds max height
            if (cell.scrollHeight > cell.clientHeight) {
                cell.classList.add('has-scroll');
                
                // Add scroll event listener
                cell.addEventListener('scroll', function() {
                    const isScrolledToBottom = Math.abs(this.scrollHeight - this.clientHeight - this.scrollTop) < 5;
                    
                    if (isScrolledToBottom) {
                        this.classList.add('scrolled-bottom');
                    } else {
                        this.classList.remove('scrolled-bottom');
                    }
                });
                
                // Initial check
                const isScrolledToBottom = Math.abs(cell.scrollHeight - cell.clientHeight - cell.scrollTop) < 5;
                if (isScrolledToBottom) {
                    cell.classList.add('scrolled-bottom');
                }
            } else {
                cell.classList.remove('has-scroll', 'scrolled-bottom');
            }
        });
    }

    applyBreakLunchRowStyles() {
        const rows = document.querySelectorAll('#timetableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes('break')) row.classList.add('break-row');
            if (text.includes('lunch')) row.classList.add('lunch-row');
        });
    }

    convertTo24Hour(timeStr) {
        if (!timeStr) return '';

        const trimmed = String(timeStr).trim();
        if (/^\d{1,2}:\d{2}$/.test(trimmed)) {
            const [h, m] = trimmed.split(':');
            return `${String(parseInt(h, 10)).padStart(2, '0')}:${m}`;
        }

        const parts = trimmed.split(/\s+/);
        const time = parts[0];
        const modifier = (parts[1] || '').toUpperCase();
        let [hours, minutes] = time.split(':');

        hours = parseInt(hours, 10);
        if (modifier === 'PM' && hours !== 12) {
            hours += 12;
        } else if (modifier === 'AM' && hours === 12) {
            hours = 0;
        }

        return `${String(hours).padStart(2, '0')}:${minutes}`;
    }

    updateCurrentWeek() {
        // Calculate ISO week number for current date
        const today = new Date();
        const date = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        // January 4th is always in week 1 (ISO 8601 standard)
        const jan4 = new Date(date.getFullYear(), 0, 4);

        // Calculate the Monday of week containing Jan 4
        const monday = new Date(jan4);
        monday.setDate(monday.getDate() - monday.getDay() + 1);

        // Calculate difference in milliseconds and convert to weeks
        const timeDiff = date - monday;
        const weekDiff = Math.floor(timeDiff / (7 * 24 * 60 * 60 * 1000));
        const weekNum = weekDiff + 1;

        // Update the week number display
        const weekElement = document.getElementById('currentWeekNumber') || document.getElementById('currentWeek');
        if (weekElement) {
            weekElement.textContent = weekNum;
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.alert-notification');
        if (existing) existing.remove();

        // Create notification element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-notification position-fixed`;
        alert.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        alert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        document.body.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }

    setupAccessibility() {
        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal)?.hide();
                });
            }

            // Calendar navigation
            if (e.target.closest('.fc')) {
                switch (e.key) {
                    case 'ArrowLeft':
                        this.calendar.prev();
                        break;
                    case 'ArrowRight':
                        this.calendar.next();
                        break;
                    case 'Home':
                        this.calendar.today();
                        break;
                }
            }
        });

        // Focus trap for modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                const focusable = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable.length) focusable[0].focus();
            });
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded - Initializing calendar...');
    console.log('Calendar element exists:', !!document.getElementById('calendar'));
    console.log('Loading overlay exists:', !!document.getElementById('calendarLoadingOverlay'));
    initCourseFilter();

    const eventModalEl = document.getElementById('eventModal');
    if (eventModalEl) {
        const syncCloseCourseFilter = () => {
            closeCourseFilterDropdown();
            requestAnimationFrame(() => closeCourseFilterDropdown());
        };
        eventModalEl.addEventListener('show.bs.modal', syncCloseCourseFilter);
        eventModalEl.addEventListener('shown.bs.modal', syncCloseCourseFilter);
        eventModalEl.addEventListener('hidden.bs.modal', () => {
            releaseCourseFilterDropdownSuppression();
        });
    }

    document.getElementById('createEventButton')?.addEventListener(
        'pointerdown',
        () => {
            closeCourseFilterDropdown();
        },
        true
    );
    
    // Absolute fallback - hide loader after 3 seconds no matter what
    setTimeout(() => {
        const overlay = document.getElementById('calendarLoadingOverlay');
        if (overlay) {
            console.log('ABSOLUTE FALLBACK: Hiding loader after 3 seconds');
            overlay.style.display = 'none';
        }
    }, 3000);
    
    try {
        window.calendarManager = new CalendarManager();
        console.log('Calendar manager initialized successfully');
        initCalendarStatusTabs();
    } catch (error) {
        console.error('Error initializing calendar:', error);
        console.error('Error stack:', error.stack);
        
        // Hide loading overlay and show error message
        const loadingOverlay = document.getElementById('calendarLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.innerHTML = `
                <div class="text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-danger">Failed to Load Calendar</h5>
                    <p class="text-muted">Please refresh the page or contact support if the problem persists.</p>
                    <p class="text-muted small">Error: ${error.message}</p>
                    <button class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reload Page
                    </button>
                </div>
            `;
        }
    }
});

// Add ARIA live region for announcements
const liveRegion = document.createElement('div');
liveRegion.setAttribute('aria-live', 'polite');
liveRegion.setAttribute('aria-atomic', 'true');
liveRegion.className = 'visually-hidden';
document.body.appendChild(liveRegion);
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false,
        });
    });
</script>
@endif

@endsection
