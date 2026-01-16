<!-- DataTables CSS/JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@extends('admin.layouts.master')
@section('title', 'Peer Evaluation - Admin Panel | Sargam Admin')
@section('setup_content')
<style>
    /* ✅ Improve focus visibility */
    .table a:focus, .btn:focus, .form-check-input:focus {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
        box-shadow: none !important;
    }

    /* ✅ Custom toggle color for clarity */
    .form-switch .form-check-input:checked {
        background-color: #198754 !important; /* Bootstrap success green */
        border-color: #198754 !important;
    }

    /* ✅ Hover feedback for rows */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    /* ✅ High contrast for badges */
    .badge.bg-info { color: #000; }
</style>

<div class="container-fluid">
     <x-breadcrum title="Peer Evaluation - Admin Panel" />
    <div class="card p-3" style="border-left: 4px solid #004a93;">
        <h4 class="mb-4">Peer Evaluation - Admin Panel</h4>

        {{-- Manage Courses Section --}}
        <div class="mb-4">
            <h5>Manage Courses</h5>
            <div class="input-group mb-3">
                <input type="text" id="course_name" class="form-control" placeholder="Enter Course Name">
                <button class="btn btn-info" id="addCourseBtn">Add Course</button>
            </div>
			<div class="input-group mb-3">
			<div id="courseMessage" class="small mt-1" style="display:none;"></div>
			</div>

            {{-- Courses List --}}
            <div class="mt-3">
    <h6>Existing Courses:</h6>

    <div class="accordion" id="coursesAccordion">
        @foreach ($courses as $index => $course)
            <div class="accordion-item mb-2">
                <h2 class="accordion-header" id="heading{{ $course->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse{{ $course->id }}" aria-expanded="false"
                        aria-controls="collapse{{ $course->id }}">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <strong>{{ $course->course_name }}</strong>
                            <div>
                                <span class="badge bg-primary">{{ $course->events_count }} Events</span>
                                <span class="badge bg-secondary ms-1">{{ $course->groups_count }} Groups</span>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapse{{ $course->id }}" class="accordion-collapse collapse"
                    aria-labelledby="heading{{ $course->id }}" data-bs-parent="#coursesAccordion">
                    <div class="accordion-body">

                        {{-- Add Event to this Course --}}
                        <div class="input-group input-group-sm mb-3">
                            <input type="text" class="form-control event-input"
                                placeholder="Add Event to {{ $course->course_name }}"
                                data-course-id="{{ $course->id }}">
                            <button class="btn btn-outline-primary add-event-btn"
                                data-course-id="{{ $course->id }}">Add Event</button>
                        </div>

                        {{-- Events List --}}
                        @foreach ($course->events as $event)
                            <div
                                class="mb-2 p-2 border rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $event->event_name }}</strong>
                                    <span
                                        class="badge bg-secondary ms-2">{{ $event->groups->count() }} Groups</span>
                                </div>
                                <small class="text-muted">Event ID: {{ $event->id }}</small>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

        </div>

        {{-- Manage Groups Section --}}
        <div class="mb-4">
            <h5>Manage Groups</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <select class="form-control" id="group_course_id">
                        <option value="">Select Course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event</label>
                    <select class="form-control" id="group_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Group Name</label>
                    <input type="text" id="group_name" class="form-control" placeholder="Group Name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Marks</label>
                    <input type="number" id="max_marks" class="form-control" placeholder="Max Marks" value="10"
                        step="0.01" min="1" max="100">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-success" id="addGroupBtn">Add Group</button>
                </div>
            </div>

            {{-- Groups List --}}
            <div class="mt-3">
                <h6>Groups List:</h6>
                <div class="table-responsive mt-3">
    <table class="table table-hover align-middle shadow-sm rounded-3 overflow-hidden" id="datatable-courses">
        <thead class="bg-primary text-white">
            <tr>
                <th scope="col">Course</th>
                <th scope="col">Event</th>
                <th scope="col">Group Name</th>
                <th scope="col">Max Marks</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-center">Members</th>
                <th scope="col" class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                <tr>
                    <td>
                        <span class="badge bg-info text-dark fw-semibold px-2 py-1">
                            {{ $group->course->course_name ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary fw-semibold px-2 py-1">
                            {{ $group->event->event_name ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="fw-medium text-dark">{{ $group->group_name }}</td>

                    <td>
                        <div class="d-flex align-items-center">
                            <label for="maxMarks{{ $group->id }}" class="visually-hidden">Max Marks</label>
                            <input type="number" id="maxMarks{{ $group->id }}"
                                class="form-control form-control-sm max-marks-input"
                                data-id="{{ $group->id }}" value="{{ $group->max_marks ?? 10 }}"
                                step="0.01" min="1" max="100" style="width: 90px;"
                                aria-label="Enter Max Marks">
                            <button class="btn btn-sm btn-outline-primary update-marks ms-2"
                                data-id="{{ $group->id }}" title="Save Marks" aria-label="Save Max Marks">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <div class="form-check form-switch m-0">
                                <input type="checkbox" role="switch" 
                                    class="form-check-input toggle-form"
                                    id="toggleForm{{ $group->id }}"
                                    data-id="{{ $group->id }}"
                                    {{ $group->is_form_active ? 'checked' : '' }}
                                    aria-label="Toggle Form Status">
                            </div>
                            <span class="badge {{ $group->is_form_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $group->is_form_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="badge bg-dark text-white fw-semibold px-2 py-1">
                            {{ $group->members_count }} Members
                        </span>
                    </td>

                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Group Actions">
                            <a href="{{ route('admin.peer.group.members', $group->id) }}"
                                class="btn btn-outline-info" title="View Members" aria-label="View Members">
                                <i class="fas fa-users"></i>
                            </a>
                            <a href="{{ route('admin.peer.group.import', $group->id) }}"
                                class="btn btn-outline-warning" title="Import Users" aria-label="Import Users">
                                <i class="fas fa-upload"></i>
                            </a>
                            <a href="{{ route('admin.peer.group.submissions', $group->id) }}"
                                class="btn btn-outline-primary" title="View Submissions" aria-label="View Submissions">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-outline-danger delete-group"
                                data-id="{{ $group->id }}" title="Delete Group" aria-label="Delete Group">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

            </div>
        </div>

        {{-- Manage Columns Section --}}
        <div class="mb-4">
            <h5>Manage Evaluation Columns</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Course (Optional)</label>
                    <select class="form-control" id="column_course_id">
                        <option value="">Global Column</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-control" id="column_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Column Name</label>
                    <input type="text" id="column_name" class="form-control" placeholder="Enter Column Name">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" id="addColumnBtn">Add Column</button>
                </div>
            </div>

            {{-- Columns List --}}
          <div class="mt-3">
    <h6>Existing Columns:</h6>
    <div class="table-responsive">
    <table class="table table-bordered align-middle" id="datatable-groups">
            <thead class="table-light">
                <tr>
                    <th scope="col">Column Name</th>
                    <th scope="col">Course</th>
                    <th scope="col">Event</th>
                    <th scope="col" class="text-center">Visible</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($columns as $column)
                    <tr>
                        <td>
                            <span
                                class="badge {{ $column->is_visible ? 'bg-success' : 'bg-secondary' }} me-1">
                                {{ $column->column_name }}
                            </span>
                        </td>
                        <td>
                            @if ($column->course)
                                <small class="text-muted">{{ $column->course->course_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if ($column->event)
                                <small class="text-muted">{{ $column->event->event_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-center">
    <div class="form-check form-switch d-inline-block">
        <input type="checkbox" 
            class="form-check-input toggle-column"
            data-id="{{ $column->id }}"
            id="toggleColumn{{ $column->id }}"
            {{ $column->is_visible ? 'checked' : '' }}
            title="Toggle Visibility">
    </div>
</td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-column"
                                data-id="{{ $column->id }}" title="Delete">
                                <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="fs-7">
                                                     </iconify-icon>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

        </div>

        {{-- Manage Reflection Fields --}}
        <div class="mb-4">
            <h5>Manage Reflection Fields</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Course (Optional)</label>
                    <select class="form-control" id="reflection_course_id">
                        <option value="">Global Field</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-control" id="reflection_event_id" disabled>
                        <option value="">Select Event</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reflection Field Name</label>
                    <input type="text" id="reflection_field" class="form-control"
                        placeholder="Enter Reflection Field Name">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary" id="addReflectionBtn">Add Field</button>
                </div>
            </div>  
            {{-- Reflection Fields List --}}
            <div class="mt-3">
                <h6>Existing Reflection Fields:</h6>
                <div class="row">
                    @php
                        $reflectionFields = DB::table('peer_reflection_fields as prf')
                            ->leftJoin('peer_courses as pc', 'prf.course_id', '=', 'pc.id')
                            ->leftJoin('peer_events as pe', 'prf.event_id', '=', 'pe.id')
                            ->select('prf.*', 'pc.course_name', 'pe.event_name')
                            ->get();
                    @endphp

                    <div class="mt-3">
    <h6>Existing Reflection Fields:</h6>
    <div class="table-responsive">
    <table class="table table-bordered align-middle" id="datatable-columns">
<script>
$(document).ready(function() {
    $('#datatable-courses').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: { previous: "Prev", next: "Next" }
        }
    });
    $('#datatable-groups').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10
    });
    $('#datatable-columns').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10
    });
});
</script>
            <thead class="table-light">
                <tr>
                    <th scope="col">Field Label</th>
                    <th scope="col">Course</th>
                    <th scope="col">Event</th>
                    <th scope="col" class="text-center">Active</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reflectionFields as $field)
                    <tr>
                        <td>
                            <span class="badge {{ $field->is_active ? 'bg-success' : 'bg-secondary' }} me-1">
                                {{ $field->field_label }}
                            </span>
                        </td>
                        <td>
                            @if ($field->course_name)
                                <small class="text-muted">{{ $field->course_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if ($field->event_name)
                                <small class="text-muted">{{ $field->event_name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-center">
    <div class="form-check form-switch d-inline-block">
        <input type="checkbox"
            class="form-check-input toggle-reflection"
            data-id="{{ $field->id }}"
            id="toggleReflection{{ $field->id }}"
            {{ $field->is_active ? 'checked' : '' }}
            title="Toggle Active">
    </div>
</td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-reflection"
                                data-id="{{ $field->id }}" title="Delete"><iconify-icon icon="solar:trash-bin-minimalistic-bold" class="fs-7">
                                                     </iconify-icon></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

                </div>
            </div>

            <div class="alert alert-info">
                <strong>Note:</strong> This is the admin panel for managing courses, events, groups and columns.
                Users will see the evaluation form on the user side.
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
        <script>
            $(document).ready(function() {
                // Load events when course is selected for groups
                $('#group_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#group_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Load events when course is selected for columns
                $('#column_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#column_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Load events when course is selected for reflection fields
                $('#reflection_course_id').change(function() {
                    const courseId = $(this).val();
                    const eventSelect = $('#reflection_event_id');

                    if (courseId) {
                        $.get('/admin/peer/events/' + courseId, function(events) {
                            eventSelect.empty().append('<option value="">Select Event</option>');
                            events.forEach(event => {
                                eventSelect.append(
                                    `<option value="${event.id}">${event.event_name}</option>`
                                );
                            });
                            eventSelect.prop('disabled', false);
                        }).fail(function() {
                            alert('Error loading events');
                        });
                    } else {
                        eventSelect.empty().append('<option value="">Select Event</option>').prop('disabled',
                            true);
                    }
                });

                // Add Course
				
               /* $('#addCourseBtn').click(function() {
                    const courseName = $('#course_name').val();
                    if (!courseName) {
                        alert('Please enter course name');
                        return;
                    }

                    $.post('{{ route('admin.peer.course.add') }}', {
                        _token: '{{ csrf_token() }}',
                        course_name: courseName
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding course');
                    });
                });
*/

function validateCourseName() {
    const courseName = $('#course_name').val().trim();

    if (!courseName) {
        $('#courseMessage')
            .removeClass('text-success')
            .addClass('text-danger')
            .text('Please enter course name')
            .show();
        return false;
    }

    $('#courseMessage').hide().text('');
    return true;
}


$('#course_name').on('keyup', function () {
    if ($(this).val().trim().length > 0) {
        $('#courseMessage').fadeOut();
    }
});

$('#course_name').on('blur', function () {
    validateCourseName();
});

$('#addCourseBtn').click(function () {

    const courseName = $('#course_name').val().trim();

    $('#courseMessage').hide().removeClass('text-success text-danger').text('');

   /* if (!courseName) {
        $('#courseMessage').addClass('text-danger')
            .text('Please enter course name')
            .show();
        return;
    }*/
	  if (!validateCourseName()) {
        return;
    }

    $.post('{{ route('admin.peer.course.add') }}', {
        _token: '{{ csrf_token() }}',
        course_name: courseName
    }, function (response) {

        if (response.success) {

          
            $('#courseMessage')
                .addClass('text-success')
                .text(response.message)
                .show();

            $('#course_name').val('');

            setTimeout(() => $('#courseMessage').fadeOut(), 3000);

           
            const course = response.course;

            const html = `
            <div class="accordion-item mb-2">
                <h2 class="accordion-header" id="heading${course.id}">
                    <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse${course.id}">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <strong>${course.course_name}</strong>
                            <div>
                                <span class="badge bg-primary">0 Events</span>
                                <span class="badge bg-secondary ms-1">0 Groups</span>
                            </div>
                        </div>
                    </button>
                </h2>

                <div id="collapse${course.id}" class="accordion-collapse collapse"
                    data-bs-parent="#coursesAccordion">
                    <div class="accordion-body">
                        <div class="input-group input-group-sm mb-3">
                            <input type="text" class="form-control event-input"
                                placeholder="Add Event to ${course.course_name}"
                                data-course-id="${course.id}">
                            <button class="btn btn-outline-primary add-event-btn"
                                data-course-id="${course.id}">
                                Add Event
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#coursesAccordion').prepend(html);
        }

    }).fail(function () {
        $('#courseMessage').addClass('text-danger')
            .text('Error adding course')
            .show();
    });
});



                // Add Event to Course
                //$('.add-event-btn').click(function() {
			$(document).on('click', '.add-event-btn', function () {
                    const courseId = $(this).data('course-id');
                    const eventInput = $(`.event-input[data-course-id="${courseId}"]`);
                    const eventName = eventInput.val();

                    if (!eventName) {
                        alert('Please enter event name');
                        return;
                    }

                    $.post('{{ route('admin.peer.event.add') }}', {
                        _token: '{{ csrf_token() }}',
                        event_name: eventName,
                        course_id: courseId
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding event');
                    });
                });

                // Add Group with Course and Event
                $('#addGroupBtn').click(function() {
                    const courseId = $('#group_course_id').val();
                    const eventId = $('#group_event_id').val();
                    const groupName = $('#group_name').val();
                    const maxMarks = $('#max_marks').val();

                    if (!courseId || !eventId || !groupName) {
                        alert('Please select course, event and enter group name');
                        return;
                    }

                    if (!maxMarks || maxMarks <= 0) {
                        alert('Please enter valid max marks');
                        return;
                    }

                    $.post('{{ route('admin.peer.group.add') }}', {
                        _token: '{{ csrf_token() }}',
                        course_id: courseId,
                        event_id: eventId,
                        group_name: groupName,
                        max_marks: maxMarks
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding group');
                    });
                });

                // Add Column with Course and Event
                $('#addColumnBtn').click(function() {
                    const courseId = $('#column_course_id').val();
                    const eventId = $('#column_event_id').val();
                    const columnName = $('#column_name').val();

                    if (!columnName) {
                        alert('Please enter column name');
                        return;
                    }

                    $.post('{{ route('admin.peer.column.add') }}', {
                        _token: '{{ csrf_token() }}',
                        course_id: courseId || null,
                        event_id: eventId || null,
                        column_name: columnName
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding column');
                    });
                });

                // Update Max Marks
                $('.update-marks').click(function() {
                    const groupId = $(this).data('id');
                    const input = $(`.max-marks-input[data-id="${groupId}"]`);
                    const maxMarks = input.val();

                    if (!maxMarks || maxMarks <= 0) {
                        alert('Please enter valid max marks');
                        return;
                    }

                    $.post('{{ route('admin.peer.groups.update-marks') }}', {
                        _token: '{{ csrf_token() }}',
                        group_id: groupId,
                        max_marks: parseFloat(maxMarks)
                    }, function(response) {
                        if (response.success) {
                            alert('Max marks updated successfully');
                            input.val(parseFloat(maxMarks));
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error updating max marks');
                    });
                });

                // Toggle Form Status
                $('.toggle-form').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');
                    const isChecked = checkbox.is(':checked') ? 1 : 0;

                    const originalState = !isChecked;
                    checkbox.prop('disabled', true);

                    $.post('/admin/peer/group/toggle-form/' + id, {
                            _token: '{{ csrf_token() }}',
                            is_form_active: isChecked
                        })
                        .done(function(response) {
                            if (response.status === 'success') {
                                const badge = checkbox.closest('td').find('.badge');
                                if (badge.length) {
                                    if (isChecked) {
                                        badge.removeClass('bg-danger').addClass('bg-success').text(
                                            'Active');
                                    } else {
                                        badge.removeClass('bg-success').addClass('bg-danger').text(
                                            'Inactive');
                                    }
                                }
                            } else {
                                const errorMessage = response.message || 'Error updating form status';
                                alert(errorMessage);
                                checkbox.prop('checked', originalState);
                            }
                        })
                        .fail(function(xhr, status, error) {
                            let errorMessage = 'Error updating form status';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {}
                            alert(errorMessage);
                            checkbox.prop('checked', originalState);
                        })
                        .always(function() {
                            checkbox.prop('disabled', false);
                        });
                });

                // Delete Group
                $('.delete-group').click(function() {
                    if (confirm('Are you sure you want to delete this group?')) {
                        const button = $(this);
                        const id = button.data('id');

                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                        $.post('/admin/peer/group/delete/' + id, {
                                _token: '{{ csrf_token() }}'
                            })
                            .done(function(response) {
                                if (response.success) {
                                    alert(response.message || 'Group deleted successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + response.message);
                                    button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                                }
                            })
                            .fail(function(xhr, status, error) {
                                alert('Error deleting group: ' + error);
                                button.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                            });
                    }
                });

                // Toggle Column Visibility
                $('.toggle-column').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');

                    $.post('/admin/peer/column/toggle/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            checkbox.prop('checked', !checkbox.is(':checked'));
                        }
                    }).fail(function() {
                        alert('Error updating column visibility');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    });
                });

                // Delete Column
                $('.delete-column').click(function() {
                    if (confirm('Are you sure you want to delete this column?')) {
                        const id = $(this).data('id');
                        $.post('/admin/peer/column/delete/' + id, {
                            _token: '{{ csrf_token() }}'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }).fail(function() {
                            alert('Error deleting column');
                        });
                    }
                });

                // Add Reflection Field with Course and Event
                $('#addReflectionBtn').click(function() {
                    const courseId = $('#reflection_course_id').val();
                    const eventId = $('#reflection_event_id').val();
                    const fieldLabel = $('#reflection_field').val();

                    if (!fieldLabel) {
                        alert('Please enter reflection field label');
                        return;
                    }

                    $.post('{{ route('admin.peer.reflection.add') }}', {
                        _token: '{{ csrf_token() }}',
                        field_label: fieldLabel,
                        course_id: courseId || null,
                        event_id: eventId || null
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }).fail(function() {
                        alert('Error adding reflection field');
                    });
                });

                // Toggle Reflection Field
                $('.toggle-reflection').change(function() {
                    const checkbox = $(this);
                    const id = checkbox.data('id');

                    $.post('/admin/peer/reflection/toggle/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function(response) {
                        if (response.success) {
                            const badge = checkbox.closest('.card').find('.badge');
                            if (response.new_state) {
                                badge.removeClass('bg-secondary').addClass('bg-success');
                            } else {
                                badge.removeClass('bg-success').addClass('bg-secondary');
                            }
                        } else {
                            alert('Error: ' + response.message);
                            checkbox.prop('checked', !checkbox.is(':checked'));
                        }
                    }).fail(function() {
                        alert('Error updating reflection field');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    });
                });

                // Delete Reflection Field
                $('.delete-reflection').click(function() {
                    if (confirm('Are you sure you want to delete this reflection field?')) {
                        const button = $(this);
                        const id = button.data('id');

                        $.post('/admin/peer/reflection/delete/' + id, {
                            _token: '{{ csrf_token() }}'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }).fail(function() {
                            alert('Error deleting reflection field');
                        });
                    }
                });
            });
        </script>

        <style>
            .card-header {
                background: linear-gradient(45deg, #007bff, #0056b3);
                color: white;
            }

            .table th {
                vertical-align: middle;
            }

            .badge {
                font-size: 0.8em;
            }

            .btn-group-sm>.btn {
                padding: 0.25rem 0.5rem;
            }

            .event-input,
            .course-input {
                max-width: 300px;
            }
        </style>
    @endsection
