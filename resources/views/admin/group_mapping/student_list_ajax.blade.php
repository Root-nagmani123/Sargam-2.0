{{-- also add search functionality using ajax --}}
{{-- <form id="studentListForm" method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form> --}}
{{-- <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div class="text-muted mb-2">
        <strong>Group Name:</strong> {{ $group->name ?? 'N/A' }} |
        <strong>Group Code:</strong> {{ $group->code ?? 'N/A' }}
    </div>
</div> --}}
@if(!empty($groupName) || !empty($facilityName) || !empty($courseName))
    <div class="row mb-3">
        <div class="col-md-4">
            <strong>Course Name:</strong>
            <span class="text-muted">{{ $courseName ?? 'N/A' }}</span>
        </div>
        <div class="col-md-4">
            <strong>Group Name:</strong>
            <span class="text-muted">{{ $groupName ?? 'N/A' }}</span>
        </div>
        <div class="col-md-4">
            <strong>Facility:</strong>
            <span class="text-muted">{{ $facilityName ?? 'N/A' }}</span>
        </div>
    </div>
@endif

<table class="table table-bordered table-hover align-middle">
    <thead class="table-primary">
        <tr>
            <th scope="col" style="width: 45px;">
                <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input" id="selectAllOts">
                </div>
            </th>
            <th>#</th>
            <th>Display Name</th>
            <th>Email</th>
            <th>Contact No</th>
            <th>Counsellor Group Name</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $index => $studentMap)
            @php
                $student = $studentMap->studentsMaster;
            @endphp
            <tr>
                <td>
                    @if($student && $student->pk)
                        <div class="form-check mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input student-select"
                                value="{{ encrypt($student->pk) }}"
                                data-email="{{ $student->email }}"
                                data-phone="{{ $student->contact_no }}"
                                data-name="{{ $student->display_name }}"
                            >
                        </div>
                    @endif
                </td>
                <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                <td>{{ $student->display_name ?? 'N/A' }}</td>
                <td>{{ $student->email ?? 'N/A' }}</td>
                <td>{{ $student->contact_no ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">No students found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
    <div class="text-muted mb-2">
        <strong>Total Students:</strong> {{ $students->total() }} |
        <strong>Page:</strong> {{ $students->currentPage() }} of {{ $students->lastPage() }} |
        <strong>Per Page:</strong> {{ $students->perPage() }}
    </div>
    
    <div class="student-list-pagination">
        {!! $students->links('pagination::bootstrap-5') !!}
    </div>
</div>

<input type="hidden" id="groupMappingEncryptedId" value="{{ encrypt($groupMappingPk) }}">