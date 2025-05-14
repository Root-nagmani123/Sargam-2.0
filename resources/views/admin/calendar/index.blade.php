@extends('admin.layouts.master')

@section('title', 'Calendar - Sargam | Lal Bahadur')

@section('content')

 <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<div class="container-fluid">
    <div class="card card-body py-3">
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

    <div class="card">
        <div class="card-body calender-sidebar app-calendar">
             <div id='calendar'></div>
        </div>
    </div>
    <!-- BEGIN MODAL -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true"
        style="display: none;">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
              <form id="eventForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        {{ $modalTitle ?? __('Add / Edit Calendar Event') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Course name</label>
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
                                <label class="form-label">Subject Name</label>
                                <select name="subject_name" id="subject_name" class="form-control">
                                    <option value="">Select Subject Name</option>
                                    @foreach($subjects as $subject)
                                    <option value="{{ $subject->pk }}"
                                        data-id="{{ $subject->subject_module_master_pk }}">{{ $subject->subject_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subject Module</label>
                                <select name="subject_module" id="subject_module" class="form-control">
                                    <option value="">Select subject Module</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Topic</label>
                                <textarea name="topic" id="topic" class="form-control" row="5"></textarea>

                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div>
                                <label class="form-label">Group Type</label>
                            </div>
                            <div class="d-flex">
                                <div class="n-chk">
                                    <div class="form-check form-check-primary form-check-inline">
                                        <input class="form-check-input" type="radio" name="event_level" value="1"
                                            id="modalDanger-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalDanger-{{ uniqid() }}">Lecture</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-warning form-check-inline">
                                        <input class="form-check-input" type="radio" name="event_level" value="2"
                                            id="modalSuccess-{{ uniqid() }}">
                                        <label class="form-check-label"
                                            for="modalSuccess-{{ uniqid() }}">Language</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-success form-check-inline">
                                        <input class="form-check-input" type="radio" name="event_level" value="3"
                                            id="modalPrimary-{{ uniqid() }}">
                                        <label class="form-check-label"
                                            for="modalPrimary-{{ uniqid() }}">Counsellar</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-danger form-check-inline">
                                        <input class="form-check-input" type="radio" name="event_level" value="4"
                                            id="modalWarning-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalWarning-{{ uniqid() }}">Module</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-danger form-check-inline">
                                        <input class="form-check-input" type="radio" name="event_level" value="5"
                                            id="modalWarning-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalWarning-{{ uniqid() }}">Custom
                                            Group</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Faculty</label>
                                <select name="faculty" id="faculty" class="form-control">
                                    <option value="">Select Faculty</option>
                                    @foreach($facultyMaster as $faculty)
                                    <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                        {{ $faculty->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Faculty Type</label>
                                <select name="faculty_type" id="faculty_type" class="form-control">
                                    <option value="">Select Faculty Type</option>
                                    <option value="1">Internal</option>
                                    <option value="2">Guest</option>
                                    <option value="3">Research</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select name="vanue" id="vanue" class="form-control">
                                    <option value="">Select Location</option>
                                    @foreach($venueMaster as $loc)
                                    <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Shift</label>
                                <select name="shift" id="shift" class="form-control">
                                    <option value="">Select Shift</option>
                                    @foreach($classSessionMaster as $shift)
                                    <option value="{{ $shift->pk }}">{{ $shift->shift_name }}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox" name="fullDayCheckbox">
                                <label class="form-check-label" for="fullDayCheckbox">Full Day</label>
                            </div>

                            <div id="dateTimeFields">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_datetime" class="form-label">Start</label>
                                        <input type="datetime-local" name="start_datetime" id="start_datetime"
                                            class="form-control" >
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_datetime" class="form-label">End</label>
                                        <input type="datetime-local" name="end_datetime" id="end_datetime"
                                            class="form-control" >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div>
                                <label class="form-label">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="feedback_checkbox" id="feedback_checkbox">
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
                                        <input class="form-check-input" type="checkbox" value="1" name="remarkCheckbox" id="remarkCheckbox">
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
                                        <input class="form-check-input" type="checkbox" value="1" name="ratingCheckbox" id="ratingCheckbox">
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
                                        <input class="form-check-input" type="checkbox" value="1" name="bio_attendanceCheckbox" id="bio_attendanceCheckbox">
                                        <label class="form-check-label" for="bio_attendanceCheckbox">
                                            Bio Attendance
                                        </label>
                                    </div>
                                </label>

                            </div>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-success btn-update-event"
                        data-fc-event-public-id="{{ $event->public_id ?? '' }}" style="display: none;">
                        Update changes
                    </button>
                    <button type="submit" class="btn btn-primary btn-add-event">
                        Add Calendar Event
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
    <!-- END MODAL -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#fullDayCheckbox').on('change', function () {
        if ($(this).is(':checked')) {
            $('#dateTimeFields').hide();
        } else {
            $('#dateTimeFields').show();
        }
    });
    $('#subject_name').on('change', function() {
        // Get data-id from selected option
        var dataId = $(this).find(':selected').data('id');

        if (dataId) {
            $.ajax({
                url: "{{ route('calendar.get.subject.modules') }}",
                type: 'GET',
                data: {
                    data_id: dataId
                },
                success: function(response) {
                    $('#subject_module').empty().append(
                        '<option value="">Select Subject Module</option>');
                    $.each(response, function(key, module) {
                        $('#subject_module').append('<option value="' + module.pk +
                            '">' + module.module_name + '</option>');
                    });
                }
            });
        } else {
            $('#subject_module').empty().append('<option value="">Select Subject Module</option>');
        }
    });

   $(document).ready(function () {
    // When faculty is selected, set the faculty_type based on its data attribute
    $('#faculty').on('change', function () {
        var selectedType = $(this).find(':selected').data('faculty_type'); // this must still be text: 'Internal' or 'Guest'

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
            $('#remarkCheckbox').prop('disabled', false);
            $('#ratingCheckbox').prop('disabled', true).prop('checked', false);
        } else if (selectedVal === "2") { // Guest
            $('#remarkCheckbox').prop('disabled', false).prop('checked', true);
            $('#ratingCheckbox').prop('disabled', false).prop('checked', true);
        } else {
            // For Research or other, disable both
            $('#remarkCheckbox').prop('disabled', true).prop('checked', false);
            $('#ratingCheckbox').prop('disabled', true).prop('checked', false);
        }
    });

    // Trigger once to set initial state
    $('#faculty').trigger('change');
});
});

</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fullDay = document.getElementById('fullDayCheckbox');
    const dtFields = document.getElementById('dateTimeFields');

    fullDay.addEventListener('change', () => {
        if (fullDay.checked) {
            // Hide and remove required validation
            dtFields.style.display = 'none';
            dtFields.querySelectorAll('input').forEach(i => i.required = false);
        } else {
            // Show and re-enable validation
            dtFields.style.display = 'block';
            dtFields.querySelectorAll('input').forEach(i => i.required = true);
        }
    });
});
  $('#eventForm').on('submit', function (e) {
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
         let fullDay = $('#fullDayCheckbox').is(':checked');

        // Check for empty values
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
        if (!shift) {
            alert("Please select a Shift.");
            $('#shift').focus();
            return false;
        }
           

          
            if (!fullDay) {
                let startDate = $('#start_datetime').val().trim();
                let endDate = $('#end_datetime').val().trim();

                if (startDate === "") {
                    alert("Start Date is required.");
                    $('#start_datetime').focus();
                    return false;
                   
                }

               
                if (endDate === "") {
                     alert("End Date is required.");
                    $('#end_datetime').focus();
                    return false;
                }

              
            }

         

            // If valid, do AJAX submission
            $.ajax({
                url: "{{ route('calendar.event.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    alert("Event created successfully!");
                    $('#eventModal').modal('hide');
                    $('#eventForm')[0].reset();
                    toggleDateTimeFields();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let messages = Object.values(errors).map(val => val.join('\n')).join('\n');
                        alert("Server Validation Failed:\n\n" + messages);
                    }
                }
            });
        });
        
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    let calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        editable: true,
        selectable: true,
         displayEventTime: false, 
        events: '/calendar/calendar-details', // Data fetch karna
        select: function (info) {
            $('#eventModal').modal('show');
        },
        eventRender: function(info) {
            // Custom rendering logic if needed, but normally FullCalendar should automatically handle color from JSON
        }
    });
    calendar.render();
});

    </script>
@endsection 