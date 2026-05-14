<!-- Add/Edit Event Modal -->
@php
    /** LBSNAA branding (override from parent: @include(..., ['calendarEventModalEmblemSrc' => ...]) ) */
    $calendarEventModalEmblemSrc = $calendarEventModalEmblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $calendarEventModalLbsnaaLogoSrc = $calendarEventModalLbsnaaLogoSrc ?? (
        is_file(public_path('images/lbsnaa_logo.jpg'))
            ? asset('images/lbsnaa_logo.jpg')
            : 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png'
    );
@endphp
<style>
    #eventModal {
        --lbsnaa-blue: #00539b;
        --lbsnaa-blue-dark: #003f78;
        --lbsnaa-blue-rgb: 0, 83, 155;
        --event-modal-accent: var(--lbsnaa-blue);
        --event-modal-focus-ring: 0 0 0 0.2rem rgba(var(--lbsnaa-blue-rgb), 0.16);
    }

    #eventModal .calendar-event-dialog {
        max-width: 360px;
    }

    #eventModal .modal-content {
        border: 0;
        border-radius: 0.75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.22);
    }

    #eventModal .modal-header {
        background: var(--bs-body-bg) !important;
        border-bottom: 1px solid var(--bs-border-color);
        padding: 1rem 1rem 0.75rem;
    }

    #eventModal .modal-title {
        color: var(--bs-emphasis-color) !important;
        font-size: 1rem;
        font-weight: 600;
    }

    #eventModal .btn-close {
        --bs-btn-close-opacity: 1;
        width: 0.75rem;
        height: 0.75rem;
        padding: 0.25rem;
    }

    #eventModal .modal-body {
        background: var(--bs-body-bg);
        overflow-x: visible !important;
        max-height: min(78vh, 760px);
        overflow-y: auto;
        padding: 0.75rem 1rem 1rem;
    }

    #eventModal .modal-footer {
        background-color: var(--bs-body-bg);
        border-top: 0;
        gap: 0.5rem;
        padding: 0.75rem 1rem 1rem;
    }

    #eventModal .event-step-progress {
        height: 0.25rem;
        background-color: var(--bs-tertiary-bg);
        border-radius: var(--bs-border-radius-pill);
    }

    #eventModal .event-step-progress .progress-bar {
        background-color: var(--lbsnaa-blue);
        border-radius: var(--bs-border-radius-pill);
        transition: width 0.2s ease;
    }

    #eventModal .event-step-percent {
        font-size: 0.625rem;
        line-height: 1;
        min-width: 1.75rem;
        text-align: right;
    }

    #eventModal .form-label {
        font-weight: 500;
        color: var(--bs-emphasis-color);
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.6875rem;
    }

    #eventModal .form-label i {
        display: none;
    }

    #eventModal .required::after {
        content: " *";
        color: var(--bs-danger);
        font-weight: 600;
    }

    #eventModal .form-control,
    #eventModal .form-select {
        border-width: 1px;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        min-height: 2rem;
        padding: 0.45rem 0.65rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    #eventModal .form-control:focus,
    #eventModal .form-select:focus {
        border-color: var(--event-modal-accent);
        box-shadow: var(--event-modal-focus-ring);
    }

    #eventModal textarea.form-control {
        resize: vertical;
        min-height: 2rem;
    }

    #eventModal .helper-text,
    #eventModal .form-text {
        font-size: 0.8125rem;
    }

    #eventModal .event-modal-section-title {
        position: relative;
        padding-bottom: 0.55rem;
        border-bottom: 1px solid var(--bs-border-color);
        color: var(--bs-emphasis-color) !important;
        font-size: 0.75rem;
        font-weight: 600;
    }

    #eventModal .event-modal-section-title::after {
        display: none;
    }

    #eventModal .event-modal-section-heading {
        color: var(--lbsnaa-blue) !important;
    }

    #eventModal .event-modal-section-card {
        box-shadow: none !important;
        border: 0 !important;
        border-radius: 0 !important;
    }

    #eventModal .event-modal-section-card > .card-header {
        border: 0 !important;
        padding: 0 0 0.75rem !important;
    }

    #eventModal .event-modal-section-card > .card-body {
        padding: 0 !important;
    }

    #eventModal .btn-primary {
        --bs-btn-bg: var(--lbsnaa-blue);
        --bs-btn-border-color: var(--lbsnaa-blue);
        --bs-btn-hover-bg: var(--lbsnaa-blue-dark);
        --bs-btn-hover-border-color: var(--lbsnaa-blue-dark);
        --bs-btn-active-bg: var(--lbsnaa-blue-dark);
        --bs-btn-active-border-color: #002a4d;
        --bs-btn-focus-shadow-rgb: var(--lbsnaa-blue-rgb);
    }

    #eventModal .form-check-input:focus {
        border-color: var(--lbsnaa-blue);
        box-shadow: 0 0 0 0.2rem rgba(var(--lbsnaa-blue-rgb), 0.25);
    }

    #eventModal .form-check-input:checked {
        background-color: var(--lbsnaa-blue);
        border-color: var(--lbsnaa-blue);
    }

    #eventModal .form-check-input {
        width: 0.875rem;
        height: 0.875rem;
        margin-top: 0.15rem;
    }

    #eventModal .form-switch .form-check-input {
        width: 2rem;
        height: 1.25rem;
        border: 0;
        background-color: #c6c6c6;
    }

    #eventModal .form-switch .form-check-input:checked {
        background-color: #6947f5;
    }

    #eventModal #type_name_container {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem !important;
        font-size: 0.75rem;
        min-height: 2rem;
        padding: 0.45rem 0.65rem !important;
        transition: border-color 0.2s ease, background 0.2s ease;
    }

    #eventModal #type_name_container:hover {
        border-color: rgba(var(--lbsnaa-blue-rgb), 0.45);
        background: var(--bs-body-bg);
    }

    #eventModal #type_name_container.border-danger {
        border-color: var(--bs-danger) !important;
        border-style: solid !important;
    }

    #eventModal .event-modal-accent-border {
        border-color: rgba(var(--lbsnaa-blue-rgb), 0.35) !important;
    }

    #eventModal .event-modal-bio-card {
        border-color: rgba(var(--lbsnaa-blue-rgb), 0.35) !important;
        background-color: rgba(var(--lbsnaa-blue-rgb), 0.08) !important;
    }

    #eventModal .form-check-input {
        cursor: pointer;
    }

    #eventModal .form-check-label {
        cursor: pointer;
        user-select: none;
    }

    #eventModal input[type="time"],
    #eventModal input[type="date"] {
        cursor: pointer;
    }

    #eventModal .modal-footer .btn {
        min-width: 4.125rem;
        border-radius: 0.375rem !important;
        font-size: 0.75rem;
        padding: 0.6rem 0.95rem;
        font-weight: 500;
    }

    /* Choices.js inside event modal */
    #eventModal .choices {
        width: 100%;
        position: relative;
        z-index: 1;
    }

    #eventModal .choices__inner.form-select {
        min-height: 2rem !important;
        background: var(--bs-body-bg) !important;
        padding: 0.25rem 0.65rem !important;
        font-size: 0.75rem;
    }

    #eventModal .choices.is-focused .choices__inner.form-select,
    #eventModal .choices.is-open .choices__inner.form-select {
        border-color: var(--event-modal-accent) !important;
        box-shadow: var(--event-modal-focus-ring) !important;
    }

    #eventModal .choices__list--multiple .choices__item {
        background: linear-gradient(135deg, #004a93 0%, #003566 100%) !important;
        border: none !important;
        border-radius: var(--bs-border-radius) !important;
        color: #fff !important;
        font-size: 0.875rem !important;
    }

    #eventModal .choices__list--dropdown,
    #eventModal .choices__list[aria-expanded] {
        border: 1px solid var(--bs-border-color) !important;
        border-radius: var(--bs-border-radius-lg) !important;
        box-shadow: var(--bs-box-shadow) !important;
        z-index: 2000 !important;
    }

    #eventModal .modal-dialog.modal-dialog-scrollable .modal-content {
        overflow: visible !important;
        max-height: min(96vh, 920px);
    }

    #eventModal .choices.is-open {
        z-index: 10600;
    }

    #eventModal .choices.is-open .choices__list--dropdown,
    #eventModal .choices.is-open .choices__list[aria-expanded] {
        z-index: 10610 !important;
    }

    #eventModal .event-options-row {
        min-height: 1.85rem;
    }

    #eventModal .event-options-row .form-check-input {
        float: none;
        margin-left: 0;
        flex-shrink: 0;
    }

    #eventModal .event-step-actions [data-event-step-prev].d-none,
    #eventModal .event-step-actions [data-event-step-next].d-none,
    #eventModal .event-step-actions #submitEventBtn.d-none {
        display: none !important;
    }

    @media (max-width: 575.98px) {
        #eventModal .calendar-event-dialog {
            max-width: none;
            margin: 0.5rem;
        }
    }
</style>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered calendar-event-dialog">
        <form id="eventForm" novalidate>
            @csrf
            <div class="modal-content overflow-hidden">
                <div class="modal-header align-items-center justify-content-between">
                    <h2 class="modal-title mb-0 text-break">
                        <span id="eventModalTitle">Add Event</span>
                    </h2>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-center gap-2 mb-3" aria-hidden="true">
                        <div class="progress event-step-progress flex-grow-1">
                            <div class="progress-bar" role="progressbar" style="width: 30%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="event-step-percent text-body-secondary" data-event-step-percent>30%</span>
                    </div>

                    <section class="event-modal-step mb-0" aria-labelledby="basicInfoHeading" data-event-step="1">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="basicInfoHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill fs-5 opacity-75" aria-hidden="true"></i>
                                    Basic Information
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="start_datetime" class="form-label required">
                                            <i class="bi bi-calendar3" aria-hidden="true"></i>Date
                                        </label>
                                        <input type="date" name="start_datetime" id="start_datetime"
                                            class="form-control" required aria-required="true">
                                    </div>

                                    <div class="col-12">
                                        <label for="Course_name" class="form-label required">
                                            <i class="bi bi-book" aria-hidden="true"></i>Course Name
                                        </label>
                                        <select name="Course_name" id="Course_name" class="form-select" required aria-required="true">
                                            <option value="">Select Course Name</option>
                                            @foreach($courseMaster as $course)
                                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="group_type" class="form-label required">
                                            <i class="bi bi-people" aria-hidden="true"></i>Group Type
                                        </label>
                                        <select name="group_type" id="group_type" class="form-select" required aria-required="true">
                                            <option value="">Select Group Type</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">
                                            <i class="bi bi-tag" aria-hidden="true"></i>Group Type Name
                                        </label>
                                        <div id="type_name_container" class="rounded-3 p-3">
                                            <div class="text-center text-body-secondary small user-select-none" id="groupTypePlaceholder">
                                                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>Select a Group Type first
                                            </div>
                                        </div>
                                        <div class="invalid-feedback" id="type_names_error" style="display: none;">
                                            Please select at least one Group Type Name.
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="subject_module" class="form-label required">
                                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>Module Name
                                        </label>
                                        <select name="subject_module" id="subject_module" class="form-select" required aria-required="true">
                                            <option value="">Select Subject Module</option>
                                            @foreach($subjects as $subject)
                                            <option value="{{ $subject->pk }}" data-id="{{ $subject->pk }}">
                                                {{ $subject->module_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="subject_name" class="form-label required">
                                            <i class="bi bi-journal-text" aria-hidden="true"></i>Subject Name
                                        </label>
                                        <select name="subject_name" id="subject_name" class="form-select" required aria-required="true">
                                            <option value="">Select Subject Name</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="topic" class="form-label required">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>Topic
                                        </label>
                                        <textarea name="topic" id="topic" class="form-control" rows="1"
                                            placeholder="eg. Lorem ipsum dolor sit amet" required aria-required="true"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="event-modal-step mb-0 d-none" aria-labelledby="facultyVenueHeading" data-event-step="2">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="facultyVenueHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge fs-5 opacity-75" aria-hidden="true"></i>
                                    Faculty &amp; Venue
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="faculty" class="form-label required">
                                            <i class="bi bi-person-circle" aria-hidden="true"></i>Faculty
                                        </label>
                                        <select name="faculty[]" id="faculty" class="form-select" required aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="faculty_type" class="form-label required">
                                            <i class="bi bi-diagram-3" aria-hidden="true"></i>Faculty Type
                                        </label>
                                        <select name="faculty_type" id="faculty_type" class="form-select" required aria-required="true">
                                            <option value="">Select Faculty Type</option>
                                            <option value="1">Internal</option>
                                            <option value="2">Guest</option>
                                            <option value="3">Research</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="vanue" class="form-label required">
                                            <i class="bi bi-geo-alt" aria-hidden="true"></i>Location
                                        </label>
                                        <select name="vanue" id="vanue" class="form-select" required aria-required="true">
                                            <option value="">Select Location</option>
                                            @foreach($venueMaster as $loc)
                                            <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12" id="internalFacultyDiv">
                                        <label for="internal_faculty" class="form-label required">
                                            <i class="bi bi-person-check" aria-hidden="true"></i>Internal Faculty
                                        </label>
                                        <select name="internal_faculty[]" id="internal_faculty" class="form-select" required
                                            aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="event-modal-step mb-0 d-none" aria-labelledby="scheduleHeading" data-event-step="3">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="scheduleHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-clock-history fs-5 opacity-75" aria-hidden="true"></i>
                                    Schedule
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="mb-3">
                                    <label class="form-label d-block required">
                                        <i class="bi bi-toggle-on" aria-hidden="true"></i>Shift Type
                                    </label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check m-0">
                                            <input type="radio" name="shift_type" id="normalShift" value="1"
                                                class="form-check-input" checked aria-controls="shiftSelect">
                                            <label class="form-check-label" for="normalShift">Normal Shift</label>
                                        </div>
                                        <div class="form-check m-0">
                                            <input type="radio" name="shift_type" id="manualShift" value="2"
                                                class="form-check-input" aria-controls="manualShiftFields">
                                            <label class="form-check-label" for="manualShift">Manual Shift</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="shiftSelect" class="mb-0">
                                    <label for="shift" class="form-label required">
                                        <i class="bi bi-calendar-range" aria-hidden="true"></i>Shift
                                    </label>
                                    <select name="shift" id="shift" class="form-select" required aria-required="true">
                                        <option value="">Select Shift</option>
                                        @foreach($classSessionMaster as $shift)
                                        <option value="{{ $shift->shift_time }}">
                                            {{ $shift->shift_name }} ({{ $shift->shift_time }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="manualShiftFields" class="d-none mt-3 pt-3 border-top">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox"
                                                name="fullDayCheckbox" aria-controls="dateTimeFields">
                                            <label class="form-check-label fw-medium" for="fullDayCheckbox">
                                                Full Day Event
                                            </label>
                                        </div>
                                    </div>

                                    <div id="dateTimeFields">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="start_time" class="form-label required">
                                                    <i class="bi bi-clock" aria-hidden="true"></i>Start Time
                                                </label>
                                                <input type="time" name="start_time" id="start_time" class="form-control"
                                                    aria-describedby="startTimeHelp">
                                                <div id="startTimeHelp" class="form-text">
                                                    Must be at least 1 hour from now
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="end_time" class="form-label required">
                                                    <i class="bi bi-clock-fill" aria-hidden="true"></i>End Time
                                                </label>
                                                <input type="time" name="end_time" id="end_time" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2" aria-labelledby="additionalOptionsHeading">
                            <h3 id="additionalOptionsHeading" class="event-modal-section-title h6 mb-3 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                <i class="bi bi-sliders fs-5 opacity-75" aria-hidden="true"></i>
                                Additional Options
                            </h3>
                            <div class="d-grid gap-2">
                                <div>
                                                <div class="form-check form-switch event-options-row d-flex align-items-center justify-content-between gap-3 ps-0 mb-2">
                                                    <label class="form-check-label fw-medium" for="feedback_checkbox">
                                                        Feedback
                                                    </label>
                                                    <input class="form-check-input" type="checkbox" id="feedback_checkbox"
                                                        name="feedback_checkbox" value="1" aria-controls="feedbackOptions">
                                                </div>

                                                <div id="feedbackOptions" class="ms-1 ps-3 border-start border-2 event-modal-accent-border d-none">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="remarkCheckbox"
                                                            name="remarkCheckbox" value="1">
                                                        <label class="form-check-label" for="remarkCheckbox">
                                                            Remark
                                                        </label>
                                                    </div>

                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="ratingCheckbox"
                                                            name="ratingCheckbox" value="1">
                                                        <label class="form-check-label" for="ratingCheckbox">
                                                            Rating
                                                        </label>
                                                    </div>

                                                    <div class="form-text mt-2 mb-0">
                                                        Select at least one feedback component.
                                                    </div>
                                                </div>
                                </div>

                                <div class="form-check form-switch event-options-row d-flex align-items-center justify-content-between gap-3 ps-0">
                                                    <label class="form-check-label fw-medium" for="bio_attendanceCheckbox">
                                                        Bio Attendance
                                                    </label>
                                                    <input class="form-check-input" type="checkbox" id="bio_attendanceCheckbox"
                                                        name="bio_attendanceCheckbox" value="1">
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal-footer event-step-actions flex-wrap justify-content-end">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-outline-primary d-none" data-event-step-prev>
                        Back
                    </button>
                    <button type="button" class="btn btn-primary" data-event-step-next>
                        Next
                    </button>
                    <button type="submit" class="btn btn-primary d-none" id="submitEventBtn">
                        <span class="btn-text">Add Event</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackToggle = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    const remark = document.getElementById('remarkCheckbox');
    const rating = document.getElementById('ratingCheckbox');
    const faculty_review_rating = document.getElementById('facultyReviewRatingDiv');
    const eventModal = document.getElementById('eventModal');
    const eventSteps = Array.from(document.querySelectorAll('#eventModal [data-event-step]'));
    const eventStepProgress = document.querySelector('#eventModal .event-step-progress .progress-bar');
    const eventStepPercent = document.querySelector('#eventModal [data-event-step-percent]');
    const eventStepNext = document.querySelector('#eventModal [data-event-step-next]');
    const eventStepPrev = document.querySelector('#eventModal [data-event-step-prev]');
    const eventSubmitBtn = document.getElementById('submitEventBtn');
    let activeEventStep = 1;

    function setEventStep(step) {
        activeEventStep = Math.min(Math.max(step, 1), eventSteps.length || 1);
        const progressValues = {
            1: 30,
            2: 60,
            3: 100
        };
        const progress = progressValues[activeEventStep] || 100;

        eventSteps.forEach((section) => {
            const isActive = Number(section.dataset.eventStep) === activeEventStep;
            section.classList.toggle('d-none', !isActive);
        });

        if (eventStepProgress) {
            eventStepProgress.style.width = `${progress}%`;
            eventStepProgress.setAttribute('aria-valuenow', String(progress));
        }
        if (eventStepPercent) {
            eventStepPercent.textContent = `${progress}%`;
        }
        if (eventStepPrev) {
            eventStepPrev.classList.toggle('d-none', activeEventStep === 1);
        }
        if (eventStepNext) {
            eventStepNext.classList.toggle('d-none', activeEventStep === eventSteps.length);
        }
        if (eventSubmitBtn) {
            eventSubmitBtn.classList.toggle('d-none', activeEventStep !== eventSteps.length);
        }

        eventModal?.querySelector('.modal-body')?.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    eventStepNext?.addEventListener('click', function() {
        setEventStep(activeEventStep + 1);
    });

    eventStepPrev?.addEventListener('click', function() {
        setEventStep(activeEventStep - 1);
    });

    eventModal?.addEventListener('show.bs.modal', function() {
        setEventStep(1);
    });

    window.calendarEventModalStepper = {
        reset: function() {
            setEventStep(1);
        },
        goTo: setEventStep
    };

    feedbackToggle.addEventListener('change', function() {
        if (this.checked) {
            feedbackOptions.classList.remove('d-none');
            // if (internalFacultyDiv.style.display === 'block') {
            //     faculty_review_rating.classList.remove('d-none');
            // } else {
            //     faculty_review_rating.classList.add('d-none');
            // }

        } else {
            feedbackOptions.classList.add('d-none');
            remark.checked = false;
            rating.checked = false;
        }
    });
    const internalFacultyDiv = document.getElementById('internalFacultyDiv');
    const facultySelect = document.getElementById('faculty');
    const faculty_type = document.getElementById('faculty_type');
    // internalFacultyDiv.style.display = 'none'; // Hide initially

    function initChoicesForSelect(el, placeholderText) {
        if (!el || typeof window.Choices === 'undefined') return null;
        if (el._choicesInstance) return el._choicesInstance;
        const isMultiple = !!el.multiple;

        const instance = new Choices(el, {
            removeItemButton: isMultiple,
            shouldSort: false,
            searchEnabled: true,
            searchPlaceholderValue: 'Search...',
            placeholder: true,
            placeholderValue: placeholderText,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerInner: ['choices__inner', 'form-select'],
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
        });

        el._choicesInstance = instance;
        return instance;
    }

    function getPlaceholderText(el) {
        if (!el) return 'Select option';
        const label = document.querySelector(`label[for="${el.id}"]`);
        const labelText = label ? label.textContent.replace(/\s+/g, ' ').trim() : 'Select option';
        return labelText || 'Select option';
    }

    function syncChoicesFromSelected(el) {
        if (!el || !el._choicesInstance) return;
        const values = Array.from(el.selectedOptions).map(option => option.value);
        el._choicesInstance.removeActiveItems();
        if (values.length) {
            el._choicesInstance.setChoiceByValue(values);
        }
    }

    function rebuildChoicesForSelect(el) {
        if (!el || typeof window.Choices === 'undefined') return;
        const selectedValues = Array.from(el.selectedOptions).map(option => option.value);
        if (el._choicesInstance) {
            el._choicesInstance.destroy();
            el._choicesInstance = null;
        }
        initChoicesForSelect(el, getPlaceholderText(el));
        if (selectedValues.length && el._choicesInstance) {
            el._choicesInstance.setChoiceByValue(selectedValues);
        }
    }

    function initAllModalChoices() {
        const selects = document.querySelectorAll('#eventModal select.form-control, #eventModal select.form-select');
        selects.forEach((el) => {
            initChoicesForSelect(el, getPlaceholderText(el));
            syncChoicesFromSelected(el);
        });
    }

    function destroyAllModalChoices() {
        const selects = document.querySelectorAll('#eventModal select.form-control, #eventModal select.form-select');
        selects.forEach((el) => {
            if (el._choicesInstance) {
                el._choicesInstance.destroy();
                el._choicesInstance = null;
            }
        });
    }

    // Expose helpers for dynamic dropdown updates from calendar page scripts
    window.calendarModalChoices = {
        init: initAllModalChoices,
        destroy: destroyAllModalChoices,
        syncById: function(id) {
            const el = document.getElementById(id);
            if (el) syncChoicesFromSelected(el);
        },
        rebuildById: function(id) {
            const el = document.getElementById(id);
            if (el) rebuildChoicesForSelect(el);
        }
    };

    // Initialize Choices.js when modal is shown
    $('#eventModal').on('shown.bs.modal', function() {
        initAllModalChoices();
    });

    // Destroy Choices when modal is hidden to prevent conflicts
    $('#eventModal').on('hidden.bs.modal', function() {
        destroyAllModalChoices();
    });

    // Show/hide internal faculty based on faculty_type dropdown
    faculty_type.addEventListener('change', function() {
        const facultyType = this.value;
        updateinternal_faculty_data(facultyType);
    });

    function updateinternal_faculty_data(facultyType) {
        switch (facultyType) {
            case '1': // Internal
            case 1:
                // internalFacultyDiv.style.display = 'none';
                break;
            case '2': // Guest
            case 2:
                internalFacultyDiv.style.display = 'block';
                break;
            default:
                // internalFacultyDiv.style.display = 'none';
        }
    }
});
</script>
