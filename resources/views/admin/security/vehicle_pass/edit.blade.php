@extends('admin.layouts.master')
@section('title', 'Edit Vehicle Pass Application - Security Management')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Edit Vehicle Pass Application - {{ $vehiclePass->vehicle_req_id }}</h4>
                <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Please update the vehicle pass application details below. Note: Only pending applications can be edited.</p>
            
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

            <form action="{{ route('admin.security.vehicle_pass.update', encrypt($vehiclePass->vehicle_tw_pk)) }}" method="POST" enctype="multipart/form-data" id="vehiclePassForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-primary">Vehicle Details</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">
                                Vehicle Type <span class="text-danger">*</span>
                            </label>
                            <select name="vehicle_type" id="vehicle_type" 
                                class="form-select @error('vehicle_type') is-invalid @enderror" required>
                                <option value="">---Select Vehicle Type---</option>
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->pk }}" 
                                        {{ (old('vehicle_type', $vehiclePass->vehicle_type) == $vt->pk) ? 'selected' : '' }}>
                                        {{ $vt->vehicle_type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_no" class="form-label">
                                Vehicle Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="vehicle_no" id="vehicle_no" 
                                class="form-control @error('vehicle_no') is-invalid @enderror" 
                                value="{{ old('vehicle_no', $vehiclePass->vehicle_no) }}" 
                                placeholder="Enter vehicle registration number" required maxlength="50">
                            @error('vehicle_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_id_card" class="form-label">Employee ID Card</label>
                            <input type="text" name="employee_id_card" id="employee_id_card" 
                                class="form-control" 
                                value="{{ old('employee_id_card', $vehiclePass->employee_id_card) }}" 
                                placeholder="Enter employee ID card number" maxlength="100">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gov_veh" class="form-label">
                                Government Vehicle <span class="text-danger">*</span>
                            </label>
                            <select name="gov_veh" id="gov_veh" class="form-select @error('gov_veh') is-invalid @enderror" required>
                                <option value="">---Select---</option>
                                <option value="1" {{ old('gov_veh', $vehiclePass->gov_veh) == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('gov_veh', $vehiclePass->gov_veh) == '0' ? 'selected' : '' }}>No</option>
                            </select>
                            @error('gov_veh')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3 mt-2">
                        <label class="form-label fw-bold text-primary">Validity Period</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="veh_card_valid_from" class="form-label">
                                Valid From <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="veh_card_valid_from" id="veh_card_valid_from" 
                                class="form-control @error('veh_card_valid_from') is-invalid @enderror" 
                                value="{{ old('veh_card_valid_from', $vehiclePass->veh_card_valid_from ? \Carbon\Carbon::parse($vehiclePass->veh_card_valid_from)->format('Y-m-d') : '') }}" 
                                required>
                            @error('veh_card_valid_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vech_card_valid_to" class="form-label">
                                Valid To <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="vech_card_valid_to" id="vech_card_valid_to" 
                                class="form-control @error('vech_card_valid_to') is-invalid @enderror" 
                                value="{{ old('vech_card_valid_to', $vehiclePass->vech_card_valid_to ? \Carbon\Carbon::parse($vehiclePass->vech_card_valid_to)->format('Y-m-d') : '') }}" 
                                required>
                            @error('vech_card_valid_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3 mt-2">
                        <label class="form-label fw-bold text-primary">Additional Information</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="doc_upload" class="form-label">Upload Document</label>
                            @if($vehiclePass->doc_upload)
                                <div class="mb-2">
                                    <a href="{{ Storage::url($vehiclePass->doc_upload) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">description</i>
                                        View Current Document
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="doc_upload" id="doc_upload" 
                                class="form-control @error('doc_upload') is-invalid @enderror" 
                                accept=".pdf,.jpg,.jpeg,.png">
                            @error('doc_upload')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional: Upload supporting document (PDF, JPG, PNG - Max 2MB)</small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">update</i>
                            Update Application
                        </button>
                        <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
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
    // Validate valid_to date is after valid_from
    $('#vech_card_valid_to').on('change', function() {
        const validFrom = new Date($('#veh_card_valid_from').val());
        const validTo = new Date($(this).val());
        
        if (validTo < validFrom) {
            toastr.error('Valid To date must be after or equal to Valid From date');
            $(this).val('');
        }
    });

    // Form validation
    $('#vehiclePassForm').on('submit', function(e) {
        const validFrom = new Date($('#veh_card_valid_from').val());
        const validTo = new Date($('#vech_card_valid_to').val());
        
        if (validTo < validFrom) {
            e.preventDefault();
            toastr.error('Valid To date must be after or equal to Valid From date');
            return false;
        }
    });
});
</script>
@endpush
