@extends('admin.layouts.master')
@section('title', 'Register Visitor - Security Management')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Register New Visitor</h4>
                <a href="{{ route('admin.security.visitor_pass.index') }}" class="btn btn-secondary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Please fill in the visitor pass details below.</p>
            
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

            <form action="{{ route('admin.security.visitor_pass.store') }}" method="POST" enctype="multipart/form-data" id="visitorPassForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-primary">Visitor Details</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="visitor_names" class="form-label">
                                Visitor Name(s) <span class="text-danger">*</span>
                            </label>
                            <div id="visitorNamesContainer">
                                <div class="input-group mb-2">
                                    <input type="text" name="visitor_names[]" class="form-control" 
                                        placeholder="Enter visitor name" value="{{ old('visitor_names.0') }}" required>
                                    <button type="button" class="btn btn-success" id="addVisitorBtn">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">add</i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Add one or more visitor names</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company" class="form-label">Company/Organization</label>
                            <input type="text" name="company" id="company" class="form-control" 
                                value="{{ old('company') }}" placeholder="Enter company name" maxlength="255">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="mobile_number" class="form-label">
                                Mobile Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="mobile_number" id="mobile_number" 
                                class="form-control @error('mobile_number') is-invalid @enderror" 
                                value="{{ old('mobile_number') }}" 
                                placeholder="Enter mobile number" required maxlength="20">
                            @error('mobile_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="2" 
                                placeholder="Enter address">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="identity_card" class="form-label">Identity Card Type</label>
                            <select name="identity_card" id="identity_card" class="form-select">
                                <option value="">---Select---</option>
                                <option value="Aadhar Card" {{ old('identity_card') == 'Aadhar Card' ? 'selected' : '' }}>Aadhar Card</option>
                                <option value="PAN Card" {{ old('identity_card') == 'PAN Card' ? 'selected' : '' }}>PAN Card</option>
                                <option value="Driving License" {{ old('identity_card') == 'Driving License' ? 'selected' : '' }}>Driving License</option>
                                <option value="Voter ID" {{ old('identity_card') == 'Voter ID' ? 'selected' : '' }}>Voter ID</option>
                                <option value="Passport" {{ old('identity_card') == 'Passport' ? 'selected' : '' }}>Passport</option>
                                <option value="Other" {{ old('identity_card') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_no" class="form-label">ID Number</label>
                            <input type="text" name="id_no" id="id_no" class="form-control" 
                                value="{{ old('id_no') }}" placeholder="Enter ID number" maxlength="50">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3 mt-2">
                        <label class="form-label fw-bold text-primary">Visit Details</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_master_pk" class="form-label">
                                Whom to Meet <span class="text-danger">*</span>
                            </label>
                            <select name="employee_master_pk" id="employee_master_pk" 
                                class="form-select @error('employee_master_pk') is-invalid @enderror" required>
                                <option value="">---Select Employee---</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->pk }}" {{ old('employee_master_pk') == $emp->pk ? 'selected' : '' }}>
                                        {{ $emp->emp_name }} ({{ $emp->emp_code ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="purpose" class="form-label">
                                Purpose of Visit <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="purpose" id="purpose" 
                                class="form-control @error('purpose') is-invalid @enderror" 
                                value="{{ old('purpose') }}" 
                                placeholder="Enter purpose of visit" required>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="in_time" class="form-label">
                                In Time <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="in_time" id="in_time" 
                                class="form-control @error('in_time') is-invalid @enderror" 
                                value="{{ old('in_time', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('in_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="valid_for_days" class="form-label">
                                Valid For (Days) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="valid_for_days" id="valid_for_days" 
                                class="form-control @error('valid_for_days') is-invalid @enderror" 
                                value="{{ old('valid_for_days', 1) }}" 
                                min="1" max="30" required>
                            @error('valid_for_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Number of days the pass is valid (max 30)</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" 
                                value="{{ old('vehicle_number') }}" 
                                placeholder="Enter vehicle number" maxlength="50">
                            <small class="form-text text-muted">Optional: If visitor has vehicle</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="upload_path" class="form-label">Upload Document</label>
                            <input type="file" name="upload_path" id="upload_path" 
                                class="form-control @error('upload_path') is-invalid @enderror" 
                                accept=".pdf,.jpg,.jpeg,.png">
                            @error('upload_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional: Upload ID proof or authorization letter (PDF, JPG, PNG - Max 2MB)</small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">save</i>
                            Register Visitor
                        </button>
                        <a href="{{ route('admin.security.visitor_pass.index') }}" class="btn btn-secondary">
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
    let visitorCount = 1;
    
    // Add more visitor names
    $('#addVisitorBtn').on('click', function() {
        if (visitorCount < 10) { // Limit to 10 visitors
            const html = `
                <div class="input-group mb-2 visitor-name-row">
                    <input type="text" name="visitor_names[]" class="form-control" 
                        placeholder="Enter visitor name" required>
                    <button type="button" class="btn btn-danger remove-visitor-btn">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">remove</i>
                    </button>
                </div>
            `;
            $('#visitorNamesContainer').append(html);
            visitorCount++;
        } else {
            toastr.warning('Maximum 10 visitors can be added');
        }
    });
    
    // Remove visitor name row
    $(document).on('click', '.remove-visitor-btn', function() {
        $(this).closest('.visitor-name-row').remove();
        visitorCount--;
    });
    
    // Form validation
    $('#visitorPassForm').on('submit', function(e) {
        const validDays = parseInt($('#valid_for_days').val());
        if (validDays < 1 || validDays > 30) {
            e.preventDefault();
            toastr.error('Valid for days must be between 1 and 30');
            return false;
        }
    });
});
</script>
@endpush
