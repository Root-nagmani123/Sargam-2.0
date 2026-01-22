@extends('admin.layouts.master')
@section('title', 'Apply for Vehicle Pass')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <h4 class="mb-0">Apply for Vehicle Pass</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.security.vehicle_pass.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_id_card" class="form-label">Employee ID Card</label>
                            <input type="text" class="form-control @error('employee_id_card') is-invalid @enderror" 
                                id="employee_id_card" name="employee_id_card" value="{{ old('employee_id_card') }}">
                            @error('employee_id_card')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="emp_master_pk" class="form-label">Employee</label>
                            <select class="form-select @error('emp_master_pk') is-invalid @enderror" 
                                id="emp_master_pk" name="emp_master_pk">
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->pk }}" {{ old('emp_master_pk') == $emp->pk ? 'selected' : '' }}>
                                        {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->emp_id }})
                                    </option>
                                @endforeach
                            </select>
                            @error('emp_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('vehicle_type') is-invalid @enderror" 
                                id="vehicle_type" name="vehicle_type" required>
                                <option value="">Select Vehicle Type</option>
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->pk }}" {{ old('vehicle_type') == $vt->pk ? 'selected' : '' }}>
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
                            <label for="vehicle_no" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('vehicle_no') is-invalid @enderror" 
                                id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no') }}" required>
                            @error('vehicle_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="veh_card_valid_from" class="form-label">Valid From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('veh_card_valid_from') is-invalid @enderror" 
                                id="veh_card_valid_from" name="veh_card_valid_from" value="{{ old('veh_card_valid_from') }}" required>
                            @error('veh_card_valid_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vech_card_valid_to" class="form-label">Valid To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('vech_card_valid_to') is-invalid @enderror" 
                                id="vech_card_valid_to" name="vech_card_valid_to" value="{{ old('vech_card_valid_to') }}" required>
                            @error('vech_card_valid_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gov_veh" class="form-label">Vehicle Ownership <span class="text-danger">*</span></label>
                            <select class="form-select @error('gov_veh') is-invalid @enderror" 
                                id="gov_veh" name="gov_veh" required>
                                <option value="">Select Type</option>
                                <option value="0" {{ old('gov_veh') == '0' ? 'selected' : '' }}>Private</option>
                                <option value="1" {{ old('gov_veh') == '1' ? 'selected' : '' }}>Government</option>
                            </select>
                            @error('gov_veh')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="doc_upload" class="form-label">Upload Document</label>
                            <input type="file" class="form-control @error('doc_upload') is-invalid @enderror" 
                                id="doc_upload" name="doc_upload" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Allowed: PDF, JPG, JPEG, PNG (Max: 2MB)</small>
                            @error('doc_upload')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">save</i>
                        Submit Application
                    </button>
                    <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
