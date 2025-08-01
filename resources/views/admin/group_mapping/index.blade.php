@extends('admin.layouts.master')

@section('title', 'Group Name Mapping - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
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
                                    <iconify-icon icon="ep:circle-plus-filled" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Add Group Mapping
                                </a>
                                <!-- Import Excel Button (opens modal) -->
                                <button type="button" class="btn btn-success d-flex align-items-center"
                                    data-bs-toggle="modal" data-bs-target="#importModal">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Import Excel
                                </button>
                                <a href="{{ route('group.mapping.export.student.list') }}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <iconify-icon icon="material-symbols:sim-card-download-rounded" width="24" height="24"></iconify-icon> Export Excel
                                </a>
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
                        <div class="modal fade modal" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg"> <!-- modal-lg for wider layout -->
                            <div class="modal-content">
                                
                                <div class="modal-header">
                                <h5 class="modal-title" id="studentDetailsModalLabel">Student Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                
                                <div class="modal-body">
                                <!-- You can populate this with student details dynamically -->
                                <div id="studentDetailsContent">
                                    <p>Loading student details...</p>
                                </div>
                                </div>
                                
                                <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
@endpush

