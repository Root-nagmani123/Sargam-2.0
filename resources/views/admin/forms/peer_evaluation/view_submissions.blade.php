@extends('admin.layouts.master')
@section('setup_content')
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
                                    <!-- <option value="pdf">PDF</option> -->
                                </select>
                                <button type="submit" class="btn btn-success">Export</button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        </div>

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
                <table id="peerEvaluationTable" class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th width="60">Sr.No</th>
                            <th>User Display Name</th>
                            <th>Group Name</th>
                            <th>User Full Name</th>
                            <th>Evaluator Name</th>
                            @foreach ($columns as $column)
                                <th>{{ $column->column_name }}</th>
                            @endforeach
                            <!-- Reflection Fields as Additional Columns -->
                            @foreach ($reflectionFields as $field)
                                <th class="bg-light">
                                    {{ $field->field_label }}
                                    <br>
                                    <small class="text-muted">Reflection</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody></tbody>
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
@push('scripts')
<script>
$(document).ready(function () {
    if (!$('#peerEvaluationTable').length) {
        return;
    }

    // Server-side: search, sort and paging are resolved on the server.
    $('#peerEvaluationTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.peer.group.submissions', $selectedGroupId) }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'member_name', name: 'member_name', className: 'text-start' },
            { data: 'group_name', name: 'group_name' },
            { data: 'user_full_name', name: 'user_full_name', className: 'text-start' },
            { data: 'evaluator_name', name: 'evaluator_name', className: 'text-start' }
            @foreach ($columns as $column)
            , { data: 'col_{{ $column->id }}', name: 'col_{{ $column->id }}' }
            @endforeach
            @foreach ($reflectionFields as $field)
            , { data: 'ref_{{ $field->id }}', name: 'ref_{{ $field->id }}', className: 'bg-light text-start' }
            @endforeach
        ],
        responsive: true,
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ordering: true,
        order: [],
        language: {
            processing: "Loading data…",
            search: "Search:",
            lengthMenu: "Show _MENU_ records",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                previous: "Prev",
                next: "Next"
            }
        }
    });
});
</script>

@endpush