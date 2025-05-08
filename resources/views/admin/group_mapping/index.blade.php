@extends('admin.layouts.master')

@section('title', 'Group Mapping')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Group Mapping" />
        <x-session_message />

        <div class="datatables">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row mb-3">
                            <div class="col-6">
                                <h4>Group Mapping</h4>
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
                                        </div>
                                    </form>
                                    <div id="importErrors" class="alert  d-none">
                                        <p class="text-danger text-center h4">Below are the validation errors</p>
                                        <table class="table table-sm table-bordered mb-0 table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Row</th>
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
                        <!-- End Modal -->

                        <hr>

                        <div id="zero_config_wrapper" class="dataTables_wrapper">
                            <table id="zero_config"
                                class="table table-striped table-bordered text-nowrap align-middle dataTable"
                                aria-describedby="zero_config_info">
                                <thead>
                                    <!-- start row -->
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Group Name</th>
                                        <th>Action</th>
                                    </tr>
                                    <!-- end row -->
                                </thead>
                                <tbody>
                                    @if (!empty($groupTypeMaster) && count($groupTypeMaster) > 0)
                                        @foreach ($groupTypeMaster as $groupType)
                                            <tr class="odd">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $groupType->group_name ?? 'N/A' }}</td>
                                                <td>
                                                    <a 
                                                        href="{{ route('group.mapping.edit', 
                                                        ['id' => encrypt($groupType->pk)]) }}"
                                                        class="btn btn-primary btn-sm"
                                                    >Edit</a>
                                                    {{-- <form 
                                                        action="{{ route('master.faculty.expertise.delete', 
                                                        ['id' => encrypt($groupType->pk)]) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="event.preventDefault(); 
                                                            if(confirm('Are you sure you want to delete this record?')) {
                                                                this.closest('form').submit();
                                                            }">
                                                            Delete
                                                        </button>
                                                    </form> --}}
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="group_type_master_course_master_map" data-column="active_inactive" data-id="{{ $groupType->pk }}" {{ $groupType->active_inactive == 1 ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        
                                    @endif
                                   
                                </tbody>
                            </table>
                            
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

