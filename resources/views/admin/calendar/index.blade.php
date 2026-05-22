@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable')

@push('styles')
<link rel="stylesheet"
    href="{{ asset('css/calendar-index.css') }}?v={{ @filemtime(public_path('css/calendar-index.css')) ?: time() }}" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')

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
        <x-breadcrum title="Academic TimeTable">
            @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
            <a href="javascript:void(0)" id="createEventButton" data-bs-toggle="modal" data-bs-target="#eventModal"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                <span>Add New Event</span>
            </a>
            @endif
        </x-breadcrum>
    </header>
    @endif
    <div class="course-header mb-3 d-none">
        <h1>{{ $courseMaster->first()->course_name ?? 'Course Name' }}</h1>
        <p class="mb-0 text-white fw-medium">
            <span class="badge">{{ $courseMaster->first()->couse_short_name ?? 'Course Code' }}</span>
            | <strong>Year:</strong> {{ $courseMaster->first()->course_year ?? date('Y') }}
        </p>
    </div>

    <!-- Calendar page content (layout already provides main#main-content) -->
    <div id="calendar-page-content">
        <!-- Action Controls with proper semantics -->
        @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
        {{-- Filters are now inside the calendar card toolbar --}}
        @endif

        <!-- Calendar Container -->
        <section class="calendar-container" aria-label="Academic calendar">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4 position-relative">

                    <!-- Loading overlay -->
                    <div id="calendarLoadingOverlay"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white"
                        style="min-height: 400px; z-index: 100;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading calendar...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading calendar...</p>
                        </div>
                    </div>

                    <!-- Unified toolbar: Filters | Month Nav | View Icons -->
                    <div id="calendarSheetToolbar"
                        class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-3 pb-3 border-bottom">

                        {{-- LEFT: Filters --}}
                        <div class="d-flex align-items-center gap-1 gap-md-3 flex-wrap flex-grow-1">
                            <span class="fw-semibold text-muted" style="font-size: 14px;">Filters</span>

                            @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
                            <div class="calendar-choices-bootstrap" style="min-width: 180px;">
                                <select class="form-select js-calendar-course-choice cal-filter-select"
                                    id="courseFilter" aria-describedby="courseFilterHelp">
                                    <option value="">Course Name</option>
                                    @foreach($courseMaster as $course)
                                    <option value="{{ $course->pk }}"
                                        {{ $courseMaster->first() && $course->pk == $courseMaster->first()->pk ? 'selected' : '' }}>
                                        {{ $course->course_name }} ({{ $course->couse_short_name }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <a href="javascript:void(0)" id="calResetFilters" class="cal-btn-reset-filters">Reset
                                Filters</a>
                        </div>

                        {{-- RIGHT: Period navigation + view toggles --}}
                        <div class="calendar-toolbar-controls d-flex align-items-center gap-2 gap-md-3 flex-wrap ms-auto justify-content-end">
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="cal-nav-btn" id="calCustomPrev" aria-label="Previous period">
                                    <i class="material-icons" style="font-size: 20px;">chevron_left</i>
                                </button>
                                <span class="cal-month-title text-center" id="calCustomTitle"></span>
                                <button type="button" class="cal-nav-btn" id="calCustomNext" aria-label="Next period">
                                    <i class="material-icons" style="font-size: 20px;">chevron_right</i>
                                </button>
                            </div>

                            <div class="d-flex align-items-center gap-1 flex-wrap" role="group" aria-label="Calendar view">
                                <button type="button" class="cal-view-icon-btn" data-view="list" id="calendarViewSheetBtn"
                                    aria-pressed="false" title="Timetable sheet">
                                    <i class="material-icons" style="font-size: 22px;">view_list</i>
                                </button>
                                <button type="button" class="cal-view-icon-btn active" data-view="month"
                                    id="calendarViewMonthBtn" aria-pressed="true" title="Month view">
                                    <i class="material-icons" style="font-size: 22px;">calendar_view_month</i>
                                </button>
                                <button type="button" class="cal-view-icon-btn" data-view="week" id="calendarViewWeekBtn"
                                    aria-pressed="false" title="Week view">
                                    <i class="material-icons" style="font-size: 22px;">view_week</i>
                                </button>
                                <button type="button" class="cal-view-icon-btn" data-view="day" id="calendarViewDayBtn"
                                    aria-pressed="false" title="Day view">
                                    <i class="material-icons" style="font-size: 22px;">calendar_view_day</i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Export buttons (shown contextually) --}}
                    <div id="calendarExportToolbar" class="d-flex flex-wrap justify-content-end gap-2 mb-3 d-none">
                        <div class="btn-group btn-group-sm shadow-sm flex-wrap" role="group"
                            aria-label="Week timetable export">
                            <button type="button" class="btn btn-outline-secondary" id="weekTimetablePrintBtn"
                                title="Print weekly timetable">
                                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="weekTimetablePdfViewBtn"
                                title="Open weekly timetable PDF in a new tab">
                                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="weekTimetablePdfDownloadBtn"
                                title="Download weekly timetable as PDF file">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>PDF file
                            </button>
                            <button type="button" class="btn btn-outline-success" id="weekTimetableExcelBtn"
                                title="Download weekly timetable as Excel (grid + sessions)">
                                <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="weekTimetablePrintPageBtn"
                                title="Print only the timetable sheet (this page)">
                                <i class="bi bi-printer-fill me-1" aria-hidden="true"></i>Print sheet
                            </button>
                        </div>
                    </div>

                    {{-- Week / day schedule context (Bootstrap 5.3) --}}
                    <div id="calendarScheduleContext"
                        class="alert alert-primary border-primary-subtle bg-primary-subtle rounded-3 py-2 px-3 mb-3 d-none flex-wrap align-items-center justify-content-between gap-2 shadow-sm"
                        role="status" aria-live="polite">
                        <div class="d-flex align-items-center flex-wrap gap-2 min-w-0">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white flex-shrink-0"
                                style="width: 2rem; height: 2rem;" aria-hidden="true">
                                <i class="bi bi-clock-fill small"></i>
                            </span>
                            <div class="min-w-0">
                                <span class="fw-semibold text-primary-emphasis d-block small">Academic schedule</span>
                                <span class="text-muted small">Visible hours for week &amp; day views</span>
                            </div>
                            <span class="badge rounded-pill text-bg-primary px-3 py-2 fw-semibold">9:00 AM – 5:30 PM</span>
                        </div>
                        <span class="badge text-bg-light border text-secondary px-3 py-2" id="calendarScheduleViewLabel">Week view</span>
                    </div>

                    <div id="calendarScheduleShell" class="calendar-schedule-shell rounded-3 border bg-body overflow-hidden shadow-sm mb-4">
                        <div id="calendar" class="fc p-0" role="application" aria-label="Interactive calendar"></div>
                    </div>

                    <!-- List View — Revised time table (PDF-style sheet) -->
                    <div id="eventListView" class="mt-4 d-none" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper">
                            <div class="card timetable-pdf-sheet border-2 shadow-sm mb-4">
                                <div class="card-body p-3 p-md-4">
                                    <header class="timetable-pdf-banner pb-3 mb-3 border-bottom border-2">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-auto">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png"
                                                    width="44" height="44" class="timetable-pdf-emblem"
                                                    alt="National Emblem" loading="lazy">
                                            </div>
                                            <div class="col min-w-0">
                                                <p
                                                    class="timetable-pdf-hindi institution-name hindi-text mb-1 small text-body-secondary">
                                                    लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी
                                                </p>
                                                <p class="timetable-pdf-english fw-semibold text-primary mb-1 mb-md-2">
                                                    Lal Bahadur Shastri National Academy of Administration, Mussoorie
                                                </p>
                                                <p class="timetable-pdf-course text-body-secondary small mb-0 fw-medium"
                                                    id="timetableCourseTitle">
                                                    Academic timetable — select a course filter when available
                                                </p>
                                                <p class="timetable-pdf-period small text-muted mb-0 mt-1 fst-italic d-none"
                                                    id="timetableCoursePeriod" aria-live="polite"></p>
                                            </div>
                                            <div class="col-auto text-end d-none d-md-block">
                                                <img src="{{ asset('images/lbsnaa_logo.jpg') }}"
                                                    onerror="this.onerror=null;this.src='https://www.lbsnaa.gov.in/admin_assets/images/logo.png'"
                                                    class="timetable-pdf-logo" alt="LBSNAA" width="160" height="48"
                                                    loading="lazy">
                                            </div>
                                        </div>

                                        <div class="row align-items-end g-2 mt-3">
                                            <div class="col-lg-8">
                                                <h1
                                                    class="h4 fw-bold text-dark mb-1 d-flex flex-wrap align-items-center gap-2">
                                                    <span>Time Table</span>
                                                    <span class="text-secondary fw-normal">:</span>
                                                    <span class="text-secondary fw-normal">Week</span>
                                                    <span id="currentWeekNumber" class="text-primary"
                                                        aria-live="polite">—</span>
                                                    <span class="text-secondary fw-normal ms-1 small">Revised</span>
                                                </h1>
                                                <p class="text-muted small mb-0" id="weekRangeText" aria-live="polite">
                                                    <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>—
                                                </p>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                                    <div class="btn-group shadow-sm" role="group"
                                                        aria-label="Week navigation">
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-sm px-2" id="prevWeekBtn"
                                                            aria-label="Previous week">
                                                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-sm px-3"
                                                            id="currentWeekBtn" aria-label="Current week">
                                                            <i class="bi bi-calendar-check me-1"
                                                                aria-hidden="true"></i>Today
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-sm px-2" id="nextWeekBtn"
                                                            aria-label="Next week">
                                                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <p
                                            class="small text-center text-body-secondary border-top border-light pt-2 mt-3 mb-0 px-md-5">
                                            <span class="fw-semibold text-dark">Note:</span>
                                            Tea break, lunch break, and venue lines follow the official programme when
                                            entered as session titles in the calendar.
                                        </p>
                                    </header>

                                    @php
                                    $ttFootnotes = array_values(array_filter(array_map('trim',
                                    config('week_timetable.footnotes', []))));
                                    @endphp
                                    @if(count($ttFootnotes))
                                    <div class="timetable-footnotes small text-body-secondary border border-light rounded-2 px-3 py-2 mb-2 bg-light"
                                        role="note" aria-label="Programme notes">
                                        @foreach ($ttFootnotes as $fn)
                                        <p class="mb-1 lh-sm">{{ e($fn) }}</p>
                                        @endforeach
                                    </div>
                                    @endif

                                    <div
                                        class="timetable-container border border-dark border-opacity-25 rounded-1 overflow-hidden bg-white">
                                        <div class="table-responsive" role="region" aria-label="Weekly timetable grid">
                                            <table class="table table-bordered timetable-grid mb-0" id="timetableTable"
                                                aria-describedby="timetableDescription">
                                                <caption class="visually-hidden" id="timetableDescription">
                                                    Weekly academic timetable showing events by time slot and weekday
                                                </caption>
                                                <thead id="timetableHead">
                                                    <tr class="day-names-row">
                                                        <th scope="col" rowspan="2" class="time-column align-middle">
                                                            TIME</th>
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

                                    <div class="accordion accordion-flush mt-4 border-top pt-3"
                                        id="timetableLegendAccordion">
                                        <div class="accordion-item border rounded-2 overflow-hidden">
                                            <h2 class="accordion-header" id="timetableLegendHeading">
                                                <button class="accordion-button collapsed py-2 small fw-semibold"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#timetableLegendCollapse" aria-expanded="false"
                                                    aria-controls="timetableLegendCollapse">
                                                    <i class="bi bi-journal-text me-2 text-primary"
                                                        aria-hidden="true"></i>
                                                    Venues, cadres &amp; abbreviations (reference)
                                                </button>
                                            </h2>
                                            <div id="timetableLegendCollapse" class="accordion-collapse collapse"
                                                aria-labelledby="timetableLegendHeading"
                                                data-bs-parent="#timetableLegendAccordion">
                                                <div class="accordion-body small text-body-secondary">
                                                    <p class="mb-2 text-dark fw-semibold">Sample reference (from
                                                        official time table format)</p>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <p class="fw-semibold text-primary mb-1">Venue abbreviations
                                                            </p>
                                                            <ul class="list-unstyled mb-0 lh-sm">
                                                                <li><strong>TH</strong> — Tagore Hall</li>
                                                                <li><strong>AH</strong> — Ambedkar Hall (Aadharshila)
                                                                </li>
                                                                <li><strong>SA</strong> — Sampoornanand Auditorium</li>
                                                                <li><strong>SPH</strong> — Sardar Patel Hall</li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="fw-semibold text-primary mb-1">Module tags</p>
                                                            <ul class="list-unstyled mb-0 lh-sm">
                                                                <li><strong>GM</strong> — Governance Module</li>
                                                                <li><strong>L</strong> — Law</li>
                                                                <li><strong>RAM</strong> — Rural &amp; Agriculture
                                                                    Module</li>
                                                                <li><strong>TM</strong> — Technology Module</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0 mt-2 fst-italic">Your live grid uses calendar data;
                                                        expand rows to read full topic, faculty, and venue from each
                                                        cell.</p>
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
    </div>
</div>

@include('admin.calendar.partials.add_edit_events')
@include('admin.calendar.partials.events_details')
@include('admin.calendar.partials.confirmation')

@endsection

@push('scripts')
@if(hasRole('Student-OT'))
<script src="{{ asset('admin_assets/libs/fullcalendar/index.global.min.js') }}"></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- Modern JavaScript with improved accessibility -->
<script>
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
    minTime: '09:00',
    maxTime: '17:30',
    /** FullCalendar: 0 = Sunday, 6 = Saturday — academic calendar Mon–Fri only */
    weekdayHiddenDays: [0, 6]
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
        } catch (e) {
            /* ignore */
        }
    }
    const wrap = document.querySelector('.calendar-choices-bootstrap .choices');
    if (wrap) {
        wrap.classList.remove('is-open', 'is-flipped');
        wrap.querySelectorAll('.choices__list--dropdown, .choices__list[aria-expanded]').forEach((el) => {
            try {
                el.setAttribute('aria-hidden', 'true');
            } catch (e2) {
                /* ignore */
            }
        });
    }
    try {
        const filterRoot = document.querySelector('.calendar-choices-bootstrap');
        if (filterRoot && document.activeElement && filterRoot.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    } catch (e3) {
        /* ignore */
    }
    document.body.classList.add('calendar-suppress-course-filter-dropdown');
}

function releaseCourseFilterDropdownSuppression() {
    document.body.classList.remove('calendar-suppress-course-filter-dropdown');
    const wrap = document.querySelector('.calendar-choices-bootstrap .choices');
    if (wrap) {
        wrap.querySelectorAll('.choices__list--dropdown, .choices__list[aria-expanded]').forEach((el) => {
            try {
                el.removeAttribute('aria-hidden');
            } catch (e) {
                /* ignore */
            }
        });
    }
    const select = document.getElementById('courseFilter');
    const inst = select && select._courseChoices;
    if (inst && typeof inst.hideDropdown === 'function') {
        try {
            inst.hideDropdown();
        } catch (e2) {
            /* ignore */
        }
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
        this.eventHoverShowTimer = null;
        this.eventHoverHideTimer = null;
        this.eventHoverPinned = false;
        this.init();
    }

    init() {
        try {
            this.initFullCalendar();

            try {
                this.bindEvents();
            } catch (e) {
                console.error('bindEvents error:', e);
            }
            try {
                this.setupAccessibility();
            } catch (e) {
                console.error('setupAccessibility error:', e);
            }
            try {
                this.validateDates();
            } catch (e) {
                console.error('validateDates error:', e);
            }
            try {
                this.updateCurrentWeek();
            } catch (e) {
                console.error('updateCurrentWeek error:', e);
            }
            try {
                this.observeMoreLinksChanges();
            } catch (e) {
                console.error('observeMoreLinksChanges error:', e);
            }
            try {
                this.initDensity();
            } catch (e) {
                console.error('initDensity error:', e);
            }

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
        const calendarEl = document.getElementById('calendar');
        const loadingOverlay = document.getElementById('calendarLoadingOverlay');

        if (!calendarEl) {
            throw new Error('Calendar element not found');
        }

        // Get initial course ID from filter dropdown
        const courseFilter = document.getElementById('courseFilter');
        this.selectedCourseId = courseFilter && courseFilter.value ? courseFilter.value : null;

        // Update course header with initial selection
        // this.updateCourseHeader();

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            firstDay: 1,
            weekends: false,
            hiddenDays: CalendarConfig.weekdayHiddenDays,
            headerToolbar: false,
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day'
            },
            allDaySlot: true,
            slotMinTime: CalendarConfig.minTime,
            slotMaxTime: CalendarConfig.maxTime,
            scrollTime: `${CalendarConfig.minTime}:00`,
            nowIndicator: true,
            slotDuration: '00:30:00',
            snapDuration: '00:30:00',
            slotLabelInterval: '01:00:00',
            slotLabelFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
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
                    dayMaxEvents: false,
                    displayEventEnd: true
                },
                timeGridWeek: {
                    allDaySlot: false,
                    dayMaxEvents: false,
                    eventMaxStack: 6,
                    slotEventOverlap: false
                },
                timeGridDay: {
                    allDaySlot: false,
                    dayMaxEvents: false,
                    eventMaxStack: 8,
                    slotEventOverlap: false
                }
            },
            events: (info, successCallback, failureCallback) => {
                this.fetchEvents(info, successCallback, failureCallback);
            },
            loading: (isLoading) => {
                const loadingOverlay = document.getElementById('calendarLoadingOverlay');
                if (!isLoading && loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            },
            eventContent: this.renderEventContent.bind(this),
            eventClick: this.handleEventClick.bind(this),
            eventMouseEnter: this.handleEventMouseEnter.bind(this),
            eventMouseLeave: this.handleEventMouseLeave.bind(this),
            select: this.handleDateSelect.bind(this),
            eventDidMount: this.setEventAccessibility.bind(this),
            dayCellDidMount: this.setDayCellAccessibility.bind(this),
            datesSet: () => {
                this.applyWeekdayOnlyCalendar();
                this.updateCalendarNavChrome();
                this.hideEventDetailsHover(true);
                if (this.isMonthGridView()) {
                    setTimeout(() => this.styleMoreLinks(), 50);
                }
            }
        });

        this.calendar.render();
        this.applyWeekdayOnlyCalendar();

        // Update custom title on initial render
        this.updateCalendarNavChrome();

        // Wire custom prev/next nav buttons
        document.getElementById('calCustomPrev')?.addEventListener('click', () => {
            this.navigateCalendarPeriod(-1);
        });
        document.getElementById('calCustomNext')?.addEventListener('click', () => {
            this.navigateCalendarPeriod(1);
        });

        this.styleMoreLinks();
        this.applyDenseMode();
    }

    /** Y-m-d in local timezone (avoids UTC shift from toISOString). */
    formatLocalDate(date) {
        if (!date || Number.isNaN(date.getTime())) {
            return '';
        }
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    syncListViewWeekToDate(dateStr) {
        if (!dateStr) {
            return;
        }
        const parts = String(dateStr).split('-').map((n) => parseInt(n, 10));
        if (parts.length < 3 || parts.some((n) => Number.isNaN(n))) {
            return;
        }
        const target = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0, 0);
        const today = new Date();
        const mondayOffset = (d) => {
            const dow = d.getDay();
            return d.getDate() - dow + (dow === 0 ? -6 : 1);
        };
        const currentWeekStart = new Date(today.getFullYear(), today.getMonth(), mondayOffset(today));
        const targetWeekStart = new Date(target.getFullYear(), target.getMonth(), mondayOffset(target));
        const msPerWeek = 7 * 24 * 60 * 60 * 1000;
        this.listViewWeekOffset = Math.round((targetWeekStart - currentWeekStart) / msPerWeek);
    }

    refreshCalendarViews() {
        if (this.calendar) {
            this.calendar.refetchEvents();
        }
        const listViewEl = document.getElementById('eventListView');
        if (listViewEl && !listViewEl.classList.contains('d-none')) {
            this.loadListView();
        }
    }

    fetchEvents(info, successCallback, failureCallback) {
        // Build URL with course filter
        let url = CalendarConfig.api.events;
        const params = new URLSearchParams();

        if (info.start) {
            params.append('start', this.formatLocalDate(info.start));
        }
        if (info.end) {
            params.append('end', this.formatLocalDate(info.end));
        }
        if (this.selectedCourseId) {
            params.append('course_id', this.selectedCourseId);
        }

        if (params.toString()) {
            url += '?' + params.toString();
        }

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
                // Filter out holidays and restricted holidays
                const filteredData = data.filter(event => {
                    const type = (event.type || event.event_type || event.session_type || '').toString()
                        .toLowerCase();
                    return type !== 'holiday' && type !== 'restricted holiday' && type !==
                        'restricted' && !type.includes('holiday');
                });
                const visibleEvents = data.length > 0 && filteredData.length === 0 ? data : filteredData;
                const normalizedEvents = this.normalizeCalendarEvents(visibleEvents);
                successCallback(normalizedEvents);
            })
            .catch(error => {
                console.error('Error fetching events:', error);
                this.showNotification('Failed to load calendar events. Please refresh the page.', 'danger');
                failureCallback(error);
            });
    }

    /** Month, week, and day views: Monday–Friday only (hide Saturday & Sunday). */
    applyWeekdayOnlyCalendar() {
        if (!this.calendar) {
            return;
        }
        this.calendar.setOption('weekends', false);
        this.calendar.setOption('hiddenDays', CalendarConfig.weekdayHiddenDays);

        if (this.calendar.view?.type === 'timeGridDay') {
            const snapped = this.snapToWeekday(this.calendar.getDate());
            if (snapped.getTime() !== this.calendar.getDate().getTime()) {
                this.calendar.gotoDate(snapped);
            }
        }
    }

    snapToWeekday(date) {
        const d = new Date(date);
        const dow = d.getDay();
        if (dow === 0) {
            d.setDate(d.getDate() + 1);
        } else if (dow === 6) {
            d.setDate(d.getDate() - 1);
        }
        return d;
    }

    navigateCalendarPeriod(direction) {
        if (!this.calendar) {
            return;
        }

        const viewType = this.calendar.view?.type || '';
        if (viewType === 'timeGridDay') {
            const step = direction < 0 ? -1 : 1;
            let date = new Date(this.calendar.getDate());
            do {
                date.setDate(date.getDate() + step);
            } while (CalendarConfig.weekdayHiddenDays.includes(date.getDay()));

            this.calendar.gotoDate(date);
            this.updateCalendarNavChrome();
            return;
        }

        if (direction < 0) {
            this.calendar.prev();
        } else {
            this.calendar.next();
        }
        this.applyWeekdayOnlyCalendar();
        this.updateCalendarNavChrome();
    }

    normalizeCalendarEvents(events) {
        if (!Array.isArray(events)) return [];

        return events
            .map((event, index) => {
                const start = event.start || event.START_DATE || event.start_datetime || event.holiday_date ||
                    event.date;
                if (!start) {
                    console.warn('Skipping calendar event without start date:', event);
                    return null;
                }

                const rawEnd = event.end || event.END_DATE || null;
                const isDateOnlyStart = /^\d{4}-\d{2}-\d{2}$/.test(String(start));
                let end = rawEnd;

                if (!end && isDateOnlyStart) {
                    const nextDate = new Date(`${start}T00:00:00`);
                    nextDate.setDate(nextDate.getDate() + 1);
                    end = nextDate.toISOString().slice(0, 10);
                }

                const title = event.title ||
                    event.subject_topic ||
                    event.holiday_name ||
                    event.topic ||
                    event.course_name ||
                    'Calendar Event';

                return {
                    ...event,
                    id: event.id || event.pk || `calendar_event_${index}`,
                    title,
                    start,
                    end,
                    allDay: typeof event.allDay === 'boolean' ? event.allDay : isDateOnlyStart,
                    display: 'block',
                    classNames: ['calendar-visible-event'],
                    extendedProps: {
                        ...event,
                        ...(event.extendedProps || {})
                    }
                };
            })
            .filter(Boolean);
    }

    isMonthGridView() {
        const viewType = this.calendar?.view?.type || '';
        return viewType === 'dayGridMonth' || viewType === 'dayGridWeek' || viewType === 'dayGridDay';
    }

    formatCalendarDayTitle(date) {
        const d = new Date(date);
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
            'October', 'November', 'December'
        ];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    formatCalendarWeekTitle(view) {
        if (!view) {
            return '';
        }
        const start = new Date(view.currentStart);
        const end = new Date(view.currentEnd);
        end.setDate(end.getDate() - 1);
        const fmt = (dt) => {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${dt.getDate()} ${months[dt.getMonth()]} ${dt.getFullYear()}`;
        };
        if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ];
            return `${start.getDate()} – ${end.getDate()} ${months[start.getMonth()]} ${start.getFullYear()}`;
        }
        return `${fmt(start)} – ${fmt(end)}`;
    }

    updateCalendarNavChrome() {
        const titleEl = document.getElementById('calCustomTitle');
        const prevBtn = document.getElementById('calCustomPrev');
        const nextBtn = document.getElementById('calCustomNext');
        if (!titleEl || !this.calendar) {
            return;
        }

        const viewType = this.calendar.view?.type || 'dayGridMonth';
        const date = this.calendar.getDate();
        let navUnit = 'month';

        if (viewType === 'timeGridWeek') {
            titleEl.textContent = this.formatCalendarWeekTitle(this.calendar.view);
            navUnit = 'week';
        } else if (viewType === 'timeGridDay') {
            titleEl.textContent = this.formatCalendarDayTitle(date);
            navUnit = 'day';
        } else {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ];
            titleEl.textContent = `${months[date.getMonth()]} ${date.getFullYear()}`;
            navUnit = 'month';
        }

        if (prevBtn) {
            prevBtn.setAttribute('aria-label', `Previous ${navUnit}`);
        }
        if (nextBtn) {
            nextBtn.setAttribute('aria-label', `Next ${navUnit}`);
        }

        const calendarRoot = document.getElementById('calendar');
        const scheduleShell = document.getElementById('calendarScheduleShell');
        const scheduleContext = document.getElementById('calendarScheduleContext');
        const scheduleViewLabel = document.getElementById('calendarScheduleViewLabel');
        const isTimeGrid = viewType === 'timeGridWeek' || viewType === 'timeGridDay';

        if (calendarRoot) {
            calendarRoot.classList.toggle('calendar-view-month', viewType === 'dayGridMonth');
            calendarRoot.classList.toggle('calendar-view-week', viewType === 'timeGridWeek');
            calendarRoot.classList.toggle('calendar-view-day', viewType === 'timeGridDay');
        }

        if (scheduleShell) {
            scheduleShell.classList.toggle('calendar-schedule-shell--active', isTimeGrid);
        }

        if (scheduleContext) {
            scheduleContext.classList.toggle('d-none', !isTimeGrid);
            scheduleContext.classList.toggle('d-flex', isTimeGrid);
        }

        if (scheduleViewLabel) {
            if (viewType === 'timeGridWeek') {
                scheduleViewLabel.textContent = 'Week view';
            } else if (viewType === 'timeGridDay') {
                scheduleViewLabel.textContent = 'Day view';
            }
        }
    }

    updateCustomTitle() {
        this.updateCalendarNavChrome();
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
            '.fc-daygrid-day-more-link, .fc-more-link, .fc-timegrid-more-link, .fc-daygrid-day-frame a[data-date], .fc-timegrid a[aria-label*="more"]'
        );
        moreLinks.forEach(link => {
            if (link.textContent.includes('+') || link.textContent.toLowerCase().includes('more')) {
                link.style.fontSize = '0.75rem';
                link.style.fontWeight = '500';
                link.style.color = '#00539b';
                link.style.backgroundColor = 'transparent';
                link.style.padding = '0.25rem 0 0';
                link.style.borderRadius = '0';
                link.style.display = 'inline-block';
                link.style.textDecoration = 'none';
                link.style.background = 'transparent';
                link.style.transition = 'all 0.2s ease';
                link.style.boxShadow = 'none';

                link.addEventListener('mouseenter', () => {
                    link.style.color = '#003f78';
                });

                link.addEventListener('mouseleave', () => {
                    link.style.color = '#00539b';
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
        const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [char]));

        try {
            const props = arg.event.extendedProps || {};
            const normalizeGroupText = (value) => {
                let parsed = value;
                if (typeof parsed === 'string' && parsed.trim().startsWith('[')) {
                    try {
                        parsed = JSON.parse(parsed);
                    } catch (e) {
                        parsed = value;
                    }
                }
                if (Array.isArray(parsed)) {
                    return parsed.map((item) => {
                        if (typeof item === 'string') return item;
                        if (item && typeof item === 'object') {
                            return item.group_name || item.type_name || item.name || '';
                        }
                        return '';
                    }).filter(Boolean).join(', ');
                }
                return parsed || '';
            };
            const formatDate = (date) => {
                if (!date || Number.isNaN(new Date(date).getTime())) return '';
                const day = date.toLocaleDateString('en-IN', {
                    day: 'numeric'
                });
                const month = date.toLocaleDateString('en-IN', {
                    month: 'short'
                });
                const year = date.toLocaleDateString('en-IN', {
                    year: 'numeric'
                });
                const weekday = date.toLocaleDateString('en-IN', {
                    weekday: 'long'
                });
                return `${day} ${month} ${year} ${weekday}`;
            };
            const startTime = arg.event.start ? arg.event.start.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }) : '';
            const endTime = arg.event.end ? arg.event.end.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }) : '';
            const idStr = String(arg.event.id || (arg.event._def && arg.event._def.publicId) || Math.random().toString(36).slice(2))
                .replace(/[^a-zA-Z0-9_-]/g, '_');
            const titleId = `fc-evt-${idStr}-title`;
            const descId = `fc-evt-${idStr}-desc`;
            const type = (props.type || props.event_type || props.session_type || '').toString();
            const eventTitle = escapeHtml(props.event_name || props.course_name || arg.event.title ||
                'Calendar Event');
            const topic = escapeHtml(props.topic || props.subject_name || props.module_name || arg.event.title ||
                '');
            const venue = escapeHtml(props.vanue || props.venue_name || '');
            const faculty = escapeHtml(props.faculty_name || '');
            const groupText = escapeHtml(normalizeGroupText(props.group_name || props.group_names || ''));
            const dateLine = escapeHtml([formatDate(arg.event.start), [startTime, endTime].filter(Boolean).join(
                ' - ')].filter(Boolean).join(' '));
            const typeAttr = escapeHtml(type.toLowerCase());
            const isTimeGrid = arg.view?.type === 'timeGridWeek' || arg.view?.type === 'timeGridDay';
            const slotTitle = escapeHtml(topic || eventTitle);
            const slotTimeLine = escapeHtml(this.formatTimeGridSlotTime(
                arg.event.start,
                arg.event.end,
                props.class_session
            ));

            if (isTimeGrid) {
                return {
                    html: `
                    <div class="fc-event-card calendar-timegrid-slot-card calendar-visible-card
                                h-100 rounded-2 p-2 overflow-hidden"
                         tabindex="0"
                         role="button"
                         aria-labelledby="${titleId}"
                         aria-describedby="${descId}"
                         ${typeAttr ? `data-event-type="${typeAttr}"` : ''}>
                        <div class="event-card-title fw-semibold text-truncate mb-0" id="${titleId}">${slotTitle}</div>
                        ${slotTimeLine ? `<div class="event-card-time text-muted text-truncate mt-1">${slotTimeLine}</div>` : ''}
                        <span class="visually-hidden" id="${descId}">${slotTitle} ${slotTimeLine} ${venue ? `at ${venue} ` : ''}${faculty ? `with ${faculty}` : ''}</span>
                    </div>
                `
                };
            }

            return {
                html: `
                <div class="fc-event-card calendar-reference-card calendar-visible-card"
                     tabindex="0"
                     role="button"
                     aria-labelledby="${titleId}"
                     aria-describedby="${descId}"
                     ${typeAttr ? `data-event-type="${typeAttr}"` : ''}>
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="min-w-0 flex-grow-1">
                            <div class="event-card-title" id="${titleId}">${eventTitle}</div>
                            ${dateLine ? `<div class="event-card-time">${dateLine}</div>` : ''}
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <span class="event-card-action event-card-action-danger" aria-hidden="true">
                                <i class="bi bi-trash3-fill"></i>
                            </span>
                            <span class="event-card-action" aria-hidden="true">
                                <i class="bi bi-pencil-fill"></i>
                            </span>
                        </div>
                    </div>
                    ${topic ? `<div class="event-card-topic mb-2">${topic}</div>` : ''}
                    ${(faculty || groupText) ? `
                        <div class="event-card-info mb-2">
                            ${faculty ? `<p>Faculty: ${faculty}</p>` : ''}
                            ${groupText ? `<p>Group Name: ${groupText}</p>` : ''}
                        </div>
                    ` : ''}
                    ${venue ? `
                        <div class="event-card-venue">
                            <span>Venue: ${venue}</span>
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        </div>
                    ` : ''}
                    <span class="visually-hidden" id="${descId}">${type ? `${escapeHtml(type)} ` : ''}${dateLine ? `${dateLine} ` : ''}${venue ? `at ${venue} ` : ''}${faculty ? `with ${faculty}` : ''}</span>
                </div>
            `
            };
        } catch (error) {
            console.error('Event render error:', error, arg.event);
            const props = arg.event.extendedProps || {};
            const title = escapeHtml(arg.event.title || props.subject_topic || props.holiday_name ||
                'Calendar Event');
            const timeText = escapeHtml(arg.timeText || props.class_session || '');
            return {
                html: `
                    <div class="fc-event-card calendar-reference-card calendar-visible-card" tabindex="0" role="button">
                        <div class="event-card-title">${title}</div>
                        ${timeText ? `<div class="event-card-time">${timeText}</div>` : ''}
                    </div>
                `
            };
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
            const dayLabel = date ? new Date(date).toLocaleDateString('en-IN', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : '';
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
                    case 'ArrowRight':
                        targetIdx = idx + 1;
                        break;
                    case 'ArrowLeft':
                        targetIdx = idx - 1;
                        break;
                    case 'ArrowDown':
                        targetIdx = idx + cols;
                        break;
                    case 'ArrowUp':
                        targetIdx = idx - cols;
                        break;
                    case 'Enter':
                    case ' ': {
                        // Open "+ more" or focus first event
                        const more = cell.querySelector('.fc-daygrid-day-more-link, .fc-more-link');
                        const evt = cell.querySelector('.fc-event, .fc-event-card');
                        if (more) {
                            more.click();
                            e.preventDefault();
                        } else if (evt) {
                            evt.dispatchEvent(new MouseEvent('click'));
                            e.preventDefault();
                        }
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
        this.closePopover();
        if (info.jsEvent) {
            info.jsEvent.preventDefault();
        }

        this.currentEventId = info.event.id;
        if (String(info.event.id || '').startsWith('holiday_')) {
            return;
        }

        const props = info.event.extendedProps || {};
        const displayTitle = props.event_name || props.course_name || info.event.title || '';
        this.showEventDetailsHover(info, displayTitle, true);
    }

    handleEventMouseEnter(info) {
        if (String(info.event.id || '').startsWith('holiday_')) {
            return;
        }

        window.clearTimeout(this.eventHoverHideTimer);
        this.eventHoverHideTimer = null;

        const props = info.event.extendedProps || {};
        const displayTitle = props.event_name || props.course_name || info.event.title || '';
        const anchorEl = info.el;

        window.clearTimeout(this.eventHoverShowTimer);
        this.eventHoverShowTimer = window.setTimeout(() => {
            this.showEventDetailsHover({
                event: info.event,
                el: anchorEl
            }, displayTitle, false);
        }, 180);
    }

    handleEventMouseLeave() {
        window.clearTimeout(this.eventHoverShowTimer);
        this.eventHoverShowTimer = null;

        if (this.eventHoverPinned) {
            return;
        }

        window.clearTimeout(this.eventHoverHideTimer);
        this.eventHoverHideTimer = window.setTimeout(() => {
            this.hideEventDetailsHover();
        }, 120);
    }

    getEventDetailsHoverPopover() {
        return document.getElementById('eventDetailsHoverPopover');
    }

    mapFcEventToDetailsData(event) {
        const props = event.extendedProps || {};
        const groupName = props.group_name ||
            (Array.isArray(props.group_names) ? props.group_names.join(', ') : '');

        return {
            id: event.id,
            topic: props.subject_topic || props.topic || event.title || '',
            start: event.start || props.START_DATE || props.start,
            faculty_name: props.faculty_name || '',
            venue_name: props.vanue || props.venue_name || '',
            class_session: props.class_session || '',
            group_name: groupName,
            internal_faculty: props.internal_faculty || ''
        };
    }

    populateEventDetailsCard(cardEl, data, displayTitle = '') {
        if (!cardEl || !data) {
            return;
        }

        const topic = String(data.topic || '').trim();
        const headline = String(displayTitle || '').trim();
        const dateLine = this.formatEventDetailsDateLine(data.start, data.class_session);
        const setDetail = (key, value) => {
            const el = cardEl.querySelector(`[data-detail="${key}"]`);
            if (el) {
                el.textContent = value;
            }
        };

        setDetail('title', headline || topic || 'Calendar Event');
        setDetail('topic', (headline && topic && headline !== topic) ? topic : '');
        setDetail('date', dateLine);
        setDetail('session', data.class_session || '');
        setDetail('faculty', data.faculty_name || '');
        setDetail('venue', data.venue_name || '');
        setDetail('group', data.group_name || '');
        setDetail('internal-faculty', data.internal_faculty || '');

        cardEl.querySelectorAll('[data-event-detail-edit], [data-event-detail-delete]').forEach((btn) => {
            if (data.id != null) {
                btn.dataset.eventId = String(data.id);
            }
        });
    }

    showEventDetailsHover(info, displayTitle = '', pin = false) {
        const popover = this.getEventDetailsHoverPopover();
        const anchorEl = info.el;
        const event = info.event;

        if (!popover || !anchorEl || !event || String(event.id || '').startsWith('holiday_')) {
            return;
        }

        const cardEl = popover.querySelector('.calendar-event-details-card');
        if (!cardEl) {
            return;
        }

        this.currentEventId = event.id;
        this.eventHoverPinned = !!pin;
        this.populateEventDetailsCard(cardEl, this.mapFcEventToDetailsData(event), displayTitle);

        popover.classList.remove('d-none');
        popover.classList.add('is-visible');
        popover.setAttribute('aria-hidden', 'false');

        this.positionEventDetailsHover(anchorEl);

        const eventId = event.id;
        this.enrichHoverEventDetails(eventId, cardEl, displayTitle);
    }

    async enrichHoverEventDetails(eventId, cardEl, displayTitle) {
        try {
            const response = await fetch(`${CalendarConfig.api.eventDetails}?id=${eventId}`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            const popover = this.getEventDetailsHoverPopover();
            if (!popover || !popover.classList.contains('is-visible')) {
                return;
            }
            if (String(this.currentEventId) !== String(eventId)) {
                return;
            }
            this.populateEventDetailsCard(cardEl, data, displayTitle);
        } catch (e) {
            console.warn('Hover event details enrich failed', e);
        }
    }

    positionEventDetailsHover(anchorEl) {
        const popover = this.getEventDetailsHoverPopover();
        if (!popover || !anchorEl) {
            return;
        }

        popover.style.visibility = 'hidden';
        popover.classList.remove('popover-arrow-left');

        const rect = anchorEl.getBoundingClientRect();
        const popRect = popover.getBoundingClientRect();
        const gap = 10;
        let left = rect.right + gap;
        let top = rect.top + (rect.height / 2) - (popRect.height / 2);

        if (left + popRect.width > window.innerWidth - 8) {
            left = rect.left - popRect.width - gap;
            popover.classList.add('popover-arrow-left');
        }

        top = Math.max(8, Math.min(top, window.innerHeight - popRect.height - 8));
        left = Math.max(8, Math.min(left, window.innerWidth - popRect.width - 8));

        popover.style.left = `${left}px`;
        popover.style.top = `${top}px`;
        popover.style.visibility = 'visible';
    }

    hideEventDetailsHover(force = false) {
        if (this.eventHoverPinned && !force) {
            return;
        }

        const popover = this.getEventDetailsHoverPopover();
        if (!popover) {
            return;
        }

        popover.classList.remove('is-visible');
        popover.classList.add('d-none');
        popover.setAttribute('aria-hidden', 'true');
        popover.style.visibility = '';
        popover.style.left = '';
        popover.style.top = '';
        this.eventHoverPinned = false;
    }

    initEventDetailsHoverPopover() {
        const popover = this.getEventDetailsHoverPopover();
        if (!popover || popover.dataset.hoverBound === 'true') {
            return;
        }
        popover.dataset.hoverBound = 'true';

        popover.addEventListener('mouseenter', () => {
            window.clearTimeout(this.eventHoverHideTimer);
            this.eventHoverHideTimer = null;
        });

        popover.addEventListener('mouseleave', () => {
            if (this.eventHoverPinned) {
                return;
            }
            window.clearTimeout(this.eventHoverHideTimer);
            this.eventHoverHideTimer = window.setTimeout(() => {
                this.hideEventDetailsHover();
            }, 120);
        });

        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('[data-event-detail-edit]');
            if (editBtn?.dataset?.eventId) {
                e.preventDefault();
                e.stopPropagation();
                this.currentEventId = editBtn.dataset.eventId;
                const modalEdit = document.getElementById('editEventBtn');
                if (modalEdit) {
                    modalEdit.dataset.id = editBtn.dataset.eventId;
                }
                this.hideEventDetailsHover(true);
                this.loadEventForEdit();
                return;
            }

            const deleteBtn = e.target.closest('[data-event-detail-delete]');
            if (deleteBtn?.dataset?.eventId) {
                e.preventDefault();
                e.stopPropagation();
                this.currentEventId = deleteBtn.dataset.eventId;
                const modalDelete = document.getElementById('deleteEventBtn');
                if (modalDelete) {
                    modalDelete.dataset.id = deleteBtn.dataset.eventId;
                }
                this.hideEventDetailsHover(true);
                this.confirmDelete();
                return;
            }

            if (!popover.classList.contains('is-visible')) {
                return;
            }
            if (popover.contains(e.target) || e.target.closest('.fc-event, .list-event-card')) {
                return;
            }
            this.hideEventDetailsHover(true);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideEventDetailsHover(true);
            }
        });

        window.addEventListener('scroll', () => {
            if (popover.classList.contains('is-visible') && !this.eventHoverPinned) {
                this.hideEventDetailsHover(true);
            }
        }, true);
    }

    closePopover() {
        this.hideEventDetailsHover(true);

        const openPopovers = document.querySelectorAll('.fc-popover');
        openPopovers.forEach(popover => {
            popover.remove();
        });

        const popoverBackdrops = document.querySelectorAll('.fc-popover-backdrop');
        popoverBackdrops.forEach(backdrop => {
            backdrop.remove();
        });
    }

    async loadEventDetails(eventId, displayTitle = '') {
        try {
            const response = await fetch(`${CalendarConfig.api.eventDetails}?id=${eventId}`, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) throw new Error('Failed to load event details');

            const data = await response.json();
            this.showEventDetails(data, displayTitle);

        } catch (error) {
            // this.showNotification('Error loading event details', 'danger');
            console.error('Event details error:', error);
        }
    }

    /**
     * Week/day slot label — "12:00AM - 12:15AM" style (matches reference design).
     */
    formatTimeGridSlotTime(start, end, classSession) {
        const formatClock = (date) => {
            if (!date || Number.isNaN(new Date(date).getTime())) {
                return '';
            }
            return new Date(date).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                })
                .replace(/\s/g, '');
        };

        const startStr = formatClock(start);
        const endStr = formatClock(end);
        if (startStr && endStr) {
            return `${startStr} - ${endStr}`;
        }

        const session = String(classSession || '').trim();
        if (session) {
            return session
                .replace(/\s*-\s*/g, ' - ')
                .replace(/\s+to\s+/gi, ' - ');
        }

        return startStr || endStr || '';
    }

    formatEventDetailsDateLine(start, classSession) {
        const startDate = start ? new Date(start) : null;
        let datePart = '';
        if (startDate && !Number.isNaN(startDate.getTime())) {
            const day = startDate.toLocaleDateString('en-IN', {
                day: 'numeric'
            });
            const month = startDate.toLocaleDateString('en-IN', {
                month: 'long'
            });
            const year = startDate.toLocaleDateString('en-IN', {
                year: 'numeric'
            });
            const weekday = startDate.toLocaleDateString('en-IN', {
                weekday: 'long'
            });
            datePart = `${day} ${month} ${year} ${weekday}`;
        }

        const session = String(classSession || '').trim();
        let timePart = '';
        if (session) {
            timePart = session
                .replace(/\s*-\s*/g, ' - ')
                .replace(/\s+to\s+/gi, ' - ');
        }

        return [datePart, timePart].filter(Boolean).join(' ');
    }

    showEventDetails(data, displayTitle = '') {
        const modal = document.getElementById('eventDetails');
        const cardEl = modal?.querySelector('.calendar-event-details-card');
        if (cardEl) {
            this.populateEventDetailsCard(cardEl, data, displayTitle);
        }

        const editBtn = document.getElementById('editEventBtn');
        const deleteBtn = document.getElementById('deleteEventBtn');
        if (editBtn && data.id != null) {
            editBtn.dataset.id = data.id;
        }
        if (deleteBtn && data.id != null) {
            deleteBtn.dataset.id = data.id;
        }

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    handleDateSelect(info) {
        if (!@json(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))) return;

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
        window.calendarEventModalStepper?.reset();

        // Reset date field
        document.getElementById('start_datetime').removeAttribute('readonly');

        // Show normal shift by default
        this.toggleShiftFields();
    }

    setFormDate(date) {
        const formattedDate = date.toLocaleDateString('en-CA');
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

        this.initEventDetailsHoverPopover();

        // Edit/Delete buttons (modal)
        document.getElementById('editEventBtn')?.addEventListener('click', () => this.loadEventForEdit());
        document.getElementById('deleteEventBtn')?.addEventListener('click', () => this.confirmDelete());

        // Create event button
        document.getElementById('createEventButton')?.addEventListener('click', () => {
            this.resetEventForm();
        });

        // List view: hover + click details
        const listView = document.getElementById('eventListView');
        listView?.addEventListener('mouseenter', (e) => {
            const card = e.target.closest('.list-event-card');
            if (!card?.dataset?.id) {
                return;
            }
            window.clearTimeout(this.eventHoverHideTimer);
            const cardTitle = card.querySelector('.title')?.textContent?.trim() || '';
            window.clearTimeout(this.eventHoverShowTimer);
            this.eventHoverShowTimer = window.setTimeout(() => {
                const popover = this.getEventDetailsHoverPopover();
                const cardEl = popover?.querySelector('.calendar-event-details-card');
                if (!popover || !cardEl) {
                    return;
                }
                this.currentEventId = card.dataset.id;
                this.eventHoverPinned = false;
                this.populateEventDetailsCard(cardEl, {
                    id: card.dataset.id,
                    topic: cardTitle,
                    start: null,
                    faculty_name: '',
                    venue_name: '',
                    class_session: card.querySelector('.meta span')?.textContent?.trim() || '',
                    group_name: card.dataset.group || '',
                    internal_faculty: ''
                }, cardTitle);
                popover.classList.remove('d-none');
                popover.classList.add('is-visible');
                popover.setAttribute('aria-hidden', 'false');
                this.positionEventDetailsHover(card);
                this.enrichHoverEventDetails(card.dataset.id, cardEl, cardTitle);
            }, 180);
        }, true);
        listView?.addEventListener('mouseleave', (e) => {
            if (!e.relatedTarget?.closest?.('#eventDetailsHoverPopover')) {
                this.handleEventMouseLeave();
            }
        }, true);
        listView?.addEventListener('click', (e) => {
            const card = e.target.closest('.list-event-card');
            if (card?.dataset?.id) {
                const cardTitle = card.querySelector('.title')?.textContent?.trim() || '';
                this.loadEventDetails(card.dataset.id, cardTitle);
            }
        });
        listView?.addEventListener('keydown', (e) => {
            const card = e.target.closest('.list-event-card');
            if (!card) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (card.dataset?.id) {
                    const cardTitle = card.querySelector('.title')?.textContent?.trim() || '';
                    this.loadEventDetails(card.dataset.id, cardTitle);
                }
            }
        });

        // Density toggle
        document.getElementById('toggleDensityBtn')?.addEventListener('click', () => this.toggleDensity());

        // Course filter change
        document.getElementById('courseFilter')?.addEventListener('change', (e) => {
            this.handleCourseFilterChange(e.target.value);
        });

        // Reset filters button
        document.getElementById('calResetFilters')?.addEventListener('click', (e) => {
            e.preventDefault();
            const courseSelect = document.getElementById('courseFilter');
            if (courseSelect) {
                // If Choices.js is active, use its API
                if (courseSelect._courseChoices) {
                    courseSelect._courseChoices.setChoiceByValue('');
                } else {
                    courseSelect.value = '';
                    courseSelect.dispatchEvent(new Event('change'));
                }
            }
            this.handleCourseFilterChange('');
        });
    }

    handleCourseFilterChange(courseId) {
        this.selectedCourseId = courseId || null;
        this.updateCourseHeader();

        this.refreshCalendarViews();
    }

    initDensity() {
        const saved = localStorage.getItem('calendarDensity');
        let isCompact;
        if (saved === null) {
            isCompact = false; // Default to comfortable mode for full cards
            try {
                localStorage.setItem('calendarDensity', 'comfortable');
            } catch {}
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
        const scheduleShell = document.getElementById('calendarScheduleShell');
        const listViewEl = document.getElementById('eventListView');
        const exportToolbar = document.getElementById('calendarExportToolbar');
        const scheduleContext = document.getElementById('calendarScheduleContext');

        if (view === 'list') {
            if (scheduleShell) {
                scheduleShell.classList.add('d-none');
            }
            if (scheduleContext) {
                scheduleContext.classList.add('d-none');
                scheduleContext.classList.remove('d-flex');
            }
            if (calendarEl) {
                calendarEl.style.display = 'none';
            }
            listViewEl.classList.remove('d-none');
            if (exportToolbar) exportToolbar.classList.remove('d-none');
            this.loadListView();
        } else {
            if (scheduleShell) {
                scheduleShell.classList.remove('d-none');
            }
            if (calendarEl) {
                calendarEl.style.display = '';
            }
            listViewEl.classList.add('d-none');
            if (exportToolbar) exportToolbar.classList.add('d-none');
            this.hideEventDetailsHover(true);
            this.calendar.changeView(this.getCalendarView(view));
            this.applyWeekdayOnlyCalendar();
            this.updateCalendarNavChrome();
            if (view === 'month') {
                setTimeout(() => this.styleMoreLinks(), 100);
                this.applyDenseMode();
            }
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
                const checkedCount = container.querySelectorAll(
                    'input[name="type_names[]"]:checked').length;

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
                const error = await response.json().catch(() => ({}));
                const err = new Error(error.message || 'Submission failed');
                if (error.errors) {
                    err.errors = error.errors;
                }
                throw err;
            }

            const result = await response.json();
            this.showNotification(result.message || 'Event saved successfully', 'success');

            // Close modal and refresh calendar
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            const savedCourseId = formData.get('Course_name');
            const savedDate = formData.get('start_datetime');

            if (savedCourseId) {
                this.selectedCourseId = savedCourseId;
                const courseFilter = document.getElementById('courseFilter');
                if (courseFilter) {
                    if (courseFilter._courseChoices) {
                        courseFilter._courseChoices.setChoiceByValue(String(savedCourseId));
                    } else {
                        courseFilter.value = savedCourseId;
                    }
                }
            }

            if (savedDate) {
                this.calendar.gotoDate(savedDate);
                this.updateCustomTitle();
                this.syncListViewWeekToDate(savedDate);
            }

            this.refreshCalendarViews();

        } catch (error) {
            let message = error.message || 'Submission failed';
            if (error.errors) {
                message = Object.values(error.errors).flat().join('\n');
            }
            this.showNotification(message, 'danger');
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
            facultySelectEl.dispatchEvent(new Event('change', {
                bubbles: true
            }));
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
        if (event.faculty_type == 2) {
            await this.setInternalFaculty(event.internal_faculty);
        }
    }
    async updateinternal_faculty(facultyType) {
        const internalFacultyDiv = document.getElementById('internalFacultyDiv');
        if (!internalFacultyDiv) return;

        switch (String(facultyType)) {
            case '1':
                internalFacultyDiv.style.display = 'none';
                break;
            case '2':
                internalFacultyDiv.style.display = 'block';
                break;
            default:
                internalFacultyDiv.style.display = 'block';
        }
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
            this.refreshCalendarViews();

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
            const weekStart = this.getListViewWeekStart();
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);

            let url = CalendarConfig.api.events;
            const params = new URLSearchParams();
            params.append('start', this.formatLocalDate(weekStart));
            params.append('end', this.formatLocalDate(weekEnd));
            if (this.selectedCourseId) {
                params.append('course_id', this.selectedCourseId);
            }
            url += '?' + params.toString();

            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const events = await response.json();

            // Week badge: programme week from course start_year (matches official PDF), else ISO-style week
            const date = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate());
            const jan4 = new Date(date.getFullYear(), 0, 4);
            const monday = new Date(jan4);
            monday.setDate(monday.getDate() - monday.getDay() + 1);
            const timeDiff = date - monday;
            const weekDiff = Math.floor(timeDiff / (7 * 24 * 60 * 60 * 1000));
            let weekNum = weekDiff + 1;

            const cmeta = CalendarConfig.courseMeta && this.selectedCourseId ?
                CalendarConfig.courseMeta[String(this.selectedCourseId)] :
                null;
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

            // Filter and render (timetable sheet omits holidays; main calendar still receives them)
            const filteredEvents = this.getEventsForWeek(events, this.listViewWeekOffset)
                .filter((e) => !this.isCalendarHoliday(e));
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
            const cmeta = CalendarConfig.courseMeta && this.selectedCourseId ?
                CalendarConfig.courseMeta[String(this.selectedCourseId)] :
                null;
            if (cmeta && cmeta.start_year && cmeta.end_date) {
                try {
                    const s = new Date(cmeta.start_year);
                    const e = new Date(cmeta.end_date);
                    if (!Number.isNaN(s.getTime()) && !Number.isNaN(e.getTime())) {
                        const fmt = (d) => d.toLocaleDateString('en-IN', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
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
                if (evd.getFullYear() !== d.getFullYear() || evd.getMonth() !== d.getMonth() || evd
                    .getDate() !== d.getDate()) {
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
        const cells = inners.map((inner) =>
            `<td class="event-cell text-center">${inner ? this.escapeHtml(inner) : ''}</td>`);
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
        const venueRow = venueText ?
            `<tr class="venue-summary-row"><th scope="row" class="time-column"></th><td colspan="5" class="event-cell text-center">${this.escapeHtml(venueText)}</td></tr>` :
            '';

        let html = breakRow + venueRow;
        sortedTimes.forEach((time) => {
            const dayEvents = timeSlots[time];
            const sample = this.pickSampleEventForTimeSlot(dayEvents);
            const timeCell = sample ? this.formatTimeColumnDisplay(sample) : String(time).replace(/\s+/g,
                '\n');
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
        el.innerHTML =
            `<i class="bi bi-calendar-week me-1" aria-hidden="true"></i><span class="fw-medium text-dark">${startStr}</span> <span class="text-muted">to</span> <span class="fw-medium text-dark">${endStr}</span>`;
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
        const sameCalDay = st.getFullYear() === en.getFullYear() &&
            st.getMonth() === en.getMonth() &&
            st.getDate() === en.getDate();
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
        return event.type === 'holiday' ||
            ex.type === 'holiday' ||
            String(event.id || '').startsWith('holiday_');
    }

    /**
     * Normalise API / FullCalendar event fields for list view (flat JSON + extendedProps).
     */
    getListEventProps(event) {
        const ex = event.extendedProps || {};
        const gn = event.group_name ?? ex.group_name ?? '';
        const gns = event.group_names ?? ex.group_names;
        const namesArr = Array.isArray(gns) ? gns : (gn ? String(gn).split(',').map(s => s.trim()).filter(Boolean) :
            []);
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
            return [{
                letter: '',
                events: list
            }];
        }

        const letters = list.map((ev, idx) => {
            const p = this.getListEventProps(ev);
            return {
                ev,
                letter: this.inferPdfGroupRowLetter(p, idx)
            };
        });

        const allBlank = letters.every(x => !x.letter);
        if (allBlank && list.length > 1) {
            return list.map((ev, i) => ({
                letter: String.fromCharCode(65 + i),
                events: [ev],
            }));
        }

        const map = new Map();
        letters.forEach(({
            ev,
            letter
        }) => {
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
            const labelHtml = row.letter ?
                `<span class="tt-pdf-group-label" aria-label="Group row ${row.letter}">${row.letter}</span>` :
                '';
            const body = row.events.map((ev) => this.renderSingleListEventCard(ev, {
                suppressGroupBadge: !!row.letter
            })).join('');
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
        const startTime = event.start ? new Date(event.start).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }) : '';
        const endTime = event.end ? new Date(event.end).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }) : '';
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
                    const isScrolledToBottom = Math.abs(this.scrollHeight - this.clientHeight - this
                        .scrollTop) < 5;

                    if (isScrolledToBottom) {
                        this.classList.add('scrolled-bottom');
                    } else {
                        this.classList.remove('scrolled-bottom');
                    }
                });

                // Initial check
                const isScrolledToBottom = Math.abs(cell.scrollHeight - cell.clientHeight - cell
                    .scrollTop) < 5;
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

    try {
        window.calendarManager = new CalendarManager();
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
@endpush