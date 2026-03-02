@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')

@php
    // Debug: Check if courseMaster is available
    if (!isset($courseMaster) || $courseMaster->isEmpty()) {
        \Log::error('Calendar view: courseMaster is empty or not set');
    }
@endphp

<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
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
        border-radius: 1rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 74, 147, 0.25), 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .course-header h1 {
        font-size: 1.85rem;
        font-weight: 600;
        color: #fff;
    }

    .course-header .badge {
        background: rgba(255, 255, 255, 0.95);
        color: #1a1a1a;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
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

/* Responsive images and media */
img {
    max-width: 100%;
    height: auto;
}

.logo-wrapper img {
    max-width: 100%;
    height: auto;
    object-fit: contain;
}

/* Calendar styling */
.fc {
    font-size: 0.95rem;
    max-width: 100%;
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
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 74, 147, 0.1) !important;
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

/* Mobile view: FullCalendar - better small screen experience */
@media (max-width: 767.98px) {
    .fc .fc-toolbar-chunk .fc-button {
        margin-left: 0.5rem !important;
        margin-right: 0.5rem !important;
    }
    .fc .fc-toolbar-chunk .fc-button:first-child {
        margin-left: 0 !important;
    }
    /* Toolbar: stack and wrap on narrow screens */
    .fc .fc-toolbar {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.5rem 0;
    }
    .fc .fc-toolbar-title {
        font-size: 1rem !important;
        margin: 0.25rem 0;
        text-align: center;
        word-break: break-word;
    }
    .fc .fc-toolbar-chunk {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.35rem;
        width: 100%;
    }
    .fc .fc-toolbar-chunk .fc-button {
        padding: 0.4rem 0.6rem !important;
        font-size: 0.8rem !important;
        flex: 0 0 auto;
        min-width: 44px;
    }
    
    /* Better button text handling */
    .fc .fc-button-group .fc-button {
        white-space: nowrap;
    }
    
    /* Ensure calendar doesn't overflow */
    .fc-scroller-liquid-absolute {
        overflow-x: hidden !important;
    }
    
    .fc-scroller {
        overflow-x: hidden !important;
    }
    /* Month view: more compact day cells */
    .fc .fc-daygrid-day-frame {
        min-height: 60px !important;
    }
    .fc .fc-daygrid-day-number {
        font-size: 0.75rem !important;
        padding: 0.25rem !important;
    }
    .fc .fc-daygrid-day-events {
        min-height: 0;
    }
    /* List view: better mobile layout */
    .fc .fc-list {
        border-width: 1px;
        font-size: 0.9rem;
    }
    .fc .fc-list-event:hover td {
        background: rgba(0, 74, 147, 0.06);
    }
    .fc .fc-list-day-cushion {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.85rem;
    }
    .fc .fc-list-event-dot {
        width: 8px;
        height: 8px;
    }
    .fc .fc-list-event-time {
        font-size: 0.8rem;
        padding: 0.4rem 0.5rem;
    }
    .fc .fc-list-event-title {
        font-size: 0.85rem;
        padding: 0.4rem 0.5rem;
    }
    /* Week view: narrower time column */
    .fc .fc-timegrid-slot-label {
        font-size: 0.7rem !important;
    }
    .fc .fc-timegrid-axis-cushion {
        font-size: 0.7rem !important;
    }
    /* Calendar container: reduce padding on mobile */
    .calendar-container .card-body {
        padding: 0.75rem !important;
    }
}

/* Extra small screens: further compact */
@media (max-width: 575.98px) {
    .fc .fc-toolbar-title {
        font-size: 0.9rem !important;
    }
    .fc .fc-toolbar-chunk .fc-button {
        padding: 0.35rem 0.5rem !important;
        font-size: 0.75rem !important;
    }
    .fc .fc-daygrid-day-frame {
        min-height: 50px !important;
    }
    .fc .fc-list-day-cushion,
    .fc .fc-list-event-time,
    .fc .fc-list-event-title {
        font-size: 0.8rem !important;
    }
}

/* Mobile view: Add Event & Event Details modals - prevent cropping */
@media (max-width: 767.98px) {
    .modal {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    
    #eventModal .modal-dialog,
    #eventDetails .modal-dialog,
    .modal-dialog {
        margin: 0 !important;
        max-width: 100vw !important;
        width: 100vw !important;
        max-height: 100vh !important;
        height: 100vh !important;
        display: flex !important;
        align-items: stretch !important;
    }
    
    #eventModal .modal-content,
    #eventDetails .modal-content,
    .modal-content {
        max-height: 100vh !important;
        height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
        border-radius: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }
    
    #eventModal .modal-body,
    #eventDetails .modal-body,
    .modal-body {
        flex: 1 1 auto !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch !important;
        padding: 1rem 0.75rem !important;
        max-height: none !important;
        min-height: 0 !important;
    }
    
    #eventModal .modal-header,
    #eventModal .modal-footer,
    #eventDetails .modal-header,
    #eventDetails .modal-footer,
    .modal-header,
    .modal-footer {
        flex-shrink: 0 !important;
        padding: 0.75rem 1rem !important;
    }
    
    .modal-header .btn-close {
        margin: 0 !important;
        padding: 0.5rem !important;
        z-index: 1051 !important;
    }
    
    .modal-footer .btn {
        flex: 1 !important;
        min-width: 0 !important;
        margin: 0.25rem !important;
    }
    
    /* Ensure modal backdrop doesn't interfere */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.5) !important;
    }
}

/* Extra small devices - further adjustments */
@media (max-width: 575.98px) {
    #eventModal .modal-dialog,
    #eventDetails .modal-dialog {
        margin: 0 !important;
        max-width: 100vw !important;
        width: 100vw !important;
    }
    
    #eventModal .modal-header,
    #eventDetails .modal-header {
        padding: 0.75rem 0.5rem !important;
    }
    
    #eventModal .modal-body,
    #eventDetails .modal-body {
        padding: 0.75rem 0.5rem !important;
    }
    
    #eventModal .modal-footer,
    #eventDetails .modal-footer {
        padding: 0.5rem !important;
        flex-direction: column !important;
    }
    
    .modal-footer .btn {
        width: 100% !important;
        margin: 0.25rem 0 !important;
    }
}

/* Landscape orientation on mobile */
@media (max-width: 767.98px) and (orientation: landscape) {
    #eventModal .modal-dialog,
    #eventDetails .modal-dialog {
        margin: 0.25rem !important;
        max-width: calc(100vw - 0.5rem) !important;
        width: calc(100vw - 0.5rem) !important;
        max-height: calc(100vh - 0.5rem) !important;
        height: calc(100vh - 0.5rem) !important;
    }
    
    #eventModal .modal-content,
    #eventDetails .modal-content {
        max-height: calc(100vh - 0.5rem) !important;
        height: calc(100vh - 0.5rem) !important;
        border-radius: 0.5rem !important;
    }
    
    #eventModal .modal-body,
    #eventDetails .modal-body {
        max-height: calc(100vh - 180px) !important;
        padding: 0.75rem !important;
    }
    
    #eventModal .modal-header,
    #eventDetails .modal-header {
        padding: 0.75rem !important;
    }
}

/* Mobile view: List of events - proper layout */
@media (max-width: 767.98px) {
    .timetable-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: block;
        width: 100%;
    }
    .timetable-container .table {
        min-width: 100%;
        width: max-content;
    }
    
    /* Ensure table-responsive wrapper works properly */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: block;
        width: 100%;
        position: relative;
    }
    .week-cards .row {
        flex-direction: column !important;
    }
    .week-cards .row > * {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    /* Improved list event card mobile layout */
    .list-event-card {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.5rem !important;
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
        min-width: 0 !important;
        max-width: 100% !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    .list-event-card .title {
        font-size: 0.9rem !important;
        line-height: 1.4 !important;
        margin-bottom: 0.5rem !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    .list-event-card .group-badge {
        font-size: 0.65rem !important;
        padding: 0.25rem 0.5rem !important;
        margin-bottom: 0.4rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 100% !important;
    }
    
    .list-event-card .meta {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.35rem !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0.4rem 0.5rem !important;
        margin-top: 0.25rem !important;
        font-size: 0.8rem !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    
    .list-event-card .meta.d-flex {
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        gap: 0.35rem !important;
    }
    
    .list-event-card .meta i {
        font-size: 0.9rem !important;
        margin-right: 0.4rem !important;
        flex-shrink: 0 !important;
    }
    
    .list-event-card .event-tooltip {
        min-width: calc(100vw - 2rem) !important;
        max-width: calc(100vw - 2rem) !important;
        left: 0 !important;
        right: 0 !important;
        font-size: 0.8rem !important;
        padding: 0.75rem !important;
    }
    
    /* Prevent hover effects on touch devices */
    .list-event-card:hover {
        transform: none !important;
    }
    
    /* Table cell adjustments */
    .timetable-grid td {
        padding: 0.4rem 0.25rem !important;
        vertical-align: top !important;
    }
    
    .timetable-grid th {
        padding: 0.5rem 0.35rem !important;
        font-size: 0.75rem !important;
    }
    
    .timetable-grid .time-column {
        min-width: 60px !important;
        font-size: 0.7rem !important;
        padding: 0.4rem 0.25rem !important;
    }
}

/* Mobile Event Cards - Full Detail View */
.mobile-event-cards-container {
    padding: 0;
    margin-top: 1rem;
    margin-bottom: 2rem;
    width: 100%;
    min-height: 200px;
}

@media (max-width: 767.98px) {
    .mobile-event-cards-container {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .timetable-container {
        display: none !important;
    }
}

@media (min-width: 768px) {
    .mobile-event-cards-container {
        display: none !important;
    }
    
    .timetable-container {
        display: block !important;
    }
}

.mobile-day-section {
    margin-bottom: 2rem;
}

.mobile-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, #003366 100%);
    border-radius: 0.75rem 0.75rem 0 0;
    margin-bottom: 0;
}

.mobile-day-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.mobile-day-title i {
    font-size: 1.2rem;
}

.mobile-day-date {
    font-size: 0.85rem;
    font-weight: 400;
    opacity: 0.9;
}

.mobile-day-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.mobile-events-list {
    background: #f8f9fa;
    border-radius: 0 0 0.75rem 0.75rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mobile-event-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 5px solid var(--primary-color);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    width: 100%;
    margin-bottom: 0;
}

.mobile-event-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mobile-event-card:active,
.mobile-event-card:focus {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
    transform: scale(0.98);
}

.mobile-event-card:active::before {
    opacity: 1;
}

.mobile-card-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--primary-color) 0%, #003366 100%);
    color: white;
    padding: 0.4rem 0.85rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 6px rgba(0, 74, 147, 0.3);
}

.mobile-card-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

.mobile-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.mobile-card-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
    font-weight: 600;
    font-size: 0.95rem;
}

.mobile-card-time i {
    font-size: 1.1rem;
}

.mobile-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.mobile-card-detail {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.65rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    transition: background 0.2s ease;
}

.mobile-card-detail i {
    font-size: 1.2rem;
    margin-top: 0.15rem;
    flex-shrink: 0;
}

.mobile-card-detail-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
    min-width: 0;
}

.mobile-card-detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mobile-card-detail-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1f2937;
    word-wrap: break-word;
    overflow-wrap: break-word;
    line-height: 1.4;
}

/* Responsive adjustments for mobile cards */
@media (max-width: 575.98px) {
    .mobile-day-header {
        padding: 0.65rem 0.85rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .mobile-day-title {
        font-size: 1rem;
        width: 100%;
    }

    .mobile-day-badge {
        align-self: flex-end;
        font-size: 0.8rem;
        padding: 0.3rem 0.65rem;
    }

    .mobile-event-card {
        padding: 1rem;
    }

    .mobile-card-title {
        font-size: 1rem;
    }

    .mobile-card-time {
        font-size: 0.9rem;
    }

    .mobile-card-detail {
        padding: 0.55rem;
    }

    .mobile-card-detail i {
        font-size: 1.1rem;
    }

    .mobile-card-detail-label {
        font-size: 0.7rem;
    }

    .mobile-card-detail-value {
        font-size: 0.85rem;
    }
}

@media (max-width: 399.98px) {
    .mobile-events-list {
        padding: 0.75rem;
        gap: 0.75rem;
    }

    .mobile-event-card {
        padding: 0.85rem;
    }

    .mobile-card-title {
        font-size: 0.95rem;
    }

    .mobile-card-detail {
        padding: 0.5rem;
        gap: 0.6rem;
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

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .fc-event-card,
    .list-event-card,
    .timeline-event-card {
        background: #111827;
        color: #E5E7EB;
        border-color: rgba(255, 255, 255, 0.12);
    }
    .list-event-card .meta, .fc-event-card .meta-item { color: #9CA3AF; }
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

/* Very Small Devices (< 400px) - Ultra Compact */
@media (max-width: 399.98px) {
    .container-fluid {
        padding: 0.25rem !important;
    }

    .course-header {
        padding: 0.75rem 0.5rem !important;
        border-radius: 0.5rem;
    }

    .course-header h1 {
        font-size: 1rem !important;
        line-height: 1.3;
    }

    .course-header p {
        font-size: 0.7rem !important;
    }

    .course-header .badge {
        font-size: 0.65rem !important;
        padding: 0.2rem 0.4rem !important;
    }

    .control-panel {
        padding: 0.75rem 0.5rem !important;
        gap: 0.5rem !important;
    }

    .btn-group .btn {
        font-size: 0.7rem !important;
        padding: 0.35rem 0.4rem !important;
    }

    .btn-group .btn span {
        display: none;
    }

    .btn-group .btn i {
        margin-right: 0 !important;
    }

    #courseFilter {
        font-size: 0.8rem !important;
        padding: 0.4rem !important;
    }

    #createEventButton {
        font-size: 0.75rem !important;
        padding: 0.5rem !important;
    }

    #createEventButton span {
        display: none;
    }

    .fc {
        font-size: 0.7rem !important;
    }

    .fc .fc-toolbar-title {
        font-size: 0.85rem !important;
    }

    .fc .fc-toolbar-chunk .fc-button {
        padding: 0.3rem 0.4rem !important;
        font-size: 0.7rem !important;
    }

    .fc-col-header-cell {
        padding: 0.4rem 0.15rem !important;
        font-size: 0.65rem !important;
    }

    .fc-daygrid-day-number {
        font-size: 0.65rem !important;
        padding: 0.25rem !important;
    }

    .fc-event-card {
        padding: 0.25rem 0.3rem !important;
        font-size: 0.65rem !important;
    }

    .fc-event-card .event-title {
        font-size: 0.7rem !important;
    }

    .timetable-header {
        padding: 0.75rem 0.5rem !important;
    }

    .timetable-header h1 {
        font-size: 0.9rem !important;
    }

    .timetable-header p {
        font-size: 0.7rem !important;
    }

    .logo-wrapper img {
        width: 40px !important;
        height: 40px !important;
    }

    .week-controls .btn {
        font-size: 0.7rem !important;
        padding: 0.35rem 0.4rem !important;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.4rem 0.25rem !important;
        font-size: 0.7rem !important;
    }

    .timetable-grid .time-column {
        min-width: 60px !important;
        font-size: 0.65rem !important;
    }

    .modal-dialog {
        margin: 0.25rem !important;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 0.75rem 0.5rem !important;
    }

    .modal-title {
        font-size: 0.9rem !important;
    }
}

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
        gap: 0.5rem !important;
    }

    .week-controls .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.5rem;
        margin: 0 0.15rem;
    }

    .week-badge .badge {
        font-size: 0.75rem !important;
        padding: 0.35rem 0.75rem !important;
    }

    /* Week Cards */
    .week-cards .row {
        gap: 0.75rem !important;
        flex-wrap: wrap;
    }

    .week-cards .row > * {
        flex: 0 0 calc(50% - 0.375rem);
        max-width: calc(50% - 0.375rem);
    }

    .week-card {
        padding: 0.75rem !important;
        min-width: 0;
    }
    
    /* On very small screens, stack week cards */
    @media (max-width: 399.98px) {
        .week-cards .row > * {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
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
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Ensure table is scrollable on very small screens */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: block;
        width: 100%;
    }

    .timetable-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .timetable-container .table {
        min-width: 100%;
        width: max-content;
    }

    /* List Event Cards */
    .list-event-card {
        padding: 0.65rem !important;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        display: flex !important;
        flex-direction: column !important;
        gap: 0.4rem !important;
        min-width: 0 !important;
        max-width: 100% !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }

    .list-event-card .title {
        font-size: 0.85rem !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.4 !important;
    }

    .list-event-card .meta {
        font-size: 0.75rem !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.3rem !important;
        width: 100% !important;
        max-width: 100% !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }

    .list-event-card .event-tooltip {
        min-width: calc(100vw - 2rem) !important;
        max-width: calc(100vw - 2rem) !important;
        font-size: 0.75rem;
        padding: 0.65rem;
        left: 0 !important;
        right: 0 !important;
    }

    .list-event-card .group-badge {
        font-size: 0.65rem !important;
        padding: 0.1rem 0.35rem !important;
        max-width: 100% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
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
        gap: 1rem;
    }

    .control-panel > .d-flex {
        flex-direction: column;
        width: 100%;
        gap: 0.75rem;
    }

    .view-toggle-section {
        flex-direction: column;
        width: 100%;
        gap: 0.75rem;
    }

    .btn-group {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .btn-group .btn {
        flex: 1;
        min-width: 0;
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Ensure form elements are full width */
    .form-floating {
        width: 100%;
    }
    
    .form-floating > .form-select,
    .form-floating > .form-control {
        width: 100%;
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

/* Large Devices (992px to 1199px) */
@media (min-width: 992px) and (max-width: 1199.98px) {
    .course-header {
        padding: 2.5rem 1.5rem;
    }

    .course-header h1 {
        font-size: 1.75rem;
    }

    .fc {
        font-size: 0.93rem;
    }

    .fc-event-card {
        padding: 0.95rem 1rem;
    }

    .fc-event-card .event-title {
        font-size: 1.03rem;
    }

    .timetable-grid th,
    .timetable-grid td {
        padding: 0.7rem;
        font-size: 0.9rem;
    }
}

/* Extra Large Devices (1200px and up) - Keep Original Styling */
@media (min-width: 1200px) {
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

/* Touch-friendly improvements for all mobile devices */
@media (hover: none) and (pointer: coarse) {
    /* Increase touch target sizes */
    .btn {
        min-height: 44px;
        min-width: 44px;
    }

    .fc .fc-button {
        min-height: 44px;
        padding: 0.5rem 0.75rem !important;
    }

    .fc-event-card {
        min-height: 44px;
        padding: 0.75rem !important;
    }

    .form-control,
    .form-select {
        min-height: 44px;
    }

    /* Better spacing for touch */
    .btn-group .btn {
        margin: 0.25rem;
    }
}

/* Landscape orientation on mobile devices */
@media (max-width: 767.98px) and (orientation: landscape) {
    .course-header {
        padding: 1rem 1.5rem;
    }

    .course-header h1 {
        font-size: 1.2rem;
    }

    .control-panel {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .control-panel .btn-group {
        width: auto;
        flex: 0 0 auto;
    }

    #courseFilter {
        width: auto;
        min-width: 180px;
    }

    #createEventButton {
        width: auto;
    }

    .fc .fc-toolbar {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .timetable-header .row {
        flex-direction: row;
        align-items: center;
    }

    .timetable-header .col-md-2,
    .timetable-header .col-md-6,
    .timetable-header .col-md-4 {
        flex: 0 0 auto;
    }
}

/* Ensure proper viewport handling */
@media (max-width: 767.98px) {
    /* Prevent horizontal scroll */
    body {
        overflow-x: hidden;
    }

    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* Ensure cards don't overflow */
    .card,
    .card-body {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* FullCalendar container */
    #calendar {
        max-width: 100%;
        overflow-x: hidden;
    }

    .fc-scroller {
        overflow-x: hidden !important;
    }
    
    /* Ensure all flex containers wrap properly */
    .d-flex {
        flex-wrap: wrap;
    }
    
    /* Better spacing for stacked elements */
    .mb-3,
    .mb-4 {
        margin-bottom: 1rem;
    }
    
    /* Ensure badges and labels don't overflow */
    .badge {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: inline-block;
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

<div class="container-fluid">
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

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">

        <!-- Filters & View Controls -->
        <fieldset class="d-flex flex-wrap align-items-center gap-3 mb-0">
            <legend class="visually-hidden">View and Filter Controls</legend>

            <!-- Density Toggle -->
            <div class="btn-group" role="group" aria-label="Toggle calendar density">
                <button
                    type="button"
                    class="btn btn-outline-secondary d-flex align-items-center gap-2"
                    id="toggleDensityBtn"
                    aria-pressed="false"
                    aria-expanded="false"
                >
                    <i class="bi bi-arrows-collapse" aria-hidden="true"></i>
                    <span class="fw-medium">Compact View</span>
                </button>
            </div>

            <!-- Course Filter -->
            <div class="form-floating">
                <select
                    class="form-select"
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
                <label for="courseFilter">Filter by Course</label>
            </div>
        </fieldset>

        <!-- Primary Actions -->
        @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="btn btn-primary px-4 d-flex align-items-center gap-2"
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

                    <!-- FullCalendar placeholder (you may initialize FullCalendar separately) -->
                    <div id="calendar" class="fc mb-4" role="application" aria-label="Interactive calendar"></div>

                    <!-- List View -->
                    <div id="eventListView" class="mt-4 d-none" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper">
                            <!-- Timetable Header -->
                            <div class="timetable-header bg-gradient shadow-sm border rounded-4 p-4 mb-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-2 text-center text-md-start">
                                        <div class="logo-wrapper p-2 bg-white rounded-3 shadow-sm d-inline-block">
                                            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo"
                                                class="img-fluid" width="70" height="70">
                                        </div>
                                    </div>

                                    <div class="col-md-6 text-center">
                                        <h1 class="h3 mb-2 fw-bold text-primary">Weekly Timetable</h1>
                                        <p class="text-muted mb-0 fw-medium" id="weekRangeText" aria-live="polite">
                                            <i class="bi bi-calendar-week me-2" aria-hidden="true"></i>—
                                        </p>
                                    </div>

                                    <div class="col-md-4 text-center text-md-end">
                                        <div class="week-controls bg-white rounded-3 p-3 shadow-sm d-inline-block">
                                            <div class="btn-group mb-2" role="group" aria-label="Week navigation">
                                                <button type="button" class="btn btn-outline-primary" id="prevWeekBtn"
                                                    aria-label="Previous week">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary px-4" id="currentWeekBtn"
                                                    aria-label="Current week">
                                                    <i class="bi bi-calendar-check me-2"></i>Today
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" id="nextWeekBtn"
                                                    aria-label="Next week">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>

                                            <div class="week-badge">
                                                <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2">
                                                    Week <span id="currentWeekNumber" class="fw-bold">—</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Week Cards (Accessible, GIGW-friendly) -->
                            <div id="weekCards" class="week-cards mb-4" role="region" aria-labelledby="weekCardsTitle">
                                <h2 id="weekCardsTitle" class="h5 fw-bold text-primary mb-3">Week at a glance</h2>
                                <div class="row g-3" role="list" aria-label="Days of the week">
                                    <!-- JS will render day cards here -->
                                </div>
                            </div>

                            <!-- Mobile Event Cards Container (shown only on mobile) -->
                            <div id="mobileEventCards" class="mobile-event-cards-container" style="display: none;">
                                <!-- Mobile cards will be rendered here by JavaScript -->
                            </div>

                            <!-- Timetable table -->
                            <div class="timetable-container border rounded-3 overflow-hidden">
                                <div class="table-responsive" role="region" aria-label="Weekly timetable">
                                    <table class="table table-bordered timetable-grid" id="timetableTable"
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
@include('admin.calendar.partials.confirmation')

  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
<!-- Modern JavaScript with improved accessibility -->
<script>
console.log('FullCalendar loaded:', typeof FullCalendar !== 'undefined');

// Configuration object
const CalendarConfig = {
    api: {
        events: "{{ route('calendar.event.calendar-details') }}",
        eventDetails: "{{ route('calendar.event.Singlecalendar-details') }}",
        store: "{{ route('calendar.event.store') }}",
        update: '/calendar/event-update/',
        delete: '/calendar/event-delete/',
        groupTypes: "{{ route('calendar.get.group.types') }}",
        subjectNames: "{{ route('calendar.get.subject.name') }}"
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
            dateClick: (info) => this.handleDateSelect({ ...info, start: info.date }),
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

        // Set edit/delete button data and ensure event listeners
        const editBtn = document.getElementById('editEventBtn');
        const deleteBtn = document.getElementById('deleteEventBtn');

        if (editBtn) {
            editBtn.dataset.id = data.id;
            // Remove any existing listeners and add new one
            editBtn.onclick = null;
            editBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.loadEventForEdit();
            });
        }
        
        if (deleteBtn) {
            deleteBtn.dataset.id = data.id;
            // Remove any existing listeners and add new one
            deleteBtn.onclick = null;
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.confirmDelete();
            });
        }

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
        // View toggle buttons
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleView(e.target));
        });

        // Week navigation buttons (List View)
        document.getElementById('prevWeekBtn')?.addEventListener('click', () => this.navigateWeek(-1));
        document.getElementById('nextWeekBtn')?.addEventListener('click', () => this.navigateWeek(1));
        document.getElementById('currentWeekBtn')?.addEventListener('click', () => this.navigateWeek(0));

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

        // Edit/Delete buttons - Use event delegation for reliability
        const eventDetailsModal = document.getElementById('eventDetails');
        if (eventDetailsModal) {
            eventDetailsModal.addEventListener('click', (e) => {
                if (e.target.closest('#editEventBtn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.loadEventForEdit();
                } else if (e.target.closest('#deleteEventBtn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.confirmDelete();
                }
            });
        }
        
        // Also attach direct listeners as backup
        document.getElementById('editEventBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.loadEventForEdit();
        });
        document.getElementById('deleteEventBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.confirmDelete();
        });

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

        // Mobile event cards: open details on click/keyboard
        const mobileCardsContainer = document.getElementById('mobileEventCards');
        if (mobileCardsContainer) {
            mobileCardsContainer.addEventListener('click', (e) => {
                const card = e.target.closest('.mobile-event-card');
                if (card?.dataset?.eventId) {
                    this.loadEventDetails(card.dataset.eventId);
                }
            });

            mobileCardsContainer.addEventListener('keydown', (e) => {
                const card = e.target.closest('.mobile-event-card');
                if (!card) return;
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (card.dataset?.eventId) {
                        this.loadEventDetails(card.dataset.eventId);
                    }
                }
            });
        }

        // Density toggle
        document.getElementById('toggleDensityBtn')?.addEventListener('click', () => this.toggleDensity());

        // Handle window resize to switch between mobile and desktop views
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const listViewEl = document.getElementById('eventListView');
                if (listViewEl && !listViewEl.classList.contains('d-none')) {
                    // Reload list view to switch between mobile cards and desktop table
                    this.loadListView();
                }
            }, 250);
        });

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

        // Populate dropdown (supporting both plain select and Choices.js)
        const select = document.getElementById('group_type');
        const choicesInstance = window.calendarEventChoices && window.calendarEventChoices.groupType
            ? window.calendarEventChoices.groupType
            : null;

        const optionsData = [{
            value: '',
            label: 'Select Group Type',
            selected: true,
            disabled: false
        }];

        Object.keys(grouped).forEach(key => {
            const typeName = grouped[key][0].type_name;
            optionsData.push({
                value: key,
                label: typeName,
                selected: false,
                disabled: false
            });
        });

        if (choicesInstance) {
            choicesInstance.clearChoices();
            choicesInstance.setChoices(optionsData, 'value', 'label', true);
        } else if (select) {
            select.innerHTML = '<option value="">Select Group Type</option>';
            Object.keys(grouped).forEach(key => {
                const typeName = grouped[key][0].type_name;
                const option = document.createElement('option');
                option.value = key;
                option.textContent = typeName;
                select.appendChild(option);
            });
        }

        // Set up change handler (works with both native select and Choices)
        if (select) {
            const handleChange = () => {
                const value = select.value;
                this.populateGroupCheckboxes(grouped[value] || []);
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

            select.onchange = handleChange;

            // If Choices is used, also listen to its change events on the underlying select
            if (choicesInstance) {
                select.removeEventListener('change', handleChange);
                select.addEventListener('change', handleChange);
            }
        }

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
        const choicesInstance = window.calendarEventChoices && window.calendarEventChoices.subjectName
            ? window.calendarEventChoices.subjectName
            : null;

        const optionsData = [{
            value: '',
            label: 'Select Subject Name',
            selected: true,
            disabled: false
        }];

        subjects.forEach(subject => {
            optionsData.push({
                value: subject.pk,
                label: subject.subject_name,
                selected: false,
                disabled: false
            });
        });

        if (choicesInstance) {
            choicesInstance.clearChoices();
            choicesInstance.setChoices(optionsData, 'value', 'label', true);
        } else if (select) {
            select.innerHTML = '<option value="">Select Subject Name</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject.pk;
                option.textContent = subject.subject_name;
                select.appendChild(option);
            });
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
         const editBtn = document.getElementById('editEventBtn');
        
        if (!editBtn) {
            console.error('Edit button not found');
            this.showNotification('Edit button not found', 'danger');
            return;
        }
        
        const eventId = editBtn.dataset.id;
        
        if (!eventId) {
            console.error('Event ID not found on edit button. Button dataset:', editBtn.dataset);
            this.showNotification('Event ID not found. Please try again.', 'danger');
            return;
        }
        
        console.log('Loading event for edit, ID:', eventId);

        try {
            const response = await fetch(`/calendar/event-edit/${eventId}`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const event = await response.json();

            await this.populateEditForm(event);

            // Update modal for edit
            const modalTitle = document.getElementById('eventModalTitle');
            if (modalTitle) {
                modalTitle.textContent = 'Edit Event';
            }
            
            const btnText = document.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = 'Update Event';
            }
            
            const submitBtn = document.getElementById('submitEventBtn');
            if (submitBtn) {
                submitBtn.dataset.action = 'edit';
                submitBtn.dataset.eventId = eventId;
            }
            
            const startDateInput = document.getElementById('start_datetime');
            if (startDateInput) {
                startDateInput.removeAttribute('readonly');
            }

            // Hide event details modal and show edit modal
            const eventDetailsModal = bootstrap.Modal.getInstance(document.getElementById('eventDetails'));
            if (eventDetailsModal) {
                eventDetailsModal.hide();
            }
            
            const editModal = new bootstrap.Modal(document.getElementById('eventModal'));
            editModal.show();

        } catch (error) {
            console.error('Edit load error:', error);
            this.showNotification('Error loading event for editing: ' + error.message, 'danger');
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
        $('#faculty').val(facultyIds).trigger('change');
        document.getElementById('faculty_type').value = event.faculty_type;
        document.getElementById('vanue').value = event.venue_id;

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

            // Update week display in header (use same calculation as updateCurrentWeek)
            const date = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
            const jan4 = new Date(date.getFullYear(), 0, 4);
            const monday = new Date(jan4);
            monday.setDate(monday.getDate() - monday.getDay() + 1);
            const timeDiff = date - monday;
            const weekDiff = Math.floor(timeDiff / (7 * 24 * 60 * 60 * 1000));
            const weekNum = weekDiff + 1;

            const weekElement = document.getElementById('currentWeek');
            if (weekElement) {
                weekElement.textContent = weekNum;
            }

            // Update table header with week dates
            this.updateTableHeader(weekStart);

            // Debug: Log the week being displayed
            console.log('List view - Week offset:', this.listViewWeekOffset);
            console.log('Week start:', weekStart);
            console.log('Total events:', events.length);

            // Filter and render events
            const filteredEvents = this.getEventsForWeek(events, this.listViewWeekOffset);
            console.log('Filtered events for this week:', filteredEvents.length);
            console.log('Sample event structure:', filteredEvents.length > 0 ? filteredEvents[0] : 'No events');
            this.renderListView(filteredEvents);
            this.renderWeekCards(events, weekStart);
            this.updateWeekRangeText(weekStart);
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

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const headers = thead.querySelectorAll('th:not(.time-column)');

        headers.forEach((header, index) => {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + index);
            const dateStr = date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
            header.innerHTML = `${days[index]}<br><small class="text-muted">${dateStr}</small>`;
        });
    }

    renderListView(events) {
        const tbody = document.getElementById('timetableBody');
        const mobileCardsContainer = document.getElementById('mobileEventCards');
        const isMobile = window.innerWidth <= 767;

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
            if (mobileCardsContainer) {
                mobileCardsContainer.innerHTML = `
                    <div class="empty-state text-center p-5">
                        <i class="bi bi-calendar-x display-5 text-muted mb-3"></i>
                        <p class="text-muted mb-3">No events scheduled</p>
                    </div>
                `;
            }
            return;
        }

        // On mobile, render cards instead of table
        if (isMobile && mobileCardsContainer) {
            console.log('Rendering mobile cards view. Events count:', events.length);
            this.renderMobileEventCards(events, mobileCardsContainer);
            // Hide table on mobile
            const tableContainer = document.querySelector('.timetable-container');
            if (tableContainer) {
                tableContainer.style.display = 'none';
            }
            mobileCardsContainer.style.display = 'block';
            mobileCardsContainer.classList.remove('d-none');
        } else {
            // Desktop: render table
            if (mobileCardsContainer) {
                mobileCardsContainer.style.display = 'none';
                mobileCardsContainer.classList.add('d-none');
            }
            const tableContainer = document.querySelector('.timetable-container');
            if (tableContainer) {
                tableContainer.style.display = 'block';
            }

            // Group events by time slot
            const timeSlots = this.groupEventsByTime(events);

            let html = '';
            Object.entries(timeSlots).forEach(([time, dayEvents]) => {
                html += `
                    <tr>
                        <th scope="row" class="time-slot">${time}</th>
                        ${['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].map(day => `
                            <td class="event-cell">
                                ${dayEvents[day] ? this.renderListEvent(dayEvents[day]) : ''}
                            </td>
                        `).join('')}
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            this.applyBreakLunchRowStyles();
            this.initializeScrollIndicators();
        }
    }

    renderMobileEventCards(events, container) {
        if (!events || events.length === 0) {
            container.innerHTML = `
                <div class="empty-state text-center p-5">
                    <i class="bi bi-calendar-x display-5 text-muted mb-3"></i>
                    <p class="text-muted mb-3">No events scheduled</p>
                </div>
            `;
            return;
        }

        // Group events by day
        const eventsByDay = {};
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const dayShortNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        events.forEach(event => {
            if (!event.start) {
                console.warn('Event missing start date:', event);
                return;
            }
            
            const eventDate = new Date(event.start);
            if (isNaN(eventDate.getTime())) {
                console.warn('Invalid event date:', event.start, event);
                return;
            }
            
            const dayIndex = eventDate.getDay();
            const dayName = dayNames[dayIndex];
            const dayShort = dayShortNames[dayIndex];
            
            // Use the first event's date for the day header
            if (!eventsByDay[dayName]) {
                eventsByDay[dayName] = {
                    dayIndex: dayIndex,
                    dayShort: dayShort,
                    date: new Date(eventDate.getFullYear(), eventDate.getMonth(), eventDate.getDate()),
                    events: []
                };
            }
            eventsByDay[dayName].events.push(event);
        });

        // Sort days by day index (Sunday=0, Monday=1, etc.)
        const sortedDays = Object.keys(eventsByDay).sort((a, b) => {
            return eventsByDay[a].dayIndex - eventsByDay[b].dayIndex;
        });

        if (sortedDays.length === 0) {
            container.innerHTML = `
                <div class="empty-state text-center p-5">
                    <i class="bi bi-calendar-x display-5 text-muted mb-3"></i>
                    <p class="text-muted mb-3">No valid events found</p>
                </div>
            `;
            return;
        }

        let html = '';
        sortedDays.forEach(dayName => {
            const dayData = eventsByDay[dayName];
            const dateStr = dayData.date.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });

            // Sort events by time within each day
            dayData.events.sort((a, b) => {
                const timeA = a.start ? new Date(a.start).getTime() : 0;
                const timeB = b.start ? new Date(b.start).getTime() : 0;
                return timeA - timeB;
            });

            html += `
                <div class="mobile-day-section mb-4">
                    <div class="mobile-day-header">
                        <h3 class="mobile-day-title">
                            <i class="bi bi-calendar-day me-2"></i>
                            ${dayName}
                            <span class="mobile-day-date">${dateStr}</span>
                        </h3>
                        <span class="mobile-day-badge">${dayData.events.length} event${dayData.events.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="mobile-events-list">
                        ${dayData.events.map(event => this.renderMobileEventCard(event)).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    renderMobileEventCard(event) {
        // Handle both FullCalendar event format and raw API format
        const extendedProps = event.extendedProps || {};
        const groupName = extendedProps.group_name || extendedProps.group || event.group_name || event.group || '';
        const title = event.title || extendedProps.topic || event.topic || event.subject_topic || 'Untitled Event';
        const faculty = extendedProps.faculty_name || event.faculty_name || '';
        const venue = extendedProps.vanue || extendedProps.venue_name || event.vanue || event.venue_name || '';
        const classSession = extendedProps.class_session || event.class_session || '';
        const internalFaculty = extendedProps.internal_faculty_name || event.internal_faculty_name || '';
        
        // Parse start and end times
        let startTime = '';
        let endTime = '';
        let timeRange = 'All Day';
        
        if (event.start) {
            const startDate = new Date(event.start);
            if (!isNaN(startDate.getTime())) {
                startTime = startDate.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    hour12: true 
                });
            }
        }
        
        if (event.end) {
            const endDate = new Date(event.end);
            if (!isNaN(endDate.getTime())) {
                endTime = endDate.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    hour12: true 
                });
            }
        }
        
        if (startTime && endTime) {
            timeRange = `${startTime} - ${endTime}`;
        } else if (startTime) {
            timeRange = startTime;
        } else if (classSession) {
            timeRange = classSession;
        }
        
        const eventId = event.id || extendedProps.event_id || event.event_id || event.pk || '';

        return `
            <div class="mobile-event-card" data-event-id="${eventId}" role="button" tabindex="0">
                ${groupName ? `<div class="mobile-card-badge">${groupName}</div>` : ''}
                <div class="mobile-card-header">
                    <h4 class="mobile-card-title">${title}</h4>
                    <div class="mobile-card-time">
                        <i class="bi bi-clock-fill"></i>
                        <span>${timeRange}</span>
                    </div>
                </div>
                <div class="mobile-card-body">
                    ${classSession ? `
                    <div class="mobile-card-detail">
                        <i class="bi bi-clock-history text-primary"></i>
                        <div class="mobile-card-detail-content">
                            <span class="mobile-card-detail-label">Session</span>
                            <span class="mobile-card-detail-value">${classSession}</span>
                        </div>
                    </div>` : ''}
                    ${venue ? `
                    <div class="mobile-card-detail">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        <div class="mobile-card-detail-content">
                            <span class="mobile-card-detail-label">Venue</span>
                            <span class="mobile-card-detail-value">${venue}</span>
                        </div>
                    </div>` : ''}
                    ${faculty ? `
                    <div class="mobile-card-detail">
                        <i class="bi bi-person-fill text-info"></i>
                        <div class="mobile-card-detail-content">
                            <span class="mobile-card-detail-label">Faculty</span>
                            <span class="mobile-card-detail-value">${faculty}</span>
                        </div>
                    </div>` : ''}
                    ${internalFaculty ? `
                    <div class="mobile-card-detail">
                        <i class="bi bi-person-check-fill text-success"></i>
                        <div class="mobile-card-detail-content">
                            <span class="mobile-card-detail-label">Internal Faculty</span>
                            <span class="mobile-card-detail-value">${internalFaculty}</span>
                        </div>
                    </div>` : ''}
                </div>
            </div>
        `;
    }

    renderWeekCards(events, weekStart) {
        const container = document.querySelector('#weekCards .row');
        if (!container) return;

        const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        const byDay = new Map();

        // Prepare boundaries: Monday start to Sunday end
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);

        days.forEach((_, i) => {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            const key = d.toISOString().split('T')[0];
            byDay.set(key, { date: d, events: [] });
        });

        // Filter incoming events to week range and allocate to day buckets
        (events || []).forEach(evt => {
            const d = new Date(evt.start);
            if (isNaN(d)) return;
            if (d < weekStart || d > weekEnd) return;
            const key = new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString?.() ?
                new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString().split('T')[0] :
                `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            if (byDay.has(key)) byDay.get(key).events.push(evt);
        });

        container.innerHTML = '';
        days.forEach((label, i) => {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            const info = byDay.get(key) || { date: d, events: [] };
            const count = info.events.length;

            const dateStr = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            const fullStr = d.toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-xl-4';
            col.setAttribute('role', 'listitem');
            col.innerHTML = `
                <div class="week-day-card" tabindex="0" aria-label="${label} ${fullStr}, ${count} event${count!==1?'s':''}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-dark">${label} <span class="text-muted">${dateStr}</span></div>
                        <span class="badge bg-primary-subtle text-primary">${count} event${count!==1?'s':''}</span>
                    </div>
                    <div class="week-day-events">
                        ${info.events.slice(0, 3).map(evt => {
                            const title = evt.title || evt.extendedProps?.topic || '';
                            const venue = evt.extendedProps?.vanue || evt.extendedProps?.venue_name || '';
                            const faculty = evt.extendedProps?.faculty_name || '';
                            const timeTxt = evt.start ? new Date(evt.start).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) : '';
                            return `
                            <div class="mini-event d-flex align-items-center gap-2" role="button" tabindex="0" aria-label="${title}${timeTxt?`, at ${timeTxt}`:''}${venue?`, at ${venue}`:''}">
                                <i class="bi bi-clock text-primary" aria-hidden="true"></i>
                                <span class="mini-title text-truncate">${title}</span>
                                ${timeTxt ? `<span class="mini-time text-muted">${timeTxt}</span>` : ''}
                            </div>`;
                        }).join('')}
                        ${count > 3 ? `<a href="#" class="mini-more" aria-label="Show ${count-3} more events">+ ${count-3} more</a>` : ''}
                    </div>
                </div>
            `;
            container.appendChild(col);
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

    groupEventsByTime(events) {
        // Implement grouping logic based on your data structure
        // This is a simplified example
        const groups = {};

        events.forEach(event => {
            const time = event.start ? new Date(event.start).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            }) : 'All Day';

            if (!groups[time]) groups[time] = {};

            const day = new Date(event.start).getDay();
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const dayName = dayNames[day];

            if (!groups[time][dayName]) {
                groups[time][dayName] = [];
            }

            groups[time][dayName].push(event);
        });

        return groups;
    }

    renderListEvent(events) {
        const arr = Array.isArray(events) ? events : [events];
        return arr.map(event => {
            const groupName = event.extendedProps.group_name || event.extendedProps.group || '';
            const title = event.title || event.extendedProps.topic || '';
            const faculty = event.extendedProps.faculty_name || '';
            const venue = event.extendedProps.vanue || event.extendedProps.venue_name || '';
            const classSession = event.extendedProps.class_session || '';
            const startTime = event.start ? new Date(event.start).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
            const endTime = event.end ? new Date(event.end).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
            const timeRange = startTime && endTime ? `${startTime} - ${endTime}` : '';
            
            return `
                <div class="list-event-card p-2 mb-2" data-group="${groupName}">
                    ${groupName ? `<div class="group-badge">${groupName}</div>` : ''}
                    <div class="title">${title}</div>
                    <div class="meta d-flex align-items-center"><i class="material-icons me-1">class</i>${classSession}</div> <div class="meta d-flex align-items-center"><i class="material-icons me-1">place</i>${venue}</div>
                    <div class="meta d-flex align-items-center"><i class="material-icons me-1">person</i>${faculty}</div>
                    
                    <!-- Hover Tooltip -->
                    <div class="event-tooltip">
                        <div class="tooltip-title">${title}</div>
                        ${timeRange ? `
                        <div class="tooltip-row">
                            <i class="bi bi-clock"></i>
                            <span class="tooltip-label">Time:</span>
                            <span class="tooltip-value">${timeRange}</span>
                        </div>` : ''}
                        ${groupName ? `
                        <div class="tooltip-row">
                            <i class="bi bi-people"></i>
                            <span class="tooltip-label">Group:</span>
                            <span class="tooltip-value">${groupName}</span>
                        </div>` : ''}
                        ${venue ? `
                        <div class="tooltip-row">
                            <i class="bi bi-geo-alt"></i>
                            <span class="tooltip-label">Venue:</span>
                            <span class="tooltip-value">${venue}</span>
                        </div>` : ''}
                        ${faculty ? `
                        <div class="tooltip-row">
                            <i class="material-icons me-1">person</i>
                            <span class="tooltip-label">Faculty:</span>
                            <span class="tooltip-value">${faculty}</span>
                        </div>` : ''}
                        ${classSession ? `
                        <div class="tooltip-row">
                            <i class="material-icons me-1">book</i>
                            <span class="tooltip-label">Session:</span>
                            <span class="tooltip-value">${classSession}</span>
                        </div>` : ''}
                    </div>
                </div>
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
            if (text.includes('break time')) row.classList.add('break-row');
            if (text.includes('lunch')) row.classList.add('lunch-row');
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

        // Update the week number display
        const weekElement = document.getElementById('currentWeek');
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