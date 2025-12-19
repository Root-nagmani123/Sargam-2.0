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
                                <label class="form-label required">Group Type Name</label>
                                <div id="type_name_container" class="border rounded p-3 bg-light-subtle">
                                    <div class="text-center text-muted" id="groupTypePlaceholder">
                                        Select a Group Type first
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="type_names_error" style="display: none;">
                                    Please select at least one Group Type Name.
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