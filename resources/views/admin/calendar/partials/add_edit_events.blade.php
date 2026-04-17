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
        --lbsnaa-blue: #004a93;
        --lbsnaa-blue-dark: #003566;
        --lbsnaa-blue-rgb: 0, 74, 147;
        --event-modal-accent: var(--lbsnaa-blue);
        --event-modal-accent-rgb: var(--lbsnaa-blue-rgb);
        --event-modal-header-gradient: linear-gradient(160deg, #003566 0%, #004a93 42%, #0a5aa8 100%);
        --event-modal-card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --event-modal-card-shadow-hover: 0 0.5rem 1rem rgba(0, 74, 147, 0.12);
        --event-modal-focus-ring: 0 0 0 0.25rem rgba(var(--lbsnaa-blue-rgb), 0.22);
    }

    #eventModal .modal-content {
        border: 0;
        box-shadow: 0 1rem 3rem rgba(0, 36, 70, 0.2);
    }

    #eventModal .modal-header {
        background: var(--event-modal-header-gradient) !important;
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }

    #eventModal .event-modal-emblem {
        width: 36px;
        height: 36px;
        object-fit: contain;
        flex-shrink: 0;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    }

    #eventModal .event-modal-lbsnaa-logo {
        height: 40px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
        flex-shrink: 0;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.15));
    }

    #eventModal .event-modal-brand-lines .event-modal-brand-line-1 {
        font-size: 0.65rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.72);
        font-weight: 600;
    }

    #eventModal .event-modal-brand-lines .event-modal-brand-line-2 {
        font-size: 0.78rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
        max-width: 16rem;
    }

    #eventModal .modal-body {
        background: linear-gradient(
            to bottom,
            rgba(var(--lbsnaa-blue-rgb), 0.05) 0%,
            var(--bs-secondary-bg) 12%,
            var(--bs-body-bg) 42%
        );
        overflow-x: visible !important;
        max-height: min(70vh, 780px);
        overflow-y: auto;
    }

    #eventModal .modal-footer {
        background-color: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color-translucent);
        gap: 0.5rem;
    }

    #eventModal .form-label {
        font-weight: 500;
        color: var(--bs-emphasis-color);
        margin-bottom: 0.375rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    #eventModal .form-label i {
        color: var(--lbsnaa-blue);
        opacity: 0.85;
        font-size: 1rem;
        flex-shrink: 0;
    }

    #eventModal .required::after {
        content: " *";
        color: var(--bs-danger);
        font-weight: 600;
    }

    #eventModal .form-control,
    #eventModal .form-select {
        border-width: 1px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    #eventModal .form-control:focus,
    #eventModal .form-select:focus {
        border-color: var(--event-modal-accent);
        box-shadow: var(--event-modal-focus-ring);
    }

    #eventModal textarea.form-control {
        resize: vertical;
        min-height: 5.5rem;
    }

    #eventModal .helper-text,
    #eventModal .form-text {
        font-size: 0.8125rem;
    }

    #eventModal .event-modal-section-title {
        position: relative;
        padding-bottom: 0.5rem;
    }

    #eventModal .event-modal-section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 2.5rem;
        height: 3px;
        background: var(--lbsnaa-blue);
        border-radius: 2px;
    }

    #eventModal .event-modal-section-heading {
        color: var(--lbsnaa-blue) !important;
    }

    #eventModal .event-modal-section-card > .card-header {
        border-left: 4px solid var(--lbsnaa-blue);
        border-radius: var(--bs-border-radius-xl) 0 0 0;
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

    #eventModal .form-switch .form-check-input:checked {
        background-color: var(--lbsnaa-blue);
        border-color: var(--lbsnaa-blue);
    }

    #eventModal #type_name_container {
        background: linear-gradient(135deg, var(--bs-secondary-bg) 0%, var(--bs-tertiary-bg) 100%);
        border: 2px dashed var(--bs-border-color);
        transition: border-color 0.2s ease, background 0.2s ease;
        min-height: 3.75rem;
    }

    #eventModal #type_name_container:hover {
        border-color: rgba(var(--lbsnaa-blue-rgb), 0.35);
        background: linear-gradient(135deg, var(--bs-body-bg) 0%, rgba(var(--lbsnaa-blue-rgb), 0.06) 100%);
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

    #eventModal .card {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    #eventModal .card:hover {
        box-shadow: var(--event-modal-card-shadow-hover) !important;
    }

    #eventModal input[type="time"],
    #eventModal input[type="date"] {
        cursor: pointer;
    }

    #eventModal .modal-footer .btn {
        min-width: 6.5rem;
    }

    /* Choices.js inside event modal */
    #eventModal .choices {
        width: 100%;
        position: relative;
        z-index: 1;
    }

    #eventModal .choices__inner.form-select {
        min-height: 2.625rem !important;
        background: var(--bs-body-bg) !important;
        padding: 0.375rem 0.75rem !important;
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
        max-height: min(90vh, 920px);
    }

    #eventModal .choices.is-open {
        z-index: 10600;
    }

    #eventModal .choices.is-open .choices__list--dropdown,
    #eventModal .choices.is-open .choices__list[aria-expanded] {
        z-index: 10610 !important;
    }
</style>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <form id="eventForm" novalidate>
            @csrf
            <div class="modal-content rounded-4 overflow-hidden">
                <div class="modal-header text-white px-3 px-md-4 pt-3 pb-3 flex-column align-items-stretch gap-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 gap-md-3 w-100">
                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                            <img src="{{ $calendarEventModalEmblemSrc }}" width="36" height="36" class="event-modal-emblem" alt="National Emblem" loading="lazy">
                            <div class="event-modal-brand-lines text-start lh-sm d-none d-sm-block min-w-0">
                                <div class="event-modal-brand-line-1">Government of India</div>
                                <div class="event-modal-brand-line-2">Lal Bahadur Shastri National Academy of Administration</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <img src="{{ $calendarEventModalLbsnaaLogoSrc }}" class="event-modal-lbsnaa-logo" alt="LBSNAA" loading="lazy">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 w-100 border-top border-white border-opacity-25 pt-2">
                        <h2 class="modal-title h5 mb-0 text-white text-break d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-plus d-sm-none" aria-hidden="true"></i>
                            <span id="eventModalTitle">Add Calendar Event</span>
                        </h2>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <label for="start_datetime" class="form-label text-white mb-0 small fw-semibold text-nowrap mb-0">
                                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Event date
                            </label>
                            <input type="date" name="start_datetime" id="start_datetime"
                                class="form-control form-control-sm bg-white text-dark border-0 shadow-sm w-auto min-w-0"
                                required aria-required="true">
                        </div>
                    </div>
                </div>

                <div class="modal-body px-3 px-md-4 py-3 py-md-4">
                    <section class="mb-3 mb-md-4" aria-labelledby="basicInfoHeading">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="basicInfoHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill fs-5 opacity-75" aria-hidden="true"></i>
                                    Basic Information
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="Course_name" class="form-label required">
                                            <i class="bi bi-book" aria-hidden="true"></i>Course Name
                                        </label>
                                        <select name="Course_name" id="Course_name" class="form-select" required aria-required="true">
                                            <option value="">Select Course</option>
                                            @foreach($courseMaster as $course)
                                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
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

                                    <div class="col-md-6">
                                        <label for="subject_module" class="form-label required">
                                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>Subject Module
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

                                    <div class="col-md-6">
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
                                        <textarea name="topic" id="topic" class="form-control" rows="3"
                                            placeholder="Enter topic details" required aria-required="true"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mb-3 mb-md-4" aria-labelledby="facultyVenueHeading">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="facultyVenueHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge fs-5 opacity-75" aria-hidden="true"></i>
                                    Faculty &amp; Venue
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
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

                                    <div class="col-md-6">
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

                                    <div class="col-md-6">
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
                                    <div class="col-md-6" id="internalFacultyDiv">
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

                    <section class="mb-3 mb-md-4" aria-labelledby="scheduleHeading">
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
                                    <div class="d-flex flex-wrap gap-3 p-3 rounded-3 bg-body-secondary border border-secondary border-opacity-25">
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
                    </section>

                    <section class="mb-0" aria-labelledby="additionalOptionsHeading">
                        <div class="card border-0 shadow-sm rounded-4 event-modal-section-card">
                            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
                                <h3 id="additionalOptionsHeading" class="event-modal-section-title h6 mb-0 event-modal-section-heading fw-semibold d-flex align-items-center gap-2">
                                    <i class="bi bi-sliders fs-5 opacity-75" aria-hidden="true"></i>
                                    Additional Options
                                </h3>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="card h-100 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary bg-opacity-25">
                                            <div class="card-body p-3 p-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="feedback_checkbox"
                                                        name="feedback_checkbox" value="1" aria-controls="feedbackOptions">
                                                    <label class="form-check-label fw-semibold" for="feedback_checkbox">
                                                        Feedback
                                                    </label>
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
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card h-100 border shadow-sm rounded-4 event-modal-bio-card">
                                            <div class="card-body p-3 p-md-4 d-flex align-items-center">
                                                <div class="form-check form-switch mb-0 w-100">
                                                    <input class="form-check-input" type="checkbox" id="bio_attendanceCheckbox"
                                                        name="bio_attendanceCheckbox" value="1">
                                                    <label class="form-check-label fw-semibold" for="bio_attendanceCheckbox">
                                                        Bio Attendance
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal-footer px-3 px-md-4 py-3 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" id="submitEventBtn">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
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
