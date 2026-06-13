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
                    <tbody>
                        @forelse($vehicleTypes as $index => $vt)
                            <tr data-pk="{{ $vt->pk }}">
                                <td>{{ $vehicleTypes->firstItem() + $index }}</td>
                                <td>{{ $vt->vehicle_type }}</td>
                                <td>{{ $vt->description ?? '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.vehicle_type.edit', encrypt($vt->pk)) }}" class="text-success" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.security.vehicle_type.delete', encrypt($vt->pk)) }}" method="POST" onsubmit="return confirm('Delete this Vehicle Type?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-url="{{ route('admin.security.vehicle_type.toggle.status', encrypt($vt->pk)) }}"
                                            {{ $vt->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No Vehicle Types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $vehicleTypes->links() }}
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

    // Status toggle
    $('.status-toggle').on('change', function() {
        const url = $(this).data('url');
        $.post(url, {
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if(response.success) {
                toastr.success('Status updated successfully');
            }
        });
    });
});
</script>
@endpush
