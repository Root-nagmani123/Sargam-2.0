@extends('admin.layouts.master')

@section('title', 'Academic TimeTable - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">
    <!-- Page Header with ARIA landmark -->
    <header aria-label="Page header">
        <x-breadcrum title="Academic TimeTable" />
    </header>

    <!-- Main Content Area -->
    <main id="main-content" role="main">
        <!-- Action Controls with proper semantics -->
        <section class="calendar-controls mb-4" aria-label="Calendar view controls">
            <div class="control-panel d-flex justify-content-between align-items-center flex-wrap gap-3 bg-white p-3 rounded-3 shadow-sm border">
                <!-- View Toggle Buttons -->
                <div class="view-toggle-section d-flex align-items-center gap-3">
                    <span class="text-muted fw-medium small d-none d-md-inline">View:</span>
                    <div class="btn-group" role="group" aria-label="Calendar view options">
                        <button type="button" class="btn btn-outline-primary" id="btnListView" aria-pressed="false"
                            data-view="list">
                            <i class="bi bi-list-ul me-2"></i>List View
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnCalendarView"
                            aria-pressed="false" data-view="calendar">
                            <i class="bi bi-calendar3 me-2"></i>Calendar View
                        </button>
                    </div>
                </div>
                <!-- Action Buttons -->
                @if(hasRole('Training') || hasRole('Admin'))
                <button type="button" class="btn btn-primary px-4" id="createEventButton" data-bs-toggle="modal"
                    data-bs-target="#eventModal">
                    <i class="bi bi-plus-circle me-2" aria-hidden="true"></i> Add New Event
                </button>
                @endif
            </div>
        </section>

        <!-- Calendar Container -->
        <section class="calendar-container" aria-label="Academic calendar">
            <div class="card border-start-4 border-primary shadow-sm">
                <div class="card-body p-3 p-md-4">

                    <!-- FullCalendar placeholder (you may initialize FullCalendar separately) -->
                    <div id="calendar" class="fc mb-4" role="application" aria-label="Interactive calendar"></div>

                    <!-- List View -->
                    <div id="eventListView" class="mt-4" role="region" aria-label="Weekly timetable">
                        <div class="timetable-wrapper">
                            <!-- Timetable Header -->
                            <div class="timetable-header bg-gradient shadow-sm border rounded-4 p-4 mb-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-2 text-center text-md-start">
                                        <div class="logo-wrapper p-2 bg-white rounded-3 shadow-sm d-inline-block">
                                            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo" class="img-fluid"
                                                width="70" height="70">
                                        </div>
                                    </div>

                                    <div class="col-md-6 text-center">
                                        <h1 class="h3 mb-2 fw-bold text-primary">Weekly Timetable</h1>
                                        <p class="text-muted mb-0 fw-medium" id="weekRangeText">
                                            <i class="bi bi-calendar-week me-2"></i>—
                                        </p>
                                    </div>

                                    <div class="col-md-4 text-center text-md-end">
                                        <div class="week-controls bg-white rounded-3 p-3 shadow-sm d-inline-block">
                                            <div class="btn-group mb-2" role="group" aria-label="Week navigation">
                                                <button type="button" class="btn btn-outline-primary"
                                                    id="prevWeekBtn" aria-label="Previous week">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary px-4"
                                                    id="currentWeekBtn" aria-label="Current week">
                                                    <i class="bi bi-calendar-check me-2"></i>Today
                                                </button>
                                                <button type="button" class="btn btn-outline-primary"
                                                    id="nextWeekBtn" aria-label="Next week">
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

                            <!-- Timetable table -->
                            <div class="timetable-container border rounded-3 overflow-hidden">
                                <div class="table-responsive" role="region" aria-label="Weekly timetable">
                                    <table class="table table-bordered" id="timetableTable"
                                        aria-describedby="timetableDescription">
                                        <caption class="visually-hidden" id="timetableDescription">
                                            Weekly academic timetable showing events
                                        </caption>
                                        <thead id="timetableHead">
                                            <!-- JS will populate header -->
                                        </thead>

                                        <tbody id="dynamicTimetable">
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
                            <h2 class="modal-title h5 mb-0 text-white" id="eventModalTitle">Add Calendar Event</h2>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <label for="start_datetime" class="form-label text-white me-2 mb-0">
                                <i class="bi bi-calendar-date me-1" aria-hidden="true"></i>Date:
                            </label>
                            <input type="date" name="start_datetime" id="start_datetime"
                                class="form-control form-control-sm w-auto text-white" required aria-required="true">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>

                        </div>
                        <div class="mt-2 d-flex align-items-center">

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
                                <select name="Course_name" id="Course_name" class="form-select" required
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
                                <select name="group_type" id="group_type" class="form-select" required
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
                                <select name="subject_module" id="subject_module" class="form-select" required
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
                                <select name="subject_name" id="subject_name" class="form-select" required
                                    aria-required="true">
                                    <option value="">Select Subject Name</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="topic" class="form-label required">
                                    Topic
                                </label>
                                <textarea name="topic" id="topic" class="form-control" rows="3"
                                    placeholder="Enter topic details" required aria-required="true"></textarea>
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
                                <select name="faculty" id="faculty" class="form-select" required aria-required="true">
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
                                <select name="faculty_type" id="faculty_type" class="form-select" required
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
                                <select name="vanue" id="vanue" class="form-select" required aria-required="true">
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
                                <input type="radio" name="shift_type" id="normalShift" value="1"
                                    class="form-check-input" checked aria-controls="shiftSelect">
                                <label class="form-check-label" for="normalShift">Normal Shift</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" name="shift_type" id="manualShift" value="2"
                                    class="form-check-input" aria-controls="manualShiftFields">
                                <label class="form-check-label" for="manualShift">Manual Shift</label>
                            </div>
                        </div>

                        <!-- Normal Shift -->
                        <div id="shiftSelect" class="mb-3">
                            <label for="shift" class="form-label required">Shift</label>
                            <select name="shift" id="shift" class="form-select" required aria-required="true">
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
                                    <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox"
                                        name="fullDayCheckbox" aria-controls="dateTimeFields">
                                    <label class="form-check-label" for="fullDayCheckbox">
                                        Full Day Event
                                    </label>
                                </div>
                            </div>

                            <div id="dateTimeFields">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_time" class="form-label required">Start Time</label>
                                        <input type="time" name="start_time" id="start_time" class="form-control"
                                            aria-describedby="startTimeHelp">
                                        <small id="startTimeHelp" class="form-text text-muted">
                                            Must be at least 1 hour from now
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label required">End Time</label>
                                        <input type="time" name="end_time" id="end_time" class="form-control">
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
                                    <input class="form-check-input" type="checkbox" value="1" name="feedback_checkbox"
                                        id="feedback_checkbox" aria-controls="remarkCheckbox ratingCheckbox">
                                    <label class="form-check-label" for="feedback_checkbox">Feedback</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" name="remarkCheckbox"
                                        id="remarkCheckbox" disabled>
                                    <label class="form-check-label" for="remarkCheckbox">Remark</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" name="ratingCheckbox"
                                        id="ratingCheckbox" disabled>
                                    <label class="form-check-label" for="ratingCheckbox">Rating</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        name="bio_attendanceCheckbox" id="bio_attendanceCheckbox">
                                    <label class="form-check-label" for="bio_attendanceCheckbox">Bio Attendance</label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitEventBtn">
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
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
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
                            <div class="d`etail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Session:</strong>
                                <span id="eventclasssession" class="ms-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d`etail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Group name:</strong>
                                <span id="eventgroupname" class="ms-1"></span>
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
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editEventBtn">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteEventBtn">
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
    --primary-light: #e6f0fa;
    --secondary-color: #af2910;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --success-color: #198754;
    --danger-color: #dc3545;
    --text-dark: #1a1a1a;
    --text-muted: #6c757d;
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

.fc-daygrid-day {
    transition: background-color 0.2s ease;
}

.fc-daygrid-day:hover {
    background-color: rgba(0, 74, 147, 0.03);
}

.fc-daygrid-day.fc-day-today {
    background-color: var(--primary-light) !important;
}

.fc-col-header-cell {
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.05), rgba(175, 41, 16, 0.05));
    font-weight: 600;
    padding: 1rem 0.5rem;
}

.fc-event-card {
    padding: 0.5rem;
    border-radius: 0.375rem;
    margin: 0.125rem;
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 3px solid var(--primary-color);
}

.fc-event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Dense mode for days with many events */
.fc-daygrid-day.dense-day .fc-event-card {
    padding: 0.25rem 0.375rem;
    border-radius: 0.25rem;
    box-shadow: none;
}

.fc-daygrid-day.dense-day .fc-event-card .fw-bold {
    font-size: 0.8rem;
}

.fc-daygrid-day.dense-day .fc-event-card .small {
    display: none; /* show only title to keep compact */
}

/* Popover styling for "+ more" */
.fc-popover {
    border-radius: 12px !important;
    box-shadow: var(--shadow) !important;
    border: 1px solid var(--border-color) !important;
    overflow: hidden;
}

.fc-popover .fc-popover-title {
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.05), rgba(175, 41, 16, 0.05));
    font-weight: 600;
}

.fc-popover .fc-popover-body .fc-event-card {
    margin: 0.25rem 0;
    padding: 0.5rem;
    border-left: 3px solid var(--primary-color);
}

/* Ensure default popover events look like cards */
.fc-popover .fc-popover-body .fc-event {
    padding: 0.5rem;
    margin: 0.25rem 0;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    border-left: 3px solid var(--primary-color);
}

/* Event badges within cards */
.fc-event-card .event-badge {
    display: inline-block;
    font-size: 0.7rem;
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    font-weight: 600;
}

/* Optional type-based accents */
.fc-event-card[data-event-type="lecture"] {
    border-left-color: #4e73df;
}

.fc-event-card[data-event-type="exam"] {
    border-left-color: #e74a3b;
}

.fc-event-card[data-event-type="meeting"] {
    border-left-color: #1cc88a;
}

.fc-event-card[data-event-type="workshop"] {
    border-left-color: #f6c23e;
}
/* Improved stacking for multiple events in same day */
.fc-daygrid-day-frame .fc-event-card {
    margin: 0.25rem 0;
    background: #fff;
    box-shadow: var(--shadow-sm);
}

.fc-daygrid-day-frame .fc-event-card .fw-bold {
    font-size: 0.85rem;
}

.fc-daygrid-day-frame .fc-event-card .small {
    font-size: 0.75rem;
}

/* TimeGrid overlapping events */
.fc-timegrid-event .fc-event-main {
    border-left: 3px solid var(--primary-color);
    border-radius: 8px;
    background: #fff;
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
}

/* Timetable styling */
.timetable-grid {
    border-collapse: separate;
    border-spacing: 0;
}

.timetable-grid th {
    font-weight: 600;
    color: var(--primary-color);
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.08), rgba(175, 41, 16, 0.05));
    padding: 1rem 0.75rem;
    border-bottom: 2px solid var(--primary-color);
}

.timetable-grid td {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    vertical-align: top;
    transition: background-color 0.2s ease;
}

.timetable-grid td:hover {
    background-color: rgba(0, 74, 147, 0.02);
}

.timetable-grid .time-column {
    min-width: 120px;
    font-weight: 600;
    color: var(--secondary-color);
    background-color: rgba(175, 41, 16, 0.05);
}

/* List Event Cards */
.list-event-card {
    background: white;
    border-radius: 8px;
    transition: var(--transition);
    border-left: 3px solid var(--primary-color) !important;
}

.list-event-card:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
    color: var(--primary-color);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    background-color: var(--light-bg);
}

/* Button states */
.btn {
    transition: var(--transition);
    font-weight: 500;
}

.btn:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 3px;
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.25);
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
}

.btn-outline-primary {
    border-width: 2px;
    font-weight: 500;
}

.btn-outline-primary:hover {
    background-color: var(--primary-light);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.btn-outline-primary.active,
.btn-outline-primary:active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
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

@keyframes spin {
    to { transform: rotate(360deg); }
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
    background-color: var(--primary-light);
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
        this.listViewWeekOffset = 0; // Track week offset for list view
        this.init();
    }

    init() {
        this.initFullCalendar();
        this.bindEvents();
        this.setupAccessibility();
        this.validateDates();
        this.updateCurrentWeek();
        this.observeMoreLinksChanges();
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
            dayMaxEventRows: 4,
            moreLinkClick: 'popover',
            eventOrder: 'start,title',
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
        this.styleMoreLinks();
        this.applyDenseMode();
    }

    styleMoreLinks() {
        // Style all "+ more" links
        const moreLinks = document.querySelectorAll(
            '.fc-daygrid-day-more-link, .fc-more-link, .fc-daygrid-day-frame a[data-date]');
        moreLinks.forEach(link => {
            if (link.textContent.includes('+')) {
                link.style.fontSize = '1.25rem';
                link.style.fontWeight = '700';
                link.style.color = '#ffffff';
                link.style.backgroundColor = '#004a93';
                link.style.padding = '0.5rem 0.75rem';
                link.style.borderRadius = '0.375rem';
                link.style.display = 'inline-block';
                link.style.textDecoration = 'none';
                link.style.background = 'linear-gradient(135deg, #004a93, #0066cc)';
                link.style.transition = 'all 0.2s ease';

                link.addEventListener('mouseenter', () => {
                    link.style.background = 'linear-gradient(135deg, #003366, #004a93)';
                    link.style.transform = 'scale(1.08)';
                    link.style.boxShadow = '0 4px 12px rgba(0, 74, 147, 0.4)';
                });

                link.addEventListener('mouseleave', () => {
                    link.style.background = 'linear-gradient(135deg, #004a93, #0066cc)';
                    link.style.transform = 'scale(1)';
                    link.style.boxShadow = 'none';
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
        const colorIdx = arg.event.id ?
            parseInt(arg.event.id) % CalendarConfig.colors.length :
            arg.event._index % CalendarConfig.colors.length;
        const cardColor = CalendarConfig.colors[colorIdx];

        const topic = arg.event.title || '';
        const venue = arg.event.extendedProps.vanue || '';
        const faculty = arg.event.extendedProps.faculty_name || '';
        const type = (arg.event.extendedProps.type || arg.event.extendedProps.event_type || arg.event.extendedProps.session_type || '').toString();
        const typeAttr = type.toLowerCase();

        return {
            html: `
                <div class="fc-event-card" 
                     style="border-left: 4px solid ${cardColor};"
                     tabindex="0"
                     role="button"
                     aria-label="${topic} at ${venue} with ${faculty}"
                     ${type ? `data-event-type="${typeAttr}"` : ''}>
                    <div class="fw-bold mb-1 text-truncate" style="color: ${cardColor};">
                        ${topic}
                    </div>
                    ${type ? `<div class="mb-1"><span class="event-badge">${type}</span></div>` : ''}
                    <div class="small text-muted text-truncate d-flex align-items-center">
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>${arg.timeText || ''}
                    </div>
                    <div class="small text-muted text-truncate d-flex align-items-center">
                        <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>${venue}
                    </div>
                    <div class="small text-muted text-truncate d-flex align-items-center">
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
        document.getElementById('eventVanue').textContent = data.venue_name || '';
        document.getElementById('eventclasssession').textContent = data.class_session || '';
        document.getElementById('eventgroupname').textContent = data.group_name || '';

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
            const response = await fetch(CalendarConfig.api.events);
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