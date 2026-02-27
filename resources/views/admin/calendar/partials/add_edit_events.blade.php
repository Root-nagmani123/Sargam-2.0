<!-- Add/Edit Event Modal -->
<!-- Choices.js (enhanced select UI) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

<style>
    #eventModal .choices__list--dropdown { z-index: 1060; }
    .required::after { content: " *"; color: var(--bs-danger); font-weight: 600; }
</style>


<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen modal-fullscreen-md-down modal-xl">
        <form id="eventForm" novalidate>
            @csrf
            <div class="modal-content shadow-lg">
                <!-- Modal Header -->
                <div class="modal-header bg-primary bg-gradient text-white border-0 px-3 px-md-4 py-3 position-relative">
                    <div class="d-flex flex-column flex-sm-row w-100">
                        <h2 class="modal-title h6 mb-0 fw-bold d-flex align-items-center gap-1 gap-sm-2 flex-wrap flex-grow-1" id="eventModalTitle">
                            <span class="d-none d-sm-inline">Add Calendar Event</span>
                            <span class="d-sm-none">Add Event</span>
                        </h2>
                        <div class="d-flex flex-column flex-sm-row">
                            <label for="start_datetime" class="form-label text-white mb-0 small fw-semibold d-flex align-items-center gap-1 flex-shrink-0">
                                <i class="bi bi-calendar-date"></i>
                                <span class="d-none d-md-inline">Date</span>
                            </label>
                            <input type="date" name="start_datetime" id="start_datetime"
                                class="form-control form-control-sm bg-white bg-opacity-90 border-0 text-dark fw-semibold w-100 w-sm-auto flex-shrink-0 shadow-sm"
                                required aria-required="true">
                            <button type="button" class="btn-close btn-close-white ms-auto ms-sm-0 flex-shrink-0" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body bg-light px-3 px-md-4">
                    <!-- Basic Information -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="basicInfoHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="basicInfoHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Basic Information</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="Course_name" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-book text-primary"></i>
                                        <span>Course Name</span>
                                    </label>
                                    <select name="Course_name" id="Course_name" class="form-select" required
                                        aria-required="true">
                                        <option value="">Select Course</option>
                                        @foreach($courseMaster as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="group_type" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-people text-primary"></i>
                                        <span>Group Type</span>
                                    </label>
                                    <select name="group_type" id="group_type" class="form-select" required
                                        aria-required="true">
                                        <option value="">Select Group Type</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-tag text-primary"></i>
                                        <span>Group Type Name</span>
                                    </label>
                                    <div id="type_name_container" class="border border-2 border-dashed rounded-3 p-3 p-md-4 bg-light bg-opacity-50 min-h-60">
                                        <div class="text-center text-muted d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2" id="groupTypePlaceholder">
                                            <i class="bi bi-arrow-right-circle fs-6 fs-md-5"></i>
                                            <span class="text-break">Select a Group Type first</span>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-block" id="type_names_error" style="display: none;">
                                        Please select at least one Group Type Name.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="subject_module" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-grid-3x3 text-primary"></i>
                                        <span>Subject Module</span>
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

                                <div class="col-12 col-md-6">
                                    <label for="subject_name" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-journal-text text-primary"></i>
                                        <span>Subject Name</span>
                                    </label>
                                    <select name="subject_name" id="subject_name" class="form-select" required
                                        aria-required="true">
                                        <option value="">Select Subject Name</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="topic" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                        <span>Topic</span>
                                    </label>
                                    <textarea name="topic" id="topic" class="form-control" rows="4"
                                        placeholder="Enter topic details..." required aria-required="true"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Faculty & Venue -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="facultyVenueHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="facultyVenueHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge"></i>
                                <span>Faculty & Venue</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="faculty" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-person-circle text-primary"></i>
                                        <span>Faculty</span>
                                    </label>
                                    <div class="position-relative">
                                        <select name="faculty[]" id="faculty" class="form-select" required aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Select multiple faculty members
                                    </small>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="faculty_type" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-diagram-3 text-primary"></i>
                                        <span>Faculty Type</span>
                                    </label>
                                    <select name="faculty_type" id="faculty_type" class="form-select" required
                                        aria-required="true">
                                        <option value="">Select Faculty Type</option>
                                        <option value="1">Internal</option>
                                        <option value="2">Guest</option>
                                        <option value="3">Research</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="vanue" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-geo-alt text-primary"></i>
                                        <span>Location</span>
                                    </label>
                                    <select name="vanue" id="vanue" class="form-select" required aria-required="true">
                                        <option value="">Select Location</option>
                                        @foreach($venueMaster as $loc)
                                        <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6" id="internalFacultyDiv">
                                    <label for="internal_faculty" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-person-check text-primary"></i>
                                        <span>Internal Faculty</span>
                                    </label>
                                    <div class="position-relative">
                                        <select name="internal_faculty[]" id="internal_faculty" class="form-select" required
                                            aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Select internal faculty for guest sessions
                                    </small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="scheduleHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="scheduleHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history"></i>
                                <span>Schedule</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <!-- Shift Type -->
                            <div class="mb-3 mb-md-4">
                                <label class="form-label fw-semibold d-block required mb-2 mb-md-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-toggle-on text-primary"></i>
                                    <span>Shift Type</span>
                                </label>
                                <div class="btn-group d-flex flex-column flex-sm-row w-100 w-md-auto" role="group" aria-label="Shift type selection">
                                    <input type="radio" class="btn-check" name="shift_type" id="normalShift" value="1"
                                        checked aria-controls="shiftSelect">
                                    <label class="btn btn-outline-primary" for="normalShift">
                                        <i class="bi bi-calendar-check me-1 me-md-2"></i><span class="d-none d-sm-inline">Normal </span>Shift
                                    </label>

                                    <input type="radio" class="btn-check" name="shift_type" id="manualShift" value="2"
                                        aria-controls="manualShiftFields">
                                    <label class="btn btn-outline-primary" for="manualShift">
                                        <i class="bi bi-calendar-event me-1 me-md-2"></i><span class="d-none d-sm-inline">Manual </span>Shift
                                    </label>
                                </div>
                            </div>

                            <!-- Normal Shift -->
                            <div id="shiftSelect" class="mb-3">
                                <label for="shift" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-range text-primary"></i>
                                    <span>Shift</span>
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

                            <!-- Manual Shift -->
                            <div id="manualShiftFields" class="d-none">
                                <div class="mb-3 mb-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox"
                                            name="fullDayCheckbox" aria-controls="dateTimeFields">
                                        <label class="form-check-label fw-semibold fs-6 fs-md-5" for="fullDayCheckbox">
                                            <i class="bi bi-calendar-day me-2"></i>Full Day Event
                                        </label>
                                    </div>
                                </div>

                                <div id="dateTimeFields">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="start_time" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                                <i class="bi bi-clock text-primary"></i>
                                                <span>Start Time</span>
                                            </label>
                                            <input type="time" name="start_time" id="start_time" class="form-control"
                                                aria-describedby="startTimeHelp">
                                            <small id="startTimeHelp" class="form-text text-muted d-block mt-1">
                                                <i class="bi bi-info-circle"></i> Must be at least 1 hour from now
                                            </small>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="end_time" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                                <i class="bi bi-clock-fill text-primary"></i>
                                                <span>End Time</span>
                                            </label>
                                            <input type="time" name="end_time" id="end_time" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Additional Options -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-0" aria-labelledby="additionalOptionsHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="additionalOptionsHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-sliders"></i>
                                <span>Additional Options</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3 g-md-4">
                                <!-- Feedback Group -->
                                <div class="col-12 col-md-8">
                                    <div class="card border border-primary border-opacity-50 h-100 shadow-sm additional-option-card">
                                        <div class="card-body p-3 p-md-4">
                                            <!-- Feedback Parent -->
                                            <div class="form-check form-switch mb-3 mb-md-4">
                                                <input class="form-check-input" type="checkbox" id="feedback_checkbox"
                                                    name="feedback_checkbox" value="1" aria-controls="feedbackOptions">
                                                <label class="form-check-label fw-bold text-primary d-flex align-items-center gap-2" for="feedback_checkbox">
                                                    <i class="bi bi-chat-square-text fs-5"></i>
                                                    <span class="fs-6 fs-md-5">Feedback</span>
                                                </label>
                                            </div>

                                            <!-- Feedback Child Options -->
                                            <div id="feedbackOptions" class="feedback-options-container d-none">
                                                <div class="form-check mb-3 mb-md-3">
                                                    <input class="form-check-input" type="checkbox" id="remarkCheckbox"
                                                        name="remarkCheckbox" value="1">
                                                    <label class="form-check-label fw-semibold d-flex align-items-center gap-2" for="remarkCheckbox">
                                                        <i class="bi bi-chat-left-text text-secondary"></i>
                                                        <span>Remark</span>
                                                    </label>
                                                </div>

                                                <div class="form-check mb-3 mb-md-3">
                                                    <input class="form-check-input" type="checkbox" id="ratingCheckbox"
                                                        name="ratingCheckbox" value="1">
                                                    <label class="form-check-label fw-semibold d-flex align-items-center gap-2" for="ratingCheckbox">
                                                        <i class="bi bi-star text-warning"></i>
                                                        <span>Rating</span>
                                                    </label>
                                                </div>

                                                <div class="alert alert-info mb-0 py-2 py-md-2">
                                                    <small class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-info-circle flex-shrink-0"></i>
                                                        <span>Select at least one feedback component.</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio Attendance (Independent) -->
                                <div class="col-12 col-md-4">
                                    <div class="card border border-success border-opacity-50 h-100 shadow-sm additional-option-card">
                                        <div class="card-body d-flex align-items-center justify-content-center p-3 p-md-4">
                                            <div class="form-check form-switch w-100">
                                                <input class="form-check-input" type="checkbox" id="bio_attendanceCheckbox"
                                                    name="bio_attendanceCheckbox" value="1">
                                                <label class="form-check-label fw-bold text-success d-flex align-items-center justify-content-center gap-2 w-100" for="bio_attendanceCheckbox">
                                                    <i class="bi bi-fingerprint fs-5"></i>
                                                    <span class="fs-6 fs-md-5 text-center">Bio Attendance</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top border-2 border-primary border-opacity-25 flex-column flex-sm-row flex-wrap gap-2 py-3 px-3 px-md-4">
                    <button type="button" class="btn btn-outline-secondary w-100 w-md-auto order-2 order-sm-1" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i><span class="d-none d-sm-inline">Cancel</span><span class="d-sm-none">Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary w-100 w-md-auto px-3 px-md-4 order-1 order-sm-2" id="submitEventBtn">
                        <i class="bi bi-check-circle me-2"></i>
                        <span class="btn-text">Add Event</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackToggle = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    const remark = document.getElementById('remarkCheckbox');
    const rating = document.getElementById('ratingCheckbox');
    const faculty_review_rating = document.getElementById('facultyReviewRatingDiv');

    if (feedbackToggle && feedbackOptions && remark && rating) {
        feedbackToggle.addEventListener('change', function() {
            if (this.checked) {
                feedbackOptions.classList.remove('d-none');
            } else {
                feedbackOptions.classList.add('d-none');
                remark.checked = false;
                rating.checked = false;
            }
        });
    }

    const internalFacultyDiv = document.getElementById('internalFacultyDiv');
    const faculty_type = document.getElementById('faculty_type');

    function updateinternal_faculty_data(facultyType) {
        switch (facultyType) {
            case '1': // Internal
            case 1:
                // internalFacultyDiv.style.display = 'none';
                break;
            case '2': // Guest
            case 2:
                if (internalFacultyDiv) {
                    internalFacultyDiv.style.display = 'block';
                }
                break;
            default:
                // internalFacultyDiv.style.display = 'none';
        }
    }

    if (faculty_type) {
        faculty_type.addEventListener('change', function() {
            const facultyType = this.value;
            updateinternal_faculty_data(facultyType);
        });
    }

    function initEventModalChoices() {
        if (typeof Choices === 'undefined') return;

        const modal = document.getElementById('eventModal');
        if (!modal) return;

        // Shared config helper
        function buildConfig(el, extraConfig = {}) {
            const isMultiple = el.multiple;
            const placeholder =
                el.getAttribute('placeholder') ||
                el.dataset.placeholder ||
                (el.options[0] && el.options[0].text) ||
                'Select an option';

            return Object.assign({
                allowHTML: false,
                searchPlaceholderValue: 'Search...',
                removeItemButton: isMultiple,
                shouldSort: false,
                placeholder: true,
                placeholderValue: placeholder,
            }, extraConfig);
        }

        // Ensure global object exists for calendar index JS (linked dropdowns)
        window.calendarEventChoices = window.calendarEventChoices || {};

        // Specific instances that calendar index JS expects
        const groupTypeEl = document.getElementById('group_type');
        if (groupTypeEl && groupTypeEl.dataset.choicesInitialized !== 'true') {
            window.calendarEventChoices.groupType = new Choices(groupTypeEl, buildConfig(groupTypeEl));
            groupTypeEl.dataset.choicesInitialized = 'true';
        }

        const subjectNameEl = document.getElementById('subject_name');
        if (subjectNameEl && subjectNameEl.dataset.choicesInitialized !== 'true') {
            window.calendarEventChoices.subjectName = new Choices(subjectNameEl, buildConfig(subjectNameEl));
            subjectNameEl.dataset.choicesInitialized = 'true';
        }

        // Enhance remaining selects in the modal (but avoid double‑initialising the linked ones)
        modal.querySelectorAll('select.form-select').forEach(function(el) {
            if (['group_type', 'subject_name'].includes(el.id)) return;
            if (el.dataset.choicesInitialized === 'true') return;

            const instance = new Choices(el, buildConfig(el));
            el.dataset.choicesInitialized = 'true';
        });
    }

    const eventModalEl = document.getElementById('eventModal');
    if (eventModalEl) {
        eventModalEl.addEventListener('shown.bs.modal', function() {
            initEventModalChoices();

            // Prevent body scroll when modal is open on mobile
            if (window.innerWidth <= 767) {
                document.body.classList.add('modal-open-mobile');
            }
        });

        eventModalEl.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open-mobile');
        });
    }
});
</script>
