@extends('admin.layouts.master')

@section('title', 'Course Group Mapping')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Course Group Mapping">
        {{-- Add Students Dropdown --}}
        <div class="dropdown d-inline-block">
            <button
                class="btn btn-sm btn-outline-primary add-students-dropdown-toggle d-inline-flex align-items-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                <span>Add Students</span>
                <i class="material-icons material-symbols-rounded fs-6 lh-1 add-students-dropdown-chevron"
                    aria-hidden="true">arrow_drop_down</i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-1 py-2 px-3" href="#" data-bs-toggle="modal"
                        data-bs-target="#addStudentModal">
                        <i class="material-icons material-symbols-rounded fs-6 text-muted"
                            aria-hidden="true">person_add</i>
                        <span>Add Single</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-1 py-2 px-3" href="#" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        <i class="material-icons material-symbols-rounded fs-6 text-muted"
                            aria-hidden="true">group_add</i>
                        <span>Add in Bulk</span>
                    </a>
                </li>
            </ul>
        </div>
        {{-- Add Group Mapping --}}
        <a href="{{ route('group.mapping.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            data-bs-toggle="modal" data-bs-target="#addGroupMappingModal" onclick="event.preventDefault();">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Group Mapping</span>
        </a>
    </x-breadcrum>
    <x-session_message />
    {{-- Status Toggle & Download --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-inline-flex align-items-center gap-0 p-1 rounded-1" style="background:#f0f1f3;" role="group" aria-label="Group Mapping Status Filter">
            <button type="button" class="btn btn-sm px-4 py-1 fw-semibold rounded-1 border-0 active-tab"
                id="filterGroupActive" aria-pressed="true" style="background-color:#1b3a5c;color:#fff;transition:all .2s ease;">
                Active
            </button>
            <button type="button" class="btn btn-sm px-4 py-1 fw-semibold rounded-1 border-0 bg-transparent text-secondary"
                id="filterGroupArchive" aria-pressed="false" style="transition:all .2s ease;">
                Archived
            </button>
        </div>
        <div class="dropdown d-inline-block">
            <button type="button"
                class="btn btn-outline-primary btn-sm group-mapping-download-toggle d-inline-flex align-items-center gap-1 rounded-1 px-3 py-1 fw-semibold text-nowrap"
                id="groupMappingDownloadToggle" data-bs-toggle="dropdown" aria-expanded="false"
                aria-haspopup="true">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">download</i>
                <span>Download</span>
                <i class="material-icons material-symbols-rounded group-mapping-download-chevron" style="font-size:18px;"
                    aria-hidden="true">arrow_drop_down</i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2"
                aria-labelledby="groupMappingDownloadToggle">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-1 py-2 px-3" href="#" id="exportGroupMappingPdf">
                        <i class="material-icons material-symbols-rounded fs-6 text-muted"
                            aria-hidden="true">picture_as_pdf</i>
                        <span>PDF</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-1 py-2 px-3" href="#"
                        id="exportGroupMappingExcel">
                        <i class="material-icons material-symbols-rounded fs-6 text-muted"
                            aria-hidden="true">table_chart</i>
                        <span>Excel</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Filters --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <span class="fw-semibold text-body-secondary small text-uppercase ls-1">Filters</span>
        <select id="courseFilter" class="form-select form-select-sm rounded-1 shadow-none"
            style="width:auto;min-width:160px;border-color:#dee2e6;padding-right:2rem;">
            <option value="">Course Name</option>
            @foreach($courses ?? [] as $pk => $name)
            <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <select id="groupTypeFilter" class="form-select form-select-sm rounded-1 shadow-none"
            style="width:auto;min-width:140px;border-color:#dee2e6;padding-right:2rem;">
            <option value="">Group Type</option>
            @foreach($groupTypes ?? [] as $pk => $name)
            <option value="{{ $pk }}">{{ $name }}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-1 px-3 fw-semibold"
            id="resetFilters">
            Reset Filters
        </button>
        <div class="ms-auto" style="min-width:200px;max-width:300px;">
            <div class="input-group input-group-sm rounded-1 overflow-hidden" style="border:1px solid #dee2e6;">
                <span class="input-group-text bg-white border-0 ps-3 pe-0">
                    <i class="material-icons material-symbols-rounded text-muted" style="font-size:18px;"
                        aria-hidden="true">search</i>
                </span>
                <input type="text" class="form-control border-0 shadow-none bg-white" id="groupMappingSearch"
                    placeholder="Search" aria-label="Search">
            </div>
        </div>
    </div>

    <div>
        <div class="card border-0 shadow-sm rounded-3 group-mapping-card">
            <div class="card-body p-3 p-md-4">

                <!-- Add Student Modal -->
                <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel"
                    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <form id="addStudentForm">
                                @csrf
                                <div class="modal-header border-bottom pb-3">
                                    <h5 class="modal-title fw-bold" id="addStudentModalLabel">Add Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body py-4">
                                    <div id="addStudentAlert" class="alert d-none" role="alert"></div>

                                    <div class="mb-4">
                                        <label for="studentOtCode" class="form-label fw-semibold">OT Code<span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            id="studentOtCode" name="otcode" placeholder="OT1344" required
                                            maxlength="255">
                                    </div>

                                    <div class="mb-4">
                                        <label for="studentName" class="form-label fw-semibold">OT Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            id="studentName" name="name" placeholder="John Doe" required
                                            maxlength="255">
                                    </div>

                                    <div class="mb-4">
                                        <label for="studentCourse" class="form-label fw-semibold">Course Name<span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                            id="studentCourse" name="course_master_pk" required>
                                            <option value="">Select Course Name</option>
                                            @foreach($courses ?? [] as $pk => $name)
                                            <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>
                                                {{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="studentGroupType" class="form-label fw-semibold">Group Type<span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                            id="studentGroupType" name="group_type" required>
                                            <option value="">Select Group Type</option>
                                            @foreach($groupTypes ?? [] as $pk => $name)
                                            <option value="{{ $name }}" data-type-id="{{ $pk }}">{{ $name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Select a group type to filter available group
                                            names</div>
                                    </div>

                                    <div class="mb-0">
                                        <label for="studentGroupName" class="form-label fw-semibold">Group Name</label>
                                        <select class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                            id="studentGroupName" name="group_name" required disabled>
                                            <option value="">Select</option>
                                        </select>
                                        <div class="form-text" id="groupNameHelp">Please select a group type
                                            first</div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-1 px-4 fw-semibold">
                                        Create Group Mapping
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Add Student Modal -->

                <!-- Import Excel Modal -->
                <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
                    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">

                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                                @csrf

                                <div class="modal-header border-bottom pb-3">
                                    <h5 class="modal-title fw-bold" id="importModalLabel">Add in Bulk</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body py-4">
                                    {{-- Progress bar --}}
                                    <div class="d-flex align-items-center gap-1 mb-4">
                                        <div class="progress flex-grow-1" role="progressbar" style="height: 8px;"
                                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar" id="importProgressBar" style="width: 50%;"></div>
                                        </div>
                                        <span class="text-muted small fw-semibold" id="importProgressText">50%</span>
                                    </div>

                                    {{-- Step 1: File Upload --}}
                                    <div id="importStep1">
                                        <div class="border border-2 border-dashed rounded-3 p-5 text-center position-relative"
                                            id="dropZone">
                                            <i class="material-icons material-symbols-rounded text-muted mb-2"
                                                style="font-size: 48px;" aria-hidden="true">description</i>
                                            <p class="mb-1 fw-semibold">Drag or click here to upload your file</p>
                                            <p class="text-muted small mb-0">
                                                Allowed: .xlsx, .xls, .csv | Max ~500 MB |
                                                <a href="{{ asset('admin_assets/sample/group_mapping_sample.xlsx') }}"
                                                    class="text-primary fw-semibold" download>Sample File</a>
                                            </p>
                                            <input type="file" name="file" id="importFile"
                                                class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                                style="cursor: pointer;" accept=".xlsx, .xls, .csv" required>
                                        </div>
                                        <div id="selectedFileName" class="text-success small mt-2 d-none">
                                            <i class="material-icons material-symbols-rounded fs-6 align-middle"
                                                aria-hidden="true">check_circle</i>
                                            <span id="fileNameDisplay"></span>
                                        </div>
                                    </div>

                                    {{-- Step 2: Course Selection --}}
                                    <div id="importStep2" class="d-none">
                                        <div
                                            class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-1 mb-4">
                                            <i class="material-icons material-symbols-rounded fs-5"
                                                aria-hidden="true">info</i>
                                            <span id="uploadInfoText">File uploaded successfully. Select a course to
                                                assign.</span>
                                        </div>
                                        <div class="mb-0">
                                            <label for="course_master_pk_model" class="form-label fw-semibold">Course
                                                Name<span class="text-danger">*</span></label>
                                            <select name="course_master_pk" id="course_master_pk_model"
                                                class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                                required>
                                                <option value="">Select</option>
                                                @foreach($courses ?? [] as $pk => $name)
                                                <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>
                                                    {{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary rounded-1 px-4 fw-semibold"
                                        id="importNextBtn">Next</button>
                                    <button type="button" class="btn btn-primary rounded-1 px-4 fw-semibold d-none"
                                        id="upload_import">Add Course Group Mapping</button>
                                </div>
                            </form>

                            <div id="importErrors" class="alert alert-danger border-0 rounded-3 m-3 d-none shadow-sm">
                                <h6 class="fw-semibold mb-3 d-flex align-items-center gap-1">
                                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
                                    Validation errors found
                                </h6>
                                <div class="table-responsive rounded-2 border bg-white">
                                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="text-nowrap" style="width: 10%;">Row</th>
                                                <th scope="col">Errors</th>
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
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg rounded-4">

                            <div class="modal-header border-bottom pb-3">
                                <h5 class="modal-title fw-bold" id="studentDetailsModalLabel">
                                    <i class="bi bi-person-vcard me-2 text-primary" aria-hidden="true"></i> Student
                                    Details
                                </h5>
                                <button type="button" class="btn-close" aria-label="Close"
                                    data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body pt-3">

                                <div class="mb-3">
                                    <label for="studentSearchInput" class="visually-hidden">Search students</label>
                                    <div
                                        class="input-group input-group-lg rounded-1 overflow-hidden border shadow-sm">
                                        <span class="input-group-text bg-white border-0 ps-3">
                                            <i class="material-icons material-symbols-rounded"
                                                aria-hidden="true">search</i>
                                        </span>
                                        <input type="text" class="form-control border-0 shadow-none"
                                            id="studentSearchInput"
                                            placeholder="Search students by name, OT code, email, or contact number..."
                                            autocomplete="off">
                                        <button class="btn btn-outline-secondary border-0 pe-3" type="button"
                                            id="clearStudentSearch" style="display: none;" aria-label="Clear search">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="form-text mt-1">
                                        <span id="studentSearchResultsCount"></span>
                                    </div>
                                </div>

                                <div id="studentDetailsContent" class="rounded-3 bg-light border p-3">
                                    <p class="text-muted mb-0">Loading student details...</p>
                                </div>

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

                                            <div class="mb-3">
                                                <label for="bulkMessageText" class="form-label fw-semibold">
                                                    Message <span class="text-danger">*</span>
                                                </label>

                                                <textarea id="bulkMessageText" rows="4" maxlength="1000"
                                                    class="form-control rounded-3 shadow-sm"
                                                    aria-describedby="bulkMessageCharHelp"
                                                    placeholder="Type your message here..."></textarea>

                                                <div id="bulkMessageCharHelp" class="form-text text-end">
                                                    <span id="bulkMessageCharCount">0</span>/1000 characters
                                                </div>
                                            </div>

                                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                                <button type="button" class="btn btn-outline-success send-bulk-message"
                                                    data-channel="sms">
                                                    <i class="bi bi-chat-text me-1" aria-hidden="true"></i> Send SMS
                                                </button>
                                                <button type="button" class="btn btn-primary send-bulk-message"
                                                    data-channel="email">
                                                    <i class="bi bi-envelope-paper-heart me-1" aria-hidden="true"></i>
                                                    Send Email
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="modal-footer border-0 justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-muted small" id="selectedOtCount">0 OT(s) selected</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary rounded-1 px-3"
                                        id="toggleBulkMessage">
                                        <i class="bi bi-send-check me-1" aria-hidden="true"></i> Send SMS / Send Email
                                    </button>
                                    <button type="button" class="btn btn-secondary rounded-1 px-3"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Student Modal -->
                <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-bottom pb-3">
                                <h5 class="modal-title fw-bold" id="editStudentModalLabel">Edit Student</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form id="editStudentForm">
                                @csrf
                                <div class="modal-body py-4">
                                    <div id="editStudentAlert" class="alert d-none" role="alert"></div>
                                    <input type="hidden" name="student_id" id="editStudentId">
                                    <div class="mb-4">
                                        <label for="editStudentName" class="form-label fw-semibold">Display Name</label>
                                        <input type="text"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            id="editStudentName" name="display_name" required maxlength="255">
                                    </div>
                                    <div class="mb-4">
                                        <label for="editStudentEmail" class="form-label fw-semibold">Email</label>
                                        <input type="email"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            id="editStudentEmail" name="email" maxlength="255">
                                    </div>
                                    <div class="mb-0">
                                        <label for="editStudentContact" class="form-label fw-semibold">Contact
                                            No</label>
                                        <input type="text"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            id="editStudentContact" name="contact_no" maxlength="20">
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-1 px-4 fw-semibold">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Group Mapping Modal -->
                <div class="modal fade" id="addGroupMappingModal" tabindex="-1"
                    aria-labelledby="addGroupMappingModalLabel" aria-hidden="true" data-bs-backdrop="static"
                    data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <form action="{{ route('group.mapping.store') }}" method="POST" id="addGroupMappingForm">
                                @csrf
                                <div class="modal-header border-bottom pb-3">
                                    <h5 class="modal-title fw-bold" id="addGroupMappingModalLabel">Add Group Mapping
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body py-4">
                                    <div class="mb-4">
                                        <label for="gmCourseName" class="form-label fw-semibold">Course Name<span
                                                class="text-danger">*</span></label>
                                        <select name="course_id" id="gmCourseName"
                                            class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                            required>
                                            <option value="">Select Course Name</option>
                                            @foreach($courses ?? [] as $pk => $name)
                                            <option value="{{ $pk }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label for="gmGroupType" class="form-label fw-semibold">Group Type<span
                                                class="text-danger">*</span></label>
                                        <select name="type_id" id="gmGroupType"
                                            class="form-select form-select-lg border-0 border-bottom rounded-0 px-0"
                                            required>
                                            <option value="">Select Group Type</option>
                                            @foreach($courseGroupTypeMaster ?? $groupTypes ?? [] as $pk => $name)
                                            <option value="{{ $pk }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label for="gmGroupName" class="form-label fw-semibold">Group Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="group_name" id="gmGroupName"
                                            class="form-control form-control-lg border-0 border-bottom rounded-0 px-0"
                                            placeholder="eg. IAS Course" required maxlength="255">
                                    </div>
                                    <div class="mb-0">
                                        <label for="gmFaculty" class="form-label fw-semibold">Faculty</label>
                                        <select name="facility_id" id="gmFaculty"
                                            class="form-select form-select-lg border-0 border-bottom rounded-0 px-0">
                                            <option value="">Select</option>
                                            @foreach($facilities ?? [] as $pk => $name)
                                            <option value="{{ $pk }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-1 px-4 fw-semibold">
                                        Create Group Mapping
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Add Group Mapping Modal -->

                {{-- DataTable --}}
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0 custom-mapping-table', 'style' => 'width:100%']) !!}
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Add Students: chevron visible on hover/focus/open */
    .add-students-dropdown-toggle .add-students-dropdown-chevron {
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .add-students-dropdown-toggle:hover .add-students-dropdown-chevron,
    .add-students-dropdown-toggle:focus-visible .add-students-dropdown-chevron,
    .add-students-dropdown-toggle[aria-expanded="true"] .add-students-dropdown-chevron {
        opacity: 1;
    }

    /* Active/Archived pill toggle */
    #filterGroupActive.active-tab {
        background-color: #1b3a5c !important;
        color: #fff !important;
    }
    #filterGroupArchive.active-tab {
        background-color: #1b3a5c !important;
        color: #fff !important;
    }
    #filterGroupActive:not(.active-tab),
    #filterGroupArchive:not(.active-tab) {
        background: transparent !important;
        color: #6c757d !important;
    }

    /* DataTable pagination */
    #group-mapping-table_wrapper .dataTables_paginate {
        display: flex;
        align-items: center;
        gap: 2px;
    }
    #group-mapping-table_wrapper .dataTables_paginate .paginate_button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 2px 8px;
        border: 1px solid transparent;
        border-radius: 6px;
        background: transparent;
        color: #495057 !important;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none !important;
        transition: all .15s ease;
    }
    #group-mapping-table_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
        background: #f0f1f3;
        color: #1b3a5c !important;
    }
    #group-mapping-table_wrapper .dataTables_paginate .paginate_button.current {
        border: 1.5px solid #1b3a5c !important;
        background: transparent !important;
        color: #1b3a5c !important;
        font-weight: 600;
    }
    #group-mapping-table_wrapper .dataTables_paginate .paginate_button.disabled {
        color: #ced4da !important;
        cursor: default;
        pointer-events: none;
    }
    #group-mapping-table_wrapper .dataTables_paginate .ellipsis {
        padding: 2px 4px;
        color: #adb5bd;
        font-size: 13px;
    }

    /* DataTable info & length */
    #group-mapping-table_wrapper .dataTables_info {
        font-size: 13px;
        color: #6c757d;
        white-space: nowrap;
    }
    #group-mapping-table_wrapper .dataTables_length {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 13px;
        color: #6c757d;
        white-space: nowrap;
    }
    #group-mapping-table_wrapper .dataTables_length select {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 2px 20px 2px 6px;
        font-size: 13px;
        color: #333;
        background: #fff;
        cursor: pointer;
    }

    /* Table clean styling */
    .custom-mapping-table {
        border-collapse: collapse;
    }
    .custom-mapping-table thead th {
        border-top: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        font-weight: 600;
        color: #495057;
        font-size: 13px;
        white-space: nowrap;
        padding: 12px 10px;
        background: #fff;
    }
    .custom-mapping-table tbody td {
        border-top: none !important;
        border-bottom: 1px solid #f0f1f3 !important;
        font-size: 13.5px;
        color: #212529;
        padding: 12px 10px;
        vertical-align: middle;
    }
    .custom-mapping-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .custom-mapping-table tbody tr:last-child td {
        border-bottom: none !important;
    }

    /* Toggle switch in action column */
    .custom-mapping-table .form-check.form-switch {
        margin-bottom: 0;
        min-height: auto;
    }

    /* Dashed border for drop zone */
    .border-dashed {
        border-style: dashed !important;
    }
    #dropZone.drag-over {
        background-color: #e8f4fd;
        border-color: #0d6efd !important;
    }

    /* Underline-style inputs in modals */
    .form-control.border-bottom,
    .form-select.border-bottom {
        border-bottom: 2px solid #dee2e6 !important;
        background-color: transparent;
    }
    .form-control.border-bottom:focus,
    .form-select.border-bottom:focus {
        border-bottom-color: #0d6efd !important;
        box-shadow: none;
    }
    </style>
    @endsection
    @push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    var activeCourses = @json($courses ?? []);
    var archivedCourses = @json($archivedCourses ?? []);

    function updateCourseFilter(status) {
        var courses = (status === 'archive') ? archivedCourses : activeCourses;
        var $filter = $('#courseFilter');
        $filter.html('<option value="">Course Name</option>');
        $.each(courses, function(pk, name) {
            $filter.append($('<option>', { value: pk, text: name }));
        });
    }

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

            // Search input for DataTable
            $('#groupMappingSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#filterGroupActive').on('click', function() {
                setActiveButton($(this));
                window.groupMappingCurrentFilter = 'active';
                updateCourseFilter('active');
                table.ajax.reload();
            });

            $('#filterGroupArchive').on('click', function() {
                setActiveButton($(this));
                window.groupMappingCurrentFilter = 'archive';
                updateCourseFilter('archive');
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
                updateCourseFilter('active');
                table.ajax.reload();
            });

            function setActiveButton(activeBtn) {
                // Reset both buttons
                $('#filterGroupActive')
                    .removeClass('active-tab')
                    .css({
                        'background-color': 'transparent',
                        'color': '#6c757d'
                    })
                    .attr('aria-pressed', 'false');

                $('#filterGroupArchive')
                    .removeClass('active-tab')
                    .css({
                        'background-color': 'transparent',
                        'color': '#6c757d'
                    })
                    .attr('aria-pressed', 'false');

                // Set the active button
                activeBtn.addClass('active-tab')
                    .css({
                        'background-color': '#1b3a5c',
                        'color': '#fff'
                    })
                    .attr('aria-pressed', 'true');
            }

            $('#exportGroupMappingPdf').on('click', function(e) {
                e.preventDefault();
                table.button('.buttons-pdf').trigger();
            });
            $('#exportGroupMappingExcel').on('click', function(e) {
                e.preventDefault();
                table.button('.buttons-excel').trigger();
            });

        }, 150);

        // Import Modal - Two-step wizard
        var importStep = 1;

        $('#importNextBtn').on('click', function() {
            var fileInput = $('#importFile')[0];
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a file to upload.');
                return;
            }
            // Move to step 2
            importStep = 2;
            $('#importStep1').addClass('d-none');
            $('#importStep2').removeClass('d-none');
            $('#importProgressBar').css('width', '100%');
            $('#importProgressText').text('100%');
            $('#importNextBtn').addClass('d-none');
            $('#upload_import').removeClass('d-none');
            $('#uploadInfoText').text('You uploaded ' + fileInput.files[0].name +
                ' to assign new course.');
        });

        // Reset import modal on close
        $('#importModal').on('hidden.bs.modal', function() {
            importStep = 1;
            $('#importStep1').removeClass('d-none');
            $('#importStep2').addClass('d-none');
            $('#importProgressBar').css('width', '50%');
            $('#importProgressText').text('50%');
            $('#importNextBtn').removeClass('d-none');
            $('#upload_import').addClass('d-none');
            $('#importExcelForm')[0].reset();
            $('#selectedFileName').addClass('d-none');
            $('#importErrors').addClass('d-none');
        });

        // Show file name when selected
        $('#importFile').on('change', function() {
            if (this.files && this.files.length > 0) {
                $('#fileNameDisplay').text(this.files[0].name);
                $('#selectedFileName').removeClass('d-none');
            } else {
                $('#selectedFileName').addClass('d-none');
            }
        });

        // Drag and drop support
        var dropZone = document.getElementById('dropZone');
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(function(eventName) {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.add('drag-over');
                });
            });
            ['dragleave', 'drop'].forEach(function(eventName) {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.remove('drag-over');
                });
            });
            dropZone.addEventListener('drop', function(e) {
                var files = e.dataTransfer.files;
                if (files.length > 0) {
                    $('#importFile')[0].files = files;
                    $('#importFile').trigger('change');
                }
            });
        }

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
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...'
                );
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
                    submitBtn.prop('disabled', false).html('Create Group Mapping');
                }
            });
        });

    });
    </script>
    @endpush