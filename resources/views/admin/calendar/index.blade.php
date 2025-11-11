@extends('admin.layouts.master')

@section('title', 'Calendar - Sargam | Lal Bahadur')

@section('content')

<style>
.readonly-checkbox {
    pointer-events: none; 
    opacity: 0.6;
}

/* Month ke har din ke box ki height/padding badhao */
.fc .fc-daygrid-day-frame {
    min-height: 110px !important;
    padding: 8px 4px !important;
}

.fc .fc-daygrid-day {
    min-height: 110px !important;
}

/* Responsive ke liye thoda adjust */
@media (max-width: 600px) {

    .fc .fc-daygrid-day-frame,
    .fc .fc-daygrid-day {
        min-height: 70px !important;
    }
}

/* Custom colored cards for each day */

/* Assign different colors for each weekday */
.fc-daygrid-day[data-day="0"] .fc-daygrid-day-frame {
    background-color: #ffe5e5;
}

/* Sunday */
.fc-daygrid-day[data-day="1"] .fc-daygrid-day-frame {
    background-color: #e5f7ff;
}

/* Monday */
.fc-daygrid-day[data-day="2"] .fc-daygrid-day-frame {
    background-color: #e5ffe5;
}

/* Tuesday */
.fc-daygrid-day[data-day="3"] .fc-daygrid-day-frame {
    background-color: #fffbe5;
}

/* Wednesday */
.fc-daygrid-day[data-day="4"] .fc-daygrid-day-frame {
    background-color: #f5e5ff;
}

/* Thursday */
.fc-daygrid-day[data-day="5"] .fc-daygrid-day-frame {
    background-color: #e5eaff;
}

/* Friday */
.fc-daygrid-day[data-day="6"] .fc-daygrid-day-frame {
    background-color: #fff0e5;
}

/* Saturday */
/* Card content styling */
.fc-event-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    padding: 8px;
    margin-bottom: 6px;
    font-size: 15px;
    color: #222;
}

/* Multiple selection styling */
.select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Faculty and Venue sections styling */
.faculty-section, .venue-section {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
}

.faculty-section h6, .venue-section h6 {
    margin-bottom: 0.75rem;
    color: #495057;
    font-weight: 600;
}

.remove-faculty, .remove-venue {
    margin-top: 0.5rem;
}

/* Topic sections styling */
.topic-section {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #fff;
}

.topic-section h6 {
    margin-bottom: 0.75rem;
    color: #495057;
    font-weight: 600;
}

.remove-topic {
    margin-top: 0.5rem;
}

/* Add button styling */
.btn-add-more {
    margin-bottom: 1rem;
}
</style>

<div class="container-fluid">
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body py-3">
            <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Calendar</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="#">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    @lang('Calendar')
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        </div>
    </div>
    <div class="text-end mb-3 gap-3">
        <button type="button" class="btn btn-primary btn-sm" id="createEventupperButton">
        <i class="bi bi-plus"></i> Add Event</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnMonthView">Month</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnWeekView">Week</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnDayView">Day</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnListView">List</button>
    </div>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body calender-sidebar app-calendar">
            <div id='calendar'></div>
            <!-- List View Table -->
                <div id="eventListView" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-2">
                            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="lbsnaa logo" class="img-fluid mb-3"
                                style="max-width: 100px;">
                        </div>
                        <div class="col-8">
                            <h3 class="text-center" style="color:#af2910;">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी
                            </h3>
                            <h3 class="text-center" style="color:#af2910;">Lal Bahadur Shastri National Academy of
                                Administration</h3>
                            <h4 class="text-center" style="color:#af2910;">IAS Professional Course, Phase - I</h4>
                            <h6 class="text-center">(Nov 06, 2023 to April 05, 2024)</h6>
                        </div>
                        <div class="col-2">
                            <h5 class="text-center">Weekly Schedule</h5>
                            <h3 class="text-center">19</h3>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped table-hover" id="eventListTable">
                        <thead>
                            <tr>
                                <th rowspan="3" class="text-center align-middle">Time</th>
                                <th rowspan="3" class="text-center align-middle">Group</th>
                                <th class="text-center align-middle">HRM Module</th>
                                <th class="text-center align-middle">Economics Module</th>
                                <th class="text-center align-middle">Economics Module</th>
                                <th class="text-center align-middle">Election Module</th>
                                <th class="text-center align-middle">Election/Economics/Law Module</th>
                            </tr>
                            <tr>
                                <th class="text-center align-middle">Monday</th>
                                <th class="text-center align-middle">Tuesday</th>
                                <th class="text-center align-middle">Wednesday</th>
                                <th class="text-center align-middle">Thursday</th>
                                <th class="text-center align-middle">Friday</th>
                            </tr>
                            <tr>
                                <th class="text-center align-middle">11.03.2024</th>
                                <th class="text-center align-middle">12.03.2024</th>
                                <th class="text-center align-middle">13.03.2024</th>
                                <th class="text-center align-middle">14.03.2024</th>
                                <th class="text-center align-middle">15.03.2024</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Events will be inserted here -->
                           
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
    <!-- BEGIN MODAL -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true"
        style="display: none;">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <form id="eventForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel">
                            {{ $modalTitle ?? __('Add Calendar Event') }}
                        </h5>
                        <input type="date" name="start_datetime" id="start_datetime" class="form-control w-50 mx-2"
                            placeholder="Select Date" required>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: scroll; height: 700px;overflow-x: hidden;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Course name <span class="text-danger">*</span></label>
                                    <select name="Course_name" id="Course_name" class="form-control">
                                        <option value="">Select Course</option>
                                        @foreach($courseMaster as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Group Type <span class="text-danger">*</span></label>
                                    <select name="group_type" id="group_type" class="form-control">
                                        <option value="">Select Group Type</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div>
                                    <label class="form-label">Group Type Name </label>
                                </div>
                                <div id="type_name_container" class="mt-3">
                                    <!-- Checkboxes will be appended here -->
                                </div>
                            </div>
                            
                            <!-- Topics Section -->
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Topics</h5>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-more" id="addTopicBtn">
                                        <i class="bi bi-plus"></i> Add Topic
                                    </button>
                                </div>
                                <div id="topicsContainer">
                                    <!-- Topic sections will be added here dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="button" class="btn btn-success btn-update-event"
                            data-fc-event-public-id="{{ $event->id ?? '' }}" style="display: none;">
                            Update changes
                        </button>
                        <button type="submit" class="btn btn-primary btn-add-event">
                            Add Calendar Event
                        </button>
                    </div>

            </form>
        </div>
    </div>
    </div>
    <!-- END MODAL -->
    <!-- eventDetails modal do-->
    <!-- Modal -->
    <div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content shadow rounded">
                <div class="modal-header d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="modal-title" id="eventDetailsLabel">
                            <span id="eventTitle">Event Title</span>:<span id="eventTopic"></span>
                        </h5>
                        <small class="text-muted" id="eventDate">Event Date</small><br>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="editEventBtn">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger me-1" id="deleteEventBtn">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <i class="bi bi-person-fill me-2"></i>Faculty: <b><span id="eventfaculty"></span></b>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-geo-alt-fill me-2"></i>Venue: <b><span id="eventVanue"></span></b>
                    </div>
                    <!-- <div class="mb-2">
                    <i class="bi bi-globe me-2"></i> <span id="eventAudience">Public</span>
                </div> -->
                </div>
            </div>
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for multiple select elements
    // $('.faculty-select').select2({
    //     placeholder: "Select Faculty",
    //     allowClear: true
    // });
    
    // $('.venue-select').select2({
    //     placeholder: "Select Venue",
    //     allowClear: true
    // });
    
    // Add first topic section on page load
    addTopicSection();
    
    // Add topic button click handler
    $('#addTopicBtn').on('click', function() {
        addTopicSection();
    });
    
    // Function to add a new topic section
    function addTopicSection() {
        const topicIndex = $('.topic-section').length;
        const topicSection = `
            <div class="topic-section" id="topicSection${topicIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Topic ${topicIndex + 1}</h6>
                    ${topicIndex > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger remove-topic" data-index="' + topicIndex + '"><i class="bi bi-trash"></i> Remove</button>' : ''}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Subject Module Name <span class="text-danger">*</span></label>
                            <select name="subject_module" class="form-control subject-module-select" id="subject_module">
                                <option value="">Select Subject Name</option>
                                @foreach($subjects as $subject)
                                <option value="{{ $subject->pk }}" data-id="{{ $subject->pk }}">
                                    {{ $subject->module_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <select name="subject_name" id="subject_name" class="form-control subject-name-select">
                                <option value="">Select Subject Name</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Topic <span class="text-danger">*</span></label>
                            <textarea name="topic" id="topic" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <!-- Faculty Section -->
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6>Faculty</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-more add-faculty-btn" data-topic-index="${topicIndex}">
                                <i class="bi bi-plus"></i> Add Faculty
                            </button>
                        </div>
                        <div class="faculty-container" id="facultyContainer${topicIndex}">
                            <!-- Faculty sections will be added here dynamically -->
                        </div>
                    </div>
                    
                    <!-- Venue Section -->
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6>Venue</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-more add-venue-btn" data-topic-index="${topicIndex}">
                                <i class="bi bi-plus"></i> Add Venue
                            </button>
                        </div>
                        <div class="venue-container" id="venueContainer${topicIndex}">
                            <!-- Venue sections will be added here dynamically -->
                        </div>
                    </div>
                    
                    <!-- Shift Section -->
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Shift Type<span class="text-danger">*</span></label>
                                    <div>
                                        <input type="radio" name="shift_type" id="normalShift" value="1" class="form-check-input" checked>
                                        <label class="form-check-label" for="normalShift">Normal Shift</label>
                                        <input type="radio" name="shift_type" id="manualShift" value="2" class="form-check-input">
                                        <label class="form-check-label" for="manualShift">Manual Shift</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12" id="shiftSelect">
                                <div class="mb-3">
                                    <label class="form-label">Shift <span class="text-danger">*</span></label>
                                    <select name="shift" class="form-control" id="shift">
                                        <option value="">Select Shift</option>
                                        @foreach($classSessionMaster as $shift)
                                        <option value="{{ $shift->shift_time }}">{{ $shift->shift_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-12" id="manualShiftFields" style="display: none;">
                                <div class="mb-3 form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox" name="fullDayCheckbox">
                                    <label class="form-check-label" for="fullDayCheckbox">Full Day</label>
                                </div>
                                
                                <div id="dateTimeFields${topicIndex}">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                            <input type="time" name="start_time" id="start_time" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                            <input type="time" name="end_time" id="end_time" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <label class="form-label">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                name="feedback_checkbox" id="feedback_checkbox" checked>
                                            <label class="form-check-label" for="feedback_checkbox">
                                                Feedback
                                            </label>
                                        </div>
                                    </label>

                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <label class="form-label">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                name="remarkCheckbox" id="remarkCheckbox">
                                            <label class="form-check-label" for="remarkCheckbox">
                                                Remark
                                            </label>
                                        </div>
                                    </label>

                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <label class="form-label">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                name="ratingCheckbox" id="ratingCheckbox">
                                            <label class="form-check-label" for="ratingCheckbox">
                                                Ratting
                                            </label>
                                        </div>
                                    </label>

                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div>
                                    <label class="form-label">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                name="bio_attendanceCheckbox" id="bio_attendanceCheckbox">
                                            <label class="form-check-label" for="bio_attendanceCheckbox">
                                                Bio Attendance
                                            </label>
                                        </div>
                                    </label>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#topicsContainer').append(topicSection);
        
        // Initialize Select2 for new selects
        // $(`#topicSection${topicIndex} .subject-module-select`).select2();
        // $(`#topicSection${topicIndex} .subject-name-select`).select2();
        
        // Add first faculty and venue for this topic
        addFacultySection(topicIndex, 0);
        addVenueSection(topicIndex, 0);
        
        // Set up event handlers for this topic
        setupTopicEventHandlers(topicIndex);
    }
    
    // Function to add a faculty section
    function addFacultySection(topicIndex, facultyIndex) {
        const facultySection = `
            <div class="faculty-section" id="facultySection${topicIndex}_${facultyIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Faculty ${facultyIndex + 1}</h6>
                    ${facultyIndex > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger remove-faculty" data-topic-index="${topicIndex}" data-faculty-index="${facultyIndex}"><i class="bi bi-trash"></i> Remove</button>' : ''}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Faculty <span class="text-danger">*</span></label>
                            <select name="faculty" id="faculty" class="form-control faculty-select" >
                                <option value="">Select Faculty</option>
                                @foreach($facultyMaster as $faculty)
                                <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                    {{ $faculty->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Faculty Type <span class="text-danger">*</span></label>
                            <select name="faculty_type" class="form-control" id="faculty_type">
                                <option value="">Select Faculty Type</option>
                                <option value="1">Internal</option>
                                <option value="2">Guest</option>
                                <option value="3">Research</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $(`#facultyContainer${topicIndex}`).append(facultySection);
        
        // Initialize Select2 for new selects
        // $(`#facultySection${topicIndex}_${facultyIndex} .faculty-select`).select2();
        // $(`#facultySection${topicIndex}_${facultyIndex} select[name="faculty_type[]"]`).select2();
    }
    
    // Function to add a venue section
    function addVenueSection(topicIndex, venueIndex) {
        const venueSection = `
            <div class="venue-section" id="venueSection${topicIndex}_${venueIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Venue ${venueIndex + 1}</h6>
                    ${venueIndex > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger remove-venue" data-topic-index="${topicIndex}" data-venue-index="${venueIndex}"><i class="bi bi-trash"></i> Remove</button>' : ''}
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="vanue" class="form-control venue-select" id="vanue">
                                <option value="">Select Location</option>
                                @foreach($venueMaster as $loc)
                                <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $(`#venueContainer${topicIndex}`).append(venueSection);
        
        // Initialize Select2 for new selects
        // $(`#venueSection${topicIndex}_${venueIndex} .venue-select`).select2();
    }
    
    // Function to set up event handlers for a topic
    function setupTopicEventHandlers(topicIndex) {
        // Subject module change handler
        $(`#topicSection${topicIndex} .subject-module-select`).on('change', function() {
            const dataId = $(this).find(':selected').data('id');
            const subjectNameSelect = $(this).closest('.topic-section').find('.subject-name-select');
            
            if (dataId) {
                $.ajax({
                    url: "{{ route('calendar.get.subject.name') }}",
                    type: 'GET',
                    data: { data_id: dataId },
                    success: function(response) {
                        subjectNameSelect.empty().append('<option value="">Select Subject Name</option>');
                        $.each(response, function(key, module) {
                            subjectNameSelect.append('<option value="' + module.pk + '">' + module.subject_name + '</option>');
                        });
                    }
                });
            } else {
                subjectNameSelect.empty().append('<option value="">Select Subject Name</option>');
            }
        });
        
        // Shift type change handler
        $(`input[name="shift_type[${topicIndex}]"]`).on('change', function() {
            if ($(`#manualShift${topicIndex}`).is(':checked')) {
                $(`#shiftSelect${topicIndex}`).hide();
                $(`#manualShiftFields${topicIndex}`).show();
            } else {
                $(`#shiftSelect${topicIndex}`).show();
                $(`#manualShiftFields${topicIndex}`).hide();
            }
        });
        
        // Full day checkbox handler
        $(`#fullDayCheckbox${topicIndex}`).on('change', function() {
            if ($(this).is(':checked')) {
                $(`#start_time${topicIndex}`).val('08:00');
                $(`#end_time${topicIndex}`).val('20:00');
            } else {
                $(`#start_time${topicIndex}`).val('');
                $(`#end_time${topicIndex}`).val('');
            }
        });
        
        // Add faculty button handler
        $(`#topicSection${topicIndex} .add-faculty-btn`).on('click', function() {
            const facultyIndex = $(`#facultyContainer${topicIndex} .faculty-section`).length;
            addFacultySection(topicIndex, facultyIndex);
        });
        
        // Add venue button handler
        $(`#topicSection${topicIndex} .add-venue-btn`).on('click', function() {
            const venueIndex = $(`#venueContainer${topicIndex} .venue-section`).length;
            addVenueSection(topicIndex, venueIndex);
        });
    }
    
    // Remove topic handler
    $(document).on('click', '.remove-topic', function() {
        const index = $(this).data('index');
        $(`#topicSection${index}`).remove();
    });
    
    // Remove faculty handler
    $(document).on('click', '.remove-faculty', function() {
        const topicIndex = $(this).data('topic-index');
        const facultyIndex = $(this).data('faculty-index');
        $(`#facultySection${topicIndex}_${facultyIndex}`).remove();
    });
    
    // Remove venue handler
    $(document).on('click', '.remove-venue', function() {
        const topicIndex = $(this).data('topic-index');
        const venueIndex = $(this).data('venue-index');
        $(`#venueSection${topicIndex}_${venueIndex}`).remove();
    });
    
    // Faculty change handler for faculty type
    $(document).on('change', '.faculty-select', function() {
        const selectedOptions = $(this).find('option:selected');
        const facultyTypeSelect = $(this).closest('.faculty-section').find('select[name^="faculty_type"]');
        
        // Clear previous selections
        facultyTypeSelect.val(null).trigger('change');
        
        // Add faculty types based on selected faculty
        selectedOptions.each(function() {
            const facultyType = $(this).data('faculty_type');
            if (facultyType) {
                facultyTypeSelect.find(`option[value="${facultyType}"]`).prop('selected', true);
            }
        });
        
        facultyTypeSelect.trigger('change');
    });
    
    // Toggle shift fields on page load
    toggleShiftFields();

    // On change of shift type
    $('input[name="shift_type"]').on('change', function() {
        toggleShiftFields();
    });

    function toggleShiftFields() {
        if ($('#manualShift').is(':checked')) {
            $('#shiftSelect').hide();
            $('#manualShiftFields').show();
        } else {
            $('#shiftSelect').show();
            $('#manualShiftFields').hide();
        }
    }

    function toggleRemarkRating() {
        if ($('#feedback_checkbox').is(':checked')) {
            $('#remarkCheckbox').off('click.readonly').removeClass('readonly-checkbox');
            $('#ratingCheckbox').off('click.readonly').removeClass('readonly-checkbox');
        } else {
            $('#remarkCheckbox')
                .prop('checked', false)
                .on('click.readonly', function(e) { e.preventDefault(); })
                .addClass('readonly-checkbox');

            $('#ratingCheckbox')
                .prop('checked', false)
                .on('click.readonly', function(e) { e.preventDefault(); })
                .addClass('readonly-checkbox');
        }
    }

    // Initial call
    toggleRemarkRating();

    // On change of Feedback checkbox
    $('#feedback_checkbox').on('change', function() {
        toggleRemarkRating();
    });

    // Course name change handler
    $('#Course_name').on('change', function() {
        var courseName = $(this).val();
        if (courseName) {
            $.ajax({
                url: "{{ route('calendar.get.group.types') }}",
                type: 'GET',
                data: { course_id: courseName },
                success: function(response) {
                    // Step 1: Group by group_type_name
                    let groupedData = {};

                    response.forEach(item => {
                        if (!groupedData[item.group_type_name]) {
                            groupedData[item.group_type_name] = [];
                        }
                        groupedData[item.group_type_name].push(item);
                    });

                    // Step 2: Fill the dropdown with unique group_type_name
                    $('#group_type').empty().append('<option value="">Select Group Type</option>');
                    $('#type_name_container').html('');
                    for (const key in groupedData) {
                        if (groupedData[key].length > 0) {
                            const typeName = groupedData[key][0].type_name;
                            $('#group_type').append(`<option value="${key}">${typeName}</option>`);
                        }
                    }

                    $('#group_type').off('change').on('change', function() {
                        console.log('Group type changed first');
                        const selectedType = $(this).val();
                        let html = '';
                        let groupNames = window.selectedGroupNames;

                        if (groupedData[selectedType]) {
                            // Agar create ke time hai, toh sab checked
                            let allChecked = groupNames === 'ALL';
                            groupedData[selectedType].forEach(group => {
                                let checked = '';
                                if (allChecked) {
                                    checked = 'checked';
                                } else if (Array.isArray(groupNames) && groupNames.includes(group.pk)) {
                                    checked = 'checked';
                                }
                                html += `
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="type_names[]" 
                                            value="${group.pk}" 
                                            id="type_${group.pk}" ${checked}>
                                        <label class="form-check-label" for="type_${group.pk}">
                                            ${group.group_name} (${group.type_name})
                                        </label>
                                    </div>
                                `;
                            });
                        }

                        $('#type_name_container').html(html);
                    });
                }
            });
        } else {
            $('#group_type').empty().append('<option value="">Select Group Type</option>');
               console.log('Group type changed 2');
            $('#type_name_container').html('');
        }
    });

    // Form submission handler
    // $('#eventForm').on('submit', function(e) {
    //     e.preventDefault();
        
    //     // Validate form
    //     if (!validateForm()) {
    //         return false;
    //     }
        
    //     // Prepare form data
    //     let formData = new FormData(this);
        
    //     // Add group type names
    //     $('input[name="type_names"]:checked').each(function() {
    //         formData.append('group_type_name[]', $(this).val());
    //     });
        
    //     // Submit form via AJAX
    //     $.ajax({
    //         url: "{{ route('calendar.event.store') }}",
    //         method: "POST",
    //         data: $(this).serialize(),
    //         success: function(response) {
    //             alert("Event created successfully!");
    //             $('#eventModal').modal('hide');
    //             $('#eventForm')[0].reset();
    //             // window.location.reload();
    //         },
    //         error: function(xhr) {
    //             if (xhr.status === 422) {
    //                 let errors = xhr.responseJSON.errors;
    //                 let messages = Object.values(errors).map(val => val.join('\n')).join('\n');
    //                 alert("Server Validation Failed:\n\n" + messages);
    //             }
    //         }
    //     });
    // });
    
    // Form validation function
    function validateForm() {
        let isValid = true;
        
        // Basic validations
        const courseName = $('#Course_name').val();
        const startDate = $('#start_datetime').val();
        
        if (!courseName) {
            alert("Please select a Course Name.");
            $('#Course_name').focus();
            return false;
        }
        
        if (!startDate) {
            alert("Please select a Start Date.");
            $('#start_datetime').focus();
            return false;
        }
        
        // Validate each topic
        $('.topic-section').each(function(index) {
            const topicIndex = index;
            const subjectModule = $(this).find('.subject-module-select').val();
            const subjectName = $(this).find('.subject-name-select').val();
            const topic = $(this).find('textarea[name="topic"]').val();
            
            if (!subjectModule) {
                alert(`Please select a Subject Module for Topic ${topicIndex + 1}.`);
                $(this).find('.subject-module-select').focus();
                isValid = false;
                return false;
            }
            
            if (!subjectName) {
                alert(`Please select a Subject Name for Topic ${topicIndex + 1}.`);
                $(this).find('.subject-name-select').focus();
                isValid = false;
                return false;
            }
            
            if (!topic) {
                alert(`Please enter a Topic for Topic ${topicIndex + 1}.`);
                $(this).find('textarea[name="topic[]"]').focus();
                isValid = false;
                return false;
            }
            
            // Validate faculty for this topic
            const facultySections = $(this).find('.faculty-section');
            if (facultySections.length === 0) {
                alert(`Please add at least one Faculty for Topic ${topicIndex + 1}.`);
                isValid = false;
                return false;
            }
            
            facultySections.each(function(facultyIndex) {
                const facultySelect = $(this).find('.faculty-select');
                const facultyTypeSelect = $(this).find('select[name^="faculty_type"]');
                
                if (facultySelect.val() === null || facultySelect.val().length === 0) {
                    alert(`Please select at least one Faculty for Faculty ${facultyIndex + 1} in Topic ${topicIndex + 1}.`);
                    facultySelect.focus();
                    isValid = false;
                    return false;
                }
                
                if (facultyTypeSelect.val() === null || facultyTypeSelect.val().length === 0) {
                    alert(`Please select at least one Faculty Type for Faculty ${facultyIndex + 1} in Topic ${topicIndex + 1}.`);
                    facultyTypeSelect.focus();
                    isValid = false;
                    return false;
                }
            });
            
            // Validate venue for this topic
            const venueSections = $(this).find('.venue-section');
            if (venueSections.length === 0) {
                alert(`Please add at least one Venue for Topic ${topicIndex + 1}.`);
                isValid = false;
                return false;
            }
            
            venueSections.each(function(venueIndex) {
                const venueSelect = $(this).find('.venue-select');
                
                if (venueSelect.val() === null || venueSelect.val().length === 0) {
                    alert(`Please select at least one Venue for Venue ${venueIndex + 1} in Topic ${topicIndex + 1}.`);
                    venueSelect.focus();
                    isValid = false;
                    return false;
                }
            });
            
            // Validate shift for this topic
            const shiftType = $(this).find(`input[name="shift_type"]:checked`).val();
            
            if (!shiftType) {
                alert(`Please select a Shift Type for Topic}.`);
                isValid = false;
                return false;
            }
            
            if (shiftType == 1) {
                // Normal shift
                const shift = $(this).find(`select[name="shift"]`).val();
                if (!shift) {
                    alert(`Please select a Shift for Topic ${topicIndex + 1}.`);
                    $(this).find(`select[name="shift"]`).focus();
                    isValid = false;
                    return false;
                }
            } else {
                // Manual shift
                const startTime = $(this).find(`#start_time${topicIndex}`).val();
                const endTime = $(this).find(`#end_time${topicIndex}`).val();
                
                if (!startTime) {
                    alert(`Please enter a Start Time for Topic ${topicIndex + 1}.`);
                    $(this).find(`#start_time${topicIndex}`).focus();
                    isValid = false;
                    return false;
                }
                
                if (!endTime) {
                    alert(`Please enter an End Time for Topic ${topicIndex + 1}.`);
                    $(this).find(`#end_time${topicIndex}`).focus();
                    isValid = false;
                    return false;
                }
            }
        });
        
        // Validate feedback options
        if ($('#feedback_checkbox').is(':checked')) {
            if (!$('#remarkCheckbox').is(':checked') && !$('#ratingCheckbox').is(':checked')) {
                alert("Please select at least Remark or Rating when Feedback is checked.");
                $('#remarkCheckbox').focus();
                isValid = false;
            }
        }
        
        return isValid;
    }
});

// Rest of your existing JavaScript code for calendar functionality...
</script>
<script>
$(document).ready(function() {
    $(document).ready(function() {
        toggleShiftFields();

        // On change of shift type
        $('input[name="shift_type"]').on('change', function() {
            toggleShiftFields();
        });

        function toggleShiftFields() {
            if ($('#manualShift').is(':checked')) {
                $('#shiftSelect').hide();
                $('#manualShiftFields').show();
            } else {
                $('#shiftSelect').show();
                $('#manualShiftFields').hide();
            }
        }

      function toggleRemarkRating() {
            if ($('#feedback_checkbox').is(':checked')) {
                $('#remarkCheckbox').off('click.readonly').removeClass('readonly-checkbox');
                $('#ratingCheckbox').off('click.readonly').removeClass('readonly-checkbox');
            } else {
                $('#remarkCheckbox')
                    .prop('checked', false)
                    .on('click.readonly', function(e) { e.preventDefault(); })
                    .addClass('readonly-checkbox');

                $('#ratingCheckbox')
                    .prop('checked', false)
                    .on('click.readonly', function(e) { e.preventDefault(); })
                    .addClass('readonly-checkbox');
            }
        }



        // Initial call
        toggleRemarkRating();

        // On change of Feedback checkbox
        $('#feedback_checkbox').on('change', function() {
            toggleRemarkRating();
        });
    });

    $('#subject_module').on('change', function() {
        // Get data-id from selected option
        var dataId = $(this).find(':selected').data('id');

        if (dataId) {
            $.ajax({
                url: "{{ route('calendar.get.subject.name') }}",
                type: 'GET',
                data: {
                    data_id: dataId
                },
                success: function(response) {
                    $('#subject_name').empty().append(
                        '<option value="">Select Subject Name</option>'
                    );
                    $.each(response, function(key, module) {
                        $('#subject_name').append(
                            '<option value="' + module.pk +
                            '">' + module.subject_name +
                            '</option>');
                    });
                }
            });
        } else {
            $('#subject_name').empty().append(
                '<option value="">Select Subject Name</option>');
        }
    });

    $(document).ready(function() {
        // When faculty is selected, set the faculty_type based on its data attribute
        $('#faculty').on('change', function() {
            var selectedType = $(this).find(':selected').data(
                'faculty_type'); // this must still be text: 'Internal' or 'Guest'

            if (selectedType) {
                if (selectedType === 1) {
                    $('#faculty_type').val("1").trigger('change');
                } else if (selectedType === 2) {
                    $('#faculty_type').val("2").trigger('change');
                } else if (selectedType === 3) {
                    $('#faculty_type').val("3").trigger('change');
                } else {
                    $('#faculty_type').val("").trigger('change');
                }
            } else {
                $('#faculty_type').val("").trigger('change');
            }
        });

        // Now handle behavior based on the numeric faculty_type values
       $('#faculty_type').on('change', function () {
    let selectedVal = $(this).val();

    if (selectedVal === "1") { // Internal
        makeCheckboxReadonly('#remarkCheckbox', false, false); // enabled, unchecked
        makeCheckboxReadonly('#ratingCheckbox', true);         // readonly, unchecked
    } else if (selectedVal === "2") { // Guest
        makeCheckboxReadonly('#remarkCheckbox', false, true);  // enabled, checked
        makeCheckboxReadonly('#ratingCheckbox', false, true);  // enabled, checked
    } else {
        // Research or Other
        makeCheckboxReadonly('#remarkCheckbox', true);         // readonly, unchecked
        makeCheckboxReadonly('#ratingCheckbox', true);         // readonly, unchecked
    }
});

        // Trigger once to set initial state
        $('#faculty').trigger('change');
    });
    $('#Course_name').on('change', function() {
        var courseName = $(this).val();
        if (courseName) {
            $.ajax({
                url: "{{ route('calendar.get.group.types') }}",
                type: 'GET',
                data: {
                    course_id: courseName
                },
                success: function(response) {
                    // Step 1: Group by group_type_name
                    let groupedData = {};

                    response.forEach(item => {
                        if (!groupedData[item.group_type_name]) {
                            groupedData[item.group_type_name] = [];
                        }
                        groupedData[item.group_type_name].push(
                            item);
                    });
                       console.log('Group type changed 3');

                    // Step 2: Fill the dropdown with unique group_type_name
                    $('#group_type').empty().append(
                        '<option value="">Select Group Type</option>');
                    $('#type_name_container').html('');
                    for (const key in groupedData) {
                        if (groupedData[key].length > 0) {
                            const typeName = groupedData[key][0]
                                .type_name; // use first element's type_name
                            $('#group_type').append(
                                `<option value="${key}">${typeName}</option>`
                            );
                        }
                    }

                    $('#group_type').off('change').on('change', function() {
                           console.log('Group type changed 4');
                        const selectedType = $(this).val();
                        let html = '';
                        let groupNames = window.selectedGroupNames;

                        if (groupedData[selectedType]) {
                            // Agar create ke time hai, toh sab checked
                            let allChecked = groupNames === 'ALL';
                            groupedData[selectedType].forEach(group => {
                                let checked = '';
                                if (allChecked) {
                                    checked = 'checked';
                                } else if (Array.isArray(groupNames) &&
                                    groupNames.includes(group.pk)) {
                                    checked = 'checked';
                                }
                                html += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                            name="type_names[]" 
                            value="${group.pk}" 
                            id="type_${group.pk}" ${checked}>
                        <label class="form-check-label" for="type_${group.pk}">
                            ${group.group_name} (${group.type_name})
                        </label>
                    </div>
                `;
                            });
                        }

                        $('#type_name_container').html(html);
                    });
                }
            });
        } else {
            $('#group_type').empty().append(
                   console.log('Group type changed 5');
                '<option value="">Select Group Type</option>');
            $('#type_name_container').html('');
        }
    });

});
waitForGroupTypeAndSet(event.course_group_type_master, function() {
    let groupNames = [];
    try {
        groupNames = JSON.parse(event.group_name || '[]');
    } catch (e) {}
    window.selectedGroupNames = groupNames; // <-- Set here for edit
    $('#group_type').trigger('change');
});
</script>
<script>
// $('.btn-update-event').on('click', function() {
//     $('#eventForm').submit();
// });
$('#eventForm').on('submit', function(e) {
    e.preventDefault();

    let isValid = true;
    let errorMsg = "";
    const courseName = $('#Course_name').val();
    const subjectName = $('#subject_name').val();
    const subjectModule = $('#subject_module').val();
    const faculty = $('#faculty').val();
    const facultyType = $('#faculty_type').val();
    const vanue = $('#vanue').val();
    const shift_type = $('[name="shift_type"]').val();


    const topic = $('#topic').val();
    if (!courseName) {
        alert("Please select a Course Name.");
        $('#Course_name').focus();
        return false;
    }
    if (!subjectName) {
        alert("Please select a Subject Name.");
        $('#subject_name').focus();
        return false;
    }
    // if (!subjectModule) {
    //     alert("Please select a Subject Module.");
    //     $('#subject_module').focus();
    //     return false;
    // }
    if (!topic) {
        alert("Please Enter topic.");
        $('#topic').focus();
        return false;
    }
    if (!faculty) {
        alert("Please select a Faculty.");
        $('#faculty').focus();
        return false;
    }
    if (!facultyType) {
        alert("Please select Faculty Type.");
        $('#faculty_type').focus();
        return false;
    }
    if (!vanue) {
        alert("Please select a Venue.");
        $('#vanue').focus();
        return false;
    }


    if (!shift_type) {
        alert("Please select a shift.");
        $('[name="shift_type"]').focus();
        return false;
    }
    // Shift type specific validations
    if ($('#normalShift').is(':checked')) {
        const shift = $('#shift').val();
        if (!shift) {
            $('#shift').addClass('is-invalid');
            $('#shift').next('.text-danger').text("Please select a Shift..");
            isValid = false;
        }
    } else if ($('#manualShift').is(':checked')) {
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();

        if (!startTime) {
            $('#start_time').addClass('is-invalid');
            $('#start_time').next('.text-danger').text("Start Time is required.");
            isValid = false;
        }
        if (!endTime) {
            $('#end_time').addClass('is-invalid');
            $('#end_time').next('.text-danger').text("End Time is required.");
            isValid = false;
        }
    }
    if ($('#feedback_checkbox').is(':checked')) {
        if (!$('#remarkCheckbox').is(':checked') && !$('#ratingCheckbox').is(':checked')) {
            alert("Please select at least Remark or Rating when Feedback is checked.");
            $('#remarkCheckbox').focus();
            return false;
        }
    }

   if ($('#fullDayCheckbox').is(':checked')) {

    let start_date = $('#start_datetime').val(); // format: "YYYY-MM-DD"
    let start_time = $('#start_time').val();     // format: "HH:MM"

    if (start_date && start_time) {
        // Combine date and time into one Date object
        let selectedDateTime = new Date(start_date + 'T' + start_time + ':00');

        // Get current time + 1 hour
        let now = new Date();
        now.setHours(now.getHours() + 1);

        // Compare
        if (selectedDateTime < now) {
            alert("Start Date & Time must be at least 1 hour ahead of current time.");
            $('#start_time').focus();
            return false;
        }
    }
}

    let formData = new FormData(this);
    $('input[name="group_type_name[]"]:checked').each(function() {
        formData.append('group_type_name[]', $(this).val());
    });
    $.ajax({
        url: "{{ route('calendar.event.store') }}",
        method: "POST",
        data: $(this).serialize(),
        success: function(response) {
            alert("Event created successfully!");
            $('#eventModal').modal('hide');
            $('#eventForm')[0].reset();

            window.location.reload(); // now this will work
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                let messages = Object.values(errors).map(val => val.join('\n'))
                    .join('\n');
                alert("Server Validation Failed:\n\n" + messages);
            }
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    let calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '09:00:00', // Start time for week/day view
        slotMaxTime: '18:00:00', // End time for week/day view
        editable: true,
        selectable: true,
        displayEventTime: false,
        eventTimeFormat: false,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false
        },
        selectAllow: function(selectInfo) {
            let today = new Date();
            today.setHours(0, 0, 0, 0); // remove time for accuracy

            let selectedDate = new Date(selectInfo.start);
            selectedDate.setHours(0, 0, 0, 0);
            return selectedDate >= today;
        },
        events: '/calendar/full-calendar-details', // Data fetch karna
        eventContent: function(arg) {
            // Get custom fields
            const topic = arg.event.title || '';
            const venue = arg.event.extendedProps.vanue || '';
            const start = arg.event.start ? new Date(arg.event.start).toLocaleDateString() : '';

            // Design: topic (bold), venue (italic), start, end (each on new line)
            let html = `
        <div class="text-start p-2 text-dark fs-6 text-truncate">
            <span>${topic}</span><br>
            <span>${venue}</span><br>
            <span>Date: ${start}</span><br>
        </div>
    `;
            return {
                html: html
            };
        },
        eventClick: function(info) {
                
            let eventId = info.event.id;
            $.ajax({
                url: '/calendar/single-calendar-details?id=' + eventId, // ✅ Fix here
                type: 'GET',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
   

                 
                    $('#eventTopic').text(data.topic ?? '');
                   const startDate = new Date(data.start).toLocaleDateString();

                    const endDate = new Date(data.end).toLocaleString();
                    $('#eventDate').html(
                        `<b>Date:</b> ${startDate}`);
                    $('#eventfaculty').text(data.faculty_name ?? '');
                    $('#eventVanue').text(data.venue_name ?? '');
                    $('#editEventBtn').attr('data-id', data.id);
                    $('#deleteEventBtn').attr('data-id', data.id);

                    
                    $('#editEventBtn')
                        .off('click') // ✅ Remove any old handler
                        .click(function() {
                            $('#eventDetails').modal('hide');
                            $('#eventForm')[0].reset();
                            const eventId = $(this).attr(
                                'data-id'); // or .data('id')
                            $.ajax({
                                url: '/calendar/event-edit/' + eventId,
                                type: 'GET',
                                success: function(event) {
                                    // Set Course and Subject
                                    $('#Course_name').val(event
                                        .course_master_pk);
                                    $('#subject_module').val(event
                                        .subject_module_master_pk);
                                    // Subject Module ko AJAX se reload karo
                                    if (event
                                        .subject_module_master_pk) {
                                        $.ajax({
                                            url: "{{ route('calendar.get.subject.name') }}",
                                            type: 'GET',
                                            data: {
                                                data_id: event
                                                    .subject_module_master_pk
                                            },
                                            success: function(
                                                response) {
                                                $('#subject_name')
                                                    .empty()
                                                    .append(
                                                        '<option value="">Select Subject Module</option>'
                                                    );
                                                $.each(response,
                                                    function(
                                                        key,
                                                        module
                                                    ) {
                                                        $('#subject_name')
                                                            .append(
                                                                '<option value="' +
                                                                module
                                                                .pk +
                                                                '">' +
                                                                module
                                                                .subject_name +
                                                                '</option>'
                                                            );
                                                    });
                                                $('#subject_name')
                                                    .val(
                                                        event
                                                        .subject_master_pk
                                                    );
                                            }
                                        });
                                    } else {
                                        $('#subject_name').empty()
                                            .append(
                                                '<option value="">Select Subject Name</option>'
                                            );
                                    }
                                    $('#Course_name').val(event
                                        .course_master_pk).trigger(
                                        'change');
                                    waitForGroupTypeAndSet(event
                                        .course_group_type_master,
                                        function() {
                                            // Checkboxes set karo
                                            let groupNames = [];
                                            try {
                                                groupNames = JSON
                                                    .parse(event
                                                        .group_name ||
                                                        '[]');
                                            } catch (e) {}
                                            groupNames.forEach(
                                                function(pk) {
                                                    $('#type_' +
                                                            pk)
                                                        .prop(
                                                            'checked',
                                                            true
                                                        );
                                                });
                                        });
                                    $('#topic').val(event
                                        .subject_topic);
                                        $('#start_datetime').val(
                                        event.START_DATE);
                                    $('#faculty').val(event
                                        .faculty_master);
                                    $('#faculty_type').val(event
                                        .faculty_type);
                                    $('#vanue').val(event.venue_id);
                                    $('#shift').val(event
                                        .class_session);
                                    $('#normalShift').prop('checked',
                                        event
                                        .session_type == 1);
                                    $('#manualShift').prop('checked',
                                        event
                                        .session_type == 2);
                                        if(event
                                        .session_type == 2) {
                                        $('#shiftSelect').hide();
                                        $('#manualShiftFields').show();
                                    } else {
                                        $('#shiftSelect').show();
                                        $('#manualShiftFields').hide();
                                    }
                                   if (event.class_session && event.session_type == 2) {
                                        const times = event.class_session.split(" - ");
                                        if (times.length === 2) {
                                            const start24 = convertTo24Hour(times[0].trim()); // e.g., "09:30 AM" → "09:30"
                                            const end24 = convertTo24Hour(times[1].trim());   // e.g., "05:30 PM" → "17:30"
                                            $('#start_time').val(start24);
                                            $('#end_time').val(end24);
                                        }
                                    }
                                    $('#fullDayCheckbox').prop(
                                        'checked', event
                                        .full_day == 1);
                                    $('#feedback_checkbox').prop(
                                        'checked', event
                                        .feedback_checkbox == 1);
                                    $('#remarkCheckbox').prop('checked',
                                        event
                                        .Remark_checkbox == 1);
                                    $('#ratingCheckbox').prop('checked',
                                        event
                                        .Ratting_checkbox == 1);
                                    $('#bio_attendanceCheckbox').prop(
                                        'checked', event
                                        .Bio_attendance == 1);
                                    $('#eventModalLabel').text(
                                        'Edit Calendar Event');
                                    $('.btn-update-event')
                                        .show()
                                        .data('id', event
                                            .pk) // JS memory ke liye
                                        .attr('data-id', event
                                            .pk
                                        );
                                    $('#start_datetime').prop(
                                        'readonly', false);
                                    $('.btn-add-event').hide();
                                    $('#eventModal').modal('show');
                                    $('#fullDayCheckbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#start_time').val('08:00');
                    $('#end_time').val('20:00');
                } else {
                    $('#start_time').val('');
                    $('#end_time').val('');
                }
            });
                                }
                            });
                        });
                    $('#eventDetails').modal('show');
                }
            });
        },
        select: function(info) {
             $('#eventModalLabel').text('Add Calendar Event');
             
            // Reset form
            $('#eventForm')[0].reset();
            $('#shiftSelect').show();
            $('#manualShiftFields').hide();
            $('.btn-update-event').hide().removeAttr('data-id');
            $('#group_type').empty().append('<option value="">Select Group Type</option>');
            $('#type_name_container').html('');
            $('.btn-add-event').show();
            window.selectedGroupNames = 'ALL';
            // Format date to "YYYY-MM-DDTHH:MM" for input[type="datetime-local"]
            let selectedDate = new Date(info.start);
            let year = selectedDate.getFullYear();
            let month = ("0" + (selectedDate.getMonth() + 1)).slice(-2);
            let day = ("0" + selectedDate.getDate()).slice(-2);
            let formattedDate = `${year}-${month}-${day}`;

            let startDateTime = `${formattedDate}`;
            
            $('#start_datetime').val(startDateTime);
            $('#start_datetime').prop('readonly', true);
            $('#eventModal').modal('show');
            $('#fullDayCheckbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#start_time').val('08:00');
                    $('#end_time').val('20:00');
                } else {
                    $('#start_time').val('');
                    $('#end_time').val('');
                }
            });
        },
        eventRender: function(info) {
         }
    });
    calendar.render();
// View switch handlers
    document.getElementById('btnMonthView').addEventListener('click', function() {
        calendar.changeView('dayGridMonth');
        document.getElementById('calendar').style.display = '';
        document.getElementById('eventListView').style.display = 'none';
    });
    document.getElementById('btnWeekView').addEventListener('click', function() {
        calendar.changeView('timeGridWeek');
        document.getElementById('calendar').style.display = '';
        document.getElementById('eventListView').style.display = 'none';
    });
    document.getElementById('btnDayView').addEventListener('click', function() {
        calendar.changeView('timeGridDay');
        document.getElementById('calendar').style.display = '';
        document.getElementById('eventListView').style.display = 'none';
    });

    // List View handler
    document.getElementById('btnListView').addEventListener('click', function() {
        document.getElementById('calendar').style.display = 'none';
        document.getElementById('eventListView').style.display = '';
        let events = calendar.getEvents();
        let tbody = document.querySelector('#eventListTable tbody');
        tbody.innerHTML = '';
        if (events.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No events found.</td></tr>';
        } else {
            events.forEach(function(event) {
                let title = event.title || '';
                let startDate = event.start ? event.start.toLocaleDateString() : '';
                let startTime = event.start ? event.start.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '';
                let endTime = event.end ? event.end.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '';
                let venue = event.extendedProps.vanue || '';
                let faculty = event.extendedProps.faculty_name || '';
                let topic = event.extendedProps.topic || '';
                let row = `<tr>
                    <td>${title}</td>
                    <td>${startDate}</td>
                    <td>${startTime}</td>
                    <td>${endTime}</td>
                    <td>${venue}</td>
                    <td>${faculty}</td>
                    <td>${topic}</td>
                </tr>
                  <tr>
                    <td rowspan="2">9:30 to 10:20</td>
                    <td>A</td>
                    <td rowspan="2">Recruitment in state (SPM) (Full Group) (0930-1030 hrs)</td>
                    <td rowspan="2">E-7 <br> Economics Growth (Rajan Govil) (Full Group) (SN)</td>
                    <td rowspan="2">E-13 <br> Case Study : The Global Financial Crisis (Rajan Govil) (Full Group) (AC)</td>
                    <td rowspan="2">Election - 1 Overview of Elections (SW)</td>
                    <td rowspan="2">Election - 6 Model Code of Conduct & Election Expenditure (GSM) (Full Group</td>
                </tr>
                <tr>
                    <td>B</td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }
    });
    // ...existing code...

    $(document).on('click', '#deleteEventBtn', function() {
        let eventId = $(this).attr('data-id');
        if (!eventId) {

            alert('Event ID not found!');
            return;
        }
        if (confirm('Are you sure you want to delete this event?')) {
            $.ajax({
                url: '/calendar/event-delete/' + eventId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert('Event deleted successfully!');
                    $('#eventDetails').modal('hide');
                    // Calendar ko refresh karo
                    let calendarEl = document.getElementById('calendar');
                    if (calendarEl && calendarEl._fullCalendar) {
                        calendarEl._fullCalendar.refetchEvents();
                    } else {
                        location.reload();
                    }
                },
                error: function() {
                    alert('Delete failed!');
                }
            });
        }
    });

});
$(document).on('click', '.btn-update-event', function(e) {
    e.preventDefault();
    let eventId = $(this).data('id');
    if (!eventId) return alert('Event ID not found!');


    e.preventDefault();
    let isValid = true;
    let errorMsg = "";
    const courseName = $('#Course_name').val();
    const subjectName = $('#subject_name').val();
    const subjectModule = $('#subject_module').val();
    const faculty = $('#faculty').val();
    const facultyType = $('#faculty_type').val();
    const vanue = $('#vanue').val();
    const shift = $('#shift').val();
    const topic = $('#topic').val();
    if (!topic) {
        alert("Please Enter topic.");
        $('#topic').focus();
        return false;
    }
    if (!courseName) {
        alert("Please select a Course Name.");
        $('#Course_name').focus();
        return false;
    }
    if (!subjectName) {
        alert("Please select a Subject Name.");
        $('#subject_name').focus();
        return false;
    }
    if (!subjectModule) {
        alert("Please select a Subject Module.");
        $('#subject_module').focus();
        return false;
    }
    if (!faculty) {
        alert("Please select a Faculty.");
        $('#faculty').focus();
        return false;
    }
    if (!facultyType) {
        alert("Please select Faculty Type.");
        $('#faculty_type').focus();
        return false;
    }
    if (!vanue) {
        alert("Please select a Venue.");
        $('#vanue').focus();
        return false;
    }
    if ($('#normalShift').is(':checked')) {
        const shift = $('#shift').val();
        if (!shift) {
            $('#shift').addClass('is-invalid');
            $('#shift').next('.text-danger').text("Please select a Shift..");
            isValid = false;
        }
    } else if ($('#manualShift').is(':checked')) {
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();

        if (!startTime) {
            $('#start_time').addClass('is-invalid');
            $('#start_time').next('.text-danger').text("Start Time is required.");
            isValid = false;
        }
        if (!endTime) {
            $('#end_time').addClass('is-invalid');
            $('#end_time').next('.text-danger').text("End Time is required.");
            isValid = false;
        }
    }
    let startDate = $('#start_datetime').val().trim();
    if (startDate === "") {
        alert("Start Date is required.");
        $('#start_datetime').focus();
        return false;
    }
 let now = new Date();
let start = new Date(startDate);

// Remove time part from both to compare only dates
let today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
let selectedDate = new Date(start.getFullYear(), start.getMonth(), start.getDate());

// Compare dates
if (selectedDate < today) {
    alert("Start Date cannot be in the past.");
    $('#start_datetime').focus();
    return false;
}

    // Check if end date is before start date
  
   if ($('#fullDayCheckbox').is(':checked')) {

    let start_date = $('#start_datetime').val(); // format: "YYYY-MM-DD"
    let start_time = $('#start_time').val();     // format: "HH:MM"

    if (start_date && start_time) {
        // Combine date and time into one Date object
        let selectedDateTime = new Date(start_date + 'T' + start_time + ':00');

        // Get current time + 1 hour
        let now = new Date();
        now.setHours(now.getHours() + 1);

        // Compare
        if (selectedDateTime < now) {
            alert("Start Date & Time must be at least 1 hour ahead of current time.");
            $('#start_time').focus();
            return false;
        }
    }
}


    if ($('#feedback_checkbox').is(':checked')) {
        if (!$('#remarkCheckbox').is(':checked') && !$('#ratingCheckbox').is(':checked')) {
            alert("Please select at least Remark or Rating when Feedback is checked.");
            $('#remarkCheckbox').focus();
            return false;
        }
    }
    $.ajax({
        url: '/calendar/event-update/' + eventId,
        method: 'POST',
        data: $('#eventForm').serialize() + '&_method=PUT',
        success: function() {
            alert('Event updated successfully!');
            $('#eventModal').modal('hide');
            $('#eventForm')[0].reset();
            location.reload();
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                alert("Server Validation Failed:\n\n" + Object.values(errors).map(val => val.join(
                    '\n')).join('\n'));
            } else {
                alert('Update failed!');
            }
        }
    });
});

function waitForGroupTypeAndSet(value, callback, retries = 20) {
    if ($('#group_type option[value="' + value + '"]').length > 0) {
        $('#group_type').val(value).trigger('change');
        if (callback) callback();
    } else if (retries > 0) {
        setTimeout(function() {
            waitForGroupTypeAndSet(value, callback, retries - 1);
        }, 150);
    }
}
$(document).on('click', '#createEventupperButton', function() {
    $('#eventModalLabel').text('Add Calendar Event');
    $('#eventForm')[0].reset();
    $('#start_datetime').prop('readonly', false);
    $('#shiftSelect').show();
    $('#manualShiftFields').hide();
    $('.btn-update-event').hide().removeAttr('data-id');
    $('#group_type').empty().append('<option value="">Select Group Type</option>');
    $('#type_name_container').html('');

    $('.btn-add-event').show();
    window.selectedGroupNames = 'ALL';
    // Format date to "YYYY-MM-DDTHH:MM" for input[type="datetime-local"]
              $('#fullDayCheckbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#start_time').val('08:00');
                    $('#end_time').val('20:00');
                } else {
                    $('#start_time').val('');
                    $('#end_time').val('');
                }
            });
    $('#eventModal').modal('show');




});
function convertTo24Hour(timeStr) {
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
function makeCheckboxReadonly(selector, isReadonly, isChecked = false) {
    const checkbox = $(selector);
    checkbox.prop('checked', isChecked);

    if (isReadonly) {
        checkbox.on('click.readonly', function(e) { e.preventDefault(); });
        checkbox.addClass('readonly-checkbox');
    } else {
        checkbox.off('click.readonly');
        checkbox.removeClass('readonly-checkbox');
    }
}
  const dateInput = document.getElementById('start_datetime');
  const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
  dateInput.setAttribute('min', today);
</script>
@endsection