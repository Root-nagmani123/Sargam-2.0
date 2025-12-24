@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Table styling */
.subjectTable thead th {
    background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 12px;
}

.subjectTable tbody tr {
    transition: all 0.2s ease;
}

.subjectTable tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.05);
}

.subjectTable tbody td {
    padding: 12px;
    vertical-align: middle;
    border-color: #e9ecef;
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Subject"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="fw-semibold text-dark mb-0">Subject</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Add New Button -->
                    <a href="{{ route('subject.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i>
                        Add Subject
                    </a>
                </div>
            </div>
            <hr>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="material-icons material-symbols-rounded me-2" style="font-size: 20px; vertical-align: middle;">check_circle</i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="material-icons material-symbols-rounded me-2" style="font-size: 20px; vertical-align: middle;">error</i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            <!-- Search Form -->
            <form method="GET" action="{{ route('subject.index') }}" class="mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label mb-1">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Search by subject name or short name...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">search</i>
                            Search
                        </button>
                    </div>
                    @if(request('search'))
                    <div class="col-md-2">
                        <a href="{{ route('subject.index') }}" class="btn btn-secondary w-100">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">clear</i>
                            Clear
                        </a>
                    </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table subjectTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Major Subject Name</th>
                            <th>Short Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $key => $subject)
                        <tr>
                            <td>{{ $subjects->firstItem() + $key }}</td>
                            <td>{{ $subject->subject_name }}</td>
                            <td>{{ $subject->sub_short_name }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="subject_master" data-column="active_inactive"
                                        data-id="{{ $subject->pk }}"
                                        {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons material-symbols-rounded"
                                            style="font-size: 22px;">more_horiz</i>
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                        {{-- Edit --}}
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('subject.edit', $subject->pk) }}">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 20px;">edit</i>
                                                Edit
                                            </a>
                                        </li>

                                        {{-- Delete --}}
                                        @if ($subject->active_inactive == 1)
                                        <li>
                                            <span class="dropdown-item text-muted d-flex align-items-center gap-2"
                                                style="cursor: not-allowed;">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 20px;">delete</i>
                                                Cannot delete (active)
                                            </span>
                                        </li>
                                        @else
                                        <li>
                                            <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="dropdown-item text-danger d-flex align-items-center gap-2"
                                                    type="submit">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size: 20px;">delete</i>
                                                    Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endif

                                    </ul>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <p class="text-muted mb-0">No subjects found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <!-- Pagination -->
            @if($subjects->total() > 0)
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="text-muted small mb-2">
                    Showing {{ $subjects->firstItem() ?? 0 }} to {{ $subjects->lastItem() ?? 0 }} of 
                    {{ $subjects->total() }} entries
                </div>
                <div>
                    {{ $subjects->appends(['search' => request('search')])->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection