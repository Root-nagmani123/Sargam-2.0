@extends('admin.layouts.master')

@section('title', 'Course Group Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid gm-master-page">
    <x-breadcrum title="Course Group Mapping">
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
            <div class="dropdown gm-add-student-hover">
                <button type="button"
                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm"
                    id="gmAddStudentTrigger"
                    aria-expanded="false"
                    aria-haspopup="true">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    <span>Add Student</span>
                    <i class="bi bi-chevron-down small opacity-75" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2 gm-add-student-menu"
                    aria-labelledby="gmAddStudentTrigger">
                    <li>
                        <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2 rounded-1 mx-2 py-2"
                            data-bs-toggle="modal"
                            data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus text-primary" aria-hidden="true"></i>
                            <span>Add Single</span>
                        </button>
                    </li>
                    <li>
                        <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2 rounded-1 mx-2 py-2"
                            data-bs-toggle="modal"
                            data-bs-target="#importModal">
                            <i class="bi bi-people text-primary" aria-hidden="true"></i>
                            <span>Add in Bulk</span>
                        </button>
                    </li>
                </ul>
            </div>
            <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal"
                data-bs-target="#gmAddGroupMappingModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add Group Mapping</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter group mappings by course status">
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="filterGroupActive"
                    aria-pressed="true"
                    aria-current="true">
                    Active
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="filterGroupArchive"
                    aria-pressed="false">
                    Archived
                </button>
            </li>
        </ul>

        <div class="dropdown">
            <button type="button" class="btn programme-dt-btn-columns gm-download-btn dropdown-toggle"
                id="gmDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="gmDownloadBtn">
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="gmDownloadCsv">
                        <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                        <span>Download CSV</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="gmDownloadPdf">
                        <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                        <span>Download PDF</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="card gm-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select id="courseFilter" class="form-select form-select-sm" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="groupTypeFilter" class="form-select form-select-sm" aria-label="Filter by group type">
                            <option value="">Group Type</option>
                            @foreach($groupTypes ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="facultyFilter" class="form-select form-select-sm" aria-label="Filter by faculty">
                            <option value="">Faculty</option>
                            @foreach($filterFaculties ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-xl-auto" id="gmSearchWrap">
                    <button type="button" class="btn programme-dt-btn-columns" id="gmBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#gmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="gmDtSearch" class="programme-dt-search">
                        <div class="dataTables_filter">
                            <label class="mb-0 w-100">
                                <input type="search" id="gmCustomSearch" class="form-control shadow-none"
                                    placeholder="Search" aria-label="Search group mappings" autocomplete="off">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel gm-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="gmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Group Mapping Modal -->
<div class="modal fade gm-mapping-modal" id="gmAddGroupMappingModal" tabindex="-1"
    aria-labelledby="gmAddGroupMappingModalLabel" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0">
            <form action="{{ route('group.mapping.store') }}" method="POST" id="classSessionForm" novalidate>
                @csrf
                <input type="hidden" name="pk" id="gmMappingPk" value="">
                <div class="modal-header">
                    <h5 class="modal-title mb-0" id="gmAddGroupMappingModalLabel">Add Group Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="gmAddGroupMappingAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="gm-mapping-form-fields">
                        <div class="mb-3">
                            <label for="gmCourseId" class="form-label cgt-field-label mb-2">Course Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="gmCourseId" name="course_id" required>
                                <option value="">Select Course Name</option>
                                @foreach($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}" {{ count($courses ?? []) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="gmTypeId" class="form-label cgt-field-label mb-2">Group Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="gmTypeId" name="type_id" required>
                                <option value="">Select Group Type</option>
                                @foreach($groupTypes ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="gmGroupName" class="form-label cgt-field-label mb-2">Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="gmGroupName" name="group_name"
                                placeholder="eg. IAS Course" required maxlength="255" autocomplete="off">
                        </div>

                        <div class="mb-0">
                            <label for="gmFacilityId" class="form-label cgt-field-label mb-2">Faculty</label>
                            <select class="form-select js-gm-faculty-choice" id="gmFacilityId" name="facility_id">
                                <option value="">Select</option>
                                @foreach($facilities ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="saveClassSessionForm">
                        Create Group Mapping
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Student Modal (Single) -->
<div class="modal fade" id="addStudentModal" tabindex="-1"
    aria-labelledby="addStudentModalLabel" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <form id="addStudentForm">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="addStudentAlert" class="alert d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="studentOtCode" class="form-label cgt-field-label">OT Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-1" id="studentOtCode" name="otcode"
                            placeholder="eg. OT1344" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="studentName" class="form-label cgt-field-label">OT Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-1" id="studentName" name="name"
                            placeholder="eg. John Doe" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="studentCourse" class="form-label cgt-field-label">Course Name <span class="text-danger">*</span></label>
                        <select class="form-select rounded-1" id="studentCourse" name="course_master_pk" required>
                            <option value="">Select Course Name</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="studentGroupType" class="form-label cgt-field-label">Group Type <span class="text-danger">*</span></label>
                        <select class="form-select rounded-1" id="studentGroupType" name="group_type" required>
                            <option value="">Select Group Type</option>
                            @foreach($groupTypes ?? [] as $pk => $name)
                            <option value="{{ $name }}" data-type-id="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="studentGroupName" class="form-label cgt-field-label">Group Name</label>
                        <select class="form-select rounded-1" id="studentGroupName" name="group_name" required disabled>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted d-block mt-1" id="groupNameHelp">Please select a group type first</small>
                    </div>
                </div>
                <div class="modal-footer border-top gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-1 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4">
                        Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add in Bulk Modal -->
<div class="modal fade" id="importModal" tabindex="-1"
    aria-labelledby="importModalLabel" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cgt-form-modal gm-bulk-modal border-0 shadow-lg rounded-4">
            <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="importModalLabel">Add in Bulk</h5>
                    <button type="button" class="btn-close btn-cancel" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="gm-import-progress-wrap mb-4">
                        <div class="progress rounded-1" style="height: 6px;" role="progressbar"
                            aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                            <div class="progress-bar bg-primary rounded-1" id="gmImportProgress" style="width: 50%;"></div>
                        </div>
                    </div>

                    <div id="gmImportStep1">
                        <label for="importFile" class="form-label cgt-field-label visually-hidden">Upload file</label>
                        <div class="gm-upload-dropzone rounded-1 text-center" id="gmUploadDropzone" role="button" tabindex="0">
                            <i class="bi bi-file-earmark-arrow-up gm-upload-icon d-block mb-2" aria-hidden="true"></i>
                            <p class="fw-semibold text-body mb-1">Drag or click here to upload your file</p>
                            <p class="text-muted small mb-0">
                                Allowed: .xlsx, .xls, .csv | Max ~500 MB |
                                <a href="{{ asset('admin_assets/sample/group_mapping_sample.xlsx') }}" class="text-primary fw-semibold" download>Sample File</a>
                            </p>
                            <p class="small text-primary fw-medium mt-2 mb-0 d-none" id="gmImportFileName"></p>
                        </div>
                        <input type="file" name="file" id="importFile" class="visually-hidden"
                            accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div id="gmImportStep2" class="d-none">
                        <div id="importBulkInfo" class="alert alert-info d-flex align-items-start gap-2 rounded-1 border-0 d-none" role="status">
                            <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <span id="importBulkInfoText">Your file is ready. Select a course to complete the import.</span>
                        </div>
                        <div class="mb-0">
                            <label for="course_master_pk_model" class="form-label cgt-field-label">Course Name <span class="text-danger">*</span></label>
                            <select name="course_master_pk" id="course_master_pk_model" class="form-select rounded-1" required>
                                <option value="">Select</option>
                                @foreach($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-1 px-4" id="gmImportNext">Next</button>
                    <button type="button" class="btn btn-primary rounded-1 px-4 d-none" id="upload_import">
                        Add Course Group Mapping
                    </button>
                </div>
            </form>

            <div id="importErrors" class="alert d-none mx-3 mb-3">
                <h5 class="text-center mb-3">
                    <i class="bi bi-exclamation-circle me-1"></i> Validation Errors Found
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10%;">Row</th>
                                <th>Errors</th>
                            </tr>
                        </thead>
                        <tbody id="importErrorTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1"
    aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-semibold mb-0" id="studentDetailsModalLabel">
                    <i class="bi bi-person-vcard me-2 text-primary" aria-hidden="true"></i> Student Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <div class="programme-dt-search w-100">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-2">
                                <i class="bi bi-search text-muted" aria-hidden="true"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 rounded-end-2 shadow-none"
                                id="studentSearchInput"
                                placeholder="Search students by name, OT code, email, or contact number..."
                                autocomplete="off"
                                aria-label="Search students">
                            <button class="btn btn-outline-secondary border-start-0" type="button"
                                id="clearStudentSearch" style="display: none;" aria-label="Clear search">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <span id="studentSearchResultsCount"></span>
                    </small>
                </div>
                <div id="studentDetailsContent" class="rounded-1 bg-body-tertiary border p-3">
                    <p class="text-muted mb-0">Loading student details...</p>
                </div>
                <div id="bulkMessageContainer" class="mt-4 d-none">
                    <div class="card border-0 shadow-sm rounded-1">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-semibold mb-1">Send Message</h6>
                                    <p class="text-muted small mb-0">Send SMS or Email to selected Officer Trainees</p>
                                </div>
                                <button type="button" class="btn-close btn-close-sm" id="closeBulkMessage" aria-label="Close"></button>
                            </div>
                            <div id="bulkMessageAlert" class="alert d-none mb-3" role="alert"></div>
                            <div class="mb-3">
                                <label for="bulkMessageText" class="form-label fw-semibold">
                                    Message <span class="text-danger">*</span>
                                </label>
                                <textarea id="bulkMessageText" rows="4" maxlength="1000"
                                    class="form-control rounded-1"
                                    aria-describedby="bulkMessageCharHelp"
                                    placeholder="Type your message here..."></textarea>
                                <div id="bulkMessageCharHelp" class="form-text text-end text-secondary small">
                                    <span id="bulkMessageCharCount">0</span>/1000 characters
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-success rounded-1 send-bulk-message" data-channel="sms">
                                    <i class="bi bi-chat-text me-1" aria-hidden="true"></i> Send SMS
                                </button>
                                <button type="button" class="btn btn-primary rounded-1 send-bulk-message" data-channel="email">
                                    <i class="bi bi-envelope me-1" aria-hidden="true"></i> Send Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top justify-content-between align-items-center px-4 py-3">
                <div class="text-muted small" id="selectedOtCount">0 OT(s) selected</div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary rounded-1" id="toggleBulkMessage">
                        <i class="bi bi-send-check me-1" aria-hidden="true"></i> Send SMS / Send Email
                    </button>
                    <button type="button" class="btn btn-secondary rounded-1" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="gmColumnVisibilityModal" tabindex="-1" aria-labelledby="gmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="gmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="gmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1"
    aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <form id="editStudentForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editStudentAlert" class="alert d-none" role="alert"></div>
                    <input type="hidden" name="student_id" id="editStudentId">
                    <div class="mb-3">
                        <label for="editStudentName" class="form-label cgt-field-label">Display Name</label>
                        <input type="text" class="form-control rounded-1" id="editStudentName"
                            name="display_name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="editStudentEmail" class="form-label cgt-field-label">Email</label>
                        <input type="email" class="form-control rounded-1" id="editStudentEmail"
                            name="email" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label for="editStudentContact" class="form-label cgt-field-label">Contact No</label>
                        <input type="text" class="form-control rounded-1" id="editStudentContact"
                            name="contact_no" maxlength="20">
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
{!! $dataTable->scripts() !!}
<script>
$(document).on('preXhr.dt', '#group-mapping-table', function(e, settings, data) {
    if (window.groupMappingCurrentFilter) {
        data.status_filter = window.groupMappingCurrentFilter;
    }
    var courseFilter = $('#courseFilter').val();
    var groupTypeFilter = $('#groupTypeFilter').val();
    var facultyFilter = $('#facultyFilter').val();
    if (courseFilter) {
        data.course_filter = courseFilter;
    }
    if (groupTypeFilter) {
        data.group_type_filter = groupTypeFilter;
    }
    if (facultyFilter) {
        data.faculty_filter = facultyFilter;
    }
});

$(document).ready(function() {
    window.groupMappingCurrentFilter = 'active';

    function reloadFacultyFilterOptions(done) {
        $.get('{{ route('group.mapping.filter.faculties') }}', {
            status_filter: window.groupMappingCurrentFilter || 'active',
            course_filter: $('#courseFilter').val() || '',
            group_type_filter: $('#groupTypeFilter').val() || ''
        }).done(function(res) {
            var $sel = $('#facultyFilter');
            var current = $sel.val();
            $sel.find('option:not(:first)').remove();
            $.each(res.faculties || {}, function(pk, name) {
                $sel.append($('<option></option>').val(pk).text(name));
            });
            if (current && res.faculties && res.faculties[current]) {
                $sel.val(current);
            } else {
                $sel.val('');
            }
            if (typeof done === 'function') {
                done();
            }
        });
    }

    function setActiveFilterButton(activeBtn) {
        $('#filterGroupActive, #filterGroupArchive')
            .removeClass('active')
            .attr('aria-pressed', 'false')
            .removeAttr('aria-current');
        activeBtn
            .addClass('active')
            .attr('aria-pressed', 'true')
            .attr('aria-current', 'true');
    }

    function enhanceGmDtControls() {
        var $wrapper = $('#group-mapping-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $footer = $('#gmDtFooter');

        if ($footer.data('dtReady')) {
            updateGmDtCount();
            return;
        }

        var $paginate = $wrapper.find('.dataTables_paginate').first();
        var $length = $wrapper.find('.dataTables_length').first();

        // Don't build (and don't lock in dtReady) until DataTables has actually
        // rendered its controls — otherwise we'd cache an empty footer forever.
        if (!$footer.length || (!$paginate.length && !$length.length)) {
            return;
        }

        var $pagCol = $('<div class="programme-dt-pagination"></div>');
        var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

        if ($paginate.length) {
            $paginate.find('.pagination').addClass('mb-0');
            $pagCol.append($paginate);
        }

        if ($length.length) {
            var $select = $length.find('select').addClass('form-select form-select-sm');
            $length.find('label')
                .empty()
                .append(document.createTextNode('Showing '))
                .append($select)
                .append(document.createTextNode(' '));
            $countCol.append($length);
        }

        // Self-managed count text — does NOT rely on relocating DataTables' own
        // .dataTables_info node (which was the fragile part that kept failing).
        $countCol.append('<span class="gm-count-text text-muted mb-0"></span>');

        $footer.append($pagCol).append($countCol);
        $footer.data('dtReady', true);
        updateGmDtCount();
    }

    function updateGmDtCount() {
        if (!$.fn.DataTable.isDataTable('#group-mapping-table')) {
            return;
        }
        var info = $('#group-mapping-table').DataTable().page.info();
        var $countText = $('#gmDtFooter .gm-count-text');
        if ($countText.length && info && info.recordsDisplay !== undefined) {
            $countText.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    /* ---------- Column show / hide (DataTables API) ---------- */
    var gmColStorageKey = 'gmGrid:hiddenColumns:v1';

    function gmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(gmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function gmPersistHiddenCols(arr) {
        try { localStorage.setItem(gmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupGmColumns(dt) {
        if (!dt) {
            return;
        }
        var hidden = gmGetHiddenCols();

        // Apply saved visibility — DataTables keeps this across redraws / ajax reloads.
        dt.columns().every(function() {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        // Build the modal checkboxes once from the live table headers.
        var $grid = $('#gmColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function() {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            var inputId = 'gmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function() {
                var h = gmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                gmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    function bindGmTableUi(table) {
        enhanceGmDtControls();
        updateGmDtCount();
        setupGmColumns(table);

        table.on('draw.dt', function() {
            var $wrapper = $('#group-mapping-table_wrapper');
            if ($wrapper.find('.dataTables_paginate').length && !$('#gmDtFooter .dataTables_paginate').length) {
                $('#gmDtFooter').empty().data('dtReady', false);
            }
            enhanceGmDtControls();
            updateGmDtCount();
        });
    }

    $('#group-mapping-table').on('init.dt', function() {
        bindGmTableUi($(this).DataTable());
    });

    setTimeout(function() {
        if (!$.fn.DataTable.isDataTable('#group-mapping-table')) {
            return;
        }

        var table = $('#group-mapping-table').DataTable();
        bindGmTableUi(table);

        setActiveFilterButton($('#filterGroupActive'));

        $('#filterGroupActive').on('click', function() {
            setActiveFilterButton($(this));
            window.groupMappingCurrentFilter = 'active';
            reloadFacultyFilterOptions(function() {
                table.ajax.reload();
            });
        });

        $('#filterGroupArchive').on('click', function() {
            setActiveFilterButton($(this));
            window.groupMappingCurrentFilter = 'archive';
            reloadFacultyFilterOptions(function() {
                table.ajax.reload();
            });
        });

        $('#courseFilter, #groupTypeFilter').on('change', function() {
            reloadFacultyFilterOptions(function() {
                table.ajax.reload();
            });
        });

        $('#facultyFilter').on('change', function() {
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function() {
            $('#courseFilter').val('');
            $('#groupTypeFilter').val('');
            $('#facultyFilter').val('');
            window.groupMappingCurrentFilter = 'active';
            setActiveFilterButton($('#filterGroupActive'));
            reloadFacultyFilterOptions(function() {
                table.ajax.reload();
            });
        });
    }, 150);

    // Drive the DataTable search from the custom input (debounced).
    var gmSearchTimer = null;
    $('#gmCustomSearch').on('input', function() {
        var value = this.value;
        clearTimeout(gmSearchTimer);
        gmSearchTimer = setTimeout(function() {
            if ($.fn.DataTable.isDataTable('#group-mapping-table')) {
                $('#group-mapping-table').DataTable().search(value).draw();
            }
        }, 300);
    });

    /* ---------- Download PDF (respects applied filters) ---------- */
    $('#gmDownloadPdf').on('click', function() {
        var params = {
            status_filter: window.groupMappingCurrentFilter || 'active'
        };
        var courseFilter = $('#courseFilter').val();
        var groupTypeFilter = $('#groupTypeFilter').val();
        var facultyFilter = $('#facultyFilter').val();
        if (courseFilter) {
            params.course_filter = courseFilter;
        }
        if (groupTypeFilter) {
            params.group_type_filter = groupTypeFilter;
        }
        if (facultyFilter) {
            params.faculty_filter = facultyFilter;
        }
        if ($.fn.DataTable.isDataTable('#group-mapping-table')) {
            var searchValue = $('#group-mapping-table').DataTable().search();
            if (searchValue) {
                params.search = searchValue;
            }
        }
        var url = '{{ route('group.mapping.download.pdf') }}?' + $.param(params);
        window.open(url, '_blank');
    });

    /* ---------- Download current table as CSV ---------- */
    $('#gmDownloadCsv').on('click', function() {
        var tableEl = document.getElementById('group-mapping-table');
        if (!tableEl) {
            return;
        }

        var lines = [];

        // Header row (exclude the trailing Action column).
        var $headers = $(tableEl).find('thead th');
        var headerCells = [];
        $headers.each(function(i) {
            if (i === $headers.length - 1) {
                return; // skip Action
            }
            headerCells.push('"' + ($(this).text() || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"');
        });
        lines.push(headerCells.join(','));

        // Body rows currently rendered (current page).
        $(tableEl).find('tbody tr').each(function() {
            var tr = this;
            if (tr.children.length <= 1) {
                return; // empty-state row
            }
            var cells = [];
            for (var i = 0; i < tr.children.length - 1; i++) { // exclude Action
                var txt = (tr.children[i].innerText || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                cells.push('"' + txt + '"');
            }
            lines.push(cells.join(','));
        });

        if (lines.length <= 1) {
            return;
        }

        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'course_group_mapping_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    $('#studentGroupType').on('change', function() {
        const groupTypeSelect = $(this);
        const groupNameSelect = $('#studentGroupName');
        const groupNameHelp = $('#groupNameHelp');
        const selectedOption = groupTypeSelect.find('option:selected');
        const groupTypeId = selectedOption.data('type-id');
        const groupTypeName = selectedOption.val();

        groupNameSelect.html('<option value="">Loading...</option>').prop('disabled', true);

        if (!groupTypeId || !groupTypeName) {
            groupNameSelect.html('<option value="">Select Group Name</option>').prop('disabled', true);
            groupNameHelp.text('Please select a group type first').removeClass('text-success text-danger').addClass('text-muted');
            return;
        }

        $.ajax({
            url: routes.groupMappingGetGroupNamesByType,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                group_type_id: groupTypeId
            },
            success: function(response) {
                if (response.status === 'success' && response.group_names && response.group_names.length > 0) {
                    groupNameSelect.html('<option value="">Select Group Name</option>');
                    response.group_names.forEach(function(groupName) {
                        groupNameSelect.append($('<option>', { value: groupName, text: groupName }));
                    });
                    groupNameSelect.prop('disabled', false);
                    groupNameHelp.text(response.group_names.length + ' group name(s) available')
                        .removeClass('text-muted text-danger').addClass('text-success');
                } else {
                    groupNameSelect.html('<option value="">No group names found</option>').prop('disabled', true);
                    groupNameHelp.text('No group names available for this group type')
                        .removeClass('text-success text-muted').addClass('text-danger');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error loading group names.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                groupNameSelect.html('<option value="">Error loading</option>').prop('disabled', true);
                groupNameHelp.text(errorMessage).removeClass('text-success text-muted').addClass('text-danger');
            }
        });
    });

    function resetGmImportWizard() {
        $('#gmImportStep1').removeClass('d-none');
        $('#gmImportStep2').addClass('d-none');
        $('#gmImportProgress').css('width', '50%');
        $('#gmImportProgress').parent().attr('aria-valuenow', 50);
        $('#gmImportNext').removeClass('d-none');
        $('#upload_import').addClass('d-none').prop('disabled', false).text('Add Course Group Mapping');
        $('#importBulkInfo').addClass('d-none');
        $('#gmImportFileName').addClass('d-none').text('');
    }

    $('#importModal').on('hidden.bs.modal', resetGmImportWizard);
    $('#importModal').on('show.bs.modal', resetGmImportWizard);

    $('#gmUploadDropzone').on('click', function() {
        $('#importFile').trigger('click');
    });

    $('#gmUploadDropzone').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $('#importFile').trigger('click');
        }
    });

    $('#importFile').on('change', function() {
        if (this.files && this.files.length) {
            $('#gmImportFileName').removeClass('d-none').text(this.files[0].name);
        }
    });

    $('#gmUploadDropzone').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('border-primary bg-white');
    });

    $('#gmUploadDropzone').on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('border-primary bg-white');
    });

    $('#gmUploadDropzone').on('drop', function(e) {
        var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
        var fileInput = document.getElementById('importFile');
        if (!files || !files.length || !fileInput) {
            return;
        }
        try {
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            fileInput.files = dt.files;
            $('#gmImportFileName').removeClass('d-none').text(files[0].name);
        } catch (err) {
            alert('Could not attach the dropped file. Please use click to upload.');
        }
    });

    $('#gmImportNext').on('click', function() {
        var fileInput = $('#importFile')[0];
        if (!fileInput.files || !fileInput.files.length) {
            alert('Please select a file to upload.');
            return;
        }

        var fileName = fileInput.files[0].name;
        if (!/\.(xlsx|xls|csv)$/i.test(fileName)) {
            alert('Invalid file type. Please upload a .xlsx, .xls, or .csv file.');
            fileInput.value = '';
            $('#gmImportFileName').addClass('d-none').text('');
            return;
        }

        $('#gmImportStep1').addClass('d-none');
        $('#gmImportStep2').removeClass('d-none');
        $('#gmImportProgress').css('width', '100%');
        $('#gmImportProgress').parent().attr('aria-valuenow', 100);
        $('#importBulkInfo').removeClass('d-none');
        $('#gmImportNext').addClass('d-none');
        $('#upload_import').removeClass('d-none');
    });

    $('#addStudentModal').on('hidden.bs.modal', function() {
        $('#addStudentForm')[0].reset();
        $('#addStudentAlert').addClass('d-none');
        $('#studentGroupName').html('<option value="">Select</option>').prop('disabled', true);
        $('#groupNameHelp').text('Please select a group type first').removeClass('text-success text-danger').addClass('text-muted');
    });

    const gmAddGroupMappingModalEl = document.getElementById('gmAddGroupMappingModal');
    const gmAddGroupMappingModal = gmAddGroupMappingModalEl
        ? bootstrap.Modal.getOrCreateInstance(gmAddGroupMappingModalEl)
        : null;
    let gmGroupMappingModalIsEdit = false;
    let gmFacilityChoices = null;

    const gmFacilityChoiceOpts = {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        placeholder: true,
        placeholderValue: 'Select',
        searchPlaceholderValue: 'Search…',
        position: 'bottom',
        classNames: {
            containerOuter: ['choices', 'w-100', 'gm-faculty-choices'],
            containerInner: ['choices__inner'],
            input: ['choices__input'],
            inputCloned: ['choices__input--cloned'],
            list: ['choices__list'],
            listItems: ['choices__list--multiple'],
            listSingle: ['choices__list--single'],
            listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'shadow-sm', 'w-100'],
            item: ['choices__item'],
            itemSelectable: ['choices__item--selectable'],
            itemDisabled: ['choices__item--disabled'],
            itemChoice: ['choices__item--choice'],
            placeholder: ['choices__placeholder'],
            button: ['choices__button'],
            activeState: ['is-active'],
            focusState: ['is-focused'],
            openState: ['is-open'],
            disabledState: ['is-disabled'],
            highlightedState: ['is-highlighted'],
            selectedState: ['is-selected']
        }
    };

    function destroyGmFacilityChoices() {
        const el = document.getElementById('gmFacilityId');
        if (!el) {
            return;
        }
        if (gmFacilityChoices) {
            try {
                gmFacilityChoices.destroy();
            } catch (e) { /* noop */ }
            gmFacilityChoices = null;
        }
        if (el._choicesInstance) {
            try {
                el._choicesInstance.destroy();
            } catch (e) { /* noop */ }
            el._choicesInstance = null;
        }
        el.dataset.choicesInitialized = 'false';
    }

    function initGmFacilityChoices() {
        const el = document.getElementById('gmFacilityId');
        if (!el || typeof Choices === 'undefined') {
            return;
        }

        destroyGmFacilityChoices();

        const pendingValue = el.getAttribute('data-gm-pending-value');
        gmFacilityChoices = new Choices(el, gmFacilityChoiceOpts);
        el._choicesInstance = gmFacilityChoices;
        el.dataset.choicesInitialized = 'true';

        if (pendingValue !== null && pendingValue !== '') {
            gmFacilityChoices.setChoiceByValue(String(pendingValue));
        } else {
            gmFacilityChoices.setChoiceByValue(el.value ? String(el.value) : '');
        }

        el.removeAttribute('data-gm-pending-value');
    }

    function gmEnsureFacilityOption(value, label) {
        const el = document.getElementById('gmFacilityId');
        if (!value || !el) {
            return;
        }
        const val = String(value);
        const exists = Array.from(el.options).some(function(opt) {
            return String(opt.value) === val;
        });
        if (!exists) {
            const option = new Option(label || val, val, false, false);
            option.className = 'gm-extra-option';
            el.add(option);
        }
        if (gmFacilityChoices) {
            gmFacilityChoices.setChoices(
                [{ value: val, label: label || val, selected: false }],
                'value',
                'label',
                false
            );
        }
    }

    function gmSetFacilityValue(value) {
        const el = document.getElementById('gmFacilityId');
        if (!el) {
            return;
        }
        const val = value ? String(value) : '';
        el.value = val;
        el.setAttribute('data-gm-pending-value', val);
        if (gmFacilityChoices) {
            gmFacilityChoices.setChoiceByValue(val);
        }
    }

    function gmEnsureSelectOption($select, value, label) {
        if (!value) {
            return;
        }
        const val = String(value);
        const exists = $select.find('option').filter(function() {
            return String($(this).val()) === val;
        }).length > 0;
        if (!exists) {
            $select.append(
                $('<option>', { value: val, text: label || val, class: 'gm-extra-option' })
            );
        }
    }

    function resetGmGroupMappingForm() {
        destroyGmFacilityChoices();
        const form = document.getElementById('classSessionForm');
        if (form) {
            form.reset();
        }
        const facilityEl = document.getElementById('gmFacilityId');
        if (facilityEl) {
            facilityEl.removeAttribute('data-gm-pending-value');
        }
        $('#gmMappingPk').val('');
        $('#gmAddGroupMappingModalLabel').text('Add Group Mapping');
        $('#saveClassSessionForm').text('Create Group Mapping');
        $('#gmCourseId option.gm-extra-option, #gmTypeId option.gm-extra-option, #gmFacilityId option.gm-extra-option').remove();
        $('#gmAddGroupMappingAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
        $('#saveClassSessionForm').prop('disabled', false);
    }

    function getGmGroupMappingSubmitText() {
        return $('#gmMappingPk').val() ? 'Update Group Mapping' : 'Create Group Mapping';
    }

    function openGmGroupMappingModalForEdit($btn) {
        gmGroupMappingModalIsEdit = true;
        resetGmGroupMappingForm();

        const editData = {
            id: $btn.data('id') || '',
            courseId: String($btn.data('course-id') || ''),
            courseName: $btn.data('course-name') || '',
            typeId: String($btn.data('type-id') || ''),
            typeName: $btn.data('type-name') || '',
            groupName: $btn.data('group-name') || '',
            facilityId: String($btn.data('facility-id') || ''),
            facultyName: $btn.data('faculty-name') || ''
        };

        $('#gmMappingPk').val(editData.id);
        $('#gmAddGroupMappingModalLabel').text('Edit Group Mapping');
        $('#saveClassSessionForm').text('Update Group Mapping');

        gmEnsureSelectOption($('#gmCourseId'), editData.courseId, editData.courseName);
        gmEnsureSelectOption($('#gmTypeId'), editData.typeId, editData.typeName);
        $('#gmCourseId').val(editData.courseId);
        $('#gmTypeId').val(editData.typeId);
        $('#gmGroupName').val(editData.groupName);
        if (editData.facilityId) {
            gmEnsureFacilityOption(editData.facilityId, editData.facultyName);
        }
        gmSetFacilityValue(editData.facilityId || '');

        if (gmAddGroupMappingModal) {
            gmAddGroupMappingModal.show();
            setTimeout(function() {
                $('#gmGroupName').trigger('focus');
            }, 300);
        }
    }

    if (gmAddGroupMappingModalEl) {
        gmAddGroupMappingModalEl.addEventListener('show.bs.modal', function() {
            if (!gmGroupMappingModalIsEdit) {
                resetGmGroupMappingForm();
            }
            gmGroupMappingModalIsEdit = false;
        });

        gmAddGroupMappingModalEl.addEventListener('shown.bs.modal', function() {
            initGmFacilityChoices();
        });

        gmAddGroupMappingModalEl.addEventListener('hidden.bs.modal', function() {
            resetGmGroupMappingForm();
        });
    }

    $(document).on('click', '#group-mapping-table .gm-edit-btn', function(e) {
        e.preventDefault();
        openGmGroupMappingModalForEdit($(this));
    });

    $('#classSessionForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('#saveClassSessionForm');
        const alertBox = $('#gmAddGroupMappingAlert');
        const defaultBtnText = getGmGroupMappingSubmitText();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        alertBox.addClass('d-none');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                const isEdit = !!$('#gmMappingPk').val();
                const fallbackMsg = isEdit
                    ? 'Group Mapping updated successfully.'
                    : 'Group Mapping created successfully.';
                const msg = (response && response.message) ? response.message : fallbackMsg;
                alertBox.removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .html('<i class="bi bi-check-circle me-1"></i>' + msg);

                resetGmGroupMappingForm();
                $('#group-mapping-table').DataTable().ajax.reload();

                setTimeout(function() {
                    bootstrap.Modal.getInstance(gmAddGroupMappingModalEl)?.hide();
                    alertBox.addClass('d-none');
                }, 1200);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while saving the group mapping.';
                if (xhr.status === 422 && xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alertBox.removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .html('<i class="bi bi-exclamation-circle me-1"></i>' + errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(defaultBtnText);
            }
        });
    });

    $('#addStudentForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const alertBox = $('#addStudentAlert');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Adding...');
        alertBox.addClass('d-none');

        $.ajax({
            url: '{{ route("group.mapping.add.single.student") }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    alertBox.removeClass('d-none alert-danger')
                        .addClass('alert-success')
                        .html('<i class="bi bi-check-circle me-1"></i>' + response.message);

                    form[0].reset();
                    $('#group-mapping-table').DataTable().ajax.reload();

                    setTimeout(function() {
                        bootstrap.Modal.getInstance(document.getElementById('addStudentModal'))?.hide();
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
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                }
                alertBox.removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .html('<i class="bi bi-exclamation-circle me-1"></i>' + errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add Student');
            }
        });
    });
});
</script>
@endpush
