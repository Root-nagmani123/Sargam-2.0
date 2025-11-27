@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('content')
    <div class="container-fluid">

        {{-- Filters + Counts + Export --}}
        <div class="card mb-3 p-3" style="border-left: 4px solid #004a93;">
            <div class="row align-items-end g-3">

                <!-- Filters Section -->
                <div class="col-md-6 col-sm-12">
                    <form id="filterForm" method="GET">
                        <div class="row g-3">
                            <!-- Course Status Filter - Buttons -->
                            <div class="col-md-12 mb-3">
                                {{-- <label class="form-label fw-bold d-block">Course Type</label> --}}
                                <div class="btn-group" role="group" aria-label="Course type filter">
                                    <input type="radio" class="btn-check" name="course_status" id="course_status_active"
                                        value="active" {{ $courseStatus === 'active' ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-success" for="course_status_active">
                                        <i class="fas fa-play-circle me-1"></i> Active
                                    </label>

                                    <input type="radio" class="btn-check" name="course_status" id="course_status_inactive"
                                        value="inactive" {{ $courseStatus === 'inactive' ? 'checked' : '' }}
                                        autocomplete="off">
                                    <label class="btn btn-danger" for="course_status_inactive">
                                        <i class="fas fa-archive me-1"></i> Archive
                                    </label>

                                    {{-- <input type="radio" class="btn-check" name="course_status" id="course_status_all"
                                        value="all" {{ $courseStatus === 'all' ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="course_status_all">
                                        <i class="fas fa-list-alt me-1"></i> All Courses
                                    </label> --}}
                                </div>
                            </div>

                            <!-- Course Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Course</label>
                                <select name="course_id" id="course_id" class="form-select">
                                    <option value="">-- Select Course --</option>
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
                                <label class="form-label fw-bold">Enrollment Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">-- All Status --</option>
                                    <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive
                                    </option>
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
                <div class="col-md-4 col-sm-12">
                    <form method="GET" action="{{ route('studentEnroll.report.export') }}" id="exportForm">
                        <input type="hidden" name="course" id="exportCourse" value="{{ $courseId }}">
                        <input type="hidden" name="status" id="exportStatus" value="{{ $status }}">
                        <input type="hidden" name="course_status" id="exportCourseStatus" value="{{ $courseStatus }}">

                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Export Format</label>
                                <select name="format" class="form-select">
                                    <option value="">-- Select Format --</option>
                                    <option value="pdf">PDF</option>
                                    <option value="xlsx">Excel</option <option value="csv">CSV</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success w-100 mt-3 mt-md-0">
                                    <i class="fas fa-download me-1"></i> Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        {{-- Info Alert --}}
        @if ($courseStatus === 'inactive')
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-archive me-2"></i>
                <strong>Viewing Archived Courses:</strong> You are currently viewing courses that have been archived. These
                courses are no longer active but historical data is preserved.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @elseif($courseStatus === 'all')
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-list-alt me-2"></i>
                <strong>Viewing All Courses:</strong> You are viewing both active and archived courses.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Data Table with Loading State --}}
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body position-relative">
                <!-- Loading Overlay -->
                <div id="loadingOverlay"
                    class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
                    style="z-index: 10;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading records...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap align-middle">
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
                            @if ($enrollments->count() > 0)
                                @include('admin.registration.student_courses_table', [
                                    'enrollments' => $enrollments,
                                ])
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        @if ($courseStatus === 'active')
                                            Select a course to see student enrollments
                                        @elseif($courseStatus === 'inactive')
                                            Select an archived course to see historical enrollments
                                        @else
                                            Apply filters to see records
                                        @endif
                                    </td>
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

            // Function to update course dropdown based on course status
            function updateCourseDropdown(courseStatus) {
                $('#loadingOverlay').removeClass('d-none');

                $.ajax({
                    url: "{{ route('student.courses') }}",
                    type: "GET",
                    data: {
                        course_status: courseStatus,
                        ajax_courses: true // Flag to indicate we only need courses data
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        // Update course dropdown
                        const courseSelect = $('#course_id');
                        courseSelect.empty().append('<option value="">-- Select Course --</option>');

                        if (response.courses && Object.keys(response.courses).length > 0) {
                            $.each(response.courses, function(id, name) {
                                courseSelect.append(new Option(name, id));
                            });
                        }

                        // Reset course selection
                        courseSelect.val('');
                        $('#loadingOverlay').addClass('d-none');

                        // Update export form
                        $('#exportCourseStatus').val(courseStatus);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Error loading courses. Please try again.');
                        $('#loadingOverlay').addClass('d-none');
                    }
                });
            }

            // AJAX Filter Function
            function applyFilters() {
                const courseId = $('#course_id').val();
                const status = $('#status').val();
                const courseStatus = $('input[name="course_status"]:checked').val();

                // Don't make AJAX call if no filters are selected
                if (!courseId && (status === '' || status === null)) {
                    $('#tableBody').html('<tr><td colspan="8" class="text-center text-muted">' +
                        (courseStatus === 'active' ? 'Select a course to see student enrollments' :
                            courseStatus === 'inactive' ?
                            'Select an archived course to see historical enrollments' :
                            'Apply filters to see records') +
                        '</td></tr>');
                    dataTable = initializeBasicTable();
                    $('#filteredCount').text('0');
                    return;
                }

                $('#loadingOverlay').removeClass('d-none');
                $('#exportCourse').val(courseId);
                $('#exportStatus').val(status);
                $('#exportCourseStatus').val(courseStatus);

                $.ajax({
                    url: "{{ route('student.courses') }}",
                    type: "GET",
                    data: {
                        course_id: courseId,
                        status: status,
                        course_status: courseStatus
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
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

            // Event handlers for radio buttons
            $('input[name="course_status"]').change(function() {
                const courseStatus = $(this).val();
                updateCourseDropdown(courseStatus);
                applyFilters(); // Apply filters after updating courses
            });

            $('#course_id, #status').change(applyFilters);

            // Initialize on page load
            updateCourseDropdown($('input[name="course_status"]:checked').val());
        });
    </script>

    <style>
        .btn-group .btn {
            border-radius: 0.375rem;
            margin-right: 0.5rem;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .btn-check:checked+.btn {
            border-width: 2px;
            font-weight: 600;
        }

        .btn-check:checked+.btn-outline-primary {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }

        .btn-check:checked+.btn-outline-warning {
            border-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .btn-check:checked+.btn-outline-secondary {
            border-color: #6c757d;
            background-color: rgba(108, 117, 125, 0.1);
        }
    </style>
@endsection
