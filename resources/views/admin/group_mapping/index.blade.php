@extends('admin.layouts.master')

@section('title', 'Course Group Mapping - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')

<div class="container-fluid">

    <x-breadcrum title="Course Group Mapping" />
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
    </div>

    <div class="card gm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
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
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>
                <div id="gmDtSearch" class="programme-dt-search ms-xl-auto" data-dt-search-for="group-mapping-table"></div>
            </div>

            <div class="programme-dt-panel gm-dt-panel">
                <div class="table-responsive gm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="gmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="group-mapping-table"></div>
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
                    <button type="button" class="btn btn-outline-primary rounded-3 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="saveClassSessionForm">
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
                        <input type="text" class="form-control rounded-2" id="studentOtCode" name="otcode"
                            placeholder="eg. OT1344" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="studentName" class="form-label cgt-field-label">OT Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-2" id="studentName" name="name"
                            placeholder="eg. John Doe" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="studentCourse" class="form-label cgt-field-label">Course Name <span class="text-danger">*</span></label>
                        <select class="form-select rounded-2" id="studentCourse" name="course_master_pk" required>
                            <option value="">Select Course Name</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="studentGroupType" class="form-label cgt-field-label">Group Type <span class="text-danger">*</span></label>
                        <select class="form-select rounded-2" id="studentGroupType" name="group_type" required>
                            <option value="">Select Group Type</option>
                            @foreach($groupTypes ?? [] as $pk => $name)
                            <option value="{{ $name }}" data-type-id="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="studentGroupName" class="form-label cgt-field-label">Group Name</label>
                        <select class="form-select rounded-2" id="studentGroupName" name="group_name" required disabled>
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
                        <div class="progress rounded-pill" style="height: 6px;" role="progressbar"
                            aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                            <div class="progress-bar bg-primary rounded-pill" id="gmImportProgress" style="width: 50%;"></div>
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
                            <select name="course_master_pk" id="course_master_pk_model" class="form-select rounded-2" required>
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
                    <div class="row mb-3">
                        <div class="row align-items-center mb-4">

                            <div class="col-12 col-md-4">
                                <h4 class="fw-bold mb-3 mb-md-0">Course Group Mapping</h4>
                            </div>

                            <div class="col-12 col-md-8 d-flex justify-content-md-end flex-wrap gap-2">

                                <a href="{{ route('group.mapping.create') }}"
                                    class="btn btn-primary px-3 d-flex align-items-center shadow-sm">
                                    <i class="bi bi-plus-circle-fill me-2" aria-hidden="true"></i>
                                    Add Group Mapping
                                </a>

                                <button type="button" class="btn btn-info px-3 d-flex align-items-center shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <iconify-icon icon="mdi:account-plus" width="1.2em" height="1.2em" class="me-2">
                                    </iconify-icon>
                                    Add Student
                                </button>

                                <button type="button" class="btn btn-success px-3 d-flex align-items-center shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#importModal">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em" class="me-2">
                                    </iconify-icon>
                                    Import Excel
                                </button>

                                <a href="{{ route('group.mapping.export.student.list') }}"
                                    class="btn btn-outline-primary px-3 d-flex align-items-center shadow-sm">
                                    <iconify-icon icon="material-symbols:sim-card-download-rounded" width="1.4em"
                                        height="1.4em" class="me-2"></iconify-icon>
                                    Export Excel
                                </a>
                            </div>
                        </div>

                        {{-- Status Filter --}}
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="courseFilter" class="form-label fw-semibold">Course Name</label>
                                <select id="courseFilter" class="form-select shadow-sm">
                                    <option value="">All Courses</option>
                                    @foreach($courses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="groupTypeFilter" class="form-label fw-semibold">Group Type</label>
                                <select id="groupTypeFilter" class="form-select shadow-sm">
                                    <option value="">All Group Types</option>
                                    @foreach($groupTypes ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary px-4 py-2 mt-lg-4 shadow-sm btn-sm"
                                    id="resetFilters">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                                </button>
                            </div>
                            <div class="col-3 text-end">
                                <div class="btn-group shadow-sm rounded-pill" role="group"
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

                        {{-- Filters --}}
                        <div class="row g-3 mb-4 align-items-end">


                        </div>


                        <!-- Add Student Modal -->
                        <div class="modal fade" id="addStudentModal" tabindex="-1"
                            aria-labelledby="addStudentModalLabel" aria-hidden="true" data-bs-backdrop="static"
                            data-bs-keyboard="false">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form id="addStudentForm">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addStudentModalLabel">Add Student to Group</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
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
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="mdi mdi-content-save"></i> Add Student
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Add Student Modal -->

                        <!-- Import Excel Modal -->
                        <div class="modal fade modal-xl" id="importModal" tabindex="-1"
                            aria-labelledby="importModalLabel" aria-hidden="true" data-bs-backdrop="static"
                            data-bs-keyboard="false">

                            <div class="modal-dialog">
                                <div class="modal-content"> 
                                    <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                                        @csrf

                                        <!-- Modal Header -->
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="importModalLabel">Import Excel File</h5>
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
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                                            <button type="button" class="btn btn-success" id="upload_import">
                                                <i class="mdi mdi-upload"></i> Upload & Import
                                            </button>

                                            <a href="{{ asset('admin_assets/sample/group_mapping_sample.xlsx') }}"
                                                class="btn btn-info" download>
                                                <i class="mdi mdi-download"></i> Download Sample
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
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
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
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <hr>
                        {!! $dataTable->table(['class' => 'table text-nowrap']) !!}
                    </div>
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
                        <input type="text" class="form-control rounded-2" id="editStudentName"
                            name="display_name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="editStudentEmail" class="form-label cgt-field-label">Email</label>
                        <input type="email" class="form-control rounded-2" id="editStudentEmail"
                            name="email" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label for="editStudentContact" class="form-label cgt-field-label">Contact No</label>
                        <input type="text" class="form-control rounded-2" id="editStudentContact"
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
    if (courseFilter) {
        data.course_filter = courseFilter;
    }
    if (groupTypeFilter) {
        data.group_type_filter = groupTypeFilter;
    }
});

$(document).ready(function() {
    window.groupMappingCurrentFilter = 'active';

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

    setTimeout(function() {
        if (!$.fn.DataTable.isDataTable('#group-mapping-table')) {
            return;
        }

        var table = $('#group-mapping-table').DataTable();

        setActiveFilterButton($('#filterGroupActive'));

        $('#filterGroupActive').on('click', function() {
            setActiveFilterButton($(this));
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
                            '<i class="mdi mdi-content-save"></i> Add Student');
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