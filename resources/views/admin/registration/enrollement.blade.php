@extends('admin.layouts.master')

@section('title', 'Enrollment - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Enroll to New Course" />
        <x-session_message />
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card" style="border-left: 4px solid #004a93;">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <h4>Enroll to New Course</h4>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#enrolledModal" id="openEnrolledBtn">
                                    <i class="bi bi-people"></i> View Enrolled Students
                                </button>
                            </div>
                        </div>
                        <hr>

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('enrollment.store') }}" id="enrollmentForm">
                            @csrf
                            <input type="hidden" name="selected_students" id="selectedStudents" value="">

                            <div class="row">
                                <!-- New Course Column -->
                                <div class="col-md-4">
                                    <label for="course_master_pk" class="form-label">Select New Course:</label>
                                    <div class="mb-3">
                                        <select id="course_master_pk"
                                            class="form-select select2 @error('course_master_pk') is-invalid @enderror"
                                            name="course_master_pk" required>
                                            <option value="">Select Course</option>
                                            @if (isset($courses) && count($courses) > 0)
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->pk }}"
                                                        {{ old('course_master_pk') == $course->pk || session('selected_course') == $course->pk ? 'selected' : '' }}>
                                                        {{ $course->course_name }}
                                                        @if ($course->couse_short_name)
                                                            ({{ $course->couse_short_name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="">No courses available</option>
                                            @endif
                                        </select>

                                        @error('course_master_pk')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Previous Courses Column -->
                                <!-- Previous Courses Column -->
                                <div class="col-md-4">
                                    <label for="previous_courses" class="form-label">Select Previous Courses:</label>
                                    <div class="mb-3">
                                        <select id="previous_courses"
                                            class="form-select select2 @error('previous_courses') is-invalid @enderror"
                                            name="previous_courses[]" multiple data-placeholder="Select previous courses">
                                            @if (isset($previousCourses) && count($previousCourses) > 0)
                                                @foreach ($previousCourses as $previousCourse)
                                                    @if ($previousCourse->course)
                                                        <option value="{{ $previousCourse->course->pk }}"
                                                            data-course-pk="{{ $previousCourse->course->pk }}"
                                                            {{ in_array($previousCourse->pk, old('previous_courses', [])) ? 'selected' : '' }}>
                                                            {{ $previousCourse->course->course_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Select multiple courses</small>

                                        @error('previous_courses')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Services Column -->
                                <div class="col-md-4">
                                    <label for="services" class="form-label">Select Service:</label>
                                    <div class="mb-3">
                                        <select id="services"
                                            class="form-select select2 @error('services') is-invalid @enderror"
                                            name="services[]" multiple data-placeholder="Select services">
                                            @if (isset($services) && count($services) > 0)
                                                @foreach ($services as $service)
                                                    <option value="{{ $service->pk }}"
                                                        {{ in_array($service->pk, old('services', [])) ? 'selected' : '' }}>
                                                        {{ $service->service_name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Select multiple services</small>

                                        @error('services')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Student Data Table Section -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                Student List (<span id="studentCount">0</span>)
                                            </h5>
                                            <div class="d-flex">
                                                <input type="text" id="studentSearch"
                                                    class="form-control form-control-sm me-2"
                                                    placeholder="Search students..." style="width: 200px;">
                                                <button type="button" id="filterBtn" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-filter"></i> Filter
                                                </button>
                                            </div>
                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary">
                                                    Enroll
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" id="studentTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="50px">
                                                                <input type="checkbox" id="selectAll" checked>
                                                            </th>
                                                            <th>Course Name</th>
                                                            <th>Student Name</th>
                                                            <th>OT Code</th>
                                                            <th>Service</th>
                                                            <th width="100px">Actions</th> {{-- New column --}}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr id="noDataRow">
                                                            <td colspan="7" class="text-center">Select previous courses
                                                                and services to load students</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class="mt-3 d-flex justify-content-center">
                                                    <ul class="pagination" id="paginationContainer"></ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrolled Students Modal -->
    {{-- <div class="modal fade" id="enrolledModal" tabindex="-1" aria-labelledby="enrolledModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title" id="enrolledModalLabel">Enrolled Students</h5>
                        <div class="small text-white-50">Course: <strong id="modalCourseName">-</strong></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <input type="text" id="enrolledSearch" class="form-control"
                                placeholder="Search students...">
                        </div>
                        <div class="col-md-4 text-end">
                            <button id="refreshEnrolled" class="btn btn-primary">Refresh Data</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="enrolledTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Course</th>
                                    <th>Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody id="enrolledTableBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Select a course to load enrolled
                                        students</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="enrolledPaginationInfo" class="text-muted"></div>
                        <ul class="pagination mb-0" id="enrolledPagination"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <!-- Export PDF -->
                    <a href="{{ route('enrollment.exportEnrolled', ['type' => 'pdf']) }}&course_id={{ $course->id ?? '' }}"
                        class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>

                    <!-- Export Excel -->
                    <a href="{{ route('enrollment.exportEnrolled', ['type' => 'excel']) }}&course_id={{ $course->id ?? '' }}"
                        class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div> --}}
    <!-- Enrolled Students Modal -->
    <div class="modal fade" id="enrolledModal" tabindex="-1" aria-labelledby="enrolledModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title" id="enrolledModalLabel">Enrolled Students</h5>
                        <div class="small text-white-50" id="modalCourseDisplay"></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Course Selection and Search Row -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="modalCourseSelect" class="form-label">Select Course:</label>
                            <select id="modalCourseSelect" class="form-select select2">
                                <option value="">All Active Courses</option>
                                @if (isset($courses) && count($courses) > 0)
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->pk }}"
                                            {{ $course->pk == (session('selected_course') ?? old('course_master_pk')) ? 'selected' : '' }}>
                                            {{ $course->course_name }}
                                            @if ($course->couse_short_name)
                                                ({{ $course->couse_short_name }})
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="enrolledSearch" class="form-label">Search Students:</label>
                            <input type="text" id="enrolledSearch" class="form-control"
                                placeholder="Search by name, email, or phone...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="refreshEnrolled" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info p-2 mb-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Course:</strong> <span id="selectedCourseName">All Courses</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Total Students:</strong> <span id="totalStudentsCount">0</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Showing:</strong> <span id="showingRange">0-0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="enrolledTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Course</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="enrolledTableBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Select a course or keep "All Active Courses" to load students
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="enrolledPaginationInfo" class="text-muted"></div>
                        <ul class="pagination mb-0" id="enrolledPagination"></ul>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <!-- Export Buttons (dynamically updated with course ID) -->
                    <div id="exportButtons" style="display: none;">
                        <a href="#" id="exportPdfBtn" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                        <a href="#" id="exportExcelBtn" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            var editStudentBaseUrl = '{{ route('enrollment.edit', ':id') }}';

            // --- INITIALIZE ALL SELECT2 DROPDOWNS ---
            function initializeSelect2() {
                // Main form select2
                $('#course_master_pk').select2({
                    placeholder: "Select course",
                    allowClear: true,
                    width: '100%'
                });

                $('#previous_courses').select2({
                    placeholder: "Select previous courses",
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });

                $('#services').select2({
                    placeholder: "Select services",
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });

                // Modal course select - use the same courses as main form
                $('#modalCourseSelect').select2({
                    placeholder: "All Courses",
                    allowClear: true,
                    width: '100%'
                });
            }

            // Initialize on page load
            initializeSelect2();

            let currentPage = 1;
            const perPage = 10;
            let currentSearchTerm = '';
            let currentCourseFilter = null;

            // Function to get course name by ID
            function getCourseName(courseId) {
                if (!courseId) return 'All Courses';
                const option = $('#modalCourseSelect option[value="' + courseId + '"]');
                return option.length ? option.text() : 'Course #' + courseId;
            }

            // Function to update export URLs
           // Function to update export URLs
function updateExportUrls(courseId) {
    const baseUrl = '{{ route("enrollment.exportEnrolled") }}';
    
    if (courseId) {
        // For specific course
        const pdfUrl = `${baseUrl}?type=pdf&course=${courseId}`;
        const excelUrl = `${baseUrl}?type=excel&course=${courseId}`;
        
        $('#exportPdfBtn').attr('href', pdfUrl);
        $('#exportExcelBtn').attr('href', excelUrl);
    } else {
        // For "All Courses"
        const pdfUrl = `${baseUrl}?type=pdf`;
        const excelUrl = `${baseUrl}?type=excel`;
        
        $('#exportPdfBtn').attr('href', pdfUrl);
        $('#exportExcelBtn').attr('href', excelUrl);
    }
    
    // Always show export buttons
    $('#exportButtons').show();
}

            // Call this when modal opens and when course selection changes
            // Course selection change in modal - single event handler
            $('#modalCourseSelect').change(function() {
                currentCourseFilter = $(this).val();
                currentPage = 1;
                updateDisplayInfo();
                updateExportUrls(currentCourseFilter);
                loadEnrolledStudents();
            });

            // Search functionality with debounce
            let searchTimeout;
            $('#enrolledSearch').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearchTerm = $(this).val();
                    currentPage = 1;
                    loadEnrolledStudents();
                }, 300);
            });

            // Refresh button
            $('#refreshEnrolled').click(function() {
                currentPage = 1;
                loadEnrolledStudents();
            });

            // Update display information
         function updateDisplayInfo() {
    const courseName = getCourseName(currentCourseFilter);
    $('#selectedCourseName').text(courseName);
    $('#modalCourseDisplay').html(`<strong>Course:</strong> ${courseName}`);
    
    // Always update export URLs, but only show buttons if a course is selected
    updateExportUrls(currentCourseFilter);
}

            // -------------------------
            // AJAX: load enrolled students
            // -------------------------
            function loadEnrolledStudents() {
                $('#enrolledTableBody').html(
                    '<tr><td colspan="7" class="text-center">' +
                    '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
                    'Loading students...</td></tr>'
                );

                $.ajax({
                    url: '{{ route('enrollment.getEnrolled') }}',
                    method: 'GET',
                    data: {
                        course: currentCourseFilter,
                        search: currentSearchTerm,
                        page: currentPage,
                        per_page: perPage
                    },
                    success: function(response) {
                        console.log('API Response:', response); // Debug log

                        if (response.success) {
                            populateEnrolledTable(response.data || []);
                            setupEnrolledPagination(response.total || 0, response.current_page || 1,
                                response.last_page || 1);
                            updateStats(response.total || 0);
                        } else {
                            $('#enrolledTableBody').html(
                                '<tr><td colspan="7" class="text-center text-danger">' +
                                (response.message || 'Error loading data') + '</td></tr>'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr); // Debug log
                        $('#enrolledTableBody').html(
                            '<tr><td colspan="7" class="text-center text-danger">' +
                            'Error loading students data. Please try again.</td></tr>'
                        );
                    }
                });
            }

            function populateEnrolledTable(students) {
                const tableBody = $('#enrolledTableBody');
                tableBody.empty();

                if (!students || students.length === 0) {
                    tableBody.html(
                        '<tr><td colspan="7" class="text-center text-muted">' +
                        'No enrolled students found for the selected filters</td></tr>'
                    );
                    return;
                }

                let startNumber = (currentPage - 1) * perPage + 1;
                students.forEach(function(student, index) {
                    const rowNumber = startNumber + index;
                    const enrollmentDate = student.enrollment_date || 'N/A';
                    const courseName = student.course_name || 'N/A';

                    const row = '<tr>' +
                        '<td>' + rowNumber + '</td>' +
                        '<td>' + (student.name || '-') + '</td>' +
                        '<td>' + (student.email || '-') + '</td>' +
                        '<td>' + (student.phone || '-') + '</td>' +
                        '<td>' + courseName + '</td>' +
                        '<td>' + enrollmentDate + '</td>' +
                        '<td><span class="badge bg-success">Enrolled</span></td>' +
                        '</tr>';
                    tableBody.append(row);
                });
            }

            function updateStats(total) {
                const start = total ? (currentPage - 1) * perPage + 1 : 0;
                const end = Math.min(currentPage * perPage, total);
                $('#totalStudentsCount').text(total);
                $('#showingRange').text(total ? `${start} to ${end}` : '0-0');
            }

            function setupEnrolledPagination(total, curPage, lastPage) {
                currentPage = curPage || 1;
                const paginationContainer = $('#enrolledPagination');
                paginationContainer.empty();

                const start = total ? (currentPage - 1) * perPage + 1 : 0;
                const end = Math.min(currentPage * perPage, total);
                $('#enrolledPaginationInfo').text(total ? `Showing ${start} to ${end} of ${total} entries` : '');

                if (lastPage <= 1) {
                    // No pagination needed if only one page
                    return;
                }

                // Previous button
                const prevDisabled = currentPage === 1 ? 'disabled' : '';
                paginationContainer.append(
                    `<li class="page-item ${prevDisabled}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>`
                );

                // Page numbers - show limited pages
                const maxVisiblePages = 5;
                let startPage = 1;
                let endPage = lastPage;

                if (lastPage > maxVisiblePages) {
                    const halfVisible = Math.floor(maxVisiblePages / 2);
                    startPage = Math.max(1, currentPage - halfVisible);
                    endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);

                    if (endPage - startPage + 1 < maxVisiblePages) {
                        startPage = Math.max(1, endPage - maxVisiblePages + 1);
                    }
                }

                // First page ellipsis if needed
                if (startPage > 1) {
                    paginationContainer.append(
                        `<li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>`
                    );
                    if (startPage > 2) {
                        paginationContainer.append(
                            `<li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>`
                        );
                    }
                }

                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    const active = i === currentPage ? 'active' : '';
                    paginationContainer.append(
                        `<li class="page-item ${active}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`
                    );
                }

                // Last page ellipsis if needed
                if (endPage < lastPage) {
                    if (endPage < lastPage - 1) {
                        paginationContainer.append(
                            `<li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>`
                        );
                    }
                    paginationContainer.append(
                        `<li class="page-item">
                        <a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a>
                    </li>`
                    );
                }

                // Next button
                const nextDisabled = currentPage === lastPage ? 'disabled' : '';
                paginationContainer.append(
                    `<li class="page-item ${nextDisabled}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>`
                );

                // Attach click event
                $('.page-link[data-page]').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page && page > 0 && page !== currentPage && page <= lastPage) {
                        currentPage = page;
                        loadEnrolledStudents();
                        // Scroll to top of table
                        $('#enrolledTable').get(0).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            }

            // Auto-open modal after successful enrollment
            // Auto-open modal after successful enrollment
            @if (session('success') && session('selected_course'))
                $(document).ready(function() {
                    setTimeout(function() {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(document
                            .getElementById('enrolledModal'));

                        // Set the modal course select to the enrolled course
                        const enrolledCourseId = @json(session('selected_course'));
                        $('#modalCourseSelect').val(enrolledCourseId).trigger('change');

                        // Show the modal
                        modalInstance.show();

                        // Update export URLs immediately
                        updateExportUrls(enrolledCourseId);
                    }, 500);
                });
            @endif

            // ============ MAIN FORM FUNCTIONS (keep your existing ones) ============
            function filterPreviousCourses() {
                const selectedNewCourse = $('#course_master_pk').val();
                const previousCoursesSelect = $('#previous_courses');

                if (selectedNewCourse) {
                    previousCoursesSelect.select2('destroy');
                    previousCoursesSelect.find('option[value="' + selectedNewCourse + '"]').remove();
                    previousCoursesSelect.select2();
                    const currentlySelected = previousCoursesSelect.val();
                    if (currentlySelected && currentlySelected.includes(selectedNewCourse)) {
                        const newSelected = currentlySelected.filter(val => val !== selectedNewCourse);
                        previousCoursesSelect.val(newSelected).trigger('change');
                    }
                }
            }

            // Initial filter on page load
            filterPreviousCourses();

            // Filter when new course selection changes
            $('#course_master_pk').change(function() {
                filterPreviousCourses();
            });

            // Student list functions
            function updateSelectedStudents() {
                var selectedStudents = [];
                $('.student-checkbox:checked').each(function() {
                    selectedStudents.push($(this).val());
                });
                $('#selectedStudents').val(selectedStudents.join(','));
                $('#studentCount').text($('.student-checkbox').length || 0);
            }

            $('#selectAll').change(function() {
                $('.student-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedStudents();
            });

            $(document).on('change', '.student-checkbox', function() {
                if (!$(this).prop('checked')) {
                    $('#selectAll').prop('checked', false);
                } else if ($('.student-checkbox:checked').length === $('.student-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                }
                updateSelectedStudents();
            });

            $('#studentSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#studentTable tbody tr').filter(function() {
                    if ($(this).attr('id') === 'noDataRow') return true;
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Filter button to load students in main table
            $('#filterBtn').click(function() {
                loadStudents();
            });

            function loadStudents() {
                var previousCourses = $('#previous_courses').val();
                var services = $('#services').val();

                if (!previousCourses || previousCourses.length === 0) {
                    alert('Please select at least one previous course');
                    return;
                }

                $('#studentTable tbody').html(
                    '<tr><td colspan="7" class="text-center">Loading students...</td></tr>');

                $.ajax({
                    url: '{{ route('enrollment.filterStudents') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        previous_courses: previousCourses,
                        services: services
                    },
                    success: function(response) {
                        if (response.success) {
                            populateStudentTable(response.students);
                        } else {
                            $('#studentTable tbody').html('<tr><td colspan="7" class="text-center">' +
                                response.message + '</td></tr>');
                        }
                    },
                    error: function(xhr) {
                        $('#studentTable tbody').html(
                            '<tr><td colspan="7" class="text-center">Error loading students</td></tr>'
                        );
                        console.error('Error:', xhr.responseText);
                    }
                });
            }

            function populateStudentTable(students) {
                var tableBody = $('#studentTable tbody');
                tableBody.empty();

                if (!students || students.length === 0) {
                    tableBody.html(
                        '<tr id="noDataRow"><td colspan="6" class="text-center">No students found for the selected filters</td></tr>'
                    );
                    $('#studentCount').text(0);
                    return;
                }

                students.forEach(function(student) {
                    var editUrl = student.edit_url || '#';

                    var row = '<tr>' +
                        '<td><input type="checkbox" name="students[]" value="' + student.student_pk +
                        '" class="student-checkbox" checked></td>' +
                        '<td>' + (student.course_name || 'N/A') + '</td>' +
                        '<td>' + (student.student_name || 'N/A') + '</td>' +
                        '<td>' + (student.ot_code || 'N/A') + '</td>' +
                        '<td>' + (student.service_name || 'N/A') + '</td>' +
                        '<td>' +
                        '<a href="' + editUrl +
                        '" class="btn btn-sm btn-warning edit-btn" data-id="' + student.student_pk +
                        '" title="Edit Student">' +
                        '<i class="bi bi-pencil"></i> Edit' +
                        '</a>' +
                        '</td>' +
                        '</tr>';

                    tableBody.append(row);
                });

                $('#selectAll').prop('checked', true);
                updateSelectedStudents();
                $('#studentCount').text(students.length);
            }
        });
    </script>
@endsection
