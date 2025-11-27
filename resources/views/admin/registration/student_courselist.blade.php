@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('content')
    <div class="container-fluid">

        {{-- Filters + Counts + Export --}}
        <div class="card mb-3 p-3" style="border-left: 4px solid #004a93;">
            <div class="row align-items-end g-3">

                <!-- Filters (Course + Status) -->
                <div class="col-md-5 col-sm-12">
                    <form id="filterForm" method="GET">
                        <div class="row g-3">
                            <!-- Course Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Course</label>
                                <select name="course_id" id="course_id" class="form-select">
                                    <option value="">-- All Courses --</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">-- All Status --</option>
                                    <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Total Count - Centered -->
                <div class="col-md-2 col-sm-12 text-center">
                    <div class="fw-bold fs-5 text-primary mt-2 mt-md-0">
                        Total: <span id="filteredCount">{{ $filteredCount }}</span>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="col-md-5 col-sm-12">
                    <form method="GET" action="{{ route('studentEnroll.report.export') }}" id="exportForm">
                        <input type="hidden" name="course" id="exportCourse" value="{{ $courseId }}">
                        <input type="hidden" name="status" id="exportStatus" value="{{ $status }}">
                        
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Export Format</label>
                                <select name="format" class="form-select">
                                    <option value="">-- Select Format --</option>
                                    <option value="pdf">PDF</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success w-100 mt-3 mt-md-0">Export</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        {{-- Data Table with Loading State --}}
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body position-relative">
                <!-- Loading Overlay -->
                <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none" style="z-index: 10;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading records...</span>
                </div>

                <div class="table-responsive">
                    <table  class="table table-bordered text-nowrap align-middle">
                        <thead style="background:#af2910; color:#fff;">
                            <tr>
                                <th>S.No</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Service</th>
                                <th>OT Code</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Modified Date</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            @if($enrollments->count() > 0)
                                @include('admin.registration.student_courses_table', ['enrollments' => $enrollments])
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Apply filters to see records</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
<script>
$(function() {
    let dataTable = null;

    // Initialize basic table for empty state
    function initializeBasicTable() {
        if (dataTable !== null) {
            dataTable.destroy();
        }
        return $('#dataTable').DataTable({
            searching: false,
            ordering: false,
            paging: false,
            info: false,
            responsive: true
        });
    }

    // Initialize full featured DataTable
    function initializeFullDataTable() {
        if (dataTable !== null) {
            dataTable.destroy();
        }
        return $('#dataTable').DataTable({
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            searching: true,
            responsive: true,
            columnDefs: [{
                orderable: false,
                targets: [5]
            }]
        });
    }

    // Start with basic table
    dataTable = initializeBasicTable();

    // AJAX Filter Function
    function applyFilters() {
        const courseId = $('#course_id').val();
        const status = $('#status').val();
        
        // Don't make AJAX call if no filters are selected
        if (!courseId && (status === '' || status === null)) {
            $('#tableBody').html('<tr><td colspan="8" class="text-center text-muted">Apply filters to see records</td></tr>');
            dataTable = initializeBasicTable();
            $('#filteredCount').text('0');
            return;
        }
        
        $('#loadingOverlay').removeClass('d-none');
        $('#exportCourse').val(courseId);
        $('#exportStatus').val(status);
        
        $.ajax({
            url: "{{ route('student.courses') }}",
            type: "GET",
            data: { course_id: courseId, status: status },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                $('#tableBody').html(response.html);
                $('#filteredCount').text(response.filteredCount);
                
                setTimeout(() => {
                    if (response.filteredCount > 0) {
                        dataTable = initializeFullDataTable();
                    } else {
                        dataTable = initializeBasicTable();
                    }
                    $('#loadingOverlay').addClass('d-none');
                }, 100);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error applying filters. Please try again.');
                $('#loadingOverlay').addClass('d-none');
            }
        });
    }

    $('#course_id, #status').change(applyFilters);
});
</script>
@endsection