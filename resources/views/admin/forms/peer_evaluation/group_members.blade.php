{{-- @extends('admin.layouts.master')
@section('setup_content')
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Group Members: {{ $group->group_name }}</h4>
            <a href="{{ route('admin.peer.group.import', $group->id) }}" class="btn btn-primary">
                Import More Users
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $member->first_name }}</td>
                            <td>
                                <button class="btn btn-danger btn-sm remove-member" 
                                        data-group-id="{{ $group->id }}" 
                                        data-member-pk="{{ $member->pk }}">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('admin.peer.index') }}" class="btn btn-secondary mt-3">Back to Groups</a>
    </div>

    @include('components.jquery-3-6')
    <script>
        $(function() {
            $('.remove-member').click(function() {
                if(confirm('Are you sure you want to remove this member from the group?')) {
                    const groupId = $(this).data('group-id');
                    const memberPk = $(this).data('member-pk');
                    
                    $.post('{{ url('admin/peer/group') }}/' + groupId + '/remove-member/' + memberPk, {
                        _token: '{{ csrf_token() }}'
                    }, function() {
                        location.reload();
                    });
                }
            });
        });
    </script>
@endsection --}}

@extends('admin.layouts.master')
@section('setup_content')
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Group Members: {{ $group->group_name }}</h4>
            <div>
                <a href="{{ route('admin.peer.group.import', $group->id) }}" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Import More Users
                </a>
                <a href="{{ route('admin.peer.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Groups
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Course Name</th>
                        <th>Event Name</th>
                        <th>OT Code</th>
                        <th>Member PK</th>
                        <th>Group ID</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $members = DB::table('peer_group_members')
                            ->where('group_id', $group->id)
                            ->orderBy('user_name')
                            ->get();
                    @endphp

                    @foreach ($members as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span>{{ $member->user_id ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <strong>{{ $member->user_name ?? 'Unknown User' }}</strong>
                            </td>
                            <td>{{ $member->course_name ?? 'N/A' }}</td>
                            <td>{{ $member->event_name ?? 'N/A' }}</td>
                            <td>
                                @if ($member->ot_code)
                                    <span>{{ $member->ot_code }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $member->member_pk }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $member->group_id }}</small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    @if ($member->created_at)
                                        {{ \Carbon\Carbon::parse($member->created_at)->format('M j, Y g:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm remove-member" data-group-id="{{ $group->id }}"
                                    data-member-pk="{{ $member->member_pk }}" data-user-name="{{ $member->user_name }}"
                                    title="Remove from group">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    @if ($members->count() == 0)
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-3"></i>
                                <br>
                                No members found in this group.
                                {{-- <br>
                                <a href="{{ route('admin.peer.group.import', $group->id) }}" class="btn btn-primary mt-2">
                                    <i class="fas fa-upload"></i> Import Users
                                </a> --}}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                <small>Total Members: <strong>{{ $members->count() }}</strong></small>
            </div>
            {{-- <a href="{{ route('admin.peer.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Groups
            </a> --}}
        </div>
    </div>

    @include('components.jquery-3-6')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $('.remove-member').click(function() {
                const groupId = $(this).data('group-id');
                const memberPk = $(this).data('member-pk');
                const userName = $(this).data('user-name');

                Swal.fire({
                    title: 'Remove Member?',
                    html: `Are you sure you want to remove <strong>${userName}</strong> from this group?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Removing...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post('{{ url('admin/peer/group') }}/' + groupId + '/remove-member/' +
                            memberPk, {
                                _token: '{{ csrf_token() }}'
                            },
                            function(response) {
                                Swal.fire({
                                    title: 'Removed!',
                                    text: 'Member has been removed from the group',
                                    icon: 'success',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    location.reload();
                                });
                            }).fail(function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to remove member: ' + (xhr
                                    .responseJSON?.message || 'Unknown error'),
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        });
                    }
                });
            });
        });
    </script>

    {{-- <style>
        .table th {
            background-color: #f0f4f8;
            color: rgb(20, 19, 19);
            font-weight: 600;
            border: none;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(233, 227, 227, 0.02);
        }

        .badge {
            font-size: 0.75em;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style> --}}
@endsection
