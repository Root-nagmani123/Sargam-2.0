@extends('admin.layouts.master')
@section('title', 'Duplicate / Extended Vehicle Pass - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Duplicate / Extended Vehicle Pass"></x-breadcrum>

    <form action="{{ route('admin.security.duplicate_vehicle_pass.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="dupVehPassForm" novalidate>
        @csrf

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Request details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="vehicle_number" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" value="{{ old('vehicle_number') }}" placeholder="Enter Vehicle Number" required>
                        <small class="text-muted d-block mt-1">
                            <i class="material-icons material-symbols-rounded" style="font-size:14px;vertical-align:middle;">info</i>
                            Press Tab or click outside the field to auto-fill details
                        </small>
                        @error('vehicle_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_pass_no" class="form-label">Vehicle Pass No: <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_pass_no" id="vehicle_pass_no" class="form-control" value="{{ old('vehicle_pass_no') }}" placeholder="Vehicle Pass Number" required>
                        @error('vehicle_pass_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="id_card_number" class="form-label">Id Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="id_card_number" id="id_card_number" class="form-control" value="{{ old('id_card_number') }}" placeholder="Enter Id Card Number">
                        @error('id_card_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="employee_name_display" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="employee_name_display" class="form-control" value="{{ old('employee_name_display') }}" placeholder="Enter Employee Name" readonly>
                        <input type="hidden" name="emp_master_pk" id="emp_master_pk" value="{{ old('emp_master_pk') }}" required>
                        @error('emp_master_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation</label>
                        <input type="text" id="designation" class="form-control bg-light" placeholder="Auto-filled from employee" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="department">Department</label>
                        <input type="text" id="department" class="form-control bg-light" placeholder="Auto-filled from employee" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach($vehicleTypes as $vt)
                                <option value="{{ $vt->pk }}" {{ (string)old('vehicle_type') === (string)$vt->pk ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                     <div class="col-6">
                        <label for="reason_for_duplicate" class="form-label">Reason For Duplicate Card <span class="text-danger">*</span></label>
                        <p class="small text-muted mb-1">Enter Reason For Duplicate Card</p>
                        <select name="reason_for_duplicate" id="reason_for_duplicate" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach(\App\Models\VehiclePassDuplicateApplyTwfw::reasonOptions() as $val => $label)
                                <option value="{{ $val }}" {{ old('reason_for_duplicate') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason_for_duplicate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="doc_upload" class="form-label">Upload Document</label>
                        <input type="file" name="doc_upload" id="doc_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block">Allowed: PDF, JPG, PNG. Max size: 2 MB</small>
                        @error('doc_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        @error('end_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                   
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:20px;">send</i>
                        Send
                    </button>
                    <a href="{{ route('admin.security.duplicate_vehicle_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:20px;">close</i>
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function() {
    var empMasterPk = document.getElementById('emp_master_pk');
    var empNameDisplay = document.getElementById('employee_name_display');
    var designation = document.getElementById('designation');
    var department = document.getElementById('department');
    var idCard = document.getElementById('id_card_number');
    var vehicleNumberInput = document.getElementById('vehicle_number');
    var vehiclePassNo = document.getElementById('vehicle_pass_no');
    var vehicleType = document.getElementById('vehicle_type');
    var startDate = document.getElementById('start_date');
    var endDate = document.getElementById('end_date');
    var reasonForDuplicate = document.getElementById('reason_for_duplicate');

    // Handle vehicle number change for auto-fill
    if (vehicleNumberInput) {
        vehicleNumberInput.addEventListener('blur', function() {
            var vehicleNo = this.value.trim();
            if (!vehicleNo) {
                return;
            }

            // Fetch vehicle details via API
            fetch('{{ route("admin.security.duplicate_vehicle_pass.api.vehicle_details") }}?vehicle_number=' + encodeURIComponent(vehicleNo))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        var vehData = data.data;
                        
                        // Auto-fill vehicle pass number
                        if (vehData.vehicle_pass_no && !vehiclePassNo.value) {
                            vehiclePassNo.value = vehData.vehicle_pass_no;
                        }

                        // Auto-fill vehicle type
                        if (vehData.vehicle_type && !vehicleType.value) {
                            vehicleType.value = vehData.vehicle_type;
                        }

                        // Auto-fill employee name and PK
                        if (vehData.emp_master_pk && !empMasterPk.value) {
                            empMasterPk.value = vehData.emp_master_pk;
                        }
                        if (vehData.employee_name && !empNameDisplay.value) {
                            empNameDisplay.value = vehData.employee_name;
                        }

                        // Auto-fill designation
                        if (vehData.designation && !designation.value) {
                            designation.value = vehData.designation;
                        }

                        // Auto-fill department
                        if (vehData.department && !department.value) {
                            department.value = vehData.department;
                        }

                        // Auto-fill ID card number
                        if (vehData.id_card_number && !idCard.value) {
                            idCard.value = vehData.id_card_number;
                        }

                        // Auto-fill dates
                        if (vehData.start_date && !startDate.value) {
                            startDate.value = vehData.start_date;
                        }

                        if (vehData.end_date && !endDate.value) {
                            endDate.value = vehData.end_date;
                        }

                        // Show success message
                        showNotification('Vehicle details loaded successfully!', 'success');
                    } else {
                        showNotification(data.message || 'Vehicle not found.', 'warning');
                    }
                })
                .catch(error => {
                    console.error('Error fetching vehicle details:', error);
                    showNotification('Error loading vehicle details.', 'danger');
                });
        });
    }

    // No need for fillFromEmployee since it's now a text input
    // Employee details are handled by vehicle autofill

    document.getElementById('dupVehPassForm')?.addEventListener('submit', function() {
        var end = document.getElementById('end_date').value;
        var start = document.getElementById('start_date').value;
        if (end && start && new Date(end) < new Date(start)) {
            alert('End Date must be on or after Start Date.');
            return false;
        }
        return true;
    });

    // Helper function to show notifications
    function showNotification(message, type = 'info') {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        
        var container = document.querySelector('.container-fluid');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    }
})();
</script>
@endpush
@endsection
