@extends('admin.layouts.master')

@section('title', 'Course Group Mapping')

@section('setup_content')

<div class="container-fluid py-3">

    <x-breadcrum title="Course Group Mapping" />
    <x-session_message />

    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 group-mapping-card" style="border-left: 4px solid #004a93 !important;">
            <div class="card-body p-3 p-md-4">
                    {{-- Toolbar --}}
                    <div class="row align-items-start align-items-md-center g-3 mb-4">
                        <div class="col-12 col-lg-4">
                            <h4 class="fw-bold mb-1">Course Group Mapping</h4>
                            <p class="text-muted small mb-0">Manage course groups, students, and imports.</p>
                        </div>

                        <div class="col-12 col-lg-8">
                            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                <a href="{{ route('group.mapping.create') }}"
                                    class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 shadow-sm">
                                    <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
                                    <span>Add Group Mapping</span>
                                </a>

                                <button type="button" class="btn btn-info text-white d-inline-flex align-items-center gap-2 px-3 shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <iconify-icon icon="mdi:account-plus" width="1.2em" height="1.2em" aria-hidden="true">
                                    </iconify-icon>
                                    <span>Add Student</span>
                                </button>

                                <button type="button" class="btn btn-success d-inline-flex align-items-center gap-2 px-3 shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#importModal">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em" aria-hidden="true">
                                    </iconify-icon>
                                    <span>Import Excel</span>
                                </button>

                                <a href="{{ route('group.mapping.export.student.list') }}"
                                    class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 shadow-sm">
                                    <iconify-icon icon="material-symbols:sim-card-download-rounded" width="1.4em"
                                        height="1.4em" aria-hidden="true"></iconify-icon>
                                    <span>Export Excel</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="rounded-3 border bg-light p-3 p-md-4 mb-4">
                        <div class="row g-3 g-lg-4 align-items-end">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label for="courseFilter" class="form-label fw-semibold mb-1">Course Name</label>
                                <select id="courseFilter" class="form-select shadow-sm">
                                    <option value="">All Courses</option>
                                    @foreach($courses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="groupTypeFilter" class="form-label fw-semibold mb-1">Group Type</label>
                                <select id="groupTypeFilter" class="form-select shadow-sm">
                                    <option value="">All Group Types</option>
                                    @foreach($groupTypes ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-sm-auto d-flex flex-wrap align-items-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm"
                                    id="resetFilters">
                                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset Filters
                                </button>
                            </div>

                            <div class="col-12 col-lg-auto ms-lg-auto">
                                <span class="form-label fw-semibold d-block mb-1">Status</span>
                                <div class="btn-group shadow-sm" role="group"
                                    aria-label="Group Mapping Status Filter">
                                    <button type="button" class="btn btn-outline-success px-3 py-2 fw-semibold"
                                        id="filterGroupActive" aria-pressed="false">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Active
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold"
                                        id="filterGroupArchive" aria-pressed="false">
                                        <i class="bi bi-archive me-1" aria-hidden="true"></i> Archive
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Student Modal -->
                    <div class="modal fade" id="addStudentModal" tabindex="-1"
                        aria-labelledby="addStudentModalLabel" aria-hidden="true" data-bs-backdrop="static"
                        data-bs-keyboard="false">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content rounded-3 border-0 shadow">
                                <form id="addStudentForm">
                                    @csrf
                                    <div class="modal-header border-0 pb-0">
                                        <div>
                                            <h5 class="modal-title fw-semibold" id="addStudentModalLabel">Add Student to Group</h5>
                                            <p class="text-muted small mb-0">Required fields are marked with <span class="text-danger">*</span></p>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body pt-3">
                                        <div id="addStudentAlert" class="alert d-none" role="alert"></div>

                                        <div class="mb-3">
                                            <label for="studentName" class="form-label fw-semibold">Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control shadow-sm" id="studentName" name="name"
                                                placeholder="Enter student name" required maxlength="255">
                                        </div>

                                        <div class="mb-3">
                                            <label for="studentOtCode" class="form-label fw-semibold">OT Code <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control shadow-sm" id="studentOtCode" name="otcode"
                                                placeholder="Enter OT code" required maxlength="255">
                                        </div>
                                        <div class="mb-3">
                                            <label for="studentCourse" class="form-label fw-semibold">Course <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select shadow-sm" id="studentCourse" name="course_master_pk" required>
                                                <option value="">Select Course</option>
                                                @foreach($courses ?? [] as $pk => $name)
                                                <option value="{{ $pk }}" {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="studentGroupType" class="form-label fw-semibold">Group Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select shadow-sm" id="studentGroupType" name="group_type"
                                                required>
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
                                            <label for="studentGroupName" class="form-label fw-semibold">Group Name <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select shadow-sm" id="studentGroupName" name="group_name"
                                                required disabled>
                                                <option value="">Select Group Name</option>
                                            </select>
                                            <div class="form-text" id="groupNameHelp">Please select a group type
                                                first</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 gap-2">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add Student
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

                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content rounded-3 border-0 shadow">
                                <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                                    @csrf

                                    <div class="modal-header border-0 pb-0">
                                        <div>
                                            <h5 class="modal-title fw-semibold" id="importModalLabel">Import Excel File</h5>
                                            <p class="text-muted small mb-0">Choose a course and upload a spreadsheet.</p>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body pt-3">

                                        <div class="mb-3">
                                            <label for="course_master_pk_model" class="form-label fw-semibold">Select Course</label>
                                           <select name="course_master_pk" id="course_master_pk_model" class="form-select shadow-sm" required>
                                                <option value="">Select Course</option>
                                               @foreach($courses ?? [] as $pk => $name)
                                                <option value="{{ $pk }}"  {{ count($courses) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-0">
                                            <label for="importFile" class="form-label fw-semibold">Select Excel File</label>
                                            <input type="file" name="file" id="importFile" class="form-control shadow-sm"
                                                accept=".xlsx, .xls, .csv" required>
                                            <div class="form-text">
                                                Allowed: .xlsx, .xls, .csv · Max ~500 MB
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-0 flex-wrap gap-2 pt-0">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>

                                        <button type="button" class="btn btn-success" id="upload_import">
                                            <i class="mdi mdi-upload"></i> Upload & Import
                                        </button>

                                        <a href="{{ asset('admin_assets/sample/group_mapping_sample.xlsx') }}"
                                            class="btn btn-info text-white ms-sm-auto" download>
                                            <i class="mdi mdi-download"></i> Download Sample
                                        </a>
                                    </div>
                                </form>

                                <div id="importErrors" class="alert alert-danger border-0 rounded-3 m-3 d-none shadow-sm">
                                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
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

                                <div class="modal-header border-0 pb-2">
                                    <h5 class="modal-title fw-semibold" id="studentDetailsModalLabel">
                                        <i class="bi bi-person-vcard me-2 text-primary" aria-hidden="true"></i> Student Details
                                    </h5>
                                    <button type="button" class="btn-close" aria-label="Close"
                                        data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body pt-0">

                                    <div class="mb-3">
                                        <label for="studentSearchInput" class="visually-hidden">Search students</label>
                                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-light border-0">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                                            </span>
                                            <input type="text" class="form-control border-0"
                                                id="studentSearchInput"
                                                placeholder="Search students by name, OT code, email, or contact number..."
                                                autocomplete="off">
                                            <button class="btn btn-outline-secondary border-0" type="button"
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

                                                    <div id="bulkMessageCharHelp"
                                                        class="form-text text-end">
                                                        <span id="bulkMessageCharCount">0</span>/1000 characters
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                                    <button type="button"
                                                        class="btn btn-outline-success send-bulk-message"
                                                        data-channel="sms">
                                                        <i class="bi bi-chat-text me-1" aria-hidden="true"></i> Send SMS
                                                    </button>
                                                    <button type="button" class="btn btn-primary send-bulk-message"
                                                        data-channel="email">
                                                        <i class="bi bi-envelope-paper-heart me-1" aria-hidden="true"></i> Send Email
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer border-0 justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="text-muted small" id="selectedOtCount">0 OT(s) selected</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-primary"
                                            id="toggleBulkMessage">
                                            <i class="bi bi-send-check me-1" aria-hidden="true"></i> Send SMS / Send Email
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
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content rounded-3 border-0 shadow">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-semibold" id="editStudentModalLabel">Edit Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form id="editStudentForm">
                                    @csrf
                                    <div class="modal-body pt-3">
                                        <div id="editStudentAlert" class="alert d-none" role="alert"></div>
                                        <input type="hidden" name="student_id" id="editStudentId">
                                        <div class="mb-3">
                                            <label for="editStudentName" class="form-label fw-semibold">Display Name</label>
                                            <input type="text" class="form-control shadow-sm" id="editStudentName"
                                                name="display_name" required maxlength="255">
                                        </div>
                                        <div class="mb-3">
                                            <label for="editStudentEmail" class="form-label fw-semibold">Email</label>
                                            <input type="email" class="form-control shadow-sm" id="editStudentEmail"
                                                name="email" maxlength="255">
                                        </div>
                                        <div class="mb-0">
                                            <label for="editStudentContact" class="form-label fw-semibold">Contact No</label>
                                            <input type="text" class="form-control shadow-sm" id="editStudentContact"
                                                name="contact_no" maxlength="20">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 gap-2">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-save me-1" aria-hidden="true"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    <hr class="my-4 text-muted opacity-25">

                    {{-- Only the DataTable region scrolls; toolbar, filters, and modals stay put --}}
                    <div class="group-mapping-table-scroll rounded-3 border bg-white w-100">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle text-nowrap mb-0']) !!}
                    </div>
            </div>
        </div>
    </div>

    <style>
        /* Scroll only the DataTable block; toolbar/filters on the card do not move with this scroll */
        .datatables .group-mapping-table-scroll {
            max-height: min(70vh, calc(100vh - 14rem));
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .datatables .group-mapping-table-scroll table.dataTable thead th,
        .datatables .group-mapping-table-scroll table.dataTable thead td,
        .datatables .group-mapping-table-scroll #group-mapping-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }
    </style>
    @endsection
    @push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
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
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...');
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
                            '<i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add Student');
                    }
                });
            });

        });
    </script>
    @endpush
