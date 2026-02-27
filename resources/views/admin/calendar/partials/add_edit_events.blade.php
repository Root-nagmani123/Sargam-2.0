<!-- Add/Edit Event Modal -->
 <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #af2910, #ff7e5f);
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --input-focus-glow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .modal-body {
        background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    }

    .form-section {
        background: #ffffff;
        border-radius: 1rem;
        border: 1px solid #e9ecef;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .form-section:hover {
        box-shadow: var(--card-shadow);
        border-color: #dee2e6;
    }

    .form-section-header {
        font-weight: 600;
        color: #0d6efd;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .required::after {
        content: " *";
        color: #dc3545;
        font-weight: 600;
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .form-label i {
        color: #6c757d;
        font-size: 0.9em;
    }

    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 0.625rem 0.875rem;
        transition: all 0.2s ease;
        font-size: 0.95rem;
    }

    .form-control:hover,
    .form-select:hover {
        border-color: #ced4da;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: var(--input-focus-glow);
        transform: translateY(-1px);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }

    .helper-text {
        font-size: 0.825rem;
        color: #6c757d;
    }

    section h3 {
        position: relative;
        padding-bottom: 0.75rem;
        margin-bottom: 1.5rem !important;
    }

    section h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: var(--primary-gradient);
        border-radius: 2px;
    }

    #type_name_container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px dashed #ced4da;
        border-radius: 0.75rem;
        padding: 1.25rem;
        transition: all 0.3s ease;
        min-height: 60px;
    }

    #type_name_container:hover {
        border-color: #adb5bd;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }

    .form-check-input {
        cursor: pointer;
        width: 1.125em;
        height: 1.125em;
        border: 2px solid #dee2e6;
        transition: all 0.2s ease;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .form-check-label {
        cursor: pointer;
        user-select: none;
        color: #495057;
        font-weight: 400;
    }

    .form-check:hover .form-check-label {
        color: #212529;
    }

    .card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e9ecef;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-shadow-hover);
    }

    input[type="time"],
    input[type="date"] {
        cursor: pointer;
    }

    .btn {
        font-weight: 500;
        padding: 0.625rem 1.25rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn:active {
        transform: translateY(0);
    }

    .modal-header {
        background: var(--primary-gradient) !important;
        border-radius: 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    section {
        animation: fadeInUp 0.4s ease-out;
    }

    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
        cursor: pointer;
    }

    .form-switch .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }

    /* Modern Select2 Styling - Enhanced Specificity */
    #eventModal .select2-container--default .select2-selection--multiple,
    #eventModal .select2-container--default .select2-selection--single,
    .modal-dialog .select2-container--default .select2-selection--multiple,
    .modal-dialog .select2-container--default .select2-selection--single {
        border: 2px solid #e9ecef !important;
        border-radius: 0.5rem !important;
        min-height: 42px !important;
        transition: all 0.2s ease !important;
        background-color: #ffffff !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple,
    .modal-dialog .select2-container--default .select2-selection--multiple {
        padding: 0.375rem 0.5rem !important;
    }

    #eventModal .select2-container--default .select2-selection--single,
    .modal-dialog .select2-container--default .select2-selection--single {
        padding: 0.625rem 0.875rem !important;
    }

    #eventModal .select2-container--default:hover .select2-selection--multiple,
    #eventModal .select2-container--default:hover .select2-selection--single,
    .modal-dialog .select2-container--default:hover .select2-selection--multiple,
    .modal-dialog .select2-container--default:hover .select2-selection--single {
        border-color: #ced4da !important;
    }

    #eventModal .select2-container--default.select2-container--focus .select2-selection--multiple,
    #eventModal .select2-container--default.select2-container--focus .select2-selection--single,
    #eventModal .select2-container--default.select2-container--open .select2-selection--multiple,
    #eventModal .select2-container--default.select2-container--open .select2-selection--single,
    .modal-dialog .select2-container--default.select2-container--focus .select2-selection--multiple,
    .modal-dialog .select2-container--default.select2-container--focus .select2-selection--single,
    .modal-dialog .select2-container--default.select2-container--open .select2-selection--multiple,
    .modal-dialog .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0d6efd !important;
        box-shadow: var(--input-focus-glow) !important;
        transform: translateY(-1px) !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
        border: none !important;
        border-radius: 0.375rem !important;
        padding: 0.25rem 0.625rem !important;
        color: #ffffff !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        margin: 0.25rem 0.25rem 0.25rem 0 !important;
        transition: all 0.2s ease !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice:hover,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice:hover {
        background: linear-gradient(135deg, #0a58ca 0%, #084298 100%) !important;
        transform: scale(1.02) !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #ffffff !important;
        font-size: 1.1em !important;
        margin-right: 0.375rem !important;
        font-weight: bold !important;
        transition: all 0.2s ease !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffebee !important;
        transform: scale(1.15) !important;
    }

    .select2-dropdown {
        border: 2px solid #e9ecef !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        margin-top: 0.25rem !important;
        animation: select2FadeIn 0.2s ease-out;
    }

    @keyframes select2FadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .select2-container--default .select2-results__option {
        padding: 0.625rem 0.875rem !important;
        font-size: 0.95rem !important;
        transition: all 0.15s ease !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
        color: #ffffff !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #e7f3ff !important;
        color: #e7f3ff !important;
        font-weight: 500 !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 2px solid #e9ecef !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.95rem !important;
        transition: all 0.2s ease !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important;
        outline: none !important;
    }

    #eventModal .select2-container--default .select2-selection--single .select2-selection__rendered,
    .modal-dialog .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057 !important;
        line-height: normal !important;
        padding: 0 !important;
    }

    #eventModal .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .modal-dialog .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important;
    }

    #eventModal .select2-container--default .select2-selection--single .select2-selection__arrow,
    .modal-dialog .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 0.5rem !important;
    }

    #eventModal .select2-container--default .select2-selection--single .select2-selection__arrow b,
    .modal-dialog .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #6c757d transparent transparent transparent !important;
        transition: all 0.2s ease !important;
    }

    #eventModal .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
    .modal-dialog .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #0d6efd transparent !important;
    }

    .select2-results__message {
        color: #6c757d !important;
        font-style: italic !important;
        padding: 0.75rem !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-search__field,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-search__field {
        margin-top: 0.25rem !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-search__field::placeholder,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-search__field::placeholder {
        color: #6c757d !important;
        font-size: 0.95rem !important;
    }

    /* Ensure Select2 width is 100% */
    #eventModal .select2-container,
    .modal-dialog .select2-container {
        width: 100% !important;
    }
</style>

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
                    <section class="mb-4 p-4 bg-white rounded-3 shadow-sm" aria-labelledby="basicInfoHeading">
                        <h3 id="basicInfoHeading" class="h6 text-primary mb-3 fw-semibold">
                            <i class="bi bi-info-circle-fill me-2"></i>Basic Information
                        </h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="Course_name" class="form-label required">
                                    <i class="bi bi-book"></i>Course Name
                                </label>
                                <select name="Course_name" id="Course_name" class="form-control" required
                                    aria-required="true">
                                    <option value="">Select Course</option>
                                    @foreach($courseMaster as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="group_type" class="form-label required">
                                    <i class="bi bi-people"></i>Group Type
                                </label>
                                <select name="group_type" id="group_type" class="form-control" required
                                    aria-required="true">
                                    <option value="">Select Group Type</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label required">
                                    <i class="bi bi-tag"></i>Group Type Name
                                </label>
                                <div id="type_name_container" class="border rounded p-3 bg-light-subtle">
                                    <div class="text-center text-muted" id="groupTypePlaceholder">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Select a Group Type first
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="subject_module" class="form-label required">
                                    <i class="bi bi-grid-3x3"></i>Subject Module
                                </label>
                                <select name="subject_module" id="subject_module" class="form-control" required
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
                                    <i class="bi bi-journal-text"></i>Subject Name
                                </label>
                                <select name="subject_name" id="subject_name" class="form-control" required
                                    aria-required="true">
                                    <option value="">Select Subject Name</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="topic" class="form-label required">
                                    <i class="bi bi-pencil-square"></i>Topic
                                </label>
                                <textarea name="topic" id="topic" class="form-control" rows="3"
                                    placeholder="Enter topic details" required aria-required="true"></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Faculty & Venue -->
                    <section class="mb-4 p-4 bg-white rounded-3 shadow-sm" aria-labelledby="facultyVenueHeading">
                        <h3 id="facultyVenueHeading" class="h6 text-primary mb-3 fw-semibold">
                            <i class="bi bi-person-badge me-2"></i>Faculty & Venue
                        </h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="faculty" class="form-label required">
                                    <i class="bi bi-person-circle"></i>Faculty
                                </label>
                                <select name="faculty[]" id="faculty" class="form-control" required aria-required="true" multiple>
                                    @foreach($facultyMaster as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="faculty_type" class="form-label required">
                                    <i class="bi bi-diagram-3"></i>Faculty Type
                                </label>
                                <select name="faculty_type" id="faculty_type" class="form-control" required
                                    aria-required="true">
                                    <option value="">Select Faculty Type</option>
                                    <option value="1">Internal</option>
                                    <option value="2">Guest</option>
                                    <option value="3">Research</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="vanue" class="form-label required">
                                    <i class="bi bi-geo-alt"></i>Location
                                </label>
                                <select name="vanue" id="vanue" class="form-control" required aria-required="true">
                                    <option value="">Select Location</option>
                                    @foreach($venueMaster as $loc)
                                    <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="internalFacultyDiv">
                                <label for="internal_faculty" class="form-label required">
                                    <i class="bi bi-person-check"></i>Internal Faculty
                                </label>
                                <select name="internal_faculty[]" id="internal_faculty" class="form-control" required aria-required="true" multiple>
                                    @foreach($internal_faculty as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="mb-4 p-4 bg-white rounded-3 shadow-sm" aria-labelledby="scheduleHeading">
                        <h3 id="scheduleHeading" class="h6 text-primary mb-3 fw-semibold">
                            <i class="bi bi-clock-history me-2"></i>Schedule
                        </h3>

                        <!-- Shift Type -->
                        <div class="mb-3">
                            <label class="form-label d-block required">
                                <i class="bi bi-toggle-on"></i>Shift Type
                            </label>
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
                            <label for="shift" class="form-label required">
                                <i class="bi bi-calendar-range"></i>Shift
                            </label>
                            <select name="shift" id="shift" class="form-control" required aria-required="true">
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
                                        <label for="start_time" class="form-label required">
                                            <i class="bi bi-clock"></i>Start Time
                                        </label>
                                        <input type="time" name="start_time" id="start_time" class="form-control"
                                            aria-describedby="startTimeHelp">
                                        <small id="startTimeHelp" class="form-text text-muted">
                                            Must be at least 1 hour from now
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label required">
                                            <i class="bi bi-clock-fill"></i>End Time
                                        </label>
                                        <input type="time" name="end_time" id="end_time" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Additional Options -->
                    <section class="pt-4 border-top" aria-labelledby="additionalOptionsHeading">
    <h3 id="additionalOptionsHeading" class="h6 text-primary mb-3">
        <i class="bi bi-sliders me-2"></i>Additional Options
    </h3>

    <div class="row g-3">
        <!-- Feedback Group -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <!-- Feedback Parent -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="feedback_checkbox"
                               name="feedback_checkbox"
                               value="1"
                               aria-controls="feedbackOptions">
                        <label class="form-check-label fw-semibold" for="feedback_checkbox">
                            Feedback
                        </label>
                    </div>

                    <!-- Feedback Child Options -->
                    <div id="feedbackOptions" class="ps-4 border-start d-none">
                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="remarkCheckbox"
                                   name="remarkCheckbox"
                                   value="1">
                            <label class="form-check-label" for="remarkCheckbox">
                                Remark
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="ratingCheckbox"
                                   name="ratingCheckbox"
                                   value="1">
                            <label class="form-check-label" for="ratingCheckbox">
                                Rating
                            </label>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Select at least one feedback component.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio Attendance (Independent) -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               id="bio_attendanceCheckbox"
                               name="bio_attendanceCheckbox"
                               value="1">
                        <label class="form-check-label fw-semibold" for="bio_attendanceCheckbox">
                            Bio Attendance
                        </label>
                    </div>
                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedbackToggle = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    const remark = document.getElementById('remarkCheckbox');
    const rating = document.getElementById('ratingCheckbox');

    feedbackToggle.addEventListener('change', function () {
        if (this.checked) {
            feedbackOptions.classList.remove('d-none');
        } else {
            feedbackOptions.classList.add('d-none');
            remark.checked = false;
            rating.checked = false;
        }
    });
    const internalFacultyDiv = document.getElementById('internalFacultyDiv');
    const facultySelect = document.getElementById('faculty');
    internalFacultyDiv.style.display = 'none'; // Hide initially
    const faculty_type = document.getElementById('faculty_type');

    // Example: Show internal faculty when a specific group type is selected
    facultySelect.addEventListener('change', function () {
        const facultyType = document.getElementById('faculty_type').value;
        updateinternal_faculty(facultyType);
    });
    faculty_type.addEventListener('change', function () {
        const facultyType = this.value;
        updateinternal_faculty(facultyType);
    });

   function  updateinternal_faculty(facultyType) {
    
        console.log(facultyType);
        switch (facultyType) {
            case '1': // Internal
                console.log('internal');
                internalFacultyDiv.style.display = 'none';
                break;
            case '2': // Guest
                console.log('guest');
                internalFacultyDiv.style.display = 'block';
                // Reinitialize Select2 after showing the div
                if (!$('#internal_faculty').hasClass('select2-hidden-accessible')) {
                    $('#internal_faculty').select2({
                        placeholder: 'Select Internal Faculty',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('#eventModal')
                    });
                }
                break;
            default: // Research/Other
                console.log('rtyuio');
                internalFacultyDiv.style.display = 'block';
                // Reinitialize Select2 after showing the div
                if (!$('#internal_faculty').hasClass('select2-hidden-accessible')) {
                    $('#internal_faculty').select2({
                        placeholder: 'Select Internal Faculty',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('#eventModal')
                    });
                }
        }
    }
});
</script>
<style>
    .form-switch .form-check-input {
    cursor: pointer;
}

.card {
    background-color: #ffffff;
}

.border-start {
    border-left: 2px dashed #dee2e6 !important;
}

</style>
