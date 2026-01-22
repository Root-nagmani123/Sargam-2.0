@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Configuration - Security Management')
@section('setup_content')
<div class="container-fluid">
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
                    <tbody>
                        @forelse($configs as $index => $config)
                            <tr data-pk="{{ $config->pk }}">
                                <td>{{ $configs->firstItem() + $index }}</td>
                                <td>
                                    @if($config->vehicleType)
                                        {{ $config->vehicleType->vehicle_type }}
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>{{ number_format($config->charges, 2) }}</td>
                                <td class="text-center">{{ $config->start_counter }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        VP{{ now()->format('Ymd') }}{{ str_pad($config->start_counter, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.vehicle_pass_config.edit', encrypt($config->pk)) }}" class="text-success" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.security.vehicle_pass_config.delete', encrypt($config->pk)) }}" method="POST" onsubmit="return confirm('Delete this configuration?')">
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
                                            data-url="{{ route('admin.security.vehicle_pass_config.toggle.status', encrypt($config->pk)) }}"
                                            {{ $config->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No configurations found. Please add one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $configs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Status toggle
    $('.status-toggle').on('change', function() {
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
