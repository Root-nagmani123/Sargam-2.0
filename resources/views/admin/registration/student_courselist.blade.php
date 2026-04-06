@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('setup_content')
    <div class="container-fluid py-4" style="background-color: #f8f9fa;">
        <div class="mb-4">
            <x-breadcrum title="Course Wise OTs List" />
        </div>
        <x-session_message />

        {{-- Filters + Counts + Export --}}
        <div class="card shadow-lg mb-4 border-0 animate-fade-in" style="border-left: 5px solid #004a93; border-radius: 15px;">
            <div class="card-header bg-gradient-primary py-4" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%); border-radius: 15px 15px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3">
                        <i class="fas fa-filter fa-lg text-white"></i>
                    </div>
                    <h5 class="mb-0 text-white fw-bold">Filters & Actions</h5>
                </div>
            </div>
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
            <div class="row align-items-end g-4">
                <!-- Filters Section -->
                <div class="col-lg-6 col-md-12">
                    <form id="filterForm" method="GET">
                        <div class="row g-3">
                            <!-- Course Status Filter - Buttons -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                                    <i class="fas fa-list-check me-2 text-primary"></i>COURSE TYPE
                                </label>
                                <div class="btn-group w-100 shadow" role="group" aria-label="Course type filter" style="border-radius: 10px; overflow: hidden;">
                                    <input type="radio" class="btn-check" name="course_status" id="course_status_active"
                                        value="active" {{ $courseStatus === 'active' ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-success btn-lg custom-toggle-btn" for="course_status_active">
                                        <i class="fas fa-check-circle me-2"></i>Active Courses
                                    </label>

                                    <input type="radio" class="btn-check" name="course_status" id="course_status_inactive"
                                        value="inactive" {{ $courseStatus === 'inactive' ? 'checked' : '' }}
                                        autocomplete="off">
                                    <label class="btn btn-outline-danger btn-lg custom-toggle-btn" for="course_status_inactive">
                                        <i class="fas fa-archive me-2"></i>Archived Courses
                                    </label>
                                </div>
                            </div>

                            <!-- Course Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.9rem;">
                                    <i class="fas fa-graduation-cap me-2 text-primary"></i>Filter by Course
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0" style="border: 2px solid #e0e0e0; border-right: none;">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <select name="course_id" id="course_id" class="form-select form-select-lg shadow-sm custom-select" style="border-left: none;">
                                        <option value="">-- Select Course --</option>
                                        @foreach ($courses as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size: 0.9rem;">
                                    <i class="fas fa-toggle-on me-2 text-primary"></i>Enrollment Status
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0" style="border: 2px solid #e0e0e0; border-right: none;">
                                        <i class="fas fa-filter text-muted"></i>
                                    </span>
                                    <select name="status" id="status" class="form-select form-select-lg shadow-sm custom-select" style="border-left: none;">
                                        <option value="">-- All Status --</option>
                                        <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Total Count -->
                <div class="col-lg-2 col-md-12 text-center">
                    <div class="stats-card h-100">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Total Records</div>
                            <div class="stats-value">
                                <span id="filteredCount" class="counter">{{ $filteredCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="col-lg-4 col-md-12">
                    <div class="row g-3">
                        <!-- Import Button -->
                        <div class="col-md-6">
                            <div class="action-card">
                                <button type="button" class="btn btn-import w-100 btn-lg shadow" data-bs-toggle="modal"
                                    data-bs-target="#importModal">
                                    <div class="btn-icon">
                                        <i class="fas fa-file-import"></i>
                                    </div>
                                    <div class="btn-text">
                                        <div class="btn-title">Import Data</div>
                                        <small class="btn-subtitle">Upload OT List</small>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('studentEnroll.report.export') }}" id="exportForm">
                                <input type="hidden" name="course" id="exportCourse" value="{{ $courseId }}">
                                <input type="hidden" name="status" id="exportStatus" value="{{ $status }}">
                                <input type="hidden" name="course_status" id="exportCourseStatus" value="{{ $courseStatus }}">

                                <div class="action-card">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark" style="font-size: 0.85rem;">
                                            <i class="fas fa-file-export me-2 text-success"></i>SELECT FORMAT
                                        </label>
                                        <select name="format" class="form-select form-select-lg shadow custom-select" required id="exportFormat">
                                            <option value="">Choose export type...</option>
                                            <option value="pdf">📄 PDF Document</option>
                                            <option value="xlsx">📊 Excel Spreadsheet</option>
                                            <option value="csv">📝 CSV File</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-export w-100 btn-lg shadow" id="exportBtn">
                                        <div class="btn-icon">
                                            <i class="fas fa-download"></i>
                                        </div>
                                        <div class="btn-text">
                                            <div class="btn-title">Export Data</div>
                                            <small class="btn-subtitle">Download Records</small>
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div> <!-- End of filter card -->

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="modal-title fw-bold" id="importModalLabel">
                            <i class="fas fa-file-import me-2"></i>Import OT Codes to Course Wise OT List
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.enrollment.import') }}" method="POST"
                        enctype="multipart/form-data" id="importForm">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="alert alert-info border-0 shadow-sm" style="background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);">
                                        <h6 class="alert-heading fw-bold text-info-emphasis">
                                            <i class="fas fa-info-circle me-2"></i>Import Instructions
                                        </h6>
                                        <p class="mb-3 fw-semibold">Your Excel file should have these columns:</p>
                                        <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle mb-0 shadow-sm">
                                            <thead class="table-dark" style="background: linear-gradient(135deg, #af2910 0%, #8b1e0f 100%);">
                                                <tr>
                                                    <th class="fw-bold">Excel Column</th>
                                                    <th class="fw-bold">Description</th>
                                                    <th class="fw-bold text-center">Required</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="bg-light p-1 rounded">student_master_pk</code></td>
                                                    <td class="text-muted">Student ID number</td>
                                                    <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light p-1 rounded">course_master_pk</code></td>
                                                    <td class="text-muted">Course ID number</td>
                                                    <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light p-1 rounded">OT Code</code></td>
                                                    <td class="text-muted">OT Code value (max 20 chars)</td>
                                                    <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        </div>
                                        <div class="alert alert-warning border-0 mt-3 mb-0" role="alert">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            <strong>Pro Tip:</strong> Export data first, edit the OT Code column, then import the same file back.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="import_file" class="form-label fw-bold text-secondary">
                                    <i class="fas fa-file-excel me-2 text-success"></i>Select Excel/CSV File
                                </label>
                                <input type="file" class="form-control form-control-lg shadow-sm" name="import_file" id="import_file"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-check-circle text-success me-1"></i>Supported formats: .xlsx, .xls, .csv
                                    </small>
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-database text-info me-1"></i>Maximum file size: 5MB
                                    </small>
                                    <small class="text-danger d-block">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Do not modify student_master_pk or course_master_pk columns
                                    </small>
                                </div>
                            </div>

                            @if (session('import_errors'))
                                <div class="alert alert-danger border-0 shadow-sm mt-3" role="alert">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="alert-heading mb-0 fw-bold">
                                            <i class="fas fa-exclamation-circle me-2"></i>Import Errors ({{ count(session('import_errors')) }})
                                        </h6>
                                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                                    </div>
                                    <div class="mt-3" style="max-height: 200px; overflow-y: auto;">
                                        <ul class="list-unstyled mb-0">
                                            @foreach (session('import_errors') as $error)
                                                <li class="mb-2 p-2 bg-white rounded">
                                                    <i class="fas fa-times-circle text-danger me-2"></i>{{ $error }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="importSubmitBtn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i class="fas fa-upload me-2"></i>Import to OT List
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Alert --}}
        @if ($courseStatus === 'inactive')
            <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show" role="alert" style="background: linear-gradient(135deg, #fff9e6 0%, #ffe7b3 100%);">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-archive fa-2x text-warning me-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading fw-bold mb-1">Viewing Archived Courses</h6>
                        <p class="mb-0">You are currently viewing courses that have been archived. Switch to "Active Courses" to see current courses.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        {{-- Data Table --}}
        <div class="card shadow-lg border-0 animate-fade-in">
            <div class="card-header bg-gradient-primary py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon-box me-3">
                            <i class="fas fa-table fa-lg text-white"></i>
                        </div>
                        <div>
                             <h5 class="mb-0 text-white fw-bold">Course Wise OT Records</h5>
                            <small class="text-white-50">View and manage student enrollments</small>
                        </div>
                    </div>
                    <div class="text-white">
                        <i class="fas fa-database fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 modern-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th class="fw-bold" style="padding: 1rem;">S.No</th>
                                <th class="fw-bold" style="padding: 1rem;">Student</th>
                                <th class="fw-bold" style="padding: 1rem;">Course</th>
                                <th class="fw-bold" style="padding: 1rem;">Service</th>
                                <th class="fw-bold" style="padding: 1rem;">OT Code</th>
                                <th class="fw-bold" style="padding: 1rem;">Rank</th>
                                <th class="fw-bold" style="padding: 1rem;">Status</th>
                                <th class="fw-bold text-center" style="padding: 1rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

  @section('scripts')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        let dataTable = null;
        console.log('Document ready - Initializing...');

        // Debug function to check DataTable state
        function debugDataTable() {
            console.log('DataTable status:', dataTable ? 'Initialized' : 'Not initialized');
            if (dataTable) {
                console.log('DataTable settings:', dataTable.settings()[0]);
                console.log('Current filters - Course ID:', $('#course_id').val(), 'Status:', $('#status').val());
            }
        }

        // --------------------------
        // Load courses on page load
        //---------------------------
        updateCourseDropdown($('input[name="course_status"]:checked').val());

        // Function to update course dropdown
        function updateCourseDropdown(courseStatus) {
            console.log('Updating course dropdown for status:', courseStatus);
            $.ajax({
                url: "{{ route('student.courses') }}",
                type: "GET",
                data: {
                    course_status: courseStatus,
                    ajax_courses: true
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    console.log('Course dropdown update success:', response);
                    const courseSelect = $('#course_id');
                    courseSelect.empty().append('<option value="">-- Select Course --</option>');

                    $.each(response.courses, function(id, name) {
                        courseSelect.append(new Option(name, id));
                    });

                    $("#exportCourseStatus").val(courseStatus);
                    syncExportFields();
                    
                    // Auto-initialize DataTable if a course is selected
                    if ($('#course_id').val() !== '') {
                        console.log('Course already selected, initializing DataTable...');
                        initializeDataTable();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Course dropdown AJAX error:', error);
                    console.log('Response:', xhr.responseText);
                }
            });
        }

        // --------------------------
        // Sync Export Hidden Inputs
        //--------------------------
        function syncExportFields() {
            $("#exportCourse").val($("#course_id").val());
            $("#exportStatus").val($("#status").val());
            $("#exportCourseStatus").val($('input[name="course_status"]:checked').val());
        }

        // Call sync when filters changed
        $("#course_id, #status").on("change", syncExportFields);
        $('input[name="course_status"]').on("change", syncExportFields);

        // Initial sync
        syncExportFields();

        // --------------------------
        // Initialize DataTable
        //--------------------------
        function initializeDataTable() {
            console.log('initializeDataTable called');
            
            // If DataTable is already initialized, just reload the data
            if (dataTable !== null && $.fn.DataTable.isDataTable('#studentsTable')) {
                console.log('DataTable already exists, reloading...');
                dataTable.ajax.reload();
                return;
            }

            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#studentsTable')) {
                console.log('Destroying existing DataTable...');
                $('#studentsTable').DataTable().destroy();
                // Clear the table body to reset layout
                $('#studentsTable tbody').empty();
            }

            console.log('Initializing new DataTable...');
            // Initialize DataTable
            dataTable = $('#studentsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ordering: true,
                autoWidth: false,
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: "{{ route('student.courses') }}",
                    type: "GET",
                    data: function(d) {
                        d.course_id = $('#course_id').val();
                        d.status = $('#status').val();
                        d.course_status = $('input[name="course_status"]:checked').val();
                        console.log('AJAX request data:', d);
                    },
                    dataSrc: function(json) {
                        console.log('AJAX response received:', json);
                        $('#filteredCount').text(json.recordsTotal || 0);
                        return json.data || [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX error:', error);
                        console.error('Error details:', thrown);
                        console.log('Response:', xhr.responseText);
                        alert('Error loading data. Please check console for details.');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '60px' },
                    { data: 'student_info', name: 'student_info', width: '200px' },
                    { data: 'course_name', name: 'course_name', width: '200px' },
                    { data: 'service_name', name: 'service_name', width: '120px' },
                    { data: 'ot_code', name: 'ot_code', width: '120px' },
                    { data: 'rank', name: 'rank', width: '100px' },
                    { data: 'status_badge', name: 'status_badge', width: '100px', orderable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '120px', className: 'text-center' }
                ],
                columnDefs: [
                    { targets: [0, 7], className: 'text-center' },
                    { targets: [1, 2, 3, 4, 5], className: 'text-truncate' }
                ],
                drawCallback: function(settings) {
                    console.log('DataTable draw complete');
                    let api = this.api();
                    $('#filteredCount').text(api.page.info().recordsTotal);
                    debugDataTable();
                },
                initComplete: function(settings, json) {
                    console.log('DataTable init complete');
                    console.log('Initial data:', json);
                }
            });
            
            debugDataTable();
        }

        // Load table when course or status changes
        $('#course_id, #status').change(function() {
            console.log('Filter changed:', $(this).attr('id'), $(this).val());
            
            if ($('#course_id').val() === '') {
                console.log('No course selected, clearing table');
                if (dataTable !== null) {
                    dataTable.clear().draw();
                    $('#filteredCount').text(0);
                }
                return;
            }

            // Initialize or reload DataTable
            if (dataTable === null) {
                console.log('DataTable not initialized, initializing...');
                initializeDataTable();
            } else {
                console.log('Reloading DataTable with new filters...');
                dataTable.ajax.reload();
            }
        });

        // When course status (Active / Archive) changes
        $('input[name="course_status"]').change(function() {
            const courseStatus = $(this).val();
            console.log('Course status changed to:', courseStatus);
            updateCourseDropdown(courseStatus);

            // Reset filters
            $('#course_id').val('');
            $('#status').val('');
            syncExportFields();

            // Reset the table if it exists
            if (dataTable !== null) {
                dataTable.clear().draw();
                $('#filteredCount').text(0);
            }
        });

        // Initialize DataTable on page load if a course is selected
        $(window).on('load', function() {
            console.log('Window loaded, checking for pre-selected course...');
            if ($('#course_id').val() !== '') {
                console.log('Course pre-selected, initializing DataTable...');
                setTimeout(() => {
                    initializeDataTable();
                }, 1000);
            }
        });

        // Export form submission
        $('#exportForm').on('submit', function(e) {
            const format = $('#exportFormat').val();
            if (!format) {
                e.preventDefault();
                alert('Please select an export format');
                return false;
            }
        });

        // Import Form Validation
        $('#importForm').on('submit', function(e) {
            const fileInput = $('#import_file')[0];
            const submitBtn = $('#importSubmitBtn');

            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file to upload');
                return false;
            }

            // Validate file extension
            const fileName = fileInput.files[0].name;
            const validExtensions = /(\.xlsx|\.xls|\.csv)$/i;

            if (!validExtensions.exec(fileName)) {
                e.preventDefault();
                alert('Please upload only Excel or CSV files (.xlsx, .xls, .csv)');
                return false;
            }

            // Validate file size (5MB max)
            const fileSize = fileInput.files[0].size;
            const maxSize = 5 * 1024 * 1024;

            if (fileSize > maxSize) {
                e.preventDefault();
                alert('File size must be less than 5MB');
                return false;
            }

            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');
        });

        // Reset import button when modal is closed
        $('#importModal').on('hidden.bs.modal', function() {
            $('#importSubmitBtn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Import to OT List');
            $('#importForm')[0].reset();
        });
    });
</script>

<style>
    /* ===== ANIMATIONS ===== */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideIn {
        from {
            transform: translateX(-20px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    /* ===== ICON BOX ===== */
    .icon-box {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }

    /* ===== CUSTOM TOGGLE BUTTONS ===== */
    .custom-toggle-btn {
        position: relative;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.9rem;
        padding: 1rem 2rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid currentColor;
    }

    .btn-check:checked + .btn-outline-success.custom-toggle-btn {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
        transform: translateY(-3px);
    }

    .btn-check:checked + .btn-outline-danger.custom-toggle-btn {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 8px 25px rgba(235, 51, 73, 0.4);
        transform: translateY(-3px);
    }

    .custom-toggle-btn:hover:not(.btn-check:checked + .custom-toggle-btn) {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    /* ===== CUSTOM SELECT ===== */
    .custom-select {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-weight: 500;
        background-color: #fff;
    }

    .custom-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    .custom-select:hover {
        border-color: #b0b0b0;
    }

    .input-group-text {
        transition: all 0.3s ease;
    }

    .input-group:focus-within .input-group-text {
        border-color: #667eea;
        background-color: #f0f4ff;
    }

    /* ===== STATS CARD ===== */
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    .stats-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
    }

    .stats-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }

    .stats-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .stats-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }

    .counter {
        display: inline-block;
        transition: all 0.3s ease;
    }

    /* ===== ACTION CARDS ===== */
    .action-card {
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .action-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }

    /* ===== CUSTOM ACTION BUTTONS ===== */
    .btn-import,
    .btn-export {
        border: none;
        border-radius: 12px;
        padding: 1.2rem 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .btn-import {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-export {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .btn-import::before,
    .btn-export::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }

    .btn-import:hover::before,
    .btn-export:hover::before {
        left: 100%;
    }

    .btn-import:hover,
    .btn-export:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
    }

    .btn-import:active,
    .btn-export:active {
        transform: translateY(-1px);
    }

    .btn-icon {
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-text {
        text-align: left;
        flex: 1;
    }

    .btn-title {
        font-size: 1rem;
        font-weight: 700;
        display: block;
        margin-bottom: 0.25rem;
    }

    .btn-subtitle {
        font-size: 0.75rem;
        opacity: 0.9;
        font-weight: 400;
    }

    /* ===== MODERN TABLE ===== */
    .modern-table {
        width: 100% !important;
        table-layout: auto;
        margin: 0;
    }

    .modern-table thead th {
        white-space: nowrap;
        vertical-align: middle;
        position: relative;
    }

    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        max-width: 250px;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .modern-table tbody tr {
        transition: all 0.3s ease;
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(to right, #f8f9ff 0%, #ffffff 100%);
        box-shadow: 0 5px 15px rgba(0, 74, 147, 0.1);
        transform: scale(1.005);
        will-change: transform;
        backface-visibility: hidden;
    }

    /* Prevent layout shift on hover */
    .modern-table tbody tr {
        transition: all 0.3s ease;
        transform: scale(1);
        will-change: transform;
    }
    .modern-table th:nth-child(1),
    .modern-table td:nth-child(1) {
        width: 60px;
        min-width: 60px;
        max-width: 60px;
    }

    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 200px;
        min-width: 150px;
    }

    .modern-table th:nth-child(3),
    .modern-table td:nth-child(3) {
        width: 200px;
        min-width: 150px;
    }

    .modern-table th:nth-child(4),
    .modern-table td:nth-child(4) {
        width: 120px;
        min-width: 100px;
    }

    .modern-table th:nth-child(5),
    .modern-table td:nth-child(5) {
        width: 120px;
        min-width: 100px;
    }

    .modern-table th:nth-child(6),
    .modern-table td:nth-child(6) {
        width: 100px;
        min-width: 80px;
    }

    .modern-table th:nth-child(7),
    .modern-table td:nth-child(7) {
        width: 100px;
        min-width: 80px;
    }

    .modern-table th:nth-child(8),
    .modern-table td:nth-child(8) {
        width: 120px;
        min-width: 100px;
        text-align: center;
    }

    /* ===== CARDS ===== */
    .card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-body {
        overflow-x: auto;
    }

    .bg-gradient-primary {
        position: relative;
    }

    .bg-gradient-primary::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.05) 100%);
        pointer-events: none;
    }

    /* ===== DATATABLES ENHANCEMENTS ===== */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.6rem 1.2rem;
        margin: 0 0.25rem;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid #e0e0e0;
        font-weight: 600;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        border-color: transparent;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
        color: white !important;
        border: none;
        box-shadow: 0 8px 25px rgba(0, 74, 147, 0.4);
        transform: translateY(-3px);
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        outline: none;
        transform: translateY(-2px);
    }

    .dataTables_wrapper .dataTables_info {
        font-weight: 600;
        color: #495057;
        padding: 0.75rem 0;
    }

    /* ===== LOADING INDICATOR ===== */
    .dataTables_processing {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 300px;
        margin-left: -150px;
        margin-top: -40px;
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        z-index: 999;
        font-weight: 700;
        font-size: 1.1rem;
        color: #004a93;
    }

    .dataTables_processing::before {
        content: "\f110";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        animation: fa-spin 1s infinite linear;
        margin-right: 1rem;
        font-size: 1.5rem;
    }

    /* ===== MODAL ENHANCEMENTS ===== */
    .modal-content {
        border-radius: 20px;
        overflow: hidden;
        border: none;
    }

    .modal-header {
        border-bottom: none;
        position: relative;
    }

    .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 1.5rem;
    }

    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    /* ===== BADGES ===== */
    .badge {
        padding: 0.6rem 1.2rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 50px;
        font-size: 0.75rem;
    }

    /* ===== ALERTS ===== */
    .alert {
        border-radius: 15px;
        border: none;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    .alert-warning {
        animation: slideIn 0.5s ease-out;
    }

    /* ===== CUSTOM SCROLLBAR ===== */
    ::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f3f5;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        border: 2px solid #f1f3f5;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* ===== FORM ENHANCEMENTS ===== */
    .form-label {
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    }

    /* ===== RESPONSIVE ADJUSTMENTS ===== */
    @media (max-width: 768px) {
        .stats-card {
            margin-top: 1rem;
        }

        .btn-title {
            font-size: 0.9rem;
        }

        .btn-subtitle {
            font-size: 0.7rem;
        }

        .icon-box {
            width: 40px;
            height: 40px;
        }

        .stats-value {
            font-size: 2rem;
        }
    }

    /* ===== TABLE RESPONSIVE ===== */
    .table-responsive {
        border-radius: 0 0 15px 15px;
        overflow: hidden;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* DataTables Container */
    .dataTables_wrapper {
        width: 100%;
        padding: 1.5rem;
    }

    .dataTables_wrapper table {
        margin: 0 !important;
    }

    /* Prevent table expansion */
    #studentsTable_wrapper {
        max-width: 100%;
        overflow-x: auto;
    }

    #studentsTable {
        border-collapse: collapse;
        width: 100% !important;
    }

    /* Fix for responsive controls */
    table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control,
    table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control {
        position: relative;
        padding-left: 30px;
        cursor: pointer;
    }

    table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
    table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
        top: 50%;
        left: 5px;
        height: 14px;
        width: 14px;
        margin-top: -7px;
        display: block;
        position: absolute;
        color: white;
        border: 2px solid #667eea;
        border-radius: 14px;
        box-shadow: 0 0 3px #444;
        background-color: #667eea;
    }

    /* ===== UTILITY CLASSES ===== */
    .opacity-50 {
        opacity: 0.5;
    }

    .text-white-50 {
        color: rgba(255, 255, 255, 0.7);
    }

    /* Text truncation for table cells */
    .text-truncate {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* General overflow prevention */
    * {
        box-sizing: border-box;
    }

    /* Fix for long words breaking layout */
    .modern-table td,
    .modern-table th {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Ensure actions column doesn't expand */
    .modern-table .text-center {
        white-space: nowrap;
    }

    /* DataTables specific fixes */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        padding: 0.5rem 0;
    }

    /* Responsive table on mobile */
    @media (max-width: 992px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .modern-table {
            min-width: 800px;
        }
    }
</style>
@endsection
