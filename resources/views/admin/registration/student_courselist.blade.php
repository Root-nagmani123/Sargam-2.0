@extends('admin.layouts.master')

@section('title', 'Course Wise OTs List')

@section('setup_content')
    {{-- Searchable dropdowns: Choices.js (same version the rest of the app uses) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="{{ asset('css/choices-theme.css') }}?v={{ @filemtime(public_path('css/choices-theme.css')) ?: time() }}">

    <style>
        /* =================================================================
           Course Wise OTs List — page-scoped polish.
           Design tokens/components come from sargam-app.css (--ds-*, .ds-*).
           Only what Bootstrap utilities can't express lives here (scl- prefix).
           ================================================================= */

        /* --- Segmented Active / Archived control ---------------------- */
        .scl-segment {
            display: inline-flex;
            gap: var(--ds-space-1);
            padding: var(--ds-space-1);
            background: var(--ds-surface-2);
            border: 1px solid var(--ds-line);
            border-radius: var(--ds-radius-2);
        }

        .scl-segment-btn {
            margin: 0;
            border: 0;
            border-radius: var(--ds-radius-1);
            padding: 0.5rem 1.35rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--ds-ink-muted);
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: var(--ds-space-1);
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .scl-segment-btn:hover {
            color: var(--ds-ink);
        }

        .btn-check:checked + .scl-segment-btn {
            background: var(--bs-primary);
            color: #fff;
            box-shadow: var(--ds-shadow-sm);
        }

        .btn-check:focus-visible + .scl-segment-btn {
            box-shadow: var(--ds-focus-ring);
        }

        /* --- Filters card: let Choices dropdowns escape the card ---------
           .ds-card sets overflow:hidden (for rounded corners), which clips
           the Choices.js dropdown when it opens past the card's bottom edge.
           Allow it to overflow here; re-round the header so visible overflow
           doesn't expose square top corners. Mirrors the project's own
           workaround in admin/partials/choices-bootstrap5.blade.php. */
        .scl-filter-card {
            overflow: visible;
        }

        .scl-filter-card > .ds-card-header {
            border-radius: var(--ds-radius-card) var(--ds-radius-card) 0 0;
        }

        /* --- Filter controls -------------------------------------------- */
        .scl-field-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--ds-ink);
            margin-bottom: 0.35rem;
        }

        /* Choices fills its grid column */
        .scl-filter .choices,
        .scl-filter .choices__inner {
            width: 100%;
        }

        /* Export format dropdown keeps a tidy, fixed footprint */
        .scl-format .choices {
            min-width: 190px;
            margin-bottom: 0;
        }

        /* --- Total Records stat ----------------------------------------- */
        .scl-stat .ds-stat-value {
            font-size: 1.75rem;
        }

        /* --- DataTable theming (scoped) --------------------------------- */
        .scl-dt #studentsTable {
            width: 100% !important;
            margin: 0;
        }

        .scl-dt #studentsTable thead th {
            background: var(--ds-surface-2);
            color: var(--ds-ink);
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            border-bottom: 1px solid var(--ds-line);
            padding: 0.75rem 0.9rem;
            vertical-align: middle;
        }

        .scl-dt #studentsTable td {
            padding: 0.7rem 0.9rem;
            vertical-align: middle;
            font-size: 0.9rem;
            color: var(--ds-ink);
        }

        .scl-dt #studentsTable tbody tr:hover {
            background: var(--ds-surface-2);
        }

        .scl-dt .badge {
            font-weight: 600;
            letter-spacing: 0.2px;
            padding: 0.4rem 0.7rem;
            border-radius: 50rem;
        }

        /* Length / info / pagination — clean, brand-aligned */
        .scl-dt .dataTables_wrapper .dataTables_length,
        .scl-dt .dataTables_wrapper .dataTables_info,
        .scl-dt .dataTables_wrapper .dataTables_paginate {
            padding-top: var(--ds-space-3);
            color: var(--ds-ink-muted);
            font-size: 0.875rem;
        }

        .scl-dt .dataTables_wrapper .dataTables_length select.form-select {
            width: auto;
            min-width: 76px;
            display: inline-block;
            border-radius: var(--ds-radius-1);
        }

        .scl-dt .pagination .page-item .page-link {
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.25rem;
            border: 1px solid var(--ds-line);
            border-radius: var(--ds-radius-1);
            color: var(--ds-ink);
            background: #fff;
        }

        .scl-dt .pagination .page-item .page-link:hover {
            background: var(--ds-surface-2);
            border-color: #c4ccd6;
        }

        .scl-dt .pagination .page-item.active .page-link {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }

        .scl-dt .pagination .page-item.disabled .page-link {
            color: var(--ds-ink-muted);
            background: var(--ds-surface-2);
            opacity: 0.6;
        }

        .scl-dt .dataTables_processing {
            border: 0;
            box-shadow: var(--ds-shadow-lg);
            border-radius: var(--ds-radius-2);
        }

        @media (max-width: 575.98px) {
            .scl-actions,
            .scl-actions #exportForm {
                width: 100%;
            }
            .scl-actions .scl-format .choices {
                flex: 1;
                min-width: 0;
            }
        }
    </style>

    <div class="container-fluid">

        <x-breadcrum title="Course Wise OTs List" />
        <x-session_message />

        {{-- ============================ Filters & Actions ============================ --}}
        <div class="ds-card ds-card--accent scl-filter-card mb-4">
            <div class="ds-card-header">
                <i class="material-icons material-symbols-rounded" style="font-size: 20px;" aria-hidden="true">tune</i>
                <span>Filters &amp; Actions</span>
            </div>
            <div class="ds-card-body">

                {{-- Toolbar: course-type segment (left) + Import / Export (right) --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">

                    <div class="scl-segment" role="group" aria-label="Course type filter">
                        <input type="radio" class="btn-check" name="course_status" id="course_status_active"
                            value="active" {{ $courseStatus === 'active' ? 'checked' : '' }} autocomplete="off">
                        <label class="scl-segment-btn" for="course_status_active">
                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">check_circle</i>
                            Active Courses
                        </label>

                        <input type="radio" class="btn-check" name="course_status" id="course_status_inactive"
                            value="inactive" {{ $courseStatus === 'inactive' ? 'checked' : '' }} autocomplete="off">
                        <label class="scl-segment-btn" for="course_status_inactive">
                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">inventory_2</i>
                            Archived Courses
                        </label>
                    </div>

                    <div class="scl-actions d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">upload_file</i>
                            <span>Import Data</span>
                        </button>

                        <form method="GET" action="{{ route('studentEnroll.report.export') }}" id="exportForm"
                            class="scl-format d-flex align-items-center gap-2 mb-0">
                            <input type="hidden" name="course" id="exportCourse" value="{{ $courseId }}">
                            <input type="hidden" name="status" id="exportStatus" value="{{ $status }}">
                            <input type="hidden" name="course_status" id="exportCourseStatus" value="{{ $courseStatus }}">

                            <select name="format" class="form-select" id="exportFormat" aria-label="Export format">
                                <option value="">Choose export type…</option>
                                <option value="pdf">PDF Document</option>
                                <option value="xlsx">Excel Spreadsheet</option>
                                <option value="csv">CSV File</option>
                            </select>

                            <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2" id="exportBtn">
                                <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">download</i>
                                <span>Export</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Filters: Course (searchable) · Enrollment Status · Total Records --}}
                <div class="row g-3 align-items-stretch">
                    <div class="col-lg-4 col-md-6 scl-filter">
                        <label for="course_id" class="scl-field-label d-block">Filter by Course</label>
                        <select name="course_id" id="course_id" class="form-select">
                            <option value="">-- Select Course --</option>
                            @foreach ($courses as $id => $name)
                                <option value="{{ $id }}" {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 scl-filter">
                        <label for="status" class="scl-field-label d-block">Enrollment Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- All Status --</option>
                            <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-lg-4 scl-stat">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Records</p>
                                <div class="ds-stat-value"><span id="filteredCount">{{ $filteredCount }}</span></div>
                            </div>
                            <span class="ds-stat-icon">
                                <i class="material-icons material-symbols-rounded" aria-hidden="true">groups</i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Archived info notice --}}
        @if ($courseStatus === 'inactive')
            <div class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="material-icons material-symbols-rounded" style="font-size: 28px;" aria-hidden="true">inventory_2</i>
                <div>
                    <div class="fw-semibold">Viewing Archived Courses</div>
                    <small class="mb-0">You are currently viewing courses that have been archived. Switch to
                        “Active Courses” to see current courses.</small>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ============================ Records table ============================ --}}
        <div class="ds-card scl-dt">
            <div class="ds-card-header">
                <i class="material-icons material-symbols-rounded" style="font-size: 20px;" aria-hidden="true">table_view</i>
                <span>Course Wise OT Records</span>
                <small class="text-body-secondary fw-normal ms-2 d-none d-sm-inline">View and manage student enrollments</small>
            </div>
            <div class="ds-card-body">
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap mb-0 dt-legacy-layout" id="studentsTable" data-sargam-dt-ui="false" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Service</th>
                                <th>OT Code</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Populated by DataTables (server-side) --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================ Import modal ============================ --}}
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold d-inline-flex align-items-center gap-2" id="importModalLabel">
                            <i class="material-icons material-symbols-rounded text-primary" style="font-size: 22px;" aria-hidden="true">upload_file</i>
                            Import OT Codes to Course Wise OT List
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.enrollment.import') }}" method="POST" enctype="multipart/form-data"
                        id="importForm">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info border-0 mb-4" role="alert">
                                <h6 class="alert-heading fw-semibold d-inline-flex align-items-center gap-2">
                                    <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">info</i>
                                    Import Instructions
                                </h6>
                                <p class="mb-2">Your Excel/CSV file should contain these columns:</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0 bg-white">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Column</th>
                                                <th>Description</th>
                                                <th class="text-center">Required</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>student_master_pk</code></td>
                                                <td class="text-body-secondary">Student ID number</td>
                                                <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>course_master_pk</code></td>
                                                <td class="text-body-secondary">Course ID number</td>
                                                <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>OT Code</code></td>
                                                <td class="text-body-secondary">OT Code value (max 20 chars)</td>
                                                <td class="text-center"><span class="badge bg-danger rounded-pill">Required</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex align-items-start gap-2 mt-3 mb-0 small text-body-secondary">
                                    <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">lightbulb</i>
                                    <span><strong>Tip:</strong> Export the data first, edit the OT Code column, then import
                                        the same file back.</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="import_file" class="form-label fw-semibold">Select Excel / CSV File</label>
                                <input type="file" class="form-control" name="import_file" id="import_file"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="mt-2 small text-body-secondary">
                                    <div><i class="material-icons material-symbols-rounded align-middle" style="font-size: 16px;" aria-hidden="true">check_circle</i>
                                        Supported formats: .xlsx, .xls, .csv</div>
                                    <div><i class="material-icons material-symbols-rounded align-middle" style="font-size: 16px;" aria-hidden="true">database</i>
                                        Maximum file size: 5MB</div>
                                    <div class="text-danger"><i class="material-icons material-symbols-rounded align-middle" style="font-size: 16px;" aria-hidden="true">warning</i>
                                        Do not modify the student_master_pk or course_master_pk columns</div>
                                </div>
                            </div>

                            @if (session('import_errors'))
                                <div class="alert alert-danger border-0 mt-3" role="alert">
                                    <h6 class="alert-heading fw-semibold mb-2">
                                        Import Errors ({{ count(session('import_errors')) }})
                                    </h6>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        <ul class="mb-0 ps-3">
                                            @foreach (session('import_errors') as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2" id="importSubmitBtn">
                                <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">upload</i>
                                Import to OT List
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery + DataTables (1.13.8) are loaded globally in the footer; only Choices.js is page-specific --}}
    <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

    <script>
        $(document).ready(function () {
            let dataTable = null;
            let courseChoices = null;
            let statusChoices = null;

            // ---------------------------------------------------------------
            // Searchable Choices.js dropdowns
            // ---------------------------------------------------------------
            if (typeof Choices !== 'undefined') {
                courseChoices = new Choices(document.getElementById('course_id'), {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search course…',
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    placeholder: true,
                    placeholderValue: '-- Select Course --'
                });
                statusChoices = new Choices(document.getElementById('status'), {
                    searchEnabled: false,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false
                });
                // Export format — short list, no search needed
                new Choices(document.getElementById('exportFormat'), {
                    searchEnabled: false,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false
                });
            }

            function buildCourseChoiceList(courses) {
                const list = [{ value: '', label: '-- Select Course --', placeholder: true, selected: true }];
                $.each(courses || {}, function (id, name) {
                    list.push({ value: String(id), label: name });
                });
                return list;
            }

            // ---------------------------------------------------------------
            // Load courses for the selected course type (Active / Archived)
            // ---------------------------------------------------------------
            updateCourseDropdown($('input[name="course_status"]:checked').val());

            function updateCourseDropdown(courseStatus) {
                $.ajax({
                    url: "{{ route('student.courses') }}",
                    type: "GET",
                    data: { course_status: courseStatus, ajax_courses: true },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        if (courseChoices) {
                            courseChoices.clearStore();
                            courseChoices.setChoices(buildCourseChoiceList(response.courses), 'value', 'label', true);
                        } else {
                            const select = $('#course_id').empty().append('<option value="">-- Select Course --</option>');
                            $.each(response.courses, function (id, name) {
                                select.append(new Option(name, id));
                            });
                        }

                        $('#exportCourseStatus').val(courseStatus);
                        syncExportFields();

                        // If a course is somehow already selected, load its records
                        if ($('#course_id').val() !== '') {
                            initializeDataTable();
                        }
                    },
                    error: function (xhr) {
                        console.error('Course dropdown AJAX error:', xhr.responseText);
                    }
                });
            }

            // ---------------------------------------------------------------
            // Keep the export form's hidden inputs in sync with the filters
            // ---------------------------------------------------------------
            function syncExportFields() {
                $('#exportCourse').val($('#course_id').val());
                $('#exportStatus').val($('#status').val());
                $('#exportCourseStatus').val($('input[name="course_status"]:checked').val());
            }

            $('#course_id, #status').on('change', syncExportFields);
            $('input[name="course_status"]').on('change', syncExportFields);
            syncExportFields();

            // ---------------------------------------------------------------
            // DataTable (server-side)
            // ---------------------------------------------------------------
            function initializeDataTable() {
                if (dataTable !== null && $.fn.DataTable.isDataTable('#studentsTable')) {
                    dataTable.ajax.reload();
                    return;
                }

                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    $('#studentsTable').DataTable().destroy();
                    $('#studentsTable tbody').empty();
                }

                dataTable = $('#studentsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false,
                    searching: false,
                    ordering: true,
                    autoWidth: false,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    // Per-page selector on top (with search beside it); record info
                    // bottom-left and pagination bottom-RIGHT.
                    dom: "<'row mb-2 g-2 align-items-center'<'col-sm-12 col-md-6 d-flex align-items-center gap-2'l><'col-sm-12 col-md-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row mt-2 align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>',
                        emptyTable: 'No records available for the selected course.',
                        zeroRecords: 'No matching records found',
                        paginate: {
                            previous: '<span aria-hidden="true">&lsaquo;</span>',
                            next: '<span aria-hidden="true">&rsaquo;</span>'
                        }
                    },
                    ajax: {
                        url: "{{ route('student.courses') }}",
                        type: "GET",
                        data: function (d) {
                            d.course_id = $('#course_id').val();
                            d.status = $('#status').val();
                            d.course_status = $('input[name="course_status"]:checked').val();
                        },
                        dataSrc: function (json) {
                            $('#filteredCount').text(json.recordsTotal || 0);
                            return json.data || [];
                        },
                        error: function (xhr, error, thrown) {
                            console.error('DataTable AJAX error:', error, thrown, xhr.responseText);
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'student_info', name: 'student_info' },
                        { data: 'course_name', name: 'course_name' },
                        { data: 'service_name', name: 'service_name' },
                        { data: 'ot_code', name: 'ot_code' },
                        { data: 'rank', name: 'rank' },
                        { data: 'status_badge', name: 'status_badge', orderable: false },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    columnDefs: [
                        { targets: [0, 7], className: 'text-center' }
                    ],
                    drawCallback: function () {
                        $('#filteredCount').text(this.api().page.info().recordsTotal);
                    }
                });
            }

            // Reload the table when course or enrollment-status filter changes
            $('#course_id, #status').on('change', function () {
                if ($('#course_id').val() === '') {
                    if (dataTable !== null) {
                        dataTable.clear().draw();
                        $('#filteredCount').text(0);
                    }
                    return;
                }

                if (dataTable === null) {
                    initializeDataTable();
                } else {
                    dataTable.ajax.reload();
                }
            });

            // When the course type (Active / Archived) changes
            $('input[name="course_status"]').on('change', function () {
                const courseStatus = $(this).val();
                updateCourseDropdown(courseStatus);

                // Reset dependent filters
                if (statusChoices) {
                    statusChoices.setChoiceByValue('');
                } else {
                    $('#status').val('');
                }
                syncExportFields();

                // Clear the table — a fresh course must be picked
                if (dataTable !== null) {
                    dataTable.clear().draw();
                    $('#filteredCount').text(0);
                }
            });

            // Initialise the table on load if a course is pre-selected
            $(window).on('load', function () {
                if ($('#course_id').val() !== '') {
                    setTimeout(initializeDataTable, 600);
                }
            });

            // ---------------------------------------------------------------
            // Export validation
            // ---------------------------------------------------------------
            $('#exportForm').on('submit', function (e) {
                if (!$('#exportFormat').val()) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Select a format', text: 'Please choose an export format first.' });
                    } else {
                        alert('Please select an export format');
                    }
                    return false;
                }
            });

            // ---------------------------------------------------------------
            // Import form validation
            // ---------------------------------------------------------------
            $('#importForm').on('submit', function (e) {
                const fileInput = $('#import_file')[0];
                const submitBtn = $('#importSubmitBtn');

                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Please select a file to upload');
                    return false;
                }

                const fileName = fileInput.files[0].name;
                const validExtensions = /(\.xlsx|\.xls|\.csv)$/i;
                if (!validExtensions.exec(fileName)) {
                    e.preventDefault();
                    alert('Please upload only Excel or CSV files (.xlsx, .xls, .csv)');
                    return false;
                }

                const maxSize = 5 * 1024 * 1024;
                if (fileInput.files[0].size > maxSize) {
                    e.preventDefault();
                    alert('File size must be less than 5MB');
                    return false;
                }

                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="material-icons material-symbols-rounded align-middle" style="font-size:18px;">autorenew</i> Processing…');
            });

            // Reset the import button when the modal closes
            $('#importModal').on('hidden.bs.modal', function () {
                $('#importSubmitBtn').prop('disabled', false)
                    .html('<i class="material-icons material-symbols-rounded" style="font-size:18px;">upload</i> Import to OT List');
                $('#importForm')[0].reset();
            });

            // Auto-open the import modal when the server returned import errors
            @if (session('import_errors'))
                var importModalEl = document.getElementById('importModal');
                if (importModalEl && window.bootstrap) {
                    new bootstrap.Modal(importModalEl).show();
                }
            @endif
        });
    </script>
@endpush
