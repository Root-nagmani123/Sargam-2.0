@extends('admin.layouts.master')

@section('title', 'Group Name Mapping - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}
</style>
<div class="container-fluid">

    <x-breadcrum title="Group Name Mapping" />
    <x-session_message />

    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h4>Group Name Mapping</h4>
                        </div>
                        <div class="col-6 d-flex justify-content-end gap-2">
                            <a href="{{ route('group.mapping.create') }}"
                                class="btn btn-primary d-flex align-items-center">
                                <iconify-icon icon="ep:circle-plus-filled" width="1.2em" height="1.2em" class="me-1">
                                </iconify-icon> Add Group Mapping
                            </a>
                            <!-- Import Excel Button (opens modal) -->
                            <button type="button" class="btn btn-success d-flex align-items-center"
                                data-bs-toggle="modal" data-bs-target="#importModal">
                                <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em" class="me-1">
                                </iconify-icon> Import Excel
                            </button>
                            <a href="{{ route('group.mapping.export.student.list') }}"
                                class="btn btn-primary d-flex align-items-center">
                                <iconify-icon icon="material-symbols:sim-card-download-rounded" width="24" height="24">
                                </iconify-icon> Export Excel
                            </a>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 text-end">
                            <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                                aria-label="Group Mapping Status Filter">
                                <button type="button" class="btn btn-success px-4 fw-semibold active"
                                    id="filterGroupActive" aria-pressed="true">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold"
                                    id="filterGroupArchive" aria-pressed="false">
                                    <i class="bi bi-archive me-1"></i> Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Import Excel Modal -->
                    <div class="modal fade modal-xl" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
                        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">

                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="importModalLabel">Import Excel File</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="importFile" class="form-label">Select Excel File</label>
                                            <input type="file" name="file" id="importFile" class="form-control"
                                                accept=".xlsx, .xls, .csv" required>
                                            <small class="text-muted">Allowed: .xlsx, .xls, .csv | Max ~500 MB</small>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-cancel"
                                            data-bs-dismiss="modal">Cancel</button>
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
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
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

                                    <!-- Student Info Dynamic Section -->
                                    <div id="studentDetailsContent" class="p-3 rounded-3 bg-light-subtle border">
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

                                                <div id="bulkMessageAlert" class="alert d-none mb-3" role="alert"></div>

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

                                                <!-- Action Buttons -->
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

                                <!-- Footer -->
                                <div class="modal-footer border-0 d-flex justify-content-between align-items-center">
                                    <div class="text-muted small" id="selectedOtCount" aria-live="polite">
                                        0 OT(s) selected
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary" id="toggleBulkMessage">
                                            <i class="bi bi-send-check me-1"></i> Send SMS / Email
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                            aria-label="Close Modal">
                                            Close
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>



                    <hr>
                    {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).on('preXhr.dt', '#group-mapping-table', function(e, settings, data) {
    data.status_filter = window.groupMappingCurrentFilter || 'active';
});

$(document).ready(function() {
    window.groupMappingCurrentFilter = 'active';

    setTimeout(function() {
        var table = $('#group-mapping-table').DataTable();

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

        // Ensure initial styling reflects default filter
        setActiveButton($('#filterGroupActive'));
    }, 150);
});
</script>
@endpush