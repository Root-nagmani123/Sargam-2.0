
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@extends('admin.layouts.master')
@section('title', 'Manage Groups')
@section('setup_content')


<div class="container-fluid">
     <x-breadcrum title="Manage Groups" />
    <div class="card p-3" >

        {{-- Courses Section.
             Courses are course_master rows now and are created in Course Master,
             not here - peer_courses was retired by
             2026_08_24_000002_point_peer_evaluation_at_course_master. This lists
             the courses that already carry peer content; use Manage Events to
             attach an event to a course that isn't listed yet. --}}
        <div class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h5 class="mb-0">Courses</h5>
                <a href="{{ route('admin.peer.events.index') }}" class="btn btn-sm btn-outline-primary">
                    Manage Events
                </a>
            </div>

            {{-- Courses List --}}
            <div class="mt-3">
    <h6>Existing Courses: <span id="successMessage" class="text-success small ms-2" style="display:none;"></span></h6>

    <div class="accordion" id="coursesAccordion">
		@foreach ($courses as $course)
    <div class="accordion-item mb-2">

        <h2 class="accordion-header d-flex align-items-center justify-content-between px-3 py-2"
            id="heading{{ $course->pk }}">

            <!-- Accordion Toggle -->
            <button class="accordion-button collapsed flex-grow-1 me-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $course->pk }}"
                    aria-expanded="false"
                    aria-controls="collapse{{ $course->pk }}">
                <strong>{{ $course->course_name }}</strong>
			</button>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-2">

                <span class="badge bg-primary">{{ $course->events_count }} Events</span>
                <span class="badge bg-secondary">{{ $course->groups_count }} Groups</span>

            </div>
		
		

        </h2>

        <!-- COLLAPSE BODY -->
        <div id="collapse{{ $course->pk }}"
             class="accordion-collapse collapse"
             aria-labelledby="heading{{ $course->pk }}"
             data-bs-parent="#coursesAccordion">

            <div class="accordion-body">

                <!-- Add Event -->
                <div class="input-group input-group-sm mb-3">
                    <input type="text"
                           class="form-control event-input"
                           placeholder="Add Event to {{ $course->course_name }}"
                           data-course-id="{{ $course->pk }}">
                    <button class="btn btn-outline-primary add-event-btn"
                            data-course-id="{{ $course->pk }}">
                        Add Event
                    </button>
                </div>

                <!-- Events List -->
                @foreach ($course->peerEvents as $event)
                    <div class="mb-2 p-2 border rounded d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $event->event_name }}</strong>
                            <span class="badge bg-secondary ms-2">
                                {{ $event->groups->count() }} Groups
                            </span>
                        </div>
                        <small class="text-muted">Event ID: {{ $event->id }}</small>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endforeach
</div>
<div class="mt-3 d-flex justify-content-center">
     {{ $courses->onEachSide(1)->links('pagination::bootstrap-5') }}
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
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
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
                                class="form-control  max-marks-input"
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
                                data-id="{{ $group->id }}"
                                 title="Delete Group" aria-label="Delete Group">
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


            <div class="alert alert-info">
                <strong>Note:</strong> This page manages evaluation groups and their members. Events,
                evaluation columns and reflection fields each have their own screen under Peer Evaluation.
                Users will see the evaluation form on the user side.
            </div>
        </div>

       
      
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

                // Add Event (inline, per course)
                //$('.add-event-btn').click(function() {
			$(document).on('click', '.add-event-btn', function () {
    const courseId = $(this).data('course-id');
    const eventInput = $(`.event-input[data-course-id="${courseId}"]`);
    const eventName = eventInput.val();

    if (!eventName) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please enter event name'
        });
        return;
    }

    $.post('{{ route('admin.peer.event.add') }}', {
        _token: '{{ csrf_token() }}',
        event_name: eventName,
        course_id: courseId
    }, function (response) {
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Event added successfully',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message
            });
        }
    }).fail(function () {
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: 'Error adding event'
        });
    });
});


                // Add Group with Course and Event
                $('#addGroupBtn').click(function () {
                     
                        const courseId = $('#group_course_id').val();
                        const eventId = $('#group_event_id').val();
                        const groupName = $('#group_name').val();
                        const maxMarks = $('#max_marks').val();

                        if (!courseId || !eventId || !groupName) {
                            Swal.fire('Warning', 'Please select course, event and enter group name', 'warning');
                            return;
                        }

                        if (!maxMarks || maxMarks <= 0) {
                            Swal.fire('Warning', 'Please enter valid max marks', 'warning');
                            return;
                        }

                        $.post('{{ route('admin.peer.group.add') }}', {
                            _token: '{{ csrf_token() }}',
                            course_id: courseId,
                            event_id: eventId,
                            group_name: groupName,
                            max_marks: maxMarks
                        }, function (response) {

                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: 'Group added successfully',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }

                        }).fail(function () {
                            Swal.fire('Error', 'Error adding group', 'error');
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
		
		
<!-- Edit Course Modal -->

    @endsection
	
@section('scripts')

<script>
</script>

<script>
$(document).ready(function() {
	 setTimeout(() => {
  var table = $('#datatable-courses').DataTable({
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
    // There is no #datatable-groups element. The Groups List table below the
    // course accordion is the one initialised above - it carries the id
    // "datatable-courses" despite listing groups.
	
    // Closes the setTimeout arrow above. PRE-EXISTING BUG: the matching close for
    // $(document).ready() was missing, so this whole block was a syntax error and
    // none of these four DataTables ever initialised.
    }, 0);
});
</script>

@endsection


