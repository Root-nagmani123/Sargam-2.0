@extends('admin.layouts.master')

@section('title', 'My Course Participant')

@section('setup_content')
    <div class="container-fluid py-4" style="background-color: #f8f9fa;">
        <div class="mb-4">
            <x-breadcrum title="My Course Participant" />
        </div>
        <x-session_message />

        @if (!empty($showFilters) && $showFilters)
        {{-- Filters (visible only to Super Admin & Training MCTP Admin) --}}
        <div class="card shadow-lg mb-4 border-0 animate-fade-in" style="border-left: 5px solid #11998e; border-radius: 15px;">
            <div class="card-header py-4" style="background: linear-gradient(135deg, #11998e 0%, #0066cc 100%); border-radius: 15px 15px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3">
                        <i class="fas fa-filter fa-lg text-white"></i>
                    </div>
                    <h5 class="mb-0 text-white fw-bold">Filters</h5>
                </div>
            </div>
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
                <form id="filterForm" method="GET">
                    <div class="row g-3">
                        <!-- Course Type -->
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
                                    value="inactive" {{ $courseStatus === 'inactive' ? 'checked' : '' }} autocomplete="off">
                                <label class="btn btn-outline-danger btn-lg custom-toggle-btn" for="course_status_inactive">
                                    <i class="fas fa-archive me-2"></i>Archived Courses
                                </label>
                            </div>
                        </div>

                        <!-- Filter by Course -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark" style="font-size: 0.9rem;">
                                <i class="fas fa-graduation-cap me-2 text-primary"></i>Filter by Course
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0" style="border: 2px solid #e0e0e0; border-right: none;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <select name="course_id" id="course_id" class="form-select form-select-lg shadow-sm custom-select" style="border-left: none;">
                                    <option value="">-- All Courses --</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}" {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Enrollment Status -->
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
        </div>
        @endif

        {{-- Counts + Import + Export --}}
        <div class="cp-panel mb-4 animate-fade-in">
            <div class="cp-panel-head">
                <div class="cp-panel-head-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="cp-panel-title mb-0">Course Participants</h5>
            </div>

            <div class="cp-toolbar">
                <!-- Total Records stat -->
                <div class="cp-stat">
                    <div class="cp-tile-icon cp-icon-blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="cp-tile-body">
                        <div class="cp-tile-label">Total Records</div>
                        <div class="cp-tile-value">
                            <span id="filteredCount" class="counter">{{ $filteredCount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="cp-actions">
                    <button type="button" class="btn cp-import-btn" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        <i class="fas fa-file-import me-2"></i>Import Data
                    </button>

                    <form method="GET" action="{{ route('my.course.participant.export') }}" id="exportForm" class="cp-export-group">
                        {{-- Mirror the active list filters so the export matches the table --}}
                        <input type="hidden" name="course_id" id="exportCourseId">
                        <input type="hidden" name="status" id="exportStatus">
                        <input type="hidden" name="search_term" id="exportSearchTerm">
                        <select name="format" class="form-select cp-select" required id="exportFormat">
                            <option value="">Choose export type…</option>
                            <option value="pdf">📄 PDF Document</option>
                            <option value="xlsx">📊 Excel Spreadsheet</option>
                            <option value="csv">📝 CSV File</option>
                        </select>
                        <button type="submit" class="btn cp-export-btn" id="exportBtn">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </form>
                </div>
            </div>
        </div> <!-- End of top panel -->

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="modal-title fw-bold" id="importModalLabel">
                            <i class="fas fa-file-import me-2"></i>Import OT Codes
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

        {{-- Data Table --}}
        <div class="card shadow-lg border-0 animate-fade-in">
            <div class="card-header py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon-box me-3">
                            <i class="fas fa-table fa-lg"></i>
                        </div>
                        <div>
                             <h5 class="mb-0 fw-bold">Course Wise OT Records</h5>
                            <small class="text-muted-50">View course participants</small>
                        </div>
                    </div>
                    <div class="cp-table-search">
                        <i class="fas fa-search cp-table-search-icon"></i>
                        <input type="text" id="participantSearch" class="form-control cp-search-input"
                            placeholder="Search name, OT code, email, mobile…" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 modern-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th class="fw-bold" style="padding: 1rem;">S.No</th>
                                <th class="fw-bold" style="padding: 1rem;">user_name</th>
                                <th class="fw-bold" style="padding: 1rem;">Name</th>
                                <th class="fw-bold" style="padding: 1rem;">ot code</th>
                                <th class="fw-bold" style="padding: 1rem;">email_id</th>
                                <th class="fw-bold" style="padding: 1rem;">mobile no</th>
                                <th class="fw-bold" style="padding: 1rem;">cadre</th>
                                <th class="fw-bold" style="padding: 1rem;">Participant group</th>
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

@push('scripts')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable - loads all participants automatically
        const dataTable = $('#studentsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            ordering: false,
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            ajax: {
                url: "{{ route('my.course.participant') }}",
                type: "GET",
                data: function(d) {
                    d.course_id = $('#course_id').val() || '';
                    d.status = $('#status').val() || '';
                    d.course_status = $('input[name="course_status"]:checked').val() || '';
                    d.search_term = $('#participantSearch').val() || '';
                },
                dataSrc: function(json) {
                    $('#filteredCount').text(json.recordsTotal || 0);
                    return json.data || [];
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                    console.log('Response:', xhr.responseText);
                    alert('Error loading data. Please check console for details.');
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '60px' },
                { data: 'user_name', name: 'user_name' },
                { data: 'name', name: 'name' },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'email_id', name: 'email_id' },
                { data: 'mobile_no', name: 'mobile_no' },
                { data: 'cadre', name: 'cadre' },
                { data: 'participant_group', name: 'participant_group' }
            ],
            columnDefs: [
                { targets: [0], className: 'text-center' }
            ],
            drawCallback: function(settings) {
                let api = this.api();
                $('#filteredCount').text(api.page.info().recordsTotal);
            }
        });

        // ----- Universal search (debounced) -----
        let searchTimer = null;
        $('#participantSearch').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                dataTable.ajax.reload();
            }, 400);
        });

        // ----- Filters (Super Admin & Training MCTP Admin only) -----
        // Reload table when course / status changes
        $('#course_id, #status').on('change', function() {
            dataTable.ajax.reload();
        });

        // When Active/Archived toggle changes, refresh the course dropdown then reload
        $('input[name="course_status"]').on('change', function() {
            const courseStatus = $(this).val();
            $.ajax({
                url: "{{ route('my.course.participant') }}",
                type: "GET",
                data: { course_status: courseStatus, ajax_courses: true },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    const courseSelect = $('#course_id');
                    courseSelect.empty().append('<option value="">-- All Courses --</option>');
                    $.each(response.courses, function(id, name) {
                        courseSelect.append(new Option(name, id));
                    });
                    courseSelect.val('');
                    dataTable.ajax.reload();
                },
                error: function(xhr) {
                    console.error('Course dropdown AJAX error:', xhr.responseText);
                }
            });
        });

        // Export form submission
        $('#exportForm').on('submit', function(e) {
            const format = $('#exportFormat').val();
            if (!format) {
                e.preventDefault();
                alert('Please select an export format');
                return false;
            }
            // Carry the active list filters into the export so counts match
            $('#exportCourseId').val($('#course_id').val() || '');
            $('#exportStatus').val($('#status').val() || '');
            $('#exportSearchTerm').val($('#participantSearch').val() || '');
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

            const fileName = fileInput.files[0].name;
            const validExtensions = /(\.xlsx|\.xls|\.csv)$/i;

            if (!validExtensions.exec(fileName)) {
                e.preventDefault();
                alert('Please upload only Excel or CSV files (.xlsx, .xls, .csv)');
                return false;
            }

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
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .animate-fade-in { animation: fadeIn 0.6s ease-out; }

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
    }

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
    }

    .btn-check:checked + .btn-outline-danger.custom-toggle-btn {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 8px 25px rgba(235, 51, 73, 0.4);
    }

    .input-group:focus-within .input-group-text {
        border-color: #667eea;
        background-color: #f0f4ff;
    }

    /* ===== Course Participants panel ===== */
    .cp-panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 6px 24px rgba(17, 38, 78, 0.06);
    }

    .cp-panel-head {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    .cp-panel-head-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 1.05rem;
    }

    .cp-panel-title { font-size: 1.15rem; font-weight: 700; color: #1f2a44; }

    /* Toolbar: stat on left, actions on right */
    .cp-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
    }

    .cp-stat {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1.5rem 0.85rem 0.85rem;
        background: #f7f9fc;
        border: 1px solid #eef0f4;
        border-radius: 14px;
        flex: 0 0 auto;
    }

    .cp-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .cp-export-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .cp-import-btn {
        border: 1.5px solid #e2e6f0;
        border-radius: 10px;
        padding: 0.65rem 1.25rem;
        font-weight: 700;
        color: #4f46e5;
        background: #eef2ff;
        white-space: nowrap;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .cp-import-btn:hover {
        color: #fff;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
    }

    .cp-tile-icon {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        font-size: 1.35rem;
        color: #fff;
    }

    .cp-icon-blue   { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
    .cp-icon-indigo { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
    .cp-icon-green  { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

    .cp-tile-body { min-width: 0; }
    .cp-tile-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: #8a93a6; margin-bottom: 0.1rem; }
    .cp-tile-value { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #1f2a44; }
    .counter { display: inline-block; transition: all 0.3s ease; }

    .cp-select {
        width: 210px;
        border: 1.5px solid #e2e6f0;
        border-radius: 10px;
        padding: 0.65rem 0.85rem;
        font-weight: 500;
        color: #1f2a44;
        transition: border-color .25s ease, box-shadow .25s ease;
    }
    .cp-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
    }

    .cp-export-btn {
        border: none;
        border-radius: 10px;
        padding: 0.65rem 1.25rem;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .cp-export-btn:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35); }

    /* Table header universal search */
    .cp-table-search { position: relative; width: 300px; max-width: 100%; }
    .cp-table-search-icon {
        position: absolute;
        top: 50%;
        left: 0.9rem;
        transform: translateY(-50%);
        color: #9aa3b5;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .cp-search-input {
        border: 1.5px solid #e2e6f0;
        border-radius: 10px;
        padding: 0.6rem 0.9rem 0.6rem 2.4rem;
        font-size: 0.9rem;
        transition: border-color .25s ease, box-shadow .25s ease;
    }
    .cp-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
    }

    @media (max-width: 767.98px) {
        .cp-table-search { width: 100%; margin-top: 0.75rem; }
        .cp-toolbar { flex-direction: column; align-items: stretch; }
        .cp-stat { justify-content: flex-start; }
        .cp-actions { flex-direction: column; align-items: stretch; }
        .cp-export-group { flex-direction: column; align-items: stretch; }
        .cp-select { width: 100%; }
        .cp-import-btn, .cp-export-btn { width: 100%; }
    }

    .modern-table { width: 100% !important; table-layout: auto; margin: 0; }
    .modern-table thead th { white-space: nowrap; vertical-align: middle; position: relative; }
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        max-width: 250px;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .modern-table tbody tr { transition: all 0.3s ease; }
    .modern-table tbody tr:hover {
        background: linear-gradient(to right, #f8f9ff 0%, #ffffff 100%);
        box-shadow: 0 5px 15px rgba(0, 74, 147, 0.1);
    }

    .card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; }
    .card-body { overflow-x: auto; }

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
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
        color: white !important;
        border: none;
    }

    .dataTables_wrapper { width: 100%; padding: 1.5rem; }
    .dataTables_wrapper table { margin: 0 !important; }

    .badge { padding: 0.6rem 1.2rem; font-weight: 700; letter-spacing: 0.5px; border-radius: 50px; font-size: 0.75rem; }
    .alert { border-radius: 15px; border: none; padding: 1.25rem 1.5rem; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); }

    .modal-content { border-radius: 20px; overflow: hidden; border: none; }
    .modal-header { border-bottom: none; position: relative; }
    .modal-footer { border-top: 1px solid #e9ecef; padding: 1.5rem; }
    .modal-body { max-height: 70vh; overflow-y: auto; }

    .opacity-50 { opacity: 0.5; }

    @media (max-width: 992px) {
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .modern-table { min-width: 700px; }
    }
</style>
@endpush
