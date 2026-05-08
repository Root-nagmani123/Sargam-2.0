@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable')

@section('setup_content')

@php
    // Debug: Check if courseMaster is available
    if (!isset($courseMaster) || $courseMaster->isEmpty()) {
        \Log::error('Calendar view: courseMaster is empty or not set');
    }
@endphp

<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
        :root {
        --primary: #004a93;
        --primary-color: #004a93;
        --primary-dark: #003366;
        --accent: #eef5ff;
        --bg-light: #f4f6f9;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border: #e5e7eb;
    }
        .course-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 2.75rem 1.5rem;
        border-radius: 1rem 1rem 1rem 1rem;
        text-align: center;
    }

    .course-header h1 {
        font-size: 1.85rem;
        font-weight: 600;
        color: #fff;
    }

    .course-header .badge {
        background: #ffffff;
        color: #000;
    }

    /* Responsive Design for Smaller Screens */
    @media (max-width: 768px) {
        .course-header {
            padding: 1.5rem 1rem;
        }

        .course-header h1 {
            font-size: 1.25rem;
        }

        .course-header p {
            font-size: 0.9rem;
        }
    }
    /* Accessibility improvements */
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Calendar styling */
.fc {
    font-size: 0.95rem;
}

.fc-daygrid-day {
    transition: background-color 0.2s ease;
    position: relative;
    overflow: visible !important;
    min-height: auto;
}

.fc-daygrid-day:hover {
    background-color: rgba(0, 74, 147, 0.03);
}

.fc-daygrid-day.fc-day-today {
    background-color: #CCDBE9 !important;
}

.fc-col-header-cell {
    background: var(--primary-color);
    color: #fff;
    font-weight: 600;
    padding: 1rem 0.5rem;
}

/* Ensure FullCalendar header text is white */
.fc .fc-col-header-cell-cushion,
.fc .fc-scrollgrid-section-header .fc-col-header-cell a,
.fc .fc-col-header-cell a {
    color: #ffffff !important;
}

.fc-event-card {
    padding: 1rem 1.1rem;
    border-radius: 0.875rem;
    margin: 0.4rem 0;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 6px solid var(--primary-color);
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.09), 0 2px 6px rgba(0, 0, 0, 0.06);
    white-space: normal;
    word-break: break-word;
    overflow: visible;
    position: relative;
    cursor: pointer;
    border-top-right-radius: 0.875rem;
    border-bottom-right-radius: 0.875rem;
    min-height: fit-content;
}

/* Choices.js + Bootstrap look for course filter */
.calendar-choices-bootstrap .choices__inner.form-select {
    background-color: var(--bs-body-bg);
    border: var(--bs-border-width) solid var(--bs-border-color);
    min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
    background-image: none !important;
    padding-inline-end: 2.25rem;
}

.calendar-choices-bootstrap .choices.is-focused .choices__inner.form-select,
.calendar-choices-bootstrap .choices.is-open .choices__inner.form-select {
    border-color: var(--bs-focus-border-color);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
}

.calendar-choices-bootstrap .choices__list--dropdown.dropdown-menu,
.calendar-choices-bootstrap .choices__list[aria-expanded].dropdown-menu {
    border: var(--bs-border-width) solid var(--bs-border-color);
}

.calendar-choices-bootstrap .choices {
    position: relative;
    z-index: 1200;
}

.calendar-choices-bootstrap .choices.is-open .choices__list--dropdown,
.calendar-choices-bootstrap .choices.is-open .choices__list[aria-expanded] {
    z-index: 1250;
}

/*
 * Dropdowns clipped / behind chrome: theme .page-wrapper { overflow-x: hidden }
 * forces overflow-y to behave like auto and clips portaled-out content. Lift the
 * control strip when open; relax page-wrapper only on this page (:has).
 */
#main-wrapper .page-wrapper:has(.calendar-admin-page) {
    overflow-x: clip;
    overflow-y: visible;
}

@supports not (overflow: clip) {
    #main-wrapper .page-wrapper:has(.calendar-admin-page) {
        overflow: visible;
    }
}

.control-panel:has(.choices.is-open) {
    position: relative;
    z-index: 10800;
}

.calendar-choices-bootstrap .choices.is-open {
    z-index: 10850;
}

.calendar-choices-bootstrap .choices.is-open .choices__list--dropdown,
.calendar-choices-bootstrap .choices.is-open .choices__list[aria-expanded] {
    z-index: 10860;
}

/* While Add/Edit Event modal is open, never show the course filter list above it */
body.calendar-suppress-course-filter-dropdown .calendar-choices-bootstrap .choices__list--dropdown,
body.calendar-suppress-course-filter-dropdown .calendar-choices-bootstrap .choices__list[aria-expanded] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    z-index: 0 !important;
}

body.calendar-suppress-course-filter-dropdown .control-panel {
    z-index: auto !important;
}

body.calendar-suppress-course-filter-dropdown .calendar-choices-bootstrap .choices,
body.calendar-suppress-course-filter-dropdown .calendar-choices-bootstrap .choices.is-open {
    z-index: auto !important;
}

.fc-event-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color) 0%, rgba(78, 115, 223, 0.5) 70%, transparent 100%);
    border-radius: 0.875rem 0.875rem 0 0;
    opacity: 0;
    transition: opacity 0.35s ease;
}

.fc-event-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 40px;
    height: 40px;
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.08) 0%, transparent 70%);
    border-radius: 0 0.875rem 0 0;
    pointer-events: none;
}

.fc-event-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 10px 32px rgba(0, 74, 147, 0.18), 0 6px 12px rgba(0, 0, 0, 0.12);
    background: linear-gradient(135deg, #ffffff 0%, #eef5ff 100%) !important;
    border-left-width: 7px;
}

.fc-event-card:hover::before {
    opacity: 1;
}

.fc-event-card:hover::after {
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.15) 0%, transparent 70%);
}

/* Event card content improvements */
.fc-event-card .event-title {
    font-weight: 700;
    font-size: 1.05rem;
    line-height: 1.5;
    margin-bottom: 0.6rem;
    color: #1f2937;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: visible;
    word-wrap: break-word;
    letter-spacing: -0.015em;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    min-height: fit-content;
}

.fc-event-card .event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem 1.1rem;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
    border-top: 1.5px solid rgba(78, 115, 223, 0.12);
    min-height: fit-content;
}

.fc-event-card .meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.875rem;
    color: #4b5563;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 0.25rem 0.5rem;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 6px;
    white-space: nowrap;
}

.fc-event-card .meta-item i {
    font-size: 1rem;
    opacity: 0.9;
}

.fc-event-card .meta-item--time i {
    color: #4e73df;
}

.fc-event-card .meta-item--venue i {
    color: #1cc88a;
}

.fc-event-card .meta-item--faculty i {
    color: #f6c23e;
}

.fc-event-card:hover .meta-item {
    color: #1f2937;
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
}

.fc-event-card:hover .meta-item i {
    opacity: 1;
}

/* Dense mode for days with many events */
.fc-daygrid-day.dense-day .fc-event-card {
    padding: 0.5rem 0.65rem;
    border-radius: 0.625rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.09);
    margin: 0.25rem 0;
    min-height: fit-content;
}

.fc-daygrid-day.dense-day .fc-event-card .event-title {
    font-size: 0.9rem;
    font-weight: 700;
    -webkit-line-clamp: 2;
    line-height: 1.4;
    margin-bottom: 0.4rem;
}

.fc-daygrid-day.dense-day .fc-event-card .event-meta { 
    margin-top: 0.35rem;
    padding-top: 0.35rem;
    gap: 0.4rem 0.6rem;
}

.fc-daygrid-day.dense-day .fc-event-card .event-meta .meta-item { 
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
}

.fc-daygrid-day.dense-day .fc-event-card .event-meta .meta-item--time { 
    display: inline-flex;
}

.fc-daygrid-day.dense-day .fc-event-card .event-meta .meta-item--venue { 
    display: inline-flex;
}

.fc-daygrid-day.dense-day .fc-event-card .event-meta .meta-item--faculty { 
    display: none;
}

.fc-daygrid-day.dense-day .fc-event-card .event-badge {
    font-size: 0.65rem;
    padding: 0.2rem 0.5rem;
}

/* Popover styling for "+ more" */
.fc-popover {
    border-radius: 18px !important;
    box-shadow: 0 16px 64px rgba(0, 0, 0, 0.18), 0 6px 20px rgba(0, 0, 0, 0.12) !important;
    border: 2px solid #4e73df !important;
    overflow: hidden;
    max-height: 650px;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(12px);
}

.fc-popover .fc-popover-title {
    background: linear-gradient(135deg, #4e73df 0%, #3a5bc7 100%);
    color: white;
    font-weight: 700;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 10;
    padding: 1.1rem 1.4rem;
    font-size: 1rem;
    letter-spacing: 0.4px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
    text-transform: uppercase;
}

/* Make popover body scrollable for many events */
.fc-popover .fc-popover-body {
    max-height: 450px;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem;
    scrollbar-width: thin;
    scrollbar-color: #4e73df #f3f4f6;
    background: linear-gradient(to bottom, #ffffff 0%, #f8fbff 100%);
}

/* Custom scrollbar for popover body */
.fc-popover .fc-popover-body::-webkit-scrollbar {
    width: 12px;
}

.fc-popover .fc-popover-body::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 12px;
    margin: 0.5rem 0;
}

.fc-popover .fc-popover-body::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #4e73df 0%, #3a5bc7 100%);
    border-radius: 12px;
    border: 3px solid #f3f4f6;
    transition: background 0.2s ease;
}

.fc-popover .fc-popover-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #3a5bc7 0%, #2d4aa7 100%);
    border-width: 2px;
}

.fc-popover .fc-popover-body .fc-event-card {
    margin: 0.6rem 0;
    padding: 1rem 1.1rem;
    border-left: 6px solid var(--primary-color);
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.09);
    border-radius: 0.875rem;
}

/* Ensure default popover events look like cards */
.fc-popover .fc-popover-body .fc-event {
    padding: 0.9rem;
    margin: 0.6rem 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.09);
    border-left: 5px solid var(--primary-color);
    transition: all 0.25s ease;
}

.fc-popover .fc-popover-body .fc-event:hover {
    transform: translateX(6px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.14);
}

/* Mobile popover adjustments */
@media (max-width: 575.98px) {
    .fc-popover {
        max-width: 95vw !important;
        left: 2.5vw !important;
        max-height: 70vh !important;
    }

    .fc-popover .fc-popover-body {
        max-height: 60vh !important;
    }

    .fc-popover .fc-popover-body .fc-event-card {
        padding: 0.5rem 0.4rem;
        font-size: 0.8rem;
    }

    .fc-popover .fc-popover-body .fc-event-card .event-title {
        font-size: 0.75rem !important;
    }
}

@media (max-width: 767.98px) {
    .fc-popover {
        max-width: 90vw !important;
    }

    .fc-popover .fc-popover-body {
        max-height: 65vh !important;
    }
}

/* Event badges within cards */
.fc-event-card .event-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #4e73df 0%, #3a5bc7 100%);
    color: #fff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    box-shadow: 0 2px 6px rgba(78, 115, 223, 0.25), 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.25s ease;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
}

.fc-event-card .event-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.fc-event-card:hover .event-badge {
    transform: scale(1.08) translateY(-1px);
    box-shadow: 0 4px 10px rgba(78, 115, 223, 0.35), 0 2px 5px rgba(0, 0, 0, 0.15);
}

.fc-event-card:hover .event-badge::before {
    left: 100%;
}

/* Optional type-based accents */
.fc-event-card[data-event-type="lecture"] {
    border-left-color: #4e73df;
    background: linear-gradient(135deg, #ffffff 0%, #f0f5ff 100%) !important;
}

.fc-event-card[data-event-type="lecture"]::after {
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.12) 0%, transparent 70%);
}

.fc-event-card[data-event-type="exam"] {
    border-left-color: #e74a3b;
    background: linear-gradient(135deg, #ffffff 0%, #fff6f5 100%) !important;
}

.fc-event-card[data-event-type="exam"]::after {
    background: radial-gradient(circle at top right, rgba(231, 74, 59, 0.12) 0%, transparent 70%);
}

.fc-event-card[data-event-type="meeting"] {
    border-left-color: #1cc88a;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf8 100%) !important;
}

.fc-event-card[data-event-type="meeting"]::after {
    background: radial-gradient(circle at top right, rgba(28, 200, 138, 0.12) 0%, transparent 70%);
}

.fc-event-card[data-event-type="workshop"] {
    border-left-color: #f6c23e;
    background: linear-gradient(135deg, #ffffff 0%, #fffcf2 100%) !important;
}

.fc-event-card[data-event-type="workshop"]::after {
    background: radial-gradient(circle at top right, rgba(246, 194, 62, 0.12) 0%, transparent 70%);
}

.fc-event-card[data-event-type="lecture"]:hover {
    background: linear-gradient(135deg, #fafcff 0%, #e3edff 100%) !important;
}

.fc-event-card[data-event-type="lecture"]:hover::after {
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.2) 0%, transparent 70%);
}

.fc-event-card[data-event-type="exam"]:hover {
    background: linear-gradient(135deg, #fffafa 0%, #ffe8e8 100%) !important;
}

.fc-event-card[data-event-type="exam"]:hover::after {
    background: radial-gradient(circle at top right, rgba(231, 74, 59, 0.2) 0%, transparent 70%);
}

.fc-event-card[data-event-type="meeting"]:hover {
    background: linear-gradient(135deg, #fafffe 0%, #e0f9ef 100%) !important;
}

.fc-event-card[data-event-type="meeting"]:hover::after {
    background: radial-gradient(circle at top right, rgba(28, 200, 138, 0.2) 0%, transparent 70%);
}

.fc-event-card[data-event-type="workshop"]:hover {
    background: linear-gradient(135deg, #fffef9 0%, #fff2d0 100%) !important;
}

.fc-event-card[data-event-type="workshop"]:hover::after {
    background: radial-gradient(circle at top right, rgba(246, 194, 62, 0.2) 0%, transparent 70%);
}

/* Improved stacking for multiple events in same day */
.fc-daygrid-day-frame {
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.fc-daygrid-day-frame .fc-event-card {
    margin: 0.25rem 0;
    background: #fff !important;
    box-shadow: var(--shadow-sm);
    flex-shrink: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fc-daygrid-day-frame .fc-event-card .event-title { 
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.fc-daygrid-day-frame .fc-event-card .meta-item { 
    font-size: 0.8rem;
    display: none;
}

/* TimeGrid overlapping events */
.fc-timegrid-event .fc-event-main {
    border-left: 3px solid var(--primary-color);
    border-radius: 8px;
    background: #fff !important;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.fc-timegrid-event:hover .fc-event-main {
    box-shadow: var(--shadow);
}

/* Focus visibility on events (GIGW) */
.fc-event-card:focus-visible,
.fc-timegrid-event:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(0, 74, 147, 0.2);
}

/* -------------------------------------------------------------------------
   List view — Revised Time Table (PDF-style sheet, LBSNAA)
   Scoped under #eventListView so grid/cards elsewhere stay unchanged.
   ------------------------------------------------------------------------- */
#eventListView .timetable-pdf-sheet {
    --tt-pdf-blue: #004a93;
    --tt-pdf-border: #1a1a1a;
    background: #fff;
    border-color: var(--tt-pdf-blue) !important;
}

#eventListView .timetable-pdf-banner {
    border-color: var(--tt-pdf-blue) !important;
}

#eventListView .timetable-pdf-emblem {
    width: 44px;
    height: 44px;
    object-fit: contain;
}

#eventListView .timetable-pdf-logo {
    height: 48px;
    width: auto;
    max-width: 160px;
    object-fit: contain;
}

#eventListView .timetable-pdf-hindi {
    line-height: 1.35;
}

#eventListView .timetable-container {
    border-color: rgba(0, 0, 0, 0.35) !important;
    border-radius: 0.25rem !important;
}

#eventListView .timetable-grid {
    margin-bottom: 0;
    font-size: 0.875rem;
}

#eventListView .timetable-grid thead th {
    background: #fff !important;
    color: #111 !important;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    border-color: rgba(0, 0, 0, 0.55) !important;
    padding: 0.5rem 0.35rem;
    font-size: 0.8rem;
    letter-spacing: 0.02em;
}

#eventListView .timetable-grid thead tr.day-names-row th {
    border-bottom: 1px solid rgba(0, 0, 0, 0.45);
}

#eventListView .timetable-grid thead tr.date-row th {
    font-size: 0.78rem;
    padding-top: 0.25rem;
    padding-bottom: 0.35rem;
}

#eventListView .timetable-grid thead th .tt-day-name {
    display: block;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.92;
}

#eventListView .timetable-grid thead th .tt-day-date {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    margin-top: 0.15rem;
}

#eventListView .timetable-grid .time-column {
    background: #fff !important;
    color: #111 !important;
    border-right: 2px solid #000 !important;
    font-variant-numeric: tabular-nums;
}

#eventListView .timetable-grid .time-slot {
    text-align: center;
    font-weight: 700;
    line-height: 1.25;
    white-space: pre-line;
    font-size: 0.78rem;
}

#eventListView .timetable-grid td.event-cell {
    background: #fff;
    max-height: 280px;
    border-color: rgba(0, 0, 0, 0.2) !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}

#eventListView .list-event-card {
    border-radius: 0.25rem;
    border: 1px solid rgba(0, 0, 0, 0.18);
    border-left-width: 3px !important;
    border-left-color: var(--tt-pdf-blue) !important;
    box-shadow: none;
    padding: 0.45rem 0.5rem;
    background: #fff;
    transform: none;
}

#eventListView .list-event-card:hover {
    transform: none;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06);
    background: #f8fafc;
}

#eventListView .list-event-card .title {
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.35;
    margin-bottom: 0.25rem;
}

#eventListView .list-event-card .meta {
    font-size: 0.72rem;
    padding: 0.1rem 0;
    background: transparent;
    width: 100%;
}

#eventListView .list-event-card .group-badge {
    font-size: 0.65rem;
    padding: 0.15rem 0.45rem;
    border-radius: 0.15rem;
}

/* Parallel Group A / B bands (official time table layout) */
#eventListView .tt-pdf-group-rows {
    display: flex;
    flex-direction: column;
    gap: 0;
}

#eventListView .tt-pdf-group-row {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 0;
    border-top: 1px solid rgba(0, 0, 0, 0.12);
}

#eventListView .tt-pdf-group-row:first-child {
    border-top: 0;
}

#eventListView .tt-pdf-group-label {
    flex: 0 0 1.75rem;
    width: 1.75rem;
    min-height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--tt-pdf-blue);
    background: rgba(0, 74, 147, 0.06);
    border-right: 1px solid rgba(0, 74, 147, 0.25);
    user-select: none;
}

#eventListView .tt-pdf-group-body {
    flex: 1 1 auto;
    min-width: 0;
    padding: 0.25rem 0.35rem 0.35rem 0.45rem;
}

#eventListView .tt-pdf-group-body .list-event-card {
    margin-bottom: 0.35rem;
}

#eventListView .tt-pdf-group-body .list-event-card:last-child {
    margin-bottom: 0;
}

#eventListView .timetable-grid tr.break-row th,
#eventListView .timetable-grid tr.break-row td,
#eventListView .timetable-grid tr.lunch-row th,
#eventListView .timetable-grid tr.lunch-row td,
#eventListView .timetable-grid tr.break-notes-row th,
#eventListView .timetable-grid tr.break-notes-row td {
    background: #fff8e6 !important;
    color: #1a1a1a !important;
    font-weight: 600;
    font-size: 0.78rem;
}

#eventListView .timetable-grid tr.venue-summary-row th,
#eventListView .timetable-grid tr.venue-summary-row td {
    background: #f8fafc !important;
    font-weight: 700;
    font-size: 0.74rem;
    text-align: center;
    vertical-align: middle;
}

#eventListView .accordion-button:not(.collapsed) {
    background-color: rgba(0, 74, 147, 0.08);
    color: var(--tt-pdf-blue);
}

/* Timetable styling */
.timetable-grid {
    border-collapse: separate;
    border-spacing: 0;
}

.timetable-grid th {
    font-weight: 600;
    color: #ffffff;
    background: var(--primary-color);
    padding: 1rem 0.75rem;
    border-bottom: 2px solid var(--primary-color);
}

.timetable-grid td {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    vertical-align: top;
    transition: background-color 0.2s ease;
    max-height: 300px;
    overflow-y: auto;
    position: relative;
}

/* Scrollbar styling for timetable cells */
.timetable-grid td::-webkit-scrollbar {
    width: 8px;
}

.timetable-grid td::-webkit-scrollbar-track {
    background: rgba(0, 74, 147, 0.05);
    border-radius: 4px;
}

.timetable-grid td::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
    transition: background 0.2s ease;
}

.timetable-grid td::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Firefox scrollbar styling */
.timetable-grid td {
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) rgba(0, 74, 147, 0.05);
}

.timetable-grid td:hover {
    background-color: rgba(0, 74, 147, 0.02);
}

/* Visual indicator for scrollable content */
.timetable-grid td.has-scroll::after {
    content: '';
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.95), transparent);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.timetable-grid td.has-scroll:not(.scrolled-bottom)::after {
    opacity: 1;
}

/* Scroll indicator icon */
.timetable-grid td.has-scroll:not(.scrolled-bottom)::before {
    content: '⌄';
    position: sticky;
    bottom: 5px;
    left: 50%;
    transform: translateX(-50%);
    display: block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    color: var(--primary-color);
    font-size: 1.2rem;
    font-weight: bold;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
    z-index: 10;
    animation: bounce 2s infinite;
    pointer-events: none;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateX(-50%) translateY(0);
    }
    40% {
        transform: translateX(-50%) translateY(-5px);
    }
    60% {
        transform: translateX(-50%) translateY(-3px);
    }
}

.timetable-grid .time-column {
    min-width: 120px;
    font-weight: 600;
    color: var(--secondary-color);
    background-color: rgba(175, 41, 16, 0.05);
}

/* List Event Cards */
.list-event-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    border: 1.5px solid #e5e7eb;
    border-radius: 16px;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 6px solid var(--primary-color) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.09), 0 2px 6px rgba(0, 0, 0, 0.06);
    position: relative;
    cursor: pointer;
    overflow: visible;
    min-height: fit-content;
    padding: 1rem 1.25rem;
}

.list-event-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color) 0%, rgba(78, 115, 223, 0.5) 70%, transparent 100%);
    opacity: 0;
    transition: opacity 0.35s ease;
    border-radius: 16px 16px 0 0;
}

.list-event-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 50px;
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.08) 0%, transparent 70%);
    border-radius: 0 16px 0 0;
    pointer-events: none;
}

.list-event-card:hover {
    transform: translateX(8px) translateY(-3px);
    box-shadow: 0 10px 32px rgba(0, 74, 147, 0.18), 0 6px 12px rgba(0, 0, 0, 0.12);
    z-index: 10;
    background: linear-gradient(135deg, #ffffff 0%, #eef5ff 100%);
    border-left-width: 7px;
}

.list-event-card:hover::before {
    opacity: 1;
}

.list-event-card:hover::after {
    background: radial-gradient(circle at top right, rgba(78, 115, 223, 0.15) 0%, transparent 70%);
}

.list-event-card:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 4px;
    box-shadow: 0 0 0 6px rgba(0, 74, 147, 0.15);
}

/* Hover tooltip for full details */
.list-event-card .event-tooltip {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 320px;
    background: white;
    border: 2px solid #4e73df;
    border-radius: 14px;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15), 0 4px 16px rgba(0, 0, 0, 0.1);
    padding: 1.25rem;
    margin-top: 0.75rem;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-15px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    pointer-events: none;
    backdrop-filter: blur(10px);
}

.list-event-card:hover .event-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Show tooltip on keyboard focus for accessibility */
.list-event-card:focus-within .event-tooltip,
.list-event-card:focus .event-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.event-tooltip .tooltip-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1f2937;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #4e73df;
    background: linear-gradient(135deg, #4e73df 0%, #3a5bc7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.event-tooltip .tooltip-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.event-tooltip .tooltip-row i {
    color: #4e73df;
    margin-top: 0.15rem;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.event-tooltip .tooltip-label {
    font-weight: 600;
    color: #374151;
    min-width: 70px;
}

.event-tooltip .tooltip-value {
    color: #6b7280;
    flex: 1;
    line-height: 1.5;
}

.list-event-card .group-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    color: white;
    background: linear-gradient(135deg, #4e73df 0%, #3a5bc7 100%);
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 6px rgba(78, 115, 223, 0.25);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    white-space: nowrap;
}

.list-event-card .title {
    font-weight: 700;
    font-size: 1.1rem;
    line-height: 1.6;
    color: #1f2937;
    margin-bottom: 0.65rem;
    word-wrap: break-word;
    overflow: visible;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.list-event-card .meta {
    color: #4b5563;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 500;
    margin-top: 0.4rem;
    padding: 0.3rem 0.6rem;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    width: fit-content;
}

.list-event-card .meta i {
    color: #4e73df;
    font-size: 1rem;
}

.list-event-card:hover .meta {
    background: rgba(255, 255, 255, 0.95);
    color: #1f2937;
}

/* Group-specific backgrounds */
.list-event-card[data-group="Group A"],
.list-event-card[data-group*="Group A"] {
    background: #e8e4f3;
    border-left-color: #8b7ab8 !important;
}

.list-event-card[data-group="Group B"],
.list-event-card[data-group*="Group B"] {
    background: #d4edda;
    border-left-color: #5cb85c !important;
}

.list-event-card[data-group*="Group A"] .group-badge {
    background: #b8a9d6;
    color: #5a4a7d;
}

.list-event-card[data-group*="Group B"] .group-badge {
    background: #8fd19e;
    color: #2d5f37;
}

/* Break / Lunch rows */
.timetable-grid tr.break-row th,
.timetable-grid tr.break-row td {
    background: #fff7ec;
}

.timetable-grid tr.lunch-row th,
.timetable-grid tr.lunch-row td {
    background: #fff2f0;
}

/* Institution name styling */
.institution-name.hindi-text {
    font-family: 'Noto Sans Devanagari', 'Arial', sans-serif;
    font-weight: 600;
    font-size: 1.25rem;
}

.institution-name.english-text {
    font-family: 'Montserrat', 'Noto Sans', 'Noto Sans Devanagari', system-ui, sans-serif;
    font-weight: 600;
    font-size: 1.1rem;
}

/* Form styling */
.form-label {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.form-label.required::after {
    content: " *";
    color: var(--danger-color);
}

.form-control,
.form-select {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    transition: var(--transition);
    font-size: 0.95rem;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
    outline: none;
}

.form-control:hover:not(:focus):not(:disabled),
.form-select:hover:not(:focus):not(:disabled) {
    border-color: var(--primary-color);
}

.readonly-checkbox {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Custom Checkbox/Radio - GIGW Compliant */
.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--primary-color);
    cursor: pointer;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.25);
    border-color: var(--primary-color);
}

/* Modal improvements */
.modal-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.05), rgba(175, 41, 16, 0.05));
    border-bottom: 2px solid var(--primary-color);
}

.modal-header .modal-title {
    font-weight: 600;
    color: white;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    background-color: var(--light-bg);
}

.control-panel {
    /* backdrop-filter creates a stacking context; dropdown then stays under fixed chrome */
    border: 1px solid rgba(0, 74, 147, 0.1) !important;
    overflow: visible !important;
    position: relative;
}

.bg-gradient {
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.03), rgba(175, 41, 16, 0.03));
}

.logo-wrapper {
    transition: var(--transition);
}

.logo-wrapper:hover {
    transform: scale(1.05);
}

.week-controls {
    transition: var(--transition);
}

.week-badge {
    margin-top: 0.5rem;
}

.badge {
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .timetable-header .institution-name {
        font-size: 1rem;
    }

    .modal-dialog {
        margin: 0.5rem;
    }

    .btn-group {
        width: 100%;
        justify-content: center;
    }

    .control-panel {
        flex-direction: column;
        align-items: stretch !important;
    }

    .control-panel .btn-group {
        width: 100%;
    }

    .control-panel .btn {
        flex: 1;
    }
}

/* Empty State Styling */
.empty-state {
    padding: 3rem 2rem;
    text-align: center;
}

.empty-state i {
    opacity: 0.3;
}

.empty-state p {
    font-size: 1.1rem;
}

/* Loading State */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 4px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Auto-hide the calendar loading overlay after 2 seconds */
#calendarLoadingOverlay {
    animation: fadeOut 0.5s ease-in-out 2s forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Focus indicators for accessibility */
:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .btn-outline-primary {
        border-width: 2px;
    }

    .fc-event-card {
        border-width: 2px;
    }
    .list-event-card { border-width: 2px; }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {

    .fc-event-card,
    .btn {
        transition: none;
    }
}

/* Compact density mode */
body.compact-mode .fc { font-size: 0.85rem; }
body.compact-mode .fc-event-card {
    padding: 0.35rem 0.5rem;
    border-left-width: 3px;
    border-radius: 0.375rem;
}
body.compact-mode .fc-event-card .event-title { font-size: 0.85rem; }
body.compact-mode .fc-event-card .event-meta .meta-item { display: none; }
body.compact-mode .fc-event-card .event-meta .meta-item--time { display: inline-flex; }
body.compact-mode .fc-timegrid-event .fc-event-main { border-left-width: 3px; }
body.compact-mode .fc-popover .fc-popover-body .fc-event-card { padding: 0.5rem 0.625rem; }

body.compact-mode .list-event-card { padding: 0.5rem 0.625rem !important; border-radius: 10px; }
body.compact-mode .list-event-card .title { font-size: 0.95rem; }
body.compact-mode .list-event-card .meta:not(:first-of-type) { display: none; }
body.compact-mode .list-event-card .event-tooltip { display: none; }

/* Compact mode - reduce cell height for better fit */
body.compact-mode .timetable-grid td {
    max-height: 200px;
}

body.compact-mode .timetable-grid td.has-scroll:not(.scrolled-bottom)::before {
    width: 20px;
    height: 20px;
    line-height: 20px;
    font-size: 1rem;
}

/* FullCalendar "+ more" text styling */
.fc-daygrid-day-more-link {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #ffffff !important;
    background-color: var(--primary-color) !important;
    padding: 0.5rem 0.75rem !important;
    border-radius: 0.375rem !important;
    display: inline-block !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
    background: linear-gradient(135deg, var(--primary-color), #0066cc) !important;
}

.fc-daygrid-day-more-link:hover {
    background: linear-gradient(135deg, var(--primary-dark), #004a93) !important;
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.4) !important;
    color: #ffffff !important;
}

/* Fallback for other FullCalendar versions */
.fc-more-link {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #ffffff !important;
    background-color: var(--primary-color) !important;
    padding: 0.5rem 0.75rem !important;
    border-radius: 0.375rem !important;
    display: inline-block !important;
    transition: all 0.2s ease !important;
}

.fc-more-link:hover {
    background-color: var(--primary-dark) !important;
    transform: scale(1.08);
    color: #ffffff !important;
}

/* TimeGrid "+ more" links for week and day views */
.fc-timegrid-more-link,
.fc-timegrid .fc-more-link {
    font-size: 1rem !important;
    font-weight: 700 !important;
    color: #ffffff !important;
    background: linear-gradient(135deg, var(--primary-color), #0066cc) !important;
    padding: 0.4rem 0.6rem !important;
    border-radius: 0.375rem !important;
    display: inline-block !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
    box-shadow: 0 2px 4px rgba(0, 74, 147, 0.2) !important;
}

.fc-timegrid-more-link:hover,
.fc-timegrid .fc-more-link:hover {
    background: linear-gradient(135deg, var(--primary-dark), #004a93) !important;
    transform: scale(1.05) !important;
    box-shadow: 0 4px 8px rgba(0, 74, 147, 0.4) !important;
    color: #ffffff !important;
}

/* Timeline View - Modern Design */
.timeline-container {
    display: flex;
    gap: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow);
}

.timeline-times {
    width: 120px;
    flex-shrink: 0;
    padding-right: 1rem;
    border-right: 2px solid var(--border-color);
}

.timeline-time-label {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.95rem;
    position: relative;
}

.timeline-time-label::after {
    content: '';
    position: absolute;
    right: -1rem;
    top: 50%;
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    transform: translateY(-50%);
}

.timeline-slots {
    flex: 1;
    position: relative;
    min-height: 600px;
}

.timeline-slot {
    position: relative;
    height: 80px;
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s ease;
}

.timeline-slot:hover {
    background-color: rgba(0, 74, 147, 0.02);
}

.timeline-slot:last-child {
    border-bottom: none;
}

.timeline-event-card {
    position: absolute;
    left: 0;
    right: 0;
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid var(--primary-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: var(--transition);
    cursor: pointer;
    overflow: hidden;
}

.timeline-event-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(0, 74, 147, 0.2);
    border-left-width: 6px;
}

.timeline-event-card .event-title {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.timeline-event-card .event-time {
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.timeline-event-card .event-time i {
    font-size: 1rem;
}

.timeline-event-card .event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.timeline-event-card .event-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.625rem;
    border-radius: 12px;
    background-color: #4e73df;
    color: var(--primary-color);
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Event Type Colors */
.timeline-event-card[data-event-type="lecture"] {
    border-left-color: #4e73df;
}

.timeline-event-card[data-event-type="exam"] {
    border-left-color: #e74a3b;
}

.timeline-event-card[data-event-type="meeting"] {
    border-left-color: #1cc88a;
}

.timeline-event-card[data-event-type="workshop"] {
    border-left-color: #f6c23e;
}

/* Timeline Responsive Design */
@media (max-width: 768px) {
    .timeline-container {
        padding: 1rem;
        gap: 1rem;
    }

    .timeline-times {
        width: 80px;
    }

    .timeline-time-label {
        font-size: 0.85rem;
    }

    .timeline-event-card {
        padding: 0.75rem;
    }

    .timeline-event-card .event-title {
        font-size: 0.9rem;
    }
}

/* ========== COMPREHENSIVE RESPONSIVE DESIGN ========== */

/* Extra Small Devices (< 576px) */
@media (max-width: 575.98px) {
    /* General Layout */
    .container-fluid {
        padding: 0.5rem;
    }

    /* Course Header */
    .course-header {
        padding: 1rem 0.75rem;
        margin-bottom: 1rem !important;
    }

    .course-header h1 {
        font-size: 1.15rem;
        margin-bottom: 0.5rem;
    }

    .course-header p {
        font-size: 0.8rem;
    }

    .course-header .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    /* Control Panel */
    .control-panel {
        flex-direction: column !important;
        align-items: stretch !important;
        padding: 1rem 0.75rem !important;
        gap: 0.75rem !important;
    }

    .view-toggle-section {
        flex-direction: column !important;
        width: 100%;
        gap: 0.75rem !important;
    }

    .view-toggle-section .text-muted {
        display: none !important;
    }

    .btn-group {
        display: flex;
        width: 100%;
        gap: 0.5rem;
    }

    .btn-group .btn {
        flex: 1;
        font-size: 0.8rem;
        padding: 0.4rem 0.5rem;
    }

    .btn-group .btn i {
        margin-right: 0.25rem;
    }

    .density-toggle {
        width: 100%;
    }

    .density-toggle .btn {
        width: 100%;
        font-size: 0.8rem;
    }

    #courseFilter {
        width: 100% !important;
        min-width: unset !important;
        font-size: 0.9rem;
        padding: 0.5rem;
    }

    #createEventButton {
        width: 100%;
        font-size: 0.85rem;
        padding: 0.6rem !important;
    }

    /* Calendar Container */
    .card {
        border-left-width: 3px !important;
    }

    .card-body {
        padding: 1rem !important;
    }

    .calendar-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    #calendar .fc-view-harness {
        min-height: 320px;
    }

    /* FullCalendar Adjustments */
    .fc {
        font-size: 0.8rem;
    }

    .fc-col-header-cell {
        padding: 0.5rem 0.25rem !important;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .fc-daygrid-day-number {
        font-size: 0.75rem;
        padding: 0.35rem;
    }

    .fc-event-card {
        padding: 0.35rem 0.4rem !important;
        border-radius: 0.3rem;
        margin: 0.15rem 0 !important;
        font-size: 0.7rem;
        background: #fff !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .fc-event-card .event-title {
        font-size: 0.75rem !important;
        line-height: 1.2 !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .fc-event-card .event-meta {
        gap: 0.3rem 0.5rem !important;
        margin-top: 0.15rem;
        flex-wrap: wrap;
    }

    .fc-event-card .meta-item {
        font-size: 0.7rem !important;
        display: none !important;
        white-space: nowrap;
    }

    .fc-event-card .event-badge {
        font-size: 0.65rem !important;
        padding: 0.1rem 0.35rem !important;
        white-space: nowrap;
    }

    .fc-daygrid-day-more-link {
        font-size: 0.8rem !important;
        padding: 0.3rem 0.5rem !important;
    }

    /* List View / Timetable */
    .timetable-header {
        padding: 1rem !important;
        border-radius: 0.75rem !important;
    }

    .timetable-header .row {
        gap: 1rem !important;
    }

    .timetable-header .col-md-2,
    .timetable-header .col-md-6,
    .timetable-header .col-md-4 {
        text-align: center !important;
    }

    .logo-wrapper {
        padding: 0.5rem !important;
    }

    .logo-wrapper img {
        width: 50px !important;
        height: 50px !important;
    }

    .timetable-header h1 {
        font-size: 1rem !important;
        margin-bottom: 0.5rem !important;
    }

    .timetable-header p {
        font-size: 0.75rem;
    }

    .week-controls {
        padding: 0.75rem !important;
    }

    .week-controls .btn-group {
        margin-bottom: 0.75rem;
        gap: 0.25rem;
    }

    .week-controls .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.5rem;
    }

    .week-badge .badge {
        font-size: 0.75rem !important;
        padding: 0.35rem 0.75rem !important;
    }

    /* Week Cards */
    .week-cards .row {
        gap: 0.75rem !important;
    }

    .week-cards .row > * {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .week-card {
        padding: 0.75rem !important;
    }

    .week-card-date {
        font-size: 0.8rem;
    }

    .week-card-day {
        font-size: 0.7rem;
    }

    /* Timetable Grid */
    .timetable-container {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .timetable-container .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        overscroll-behavior-x: contain;
    }

    .table-responsive {
        font-size: 0.8rem;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.5rem 0.35rem !important;
        font-size: 0.75rem;
    }

    .timetable-grid .time-column {
        min-width: 70px;
        font-size: 0.7rem;
    }

    .timetable-grid td {
        max-height: 200px;
    }

    /* List Event Cards */
    .list-event-card {
        padding: 0.65rem !important;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .list-event-card .title {
        font-size: 0.85rem;
    }

    .list-event-card .meta {
        font-size: 0.75rem;
    }

    .list-event-card .event-tooltip {
        min-width: 200px;
        font-size: 0.75rem;
        padding: 0.65rem;
    }

    .list-event-card .group-badge {
        font-size: 0.65rem;
        padding: 0.1rem 0.35rem;
    }

    /* Form Elements */
    .form-label {
        font-size: 0.85rem;
    }

    .form-control,
    .form-select {
        font-size: 0.85rem;
        padding: 0.5rem 0.65rem;
    }

    .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
    }

    /* Modal */
    .modal-dialog {
        margin: 0.5rem !important;
    }

    .modal-content {
        border-radius: 0.75rem;
    }

    .modal-header {
        padding: 1rem !important;
    }

    .modal-header .modal-title {
        font-size: 1rem;
    }

    .modal-body {
        padding: 1rem !important;
        font-size: 0.85rem;
    }

    .modal-footer {
        padding: 0.75rem 1rem !important;
    }

    .modal-footer .btn {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }

    /* Buttons */
    .btn {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }

    .btn-lg {
        padding: 0.6rem 0.9rem;
        font-size: 0.9rem;
    }

    /* Badges */
    .badge {
        font-size: 0.65rem;
    }

    /* Utility Classes */
    .p-3 {
        padding: 0.75rem !important;
    }

    .p-4 {
        padding: 1rem !important;
    }

    .p-md-4 {
        padding: 1rem !important;
    }

    .mb-3 {
        margin-bottom: 0.75rem !important;
    }

    .mb-4 {
        margin-bottom: 1rem !important;
    }

    .gap-3 {
        gap: 0.75rem !important;
    }

    .gap-4 {
        gap: 1rem !important;
    }
}

/* Small Devices (576px to 767px) */
@media (max-width: 767.98px) {
    .course-header {
        padding: 1.5rem 1rem;
    }

    .course-header h1 {
        font-size: 1.35rem;
    }

    .control-panel {
        flex-direction: column;
        align-items: stretch !important;
    }

    .view-toggle-section {
        flex-direction: column;
        width: 100%;
    }

    .btn-group {
        width: 100%;
    }

    .btn-group .btn {
        flex: 1;
        font-size: 0.85rem;
    }

    #courseFilter {
        width: 100% !important;
        min-width: unset !important;
    }

    #createEventButton {
        width: 100%;
    }

    .fc {
        font-size: 0.85rem;
    }

    .fc-col-header-cell {
        padding: 0.75rem 0.4rem !important;
        font-size: 0.8rem;
    }

    .fc-daygrid-day-number {
        font-size: 0.8rem;
        padding: 0.4rem;
    }

    .fc-event-card {
        padding: 0.65rem 0.75rem !important;
        border-radius: 0.625rem;
        margin: 0.3rem 0 !important;
        font-size: 0.8rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
        overflow: visible;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.09);
        min-height: fit-content;
    }

    .fc-event-card .event-title {
        font-size: 0.9rem !important;
        line-height: 1.4 !important;
        overflow: visible;
        -webkit-line-clamp: 3;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        margin-bottom: 0.45rem !important;
        word-break: break-word;
    }

    .fc-event-card .event-meta {
        gap: 0.4rem 0.6rem !important;
        margin-top: 0.35rem;
        padding-top: 0.4rem;
        flex-wrap: wrap;
        border-top-width: 1px;
    }

    .fc-event-card .meta-item {
        font-size: 0.75rem !important;
        padding: 0.2rem 0.4rem !important;
        gap: 0.3rem;
    }
    
    .fc-event-card .meta-item--time {
        display: inline-flex !important;
    }
    
    .fc-event-card .meta-item--venue {
        display: inline-flex !important;
    }
    
    .fc-event-card .meta-item--faculty {
        display: none !important;
    }

    .fc-event-card .event-badge {
        font-size: 0.65rem !important;
        padding: 0.25rem 0.5rem !important;
        white-space: nowrap;
    }

    .timetable-header .row {
        flex-direction: column;
        text-align: center;
    }

    .timetable-header .col-md-2,
    .timetable-header .col-md-6,
    .timetable-header .col-md-4 {
        flex: 0 0 100%;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.65rem 0.4rem;
        font-size: 0.8rem;
    }

    .timetable-grid .time-column {
        min-width: 90px;
    }

    .timetable-container .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        overscroll-behavior-x: contain;
    }

    .calendar-container {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    .list-event-card {
        padding: 0.75rem;
    }

    .list-event-card .title {
        font-size: 0.9rem;
    }

    .list-event-card .event-tooltip {
        min-width: 220px;
    }
}

/* Medium Devices (768px to 991px) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .course-header {
        padding: 2rem 1.25rem;
    }

    .course-header h1 {
        font-size: 1.5rem;
    }

    .fc {
        font-size: 0.9rem;
    }

    .fc-event-card {
        padding: 0.75rem 0.875rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
        overflow: visible;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        min-height: fit-content;
    }

    .fc-event-card .event-title {
        overflow: visible;
        -webkit-line-clamp: 3;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        font-size: 0.98rem;
        line-height: 1.45;
        word-break: break-word;
    }

    .fc-event-card .event-meta {
        flex-wrap: wrap;
        gap: 0.5rem 0.8rem;
    }
    
    .fc-event-card .meta-item {
        font-size: 0.82rem;
        padding: 0.25rem 0.45rem;
    }
    
    .fc-event-card .meta-item--time,
    .fc-event-card .meta-item--venue {
        display: inline-flex !important;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.75rem;
        font-size: 0.85rem;
    }

    .timetable-grid .time-column {
        min-width: 100px;
    }

    .list-event-card {
        padding: 0.75rem;
    }
}

/* Desktop Devices (992px and up) - Keep Original Styling */
@media (min-width: 992px) {
    .course-header {
        padding: 2.75rem 1.5rem;
    }

    .course-header h1 {
        font-size: 1.85rem;
    }

    .control-panel {
        flex-direction: row;
        align-items: center;
    }

    .view-toggle-section {
        flex-direction: row;
    }

    #courseFilter {
        min-width: 200px;
    }

    .fc {
        font-size: 0.95rem;
    }

    .fc-event-card {
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
        min-height: fit-content;
    }

    .fc-event-card .event-title {
        white-space: normal;
        font-size: 1.05rem;
        overflow: visible;
        word-break: break-word;
        -webkit-line-clamp: 3;
        line-height: 1.5;
    }

    .fc-event-card .event-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem 1.1rem;
    }
    
    .fc-event-card .meta-item {
        font-size: 0.875rem;
        display: inline-flex !important;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.75rem;
    }

    .timetable-grid .time-column {
        min-width: 120px;
    }

    .list-event-card {
        padding: 0.75rem 1rem;
    }
}

/* Print Styles */
@media print {
    .control-panel,
    #createEventButton,
    .btn,
    button {
        display: none !important;
    }

    .calendar-container {
        box-shadow: none;
    }

    .fc {
        font-size: 0.95rem;
    }

    /* When printing from Timetable sheet tab, cells read like the official PDF */
    #eventListView .timetable-pdf-sheet {
        box-shadow: none !important;
    }

    #eventListView .timetable-grid th,
    #eventListView .timetable-grid td {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    #eventListView .timetable-grid tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    #eventListView #timetableLegendAccordion {
        display: none !important;
    }

    /* Print only the timetable sheet (from toolbar "Print sheet") */
    body.timetable-print-only * {
        visibility: hidden;
    }
    body.timetable-print-only #eventListView,
    body.timetable-print-only #eventListView * {
        visibility: visible;
    }
    body.timetable-print-only #eventListView {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100%;
        padding: 0 8mm;
        background: #fff;
    }
}
.control-panel:focus-within {
    outline: 2px solid #004a93;
    outline-offset: 2px;
}

.btn:focus-visible,
.form-select:focus-visible {
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.25);
}

</style>

<!-- Debug: Page is loading -->
<script>console.log('Calendar view is rendering...', {
    courseMasterExists: {{ isset($courseMaster) ? 'true' : 'false' }},
    courseMasterCount: {{ isset($courseMaster) ? $courseMaster->count() : 0 }}
});</script>

<div class="container-fluid calendar-admin-page">
    @if(!isset($courseMaster) || $courseMaster->isEmpty())
        <div class="alert alert-warning m-4">
            <h4><i class="bi bi-exclamation-triangle me-2"></i>No Courses Available</h4>
            <p>No active courses found. Please contact the administrator.</p>
        </div>
    @endif
    
    <!-- Page Header with ARIA landmark -->
    @if(hasRole('Admin'))
        <header aria-label="Page header">
            <x-breadcrum title="Academic TimeTable" />
        </header>
    @endif
        <div class="course-header mb-3">
            <h1>{{ $courseMaster->first()->course_name ?? 'Course Name' }}</h1>
            <p class="mb-0 text-white fw-medium">
                <span class="badge">{{ $courseMaster->first()->couse_short_name ?? 'Course Code' }}</span>
                | <strong>Year:</strong> {{ $courseMaster->first()->course_year ?? date('Y') }}
            </p>
        </div>

    <!-- Main Content Area -->
    <main id="main-content" role="main">
        <!-- Action Controls with proper semantics -->
         @if(hasRole('Training') || hasRole('Admin') ||  hasRole('Training-MCTP') || hasRole('IST'))
        <section
    class="control-panel bg-white p-3 p-md-4 rounded-3 shadow-sm border mb-3"
    role="region"
    aria-labelledby="controlPanelHeading"
    style="border-left: 4px solid #004a93;"
>
    <h2 id="controlPanelHeading" class="visually-hidden">
        Calendar Control Panel
    </h2>

    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-stretch align-items-xl-center gap-3 gap-xl-4">

        <!-- Filters & View Controls -->
        <fieldset class="d-flex flex-column flex-md-row align-items-stretch align-items-md-end gap-3 mb-0">
            <legend class="visually-hidden">View and Filter Controls</legend>

            <!-- Course Filter -->
            <div class="calendar-choices-bootstrap d-flex flex-column gap-1 min-w-0" style="min-width: 260px;">
                <label for="courseFilter" class="form-label mb-0 fw-semibold text-secondary small">Filter by Course</label>
                <select
                    class="form-select js-calendar-course-choice"
                    id="courseFilter"
                    aria-describedby="courseFilterHelp"
                >
                    <option value="">All Courses</option>
                    @foreach($courseMaster as $course)
                        <option value="{{ $course->pk }}"
                            {{ $courseMaster->first() && $course->pk == $courseMaster->first()->pk ? 'selected' : '' }}>
                            {{ $course->course_name }} ({{ $course->couse_short_name }})
                        </option>
                    @endforeach
                </select>
            </div>
        </fieldset>

        <!-- Primary Actions -->
        @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
        <div class="d-flex align-items-center justify-content-start justify-content-xl-end gap-2">
            <button
                type="button"
                class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm rounded-2"
                id="createEventButton"
                data-bs-toggle="modal"
                data-bs-target="#eventModal"
            >
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span>Add New Event</span>
            </button>
        </div>
        @endif

    </div>
</section>

        @endif

        <!-- Calendar Container -->
        <section class="calendar-container" aria-label="Academic calendar">
            <div class="card border-start-4 border-primary shadow-sm">
                <div class="card-body p-3 p-md-4 position-relative">
                    
                    <!-- Loading overlay -->
                    <div id="calendarLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white" style="min-height: 400px; z-index: 100;">
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

                    <!-- View switch + week exports: outside #eventListView so controls stay visible on calendar modes -->
                    <div id="calendarSheetToolbar" class="d-flex flex-wrap align-items-stretch align-items-sm-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light">
                        <div class="btn-group btn-group-sm shadow-sm flex-shrink-0" role="group" aria-label="Calendar view">
                            <button type="button" class="btn btn-outline-primary active" data-view="month" id="calendarViewMonthBtn" aria-pressed="true">Calendar</button>
                            <button type="button" class="btn btn-outline-primary" data-view="week" id="calendarViewWeekBtn" aria-pressed="false">Week</button>
                            <button type="button" class="btn btn-outline-primary" data-view="list" id="calendarViewSheetBtn" aria-pressed="false">Timetable sheet</button>
                        </div>
                        <div class="btn-group btn-group-sm shadow-sm flex-wrap" role="group" aria-label="Week timetable export">
                            <button type="button" class="btn btn-outline-secondary" id="weekTimetablePrintBtn" title="Print weekly timetable">
                                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="weekTimetablePdfViewBtn" title="Open weekly timetable PDF in a new tab">
                                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="weekTimetablePdfDownloadBtn" title="Download weekly timetable as PDF file">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>PDF file
                            </button>
                            <button type="button" class="btn btn-outline-success" id="weekTimetableExcelBtn" title="Download weekly timetable as Excel (grid + sessions)">
                                <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="weekTimetablePrintPageBtn" title="Print only the timetable sheet (this page)">
                                <i class="bi bi-printer-fill me-1" aria-hidden="true"></i>Print sheet
                            </button>
                        </div>
                    </div>

                    <!-- FullCalendar placeholder (you may initialize FullCalendar separately) -->
                    <div id="calendar" class="fc mb-4" role="application" aria-label="Interactive calendar"></div>

                    <!-- List View — Revised time table (PDF-style sheet) -->
                    <div id="eventListView" class="mt-4 d-none" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper">
                            <div class="card timetable-pdf-sheet border-2 shadow-sm mb-4">
                                <div class="card-body p-3 p-md-4">
                                    <header class="timetable-pdf-banner pb-3 mb-3 border-bottom border-2">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-auto">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png"
                                                    width="44" height="44" class="timetable-pdf-emblem" alt="National Emblem" loading="lazy">
                                            </div>
                                            <div class="col min-w-0">
                                                <p class="timetable-pdf-hindi institution-name hindi-text mb-1 small text-body-secondary">
                                                    लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी
                                                </p>
                                                <p class="timetable-pdf-english fw-semibold text-primary mb-1 mb-md-2">
                                                    Lal Bahadur Shastri National Academy of Administration, Mussoorie
                                                </p>
                                                <p class="timetable-pdf-course text-body-secondary small mb-0 fw-medium" id="timetableCourseTitle">
                                                    Academic timetable — select a course filter when available
                                                </p>
                                                <p class="timetable-pdf-period small text-muted mb-0 mt-1 fst-italic d-none" id="timetableCoursePeriod" aria-live="polite"></p>
                                            </div>
                                            <div class="col-auto text-end d-none d-md-block">
                                                <img src="{{ asset('images/lbsnaa_logo.jpg') }}"
                                                    onerror="this.onerror=null;this.src='https://www.lbsnaa.gov.in/admin_assets/images/logo.png'"
                                                    class="timetable-pdf-logo" alt="LBSNAA" width="160" height="48" loading="lazy">
                                            </div>
                                        </div>

                                        <div class="row align-items-end g-2 mt-3">
                                            <div class="col-lg-8">
                                                <h1 class="h4 fw-bold text-dark mb-1 d-flex flex-wrap align-items-center gap-2">
                                                    <span>Time Table</span>
                                                    <span class="text-secondary fw-normal">:</span>
                                                    <span class="text-secondary fw-normal">Week</span>
                                                    <span id="currentWeekNumber" class="text-primary" aria-live="polite">—</span>
                                                    <span class="text-secondary fw-normal ms-1 small">Revised</span>
                                                </h1>
                                                <p class="text-muted small mb-0" id="weekRangeText" aria-live="polite">
                                                    <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>—
                                                </p>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                                    <div class="btn-group shadow-sm" role="group" aria-label="Week navigation">
                                                        <button type="button" class="btn btn-outline-primary btn-sm px-2" id="prevWeekBtn" aria-label="Previous week">
                                                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-sm px-3" id="currentWeekBtn" aria-label="Current week">
                                                            <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Today
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary btn-sm px-2" id="nextWeekBtn" aria-label="Next week">
                                                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="small text-center text-body-secondary border-top border-light pt-2 mt-3 mb-0 px-md-5">
                                            <span class="fw-semibold text-dark">Note:</span>
                                            Tea break, lunch break, and venue lines follow the official programme when entered as session titles in the calendar.
                                        </p>
                                    </header>

                                    @php
                                        $ttFootnotes = array_values(array_filter(array_map('trim', config('week_timetable.footnotes', []))));
                                    @endphp
                                    @if(count($ttFootnotes))
                                        <div class="timetable-footnotes small text-body-secondary border border-light rounded-2 px-3 py-2 mb-2 bg-light" role="note" aria-label="Programme notes">
                                            @foreach ($ttFootnotes as $fn)
                                                <p class="mb-1 lh-sm">{{ e($fn) }}</p>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="timetable-container border border-dark border-opacity-25 rounded-1 overflow-hidden bg-white">
                                        <div class="table-responsive" role="region" aria-label="Weekly timetable grid">
                                            <table class="table table-bordered timetable-grid mb-0" id="timetableTable"
                                                aria-describedby="timetableDescription">
                                                <caption class="visually-hidden" id="timetableDescription">
                                                    Weekly academic timetable showing events by time slot and weekday
                                                </caption>
                                                <thead id="timetableHead">
                                                    <tr class="day-names-row">
                                                        <th scope="col" rowspan="2" class="time-column align-middle">TIME</th>
                                                        <th scope="col" class="text-center">Monday</th>
                                                        <th scope="col" class="text-center">Tuesday</th>
                                                        <th scope="col" class="text-center">Wednesday</th>
                                                        <th scope="col" class="text-center">Thursday</th>
                                                        <th scope="col" class="text-center">Friday</th>
                                                    </tr>
                                                    <tr class="date-row">
                                                        <th scope="col" class="text-center">—</th>
                                                        <th scope="col" class="text-center">—</th>
                                                        <th scope="col" class="text-center">—</th>
                                                        <th scope="col" class="text-center">—</th>
                                                        <th scope="col" class="text-center">—</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="timetableBody">
                                                    <!-- JS populates rows -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="accordion accordion-flush mt-4 border-top pt-3" id="timetableLegendAccordion">
                                        <div class="accordion-item border rounded-2 overflow-hidden">
                                            <h2 class="accordion-header" id="timetableLegendHeading">
                                                <button class="accordion-button collapsed py-2 small fw-semibold" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#timetableLegendCollapse"
                                                    aria-expanded="false" aria-controls="timetableLegendCollapse">
                                                    <i class="bi bi-journal-text me-2 text-primary" aria-hidden="true"></i>
                                                    Venues, cadres &amp; abbreviations (reference)
                                                </button>
                                            </h2>
                                            <div id="timetableLegendCollapse" class="accordion-collapse collapse" aria-labelledby="timetableLegendHeading"
                                                data-bs-parent="#timetableLegendAccordion">
                                                <div class="accordion-body small text-body-secondary">
                                                    <p class="mb-2 text-dark fw-semibold">Sample reference (from official time table format)</p>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <p class="fw-semibold text-primary mb-1">Venue abbreviations</p>
                                                            <ul class="list-unstyled mb-0 lh-sm">
                                                                <li><strong>TH</strong> — Tagore Hall</li>
                                                                <li><strong>AH</strong> — Ambedkar Hall (Aadharshila)</li>
                                                                <li><strong>SA</strong> — Sampoornanand Auditorium</li>
                                                                <li><strong>SPH</strong> — Sardar Patel Hall</li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="fw-semibold text-primary mb-1">Module tags</p>
                                                            <ul class="list-unstyled mb-0 lh-sm">
                                                                <li><strong>GM</strong> — Governance Module</li>
                                                                <li><strong>L</strong> — Law</li>
                                                                <li><strong>RAM</strong> — Rural &amp; Agriculture Module</li>
                                                                <li><strong>TM</strong> — Technology Module</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0 mt-2 fst-italic">Your live grid uses calendar data; expand rows to read full topic, faculty, and venue from each cell.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
@include('admin.calendar.partials.confirmation')

  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- Modern JavaScript with improved accessibility -->
<script>
console.log('FullCalendar loaded:', typeof FullCalendar !== 'undefined');

// Configuration object
const CalendarConfig = {
    courseMeta: @json($calendarCourseMeta ?? []),
    api: {
        events: "{{ route('calendar.event.calendar-details') }}",
        eventDetails: "{{ route('calendar.event.Singlecalendar-details') }}",
        store: "{{ route('calendar.event.store') }}",
        update: '/calendar/event-update/',
        delete: '/calendar/event-delete/',
        groupTypes: "{{ route('calendar.get.group.types') }}",
        subjectNames: "{{ route('calendar.get.subject.name') }}",
        weekTimetablePdf: "{{ route('calendar.week-timetable-pdf') }}",
        weekTimetablePrint: "{{ route('calendar.week-timetable-print') }}",
        weekTimetableExcel: "{{ route('calendar.week-timetable-excel') }}"
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
    minTime: '08:00',
    maxTime: '20:00'
};

function initCourseFilterChoices() {
    const select = document.getElementById('courseFilter');
    if (!select || typeof window.Choices === 'undefined') return;
    if (select.dataset.choicesInitialized === 'true') return;

    const courseChoicesOptions = {
        shouldSort: false,
        searchEnabled: true,
        searchPlaceholderValue: 'Search courses...',
        searchResultLimit: 50,
        searchFloor: 1,
        itemSelectText: '',
        allowHTML: false,
        classNames: {
            containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
            input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
            inputCloned: ['choices__input--cloned'],
            listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
            item: ['choices__item', 'dropdown-item', 'rounded-0'],
            itemSelectable: ['choices__item--selectable'],
            itemDisabled: ['choices__item--disabled', 'disabled'],
            itemChoice: ['choices__item--choice'],
            placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
            highlightedState: ['is-highlighted', 'active'],
            notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2']
        }
    };

    select._courseChoices = new Choices(select, courseChoicesOptions);
    select.dataset.choicesInitialized = 'true';
}

/**
 * Close "Filter by Course" Choices and keep it hidden while #eventModal is open.
 * (High z-index + stacking could leave the list painted on top of the modal even if API close fails.)
 */
function closeCourseFilterDropdown() {
    const select = document.getElementById('courseFilter');
    const inst = select && select._courseChoices;
    if (inst) {
        try {
            if (typeof inst.hideDropdown === 'function') {
                inst.hideDropdown();
            }
        } catch (e) { /* ignore */ }
    }
    const wrap = document.querySelector('.calendar-choices-bootstrap .choices');
    if (wrap) {
        wrap.classList.remove('is-open', 'is-flipped');
        wrap.querySelectorAll('.choices__list--dropdown, .choices__list[aria-expanded]').forEach((el) => {
            try {
                el.setAttribute('aria-hidden', 'true');
            } catch (e2) { /* ignore */ }
        });
    }
    try {
        const filterRoot = document.querySelector('.calendar-choices-bootstrap');
        if (filterRoot && document.activeElement && filterRoot.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    } catch (e3) { /* ignore */ }
    document.body.classList.add('calendar-suppress-course-filter-dropdown');
}

function releaseCourseFilterDropdownSuppression() {
    document.body.classList.remove('calendar-suppress-course-filter-dropdown');
    const wrap = document.querySelector('.calendar-choices-bootstrap .choices');
    if (wrap) {
        wrap.querySelectorAll('.choices__list--dropdown, .choices__list[aria-expanded]').forEach((el) => {
            try {
                el.removeAttribute('aria-hidden');
            } catch (e) { /* ignore */ }
        });
    }
    const select = document.getElementById('courseFilter');
    const inst = select && select._courseChoices;
    if (inst && typeof inst.hideDropdown === 'function') {
        try {
            inst.hideDropdown();
        } catch (e2) { /* ignore */ }
    }
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
        this.eventsLoaded = false; // Track if events have been loaded initially
        this.init();
    }

    init() {
        try {
            console.log('Initializing calendar manager...');
            this.initFullCalendar();
            
            try { this.bindEvents(); } catch (e) { console.error('bindEvents error:', e); }
            try { this.setupAccessibility(); } catch (e) { console.error('setupAccessibility error:', e); }
            try { this.validateDates(); } catch (e) { console.error('validateDates error:', e); }
            try { this.updateCurrentWeek(); } catch (e) { console.error('updateCurrentWeek error:', e); }
            try { this.observeMoreLinksChanges(); } catch (e) { console.error('observeMoreLinksChanges error:', e); }
            try { this.initDensity(); } catch (e) { console.error('initDensity error:', e); }
            
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
            slotLabelInterval: '00:30:00',
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
                    eventMaxStack: 8
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
            select: this.handleDateSelect.bind(this),
            eventDidMount: this.setEventAccessibility.bind(this),
            dayCellDidMount: this.setDayCellAccessibility.bind(this)
        });

        this.calendar.render();
        console.log('Calendar rendered');
        
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

    fetchEvents(info, successCallback, failureCallback) {
        // Build URL with course filter
        let url = CalendarConfig.api.events;
        const params = new URLSearchParams();
        
        if (info.start) {
            params.append('start', info.start.toISOString().split('T')[0]);
        }
        if (info.end) {
            params.append('end', info.end.toISOString().split('T')[0]);
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
            console.log('Events after filtering:', filteredData.length);
            successCallback(filteredData);
        })
        .catch(error => {
            console.error('Error fetching events:', error);
            this.showNotification('Failed to load calendar events. Please refresh the page.', 'danger');
            failureCallback(error);
        });
    }

    handleWeekendVisibility(events) {
        // Wait for calendar to be fully rendered before adjusting days
        if (!this.calendar || !events || events.length === 0) {
            // If no events yet, just mark as loaded and don't hide days
            this.eventsLoaded = true;
            return;
        }
        
        // Check if any events fall on Saturday (day 6)
        const hasSaturdayEvents = events.some(event => {
            const eventDate = new Date(event.start);
            return eventDate.getDay() === 6; // 6 = Saturday
        });

        // Update hiddenDays: always hide Sunday (0), conditionally hide Saturday (6)
        const hiddenDays = hasSaturdayEvents ? [0] : [0, 6];
        
        // Use setTimeout to ensure calendar is fully rendered
        setTimeout(() => {
            this.calendar.setOption('hiddenDays', hiddenDays);
            this.eventsLoaded = true;
        }, 50);
    }

    updateWeekendVisibility() {
        // Get all events currently in the calendar
        const events = this.calendar.getEvents();
        
        // Check if any events fall on Saturday (day 6)
        const hasSaturdayEvents = events.some(event => {
            const eventDate = new Date(event.start);
            return eventDate.getDay() === 6;
        });

        // Update hiddenDays: always hide Sunday (0), conditionally hide Saturday (6)
        const newHiddenDays = hasSaturdayEvents ? [0] : [0, 6];
        const currentHiddenDays = this.calendar.getOption('hiddenDays') || [];
        
        // Only update if changed to prevent unnecessary re-renders
        if (JSON.stringify(newHiddenDays.sort()) !== JSON.stringify(currentHiddenDays.sort())) {
            this.calendar.setOption('hiddenDays', newHiddenDays);
        }
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
        // Style all "+ more" links including timeGrid views
        const moreLinks = document.querySelectorAll(
            '.fc-daygrid-day-more-link, .fc-more-link, .fc-timegrid-more-link, .fc-daygrid-day-frame a[data-date], .fc-timegrid a[aria-label*="more"]');
        moreLinks.forEach(link => {
            if (link.textContent.includes('+') || link.textContent.toLowerCase().includes('more')) {
                // Check if it's a timeGrid link (smaller styling)
                const isTimeGrid = link.closest('.fc-timegrid') !== null;
                
                link.style.fontSize = isTimeGrid ? '1rem' : '1.25rem';
                link.style.fontWeight = '700';
                link.style.color = '#ffffff';
                link.style.backgroundColor = '#004a93';
                link.style.padding = isTimeGrid ? '0.4rem 0.6rem' : '0.5rem 0.75rem';
                link.style.borderRadius = '0.375rem';
                link.style.display = 'inline-block';
                link.style.textDecoration = 'none';
                link.style.background = 'linear-gradient(135deg, #004a93, #0066cc)';
                link.style.transition = 'all 0.2s ease';
                link.style.boxShadow = '0 2px 4px rgba(0, 74, 147, 0.2)';

                link.addEventListener('mouseenter', () => {
                    link.style.background = 'linear-gradient(135deg, #003366, #004a93)';
                    link.style.transform = isTimeGrid ? 'scale(1.05)' : 'scale(1.08)';
                    link.style.boxShadow = '0 4px 12px rgba(0, 74, 147, 0.4)';
                });

                link.addEventListener('mouseleave', () => {
                    link.style.background = 'linear-gradient(135deg, #004a93, #0066cc)';
                    link.style.transform = 'scale(1)';
                    link.style.boxShadow = '0 2px 4px rgba(0, 74, 147, 0.2)';
                });
            }
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

    renderEventContent(arg) {
        // Normalize type and derive color
        const type = (arg.event.extendedProps.type || arg.event.extendedProps.event_type || arg.event.extendedProps
            .session_type || '').toString();
        const typeAttr = type.toLowerCase();
        const fallbackIdx = arg.event.id ?
            parseInt(arg.event.id) % CalendarConfig.colors.length :
            arg.event._index % CalendarConfig.colors.length;
        const cardColor = CalendarConfig.eventTypeColors[typeAttr] || CalendarConfig.colors[fallbackIdx];

        const topic = arg.event.title || '';
        const venue = arg.event.extendedProps.vanue || '';
        const faculty = arg.event.extendedProps.faculty_name || '';
        const idStr = (arg.event.id || arg.event._def?.publicId || Math.random().toString(36).slice(2));
        const titleId = `fc-evt-${idStr}-title`;
        const descId = `fc-evt-${idStr}-desc`;

        return {
            html: `
                <div class="fc-event-card" 
                     style="border-left-color: ${cardColor};"
                     tabindex="0"
                     role="button"
                     aria-labelledby="${titleId}"
                     aria-describedby="${descId}"
                     ${type ? `data-event-type="${typeAttr}"` : ''}>
                    <div class="d-flex align-items-start justify-content-between gap-2" style="margin-bottom: 0.65rem;">
                        <div class="event-title flex-grow-1" id="${titleId}" style="color: ${cardColor}; flex: 1; min-width: 0;">
                            ${topic}
                        </div>
                        ${type ? `<span class="event-badge flex-shrink-0" style="margin-left: 0.5rem;">${type}</span>` : ''}
                    </div>
                    <div class="event-meta" style="width: 100%;">
                        ${arg.timeText ? `<span class=\"meta-item meta-item--time\"><i class=\"bi bi-clock-fill\" aria-hidden=\"true\"></i><span>${arg.timeText}</span></span>` : ''}
                        ${venue ? `<span class=\"meta-item meta-item--venue\"><i class=\"bi bi-geo-alt-fill\" aria-hidden=\"true\"></i><span>${venue}</span></span>` : ''}
                        ${faculty ? `<span class=\"meta-item meta-item--faculty\"><i class=\"bi bi-person-fill\" aria-hidden=\"true\"></i><span>${faculty}</span></span>` : ''}
                    </div>
                    <span class="visually-hidden" id="${descId}">${type ? `${type} ` : ''}${arg.timeText ? `${arg.timeText} ` : ''}${venue ? `at ${venue} ` : ''}${faculty ? `with ${faculty}` : ''}</span>
                </div>
            `
        };
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
                        const evt = cell.querySelector('.fc-event, .fc-event-card');
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
        // Close any open popover when an event is clicked
        this.closePopover();
        
        this.currentEventId = info.event.id;
        this.loadEventDetails(info.event.id);
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
        // Update modal content
        document.getElementById('eventTitle').textContent = 'Event Details';
        document.getElementById('eventTopic').textContent = data.topic || '';
        document.getElementById('eventDate').textContent =
            new Date(data.start).toLocaleDateString('en-IN', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        document.getElementById('eventfaculty').textContent = data.faculty_name || '';
        document.getElementById('eventVanue').textContent = data.venue_name || '';
        document.getElementById('eventVanue').textContent = data.venue_name || '';
        document.getElementById('eventclasssession').textContent = data.class_session || '';
        document.getElementById('eventgroupname').textContent = data.group_name || '';
        document.getElementById('internal_faculty_name_show').textContent = data.internal_faculty || '';

        // Set edit/delete button data
        const editBtn = document.getElementById('editEventBtn');
        const deleteBtn = document.getElementById('deleteEventBtn');

        if (editBtn) editBtn.dataset.id = data.id;
        if (deleteBtn) deleteBtn.dataset.id = data.id;

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('eventDetails'));
        modal.show();
    }

    handleDateSelect(info) {
        if (!@json(hasRole('Training') || hasRole('Admin') ||  hasRole('Training-MCTP') || hasRole('IST'))) return;

        this.resetEventForm();
        this.setFormDate(info.start);

        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        modal.show();
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
        document.getElementById('eventModalTitle').textContent = 'Add Calendar Event';
        document.querySelector('.btn-text').textContent = 'Add Event';
        document.getElementById('submitEventBtn').dataset.action = 'create';

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
        // View toggle buttons (use currentTarget so clicks on inner icons hit the button)
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const t = e.currentTarget;
                if (t) {
                    this.toggleView(t);
                }
            });
        });

        // Week navigation buttons (List View)
        document.getElementById('prevWeekBtn')?.addEventListener('click', () => this.navigateWeek(-1));
        document.getElementById('nextWeekBtn')?.addEventListener('click', () => this.navigateWeek(1));
        document.getElementById('currentWeekBtn')?.addEventListener('click', () => this.navigateWeek(0));

        const weekTimetableExportParams = () => {
            const params = new URLSearchParams();
            params.set('week_offset', String(this.listViewWeekOffset ?? 0));
            if (this.selectedCourseId) {
                params.set('course_id', String(this.selectedCourseId));
            }
            return params;
        };
        const weekTimetableQs = () => weekTimetableExportParams().toString();

        document.getElementById('weekTimetablePrintBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            const base = CalendarConfig.api.weekTimetablePrint;
            window.open(`${base}?${weekTimetableQs()}`, '_blank', 'noopener');
        });
        document.getElementById('weekTimetablePdfViewBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            const base = CalendarConfig.api.weekTimetablePdf;
            window.open(`${base}?${weekTimetableQs()}`, '_blank', 'noopener');
        });
        document.getElementById('weekTimetablePdfDownloadBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            const base = CalendarConfig.api.weekTimetablePdf;
            const params = weekTimetableExportParams();
            params.set('download', '1');
            window.location.assign(`${base}?${params.toString()}`);
        });
        document.getElementById('weekTimetableExcelBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            const base = CalendarConfig.api.weekTimetableExcel;
            window.location.assign(`${base}?${weekTimetableQs()}`);
        });

        document.getElementById('weekTimetablePrintPageBtn')?.addEventListener('click', () => {
            const list = document.getElementById('eventListView');
            if (!list || list.classList.contains('d-none')) {
                window.alert('Switch to “Timetable sheet” first, then use Print sheet.');
                return;
            }
            document.body.classList.add('timetable-print-only');
            const done = () => {
                document.body.classList.remove('timetable-print-only');
                window.removeEventListener('afterprint', done);
            };
            window.addEventListener('afterprint', done);
            window.setTimeout(() => window.print(), 150);
            window.setTimeout(done, 20000);
        });

        // Form submission
        document.getElementById('eventForm').addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Dynamic field dependencies
        document.getElementById('Course_name').addEventListener('change', () => this.loadGroupTypes());
        document.getElementById('subject_module').addEventListener('change', () => this.loadSubjectNames());
        document.getElementById('faculty').addEventListener('change', () => this.updateFacultyType());
        document.getElementById('faculty_type').addEventListener('change', () => this.updateCheckboxState());

        // Shift type toggles
        document.querySelectorAll('input[name="shift_type"]').forEach(radio => {
            radio.addEventListener('change', () => this.toggleShiftFields());
        });

        // Full day checkbox
        document.getElementById('fullDayCheckbox').addEventListener('change', (e) => {
            this.toggleFullDayFields(e.target.checked);
        });

        // Feedback checkbox
        document.getElementById('feedback_checkbox').addEventListener('change', () => {
            this.toggleFeedbackDependencies();
        });

        // Edit/Delete buttons
        document.getElementById('editEventBtn')?.addEventListener('click', () => this.loadEventForEdit());
        document.getElementById('deleteEventBtn')?.addEventListener('click', () => this.confirmDelete());

        // Create event button
        document.getElementById('createEventButton')?.addEventListener('click', () => {
            this.resetEventForm();
        });

        // List view: open details on click/keyboard
        const listView = document.getElementById('eventListView');
        listView?.addEventListener('click', (e) => {
            const card = e.target.closest('.list-event-card');
            if (card?.dataset?.id) {
                this.loadEventDetails(card.dataset.id);
            }
        });
        listView?.addEventListener('keydown', (e) => {
            const card = e.target.closest('.list-event-card');
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
        if (!button || !button.dataset || !button.dataset.view) {
            return;
        }

        // Update button states
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        button.classList.add('active');
        button.setAttribute('aria-pressed', 'true');

        const view = button.dataset.view;
        const calendarEl = document.getElementById('calendar');
        const listViewEl = document.getElementById('eventListView');

        if (view === 'list') {
            calendarEl.style.display = 'none';
            listViewEl.classList.remove('d-none');
            this.loadListView();
        } else {
            calendarEl.style.display = '';
            listViewEl.classList.add('d-none');
            this.calendar.changeView(this.getCalendarView(view));
            // Style "+ more" links after view change
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
            document.getElementById('start_time').value = '08:00';
            document.getElementById('end_time').value = '20:00';
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
            `${CalendarConfig.api.update}${this.currentEventId}` :
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
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
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
        const eventId = document.getElementById('editEventBtn').dataset.id;

        try {
            const response = await fetch(`/calendar/event-edit/${eventId}`);
            const event = await response.json();

            await this.populateEditForm(event);

            // Update modal for edit
            document.getElementById('eventModalTitle').textContent = 'Edit Event';
            document.querySelector('.btn-text').textContent = 'Update Event';
            document.getElementById('submitEventBtn').dataset.action = 'edit';
            document.getElementById('start_datetime').removeAttribute('readonly');

            // Show modal
            bootstrap.Modal.getInstance(document.getElementById('eventDetails')).hide();
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();

        } catch (error) {
            this.showNotification('Error loading event for editing', 'danger');
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
        // Handle multiple faculty selection
        const facultyIds = Array.isArray(event.faculty_master) ? event.faculty_master : [event.faculty_master];
        const facultySelectEl = document.getElementById('faculty');
        if (facultySelectEl) {
            const normalizedFacultyIds = facultyIds.map(id => String(id));
            Array.from(facultySelectEl.options).forEach(option => {
                option.selected = normalizedFacultyIds.includes(String(option.value));
            });
            facultySelectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
        document.getElementById('faculty_type').value = event.faculty_type;
        document.getElementById('vanue').value = event.venue_id;

        if (window.calendarModalChoices?.syncById) {
            window.calendarModalChoices.syncById('Course_name');
            window.calendarModalChoices.syncById('subject_module');
            window.calendarModalChoices.syncById('subject_name');
            window.calendarModalChoices.syncById('faculty');
            window.calendarModalChoices.syncById('faculty_type');
            window.calendarModalChoices.syncById('vanue');
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

        // Trigger dependent loads (await group types to ensure it completes)
        await this.loadGroupTypesForEdit(event);
        this.loadSubjectNamesForEdit(event);

        // Store current event ID
        this.currentEventId = event.pk;
        await this.updateinternal_faculty(event.faculty_type);
        if(event.faculty_type == 2){
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
        const eventId = document.getElementById('deleteEventBtn').dataset.id;

        // Show confirmation modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        document.getElementById('confirmAction').onclick = () => this.deleteEvent(eventId);
        confirmModal.show();
    }

    async deleteEvent(eventId) {
        try {
            const response = await fetch(`${CalendarConfig.api.delete}${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) throw new Error('Delete failed');

            this.showNotification('Event deleted successfully', 'success');

            // Close modals and refresh
            bootstrap.Modal.getInstance(document.getElementById('eventDetails')).hide();
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
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
        // Calculate the start date of the week based on offset
        const today = new Date();
        const dayOfWeek = today.getDay();
        const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);

        // Create new date to avoid mutation
        const weekStart = new Date(today.getFullYear(), today.getMonth(), diff);

        // Apply week offset
        weekStart.setDate(weekStart.getDate() + (weekOffset * 7));

        // Set week end (Friday)
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 4); // Monday to Friday

        // Filter events that fall within this week
        return events.filter(event => {
            const eventDate = new Date(event.start);
            const eventDay = eventDate.getDate();
            const eventMonth = eventDate.getMonth();
            const eventYear = eventDate.getFullYear();

            const startDay = weekStart.getDate();
            const startMonth = weekStart.getMonth();
            const startYear = weekStart.getFullYear();

            const endDay = weekEnd.getDate();
            const endMonth = weekEnd.getMonth();
            const endYear = weekEnd.getFullYear();

            // Compare dates properly
            const eventDateObj = new Date(eventYear, eventMonth, eventDay);
            const startDateObj = new Date(startYear, startMonth, startDay);
            const endDateObj = new Date(endYear, endMonth, endDay);

            return eventDateObj >= startDateObj && eventDateObj <= endDateObj;
        });
    }

    async loadListView() {
        try {
            // Build URL with course filter
            let url = CalendarConfig.api.events;
            const params = new URLSearchParams();
            if (this.selectedCourseId) {
                params.append('course_id', this.selectedCourseId);
            }
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const events = await response.json();

            // Calculate week start date based on offset
            const today = new Date();
            const dayOfWeek = today.getDay();
            // Monday = 1, Sunday = 0
            const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
            const weekStart = new Date(today.getFullYear(), today.getMonth(), diff);
            weekStart.setDate(weekStart.getDate() + (this.listViewWeekOffset * 7));

            // Week badge: programme week from course start_year (matches official PDF), else ISO-style week
            const date = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
            const jan4 = new Date(date.getFullYear(), 0, 4);
            const monday = new Date(jan4);
            monday.setDate(monday.getDate() - monday.getDay() + 1);
            const timeDiff = date - monday;
            const weekDiff = Math.floor(timeDiff / (7 * 24 * 60 * 60 * 1000));
            let weekNum = weekDiff + 1;

            const cmeta = CalendarConfig.courseMeta && this.selectedCourseId
                ? CalendarConfig.courseMeta[String(this.selectedCourseId)]
                : null;
            if (cmeta && cmeta.start_year) {
                try {
                    const prog = new Date(cmeta.start_year);
                    if (!Number.isNaN(prog.getTime())) {
                        const progMon = this.startOfMondayTs(prog);
                        const wsMon = this.startOfMondayTs(weekStart);
                        const days = Math.round((wsMon - progMon) / 86400000);
                        if (!Number.isNaN(days) && days >= 0) {
                            weekNum = Math.floor(days / 7) + 1;
                        }
                    }
                } catch (e) {
                    console.warn('Programme week number', e);
                }
            }

            const weekElement = document.getElementById('currentWeekNumber');
            if (weekElement) {
                weekElement.textContent = weekNum;
            }

            this.updateTimetablePdfBanner(weekStart);

            // Update table header with week dates
            this.updateTableHeader(weekStart);

            // Debug: Log the week being displayed
            console.log('List view - Week offset:', this.listViewWeekOffset);
            console.log('Week start:', weekStart);
            console.log('Total events:', events.length);

            // Filter and render (timetable sheet omits holidays; main calendar still receives them)
            const filteredEvents = this.getEventsForWeek(events, this.listViewWeekOffset)
                .filter((e) => !this.isCalendarHoliday(e));
            console.log('Filtered events for this week:', filteredEvents.length);
            this.renderListView(filteredEvents, weekStart);
            this.updateWeekRangeText(weekStart);
        } catch (error) {
            console.error('Error loading list view:', error);
        }
    }

    /**
     * List view banner — course line (matches official time table header style).
     */
    updateTimetablePdfBanner(weekStart) {
        const courseEl = document.getElementById('timetableCourseTitle');
        const periodEl = document.getElementById('timetableCoursePeriod');
        if (!courseEl) return;

        const sel = document.getElementById('courseFilter');
        const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        const fromSelect = opt && opt.value ? String(opt.text).trim() : '';

        const setPeriodFromMeta = () => {
            if (!periodEl) return;
            const cmeta = CalendarConfig.courseMeta && this.selectedCourseId
                ? CalendarConfig.courseMeta[String(this.selectedCourseId)]
                : null;
            if (cmeta && cmeta.start_year && cmeta.end_date) {
                try {
                    const s = new Date(cmeta.start_year);
                    const e = new Date(cmeta.end_date);
                    if (!Number.isNaN(s.getTime()) && !Number.isNaN(e.getTime())) {
                        const fmt = (d) => d.toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
                        periodEl.textContent = '(' + fmt(s) + ' to ' + fmt(e) + ')';
                        periodEl.classList.remove('d-none');
                        return;
                    }
                } catch (err) {
                    /* ignore */
                }
            }
            periodEl.textContent = '';
            periodEl.classList.add('d-none');
        };

        if (fromSelect) {
            courseEl.textContent = fromSelect;
            setPeriodFromMeta();
            return;
        }
        if (Array.isArray(this.courses) && this.selectedCourseId) {
            const c = this.courses.find(x => String(x.pk) === String(this.selectedCourseId));
            if (c && (c.course_name || c.name)) {
                courseEl.textContent = (c.course_name || c.name).trim();
                setPeriodFromMeta();
                return;
            }
        }
        courseEl.textContent = 'Academic timetable — select a course filter when available';
        setPeriodFromMeta();
    }

    /** Monday 00:00 of the list-view week (same logic as loadListView). */
    getListViewWeekStart() {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
        const weekStart = new Date(today.getFullYear(), today.getMonth(), diff);
        weekStart.setDate(weekStart.getDate() + (this.listViewWeekOffset * 7));
        weekStart.setHours(0, 0, 0, 0);
        return weekStart;
    }

    escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * One row of tea/lunch notices per weekday (matches PDF break row).
     */
    buildBreakNoticeRowHtml(events, weekStart) {
        const dayKeys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        const inners = dayKeys.map((_, i) => {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            const parts = [];
            events.forEach((ev) => {
                if (this.isCalendarHoliday(ev)) return;
                const evd = new Date(ev.start);
                if (evd.getFullYear() !== d.getFullYear() || evd.getMonth() !== d.getMonth() || evd.getDate() !== d.getDate()) {
                    return;
                }
                const title = String(ev.title || '').trim();
                if (!/\b(tea\s*break|lunch\s*break)\b/i.test(title)) return;
                const p = this.getListEventProps(ev);
                const line = title + (p.class_session ? ': ' + p.class_session : '');
                parts.push(line);
            });
            return parts.length ? parts.join(' ') : '';
        });
        if (!inners.some(Boolean)) {
            return '';
        }
        const cells = inners.map((inner) => `<td class="event-cell text-center">${inner ? this.escapeHtml(inner) : ''}</td>`);
        return `<tr class="break-notes-row break-row"><th scope="row" class="time-column"></th>${cells.join('')}</tr>`;
    }

    /** VENUES: … line from distinct group + venue (matches official sheet). */
    buildVenueSummaryLine(events) {
        const labels = [];
        const seen = new Set();
        events.forEach((ev) => {
            if (this.isCalendarHoliday(ev)) return;
            const title = String(ev.title || '').trim();
            if (title && /\b(tea\s*break|lunch\s*break)\b/i.test(title)) return;
            const p = this.getListEventProps(ev);
            const g = String(p.group_name || '').trim();
            const v = String(p.vanue || p.venue_name || '').trim();
            if (!g && !v) return;
            const label = g ? `${g}: ${v}` : v;
            const key = label.toLowerCase();
            if (seen.has(key)) return;
            seen.add(key);
            labels.push(label);
        });
        if (!labels.length) return '';
        return 'VENUES: ' + labels.join(', ');
    }

    updateTableHeader(weekStart) {
        const table = document.getElementById('timetableTable');
        if (!table) {
            console.warn('Table #timetableTable not found');
            return;
        }

        const thead = table.querySelector('thead');
        if (!thead) {
            console.warn('Table header not found');
            return;
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const dateCells = days.map((_, index) => {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + index);
            const dd = String(date.getDate()).padStart(2, '0');
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const yyyy = date.getFullYear();
            const dateStr = `${dd}.${mm}.${yyyy}`;
            return `<th scope="col" class="text-center">${dateStr}</th>`;
        }).join('');

        const dayCells = days.map((d) => `<th scope="col" class="text-center">${d}</th>`).join('');

        thead.innerHTML = `
            <tr class="day-names-row">
                <th scope="col" rowspan="2" class="time-column align-middle">TIME</th>
                ${dayCells}
            </tr>
            <tr class="date-row">
                ${dateCells}
            </tr>
        `;
    }

    renderListView(events, weekStart) {
        const tbody = document.getElementById('timetableBody');
        const ws = weekStart || this.getListViewWeekStart();

        if (!events.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center p-5">
                        <div class="empty-state">
                            <i class="bi bi-calendar-x display-5 text-muted mb-3"></i>
                            <p class="text-muted mb-3">No events scheduled</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        // Group events by time slot (same keys / order as PDF export)
        const timeSlots = this.groupEventsByTime(events);
        const sortedTimes = Object.keys(timeSlots).sort((a, b) => this.compareWeekTimetableTimeSlots(a, b));

        const breakRow = this.buildBreakNoticeRowHtml(events, ws);
        const venueText = this.buildVenueSummaryLine(events);
        const venueRow = venueText
            ? `<tr class="venue-summary-row"><th scope="row" class="time-column"></th><td colspan="5" class="event-cell text-center">${this.escapeHtml(venueText)}</td></tr>`
            : '';

        let html = breakRow + venueRow;
        sortedTimes.forEach((time) => {
            const dayEvents = timeSlots[time];
            const sample = this.pickSampleEventForTimeSlot(dayEvents);
            const timeCell = sample ? this.formatTimeColumnDisplay(sample) : String(time).replace(/\s+/g, '\n');
            html += `
                <tr>
                    <th scope="row" class="time-slot time-column">${timeCell.replace(/\n/g, '<br>')}</th>
                    ${['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].map(day => `
                        <td class="event-cell">
                            ${dayEvents[day] ? this.renderGroupedDayCell(dayEvents[day]) : ''}
                        </td>
                    `).join('')}
                </tr>
            `;
        });

        tbody.innerHTML = html;
        this.applyBreakLunchRowStyles();
        this.initializeScrollIndicators();
    }

    updateWeekRangeText(weekStart) {
        const el = document.getElementById('weekRangeText');
        if (!el) return;
        const fmt = (d) => {
            const x = new Date(d);
            const dd = String(x.getDate()).padStart(2, '0');
            const mm = String(x.getMonth() + 1).padStart(2, '0');
            const yyyy = x.getFullYear();
            return `${dd}.${mm}.${yyyy}`;
        };
        const startStr = fmt(weekStart);
        const end = new Date(weekStart);
        end.setDate(end.getDate() + 6);
        const endStr = fmt(end);
        el.innerHTML = `<i class="bi bi-calendar-week me-1" aria-hidden="true"></i><span class="fw-medium text-dark">${startStr}</span> <span class="text-muted">to</span> <span class="fw-medium text-dark">${endStr}</span>`;
    }

    pad2(n) {
        return String(n).padStart(2, '0');
    }

    normalizeTimetableSessionString(s) {
        if (s == null || s === '') {
            return '';
        }
        let t = String(s).trim();
        t = t.replace(/[\u2013\u2014\u2212]/g, '-').replace(/\s*hrs\.?\s*$/i, '').trim();

        return t;
    }

    /** @returns {[string, string]|null} */
    splitSessionTimeRange(raw) {
        const s = this.normalizeTimetableSessionString(raw);
        if (!s) {
            return null;
        }
        const patterns = [/\s+to\s+/i, /\s*-\s*/];
        for (let i = 0; i < patterns.length; i += 1) {
            const parts = s.split(patterns[i]);
            if (parts.length === 2) {
                const a = parts[0].trim();
                const b = parts[1].trim();
                if (a && b) {
                    return [a, b];
                }
            }
        }

        return null;
    }

    /** Monday 00:00 local time as epoch ms */
    startOfMondayTs(d) {
        const x = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const dow = x.getDay();
        x.setDate(x.getDate() + (dow === 0 ? -6 : 1 - dow));
        x.setHours(0, 0, 0, 0);
        return x.getTime();
    }

    /** Parse class_session start on calendar day → epoch ms, or null */
    weekTimetableSessionSlotStartMs(event, dayDate) {
        const p = this.getListEventProps(event);
        const pair = this.splitSessionTimeRange(p.class_session);
        let left = '';
        if (pair) {
            left = pair[0].trim();
        } else {
            const sessNorm = this.normalizeTimetableSessionString(p.class_session || '');
            left = sessNorm;
        }
        if (!left) {
            return null;
        }
        const y = dayDate.getFullYear();
        const mo = dayDate.getMonth();
        const da = dayDate.getDate();
        const probe = new Date(`${y}-${this.pad2(mo + 1)}-${this.pad2(da)} ${left}`);
        return Number.isNaN(probe.getTime()) ? null : probe.getTime();
    }

    /**
     * Start/end minutes from local midnight (matches PHP weekTimetableSlotWindowMinutes).
     * Same clock band on all weekdays → one timetable row (serial 9:00 … 17:30 style order).
     *
     * @returns {[number, number]}  Use t1 negative when no usable clock (sort last).
     */
    weekTimetableSlotWindowMinutes(event) {
        const p = this.getListEventProps(event);
        const st = event.start ? new Date(event.start) : null;
        const pair = this.splitSessionTimeRange(p.class_session);
        if (pair) {
            const left = this.parseClockTo24h(pair[0].trim());
            const right = this.parseClockTo24h(pair[1].trim());
            if (left && right) {
                const [h1, m1] = left.split(':').map((x) => parseInt(x, 10));
                const [h2, m2] = right.split(':').map((x) => parseInt(x, 10));
                let t1 = h1 * 60 + m1;
                let t2 = h2 * 60 + m2;
                if (t2 < t1) {
                    t2 += 1440;
                }
                return [t1, t2];
            }
        }
        if (event.allDay && st && !Number.isNaN(st.getTime())) {
            const sessNorm = this.normalizeTimetableSessionString(p.class_session || '');
            if (sessNorm) {
                const single = this.parseClockTo24h(sessNorm);
                if (single) {
                    const [h, mi] = single.split(':').map((x) => parseInt(x, 10));
                    const t1 = h * 60 + mi;
                    return [t1, Math.min(t1 + 50, 24 * 60)];
                }
            }
            const ms = this.weekTimetableSessionSlotStartMs(event, st);
            if (ms != null) {
                const d = new Date(ms);
                const t1 = d.getHours() * 60 + d.getMinutes();
                return [t1, Math.min(t1 + 50, 24 * 60)];
            }
            return [-1, -1];
        }
        if (!st || Number.isNaN(st.getTime())) {
            return [-1, -1];
        }
        const t1 = st.getHours() * 60 + st.getMinutes();
        const en = event.end ? new Date(event.end) : new Date(st.getTime() + 50 * 60 * 1000);
        let t2 = en.getHours() * 60 + en.getMinutes();
        const sameCalDay = st.getFullYear() === en.getFullYear()
            && st.getMonth() === en.getMonth()
            && st.getDate() === en.getDate();
        if (!sameCalDay && t2 < t1) {
            t2 += 1440;
        }
        return [t1, t2];
    }

    /** Same slot key as PHP weekTimetableSlotSortKey (clock-only band). */
    weekTimetableSlotSortKey(event) {
        const [t1, t2] = this.weekTimetableSlotWindowMinutes(event);
        if (t1 < 0) {
            return '999990_999990';
        }
        const pad5 = (n) => String(Math.min(n, 99999)).padStart(5, '0');
        return `${pad5(t1)}_${pad5(t2)}`;
    }

    parseClockTo24h(chunk) {
        const c = String(chunk == null ? '' : chunk).trim();
        if (!c) {
            return null;
        }
        const m = c.match(/^(\d{1,2})(\d{2})$/);
        if (m) {
            const h = parseInt(m[1], 10);
            const mi = parseInt(m[2], 10);
            if (h <= 23 && mi <= 59) {
                return `${this.pad2(h)}:${this.pad2(mi)}`;
            }
        }
        const d = new Date(`1970-01-01 ${c}`);
        if (Number.isNaN(d.getTime())) {
            return null;
        }
        return `${this.pad2(d.getHours())}:${this.pad2(d.getMinutes())}`;
    }

    /** Official sheet: "HH:mm\nto\nHH:mm" from class_session when possible (including all-day rows). */
    formatTimeColumnDisplay(event) {
        const p = this.getListEventProps(event);
        const pair = this.splitSessionTimeRange(p.class_session);
        if (pair) {
            const left = this.parseClockTo24h(pair[0]);
            const right = this.parseClockTo24h(pair[1]);
            if (left && right) {
                return `${left}\nto\n${right}`;
            }
        }
        const sessNorm = this.normalizeTimetableSessionString(p.class_session || '');
        if (event.allDay && sessNorm) {
            const single = this.parseClockTo24h(sessNorm);
            if (single) {
                return `${single}\nto\n${single}`;
            }
            return 'All Day';
        }
        if (event.allDay) {
            return 'All Day';
        }
        const st = event.start ? new Date(event.start) : null;
        const en = event.end ? new Date(event.end) : null;
        if (!st || Number.isNaN(st.getTime())) {
            return '—';
        }
        const e2 = (!en || Number.isNaN(en.getTime())) ? new Date(st.getTime() + 50 * 60 * 1000) : en;
        return `${this.pad2(st.getHours())}:${this.pad2(st.getMinutes())}\nto\n${this.pad2(e2.getHours())}:${this.pad2(e2.getMinutes())}`;
    }

    compareWeekTimetableTimeSlots(a, b) {
        return String(a).localeCompare(String(b));
    }

    pickSampleEventForTimeSlot(dayMap) {
        for (const d of ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']) {
            if (dayMap[d] && dayMap[d][0]) {
                return dayMap[d][0];
            }
        }
        return null;
    }

    groupEventsByTime(events) {
        const groups = {};

        events.forEach(event => {
            const slotKey = this.weekTimetableSlotSortKey(event);

            if (!groups[slotKey]) {
                groups[slotKey] = {};
            }

            const day = new Date(event.start).getDay();
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const dayName = dayNames[day];

            if (!groups[slotKey][dayName]) {
                groups[slotKey][dayName] = [];
            }

            groups[slotKey][dayName].push(event);
        });

        return groups;
    }

    /** Calendar / API holiday entries — hidden on the revised timetable sheet only. */
    isCalendarHoliday(event) {
        const ex = event.extendedProps || {};
        return event.type === 'holiday'
            || ex.type === 'holiday'
            || String(event.id || '').startsWith('holiday_');
    }

    /**
     * Normalise API / FullCalendar event fields for list view (flat JSON + extendedProps).
     */
    getListEventProps(event) {
        const ex = event.extendedProps || {};
        const gn = event.group_name ?? ex.group_name ?? '';
        const gns = event.group_names ?? ex.group_names;
        const namesArr = Array.isArray(gns) ? gns : (gn ? String(gn).split(',').map(s => s.trim()).filter(Boolean) : []);
        return {
            ...ex,
            group_name: gn || namesArr.join(', '),
            group_names: namesArr,
            class_session: event.class_session ?? ex.class_session ?? '',
            vanue: event.vanue ?? ex.vanue ?? '',
            venue_name: event.venue_name ?? ex.venue_name ?? '',
            faculty_name: event.faculty_name ?? ex.faculty_name ?? '',
            topic: event.topic ?? ex.topic ?? '',
        };
    }

    /**
     * Map group text to a PDF-style row letter (A, B, …) when possible.
     */
    inferPdfGroupRowLetter(props, fallbackIndex = 0) {
        const raw = (props.group_name || '').trim();
        const blob = raw.toLowerCase();
        const m = blob.match(/\bgroup\s*([a-z])\b/i);
        if (m) return m[1].toUpperCase();
        if (blob.includes('group a')) return 'A';
        if (blob.includes('group b')) return 'B';
        if (blob.includes('group c')) return 'C';
        if (blob.includes('group d')) return 'D';
        if (Array.isArray(props.group_names) && props.group_names.length) {
            const first = String(props.group_names[0]).trim();
            const m2 = first.toLowerCase().match(/\bgroup\s*([a-z])\b/i);
            if (m2) return m2[1].toUpperCase();
        }
        return '';
    }

    /**
     * Split concurrent sessions into parallel rows (Group A / Group B style).
     */
    bucketEventsForPdfRows(events) {
        const list = Array.isArray(events) ? [...events] : [events];
        if (!list.length) return [];

        const isHoliday = (ev) => ev.type === 'holiday' || String(ev.id || '').startsWith('holiday_');
        if (list.every(isHoliday)) {
            return [{ letter: '', events: list }];
        }

        const letters = list.map((ev, idx) => {
            const p = this.getListEventProps(ev);
            return { ev, letter: this.inferPdfGroupRowLetter(p, idx) };
        });

        const allBlank = letters.every(x => !x.letter);
        if (allBlank && list.length > 1) {
            return list.map((ev, i) => ({
                letter: String.fromCharCode(65 + i),
                events: [ev],
            }));
        }

        const map = new Map();
        letters.forEach(({ ev, letter }) => {
            const key = letter || '_';
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(ev);
        });

        const orderKeys = (a, b) => {
            if (a === '_') return 1;
            if (b === '_') return -1;
            return a.localeCompare(b);
        };

        return [...map.entries()]
            .sort(([a], [b]) => orderKeys(a, b))
            .map(([key, evs]) => ({
                letter: key === '_' ? '' : key,
                events: evs,
            }));
    }

    renderGroupedDayCell(dayEvents) {
        const arr = Array.isArray(dayEvents) ? dayEvents : [dayEvents];
        const rows = this.bucketEventsForPdfRows(arr);
        const html = rows.map((row) => {
            const labelHtml = row.letter
                ? `<span class="tt-pdf-group-label" aria-label="Group row ${row.letter}">${row.letter}</span>`
                : '';
            const body = row.events.map((ev) => this.renderSingleListEventCard(ev, { suppressGroupBadge: !!row.letter })).join('');
            return `<div class="tt-pdf-group-row">${labelHtml}<div class="tt-pdf-group-body">${body}</div></div>`;
        }).join('');
        return `<div class="tt-pdf-group-rows">${html}</div>`;
    }

    /** One or more cards (legacy array support). */
    renderListEvent(events) {
        const arr = Array.isArray(events) ? events : [events];
        return arr.map((ev) => this.renderSingleListEventCard(ev, {})).join('');
    }

    renderSingleListEventCard(event, opts = {}) {
        const p = this.getListEventProps(event);
        const groupName = p.group_name || '';
        const title = event.title || p.topic || '';
        const faculty = p.faculty_name || '';
        const venue = p.vanue || p.venue_name || '';
        const classSession = p.class_session || '';
        const startTime = event.start ? new Date(event.start).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
        const endTime = event.end ? new Date(event.end).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
        const timeRange = startTime && endTime ? `${startTime} - ${endTime}` : '';
        const showBadge = !opts.suppressGroupBadge && groupName;
        const eid = event.id != null ? String(event.id).replace(/"/g, '&quot;') : '';

        return `
                <div class="list-event-card p-2 mb-2" data-group="${groupName}" data-id="${eid}">
                    ${showBadge ? `<div class="group-badge">${groupName}</div>` : ''}
                    <div class="title">${title}</div>
                    ${classSession ? `<div class="meta d-flex align-items-start gap-1"><i class="bi bi-book flex-shrink-0" aria-hidden="true"></i><span>${classSession}</span></div>` : ''}
                    ${faculty ? `<div class="meta d-flex align-items-start gap-1"><i class="bi bi-person flex-shrink-0" aria-hidden="true"></i><span>(${faculty})</span></div>` : ''}
                    ${venue ? `<div class="meta d-flex align-items-start gap-1"><i class="bi bi-geo-alt flex-shrink-0" aria-hidden="true"></i><span>${venue}</span></div>` : ''}
                    <div class="event-tooltip">
                        <div class="tooltip-title">${title}</div>
                        ${timeRange ? `
                        <div class="tooltip-row">
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            <span class="tooltip-label">Time:</span>
                            <span class="tooltip-value">${timeRange}</span>
                        </div>` : ''}
                        ${groupName ? `
                        <div class="tooltip-row">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span class="tooltip-label">Group:</span>
                            <span class="tooltip-value">${groupName}</span>
                        </div>` : ''}
                        ${venue ? `
                        <div class="tooltip-row">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            <span class="tooltip-label">Venue:</span>
                            <span class="tooltip-value">${venue}</span>
                        </div>` : ''}
                        ${faculty ? `
                        <div class="tooltip-row">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <span class="tooltip-label">Faculty:</span>
                            <span class="tooltip-value">${faculty}</span>
                        </div>` : ''}
                        ${classSession ? `
                        <div class="tooltip-row">
                            <i class="bi bi-journal-text" aria-hidden="true"></i>
                            <span class="tooltip-label">Session:</span>
                            <span class="tooltip-value">${classSession}</span>
                        </div>` : ''}
                    </div>
                </div>
            `;
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
            if (text.includes('break time')) row.classList.add('break-row');
            if (text.includes('lunch')) row.classList.add('lunch-row');
            if (/\btea\s*break\b/.test(text) || /\blunch\s*break\b/.test(text)) {
                row.classList.add('break-row');
            }
        });
    }

    convertTo24Hour(timeStr) {
        if (!timeStr) return '';

        const [time, modifier] = timeStr.split(' ');
        let [hours, minutes] = time.split(':');

        hours = parseInt(hours);
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

        // Update the week number display (list view banner)
        const weekElement = document.getElementById('currentWeekNumber');
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
                    bootstrap.Modal.getInstance(modal).hide();
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
    initCourseFilterChoices();

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

@endsection
