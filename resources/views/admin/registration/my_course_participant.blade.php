@extends('admin.layouts.master')

@section('title', 'My Course Participant')

@section('setup_content')
    <div class="container-fluid py-4" style="background-color: #f8f9fa;">
        <div class="mb-4">
            <x-breadcrum title="My Course Participant" />
        </div>
        <x-session_message />

        {{-- Counts + Import + Export --}}
        <div class="card shadow-lg mb-4 border-0 animate-fade-in" style="border-left: 5px solid #004a93; border-radius: 15px;">
            <div class="card-header bg-gradient-primary py-4" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%); border-radius: 15px 15px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3">
                        <i class="fas fa-users fa-lg text-white"></i>
                    </div>
                    <h5 class="mb-0 text-white fw-bold">Course Participants</h5>
                </div>
            </div>
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
                <div class="row g-4">
                    <!-- Total Count -->
                    <div class="col-lg-4 col-md-12">
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

                    <!-- Import Button -->
                    <div class="col-lg-4 col-md-6">
                        <div class="action-card h-100 d-flex flex-column">
                            <label class="form-label fw-bold text-dark mb-3" style="font-size: 0.85rem;">
                                <i class="fas fa-file-import me-2 text-primary"></i>IMPORT
                            </label>
                            <button type="button" class="btn btn-import w-100 flex-grow-1" data-bs-toggle="modal"
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

                    <!-- Export Section -->
                    <div class="col-lg-4 col-md-6">
                        <form method="GET" action="{{ route('my.course.participant.export') }}" id="exportForm" class="h-100">
                            <div class="action-card h-100 d-flex flex-column">
                                <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-file-export me-2 text-success"></i>EXPORT (SELECT FORMAT)
                                </label>
                                <select name="format" class="form-select shadow-sm custom-select mb-3" required id="exportFormat">
                                    <option value="">Choose export type...</option>
                                    <option value="pdf">📄 PDF Document</option>
                                    <option value="xlsx">📊 Excel Spreadsheet</option>
                                    <option value="csv">📝 CSV File</option>
                                </select>
                                <button type="submit" class="btn btn-export w-100 flex-grow-1" id="exportBtn">
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
        </div> <!-- End of top card -->

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
                    <div>
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
                                <th class="fw-bold" style="padding: 1rem;">user_name</th>
                                <th class="fw-bold" style="padding: 1rem;">Name</th>
                                <th class="fw-bold" style="padding: 1rem;">ot code</th>
                                <th class="fw-bold" style="padding: 1rem;">email_id</th>
                                <th class="fw-bold" style="padding: 1rem;">mobile no</th>
                                <th class="fw-bold" style="padding: 1rem;">cadre</th>
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
                { data: 'cadre', name: 'cadre' }
            ],
            columnDefs: [
                { targets: [0], className: 'text-center' }
            ],
            drawCallback: function(settings) {
                let api = this.api();
                $('#filteredCount').text(api.page.info().recordsTotal);
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

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
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

    .stats-icon { font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.9; }
    .stats-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 0.5rem; }
    .stats-value { font-size: 2.5rem; font-weight: 700; line-height: 1; }
    .counter { display: inline-block; transition: all 0.3s ease; }

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

    .btn-import, .btn-export {
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

    .btn-import { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-export { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }

    .btn-import:hover, .btn-export:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
    }

    .btn-icon { font-size: 1.5rem; display: flex; align-items: center; justify-content: center; }
    .btn-text { text-align: left; flex: 1; }
    .btn-title { font-size: 1rem; font-weight: 700; display: block; margin-bottom: 0.25rem; }
    .btn-subtitle { font-size: 0.75rem; opacity: 0.9; font-weight: 400; }

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
