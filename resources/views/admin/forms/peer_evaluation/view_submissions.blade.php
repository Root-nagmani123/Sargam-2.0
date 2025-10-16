@extends('admin.layouts.master')
@section('content')
    <div class="card p-3">
        <h4 class="mb-4">Peer Evaluation Submissions</h4>

        <!-- Group Selection -->
        <div class="card mb-4">
            <div class="card-header bg-light text-dark">
                <h5 class="mb-0"><i class="fas fa-users"></i> Peer Evaluation</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center g-3">

                    {{-- <!-- Group Selection (Commented for future use) -->
            <div class="col-md-6">
                <form method="GET" action="#" id="groupForm">
                    <label for="group_id" class="form-label">Select Group</label>
                    <select name="group_id" id="group_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Select a Group --</option>
                        @foreach ($groups as $group)
                            @php
                                $groupObj = is_object($group) ? $group : (object) [
                                    'id' => $group['id'],
                                    'group_name' => $group['group_name'],
                                    'is_active' => $group['is_active'] ?? 1
                                ];
                            @endphp
                            <option value="{{ $groupObj->id }}"
                                {{ ($selectedGroupId ?? null) == $groupObj->id ? 'selected' : '' }}>
                                {{ $groupObj->group_name }}
                                @if (!$groupObj->is_active)
                                    (Inactive)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </form>
            </div> --}}

                    <!-- Selected Group Info -->
                    @if (!empty($selectedGroupId))
                        <div class="col-md-6">
                            @php
                                $memberCount = count($members ?? []);
                                $selectedGroup = $groups->where('id', $selectedGroupId)->first();
                            @endphp
                            <div class="alert alert-info mb-0 text-center">
                                <strong>Group:</strong> {{ $selectedGroup->group_name ?? 'N/A' }}
                                | <strong>Members:</strong> {{ $memberCount }}
                            </div>
                        </div>

                        <!-- Export Form -->
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('admin.peer.export', $selectedGroupId) }}"
                                class="d-flex justify-content-center">
                                <select name="format" id="format" class="form-select me-2" required>
                                    <option value="">Select Format</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                                <button type="submit" class="btn btn-success">Export</button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        </div>


        {{-- <script>
    // Auto-submit group filter
    document.getElementById('group_id').addEventListener('change', function() {
        document.getElementById('groupForm').submit();
    });
</script> --}}


        @if ($selectedGroupId && count($members) > 0)
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i>
                        Evaluation - {{ $groups->where('id', $selectedGroupId)->first()->group_name }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">Sr.No</th>
                                    <th>User Name</th>
                                    <th>Group Name</th>
                                    @foreach ($columns as $column)
                                        <th>{{ $column->column_name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($members as $index => $member)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-start">
                                            <strong>{{ $member->first_name }}</strong>
                                            @if ($member->user_id)
                                                <br>
                                                <small class="text-muted">- {{ $member->ot_code }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $groups->where('id', $selectedGroupId)->first()->group_name }}
                                            </span>
                                        </td>
                                        @foreach ($columns as $column)
                                            <td>
                                                @php
                                                    // Fetch submitted score
                                                    $score = $scores
                                                        ->where('member_id', $member->id)
                                                        ->where('column_id', $column->id)
                                                        ->first();
                                                @endphp
                                                {{ $score->score ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @elseif($selectedGroupId)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                No evaluation submitted yet for this group.
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Please select a group to view submissions.
            </div>
        @endif
    </div>

    <script>
        // Auto-submit form when group changes
        document.getElementById('group_id').addEventListener('change', function() {
            document.getElementById('groupForm').submit();
        });
    </script>

    <style>
        .table th {
            vertical-align: middle;
            font-weight: 600;
        }

        .badge {
            font-size: 0.9em;
        }

        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection
