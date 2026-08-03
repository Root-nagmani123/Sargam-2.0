@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Configuration - Security Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Vehicle Pass Configuration']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Vehicle Pass Configuration</h4>
                <a href="{{ route('admin.security.vehicle_pass_config.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Configuration
                </a>
            </div>
            
            <div class="alert alert-info">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">info</i>
                This page displays all vehicle pass configurations and allows you to manage them.
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped mb-0" id="vehiclePassConfigTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Vehicle Type</th>
                            <th style="width:120px;">Charges (₹)</th>
                            <th style="width:140px;">Start Counter</th>
                            <th style="width:180px;">Preview</th>
                            <th style="width:160px;">Actions</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var vehiclePassConfigTable = $('#vehiclePassConfigTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.security.vehicle_pass_config.index') }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'vehicle_type_name', name: 'vehicle_type_name', orderable: false, searchable: false },
            { data: 'charges', name: 'charges', className: 'text-start' },
            { data: 'start_counter', name: 'start_counter', className: 'text-center' },
            { data: 'preview', name: 'preview', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            search: 'Search configurations:',
            zeroRecords: 'No configurations found. Please add one.',
            emptyTable: 'No configurations found. Please add one.'
        }
    });

    // Status toggle (delegated: rows are loaded via AJAX)
    $('#vehiclePassConfigTable').on('change', '.status-toggle', function() {
        const url = $(this).data('url');
        const checkbox = $(this);
        const isChecked = checkbox.is(':checked');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                status: isChecked ? 1 : 0
            },
            success: function(response) {
                toastr.success(response.message || 'Status updated successfully');
                vehiclePassConfigTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                checkbox.prop('checked', !isChecked);
                toastr.error(xhr.responseJSON?.message || 'Error updating status');
            }
        });
    });
});
</script>
@endpush
