@extends('admin.layouts.master')

@section('title', 'Enrollment - Sargam | Lal Bahadur')

@section('setup_content')
    @include('admin.partials.choices-bootstrap5')
    <div id="enrollment-page" class="container-fluid choices-bs-scope">
        <x-breadcrum title="Enroll to New Course" />
        <x-session_message />
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card" >
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
                                <ul class="mb-0">
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
                                            class="form-select @error('course_master_pk') is-invalid @enderror"
                                            name="course_master_pk" required data-placeholder="Select course" data-search="true">
                                            <option value="">Select Course</option>
                                            @if (isset($courses) && $courses->count())
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->pk }}"
                                                        data-short="{{ e($course->couse_short_name ?? '') }}"
                                                        {{ (string) old('course_master_pk') === (string) $course->pk || (session('selected_course') == $course->pk) ? 'selected' : '' }}>
                                                        {{ $course->course_name }}@if ($course->couse_short_name) ({{ $course->couse_short_name }})@endif
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
                                            class="form-select @error('previous_courses') is-invalid @enderror"
                                            name="previous_courses[]" multiple data-placeholder="Select previous courses" data-search="true">
                                            @if (isset($previousCourses) && $previousCourses->count())
                                                @foreach ($previousCourses as $prev)
                                                    <option value="{{ $prev->pk }}"
                                                        data-short="{{ e($prev->couse_short_name ?? '') }}"
                                                        {{ in_array((string)$prev->pk, old('previous_courses', [])) ? 'selected' : '' }}>
                                                        {{ $prev->course_name }}@if ($prev->couse_short_name) ({{ $prev->couse_short_name }})@endif
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Search by course name or short name. Select multiple courses.</small>

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
                                            class="form-select @error('services') is-invalid @enderror"
                                            name="services[]" multiple data-placeholder="Select services" data-search="true">
                                            @if (isset($services) && $services->count())
                                                @foreach ($services as $service)
                                                    <option value="{{ $service->pk }}"
                                                        data-short="{{ e($service->service_short_name ?? '') }}"
                                                        {{ in_array($service->pk, old('services', [])) ? 'selected' : '' }}>
                                                        {{ $service->service_name }}@if ($service->service_short_name) ({{ $service->service_short_name }})@endif
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Search by service name or short name. Select multiple services.</small>

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
                                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <h5 class="mb-0">
                                                Student List (<span id="studentCount">0</span>)
                                            </h5>
                                            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                                                <button type="button" id="filterBtn" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-filter"></i> Filter
                                                </button>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Enroll
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive enrollment-dt-wrap">
                                                <table class="table table-striped table-hover table-sm align-middle w-100 dt-legacy-layout" id="studentTable" data-sargam-dt-ui="false">
                                                    <thead>
                                                        <tr>
                                                            <th width="50px" class="align-middle">
                                                                <input type="checkbox" class="form-check-input js-select-all-students" title="Select all students" aria-label="Select all students">
                                                            </th>
                                                            <th>Course Name</th>
                                                            <th>Student Name</th>
                                                            <th>OT Code</th>
                                                            <th>Service</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr id="noDataRow">
                                                            <td colspan="5" class="text-center">Select previous courses
                                                                and services to load students</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
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
                            <select id="modalCourseSelect" class="form-select" data-placeholder="All Active Courses" data-search="true">
                                <option value="">All Active Courses</option>
                                @if (isset($courses) && $courses->count())
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->pk }}"
                                            data-short="{{ e($course->couse_short_name ?? '') }}"
                                            {{ $course->pk == (session('selected_course') ?? old('course_master_pk')) ? 'selected' : '' }}>
                                            {{ $course->course_name }}@if ($course->couse_short_name) ({{ $course->couse_short_name }})@endif
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
                    <div class="table-responsive enrollment-dt-wrap">
                        <table class="table table-striped table-hover table-sm align-middle w-100" id="enrolledTable">
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
    </div>
@endsection

@push('scripts')
<script>
    $(function() {
        var editStudentBaseUrl = '{{ route('enrollment.edit', ':id') }}';

        var enrollmentRoot = document.getElementById('enrollment-page');
        if (enrollmentRoot && typeof window.initChoicesBootstrap5In === 'function') {
            window.initChoicesBootstrap5In(enrollmentRoot);
        }

        function formatNameWithShort(name, short) {
            if (!name) return 'N/A';
            var s = (short || '').trim();
            return s ? name + ' (' + s + ')' : name;
        }

        function refreshChoicesSelect(selectId) {
            var el = document.getElementById(selectId);
            if (el && typeof window.reinitChoicesBootstrap5 === 'function') {
                window.reinitChoicesBootstrap5(el);
            }
        }

        function setModalCourseValue(value) {
            var el = document.getElementById('modalCourseSelect');
            if (!el) return;
            var v = value != null && value !== '' ? String(value) : '';
            el.value = v;
            if (el._choicesBs && typeof el._choicesBs.setChoiceByValue === 'function') {
                try {
                    el._choicesBs.setChoiceByValue(v);
                } catch (e) {
                    /* keep native value */
                }
            }
            $(el).trigger('change');
        }

        // ── Student picker: server-side DataTable + selection that survives paging ──
        //
        // The list used to load every matching student at once and page them in the
        // browser, so "checked" could simply be read back off the DOM. With
        // server-side paging only one page of rows exists at a time, so the chosen
        // students are tracked in a Set of pks instead and re-applied on every draw.
        var studentTable = null;
        var selectedStudentPks = new Set();
        var lastFilters = { previous_courses: [], services: [] };

        function destroyStudentDataTableIfAny() {
            if ($.fn.dataTable.isDataTable('#studentTable')) {
                $('#studentTable').DataTable().destroy();
                $('#studentTable tbody').empty();
            }
            studentTable = null;
        }

        function updateSelectedStudents() {
            var pks = Array.from(selectedStudentPks);
            $('#selectedStudents').val(pks.join(','));
            $('#studentCount').text(pks.length);

            // Header checkbox reflects "is every row on this page selected?"
            var $boxes = $('#studentTable tbody .student-checkbox');
            var allOnPage = $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length;
            $('.js-select-all-students').prop('checked', allOnPage);
        }

        // Re-tick the boxes for rows that are in the selected set.
        function applySelectionToPage() {
            $('#studentTable tbody .student-checkbox').each(function () {
                $(this).prop('checked', selectedStudentPks.has(parseInt(this.value, 10)));
            });
            updateSelectedStudents();
        }

        function initStudentDataTable() {
            destroyStudentDataTableIfAny();

            studentTable = $('#studentTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                searching: true,
                ordering: true,
                info: true,
                paging: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[2, 'asc']],
                ajax: {
                    url: '{{ route('enrollment.studentsData') }}',
                    type: 'GET',
                    data: function (d) {
                        d.previous_courses = lastFilters.previous_courses;
                        d.services = lastFilters.services;
                    }
                },
                columns: [
                    { data: 'select',       name: 'select',       orderable: false, searchable: false, className: 'text-center' },
                    { data: 'course_name',  name: 'course_name' },
                    { data: 'student_name', name: 'student_name' },
                    { data: 'ot_code',      name: 'ot_code' },
                    { data: 'service',      name: 'service' }
                ],
                // Layout: per-page selector on top (left) with the search box beside it;
                // record info bottom-left and pagination bottom-RIGHT.
                dom: "<'row mb-2 g-2 align-items-center'<'col-sm-12 col-md-6 d-flex align-items-center gap-2'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-2 align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
                language: {
                    processing: 'Loading…',
                    search: 'Search:',
                    searchPlaceholder: 'Filter students…',
                    lengthMenu: 'Show _MENU_',
                    emptyTable: 'No students loaded',
                    zeroRecords: 'No matching students',
                    info: 'Showing _START_ to _END_ of _TOTAL_ student(s)',
                    infoEmpty: 'No students',
                    infoFiltered: '(filtered from _MAX_ total)'
                },
                drawCallback: function () {
                    applySelectionToPage();
                }
            });
        }

        function destroyEnrolledDataTableIfAny() {
            if ($.fn.dataTable.isDataTable('#enrolledTable')) {
                $('#enrolledTable').DataTable().destroy();
            }
        }

        function enrolledTableHasOnlyPlaceholderRow() {
            var $rows = $('#enrolledTable tbody tr');
            if ($rows.length !== 1) return false;
            return $rows.find('td[colspan]').length > 0;
        }

        function initEnrolledDataTable() {
            destroyEnrolledDataTableIfAny();
            if (enrolledTableHasOnlyPlaceholderRow()) return;
            if (!$('#enrolledTable tbody tr').length) return;

            $('#enrolledTable').DataTable({
                responsive: true,
                autoWidth: false,
                paging: false,
                searching: false,
                ordering: true,
                info: false,
                order: [[1, 'asc']],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 6]
                }],
                dom: 'rt'
            });
        }

        $('#enrolledModal').on('shown.bs.modal', function() {
            if ($.fn.dataTable.isDataTable('#enrolledTable')) {
                $('#enrolledTable').DataTable().columns.adjust().responsive.recalc();
            }
        });

        // ---------- Variables for enrolled modal ----------
        let currentPage = 1;
        const perPage = 10;
        let currentSearchTerm = '';
        let currentCourseFilter = null;

        function getCourseName(courseId) {
            if (!courseId) return 'All Courses';
            const option = $('#modalCourseSelect option[value="' + courseId + '"]');
            return option.length ? option.text() : 'Course #' + courseId;
        }

        function updateExportUrls(courseId) {
            const baseUrl = '{{ route("enrollment.exportEnrolled") }}';

            if (courseId) {
                const pdfUrl = `${baseUrl}?type=pdf&course=${courseId}`;
                const excelUrl = `${baseUrl}?type=excel&course=${courseId}`;
                $('#exportPdfBtn').attr('href', pdfUrl);
                $('#exportExcelBtn').attr('href', excelUrl);
            } else {
                const pdfUrl = `${baseUrl}?type=pdf`;
                const excelUrl = `${baseUrl}?type=excel`;
                $('#exportPdfBtn').attr('href', pdfUrl);
                $('#exportExcelBtn').attr('href', excelUrl);
            }
            $('#exportButtons').show();
        }

        // modal course change
        $('#modalCourseSelect').on('change', function() {
            currentCourseFilter = $(this).val();
            currentPage = 1;
            updateDisplayInfo();
            loadEnrolledStudents();
            updateExportUrls(currentCourseFilter);
        });

        // debounce search
        let searchTimeout;
        $('#enrolledSearch').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearchTerm = $(this).val();
                currentPage = 1;
                loadEnrolledStudents();
            }, 300);
        });

        $('#refreshEnrolled').on('click', function() {
            currentPage = 1;
            loadEnrolledStudents();
        });

        function updateDisplayInfo() {
            const courseName = getCourseName(currentCourseFilter);
            $('#selectedCourseName').text(courseName);
            $('#modalCourseDisplay').html(`<strong>Course:</strong> ${courseName}`);
        }

        function loadEnrolledStudents() {
            destroyEnrolledDataTableIfAny();
            $('#enrolledTableBody').html(
                '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading students...</td></tr>'
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
                    console.log('Enrolled Students Response:', response); // Debug
                    if (response.success) {
                        populateEnrolledTable(response.data || []);
                        setupEnrolledPagination(response.total || 0, response.current_page || 1,
                            response.last_page || 1);
                        updateStats(response.total || 0);
                    } else {
                        destroyEnrolledDataTableIfAny();
                        $('#enrolledTableBody').html('<tr><td colspan="7" class="text-center text-danger">' + (response.message || 'Error loading data') + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Enrolled Students AJAX Error:', error, xhr.responseText); // Debug
                    destroyEnrolledDataTableIfAny();
                    $('#enrolledTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading students data. Please try again.</td></tr>');
                }
            });
        }

        function populateEnrolledTable(students) {
            const tableBody = $('#enrolledTableBody');
            destroyEnrolledDataTableIfAny();
            tableBody.empty();

            if (!students || students.length === 0) {
                tableBody.html('<tr><td colspan="7" class="text-center text-muted">No enrolled students found for the selected filters</td></tr>');
                return;
            }

            let startNumber = (currentPage - 1) * perPage + 1;
            students.forEach(function(student, index) {
                const rowNumber = startNumber + index;
                const enrollmentDate = student.enrollment_date || 'N/A';
                const courseName = student.course_name || 'N/A';

                const row = `<tr>
                    <td>${rowNumber}</td>
                    <td>${student.name || '-'}</td>
                    <td>${student.email || '-'}</td>
                    <td>${student.phone || '-'}</td>
                    <td>${courseName}</td>
                    <td>${enrollmentDate}</td>
                    <td><span class="badge bg-success">Enrolled</span></td>
                </tr>`;
                tableBody.append(row);
            });

            initEnrolledDataTable();
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

            if (lastPage <= 1) return;

            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            paginationContainer.append(`<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="${currentPage - 1}"><i class="bi bi-chevron-left"></i></a></li>`);

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

            if (startPage > 1) {
                paginationContainer.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
                if (startPage > 2) {
                    paginationContainer.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const active = i === currentPage ? 'active' : '';
                paginationContainer.append(`<li class="page-item ${active}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
            }

            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    paginationContainer.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                }
                paginationContainer.append(`<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`);
            }

            const nextDisabled = currentPage === lastPage ? 'disabled' : '';
            paginationContainer.append(`<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="${currentPage + 1}"><i class="bi bi-chevron-right"></i></a></li>`);

            // attach
            $('.page-link[data-page]').off('click').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page > 0 && page !== currentPage && page <= lastPage) {
                    currentPage = page;
                    loadEnrolledStudents();
                    $('#enrolledTable').get(0).scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        // Auto-open modal after successful enrollment
        @if (session('success') && session('selected_course'))
            $(function() {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('enrolledModal'));
                const enrolledCourseId = @json(session('selected_course'));
                setModalCourseValue(enrolledCourseId);
                modalInstance.show();
                updateExportUrls(enrolledCourseId);
            });
        @endif

        // ============ MAIN FORM FUNCTIONS ============

        // Remove the option of the newly selected course from previous courses (so user can't mark new course as previous)
        function filterPreviousCourses() {
            const selectedNewCourse = $('#course_master_pk').val();
            const previousCoursesSelect = $('#previous_courses');

            // Rebuild the select options (keep original options) by enabling/disabling the chosen one
            previousCoursesSelect.find('option').each(function() {
                const val = $(this).val();
                if (val === selectedNewCourse) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });

            refreshChoicesSelect('previous_courses');
            previousCoursesSelect.trigger('change');
        }

        filterPreviousCourses();
        $('#course_master_pk').on('change', filterPreviousCourses);

        // Student list: select tracking
        // Select-all applies to EVERY matching student, not just the visible page:
        // with server-side paging the browser only holds one page, so the full pk
        // list is fetched from the server (mirrors the old "all checked" default).
        $('#studentTable').on('change', '.js-select-all-students', function () {
            var checked = $(this).prop('checked');

            if (!checked) {
                selectedStudentPks.clear();
                applySelectionToPage();
                return;
            }

            if (!lastFilters.previous_courses.length) { return; }

            $.ajax({
                url: '{{ route('enrollment.studentsAllPks') }}',
                method: 'GET',
                data: {
                    previous_courses: lastFilters.previous_courses,
                    services: lastFilters.services
                },
                success: function (res) {
                    if (res && res.success) {
                        (res.pks || []).forEach(function (pk) { selectedStudentPks.add(parseInt(pk, 10)); });
                    }
                    applySelectionToPage();
                },
                error: function () { applySelectionToPage(); }
            });
        });

        // Individual row toggle updates the tracked set (survives paging/search).
        $(document).on('change', '#studentTable tbody .student-checkbox', function () {
            var pk = parseInt(this.value, 10);
            if ($(this).prop('checked')) {
                selectedStudentPks.add(pk);
            } else {
                selectedStudentPks.delete(pk);
            }
            updateSelectedStudents();
        });

        // Filter button loads students via ajax - FIXED VERSION
        $('#filterBtn').on('click', function() {
            console.log('Filter button clicked'); // Debug
            loadStudents();
        });

        function loadStudents() {
            var previousCourses = $('#previous_courses').val() || [];
            var services = $('#services').val() || [];

            if (!previousCourses.length) {
                alert('Please select at least one previous course');
                return;
            }

            // Filters changed → previous selection no longer applies (same as the
            // old behaviour, which rebuilt the table and re-checked everything).
            lastFilters = { previous_courses: previousCourses, services: services };
            selectedStudentPks.clear();

            if (!studentTable) {
                initStudentDataTable();
            } else {
                studentTable.ajax.reload(null, true);
            }

            // Preserve the old default of "everything matching is selected".
            $.ajax({
                url: '{{ route('enrollment.studentsAllPks') }}',
                method: 'GET',
                data: { previous_courses: previousCourses, services: services },
                success: function (res) {
                    if (res && res.success) {
                        (res.pks || []).forEach(function (pk) { selectedStudentPks.add(parseInt(pk, 10)); });
                    }
                    applySelectionToPage();
                },
                error: function () { applySelectionToPage(); }
            });
        }

        // Optional: Auto-load students when both previous courses and services are selected
        $('#previous_courses, #services').on('change', function() {
            var previousCourses = $('#previous_courses').val() || [];
            var services = $('#services').val() || [];
            
            // Only auto-load if at least one previous course is selected
            if (previousCourses.length > 0) {
                console.log('Auto-loading students...');
                loadStudents();
            }
        });
    });
</script>
@endpush
