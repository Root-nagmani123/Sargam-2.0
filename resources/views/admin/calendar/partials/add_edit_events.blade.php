<!-- Add/Edit Event Modal — styles: public/css/calendar-admin.css -->
<style>
    /* form between dialog & content was shrinking width (Bootstrap expects dialog > content) */
    #eventModal .modal-dialog.modal-xl {
        --bs-modal-width: 1140px;
        width: min(1140px, calc(100vw - 2rem)) !important;
        max-width: min(1140px, calc(100vw - 2rem)) !important;
    }
    #eventModal .modal-content,
    #eventModal #eventForm {
        width: 100%;
        max-width: 100%;
    }
</style>
<div class="modal fade cal-event-modal" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable cal-event-modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <form id="eventForm" class="cal-event-modal-form d-flex flex-column" novalidate>
            @csrf
                <div class="modal-header cal-event-modal-header border-0">
                    <h2 class="modal-title h5 fw-bold mb-0" id="eventModalTitle">Add Event</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="cal-event-modal-progress px-4 pt-1 pb-3">
                    <div class="d-flex justify-content-end align-items-center mb-2">
                        <span id="eventModalStepPercent" class="cal-event-modal-percent small fw-semibold text-secondary" aria-live="polite">30%</span>
                    </div>
                    <div class="progress cal-event-progress rounded-pill mb-0" role="progressbar"
                        aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"
                        aria-labelledby="eventModalStepLabel" id="eventModalProgressWrap">
                        <div class="progress-bar rounded-pill" id="eventModalProgressBar" style="width: 30%;"></div>
                    </div>
                    <span id="eventModalStepLabel" class="visually-hidden">Basic Information</span>
                </div>

                <div class="modal-body cal-event-modal-body">
                    <section class="cal-modal-step" data-cal-step="1" aria-labelledby="basicInfoHeading">
                        <h3 id="basicInfoHeading" class="cal-modal-section-title h6 fw-bold mb-0 pb-3">Basic Information</h3>
                        <div class="row g-3 cal-modal-fields pt-1">
                            <div class="col-md-6 col-lg-4">
                                <label for="start_datetime" class="form-label required mb-2">Date</label>
                                <input type="date" name="start_datetime" id="start_datetime"
                                    class="form-control cal-modal-input" required aria-required="true">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="Course_name" class="form-label required mb-2">Course Name</label>
                                <select name="Course_name" id="Course_name" class="form-select form-control cal-modal-input" required
                                    aria-required="true">
                                    <option value="">Select Course Name</option>
                                    @foreach($courseMaster as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="group_type" class="form-label required mb-2">Group Type</label>
                                <select name="group_type" id="group_type" class="form-select form-control cal-modal-input" required
                                    aria-required="true">
                                    <option value="">Select Group Type</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label required mb-2">Group Type Name</label>
                                <div id="type_name_container" class="cal-modal-group-names">
                                    <div class="text-center text-muted small py-2" id="groupTypePlaceholder">
                                        Select a Group Type first
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block" id="type_names_error" style="display: none;">
                                    Please select at least one Group Type Name.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="subject_module" class="form-label required mb-2">Module Name</label>
                                <select name="subject_module" id="subject_module" class="form-select form-control cal-modal-input" required
                                    aria-required="true">
                                    <option value="">Select Module Name</option>
                                    @foreach($subjects as $subject)
                                    <option value="{{ $subject->pk }}" data-id="{{ $subject->pk }}">
                                        {{ $subject->module_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subject_name" class="form-label required mb-2">Subject Name</label>
                                <select name="subject_name" id="subject_name" class="form-select form-control cal-modal-input" required
                                    aria-required="true">
                                    <option value="">Select Subject Name</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="topic" class="form-label required mb-2">Topic</label>
                                <input type="text" name="topic" id="topic" class="form-control cal-modal-input"
                                    placeholder="eg. Lorem ipsum dolor sit amet" required aria-required="true">
                            </div>
                        </div>
                    </section>

                    <section class="cal-modal-step d-none" data-cal-step="2" aria-labelledby="facultyVenueHeading">
                        <h3 id="facultyVenueHeading" class="cal-modal-section-title h6 fw-bold mb-0 pb-3">Faculty &amp; Venue</h3>
                        <div class="row g-3 cal-modal-fields pt-1">
                            <div class="col-md-6">
                                <label for="faculty" class="form-label required mb-2">Faculty</label>
                                <select name="faculty[]" id="faculty" class="form-select form-control cal-modal-input" required aria-required="true" multiple>
                                    @foreach($facultyMaster as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="faculty_type" class="form-label required mb-2">Faculty Type</label>
                                <select name="faculty_type" id="faculty_type" class="form-select form-control cal-modal-input" required
                                    aria-required="true">
                                    <option value="">Select Faculty Type</option>
                                    <option value="1">Internal</option>
                                    <option value="2">Guest</option>
                                    <option value="3">Research</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vanue" class="form-label required mb-2">Location</label>
                                <select name="vanue" id="vanue" class="form-select form-control cal-modal-input" required aria-required="true">
                                    <option value="">Select Location</option>
                                    @foreach($venueMaster as $loc)
                                    <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="internalFacultyDiv">
                                <label for="internal_faculty" class="form-label required mb-2">Internal Faculty</label>
                                <select name="internal_faculty[]" id="internal_faculty" class="form-select form-control cal-modal-input" required
                                    aria-required="true" multiple>
                                    @foreach($facultyMaster as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="cal-modal-step d-none" data-cal-step="3" aria-labelledby="scheduleHeading">
                        <h3 id="scheduleHeading" class="cal-modal-section-title h6 fw-bold mb-0 pb-3">Schedule</h3>
                        <div class="row g-3 cal-modal-fields pt-1 mb-4">
                            <div class="col-12">
                                <span class="form-label required d-block mb-2">Shift Type</span>
                                <div class="d-flex flex-wrap gap-4 cal-modal-radio-row">
                                    <div class="form-check mb-0">
                                        <input type="radio" name="shift_type" id="normalShift" value="1"
                                            class="form-check-input" checked aria-controls="shiftSelect">
                                        <label class="form-check-label" for="normalShift">Normal Shift</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input type="radio" name="shift_type" id="manualShift" value="2"
                                            class="form-check-input" aria-controls="manualShiftFields">
                                        <label class="form-check-label" for="manualShift">Manual Shift</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6" id="shiftSelect">
                                <label for="shift" class="form-label required mb-2">Shift</label>
                                <select name="shift" id="shift" class="form-select form-control cal-modal-input" required aria-required="true">
                                    <option value="">Select Shift</option>
                                    @foreach($classSessionMaster as $shift)
                                    <option value="{{ $shift->shift_time }}">
                                        {{ $shift->shift_name }} ({{ $shift->shift_time }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="manualShiftFields" class="col-12 d-none">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox"
                                            name="fullDayCheckbox" aria-controls="dateTimeFields">
                                        <label class="form-check-label" for="fullDayCheckbox">Full Day Event</label>
                                    </div>
                                </div>
                                <div id="dateTimeFields" class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_time" class="form-label required mb-2">Start Time</label>
                                        <input type="time" name="start_time" id="start_time" class="form-control cal-modal-input"
                                            aria-describedby="startTimeHelp">
                                        <small id="startTimeHelp" class="form-text text-muted">
                                            Must be at least 1 hour from now
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label required mb-2">End Time</label>
                                        <input type="time" name="end_time" id="end_time" class="form-control cal-modal-input">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 id="additionalOptionsHeading" class="cal-modal-section-title h6 fw-bold mb-0 pb-3 mt-2">Additional Options</h3>
                        <div class="cal-modal-options-list pt-1">
                            <div class="cal-modal-option-row">
                                <span class="cal-modal-option-label">Feedback</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="feedback_checkbox"
                                        name="feedback_checkbox" value="1" aria-controls="feedbackOptions">
                                    <label class="form-check-label visually-hidden" for="feedback_checkbox">Feedback</label>
                                </div>
                            </div>
                            <div id="feedbackOptions" class="cal-modal-feedback-nested ps-3 ms-1 mb-2 d-none">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="remarkCheckbox"
                                        name="remarkCheckbox" value="1">
                                    <label class="form-check-label" for="remarkCheckbox">Remark</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ratingCheckbox"
                                        name="ratingCheckbox" value="1">
                                    <label class="form-check-label" for="ratingCheckbox">Rating</label>
                                </div>
                                <small class="text-muted d-block mt-2">Select at least one feedback component.</small>
                            </div>
                            <div class="cal-modal-option-row">
                                <span class="cal-modal-option-label">Bio Attendance</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input cal-switch-bio" type="checkbox" id="bio_attendanceCheckbox"
                                        name="bio_attendanceCheckbox" value="1">
                                    <label class="form-check-label visually-hidden" for="bio_attendanceCheckbox">Bio Attendance</label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal-footer cal-event-modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary rounded-2 px-4 d-none" id="btnEventModalBack">Back</button>
                    <button type="button" class="btn btn-primary rounded-2 px-4" id="btnEventModalNext">Next</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4 d-none" id="submitEventBtn">
                        <span class="btn-text">Add Event</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackToggle = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    const remark = document.getElementById('remarkCheckbox');
    const rating = document.getElementById('ratingCheckbox');

    if (feedbackToggle && feedbackOptions) {
        feedbackToggle.addEventListener('change', function() {
            if (this.checked) {
                feedbackOptions.classList.remove('d-none');
            } else {
                feedbackOptions.classList.add('d-none');
                if (remark) remark.checked = false;
                if (rating) rating.checked = false;
            }
        });
    }

    const internalFacultyDiv = document.getElementById('internalFacultyDiv');
    const faculty_type = document.getElementById('faculty_type');

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
                containerInner: ['choices__inner', 'form-select', 'cal-modal-input'],
                input: ['choices__input'],
                inputCloned: ['choices__input--cloned'],
                listDropdown: ['choices__list--dropdown'],
                item: ['choices__item'],
                itemSelectable: ['choices__item--selectable'],
                itemDisabled: ['choices__item--disabled'],
                itemChoice: ['choices__item--choice'],
                placeholder: ['choices__placeholder'],
                highlightedState: ['is-highlighted'],
                notice: ['choices__notice']
            }
        });

        el._choicesInstance = instance;
        return instance;
    }

    function getPlaceholderText(el) {
        if (!el) return 'Select option';
        const firstOption = el.querySelector('option[value=""]');
        if (firstOption && firstOption.textContent.trim()) {
            return firstOption.textContent.trim();
        }
        const label = document.querySelector(`label[for="${el.id}"]`);
        const labelText = label ? label.textContent.replace(/\s*\*\s*$/, '').trim() : '';
        return labelText ? `Select ${labelText}` : 'Select option';
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

    $('#eventModal').on('shown.bs.modal', function() {
        initAllModalChoices();
        if (window.calendarEventModalWizard) {
            window.calendarEventModalWizard.reset();
        }
    });

    $('#eventModal').on('hidden.bs.modal', function() {
        destroyAllModalChoices();
        if (window.calendarEventModalWizard) {
            window.calendarEventModalWizard.reset();
        }
    });

    (function initEventModalWizard() {
        const steps = [
            { label: 'Basic Information', percent: 30 },
            { label: 'Faculty & Venue', percent: 60 },
            { label: 'Schedule', percent: 100 }
        ];
        let currentStep = 1;
        const stepEls = document.querySelectorAll('#eventModal .cal-modal-step[data-cal-step]');
        const stepLabel = document.getElementById('eventModalStepLabel');
        const stepPercent = document.getElementById('eventModalStepPercent');
        const progressBar = document.getElementById('eventModalProgressBar');
        const progressWrap = document.getElementById('eventModalProgressWrap');
        const btnNext = document.getElementById('btnEventModalNext');
        const btnBack = document.getElementById('btnEventModalBack');
        const btnSubmit = document.getElementById('submitEventBtn');

        function goToStep(step) {
            currentStep = step;
            stepEls.forEach((el) => {
                const n = parseInt(el.getAttribute('data-cal-step'), 10);
                el.classList.toggle('d-none', n !== step);
            });
            const meta = steps[step - 1];
            if (stepLabel) stepLabel.textContent = meta.label;
            if (stepPercent) stepPercent.textContent = meta.percent + '%';
            if (progressBar) {
                progressBar.style.width = meta.percent + '%';
            }
            if (progressWrap) {
                progressWrap.setAttribute('aria-valuenow', String(meta.percent));
            }
            if (btnBack) btnBack.classList.toggle('d-none', step === 1);
            if (btnNext) btnNext.classList.toggle('d-none', step === 3);
            if (btnSubmit) btnSubmit.classList.toggle('d-none', step !== 3);
        }

        btnNext?.addEventListener('click', () => {
            if (currentStep < 3) goToStep(currentStep + 1);
        });
        btnBack?.addEventListener('click', () => {
            if (currentStep > 1) goToStep(currentStep - 1);
        });

        window.calendarEventModalWizard = {
            reset: function() { goToStep(1); }
        };
        goToStep(1);
    })();

    if (faculty_type && internalFacultyDiv) {
        faculty_type.addEventListener('change', function() {
            updateinternal_faculty_data(this.value);
        });

        function updateinternal_faculty_data(facultyType) {
            switch (facultyType) {
                case '1':
                case 1:
                    break;
                case '2':
                case 2:
                    internalFacultyDiv.style.display = 'block';
                    break;
                default:
                    break;
            }
        }
    }
});
</script>
