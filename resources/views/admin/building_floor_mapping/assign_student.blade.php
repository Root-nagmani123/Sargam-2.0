@extends('admin.layouts.master')

@section('title', 'Hostel Building Assign Student')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Hostel Building Assign Student" />
        <x-session_message />

        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-6">
                                <h4>Hostel Building Assign Student</h4>
                            </div>
                            <div class="col-6 d-flex justify-content-end gap-2">
                                {{-- <button type="button" class="btn btn-success d-flex align-items-center"
                                    data-bs-toggle="modal" data-bs-target="#importModal">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Import Excel
                                </button> --}}
                                <a href="{{ route('hostel.building.map.import') }}" class="btn btn-success d-flex align-items-center">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Import Excel
                                </a>
                                <a href="{{ route('hostel.building.map.export') }}" class="btn btn-info d-flex align-items-center">
                                    <iconify-icon icon="mdi:file-excel" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Export Excel
                                </a>
                            </div>
                        </div>
                        <hr>
                        {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>

    <div class="modal fade modal-xl" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">

        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import Excel File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Select Excel File</label>
                            <input type="file" name="file" id="importFile" class="form-control" accept=".xlsx, .xls, .csv"
                                required>
                            <small class="text-muted">Allowed: .xlsx, .xls, .csv | Max ~500 MB</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="upload_import_hostel_mapping_to_student">
                            <i class="mdi mdi-upload"></i> Upload & Import
                        </button>
                        <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}" class="btn btn-info"
                            download>
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

@endsection
@push('scripts')
{!! $dataTable->scripts() !!}
@endpush