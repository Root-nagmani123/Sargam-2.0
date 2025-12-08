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
            <!-- Export Section -->
<div class="col-md-4 col-sm-12">
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
                <table class="table table-bordered text-nowrap align-middle" id="studentsTable">
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

    // --------------------------
    // Load courses on page load
    //---------------------------
    updateCourseDropdown($('input[name="course_status"]:checked').val());

    // Function to update course dropdown
    function updateCourseDropdown(courseStatus) {
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
                const courseSelect = $('#course_id');
                courseSelect.empty().append('<option value="">-- Select Course --</option>');

                $.each(response.courses, function(id, name) {
                    courseSelect.append(new Option(name, id));
                });

                $("#exportCourseStatus").val(courseStatus);
                syncExportFields(); // keep export filters updated
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
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

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
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'student_info' },
                { data: 'course_name' },
                { data: 'service_name' },
                { data: 'ot_code' },
                { data: 'rank' },
                { data: 'status_badge' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            drawCallback: function(settings) {
                let api = this.api();
                $('#filteredCount').text(api.page.info().recordsTotal);
            }
        });
    }

    // Load table when course or status changes
    $('#course_id, #status').change(function() {
        if ($('#course_id').val() !== '') {
            initializeDataTable();
        }
    });

    // When course status (Active / Archive) changes
    $('input[name="course_status"]').change(function() {
        const courseStatus = $(this).val();
        updateCourseDropdown(courseStatus);

        if (dataTable) {
            dataTable.clear().draw();
        }

        $('#filteredCount').text(0);
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
</style>
@endsection