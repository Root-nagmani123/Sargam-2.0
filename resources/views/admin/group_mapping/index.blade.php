@extends('admin.layouts.master')

@section('title', 'Course Group Mapping - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* Fix Select2 dropdown cropping on Group Mapping index page */
.group-mapping-index .select2-container { width: 100% !important; }
.group-mapping-index .select2-selection--single .select2-selection__rendered {
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
}
/* Ensure Select2 dropdown is visible and above modals */
body .select2-dropdown {
    z-index: 99999 !important;
}
body:has(.group-mapping-index) .select2-dropdown {
    min-width: 280px !important;
    z-index: 99999 !important;
}
body:has(.group-mapping-index) .select2-results__option {
    white-space: normal !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
body:has(.group-mapping-index) .select2-results__options {
    max-height: 300px;
}

/* Group Mapping Index - Bootstrap 5.3 enhanced */
.group-mapping-index .group-mapping-card {
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-start: 4px solid #004a93;
    transition: box-shadow 0.2s ease;
}
.group-mapping-index .group-mapping-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}
.group-mapping-index .form-label {
    font-weight: 500;
    color: #495057;
}
.group-mapping-index .form-select {
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.group-mapping-index .form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.group-mapping-index .btn-group .btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}
.group-mapping-index .btn-primary,
.group-mapping-index .btn-info,
.group-mapping-index .btn-success,
.group-mapping-index .btn-outline-primary {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}
.group-mapping-index .btn-primary:hover,
.group-mapping-index .btn-info:hover,
.group-mapping-index .btn-success:hover,
.group-mapping-index .btn-outline-primary:hover {
    transform: translateY(-1px);
}
.group-mapping-index .modal-content {
    border-radius: 0.75rem;
    box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
}
.group-mapping-index .modal-header {
    border-radius: 0.75rem 0.75rem 0 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}
.group-mapping-index .modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
}
@media (prefers-reduced-motion: reduce) {
    .group-mapping-index .group-mapping-card,
    .group-mapping-index .btn { transition: none; }
    .group-mapping-index .btn:hover { transform: none; }
}
/* Mobile: ensure DataTable wrapper is contained and scrollable */
@media (max-width: 991.98px) {
    .group-mapping-index .group-mapping-table-wrapper,
    .group-mapping-index #group-mapping-table_wrapper {
        width: 100%;
        max-width: 100%;
    }
}
</style>
@endsection

@section('setup_content')

<div class="container-fluid group-mapping-index py-3">

    <x-breadcrum title="Course Group Mapping" />
    <x-session_message />

    <div class="datatables">
        <div class="card group-mapping-card shadow-sm overflow-visible">
            <div class="card-body p-4 p-lg-5">
                {{-- Header and filters: fixed, no horizontal scroll --}}
                <div class="group-mapping-top-section">
                    <div class="row mb-4 group-mapping-header-row align-items-center">
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-diagram-3-fill text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <h4 class="fw-semibold mb-0">Course Group Mapping</h4>
                                    <p class="text-muted small mb-0">Manage courses, groups and student assignments</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-8 d-flex justify-content-md-end flex-wrap gap-2">

                            <a href="{{ route('group.mapping.create') }}"
                                class="btn btn-primary px-3 d-flex align-items-center gap-2 shadow-sm">
                                <i class="bi bi-plus-circle-fill"></i>
                                Add Group Mapping
                            </a>

                            <button type="button" class="btn btn-info px-3 d-flex align-items-center gap-2 shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="bi bi-person-plus-fill"></i>
                                Add Student
                            </button>

                            <button type="button" class="btn btn-success px-3 d-flex align-items-center gap-2 shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                                Import Excel
                            </button>

                            <a href="{{ route('group.mapping.export.student.list') }}"
                                class="btn btn-outline-primary px-3 d-flex align-items-center gap-2 shadow-sm">
                                <i class="bi bi-download"></i>
                                Export Excel
                            </a>
                        </div>
                    </div>

                        {{-- Status Filter --}}
                        <div class="row g-3 mb-4 align-items-end group-mapping-filters-row">
                            <div class="col-12 col-md-4">
                                <label for="courseFilter" class="form-label fw-semibold">Course Name</label>
                                <select id="courseFilter" class="form-select shadow-sm">
                                    <option value="">All Courses</option>
                                    @foreach($courses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="groupTypeFilter" class="form-label fw-semibold">Group Type</label>
                                <select id="groupTypeFilter" class="form-select shadow-sm">
                                    <option value="">All Group Types</option>
                                    @foreach($groupTypes ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-2 d-flex gap-2 align-items-end">
                                <button type="button"
                                    class="btn btn-outline-secondary px-4 py-2 mt-0 mt-md-4 shadow-sm w-100 w-md-auto"
                                    id="resetFilters">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                                </button>
                            </div>
                            <div class="col-12 col-md-3 text-md-end group-mapping-status-col">
                                <label class="form-label fw-semibold d-md-none mb-2">Status</label>
                                <div class="btn-group shadow-sm w-100 w-md-auto"
                                    role="group"
                                    aria-label="Group Mapping Status Filter">
                                    <button type="button" class="btn btn-outline-success px-4 py-2 fw-semibold"
                                        id="filterGroupActive" aria-pressed="false">
                                        <i class="bi bi-check-circle me-1"></i> Active
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold"
                                        id="filterGroupArchive" aria-pressed="false">
                                        <i class="bi bi-archive me-1"></i> Archive
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Filters reserved row (kept for future advanced filters) --}}
                        <div class="row g-3 mb-4 align-items-end"></div>
                </div>
                {{-- End group-mapping-top-section --}}

                        <!-- Add Student Modal -->
                        <div class="modal fade" id="addStudentModal" tabindex="-1"
                            aria-labelledby="addStudentModalLabel" aria-hidden="true" data-bs-backdrop="static"
                            data-bs-keyboard="false">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                                <div class="modal-content border-0 shadow-lg">
                                    <form id="addStudentForm">
                                        @csrf
                                        <div class="modal-header border-bottom bg-light">
                                            <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="addStudentModalLabel">
                                                <i class="bi bi-person-plus-fill text-primary"></i>
                                                Add Student to Group
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body py-4">
                                            <div id="addStudentAlert" class="alert d-none" role="alert"></div>

                                            <div class="mb-3">
                                                <label for="studentName" class="form-label">Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="studentName" name="name"
                                                    placeholder="Enter student name" required maxlength="255">
                                            </div>

                                            <div class="mb-3">
                                                <label for="studentOtCode" class="form-label">OT Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="studentOtCode" name="otcode"
                                                    placeholder="Enter OT code" required maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label for="studentEmail" class="form-label">Course <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="studentCourse" name="course_master_pk" required>
                                                    <option value="">Select Course</option>
                                                    @foreach($courses ?? [] as $pk => $name)
                                                    <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="studentGroupType" class="form-label">Group Type <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="studentGroupType" name="group_type"
                                                    required>
                                                    <option value="">Select Group Type</option>
                                                    @foreach($groupTypes ?? [] as $pk => $name)
                                                    <option value="{{ $name }}" data-type-id="{{ $pk }}">{{ $name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Select a group type to filter available group
                                                    names</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="studentGroupName" class="form-label">Group Name <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="studentGroupName" name="group_name"
                                                    required disabled>
                                                    <option value="">Select Group Name</option>
                                                </select>
                                                <small class="text-muted" id="groupNameHelp">Please select a group type
                                                    first</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top bg-light">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-person-plus me-1"></i> Add Student
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Add Student Modal -->

                        <!-- Import Excel Modal -->
                        <div class="modal fade" id="importModal" tabindex="-1"
                            aria-labelledby="importModalLabel" aria-hidden="true" data-bs-backdrop="static"
                            data-bs-keyboard="false">

                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg"> 
                                    <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                                        @csrf

                                        <!-- Modal Header -->
                                        <div class="modal-header border-bottom bg-light">
                                            <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="importModalLabel">
                                                <i class="bi bi-file-earmark-excel-fill text-success"></i>
                                                Import Excel File
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                     
                                        <!-- Dropdown Section -->
                                        <div class="modal-body pt-3">

                                            <!-- File Upload -->
                                            <div class="mb-3">
                                                <label for="importFile" class="form-label">Select Course</label>
                                               <select name="course_master_pk" id="course_master_pk_model" class="form-select shadow-sm " required>
                                                    <option value="">Select Course</option>
                                                   @foreach($courses ?? [] as $pk => $name)
                                                    <option value="{{ $pk }}"  {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="importFile" class="form-label">Select Excel File</label>
                                                <input type="file" name="file" id="importFile" class="form-control"
                                                    accept=".xlsx, .xls, .csv" required>
                                                <small class="text-muted">
                                                    Allowed: .xlsx, .xls, .csv | Max ~500 MB
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer border-top bg-light">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>

                                            <button type="button" class="btn btn-success" id="upload_import">
                                                <i class="bi bi-upload me-1"></i> Upload & Import
                                            </button>

                                            <a href="{{ asset('admin_assets/sample/group_mapping_sample.xlsx') }}"
                                                class="btn btn-info" download>
                                                <i class="bi bi-download me-1"></i> Download Sample
                                            </a>
                                        </div>
                                    </form>

                                    <div id="importErrors" class="alert  d-none ">
                                        <h5 class="text-center mb-3">
                                            <i class="mdi mdi-alert-circle-outline"></i> Validation Errors Found
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover table-sm align-middle">
                                                <thead class="table-info">
                                                    <tr>
                                                        <th style="width: 10%;">Row</th>
                                                        <th>Errors</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="importErrorTableBody">
                                                    <!-- JS will insert rows here -->
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- End Modal -->

                        <!-- Student Details Modal -->
                        <div class="modal fade" id="studentDetailsModal" tabindex="-1"
                            aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
                                <div class="modal-content border-0 shadow-lg rounded-4">

                                    <!-- Header -->
                                    <div class="modal-header border-0 pb-2">
                                        <h5 class="modal-title fw-semibold" id="studentDetailsModalLabel">
                                            <i class="bi bi-person-vcard me-2 text-primary"></i> Student Details
                                        </h5>
                                        <button type="button" class="btn-close" aria-label="Close"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <!-- Body -->
                                    <div class="modal-body pt-0">

                                        <!-- Search Section -->
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="material-icons material-symbols-rounded">search</i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    id="studentSearchInput"
                                                    placeholder="Search students by name, OT code, email, or contact number..."
                                                    autocomplete="off">
                                                <button class="btn btn-outline-secondary border-start-0" type="button"
                                                    id="clearStudentSearch" style="display: none;">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <span id="studentSearchResultsCount"></span>
                                            </small>
                                        </div>

                                        <!-- Student Info Dynamic Section -->
                                        <div id="studentDetailsContent" class="rounded-3 bg-light-subtle border">
                                            <p class="text-muted mb-0">Loading student details...</p>
                                        </div>

                                        <!-- Bulk Message Section -->
                                        <div id="bulkMessageContainer" class="mt-4 d-none">
                                            <div class="card border-0 shadow-sm rounded-4">
                                                <div class="card-body">

                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h6 class="fw-semibold mb-1">Send Message</h6>
                                                            <p class="text-muted small mb-0">
                                                                Send SMS or Email to selected Officer Trainees
                                                            </p>
                                                        </div>

                                                        <button type="button" class="btn-close btn-close-sm"
                                                            id="closeBulkMessage" aria-label="Close"></button>
                                                    </div>

                                                    <div id="bulkMessageAlert" class="alert d-none mb-3" role="alert">
                                                    </div>

                                                    <!-- Message Box -->
                                                    <div class="mb-3">
                                                        <label for="bulkMessageText" class="form-label fw-semibold">
                                                            Message <span class="text-danger">*</span>
                                                        </label>

                                                        <textarea id="bulkMessageText" rows="4" maxlength="1000"
                                                            class="form-control rounded-3"
                                                            aria-describedby="bulkMessageCharHelp"
                                                            placeholder="Type your message here..."></textarea>

                                                        <div id="bulkMessageCharHelp"
                                                            class="form-text text-end text-secondary small">
                                                            <span id="bulkMessageCharCount">0</span>/1000 characters
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button type="button"
                                                            class="btn btn-outline-success send-bulk-message"
                                                            data-channel="sms">
                                                            <i class="bi bi-chat-text me-1"></i> Send SMS
                                                        </button>
                                                        <button type="button" class="btn btn-primary send-bulk-message"
                                                            data-channel="email">
                                                            <i class="bi bi-envelope-paper-heart me-1"></i> Send Email
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer justify-content-between align-items-center">
                                        <div class="text-muted small" id="selectedOtCount">0 OT(s) selected</div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary"
                                                id="toggleBulkMessage">
                                                <i class="bi bi-send-check me-1"></i> Send SMS / Send Email
                                            </button>
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Student Modal -->
                        <div class="modal fade" id="editStudentModal" tabindex="-1"
                            aria-labelledby="editStudentModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                                <div class="modal-content border-0 shadow-lg">
                                    <div class="modal-header border-bottom bg-light">
                                        <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="editStudentModalLabel">
                                            <i class="bi bi-pencil-square text-primary"></i>
                                            Edit Student
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form id="editStudentForm">
                                        @csrf
                                        <div class="modal-body">
                                            <div id="editStudentAlert" class="alert d-none" role="alert"></div>
                                            <input type="hidden" name="student_id" id="editStudentId">
                                            <div class="mb-3">
                                                <label for="editStudentName" class="form-label">Display Name</label>
                                                <input type="text" class="form-control" id="editStudentName"
                                                    name="display_name" required maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editStudentEmail" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="editStudentEmail"
                                                    name="email" maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editStudentContact" class="form-label">Contact No</label>
                                                <input type="text" class="form-control" id="editStudentContact"
                                                    name="contact_no" maxlength="20">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top bg-light">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <hr class="my-4">
                        {{-- Table: controls above scroll area so they stay visible on mobile --}}
                        <div class="group-mapping-table-section">
                            <p class="group-mapping-scroll-hint d-md-none text-muted small mb-2 text-center">
                                <i class="bi bi-arrow-left-right me-1"></i> Swipe horizontally to see all columns
                            </p>
                            <div class="group-mapping-table-wrapper table-responsive">
                                {!! $dataTable->table(['class' => 'table text-nowrap']) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection
    @push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
        // Fix: Allow dropdowns (Select2, native select) to receive clicks inside Bootstrap 5 modals
        (function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.prototype._enforceFocus) {
                var _enforceFocus = bootstrap.Modal.prototype._enforceFocus;
                bootstrap.Modal.prototype._enforceFocus = function() {
                    if (document.querySelector('.select2-container--open') || document.activeElement?.closest?.('.select2-container')) return;
                    if (document.activeElement?.tagName === 'OPTION') return;
                    _enforceFocus.apply(this, arguments);
                };
            }
        })();

        $(document).on('preXhr.dt', '#group-mapping-table', function(e, settings, data) {
            // Only send filters if they are explicitly set
            if (window.groupMappingCurrentFilter) {
                data.status_filter = window.groupMappingCurrentFilter;
            }
            var courseFilter = $('#courseFilter').val();
            var groupTypeFilter = $('#groupTypeFilter').val();

            if (courseFilter) {
                data.course_filter = courseFilter;
            }
            if (groupTypeFilter) {
                data.group_type_filter = groupTypeFilter;
            }
        });

        $(document).ready(function() {
            // Set default filter to active courses
            window.groupMappingCurrentFilter = 'active';

            setTimeout(function() {
                var table = $('#group-mapping-table').DataTable();

                // Set Active button as active by default
                setActiveButton($('#filterGroupActive'));

                $('#filterGroupActive').on('click', function() {
                    setActiveButton($(this));
                    window.groupMappingCurrentFilter = 'active';
                    table.ajax.reload();
                });

                $('#filterGroupArchive').on('click', function() {
                    setActiveButton($(this));
                    window.groupMappingCurrentFilter = 'archive';
                    table.ajax.reload();
                });

                $('#courseFilter, #groupTypeFilter').on('change', function() {
                    // Reload table when filters change
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function() {
                    $('#courseFilter').val('');
                    $('#groupTypeFilter').val('');
                    window.groupMappingCurrentFilter = 'active'; // Reset to active by default
                    setActiveButton($('#filterGroupActive'));
                    table.ajax.reload();
                });

                function setActiveButton(activeBtn) {
                    $('#filterGroupActive')
                        .removeClass('btn-success active text-white')
                        .addClass('btn-outline-success')
                        .attr('aria-pressed', 'false');

                    $('#filterGroupArchive')
                        .removeClass('btn-secondary active text-white')
                        .addClass('btn-outline-secondary')
                        .attr('aria-pressed', 'false');

                    if (activeBtn.attr('id') === 'filterGroupActive') {
                        activeBtn.removeClass('btn-outline-success')
                            .addClass('btn-success text-white active')
                            .attr('aria-pressed', 'true');
                    } else {
                        activeBtn.removeClass('btn-outline-secondary')
                            .addClass('btn-secondary text-white active')
                            .attr('aria-pressed', 'true');
                    }
                }

                function resetFilterButtons() {
                    $('#filterGroupActive')
                        .removeClass('btn-success active text-white')
                        .addClass('btn-outline-success')
                        .attr('aria-pressed', 'false');

                    $('#filterGroupArchive')
                        .removeClass('btn-secondary active text-white')
                        .addClass('btn-outline-secondary')
                        .attr('aria-pressed', 'false');
                }

            }, 150);

            // Handle Group Type change - Load Group Names
            $('#studentGroupType').on('change', function() {
                const groupTypeSelect = $(this);
                const groupNameSelect = $('#studentGroupName');
                const groupNameHelp = $('#groupNameHelp');
                const selectedOption = groupTypeSelect.find('option:selected');
                const groupTypeId = selectedOption.data('type-id');
                const groupTypeName = selectedOption.val();

                // Reset group name dropdown
                groupNameSelect.html('<option value="">Loading...</option>').prop('disabled', true);

                if (!groupTypeId || !groupTypeName) {
                    groupNameSelect.html('<option value="">Select Group Name</option>').prop('disabled',
                        true);
                    groupNameHelp.text('Please select a group type first').removeClass('text-success')
                        .addClass(
                            'text-muted');
                    return;
                }

                // Fetch group names for selected group type
                $.ajax({
                    url: routes.groupMappingGetGroupNamesByType,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        group_type_id: groupTypeId
                    },
                    success: function(response) {
                        if (response.status === 'success' && response.group_names && response
                            .group_names.length > 0) {
                            groupNameSelect.html('<option value="">Select Group Name</option>');

                            // Populate group names
                            response.group_names.forEach(function(groupName) {
                                groupNameSelect.append($('<option>', {
                                    value: groupName,
                                    text: groupName
                                }));
                            });

                            groupNameSelect.prop('disabled', false);
                            groupNameHelp.text(
                                    `${response.group_names.length} group name(s) available`)
                                .removeClass('text-muted').addClass('text-success');
                        } else {
                            groupNameSelect.html(
                                    '<option value="">No group names found</option>')
                                .prop('disabled', true);
                            groupNameHelp.text('No group names available for this group type')
                                .removeClass('text-success').addClass('text-danger');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading group names.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        groupNameSelect.html('<option value="">Error loading</option>').prop(
                            'disabled', true);
                        groupNameHelp.text(errorMessage).removeClass('text-success').addClass(
                            'text-danger');
                    }
                });
            });

            // Reset form when modal is closed
            $('#addStudentModal').on('hidden.bs.modal', function() {
                $('#addStudentForm')[0].reset();
                $('#addStudentAlert').addClass('d-none');
                $('#studentGroupName').html('<option value="">Select Group Name</option>').prop('disabled',
                    true);
                $('#groupNameHelp').text('Please select a group type first').removeClass(
                    'text-success text-danger').addClass('text-muted');
            });

            // Handle Add Student Form Submission
            $('#addStudentForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const alertBox = $('#addStudentAlert');

                // Disable submit button
                submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Adding...');
                alertBox.addClass('d-none');

                $.ajax({
                    url: '{{ route("group.mapping.add.single.student") }}',
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.status === 'success') {
                            alertBox.removeClass('d-none alert-danger')
                                .addClass('alert-success')
                                .html('<i class="mdi mdi-check-circle"></i> ' + response
                                    .message);

                            // Reset form
                            form[0].reset();

                            // Reload DataTable
                            $('#group-mapping-table').DataTable().ajax.reload();

                            // Close modal after 1.5 seconds
                            setTimeout(function() {
                                $('#addStudentModal').modal('hide');
                                alertBox.addClass('d-none');
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while adding the student.';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                const errors = Object.values(xhr.responseJSON.errors).flat();
                                errorMessage = errors.join('<br>');
                            }
                        }

                        alertBox.removeClass('d-none alert-success')
                            .addClass('alert-danger')
                            .html('<i class="mdi mdi-alert-circle"></i> ' + errorMessage);
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-person-plus me-1"></i> Add Student');
                    }
                });
            });

            // Reset alert when modal is closed
            $('#addStudentModal').on('hidden.bs.modal', function() {
                $('#addStudentForm')[0].reset();
                $('#addStudentAlert').addClass('d-none');
            });
        });
    </script>
    @endpush