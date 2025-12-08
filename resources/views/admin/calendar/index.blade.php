

@extends('admin.layouts.master')

@section('title', 'Councillor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">
    <!-- Page Header with ARIA landmark -->
    <header aria-label="Page header">
        <x-breadcrum title="Academic Calendar" />
    </header>

    <!-- Main Content Area -->
    <main id="main-content" role="main">
        <!-- Action Controls with proper semantics -->
        <section class="calendar-controls mb-4" aria-label="Calendar view controls">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <!-- View Toggle Buttons -->
                <div class="btn-group" role="group" aria-label="Calendar view options">
                    <button type="button" 
                            class="btn btn-outline-primary btn-sm active" 
                            id="btnMonthView"
                            aria-pressed="true"
                            data-view="month">
                        <i class="bi bi-calendar-month me-1" aria-hidden="true"></i> Month
                    </button>
                    <button type="button" 
                            class="btn btn-outline-primary btn-sm" 
                            id="btnWeekView"
                            aria-pressed="false"
                            data-view="week">
                        <i class="bi bi-calendar-week me-1" aria-hidden="true"></i> Week
                    </button>
                    <button type="button" 
                            class="btn btn-outline-primary btn-sm" 
                            id="btnDayView"
                            aria-pressed="false"
                            data-view="day">
                        <i class="bi bi-calendar-day me-1" aria-hidden="true"></i> Day
                    </button>
                    <button type="button" 
                            class="btn btn-outline-primary btn-sm" 
                            id="btnListView"
                            aria-pressed="false"
                            data-view="list">
                        <i class="bi bi-list-ul me-1" aria-hidden="true"></i> List
                    </button>
                </div>

                <!-- Action Buttons -->
                @if(hasRole('Training') || hasRole('Admin'))
                <button type="button" 
                        class="btn btn-primary btn-sm" 
                        id="createEventButton"
                        data-bs-toggle="modal" 
                        data-bs-target="#eventModal">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Event
                </button>
                @endif
            </div>
        </section>

        <!-- Calendar Container -->
        <section class="calendar-container" aria-label="Academic calendar">
            <div class="card border-start-4 border-primary shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <!-- FullCalendar -->
                    <div id="calendar" class="fc" role="application" aria-label="Interactive calendar"></div>

                    <!-- List View (Hidden by default) -->
                    <div id="eventListView" class="d-none" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper">
                            <!-- Timetable Header -->
                            <div class="timetable-header bg-white border rounded-3 p-3 mb-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-2 text-center text-md-start">
                                        <img src="{{ asset('images/lbsnaa_logo.jpg') }}" 
                                             alt="Lal Bahadur Shastri National Academy of Administration Logo" 
                                             class="img-fluid institution-logo"
                                             width="80" 
                                             height="80">
                                    </div>
                                    <div class="col-md-8 text-center">
                                        <h1 class="institution-name hindi-text mb-1 text-primary-dark">
                                            लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी
                                        </h1>
                                        <h2 class="institution-name english-text mb-1 text-primary">
                                            Lal Bahadur Shastri National Academy of Administration
                                        </h2>
                                        <p class="text-muted mb-0">Weekly Timetable</p>
                                    </div>
                                    <div class="col-md-2 text-center text-md-end">
                                        <p class="text-muted mb-1">Week</p>
                                        <p class="week-number text-primary fw-bold fs-2 mb-0" id="currentWeek">19</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Timetable -->
                            <div class="timetable-container border rounded-3 overflow-hidden">
                                <div class="table-responsive" role="region" aria-label="Weekly timetable">
                                    <table class="table table-bordered mb-0 timetable-grid" 
                                           id="timetableTable"
                                           aria-describedby="timetableDescription">
                                        <caption class="visually-hidden" id="timetableDescription">
                                            Weekly academic timetable showing events for Monday through Friday
                                        </caption>
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="time-column">Time</th>
                                                <th scope="col">
                                                    Monday<br>
                                                    <small class="text-muted">Nov 30</small>
                                                </th>
                                                <th scope="col">
                                                    Tuesday<br>
                                                    <small class="text-muted">Dec 1</small>
                                                </th>
                                                <th scope="col">
                                                    Wednesday<br>
                                                    <small class="text-muted">Dec 2</small>
                                                </th>
                                                <th scope="col">
                                                    Thursday<br>
                                                    <small class="text-muted">Dec 3</small>
                                                </th>
                                                <th scope="col">
                                                    Friday<br>
                                                    <small class="text-muted">Dec 4</small>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="timetableBody">
                                            <tr>
                                                <td colspan="6" class="text-center p-5">
                                                    <div class="empty-state">
                                                        <i class="bi bi-calendar-x display-5 text-muted mb-3" 
                                                           aria-hidden="true"></i>
                                                        <p class="text-muted mb-3">No events scheduled yet.</p>
                                                        @if(hasRole('Training') || hasRole('Admin'))
                                                        <button type="button" 
                                                                class="btn btn-primary"
                                                                onclick="document.getElementById('createEventButton').click();">
                                                            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                                                            Add Event
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
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

<!-- Add/Edit Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <form id="eventForm" novalidate>
            @csrf
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <div class="d-flex flex-column w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="modal-title h5 mb-0" id="eventModalTitle">Add Calendar Event</h2>
                            <button type="button" 
                                    class="btn-close btn-close-white" 
                                    data-bs-dismiss="modal" 
                                    aria-label="Close"></button>
                        </div>
                        <div class="mt-2 d-flex align-items-center">
                            <label for="start_datetime" class="form-label text-white me-2 mb-0">
                                <i class="bi bi-calendar-date me-1" aria-hidden="true"></i>Date:
                            </label>
                            <input type="date" 
                                   name="start_datetime" 
                                   id="start_datetime" 
                                   class="form-control form-control-sm w-auto" 
                                   required
                                   aria-required="true">
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Basic Information -->
                    <section class="mb-4" aria-labelledby="basicInfoHeading">
                        <h3 id="basicInfoHeading" class="h6 text-primary mb-3">Basic Information</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="Course_name" class="form-label required">
                                    Course Name
                                </label>
                                <select name="Course_name" 
                                        id="Course_name" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Course</option>
                                    @foreach($courseMaster as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="group_type" class="form-label required">
                                    Group Type
                                </label>
                                <select name="group_type" 
                                        id="group_type" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Group Type</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Group Type Name</label>
                                <div id="type_name_container" class="border rounded p-3 bg-light-subtle">
                                    <div class="text-center text-muted" id="groupTypePlaceholder">
                                        Select a Group Type first
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="subject_module" class="form-label required">
                                    Subject Module
                                </label>
                                <select name="subject_module" 
                                        id="subject_module" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Subject Module</option>
                                    @foreach($subjects as $subject)
                                    <option value="{{ $subject->pk }}" data-id="{{ $subject->pk }}">
                                        {{ $subject->module_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="subject_name" class="form-label required">
                                    Subject Name
                                </label>
                                <select name="subject_name" 
                                        id="subject_name" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Subject Name</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="topic" class="form-label required">
                                    Topic
                                </label>
                                <textarea name="topic" 
                                          id="topic" 
                                          class="form-control" 
                                          rows="3" 
                                          placeholder="Enter topic details"
                                          required
                                          aria-required="true"></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Faculty & Venue -->
                    <section class="mb-4" aria-labelledby="facultyVenueHeading">
                        <h3 id="facultyVenueHeading" class="h6 text-primary mb-3">Faculty & Venue</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="faculty" class="form-label required">
                                    Faculty
                                </label>
                                <select name="faculty" 
                                        id="faculty" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Faculty</option>
                                    @foreach($facultyMaster as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="faculty_type" class="form-label required">
                                    Faculty Type
                                </label>
                                <select name="faculty_type" 
                                        id="faculty_type" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Faculty Type</option>
                                    <option value="1">Internal</option>
                                    <option value="2">Guest</option>
                                    <option value="3">Research</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="vanue" class="form-label required">
                                    Location
                                </label>
                                <select name="vanue" 
                                        id="vanue" 
                                        class="form-select" 
                                        required
                                        aria-required="true">
                                    <option value="">Select Location</option>
                                    @foreach($venueMaster as $loc)
                                    <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="mb-4" aria-labelledby="scheduleHeading">
                        <h3 id="scheduleHeading" class="h6 text-primary mb-3">Schedule</h3>
                        
                        <!-- Shift Type -->
                        <div class="mb-3">
                            <label class="form-label d-block required">Shift Type</label>
                            <div class="form-check form-check-inline">
                                <input type="radio" 
                                       name="shift_type" 
                                       id="normalShift" 
                                       value="1" 
                                       class="form-check-input" 
                                       checked
                                       aria-controls="shiftSelect">
                                <label class="form-check-label" for="normalShift">Normal Shift</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" 
                                       name="shift_type" 
                                       id="manualShift" 
                                       value="2" 
                                       class="form-check-input"
                                       aria-controls="manualShiftFields">
                                <label class="form-check-label" for="manualShift">Manual Shift</label>
                            </div>
                        </div>

                        <!-- Normal Shift -->
                        <div id="shiftSelect" class="mb-3">
                            <label for="shift" class="form-label required">Shift</label>
                            <select name="shift" 
                                    id="shift" 
                                    class="form-select" 
                                    required
                                    aria-required="true">
                                <option value="">Select Shift</option>
                                @foreach($classSessionMaster as $shift)
                                <option value="{{ $shift->shift_time }}">
                                    {{ $shift->shift_name }} ({{ $shift->shift_time }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Manual Shift -->
                        <div id="manualShiftFields" class="d-none">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           id="fullDayCheckbox" 
                                           name="fullDayCheckbox"
                                           aria-controls="dateTimeFields">
                                    <label class="form-check-label" for="fullDayCheckbox">
                                        Full Day Event
                                    </label>
                                </div>
                            </div>

                            <div id="dateTimeFields">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_time" class="form-label required">Start Time</label>
                                        <input type="time" 
                                               name="start_time" 
                                               id="start_time" 
                                               class="form-control" 
                                               aria-describedby="startTimeHelp">
                                        <small id="startTimeHelp" class="form-text text-muted">
                                            Must be at least 1 hour from now
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label required">End Time</label>
                                        <input type="time" 
                                               name="end_time" 
                                               id="end_time" 
                                               class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Additional Options -->
                    <section class="pt-3 border-top" aria-labelledby="additionalOptionsHeading">
                        <h3 id="additionalOptionsHeading" class="h6 text-primary mb-3">Additional Options</h3>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           name="feedback_checkbox" 
                                           id="feedback_checkbox"
                                           aria-controls="remarkCheckbox ratingCheckbox">
                                    <label class="form-check-label" for="feedback_checkbox">Feedback</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           name="remarkCheckbox" 
                                           id="remarkCheckbox"
                                           disabled>
                                    <label class="form-check-label" for="remarkCheckbox">Remark</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           name="ratingCheckbox" 
                                           id="ratingCheckbox"
                                           disabled>
                                    <label class="form-check-label" for="ratingCheckbox">Rating</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           value="1" 
                                           name="bio_attendanceCheckbox" 
                                           id="bio_attendanceCheckbox">
                                    <label class="form-check-label" for="bio_attendanceCheckbox">Bio Attendance</label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="btn btn-primary" 
                            id="submitEventBtn">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                        <span class="btn-text">Add Event</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <div class="d-flex flex-column w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="modal-title h5 mb-0" id="eventDetailsTitle">
                            <span id="eventTitle">Event</span>
                        </h3>
                        <button type="button" 
                                class="btn-close btn-close-white" 
                                data-bs-dismiss="modal" 
                                aria-label="Close"></button>
                    </div>
                    <div class="mt-2">
                        <p class="mb-0 small">
                            <i class="bi bi-calendar-date me-1" aria-hidden="true"></i>
                            <span id="eventDate"></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="event-details">
                    <h4 class="h6 mb-3" id="eventTopic"></h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Faculty:</strong>
                                <span id="eventfaculty" class="ms-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-geo-alt-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Venue:</strong>
                                <span id="eventVanue" class="ms-1"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            @if(hasRole('Training') || hasRole('Admin'))
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary" 
                                    id="editEventBtn">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger" 
                                    id="deleteEventBtn">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to delete this event?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS for modern look -->
<style>
:root {
    --primary-color: #004a93;
    --primary-dark: #003366;
    --secondary-color: #af2910;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --success-color: #198754;
    --danger-color: #dc3545;
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
    font-size: 0.875rem;
}

.fc-event-card {
    padding: 0.5rem;
    border-radius: 0.375rem;
    margin: 0.125rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.fc-event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Timetable styling */
.timetable-grid th {
    font-weight: 600;
    color: var(--primary-color);
    background-color: var(--light-bg);
}

.timetable-grid .time-column {
    min-width: 120px;
    font-weight: 600;
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
.form-label.required::after {
    content: " *";
    color: var(--danger-color);
}

.readonly-checkbox {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Modal improvements */
.modal-header {
    padding: 1rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Button states */
.btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.25);
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
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .fc-event-card,
    .btn {
        transition: none;
    }
}
</style>

<!-- Modern JavaScript with improved accessibility -->
<script>
// Configuration object
const CalendarConfig = {
    api: {
        events: '/calendar/full-calendar-details',
        eventDetails: '/calendar/single-calendar-details',
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
    minDate: new Date().toISOString().split('T')[0],
    minTime: '09:00',
    maxTime: '18:00'
};

// Calendar Manager Class
class CalendarManager {
    constructor() {
        this.calendar = null;
        this.currentEventId = null;
        this.selectedGroupNames = 'ALL';
        this.init();
    }

    init() {
        this.initFullCalendar();
        this.bindEvents();
        this.setupAccessibility();
        this.validateDates();
    }

    initFullCalendar() {
        const calendarEl = document.getElementById('calendar');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridDay',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: CalendarConfig.minTime,
            slotMaxTime: CalendarConfig.maxTime,
            editable: true,
            selectable: true,
            dayMaxEvents: true,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            events: CalendarConfig.api.events,
            eventContent: this.renderEventContent.bind(this),
            eventClick: this.handleEventClick.bind(this),
            select: this.handleDateSelect.bind(this),
            eventDidMount: this.setEventAccessibility.bind(this)
        });
        
        this.calendar.render();
    }

    renderEventContent(arg) {
        const colorIdx = arg.event.id ? 
            parseInt(arg.event.id) % CalendarConfig.colors.length : 
            arg.event._index % CalendarConfig.colors.length;
        const cardColor = CalendarConfig.colors[colorIdx];
        
        const topic = arg.event.title || '';
        const venue = arg.event.extendedProps.vanue || '';
        const faculty = arg.event.extendedProps.faculty_name || '';
        
        return {
            html: `
                <div class="fc-event-card" 
                     style="border-left: 4px solid ${cardColor};"
                     tabindex="0"
                     role="button"
                     aria-label="${topic} at ${venue} with ${faculty}">
                    <div class="fw-bold mb-1 text-truncate" style="color: ${cardColor};">
                        ${topic}
                    </div>
                    <div class="small text-muted text-truncate">
                        <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>${venue}
                    </div>
                    <div class="small text-muted text-truncate">
                        <i class="bi bi-person me-1" aria-hidden="true"></i>${faculty}
                    </div>
                </div>
            `
        };
    }

    setEventAccessibility(arg) {
        arg.el.setAttribute('role', 'button');
        arg.el.setAttribute('tabindex', '0');
        arg.el.setAttribute('aria-label', `${arg.event.title} - Click for details`);
    }

    handleEventClick(info) {
        this.currentEventId = info.event.id;
        this.loadEventDetails(info.event.id);
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
            this.showNotification('Error loading event details', 'danger');
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
        if (!@json(hasRole('Training') || hasRole('Admin'))) return;
        
        this.resetEventForm();
        this.setFormDate(info.start);
        
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        modal.show();
    }

    resetEventForm() {
        const form = document.getElementById('eventForm');
        form.reset();
        
        // Reset dynamic fields
        document.getElementById('group_type').innerHTML = '<option value="">Select Group Type</option>';
        document.getElementById('type_name_container').innerHTML = 
            '<div class="text-center text-muted">Select a Group Type first</div>';
        
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
        const formattedDate = date.toISOString().split('T')[0];
        document.getElementById('start_datetime').value = formattedDate;
        document.getElementById('start_datetime').setAttribute('readonly', 'true');
    }

    bindEvents() {
        // View toggle buttons
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleView(e.target));
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
        
        // Set up change handler
        select.onchange = () => this.populateGroupCheckboxes(grouped[select.value] || []);
    }

    populateGroupCheckboxes(groups) {
        const container = document.getElementById('type_name_container');
        
        if (!groups.length) {
            container.innerHTML = '<div class="text-center text-muted">No groups found</div>';
            return;
        }
        
        let html = '<div class="row g-2">';
        
        groups.forEach(group => {
            const checked = this.selectedGroupNames === 'ALL' || 
                           (Array.isArray(this.selectedGroupNames) && 
                            this.selectedGroupNames.includes(group.pk)) ? 'checked' : '';
            
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
        
        switch(facultyType) {
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
        dateInput.setAttribute('min', CalendarConfig.minDate);
        
        // Add real-time validation
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.setCustomValidity('Date cannot be in the past');
                this.reportValidity();
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
            'Course_name', 'subject_module', 'subject_name', 'topic',
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
                this.showNotification('Please select at least Remark or Rating when Feedback is checked', 'warning');
                isValid = false;
            }
        }
        
        return isValid;
    }

    async loadEventForEdit() {
        const eventId = document.getElementById('editEventBtn').dataset.id;
        
        try {
            const response = await fetch(`/calendar/event-edit/${eventId}`);
            const event = await response.json();
            
            this.populateEditForm(event);
            
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

    populateEditForm(event) {
        // Basic fields
        document.getElementById('Course_name').value = event.course_master_pk;
        document.getElementById('subject_module').value = event.subject_module_master_pk;
        document.getElementById('subject_name').value = event.subject_master_pk;
        document.getElementById('topic').value = event.subject_topic;
        document.getElementById('start_datetime').value = event.START_DATE;
        document.getElementById('faculty').value = event.faculty_master;
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
        document.getElementById('feedback_checkbox').checked = event.feedback_checkbox == 1;
        document.getElementById('remarkCheckbox').checked = event.Remark_checkbox == 1;
        document.getElementById('ratingCheckbox').checked = event.Ratting_checkbox == 1;
        document.getElementById('bio_attendanceCheckbox').checked = event.Bio_attendance == 1;
        
        // Trigger dependent loads
        this.loadGroupTypesForEdit(event);
        this.loadSubjectNamesForEdit(event);
        
        // Store current event ID
        this.currentEventId = event.pk;
    }

    loadGroupTypesForEdit(event) {
        // Set selected group names for edit
        try {
            this.selectedGroupNames = JSON.parse(event.group_name || '[]');
        } catch {
            this.selectedGroupNames = [];
        }
        
        // Trigger group type load
        document.getElementById('Course_name').dispatchEvent(new Event('change'));
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

    async loadListView() {
        try {
            const response = await fetch(CalendarConfig.api.events);
            const events = await response.json();
            
            this.renderListView(events);
        } catch (error) {
            console.error('Error loading list view:', error);
        }
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
                        <td>
                            ${dayEvents[day] ? this.renderListEvent(dayEvents[day]) : ''}
                        </td>
                    `).join('')}
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
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

    renderListEvent(event) {
        return `
            <div class="list-event-card p-2 mb-2 border-start border-3" 
                 style="border-color: ${CalendarConfig.colors[event.id % CalendarConfig.colors.length]};">
                <div class="fw-bold small">${event.title}</div>
                <div class="text-muted x-small">
                    <i class="bi bi-person me-1"></i>${event.extendedProps.faculty_name || ''}
                </div>
                <div class="text-muted x-small">
                    <i class="bi bi-geo-alt me-1"></i>${event.extendedProps.vanue || ''}
                </div>
            </div>
        `;
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
                switch(e.key) {
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
                const focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable.length) focusable[0].focus();
            });
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.calendarManager = new CalendarManager();
});

// Add ARIA live region for announcements
const liveRegion = document.createElement('div');
liveRegion.setAttribute('aria-live', 'polite');
liveRegion.setAttribute('aria-atomic', 'true');
liveRegion.className = 'visually-hidden';
document.body.appendChild(liveRegion);
</script>

@endsection