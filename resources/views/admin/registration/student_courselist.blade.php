@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Course Wise OTs List" />
        <x-session_message />

        {{-- Filters + Counts + Export --}}
        <div class="card mb-3 p-3" style="border-left: 4px solid #004a93;">
            <div class="row align-items-end g-3">
                <!-- Filters Section -->
                <div class="col-md-6 col-sm-12">
                    <form id="filterForm" method="GET">
                        <div class="row g-3">
                            <!-- Course Status Filter - Buttons -->
                            <div class="col-md-12 mb-3">
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
                                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Total Count -->
                <div class="col-md-2 col-sm-12 text-center">
                    <div class="fw-bold fs-5 text-primary mt-2 mt-md-0">
                        Total: <span id="filteredCount">{{ $filteredCount }}</span>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="col-md-4 col-sm-12">
                    <div class="row g-3">
                        <!-- Import Button -->
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary w-100 mb-2" data-bs-toggle="modal"
                                data-bs-target="#importModal">
                                <i class="fas fa-file-import me-1"></i> Import to OT List
                            </button>
                            <div class="text-center">
                                <small class="text-muted">Insert/Update course wise OT records</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('studentEnroll.report.export') }}" id="exportForm">
                                <input type="hidden" name="course" id="exportCourse" value="{{ $courseId }}">
                                <input type="hidden" name="status" id="exportStatus" value="{{ $status }}">
                                <input type="hidden" name="course_status" id="exportCourseStatus" value="{{ $courseStatus }}">

                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Export Format</label>
                                        <select name="format" class="form-select" required id="exportFormat">
                                            <option value="">-- Select Format --</option>
                                            <option value="pdf">PDF</option>
                                            <option value="xlsx">Excel</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-success w-100 mt-3 mt-md-0" id="exportBtn">
                                            <i class="fas fa-download me-1"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End of filter card -->

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">
                            <i class="fas fa-file-import me-2"></i>Import OT Codes to Course Wise OT List
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.enrollment.import') }}" method="POST"
                        enctype="multipart/form-data" id="importForm">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="alert" style="background:#e9f5ff; border:1px solid #b6e0fe; color:#055160;">
                                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Import Instructions:</h6>
                                        <p class="mb-2">Your Excel file should have these columns:</p>
                                        <table class="table table-bordered text-nowrap align-middle">
                                            <thead style="background:#af2910; color:#fff;">
                                                <tr>
                                                    <th>Excel Column</th>
                                                    <th>Description</th>
                                                    <th>Required</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>student_master_pk</code></td>
                                                    <td>Student ID number</td>
                                                    <td><span class="badge bg-danger">Required</span></td>
                                                </tr>
                                                <tr>
                                                    <td><code>course_master_pk</code></td>
                                                    <td>Course ID number</td>
                                                    <td><span class="badge bg-danger">Required</span></td>
                                                </tr>
                                                <tr>
                                                    <td><code>OT Code</code></td>
                                                    <td>OT Code value (max 20 chars)</td>
                                                    <td><span class="badge bg-danger">Required</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <p class="mt-2 mb-0"><strong>Note:</strong> Export first, edit OT Code column, then import the same file.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="import_file" class="form-label fw-bold">
                                    <i class="fas fa-file-excel me-1"></i>Select Excel/CSV File
                                </label>
                                <input type="file" class="form-control" name="import_file" id="import_file"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="form-text-dark">
                                    <i class="fas fa-file me-1"></i>Supported: .xlsx, .xls, .csv |
                                    <i class="fas fa-database me-1"></i>Max: 5MB
                                </div>
                                <div class="form-text-dark">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Do not modify student_master_pk or course_master_pk columns
                                </div>
                            </div>

                            @if (session('import_errors'))
                                <div class="alert alert-danger mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="alert-heading mb-0">
                                            <i class="fas fa-exclamation-circle me-2"></i>Import Errors ({{ count(session('import_errors')) }}):
                                        </h6>
                                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                                    </div>
                                    <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-borderless mb-0">
                                            @foreach (session('import_errors') as $error)
                                                <tr>
                                                    <td class="text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>{{ $error }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                                <i class="fas fa-upload me-1"></i> Import to OT List
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Alert --}}
        @if ($courseStatus === 'inactive')
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-archive me-2"></i>
                <strong>Viewing Archived Courses:</strong> You are currently viewing courses that have been archived.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Data Table --}}
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="studentsTable">
                        <thead style="background:#af2910; color:#fff;">
                            <tr>
                                <th>S.No</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Service</th>
                                <th>OT Code</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Actions</th>
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
                $('#studentsTable').empty();
            }

            console.log('Initializing new DataTable...');
            // Initialize DataTable
            dataTable = $('#studentsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ordering: true,
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
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'student_info', name: 'student_info' },
                    { data: 'course_name', name: 'course_name' },
                    { data: 'service_name', name: 'service_name' },
                    { data: 'ot_code', name: 'ot_code' },
                    { data: 'rank', name: 'rank' },
                    { data: 'status_badge', name: 'status_badge' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
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

    /* DataTables styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin-left: 2px;
        border-radius: 0.375rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #004a93;
        color: white !important;
        border: 1px solid #004a93;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    /* Loading indicator */
    .dataTables_processing {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 200px;
        margin-left: -100px;
        margin-top: -20px;
        text-align: center;
        padding: 10px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        border-radius: 4px;
        z-index: 999;
    }
</style>
@endsection
