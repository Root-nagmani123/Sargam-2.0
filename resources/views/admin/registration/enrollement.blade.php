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
                                <div class="col-md-4">
                                    <label for="previous_courses" class="form-label">Select Previous Courses:</label>
                                    <div class="mb-3">
                                        <select id="previous_courses"
                                            class="form-select select2 @error('previous_courses') is-invalid @enderror"
                                            name="previous_courses[]" multiple>
                                            @if (isset($previousCourses) && count($previousCourses) > 0)
                                                @foreach ($previousCourses as $previousCourse)
                                                    @if ($previousCourse->course)
                                                        <option value="{{ $previousCourse->course->pk }}"
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
                                            name="services[]" multiple>
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
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" id="studentTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="50px">
                                                                <input type="checkbox" id="selectAll" checked>
                                                            </th>
                                                            <th>Student PK</th>
                                                            <th>Course PK</th>
                                                            <th>Course Name</th>
                                                            <th>Student Name</th>
                                                            <th>OT Code</th>
                                                            <th>Service</th>
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

                            <hr>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    Enroll
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 NEW: Enrolled Students Modal (no course filter inside modal) -->
    <div class="modal fade" id="enrolledModal" tabindex="-1" aria-labelledby="enrolledModalLabel" aria-hidden="true">
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
                    <!-- removed course filter select; modal will show students for the course selected in main form -->
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
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // --- CONFIG & STATE ---
            let currentPage = 1;
            const perPage = 10;
            let currentSearchTerm = '';

            // initial course (priority: session selected_course > old input > currently selected option)
            let currentCourseFilter = @json(session('selected_course') ?? (old('course_master_pk') ?? null));

            // helper: set modal course name text
            function setModalCourseName() {
                let name = '-';
                if (currentCourseFilter) {
                    const opt = $('#course_master_pk').find('option[value="' + currentCourseFilter + '"]');
                    name = opt.length ? opt.text() : currentCourseFilter;
                } else {
                    // if main select has a value, use it
                    const mainVal = $('#course_master_pk').val();
                    if (mainVal) {
                        const opt = $('#course_master_pk option[value="' + mainVal + '"]');
                        name = opt.length ? opt.text() : mainVal;
                        currentCourseFilter = mainVal;
                    } else {
                        name = 'No course selected';
                    }
                }
                $('#modalCourseName').text(name);
            }

            setModalCourseName();

            // When main course select changes, update currentCourseFilter and modal label
            $('#course_master_pk').change(function() {
                currentCourseFilter = $(this).val() || null;
                setModalCourseName();
            });

            // -------------------------
            // Enrolled modal events
            // -------------------------
            // When modal shown, load students for the selected course
            const enrolledModalEl = document.getElementById('enrolledModal');
            enrolledModalEl.addEventListener('show.bs.modal', function() {
                // If no course selected, show message and return
                if (!currentCourseFilter) {
                    $('#enrolledTableBody').html(
                        '<tr><td colspan="6" class="text-center text-muted">Please select a course from the form to load enrolled students.</td></tr>'
                        );
                    $('#enrolledPaginationInfo').text('');
                    $('#enrolledPagination').empty();
                    return;
                }
                currentPage = 1;
                currentSearchTerm = $('#enrolledSearch').val() || '';
                loadEnrolledStudents();
            });

            // refresh/search/refresh button
            $('#refreshEnrolled').click(function() {
                currentPage = 1;
                loadEnrolledStudents();
            });
            $('#enrolledSearch').on('keyup', function() {
                currentSearchTerm = $(this).val();
                currentPage = 1;
                loadEnrolledStudents();
            });

            // -------------------------
            // AJAX: load enrolled students for a specific course
            // -------------------------
            function loadEnrolledStudents() {
                $('#enrolledTableBody').html(
                    '<tr><td colspan="6" class="text-center">Loading enrolled students...</td></tr>');
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
                        if (response.success) {
                            populateEnrolledTable(response.data || []);
                            setupEnrolledPagination(response.total || 0, response.current_page || 1,
                                response.last_page || 1);
                        } else {
                            $('#enrolledTableBody').html(
                                '<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>'
                                );
                        }
                    },
                    error: function(xhr) {
                        $('#enrolledTableBody').html(
                            '<tr><td colspan="6" class="text-center text-danger">Error loading enrolled students</td></tr>'
                            );
                        console.error('Error:', xhr.responseText);
                    }
                });
            }

            function populateEnrolledTable(students) {
                const tableBody = $('#enrolledTableBody');
                tableBody.empty();

                if (!students || students.length === 0) {
                    tableBody.html(
                        '<tr><td colspan="6" class="text-center text-muted">No enrolled students found</td></tr>'
                        );
                    return;
                }

                let startNumber = (currentPage - 1) * perPage + 1;
                students.forEach(function(student, index) {
                    const row = '<tr>' +
                        '<td>' + (startNumber + index) + '</td>' +
                        '<td>' + (student.name || '-') + '</td>' +
                        '<td>' + (student.email || '-') + '</td>' +
                        '<td>' + (student.phone || '-') + '</td>' +
                        '<td>' + (student.course_name || '-') + '</td>' +
                        '<td>' + (student.enrollment_date || '-') + '</td>' +
                        '</tr>';
                    tableBody.append(row);
                });
            }

            function setupEnrolledPagination(total, curPage, lastPage) {
                currentPage = curPage || 1;
                const paginationContainer = $('#enrolledPagination');
                paginationContainer.empty();

                const start = (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, total);
                $('#enrolledPaginationInfo').text(total ? `Showing ${start} to ${end} of ${total} entries` : '');

                // Previous
                const prevDisabled = currentPage === 1 ? 'disabled' : '';
                paginationContainer.append(
                    `<li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>`
                );

                // Page numbers (limiting to first 10 pages to avoid huge lists; adjust as needed)
                const maxPagesToShow = 10;
                let startPage = 1,
                    endPage = lastPage;
                if (lastPage > maxPagesToShow) {
                    startPage = Math.max(1, currentPage - 4);
                    endPage = Math.min(lastPage, startPage + maxPagesToShow - 1);
                }
                for (let i = startPage; i <= endPage; i++) {
                    const active = i === currentPage ? 'active' : '';
                    paginationContainer.append(
                        `<li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`
                    );
                }

                // Next
                const nextDisabled = currentPage === lastPage ? 'disabled' : '';
                paginationContainer.append(
                    `<li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>`
                );

                // Click handler
                $('.page-link').off('click').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page && page > 0 && page !== currentPage) {
                        currentPage = page;
                        loadEnrolledStudents();
                    }
                });
            }

            // Export button: export current course (no in-modal filter)
            $('#exportEnrolledBtn').click(function() {
                if (!currentCourseFilter) {
                    alert('Please select a course to export.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'GET';
                form.action = '{{ route('enrollment.exportEnrolled') }}';

                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course';
                courseInput.value = currentCourseFilter;
                form.appendChild(courseInput);

                // include search filter if you want
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = currentSearchTerm || '';
                form.appendChild(searchInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });

            // -------------------------
            // Student list (main page) interactions: select all, load filtered students
            // Cleaned duplicate functions and kept logic
            // -------------------------
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
                        '<tr id="noDataRow"><td colspan="7" class="text-center">No students found for the selected filters</td></tr>'
                        );
                    $('#studentCount').text(0);
                    return;
                }

                students.forEach(function(student) {
                    var row = '<tr>' +
                        '<td><input type="checkbox" name="students[]" value="' + student.student_pk +
                        '" class="student-checkbox" checked></td>' +
                        '<td>' + student.student_pk + '</td>' +
                        '<td>' + student.course_pk + '</td>' +
                        '<td>' + student.course_name + '</td>' +
                        '<td>' + student.student_name + '</td>' +
                        '<td>' + student.ot_code + '</td>' +
                        '<td>' + (student.service_name ?? '') + '</td>' +
                        '</tr>';

                    tableBody.append(row);
                });

                $('#selectAll').prop('checked', true);
                updateSelectedStudents();
                $('#studentCount').text(students.length);
            }

            // -------------------------
            // Auto-open modal after successful enrollment (if session('success') and session('selected_course') exist)
            // -------------------------
            @if (session('success') && session('selected_course'))
                // ensure modal label is up-to-date
                currentCourseFilter = @json(session('selected_course'));
                setModalCourseName();

                // Bootstrap 5-safe show
                const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('enrolledModal'));
                modalInstance.show();
            @endif
        });
    </script>
@endsection
