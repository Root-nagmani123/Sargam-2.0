@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable')

@section('setup_content')

@php
    // Debug: Check if courseMaster is available
    if (!isset($courseMaster) || $courseMaster->isEmpty()) {
        \Log::error('Calendar view: courseMaster is empty or not set');
    }
@endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="{{ asset('css/calendar-admin.css') }}?v={{ @filemtime(public_path('css/calendar-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/cal-event-pill.css') }}">
<link rel="stylesheet" href="{{ asset('css/cal-portal-master.css') }}">

<div class="container-fluid calendar-admin-page cal-master-page">
    @if(!isset($courseMaster) || $courseMaster->isEmpty())
        <div class="alert alert-warning mb-3 mt-3">
            <h4 class="h6 mb-1"><i class="bi bi-exclamation-triangle me-2"></i>No Courses Available</h4>
            <p class="mb-0 small">No active courses found. Please contact the administrator.</p>
        </div>
    @endif
    <x-breadcrum title="Calendar Creation">
    @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
        <a id="createEventButton"
                data-bs-toggle="modal"
                data-bs-target="#eventModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Event</span>
        </a>
        @endif

        {{-- Whole-week timetable export (always visible) — exports the week currently in view --}}
        <div class="btn-group btn-group-sm ms-2 gap-2" role="group" aria-label="Weekly timetable export">
            <button type="button" id="btnToolbarWeekPdf" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm fw-semibold text-nowrap" title="Download the whole week's timetable as PDF">
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                <span>Weekly PDF</span>
            </button>
            <button type="button" id="btnToolbarWeekPrint" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm fw-semibold text-nowrap" title="Print the whole week's timetable">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <button type="button" id="btnToolbarWeekInfo" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm fw-semibold text-nowrap" title="Course information &amp; faculty for the week (PDF)">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Info Sheet</span>
            </button>
            @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
            <button type="button" id="btnEditWeekInfo" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm fw-semibold text-nowrap" title="Edit info-sheet details (Director, Participants Profile, Mention of the Week)">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                <span>Edit Info</span>
            </button>
            @endif
        </div>
    </x-breadcrum>

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
                    <div class="cal-portal-toolbar-row programme-dt-toolbar d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-between gap-3 mb-4 w-100" style="z-index: 0;">
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
                            <button type="button" class="btn programme-dt-btn-reset flex-shrink-0 ms-3" id="btnResetCalendarFilters">
                                Reset Filters
                            </button>
                        </div>

                        <div id="calPortalToolbar" class="cal-toolbar-nav d-flex flex-wrap align-items-center justify-content-xl-end gap-3 ms-xl-auto" aria-label="Calendar navigation">
                            <div class="cal-portal-nav-cluster d-flex align-items-center gap-1">
                                <button type="button" class="cal-portal-nav-btn" id="calPortalPrev" aria-label="Previous period">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </button>
                                <h2 class="cal-portal-title mb-0" id="calPortalTitle" aria-live="polite"></h2>
                                <button type="button" class="cal-portal-nav-btn" id="calPortalNext" aria-label="Next period">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="btn-group cal-view-switch" role="group" aria-label="Calendar view mode">
                                <button type="button" class="btn" data-view="week" aria-pressed="false" title="Week schedule view">
                                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                                    <span class="visually-hidden">Week schedule</span>
                                </button>
                                <button type="button" class="btn active" data-view="month" aria-pressed="true" title="Month calendar view">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <span class="visually-hidden">Month calendar</span>
                                </button>
                            </div>
                            <div class="dropdown cal-toolbar-more">
                                <button type="button" class="btn cal-toolbar-more-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More calendar options">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-2 py-1">
                                    <li>
                                        <button type="button" class="dropdown-item py-2" id="btnTimetableListView" data-view="list">
                                            <i class="bi bi-table me-2" aria-hidden="true"></i>Weekly Timetable
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item py-2" id="toggleDensityBtn" aria-pressed="false">
                                            <i class="bi bi-arrows-collapse me-2" aria-hidden="true"></i>Compact View
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

                                            {{-- Whole-week timetable: download / print PDF --}}
                                            <div class="btn-group btn-group-sm mt-2" role="group" aria-label="Timetable export">
                                                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" id="btnWeekTimetablePdf" title="Download the whole week as a PDF">
                                                    <i class="bi bi-download"></i><span>Download</span>
                                                </button>
                                                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" id="btnWeekTimetablePrint" title="Print the whole week timetable">
                                                    <i class="bi bi-printer"></i><span>Print</span>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnWeekInfoPdf" title="Course information & faculty for the week (PDF)">
                                                    <i class="bi bi-people"></i><span>Info Sheet</span>
                                                </button>
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
@include('admin.calendar.partials.event_hover_card')
@include('admin.calendar.partials.confirmation')
@if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
@include('admin.calendar.partials.weekly_info_editor')
@endif

  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
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
        subjectNames: "{{ route('calendar.get.subject.name') }}",
        eventCard: "{{ route('calendar.event.card', ['id' => 'EVENT_ID']) }}",
        weeklyTimetablePdf: "{{ route('calendar.weekly-timetable.pdf') }}",
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
        this.eventsLoaded = false; // Track if events have been loaded initially
        this.eventDetailsCache = new Map();
        this.hoverShowTimer = null;
        this.hoverHideTimer = null;
        this.hoverAnchorEl = null;
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
            headerToolbar: false,
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
            const normalized = filteredData.map(event => this.normalizeEventForTimeGrid(event));
            console.log('Events after filtering:', normalized.length);
            successCallback(normalized);
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
        if (editBtn) editBtn.dataset.id = data.id;
        if (deleteBtn) deleteBtn.dataset.id = data.id;
        if (modalEdit) modalEdit.dataset.id = data.id;
        if (modalDelete) modalDelete.dataset.id = data.id;

        // Event Card (PDF preview) link
        const hoverViewCardBtn = document.getElementById('calHoverViewCardBtn');
        if (hoverViewCardBtn && data.id) {
            hoverViewCardBtn.href = CalendarConfig.api.eventCard.replace('EVENT_ID', data.id);
        }
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
            const btn = document.getElementById('editEventBtn');
            if (btn && document.getElementById('calHoverEditBtn')?.dataset.id) {
                btn.dataset.id = document.getElementById('calHoverEditBtn').dataset.id;
            }
            this.hideEventHoverCard();
            this.loadEventForEdit();
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

        // Whole-week timetable PDF (download / print) — list-view panel + main toolbar
        document.getElementById('btnWeekTimetablePdf')?.addEventListener('click', () => this.openWeeklyTimetablePdf(true));
        document.getElementById('btnWeekTimetablePrint')?.addEventListener('click', () => this.openWeeklyTimetablePdf(false));
        document.getElementById('btnToolbarWeekPdf')?.addEventListener('click', () => this.openWeeklyTimetablePdf(true));
        document.getElementById('btnToolbarWeekPrint')?.addEventListener('click', () => this.openWeeklyTimetablePdf(false));
        document.getElementById('btnToolbarWeekInfo')?.addEventListener('click', () => this.openWeeklyInfoPdf(false));
        document.getElementById('btnWeekInfoPdf')?.addEventListener('click', () => this.openWeeklyInfoPdf(false));
        document.getElementById('btnEditWeekInfo')?.addEventListener('click', () => this.openWeeklyInfoEditor());
        document.getElementById('weeklyInfoForm')?.addEventListener('submit', (e) => this.saveWeeklyInfo(e));

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
        const eventId = document.getElementById('calHoverEditBtn')?.dataset.id
            || document.getElementById('editEventBtn')?.dataset.id
            || this.currentEventId;

        try {
            const response = await fetch(`/calendar/event-edit/${eventId}`);
            const event = await response.json();

            await this.populateEditForm(event);

            // Update modal for edit
            document.getElementById('eventModalTitle').textContent = 'Edit Event';
            document.querySelector('.btn-text').textContent = 'Update Event';
            document.getElementById('submitEventBtn').dataset.action = 'edit';
            document.getElementById('start_datetime').removeAttribute('readonly');

            this.hideEventHoverCard();
            bootstrap.Modal.getInstance(document.getElementById('eventDetails'))?.hide();
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
        const eventId = document.getElementById('calHoverDeleteBtn')?.dataset.id
            || document.getElementById('deleteEventBtn')?.dataset.id
            || this.currentEventId;

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

            this.hideEventHoverCard();
            this.eventDetailsCache.delete(String(eventId));
            bootstrap.Modal.getInstance(document.getElementById('eventDetails'))?.hide();
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
        window.open(`${CalendarConfig.api.weeklyTimetablePdf}?${this.weeklyExportParams(download).toString()}`, '_blank', 'noopener');
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
            if (!res.ok) {
                throw new Error(data.error || 'Failed to load details.');
            }

            document.getElementById('wi_course_id').value = data.course_id;
            document.getElementById('wi_week_start').value = data.week_start;
            document.getElementById('wi_director').value = data.director_name || '';
            document.getElementById('wi_joint_director').value = data.joint_director_name || '';
            document.getElementById('wi_participants_profile').value = data.participants_profile || '';
            document.getElementById('wi_mention_of_week').value = data.mention_of_week || '';

            const course = (this.courses || []).find(c => c.pk == data.course_id);
            document.getElementById('weeklyInfoContext').textContent =
                `${course ? course.course_name + ' — ' : ''}Week starting ${data.week_start}`;

            new bootstrap.Modal(document.getElementById('weeklyInfoModal')).show();
        } catch (err) {
            this.showNotification(err.message || 'Failed to load info-sheet details.', 'danger');
        }
    }

    /** Persist info-sheet details. */
    async saveWeeklyInfo(e) {
        e.preventDefault();
        const form = document.getElementById('weeklyInfoForm');
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
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
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
