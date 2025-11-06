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

<table class="table table-bordered table-hover">
    <thead class="table-primary">
        <tr>
            <th>#</th>
            <th>Display Name</th>
            <th>Email</th>
            <th>Contact No</th>
            <th>Counsellor Code</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $index => $studentMap)
            <tr>
                <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                <td>{{ $studentMap->studentsMaster->display_name ?? 'N/A' }}</td>
                <td>{{ $studentMap->studentsMaster->email ?? 'N/A' }}</td>
                <td>{{ $studentMap->studentsMaster->contact_no ?? 'N/A' }}</td>
                <td>{{ $studentMap->counsellor_code ?? 'N/A' }}</td>
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

<input type="hidden" name="groupMappingID" class="view-student" value="{{ encrypt($groupMappingPk) }}" data-id="{{ encrypt($groupMappingPk) }}">