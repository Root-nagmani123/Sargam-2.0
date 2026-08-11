@extends('admin.layouts.master')
@section('title', 'Vehicle Types - Security Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Vehicle Types']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Vehicle Types</h4>
                <a href="{{ route('admin.security.vehicle_type.create') }}" class="btn btn-primary" id="openCreateVehicleType">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Vehicle Type
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table mb-0" id="vehicleTypeTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Vehicle Type</th>
                            <th>Description</th>
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

<!-- Modal for Create/Edit -->
<div class="modal fade" id="vehicleTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" id="vehicleTypeModalContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Open create modal
    $('#openCreateVehicleType').on('click', function(e) {
        e.preventDefault();
        $.get($(this).attr('href'), function(data) {
            $('#vehicleTypeModalContent').html(data);
            $('#vehicleTypeModal').modal('show');
        });
    });

    var vehicleTypeTable = $('#vehicleTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.security.vehicle_type.index') }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'vehicle_type', name: 'vehicle_type' },
            { data: 'description', name: 'description', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            search: 'Search vehicle types:',
            zeroRecords: 'No Vehicle Types found.',
            emptyTable: 'No Vehicle Types found.'
        }
    });

    // Status toggle (delegated: rows are loaded via AJAX)
    $('#vehicleTypeTable').on('change', '.status-toggle', function() {
        const url = $(this).data('url');
        $.post(url, {
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if(response.success) {
                toastr.success('Status updated successfully');
                vehicleTypeTable.ajax.reload(null, false);
            }
        });
    });
});
</script>
@endpush
