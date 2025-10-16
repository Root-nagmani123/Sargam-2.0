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
                                        <span class="badge {{ $group->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $group->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <input type="checkbox" class="toggle-group ms-2" data-id="{{ $group->id }}"
                                            {{ $group->is_active ? 'checked' : '' }}>
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
        });
    </script>
@endsection
