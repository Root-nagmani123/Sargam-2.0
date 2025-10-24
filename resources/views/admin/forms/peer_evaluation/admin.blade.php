@extends('admin.layouts.master')
@section('content')
    <div class="card p-3">
        <h4 class="mb-4">Peer Evaluation - Admin Panel</h4>

        {{-- Add Group --}}
        {{-- <div class="mb-4">
            <h5>Manage Groups</h5>
            <div class="input-group mb-3">
                <input type="text" id="group_name" class="form-control" placeholder="Enter Group Name (e.g. Syndicate-20)">
                <button class="btn btn-success" id="addGroupBtn">Add Group</button>
            </div> --}}

        {{-- Groups List --}}
        {{-- <div class="mt-3">
                <h6>Existing Groups:</h6>
                @foreach ($groups as $group)
                    <span class="badge bg-primary me-2 mb-2 p-2">
                        {{ $group->group_name }}
                        <button class="btn btn-sm btn-danger ms-1 delete-group" data-id="{{ $group->id }}">×</button>
                    </span>
                @endforeach
            </div>
        </div> --}}

        {{-- Manage Groups Section --}}
        <div class="mb-4">
            <h5>Manage Groups</h5>
            <div class="input-group mb-3">
                <input type="text" id="group_name" class="form-control" placeholder="Enter Group Name (e.g. Syndicate-20)">
                <button class="btn btn-success" id="addGroupBtn">Add Group</button>
            </div>

            {{-- Groups List --}}
            <div class="mt-3">
                <h6>Groups List:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
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
                                    <td>{{ $group->group_name }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm max-marks-input"
                                            data-id="{{ $group->id }}" value="{{ $group->max_marks ?? 10 }}"
                                            step="0.01" min="1" max="100" style="width: 80px;">
                                        <button class="btn btn-sm btn-outline-primary update-marks mt-1"
                                            data-id="{{ $group->id }}">Update</button>
                                    </td>
                                    <td>
                                        <span class="badge {{ $group->is_form_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $group->is_form_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <input type="checkbox" class="toggle-form ms-2" data-id="{{ $group->id }}"
                                            {{ $group->is_form_active ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $group->member_count }} members</td>
                                    <td>
                                        <a href="{{ route('admin.peer.group.members', $group->id) }}"
                                            class="btn btn-info btn-sm">
                                            View Members
                                        </a>
                                        <a href="{{ route('admin.peer.group.import', $group->id) }}"
                                            class="btn btn-warning btn-sm">
                                            Import Users
                                        </a>
                                        <a href="{{ route('admin.peer.group.submissions', $group->id) }}"
                                            class="btn btn-primary btn-sm">
                                            View Submissions
                                        </a>
                                        <button class="btn btn-danger btn-sm delete-group" data-id="{{ $group->id }}">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Add Column --}}
        <div class="mb-4">
            <h5>Manage Evaluation Columns</h5>
            <div class="input-group mb-3">
                <input type="text" id="column_name" class="form-control"
                    placeholder="Enter Column Name (e.g. Team Player)">
                <button class="btn btn-primary" id="addColumnBtn">Add Column</button>
            </div>

            {{-- Columns List --}}
            <div class="mt-3">
                <h6>Existing Columns:</h6>
                @foreach ($columns as $column)
                    <span class="badge {{ $column->is_visible ? 'bg-success' : 'bg-secondary' }} me-2 mb-2 p-2">
                        {{ $column->column_name }}
                        <input type="checkbox" class="toggle-column ms-1" data-id="{{ $column->id }}"
                            {{ $column->is_visible ? 'checked' : '' }}>
                        <button class="btn btn-sm btn-danger ms-1 delete-column" data-id="{{ $column->id }}">×</button>
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Manage Additional Reflection Fields --}}
        <div class="mb-4">
            <h5>Manage Reflection Fields (Dynamic)</h5>
            <div class="input-group mb-3">
                <input type="text" id="reflection_field" class="form-control"
                    placeholder="Enter Reflection Field (e.g. Learning from the Paramilitary Forces)">
                <button class="btn btn-secondary" id="addReflectionBtn">Add Field</button>
            </div>

            {{-- Reflection Fields List --}}
            <div class="mt-3">
                <h6>Existing Reflection Fields:</h6>
                @foreach (DB::table('peer_reflection_fields')->get() as $field)
                    <span class="badge {{ $field->is_active ? 'bg-success' : 'bg-secondary' }} me-2 mb-2 p-2">
                        {{ $field->field_label }}
                        <input type="checkbox" class="toggle-reflection ms-1" data-id="{{ $field->id }}"
                            {{ $field->is_active ? 'checked' : '' }}>
                        <button class="btn btn-sm btn-danger ms-1 delete-reflection"
                            data-id="{{ $field->id }}">×</button>
                    </span>
                @endforeach
            </div>
        </div>


        <div class="alert alert-info">
            <strong>Note:</strong> This is the admin panel for managing groups and columns.
            Users will see the evaluation form on the user side.
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            // Add Group
            $('#addGroupBtn').click(function() {
                $.post('{{ route('admin.peer.group.store') }}', {
                    _token: '{{ csrf_token() }}',
                    group_name: $('#group_name').val()
                }, function() {
                    location.reload();
                });
            });

            // Add Column
            $('#addColumnBtn').click(function() {
                $.post('{{ route('admin.peer.column.store') }}', {
                    _token: '{{ csrf_token() }}',
                    column_name: $('#column_name').val()
                }, function() {
                    location.reload();
                });
            });

            // Toggle Column
            $('.toggle-column').change(function() {
                const id = $(this).data('id');
                $.post('{{ url('admin/peer/toggle') }}/' + id, {
                    _token: '{{ csrf_token() }}'
                }, function() {
                    location.reload();
                });
            });

            // Delete Group
            $('.delete-group').click(function() {
                if (confirm('Are you sure you want to delete this group?')) {
                    const id = $(this).data('id');
                    $.post('{{ url('admin/peer/group/delete') }}/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function() {
                        location.reload();
                    });
                }
            });

            // Delete Column
            $('.delete-column').click(function() {
                if (confirm('Are you sure you want to delete this column?')) {
                    const id = $(this).data('id');
                    $.post('{{ url('admin/peer/column/delete') }}/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function() {
                        location.reload();
                    });
                }
            });

            // Add Reflection Field
            $('#addReflectionBtn').click(function() {
                $.post('{{ route('admin.peer.reflection.add') }}', {
                    _token: '{{ csrf_token() }}',
                    field_label: $('#reflection_field').val()
                }, function() {
                    location.reload();
                });
            });

            // Toggle Reflection Field
            $('.toggle-reflection').change(function() {
                const id = $(this).data('id');
                $.post('{{ url('admin/peer/reflection/toggle') }}/' + id, {
                    _token: '{{ csrf_token() }}'
                }, function() {
                    location.reload();
                });
            });

            // Delete Reflection Field
            $('.delete-reflection').click(function() {
                if (confirm('Are you sure you want to delete this field?')) {
                    const id = $(this).data('id');
                    $.post('{{ url('admin/peer/reflection/delete') }}/' + id, {
                        _token: '{{ csrf_token() }}'
                    }, function() {
                        location.reload();
                    });
                }
            });

        });

        $(function() {
            // Toggle Form Active/Inactive
            $('.toggle-form').change(function() {
                const checkbox = $(this);
                const id = checkbox.data('id');
                const isChecked = checkbox.is(':checked') ? 1 : 0;

                $.ajax({
                    url: '/admin/peer/group/toggle-form/' + id, // make sure route exists
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        is_form_active: isChecked
                    },
                    success: function(response) {
                        // Option 1: Reload page
                        location.reload();

                        // Option 2: OR update badge dynamically without reload
                        // const badge = checkbox.siblings('.form-badge');
                        // if (isChecked) {
                        //     badge.removeClass('bg-danger').addClass('bg-success').text('Active');
                        // } else {
                        //     badge.removeClass('bg-success').addClass('bg-danger').text('Inactive');
                        // }
                    },
                    error: function(xhr) {
                                            console.error('Error:', error);
                        
                        alert('Something went wrong!!!!');
                        // revert checkbox if error
                        checkbox.prop('checked', !isChecked);
                    }
                });
            });
        });

        // Add new group with max marks
        document.getElementById('addGroupBtn').addEventListener('click', function() {
            const groupName = document.getElementById('group_name').value;
            const maxMarks = document.getElementById('max_marks').value;

            if (!groupName) {
                alert('Please enter a group name');
                return;
            }

            if (!maxMarks || maxMarks <= 0) {
                alert('Please enter valid max marks');
                return;
            }

            // Send AJAX request to add group
            fetch('{{ route('admin.peer.groups.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        group_name: groupName,
                        max_marks: parseFloat(maxMarks)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the group');
                });
        });

        // Update max marks for existing group
        document.querySelectorAll('.update-marks').forEach(button => {
            button.addEventListener('click', function() {
                const groupId = this.getAttribute('data-id');
                const input = document.querySelector(`.max-marks-input[data-id="${groupId}"]`);
                const maxMarks = input.value;

                if (!maxMarks || maxMarks <= 0) {
                    alert('Please enter valid max marks');
                    return;
                }

                // Send AJAX request to update max marks
                fetch('{{ route('admin.peer.groups.update-marks') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            group_id: groupId,
                            max_marks: parseFloat(maxMarks)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Max marks updated successfully');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating max marks');
                    });
            });
        });

        // Allow Enter key to trigger update
        document.querySelectorAll('.max-marks-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const groupId = this.getAttribute('data-id');
                    document.querySelector(`.update-marks[data-id="${groupId}"]`).click();
                }
            });
        });
    </script>
@endsection
