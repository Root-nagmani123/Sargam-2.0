@extends('admin.layouts.master')
@section('title', 'Add Vehicle Pass Configuration - Security Management')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Add Vehicle Pass Configuration</h4>
                <a href="{{ route('admin.security.vehicle_pass_config.index') }}" class="btn btn-secondary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Please add the vehicle pass configuration details below.</p>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.security.vehicle_pass_config.store') }}" method="POST" id="vehiclePassConfigForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sec_vehicle_type_pk" class="form-label">
                                Vehicle Type <span class="text-danger">*</span>
                            </label>
                            <select name="sec_vehicle_type_pk" id="sec_vehicle_type_pk" class="form-select @error('sec_vehicle_type_pk') is-invalid @enderror" required>
                                <option value="">---Select Vehicle Type---</option>
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->pk }}" {{ old('sec_vehicle_type_pk') == $vt->pk ? 'selected' : '' }}>
                                        {{ $vt->vehicle_type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sec_vehicle_type_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Please select the vehicle type</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="charges" class="form-label">
                                Charges (₹) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="charges" id="charges" 
                                class="form-control @error('charges') is-invalid @enderror" 
                                value="{{ old('charges') }}" 
                                step="0.01" min="0" placeholder="Enter charges" required>
                            @error('charges')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Enter the vehicle pass charges</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_counter" class="form-label">
                                Start Counter <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="start_counter" id="start_counter" 
                                class="form-control @error('start_counter') is-invalid @enderror" 
                                value="{{ old('start_counter', 1) }}" 
                                min="1" placeholder="Enter start counter" required>
                            @error('start_counter')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Enter the starting number for vehicle pass IDs</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="preview" class="form-label">Preview</label>
                            <input type="text" id="preview" class="form-control bg-light" readonly 
                                value="VP{{ now()->format('Ymd') }}0001">
                            <small class="form-text text-muted">Preview of vehicle pass ID format</small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">save</i>
                            Save Configuration
                        </button>
                        <a href="{{ route('admin.security.vehicle_pass_config.index') }}" class="btn btn-secondary">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">cancel</i>
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update preview when start counter changes
    $('#start_counter').on('input', function() {
        const counter = $(this).val() || 1;
        const paddedCounter = String(counter).padStart(4, '0');
        const today = '{{ now()->format("Ymd") }}';
        $('#preview').val('VP' + today + paddedCounter);
    });

    // Form validation
    $('#vehiclePassConfigForm').on('submit', function(e) {
        const charges = parseFloat($('#charges').val());
        const counter = parseInt($('#start_counter').val());
        
        if (charges < 0) {
            e.preventDefault();
            toastr.error('Charges cannot be negative');
            return false;
        }
        
        if (counter < 1) {
            e.preventDefault();
            toastr.error('Start counter must be at least 1');
            return false;
        }
    });
});
</script>
@endpush
