@extends('admin.layouts.master')
@section('content')
    <div class="card p-3">
        <h4 class="mb-4">Peer Evaluation - Admin Panel</h4>

        {{-- Manage Events Section --}}
        <div class="mb-4">
            <h5>Manage Events & Courses</h5>
            <div class="input-group mb-3">
                <input type="text" id="event_name" class="form-control"
                    placeholder="Enter Event Name (e.g. Foundation Course)">
                <button class="btn btn-info" id="addEventBtn">Add Event</button>
            </div>

            {{-- Events List --}}
            <div class="mt-3">
                <h6>Existing Events & Courses:</h6>
                @foreach ($events as $event)
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>{{ $event->event_name }}</strong>
                            <div>
                                <span class="badge bg-primary">{{ $event->courses->count() }} Courses</span>
                                <span class="badge bg-secondary ms-1">{{ $event->groups->count() }} Groups</span>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Add Course to this Event --}}
                            <div class="input-group input-group-sm mb-3">
                                <input type="text" class="form-control course-input"
                                    placeholder="Add Course to {{ $event->event_name }}"
                                    data-event-id="{{ $event->id }}">
                                <button class="btn btn-outline-primary add-course-btn"
                                    data-event-id="{{ $event->id }}">Add Course</button>
                            </div>

                            {{-- Courses List --}}
                            @foreach ($event->courses as $course)
                                <div class="mb-2 p-2 border rounded d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $course->course_name }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ $course->groups_count ?? 0 }} Groups</span>
                                    </div>
                                    <small class="text-muted">Course ID: {{ $course->id }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Manage Groups Section --}}
        <div class="mb-4">
            <h5>Manage Groups</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Event</label>
                    <select class="form-control" id="group_event_id">
                        <option value="">Select Event</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <select class="form-control" id="group_course_id" disabled>
                        <option value="">Select Course</option>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-navbar-dark bg-dark text-white">
                            <tr>
                                <th>Event</th>
                                <th>Course</th>
                                <th>Group Name</th>
                                <th>Max Marks</th>
                                <th>Status</th>
                                <th>Members</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group)
                                <tr>
                                    <td>
                                        <span class="badge bg-info">{{ $group->event->event_name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $group->course->course_name ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $group->group_name }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <input type="number" class="form-control form-control-sm max-marks-input"
                                                data-id="{{ $group->id }}" value="{{ $group->max_marks ?? 10 }}"
                                                step="0.01" min="1" max="100" style="width: 80px;">
                                            <button class="btn btn-sm btn-outline-primary update-marks ms-2"
                                                data-id="{{ $group->id }}">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $group->is_form_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $group->is_form_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <input type="checkbox" class="toggle-form ms-2" data-id="{{ $group->id }}"
                                            {{ $group->is_form_active ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">{{ $group->members_count }} members</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.peer.group.members', $group->id) }}"
                                                class="btn btn-info" title="View Members">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="{{ route('admin.peer.group.import', $group->id) }}"
                                                class="btn btn-warning" title="Import Users">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                            <a href="{{ route('admin.peer.group.submissions', $group->id) }}"
                                                class="btn btn-primary" title="View Submissions">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-danger delete-group" data-id="{{ $group->id }}"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
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
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-control" id="column_event_id">
                        <option value="">Global Column</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course (Optional)</label>
                    <select class="form-control" id="column_course_id" disabled>
                        <option value="">Select Course</option>
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
                <div class="row">
                    @foreach ($columns as $column)
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span
                                                class="badge {{ $column->is_visible ? 'bg-success' : 'bg-secondary' }} me-1">
                                                {{ $column->column_name }}
                                            </span>
                                            {{-- Safe access to event and course --}}
                                            @if (isset($column->event_id) && $column->event_id)
                                                <small class="text-muted">
                                                    (Event ID: {{ $column->event_id }})
                                                </small>
                                            @endif
                                            @if (isset($column->course_id) && $column->course_id)
                                                <small class="text-muted">
                                                    - Course ID: {{ $column->course_id }}
                                                </small>
                                            @endif
                                        </div>
                                        <div>
                                            <input type="checkbox" class="toggle-column" data-id="{{ $column->id }}"
                                                {{ $column->is_visible ? 'checked' : '' }} title="Toggle Visibility">
                                            <button class="btn btn-sm btn-danger delete-column ms-1"
                                                data-id="{{ $column->id }}" title="Delete">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Manage Reflection Fields --}}
        <div class="mb-4">
            <h5>Manage Reflection Fields</h5>

            {{-- Event and Course Selection --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Event (Optional)</label>
                    <select class="form-control" id="reflection_event_id">
                        <option value="">Global Field</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Course (Optional)</label> {{-- Changed label from "Group" to "Course" --}}
                    <select class="form-control" id="reflection_course_id" disabled> {{-- Changed ID from reflection_column_id to reflection_course_id --}}
                        <option value="">Select Course</option> {{-- Changed from "Select Group" to "Select Course" --}}
                    </select>
                </div>
            </div>

            {{-- Add Reflection Field --}}
            <div class="input-group mb-3">
                <input type="text" id="reflection_field" class="form-control"
                    placeholder="Enter Reflection Field (e.g. Learning from the Paramilitary Forces)">
                <button class="btn btn-secondary" id="addReflectionBtn">Add Field</button>
            </div>

            {{-- Reflection Fields List --}}
            <div class="mt-3">
                <h6>Existing Reflection Fields:</h6>
                <div class="row">
                    @foreach (DB::table('peer_reflection_fields as prf')->leftJoin('peer_events as pe', 'prf.event_id', '=', 'pe.id')->leftJoin('peer_groups as pg', 'prf.group_id', '=', 'pg.id')->select('prf.*', 'pe.event_name', 'pg.group_name')->get() as $field)
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span
                                                class="badge {{ $field->is_active ? 'bg-success' : 'bg-secondary' }} me-1">
                                                {{ $field->field_label }}
                                            </span>
                                            @if ($field->event_name)
                                                <small class="text-muted">({{ $field->event_name }})</small>
                                            @endif
                                            @if ($field->group_name)
                                                <small class="text-muted">- {{ $field->group_name }}</small>
                                            @endif
                                        </div>
                                        <div>
                                            <input type="checkbox" class="toggle-reflection"
                                                data-id="{{ $field->id }}" {{ $field->is_active ? 'checked' : '' }}
                                                title="Toggle Active">
                                            <button class="btn btn-sm btn-danger ms-1 delete-reflection"
                                                data-id="{{ $field->id }}" title="Delete">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Note:</strong> This is the admin panel for managing events, courses, groups and columns.
            Users will see the evaluation form on the user side.
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load courses when event is selected for groups
            $('#group_event_id').change(function() {
                const eventId = $(this).val();
                const courseSelect = $('#group_course_id');

                if (eventId) {
                    $.get('/admin/peer/courses/' + eventId, function(courses) {
                        courseSelect.empty().append('<option value="">Select Course</option>');
                        courses.forEach(course => {
                            courseSelect.append(
                                `<option value="${course.id}">${course.course_name}</option>`
                            );
                        });
                        courseSelect.prop('disabled', false);
                    }).fail(function() {
                        alert('Error loading courses');
                    });
                } else {
                    courseSelect.empty().append('<option value="">Select Course</option>').prop('disabled',
                        true);
                }
            });

            // Load courses when event is selected for columns
            $('#column_event_id').change(function() {
                const eventId = $(this).val();
                const courseSelect = $('#column_course_id');

                if (eventId) {
                    $.get('/admin/peer/courses/' + eventId, function(courses) {
                        courseSelect.empty().append('<option value="">Select Course</option>');
                        courses.forEach(course => {
                            courseSelect.append(
                                `<option value="${course.id}">${course.course_name}</option>`
                            );
                        });
                        courseSelect.prop('disabled', false);
                    }).fail(function() {
                        alert('Error loading courses');
                    });
                } else {
                    courseSelect.empty().append('<option value="">Select Course</option>').prop('disabled',
                        true);
                }
            });

            // Add Event
            $('#addEventBtn').click(function() {
                const eventName = $('#event_name').val();
                if (!eventName) {
                    alert('Please enter event name');
                    return;
                }

                $.post('{{ route('admin.peer.event.add') }}', {
                    _token: '{{ csrf_token() }}',
                    event_name: eventName
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

            // Add Course to Event
            $('.add-course-btn').click(function() {
                const eventId = $(this).data('event-id');
                const courseInput = $(`.course-input[data-event-id="${eventId}"]`);
                const courseName = courseInput.val();

                if (!courseName) {
                    alert('Please enter course name');
                    return;
                }

                $.post('{{ route('admin.peer.course.add') }}', {
                    _token: '{{ csrf_token() }}',
                    course_name: courseName,
                    event_id: eventId
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

            // Add Group with Event and Course
            $('#addGroupBtn').click(function() {
                const eventId = $('#group_event_id').val();
                const courseId = $('#group_course_id').val();
                const groupName = $('#group_name').val();
                const maxMarks = $('#max_marks').val();

                if (!eventId || !courseId || !groupName) {
                    alert('Please select event, course and enter group name');
                    return;
                }

                if (!maxMarks || maxMarks <= 0) {
                    alert('Please enter valid max marks');
                    return;
                }

                $.post('{{ route('admin.peer.group.add') }}', {
                    _token: '{{ csrf_token() }}',
                    event_id: eventId,
                    course_id: courseId,
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

            // Add Column with Event and Course
            $('#addColumnBtn').click(function() {
                const eventId = $('#column_event_id').val();
                const courseId = $('#column_course_id').val();
                const columnName = $('#column_name').val();

                if (!columnName) {
                    alert('Please enter column name');
                    return;
                }

                $.post('{{ route('admin.peer.column.add') }}', {
                    _token: '{{ csrf_token() }}',
                    event_id: eventId || null,
                    course_id: courseId || null,
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
            // Toggle Form Status - Enhanced version
            $('.toggle-form').change(function() {
                const checkbox = $(this);
                const id = checkbox.data('id');
                const isChecked = checkbox.is(':checked') ? 1 : 0;

                // Store original state in case we need to revert
                const originalState = !isChecked;

                // Optional: Show loading state
                checkbox.prop('disabled', true);

                $.post('/admin/peer/group/toggle-form/' + id, {
                        _token: '{{ csrf_token() }}',
                        is_form_active: isChecked
                    })
                    .done(function(response) {
                        if (response.status === 'success') {
                            // Success - update the badge/text to reflect new status
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
                            console.log('Form status updated successfully');
                        } else {
                            // Handle error response
                            const errorMessage = response.message || 'Error updating form status';
                            alert(errorMessage);
                            checkbox.prop('checked', originalState);
                        }
                    })
                    .fail(function(xhr, status, error) {
                        // Handle AJAX failure
                        let errorMessage = 'Error updating form status';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            // If response is not JSON, use default message
                        }
                        alert(errorMessage);
                        checkbox.prop('checked', originalState);
                    })
                    .always(function() {
                        // Re-enable checkbox
                        checkbox.prop('disabled', false);
                    });
            });

            // Delete Group
            // Delete Group
            $('.delete-group').click(function() {
                if (confirm('Are you sure you want to delete this group?')) {
                    const button = $(this);
                    const id = button.data('id');

                    // Show loading state
                    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    $.post('/admin/peer/group/delete/' + id, {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(response) {
                            if (response.success) {
                                // Show success message and reload
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

                $.post('/admin/peer/toggle/' + id, {
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    if (response.success) {
                        // reload page to see changes
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

            // Load courses when event is selected for reflection fields
            $('#reflection_event_id').change(function() {
                const eventId = $(this).val();
                const courseSelect = $('#reflection_course_id');
                if (eventId) {
                    courseSelect.prop('disabled', true).html(
                        '<option value="">Loading courses...</option>');

                    $.get('/admin/peer/courses/' + eventId)
                        .done(function(courses) {
                            courseSelect.empty().append('<option value="">Select Course</option>');
                            if (courses && courses.length > 0) {
                                courses.forEach(course => {
                                    console.log('Adding course:', course.id, course
                                        .course_name);
                                    courseSelect.append(
                                        `<option value="${course.id}">${course.course_name}</option>`
                                    );
                                });
                                courseSelect.prop('disabled', false);
                                console.log('Courses populated successfully');
                            } else {
                                courseSelect.append(
                                    '<option value="" disabled>No courses found</option>');
                                courseSelect.prop('disabled', false);
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.error('Error loading courses:', status, error);
                            console.error('Response text:', xhr.responseText);
                            alert('Error loading courses: ' + error);
                            courseSelect.empty().append('<option value="">Select Course</option>').prop(
                                'disabled', true);
                        });
                } else {
                    courseSelect.empty().append('<option value="">Select Course</option>').prop('disabled',
                        true);
                }
            });
            // Add Reflection Field with Event and Group
            $('#addReflectionBtn').click(function() {
                const eventId = $('#reflection_event_id').val();
                const groupId = $('#reflection_group_id').val();
                const fieldLabel = $('#reflection_field').val();

                if (!fieldLabel) {
                    alert('Please enter reflection field label');
                    return;
                }

                $.post('{{ route('admin.peer.reflection.add') }}', {
                    _token: '{{ csrf_token() }}',
                    field_label: fieldLabel,
                    event_id: eventId || null,
                    group_id: groupId || null
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

            $('.toggle-reflection').change(function() {
                const checkbox = $(this);
                const id = checkbox.data('id');

                $.post('/admin/peer/reflection/toggle/' + id, {
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    if (response.success) {
                        // Update badge color
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
    </style>
@endsection
