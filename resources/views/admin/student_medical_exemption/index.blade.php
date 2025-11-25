@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('content')
<style>
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}
</style>
<div class="container-fluid">
    <x-breadcrum title="Student Medical Exemption" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h4>Student Medical Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('student.medical.exemption.create')}}" class="btn btn-primary">+ Add
                                    Student Medical Exemption</a>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 text-end">
                            <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                                aria-label="Medical Exemption Status Filter">
                                <button type="button" class="btn btn-success px-4 fw-semibold {{ request('status_filter', 'active') == 'active' ? 'active' : '' }}"
                                    id="filterActive" aria-pressed="{{ request('status_filter', 'active') == 'active' ? 'true' : 'false' }}">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </button>
                                <button type="button"
                                    class="btn {{ request('status_filter') == 'archive' ? 'btn-secondary active' : 'btn-outline-secondary' }} px-4 fw-semibold"
                                    id="filterArchive" aria-pressed="{{ request('status_filter') == 'archive' ? 'true' : 'false' }}">
                                    <i class="bi bi-archive me-1"></i> Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="courseFilter" class="form-label mb-1">Course Name</label>
                            <select id="courseFilter" class="form-select">
                                <option value="">All Courses</option>
                                @foreach($courses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}" {{ request('course_filter') == $pk ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="dateRangeFilter" class="form-label mb-1">Exemption Status</label>
                            <select id="dateRangeFilter" class="form-select">
                                <option value="all" {{ request('date_range_filter', 'all') == 'all' ? 'selected' : '' }}>All Exemptions</option>
                                <option value="current" {{ request('date_range_filter') == 'current' ? 'selected' : '' }}>Currently Active (Today)</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="reloadPage">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <hr>
                    <div class="table-responsive">
                        <table id="zero_config" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Student</th>
                                    <th class="col">Course Name</th>
                                    <th class="col">Category</th>
                                    <th class="col">Medical Speciality</th>
                                    <th class="col">From-To</th>
                                    <th class="col">OPD Type</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->student->display_name ?? 'N/A' }}</td>
                                    <td>{{ $row->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $row->category->exemp_category_name ?? 'N/A' }}</td>
                                    <td>{{ $row->speciality->speciality_name ?? 'N/A' }}</td>
                                    <td>
    {{ \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') }}
    to
    {{ \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') }}
</td>

                                    <td>{{ $row->opd_category }}</td>
                                    <td>
                                        <a href="{{ route('student.medical.exemption.edit', ['id' => encrypt(value: $row->pk)])  }}"
                                            class="btn btn-sm btn-info">Edit</a>

                                        <form
                                            title="{{ $row->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('student.medical.exemption.delete', 
                                                    ['id' => encrypt($row->pk)]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="student_medical_exemption" data-column="active_inactive"
                                                data-id="{{ $row->pk }}"
                                                {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const currentStatus = '{{ request("status_filter", "active") }}';
    const currentCourse = '{{ request("course_filter", "") }}';
    const currentDateRange = '{{ request("date_range_filter", "all") }}';

    // Handle Active button click
    $('#filterActive').on('click', function() {
        applyFilter('active', currentCourse, currentDateRange);
    });

    // Handle Archive button click
    $('#filterArchive').on('click', function() {
        applyFilter('archive', currentCourse, currentDateRange);
    });

    // Handle Course filter change
    $('#courseFilter').on('change', function() {
        const selectedCourse = $(this).val();
        const selectedDateRange = $('#dateRangeFilter').val();
        applyFilter(currentStatus, selectedCourse, selectedDateRange);
    });

    // Handle Date Range filter change
    $('#dateRangeFilter').on('change', function() {
        const selectedDateRange = $(this).val();
        const selectedCourse = $('#courseFilter').val();
        applyFilter(currentStatus, selectedCourse, selectedDateRange);
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        window.location.href = '{{ route("student.medical.exemption.index") }}?status_filter=' + currentStatus;
    });

    // Reload page
    $('#reloadPage').on('click', function() {
        window.location.href = '{{ route("student.medical.exemption.index") }}';
    });

    function applyFilter(status, course, dateRange) {
        let url = '{{ route("student.medical.exemption.index") }}?status_filter=' + status;
        if (course) {
            url += '&course_filter=' + course;
        }
        if (dateRange && dateRange !== 'all') {
            url += '&date_range_filter=' + dateRange;
        }
        window.location.href = url;
    }

    // Set active button styling on page load
    function setActiveButton() {
        if (currentStatus === 'active') {
            $('#filterActive')
                .removeClass('btn-outline-success')
                .addClass('btn-success text-white active')
                .attr('aria-pressed', 'true');
            $('#filterArchive')
                .removeClass('btn-secondary active text-white')
                .addClass('btn-outline-secondary')
                .attr('aria-pressed', 'false');
        } else {
            $('#filterArchive')
                .removeClass('btn-outline-secondary')
                .addClass('btn-secondary text-white active')
                .attr('aria-pressed', 'true');
            $('#filterActive')
                .removeClass('btn-success active text-white')
                .addClass('btn-outline-success')
                .attr('aria-pressed', 'false');
        }
    }

    setActiveButton();
});
</script>
@endpush