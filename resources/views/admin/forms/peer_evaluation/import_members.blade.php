{{-- @extends('admin.layouts.master')
@section('setup_content')
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Import Users to: {{ $group->group_name }}</h4>
            <a href="{{ route('admin.peer.group.members', $group->id) }}" class="btn btn-secondary">
                Back to Members
            </a>
        </div>

        @if ($availableUsers->count() > 0)
            <form method="POST" action="{{ route('admin.peer.group.add-members', $group->id) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="50px">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>#</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($availableUsers as $index => $user)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="member_pks[]" value="{{ $user->pk }}" class="user-checkbox">
                                    </td>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $user->first_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-success mt-3">Add Selected Users to Group</button>
            </form>
        @else
            <div class="alert alert-info">
                No available users to import. All users are already in this group.
            </div>
        @endif
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            // Select all functionality
            $('#selectAll').change(function() {
                $('.user-checkbox').prop('checked', this.checked);
            });

            $('.user-checkbox').change(function() {
                if (!this.checked) {
                    $('#selectAll').prop('checked', false);
                }
            });
        });
    </script>
@endsection --}}




@extends('admin.layouts.master')
@section('setup_content')
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Import Users to: {{ $group->group_name }}</h4>
            <a href="{{ route('admin.peer.group.members', $group->id) }}" class="btn btn-secondary">
                Back to Members
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="row g-3">
            <!-- Import from Excel -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h5>Import from Excel</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <form method="POST" action="{{ route('admin.peer.group.import-excel', $group->id) }}"
                            enctype="multipart/form-data" class="flex-fill d-flex flex-column">
                            @csrf
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Select Excel File</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">
                                    Supported formats: .xlsx, .xls, .csv
                                </div>
                            </div>
                            <div class="mt-auto">
                                <button type="submit" class="btn btn-success w-100">Import Excel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Excel Template -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h5>Excel Template</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p>Download the template file to ensure correct format:</p>
                        <div class="mb-3 flex-fill">
                            <strong>Required Columns:</strong>
                            <ul class="mt-2">
                                <li>Course Name</li>
                                <li>Event Name</li>
                                <li>User ID</li>
                                <li>User Name</li>
                                <li>OT Code</li>
                            </ul>
                        </div>
                        <div class="mt-auto">
                            <a href="{{ route('admin.peer.download-template') }}" class="btn btn-primary w-100">
                                Download Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Manual Import Section (Optional) --}}
        {{-- <div class="card mt-4">
            <div class="card-header">
                <h5>Manual Import (Alternative)</h5>
            </div>
            <div class="card-body">
                @php
                    $availableUsers = DB::table('fc_registration_master')
                        ->whereNotIn('pk', function($query) use ($group) {
                            $query->select('member_pk')
                                ->from('peer_group_members')
                                ->where('group_id', $group->id);
                        })
                        ->select('pk', 'first_name')
                        ->orderBy('first_name')
                        ->get();
                @endphp

                @if ($availableUsers->count() > 0)
                    <form method="POST" action="{{ route('admin.peer.group.add-members', $group->id) }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50px">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>#</th>
                                        <th>User ID (PK)</th>
                                        <th>Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableUsers as $index => $user)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="member_pks[]" value="{{ $user->pk }}" class="user-checkbox">
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $user->pk }}</td>
                                            <td>{{ $user->first_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-warning mt-3">Add Selected Users to Group</button>
                    </form>
                @else
                    <div class="alert alert-info">
                        No available users to import. All users are already in this group.
                    </div>
                @endif
            </div>
        </div> --}}
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            // Select all functionality
            $('#selectAll').change(function() {
                $('.user-checkbox').prop('checked', this.checked);
            });

            $('.user-checkbox').change(function() {
                if (!this.checked) {
                    $('#selectAll').prop('checked', false);
                }
            });
        });
    </script>
@endsection
